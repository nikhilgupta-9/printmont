<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../controllers/NotificationController.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'notifications' => [], 'unread_count' => 0]);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;

$notificationController = new NotificationController();
$rawNotifications = $notificationController->getUserNotifications($user_id, $limit, $unread_only);
$unread_count = $notificationController->getUnreadCount($user_id);

$notifications = [];
foreach ($rawNotifications as $n) {
    $notifications[] = [
        'id' => $n['id'],
        'title' => $n['title'],
        'message' => $n['message'],
        'type' => $n['type'],
        'icon' => $notificationController->getNotificationIcon($n['type'], $n['icon']),
        'color' => $notificationController->getNotificationColor($n['type']),
        'link' => $n['link'] ?: 'notifications.php',
        'is_read' => (bool)$n['is_read'],
        'time_ago' => $notificationController->formatNotificationTime($n['created_at']),
        'created_at' => $n['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
exit;
