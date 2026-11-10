<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/OrderController.php';

$database = new Database();
$db = $database->getConnection();
$orderController = new OrderController();

// Pagination and filters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 10;
$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];
$orderViewTitles = [
    'pending' => 'Pending Orders',
    'confirmed' => 'Confirmed Orders',
    'processing' => 'In Production',
    'shipped' => 'Shipped Orders',
    'delivered' => 'Delivered Orders',
    'cancelled' => 'Cancelled Orders',
    'refunded' => 'Refunded Orders',
];
$orderViewTitle = $orderViewTitles[$filters['status']] ?? ($filters['payment_status'] === 'paid' ? 'Paid Orders' : 'All Orders');

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Export with high limit
    $exportData = $orderController->getAllOrders(1, 10000, $filters);
    $exportOrders = $exportData['orders'] ?? [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=printmont_orders_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Order ID', 'Order Number', 'Internal Tracking ID', 'Courier Partner', 'Courier AWB',
        'Customer Name', 'Customer Email', 'Customer Phone',
        'Items Count', 'Total Quantity', 'Subtotal', 'Tax', 'Shipping', 'Discount', 'Coupon Code',
        'Grand Total (INR)', 'Order Status', 'Payment Status', 'Payment Method', 'Transaction ID',
        'Shipping Address', 'Billing Address', 'Placed Date', 'Last Updated'
    ]);

    foreach ($exportOrders as $o) {
        fputcsv($output, [
            $o['id'],
            $o['order_number'],
            $o['tracking_number'] ?? '',
            $o['courier_name'] ?? '',
            $o['courier_tracking_number'] ?? '',
            $o['customer_name'] ?? '',
            $o['customer_email'] ?? '',
            $o['customer_phone'] ?? '',
            $o['items_count'] ?? 1,
            $o['total_quantity'] ?? 1,
            number_format((float)($o['subtotal'] ?? 0), 2, '.', ''),
            number_format((float)($o['tax_amount'] ?? 0), 2, '.', ''),
            number_format((float)($o['shipping_cost'] ?? 0), 2, '.', ''),
            number_format((float)($o['discount_amount'] ?? 0), 2, '.', ''),
            $o['coupon_code'] ?? '',
            number_format((float)($o['grand_total'] ?? $o['total_amount']), 2, '.', ''),
            ucfirst($o['status'] ?? 'pending'),
            ucfirst($o['payment_status'] ?? 'pending'),
            strtoupper($o['payment_method'] ?? 'COD'),
            $o['transaction_id'] ?? '',
            str_replace(["\r", "\n"], " ", $o['shipping_address'] ?? ''),
            str_replace(["\r", "\n"], " ", $o['billing_address'] ?? ''),
            $o['created_at'] ?? '',
            $o['updated_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

$orders_data = $orderController->getAllOrders($page, $limit, $filters);
$orders = $orders_data['orders'];
$totalPages = (int)($orders_data['total_pages'] ?? 1);
$pageNum = (int)($orders_data['current_page'] ?? $page);
$total_count = (int)($orders_data['total_count'] ?? 0);

// Attach items preview for thumbnails and multi-item tooltips
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $itemsByOrder = $orderController->getOrderById(0); // placeholder or direct call
    // Fetch items batched
    require_once 'models/Order.php';
    $orderModel = new Order($db);
    $itemsMap = $orderModel->getItemsForOrders($orderIds);
    foreach ($orders as &$ord) {
        $ord['preview_items'] = $itemsMap[(int)$ord['id']] ?? [];
    }
    unset($ord);
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get order statistics
$stats = $orderController->getOrderStats();

require_once 'controllers/LogoController.php';
$logoController = new LogoController($db);
$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($orderViewTitle); ?> | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        .kpi-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
        }
        .kpi-card .kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            gap: 4px;
        }
        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 13.5px;
            padding: 10px 18px;
            border-radius: 8px 8px 0 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            transition: all 0.2s ease;
        }
        .nav-tabs-custom .nav-link:hover {
            color: #0b53a1;
            background: #f8fafc;
        }
        .nav-tabs-custom .nav-link.active {
            color: #0b53a1;
            border-bottom: 2.5px solid #0b53a1;
            background: #f1f5f9;
        }
        .nav-tabs-custom .badge {
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 12px;
        }

        .filter-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.02);
        }

        .order-table th {
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 14px;
            white-space: nowrap;
        }
        .order-table td {
            padding: 14px;
            vertical-align: middle;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .order-table tr:hover {
            background-color: #f8fafc;
        }

        .badge-status {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-transform: capitalize;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-processing { background-color: #e0f2fe; color: #0369a1; }
        .badge-confirmed { background-color: #e0e7ff; color: #3730a3; }
        .badge-shipped { background-color: #f3e8ff; color: #6b21a8; }
        .badge-delivered { background-color: #dcfce7; color: #166534; }
        .badge-cancelled, .badge-refunded { background-color: #fee2e2; color: #991b1b; }
        .badge-refunded { background-color: #f1f5f9; color: #475569; }

        .badge-pay {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .badge-pay-paid { background: #dcfce7; color: #15803d; }
        .badge-pay-pending { background: #fef3c7; color: #b45309; }
        .badge-pay-failed, .badge-pay-refunded { background: #fee2e2; color: #b91c1c; }

        .method-pill {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .method-cod { background: #ffedd5; color: #c2410c; }
        .method-prepaid { background: #dbeafe; color: #1d4ed8; }

        .item-thumb {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
        .item-thumb-placeholder {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 14px;
        }

        .btn-action-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .order-page-heading h1 { letter-spacing: -.025em; }
        .nav-tabs-custom { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; scrollbar-width: thin; }
        .nav-tabs-custom .nav-item { flex: 0 0 auto; }
        .nav-tabs-custom .nav-link { white-space: nowrap; }
        .filter-box .form-control, .filter-box .form-select, .filter-box .btn { min-height: 36px; }
        .order-table { min-width: 1160px; }
        .orders-table-card { border-radius: 12px; overflow: hidden; }
        .orders-table-card .card-header { gap: 12px; }
        @media (max-width: 767.98px) {
            main.content { padding: 1rem .75rem !important; }
            .order-page-heading { align-items: flex-start !important; }
            .order-page-heading > div { width: 100%; }
            .order-page-heading h1 { font-size: 1.3rem; }
            .order-page-actions { display: flex; gap: .5rem; }
            .order-page-actions .btn { flex: 1 1 0; margin: 0 !important; }
            .kpi-card .card-body { padding: .9rem !important; }
            .kpi-card h3 { font-size: 1.2rem; }
            .nav-tabs-custom { margin-right: -.75rem; padding-right: .75rem; }
            .nav-tabs-custom .nav-link { padding: .65rem .8rem; font-size: .8rem; }
            .filter-box .card-body { padding: .85rem !important; }
            .filter-box .form-control, .filter-box .form-select { min-height: 42px; font-size: 16px; }
            .filter-actions { display: flex; }
            .filter-actions > * { flex: 1 1 0; min-height: 42px; }
            .orders-table-card .card-header { align-items: flex-start !important; }
            .orders-table-card .card-header > div:last-child { white-space: nowrap; }
            .order-table { min-width: 0; }
            .order-table thead { display: none; }
            .order-table tbody { display: block; padding: .7rem; }
            .order-table tbody tr.no-orders-row { display: block; border: 0; }
            .order-table tbody tr.no-orders-row td { display: block; width: 100%; }
            .order-table tbody tr:not(.no-orders-row) {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: .2rem .75rem;
                margin-bottom: .7rem;
                padding: .55rem .7rem;
                border: 1px solid #e6ebf2;
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, .035);
            }
            .order-table tbody tr:last-child { margin-bottom: 0; }
            .order-table tbody tr:not(.no-orders-row) td {
                display: block;
                min-width: 0;
                padding: .48rem 0 !important;
                border: 0 !important;
                white-space: normal;
                overflow-wrap: anywhere;
                text-align: left !important;
            }
            .order-table tbody tr:not(.no-orders-row) td::before {
                display: block;
                margin-bottom: .22rem;
                color: #778399;
                font-size: .65rem;
                font-weight: 700;
                letter-spacing: .045em;
                text-transform: uppercase;
            }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(1) { display: none; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(2) { grid-column: 1 / -1; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(2)::before { content: 'Order'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(3) { grid-column: 1 / -1; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(3)::before { content: 'Customer'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(4) { grid-column: 1 / -1; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(4)::before { content: 'Items'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(5)::before { content: 'Amount'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(6)::before { content: 'Payment'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(7)::before { content: 'Order status'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(8) { grid-column: 1 / -1; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(8)::before { content: 'Courier / tracking'; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(9) { grid-column: 1 / -1; padding-top: .55rem !important; border-top: 1px solid #edf0f4 !important; }
            .order-table tbody tr:not(.no-orders-row) td:nth-child(9)::before { content: 'Order actions'; }
            .order-table .btn-group { display: flex; gap: .4rem; }
            .order-table .btn-action-icon { width: 40px; height: 38px; }
            .orders-table-card .card-body.p-0 { padding: 0 !important; }
            .orders-table-card .table-responsive { overflow: visible; }
            .orders-table-card .pagination { flex-wrap: wrap; }
            .orders-table-card .card-body > .p-3 { align-items: flex-start !important; }
        }
        @media (max-width: 380px) {
            .order-table tbody { padding: .5rem; }
            .order-table tbody tr:not(.no-orders-row) { padding: .45rem .55rem; }
            .order-page-actions .btn { font-size: .75rem; padding: .45rem .35rem; }
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Top Page Title & Actions -->
                    <div class="row mb-3 align-items-center order-page-heading">
                        <div class="col-md-6">
                            <h1 class="h3 fw-bold mb-1 text-dark"><?php echo htmlspecialchars($orderViewTitle); ?></h1>
                            <p class="text-muted mb-0 font-13">Review customer details, payment, order items and dispatch status from one list.</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0 order-page-actions">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline-success btn-sm me-2 fw-semibold">
                                <i class="fas fa-file-csv me-1"></i> Export to CSV
                            </a>
                            <a href="orders.php" class="btn btn-primary btn-sm fw-semibold">
                                <i class="fas fa-sync-alt me-1"></i> Refresh Orders
                            </a>
                        </div>
                    </div>

                    <!-- KPI Statistics Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-bold font-11">Total Revenue</span>
                                            <h3 class="fw-bold text-dark mt-1 mb-0">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                                            <span class="text-muted font-11">From <?php echo number_format($stats['total_orders'] ?? 0); ?> orders placed</span>
                                        </div>
                                        <div class="kpi-icon bg-success-subtle text-success">
                                            <i class="fas fa-indian-rupee-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-bold font-11">Pending Fulfillment</span>
                                            <h3 class="fw-bold text-warning mt-1 mb-0"><?php echo number_format($stats['pending_orders'] ?? 0); ?></h3>
                                            <span class="text-muted font-11">Requires confirmation / packing</span>
                                        </div>
                                        <div class="kpi-icon bg-warning-subtle text-warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-bold font-11">In-Transit / Shipped</span>
                                            <h3 class="fw-bold text-info mt-1 mb-0"><?php echo number_format($stats['shipped_orders'] ?? 0); ?></h3>
                                            <span class="text-muted font-11">With logistics / couriers</span>
                                        </div>
                                        <div class="kpi-icon bg-info-subtle text-info">
                                            <i class="fas fa-truck-fast"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted text-uppercase fw-bold font-11">Delivered / Completed</span>
                                            <h3 class="fw-bold text-primary mt-1 mb-0"><?php echo number_format($stats['delivered_orders'] ?? 0); ?></h3>
                                            <span class="text-muted font-11"><?php echo number_format($stats['paid_orders'] ?? 0); ?> orders fully paid</span>
                                        </div>
                                        <div class="kpi-icon bg-primary-subtle text-primary">
                                            <i class="fas fa-circle-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter Tabs -->
                    <?php 
                        $activeStatus = $filters['status']; 
                        function tabUrl($st) {
                            $q = $_GET;
                            $q['status'] = $st;
                            unset($q['payment_status']);
                            $q['page'] = 1;
                            return '?' . http_build_query($q);
                        }
                        function paidTabUrl() {
                            $q = $_GET;
                            unset($q['status']);
                            $q['payment_status'] = 'paid';
                            $q['page'] = 1;
                            return '?' . http_build_query($q);
                        }
                    ?>
                    <ul class="nav nav-tabs nav-tabs-custom mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo empty($activeStatus) && $filters['payment_status'] !== 'paid' ? 'active' : ''; ?>" href="<?php echo tabUrl(''); ?>">
                                All Orders <span class="badge bg-secondary"><?php echo $stats['total_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filters['payment_status'] === 'paid' ? 'active' : ''; ?>" href="<?php echo paidTabUrl(); ?>">
                                <i class="fas fa-circle-check fa-xs text-success"></i> Paid <span class="badge bg-success"><?php echo $stats['paid_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'pending' ? 'active' : ''; ?>" href="<?php echo tabUrl('pending'); ?>">
                                <i class="fas fa-hourglass-start fa-xs text-warning"></i> Pending <span class="badge bg-warning text-dark"><?php echo $stats['pending_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'processing' ? 'active' : ''; ?>" href="<?php echo tabUrl('processing'); ?>">
                                <i class="fas fa-gears fa-xs text-info"></i> In Production <span class="badge bg-info text-dark"><?php echo $stats['processing_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'shipped' ? 'active' : ''; ?>" href="<?php echo tabUrl('shipped'); ?>">
                                <i class="fas fa-truck fa-xs text-purple" style="color: #8b5cf6;"></i> Shipped <span class="badge" style="background:#8b5cf6; color:#fff;"><?php echo $stats['shipped_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'delivered' ? 'active' : ''; ?>" href="<?php echo tabUrl('delivered'); ?>">
                                <i class="fas fa-check fa-xs text-success"></i> Delivered <span class="badge bg-success"><?php echo $stats['delivered_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'cancelled' ? 'active' : ''; ?>" href="<?php echo tabUrl('cancelled'); ?>">
                                <i class="fas fa-ban fa-xs text-danger"></i> Cancelled <span class="badge bg-danger"><?php echo $stats['cancelled_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activeStatus === 'refunded' ? 'active' : ''; ?>" href="<?php echo tabUrl('refunded'); ?>">
                                <i class="fas fa-rotate-left fa-xs text-muted"></i> Refunded <span class="badge bg-secondary"><?php echo $stats['refunded_orders'] ?? 0; ?></span>
                            </a>
                        </li>
                    </ul>

                    <!-- Advanced Filter Card -->
                    <div class="card filter-box mb-4">
                        <div class="card-body p-3">
                            <form method="GET" class="row g-2 align-items-end">
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filters['status']); ?>">
                                
                                <div class="col-md-3">
                                    <label class="form-label font-12 fw-semibold text-muted mb-1">Search Keywords</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" name="search" class="form-control form-control-sm" 
                                               placeholder="Order #, Customer, Phone, AWB, SKU" 
                                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label font-12 fw-semibold text-muted mb-1">Payment Status</label>
                                    <select name="payment_status" class="form-select form-select-sm">
                                        <option value="">All Payment Statuses</option>
                                        <option value="paid" <?php echo $filters['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="pending" <?php echo $filters['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending / COD</option>
                                        <option value="failed" <?php echo $filters['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $filters['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label font-12 fw-semibold text-muted mb-1">Payment Method</label>
                                    <select name="payment_method" class="form-select form-select-sm">
                                        <option value="">All Methods</option>
                                        <option value="cod" <?php echo $filters['payment_method'] == 'cod' ? 'selected' : ''; ?>>Cash On Delivery (COD)</option>
                                        <option value="razorpay" <?php echo $filters['payment_method'] == 'razorpay' ? 'selected' : ''; ?>>Razorpay / UPI / Card</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label font-12 fw-semibold text-muted mb-1">From Date</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $filters['date_from']; ?>">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label font-12 fw-semibold text-muted mb-1">To Date</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $filters['date_to']; ?>">
                                </div>

                                <div class="col-md-1 d-flex gap-1 filter-actions">
                                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Apply Filters">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                    <a href="orders.php" class="btn btn-light btn-sm border" title="Reset Filters">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table Card -->
                    <div class="card shadow-sm border-0 orders-table-card">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-0 fw-bold">Orders Register</h5>
                                <small class="text-muted">Showing <?php echo count($orders); ?> of <?php echo number_format($total_count); ?> matching orders</small>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark border">Page <?php echo $pageNum; ?> of <?php echo max(1, $totalPages); ?></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover order-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 45px;" class="text-center">#</th>
                                            <th>Order # & Tracking</th>
                                            <th>Customer Details</th>
                                            <th>Items Preview</th>
                                            <th>Total Amount</th>
                                            <th>Payment</th>
                                            <th>Order Status</th>
                                            <th>Logistics / AWB</th>
                                            <th class="text-end" style="width: 130px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr class="no-orders-row">
                                                <td colspan="9" class="text-center py-5">
                                                    <div class="text-muted">
                                                        <i class="fas fa-box-open fa-3x mb-3 text-light-emphasis"></i>
                                                        <h5 class="fw-semibold">No Orders Found</h5>
                                                        <p class="font-13 mb-0">Try clearing or modifying the search filters above.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $sno = ($pageNum - 1) * $limit + 1;
                                            foreach ($orders as $order): 
                                                $isCod = (stripos($order['payment_method'] ?? '', 'cod') !== false);
                                                $previewItems = $order['preview_items'] ?? [];
                                                $rawImg = !empty($previewItems[0]['product_image']) ? $previewItems[0]['product_image'] : '';
                                                $firstThumb = resolveBackendImageUrl($rawImg);
                                                $firstProdName = !empty($previewItems[0]['product_name']) ? $previewItems[0]['product_name'] : 'Item';
                                            ?>
                                                <tr>
                                                    <td class="text-center text-muted fw-semibold"><?php echo $sno++; ?></td>
                                                    
                                                    <!-- Order Number & Date -->
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <a href="view-order.php?id=<?php echo $order['id']; ?>" class="fw-bold text-primary font-14">
                                                                #<?php echo htmlspecialchars($order['order_number']); ?>
                                                            </a>
                                                        </div>
                                                        <div class="text-muted font-11 mt-1">
                                                            <i class="far fa-calendar-alt me-1"></i><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                                                        </div>
                                                        <?php if (!empty($order['tracking_number'])): ?>
                                                            <div class="font-11 text-muted">
                                                                <span class="text-secondary fw-semibold">ID:</span> <?php echo htmlspecialchars($order['tracking_number']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Customer Details -->
                                                    <td>
                                                        <div class="fw-bold text-dark font-13">
                                                            <?php if (!empty($order['user_id'])): ?>
                                                                <a href="view-customer.php?id=<?php echo $order['user_id']; ?>" class="text-dark" title="View customer profile">
                                                                    <?php echo htmlspecialchars($order['customer_name']); ?> <i class="fas fa-external-link-alt fa-xs text-muted ms-1"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($order['customer_phone'])): ?>
                                                            <div class="font-12 text-muted">
                                                                <i class="fas fa-phone fa-xs me-1"></i><a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></a>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($order['customer_email'])): ?>
                                                            <div class="font-11 text-muted text-truncate" style="max-width: 180px;">
                                                                <i class="far fa-envelope fa-xs me-1"></i><?php echo htmlspecialchars($order['customer_email']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Items Preview -->
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php if (!empty($firstThumb)): ?>
                                                                <img src="<?php echo htmlspecialchars($firstThumb); ?>" class="item-thumb" alt="Product">
                                                            <?php else: ?>
                                                                <div class="item-thumb-placeholder"><i class="fas fa-box"></i></div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-semibold text-dark font-12 text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($firstProdName); ?>">
                                                                    <?php echo htmlspecialchars($firstProdName); ?>
                                                                </div>
                                                                <span class="badge bg-light text-muted border font-11">
                                                                    <?php echo $order['items_count']; ?> item<?php echo $order['items_count'] > 1 ? 's' : ''; ?>
                                                                    <?php if (($order['total_quantity'] ?? 0) > 0): ?>
                                                                        (<?php echo $order['total_quantity']; ?> units)
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <!-- Total Amount & Payment Mode -->
                                                    <td>
                                                        <div class="fw-bold text-dark font-14">
                                                            ₹<?php echo number_format($order['grand_total'] ?? $order['total_amount'], 2); ?>
                                                        </div>
                                                        <span class="method-pill <?php echo $isCod ? 'method-cod' : 'method-prepaid'; ?>">
                                                            <?php echo strtoupper($order['payment_method'] ?? 'COD'); ?>
                                                        </span>
                                                    </td>

                                                    <!-- Payment Status -->
                                                    <td>
                                                        <?php 
                                                            $payStatus = strtolower($order['payment_status'] ?? 'pending');
                                                            $payClass = in_array($payStatus, ['paid', 'pending', 'failed', 'refunded'], true) ? 'badge-pay-' . $payStatus : 'badge-pay-pending';
                                                        ?>
                                                        <span class="badge-pay <?php echo $payClass; ?>">
                                                            <i class="fas <?php echo $payStatus === 'paid' ? 'fa-check-circle' : 'fa-clock'; ?> me-1"></i>
                                                            <?php echo ucfirst($payStatus); ?>
                                                        </span>
                                                    </td>

                                                    <!-- Order Status -->
                                                    <td>
                                                        <?php 
                                                            $st = strtolower($order['status'] ?? 'pending');
                                                            $statusClass = 'badge-' . $st;
                                                        ?>
                                                        <span class="badge-status <?php echo $statusClass; ?>">
                                                            <?php echo ucfirst($st); ?>
                                                        </span>
                                                    </td>

                                                    <!-- Logistics & Courier AWB -->
                                                    <td>
                                                        <?php if (!empty($order['courier_tracking_number'])): ?>
                                                            <div class="font-12 fw-semibold text-dark">
                                                                <?php echo htmlspecialchars($order['courier_name'] ?: 'Courier'); ?>
                                                            </div>
                                                            <div class="font-11 text-muted">
                                                                AWB: <strong><?php echo htmlspecialchars($order['courier_tracking_number']); ?></strong>
                                                            </div>
                                                            <?php if (!empty($order['tracking_url'])): ?>
                                                                <a href="<?php echo htmlspecialchars($order['tracking_url']); ?>" target="_blank" class="font-11 text-primary text-decoration-none">
                                                                    <i class="fas fa-arrow-up-right-from-square fa-xs"></i> Track Live
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted font-11"><i class="fas fa-boxes-packing text-muted me-1"></i> Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Actions -->
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="view-order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-primary btn-action-icon" title="View Order Details" aria-label="View order details">
                                                               <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="print-invoice.php?id=<?php echo $order['id']; ?>" 
                                                               target="_blank"
                                                               class="btn btn-outline-success btn-action-icon" title="Print Tax Invoice" aria-label="Print tax invoice">
                                                               <i class="fas fa-file-invoice"></i>
                                                            </a>
                                                            <a href="edit-order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-warning btn-action-icon" title="Edit Order" aria-label="Edit order">
                                                               <i class="fas fa-pen-to-square"></i>
                                                            </a>
                                                            <a href="delete-order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-danger btn-action-icon" 
                                                               aria-label="Delete order"
                                                               onclick="return confirm('Are you sure you want to permanently delete order #<?php echo $order['order_number']; ?>?');"
                                                               title="Delete Order">
                                                               <i class="fas fa-trash-can"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination & Records Counter Footer -->
                            <?php 
                                $fromRecord = $total_count > 0 ? (($pageNum - 1) * $limit + 1) : 0;
                                $toRecord = min($total_count, $pageNum * $limit);
                            ?>
                            <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-muted font-12">
                                        Showing <strong><?php echo $fromRecord; ?></strong> to <strong><?php echo $toRecord; ?></strong> of <strong><?php echo number_format($total_count); ?></strong> orders
                                        <?php if ($totalPages > 1): ?>
                                            (Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong>)
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 font-12 text-muted">
                                        <label for="limitSelect">Per page:</label>
                                        <select id="limitSelect" class="form-select form-select-sm py-0 ps-2 pe-4" style="width: auto; height: 28px; font-size: 12px;" onchange="location.href='?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>&limit=' + this.value">
                                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                        </select>
                                    </div>
                                </div>

                                <nav aria-label="Order pagination">
                                    <ul class="pagination pagination-sm mb-0">
                                        <!-- First & Prev -->
                                        <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" title="First Page">
                                                <i class="fas fa-angles-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $pageNum - 1)])); ?>" title="Previous Page">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <!-- Page Numbers -->
                                        <?php
                                            $startPage = max(1, $pageNum - 2);
                                            $endPage = min($totalPages, $pageNum + 2);
                                            if ($startPage > 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            for ($i = $startPage; $i <= $endPage; $i++): 
                                        ?>
                                            <li class="page-item <?php echo $i == $pageNum ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php 
                                            endfor; 
                                            if ($endPage < $totalPages) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        ?>
                                        
                                        <!-- Next & Last -->
                                        <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($totalPages, $pageNum + 1)])); ?>" title="Next Page">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" title="Last Page">
                                                <i class="fas fa-angles-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
