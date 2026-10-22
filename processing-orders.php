<?php
if (!isset($_GET['status'])) {
    $_GET['status'] = 'processing';
}
require_once __DIR__ . '/orders.php';