<?php
session_start();
require_once '../../config/database.php';             // your DB connection file
require_once '../../controllers/AuthController.php';

// Pass DB connection to controller
$authController = new AuthController($conn);

$authController->logout(true);
