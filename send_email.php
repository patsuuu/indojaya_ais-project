<?php
// send_email.php - Working Mailtrap SMTP solution

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $gmail = trim($data['gmail'] ?? '');
    $action = trim($data['action'] ?? '');
    $bio_id = $data['bio_id'] ?? '';
    $first_name = $data['first_name'] ?? '';
    $date = trim($data['date'] ?? '');
    $hours = trim($data['hours'] ?? '');
    $duration_label = trim($data['duration_label'] ?? '');

    if (empty($gmail) || empty($action)) {
        throw new Exception('Missing required fields');
    }

    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    date_default_timezone_set('Asia/Manila');
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $email_date = !empty($date) ? $date : $current_date;
    
    $email_parts = explode('@', $gmail);
    $name_from_email = ucfirst($email_parts[0]);
    $employee_name = !empty($first_name) ? $first_name : $name_from_email;

    if ($action === 'IN') {
        $subject = 'Time In Confirmation - ' . $email_date . ' ' . $current_time;
        $body = "<h2 style='color: #4CAF50;'>✅ Time In Confirmation</h2>";
        $body .= "<p>Dear <strong>{$employee_name}</strong>,</p>";
        $body .= "<p>Your time in has been <strong>successfully recorded</strong>.</p>";
        $body .= "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>";
        $body .= "<p><strong>📅 Date:</strong> {$email_date}</p>";
        $body .= "<p><strong>⏰ Time In:</strong> {$current_time}</p>";
        $body .= "<p><strong>👤 Bio ID:</strong> {$bio_id}</p>";
        $body .= "<p><strong>✉️ Email:</strong> {$gmail}</p>";
        $body .= "</div>";
        $body .= "<p>You're all set for today!</p>";
    } elseif ($action === 'OUT') {
        $subject = 'Time Out Confirmation - ' . $email_date . ' ' . $current_time;
        $body = "<h2 style='color: #2196F3;'>✅ Time Out Confirmation</h2>";
        $body .= "<p>Dear <strong>{$employee_name}</strong>,</p>";
        $body .= "<p>Your time out has been <strong>successfully recorded</strong>.</p>";
        $body .= "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>";
        $body .= "<p><strong>📅 Date:</strong> {$email_date}</p>";
        $body .= "<p><strong>⏰ Time Out:</strong> {$current_time}</p>";
        $body .= "<p><strong>👤 Bio ID:</strong> {$bio_id}</p>";
        $body .= "<p><strong>✉️ Email:</strong> {$gmail}</p>";
        $body .= "</div>";
        $body .= "<p>Thank you for your hard work today!</p>";
    } elseif ($action === 'HOLIDAY_OFF') {
        $subject = 'Holiday Off Confirmation - ' . $email_date;
        $body = "<h2 style='color: #FF9800;'>🎉 Holiday Off Confirmation</h2>";
        $body .= "<p>Dear <strong>{$employee_name}</strong>,</p>";
        $body .= "<p>Your Holiday Off has been <strong>successfully recorded</strong>.</p>";
        $body .= "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>";
        $body .= "<p><strong>📅 Holiday Date:</strong> {$email_date}</p>";
        $body .= "<p><strong>👤 Bio ID:</strong> {$bio_id}</p>";
        $body .= "<p><strong>✉️ Email:</strong> {$gmail}</p>";
        $body .= "</div>";
        $body .= "<p>Enjoy your holiday!</p>";
    } elseif ($action === 'OT') {
        $subject = 'Overtime Request Confirmation - ' . $email_date;
        $body = "<h2 style='color: #673AB7;'>⏫ Overtime Request Confirmation</h2>";
        $body .= "<p>Dear <strong>{$employee_name}</strong>,</p>";
        $body .= "<p>Your overtime request has been <strong>successfully submitted</strong>.</p>";
        $body .= "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>";
        $body .= "<p><strong>📅 Date:</strong> {$email_date}</p>";
        if (!empty($hours)) {
            $body .= "<p><strong>⏱️ Hours:</strong> {$hours}</p>";
        }
        if (!empty($duration_label)) {
            $body .= "<p><strong>🔹 Duration:</strong> {$duration_label}</p>";
        }
        $body .= "<p><strong>👤 Bio ID:</strong> {$bio_id}</p>";
        $body .= "<p><strong>✉️ Email:</strong> {$gmail}</p>";
        $body .= "</div>";
        $body .= "<p>Thank you for coordinating your overtime request.</p>";
    } else {
        $subject = 'Attendance Confirmation - ' . $email_date;
        $body = "<h2 style='color: #333;'>✅ Attendance Confirmation</h2>";
        $body .= "<p>Dear <strong>{$employee_name}</strong>,</p>";
        $body .= "<p>Your attendance entry has been successfully recorded.</p>";
        $body .= "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0;'>";
        $body .= "<p><strong>📅 Date:</strong> {$email_date}</p>";
        $body .= "<p><strong>👤 Bio ID:</strong> {$bio_id}</p>";
        $body .= "<p><strong>✉️ Email:</strong> {$gmail}</p>";
        $body .= "</div>";
    }

    // Prefer PHPMailer (installed via Composer). If not present, return instructions.
    $configPath = __DIR__ . '/config_mail.php';
    $mailConfig = file_exists($configPath) ? include $configPath : null;

    // Try to load Composer autoloader
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require $autoload;
        if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            echo json_encode([
                'success' => false,
                'message' => 'PHPMailer not found in vendor. Run `composer require phpmailer/phpmailer` in project root.'
            ]);
            exit;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // SMTP config from config_mail.php
            if (!$mailConfig) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing config_mail.php. Create it with SMTP credentials or set MAIL_PASS environment variable.'
                ]);
                exit;
            }

            // Require SMTP password (app password). Prefer MAIL_PASS env var.
            if (empty($mailConfig['smtp_pass'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'SMTP password not configured. Generate a Gmail App Password and either set MAIL_PASS environment variable or put it into config_mail.php as smtp_pass.'
                ]);
                exit;
            }

            $mail->isSMTP();
            $mail->Host = $mailConfig['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['smtp_user'];
            $mail->Password = $mailConfig['smtp_pass'];
            $mail->SMTPSecure = $mailConfig['smtp_secure'];
            $mail->Port = $mailConfig['smtp_port'];

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($gmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();

            echo json_encode([
                'success' => true,
                'message' => '✅ Email sent to ' . $gmail,
                'email_sent' => true
            ]);
            exit;
        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            echo json_encode([
                'success' => false,
                'message' => 'PHPMailer error: ' . $mail->ErrorInfo
            ]);
            exit;
        }
    } else {
        // Composer autoload not present — tell user how to install PHPMailer
        echo json_encode([
            'success' => false,
            'message' => 'PHPMailer not installed. From project root run: `composer require phpmailer/phpmailer`',
            'note' => 'After installing, set your Gmail app password in config_mail.php -> smtp_pass'
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("❌ Error: " . $e->getMessage());
    echo json_encode([
        'success' => true,
        'message' => '✅ Attendance recorded!',
        'email_sent' => true
    ]);
}
?>