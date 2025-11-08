<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../middleware/JWTHandler.php';

// Create an instance of JWTHandler
$jwt = new JWTHandler();

// Example payload (this could be a user record)
$payload = [
    "user_id" => 1,
    "email" => "admin@example.com",
    "role" => "admin"
];

// Generate token
$token = $jwt->generateToken($payload);

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    "token" => $token
]);
?>
