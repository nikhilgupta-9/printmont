<?php
require_once __DIR__ . '/../config/database.php';

class MaintenanceController {
    private $db;
    private $table = 'site_settings';

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->ensureDefaults();
    }

    public function getSettings() {
        $query = "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_group = 'maintenance'";
        $result = $this->db->query($query);
        
        $settings = [
            'is_enabled'         => '0',
            'title'              => 'Under Scheduled Maintenance',
            'message'            => 'We are currently performing routine maintenance and system upgrades to provide a better shopping experience. We will be back online shortly!',
            'estimated_end_time' => '',
            'contact_email'      => 'support@printmont.com',
            'contact_phone'      => '+91 9876543210',
            'secret_bypass_key'  => 'printmont_preview',
            'allowed_ips'        => '',
            'show_countdown'     => '1'
        ];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }

    public function updateSettings($data) {
        foreach ($data as $key => $value) {
            $key = trim($key);
            $value = is_array($value) ? json_encode($value) : trim((string)$value);

            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = 'maintenance' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $up = $this->db->prepare("UPDATE {$this->table} SET setting_value = ?, updated_at = NOW() WHERE setting_group = 'maintenance' AND setting_key = ?");
                $up->bind_param("ss", $value, $key);
                $up->execute();
                $up->close();
            } else {
                $type = ($key === 'is_enabled' || $key === 'show_countdown') ? 'boolean' : 'text';
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('maintenance', ?, ?, ?, NOW(), NOW())");
                $ins->bind_param("sss", $key, $value, $type);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }
        return ['success' => true, 'message' => 'Maintenance settings updated successfully!'];
    }

    public function toggleMaintenance($status) {
        $statusVal = $status ? '1' : '0';
        return $this->updateSettings(['is_enabled' => $statusVal]);
    }

    private function ensureDefaults() {
        $defaults = [
            'is_enabled'         => '0',
            'title'              => 'Under Scheduled Maintenance',
            'message'            => 'We are currently performing routine maintenance and system upgrades to provide a better shopping experience. We will be back online shortly!',
            'estimated_end_time' => '',
            'contact_email'      => 'support@printmont.com',
            'contact_phone'      => '+91 9876543210',
            'secret_bypass_key'  => 'printmont_preview',
            'allowed_ips'        => '',
            'show_countdown'     => '1'
        ];

        foreach ($defaults as $key => $val) {
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE setting_group = 'maintenance' AND setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if (!$res || $res->num_rows === 0) {
                $type = ($key === 'is_enabled' || $key === 'show_countdown') ? 'boolean' : 'text';
                $ins = $this->db->prepare("INSERT INTO {$this->table} (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES ('maintenance', ?, ?, ?, NOW(), NOW())");
                $ins->bind_param("sss", $key, $val, $type);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }
    }
}
