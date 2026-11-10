<?php
/**
 * Move a PHP image upload into a destination folder as an optimized WebP file.
 *
 * Reuse from controllers/pages:
 *   $result = saveUploadedImageAsWebp($file, $absoluteDir, 'uploads/banners/desktop');
 *   if (!$result['success']) return ['success' => false, 'error' => $result['error']];
 *   $publicPath = $result['path'];
 *
 * Decoding uses the server's GD build, so supported source formats depend on
 * the image decoders enabled in that PHP installation. The upload contents,
 * rather than the filename extension or browser MIME, determine validity.
 */
if (!function_exists('saveUploadedImageAsWebp')) {
    function saveUploadedImageAsWebp(array $file, string $absoluteDirectory, string $publicDirectory, array $options = []): array {
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Image exceeds the server upload size limit.',
                UPLOAD_ERR_FORM_SIZE => 'Image exceeds the form upload size limit.',
                UPLOAD_ERR_PARTIAL => 'Image upload was incomplete. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No image file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server upload temporary folder is unavailable.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
                UPLOAD_ERR_EXTENSION => 'The server blocked this image upload.',
            ];
            return ['success' => false, 'error' => $errors[$errorCode] ?? 'Image upload failed.'];
        }

        $temporaryPath = (string)($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            return ['success' => false, 'error' => 'The uploaded image could not be verified. Please upload it again.'];
        }

        $maxBytes = max(1, (int)($options['max_bytes'] ?? (10 * 1024 * 1024)));
        $size = (int)($file['size'] ?? filesize($temporaryPath));
        if ($size <= 0 || $size > $maxBytes) {
            return ['success' => false, 'error' => 'Image must be between 1 byte and ' . number_format($maxBytes / 1024 / 1024, 0) . ' MB.'];
        }

        $imageInfo = @getimagesize($temporaryPath);
        if (!$imageInfo || empty($imageInfo['mime']) || strpos($imageInfo['mime'], 'image/') !== 0) {
            return ['success' => false, 'error' => 'This file is not a supported raster image. Try JPG, PNG, GIF, WebP, BMP, or AVIF.'];
        }

        $width = (int)$imageInfo[0];
        $height = (int)$imageInfo[1];
        $maxPixels = max(1, (int)($options['max_pixels'] ?? 20000000));
        if ($width < 1 || $height < 1 || $width > 30000 || $height > 30000 || ($width * $height) > $maxPixels) {
            return ['success' => false, 'error' => 'Image dimensions are too large to convert safely.'];
        }

        if (!is_dir($absoluteDirectory) && !@mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
            return ['success' => false, 'error' => 'The image upload folder could not be created.'];
        }

        $quality = max(0, min(100, (int)($options['quality'] ?? 84)));
        try {
            $filename = 'image_' . bin2hex(random_bytes(12)) . '.webp';
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'Could not generate a safe image filename.'];
        }
        $destination = rtrim($absoluteDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $saved = false;
        if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
            $sourceBytes = @file_get_contents($temporaryPath);
            $image = ($sourceBytes !== false) ? @imagecreatefromstring($sourceBytes) : false;
            unset($sourceBytes);
            if ($image) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $saved = @imagewebp($image, $destination, $quality);
                imagedestroy($image);
            }
        } else {
            // XAMPP builds may include GD without its WebP encoder. Fall back to
            // the WebP CLI when installed, passing paths as escaped arguments.
            $webpEncoder = null;
            foreach (['/opt/homebrew/bin/cwebp', '/usr/local/bin/cwebp', '/usr/bin/cwebp'] as $candidate) {
                if (is_executable($candidate)) {
                    $webpEncoder = $candidate;
                    break;
                }
            }
            if ($webpEncoder && function_exists('exec')) {
                $command = escapeshellarg($webpEncoder) . ' -quiet -q ' . $quality . ' '
                    . escapeshellarg($temporaryPath) . ' -o ' . escapeshellarg($destination) . ' 2>&1';
                $output = [];
                $exitCode = 1;
                @exec($command, $output, $exitCode);
                $saved = ($exitCode === 0);
            } else {
                return ['success' => false, 'error' => 'WebP conversion is unavailable. Enable GD WebP support or install the cwebp command on the server.'];
            }
        }

        if (!$saved || !is_file($destination) || filesize($destination) === 0) {
            if (is_file($destination)) @unlink($destination);
            return ['success' => false, 'error' => 'Could not convert this image to WebP.'];
        }
        @chmod($destination, 0644);

        return [
            'success' => true,
            'path' => trim($publicDirectory, '/') . '/' . $filename,
            'filename' => $filename,
            'mime_type' => (string)$imageInfo['mime'],
            'width' => $width,
            'height' => $height,
            'size' => (int)filesize($destination),
        ];
    }
}
