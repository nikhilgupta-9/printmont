<?php
/**
 * Total Sold Inquiries & Converted Orders — Printmont Admin
 * 
 * Module 17: Track, manage, and process converted bulk order inquiries through fulfillment.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle Status or Note update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry'])) {
    $inqId = (int)($_POST['inquiry_id'] ?? 0);
    $status = $_POST['status'] ?? 'sold';
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($inqId > 0) {
        $uStmt = $db->prepare("UPDATE bulk_inquiries SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $uStmt->bind_param('ssi', $status, $notes, $inqId);
        if ($uStmt->execute()) {
            $message = "Inquiry #$inqId status updated successfully!";
        } else {
            $error = "Failed to update inquiry: " . $uStmt->error;
        }
        $uStmt->close();
    }
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sold_inquiries_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Inquiry ID', 'Customer Name', 'Phone', 'Email', 'Product', 'Quantity', 'Status', 'Notes', 'Created Date']);

    $expRes = $db->query("SELECT b.*, p.name as product_name FROM bulk_inquiries b LEFT JOIN products p ON b.product_id = p.id WHERE b.status = 'sold' ORDER BY b.id DESC");
    while ($row = $expRes->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['customer_name'] ?? $row['full_name'] ?? '',
            $row['customer_phone'] ?? $row['phone'] ?? '',
            $row['customer_email'] ?? $row['email'] ?? '',
            $row['product_name'] ?? 'Custom Bulk Item',
            $row['quantity'] ?? 1,
            $row['status'],
            $row['admin_notes'] ?? '',
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

// Fetch Stats
$stats = [
    'total_sold' => 0,
    'total_units' => 0,
    'completed' => 0,
    'pending' => 0
];
$statRes = $db->query("SELECT 
    COUNT(*) as total_sold,
    COALESCE(SUM(quantity), 0) as total_units,
    SUM(IF(status = 'completed', 1, 0)) as completed,
    SUM(IF(status = 'pending' OR status = 'sold', 1, 0)) as pending
FROM bulk_inquiries WHERE status = 'sold' OR status = 'completed'");
if ($statRes && $r = $statRes->fetch_assoc()) {
    $stats = $r;
}

// Filter
$statusFilter = $_GET['filter'] ?? 'all';
$whereClause = "WHERE (b.status = 'sold' OR b.status = 'completed')";
if ($statusFilter === 'sold') {
    $whereClause = "WHERE b.status = 'sold'";
} elseif ($statusFilter === 'completed') {
    $whereClause = "WHERE b.status = 'completed'";
}

$query = "SELECT b.*, 
    COALESCE(b.customer_name, b.email) as display_name,
    COALESCE(b.customer_phone, b.phone) as display_phone,
    COALESCE(b.customer_email, b.email) as display_email,
    p.name as product_name,
    p.price as product_price,
    p.thumbnail_image as product_image
FROM bulk_inquiries b
LEFT JOIN products p ON b.product_id = p.id
$whereClause
ORDER BY b.id DESC";

$inquiries = [];
$res = $db->query($query);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $inquiries[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Total Sold Inquiries | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 16px;
        }
        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .status-sold { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e0f2fe; color: #0369a1; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .prod-thumb {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
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
                    <div class="row mb-3 align-items-center">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong>Total Sold</strong> Inquiries</h1>
                            <p class="text-muted small">Converted bulk order leads, sales pipeline fulfillment & delivered orders.</p>
                        </div>
                        <div class="col-auto ms-auto d-flex gap-2">
                            <a href="total-sold-inquiry.php?export=csv" class="btn btn-outline-success">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </a>
                            <a href="bulk-inquiries.php" class="btn btn-outline-secondary">
                                <i class="fas fa-inbox me-1"></i> All Inquiries
                            </a>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Row -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded text-success fs-3">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['total_sold']; ?></h4>
                                    <div class="text-muted small">Sold Deals</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded text-primary fs-3">
                                    <i class="fas fa-cubes"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo number_format($stats['total_units']); ?></h4>
                                    <div class="text-muted small">Units Sold</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-info bg-opacity-10 rounded text-info fs-3">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['completed']; ?></h4>
                                    <div class="text-muted small">Fulfillment Complete</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card d-flex align-items-center gap-3">
                                <div class="p-3 bg-warning bg-opacity-10 rounded text-warning fs-3">
                                    <i class="fas fa-shipping-fast"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo (int)$stats['pending']; ?></h4>
                                    <div class="text-muted small">In Process / Dispatch</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="total-sold-inquiry.php?filter=all">All Converted</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $statusFilter === 'sold' ? 'active' : ''; ?>" href="total-sold-inquiry.php?filter=sold">Sold / Processing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>" href="total-sold-inquiry.php?filter=completed">Completed & Delivered</a>
                        </li>
                    </ul>

                    <!-- Inquiries Table Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Sold Inquiries Pipeline (<?php echo count($inquiries); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($inquiries)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart text-muted mb-3" style="font-size: 48px;"></i>
                                    <h5>No Sold Inquiries Yet</h5>
                                    <p class="text-muted small mb-3">When bulk inquiries or lead quotes are marked as "sold", they appear here for fulfillment tracking.</p>
                                    <a href="bulk-inquiries.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-inbox me-1"></i> Review Bulk Inquiries
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Customer Info</th>
                                                <th>Product Details</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                                <th>Admin Notes</th>
                                                <th>Date</th>
                                                <th class="text-end" style="width: 100px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inquiries as $inq): ?>
                                                <tr>
                                                    <td><span class="text-muted fw-bold">#<?php echo $inq['id']; ?></span></td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($inq['display_name'] ?: 'Customer'); ?></div>
                                                        <div class="small text-muted"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($inq['display_phone'] ?? 'N/A'); ?></div>
                                                        <div class="small text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($inq['display_email'] ?? 'N/A'); ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php if (!empty($inq['product_image'])): ?>
                                                                <img src="<?php echo htmlspecialchars($inq['product_image']); ?>" class="prod-thumb" alt="Product">
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-bold small"><?php echo htmlspecialchars($inq['product_name'] ?: 'Bulk Order'); ?></div>
                                                                <?php if (!empty($inq['message'])): ?>
                                                                    <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($inq['message'], 0, 50, '...')); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border fw-bold"><?php echo (int)($inq['quantity'] ?? 1); ?> units</span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $inq['status']; ?>">
                                                            <?php echo htmlspecialchars($inq['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo htmlspecialchars($inq['admin_notes'] ?: 'No notes yet'); ?></small>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></small>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $inq['id']; ?>">
                                                            <i class="fas fa-edit"></i> Update
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Update Modal -->
                                                <div class="modal fade" id="editModal<?php echo $inq['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <input type="hidden" name="update_inquiry" value="1">
                                                                <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Sold Inquiry #<?php echo $inq['id']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Pipeline Stage / Status</label>
                                                                        <select name="status" class="form-select">
                                                                            <option value="sold" <?php echo $inq['status'] === 'sold' ? 'selected' : ''; ?>>Sold / In Process</option>
                                                                            <option value="completed" <?php echo $inq['status'] === 'completed' ? 'selected' : ''; ?>>Completed & Delivered</option>
                                                                            <option value="pending" <?php echo $inq['status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Fulfillment & Delivery Notes</label>
                                                                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Enter tracking number, dispatch date, or payment receipt details..."><?php echo htmlspecialchars($inq['admin_notes'] ?? ''); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
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
