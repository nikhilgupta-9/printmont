<?php
if (!isset($_GET['payment_status'])) {
    $_GET['payment_status'] = 'paid';
}
require_once __DIR__ . '/orders.php';
