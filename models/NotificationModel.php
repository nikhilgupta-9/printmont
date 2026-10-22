<?php
require_once(__DIR__ . '/../config/database.php');

class NotificationModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        $user_id = $this->conn->real_escape_string($user_id);
        $limit = (int)$limit;
        
        $where_condition = "WHERE n.user_id = '$user_id' OR n.user_id IS NULL";
        if ($unread_only) {
            $where_condition .= " AND (nr.id IS NULL OR nr.read_at IS NULL)";
        }
        
        $query = "
            SELECT 
                n.id,
                n.title,
                n.message,
                n.type,
                n.icon,
                n.link,
                n.created_at,
                nr.read_at,
                CASE 
                    WHEN nr.read_at IS NULL THEN 0 
                    ELSE 1 
                END as is_read
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = '$user_id'
            $where_condition
            ORDER BY n.created_at DESC
            LIMIT $limit
        ";

        $result = $this->conn->query($query);
        
        $notifications = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        
        return $notifications;
    }

    public function getPaginatedNotifications($user_id, $page = 1, $limit = 10, $filter = 'all') {
        $user_id = $this->conn->real_escape_string($user_id);
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE (n.user_id = '$user_id' OR n.user_id IS NULL)";
        
        if ($filter === 'unread') {
            $where .= " AND (nr.id IS NULL OR nr.read_at IS NULL)";
        } elseif ($filter === 'orders') {
            $where .= " AND n.type IN ('order', 'success')";
        } elseif ($filter === 'users') {
            $where .= " AND n.type IN ('user', 'info')";
        } elseif ($filter === 'system') {
            $where .= " AND n.type NOT IN ('order', 'success', 'user', 'info')";
        }
        
        // Count total filtered
        $count_query = "
            SELECT COUNT(DISTINCT n.id) as total
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = '$user_id'
            $where
        ";
        $count_result = $this->conn->query($count_query);
        $total = 0;
        if ($count_result && $row = $count_result->fetch_assoc()) {
            $total = (int)$row['total'];
        }
        
        // Fetch records
        $query = "
            SELECT 
                n.id,
                n.title,
                n.message,
                n.type,
                n.icon,
                n.link,
                n.created_at,
                nr.read_at,
                CASE 
                    WHEN nr.read_at IS NULL THEN 0 
                    ELSE 1 
                END as is_read
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = '$user_id'
            $where
            ORDER BY n.created_at DESC
            LIMIT $offset, $limit
        ";
        
        $result = $this->conn->query($query);
        $notifications = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
        
        $total_pages = ($total > 0) ? (int)ceil($total / $limit) : 1;
        
        return [
            'data' => $notifications,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $total_pages,
            'offset' => $offset
        ];
    }

    public function getUnreadCount($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "
            SELECT COUNT(*) as unread_count
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = '$user_id'
            WHERE (n.user_id = '$user_id' OR n.user_id IS NULL) 
            AND (nr.id IS NULL OR nr.read_at IS NULL)
        ";

        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['unread_count'];
        }
        
        return 0;
    }

    public function getNotificationById($notification_id) {
        $notification_id = $this->conn->real_escape_string($notification_id);
        $query = "SELECT * FROM notifications WHERE id = '$notification_id' LIMIT 1";
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getNotificationDetail($notification_id, $user_id) {
        $notification_id = $this->conn->real_escape_string($notification_id);
        $user_id = $this->conn->real_escape_string($user_id);
        
        $query = "
            SELECT 
                n.id,
                n.title,
                n.message,
                n.type,
                n.icon,
                n.link,
                n.created_at,
                nr.read_at,
                CASE 
                    WHEN nr.read_at IS NULL THEN 0 
                    ELSE 1 
                END as is_read
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = '$user_id'
            WHERE n.id = '$notification_id'
            LIMIT 1
        ";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function markAsRead($notification_id, $user_id) {
        $notification_id = $this->conn->real_escape_string($notification_id);
        $user_id = $this->conn->real_escape_string($user_id);
        
        // Check if record already exists
        $check_query = "SELECT id FROM notification_reads WHERE notification_id = '$notification_id' AND user_id = '$user_id'";
        $check_result = $this->conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing record
            $query = "UPDATE notification_reads SET read_at = NOW() WHERE notification_id = '$notification_id' AND user_id = '$user_id'";
        } else {
            // Insert new record
            $query = "INSERT INTO notification_reads (notification_id, user_id, read_at) VALUES ('$notification_id', '$user_id', NOW())";
        }
        
        return $this->conn->query($query);
    }

    public function markAllAsRead($user_id) {
        $user_id = $this->conn->real_escape_string($user_id);
        
        // Get all unread notifications for this user
        $unread_notifications = $this->getUserNotifications($user_id, 1000, true);
        
        foreach ($unread_notifications as $notification) {
            $this->markAsRead($notification['id'], $user_id);
        }
        
        return true;
    }

    public function createNotification($data) {
        $title = $this->conn->real_escape_string($data['title']);
        $message = $this->conn->real_escape_string($data['message']);
        $type = $this->conn->real_escape_string($data['type'] ?? 'info');
        $icon = $this->conn->real_escape_string($data['icon'] ?? 'bell');
        $link = $this->conn->real_escape_string($data['link'] ?? '');
        $user_id = isset($data['user_id']) ? "'" . $this->conn->real_escape_string($data['user_id']) . "'" : "NULL";
        
        $query = "
            INSERT INTO notifications (title, message, type, icon, link, user_id, created_at) 
            VALUES ('$title', '$message', '$type', '$icon', '$link', $user_id, NOW())
        ";

        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        
        return false;
    }

    public function deleteNotification($notification_id) {
        $notification_id = $this->conn->real_escape_string($notification_id);
        
        // Delete reads first
        $delete_reads = "DELETE FROM notification_reads WHERE notification_id = '$notification_id'";
        $this->conn->query($delete_reads);
        
        // Delete notification
        $query = "DELETE FROM notifications WHERE id = '$notification_id'";
        return $this->conn->query($query);
    }

    public function getNotificationTypes() {
        return [
            'info' => ['color' => 'primary', 'icon' => 'info'],
            'success' => ['color' => 'success', 'icon' => 'check-circle'],
            'warning' => ['color' => 'warning', 'icon' => 'alert-triangle'],
            'error' => ['color' => 'danger', 'icon' => 'alert-circle'],
            'order' => ['color' => 'info', 'icon' => 'shopping-cart'],
            'user' => ['color' => 'success', 'icon' => 'user'],
            'system' => ['color' => 'secondary', 'icon' => 'settings']
        ];
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>