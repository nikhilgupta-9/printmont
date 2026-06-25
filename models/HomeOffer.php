<?php
class HomeOffer {
    private $conn;
    private $table = "home_offers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY order_number ASC, created_at DESC";
        $result = $this->conn->query($query);
        
        $offers = [];
        while ($row = $result->fetch_assoc()) {
            $offers[] = $row;
        }
        
        return $offers;
    }

    public function getActiveOffers() {
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'active' 
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date IS NULL OR end_date >= CURDATE())
                  ORDER BY order_number ASC";
        $result = $this->conn->query($query);
        
        $offers = [];
        while ($row = $result->fetch_assoc()) {
            $offers[] = $row;
        }
        
        return $offers;
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
        // Calculate discount percentage if not provided
        if (empty($data['discount_percentage']) && $data['original_price'] > 0 && $data['offer_price'] > 0) {
            $discount = (($data['original_price'] - $data['offer_price']) / $data['original_price']) * 100;
            $data['discount_percentage'] = round($discount);
        }

        $query = "INSERT INTO {$this->table} (title, subtitle, description, image, offer_price, original_price, discount_percentage, button_text, button_url, badge_text, badge_color, order_number, status, start_date, end_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssddissisiss", 
            $data['title'], 
            $data['subtitle'], 
            $data['description'], 
            $data['image'], 
            $data['offer_price'], 
            $data['original_price'], 
            $data['discount_percentage'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['badge_text'], 
            $data['badge_color'], 
            $data['order_number'], 
            $data['status'], 
            $data['start_date'], 
            $data['end_date']
        );
        return $stmt->execute();
    }

    public function update($id, $data) {
        // Calculate discount percentage if not provided
        if (empty($data['discount_percentage']) && $data['original_price'] > 0 && $data['offer_price'] > 0) {
            $discount = (($data['original_price'] - $data['offer_price']) / $data['original_price']) * 100;
            $data['discount_percentage'] = round($discount);
        }

        $query = "UPDATE {$this->table} SET 
                  title = ?, subtitle = ?, description = ?, image = ?, offer_price = ?, 
                  original_price = ?, discount_percentage = ?, button_text = ?, button_url = ?, 
                  badge_text = ?, badge_color = ?, order_number = ?, status = ?, start_date = ?, end_date = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssddissisissi", 
            $data['title'], 
            $data['subtitle'], 
            $data['description'], 
            $data['image'], 
            $data['offer_price'], 
            $data['original_price'], 
            $data['discount_percentage'], 
            $data['button_text'], 
            $data['button_url'], 
            $data['badge_text'], 
            $data['badge_color'], 
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