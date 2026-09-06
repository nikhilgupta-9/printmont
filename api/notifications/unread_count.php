<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../controllers/NotificationController.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'unread_count' => 0]);
    exit;
}

$notificationController = new NotificationController();
$unread_count = $notificationController->getUnreadCount($user_id);

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count
]);
exit;
