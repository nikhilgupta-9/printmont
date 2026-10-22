<?php
if (!isset($_GET['status'])) {
    $_GET['status'] = 'cancelled';
}
require_once __DIR__ . '/orders.php';