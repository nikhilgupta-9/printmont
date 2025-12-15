<?php
class FooterPaymentMethod {
    private $conn;
    private $table = "footer_payment_methods";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY display_order ASC";
        $result = $this->conn->query($query);
        
        $methods = [];
        while ($row = $result->fetch_assoc()) {
            $methods[] = $row;
        }
        
        return $methods;
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
        $query = "INSERT INTO {$this->table} (name, icon, display_order, status) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssis", $data['name'], $data['icon'], $data['display_order'], $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET name = ?, icon = ?, display_order = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssisi", $data['name'], $data['icon'], $data['display_order'], $data['status'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getActiveMethods() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY display_order ASC";
        $result = $this->conn->query($query);
        
        $methods = [];
        while ($row = $result->fetch_assoc()) {
            $methods[] = $row;
        }
        
        return $methods;
    }

    public function getPaymentOptions() {
        return [
            'visa' => ['name' => 'Visa', 'icon' => 'fab fa-cc-visa'],
            'mastercard' => ['name' => 'Mastercard', 'icon' => 'fab fa-cc-mastercard'],
            'rupay' => ['name' => 'RuPay', 'icon' => 'fas fa-credit-card'],
            'amex' => ['name' => 'American Express', 'icon' => 'fab fa-cc-amex'],
            'netbanking' => ['name' => 'Net Banking', 'icon' => 'fas fa-university'],
            'upi' => ['name' => 'UPI', 'icon' => 'fas fa-mobile-alt'],
            'wallets' => ['name' => 'Wallets', 'icon' => 'fas fa-wallet']
        ];
    }
}
?>