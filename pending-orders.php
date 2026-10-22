<?php
if (!isset($_GET['status'])) {
    $_GET['status'] = 'pending';
}
require_once __DIR__ . '/orders.php';