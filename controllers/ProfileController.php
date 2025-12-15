<?php
require_once(__DIR__ . '/../models/ProfileModel.php');

class ProfileController {
    private $profileModel;

    public function __construct() {
        $this->profileModel = new ProfileModel();
    }

    public function getUserProfile($user_id) {
        return $this->profileModel->getUserById($user_id);
    }

    public function updateProfile($user_id, $data) {
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            return ['success' => false, 'message' => 'First name and last name are required.'];
        }

        // Sanitize data
        $profileData = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'phone' => trim($data['phone'] ?? ''),
            'bio' => trim($data['bio'] ?? ''),
            'location' => trim($data['location'] ?? ''),
            'website' => trim($data['website'] ?? ''),
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? '',
            'twitter_url' => trim($data['twitter_url'] ?? ''),
            'facebook_url' => trim($data['facebook_url'] ?? ''),
            'linkedin_url' => trim($data['linkedin_url'] ?? ''),
            'instagram_url' => trim($data['instagram_url'] ?? ''),
            'email_notifications' => isset($data['email_notifications']) ? 1 : 0,
            'sms_notifications' => isset($data['sms_notifications']) ? 1 : 0
        ];

        try {
            $result = $this->profileModel->updateProfile($user_id, $profileData);
            
            if ($result) {
                return ['success' => true, 'message' => 'Profile updated successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()];
        }
    }

    public function updateProfilePicture($user_id, $file) {
        if (empty($file['name'])) {
            return ['success' => false, 'message' => 'No file selected.'];
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size too large. Maximum size is 2MB.'];
        }

        // Create upload directory
        $upload_dir = 'uploads/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Get current profile picture to delete old one
            $current_user = $this->profileModel->getUserById($user_id);
            
            // Delete old profile picture if exists
            if (!empty($current_user['profile_picture']) && file_exists($current_user['profile_picture'])) {
                unlink($current_user['profile_picture']);
            }

            // Update database
            $result = $this->profileModel->updateProfilePicture($user_id, $file_path);
            
            if ($result) {
                return ['success' => true, 'message' => 'Profile picture updated successfully!', 'file_path' => $file_path];
            } else {
                // Delete the uploaded file if database update fails
                unlink($file_path);
                return ['success' => false, 'message' => 'Failed to update profile picture in database.'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }

    public function changePassword($user_id, $current_password, $new_password) {
        // Validate passwords
        if (empty($current_password) || empty($new_password)) {
            return ['success' => false, 'message' => 'All password fields are required.'];
        }

        if (strlen($new_password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        // Verify current password
        if (!$this->profileModel->verifyCurrentPassword($user_id, $current_password)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // Hash new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $result = $this->profileModel->changePassword($user_id, $new_password_hash);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password.'];
        }
    }

    public function getUserActivities($user_id) {
        return $this->profileModel->getUserActivities($user_id);
    }

    public function getProfileStats($user_id) {
        $user = $this->profileModel->getUserById($user_id);
        
        if (!$user) {
            return null;
        }

        return [
            'member_since' => $user['created_at'],
            'status' => $user['status'],
            'role' => $user['role'],
            'last_activity' => $user['last_login']
        ];
    }

    // Additional methods for user management
    public function getAllUsers($limit = null, $offset = 0) {
        return $this->profileModel->getAllUsers($limit, $offset);
    }

    public function getUsersCount() {
        return $this->profileModel->getUsersCount();
    }

    public function searchUsers($search_term, $limit = null, $offset = 0) {
        return $this->profileModel->searchUsers($search_term, $limit, $offset);
    }

    public function updateUserStatus($user_id, $status) {
        return $this->profileModel->updateUserStatus($user_id, $status);
    }

    public function updateUserRole($user_id, $role) {
        return $this->profileModel->updateUserRole($user_id, $role);
    }

    public function deleteUser($user_id) {
        return $this->profileModel->deleteUser($user_id);
    }

    public function createUser($user_data) {
        // Validate required fields
        if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['password'])) {
            return ['success' => false, 'message' => 'Username, email, and password are required.'];
        }

        // Check if username or email already exists
        if ($this->profileModel->checkUsernameExists($user_data['username'])) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        if ($this->profileModel->checkEmailExists($user_data['email'])) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Hash password
        $user_data['password_hash'] = password_hash($user_data['password'], PASSWORD_DEFAULT);

        // Create user
        $user_id = $this->profileModel->createUser($user_data);
        
        if ($user_id) {
            return ['success' => true, 'message' => 'User created successfully!', 'user_id' => $user_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
    }
}
?>