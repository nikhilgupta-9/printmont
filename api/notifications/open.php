<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../controllers/NotificationController.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // If not logged in, redirect to login or return error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    header('Location: ../../index.php');
    exit;
}

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? null;

$notificationController = new NotificationController();

if ($id) {
    $notificationController->markAsRead($id, $user_id);
    
    // If redirect is not provided, look up notification link
    if (empty($redirect) || $redirect === '#') {
        $notification = $notificationController->getNotification($id);
        if ($notification && !empty($notification['link']) && $notification['link'] !== '#') {
            $redirect = $notification['link'];
        }
    }
}

// Check if AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => $redirect]);
    exit;
}

// Fallback redirect
if (empty($redirect) || $redirect === '#') {
    $redirect = '../../notifications.php';
}

header('Location: ' . $redirect);
exit;
