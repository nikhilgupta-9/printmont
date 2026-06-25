<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CustomerController.php';

$customerController = new CustomerController();
$customers = $customerController->getAllCustomers();
$stats = $customerController->getCustomerStats();

// Handle search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $customers = $customerController->searchCustomers($search);
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    switch ($_GET['action']) {
        case 'delete':
            if ($customerController->deleteCustomer($id)) {
                $_SESSION['success_message'] = "Customer deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete customer.";
            }
            break;
            
        case 'suspend':
            if ($customerController->updateCustomerStatus($id, 'suspended')) {
                $_SESSION['success_message'] = "Customer suspended successfully!";
            }
            break;
            
        case 'activate':
            if ($customerController->updateCustomerStatus($id, 'active')) {
                $_SESSION['success_message'] = "Customer activated successfully!";
            }
            break;
            
        case 'deactivate':
            if ($customerController->updateCustomerStatus($id, 'inactive')) {
                $_SESSION['success_message'] = "Customer deactivated successfully!";
            }
            break;
    }
    
    header("Location: customers.php" . (!empty($search) ? "?search=$search" : ""));
    exit;
}

// Check for messages
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
    <title>Customers | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-suspended { background-color: #fff3cd; color: #856404; }
        .type-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .type-business { background-color: #e3f2fd; color: #1565c0; }
        .type-individual { background-color: #f3e5f5; color: #7b1fa2; }
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .stats-card { border-left: 4px solid; }
        .stats-card.total { border-color: #007bff; }
        .stats-card.active { border-color: #28a745; }
        .stats-card.revenue { border-color: #ffc107; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Customers</strong> List</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-customer.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Customer
                            </a>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card total">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_customers']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card active">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Customers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_customers']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                                Total Orders</div>
                                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_orders']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-white-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card revenue">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Revenue</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format(floatval($customer['total_spent'] ?? 0), 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
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

                    <!-- Search and Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by name, email, company, or phone...">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="customers.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-refresh"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Customers</h5>
                                    <h6 class="card-subtitle text-muted">Manage your customers and their information.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($customers)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No Customers Found</h5>
                                            <p class="text-muted"><?php echo !empty($search) ? 'No customers match your search criteria.' : 'Get started by adding your first customer.'; ?></p>
                                            <?php if (empty($search)): ?>
                                                <a href="add-customer.php" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Add Customer
                                                </a>
                                            <?php else: ?>
                                                <a href="customers.php" class="btn btn-secondary">
                                                    <i class="fas fa-refresh"></i> View All Customers
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Contact</th>
                                                        <th>Company</th>
                                                        <th>Type</th>
                                                        <th>Location</th>
                                                        <th>Orders</th>
                                                        <th>Spent</th>
                                                        <th>Status</th>
                                                        <th>Registered</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($customers as $customer): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($customer['phone']): ?>
                                                                    <i class="fas fa-phone text-muted me-1"></i>
                                                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No phone</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $customer['company_name'] ? htmlspecialchars($customer['company_name']) : '<span class="text-muted">Individual</span>'; ?>
                                                            </td>
                                                            <td>
                                                                <span class="type-badge type-<?php echo $customer['customer_type']; ?>">
                                                                    <?php echo ucfirst($customer['customer_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <?php 
                                                                    $location = [];
                                                                    if ($customer['city']) $location[] = $customer['city'];
                                                                    if ($customer['state']) $location[] = $customer['state'];
                                                                    echo $location ? htmlspecialchars(implode(', ', $location)) : '<span class="text-muted">No location</span>';
                                                                    ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?php echo $customer['total_orders']; ?></span>
                                                            </td>
                                                            <td>
                                                                <strong>₹<?php echo number_format($customer['total_spent'], 2); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo $customer['status']; ?>">
                                                                    <?php echo ucfirst($customer['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($customer['registration_date'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="view-customer.php?id=<?php echo $customer['id']; ?>" 
                                                                   class="btn btn-sm btn-info" title="View">
                                                                   <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <?php if ($customer['status'] == 'active'): ?>
                                                                    <a href="customers.php?action=suspend&id=<?php echo $customer['id']; ?>" 
                                                                       class="btn btn-sm btn-warning" 
                                                                       onclick="return confirm('Are you sure you want to suspend this customer?')"
                                                                       title="Suspend">
                                                                       <i class="fas fa-pause"></i>
                                                                    </a>
                                                                <?php elseif ($customer['status'] == 'suspended'): ?>
                                                                    <a href="customers.php?action=activate&id=<?php echo $customer['id']; ?>" 
                                                                       class="btn btn-sm btn-success" 
                                                                       onclick="return confirm('Are you sure you want to activate this customer?')"
                                                                       title="Activate">
                                                                       <i class="fas fa-play"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="customers.php?action=delete&id=<?php echo $customer['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this customer? This will also remove their user account.')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
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