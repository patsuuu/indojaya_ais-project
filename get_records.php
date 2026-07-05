<?php
require_once 'db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

function ensureOtColumns($conn) {
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
}

ensureOtColumns($conn);

$response = [
    'records' => [],
    'count' => 0,
    'photos_found' => 0,
    'error' => null
];
try {
    $hasPayslipData = false;
    $dataColumnCheck = $conn->query("SHOW COLUMNS FROM records LIKE 'payslip_data'");
    if ($dataColumnCheck && $dataColumnCheck->num_rows > 0) {
        $hasPayslipData = true;
    }

    $sql = "SELECT id, bio_id, gmail, date, last_name, first_name, time_in, time_out, late_in_minutes, late_out_minutes,
            department, account_stage, account, team_leader, direction, photo, status, payslip_file,
            ot_hours, ot_rate, ot_pay, ot_requested, ot_reason";
    if ($hasPayslipData) {
        $sql .= ", payslip_data";
    }
    $sql .= " FROM records 
            ORDER BY date DESC, time_in DESC 
            LIMIT 500";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
       while ($row = $result->fetch_assoc()) {
        $photo_url = '';
        
        if (!empty($row['photo'])) {
            $photo_url = 'uploads/' . htmlspecialchars($row['photo']);
        }
        
        // Check if no time in or time out
        $no_time_in = empty($row['time_in']) ? true : false;
        $no_time_out = empty($row['time_out']) ? true : false;
        
        $response['records'][] = [
            'id' => $row['id'],
            'bio_id' => $row['bio_id'],
            'gmail' => $row['gmail'],
            'date' => $row['date'],
            'last_name' => $row['last_name'],
            'first_name' => $row['first_name'],
            'time_in' => $row['time_in'],
            'time_out' => $row['time_out'],
            'late_in_minutes' => $row['late_in_minutes'],
            'late_out_minutes' => $row['late_out_minutes'],
            'department' => $row['department'],
            'account_stage' => $row['account_stage'],
            'account' => $row['account'],
            'team_leader' => $row['team_leader'],
            'direction' => $row['direction'],
            'status' => $row['status'],
            'ot_hours' => isset($row['ot_hours']) ? floatval($row['ot_hours']) : 0,
            'ot_rate' => isset($row['ot_rate']) ? floatval($row['ot_rate']) : 0,
            'ot_pay' => isset($row['ot_pay']) ? floatval($row['ot_pay']) : 0,
            'ot_requested' => isset($row['ot_requested']) ? boolval($row['ot_requested']) : false,
            'ot_reason' => $row['ot_reason'] ?? null,
            'no_time_in' => $no_time_in,
            'no_time_out' => $no_time_out,
            'photo_url' => $photo_url,
            'payslip_file' => $row['payslip_file'],
            'payslip_data' => $row['payslip_data'] ?? null,
            'payslip_url' => !empty($row['payslip_file']) ? 'uploads/' . htmlspecialchars($row['payslip_file']) : ''
        ];
        
        if (!empty($row['photo'])) {
            $response['photos_found']++;
        }
    }
    
    $response['count'] = count($response['records']);
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>