<?php
/**
 * Shared helper: upload a carousel background image.
 * Returns the saved relative path, or '' when no file was submitted.
 * Throws Exception on validation / IO failure.
 */
if (!function_exists('carousel_upload_image')) {
    function carousel_upload_image($field) {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error on {$field}.");
        }
        $upload_dir = 'uploads/carousel/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new Exception("Invalid image type for {$field}. Allowed: JPG, PNG, GIF, WebP.");
        }
        if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
            throw new Exception("Image for {$field} is too large (max 5MB).");
        }
        $dest = $upload_dir . uniqid('carousel_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            throw new Exception("Failed to save uploaded image for {$field}.");
        }
        return $dest;
    }
}
