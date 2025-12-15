<?php
require_once(__DIR__ . '/../config/database.php');

class CompletedOrdersController {
    private $conn;
    private $table = "orders";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getCompletedOrders($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["o.status = 'completed'"];
        $params = [];
        $types = '';
        
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
            $whereConditions[] = "DATE(o.completed_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "DATE(o.completed_at) <= ?";
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
                    SUM(oi.quantity) as total_quantity,
                    DATEDIFF(o.completed_at, o.created_at) as completion_days
                  FROM {$this->table} o 
                  LEFT JOIN order_items oi ON o.id = oi.order_id 
                  {$whereClause}
                  GROUP BY o.id 
                  ORDER BY o.completed_at DESC 
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

    public function getCompletedStats($period = 'month') {
        $dateCondition = "";
        switch ($period) {
            case 'today':
                $dateCondition = "AND DATE(completed_at) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "";
        }
        
        $query = "SELECT 
                    COUNT(*) as total_completed,
                    SUM(grand_total) as total_revenue,
                    AVG(DATEDIFF(completed_at, created_at)) as avg_completion_days
                  FROM {$this->table} 
                  WHERE status = 'completed' {$dateCondition}";
        
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
}
?>