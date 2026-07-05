<?php
require_once 'db_connect.php';

// Default HR credentials
$username = 'hr';
$password = 'hr123';
$email = 'hr@example.com';
$role = 'HR';

$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h2>Creating HR Users Table...</h2>";
echo "Password: " . htmlspecialchars($password) . "<br>";

echo "<pre>";

echo "Creating hr_users table if it does not exist...\n";
$sql = "CREATE TABLE IF NOT EXISTS `hr_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100),
  `role` varchar(20) NOT NULL DEFAULT 'HR',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ hr_users table created successfully\n";
} else {
    echo "❌ Error creating hr_users table: " . $conn->error . "\n";
}

echo "Inserting default HR account if it doesn't already exist...\n";
$check_hr_sql = "SELECT id FROM hr_users WHERE username = ?";
$check_stmt = $conn->prepare($check_hr_sql);

if (!$check_stmt) {
    echo "❌ Prepare failed: " . $conn->error . "\n";
    exit;
}

$check_stmt->bind_param('s', $username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result && $check_result->num_rows > 0) {
    echo "⚠️ HR account already exists for username: " . htmlspecialchars($username) . "\n";
} else {
    $check_stmt->close();

    $insert_sql = "INSERT INTO hr_users (username, password, email, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);

    if (!$stmt) {
        echo "❌ Prepare failed: " . $conn->error . "\n";
        exit;
    }

    $stmt->bind_param('ssss', $username, $hash, $email, $role);

    if ($stmt->execute()) {
        echo "✅ Default HR account created successfully\n";
        echo "Username: " . htmlspecialchars($username) . "\n";
        echo "Password: " . htmlspecialchars($password) . "\n";
    } else {
        echo "❌ Error inserting HR account: " . $stmt->error . "\n";
    }

    $stmt->close();
}

$conn->close();

echo "</pre>";
echo "<p><a href=\"login.php\">Go to Login</a></p>";
