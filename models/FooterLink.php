<?php
class FooterLink {
    private $conn;
    private $table = "footer_links";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getBySection($section_id) {
        $query = "SELECT fl.*, fs.title as section_title 
                  FROM {$this->table} fl 
                  LEFT JOIN footer_sections fs ON fl.section_id = fs.id 
                  WHERE fl.section_id = ? 
                  ORDER BY fl.link_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }

    public function getAll() {
        $query = "SELECT fl.*, fs.title as section_title 
                  FROM {$this->table} fl 
                  LEFT JOIN footer_sections fs ON fl.section_id = fs.id 
                  ORDER BY fs.column_order ASC, fl.link_order ASC";
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }

    public function getById($id) {
        $query = "SELECT fl.*, fs.title as section_title 
                  FROM {$this->table} fl 
                  LEFT JOIN footer_sections fs ON fl.section_id = fs.id 
                  WHERE fl.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (section_id, title, url, link_order, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issis", $data['section_id'], $data['title'], $data['url'], $data['link_order'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET section_id = ?, title = ?, url = ?, link_order = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issisi", $data['section_id'], $data['title'], $data['url'], $data['link_order'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getActiveLinks() {
        $query = "SELECT fl.*, fs.title as section_title 
                  FROM {$this->table} fl 
                  LEFT JOIN footer_sections fs ON fl.section_id = fs.id 
                  WHERE fl.status = 'active' AND fs.status = 'active'
                  ORDER BY fs.column_order ASC, fl.link_order ASC";
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }
}
?>