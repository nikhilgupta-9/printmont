<?php
class EmailConfiguration {
    private $conn;
    private $table = "email_configurations";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $configs = [];
        while ($row = $result->fetch_assoc()) {
            $configs[] = $row;
        }
        
        return $configs;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function getActive() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' LIMIT 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function create($data) {
        if ($data['status'] == 'active') {
            $this->deactivateAll();
        }
        
        $purpose = $data['purpose'] ?? 'Customer Support';

        $query = "INSERT INTO {$this->table} (config_name, purpose, mail_driver, mail_host, mail_port, mail_username, mail_password, mail_encryption, mail_from_address, mail_from_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssssss", 
            $data['config_name'], 
            $purpose,
            $data['mail_driver'], 
            $data['mail_host'], 
            $data['mail_port'], 
            $data['mail_username'], 
            $data['mail_password'], 
            $data['mail_encryption'], 
            $data['mail_from_address'], 
            $data['mail_from_name'], 
            $data['status']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        if ($data['status'] == 'active') {
            $this->deactivateAll();
        }

        $purpose = $data['purpose'] ?? 'Customer Support';
        
        $query = "UPDATE {$this->table} SET config_name = ?, purpose = ?, mail_driver = ?, mail_host = ?, mail_port = ?, mail_username = ?, mail_password = ?, mail_encryption = ?, mail_from_address = ?, mail_from_name = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssssssi", 
            $data['config_name'], 
            $purpose,
            $data['mail_driver'], 
            $data['mail_host'], 
            $data['mail_port'], 
            $data['mail_username'], 
            $data['mail_password'], 
            $data['mail_encryption'], 
            $data['mail_from_address'], 
            $data['mail_from_name'], 
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    private function deactivateAll() {
        $query = "UPDATE {$this->table} SET status = 'inactive'";
        $this->conn->query($query);
    }

    /**
     * Real SMTP connect + authenticate, so a failure says why.
     *
     * This previously used Swift_SmtpTransport, which is not installed and is
     * not even in composer.json — the missing class raised an Error, which
     * catch(Exception) does not catch, so the button produced a fatal.
     *
     * @return array{success: bool, error: string}
     */
    public function testConnection($data) {
        require_once __DIR__ . '/../services/MailService.php';

        try {
            $mailService = new MailService();
            return $mailService->testConnection($data);
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Connect, authenticate and actually deliver a message, to prove the whole
     * path works rather than just the credentials.
     *
     * @return array{success: bool, error: string}
     */
    public function sendTestEmail($data, $recipient) {
        require_once __DIR__ . '/../services/MailService.php';

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Enter a valid recipient email address.'];
        }

        try {
            $mailService = new MailService();
            $config = $mailService->normalizeConfig($data);

            if (!$config) {
                return ['success' => false, 'error' => 'Host and username are required.'];
            }

            $body = '<div style="font-family:Arial,sans-serif;padding:20px;">'
                . '<h2 style="color:#0b53a1;margin-top:0;">SMTP test successful</h2>'
                . '<p>This message was sent from the Printmont admin panel to confirm your mail settings.</p>'
                . '<p style="color:#64748b;font-size:13px;">Host: ' . htmlspecialchars($config['host'])
                . ':' . (int) $config['port'] . ' (' . htmlspecialchars($config['encryption']) . ')<br>'
                . 'Sent at ' . date('Y-m-d H:i:s') . '</p></div>';

            $sent = $mailService->sendEmailWithConfig($config, $recipient, 'Printmont SMTP test', $body);

            return $sent
                ? ['success' => true, 'error' => '']
                : ['success' => false, 'error' => $mailService->getLastError()];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>