<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CustomerController.php';

$customerController = new CustomerController();
$customerId = $_GET['id'] ?? 0;
$customer = $customerController->getCustomerById($customerId);

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found!";
    header("Location: customers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Customer | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .customer-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .info-card { border-left: 4px solid #007bff; }
        .stats-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Customer Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card customer-header">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h2 class="card-title text-white mb-1"><?php echo htmlspecialchars($customer['username']); ?></h2>
                                            <p class="card-text text-white-50 mb-0">
                                                <?php echo htmlspecialchars($customer['email']); ?>
                                                <?php if ($customer['company_name']): ?>
                                                    • <?php echo htmlspecialchars($customer['company_name']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge bg-<?php 
                                                echo $customer['status'] == 'active' ? 'success' : 
                                                       ($customer['status'] == 'suspended' ? 'warning' : 'secondary'); 
                                            ?> fs-6">
                                                <?php echo ucfirst($customer['status']); ?>
                                            </span>
                                            <div class="mt-2">
                                                <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-light btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="customers.php" class="btn btn-outline-light btn-sm">
                                                    <i class="fas fa-arrow-left"></i> Back
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-lg-4">
                            <div class="card info-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Customer Type:</strong>
                                        <span class="badge bg-<?php echo $customer['customer_type'] == 'business' ? 'primary' : 'info'; ?>">
                                            <?php echo ucfirst($customer['customer_type']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($customer['phone']): ?>
                                    <div class="mb-3">
                                        <strong>Phone:</strong><br>
                                        <i class="fas fa-phone text-muted me-2"></i>
                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($customer['address']): ?>
                                    <div class="mb-3">
                                        <strong>Address:</strong><br>
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <strong>Location:</strong><br>
                                        <?php 
                                        $location = [];
                                        if ($customer['city']) $location[] = $customer['city'];
                                        if ($customer['state']) $location[] = $customer['state'];
                                        if ($customer['country']) $location[] = $customer['country'];
                                        echo $location ? htmlspecialchars(implode(', ', $location)) : 'No location specified';
                                        ?>
                                    </div>

                                    <?php if ($customer['postal_code']): ?>
                                    <div class="mb-3">
                                        <strong>Postal Code:</strong><br>
                                        <?php echo htmlspecialchars($customer['postal_code']); ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($customer['notes']): ?>
                                    <div class="mb-3">
                                        <strong>Notes:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($customer['notes'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics & Activity -->
                        <div class="col-lg-8">
                            <!-- Statistics Cards -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stats-card">
                                        <div class="text-center">
                                            <div class="h3 text-primary"><?php echo $customer['total_orders']; ?></div>
                                            <div class="text-muted">Total Orders</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stats-card">
                                        <div class="text-center">
                                            <div class="h3 text-success">₹<?php echo number_format($customer['total_spent'], 2); ?></div>
                                            <div class="text-muted">Total Spent</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Info -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Account Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Registration Date:</strong><br>
                                            <?php echo date('F j, Y g:i A', strtotime($customer['registration_date'])); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Last Updated:</strong><br>
                                            <?php echo date('F j, Y g:i A', strtotime($customer['updated_at'])); ?>
                                        </div>
                                    </div>
                                    <?php if ($customer['last_login']): ?>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <strong>Last Login:</strong><br>
                                            <?php echo date('F j, Y g:i A', strtotime($customer['last_login'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Orders (Placeholder) -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Recent Orders</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Order history would be displayed here.</p>
                                    <!-- You would integrate with your existing orders system -->
                                    <a href="#" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-shopping-cart"></i> View All Orders
                                    </a>
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