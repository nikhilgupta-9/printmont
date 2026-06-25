<?php
class FooterSection {
    private $conn;
    private $table = "footer_sections";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY column_order ASC";
        $result = $this->conn->query($query);
        
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        
        return $sections;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (title, column_order, status) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sis", $data['title'], $data['column_order'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET title = ?, column_order = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sisi", $data['title'], $data['column_order'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getActiveSections() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY column_order ASC";
        $result = $this->conn->query($query);
        
        $sections = [];
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        
        return $sections;
    }
}
?>