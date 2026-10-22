<?php
require_once __DIR__ . '/../config/database.php';

class LoaderController {
    private $db;
    private $table = 'site_settings';
    private $group = 'loader_settings';
    private $upload_dir = 'uploads/loaders/';

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }

        $fullUploadPath = __DIR__ . '/../' . $this->upload_dir;
        if (!file_exists($fullUploadPath)) {
            @mkdir($fullUploadPath, 0777, true);
        }

        $this->ensureDefaults();
    }

    public function getSettings() {
        $defaults = [
            'is_enabled'          => '1',
            'loader_type'         => 'brand_logo_pulse',
            'custom_loader_image' => '',
            'loading_text'        => 'Printing Ideas Into Reality...',
            'show_text'           => '1',
            'bg_color'            => '#ffffff',
            'bg_opacity'          => '0.96',
            'accent_color'        => '#0B53A1',
            'secondary_color'     => '#f8991d',
            'min_duration'        => '500',
            'blur_backdrop'       => '1',
            'preset'              => 'brand_pulse'
        ];

        $query = "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_group = '{$this->group}'";
        $result = $this->db->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        }

        return $defaults;
    }

    public function updateSettings($data, $file = null) {
        if ($file && isset($file['error']) && $file['error'] === 0) {
            $uploadResult = $this->handleFileUpload($file);
            if ($uploadResult['success']) {
                $data['custom_loader_image'] = $uploadResult['file_path'];
            } else {
                return ['success' => false, 'message' => $uploadResult['message']];
            }
        }

        foreach ($data as $key => $value) {
            $key = trim($key);
            $value = is_array($value) ? json_encode($value) : trim((string)$value);

            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = '{$this->group}' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $up = $this->db->prepare("UPDATE {$this->table} SET setting_value = ?, updated_at = NOW() WHERE setting_group = '{$this->group}' AND setting_key = ?");
                $up->bind_param("ss", $value, $key);
                $up->execute();
                $up->close();
            } else {
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('{$this->group}', ?, ?, 'text', NOW(), NOW())");
                $ins->bind_param("ss", $key, $value);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }

        return ['success' => true, 'message' => 'Loader settings and animation updated successfully!'];
    }

    private function handleFileUpload($file) {
        $allowed = ['gif', 'svg', 'png', 'webp', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Invalid file format. Allowed: ' . implode(', ', $allowed)];
        }

        if ($file['size'] > 4 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Max allowed: 4MB'];
        }

        $fileName = 'loader_' . uniqid() . '.' . $ext;
        $relativePath = $this->upload_dir . $fileName;
        $dest = __DIR__ . '/../' . $relativePath;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $frontendDest = __DIR__ . '/../../frontend/public/assets/' . $fileName;
            @copy($dest, $frontendDest);

            return [
                'success'   => true,
                'file_path' => $relativePath
            ];
        }

        return ['success' => false, 'message' => 'Failed to save uploaded loader image.'];
    }

    private function ensureDefaults() {
        $defaults = [
            'is_enabled'          => '1',
            'loader_type'         => 'brand_logo_pulse',
            'custom_loader_image' => '',
            'loading_text'        => 'Printing Ideas Into Reality...',
            'show_text'           => '1',
            'bg_color'            => '#ffffff',
            'bg_opacity'          => '0.96',
            'accent_color'        => '#0B53A1',
            'secondary_color'     => '#f8991d',
            'min_duration'        => '500',
            'blur_backdrop'       => '1',
            'preset'              => 'brand_pulse'
        ];

        foreach ($defaults as $key => $val) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = '{$this->group}' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if (!$res || $res->num_rows === 0) {
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('{$this->group}', ?, ?, 'text', NOW(), NOW())");
                $ins->bind_param("ss", $key, $val);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }
    }
}
?>
