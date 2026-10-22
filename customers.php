<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CustomerController.php';

$customerController = new CustomerController();
$customers = $customerController->getAllCustomers();
$stats = $customerController->getCustomerStats();

// Handle search and filters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');

if (!empty($search)) {
    $customers = $customerController->searchCustomers($search);
}

// Apply in-memory status & type filters if provided
if (!empty($statusFilter) || !empty($typeFilter)) {
    $customers = array_filter($customers, function ($c) use ($statusFilter, $typeFilter) {
        $statusMatch = empty($statusFilter) || ($c['status'] === $statusFilter);
        $typeMatch = empty($typeFilter) || ($c['customer_type'] === $typeFilter);
        return $statusMatch && $typeMatch;
    });
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=printmont_customers_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Full Name', 'Username', 'Email', 'Phone', 'Company', 'Customer Type', 'City', 'State', 'Country', 'Postal Code', 'Total Orders', 'Total Spent (INR)', 'Status', 'Verified', 'Registered Date']);
    
    foreach ($customers as $c) {
        $fullName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
        if (empty($fullName)) $fullName = $c['username'] ?? 'User';
        
        fputcsv($output, [
            $c['id'],
            $c['first_name'] ?? '',
            $c['last_name'] ?? '',
            $fullName,
            $c['username'] ?? '',
            $c['email'] ?? '',
            $c['phone'] ?? ($c['user_phone'] ?? ''),
            $c['company_name'] ?? '',
            ucfirst($c['customer_type'] ?? 'individual'),
            $c['city'] ?? '',
            $c['state'] ?? '',
            $c['country'] ?? 'India',
            $c['postal_code'] ?? '',
            $c['total_orders'] ?? 0,
            number_format(floatval($c['total_spent'] ?? 0), 2, '.', ''),
            ucfirst($c['status'] ?? 'active'),
            (!empty($c['is_verified']) && $c['is_verified'] == 1) ? 'Yes' : 'No',
            $c['registration_date'] ?? $c['created_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Handle actions (delete, suspend, activate, deactivate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
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
    
    $redirectUrl = "customers.php";
    $queryParts = [];
    if (!empty($search)) $queryParts[] = "search=" . urlencode($search);
    if (!empty($statusFilter)) $queryParts[] = "status=" . urlencode($statusFilter);
    if (!empty($typeFilter)) $queryParts[] = "type=" . urlencode($typeFilter);
    if (!empty($queryParts)) $redirectUrl .= "?" . implode("&", $queryParts);
    
    header("Location: " . $redirectUrl);
    exit;
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Helper function to generate initials
function getInitials($firstName, $lastName, $username, $email) {
    $first = trim($firstName ?? '');
    $last = trim($lastName ?? '');
    if (!empty($first) && !empty($last)) {
        return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    } elseif (!empty($first)) {
        return strtoupper(substr($first, 0, 2));
    } elseif (!empty($username)) {
        return strtoupper(substr($username, 0, 2));
    } elseif (!empty($email)) {
        return strtoupper(substr($email, 0, 2));
    }
    return 'CU';
}

// Avatar background colors generator
function getAvatarColor($name) {
    $colors = ['#0b53a1', '#2563eb', '#4f46e5', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Management | Printmont Admin</title>
    
    <!-- Google Fonts & Styles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    
    <style>
        :root {
            --pm-primary: #0b53a1;
            --pm-primary-dark: #073a72;
            --pm-primary-light: #e8f1fb;
            --pm-success: #10b981;
            --pm-warning: #f59e0b;
            --pm-danger: #ef4444;
            --pm-dark: #0f172a;
            --pm-slate: #475569;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f6f9;
            color: #1e293b;
        }

        /* Stats Cards */
        .pm-stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .pm-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .pm-stat-card .icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .icon-badge.blue { background-color: #e0f2fe; color: #0284c7; }
        .icon-badge.green { background-color: #dcfce7; color: #16a34a; }
        .icon-badge.purple { background-color: #f3e8ff; color: #9333ea; }
        .icon-badge.amber { background-color: #fef3c7; color: #d97706; }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin-top: 4px;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Card */
        .pm-filter-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }

        /* Table Card */
        .pm-table-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .pm-table-card .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 24px;
        }

        .table-responsive {
            margin-bottom: 0;
        }

        .pm-table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .pm-table thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            border-top: none;
            white-space: nowrap;
        }

        .pm-table tbody td {
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
            color: #334155;
        }

        .pm-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Customer Cell with Avatar */
        .customer-profile-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .customer-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .customer-avatar-initials {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .customer-name-wrap {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .customer-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .customer-name:hover {
            color: var(--pm-primary);
        }

        .customer-meta {
            font-size: 12.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        /* Badges */
        .badge-verified {
            background-color: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #bbf7d0;
        }

        .badge-unverified {
            background-color: #fef3c7;
            color: #b45309;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #fde68a;
        }

        .badge-status-active {
            background-color: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-status-inactive {
            background-color: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-status-suspended {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-type-business {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11.5px;
        }

        .badge-type-individual {
            background-color: #f3e8ff;
            color: #7e22ce;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11.5px;
        }

        /* Action Buttons */
        .btn-action-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: #475569;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease;
            font-size: 13px;
        }

        .btn-action-icon:hover {
            color: #ffffff;
            background-color: var(--pm-primary);
            border-color: var(--pm-primary);
        }

        .btn-action-icon.btn-action-delete:hover {
            background-color: #ef4444;
            border-color: #ef4444;
        }

        .btn-action-icon.btn-action-suspend:hover {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }

        .btn-action-icon.btn-action-activate:hover {
            background-color: #10b981;
            border-color: #10b981;
        }

        .copy-email-btn {
            background: none;
            border: none;
            padding: 0;
            color: #94a3b8;
            cursor: pointer;
            font-size: 11px;
            transition: color 0.15s ease;
        }

        .copy-email-btn:hover {
            color: var(--pm-primary);
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
                    
                    <!-- Page Header & Action Bar -->
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <h2 class="h3 fw-bold text-dark mb-0">Customers</h2>
                                <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold">
                                    <?php echo count($customers); ?> Total
                                </span>
                            </div>
                            <nav aria-label="breadcrumb" class="mt-1">
                                <ol class="breadcrumb mb-0 small text-muted">
                                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Customer Directory</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
                            <a href="customers.php?export=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                                <i class="fas fa-file-csv text-success"></i> Export CSV
                            </a>
                            <a href="add-customer.php" class="btn btn-primary d-inline-flex align-items-center gap-2" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                <i class="fas fa-user-plus"></i> Add New Customer
                            </a>
                        </div>
                    </div>

                    <!-- Alerts & Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                            <i class="fas fa-check-circle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                            <i class="fas fa-exclamation-circle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Overview Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-sm-6">
                            <div class="pm-stat-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-label">Total Customers</div>
                                        <div class="stat-value"><?php echo number_format($stats['total_customers'] ?? count($customers)); ?></div>
                                        <small class="text-muted"><i class="fas fa-arrow-up text-success me-1"></i>Registered Accounts</small>
                                    </div>
                                    <div class="icon-badge blue">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="pm-stat-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-label">Active & Verified</div>
                                        <div class="stat-value text-success"><?php echo number_format($stats['active_customers'] ?? 0); ?></div>
                                        <small class="text-muted"><i class="fas fa-shield-alt text-success me-1"></i>Verified Users</small>
                                    </div>
                                    <div class="icon-badge green">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="pm-stat-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-label">Total Orders Placed</div>
                                        <div class="stat-value text-primary"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
                                        <small class="text-muted"><i class="fas fa-box me-1"></i>Lifetime Orders</small>
                                    </div>
                                    <div class="icon-badge purple">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="pm-stat-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-label">Total Revenue (LTV)</div>
                                        <div class="stat-value text-warning">₹<?php echo number_format(floatval($stats['total_revenue'] ?? 0), 2); ?></div>
                                        <small class="text-muted"><i class="fas fa-chart-line text-warning me-1"></i>Customer Spending</small>
                                    </div>
                                    <div class="icon-badge amber">
                                        <i class="fas fa-rupee-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Advanced Filters -->
                    <div class="pm-filter-card">
                        <form method="GET" action="customers.php" class="row g-2 align-items-center">
                            <div class="col-lg-5 col-md-12">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 ps-0" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by customer name, email, phone, city...">
                                </div>
                            </div>

                            <div class="col-lg-2 col-sm-6">
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-sm-6">
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="individual" <?php echo $typeFilter === 'individual' ? 'selected' : ''; ?>>Individual</option>
                                    <option value="business" <?php echo $typeFilter === 'business' ? 'selected' : ''; ?>>Business</option>
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                    Filter
                                </button>
                                <?php if (!empty($search) || !empty($statusFilter) || !empty($typeFilter)): ?>
                                    <a href="customers.php" class="btn btn-outline-secondary" title="Reset Filters">
                                        <i class="fas fa-rotate-left"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Customers Master Table -->
                    <div class="pm-table-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title fw-bold mb-1">Customer Records</h5>
                                <h6 class="card-subtitle text-muted mb-0">Detailed list of registered customers, contacts and spending lifetime value.</h6>
                            </div>
                            <span class="text-muted small">
                                Showing <strong><?php echo count($customers); ?></strong> results
                            </span>
                        </div>

                        <div class="card-body p-0">
                            <?php if (empty($customers)): ?>
                                <div class="text-center py-5 px-3">
                                    <div class="mb-3 text-muted">
                                        <i class="fas fa-user-slash fa-4x opacity-50"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">No Customers Found</h4>
                                    <p class="text-muted mb-4">
                                        <?php echo !empty($search) ? 'No customer records matched your query: "<strong>' . htmlspecialchars($search) . '</strong>"' : 'No customer records exist yet in the database.'; ?>
                                    </p>
                                    <?php if (!empty($search) || !empty($statusFilter) || !empty($typeFilter)): ?>
                                        <a href="customers.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-rotate-left me-1"></i> Clear Filters
                                        </a>
                                    <?php else: ?>
                                        <a href="add-customer.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i> Add First Customer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table pm-table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Customer Profile</th>
                                                <th>Contact & Phone</th>
                                                <th>Location</th>
                                                <th>Type / Company</th>
                                                <th>Orders & LTV</th>
                                                <th>Account Status</th>
                                                <th>Joined Date</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): ?>
                                                <?php
                                                    // Determine Customer Full Name with fallback hierarchy
                                                    $firstName = trim($customer['first_name'] ?? '');
                                                    $lastName = trim($customer['last_name'] ?? '');
                                                    $fullName = trim($firstName . ' ' . $lastName);
                                                    
                                                    if (empty($fullName)) {
                                                        if (!empty($customer['company_name'])) {
                                                            $fullName = $customer['company_name'];
                                                        } elseif (!empty($customer['username'])) {
                                                            $fullName = ucfirst($customer['username']);
                                                        } elseif (!empty($customer['email'])) {
                                                            $fullName = ucfirst(explode('@', $customer['email'])[0]);
                                                        } else {
                                                            $fullName = "Customer #" . $customer['id'];
                                                        }
                                                    }

                                                    $email = $customer['email'] ?? '';
                                                    $phone = $customer['phone'] ?? ($customer['user_phone'] ?? '');
                                                    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                                                    $avatarColor = getAvatarColor($fullName);
                                                    $initials = getInitials($firstName, $lastName, $customer['username'] ?? '', $email);
                                                    
                                                    // Verification badge check
                                                    $isVerified = (!empty($customer['is_verified']) && $customer['is_verified'] == 1);
                                                    
                                                    // Profile image check
                                                    $avatarImg = $customer['profile_picture'] ?? '';
                                                ?>
                                                <tr>
                                                    <!-- Customer Info with Avatar -->
                                                    <td>
                                                        <div class="customer-profile-cell">
                                                            <?php if (!empty($avatarImg) && file_exists(__DIR__ . '/' . ltrim($avatarImg, '/'))): ?>
                                                                <img src="<?php echo htmlspecialchars($avatarImg); ?>" 
                                                                     alt="<?php echo htmlspecialchars($fullName); ?>" 
                                                                     class="customer-avatar" />
                                                            <?php else: ?>
                                                                <div class="customer-avatar-initials" style="background-color: <?php echo $avatarColor; ?>;">
                                                                    <?php echo $initials; ?>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="customer-name-wrap">
                                                                <a href="view-customer.php?id=<?php echo $customer['id']; ?>" class="customer-name">
                                                                    <span><?php echo htmlspecialchars($fullName); ?></span>
                                                                    <?php if ($isVerified): ?>
                                                                        <i class="fas fa-check-circle text-primary" title="Verified Customer" style="font-size: 13px;"></i>
                                                                    <?php endif; ?>
                                                                </a>
                                                                <div class="customer-meta">
                                                                    <i class="far fa-envelope text-muted"></i>
                                                                    <span><?php echo htmlspecialchars($email); ?></span>
                                                                    <button type="button" class="copy-email-btn ms-1" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($email); ?>'); this.innerHTML='<i class=\'fas fa-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'far fa-copy\'></i>', 1500);" title="Copy Email">
                                                                        <i class="far fa-copy"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="text-muted" style="font-size: 11px;">@<?php echo htmlspecialchars($customer['username'] ?? 'user'); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <!-- Phone & Contact -->
                                                    <td>
                                                        <?php if (!empty($phone)): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="fw-medium text-dark"><i class="fas fa-phone-alt text-muted me-1 small"></i><?php echo htmlspecialchars($phone); ?></span>
                                                                <?php if (strlen($cleanPhone) >= 10): ?>
                                                                    <a href="https://wa.me/<?php echo (strlen($cleanPhone) == 10 ? '91' . $cleanPhone : $cleanPhone); ?>" 
                                                                       target="_blank" 
                                                                       class="text-success small" 
                                                                       title="Chat on WhatsApp">
                                                                        <i class="fab fa-whatsapp"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Not provided</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Location -->
                                                    <td>
                                                        <?php 
                                                            $locParts = array_filter([$customer['city'] ?? '', $customer['state'] ?? '']);
                                                            $locText = !empty($locParts) ? implode(', ', $locParts) : ($customer['country'] ?? 'India');
                                                        ?>
                                                        <div class="d-flex align-items-center text-secondary small">
                                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                            <span><?php echo htmlspecialchars($locText); ?></span>
                                                        </div>
                                                    </td>

                                                    <!-- Customer Type & Company -->
                                                    <td>
                                                        <div>
                                                            <span class="<?php echo ($customer['customer_type'] === 'business') ? 'badge-type-business' : 'badge-type-individual'; ?>">
                                                                <?php echo ucfirst($customer['customer_type'] ?? 'individual'); ?>
                                                            </span>
                                                        </div>
                                                        <?php if (!empty($customer['company_name'])): ?>
                                                            <div class="text-dark fw-semibold small mt-1">
                                                                <i class="fas fa-building text-muted me-1"></i><?php echo htmlspecialchars($customer['company_name']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Orders & Spent (LTV) -->
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold text-dark">₹<?php echo number_format(floatval($customer['total_spent'] ?? 0), 2); ?></span>
                                                            <small class="text-muted">
                                                                <i class="fas fa-shopping-bag me-1 text-primary"></i><?php echo intval($customer['total_orders'] ?? 0); ?> orders
                                                            </small>
                                                        </div>
                                                    </td>

                                                    <!-- Account Status -->
                                                    <td>
                                                        <?php if ($customer['status'] === 'active'): ?>
                                                            <span class="badge-status-active">
                                                                <i class="fas fa-circle" style="font-size: 6px;"></i> Active
                                                            </span>
                                                        <?php elseif ($customer['status'] === 'suspended'): ?>
                                                            <span class="badge-status-suspended">
                                                                <i class="fas fa-ban" style="font-size: 10px;"></i> Suspended
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge-status-inactive">
                                                                <i class="fas fa-circle" style="font-size: 6px;"></i> Inactive
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Registration Date -->
                                                    <td>
                                                        <?php 
                                                            $regDate = $customer['registration_date'] ?? $customer['created_at'] ?? '';
                                                        ?>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-medium text-dark small">
                                                                <?php echo !empty($regDate) ? date('M j, Y', strtotime($regDate)) : 'N/A'; ?>
                                                            </span>
                                                            <small class="text-muted" style="font-size: 11px;">
                                                                <?php echo !empty($regDate) ? date('h:i A', strtotime($regDate)) : ''; ?>
                                                            </small>
                                                        </div>
                                                    </td>

                                                    <!-- Actions Column -->
                                                    <td class="text-end pe-4">
                                                        <div class="d-inline-flex gap-1">
                                                            <a href="view-customer.php?id=<?php echo $customer['id']; ?>" 
                                                               class="btn-action-icon" 
                                                               title="View Complete Profile">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" 
                                                               class="btn-action-icon" 
                                                               title="Edit Customer">
                                                                <i class="fas fa-pen"></i>
                                                            </a>
                                                            <?php if ($customer['status'] === 'active'): ?>
                                                                <a href="customers.php?action=suspend&id=<?php echo $customer['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                                   class="btn-action-icon btn-action-suspend" 
                                                                   onclick="return confirm('Are you sure you want to suspend this customer account?')" 
                                                                   title="Suspend Customer">
                                                                    <i class="fas fa-pause"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="customers.php?action=activate&id=<?php echo $customer['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                                   class="btn-action-icon btn-action-activate" 
                                                                   onclick="return confirm('Activate this customer account?')" 
                                                                   title="Activate Customer">
                                                                    <i class="fas fa-play"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="customers.php?action=delete&id=<?php echo $customer['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                               class="btn-action-icon btn-action-delete" 
                                                               onclick="return confirm('WARNING: Are you sure you want to delete this customer? This action cannot be undone.')" 
                                                               title="Delete Customer">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
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
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>