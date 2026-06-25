<?php
class Order
{
    private $conn;
    private $table_orders = "orders";
    private $table_order_items = "order_items";
    private $table_status_history = "order_status_history";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all orders with pagination
    public function getAllOrders($page = 1, $limit = 10, $filters = [])
    {
        $offset = ($page - 1) * $limit;

        $where_conditions = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['status'])) {
            $where_conditions[] = "o.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['payment_status'])) {
            $where_conditions[] = "o.payment_status = ?";
            $params[] = $filters['payment_status'];
            $types .= "s";
        }

        if (!empty($filters['search'])) {
            $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $types .= "sss";
        }

        if (!empty($filters['date_from'])) {
            $where_conditions[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $where_clause = implode(" AND ", $where_conditions);

        $query = "SELECT o.*, 
                         COUNT(oi.id) as items_count,
                         SUM(oi.quantity) as total_quantity
                  FROM {$this->table_orders} o
                  LEFT JOIN {$this->table_order_items} oi ON o.id = oi.order_id
                  WHERE {$where_clause}
                  GROUP BY o.id
                  ORDER BY o.created_at DESC 
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        // Get total count for pagination
        $count_query = "SELECT COUNT(DISTINCT o.id) as total 
                       FROM {$this->table_orders} o 
                       WHERE {$where_clause}";
        $count_stmt = $this->conn->prepare($count_query);

        if (!empty($params)) {
            // Remove limit and offset params for count query
            $count_params = array_slice($params, 0, count($params) - 2);
            $count_types = substr($types, 0, -2);
            if (!empty($count_params)) {
                $count_stmt->bind_param($count_types, ...$count_params);
            }
        }

        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()['total'];

        return [
            'orders' => $orders,
            'total_count' => $total_count,
            'total_pages' => ceil($total_count / $limit),
            'current_page' => $page
        ];
    }

    // Get order by ID
    public function getOrderById($id)
    {
        $query = "SELECT o.*, 
                         COUNT(oi.id) as items_count,
                         SUM(oi.quantity) as total_quantity
                  FROM {$this->table_orders} o
                  LEFT JOIN {$this->table_order_items} oi ON o.id = oi.order_id
                  WHERE o.id = ?
                  GROUP BY o.id";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    // Get order items
    // Get order items (more robust version)
    public function getOrderItems($order_id)
    {
        $query = "SELECT oi.*, 
                     COALESCE(pi.image_url, '') as image,
                     COALESCE(p.name, oi.product_name) as product_name,
                     COALESCE(p.sku, oi.product_sku) as product_sku
              FROM {$this->table_order_items} oi
              LEFT JOIN products p ON oi.product_id = p.id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              WHERE oi.order_id = ?
              ORDER BY oi.id";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        return $items;
    }
    // Get order status history
    public function getStatusHistory($order_id)
    {
        $query = "SELECT * FROM {$this->table_status_history} 
                  WHERE order_id = ? 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }

        return $history;
    }

    // Add this method to your Order.php model
    public function updateOrder($order_id, $data)
    {
        $setClause = [];
        $types = "";
        $values = [];

        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";

            if (is_int($value)) {
                $types .= "i";
            } elseif (is_float($value)) {
                $types .= "d";
            } else {
                $types .= "s";
            }

            $values[] = $value;
        }

        $values[] = $order_id;
        $types .= "i";

        $query = "UPDATE {$this->table_orders} SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            return $stmt->execute();
        }

        return false;
    }

    // Update order status
    public function updateOrderStatus($order_id, $status, $notes = '', $user_id = null)
    {
        // Update order
        $query = "UPDATE {$this->table_orders} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $order_id);

        if (!$stmt->execute()) {
            return false;
        }

        // Add to status history
        $history_query = "INSERT INTO {$this->table_status_history} (order_id, status, notes, created_by) VALUES (?, ?, ?, ?)";
        $history_stmt = $this->conn->prepare($history_query);
        $history_stmt->bind_param("issi", $order_id, $status, $notes, $user_id);

        return $history_stmt->execute();
    }

    // Update payment status
    public function updatePaymentStatus($order_id, $payment_status)
    {
        $query = "UPDATE {$this->table_orders} SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $payment_status, $order_id);

        return $stmt->execute();
    }

    // Delete order
    public function deleteOrder($order_id)
    {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Delete order items
            $delete_items = "DELETE FROM {$this->table_order_items} WHERE order_id = ?";
            $stmt1 = $this->conn->prepare($delete_items);
            $stmt1->bind_param("i", $order_id);
            $stmt1->execute();

            // Delete status history
            $delete_history = "DELETE FROM {$this->table_status_history} WHERE order_id = ?";
            $stmt2 = $this->conn->prepare($delete_history);
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();

            // Delete order
            $delete_order = "DELETE FROM {$this->table_orders} WHERE id = ?";
            $stmt3 = $this->conn->prepare($delete_order);
            $stmt3->bind_param("i", $order_id);
            $stmt3->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Get order statistics
    public function getOrderStats()
    {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(grand_total) as total_revenue,
                    AVG(grand_total) as avg_order_value,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                  FROM {$this->table_orders}";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }


    // Add this method to your Order.php model
public function pendingOrders($page = 1, $limit = 10, $filters = []) {
    $offset = ($page - 1) * $limit;
    
    $where_conditions = ["o.status = 'pending'"];
    $params = [];
    $types = "";
    
    if (!empty($filters['payment_status'])) {
        $where_conditions[] = "o.payment_status = ?";
        $params[] = $filters['payment_status'];
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
        $types .= "sss";
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query = "SELECT o.*, 
                     COUNT(oi.id) as items_count,
                     SUM(oi.quantity) as total_quantity
              FROM {$this->table_orders} o
              LEFT JOIN {$this->table_order_items} oi ON o.id = oi.order_id
              WHERE {$where_clause}
              GROUP BY o.id
              ORDER BY o.created_at ASC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(DISTINCT o.id) as total 
                   FROM {$this->table_orders} o
                   WHERE {$where_clause}";
    $count_stmt = $this->conn->prepare($count_query);
    
    if (!empty($params)) {
        // Remove limit and offset params for count query
        $count_params = array_slice($params, 0, count($params) - 2);
        $count_types = substr($types, 0, -2);
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    
    return [
        'orders' => $orders,
        'total_count' => $total_count,
        'total_pages' => ceil($total_count / $limit),
        'current_page' => $page
    ];
}

// You might also want these additional methods for different order statuses:

public function processingOrders($page = 1, $limit = 10, $filters = []) {
    return $this->getOrdersByStatus('processing', $page, $limit, $filters);
}

public function shippedOrders($page = 1, $limit = 10, $filters = []) {
    return $this->getOrdersByStatus('shipped', $page, $limit, $filters);
}

public function deliveredOrders($page = 1, $limit = 10, $filters = []) {
    return $this->getOrdersByStatus('delivered', $page, $limit, $filters);
}

public function cancelledOrders($page = 1, $limit = 10, $filters = []) {
    return $this->getOrdersByStatus('cancelled', $page, $limit, $filters);
}

// Generic method for any status
private function getOrdersByStatus($status, $page = 1, $limit = 10, $filters = []) {
    $offset = ($page - 1) * $limit;
    
    $where_conditions = ["o.status = ?"];
    $params = [$status];
    $types = "s";
    
    if (!empty($filters['payment_status'])) {
        $where_conditions[] = "o.payment_status = ?";
        $params[] = $filters['payment_status'];
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
        $types .= "sss";
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query = "SELECT o.*, 
                     COUNT(oi.id) as items_count,
                     SUM(oi.quantity) as total_quantity
              FROM {$this->table_orders} o
              LEFT JOIN {$this->table_order_items} oi ON o.id = oi.order_id
              WHERE {$where_clause}
              GROUP BY o.id
              ORDER BY o.created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $this->conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(DISTINCT o.id) as total 
                   FROM {$this->table_orders} o
                   WHERE {$where_clause}";
    $count_stmt = $this->conn->prepare($count_query);
    
    if (!empty($params)) {
        $count_params = array_slice($params, 0, count($params) - 2);
        $count_types = substr($types, 0, -2);
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    
    return [
        'orders' => $orders,
        'total_count' => $total_count,
        'total_pages' => ceil($total_count / $limit),
        'current_page' => $page
    ];
}

// Get order counts by status for dashboard
public function getOrderCountsByStatus() {
    $query = "SELECT 
                status,
                COUNT(*) as count,
                SUM(grand_total) as total_amount
              FROM {$this->table_orders} 
              GROUP BY status";
    
    $result = $this->conn->query($query);
    
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['status']] = [
            'count' => $row['count'],
            'total_amount' => $row['total_amount']
        ];
    }
    
    return $counts;
}

// Get recent pending orders (for dashboard widget)
public function getRecentPendingOrders($limit = 5) {
    $query = "SELECT o.*, 
                     COUNT(oi.id) as items_count
              FROM {$this->table_orders} o
              LEFT JOIN {$this->table_order_items} oi ON o.id = oi.order_id
              WHERE o.status = 'pending'
              GROUP BY o.id
              ORDER BY o.created_at DESC 
              LIMIT ?";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    return $orders;
}
}
?>