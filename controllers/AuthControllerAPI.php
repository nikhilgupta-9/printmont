<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AddressModel.php';
require_once __DIR__ . '/../middleware/JWTHandler.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class AuthController {
    private $db;
    private $userModel;
    private $addressModel; // Add this property
    private $jwt;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
          $this->addressModel = new AddressModel($db); // Initialize AddressModel
        $this->addressModel->createTable(); // Ensure table exists
        $this->jwt = new JWTHandler();
    }

    // User Registration
    public function register($data) {
        try {
            // Validation - check if all required fields exist
            $required = ['firstName', 'lastName', 'mobile', 'email', 'gender', 'password'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("Field $field is required");
                }
            }

            // Trim and sanitize data
            $firstName = trim($data['firstName']);
            $lastName = trim($data['lastName']);
            $mobile = trim($data['mobile']);
            $email = trim($data['email']);
            $gender = trim($data['gender']);
            $password = trim($data['password']);

            // Check if email already exists
            if ($this->userModel->checkEmailExists($email)) {
                throw new Exception("Email already registered");
            }

            // Check if phone already exists
            if ($this->userModel->checkPhoneExists($mobile)) {
                throw new Exception("Phone number already registered");
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Validate password strength
            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long");
            }

            // Prepare user data with validated fields
            $userData = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'mobile' => $mobile,
                'gender' => $gender,
                'password' => $password
            ];

            // Customer data (can be empty for basic registration)
            $customerData = [
                'company_name' => '',
                'address' => '',
                'city' => '',
                'state' => '',
                'country' => '',
                'postal_code' => ''
            ];

            // Create user
            $userId = $this->userModel->createUser($userData, $customerData);
            $user = $this->userModel->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
                'user' => $this->sanitizeUser($user),
                'tokens' => $this->jwt->generateTokenPair(['id' => $userId, 'email' => $email]),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // User Login
    public function login($data) {
        try {
            // Validation
            if (!isset($data['email']) || empty(trim($data['email']))) {
                throw new Exception("Email is required");
            }

            if (!isset($data['password']) || empty(trim($data['password']))) {
                throw new Exception("Password is required");
            }

            $email = trim($data['email']);
            $password = trim($data['password']);

            // Verify credentials
            $user = $this->userModel->verifyPassword($email, $password);

            if (!$user) {
                throw new Exception("Invalid email or password");
            }

            // Update last login
            $this->userModel->updateLastLogin($user['id']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUser($user),
                'tokens' => $this->jwt->generateTokenPair(['id' => $user['id'], 'email' => $user['email']]),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Strips password/reset/lockout fields that should never leave the API.
    private function sanitizeUser($user) {
        foreach (['password', 'reset_token', 'reset_token_expiry', 'reset_otp', 'reset_otp_expiry',
                  'login_attempts', 'last_login_attempt', 'lock_until'] as $field) {
            unset($user[$field]);
        }
        return $user;
    }

    // Get User Profile
    public function getProfile($userId) {
        try {
            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                throw new Exception("User not found");
            }

            return [
                'success' => true,
                'user' => $this->sanitizeUser($user)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    // Update User Profile
    public function updateProfile($userId, $data) {
        try {
            // Validate required fields
            $required = ['firstName', 'lastName', 'mobile', 'gender'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("Field $field is required");
                }
            }

            $userData = [
                'firstName' => trim($data['firstName']),
                'lastName' => trim($data['lastName']),
                'phone' => trim($data['mobile']),
                'gender' => trim($data['gender'])
            ];

            $customerData = [
                'company_name' => $data['company_name'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'country' => $data['country'] ?? '',
                'postal_code' => $data['postal_code'] ?? ''
            ];

            $success = $this->userModel->updateProfile($userId, $userData, $customerData);

            if (!$success) {
                throw new Exception("Failed to update profile");
            }

            // Optional extended fields (bio, socials, notifications, etc.) — only
            // touched if actually present in the request, everything else is untouched.
            $this->userModel->updateExtendedProfile($userId, $data);

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Change Password
    public function changePassword($userId, $data) {
        try {
            if (!isset($data['current_password']) || empty(trim($data['current_password']))) {
                throw new Exception("Current password is required");
            }

            if (!isset($data['new_password']) || empty(trim($data['new_password']))) {
                throw new Exception("New password is required");
            }

            $currentPassword = trim($data['current_password']);
            $newPassword = trim($data['new_password']);

            // Verify current password
            $user = $this->userModel->getUserById($userId);
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            if (strlen($newPassword) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }

            $success = $this->userModel->changePassword($userId, $newPassword);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                throw new Exception("Failed to change password");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Verify an access token (used to authenticate every protected request)
    public function verifyToken($token) {
        try {
            if (empty($token)) {
                throw new Exception("No token provided");
            }

            // Remove 'Bearer ' prefix if present
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }

            $data = $this->jwt->validateToken($token, 'access');
            if ($data === false) {
                throw new Exception("Invalid or expired token");
            }

            $userId = is_array($data) ? ($data['id'] ?? null) : (is_object($data) ? ($data->id ?? null) : null);
            if (!$userId) {
                throw new Exception("Invalid token payload");
            }

            $user = $this->userModel->getUserById($userId);

            if ($user && $user['status'] === 'active') {
                return [
                    'success' => true,
                    'user' => $this->sanitizeUser($user)
                ];
            }

            throw new Exception("Invalid token");

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Stateless JWT: nothing to invalidate server-side, this exists so
    // clients have a formal endpoint to call when discarding their tokens.
    public function logout() {
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }

    // Exchange a refresh token for a fresh access + refresh token pair (rotation).
    public function refreshToken($refreshToken) {
        try {
            if (empty($refreshToken)) {
                throw new Exception("No refresh token provided");
            }
            if (strpos($refreshToken, 'Bearer ') === 0) {
                $refreshToken = substr($refreshToken, 7);
            }

            $data = $this->jwt->validateToken($refreshToken, 'refresh');
            if ($data === false) {
                throw new Exception("Invalid or expired refresh token");
            }

            $userId = is_array($data) ? ($data['id'] ?? null) : (is_object($data) ? ($data->id ?? null) : null);
            if (!$userId) {
                throw new Exception("Invalid token payload");
            }

            $user = $this->userModel->getUserById($userId);
            if (!$user || $user['status'] !== 'active') {
                throw new Exception("Account not found or inactive");
            }

            return [
                'success' => true,
                'tokens' => $this->jwt->generateTokenPair(['id' => $user['id'], 'email' => $user['email']]),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Request a password reset OTP by email. Always returns a generic
    // success message regardless of whether the email exists, to avoid
    // leaking which addresses are registered.
    public function forgotPassword($data) {
        try {
            $email = trim($data['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("A valid email is required");
            }

            $genericResponse = [
                'success' => true,
                'message' => 'If that email is registered, a reset code has been sent.'
            ];

            $user = $this->userModel->getUserByEmail($email);
            if (!$user) {
                usleep(random_int(100000, 400000)); // timing-attack mitigation
                return $genericResponse;
            }

            $otp = sprintf('%06d', random_int(0, 999999));
            $expiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            $this->userModel->setResetOTP($user['id'], $otp, $expiry);

            $this->sendOtpEmail($user['email'], trim($user['first_name'] . ' ' . $user['last_name']), $otp);

            return $genericResponse;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Verify the OTP and set a new password in one call.
    public function resetPassword($data) {
        try {
            $email = trim($data['email'] ?? '');
            $otp = trim($data['otp'] ?? '');
            $newPassword = $data['new_password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("A valid email is required");
            }
            if (empty($otp) || !preg_match('/^[0-9]{6}$/', $otp)) {
                throw new Exception("Invalid OTP format");
            }
            if (strlen($newPassword) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }

            $user = $this->userModel->getUserByEmail($email);
            if (!$user || !$this->userModel->isOTPValid($user['id'], $otp)) {
                throw new Exception("Invalid or expired OTP");
            }

            $this->userModel->changePassword($user['id'], $newPassword);
            $this->userModel->clearResetData($user['id']);

            return [
                'success' => true,
                'message' => 'Password has been reset successfully. You can now log in with your new password.'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Permanently delete the account (hard delete). Requires the current
    // password as confirmation since this cannot be undone.
    public function deleteAccount($userId, $data) {
        try {
            $password = $data['password'] ?? '';
            if (empty($password)) {
                throw new Exception("Password confirmation is required to delete your account");
            }

            $user = $this->userModel->getUserById($userId);
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception("Incorrect password");
            }

            if (!$this->userModel->deleteUser($userId)) {
                throw new Exception("Failed to delete account");
            }

            return [
                'success' => true,
                'message' => 'Account permanently deleted'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Deactivate the account (status = inactive) without deleting data.
    public function softDeleteAccount($userId) {
        try {
            if (!$this->userModel->softDeleteUser($userId)) {
                throw new Exception("Failed to deactivate account");
            }

            return [
                'success' => true,
                'message' => 'Account deactivated'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Send the OTP email using the active "Password Reset" SMTP config
    // stored in email_configurations. Returns false (logged) on failure
    // rather than throwing, so forgotPassword() can still return its
    // generic success response either way.
    private function sendOtpEmail($toEmail, $toName, $otp) {
        if (file_exists(__DIR__ . '/../services/MailService.php')) {
            require_once __DIR__ . '/../services/MailService.php';
            try {
                $mailService = new MailService();
                $bodyHtml = '<div style="font-family:Arial,sans-serif; padding:20px; border:1px solid #eee; border-radius:8px;">'
                    . '<h2 style="color:#1e293b;">Printmont Password Reset</h2>'
                    . '<p>Hi ' . htmlspecialchars($toName ?: 'User') . ',</p>'
                    . '<p>Your OTP code for resetting your password is:</p>'
                    . '<div style="background:#f1f5f9; padding:12px; font-size:24px; font-weight:bold; letter-spacing:4px; text-align:center; color:#2563eb; border-radius:6px;">'
                    . htmlspecialchars($otp)
                    . '</div>'
                    . '<p style="color:#64748b; font-size:12px; margin-top:20px;">This code will expire in 15 minutes. If you did not request a password reset, please ignore this email.</p>'
                    . '</div>';
                
                $sent = $mailService->sendEmail($toEmail, 'Your Password Reset OTP - Printmont', $bodyHtml, 'Password Reset', 'Printmont');
                if ($sent) return true;
            } catch (Throwable $t) {
                error_log("MailService in sendOtpEmail error: " . $t->getMessage());
            }
        }

        // Fallback if MailService fails or is unavailable
        $config = null;
        try {
            $res = $this->db->query(
                "SELECT * FROM email_configurations WHERE purpose = 'Password Reset' AND status = 'active' LIMIT 1"
            );
            if ($res && $res->num_rows > 0) {
                $config = $res->fetch_assoc();
            } else {
                $resFallback = $this->db->query(
                    "SELECT * FROM email_configurations WHERE status = 'active' ORDER BY id ASC LIMIT 1"
                );
                if ($resFallback && $resFallback->num_rows > 0) {
                    $config = $resFallback->fetch_assoc();
                }
            }
        } catch (Throwable $t) {
            error_log("sendOtpEmail DB error: " . $t->getMessage());
        }

        $smtpHost = $config['smtp_host'] ?? $config['mail_host'] ?? $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: '';
        $smtpUser = $config['smtp_user'] ?? $config['mail_username'] ?? $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: '';
        $smtpPass = $config['smtp_pass'] ?? $config['mail_password'] ?? $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '';
        $smtpPort = (int) ($config['smtp_port'] ?? $config['mail_port'] ?? $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587);
        $fromEmail = $config['from_email'] ?? $config['mail_from_address'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'noreply@printmont.com';
        $fromName = $config['from_name'] ?? $config['mail_from_name'] ?? $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'Printmont';

        $subject = 'Your Password Reset Code - Printmont';
        $bodyHtml = '<p>Hi ' . htmlspecialchars($toName ?: 'User') . ',</p>'
            . '<p>Your password reset OTP code is:</p>'
            . '<h2 style="letter-spacing:4px; color:#3b82f6;">' . htmlspecialchars($otp) . '</h2>'
            . '<p>This code expires in 15 minutes. If you did not request this, please ignore this email.</p>';

        if (!empty($smtpHost) && !empty($smtpUser)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $encryption = strtolower($config['encryption'] ?? $config['mail_encryption'] ?? $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
                $mail->SMTPSecure = ($encryption === 'ssl' || $smtpPort === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtpPort;

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail, $toName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $bodyHtml;
                $mail->AltBody = "Your password reset code is: $otp (expires in 15 minutes)";

                $mail->send();
                return true;
            } catch (Throwable $e) {
                error_log('sendOtpEmail PHPMailer failed: ' . $e->getMessage());
            }
        }

        // Native PHP mail() fallback
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";

        return @mail($toEmail, $subject, $bodyHtml, $headers);
    }

     // Get all addresses for user
    public function getAddresses($user_id) {
        try {
            $addresses = $this->addressModel->getAddressesByUserId($user_id);
            
            return [
                'success' => true,
                'addresses' => $addresses
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch addresses: ' . $e->getMessage()
            ];
        }
    }

    // Add new address
    public function addAddress($user_id, $data) {
        try {
            // Validate required fields
            $required = ['name', 'phone', 'pincode', 'locality', 'address', 'city', 'state'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("Field $field is required");
                }
            }

            // Additional validation
            $validationErrors = $this->addressModel->validateAddress($data);
            if (!empty($validationErrors)) {
                throw new Exception(implode(', ', $validationErrors));
            }

            $addressData = [
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'pincode' => trim($data['pincode']),
                'locality' => trim($data['locality']),
                'address' => trim($data['address']),
                'city' => trim($data['city']),
                'state' => trim($data['state']),
                'landmark' => isset($data['landmark']) ? trim($data['landmark']) : '',
                'altPhone' => isset($data['altPhone']) ? trim($data['altPhone']) : '',
                'type' => isset($data['type']) ? $data['type'] : 'Home'
            ];

            $addressId = $this->addressModel->addAddress($user_id, $addressData);

            if ($addressId) {
                return [
                    'success' => true,
                    'message' => 'Address added successfully',
                    'address_id' => $addressId
                ];
            } else {
                throw new Exception("Failed to add address");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Update address
    public function updateAddress($user_id, $data) {
        try {
            if (!isset($data['id']) || empty($data['id'])) {
                throw new Exception("Address ID is required");
            }

            // Validate required fields
            $required = ['name', 'phone', 'pincode', 'locality', 'address', 'city', 'state'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    throw new Exception("Field $field is required");
                }
            }

            // Additional validation
            $validationErrors = $this->addressModel->validateAddress($data);
            if (!empty($validationErrors)) {
                throw new Exception(implode(', ', $validationErrors));
            }

            $addressData = [
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'pincode' => trim($data['pincode']),
                'locality' => trim($data['locality']),
                'address' => trim($data['address']),
                'city' => trim($data['city']),
                'state' => trim($data['state']),
                'landmark' => isset($data['landmark']) ? trim($data['landmark']) : '',
                'altPhone' => isset($data['altPhone']) ? trim($data['altPhone']) : '',
                'type' => isset($data['type']) ? $data['type'] : 'Home'
            ];

            $success = $this->addressModel->updateAddress($data['id'], $user_id, $addressData);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Address updated successfully'
                ];
            } else {
                throw new Exception("Failed to update address");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Delete address
    public function deleteAddress($user_id, $data) {
        try {
            if (!isset($data['id']) || empty($data['id'])) {
                throw new Exception("Address ID is required");
            }

            $success = $this->addressModel->deleteAddress($data['id'], $user_id);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Address deleted successfully'
                ];
            } else {
                throw new Exception("Failed to delete address");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Set default address
    public function setDefaultAddress($user_id, $data) {
        try {
            if (!isset($data['id']) || empty($data['id'])) {
                throw new Exception("Address ID is required");
            }

            $success = $this->addressModel->setDefaultAddress($data['id'], $user_id);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Default address set successfully'
                ];
            } else {
                throw new Exception("Failed to set default address");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    
}
?>