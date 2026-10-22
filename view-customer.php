<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CustomerController.php';

$customerController = new CustomerController();
$customerId = (int)($_GET['id'] ?? 0);
$customer = $customerController->getCustomerById($customerId);

if (!$customer) {
    // Also try fetching by user_id in case user clicked user_id link
    $customer = $customerController->getCustomerByUserId($customerId);
}

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found!";
    header("Location: customers.php");
    exit;
}

$userId = !empty($customer['user_id']) ? (int)$customer['user_id'] : null;
$customerOrders = $customerController->getCustomerOrders($customer['id'], $userId, $customer['email'] ?? '', $customer['phone'] ?? '');
$customerAddresses = $customerController->getCustomerAddresses($userId);
$customerStats = $customerController->getCustomerFullStats($customer['id'], $userId, $customer['email'] ?? '', $customer['phone'] ?? '');

$fullName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
if (empty($fullName)) {
    $fullName = !empty($customer['company_name']) ? $customer['company_name'] : ($customer['username'] ?? 'Customer #' . $customer['id']);
}
$isVerified = (!empty($customer['is_verified']) && $customer['is_verified'] == 1);
$cleanPhone = preg_replace('/[^0-9]/', '', $customer['phone'] ?? ($customer['user_phone'] ?? ''));
if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($fullName); ?> | Customer 360 | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .customer-hero {
            background: linear-gradient(135deg, #0b53a1 0%, #1e293b 100%);
            border-radius: 12px;
            color: #ffffff;
            padding: 28px;
            margin-bottom: 24px;
        }
        .stat-badge-card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .customer-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #ffffff;
            color: #0b53a1;
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .info-group {
            margin-bottom: 14px;
        }
        .info-group .label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }
        .info-group .val {
            font-size: 13.5px;
            color: #0f172a;
            font-weight: 600;
        }
        .address-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            background: #f8fafc;
            margin-bottom: 12px;
        }
        .badge-status {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: capitalize;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-processing { background-color: #e0f2fe; color: #0369a1; }
        .badge-shipped { background-color: #f3e8ff; color: #6b21a8; }
        .badge-delivered { background-color: #dcfce7; color: #166534; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Customer Hero Header -->
                    <div class="customer-hero">
                        <div class="row align-items-center">
                            <div class="col-lg-7">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(substr($fullName, 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h2 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($fullName); ?></h2>
                                            <?php if ($isVerified): ?>
                                                <span class="badge bg-success font-11"><i class="fas fa-circle-check me-1"></i>Verified</span>
                                            <?php endif; ?>
                                            <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?> font-11">
                                                <?php echo ucfirst($customer['status'] ?? 'active'); ?>
                                            </span>
                                        </div>
                                        <div class="text-white-50 font-13 mt-1">
                                            <i class="far fa-envelope me-1"></i><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>
                                            <?php if (!empty($customer['phone'])): ?>
                                                &nbsp;•&nbsp; <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['customer_type'])): ?>
                                                &nbsp;•&nbsp; <span class="badge bg-light text-dark font-11"><?php echo ucfirst($customer['customer_type']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                    <?php if (!empty($cleanPhone)): ?>
                                        <a href="https://wa.me/<?php echo $cleanPhone; ?>" target="_blank" class="btn btn-success btn-sm fw-semibold">
                                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>
                                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="btn btn-light btn-sm fw-semibold">
                                        <i class="fas fa-envelope text-primary me-1"></i> Email
                                    </a>
                                    <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline-light btn-sm fw-semibold">
                                        <i class="fas fa-edit me-1"></i> Edit Profile
                                    </a>
                                    <a href="customers.php" class="btn btn-secondary btn-sm fw-semibold">
                                        <i class="fas fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Metric Summary Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-sm-6">
                            <div class="stat-badge-card">
                                <span class="text-muted text-uppercase fw-bold font-11">Lifetime Spend (LTV)</span>
                                <h3 class="fw-bold text-success mt-1 mb-0">₹<?php echo number_format($customerStats['total_spent'] ?? 0, 2); ?></h3>
                                <span class="text-muted font-11">Avg Order: ₹<?php echo number_format($customerStats['avg_order_value'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="stat-badge-card">
                                <span class="text-muted text-uppercase fw-bold font-11">Total Orders</span>
                                <h3 class="fw-bold text-primary mt-1 mb-0"><?php echo count($customerOrders); ?></h3>
                                <span class="text-muted font-11"><?php echo $customerStats['completed_orders'] ?? 0; ?> fulfilled successfully</span>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="stat-badge-card">
                                <span class="text-muted text-uppercase fw-bold font-11">Pending Orders</span>
                                <h3 class="fw-bold text-warning mt-1 mb-0"><?php echo $customerStats['pending_orders'] ?? 0; ?></h3>
                                <span class="text-muted font-11">Currently in fulfillment</span>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="stat-badge-card">
                                <span class="text-muted text-uppercase fw-bold font-11">Registered Since</span>
                                <h3 class="fw-bold text-dark mt-1 mb-0 font-16">
                                    <?php echo date('M d, Y', strtotime($customer['registration_date'] ?? ($customer['created_at'] ?? 'now'))); ?>
                                </h3>
                                <span class="text-muted font-11">
                                    Last Active: <?php echo !empty($customer['last_login']) ? date('d M Y', strtotime($customer['last_login'])) : 'Recent'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column: Orders History & Saved Addresses -->
                        <div class="col-lg-8">
                            
                            <!-- Real Customer Orders History Table -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-box-archive text-primary me-2"></i>Order History (<?php echo count($customerOrders); ?> Orders)
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light font-12 text-uppercase">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date & Time</th>
                                                    <th>Items</th>
                                                    <th>Grand Total</th>
                                                    <th>Order Status</th>
                                                    <th>Payment</th>
                                                    <th class="text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($customerOrders)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center py-4 text-muted">
                                                            <i class="fas fa-shopping-cart fa-2x mb-2 text-light-emphasis"></i>
                                                            <p class="mb-0">This customer has not placed any orders yet.</p>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($customerOrders as $ord): 
                                                        $st = strtolower($ord['status'] ?? 'pending');
                                                        $statusClass = 'badge-' . $st;
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <a href="view-order.php?id=<?php echo $ord['id']; ?>" class="fw-bold text-primary">
                                                                    #<?php echo htmlspecialchars($ord['order_number']); ?>
                                                                </a>
                                                            </td>
                                                            <td class="font-12 text-muted">
                                                                <?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border">
                                                                    <?php echo $ord['items_count']; ?> item<?php echo $ord['items_count'] > 1 ? 's' : ''; ?>
                                                                </span>
                                                            </td>
                                                            <td class="fw-bold text-dark font-13">
                                                                ₹<?php echo number_format((float)($ord['grand_total'] ?? $ord['total_amount']), 2); ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge-status <?php echo $statusClass; ?>">
                                                                    <?php echo ucfirst($st); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo ($ord['payment_status'] === 'paid') ? 'success' : 'warning text-dark'; ?> font-11">
                                                                    <?php echo strtoupper($ord['payment_status'] ?? 'PENDING'); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-end">
                                                                <a href="view-order.php?id=<?php echo $ord['id']; ?>" class="btn btn-outline-primary btn-sm" title="View Order">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Saved Delivery Addresses -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-map-location-dot text-primary me-2"></i>Saved Delivery Addresses
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($customerAddresses)): ?>
                                        <?php if (!empty($customer['address'])): ?>
                                            <div class="address-box">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <strong>Default Profile Address</strong>
                                                    <span class="badge bg-primary">Primary</span>
                                                </div>
                                                <p class="font-13 text-muted mb-0"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                                                <?php if (!empty($customer['city']) || !empty($customer['state'])): ?>
                                                    <p class="font-12 text-muted mb-0 mt-1"><?php echo htmlspecialchars(implode(', ', array_filter([$customer['city'] ?? '', $customer['state'] ?? '', $customer['postal_code'] ?? '']))); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No saved addresses on file.</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="row g-3">
                                            <?php foreach ($customerAddresses as $addr): ?>
                                                <div class="col-md-6">
                                                    <div class="address-box h-100">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <strong><?php echo htmlspecialchars($addr['name'] ?? $fullName); ?></strong>
                                                            <div>
                                                                <span class="badge bg-secondary font-11"><?php echo htmlspecialchars($addr['type'] ?? 'Home'); ?></span>
                                                                <?php if (!empty($addr['is_default'])): ?>
                                                                    <span class="badge bg-success font-11">Default</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <p class="font-13 text-muted mb-1"><?php echo htmlspecialchars($addr['address'] ?? ''); ?></p>
                                                        <?php if (!empty($addr['locality'])): ?>
                                                            <p class="font-12 text-muted mb-1"><?php echo htmlspecialchars($addr['locality']); ?><?php if (!empty($addr['landmark'])): ?> (Near <?php echo htmlspecialchars($addr['landmark']); ?>)<?php endif; ?></p>
                                                        <?php endif; ?>
                                                        <p class="font-12 text-dark fw-semibold mb-1"><?php echo htmlspecialchars($addr['city'] ?? ''); ?>, <?php echo htmlspecialchars($addr['state'] ?? ''); ?> - <?php echo htmlspecialchars($addr['pincode'] ?? ''); ?></p>
                                                        <?php if (!empty($addr['phone'])): ?>
                                                            <p class="font-11 text-muted mb-0"><i class="fas fa-phone fa-xs me-1"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Profile & Tax Details, Admin Controls -->
                        <div class="col-lg-4">
                            
                            <!-- Profile & Contact Card -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-bold"><i class="fas fa-id-card text-primary me-2"></i>Account & Contact Info</h6>
                                </div>
                                <div class="card-body">
                                    <div class="info-group">
                                        <div class="label">Username</div>
                                        <div class="val">@<?php echo htmlspecialchars($customer['username'] ?? 'user'); ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Email Address</div>
                                        <div class="val"><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Phone Number</div>
                                        <div class="val"><?php echo htmlspecialchars($customer['phone'] ?? ($customer['user_phone'] ?? 'N/A')); ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Customer Type</div>
                                        <div class="val text-capitalize"><?php echo htmlspecialchars($customer['customer_type'] ?? 'Individual'); ?></div>
                                    </div>
                                    <?php if (!empty($customer['gender'])): ?>
                                    <div class="info-group">
                                        <div class="label">Gender</div>
                                        <div class="val text-capitalize"><?php echo htmlspecialchars($customer['gender']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Business / GST / PAN Card (if present) -->
                            <?php if (!empty($customer['company_name']) || !empty($customer['gst_number']) || !empty($customer['pan_number'])): ?>
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-bold"><i class="fas fa-building text-primary me-2"></i>Business & Tax Information</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($customer['company_name'])): ?>
                                    <div class="info-group">
                                        <div class="label">Company Name</div>
                                        <div class="val"><?php echo htmlspecialchars($customer['company_name']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($customer['gst_number'])): ?>
                                    <div class="info-group">
                                        <div class="label">GST Number</div>
                                        <div class="val font-monospace"><?php echo htmlspecialchars($customer['gst_number']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($customer['pan_number'])): ?>
                                    <div class="info-group">
                                        <div class="label">PAN Details</div>
                                        <div class="val font-monospace"><?php echo htmlspecialchars($customer['pan_number']); ?> (<?php echo htmlspecialchars($customer['pan_name'] ?? ''); ?>)</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Admin Quick Actions -->
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-bold"><i class="fas fa-sliders text-primary me-2"></i>Account Controls</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-pen-to-square me-1"></i> Edit Customer
                                        </a>
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <a href="customers.php?action=suspend&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-warning btn-sm"
                                               onclick="return confirm('Suspend this customer account?')">
                                                <i class="fas fa-pause-circle me-1"></i> Suspend Account
                                            </a>
                                        <?php else: ?>
                                            <a href="customers.php?action=activate&id=<?php echo $customer['id']; ?>" 
                                               class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-play-circle me-1"></i> Activate Account
                                            </a>
                                        <?php endif; ?>
                                        <a href="customers.php?action=delete&id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this customer and their login access?')">
                                            <i class="fas fa-trash-can me-1"></i> Delete Customer
                                        </a>
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