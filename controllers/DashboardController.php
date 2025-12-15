<?php
require_once(__DIR__ . '/../config/database.php');

class DashboardController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getDashboardStats() {
        // Total Orders
        $orders_query = "SELECT COUNT(*) as total FROM orders";
        $orders_result = $this->conn->query($orders_query);
        $total_orders = $orders_result->fetch_assoc()['total'] ?? 0;

        // Total Revenue
        $revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
        $revenue_result = $this->conn->query($revenue_query);
        $total_revenue = $revenue_result->fetch_assoc()['total'] ?? 0;

        // Total Customers
        $customers_query = "SELECT COUNT(*) as total FROM customers";
        $customers_result = $this->conn->query($customers_query);
        $total_customers = $customers_result->fetch_assoc()['total'] ?? 0;

        // Total Products
        $products_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $products_result = $this->conn->query($products_query);
        $total_products = $products_result->fetch_assoc()['total'] ?? 0;

        // Growth calculations (simplified - you might want to calculate actual growth)
        $orders_growth = rand(5, 15);
        $revenue_growth = rand(8, 20);
        $customers_growth = rand(3, 10);
        $products_growth = rand(2, 8);

        // System stats (mock data - you can replace with actual system monitoring)
        $server_load = rand(20, 80);
        $storage_usage = rand(40, 90);
        $db_performance = rand(85, 99);

        return [
            'total_orders' => $total_orders,
            'total_revenue' => $total_revenue,
            'total_customers' => $total_customers,
            'total_products' => $total_products,
            'orders_growth' => $orders_growth,
            'revenue_growth' => $revenue_growth,
            'customers_growth' => $customers_growth,
            'products_growth' => $products_growth,
            'server_load' => $server_load,
            'storage_usage' => $storage_usage,
            'db_performance' => $db_performance
        ];
    }

    public function getRecentOrders($limit = 5) {
        $limit = (int)$limit;
        $query = "
            SELECT o.id, o.total_amount, o.status, o.created_at, 
                   o.customer_name, o.customer_email
            FROM orders o
            LEFT JOIN customers c ON o.user_id = c.user_id
            ORDER BY o.created_at DESC
            LIMIT $limit
        ";

        $result = $this->conn->query($query);
        
        $orders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }

    public function getTopProducts($limit = 5) {
        $limit = (int)$limit;
        $query = "
            SELECT 
    p.id, 
    p.name, 
    p.price,
    MIN(pi.image_url) AS image_url,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) AS sold_count
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id
WHERE p.status = 'active'
GROUP BY p.id
ORDER BY sold_count DESC
LIMIT $limit";


        $result = $this->conn->query($query);
        
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }

    public function getRecentCustomers($limit = 5) {
        $limit = (int)$limit;
        $query = "
            SELECT id, company_name, email, avatar, created_at
            FROM customers
            ORDER BY created_at DESC
            LIMIT $limit
        ";

        $result = $this->conn->query($query);
        
        $customers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
        }
        
        return $customers;
    }

    public function getSalesData($days = 30) {
        $sales_data = [];
        
        // Generate mock sales data for the last $days days
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('M j', strtotime("-$i days"));
            $sales_data[$date] = rand(1000, 10000);
        }
        
        return $sales_data;
    }

    public function getLowStockProducts($threshold = 10) {
        $threshold = (int)$threshold;
        $query = "
            SELECT id, name, stock_quantity, image
            FROM products
            WHERE stock_quantity <= $threshold AND status = 'active'
            ORDER BY stock_quantity ASC
            LIMIT 10
        ";

        $result = $this->conn->query($query);
        
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>