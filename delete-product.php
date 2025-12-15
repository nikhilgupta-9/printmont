<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/ProductController.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Product ID is required!";
    header('Location: view-products.php');
    exit;
}

$productController = new ProductController();
$result = $productController->deleteProduct($_GET['id']);

if ($result['success']) {
    $_SESSION['success_message'] = "Product deleted successfully!";
} else {
    $_SESSION['error_message'] = $result['error'];
}

header('Location: view-products.php');
exit;
?>