<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/OrderController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid order ID!";
    header("Location: orders.php");
    exit();
}

$orderId = (int)$_GET['id'];
$orderController = new OrderController();
$order = $orderController->getOrderById($orderId);
$orderItems = $orderController->getOrderItems($orderId);
$statusHistory = $orderController->getStatusHistory($orderId);

if (!$order) {
    $_SESSION['error_message'] = "Order not found!";
    header("Location: orders.php");
    exit();
}

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $notes = $_POST['status_notes'] ?? '';
    
    if ($orderController->updateOrderStatus($orderId, $status, $notes, $_SESSION['user_id'] ?? null)) {
        $_SESSION['success_message'] = "Order status updated successfully!";
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
        $_SESSION['success_message'] = "Payment status updated successfully!";
        header("Location: view-order.php?id=" . $orderId);
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update payment status.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Order #<?php echo $order['order_number']; ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .order-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce7ff; color: #004085; }
        .status-shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .payment-paid { background-color: #d4edda; color: #155724; }
        .payment-pending { background-color: #fff3cd; color: #856404; }
        .payment-failed { background-color: #f8d7da; color: #721c24; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -23px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #007bff; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Order Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card order-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h2 class="card-title text-white mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                                            <p class="text-white-50 mb-0">
                                                Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <span class="status-badge status-<?php echo $order['status']; ?> me-2">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Order Details -->
                        <div class="col-lg-8">
                            <!-- Order Items -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Order Items</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                              <!-- In the order items display section -->
                                                <?php foreach ($orderItems as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['image'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                                        class="product-image me-3" 
                                                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                                <?php else: ?>
                                                                    <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                                        <i class="fas fa-box text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                                    <?php if (!empty($item['product_sku'])): ?>
                                                                        <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['variant'])): ?>
                                                                        <br><small class="text-muted">Variant: <?php echo htmlspecialchars($item['variant']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>&#8377; <?php echo number_format($item['unit_price'], 2); ?></td>
                                                        <td><?php echo $item['quantity']; ?></td>
                                                        <td><strong>&#8377; <?php echo number_format($item['total_price'], 2); ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                                    <td><strong>&#8377; <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                                </tr>
                                                <?php if ($order['tax_amount'] > 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                                    <td><strong>&#8377; <?php echo number_format($order['tax_amount'], 2); ?></strong></td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($order['shipping_cost'] > 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                                    <td><strong>&#8377; <?php echo number_format($order['shipping_cost'], 2); ?></strong></td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php if ($order['discount_amount'] > 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                                    <td><strong>&#8377; <?php echo number_format($order['discount_amount'], 2); ?></strong></td>
                                                </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                                    <td><strong>&#8377; <?php echo number_format($order['grand_total'] ?? $order['total_amount'], 2); ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Status History -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Status History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($statusHistory)): ?>
                                        <p class="text-muted">No status history available.</p>
                                    <?php else: ?>
                                        <div class="timeline">
                                            <?php foreach ($statusHistory as $history): ?>
                                                <div class="timeline-item">
                                                    <h6 class="mb-1">Status changed to <span class="status-badge status-<?php echo $history['status']; ?>"><?php echo ucfirst($history['status']); ?></span></h6>
                                                    <p class="text-muted mb-1">
                                                        <?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?>
                                                    </p>
                                                    <?php if (!empty($history['notes'])): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($history['notes']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Customer Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <h6><?php echo htmlspecialchars($order['customer_name']); ?></h6>
                                    <?php if (!empty($order['customer_email'])): ?>
                                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['customer_phone'])): ?>
                                        <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Shipping Address -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Shipping Address</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                            </div>

                            <!-- Billing Address -->
                            <?php if (!empty($order['billing_address'])): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Billing Address</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Order Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Order Actions</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Status Update Form -->
                                    <form method="POST" class="mb-3">
                                        <div class="mb-3">
                                            <label class="form-label">Update Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status Notes</label>
                                            <textarea name="status_notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                    </form>

                                    <!-- Payment Status Update -->
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Payment Status</label>
                                            <select name="payment_status" class="form-control" required>
                                                <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_payment_status" class="btn btn-warning w-100">Update Payment</button>
                                    </form>

                                    <hr>
                                    <div class="d-grid gap-2">
                                        <a href="edit-order.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary">Edit Order</a>
                                        <a href="delete-order.php?id=<?php echo $orderId; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this order?')">Delete Order</a>
                                        <a href="orders.php" class="btn btn-outline-secondary">Back to Orders</a>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>