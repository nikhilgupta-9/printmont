<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Banner ID is required!";
    header('Location: view-banner.php');
    exit;
}

// Create banner controller
$bannerController = new BannerController();
$result = $bannerController->deleteBanner($_GET['id']);

if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
} else {
    $_SESSION['error_message'] = $result['error'];
}

header('Location: view-banner.php');
exit;
?>