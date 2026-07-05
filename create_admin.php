<?php
require_once 'db_connect.php';

// Generate hash for password 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h2>Creating Admin User...</h2>";
echo "Password: admin123<br>";
echo "Hash: " . $hash . "<br><br>";

// Delete old users table and recreate
$conn->query("DROP TABLE IF EXISTS users");

$sql = "CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100),
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✅ Users table created successfully<br><br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br><br>";
}

// Insert admin user with the newly generated hash
$insert_sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);

if (!$stmt) {
    echo "❌ Prepare failed: " . $conn->error;
    exit;
}

$username = 'admin';
$email = 'admin@example.com';
$role = 'admin';

$stmt->bind_param('ssss', $username, $hash, $email, $role);

if ($stmt->execute()) {
    echo "✅ Admin user created successfully!<br><br>";
    echo "<strong>Login Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>admin123</code><br><br>";
    echo "<a href='login.php'>Go to Login →</a>";
} else {
    echo "❌ Error inserting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>