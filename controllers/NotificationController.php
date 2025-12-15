<?php
require_once(__DIR__ . '/../models/NotificationModel.php');

class NotificationController {
    private $notificationModel;

    public function __construct() {
        $this->notificationModel = new NotificationModel();
    }

    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        return $this->notificationModel->getUserNotifications($user_id, $limit, $unread_only);
    }

    public function getUnreadCount($user_id) {
        return $this->notificationModel->getUnreadCount($user_id);
    }

    public function markAsRead($notification_id, $user_id) {
        return $this->notificationModel->markAsRead($notification_id, $user_id);
    }

    public function markAllAsRead($user_id) {
        return $this->notificationModel->markAllAsRead($user_id);
    }

    public function createNotification($data) {
        // Validate required fields
        if (empty($data['title']) || empty($data['message'])) {
            return ['success' => false, 'message' => 'Title and message are required.'];
        }

        // Set default values
        $notificationData = [
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? 'info',
            'icon' => $data['icon'] ?? 'bell',
            'link' => $data['link'] ?? '',
            'user_id' => $data['user_id'] ?? null
        ];

        $notificationId = $this->notificationModel->createNotification($notificationData);
        
        if ($notificationId) {
            return ['success' => true, 'message' => 'Notification created successfully.', 'notification_id' => $notificationId];
        } else {
            return ['success' => false, 'message' => 'Failed to create notification.'];
        }
    }

    public function formatNotificationTime($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' min ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }

    public function getNotificationIcon($type, $custom_icon = null) {
        $types = $this->notificationModel->getNotificationTypes();
        
        if ($custom_icon) {
            return $custom_icon;
        }
        
        return $types[$type]['icon'] ?? 'bell';
    }

    public function getNotificationColor($type) {
        $types = $this->notificationModel->getNotificationTypes();
        return $types[$type]['color'] ?? 'primary';
    }

    // System notification methods
    public function notifyNewOrder($order_id, $order_total, $customer_name) {
        $data = [
            'title' => 'New Order Received',
            'message' => "New order #{$order_id} from {$customer_name} for ₹{$order_total}",
            'type' => 'order',
            'icon' => 'shopping-cart',
            'link' => "orders.php?action=view&id={$order_id}"
        ];
        
        return $this->createNotification($data);
    }

    public function notifyLowStock($product_id, $product_name, $current_stock) {
        $data = [
            'title' => 'Low Stock Alert',
            'message' => "{$product_name} is running low. Only {$current_stock} items left.",
            'type' => 'warning',
            'icon' => 'package',
            'link' => "view-products.php?action=edit&id={$product_id}"
        ];
        
        return $this->createNotification($data);
    }

    public function notifySystemUpdate($update_message) {
        $data = [
            'title' => 'System Update',
            'message' => $update_message,
            'type' => 'system',
            'icon' => 'refresh-cw',
            'link' => 'system-updates.php'
        ];
        
        return $this->createNotification($data);
    }

    public function notifyNewUser($user_id, $username) {
        $data = [
            'title' => 'New User Registration',
            'message' => "New user {$username} has registered.",
            'type' => 'user',
            'icon' => 'user-plus',
            'link' => "customers.php?action=view&id={$user_id}"
        ];
        
        return $this->createNotification($data);
    }
}
?>