<?php
require_once(__DIR__ . '/../config/database.php');

class ProcessingOrdersController {
    private $conn;
    private $table = "orders";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getProcessingOrders($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["o.status = 'processing'"];
        $params = [];
        $types = '';
        
        // Additional filters
        if (!empty($filters['payment_status'])) {
            $whereConditions[] = "o.payment_status = ?";
            $params[] = $filters['payment_status'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = "WHERE " . implode(' AND ', $whereConditions);
        
        // Count total records
        $countQuery = "SELECT COUNT(*) as total FROM {$this->table} o {$whereClause}";
        $countStmt = $this->conn->prepare($countQuery);
        
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $totalRows = $totalResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRows / $limit);
        
        // Main query
        $query = "SELECT 
                    o.*,
                    COUNT(oi.id) as items_count,
                    SUM(oi.quantity) as total_quantity
                  FROM {$this->table} o 
                  LEFT JOIN order_items oi ON o.id = oi.order_id 
                  {$whereClause}
                  GROUP BY o.id 
                  ORDER BY o.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        return [
            'orders' => $orders,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $totalRows
        ];
    }

    public function getProcessingStats() {
        $query = "SELECT 
                    COUNT(*) as total_processing,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_processing,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payment_processing,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_processing_hours
                  FROM {$this->table} 
                  WHERE status = 'processing'";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function bulkUpdateToShipped($orderIds) {
        $this->conn->begin_transaction();
        
        try {
            $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
            $query = "UPDATE {$this->table} SET status = 'shipped', updated_at = NOW() WHERE id IN ($placeholders) AND status = 'processing'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($orderIds)), ...$orderIds);
            $stmt->execute();
            
            $this->conn->commit();
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
?>