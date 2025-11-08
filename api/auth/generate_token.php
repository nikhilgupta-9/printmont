<?php
require_once __DIR__ . '/../../middleware/JWTHandler.php';  // ⬅️ two levels up now

$jwt = new JWTHandler();

$user_data = [
    'id' => 1,
    'email' => 'test@example.com',
    'role' => 'admin'
];

$token = $jwt->generateToken($user_data);

header('Content-Type: application/json');
echo json_encode(['token' => $token]);
?>
