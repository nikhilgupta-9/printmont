<?php
class EmailConfiguration {
    private $conn;
    private $table = "email_configurations";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT id, config_name, mail_driver, mail_host, mail_port, mail_from_address, mail_from_name, status, created_at FROM {$this->table} ORDER BY created_at DESC";
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
        // Deactivate all other configurations first
        $this->deactivateAll();
        
        $query = "INSERT INTO {$this->table} (config_name, mail_driver, mail_host, mail_port, mail_username, mail_password, mail_encryption, mail_from_address, mail_from_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssss", 
            $data['config_name'], 
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
        // If setting to active, deactivate all others first
        if ($data['status'] == 'active') {
            $this->deactivateAll();
        }
        
        $query = "UPDATE {$this->table} SET config_name = ?, mail_driver = ?, mail_host = ?, mail_port = ?, mail_username = ?, mail_password = ?, mail_encryption = ?, mail_from_address = ?, mail_from_name = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssssi", 
            $data['config_name'], 
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

    public function testConnection($data) {
        // This is a basic connection test - you might want to implement actual SMTP testing
        try {
            $transport = (new Swift_SmtpTransport($data['mail_host'], $data['mail_port'], $data['mail_encryption']))
                ->setUsername($data['mail_username'])
                ->setPassword($data['mail_password']);

            $mailer = new Swift_Mailer($transport);
            return $mailer->getTransport()->ping();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>