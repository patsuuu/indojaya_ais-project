<?php
session_start();

// If already logged in, redirect to records


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_connect.php';
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = '❌ All fields are required';
    } elseif (strlen($username) < 3) {
        $error = '❌ Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = '❌ Password must be at least 6 characters';
    } elseif ($password !== $password_confirm) {
        $error = '❌ Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Invalid email address';
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = '❌ Username already exists';
        } else {
            // Check if email already exists
            $email_check_sql = "SELECT id FROM users WHERE email = ?";
            $email_check_stmt = $conn->prepare($email_check_sql);
            $email_check_stmt->bind_param('s', $email);
            $email_check_stmt->execute();
            $email_check_result = $email_check_stmt->get_result();
            
            if ($email_check_result->num_rows > 0) {
                $error = '❌ Email already registered';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $role = 'user';
                
                $insert_sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if (!$insert_stmt) {
                    $error = '❌ Database error: ' . $conn->error;
                } else {
                    $insert_stmt->bind_param('ssss', $username, $hashed_password, $email, $role);
                    
                    if ($insert_stmt->execute()) {
                        $success = '✅ Account created successfully! You can now login.';
                        $username = '';
                        $email = '';
                        $password = '';
                        $password_confirm = '';
                        
                        error_log("New user registered: " . $username);
                    } else {
                        $error = '❌ Error creating account: ' . $insert_stmt->error;
                    }
                    
                    $insert_stmt->close();
                }
            }
            
            $email_check_stmt->close();
        }
        
        $check_stmt->close();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .register-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .register-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        
        .password-requirements li {
            margin: 3px 0;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .error-alert {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success-alert {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .register-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            color: #666;
            font-size: 14px;
        }
        
        .register-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .eye-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }
        
        .strength-meter {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        
        .strength-text {
            font-size: 11px;
            margin-top: 3px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>📝 Create Account</h1>
                <p>Join the Attendance System</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="error-alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-alert">
                        <?php echo htmlspecialchars($success); ?>
                        <br><br>
                        <a href="register.php" class="btn btn-sm btn-primary">Go Back →</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                placeholder="Enter username (min 3 characters)"
                                value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                required
                                minlength="3"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Enter password (min 6 characters)"
                                    required
                                    minlength="6"
                                    onkeyup="checkPasswordStrength()"
                                >
                                <span class="eye-icon" onclick="togglePassword('password')">👁️</span>
                            </div>
                            <div class="strength-meter">
                                <div class="strength-meter-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text">
                                Password strength: <span id="strengthText">-</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Re-enter your password"
                                    required
                                    minlength="6"
                                >
                                <span class="eye-icon" onclick="togglePassword('password_confirm')">👁️</span>
                            </div>
                        </div>
                        
                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li>✓ At least 6 characters</li>
                                <li>✓ Mix of letters and numbers (recommended)</li>
                                <li>✓ Use special characters for better security (recommended)</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn-register">🔐 Create Account</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="register-footer">
                <p style="margin-top: 10px; font-size: 12px;">© 2026 Attendance System. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = event.target;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let text = '';
            let color = '';
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 12) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 12.5;
            if (/[!@#$%^&*]/.test(password)) strength += 12.5;
            
            if (strength < 25) {
                text = 'Weak';
                color = '#dc3545';
            } else if (strength < 50) {
                text = 'Fair';
                color = '#ffc107';
            } else if (strength < 75) {
                text = 'Good';
                color = '#17a2b8';
            } else {
                text = 'Strong';
                color = '#28a745';
            }
            
            strengthFill.style.width = strength + '%';
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = text;
        }
        
        function validateForm() {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alert('❌ Passwords do not match!');
                return false;
            }
            
            return true;
        }
        
        // Allow Enter key to submit
        document.querySelector('form')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.name === 'password_confirm') {
                e.preventDefault();
                this.submit();
            }
        });
    </script>
</body>
</html>