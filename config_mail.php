<?php
// Mail configuration for PHPMailer (Gmail SMTP)
// IMPORTANT: generate a Gmail App Password and put it in 'smtp_pass'
// Read SMTP password from environment variable for better security if available
$envPass = getenv('MAIL_PASS');
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'spatag14@gmail.com',
    'smtp_pass' => 'wwqk nioi iumo hvqi', // <-- Replace with your Gmail App Password or set MAIL_PASS env variable
    'smtp_secure' => 'tls', // tls or ssl
    'from_email' => 'spatag14@gmail.com',
    'from_name' => 'Attendance System'
];
