<?php
require_once 'db_connect.php';

$username = 'admin';

// Get user from database
$sql = "SELECT id, username, password, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "✅ User found!<br><br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Password Hash: " . $user['password'] . "<br>";
    echo "Role: " . $user['role'] . "<br><br>";
    
    // Test password verification
    $password = 'admin123';
    if (password_verify($password, $user['password'])) {
        echo "✅ Password is CORRECT!<br>";
    } else {
        echo "❌ Password is WRONG!<br>";
        echo "Trying to verify: " . $password . "<br>";
        echo "Against hash: " . $user['password'];
    }
} else {
    echo "❌ User not found in database!<br>";
    echo "Total users: " . $result->num_rows;
}

$stmt->close();
$conn->close();
?>