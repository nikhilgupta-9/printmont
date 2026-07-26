<?php
// api/cors.php - Centralized CORS headers configuration

$allowed_origins = [
    'https://printmont.me',
    'https://printmont-livid.vercel.app',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:3000'
];

$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (!empty($http_origin)) {
    header("Access-Control-Allow-Origin: $http_origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Access-Control-Request-Method, Access-Control-Request-Headers");
header("Access-Control-Max-Age: 86400");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
