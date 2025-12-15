<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterSectionController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid section ID!";
    header("Location: footer-sections.php");
    exit();
}

$sectionId = (int)$_GET['id'];
$sectionController = new FooterSectionController();

if ($sectionController->deleteSection($sectionId)) {
    $_SESSION['success_message'] = "Footer section deleted successfully!";
} else {
    $_SESSION['error_message'] = "Failed to delete footer section.";
}

header("Location: footer-sections.php");
exit();
?>