<?php
session_start();
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Check if OTP is verified
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
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
    } else {
        // Add resetPassword method to your AuthController
        $result = $authController->resetPassword($_SESSION['reset_user_id'], $new_password);
        
        if ($result['success']) {
            // Clear reset sessions
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
    <!-- Add your CSS styles here -->
</head>
<body>
    <div class="container">
        <h1>Reset Your Password</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="reset_password" value="1">
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    </div>
</body>
</html>