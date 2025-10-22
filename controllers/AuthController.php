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
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Check if user exists
        $query = "SELECT id, username, email, status FROM " . $this->table_name . " 
              WHERE email = ? AND status = 'active' LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Generate secure reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_token_expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Store token in database
            $update_query = "UPDATE " . $this->table_name . " 
                         SET reset_token = ?, reset_token_expiry = ? 
                         WHERE id = ?";

            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $reset_token, $reset_token_expiry, $user['id']);

            if ($update_stmt->execute()) {
                // Send reset email (in production)
                $this->sendResetEmail($user['email'], $user['username'], $reset_token);

                return [
                    'success' => true,
                    'message' => 'Password reset instructions have been sent to your email.'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to generate reset token'];
            }
        } else {
            // Don't reveal if email exists or not
            $this->randomDelay();
            return [
                'success' => true,
                'message' => 'If the email exists, reset instructions will be sent.'
            ];
        }
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