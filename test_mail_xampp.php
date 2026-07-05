<?php
$to = "your-email@gmail.com"; // CHANGE THIS
$subject = "XAMPP Email Test";
$message = "This is a test email from XAMPP!";
$headers = "From: system@attendance.com\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✅ Email sent successfully! Check your inbox.";
} else {
    echo "❌ Email failed. Check sendmail configuration.";
}
?>