<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/OrderController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid order ID!";
    header("Location: orders.php");
    exit();
}

$orderId = (int)$_GET['id'];
$orderController = new OrderController();

if ($orderController->deleteOrder($orderId)) {
    $_SESSION['success_message'] = "Order deleted successfully!";
} else {
    $_SESSION['error_message'] = "Failed to delete order.";
}

header("Location: orders.php");
exit();
?>