<?php
if (!isset($_GET['status'])) {
    $_GET['status'] = 'refunded';
}
require_once __DIR__ . '/orders.php';