<?php
// api/customization-upload-api.php
// POST multipart "file" → stores a customer design / customization file and returns its URL.
// Used by the product page design popup (Upload Designs) and customization upload fields.
// Accepts JPG / PNG images (and PDF for customization fields), up to 10 MB.
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$file = $_FILES['file'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'File is larger than 10 MB.' : 'No file received, please try again.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$maxBytes = 10 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File is larger than 10 MB.']);
    exit;
}

// Decide the type from the file contents, never from the name the browser sent
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Only JPG, JPEG, PNG or PDF files are allowed.']);
    exit;
}
if ($mime !== 'application/pdf' && @getimagesize($file['tmp_name']) === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'This image could not be read, please try another file.']);
    exit;
}

$subDir = 'uploads/customizations/' . date('Y-m') . '/';
$absDir = __DIR__ . '/../' . $subDir;
if (!is_dir($absDir)) {
    @mkdir($absDir, 0777, true);
    @chmod($absDir, 0777);
}

$name = 'design_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
if (!@move_uploaded_file($file['tmp_name'], $absDir . $name)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save the file on the server.']);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x.php')), '/');

echo json_encode([
    'success' => true,
    'url'     => $scheme . '://' . $host . $base . '/' . $subDir . $name,
    'path'    => $subDir . $name,
    'name'    => basename((string) $file['name']),
    'size'    => (int) $file['size'],
]);
