<?php
session_start(); // Add this at the very top
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Redirect if already logged in
if ($authController->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = $_POST['email'] ?? '';
    
    $result = $authController->forgotPassword($email);
    
    if ($result['success']) {
        // Check if redirect is needed BEFORE any HTML output
        if (isset($result['redirect'])) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $message = $result['message'];
            $message_type = 'success';
        }
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PrintMont Admin</title>
    <style>
        /* Add all your CSS styles here (remove the link to style.css) */
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

        .instructions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .instructions ol {
            margin-left: 20px;
            margin-top: 10px;
        }

        .instructions li {
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
            <h1>Reset Password</h1>
            <p>Enter your email to receive OTP</p>
        </div>
        
        <div class="forgot-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <strong>How to reset your password:</strong>
                <ol>
                    <li>Enter your registered email address below</li>
                    <li>Check your email for OTP (6-digit code)</li>
                    <li>Enter the OTP on next screen (valid for 15 minutes)</li>
                    <li>Create a new password</li>
                </ol>
            </div>

            <form method="POST" action="" id="forgotForm">
                <input type="hidden" name="forgot_password" value="1">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your registered email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Send OTP
                </button>
            </form>

            <div class="forgot-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Form submission handler
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value;
            
            if (!email) {
                e.preventDefault();
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = 'Sending OTP...';
            
            // Re-enable button after 5 seconds in case of error
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = 'Send OTP';
            }, 5000);
        });

        // Focus on email field on page load
        document.getElementById('email').focus();

        // Email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>