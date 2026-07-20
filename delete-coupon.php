<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CouponController.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Coupon ID is required!";
    header('Location: coupons.php');
    exit;
}

$couponController = new CouponController();
$result = $couponController->deleteCoupon((int)$_GET['id']);

if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
} else {
    $_SESSION['error_message'] = $result['error'];
}

header('Location: coupons.php');
exit;
