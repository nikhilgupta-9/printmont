<?php
require_once __DIR__ . '/../config/database.php';

class ErrorPageController {
    private $db;
    private $table = 'site_settings';
    private $upload_dir = 'uploads/error-pages/';

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
        $query = "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_group = 'error_page'";
        $result = $this->db->query($query);

        $settings = [
            'badge'                 => '404 Error',
            'title'                 => 'Oops! Page Not Found',
            'message'               => 'The page you are looking for has been printed elsewhere, moved, or no longer exists. Let’s get you back on track!',
            'image_url'             => 'uploads/error-pages/404-printmont-error.jpg',
            'primary_btn_text'      => 'Back to Home',
            'primary_btn_url'       => '/',
            'secondary_btn_text'    => 'Explore Top Products',
            'secondary_btn_url'     => '/allproducts',
            'show_search_bar'       => '1',
            'show_quick_links'      => '1',
            'contact_support_text'  => 'Need Help? Reach our 24x7 Customer Desk',
            'support_email'         => 'support@printmont.com',
            'support_phone'         => '+91 9876543210'
        ];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }

    public function updateSettings($data, $file = null) {
        if ($file && isset($file['error']) && $file['error'] === 0) {
            $uploadResult = $this->handleFileUpload($file);
            if ($uploadResult['success']) {
                $data['image_url'] = $uploadResult['file_path'];
            } else {
                return ['success' => false, 'message' => $uploadResult['message']];
            }
        }

        foreach ($data as $key => $value) {
            $key = trim($key);
            $value = is_array($value) ? json_encode($value) : trim((string)$value);

            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = 'error_page' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $up = $this->db->prepare("UPDATE {$this->table} SET setting_value = ?, updated_at = NOW() WHERE setting_group = 'error_page' AND setting_key = ?");
                $up->bind_param("ss", $value, $key);
                $up->execute();
                $up->close();
            } else {
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('error_page', ?, ?, 'text', NOW(), NOW())");
                $ins->bind_param("ss", $key, $value);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }

        return ['success' => true, 'message' => 'Error page settings and graphic updated successfully!'];
    }

    private function handleFileUpload($file) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Invalid file format. Allowed: ' . implode(', ', $allowed)];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Max allowed: 5MB'];
        }

        $fileName = 'error_bg_' . uniqid() . '.' . $ext;
        $relativePath = $this->upload_dir . $fileName;
        $dest = __DIR__ . '/../' . $relativePath;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Also copy to frontend public assets
            $frontendDest = __DIR__ . '/../../frontend/public/assets/' . $fileName;
            @copy($dest, $frontendDest);
            @copy($dest, __DIR__ . '/../../frontend/public/404-printmont-error.jpg');

            return [
                'success'   => true,
                'file_path' => $relativePath
            ];
        }

        return ['success' => false, 'message' => 'Failed to save uploaded image.'];
    }

    private function ensureDefaults() {
        $defaults = [
            'badge'                 => '404 Error',
            'title'                 => 'Oops! Page Not Found',
            'message'               => 'The page you are looking for has been printed elsewhere, moved, or no longer exists. Let’s get you back on track!',
            'image_url'             => 'uploads/error-pages/404-printmont-error.jpg',
            'primary_btn_text'      => 'Back to Home',
            'primary_btn_url'       => '/',
            'secondary_btn_text'    => 'Explore Top Products',
            'secondary_btn_url'     => '/allproducts',
            'show_search_bar'       => '1',
            'show_quick_links'      => '1',
            'contact_support_text'  => 'Need Help? Reach our 24x7 Customer Desk',
            'support_email'         => 'support@printmont.com',
            'support_phone'         => '+91 9876543210'
        ];

        foreach ($defaults as $key => $val) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = 'error_page' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if (!$res || $res->num_rows === 0) {
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('error_page', ?, ?, 'text', NOW(), NOW())");
                $ins->bind_param("ss", $key, $val);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }
    }
}
