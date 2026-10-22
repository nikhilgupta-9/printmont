<?php
require_once __DIR__ . '/../config/database.php';

class SalesReportController
{
    private $db;
    private $conn;

    public function __construct($db = null)
    {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Parse date range preset into start and end dates (Y-m-d)
     */
    public function parseDateRange($preset, $customStart = null, $customEnd = null)
    {
        $today = date('Y-m-d');
        
        switch ($preset) {
            case 'today':
                return [$today . ' 00:00:00', $today . ' 23:59:59'];
            
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'];
            
            case 'this_week':
                $startOfWeek = date('Y-m-d', strtotime('monday this week'));
                return [$startOfWeek . ' 00:00:00', $today . ' 23:59:59'];
            
            case 'this_month':
                $startOfMonth = date('Y-m-01');
                return [$startOfMonth . ' 00:00:00', $today . ' 23:59:59'];
            
            case 'last_month':
                $startOfLastMonth = date('Y-m-01', strtotime('first day of last month'));
                $endOfLastMonth = date('Y-m-t', strtotime('last day of last month'));
                return [$startOfLastMonth . ' 00:00:00', $endOfLastMonth . ' 23:59:59'];
            
            case 'this_year':
                $startOfYear = date('Y-01-01');
                return [$startOfYear . ' 00:00:00', $today . ' 23:59:59'];
            
            case 'custom':
                $start = !empty($customStart) ? date('Y-m-d 00:00:00', strtotime($customStart)) : '2000-01-01 00:00:00';
                $end = !empty($customEnd) ? date('Y-m-d 23:59:59', strtotime($customEnd)) : $today . ' 23:59:59';
                return [$start, $end];
            
            case 'all_time':
            default:
                return ['2000-01-01 00:00:00', $today . ' 23:59:59'];
        }
    }

    /**
     * Build WHERE SQL clause from filter array
     */
    private function buildWhereClause($filters)
    {
        $preset = $filters['date_preset'] ?? 'all_time';
        $customStart = $filters['start_date'] ?? null;
        $customEnd = $filters['end_date'] ?? null;
        
        list($startDate, $endDate) = $this->parseDateRange($preset, $customStart, $customEnd);
        
        $where = ["o.created_at >= '$startDate'", "o.created_at <= '$endDate'"];

        // Order Status Filter
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $status = $this->conn->real_escape_string($filters['order_status']);
            $where[] = "o.status = '$status'";
        }

        // Payment Status Filter
        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $pStatus = $this->conn->real_escape_string($filters['payment_status']);
            $where[] = "o.payment_status = '$pStatus'";
        }

