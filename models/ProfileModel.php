<?php
require_once(__DIR__ . '/../config/database.php');

class ProfileModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUserById($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        $query = "
            SELECT 
                id, username, email, role, status, 
                first_name, last_name, profile_picture, phone, bio, 
                location, website, date_of_birth, gender,
                twitter_url, facebook_url, linkedin_url, instagram_url,
                email_notifications, sms_notifications,
                last_login, created_at, updated_at
            FROM users 
            WHERE id = '$user_id'
        ";

        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    public function updateProfile($user_id, $data) {
        // Escape all data
        $user_id = $this->conn->real_escape_string($user_id);
        $first_name = $this->conn->real_escape_string($data['first_name'] ?? '');
        $last_name = $this->conn->real_escape_string($data['last_name'] ?? '');
        $phone = $this->conn->real_escape_string($data['phone'] ?? '');
        $bio = $this->conn->real_escape_string($data['bio'] ?? '');
        $location = $this->conn->real_escape_string($data['location'] ?? '');
        $website = $this->conn->real_escape_string($data['website'] ?? '');
        $date_of_birth = $this->conn->real_escape_string($data['date_of_birth'] ?? '');
        $gender = $this->conn->real_escape_string($data['gender'] ?? '');
        $twitter_url = $this->conn->real_escape_string($data['twitter_url'] ?? '');
        $facebook_url = $this->conn->real_escape_string($data['facebook_url'] ?? '');
        $linkedin_url = $this->conn->real_escape_string($data['linkedin_url'] ?? '');
        $instagram_url = $this->conn->real_escape_string($data['instagram_url'] ?? '');
        $email_notifications = isset($data['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($data['sms_notifications']) ? 1 : 0;

        $query = "
            UPDATE users SET 
                first_name = '$first_name', 
                last_name = '$last_name', 
                phone = '$phone', 
                bio = '$bio', 
                location = '$location', 
                website = '$website', 
                date_of_birth = " . ($date_of_birth ? "'$date_of_birth'" : "NULL") . ", 
                gender = '$gender',
                twitter_url = '$twitter_url', 
                facebook_url = '$facebook_url', 
                linkedin_url = '$linkedin_url', 
                instagram_url = '$instagram_url',
                email_notifications = $email_notifications, 
                sms_notifications = $sms_notifications, 
                updated_at = NOW()
            WHERE id = '$user_id'
        ";
        
        return $this->conn->query($query);
    }

    public function updateProfilePicture($user_id, $profile_picture) {
        $user_id = $this->conn->real_escape_string($user_id);
        $profile_picture = $this->conn->real_escape_string($profile_picture);
        
        $query = "UPDATE users SET profile_picture = '$profile_picture', updated_at = NOW() WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function changePassword($user_id, $new_password_hash) {
        $user_id = $this->conn->real_escape_string($user_id);
        $new_password_hash = $this->conn->real_escape_string($new_password_hash);
        
        $query = "UPDATE users SET password = '$new_password_hash', updated_at = NOW() WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function getUserActivities($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "
            SELECT 
                'login' as type,
                last_login as date,
                CONCAT('Last login: ', COALESCE(DATE_FORMAT(last_login, '%M %d, %Y at %h:%i %p'), 'Never')) as description
            FROM users 
            WHERE id = '$user_id'
            UNION ALL
            SELECT 
                'profile_update' as type,
                updated_at as date,
                'Profile information updated' as description
            FROM users 
            WHERE id = '$user_id' AND updated_at IS NOT NULL
            ORDER BY date DESC 
            LIMIT 10
        ";

        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            return $activities;
        }
        
        return [];
    }

    public function verifyCurrentPassword($user_id, $password) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "SELECT password FROM users WHERE id = '$user_id'";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return password_verify($password, $user['password']);
        }
        
        return false;
    }

    public function checkEmailExists($email, $exclude_user_id = null) {
        $email = $this->conn->real_escape_string($email);
        $exclude_condition = '';
        
        if ($exclude_user_id) {
            $exclude_user_id = $this->conn->real_escape_string($exclude_user_id);
            $exclude_condition = " AND id != '$exclude_user_id'";
        }
        
        $query = "SELECT id FROM users WHERE email = '$email' $exclude_condition";
        $result = $this->conn->query($query);
        
        return $result && $result->num_rows > 0;
    }

    public function checkUsernameExists($username, $exclude_user_id = null) {
        $username = $this->conn->real_escape_string($username);
        $exclude_condition = '';
        
        if ($exclude_user_id) {
            $exclude_user_id = $this->conn->real_escape_string($exclude_user_id);
            $exclude_condition = " AND id != '$exclude_user_id'";
        }
        
        $query = "SELECT id FROM users WHERE username = '$username' $exclude_condition";
        $result = $this->conn->query($query);
        
        return $result && $result->num_rows > 0;
    }

    public function getUsersCount() {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        
        return 0;
    }

    public function getAllUsers($limit = null, $offset = 0) {
        $limit_clause = '';
        if ($limit) {
            $limit = (int)$limit;
            $offset = (int)$offset;
            $limit_clause = " LIMIT $offset, $limit";
        }
        
        $query = "SELECT id, username, email, role, status, first_name, last_name, created_at, last_login FROM users ORDER BY created_at DESC" . $limit_clause;
        $result = $this->conn->query($query);
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }

    public function searchUsers($search_term, $limit = null, $offset = 0) {
        $search_term = $this->conn->real_escape_string($search_term);
        $limit_clause = '';
        
        if ($limit) {
            $limit = (int)$limit;
            $offset = (int)$offset;
            $limit_clause = " LIMIT $offset, $limit";
        }
        
        $query = "
            SELECT id, username, email, role, status, first_name, last_name, created_at, last_login 
            FROM users 
            WHERE 
                username LIKE '%$search_term%' OR 
                email LIKE '%$search_term%' OR 
                first_name LIKE '%$search_term%' OR 
                last_name LIKE '%$search_term%'
            ORDER BY created_at DESC" . $limit_clause;
            
        $result = $this->conn->query($query);
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }

    public function updateUserStatus($user_id, $status) {
        $user_id = $this->conn->real_escape_string($user_id);
        $status = $this->conn->real_escape_string($status);
        
        $query = "UPDATE users SET status = '$status', updated_at = NOW() WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function updateUserRole($user_id, $role) {
        $user_id = $this->conn->real_escape_string($user_id);
        $role = $this->conn->real_escape_string($role);
        
        $query = "UPDATE users SET role = '$role', updated_at = NOW() WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function deleteUser($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "DELETE FROM users WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function createUser($user_data) {
        // Escape all data
        $username = $this->conn->real_escape_string($user_data['username']);
        $email = $this->conn->real_escape_string($user_data['email']);
        $password_hash = $this->conn->real_escape_string($user_data['password_hash']);
        $first_name = $this->conn->real_escape_string($user_data['first_name'] ?? '');
        $last_name = $this->conn->real_escape_string($user_data['last_name'] ?? '');
        $role = $this->conn->real_escape_string($user_data['role'] ?? 'user');
        $status = $this->conn->real_escape_string($user_data['status'] ?? 'active');

        $query = "
            INSERT INTO users (
                username, email, password, first_name, last_name, role, status, created_at
            ) VALUES (
                '$username', '$email', '$password_hash', '$first_name', '$last_name', '$role', '$status', NOW()
            )
        ";

        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    public function updateLastLogin($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "UPDATE users SET last_login = NOW() WHERE id = '$user_id'";
        return $this->conn->query($query);
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>