<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'error' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['error'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

$bio_id = trim($_POST['bio_id'] ?? '');
$gmail = trim($_POST['gmail'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$account_stage = trim($_POST['account_stage'] ?? '');
$account = trim($_POST['account'] ?? '');
$team_leader = trim($_POST['team_leader'] ?? '');

if (empty($bio_id) || empty($gmail) || empty($last_name) || empty($first_name) || empty($department) || empty($account_stage) || empty($account) || empty($team_leader)) {
    $response['error'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
    $response['error'] = 'Please enter a valid Gmail address.';
    echo json_encode($response);
    exit;
}

$check_sql = "SELECT id FROM employees WHERE bio_id = ? LIMIT 1";
$stmt = $conn->prepare($check_sql);
if (!$stmt) {
    $response['error'] = 'Database error: ' . $conn->error;
    echo json_encode($response);
    exit;
}
$stmt->bind_param('s', $bio_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $response['error'] = 'This Bio ID is already registered.';
    echo json_encode($response);
    exit;
}

$insert_sql = "INSERT INTO employees (bio_id, gmail, last_name, first_name, department, account_stage, account, team_leader) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
if (!$insert_stmt) {
    $response['error'] = 'Database error: ' . $conn->error;
    echo json_encode($response);
    exit;
}
$insert_stmt->bind_param('ssssssss', $bio_id, $gmail, $last_name, $first_name, $department, $account_stage, $account, $team_leader);

if ($insert_stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Employee Bio ID registered successfully.';
} else {
    $response['error'] = 'Error saving employee info: ' . $insert_stmt->error;
}

$insert_stmt->close();
$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>
