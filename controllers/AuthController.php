<?php
require_once __DIR__ . '/../config/database.php';

class AuthController
{
    private $conn;
    private $table_name = "users";
    private $max_attempts = 5;
    private $lock_time = 900; // 15 minutes in seconds

    public function __construct($db)
    {
        $this->conn = $db;

        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Secure login method
    public function login($email, $password)
    {
        // Validate input
        if (!$this->validateInput($email, $password)) {
            return ['success' => false, 'message' => 'Invalid input format'];
        }

        // Check for brute force attacks
        if ($this->isBruteForce($email)) {
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.'];
        }

        // Prepare statement to prevent SQL injection
        $query = "SELECT id, username, email, password, role, status, login_attempts, lock_until 
                  FROM " . $this->table_name . " 
                  WHERE email = ? AND status IN ('active') 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error'];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is locked
                if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                    return ['success' => false, 'message' => 'Account is locked. Please try again later.'];
                }

                // Reset login attempts on successful login
                $this->resetLoginAttempts($user['id']);

                // Update last login
                $this->updateLastLogin($user['id']);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                return ['success' => true, 'message' => 'Login successful'];
            } else {
                // Increment failed login attempts
                $this->incrementLoginAttempts($user['id']);
                $attempts_left = $this->max_attempts - ($user['login_attempts'] + 1);

                return [
                    'success' => false,
                    'message' => 'Invalid credentials. Attempts left: ' . $attempts_left
                ];
            }
        } else {
            // Delay response to prevent timing attacks
            $this->randomDelay();
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
    }

    // Logout method
    public function logout()
    {
        // Unset all session variables
        $_SESSION = array();

        // Destroy session
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return ['success' => true, 'message' => 'Logout successful'];
    }

    // Check if user is logged in
    public function isLoggedIn()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Check if user has specific role
    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    // Validate input data
    private function validateInput($email, $password)
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Validate password length
        if (strlen($password) < 6 || strlen($password) > 255) {
            return false;
        }

        // Prevent XSS attacks
        $email = $this->sanitizeInput($email);
        $password = $this->sanitizeInput($password);

        return true;
    }

    // Sanitize input data
    private function sanitizeInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    // Check for brute force attacks
    private function isBruteForce($email)
    {
        $query = "SELECT login_attempts, lock_until 
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if account is locked
            if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                return true;
            }

            // Check if max attempts reached
            if ($user['login_attempts'] >= $this->max_attempts) {
                $this->lockAccount($email);
                return true;
            }
        }

        return false;
    }

    // Increment login attempts
    private function incrementLoginAttempts($user_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET login_attempts = login_attempts + 1, 
                      last_login_attempt = NOW() 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Lock account if max attempts reached
        $this->checkAndLockAccount($user_id);
    }

    // Check and lock account if needed
    private function checkAndLockAccount($user_id)
    {
        $query = "SELECT login_attempts FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ($user['login_attempts'] >= $this->max_attempts) {
                $this->lockAccountById($user_id);
            }
        }
    }

    // Lock account by email
    private function lockAccount($email)
    {
        $lock_until = date('Y-m-d H:i:s', time() + $this->lock_time);

        $query = "UPDATE " . $this->table_name . " 
                  SET lock_until = ? 
                  WHERE email = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $lock_until, $email);
        $stmt->execute();
    }

    // Lock account by ID
    private function lockAccountById($user_id)
    {
        $lock_until = date('Y-m-d H:i:s', time() + $this->lock_time);

        $query = "UPDATE " . $this->table_name . " 
                  SET lock_until = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $lock_until, $user_id);
        $stmt->execute();
    }

    // Reset login attempts
    private function resetLoginAttempts($user_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET login_attempts = 0, lock_until = NULL 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    // Update last login
    private function updateLastLogin($user_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = NOW() 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    // Random delay to prevent timing attacks
    private function randomDelay()
    {
        usleep(rand(100000, 1000000)); // 0.1 to 1 second delay
    }

    // Get user IP address
    public function getUserIP()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Check session timeout (30 minutes)
    public function checkSessionTimeout()
    {
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            $this->logout();
            return false;
        }

        // Update session time on activity
        $_SESSION['login_time'] = time();
        return true;
    }

    // Forgot password - generate reset token
    public function forgotPassword($email)
{
    // Start session if not already started - ADD THIS
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    // Check if user exists
    $query = "SELECT id, username, email, status FROM users 
          WHERE email = ? AND status = 'active' LIMIT 1";

    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Generate OTP (6-digit code)
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes expiry

        // Store OTP in database using existing reset_token columns or new OTP columns
        $update_query = "UPDATE users 
                     SET reset_otp = ?, reset_otp_expiry = ? 
                     WHERE id = ?";

        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $otp, $otp_expiry, $user['id']);

        if ($update_stmt->execute()) {
            // Send OTP to email
            $emailSent = $this->sendOTPToEmail($user['email'], $user['username'], $otp);

            if ($emailSent) {
                // Store email in session for verification
                $_SESSION['reset_email'] = $user['email'];
                $_SESSION['otp_verified'] = false;

                // Debug: Log session setting
                error_log("Session set - reset_email: " . $_SESSION['reset_email']);

                return [
                    'success' => true,
                    'message' => 'OTP has been sent to your email address.',
                    'redirect' => 'verify_otp.php'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to send OTP email. Please try again.'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to generate OTP'];
        }
    } else {
        // Don't reveal if email exists or not
        $this->randomDelay();
        return [
            'success' => true,
            'message' => 'If the email is registered, OTP will be sent.'
        ];
    }
}

    private function sendOTPToEmail($email, $username, $otp)
    {
        try {
            // Check if PHPMailer is available
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                error_log("PHPMailer class not found");
                return false;
            }

            require_once '../../vendor/autoload.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'no-reply@printmont.com';
            $mail->Password = 'aU9>l2S2Ve*m';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Enable verbose debug output
            $mail->SMTPDebug = 2; // Set to 2 for detailed debug info
            $mail->Debugoutput = 'error_log';

            // Recipients
            $mail->setFrom('no-reply@printmont.com', 'PrintMont Admin');
            $mail->addAddress($email, $username);
            $mail->addReplyTo('no-reply@printmont.com', 'PrintMont Admin');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - PrintMont Admin';

            $mail->Body = "
        <html>
        <head>
            <title>Password Reset OTP</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
                .otp-code { font-size: 32px; font-weight: bold; text-align: center; color: #667eea; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; letter-spacing: 5px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e1e5e9; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                
                <p>Hello <strong>$username</strong>,</p>
                
                <p>You have requested to reset your password for your PrintMont Admin account. Use the OTP below to verify your identity:</p>
                
                <div class='otp-code'>$otp</div>
                
                <p><strong>This OTP is valid for 15 minutes.</strong></p>
                
                <p>If you didn't request this reset, please ignore this email. Your account remains secure.</p>
                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " PrintMont Admin. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

            // Plain text version for email clients that don't support HTML
            $mail->AltBody = "Password Reset OTP\n\nHello $username,\n\nYou have requested to reset your password. Your OTP is: $otp\n\nThis OTP is valid for 15 minutes.\n\nIf you didn't request this reset, please ignore this email.";

            $mail->send();
            error_log("OTP email successfully sent to: $email");
            return true;

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function verifyOTP($email, $otp)
    {
        // Validate OTP
        if (empty($otp) || !preg_match('/^[0-9]{6}$/', $otp)) {
            return ['success' => false, 'message' => 'Invalid OTP format'];
        }

        $query = "SELECT id, reset_otp, reset_otp_expiry FROM users 
          WHERE email = ? AND status = 'active' LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if OTP matches and is not expired
            if ($user['reset_otp'] === $otp && strtotime($user['reset_otp_expiry']) > time()) {
                // Clear OTP after successful verification
                $clear_query = "UPDATE users SET reset_otp = NULL, reset_otp_expiry = NULL WHERE id = ?";
                $clear_stmt = $this->conn->prepare($clear_query);
                $clear_stmt->bind_param("i", $user['id']);
                $clear_stmt->execute();

                // Set session for password reset
                $_SESSION['otp_verified'] = true;
                $_SESSION['reset_user_id'] = $user['id'];

                return ['success' => true, 'message' => 'OTP verified successfully'];
            } else {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }
        }

        return ['success' => false, 'message' => 'Invalid OTP'];
    }

    // Verify reset token
    public function verifyResetToken($token)
    {
        if (empty($token) || strlen($token) !== 64) {
            return ['success' => false, 'message' => 'Invalid reset token'];
        }

        $query = "SELECT id, email, reset_token_expiry 
              FROM " . $this->table_name . " 
              WHERE reset_token = ? AND status = 'active' LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Check if token is expired
            if (strtotime($user['reset_token_expiry']) < time()) {
                return ['success' => false, 'message' => 'Reset token has expired'];
            }

            return [
                'success' => true,
                'message' => 'Token is valid',
                'email' => $user['email']
            ];
        } else {
            $this->randomDelay();
            return ['success' => false, 'message' => 'Invalid reset token'];
        }
    }

    // Reset password with token
    public function resetPassword($token, $new_password)
    {
        // Validate token and password
        if (empty($token) || strlen($token) !== 64) {
            return ['success' => false, 'message' => 'Invalid reset token'];
        }

        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }

        // Verify token first
        $token_verify = $this->verifyResetToken($token);
        if (!$token_verify['success']) {
            return $token_verify;
        }

        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $query = "UPDATE " . $this->table_name . " 
              SET password = ?, reset_token = NULL, reset_token_expiry = NULL,
                  login_attempts = 0, lock_until = NULL 
              WHERE reset_token = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $hashed_password, $token);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }

    // Send reset email (simulated - implement with your email service)
    private function sendResetEmail($email, $username, $token)
    {
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/printmont-admin/reset-password.php?token=" . $token;

        // In production, use PHPMailer or similar
        $subject = "PrintMont Admin - Password Reset Request";
        $message = "
    <html>
    <head>
        <title>Password Reset Request</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello " . htmlspecialchars($username) . ",</p>
        <p>You have requested to reset your password for PrintMont Admin.</p>
        <p>Click the link below to reset your password (valid for 1 hour):</p>
        <p><a href='" . $reset_link . "' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>If you didn't request this, please ignore this email.</p>
        <br>
        <p>Best regards,<br>PrintMont Admin Team</p>
    </body>
    </html>
    ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@printmont.com" . "\r\n";

        // For demo purposes, we'll log instead of actually sending
        error_log("Password Reset Email for: " . $email);
        error_log("Reset Link: " . $reset_link);

        // Uncomment to actually send email in production:
        // mail($email, $subject, $message, $headers);

        return true;
    }

    // Clean expired tokens (can be run via cron job)
    public function cleanExpiredTokens()
    {
        $query = "UPDATE " . $this->table_name . " 
              SET reset_token = NULL, reset_token_expiry = NULL 
              WHERE reset_token_expiry < NOW()";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>