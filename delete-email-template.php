<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/EmailController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $emailController = new EmailController();
    if ($emailController->deleteTemplate($id)) {
        $_SESSION['success_message'] = "Email template #{$id} deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Could not delete email template. Please try again.";
    }
} else {
    $_SESSION['error_message'] = "Invalid email template ID specified.";
}

header("Location: email-templates.php");
exit;
