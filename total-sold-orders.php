<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/OrderController.php';
require_once 'controllers/ReportController.php';

$orderController = new OrderController();
$reportController = new ReportController();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? ''
];

// Get all sold orders (excluding cancelled)
$orders_data = $orderController->getAllSoldOrders($limit, $page, $filters);
$orders = $orders_data['orders'];
$total_pages = $orders_data['total_pages'];
$current_page = $orders_data['current_page'];
$total_count = $orders_data['total_count'];

// Get sales statistics
$sales_stats = $reportController->getSalesStatistics($filters['date_from'], $filters['date_to']);

// Get payment method stats
$payment_stats = $reportController->getPaymentMethodStatistics($filters['date_from'], $filters['date_to']);

// Get monthly sales data for chart
$monthly_sales = $reportController->getMonthlySalesData();

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get order counts for sidebar
$order_counts = $orderController->getOrderCountsByStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>All Sold Orders | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/settings.js"></script>
    <style>
        .stats-card { transition: transform 0.3s; }
        .stats-card:hover { transform: translateY(-5px); }
        .order-table th { font-weight: 600; background-color: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .payment-paid { background-color: #d4edda; color: #155724; }
        .payment-pending { background-color: #fff3cd; color: #856404; }
        .payment-failed { background-color: #f8d7da; color: #721c24; }
        .payment-refunded { background-color: #e2e3e5; color: #383d41; }
        .filter-card { background-color: #f8f9fa; }
        .export-btn { margin-left: 10px; }
        .chart-container { height: 300px; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>All Sold</strong> Orders</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" onclick="exportData('csv')">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportData('excel')">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <button type="button" class="btn btn-info" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Sales Statistics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stats-card border-left-primary">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Total Orders</h5>
                                            <span class="h2 font-weight-bold mb-0"><?php echo number_format($sales_stats['total_orders']); ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <span class="text-success mr-2"><i class="fas fa-arrow-up"></i> 
                                            <?php echo number_format($sales_stats['order_growth'], 1); ?>%
                                        </span>
                                        <span class="text-nowrap">From last month</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stats-card border-left-success">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Total Revenue</h5>
                                            <span class="h2 font-weight-bold mb-0">₹<?php echo number_format($sales_stats['total_revenue'], 2); ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <span class="text-success mr-2"><i class="fas fa-arrow-up"></i> 
                                            <?php echo number_format($sales_stats['revenue_growth'], 1); ?>%
                                        </span>
                                        <span class="text-nowrap">From last month</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stats-card border-left-info">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Average Order Value</h5>
                                            <span class="h2 font-weight-bold mb-0">₹<?php echo number_format($sales_stats['avg_order_value'], 2); ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <span class="<?php echo $sales_stats['aov_growth'] >= 0 ? 'text-success' : 'text-danger'; ?> mr-2">
                                            <i class="fas fa-arrow-<?php echo $sales_stats['aov_growth'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                            <?php echo number_format(abs($sales_stats['aov_growth']), 1); ?>%
                                        </span>
                                        <span class="text-nowrap">From last month</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stats-card border-left-warning">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Items Sold</h5>
                                            <span class="h2 font-weight-bold mb-0"><?php echo number_format($sales_stats['items_sold']); ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                                <i class="fas fa-cube"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted text-sm">
                                        <span class="text-success mr-2"><i class="fas fa-arrow-up"></i> 
                                            <?php echo number_format($sales_stats['items_growth'], 1); ?>%
                                        </span>
                                        <span class="text-nowrap">From last month</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Monthly Sales Trend</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Payment Methods</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="paymentChart"></canvas>
                                    </div>
                                    <div class="mt-3">
                                        <?php foreach ($payment_stats as $payment): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><?php echo ucfirst($payment['method']); ?></span>
                                                <span class="font-weight-bold">$<?php echo number_format($payment['total'], 2); ?></span>
                                            </div>
                                            <div class="progress mb-3" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $payment['percentage']; ?>%; background-color: <?php echo $payment['color']; ?>;">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Order Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $filters['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $filters['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $filters['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-control">
                                        <option value="">All Payments</option>
                                        <option value="paid" <?php echo $filters['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="pending" <?php echo $filters['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $filters['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $filters['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-control">
                                        <option value="">All Methods</option>
                                        <option value="credit_card" <?php echo $filters['payment_method'] == 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="paypal" <?php echo $filters['payment_method'] == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                        <option value="cash" <?php echo $filters['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="bank_transfer" <?php echo $filters['payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Order #, Customer" value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <a href="total-sold-orders.php" class="btn btn-secondary">Reset Filters</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Sold Orders (<?php echo number_format($total_count); ?>)</h5>
                                    <h6 class="card-subtitle text-muted">Complete sales history with all order details</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($orders)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <h5>No Orders Found</h5>
                                            <p class="text-muted">Try adjusting your filters or date range</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover order-table">
                                                <thead>
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Customer</th>
                                                        <th>Date</th>
                                                        <th>Items</th>
                                                        <th>Subtotal</th>
                                                        <th>Tax</th>
                                                        <th>Shipping</th>
                                                        <th>Total</th>
                                                        <th>Payment</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($orders as $order): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                                <br><small class="text-muted">Method: <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                                    <?php if (!empty($order['customer_email'])): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($order['customer_phone'])): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                                    <br><?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <?php echo $order['items_count']; ?> items
                                                                </span>
                                                                <?php if ($order['total_quantity'] > 0): ?>
                                                                    <br><small class="text-muted"><?php echo $order['total_quantity']; ?> units</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                            <td>₹<?php echo number_format($order['tax_amount'], 2); ?></td>
                                                            <td>₹<?php echo number_format($order['shipping_cost'], 2); ?></td>
                                                            <td>
                                                                <strong>$<?php echo number_format($order['grand_total'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                                    <?php echo ucfirst($order['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="view-order.php?id=<?php echo $order['id']; ?>" 
                                                                       class="btn btn-primary" title="View">
                                                                       <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="invoice.php?id=<?php echo $order['id']; ?>" 
                                                                       class="btn btn-info" title="Invoice">
                                                                       <i class="fas fa-file-invoice"></i>
                                                                    </a>
                                                                    <a href="receipt.php?id=<?php echo $order['id']; ?>" 
                                                                       class="btn btn-success" title="Receipt">
                                                                       <i class="fas fa-receipt"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="bg-light">
                                                        <td colspan="4" class="text-end"><strong>Totals (Page):</strong></td>
                                                        <td><strong>₹<?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></strong></td>
                                                        <td><strong>₹<?php echo number_format(array_sum(array_column($orders, 'tax_amount')), 2); ?></strong></td>
                                                        <td><strong>₹<?php echo number_format(array_sum(array_column($orders, 'shipping_cost')), 2); ?></strong></td>
                                                        <td><strong>₹<?php echo number_format(array_sum(array_column($orders, 'grand_total')), 2); ?></strong></td>
                                                        <td colspan="3"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination justify-content-center mt-4">
                                                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">Previous</a>
                                                </li>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Monthly Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
                datasets: [{
                    label: 'Total Revenue ($)',
                    data: <?php echo json_encode(array_column($monthly_sales, 'revenue')); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Number of Orders',
                    data: <?php echo json_encode(array_column($monthly_sales, 'orders')); ?>,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.05)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return this.label === 'Total Revenue ($)' ? '$' + value : value;
                            }
                        }
                    }
                }
            }
        });

        // Payment Method Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($payment_stats, 'method')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($payment_stats, 'total')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($payment_stats, 'color')); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Export functionality
        function exportData(type) {
            let url = new URL(window.location.href);
            url.searchParams.set('export', type);
            window.location.href = url.toString();
        }

        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    // You could implement partial refresh here
                    console.log('Stats refreshed');
                });
        }, 300000); // 5 minutes
    </script>
</body>
</html>