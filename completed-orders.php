<?php
if (!isset($_GET['status'])) {
    $_GET['status'] = 'delivered';
}
require_once __DIR__ . '/orders.php';