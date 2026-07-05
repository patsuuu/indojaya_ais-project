<?php
// db_connect.php: Database connection script
$host = 'localhost';
$user = 'root';
$password = ''; // Change to your MySQL password
$db = 'attendance_records';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
?>
