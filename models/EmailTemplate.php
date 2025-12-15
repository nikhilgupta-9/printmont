<?php
class EmailTemplate {
    private $conn;
    private $table = "email_templates";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
        
        return $templates;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function getByType($type) {
        $query = "SELECT * FROM {$this->table} WHERE template_type = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
        
        return $templates;
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (template_name, template_subject, template_body, template_type, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssss", $data['template_name'], $data['template_subject'], $data['template_body'], $data['template_type'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET template_name = ?, template_subject = ?, template_body = ?, template_type = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssi", $data['template_name'], $data['template_subject'], $data['template_body'], $data['template_type'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>