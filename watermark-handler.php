<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Watermark.php';

$src = trim($_GET['src'] ?? ($_GET['img'] ?? ($_GET['file'] ?? '')));
if (empty($src)) {
    http_response_code(400);
    die('Image source path required.');
}

// Security: Prevent Directory Traversal
$src = ltrim(str_replace(['../', '..\\'], '', $src), '/');
if (strpos($src, 'uploads/') !== 0) {
    $src = 'uploads/' . $src;
}

$baseDir = realpath(__DIR__);
$fullSourcePath = realpath($baseDir . '/' . $src);

// Ensure file exists and is inside uploads directory
$uploadsDir = realpath($baseDir . '/uploads');
if (!$fullSourcePath || !$uploadsDir || strpos($fullSourcePath, $uploadsDir) !== 0 || !file_exists($fullSourcePath)) {
    http_response_code(404);
    die('Image not found.');
}

$info = @getimagesize($fullSourcePath);
if (!$info) {
    // Fallback: serve raw file if not an image
    header('Content-Type: application/octet-stream');
    readfile($fullSourcePath);
    exit;
}

$mime = $info['mime'];
$isDownload = !empty($_GET['download']) || !empty($_GET['dl']) || !empty($_GET['export']) || !empty($_GET['save']);
$isExplicitWatermark = !empty($_GET['wm']) || $isDownload;

$db = (new Database())->getConnection();
$watermarkModel = new Watermark($db);
$activeWm = $watermarkModel->getActiveWatermark();

// Check if image belongs to protected categories (products, banners, carousels)
$protectedTypes = ['products', 'banners', 'carousel', 'page-sections', 'card', 'crouselimages', 'top-deals'];
$isProtectedFolder = false;
foreach ($protectedTypes as $type) {
    if (strpos($src, $type) !== false) {
        $isProtectedFolder = true;
        break;
    }
}

// If watermark is inactive OR request is normal web viewing without download/wm flag:
// Serve original clean image directly without watermark!
if (!$activeWm || $activeWm['status'] !== 'active' || (!$isExplicitWatermark && !$isDownload) || !$isProtectedFolder) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullSourcePath));
    header('Cache-Control: public, max-age=86400');
    if ($isDownload) {
        $outName = 'printmont_' . basename($fullSourcePath);
        header('Content-Disposition: attachment; filename="' . $outName . '"');
    }
    readfile($fullSourcePath);
    exit;
}

// Cache directory for watermarked generated images
$cacheDir = __DIR__ . '/uploads/cache/watermarked/';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

$cacheKey = md5($fullSourcePath . '_' . filemtime($fullSourcePath) . '_' . json_encode($activeWm));
$ext = pathinfo($fullSourcePath, PATHINFO_EXTENSION) ?: 'jpg';
$cacheFile = $cacheDir . $cacheKey . '.' . $ext;

$outFilename = 'printmont_' . basename($fullSourcePath);

// If cached watermarked version exists and is fresh, serve from cache
if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($fullSourcePath)) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($cacheFile));
    header('Cache-Control: public, max-age=86400');
    header('X-Watermark-Status: Applied (Cached)');
    if ($isDownload) {
        header('Content-Disposition: attachment; filename="' . $outFilename . '"');
    }
    readfile($cacheFile);
    exit;
}

// Generate watermarked image into cache
$rendered = $watermarkModel->applyWatermarkToImage($fullSourcePath, $cacheFile, $activeWm);

if ($rendered && file_exists($cacheFile)) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($cacheFile));
    header('Cache-Control: public, max-age=86400');
    header('X-Watermark-Status: Applied (Fresh)');
    if ($isDownload) {
        header('Content-Disposition: attachment; filename="' . $outFilename . '"');
    }
    readfile($cacheFile);
    exit;
} else {
    // If GD watermarking failed, fallback to original image
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullSourcePath));
    header('Cache-Control: public, max-age=86400');
    header('X-Watermark-Status: Fallback');
    if ($isDownload) {
        header('Content-Disposition: attachment; filename="' . $outFilename . '"');
    }
    readfile($fullSourcePath);
    exit;
}
