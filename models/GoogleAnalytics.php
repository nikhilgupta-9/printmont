<?php
class GoogleAnalytics {
    private $conn;
    private $table = "google_analytics";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $analytics[] = $row;
        }
        
        return $analytics;
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
        
        $query = "INSERT INTO {$this->table} (tracking_id, measurement_id, status) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $data['tracking_id'], $data['measurement_id'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        // If setting to active, deactivate all others first
        if ($data['status'] == 'active') {
            $this->deactivateAll();
        }
        
        $query = "UPDATE {$this->table} SET tracking_id = ?, measurement_id = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $data['tracking_id'], $data['measurement_id'], $data['status'], $id);
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