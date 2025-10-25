<?php
session_start();
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Debug: Check session
error_log("verify_otp.php - Session data: " . print_r($_SESSION, true));

// Check if email is set in session
if (!isset($_SESSION['reset_email'])) {
    error_log("No reset_email in session, redirecting to forgot_password.php");
    header("Location: forgot_password.php");
    exit();
}

$message = '';
$message_type = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp = $_POST['otp'] ?? '';
    $email = $_SESSION['reset_email'];
    
    error_log("OTP verification attempt for: $email, OTP: $otp");
    
    $result = $authController->verifyOTP($email, $otp);
    
    if ($result['success']) {
        error_log("OTP verified successfully, redirecting to reset_password.php");
        header("Location: reset_password.php");
        exit();
    } else {
        $message = $result['message'];
        $message_type = 'error';
        error_log("OTP verification failed: " . $result['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - PrintMont Admin</title>
    <style>
        /* Add your CSS styles here */
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

        .otp-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .otp-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .otp-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .otp-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-header">
            <h1>Verify OTP</h1>
            <p>Enter the 6-digit code sent to your email</p>
        </div>
        
        <div class="otp-body">
            <?php if ($message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-bottom: 20px;">
                <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['reset_email']); ?><br>
                <small>Check your email for the 6-digit OTP</small>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="verify_otp" value="1">
                
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" class="otp-input" id="otp" name="otp" 
                           placeholder="000000" required maxlength="6"
                           pattern="[0-9]{6}">
                </div>

                <button type="submit" class="btn">Verify OTP</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="forgot_password.php">Resend OTP</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('otp').focus();
        
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>