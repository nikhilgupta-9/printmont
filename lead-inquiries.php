<?php
/**
 * Lead Inquiries Manager — Printmont Admin
 * 
 * View, filter, manage, and export corporate business leads.
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lead'])) {
    $leadId = (int)($_POST['lead_id'] ?? 0);
    $status = $_POST['status'] ?? 'new';
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($leadId > 0) {
        $uStmt = $db->prepare("UPDATE lead_inquiries SET status = ?, admin_notes = ? WHERE id = ?");
        $uStmt->bind_param('ssi', $status, $notes, $leadId);
        if ($uStmt->execute()) {
            $message = "Lead #$leadId updated successfully!";
        } else {
            $error = "Failed to update lead.";
        }
        $uStmt->close();
    }
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=business_leads_' . date('Y-m-d_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Lead ID', 'Full Name', 'Phone', 'Email', 'Company', 'Budget', 'Requirements', 'Source Page', 'Device', 'Status', 'Admin Notes', 'Date']);

    $expRes = $db->query("SELECT * FROM lead_inquiries ORDER BY id DESC");
    while ($row = $expRes->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['full_name'],
            $row['contact_number'],
            $row['email_id'],
            $row['company_name'],
            $row['budget'],
            $row['product_requirement'],
            $row['source_page'],
            $row['device_type'],
            $row['status'],
            $row['admin_notes'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

// Filter query
$statusFilter = $_GET['status'] ?? '';
$sourceFilter = trim($_GET['source'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if ($statusFilter && in_array($statusFilter, ['new', 'contacted', 'qualified', 'closed', 'junk'])) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($sourceFilter === 'franchise') {
    $where[] = "source_page LIKE '%franchise%'";
} elseif ($sourceFilter === 'seller') {
    $where[] = "(source_page LIKE '%seller%' OR source_page LIKE '%become%')";
} elseif ($sourceFilter === 'business') {
    $where[] = "source_page LIKE '%business%'";
} elseif ($sourceFilter === 'advertising') {
    $where[] = "source_page LIKE '%advertising%'";
}

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR contact_number LIKE ? OR email_id LIKE ? OR company_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql = "SELECT * FROM lead_inquiries WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT 100";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary stats
$statsRes = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_cnt,
    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_cnt,
    SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_cnt,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_cnt,
    SUM(CASE WHEN source_page LIKE '%franchise%' THEN 1 ELSE 0 END) as franchise_cnt,
    SUM(CASE WHEN source_page LIKE '%seller%' OR source_page LIKE '%become%' THEN 1 ELSE 0 END) as seller_cnt
    FROM lead_inquiries");
$stats = $statsRes ? $statsRes->fetch_assoc() : ['total' => 0, 'new_cnt' => 0, 'contacted_cnt' => 0, 'qualified_cnt' => 0, 'closed_cnt' => 0, 'franchise_cnt' => 0, 'seller_cnt' => 0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Lead Inquiries | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
</head>

<body>
<div class="wrapper">
    <?php include_once 'includes/side-navbar.php'; ?>

    <div class="main">
        <?php include_once 'includes/top-navbar.php'; ?>

        <main class="content">
            <div class="container-fluid p-0">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h1 class="h3 fw-bold mb-1">Partnership &amp; Business Leads</h1>
                        <p class="text-muted mb-0">Manage Franchise Applications, Seller Registrations &amp; Corporate Inquiries.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="business-solutions-page.php" class="btn btn-outline-primary fw-bold">
                            <i class="align-middle me-1" data-feather="layout"></i> Page Builder
                        </a>
                        <a href="lead-inquiries.php?export=csv" class="btn btn-success fw-bold shadow-sm">
                            <i class="align-middle me-1" data-feather="download"></i> Export CSV
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stat Counters -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm border-0 border-start border-4 border-primary">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-bold text-uppercase">Total Inquiries</span>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo (int)$stats['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm border-0 border-start border-4 border-info">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-bold text-uppercase">Franchise Leads</span>
                                <h3 class="fw-bold mb-0 text-info"><?php echo (int)$stats['franchise_cnt']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm border-0 border-start border-4 border-success">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-bold text-uppercase">Seller Onboarding</span>
                                <h3 class="fw-bold mb-0 text-success"><?php echo (int)$stats['seller_cnt']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card shadow-sm border-0 border-start border-4 border-warning">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-bold text-uppercase">New / Unread</span>
                                <h3 class="fw-bold mb-0 text-warning"><?php echo (int)$stats['new_cnt']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="lead-inquiries.php" class="row g-2 align-items-center">
                            <div class="col-12 col-md-4">
                                <input type="search" name="search" class="form-control form-control-sm" 
                                       placeholder="Search name, email, phone, city..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="source" class="form-select form-select-sm">
                                    <option value="">All Channels</option>
                                    <option value="franchise" <?php echo ($sourceFilter === 'franchise') ? 'selected' : ''; ?>>Franchise Inquiries</option>
                                    <option value="seller" <?php echo ($sourceFilter === 'seller') ? 'selected' : ''; ?>>Become a Seller</option>
                                    <option value="business" <?php echo ($sourceFilter === 'business') ? 'selected' : ''; ?>>Corporate Solutions</option>
                                    <option value="advertising" <?php echo ($sourceFilter === 'advertising') ? 'selected' : ''; ?>>Advertising</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="new" <?php echo ($statusFilter === 'new') ? 'selected' : ''; ?>>New</option>
                                    <option value="contacted" <?php echo ($statusFilter === 'contacted') ? 'selected' : ''; ?>>Contacted</option>
                                    <option value="qualified" <?php echo ($statusFilter === 'qualified') ? 'selected' : ''; ?>>Qualified</option>
                                    <option value="closed" <?php echo ($statusFilter === 'closed') ? 'selected' : ''; ?>>Closed / Won</option>
                                    <option value="junk" <?php echo ($statusFilter === 'junk') ? 'selected' : ''; ?>>Junk</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-bold">Filter</button>
                                <?php if ($search || $statusFilter || $sourceFilter): ?>
                                    <a href="lead-inquiries.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#ID</th>
                                        <th>Client Details</th>
                                        <th>Channel</th>
                                        <th>Location &amp; Budget</th>
                                        <th>Requirements / Model</th>
                                        <th>Device / Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($leads)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                No lead inquiries found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($leads as $l): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo $l['id']; ?></td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($l['full_name']); ?></span>
                                                    <small class="d-block text-muted">
                                                        <a href="tel:<?php echo htmlspecialchars($l['contact_number']); ?>" class="text-decoration-none">
                                                            📞 <?php echo htmlspecialchars($l['contact_number']); ?>
                                                        </a>
                                                    </small>
                                                    <small class="d-block text-muted">
                                                        <a href="mailto:<?php echo htmlspecialchars($l['email_id']); ?>" class="text-decoration-none">
                                                            ✉️ <?php echo htmlspecialchars($l['email_id']); ?>
                                                        </a>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $src = strtolower($l['source_page'] ?? '');
                                                        if (strpos($src, 'franchise') !== false) {
                                                            echo '<span class="badge bg-primary text-white mb-1"><i class="align-middle me-1" data-feather="home"></i>FRANCHISE</span>';
                                                            if (strpos($src, 'callback') !== false) {
                                                                echo '<small class="d-block text-muted">Quick Callback</small>';
                                                            } elseif (strpos($src, 'brochure') !== false) {
                                                                echo '<small class="d-block text-muted">Brochure Download</small>';
                                                            } else {
                                                                echo '<small class="d-block text-muted">Application Form</small>';
                                                            }
                                                        } elseif (strpos($src, 'seller') !== false || strpos($src, 'become') !== false) {
                                                            echo '<span class="badge bg-info text-dark mb-1"><i class="align-middle me-1" data-feather="shopping-bag"></i>SELLER</span>';
                                                            if (strpos($src, 'callback') !== false) {
                                                                echo '<small class="d-block text-muted">Quick Callback</small>';
                                                            } elseif (strpos($src, 'handbook') !== false) {
                                                                echo '<small class="d-block text-muted">Handbook Download</small>';
                                                            } else {
                                                                echo '<small class="d-block text-muted">Registration Form</small>';
                                                            }
                                                        } else {
                                                            echo '<span class="badge bg-dark text-white mb-1"><i class="align-middle me-1" data-feather="briefcase"></i>CORPORATE</span>';
                                                            echo '<small class="d-block text-muted">Business Solution</small>';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($l['company_name'] ?: '—'); ?></span>
                                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($l['budget'] ?: 'Not specified'); ?></span>
                                                </td>
                                                <td>
                                                    <p class="small text-muted mb-0" style="max-width: 260px;">
                                                        <?php echo htmlspecialchars($l['product_requirement'] ?: 'No additional notes'); ?>
                                                    </p>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo strtoupper($l['device_type'] ?? 'DESKTOP'); ?></span>
                                                    <small class="d-block text-muted mt-1"><?php echo date('d M Y, h:i A', strtotime($l['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch($l['status']) {
                                                            case 'new': echo 'warning text-dark'; break;
                                                            case 'contacted': echo 'info'; break;
                                                            case 'qualified': echo 'primary'; break;
                                                            case 'closed': echo 'success'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo strtoupper($l['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php 
                                                        $cleanPhone = preg_replace('/[^0-9]/', '', $l['contact_number']);
                                                    ?>
                                                    <a href="https://wa.me/<?php echo $cleanPhone; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Chat on WhatsApp">
                                                        💬
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#editLeadModal-<?php echo $l['id']; ?>">
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit Lead Modal -->
                                            <div class="modal fade" id="editLeadModal-<?php echo $l['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="lead-inquiries.php">
                                                            <input type="hidden" name="update_lead" value="1">
                                                            <input type="hidden" name="lead_id" value="<?php echo $l['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Update Lead #<?php echo $l['id']; ?> - <?php echo htmlspecialchars($l['full_name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="new" <?php echo ($l['status'] === 'new') ? 'selected' : ''; ?>>New</option>
                                                                        <option value="contacted" <?php echo ($l['status'] === 'contacted') ? 'selected' : ''; ?>>Contacted</option>
                                                                        <option value="qualified" <?php echo ($l['status'] === 'qualified') ? 'selected' : ''; ?>>Qualified / In Discussion</option>
                                                                        <option value="closed" <?php echo ($l['status'] === 'closed') ? 'selected' : ''; ?>>Closed / Won</option>
                                                                        <option value="junk" <?php echo ($l['status'] === 'junk') ? 'selected' : ''; ?>>Junk / Not Interested</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Admin Internal Notes</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="4" placeholder="Follow-up details, quotation sent, customer requirements..."><?php echo htmlspecialchars($l['admin_notes'] ?? ''); ?></textarea>
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
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php include_once 'includes/footer.php'; ?>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
