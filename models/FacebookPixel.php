<?php
class FacebookPixel {
    private $conn;
    private $table = "facebook_pixels";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $pixels = [];
        while ($row = $result->fetch_assoc()) {
            $pixels[] = $row;
        }
        
        return $pixels;
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
        // Deactivate all other entries first
        $this->deactivateAll();
        
        $query = "INSERT INTO {$this->table} (pixel_id, pixel_name, status) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $data['pixel_id'], $data['pixel_name'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        // If setting to active, deactivate all others first
        if ($data['status'] == 'active') {
            $this->deactivateAll();
        }
        
        $query = "UPDATE {$this->table} SET pixel_id = ?, pixel_name = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $data['pixel_id'], $data['pixel_name'], $data['status'], $id);
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
}
?>