<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/OrderController.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid order ID!";
    header("Location: orders.php");
    exit();
}

$orderId = (int)$_GET['id'];
$orderController = new OrderController();
$order = $orderController->getOrderById($orderId);

if (!$order) {
    $_SESSION['error_message'] = "Order not found!";
    header("Location: orders.php");
    exit();
}

$orderItems = $orderController->getOrderItems($orderId);
$statusHistory = $orderController->getStatusHistory($orderId);

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $notes = trim($_POST['status_notes'] ?? '');
    
    if ($orderController->updateOrderStatus($orderId, $status, $notes, $_SESSION['user_id'] ?? null)) {
        $_SESSION['success_message'] = "Order status updated to " . ucfirst($status) . " successfully!";
        header("Location: view-order.php?id=" . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update order status.";
    }
}

// Handle payment status update
if ($_POST && isset($_POST['update_payment_status'])) {
    $payment_status = $_POST['payment_status'];
    
    if ($orderController->updatePaymentStatus($orderId, $payment_status)) {
        $_SESSION['success_message'] = "Payment status updated to " . ucfirst($payment_status) . " successfully!";
        header("Location: view-order.php?id=" . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update payment status.";
    }
}

// Handle Reverse / Refund to Customer Wallet
if ($_POST && isset($_POST['reverse_to_wallet'])) {
    $revAmount = isset($_POST['reversal_amount']) && floatval($_POST['reversal_amount']) > 0 ? floatval($_POST['reversal_amount']) : (float)$order['grand_total'];
    $revReason = trim($_POST['reversal_reason'] ?? 'Admin refund reversal on order cancellation');

    require_once 'services/WalletService.php';
    $ws = new WalletService($database->getConnection());
    $res = $ws->reverseOrderToWallet($orderId, $revAmount, $revReason, $_SESSION['user_id'] ?? null);

    if ($res['success']) {
        $newStat = ($_POST['reversal_order_status'] ?? 'refunded') === 'cancelled' ? 'cancelled' : 'refunded';
        $orderController->updateOrderStatus($orderId, $newStat, 'Payment of ₹' . number_format($revAmount, 2) . ' reversed to customer wallet. Reason: ' . $revReason, $_SESSION['user_id'] ?? null);
        $_SESSION['success_message'] = "₹" . number_format($revAmount, 2) . " reversed to customer wallet successfully!";
        header("Location: view-order.php?id=" . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = "Wallet reversal failed: " . ($res['error'] ?? 'Unknown error');
    }
}

// Handle courier and tracking update
if ($_POST && isset($_POST['update_tracking'])) {
    $courier_name = trim($_POST['courier_name'] ?? '');
    $courier_tracking_number = trim($_POST['courier_tracking_number'] ?? '');
    $tracking_url = trim($_POST['tracking_url'] ?? '');
    $auto_ship = !empty($_POST['mark_as_shipped']);

    // Generate smart courier tracking URL if not provided
    if (empty($tracking_url) && !empty($courier_tracking_number)) {
        $clow = strtolower($courier_name);
        if (strpos($clow, 'delhivery') !== false) {
            $tracking_url = "https://www.delhivery.com/track/package/" . urlencode($courier_tracking_number);
        } elseif (strpos($clow, 'bluedart') !== false) {
            $tracking_url = "https://www.bluedart.com/tracking";
        } elseif (strpos($clow, 'shiprocket') !== false) {
            $tracking_url = "https://shiprocket.co/tracking/" . urlencode($courier_tracking_number);
        } elseif (strpos($clow, 'dtdc') !== false) {
            $tracking_url = "https://www.dtdc.in/tracking.asp";
        }
    }

    if ($orderController->updateCourierTracking($orderId, $courier_name, $courier_tracking_number, $tracking_url, $auto_ship, $_SESSION['user_id'] ?? null)) {
        $_SESSION['success_message'] = "Courier and tracking details updated successfully!";
        header("Location: view-order.php?id=" . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update courier tracking details.";
    }
}

// Fetch customer lifetime stats if user_id or customer_id is available
$customerLifetimeStats = null;
$customerWallet = null;
$customerUserId = $order['user_id'] ?? null;
if ($customerUserId) {
    require_once 'controllers/CustomerController.php';
    $custCtrl = new CustomerController();
    $customerLifetimeStats = $custCtrl->getCustomerFullStats(0, $customerUserId, $order['customer_email'], $order['customer_phone']);

    require_once 'services/WalletService.php';
    $ws = new WalletService($database->getConnection());
    $customerWallet = $ws->getWallet($customerUserId);
}

// Stepper steps calculation
$statusOrder = ['pending' => 1, 'confirmed' => 2, 'processing' => 3, 'shipped' => 4, 'delivered' => 5];
$currStatus = strtolower($order['status'] ?? 'pending');
$currStep = $statusOrder[$currStatus] ?? 1;
$isCancelled = in_array($currStatus, ['cancelled', 'refunded', 'declined']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Order #<?php echo htmlspecialchars($order['order_number']); ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .order-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 12px;
            color: #ffffff;
            padding: 24px 28px;
            margin-bottom: 24px;
        }

        .stepper-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .order-stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        .order-stepper::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }
        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        .step-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #cbd5e1;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .step-item.completed .step-icon {
            background: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
        }
        .step-item.active .step-icon {
            background: #0b53a1;
            border-color: #0b53a1;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(11, 83, 161, 0.2);
        }
        .step-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
        }
        .step-item.active .step-title {
            color: #0b53a1;
            font-weight: 700;
        }

        .product-card-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .attr-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 4px;
            margin-top: 4px;
            border: 1px solid #e2e8f0;
        }

        .timeline-modern {
            position: relative;
            padding-left: 24px;
        }
        .timeline-modern::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-point {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-point::before {
            content: '';
            position: absolute;
            left: -21px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #0b53a1;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px #0b53a1;
        }

        .sidebar-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }
        .sidebar-card .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 18px;
            font-weight: 700;
            font-size: 13px;
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
                    
                    <!-- Flash Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Order Hero Card -->
                    <div class="order-hero">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <h2 class="text-white fw-bold mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                                    <span class="badge bg-primary text-uppercase px-2 py-1 font-11">
                                        <?php echo strtoupper($order['payment_method'] ?? 'COD'); ?>
                                    </span>
                                </div>
                                <p class="text-white-50 font-13 mb-0">
                                    <i class="far fa-calendar-alt me-1"></i> Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        &nbsp;•&nbsp; <i class="fas fa-hashtag me-1"></i> Tracking Ref: <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                    <a href="print-invoice.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-light fw-semibold btn-sm">
                                        <i class="fas fa-file-invoice text-primary me-1"></i> Tax Invoice
                                    </a>
                                    <a href="print-packing-slip.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-outline-light fw-semibold btn-sm">
                                        <i class="fas fa-box-open me-1"></i> Packing Slip
                                    </a>
                                    <a href="edit-order.php?id=<?php echo $orderId; ?>" class="btn btn-outline-light fw-semibold btn-sm">
                                        <i class="fas fa-pen-to-square me-1"></i> Edit Order
                                    </a>
                                    <a href="orders.php" class="btn btn-secondary fw-semibold btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i> All Orders
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Stepper (if not cancelled) -->
                    <?php if (!$isCancelled): ?>
                    <div class="stepper-container shadow-sm">
                        <div class="order-stepper">
                            <div class="step-item <?php echo $currStep >= 1 ? ($currStep > 1 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $currStep > 1 ? 'fa-check' : 'fa-receipt'; ?>"></i>
                                </div>
                                <div class="step-title">Order Placed</div>
                            </div>
                            <div class="step-item <?php echo $currStep >= 2 ? ($currStep > 2 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $currStep > 2 ? 'fa-check' : 'fa-clipboard-check'; ?>"></i>
                                </div>
                                <div class="step-title">Confirmed</div>
                            </div>
                            <div class="step-item <?php echo $currStep >= 3 ? ($currStep > 3 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $currStep > 3 ? 'fa-check' : 'fa-gears'; ?>"></i>
                                </div>
                                <div class="step-title">In Production</div>
                            </div>
                            <div class="step-item <?php echo $currStep >= 4 ? ($currStep > 4 ? 'completed' : 'active') : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $currStep > 4 ? 'fa-check' : 'fa-truck-fast'; ?>"></i>
                                </div>
                                <div class="step-title">Shipped</div>
                            </div>
                            <div class="step-item <?php echo $currStep >= 5 ? 'completed active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fas fa-house-chimney-check"></i>
                                </div>
                                <div class="step-title">Delivered</div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4">
                        <i class="fas fa-ban fa-2x me-3"></i>
                        <div>
                            <h5 class="fw-bold mb-0">Order <?php echo ucfirst($currStatus); ?></h5>
                            <span class="font-13">This order has been marked as <?php echo $currStatus; ?>. No further automated dispatch actions are pending.</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Left Column: Items, Financials & Audit Trail -->
                        <div class="col-lg-8">
                            
                            <!-- Order Line Items Card -->
                            <div class="card sidebar-card mb-4 shadow-sm">
                                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-bag-shopping text-primary me-2"></i>Ordered Products (<?php echo count($orderItems); ?> Line Items)
                                    </h5>
                                    <span class="badge bg-light text-dark border">
                                        Total Units: <?php echo array_sum(array_column($orderItems, 'quantity')); ?>
                                    </span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50%;">Product Details & Customization</th>
                                                    <th class="text-center">Rate</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Total Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orderItems as $item): 
                                                    $parsedAttrs = [];
                                                    if (!empty($item['attributes'])) {
                                                        $decoded = json_decode($item['attributes'], true);
                                                        if (is_array($decoded)) {
                                                            foreach ($decoded as $k => $v) {
                                                                if (!empty($v) && !is_array($v)) $parsedAttrs[ucfirst($k)] = $v;
                                                            }
                                                        }
                                                    }
                                                    $rawImg = !empty($item['product_image']) ? $item['product_image'] : (!empty($item['image']) ? $item['image'] : '');
                                                    $itemImg = resolveBackendImageUrl($rawImg);
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-start gap-3">
                                                                <?php if (!empty($itemImg)): ?>
                                                                    <img src="<?php echo htmlspecialchars($itemImg); ?>" class="product-card-img" alt="Product">
                                                                <?php else: ?>
                                                                    <div class="product-card-img bg-light d-flex align-items-center justify-content-center">
                                                                        <i class="fas fa-box text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <h6 class="fw-bold text-dark mb-1 font-14">
                                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                                    </h6>
                                                                    <div class="font-12 text-muted mb-1">
                                                                        SKU: <span class="text-dark fw-semibold font-monospace"><?php echo htmlspecialchars($item['product_sku'] ?: ($item['sku'] ?? 'N/A')); ?></span>
                                                                        <?php if (!empty($item['variant'])): ?>
                                                                            &nbsp;|&nbsp; Variant: <span class="text-dark fw-semibold"><?php echo htmlspecialchars($item['variant']); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <!-- Customization Specs -->
                                                                    <?php if (!empty($parsedAttrs)): ?>
                                                                        <div class="mt-1">
                                                                            <span class="text-muted font-11 fw-bold">CUSTOMIZATION:</span>
                                                                            <div>
                                                                                <?php foreach ($parsedAttrs as $k => $val): ?>
                                                                                    <span class="attr-pill">
                                                                                        <strong><?php echo htmlspecialchars($k); ?>:</strong> <?php echo htmlspecialchars($val); ?>
                                                                                    </span>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center font-13 align-middle">
                                                            ₹<?php echo number_format((float)$item['unit_price'], 2); ?>
                                                        </td>
                                                        <td class="text-center align-middle font-14 fw-bold">
                                                            <?php echo (int)$item['quantity']; ?>
                                                        </td>
                                                        <td class="text-end align-middle font-14 fw-bold text-dark">
                                                            ₹<?php echo number_format((float)$item['total_price'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Price Calculation Table -->
                                    <div class="p-4 bg-light-subtle border-top">
                                        <div class="row justify-content-end">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2 font-13">
                                                    <span class="text-muted">Item Subtotal:</span>
                                                    <span class="fw-semibold">₹<?php echo number_format((float)($order['subtotal'] > 0 ? $order['subtotal'] : $order['total_amount']), 2); ?></span>
                                                </div>
                                                <?php if ((float)$order['discount_amount'] > 0): ?>
                                                <div class="d-flex justify-content-between mb-2 font-13 text-success">
                                                    <span>Coupon Discount (<?php echo htmlspecialchars($order['coupon_code'] ?: 'COUPON'); ?>):</span>
                                                    <span class="fw-bold">- ₹<?php echo number_format((float)$order['discount_amount'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-between mb-2 font-13">
                                                    <span class="text-muted">Shipping & Delivery:</span>
                                                    <span class="fw-semibold"><?php echo (float)$order['shipping_cost'] > 0 ? '₹' . number_format((float)$order['shipping_cost'], 2) : '<span class="badge bg-success-subtle text-success">FREE</span>'; ?></span>
                                                </div>
                                                <?php if ((float)$order['tax_amount'] > 0): ?>
                                                <div class="d-flex justify-content-between mb-2 font-13">
                                                    <span class="text-muted">GST / Taxes:</span>
                                                    <span class="fw-semibold">₹<?php echo number_format((float)$order['tax_amount'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between font-16 fw-bold text-dark">
                                                    <span>Grand Total:</span>
                                                    <span class="text-primary">₹<?php echo number_format((float)($order['grand_total'] ?? $order['total_amount']), 2); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between font-12 text-muted mt-1">
                                                    <span>Payment Status:</span>
                                                    <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning text-dark'; ?>">
                                                        <?php echo strtoupper($order['payment_status'] ?? 'PENDING'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status History & Audit Trail -->
                            <div class="card sidebar-card shadow-sm">
                                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-timeline text-primary me-2"></i>Order Lifecycle & Activity Log
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <?php if (empty($statusHistory)): ?>
                                        <p class="text-muted mb-0">No lifecycle history available.</p>
                                    <?php else: ?>
                                        <div class="timeline-modern">
                                            <?php foreach ($statusHistory as $h): ?>
                                                <div class="timeline-point">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="fw-bold text-dark mb-1">
                                                            Status changed to <span class="badge bg-light text-primary border font-12"><?php echo ucfirst($h['status']); ?></span>
                                                        </h6>
                                                        <span class="text-muted font-11">
                                                            <i class="far fa-clock me-1"></i><?php echo date('d M Y, h:i A', strtotime($h['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($h['notes'])): ?>
                                                        <p class="text-muted font-13 mb-0 mt-1"><?php echo htmlspecialchars($h['notes']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Customer Profile, Shipping, Courier Manager, Actions -->
                        <div class="col-lg-4">
                            
                            <!-- Customer Profile Card -->
                            <div class="card sidebar-card shadow-sm">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-user-circle text-primary me-2"></i>Customer 360</span>
                                    <?php if (!empty($order['user_id'])): ?>
                                        <a href="view-customer.php?id=<?php echo $order['user_id']; ?>" class="badge bg-primary-subtle text-primary text-decoration-none">
                                            View Profile <i class="fas fa-arrow-right fa-xs"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 16px;">
                                            <?php echo strtoupper(substr($order['customer_name'] ?? 'C', 0, 2)); ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></h6>
                                            <?php if (!empty($order['user_id'])): ?>
                                                <span class="badge bg-success-subtle text-success font-11"><i class="fas fa-check-circle me-1"></i>Registered User</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border font-11">Guest Buyer</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mb-2 font-13">
                                        <i class="far fa-envelope text-muted me-2"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" class="text-dark text-decoration-none">
                                            <?php echo htmlspecialchars($order['customer_email']); ?>
                                        </a>
                                    </div>
                                    <div class="mb-3 font-13">
                                        <i class="fas fa-phone text-muted me-2"></i>
                                        <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="text-dark text-decoration-none">
                                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                                        </a>
                                    </div>

                                    <!-- Direct WhatsApp button -->
                                    <?php 
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? '');
                                        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                                        $waMsg = urlencode("Hello " . $order['customer_name'] . ", regarding your Printmont Order #" . $order['order_number'] . "...");
                                    ?>
                                    <?php if (!empty($cleanPhone)): ?>
                                        <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=<?php echo $waMsg; ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 fw-semibold mb-3">
                                            <i class="fab fa-whatsapp me-1 font-14"></i> Chat on WhatsApp
                                        </a>
                                    <?php endif; ?>

                                    <!-- Customer Lifetime Summary -->
                                    <?php if (!empty($customerLifetimeStats)): ?>
                                        <div class="p-2 bg-light rounded font-12">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Total Lifetime Orders:</span>
                                                <strong class="text-dark"><?php echo $customerLifetimeStats['total_orders']; ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Total Spent (LTV):</span>
                                                <strong class="text-success">₹<?php echo number_format($customerLifetimeStats['total_spent'], 2); ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Shipping & Delivery Address Card -->
                            <div class="card sidebar-card shadow-sm">
                                <div class="card-header">
                                    <i class="fas fa-location-dot text-primary me-2"></i>Shipping & Delivery Address
                                </div>
                                <div class="card-body">
                                    <p class="font-13 text-dark mb-2 line-height-md">
                                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                    </p>
                                    <a href="https://maps.google.com/?q=<?php echo urlencode($order['shipping_address']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm font-11">
                                        <i class="fas fa-map-location-dot me-1"></i> Locate on Google Maps
                                    </a>
                                </div>
                            </div>

                            <!-- Courier & Logistics Manager -->
                            <div class="card sidebar-card shadow-sm">
                                <div class="card-header bg-white">
                                    <i class="fas fa-truck-ramp-box text-primary me-2"></i>Courier & AWB Tracking
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($order['courier_tracking_number'])): ?>
                                        <div class="alert alert-info py-2 px-3 mb-3 font-13">
                                            <strong>Carrier:</strong> <?php echo htmlspecialchars($order['courier_name'] ?: 'Express Courier'); ?><br>
                                            <strong>AWB Number:</strong> <?php echo htmlspecialchars($order['courier_tracking_number']); ?><br>
                                            <?php if (!empty($order['tracking_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($order['tracking_url']); ?>" target="_blank" class="fw-bold text-primary font-12 mt-1 d-inline-block">
                                                    <i class="fas fa-arrow-up-right-from-square"></i> Open Live Courier Tracking
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Update Tracking Form -->
                                    <form method="POST">
                                        <div class="mb-2">
                                            <label class="form-label font-12 fw-bold text-muted mb-1">Courier Partner</label>
                                            <input type="text" name="courier_name" list="courierList" class="form-control form-control-sm" 
                                                   placeholder="e.g. Delhivery, BlueDart, DTDC" 
                                                   value="<?php echo htmlspecialchars($order['courier_name'] ?? ''); ?>" required>
                                            <datalist id="courierList">
                                                <option value="Delhivery">
                                                <option value="BlueDart">
                                                <option value="Shiprocket">
                                                <option value="DTDC">
                                                <option value="Xpressbees">
                                                <option value="Shadowfax">
                                                <option value="India Post">
                                            </datalist>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label font-12 fw-bold text-muted mb-1">Courier AWB / Tracking #</label>
                                            <input type="text" name="courier_tracking_number" class="form-control form-control-sm" 
                                                   placeholder="Enter AWB number" 
                                                   value="<?php echo htmlspecialchars($order['courier_tracking_number'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label font-12 fw-bold text-muted mb-1">Tracking URL (Optional)</label>
                                            <input type="url" name="tracking_url" class="form-control form-control-sm" 
                                                   placeholder="https://..." 
                                                   value="<?php echo htmlspecialchars($order['tracking_url'] ?? ''); ?>">
                                        </div>
                                        <?php if ($order['status'] !== 'shipped' && $order['status'] !== 'delivered'): ?>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="mark_as_shipped" value="1" id="markShippedCheck" checked>
                                            <label class="form-check-label font-12 text-dark" for="markShippedCheck">
                                                Auto mark status as <strong>Shipped</strong>
                                            </label>
                                        </div>
                                        <?php endif; ?>
                                        <button type="submit" name="update_tracking" class="btn btn-primary btn-sm w-100 fw-semibold">
                                            <i class="fas fa-save me-1"></i> Save Tracking Details
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Order Status & Payment Actions -->
                            <div class="card sidebar-card shadow-sm">
                                <div class="card-header bg-white">
                                    <i class="fas fa-sliders text-primary me-2"></i>Status & Payment Controller
                                </div>
                                <div class="card-body">
                                    <!-- Status Update -->
                                    <form method="POST" class="mb-4">
                                        <label class="form-label font-12 fw-bold text-muted mb-1">Update Order Status</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <select name="status" class="form-select form-select-sm" required>
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing (In Production)</option>
                                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="refunded" <?php echo $order['status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>
                                        <textarea name="status_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Internal notes / update reason..."></textarea>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100 fw-semibold">
                                            <i class="fas fa-arrows-rotate me-1"></i> Update Order Status
                                        </button>
                                    </form>

                                    <!-- Payment Status -->
                                    <form method="POST" class="mb-3">
                                        <label class="form-label font-12 fw-bold text-muted mb-1">Update Payment Status</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <select name="payment_status" class="form-select form-select-sm" required>
                                                <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_payment_status" class="btn btn-warning btn-sm w-100 fw-semibold">
                                            <i class="fas fa-credit-card me-1"></i> Update Payment Status
                                        </button>
                                    </form>

                                    <!-- Reverse / Refund Payment to Customer Wallet -->
                                    <div class="border rounded p-3 bg-light border-success border-opacity-50">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label class="form-label font-12 fw-bold text-success mb-0">
                                                <i class="fas fa-rotate-left me-1"></i>Reverse to Wallet
                                            </label>
                                            <?php if ($customerWallet !== null): ?>
                                                <span class="badge bg-success font-11">
                                                    Bal: ₹<?php echo number_format($customerWallet['balance'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="font-11 text-muted mb-2">
                                            Instant cancellation reversal into customer's cash wallet.
                                        </p>
                                        <form method="POST">
                                            <div class="mb-2">
                                                <label class="font-11 text-muted fw-semibold">Reversal Amount (₹)</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" step="0.01" name="reversal_amount" class="form-control form-control-sm" value="<?php echo (float)($order['grand_total'] ?? $order['total_amount']); ?>" required min="1">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="font-11 text-muted fw-semibold">Mark Order As</label>
                                                <select name="reversal_order_status" class="form-select form-select-sm">
                                                    <option value="refunded" selected>Refunded</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <input type="text" name="reversal_reason" class="form-control form-control-sm" placeholder="Reason (e.g. Cancelled by customer)" value="Order cancellation refund reversed to wallet">
                                            </div>
                                            <button type="submit" name="reverse_to_wallet" class="btn btn-success btn-sm w-100 fw-bold" onclick="return confirm('Reverse payment directly to customer wallet?');">
                                                <i class="fas fa-wallet me-1"></i> Reverse to Customer Wallet
                                            </button>
                                        </form>
                                    </div>
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
</body>
</html>