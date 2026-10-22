<?php
class Watermark {
    private $conn;
    private $table = "watermarks";
    private $uploadDir;

    public function __construct($db) {
        $this->conn = $db;
        $this->uploadDir = __DIR__ . '/../uploads/watermarks/';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }
        $this->ensureTable();
        $this->ensureDefaultWatermark();
    }

    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `type` enum('text','image') DEFAULT 'text',
            `watermark_text` varchar(255) DEFAULT '© Printmont • www.printmont.com',
            `font_color` varchar(50) DEFAULT '#FFFFFF',
            `font_size` int(11) DEFAULT 24,
            `image` varchar(500) DEFAULT NULL,
            `position` enum('top_left','top_right','bottom_left','bottom_right','center','repeat') DEFAULT 'bottom_right',
            `opacity` tinyint(4) DEFAULT 60,
            `is_tiled` tinyint(1) DEFAULT 0,
            `apply_on_download` tinyint(1) DEFAULT 1,
            `protected_folders` text DEFAULT 'products',
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);

        // Add missing columns if upgrading from earlier version
        $columns = [
            "type" => "ALTER TABLE `{$this->table}` ADD COLUMN `type` enum('text','image') DEFAULT 'text' AFTER `title`",
            "watermark_text" => "ALTER TABLE `{$this->table}` ADD COLUMN `watermark_text` varchar(255) DEFAULT '© Printmont • www.printmont.com' AFTER `type`",
            "font_color" => "ALTER TABLE `{$this->table}` ADD COLUMN `font_color` varchar(50) DEFAULT '#FFFFFF' AFTER `watermark_text`",
            "font_size" => "ALTER TABLE `{$this->table}` ADD COLUMN `font_size` int(11) DEFAULT 24 AFTER `font_color`",
            "is_tiled" => "ALTER TABLE `{$this->table}` ADD COLUMN `is_tiled` tinyint(1) DEFAULT 0 AFTER `opacity`",
            "apply_on_download" => "ALTER TABLE `{$this->table}` ADD COLUMN `apply_on_download` tinyint(1) DEFAULT 1 AFTER `is_tiled`",
            "protected_folders" => "ALTER TABLE `{$this->table}` ADD COLUMN `protected_folders` text DEFAULT 'products' AFTER `apply_on_download`"
        ];

        foreach ($columns as $col => $alterSql) {
            $check = $this->conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE '{$col}'");
            if ($check && $check->num_rows === 0) {
                @$this->conn->query($alterSql);
            }
        }

        // Ensure 'repeat' is allowed in position enum
        @$this->conn->query("ALTER TABLE `{$this->table}` MODIFY COLUMN `position` enum('top_left','top_right','bottom_left','bottom_right','center','repeat') DEFAULT 'bottom_right'");
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY status ASC, id DESC";
        $result = $this->conn->query($query);
        $watermarks = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $watermarks[] = $row;
            }
        }
        return $watermarks;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function getActiveWatermark() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY id DESC LIMIT 1";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : null;
    }

    public function setActive($id) {
        $this->conn->query("UPDATE {$this->table} SET status = 'inactive'");
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = 'active', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function toggleStatus($id) {
        $wm = $this->getById($id);
        if (!$wm) return false;
        $newStatus = ($wm['status'] === 'active') ? 'inactive' : 'active';
        
        // If activating, deactivate others so only one primary watermark is active
        if ($newStatus === 'active') {
            $this->conn->query("UPDATE {$this->table} SET status = 'inactive'");
        }

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? $newStatus : false;
    }

    public function create($data, $file = null) {
        $title = trim($data['title'] ?? 'Watermark Configuration');
        $type = in_array($data['type'] ?? '', ['text', 'image']) ? $data['type'] : 'text';
        $watermarkText = trim($data['watermark_text'] ?? '© Printmont • www.printmont.com');
        $fontColor = trim($data['font_color'] ?? '#FFFFFF');
        $fontSize = (int)($data['font_size'] ?? 24);
        $position = in_array($data['position'] ?? '', ['top_left','top_right','bottom_left','bottom_right','center','repeat']) ? $data['position'] : 'bottom_right';
        $opacity = max(10, min(100, (int)($data['opacity'] ?? 60)));
        $isTiled = (!empty($data['is_tiled']) || $position === 'repeat') ? 1 : 0;
        $applyOnDownload = !empty($data['apply_on_download']) ? 1 : 0;
        $protectedFolders = !empty($data['protected_folders']) ? $data['protected_folders'] : 'products';
        $status = ($data['status'] ?? '') === 'active' ? 'active' : 'inactive';

        $imagePath = '';
        if ($type === 'image' && !empty($file['name'])) {
            $uploaded = $this->handleFileUpload($file);
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        }

        // If newly created is active, deactivate others
        if ($status === 'active') {
            $this->conn->query("UPDATE {$this->table} SET status = 'inactive'");
        }

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (title, type, watermark_text, font_color, font_size, image, position, opacity, is_tiled, apply_on_download, protected_folders, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) return false;

        $stmt->bind_param("ssssissiiiss", $title, $type, $watermarkText, $fontColor, $fontSize, $imagePath, $position, $opacity, $isTiled, $applyOnDownload, $protectedFolders, $status);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update($id, $data, $file = null) {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $title = trim($data['title'] ?? $existing['title']);
        $type = in_array($data['type'] ?? '', ['text', 'image']) ? $data['type'] : $existing['type'];
        $watermarkText = isset($data['watermark_text']) ? trim($data['watermark_text']) : $existing['watermark_text'];
        $fontColor = trim($data['font_color'] ?? $existing['font_color']);
        $fontSize = (int)($data['font_size'] ?? $existing['font_size']);
        $position = in_array($data['position'] ?? '', ['top_left','top_right','bottom_left','bottom_right','center','repeat']) ? $data['position'] : $existing['position'];
        $opacity = max(10, min(100, (int)($data['opacity'] ?? $existing['opacity'])));
        $isTiled = (!empty($data['is_tiled']) || $position === 'repeat') ? 1 : 0;
        $applyOnDownload = isset($data['apply_on_download']) ? (!empty($data['apply_on_download']) ? 1 : 0) : $existing['apply_on_download'];
        $protectedFolders = $data['protected_folders'] ?? $existing['protected_folders'];
        $status = $data['status'] ?? $existing['status'];

        $imagePath = $existing['image'];
        if ($type === 'image' && !empty($file['name'])) {
            $uploaded = $this->handleFileUpload($file);
            if ($uploaded) {
                // Delete old image file if exists
                if (!empty($existing['image']) && file_exists(__DIR__ . '/../' . $existing['image'])) {
                    @unlink(__DIR__ . '/../' . $existing['image']);
                }
                $imagePath = $uploaded;
            }
        }

        if ($status === 'active') {
            $this->conn->query("UPDATE {$this->table} SET status = 'inactive' WHERE id != {$id}");
        }

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET title = ?, type = ?, watermark_text = ?, font_color = ?, font_size = ?, image = ?, position = ?, opacity = ?, is_tiled = ?, apply_on_download = ?, protected_folders = ?, status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("ssssissiiissi", $title, $type, $watermarkText, $fontColor, $fontSize, $imagePath, $position, $opacity, $isTiled, $applyOnDownload, $protectedFolders, $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete($id) {
        $existing = $this->getById($id);
        if ($existing && !empty($existing['image']) && file_exists(__DIR__ . '/../' . $existing['image'])) {
            @unlink(__DIR__ . '/../' . $existing['image']);
        }
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    private function handleFileUpload($file) {
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'], $allowed)) {
            return false;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'wm_' . time() . '_' . uniqid() . '.' . $ext;
        $targetPath = $this->uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/watermarks/' . $fileName;
        }
        return false;
    }

    /**
     * Watermark Processing Engine using PHP GD
     * Renders text or logo overlay at specified opacity and position.
     */
    public function applyWatermarkToImage($sourceImagePath, $destinationPath = null, $customConfig = null) {
        if (!file_exists($sourceImagePath)) {
            return false;
        }

        $config = $customConfig ?: $this->getActiveWatermark();
        if (!$config || $config['status'] !== 'active') {
            // If inactive, return original unmodified
            if ($destinationPath && $destinationPath !== $sourceImagePath) {
                copy($sourceImagePath, $destinationPath);
            }
            return true;
        }

        $info = getimagesize($sourceImagePath);
        if (!$info) return false;

        $mime = $info['mime'];
        $srcImg = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImg = @imagecreatefromjpeg($sourceImagePath);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($sourceImagePath);
                break;
            case 'image/gif':
                $srcImg = @imagecreatefromgif($sourceImagePath);
                break;
            case 'image/bmp':
                $srcImg = @imagecreatefrombmp($sourceImagePath);
                break;
            default:
                // If unsupported format (like WebP on PHP without WebP GD support), leave original
                return false;
        }

        if (!$srcImg) return false;

        $imgW = imagesx($srcImg);
        $imgH = imagesy($srcImg);

        // Handle transparency preservation for PNG/GIF
        imagealphablending($srcImg, true);
        imagesavealpha($srcImg, true);

        $opacity = max(10, min(100, (int)($config['opacity'] ?? 60)));
        $position = $config['position'] ?? 'bottom_right';
        $isTiled = !empty($config['is_tiled']) || $position === 'repeat';

        if ($config['type'] === 'image' && !empty($config['image'])) {
            $wmImagePath = __DIR__ . '/../' . $config['image'];
            if (file_exists($wmImagePath)) {
                $wmInfo = getimagesize($wmImagePath);
                $wmImg = null;
                if ($wmInfo['mime'] === 'image/png') $wmImg = @imagecreatefrompng($wmImagePath);
                elseif ($wmInfo['mime'] === 'image/jpeg') $wmImg = @imagecreatefromjpeg($wmImagePath);

                if ($wmImg) {
                    $wmW = imagesx($wmImg);
                    $wmH = imagesy($wmImg);

                    // Auto-scale watermark if it exceeds 35% of target image width
                    $maxWmW = (int)($imgW * 0.35);
                    if ($wmW > $maxWmW && $maxWmW > 20) {
                        $scale = $maxWmW / $wmW;
                        $newWmW = (int)($wmW * $scale);
                        $newWmH = (int)($wmH * $scale);
                        $scaledWm = imagecreatetruecolor($newWmW, $newWmH);
                        imagealphablending($scaledWm, false);
                        imagesavealpha($scaledWm, true);
                        imagecopyresampled($scaledWm, $wmImg, 0, 0, 0, 0, $newWmW, $newWmH, $wmW, $wmH);
                        imagedestroy($wmImg);
                        $wmImg = $scaledWm;
                        $wmW = $newWmW;
                        $wmH = $newWmH;
                    }

                    if ($isTiled) {
                        for ($x = 20; $x < $imgW; $x += $wmW + 80) {
                            for ($y = 20; $y < $imgH; $y += $wmH + 80) {
                                $this->imagecopymerge_alpha($srcImg, $wmImg, $x, $y, 0, 0, $wmW, $wmH, $opacity);
                            }
                        }
                    } else {
                        list($posX, $posY) = $this->calculatePosition($imgW, $imgH, $wmW, $wmH, $position);
                        $this->imagecopymerge_alpha($srcImg, $wmImg, $posX, $posY, 0, 0, $wmW, $wmH, $opacity);
                    }
                    imagedestroy($wmImg);
                }
            }
        } else {
            // Text Watermark
            $text = !empty($config['watermark_text']) ? $config['watermark_text'] : '© Printmont • www.printmont.com';
            $fontSize = max(12, min(72, (int)($config['font_size'] ?? 24)));
            $hexColor = !empty($config['font_color']) ? $config['font_color'] : '#FFFFFF';
            list($r, $g, $b) = $this->hex2rgb($hexColor);

            // GD alpha: 0 is completely opaque, 127 is completely transparent
            $gdAlpha = (int)(127 - ($opacity / 100) * 127);
            $textColor = imagecolorallocatealpha($srcImg, $r, $g, $b, $gdAlpha);
            $shadowColor = imagecolorallocatealpha($srcImg, 0, 0, 0, min(127, $gdAlpha + 30));

            // Estimate text dimensions
            $approxCharW = $fontSize * 0.65;
            $textW = strlen($text) * $approxCharW;
            $textH = $fontSize * 1.3;

            if ($isTiled) {
                for ($x = 40; $x < $imgW; $x += (int)($textW + 120)) {
                    for ($y = 60; $y < $imgH; $y += (int)($textH + 100)) {
                        $this->drawText($srcImg, $text, $x + 2, $y + 2, $fontSize, $shadowColor);
                        $this->drawText($srcImg, $text, $x, $y, $fontSize, $textColor);
                    }
                }
            } else {
                list($posX, $posY) = $this->calculatePosition($imgW, $imgH, $textW, $textH, $position);
                // Drop shadow for legibility
                $this->drawText($srcImg, $text, $posX + 2, $posY + 2, $fontSize, $shadowColor);
                $this->drawText($srcImg, $text, $posX, $posY, $fontSize, $textColor);
            }
        }

        $dest = $destinationPath ?: $sourceImagePath;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($srcImg, $dest, 90);
                break;
            case 'image/png':
                imagepng($srcImg, $dest, 8);
                break;
            case 'image/gif':
                imagegif($srcImg, $dest);
                break;
            case 'image/bmp':
                imagebmp($srcImg, $dest);
                break;
        }

        imagedestroy($srcImg);
        return true;
    }

    private function drawText($image, $text, $x, $y, $fontSize, $color) {
        // Check for TrueType font file in system or backend
        $fontPaths = [
            __DIR__ . '/../fonts/PlusJakartaSans-Bold.ttf',
            __DIR__ . '/../fonts/Roboto-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
        ];

        $fontFile = null;
        foreach ($fontPaths as $fp) {
            if (file_exists($fp)) {
                $fontFile = $fp;
                break;
            }
        }

        if ($fontFile && function_exists('imagettftext')) {
            @imagettftext($image, $fontSize * 0.75, 0, (int)$x, (int)($y + $fontSize), $color, $fontFile, $text);
        } else {
            // GD built-in font fallback (1-5)
            $gdFont = 5; // largest built-in font
            imagestring($image, $gdFont, (int)$x, (int)$y, $text, $color);
        }
    }

    private function calculatePosition($imgW, $imgH, $elemW, $elemH, $position) {
        $pad = 24;
        switch ($position) {
            case 'top_left':
                return [$pad, $pad + 10];
            case 'top_right':
                return [max($pad, $imgW - $elemW - $pad), $pad + 10];
            case 'bottom_left':
                return [$pad, max($pad, $imgH - $elemH - $pad)];
            case 'center':
                return [max($pad, ($imgW - $elemW) / 2), max($pad, ($imgH - $elemH) / 2)];
            case 'bottom_right':
            default:
                return [max($pad, $imgW - $elemW - $pad), max($pad, $imgH - $elemH - $pad)];
        }
    }

    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        $pct /= 100;
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct * 100);
        imagedestroy($cut);
    }

    private function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return [$r, $g, $b];
    }

    public function ensureDefaultWatermark() {
        $check = $this->conn->query("SELECT COUNT(*) as cnt FROM {$this->table}");
        if ($check) {
            $row = $check->fetch_assoc();
            if ((int)$row['cnt'] > 0) return;
        }

        $default = [
            'title' => 'Printmont Brand Copyright Watermark',
            'type' => 'text',
            'watermark_text' => '© Printmont Custom Prints • www.printmont.com',
            'font_color' => '#FFFFFF',
            'font_size' => 22,
            'position' => 'bottom_right',
            'opacity' => 65,
            'is_tiled' => 0,
            'apply_on_download' => 1,
            'protected_folders' => 'products,banners,page-sections',
            'status' => 'active'
        ];
        $this->create($default);
    }
}
?>
