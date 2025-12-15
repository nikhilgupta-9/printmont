<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AddressModel.php';
class AuthController {
    private $userModel;
    private $addressModel; // Add this property

    public function __construct($db) {
        $this->userModel = new UserModel($db);
          $this->addressModel = new AddressModel($db); // Initialize AddressModel
        $this->addressModel->createTable(); // Ensure table exists
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

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
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

            // Remove password from response
            unset($user['password']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $this->generateToken($user['id'])
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Get User Profile
    public function getProfile($userId) {
        try {
            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Remove sensitive data from response
            unset($user['password']);
            unset($user['reset_token']);
            unset($user['reset_token_expiry']);
            unset($user['reset_otp']);
            unset($user['reset_otp_expiry']);
            unset($user['login_attempts']);
            unset($user['last_login_attempt']);
            unset($user['lock_until']);

            return [
                'success' => true,
                'user' => $user
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

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                throw new Exception("Failed to update profile");
            }

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

    // Simple token generation
    // Enhanced token generation with timestamp
    private function generateToken($userId) {
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        return base64_encode($userId . ':' . $timestamp . ':' . $random);
    }
    

    // Verify token
    public function verifyToken($token) {
        try {
            if (empty($token)) {
                throw new Exception("No token provided");
            }

            // Remove 'Bearer ' prefix if present
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }

            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) === 3) {
                $userId = $parts[0];
                $timestamp = $parts[1];
                
                // Check if token is expired (24 hours)
                if (time() - $timestamp > 86400) {
                    throw new Exception("Token expired");
                }

                $user = $this->userModel->getUserById($userId);
                
                if ($user && $user['status'] === 'active') {
                    // Remove sensitive data
                    unset($user['password']);
                    unset($user['reset_token']);
                    unset($user['reset_token_expiry']);
                    unset($user['reset_otp']);
                    unset($user['reset_otp_expiry']);
                    
                    return [
                        'success' => true,
                        'user' => $user
                    ];
                }
            }
            
            throw new Exception("Invalid token");
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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