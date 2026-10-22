<?php
require_once __DIR__ . '/../config/database.php';

class FooterStylingController {
    private $db;
    private $table = 'site_settings';
    private $group = 'footer_styling';

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->ensureDefaults();
    }

    public function getStyling() {
        $defaults = [
            'footer_bg_color'          => '#0e1a35',
            'footer_text_color'        => '#cbd5e1',
            'footer_heading_color'     => '#ffffff',
            'footer_link_color'        => '#94a3b8',
            'footer_link_hover_color'  => '#f8991d',
            'footer_bottom_bg_color'   => '#091224',
            'footer_border_color'      => 'rgba(255, 255, 255, 0.1)',
            'footer_preset'            => 'printmont_dark'
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

    public function updateStyling($data) {
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

        return ['success' => true, 'message' => 'Footer colors and styling updated successfully!'];
    }

    private function ensureDefaults() {
        $defaults = [
            'footer_bg_color'          => '#0e1a35',
            'footer_text_color'        => '#cbd5e1',
            'footer_heading_color'     => '#ffffff',
            'footer_link_color'        => '#94a3b8',
            'footer_link_hover_color'  => '#f8991d',
            'footer_bottom_bg_color'   => '#091224',
            'footer_border_color'      => 'rgba(255, 255, 255, 0.1)',
            'footer_preset'            => 'printmont_dark'
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
