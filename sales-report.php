<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/SalesReportController.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$salesController = new SalesReportController();

// Handle AJAX Order Details Request
if (isset($_GET['action']) && $_GET['action'] === 'get_order_details' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    $order = $salesController->getOrderModalData($_GET['id']);
    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
    exit;
}

// Handle Filter parameters
$filters = [
    'date_preset'    => $_GET['date_preset'] ?? 'all_time',
    'start_date'     => $_GET['start_date'] ?? '',
    'end_date'       => $_GET['end_date'] ?? '',
    'order_status'   => $_GET['order_status'] ?? 'all',
    'payment_status' => $_GET['payment_status'] ?? 'all',
    'payment_method' => $_GET['payment_method'] ?? 'all',
    'search'         => $_GET['search'] ?? ''
];

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $salesController->exportCsv($filters);
    exit;
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(200, (int)$_GET['limit'])) : 15;

// Fetch complete report data
$report = $salesController->getSalesReport($filters, $page, $limit);

$kpis = $report['kpis'];
$pagination = $report['pagination'];
$orders = $report['orders'];
$chartData = $report['chart'];
$paymentMethods = $report['payment_methods'];
$orderStatuses = $report['order_statuses'];
$topProducts = $report['top_products'];
$dateRange = $report['date_range'];

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sales Report & Revenue Analytics | Printmont Admin</title>
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            border: none;
            border-radius: 12px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .stat-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .report-filter-card {
            border-radius: 12px;
            border: 1px solid #edf2f7;
            background: #ffffff;
        }
        .table-sales-row {
            transition: background-color 0.15s ease;
        }
        .table-sales-row:hover {
            background-color: #f8fafc;
        }
        .badge-status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-transform: capitalize;
        }
        .badge-paid { background-color: #d1fae5; color: #065f46; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-failed { background-color: #fee2e2; color: #991b1b; }
        .badge-refunded { background-color: #e0e7ff; color: #3730a3; }
        
        .badge-delivered { background-color: #dcfce7; color: #166534; }
        .badge-shipped { background-color: #e0f2fe; color: #0369a1; }
        .badge-processing { background-color: #fef9c3; color: #854d0e; }
        .badge-confirmed { background-color: #f3e8ff; color: #6b21a8; }
        .badge-cancelled { background-color: #f3f4f6; color: #4b5563; }

        @media print {
            .sidebar, .navbar, .report-filter-card, .btn-print-hide, .pagination-wrapper {
                display: none !important;
            }
            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Header & Quick Export Actions -->
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <h3 class="mb-1 fw-bold text-dark">
                                <i class="fas fa-chart-line me-2 text-primary"></i>Sales Report & Revenue Analytics
                            </h3>
                            <p class="text-muted mb-0">
                                Trace revenue, analyze payment channels, inspect order fulfillment, and export sales ledgers.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 btn-print-hide">
                            <a href="sales-report.php?<?php echo http_build_query(array_merge($_GET, ['action' => 'export_csv'])); ?>" class="btn btn-outline-success">
                                <i class="fas fa-file-csv me-1"></i> Export to CSV
                            </a>
                            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                            <a href="sales-report.php" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-1"></i> Reset Filters
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter Toolbar -->
                    <div class="card report-filter-card shadow-sm mb-4">
                        <div class="card-body p-3 p-lg-4">
                            <form method="GET" action="sales-report.php" id="filterForm">
                                <div class="row g-3">
                                    <!-- Date Preset -->
                                    <div class="col-sm-6 col-lg-3">
                                        <label class="form-label small fw-semibold text-muted mb-1">Time Period</label>
                                        <select class="form-select" name="date_preset" id="datePresetSelect" onchange="toggleCustomDates(this.value)">
                                            <option value="all_time" <?php echo $filters['date_preset'] === 'all_time' ? 'selected' : ''; ?>>All Time</option>
                                            <option value="today" <?php echo $filters['date_preset'] === 'today' ? 'selected' : ''; ?>>Today</option>
                                            <option value="yesterday" <?php echo $filters['date_preset'] === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                            <option value="this_week" <?php echo $filters['date_preset'] === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                            <option value="this_month" <?php echo $filters['date_preset'] === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                            <option value="last_month" <?php echo $filters['date_preset'] === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                            <option value="this_year" <?php echo $filters['date_preset'] === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                                            <option value="custom" <?php echo $filters['date_preset'] === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                                        </select>
                                    </div>

                                    <!-- Custom Start Date -->
                                    <div class="col-sm-6 col-lg-2 custom-date-col <?php echo $filters['date_preset'] === 'custom' ? '' : 'd-none'; ?>">
                                        <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                                        <input type="date" class="form-control" name="start_date" id="startDateInput" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                                    </div>

                                    <!-- Custom End Date -->
                                    <div class="col-sm-6 col-lg-2 custom-date-col <?php echo $filters['date_preset'] === 'custom' ? '' : 'd-none'; ?>">
                                        <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                                        <input type="date" class="form-control" name="end_date" id="endDateInput" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                                    </div>

                                    <!-- Order Status -->
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Order Status</label>
                                        <select class="form-select" name="order_status">
                                            <option value="all">All Orders</option>
                                            <option value="pending" <?php echo $filters['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $filters['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="processing" <?php echo $filters['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $filters['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $filters['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $filters['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="refunded" <?php echo $filters['order_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </div>

                                    <!-- Payment Status -->
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Payment Status</label>
                                        <select class="form-select" name="payment_status">
                                            <option value="all">All Payments</option>
                                            <option value="paid" <?php echo $filters['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="pending" <?php echo $filters['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="failed" <?php echo $filters['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            <option value="refunded" <?php echo $filters['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label small fw-semibold text-muted mb-1">Payment Method</label>
                                        <select class="form-select" name="payment_method">
                                            <option value="all">All Methods</option>
                                            <option value="cod" <?php echo $filters['payment_method'] === 'cod' ? 'selected' : ''; ?>>Cash on Delivery (COD)</option>
                                            <option value="online" <?php echo $filters['payment_method'] === 'online' ? 'selected' : ''; ?>>Online / Prepaid</option>
                                            <option value="razorpay" <?php echo $filters['payment_method'] === 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                                        </select>
                                    </div>

                                    <!-- Keyword Search -->
                                    <div class="col-sm-8 col-lg-9">
                                        <label class="form-label small fw-semibold text-muted mb-1">Search Keywords</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-start-0 ps-0" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search by Order #, Customer Name, Phone, Email, Coupon...">
                                        </div>
                                    </div>

                                    <!-- Submit Filter Button -->
                                    <div class="col-sm-4 col-lg-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter me-1"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Date Range Indicator Banner -->
                    <div class="d-flex align-items-center justify-content-between p-2 px-3 bg-light rounded-3 mb-4 border text-muted small">
                        <div>
                            <i class="far fa-calendar-alt text-primary me-2"></i>
                            Active Reporting Window: 
                            <strong class="text-dark">
                                <?php echo date('M d, Y', strtotime($dateRange['start'])); ?>
                            </strong> 
                            to 
                            <strong class="text-dark">
                                <?php echo date('M d, Y', strtotime($dateRange['end'])); ?>
                            </strong>
                        </div>
                        <div>
                            Found <strong class="text-primary"><?php echo $pagination['total']; ?></strong> matching orders
                        </div>
                    </div>

                    <!-- Top KPI Summary Metric Cards -->
                    <div class="row mb-4 g-3">
                        <!-- Gross Sales -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase">Gross Revenue</span>
                                        <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                                            <i class="fas fa-rupee-sign"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-1 text-dark">₹<?php echo number_format((float)$kpis['gross_revenue'], 2); ?></h3>
                                    <div class="text-muted small">
                                        <span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>₹<?php echo number_format((float)$kpis['paid_revenue'], 2); ?></span> realized
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Realized Paid Revenue -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase">Pending / Pipeline</span>
                                        <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-1 text-dark">₹<?php echo number_format((float)$kpis['pending_revenue'], 2); ?></h3>
                                    <div class="text-muted small">
                                        Awaiting delivery / COD payment
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders & Items Sold -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase">Orders & Volume</span>
                                        <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-1 text-dark"><?php echo $kpis['total_orders']; ?> <span class="fs-6 fw-normal text-muted">Orders</span></h3>
                                    <div class="text-muted small">
                                        <strong><?php echo $kpis['total_items_sold']; ?></strong> units sold • AOV: <strong>₹<?php echo number_format((float)$kpis['avg_order_value'], 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Discounts & Deductions -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="card stat-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase">Deductions & Tax</span>
                                        <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                                            <i class="fas fa-tags"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-1 text-dark">₹<?php echo number_format((float)$kpis['total_discounts'], 2); ?> <span class="fs-6 fw-normal text-muted">Discounts</span></h3>
                                    <div class="text-muted small">
                                        Tax: ₹<?php echo number_format((float)$kpis['total_tax'], 2); ?> • Ship: ₹<?php echo number_format((float)$kpis['total_shipping'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Charts Section -->
                    <div class="row mb-4 g-3">
                        <!-- Revenue Trend Chart -->
                        <div class="col-xl-8">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between p-3">
                                    <h5 class="card-title mb-0 fw-bold text-dark">
                                        <i class="fas fa-chart-area me-2 text-primary"></i>Sales & Revenue Trend
                                    </h5>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-normal">Timeline Analytics</span>
                                </div>
                                <div class="card-body p-3">
                                    <?php if (empty($chartData['labels'])): ?>
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-chart-line fa-3x mb-2 opacity-50"></i>
                                            <p class="mb-0">No sales transactions found in this time range.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="chart chart-lg" style="height: 300px; position: relative;">
                                            <canvas id="salesTimelineChart"></canvas>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Channels & Order Status Breakdown -->
                        <div class="col-xl-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between p-3">
                                    <h5 class="card-title mb-0 fw-bold text-dark">
                                        <i class="fas fa-wallet me-2 text-info"></i>Payment Modes
                                    </h5>
                                </div>
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <?php if (empty($paymentMethods)): ?>
                                        <div class="text-center py-5 text-muted">
                                            <p class="mb-0">No payment data available.</p>
                                        </div>
                                    <?php else: ?>
                                        <div style="height: 200px; position: relative;" class="mb-3">
                                            <canvas id="paymentMethodsChart"></canvas>
                                        </div>
                                        <div class="list-group list-group-flush border-top">
                                            <?php foreach ($paymentMethods as $pm): 
                                                $pct = $kpis['gross_revenue'] > 0 ? round(($pm['revenue'] / $kpis['gross_revenue']) * 100, 1) : 0;
                                            ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($pm['method_name']); ?></span>
                                                        <span class="text-muted small ms-1">(<?php echo $pm['count']; ?> orders)</span>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold text-dark">₹<?php echo number_format((float)$pm['revenue'], 2); ?></span>
                                                        <span class="badge bg-light text-muted ms-1"><?php echo $pct; ?>%</span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Bestselling Products in Period -->
                    <?php if (!empty($topProducts)): ?>
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-transparent border-bottom p-3">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="fas fa-crown me-2 text-warning"></i>Top Selling Products in Selected Window
                                </h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product Name</th>
                                            <th class="text-center">Units Sold</th>
                                            <th class="text-end">Generated Revenue</th>
                                            <th class="text-end" style="width: 150px;">Share of Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $prod): 
                                            $prodShare = $kpis['gross_revenue'] > 0 ? round(($prod['total_sales'] / $kpis['gross_revenue']) * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded bg-light p-1" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-box text-secondary"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($prod['product_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center fw-bold"><?php echo $prod['total_qty']; ?></td>
                                                <td class="text-end fw-bold text-dark">₹<?php echo number_format((float)$prod['total_sales'], 2); ?></td>
                                                <td class="text-end">
                                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $prodShare); ?>%"></div>
                                                        </div>
                                                        <span class="small text-muted"><?php echo $prodShare; ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Detailed Sales Ledger Table -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between p-3">
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                <i class="fas fa-list-alt me-2 text-primary"></i>Sales Transactions Ledger
                            </h5>
                            <span class="text-muted small">
                                Showing <?php echo $pagination['start']; ?>-<?php echo $pagination['end']; ?> of <?php echo $pagination['total']; ?>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date & Time</th>
                                        <th>Customer</th>
                                        <th>Payment Mode</th>
                                        <th>Pay Status</th>
                                        <th>Order Status</th>
                                        <th class="text-end">Grand Total</th>
                                        <th class="text-center btn-print-hide">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                                <h5>No sales records found</h5>
                                                <p class="mb-0">Try clearing or broadening your filters.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $o): 
                                            $pStatusClass = 'badge-' . strtolower($o['payment_status'] ?? 'pending');
                                            $oStatusClass = 'badge-' . strtolower($o['status'] ?? 'pending');
                                        ?>
                                            <tr class="table-sales-row">
                                                <td>
                                                    <a href="view-order.php?id=<?php echo $o['id']; ?>" class="fw-bold text-primary text-decoration-none">
                                                        #<?php echo htmlspecialchars($o['order_number'] ?? $o['id']); ?>
                                                    </a>
                                                    <?php if (!empty($o['items_count'])): ?>
                                                        <div class="text-muted small"><?php echo $o['items_count']; ?> item(s)</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="text-dark small fw-medium"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></div>
                                                    <div class="text-muted small"><?php echo date('h:i A', strtotime($o['created_at'])); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($o['customer_name'] ?? 'Guest'); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($o['customer_phone'] ?? $o['customer_email'] ?? ''); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php echo strtoupper($o['payment_method'] ?? 'COD'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-status <?php echo $pStatusClass; ?>">
                                                        <?php echo ucfirst($o['payment_status'] ?? 'Pending'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-status <?php echo $oStatusClass; ?>">
                                                        <?php echo ucfirst($o['status'] ?? 'Pending'); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-dark fs-6">₹<?php echo number_format((float)$o['grand_total'], 2); ?></span>
                                                    <?php if ((float)($o['discount_amount'] ?? 0) > 0): ?>
                                                        <div class="text-danger small">-₹<?php echo number_format((float)$o['discount_amount'], 2); ?> off</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center btn-print-hide">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary btn-preview-order" data-id="<?php echo $o['id']; ?>" title="Quick Inspect">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="view-order.php?id=<?php echo $o['id']; ?>" class="btn btn-outline-secondary" title="Full Order Page">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="card-footer bg-transparent border-top p-3 pagination-wrapper">
                                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                                    <div class="text-muted small">
                                        Showing <span class="fw-bold text-dark"><?php echo $pagination['start']; ?></span> to <span class="fw-bold text-dark"><?php echo $pagination['end']; ?></span> of <span class="fw-bold text-dark"><?php echo $pagination['total']; ?></span> orders
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center gap-1 text-muted small">
                                            <span>Rows:</span>
                                            <select class="form-select form-select-sm" style="width: auto;" onchange="location.href='sales-report.php?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>&limit=' + this.value;">
                                                <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                            </select>
                                        </div>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination pagination-sm mb-0">
                                                <!-- First Page -->
                                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="sales-report.php?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                                        <i class="fas fa-angle-double-left"></i>
                                                    </a>
                                                </li>
                                                <!-- Prev Page -->
                                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="sales-report.php?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])); ?>">
                                                        <i class="fas fa-angle-left"></i>
                                                    </a>
                                                </li>
                                                
                                                <?php
                                                $range = 2;
                                                $start_p = max(1, $page - $range);
                                                $end_p = min($pagination['total_pages'], $page + $range);
                                                
                                                if ($start_p > 1) {
                                                    echo '<li class="page-item"><a class="page-link" href="sales-report.php?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                                    if ($start_p > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }
                                                
                                                for ($p = $start_p; $p <= $end_p; $p++) {
                                                    $active = ($p == $page) ? 'active' : '';
                                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="sales-report.php?' . http_build_query(array_merge($_GET, ['page' => $p])) . '">' . $p . '</a></li>';
                                                }
                                                
                                                if ($end_p < $pagination['total_pages']) {
                                                    if ($end_p < $pagination['total_pages'] - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item"><a class="page-link" href="sales-report.php?' . http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])) . '">' . $pagination['total_pages'] . '</a></li>';
                                                }
                                                ?>
                                                
                                                <!-- Next Page -->
                                                <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="sales-report.php?<?php echo http_build_query(array_merge($_GET, ['page' => min($pagination['total_pages'], $page + 1)])); ?>">
                                                        <i class="fas fa-angle-right"></i>
                                                    </a>
                                                </li>
                                                <!-- Last Page -->
                                                <li class="page-item <?php echo ($page >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
                                                    <a class="page-link" href="sales-report.php?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])); ?>">
                                                        <i class="fas fa-angle-double-right"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
            
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Quick Order Details Modal -->
    <div class="modal fade" id="salesOrderModal" tabindex="-1" aria-labelledby="salesOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light align-items-center">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="salesOrderModalLabel">Order Details</h5>
                        <span class="text-muted small" id="modalOrderNumber"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="salesOrderModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="modalFullOrderLink" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Open Full Order Management
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
    function toggleCustomDates(val) {
        const customCols = document.querySelectorAll('.custom-date-col');
        customCols.forEach(col => {
            if (val === 'custom') {
                col.classList.remove('d-none');
            } else {
                col.classList.add('d-none');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Render Timeline Chart
        const timelineCanvas = document.getElementById('salesTimelineChart');
        if (timelineCanvas) {
            const ctx = timelineCanvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(59, 125, 221, 0.35)');
            gradient.addColorStop(1, 'rgba(59, 125, 221, 0.0)');

            new Chart(timelineCanvas, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartData['labels']); ?>,
                    datasets: [
                        {
                            label: 'Gross Revenue (₹)',
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: '#3b7ddd',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#3b7ddd',
                            pointBorderColor: '#ffffff',
                            pointHoverRadius: 6,
                            pointRadius: 4,
                            tension: 0.3,
                            data: <?php echo json_encode($chartData['revenue']); ?>
                        },
                        {
                            label: 'Realized Paid (₹)',
                            fill: false,
                            borderColor: '#10b981',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointBackgroundColor: '#10b981',
                            pointRadius: 3,
                            tension: 0.3,
                            data: <?php echo json_encode($chartData['paid']); ?>
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₹' + Number(context.parsed.y).toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)'
                            },
                            ticks: {
                                callback: function(val) {
                                    return '₹' + val.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Payment Methods Doughnut Chart
        const payCanvas = document.getElementById('paymentMethodsChart');
        if (payCanvas) {
            const payLabels = <?php echo json_encode(array_column($paymentMethods, 'method_name')); ?>;
            const payData = <?php echo json_encode(array_column($paymentMethods, 'revenue')); ?>;
            
            new Chart(payCanvas, {
                type: 'doughnut',
                data: {
                    labels: payLabels,
                    datasets: [{
                        data: payData,
                        backgroundColor: ['#3b7ddd', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#6b7280'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = Number(context.parsed).toLocaleString();
                                    return label + ': ₹' + value;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // Quick Inspect Modal Handler
        const modalEl = document.getElementById('salesOrderModal');
        const orderModal = new bootstrap.Modal(modalEl);
        const modalBody = document.getElementById('salesOrderModalBody');
        const modalOrderNum = document.getElementById('modalOrderNumber');
        const fullLink = document.getElementById('modalFullOrderLink');

        document.querySelectorAll('.btn-preview-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                modalOrderNum.textContent = 'Order #' + orderId;
                fullLink.href = 'view-order.php?id=' + orderId;
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 mb-0">Loading order details...</p>
                    </div>
                `;
                orderModal.show();

                fetch('sales-report.php?action=get_order_details&id=' + orderId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.order) {
                            const o = data.order;
                            modalOrderNum.textContent = 'Order #' + (o.order_number || o.id);
                            
                            let itemsHtml = '';
                            if (o.items && o.items.length > 0) {
                                o.items.forEach(item => {
                                    itemsHtml += `
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">${item.product_name}</div>
                                                ${item.variant ? `<small class="text-muted">${item.variant}</small>` : ''}
                                            </td>
                                            <td class="text-center">${item.quantity}</td>
                                            <td class="text-end">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                                            <td class="text-end fw-bold">₹${parseFloat(item.total_price).toFixed(2)}</td>
                                        </tr>
                                    `;
                                });
                            } else {
                                itemsHtml = '<tr><td colspan="4" class="text-center text-muted">No items recorded</td></tr>';
                            }

                            modalBody.innerHTML = `
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="text-muted small fw-semibold text-uppercase mb-1">Customer Details</div>
                                            <h6 class="fw-bold text-dark mb-1">${o.customer_name || 'Guest'}</h6>
                                            <div class="text-muted small"><i class="fas fa-phone me-1"></i> ${o.customer_phone || 'N/A'}</div>
                                            <div class="text-muted small"><i class="fas fa-envelope me-1"></i> ${o.customer_email || 'N/A'}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="text-muted small fw-semibold text-uppercase mb-1">Payment & Status</div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted small">Method:</span>
                                                <span class="fw-bold">${(o.payment_method || 'COD').toUpperCase()}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted small">Payment Status:</span>
                                                <span class="badge bg-primary text-uppercase" style="font-size:0.7rem;">${o.payment_status || 'Pending'}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted small">Fulfillment:</span>
                                                <span class="badge bg-info text-uppercase" style="font-size:0.7rem;">${o.status || 'Pending'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border rounded-3 mb-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>${itemsHtml}</tbody>
                                    </table>
                                </div>

                                <div class="row justify-content-end">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between mb-1 text-muted small">
                                            <span>Subtotal:</span>
                                            <span>₹${parseFloat(o.subtotal || o.grand_total).toFixed(2)}</span>
                                        </div>
                                        ${parseFloat(o.discount_amount || 0) > 0 ? `
                                            <div class="d-flex justify-content-between mb-1 text-danger small">
                                                <span>Discount (${o.coupon_code || 'Coupon'}):</span>
                                                <span>-₹${parseFloat(o.discount_amount).toFixed(2)}</span>
                                            </div>
                                        ` : ''}
                                        <div class="d-flex justify-content-between mb-1 text-muted small">
                                            <span>Tax:</span>
                                            <span>₹${parseFloat(o.tax_amount || 0).toFixed(2)}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 text-muted small">
                                            <span>Shipping:</span>
                                            <span>₹${parseFloat(o.shipping_cost || 0).toFixed(2)}</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between fs-6 fw-bold text-dark">
                                            <span>Grand Total:</span>
                                            <span class="text-primary">₹${parseFloat(o.grand_total).toFixed(2)}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            modalBody.innerHTML = '<div class="alert alert-danger mb-0">Failed to load order details.</div>';
                        }
                    })
                    .catch(err => {
                        modalBody.innerHTML = '<div class="alert alert-danger mb-0">Error loading details.</div>';
                    });
            });
        });
    });
    </script>
</body>
</html>
