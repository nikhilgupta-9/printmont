<?php
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

$result = $authController->logout();

// Redirect to login page
header("Location: login.php");
exit();
?>