        // Payment Method Filter
        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $method = $this->conn->real_escape_string($filters['payment_method']);
            if ($method === 'cod') {
                $where[] = "(LOWER(o.payment_method) LIKE '%cod%' OR LOWER(o.payment_method) LIKE '%cash%')";
            } elseif ($method === 'online') {
                $where[] = "(LOWER(o.payment_method) NOT LIKE '%cod%' AND LOWER(o.payment_method) NOT LIKE '%cash%')";
            } else {
                $where[] = "LOWER(o.payment_method) LIKE '%$method%'";
            }
        }

        // Keyword Search Filter
        if (!empty($filters['search'])) {
            $search = $this->conn->real_escape_string(trim($filters['search']));
            $where[] = "(o.order_number LIKE '%$search%' OR o.customer_name LIKE '%$search%' OR o.customer_email LIKE '%$search%' OR o.customer_phone LIKE '%$search%' OR o.coupon_code LIKE '%$search%')";
        }

        return [
            'sql' => implode(' AND ', $where),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    /**
     * Get Complete Sales Report Data (KPIs, Charts, Top Products, and Paginated Orders)
     */
    public function getSalesReport($filters = [], $page = 1, $limit = 15)
    {
        $page = max(1, (int)$page);
        $limit = max(5, min(200, (int)$limit));
        $offset = ($page - 1) * $limit;

        $whereData = $this->buildWhereClause($filters);
        $whereSql = $whereData['sql'];

        // 1. Overall Summary / KPIs
        $kpiSql = "
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(o.grand_total), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.grand_total ELSE 0 END), 0) as paid_revenue,
                COALESCE(SUM(CASE WHEN o.payment_status = 'pending' THEN o.grand_total ELSE 0 END), 0) as pending_revenue,
                COALESCE(SUM(CASE WHEN o.status = 'cancelled' OR o.status = 'refunded' THEN o.grand_total ELSE 0 END), 0) as refunded_revenue,
                COALESCE(AVG(o.grand_total), 0) as avg_order_value,
                COALESCE(SUM(o.discount_amount), 0) as total_discounts,
                COALESCE(SUM(o.tax_amount), 0) as total_tax,
                COALESCE(SUM(o.shipping_cost), 0) as total_shipping
            FROM orders o
            WHERE $whereSql
        ";
        $kpiResult = $this->conn->query($kpiSql);
        $kpis = $kpiResult ? $kpiResult->fetch_assoc() : [];

        // 2. Total items sold in filtered orders
        $itemsSoldSql = "
            SELECT COALESCE(SUM(oi.quantity), 0) as total_items_sold
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE $whereSql
        ";
        $itemsResult = $this->conn->query($itemsSoldSql);
        $itemsData = $itemsResult ? $itemsResult->fetch_assoc() : [];
        $kpis['total_items_sold'] = (int)($itemsData['total_items_sold'] ?? 0);

        // 3. Payment Method Distribution
        $payMethodSql = "
            SELECT 
                CASE 
                    WHEN LOWER(o.payment_method) LIKE '%cod%' OR LOWER(o.payment_method) LIKE '%cash%' THEN 'Cash on Delivery (COD)'
                    WHEN LOWER(o.payment_method) LIKE '%razorpay%' THEN 'Razorpay'
                    WHEN LOWER(o.payment_method) LIKE '%upi%' THEN 'UPI / QR'
                    WHEN LOWER(o.payment_method) LIKE '%card%' OR LOWER(o.payment_method) LIKE '%credit%' THEN 'Credit/Debit Card'
                    WHEN o.payment_method IS NULL OR o.payment_method = '' THEN 'Not Specified'
                    ELSE o.payment_method
                END as method_name,
                COUNT(*) as count,
                COALESCE(SUM(o.grand_total), 0) as revenue
            FROM orders o
            WHERE $whereSql
            GROUP BY method_name
            ORDER BY revenue DESC
        ";
        $payResult = $this->conn->query($payMethodSql);
        $paymentMethods = [];
        if ($payResult && $payResult->num_rows > 0) {
            while ($row = $payResult->fetch_assoc()) {
                $paymentMethods[] = $row;
            }
        }

        // 4. Order Status Distribution
        $statusSql = "
            SELECT 
                o.status,
                COUNT(*) as count,
                COALESCE(SUM(o.grand_total), 0) as revenue
            FROM orders o
            WHERE $whereSql
            GROUP BY o.status
            ORDER BY count DESC
        ";
        $statusResult = $this->conn->query($statusSql);
        $orderStatuses = [];
        if ($statusResult && $statusResult->num_rows > 0) {
            while ($row = $statusResult->fetch_assoc()) {
                $orderStatuses[] = $row;
            }
        }

        // 5. Timeline Chart Data (Daily or Monthly depending on range)
        $diffDays = (strtotime($whereData['end_date']) - strtotime($whereData['start_date'])) / 86400;
        $groupByFormat = ($diffDays <= 65) ? '%Y-%m-%d' : '%Y-%m';
        $displayFormat = ($diffDays <= 65) ? 'M d' : 'M Y';

        $timelineSql = "
            SELECT 
                DATE_FORMAT(o.created_at, '$groupByFormat') as period,
                COUNT(*) as orders_count,
                COALESCE(SUM(o.grand_total), 0) as revenue,
                COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.grand_total ELSE 0 END), 0) as paid_revenue
            FROM orders o
            WHERE $whereSql
            GROUP BY period
            ORDER BY period ASC
        ";
        $timelineResult = $this->conn->query($timelineSql);
        $chartLabels = [];
        $chartRevenue = [];
        $chartOrders = [];
        $chartPaid = [];

        if ($timelineResult && $timelineResult->num_rows > 0) {
            while ($row = $timelineResult->fetch_assoc()) {
                $time = strtotime($row['period'] . ($diffDays > 65 ? '-01' : ''));
                $chartLabels[] = date($displayFormat, $time);
                $chartRevenue[] = (float)$row['revenue'];
                $chartPaid[] = (float)$row['paid_revenue'];
                $chartOrders[] = (int)$row['orders_count'];
            }
        }

        // 6. Top 5 Best Selling Products in filtered date range
        $topProductsSql = "
            SELECT 
                oi.product_id,
                oi.product_name,
                oi.product_image,
                SUM(oi.quantity) as total_qty,
                SUM(oi.total_price) as total_sales
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE $whereSql
            GROUP BY oi.product_id, oi.product_name, oi.product_image
            ORDER BY total_sales DESC
            LIMIT 5
        ";
        $topProdResult = $this->conn->query($topProductsSql);
        $topProducts = [];
        if ($topProdResult && $topProdResult->num_rows > 0) {
            while ($row = $topProdResult->fetch_assoc()) {
                $topProducts[] = $row;
            }
        }

        // 7. Paginated Orders List
        $ordersSql = "
            SELECT 
                o.*,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
                (SELECT GROUP_CONCAT(CONCAT(product_name, ' (x', quantity, ')') SEPARATOR ', ') FROM order_items WHERE order_id = o.id) as item_summary
            FROM orders o
            WHERE $whereSql
            ORDER BY o.created_at DESC
            LIMIT $offset, $limit
        ";
        $ordersResult = $this->conn->query($ordersSql);
        $orders = [];
        if ($ordersResult && $ordersResult->num_rows > 0) {
            while ($row = $ordersResult->fetch_assoc()) {
                $orders[] = $row;
            }
        }

        $totalRecords = (int)($kpis['total_orders'] ?? 0);
        $totalPages = ($totalRecords > 0) ? (int)ceil($totalRecords / $limit) : 1;

        return [
            'kpis' => $kpis,
            'payment_methods' => $paymentMethods,
            'order_statuses' => $orderStatuses,
            'chart' => [
                'labels' => $chartLabels,
                'revenue' => $chartRevenue,
                'paid' => $chartPaid,
                'orders' => $chartOrders
            ],
            'top_products' => $topProducts,
            'orders' => $orders,
            'pagination' => [
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'offset' => $offset,
                'start' => ($totalRecords > 0) ? ($offset + 1) : 0,
                'end' => min($offset + count($orders), $totalRecords)
            ],
            'date_range' => [
                'start' => $whereData['start_date'],
                'end' => $whereData['end_date']
            ]
        ];
    }

    /**
     * Get Order Details with items for AJAX modal
     */
    public function getOrderModalData($orderId)
    {
        $orderId = (int)$orderId;
        $orderSql = "SELECT * FROM orders WHERE id = $orderId LIMIT 1";
        $orderRes = $this->conn->query($orderSql);
        if (!$orderRes || $orderRes->num_rows === 0) {
            return null;
        }

        $order = $orderRes->fetch_assoc();

        $itemsSql = "SELECT * FROM order_items WHERE order_id = $orderId";
        $itemsRes = $this->conn->query($itemsSql);
        $items = [];
        if ($itemsRes && $itemsRes->num_rows > 0) {
            while ($row = $itemsRes->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $order['items'] = $items;

        return $order;
    }

    /**
     * Export Filtered Sales to CSV
     */
    public function exportCsv($filters)
    {
        $whereData = $this->buildWhereClause($filters);
        $whereSql = $whereData['sql'];

        $query = "
            SELECT 
                o.id,
                o.order_number,
                o.created_at,
                o.customer_name,
                o.customer_phone,
                o.customer_email,
                o.subtotal,
                o.discount_amount,
                o.coupon_code,
                o.tax_amount,
                o.shipping_cost,
                o.grand_total,
                o.payment_method,
                o.payment_status,
                o.transaction_id,
                o.status as order_status,
                o.shipping_address
            FROM orders o
            WHERE $whereSql
            ORDER BY o.created_at DESC
        ";

        $result = $this->conn->query($query);

        $filename = "sales_report_" . date('Y-m-d_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // CSV Header
        fputcsv($output, [
            'Order ID',
            'Order Number',
            'Order Date',
            'Customer Name',
            'Phone',
            'Email',
            'Subtotal (INR)',
            'Discount (INR)',
            'Coupon Applied',
            'Tax (INR)',
            'Shipping (INR)',
            'Grand Total (INR)',
            'Payment Method',
            'Payment Status',
            'Transaction ID',
            'Order Status',
            'Shipping Address'
        ]);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['order_number'],
                    date('Y-m-d H:i:s', strtotime($row['created_at'])),
                    $row['customer_name'],
                    $row['customer_phone'],
                    $row['customer_email'],
                    number_format((float)$row['subtotal'], 2, '.', ''),
                    number_format((float)$row['discount_amount'], 2, '.', ''),
                    $row['coupon_code'] ?? 'None',
                    number_format((float)$row['tax_amount'], 2, '.', ''),
                    number_format((float)$row['shipping_cost'], 2, '.', ''),
                    number_format((float)$row['grand_total'], 2, '.', ''),
                    strtoupper($row['payment_method'] ?? 'N/A'),
                    ucfirst($row['payment_status'] ?? 'pending'),
                    $row['transaction_id'] ?? 'N/A',
                    ucfirst($row['order_status'] ?? 'pending'),
                    str_replace(["\r", "\n"], ' ', $row['shipping_address'] ?? '')
                ]);
            }
        }

        fclose($output);
        exit;
    }
}
