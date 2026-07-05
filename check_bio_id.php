<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$bio_id = trim($_GET['bio_id'] ?? '');

if ($bio_id === '') {
    echo json_encode(['success' => false, 'message' => 'Bio ID is required.']);
    exit;
}

$sql = "SELECT bio_id, gmail, last_name, first_name, department, account_stage, account, team_leader FROM employees WHERE bio_id = ? AND is_active = 1 LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('s', $bio_id);
$stmt->execute();
$result = $stmt->get_result();

$holiday_off_exists = false;
$has_time_in = false;
$has_time_out = false;
$date = trim($_GET['date'] ?? '');
if ($date !== '' && $result && $result->num_rows === 1) {
    $checkSql = "SELECT time_in, time_out, status FROM records WHERE bio_id = ? AND date = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param('ss', $bio_id, $date);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult && $row = $checkResult->fetch_assoc()) {
            $holiday_off_exists = ($row['status'] === 'holiday_off');
            $has_time_in = !empty($row['time_in']);
            $has_time_out = !empty($row['time_out']);
        }
        $checkStmt->close();
    }
}

if ($result && $result->num_rows === 1) {
    $employee = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'holiday_off_exists' => $holiday_off_exists,
        'has_time_in' => $has_time_in,
        'has_time_out' => $has_time_out,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Bio ID not registered.',
        'holiday_off_exists' => $holiday_off_exists,
        'has_time_in' => $has_time_in,
        'has_time_out' => $has_time_out,
    ]);
}

$stmt->close();
$conn->close();
