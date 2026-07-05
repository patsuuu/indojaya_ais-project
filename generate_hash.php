<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password Hash: " . $hash;
echo "<br>";
echo "Copy this hash and use it in your SQL INSERT command";
?>