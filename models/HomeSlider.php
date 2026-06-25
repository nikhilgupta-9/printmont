<?php
class HomeSlider {
    private $conn;
    private $table = "home_sliders";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY order_number ASC, created_at DESC";
        $result = $this->conn->query($query);
        
        $sliders = [];
        while ($row = $result->fetch_assoc()) {
            $sliders[] = $row;
        }
        
        return $sliders;
    }

    public function getActiveSliders() {
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'active' 
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date IS NULL OR end_date >= CURDATE())
                  ORDER BY order_number ASC";
        $result = $this->conn->query($query);
        
        $sliders = [];
        while ($row = $result->fetch_assoc()) {
            $sliders[] = $row;
        }
        
        return $sliders;
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
        $query = "INSERT INTO {$this->table} (title, subtitle, description, image, button_text, button_url, text_position, text_color, overlay_opacity, order_number, status, start_date, end_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssdisss", 
            $data['title'], 
            $data['subtitle'], 
            $data['description'], 
            $data['image'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['text_position'], 
            $data['text_color'], 
            $data['overlay_opacity'], 
            $data['order_number'], 
            $data['status'], 
            $data['start_date'], 
            $data['end_date']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET 
                  title = ?, subtitle = ?, description = ?, image = ?, button_text = ?, 
                  button_url = ?, text_position = ?, text_color = ?, overlay_opacity = ?, 
                  order_number = ?, status = ?, start_date = ?, end_date = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssdisssi", 
            $data['title'], 
            $data['subtitle'], 
            $data['description'], 
            $data['image'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['text_position'], 
            $data['text_color'], 
            $data['overlay_opacity'], 
            $data['order_number'], 
            $data['status'], 
            $data['start_date'], 
            $data['end_date'],
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