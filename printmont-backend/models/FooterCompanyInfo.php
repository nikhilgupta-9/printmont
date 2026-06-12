<?php
class FooterCompanyInfo {
    private $conn;
    private $table = "footer_company_info";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function get() {
        $query = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function update($data) {
        // Check if record exists
        $existing = $this->get();
        
        if ($existing) {
            $query = "UPDATE {$this->table} SET 
                     company_name = ?, address = ?, customer_care = ?, 
                     newsletter_text = ?, copyright_text = ?, status = ? 
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "ssssssi",
                $data['company_name'],
                $data['address'],
                $data['customer_care'],
                $data['newsletter_text'],
                $data['copyright_text'],
                $data['status'],
                $existing['id']
            );
        } else {
            $query = "INSERT INTO {$this->table} 
                     (company_name, address, customer_care, newsletter_text, copyright_text, status) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "ssssss",
                $data['company_name'],
                $data['address'],
                $data['customer_care'],
                $data['newsletter_text'],
                $data['copyright_text'],
                $data['status']
            );
        }
        
        return $stmt->execute();
    }
}
?>