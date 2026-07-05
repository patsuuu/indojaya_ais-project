<?php
$to = "your-email@gmail.com";  // CHANGE THIS
$subject = "XAMPP Mailtrap Test";
$message = "This is a test email from XAMPP using Mailtrap!";
$headers = "From: noreply@attendance.com\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✅ Email sent! Check your Mailtrap inbox and your Gmail.";
} else {
    echo "❌ Email failed. Check XAMPP configuration.";
}
?>