<?php
class Order
{
    private $conn;
    private $table_orders = "orders";
    private $table_order_items = "order_items";
    private $table_status_history = "order_status_history";
    private $customer_column = null;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * The orders table is not in database/migrations, so the column that links
     * an order to its customer differs between environments. Resolve it once
     * instead of hardcoding a guess.
     */
    public function getCustomerColumn()
    {
        if ($this->customer_column !== null) {
            return $this->customer_column ?: null;
        }

        $this->customer_column = false;

        // SHOW COLUMNS ... LIKE ? rejects placeholders on MariaDB, so read
        // information_schema instead.
        $stmt = $this->conn->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN ('user_id', 'customer_id')"
        );
        if ($stmt) {
            $stmt->bind_param("s", $this->table_orders);
            $stmt->execute();
            $result = $stmt->get_result();

            $present = [];
            while ($row = $result->fetch_assoc()) {
                $present[] = $row['COLUMN_NAME'];
            }
            $stmt->close();

            // user_id wins when both exist — it is the column actually populated.
            foreach (['user_id', 'customer_id'] as $candidate) {
                if (in_array($candidate, $present, true)) {
                    $this->customer_column = $candidate;
                    break;
                }
            }
        }

        return $this->customer_column ?: null;
    }

    // Get all orders with pagination
    public function getAllOrders($page = 1, $limit = 10, $filters = [])
    {
        $page = max(1, (int) $page);
        $limit = max(1, (int) $limit);
        $offset = ($page - 1) * $limit;

        $where_conditions = ["1=1"];
        $params = [];
        $types = "";

        // Scope to a single customer. Callers that pass this MUST have verified
        // the id against the bearer token — never straight from a query param.
        if (!empty($filters['customer_user_id'])) {
            $column = $this->getCustomerColumn();
            if ($column === null) {
                throw new Exception("Cannot scope orders to a customer: the orders table has no user_id or customer_id column.");
            }
            $where_conditions[] = "o.{$column} = ?";
            $params[] = (int) $filters['customer_user_id'];
            $types .= "i";
        }

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

    /**
     * Create an order and its line items in one transaction.
     * $data is already normalized and priced by OrderController — this method
     * does no validation and trusts nothing from the request directly.
     *
     * @return int the new order id
     */
    public function createOrder(array $data)
    {
        $customerColumn = $this->getCustomerColumn();

        $this->conn->begin_transaction();

        try {
            $orderId = null;
            $orderNumber = null;

            // order_number is NOT NULL UNIQUE and has to exist before insert, so
            // take the next free sequence and retry if another request wins the race.
            $next = $this->nextOrderSequence();

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $orderNumber = 'ORD-' . ($next + $attempt);

                $columns = ['order_number', 'customer_name', 'customer_email', 'customer_phone',
                            'subtotal', 'total_amount', 'tax_amount', 'shipping_cost', 'discount_amount',
                            'grand_total', 'status', 'payment_status', 'payment_method',
                            'notes', 'shipping_address', 'billing_address', 'shipping_method', 'coupon_code'];
                $values = [$orderNumber, $data['customer_name'], $data['customer_email'], $data['customer_phone'],
                           $data['subtotal'], $data['total_amount'], $data['tax_amount'], $data['shipping_cost'],
                           $data['discount_amount'], $data['grand_total'], 'pending', 'pending',
                           $data['payment_method'], $data['notes'], $data['shipping_address'],
                           $data['billing_address'], $data['shipping_method'], $data['coupon_code']];
                // 4 strings, 6 decimals, 8 strings — must match $columns order.
                $types = 'ssss' . 'dddddd' . 'ssssssss';

                if ($customerColumn !== null && !empty($data['user_id'])) {
                    $columns[] = $customerColumn;
                    $values[] = (int) $data['user_id'];
                    $types .= 'i';
                }

                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $sql = "INSERT INTO {$this->table_orders} (" . implode(', ', $columns) . ", created_at, updated_at)
                        VALUES ($placeholders, NOW(), NOW())";

                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $this->conn->error);
                }
                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    $orderId = $stmt->insert_id;
                    $stmt->close();
                    break;
                }

                $duplicate = $stmt->errno === 1062;
                $error = $stmt->error;
                $stmt->close();

                if (!$duplicate) {
                    throw new Exception('Failed to create order: ' . $error);
                }
            }

            if (!$orderId) {
                throw new Exception('Could not allocate a unique order number.');
            }

            $itemStmt = $this->conn->prepare(
                "INSERT INTO {$this->table_order_items}
                    (order_id, product_id, product_name, product_sku, sku, quantity, unit_price, total_price, product_image, attributes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            if (!$itemStmt) {
                throw new Exception('Prepare failed: ' . $this->conn->error);
            }

            foreach ($data['items'] as $item) {
                $itemStmt->bind_param(
                    'iisssiddss',
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_sku'],
                    $item['product_sku'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['product_image'],
                    $item['attributes']
                );
                if (!$itemStmt->execute()) {
                    $error = $itemStmt->error;
                    $itemStmt->close();
                    throw new Exception('Failed to add order item: ' . $error);
                }
            }
            $itemStmt->close();

            // Seed the status trail with the order's own creation. Without this
            // the timeline shown on Track Order stays empty until an admin
            // happens to change the status.
            $historyStmt = $this->conn->prepare(
                "INSERT INTO {$this->table_status_history} (order_id, status, notes, created_at)
                 VALUES (?, 'pending', 'Order placed', NOW())"
            );
            if ($historyStmt) {
                $historyStmt->bind_param('i', $orderId);
                $historyStmt->execute();
                $historyStmt->close();
            }

            $this->conn->commit();

            return ['id' => (int) $orderId, 'order_number' => $orderNumber];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Authoritative product data for pricing an order line.
     * Returns null when the product is missing or not purchasable.
     */
    public function getProductForOrder($productId)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.id, p.name, p.sku, p.price, p.offer_price, p.discount_price, p.status,
                    (SELECT image_url FROM product_images pi
                      WHERE pi.product_id = p.id
                      ORDER BY pi.is_primary DESC, pi.display_order ASC LIMIT 1) AS image_url
             FROM products p
             WHERE p.id = ? AND p.status = 'active'"
        );
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private function nextOrderSequence()
    {
        $result = $this->conn->query(
            "SELECT MAX(CAST(SUBSTRING(order_number, 5) AS UNSIGNED)) AS m
             FROM {$this->table_orders} WHERE order_number LIKE 'ORD-%'"
        );
        $max = $result ? (int) $result->fetch_assoc()['m'] : 0;

        return max(1001, $max + 1);
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
    /**
     * Public order lookup for the Track Order page.
     *
     * Unauthenticated, so the order number alone is never enough: the caller
     * must also supply the email or phone the order was placed with. Without
     * that second factor, sequential numbers like ORD-1011 would let anyone
     * walk the whole order table.
     */
    public function findForTracking($reference, $contact)
    {
        $query = "SELECT id, order_number, customer_name, status, payment_status,
                         grand_total, shipping_method, shipping_address,
                         courier_name, tracking_number, tracking_url,
                         created_at, updated_at
                  FROM {$this->table_orders}
                  WHERE (order_number = ? OR tracking_number = ?)
                    AND (customer_email = ? OR customer_phone = ?)
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ssss', $reference, $reference, $contact, $contact);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
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