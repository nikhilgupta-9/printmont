<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

$logo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'logo-management.php';

if ($logo_id > 0) {
    $result = $logoController->activateLogo($logo_id);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        $_SESSION['flash_message'] = [
            'message' => $result['message'],
            'type' => 'success'
        ];
    } else {
        $_SESSION['error_message'] = $result['message'];
        $_SESSION['flash_message'] = [
            'message' => $result['message'],
            'type' => 'danger'
        ];
    }
} else {
    $_SESSION['error_message'] = 'Invalid asset ID!';
    $_SESSION['flash_message'] = [
        'message' => 'Invalid asset ID!',
        'type' => 'danger'
    ];
}

// Redirect back
$allowedRedirects = ['logo-management.php', 'favicon-settings.php'];
if (!in_array($redirect, $allowedRedirects)) {
    $redirect = 'logo-management.php';
}

header("Location: " . $redirect);
exit();
?>
