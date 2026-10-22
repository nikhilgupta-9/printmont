<?php
if (!defined('JWT_SECRET')) define('JWT_SECRET', '609a34ea43dcbe481fd8b3b3df7f59c4d3ef4148cc84cfb47150ed958a050d24');
if (!defined('JWT_ALGORITHM')) define('JWT_ALGORITHM', 'HS256');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', __DIR__ . '/../uploads/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// CORS Origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
header('Access-Control-Allow-Credentials: true');

if (($_SERVER['REQUEST_METHOD'] ?? '') == 'OPTIONS') {
    exit(0);
}

if (!defined('BASE_URL')) define('BASE_URL', 'https://mediumvioletred-pelican-783174.hostingersite.com/');

if (!function_exists('resolveBackendImageUrl')) {
    function resolveBackendImageUrl($path) {
        if (empty($path)) return '';
        $path = trim($path);
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, 'data:image') === 0) {
            return $path;
        }
        $cleanPath = ltrim($path, '/');
        
        // If file exists directly in printmont-backend/
        if (file_exists(__DIR__ . '/../' . $cleanPath)) {
            return $cleanPath;
        }
        // If file exists in uploads/
        if (file_exists(__DIR__ . '/../uploads/' . $cleanPath)) {
            return 'uploads/' . $cleanPath;
        }
        // If in frontend public
        if (file_exists(__DIR__ . '/../../frontend/public/' . $cleanPath)) {
            return '../frontend/public/' . $cleanPath;
        }
        if (file_exists(__DIR__ . '/../../frontend/dist/' . $cleanPath)) {
            return '../frontend/dist/' . $cleanPath;
        }
        return $cleanPath;
    }
}

if (!function_exists('resolveAdminThumb')) {
    function resolveAdminThumb($path) {
        if (empty($path)) return '';
        $trimmed = trim($path);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/printmont/')) {
            return $trimmed;
        }
        return ltrim($trimmed, '/');
    }
}
