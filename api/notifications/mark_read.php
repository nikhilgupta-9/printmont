<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../controllers/NotificationController.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? $_POST['notification_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

$notificationController = new NotificationController();
$result = $notificationController->markAsRead($notification_id, $user_id);
$unread_count = $notificationController->getUnreadCount($user_id);

echo json_encode([
    'success' => (bool)$result,
    'unread_count' => $unread_count
]);
exit;
