<?php
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Redirect if already logged in
if ($authController->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;
$user_email = '';

// Verify token
if (!empty($token)) {
    $token_result = $authController->verifyResetToken($token);
    if ($token_result['success']) {
        $valid_token = true;
        $user_email = $token_result['email'];
    } else {
        $message = $token_result['message'];
        $message_type = 'error';
    }
} else {
    $message = 'No reset token provided';
    $message_type = 'error';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'error';
    } else {
        $result = $authController->resetPassword($token, $new_password);
        
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
            $valid_token = false; // Token is now used
        } else {
            $message = $result['message'];
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PrintMont Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .reset-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .reset-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .reset-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            height: 15px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .reset-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .reset-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .reset-footer a:hover {
            text-decoration: underline;
        }

        .user-info {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #155724;
        }

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .reset-container {
                margin: 10px;
            }
            
            .reset-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Create New Password</h1>
            <p>Choose a strong and secure password</p>
        </div>
        
        <div class="reset-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <div class="user-info">
                    <strong>Account:</strong> <?php echo htmlspecialchars($user_email); ?><br>
                    <small>You're about to reset the password for this account.</small>
                </div>

                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="reset_password" value="1">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-toggle">
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password" required minlength="8"
                                   oninput="checkPasswordStrength()">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                👁️
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-toggle">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password" required minlength="8"
                                   oninput="checkPasswordMatch()">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                👁️
                            </button>
                        </div>
                        <div class="password-strength" id="passwordMatch"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Reset Password
                    </button>
                </form>
            <?php elseif (empty($message)): ?>
                <div class="alert alert-error">
                    Invalid or expired reset token. Please request a new password reset.
                </div>
            <?php endif; ?>

            <div class="reset-footer">
                <a href="login.php">← Back to Login</a> • 
                <a href="forgot-password.php">Request New Reset</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.parentNode.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthText = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthText.textContent = '';
                return;
            }
            
            let strength = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                feedback = 'Weak password';
                strengthText.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                feedback = 'Medium strength password';
                strengthText.className = 'password-strength strength-medium';
            } else {
                feedback = 'Strong password';
                strengthText.className = 'password-strength strength-strong';
            }
            
            strengthText.textContent = feedback;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.className = 'password-strength strength-strong';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.className = 'password-strength strength-weak';
            }
        }

        // Form submission handler
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const btn = document.getElementById('submitBtn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = 'Resetting...';
        });

        // Focus on password field if form exists
        if (document.getElementById('new_password')) {
            document.getElementById('new_password').focus();
        }
    </script>
</body>
</html>