<?php
$host = 'smtp.mailtrap.io';
$port = 587;
$user = 'YOUR_USERNAME';   // From Mailtrap
$pass = 'YOUR_PASSWORD';   // From Mailtrap

$conn = fsockopen($host, $port, $errno, $errstr, 10);

if (!$conn) {
    echo "❌ Connection failed: $errstr";
} else {
    echo "✅ Connected to Mailtrap!<br>";
    fgets($conn, 1024);
    
    fputs($conn, "EHLO localhost\r\n");
    fgets($conn, 1024);
    
    fputs($conn, "AUTH LOGIN\r\n");
    fgets($conn, 1024);
    
    fputs($conn, base64_encode($user) . "\r\n");
    fgets($conn, 1024);
    
    fputs($conn, base64_encode($pass) . "\r\n");
    $response = fgets($conn, 1024);
    
    if (strpos($response, '235') !== false) {
        echo "✅ Authentication successful!";
    } else {
        echo "❌ Authentication failed. Check your credentials.";
    }
    
    fclose($conn);
}
?>