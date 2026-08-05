<?php
define('JWT_SECRET', '609a34ea43dcbe481fd8b3b3df7f59c4d3ef4148cc84cfb47150ed958a050d24');
define('JWT_ALGORITHM', 'HS256');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// CORS Origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

define('BASE_URL', 'https://mediumvioletred-pelican-783174.hostingersite.com/');
