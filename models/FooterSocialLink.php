<?php
class FooterSocialLink {
    private $conn;
    private $table = "footer_social_links";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY display_order ASC";
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
        $query = "INSERT INTO {$this->table} (platform, url, icon, display_order, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssis", $data['platform'], $data['url'], $data['icon'], $data['display_order'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET platform = ?, url = ?, icon = ?, display_order = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssisi", $data['platform'], $data['url'], $data['icon'], $data['display_order'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getActiveLinks() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY display_order ASC";
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }

    public function getPlatformOptions() {
        return [
            'facebook' => ['name' => 'Facebook', 'icon' => 'fab fa-facebook-f'],
            'twitter' => ['name' => 'Twitter', 'icon' => 'fab fa-twitter'],
            'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram'],
            'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin-in'],
            'youtube' => ['name' => 'YouTube', 'icon' => 'fab fa-youtube'],
            'pinterest' => ['name' => 'Pinterest', 'icon' => 'fab fa-pinterest-p']
        ];
    }
}
?>