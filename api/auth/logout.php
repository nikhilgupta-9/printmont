<?php
session_start();
require_once '../controllers/AuthController.php'; // Include your controller file

// Create instance of your controller class
$authController = new AuthController(); // Adjust class name as needed

// Call logout method
$result = $authController->logout(true); // true for redirect
?>