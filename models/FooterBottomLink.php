<?php
class FooterBottomLink {
    private $conn;
    private $table = "footer_bottom_links";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY link_order ASC";
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
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
        $query = "INSERT INTO {$this->table} (title, url, icon, link_order, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssis", $data['title'], $data['url'], $data['icon'], $data['link_order'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET title = ?, url = ?, icon = ?, link_order = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssisi", $data['title'], $data['url'], $data['icon'], $data['link_order'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getActiveLinks() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY link_order ASC";
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }
}
?>