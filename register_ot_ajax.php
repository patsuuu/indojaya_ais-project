<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Allow OT requests without an active session after 6:00 PM
$currentHour = (int) date('H');
$allowWithoutSession = $currentHour >= 18;
if (empty($_SESSION['user_id']) && !$allowWithoutSession) {
    http_response_code(401);
    $response['error'] = 'Authentication required. OT requests without login are allowed only after 6:00 PM.';
    echo json_encode($response);
    exit;
}

$response = ['success' => false, 'error' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

$bio_id = trim($_POST['bio_id'] ?? '');
$date = trim($_POST['date'] ?? date('Y-m-d'));
$hours = floatval($_POST['hours'] ?? 0);
$duration_label = trim($_POST['duration_label'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if ($bio_id === '' || $hours <= 0) {
    $response['error'] = 'Bio ID and duration are required.';
    echo json_encode($response);
    exit;
}

// Ensure OT columns exist on records table
$columns = [
    'ot_hours' => 'DECIMAL(5,2) NOT NULL DEFAULT 0',
    'ot_rate' => 'DECIMAL(7,2) NOT NULL DEFAULT 0',
    'ot_pay' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
    'ot_requested' => 'TINYINT(1) NOT NULL DEFAULT 0',
    'ot_reason' => 'TEXT DEFAULT NULL'
];
foreach ($columns as $column => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM records LIKE '$column'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE records ADD COLUMN $column $definition");
    }
}

// Server-side validation: ensure the agent has a Time IN for that date and no OT request exists yet
$checkSql = "SELECT id, time_in, ot_requested, ot_hours, ot_pay FROM records WHERE bio_id = ? AND date = ? LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
if ($checkStmt) {
    $checkStmt->bind_param('ss', $bio_id, $date);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    if ($checkRes && $row = $checkRes->fetch_assoc()) {
        if (empty($row['time_in'])) {
            $response['error'] = 'Cannot request OT before Time IN for that date.';
            echo json_encode($response);
            exit;
        }
        if (!empty($row['ot_requested']) || floatval($row['ot_hours']) > 0 || floatval($row['ot_pay']) > 0) {
            $response['error'] = 'Only one OT request is allowed per day.';
            echo json_encode($response);
            exit;
        }
        $recordId = $row['id'];
    } else {
        $response['error'] = 'No attendance record for that date. Cannot request OT.';
        echo json_encode($response);
        exit;
    }
    $checkStmt->close();
} else {
    $response['error'] = 'Database error while validating attendance.';
    echo json_encode($response);
    exit;
}

// Determine OT rate by weekday/weekend
$dayOfWeek = date('w', strtotime($date));
$otRate = ($dayOfWeek === '0' || $dayOfWeek === '6') ? 25.0 : 95.0;
$otPay = round($hours * $otRate, 2);

$updateSql = "UPDATE records SET ot_hours = ?, ot_rate = ?, ot_pay = ?, ot_requested = 1, ot_reason = ? WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    $response['error'] = 'Database error: ' . $conn->error;
    echo json_encode($response);
    exit;
}
$updateStmt->bind_param('dddsi', $hours, $otRate, $otPay, $reason, $recordId);
if ($updateStmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Overtime request added to the attendance record.';
    $response['ot_hours'] = $hours;
    $response['ot_rate'] = $otRate;
    $response['ot_pay'] = $otPay;

    // Attempt server-side confirmation email so user always receives OT confirmation
    try {
        $empGmail = '';
        $empFirst = '';
        $empStmt = $conn->prepare("SELECT gmail, first_name FROM employees WHERE bio_id = ? LIMIT 1");
        if ($empStmt) {
            $empStmt->bind_param('s', $bio_id);
            $empStmt->execute();
            $empRes = $empStmt->get_result();
            if ($empRes && $erow = $empRes->fetch_assoc()) {
                $empGmail = $erow['gmail'];
                $empFirst = $erow['first_name'];
            }
            $empStmt->close();
        }

        if (!empty($empGmail) && filter_var($empGmail, FILTER_VALIDATE_EMAIL)) {
            $payload = json_encode([
                'gmail' => $empGmail,
                'action' => 'OT',
                'bio_id' => $bio_id,
                'first_name' => $empFirst,
                'date' => $date,
                'hours' => $hours,
                'duration_label' => $duration_label
            ]);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = $protocol . '://' . $host . dirname($_SERVER['REQUEST_URI']) . '/send_email.php';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $curlRes = curl_exec($ch);
            $curlErr = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);

            // Log attempt for debugging
            $logLine = date('Y-m-d H:i:s') . " | OT email to: {$empGmail} | curl_err: {$curlErr} | http_code: " . ($curlInfo['http_code'] ?? 'N/A') . " | response: " . ($curlRes ?? '') . "\n";
            @file_put_contents(__DIR__ . '/ot_email.log', $logLine, FILE_APPEND);

            if ($curlRes) {
                $decoded = json_decode($curlRes, true);
                if (is_array($decoded)) {
                    $response['email_result'] = $decoded;
                } else {
                    $response['email_raw'] = $curlRes;
                }
            } else {
                $response['email_error'] = $curlErr ?: 'No response from send_email.php';
            }
        } else {
            $response['email_error'] = 'No gmail found for bio_id or invalid email.';
        }
    } catch (Exception $e) {
        $response['email_exception'] = $e->getMessage();
    }

} else {
    $response['error'] = 'Failed to save overtime: ' . $updateStmt->error;
}

$updateStmt->close();
$conn->close();

echo json_encode($response);
exit;
?>
