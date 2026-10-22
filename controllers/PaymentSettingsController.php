<?php
require_once __DIR__ . '/../config/database.php';

class PaymentSettingsController {
    private $db;
    private $table = 'site_settings';
    private $group = 'payment_settings';

    public function __construct($db = null) {
        if ($db instanceof mysqli) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->ensureDefaults();
    }

    public function getSettings($includeSecret = true) {
        $defaults = [
            'razorpay_enabled'               => '1',
            'razorpay_mode'                  => 'test', // 'test' or 'live'
            'razorpay_test_key_id'           => 'rzp_test_1DP5mmOlF5G5ag',
            'razorpay_test_secret'           => 'rzp_test_secret_sample_key',
            'razorpay_live_key_id'           => '',
            'razorpay_live_secret'           => '',
            'razorpay_webhook_secret'        => '',
            'cod_enabled'                    => '1',
            'cod_advance_type'               => 'percentage', // 'percentage' or 'fixed'
            'cod_advance_percent'            => '30',
            'cod_advance_fixed_amount'       => '150',
            'cod_extra_fee'                  => '0',
            'cod_free_threshold'             => '999',
            'cod_min_amount'                 => '0',
            'cod_max_amount'                 => '50000',
            'cod_customized_advance_percent' => '50',
            'cod_instructions'               => 'Pay remaining balance upon delivery in cash or via delivery partner UPI QR.',
            'cod_restricted_pincodes'        => '',
            'upi_enabled'                    => '1',
            'cards_enabled'                  => '1',
            'netbanking_enabled'             => '1',
            'emi_enabled'                    => '1',
            'brand_name'                     => 'Printmont',
            'theme_color'                    => '#0B53A1'
        ];

        $query = "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_group = '{$this->group}'";
        $result = $this->db->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        }

        // Compute active key id and secret based on active mode
        $activeMode = $defaults['razorpay_mode'] ?? 'test';
        $defaults['active_key_id'] = ($activeMode === 'live') ? ($defaults['razorpay_live_key_id'] ?: $defaults['razorpay_test_key_id']) : $defaults['razorpay_test_key_id'];
        $defaults['active_secret'] = ($activeMode === 'live') ? ($defaults['razorpay_live_secret'] ?: $defaults['razorpay_test_secret']) : $defaults['razorpay_test_secret'];

        if (!$includeSecret) {
            unset($defaults['razorpay_test_secret'], $defaults['razorpay_live_secret'], $defaults['active_secret'], $defaults['razorpay_webhook_secret']);
        }

        return $defaults;
    }

    public function updateSettings($data) {
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

        return ['success' => true, 'message' => 'Payment gateway settings updated successfully!'];
    }

    private function ensureDefaults() {
        $defaults = [
            'razorpay_enabled'               => '1',
            'razorpay_mode'                  => 'test',
            'razorpay_test_key_id'           => 'rzp_test_1DP5mmOlF5G5ag',
            'razorpay_test_secret'           => 'rzp_test_secret_sample_key',
            'razorpay_live_key_id'           => '',
            'razorpay_live_secret'           => '',
            'razorpay_webhook_secret'        => '',
            'cod_enabled'                    => '1',
            'cod_advance_type'               => 'percentage',
            'cod_advance_percent'            => '30',
            'cod_advance_fixed_amount'       => '150',
            'cod_extra_fee'                  => '0',
            'cod_free_threshold'             => '999',
            'cod_min_amount'                 => '0',
            'cod_max_amount'                 => '50000',
            'cod_customized_advance_percent' => '50',
            'cod_instructions'               => 'Pay remaining balance upon delivery in cash or via delivery partner UPI QR.',
            'cod_restricted_pincodes'        => '',
            'upi_enabled'                    => '1',
            'cards_enabled'                  => '1',
            'netbanking_enabled'             => '1',
            'emi_enabled'                    => '1',
            'brand_name'                     => 'Printmont',
            'theme_color'                    => '#0B53A1'
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
