<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $status;
    public $login_attempts;
    public $last_login_attempt;
    public $lock_until;
    public $created_at;
    public $last_login;
    public $reset_token;
    public $reset_token_expiry;
    public $reset_otp;
    public $reset_otp_expiry;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create new user
     */
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                SET username=?, email=?, password=?, role=?, status=?, created_at=NOW(), updated_at=NOW()";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bind_param("sssss", $this->username, $this->email, $hashed_password, $this->role, $this->status);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Get user by email
     */
    public function getByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->setProperties($row);
            return true;
        }
        return false;
    }

    /**
     * Get user by ID
     */
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->setProperties($row);
            return true;
        }
        return false;
    }

    /**
     * Update user
     */
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                SET username=?, email=?, role=?, status=?, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bind_param("ssssi", $this->username, $this->email, $this->role, $this->status, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update password
     */
    public function updatePassword($new_password)
    {
        $query = "UPDATE " . $this->table_name . "
                SET password=?, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt->bind_param("si", $hashed_password, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Update login attempts
     */
    public function updateLoginAttempts($attempts)
    {
        $query = "UPDATE " . $this->table_name . "
                SET login_attempts=?, last_login_attempt=NOW(), updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $attempts, $this->id);
        return $stmt->execute();
    }

    /**
     * Reset login attempts
     */
    public function resetLoginAttempts()
    {
        $query = "UPDATE " . $this->table_name . "
                SET login_attempts=0, lock_until=NULL, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    /**
     * Set lock until timestamp
     */
    public function setLockUntil($lock_until)
    {
        $query = "UPDATE " . $this->table_name . "
                SET lock_until=?, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $lock_until, $this->id);
        return $stmt->execute();
    }

    /**
     * Update last login
     */
    public function updateLastLogin()
    {
        $query = "UPDATE " . $this->table_name . "
                SET last_login=NOW(), updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    /**
     * Set reset token
     */
    public function setResetToken($token, $expiry)
    {
        $query = "UPDATE " . $this->table_name . "
                SET reset_token=?, reset_token_expiry=?, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $token, $expiry, $this->id);
        return $stmt->execute();
    }

    /**
     * Set reset OTP
     */
    public function setResetOTP($otp, $expiry)
    {
        $query = "UPDATE " . $this->table_name . "
                SET reset_otp=?, reset_otp_expiry=?, updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $otp, $expiry, $this->id);
        return $stmt->execute();
    }

    /**
     * Clear reset data
     */
    public function clearResetData()
    {
        $query = "UPDATE " . $this->table_name . "
                SET reset_token=NULL, reset_token_expiry=NULL, 
                    reset_otp=NULL, reset_otp_expiry=NULL,
                    updated_at=NOW()
                WHERE id=?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * Check if account is locked
     */
    public function isLocked()
    {
        if ($this->lock_until && strtotime($this->lock_until) > time()) {
            return true;
        }
        return false;
    }

    /**
     * Check if OTP is valid
     */
    public function isOTPValid($otp)
    {
        if ($this->reset_otp === $otp && $this->reset_otp_expiry && strtotime($this->reset_otp_expiry) > time()) {
            return true;
        }
        return false;
    }

    /**
     * Check if reset token is valid
     */
    public function isResetTokenValid($token)
    {
        if ($this->reset_token === $token && $this->reset_token_expiry && strtotime($this->reset_token_expiry) > time()) {
            return true;
        }
        return false;
    }

    /**
     * Set object properties from array
     */
    private function setProperties($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get user as array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'last_login' => $this->last_login,
            'created_at' => $this->created_at
        ];
    }
}
?>