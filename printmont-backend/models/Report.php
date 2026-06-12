<?php
// models/Report.php
require_once 'config/Database.php';

class Report
{
    private $conn;
    private $table_orders = 'orders';
    private $table_order_items = 'order_items';
    
    public function __construct()
    {
        // Create Database instance and get connection
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    public function getSalesStatistics($date_from = null, $date_to = null)
    {
        $where_clauses = ["status != 'cancelled'"];
        $params = [];
        
        if ($date_from) {
            $where_clauses[] = "DATE(created_at) >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where_clauses[] = "DATE(created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_sql = implode(" AND ", $where_clauses);
        
        // Current period stats
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(grand_total), 0) as total_revenue,
                    COALESCE(AVG(grand_total), 0) as avg_order_value
                  FROM {$this->table_orders}
                  WHERE {$where_sql}";
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $current_stats = $result->fetch_assoc();
        
        // Get items sold
        $items_query = "SELECT COALESCE(SUM(oi.quantity), 0) as items_sold
                        FROM {$this->table_order_items} oi
                        JOIN {$this->table_orders} o ON oi.order_id = o.id
                        WHERE o.status != 'cancelled'";
        
        $items_params = [];
        if ($date_from) {
            $items_query .= " AND DATE(o.created_at) >= ?";
            $items_params[] = $date_from;
        }
        if ($date_to) {
            $items_query .= " AND DATE(o.created_at) <= ?";
            $items_params[] = $date_to;
        }
        
        $items_stmt = $this->conn->prepare($items_query);
        if (!empty($items_params)) {
            $items_stmt->bind_param(str_repeat('s', count($items_params)), ...$items_params);
        }
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items_stats = $items_result->fetch_assoc();
        $current_stats['items_sold'] = $items_stats['items_sold'] ?? 0;
        
        // Last month stats for comparison
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        
        $query_last = "SELECT 
                         COUNT(*) as total_orders,
                         COALESCE(SUM(grand_total), 0) as total_revenue,
                         COALESCE(AVG(grand_total), 0) as avg_order_value
                       FROM {$this->table_orders}
                       WHERE status != 'cancelled'
                       AND DATE(created_at) >= ? AND DATE(created_at) <= ?";
        
        $stmt_last = $this->conn->prepare($query_last);
        $stmt_last->bind_param("ss", $last_month_start, $last_month_end);
        $stmt_last->execute();
        $result_last = $stmt_last->get_result();
        $last_stats = $result_last->fetch_assoc();
        
        // Calculate growth percentages
        $order_growth = ($last_stats['total_orders'] > 0 && $current_stats['total_orders'] > 0) ? 
            (($current_stats['total_orders'] - $last_stats['total_orders']) / $last_stats['total_orders']) * 100 : 0;
        
        $revenue_growth = ($last_stats['total_revenue'] > 0 && $current_stats['total_revenue'] > 0) ? 
            (($current_stats['total_revenue'] - $last_stats['total_revenue']) / $last_stats['total_revenue']) * 100 : 0;
        
        $aov_growth = ($last_stats['avg_order_value'] > 0 && $current_stats['avg_order_value'] > 0) ? 
            (($current_stats['avg_order_value'] - $last_stats['avg_order_value']) / $last_stats['avg_order_value']) * 100 : 0;
        
        // Default items growth (you might want to calculate this properly)
        $items_growth = 0;
        
        return [
            'total_orders' => $current_stats['total_orders'] ?? 0,
            'total_revenue' => $current_stats['total_revenue'] ?? 0,
            'avg_order_value' => $current_stats['avg_order_value'] ?? 0,
            'items_sold' => $current_stats['items_sold'] ?? 0,
            'order_growth' => $order_growth,
            'revenue_growth' => $revenue_growth,
            'aov_growth' => $aov_growth,
            'items_growth' => $items_growth
        ];
    }
    
    public function getPaymentMethodStatistics($date_from = null, $date_to = null)
    {
        $where_clauses = ["status != 'cancelled'"];
        $params = [];
        
        if ($date_from) {
            $where_clauses[] = "DATE(created_at) >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where_clauses[] = "DATE(created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_sql = implode(" AND ", $where_clauses);
        
        $query = "SELECT 
                    COALESCE(payment_method, 'unknown') as method,
                    COUNT(*) as count,
                    COALESCE(SUM(grand_total), 0) as total
                  FROM {$this->table_orders}
                  WHERE {$where_sql}
                  GROUP BY payment_method
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        $total = 0;
        $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
        $color_index = 0;
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
            $total += $row['total'];
        }
        
        // Add percentages and colors
        foreach ($stats as &$stat) {
            $stat['percentage'] = $total > 0 ? ($stat['total'] / $total) * 100 : 0;
            $stat['color'] = $colors[$color_index % count($colors)];
            $color_index++;
        }
        
        return $stats;
    }
    
    public function getMonthlySalesData()
    {
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    DATE_FORMAT(created_at, '%b %Y') as month_display,
                    COUNT(*) as orders,
                    COALESCE(SUM(grand_total), 0) as revenue
                  FROM {$this->table_orders}
                  WHERE status != 'cancelled'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'month' => $row['month_display'],
                'orders' => (int)$row['orders'],
                'revenue' => (float)$row['revenue']
            ];
        }
        
        // Ensure we have 12 months of data
        $complete_data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $display = date('M Y', strtotime("-$i months"));
            
            $found = false;
            foreach ($data as $row) {
                if (date('Y-m', strtotime($row['month'])) == $date) {
                    $complete_data[] = [
                        'month' => $row['month'],
                        'orders' => (int)$row['orders'],
                        'revenue' => (float)$row['revenue']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $complete_data[] = [
                    'month' => $display,
                    'orders' => 0,
                    'revenue' => 0
                ];
            }
        }
        
        return $complete_data;
    }
    
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>