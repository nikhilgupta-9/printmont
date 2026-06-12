<?php
class MetaKeyword {
    private $conn;
    private $table = "meta_keywords";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $keywords = [];
        while ($row = $result->fetch_assoc()) {
            $keywords[] = $row;
        }
        
        return $keywords;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function getByUrl($url) {
        $query = "SELECT * FROM {$this->table} WHERE page_url = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (page_url, meta_title, meta_description, keywords) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $data['page_url'], $data['meta_title'], $data['meta_description'], $data['keywords']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET page_url = ?, meta_title = ?, meta_description = ?, keywords = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssi", $data['page_url'], $data['meta_title'], $data['meta_description'], $data['keywords'], $id);
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