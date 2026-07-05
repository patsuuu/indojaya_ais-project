<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'counts' => []];

// By default sum OT for current month
$start = date('Y-m-01');
$end = date('Y-m-t');

$sql = "SELECT bio_id, SUM(hours) AS total_hours FROM overtime_forms WHERE ot_date BETWEEN ? AND ? GROUP BY bio_id";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $response['error'] = 'Database error: ' . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response['counts'][$row['bio_id']] = floatval($row['total_hours']);
}

$response['success'] = true;

echo json_encode($response);
exit;
?>
