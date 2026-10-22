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
    <title>Sales & Orders Management | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
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
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        .badge-refunded { background-color: #f1f5f9; color: #475569; }

        .badge-pay {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .badge-pay-paid { background: #dcfce7; color: #15803d; }
        .badge-pay-pending { background: #fef3c7; color: #b45309; }
        .badge-pay-failed { background: #fee2e2; color: #b91c1c; }

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
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <h3 class="fw-bold mb-1 text-dark">Sales & Orders Management</h3>
                            <p class="text-muted mb-0 font-13">Monitor, process, fulfill, and track all customer orders and shipments in real-time.</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
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
                        <div class="col-xl-3 col-sm-6">
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
                        <div class="col-xl-3 col-sm-6">
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
                        <div class="col-xl-3 col-sm-6">
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
                        <div class="col-xl-3 col-sm-6">
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
                            $q['page'] = 1;
                            return '?' . http_build_query($q);
                        }
                    ?>
                    <ul class="nav nav-tabs nav-tabs-custom mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo empty($activeStatus) ? 'active' : ''; ?>" href="<?php echo tabUrl(''); ?>">
                                All Orders <span class="badge bg-secondary"><?php echo $stats['total_orders'] ?? 0; ?></span>
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
                            <a class="nav-link <?php echo in_array($activeStatus, ['cancelled', 'refunded']) ? 'active' : ''; ?>" href="<?php echo tabUrl('cancelled'); ?>">
                                <i class="fas fa-ban fa-xs text-danger"></i> Cancelled / Return <span class="badge bg-danger"><?php echo ($stats['cancelled_orders'] ?? 0) + ($stats['refunded_orders'] ?? 0); ?></span>
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

                                <div class="col-md-1 d-flex gap-1">
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
                    <div class="card shadow-sm border-0">
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
                                            <tr>
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
                                                            $payClass = ($payStatus === 'paid') ? 'badge-pay-paid' : (($payStatus === 'failed') ? 'badge-pay-failed' : 'badge-pay-pending');
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
                                                               class="btn btn-outline-primary btn-action-icon" title="View Order Details">
                                                               <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="print-invoice.php?id=<?php echo $order['id']; ?>" 
                                                               target="_blank"
                                                               class="btn btn-outline-success btn-action-icon" title="Print Tax Invoice">
                                                               <i class="fas fa-file-invoice"></i>
                                                            </a>
                                                            <a href="edit-order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-warning btn-action-icon" title="Edit Order">
                                                               <i class="fas fa-pen-to-square"></i>
                                                            </a>
                                                            <a href="delete-order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-danger btn-action-icon" 
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