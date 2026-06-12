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

if (!$order) {
    $_SESSION['error_message'] = "Order not found!";
    header("Location: orders.php");
    exit();
}

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'customer_name' => trim($_POST['customer_name']),
            'customer_email' => trim($_POST['customer_email']),
            'customer_phone' => trim($_POST['customer_phone']),
            'shipping_address' => trim($_POST['shipping_address']),
            'billing_address' => trim($_POST['billing_address']),
            'shipping_method' => trim($_POST['shipping_method']),
            'payment_method' => trim($_POST['payment_method']),
            'notes' => trim($_POST['notes']),
            'tax_amount' => (float)$_POST['tax_amount'],
            'shipping_cost' => (float)$_POST['shipping_cost'],
            'discount_amount' => (float)$_POST['discount_amount'],
            'status' => $_POST['status'],
            'payment_status' => $_POST['payment_status']
        ];

        // Recalculate grand total
        $subtotal = $order['total_amount'];
        $grand_total = $subtotal + $data['tax_amount'] + $data['shipping_cost'] - $data['discount_amount'];
        $data['grand_total'] = $grand_total;

        if ($orderController->updateOrder($orderId, $data)) {
            $_SESSION['success_message'] = "Order updated successfully!";
            header("Location: view-order.php?id=" . $orderId);
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to update order.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Refresh order data
    $order = $orderController->getOrderById($orderId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Order #<?php echo $order['order_number']; ?> | Printmont</title>
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
        .calculation-row { border-bottom: 1px solid #dee2e6; padding: 8px 0; }
        .calculation-total { border-top: 2px solid #007bff; font-weight: bold; }
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
                                            <h2 class="card-title text-white mb-1">Edit Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
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
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <form method="POST" id="orderForm">
                        <div class="row">
                            <!-- Order Details -->
                            <div class="col-lg-8">
                                <!-- Customer Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Customer Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Customer Name *</label>
                                                    <input type="text" class="form-control" name="customer_name" 
                                                           value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Customer Email</label>
                                                    <input type="email" class="form-control" name="customer_email" 
                                                           value="<?php echo htmlspecialchars($order['customer_email']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Customer Phone</label>
                                                    <input type="text" class="form-control" name="customer_phone" 
                                                           value="<?php echo htmlspecialchars($order['customer_phone']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shipping & Billing -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Address Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Shipping Address *</label>
                                                    <textarea class="form-control" name="shipping_address" rows="4" required><?php echo htmlspecialchars($order['shipping_address']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Billing Address</label>
                                                    <textarea class="form-control" name="billing_address" rows="4"><?php echo htmlspecialchars($order['billing_address']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items (Read-only) -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Order Items</h5>
                                        <p class="text-muted mb-0 small">Items cannot be edited here. To modify items, cancel and recreate the order.</p>
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
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <!-- Order Summary -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Order Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="calculation-row">
                                            <div class="d-flex justify-content-between">
                                                <span>Subtotal:</span>
                                                <span>&#8377; <?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="calculation-row">
                                            <div class="mb-3">
                                                <label class="form-label">Tax Amount</label>
                                                <input type="number" class="form-control" name="tax_amount" 
                                                       value="<?php echo number_format($order['tax_amount'] ?? 0, 2); ?>" 
                                                       step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="calculation-row">
                                            <div class="mb-3">
                                                <label class="form-label">Shipping Cost</label>
                                                <input type="number" class="form-control" name="shipping_cost" 
                                                       value="<?php echo number_format($order['shipping_cost'] ?? 0, 2); ?>" 
                                                       step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="calculation-row">
                                            <div class="mb-3">
                                                <label class="form-label">Discount Amount</label>
                                                <input type="number" class="form-control" name="discount_amount" 
                                                       value="<?php echo number_format($order['discount_amount'] ?? 0, 2); ?>" 
                                                       step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="calculation-row calculation-total pt-2">
                                            <div class="d-flex justify-content-between">
                                                <strong>Grand Total:</strong>
                                                <strong id="grandTotal">&#8377; <?php echo number_format($order['grand_total'] ?? $order['total_amount'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Order Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Order Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Payment Status</label>
                                            <select name="payment_status" class="form-control" required>
                                                <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Shipping Method</label>
                                            <input type="text" class="form-control" name="shipping_method" 
                                                   value="<?php echo htmlspecialchars($order['shipping_method'] ?? ''); ?>"
                                                   placeholder="e.g., Standard Shipping, Express">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <input type="text" class="form-control" name="payment_method" 
                                                   value="<?php echo htmlspecialchars($order['payment_method'] ?? ''); ?>"
                                                   placeholder="e.g., Credit Card, PayPal, Cash">
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Notes -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Order Notes</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <textarea class="form-control" name="notes" rows="4" 
                                                      placeholder="Any special instructions or notes..."><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">Update Order</button>
                                            <a href="view-order.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
                                            <a href="orders.php" class="btn btn-outline-secondary">Back to Orders</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Calculate grand total dynamically
        function calculateGrandTotal() {
            const subtotal = <?php echo $order['total_amount']; ?>;
            const tax = parseFloat(document.querySelector('input[name="tax_amount"]').value) || 0;
            const shipping = parseFloat(document.querySelector('input[name="shipping_cost"]').value) || 0;
            const discount = parseFloat(document.querySelector('input[name="discount_amount"]').value) || 0;
            
            const grandTotal = subtotal + tax + shipping - discount;
            document.getElementById('grandTotal').textContent = '$' + grandTotal.toFixed(2);
        }

        // Add event listeners to calculation inputs
        document.querySelectorAll('input[name="tax_amount"], input[name="shipping_cost"], input[name="discount_amount"]').forEach(input => {
            input.addEventListener('input', calculateGrandTotal);
        });

        // Form validation
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const customerName = document.querySelector('input[name="customer_name"]').value.trim();
            const shippingAddress = document.querySelector('textarea[name="shipping_address"]').value.trim();
            
            if (!customerName) {
                e.preventDefault();
                alert('Please enter customer name');
                document.querySelector('input[name="customer_name"]').focus();
                return;
            }
            
            if (!shippingAddress) {
                e.preventDefault();
                alert('Please enter shipping address');
                document.querySelector('textarea[name="shipping_address"]').focus();
                return;
            }
        });
    </script>
</body>
</html>