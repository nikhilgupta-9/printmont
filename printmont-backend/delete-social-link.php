<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SocialLinkController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid social link ID!";
    header("Location: social-links.php");
    exit();
}

$linkId = (int)$_GET['id'];
$socialLinkController = new SocialLinkController();

if ($socialLinkController->deleteSocialLink($linkId)) {
    $_SESSION['success_message'] = "Social link deleted successfully!";
} else {
    $_SESSION['error_message'] = "Failed to delete social link.";
}

header("Location: social-links.php");
exit();
?>