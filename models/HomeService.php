<?php
class HomeService {
    private $conn;
    private $table = "home_services";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY order_number ASC, created_at DESC";
        $result = $this->conn->query($query);
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        
        return $services;
    }

    public function getActiveServices() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY order_number ASC";
        $result = $this->conn->query($query);
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        
        return $services;
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
        $query = "INSERT INTO {$this->table} (title, description, icon, image, button_text, button_url, background_color, text_color, order_number, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssis", 
            $data['title'], 
            $data['description'], 
            $data['icon'], 
            $data['image'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['background_color'], 
            $data['text_color'], 
            $data['order_number'], 
            $data['status']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET 
                  title = ?, description = ?, icon = ?, image = ?, button_text = ?, 
                  button_url = ?, background_color = ?, text_color = ?, order_number = ?, status = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssisi", 
            $data['title'], 
            $data['description'], 
            $data['icon'], 
            $data['image'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['background_color'], 
            $data['text_color'], 
            $data['order_number'], 
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

    public function updateOrder($id, $order) {
        $query = "UPDATE {$this->table} SET order_number = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $order, $id);
        return $stmt->execute();
    }

    public function getMaxOrder() {
        $query = "SELECT MAX(order_number) as max_order FROM {$this->table}";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['max_order'] ? $row['max_order'] + 1 : 1;
    }
}
?>