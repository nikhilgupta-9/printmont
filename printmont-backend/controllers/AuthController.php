<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $conn;
    private $user;
    private $max_attempts = 5;
    private $lock_time = 900; // 15 minutes in seconds
    private $session_timeout = 1800; // 30 minutes in seconds

    public function __construct($db)
    {
        $this->conn = $db;
        $this->user = new User($db);
        $this->initializeSession();
    }

    
    /**
     * Initialize session securely
     */
    private function initializeSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }

    /**
     * Secure login method
     */
    public function login($email, $password)
    {
        // Validate input
        if (!$this->validateInput($email, $password)) {
            return ['success' => false, 'message' => 'Invalid input format'];
        }

        // Load user by email
        if (!$this->user->getByEmail($email)) {
            $this->randomDelay();
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Check if account is active
        if ($this->user->status !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }

        // Check for brute force attacks
        if ($this->isBruteForce()) {
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.'];
        }

        // Check if account is locked
        if ($this->user->isLocked()) {
            return ['success' => false, 'message' => 'Account is locked. Please try again later.'];
        }

        // Verify password
        if ($this->user->verifyPassword($password)) {
            // Successful login
            return $this->handleSuccessfulLogin();
        } else {
            // Failed login
            return $this->handleFailedLogin();
        }
    }

    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin()
    {
        // Reset login attempts
        $this->user->resetLoginAttempts();

        // Update last login
        $this->user->updateLastLogin();

        // Set session variables
        $this->setUserSession();

        // Regenerate session ID
        session_regenerate_id(true);

        error_log("Login successful - User: " . $this->user->email);

        return [
            'success' => true, 
            'message' => 'Login successful',
            'user' => $this->user->toArray()
        ];
    }

    /**
     * Handle failed login
     */
    private function handleFailedLogin()
    {
        // Increment login attempts
        $new_attempts = $this->user->login_attempts + 1;
        $this->user->updateLoginAttempts($new_attempts);

        // Lock account if max attempts reached
        if ($new_attempts >= $this->max_attempts) {
            $lock_until = date('Y-m-d H:i:s', time() + $this->lock_time);
            $this->user->setLockUntil($lock_until);
            
            return [
                'success' => false,
                'message' => 'Account locked due to too many failed attempts. Please try again later.'
            ];
        }

        $attempts_left = $this->max_attempts - $new_attempts;
        
        return [
            'success' => false,
            'message' => 'Invalid credentials. Attempts left: ' . $attempts_left
        ];
    }

    /**
     * Set user session after successful login
     */
    private function setUserSession()
    {
        $_SESSION['user_id'] = $this->user->id;
        $_SESSION['username'] = $this->user->username;
        $_SESSION['email'] = $this->user->email;
        $_SESSION['role'] = $this->user->role;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['session_id'] = session_id();
    }

    /**
     * Logout method
     */
    public function logout($redirect = true)
    {
        // Clear all session variables
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if ($redirect) {
            header('Location:  ../index.php');
            exit();
        }

        return ['success' => true, 'message' => 'Logout successful'];
    }

    /**
     * Check if user is logged in with valid session
     */
    public function isLoggedIn()
    {
        $this->initializeSession();

        // Check if session variables exist and are valid
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check if essential session variables exist
            if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['username'], $_SESSION['login_time'])) {
                // Check session timeout
                if ($this->checkSessionTimeout()) {
                    // Update activity timestamp
                    $_SESSION['login_time'] = time();
                    return true;
                }
            }
        }

        // If any check fails, clear session
        $this->clearInvalidSession();
        return false;
    }

    /**
     * Clear invalid session
     */
    private function clearInvalidSession()
    {
        $_SESSION = [];
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Check session timeout
     */
    public function checkSessionTimeout()
    {
        if (!isset($_SESSION['login_time'])) {
            return false;
        }

        $current_time = time();
        $last_activity = $_SESSION['login_time'];
        
        return ($current_time - $last_activity) <= $this->session_timeout;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        // Load current user data
        $user = new User($this->conn);
        if ($user->getById($_SESSION['user_id'])) {
            return $user->toArray();
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }

    /**
     * Check for brute force attacks
     */
    private function isBruteForce()
    {
        return $this->user->login_attempts >= $this->max_attempts;
    }

    /**
     * Validate input data
     */
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

        return true;
    }

    /**
     * Random delay to prevent timing attacks
     */
    private function randomDelay()
    {
        usleep(rand(100000, 500000)); // 0.1 to 0.5 second delay
    }

    /**
     * Forgot password - generate OTP
     */
    public function forgotPassword($email)
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Load user by email
        if (!$this->user->getByEmail($email)) {
            $this->randomDelay();
            return [
                'success' => true,
                'message' => 'If the email is registered, OTP will be sent.'
            ];
        }

        // Check if account is active
        if ($this->user->status !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }

        // Generate OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        // Store OTP in database
        if ($this->user->setResetOTP($otp, $otp_expiry)) {
            // Send OTP to email
            $emailSent = $this->sendOTPToEmail($this->user->email, $this->user->username, $otp);

            if ($emailSent) {
                // Store email in session for verification
                $_SESSION['reset_email'] = $this->user->email;
                $_SESSION['otp_verified'] = false;

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
    }

    /**
     * Verify OTP
     */
    public function verifyOTP($email, $otp)
    {
        // Validate OTP
        if (empty($otp) || !preg_match('/^[0-9]{6}$/', $otp)) {
            return ['success' => false, 'message' => 'Invalid OTP format'];
        }

        // Load user by email
        if (!$this->user->getByEmail($email)) {
            return ['success' => false, 'message' => 'Invalid OTP'];
        }

        // Verify OTP
        if ($this->user->isOTPValid($otp)) {
            // Clear OTP after successful verification
            $this->user->clearResetData();

            // Set session for password reset
            $_SESSION['otp_verified'] = true;
            $_SESSION['reset_user_id'] = $this->user->id;

            return ['success' => true, 'message' => 'OTP verified successfully'];
        } else {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
    }

    /**
     * Reset password using email (after OTP verification)
     */
    public function resetPasswordWithEmail($email, $new_password)
    {
        // Validate password
        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }

        // Load user by email
        if (!$this->user->getByEmail($email)) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Update password and clear reset data
        if ($this->user->updatePassword($new_password)) {
            $this->user->clearResetData();
            $this->user->resetLoginAttempts();

            return [
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }

    // ... (Keep your existing email sending methods)
    private function sendOTPToEmail($email, $username, $otp)
    {
        // Your existing email sending code here
        // Return true for testing, false if failed
        return true;
    }

    /**
     * Get user IP address
     */
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
}
?>