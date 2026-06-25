<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/OrderController.php';

$orderController = new OrderController();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$orders_data = $orderController->getAllOrders($page, $limit, $filters);
$orders = $orders_data['orders'];
$total_pages = $orders_data['total_pages'];
$current_page = $orders_data['current_page'];

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get order statistics
$stats = $orderController->getOrderStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Orders | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .order-table th { font-weight: 600; background-color: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce7ff; color: #004085; }
        .status-shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .payment-paid { background-color: #d4edda; color: #155724; }
        .payment-pending { background-color: #fff3cd; color: #856404; }
        .payment-failed { background-color: #f8d7da; color: #721c24; }
        .stat-card { border-left: 4px solid #007bff; }
        .order-image { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .filter-card { background-color: #f8f9fa; }
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
                            <h3><strong>Order</strong> Management</h3>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Total Orders</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-primary">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['total_orders']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Total Revenue</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-success">
                                                <i class="fas fa-rupee-sign"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3">&#8377; <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Completed</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-info">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['completed_orders']); ?></h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col mt-0">
                                            <h5 class="card-title">Paid Orders</h5>
                                        </div>
                                        <div class="col-auto">
                                            <div class="stat text-warning">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h1 class="mt-1 mb-3"><?php echo number_format($stats['paid_orders']); ?></h1>
                                </div>
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

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $filters['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $filters['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $filters['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $filters['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Payment</label>
                                    <select name="payment_status" class="form-control">
                                        <option value="">All Payments</option>
                                        <option value="paid" <?php echo $filters['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="pending" <?php echo $filters['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $filters['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
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
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Order #, Customer, Email" value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Orders</h5>
                                    <h6 class="card-subtitle text-muted">Manage customer orders and track their status.</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover order-table">
                                            <thead>
                                                <tr>
                                                    <th>S No.</th>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($orders)): ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="text-muted">No orders found.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php
                                                    $sno = 1;
                                                         foreach ($orders as $order): ?>
                                                        <tr>
                                                            <td><?=$sno++?></td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                                    <?php if (!empty($order['customer_email'])): ?>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
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
                                                            <td>
                                                                <strong>&#8377; <?php echo number_format($order['grand_total'] ?? $order['total_amount'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                                    <?php echo ucfirst($order['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="view-order.php?id=<?php echo $order['id']; ?>" 
                                                                   class="btn btn-sm btn-primary" title="View">
                                                                   <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit-order.php?id=<?php echo $order['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-order.php?id=<?php echo $order['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this order?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
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