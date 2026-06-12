<?php
session_start();
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Check if OTP is verified and email is set
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$message = '';
$message_type = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long";
        $message_type = 'error';
    } else {
        // Use the reset email to update password
        $result = $authController->resetPasswordWithEmail($_SESSION['reset_email'], $new_password);
        
        if ($result['success']) {
            // Clear all reset sessions
            unset($_SESSION['reset_email'], $_SESSION['otp_verified'], $_SESSION['reset_user_id']);
            
            $message = "Password reset successfully! You can now login with your new password.";
            $message_type = 'success';
            
            // Redirect to login after 3 seconds
            header("Refresh: 3; url=login.php");
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

        .forgot-container {
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

        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .forgot-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .forgot-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .forgot-body {
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

        .forgot-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .forgot-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-footer a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .password-requirements ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        .password-requirements li {
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .forgot-container {
                margin: 10px;
            }
            
            .forgot-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>Create New Password</h1>
            <p>Enter your new password below</p>
        </div>
        
        <div class="forgot-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Include uppercase and lowercase letters</li>
                    <li>Include numbers and special characters</li>
                </ul>
            </div>

            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="reset_password" value="1">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Enter new password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm new password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Reset Password
                </button>
            </form>

            <div class="forgot-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const btn = document.getElementById('submitBtn');
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = 'Resetting Password...';
        });

        // Real-time password matching
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        // Focus on first password field
        document.getElementById('new_password').focus();
    </script>
</body>
</html>