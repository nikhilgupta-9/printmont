<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/ContactController.php';

$database = new Database();
$db = $database->getConnection();
$contactController = new ContactController();

// 1. Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $statusFilter = $_GET['status'] ?? '';
    $searchQuery = trim($_GET['search'] ?? '');
    $inquiriesToExport = $contactController->getInquiries($statusFilter, $searchQuery);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contact_inquiries_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, ['ID', 'Date Received', 'Customer Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Admin Notes', 'Last Updated']);

    foreach ($inquiriesToExport as $row) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['name'],
            $row['email'],
            $row['phone'] ?? '',
            $row['subject'] ?? '',
            $row['message'],
            $row['status'],
            $row['admin_notes'] ?? '',
            $row['updated_at'] ?? ''
        ]);
    }
    fclose($output);
    exit();
}

// 2. Handle POST Actions (Status/Notes Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_status';
    $statusFilter = $_POST['current_status'] ?? ($_GET['status'] ?? '');
    $searchQuery = $_POST['current_search'] ?? ($_GET['search'] ?? '');

    $redirectUrl = 'contact-inquiries.php?' . http_build_query(array_filter([
        'status' => $statusFilter,
        'search' => $searchQuery
    ]));

    if ($action === 'delete' && isset($_POST['inquiry_id'])) {
        $ok = $contactController->deleteInquiry((int)$_POST['inquiry_id']);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Inquiry deleted successfully.' : 'Could not delete inquiry.';
        header('Location: ' . $redirectUrl);
        exit();
    }

    if ($action === 'update_status' && isset($_POST['inquiry_id'])) {
        $status = $_POST['status'] ?? 'new';
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $ok = $contactController->updateInquiryStatus((int)$_POST['inquiry_id'], $status, $adminNotes);

        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Inquiry details updated successfully.' : 'Could not update inquiry.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

$statusFilter = $_GET['status'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

$stats = $contactController->getInquiryStats();
$inquiries = $contactController->getInquiries($statusFilter, $searchQuery);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once 'controllers/LogoController.php';
$logoController = new LogoController($db);
$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';

$STATUS_LABELS = [
    'new'         => ['label' => 'New', 'badge' => 'bg-primary-subtle text-primary border border-primary-subtle', 'icon' => 'fa-envelope-open-text'],
    'in_progress' => ['label' => 'In Progress', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'icon' => 'fa-spinner'],
    'resolved'    => ['label' => 'Resolved', 'badge' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'fa-check-circle'],
    'closed'      => ['label' => 'Closed', 'badge' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'fa-archive'],
    'read'        => ['label' => 'Read', 'badge' => 'bg-info-subtle text-info border border-info-subtle', 'icon' => 'fa-eye'],
    'replied'     => ['label' => 'Replied', 'badge' => 'bg-teal-subtle text-teal border border-teal-subtle', 'icon' => 'fa-reply']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contact Inquiries | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .kpi-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-transform: uppercase;
        }
        .table > :not(caption) > * > * {
            padding: 12px 14px;
            vertical-align: middle;
        }
        .msg-cell {
            max-width: 320px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
            color: #475569;
        }
        .tab-pill-btn {
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 500;
            font-size: 13px;
        }
        .pulse-new {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3b82f6;
            margin-right: 4px;
            animation: blinker 1.5s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0.2; }
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
                    
                    <!-- Page Header -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1"><strong>Contact</strong> Inquiries</h3>
                            <p class="text-muted mb-0">Review customer inquiries submitted via storefront contact form, respond via WhatsApp or Email, and track resolution status.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                            <a href="contact-inquiries.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-file-export me-1"></i> Export CSV
                            </a>
                            <a href="general-settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-address-book me-1"></i> Store Contact Info
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Summary Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="kpi-icon bg-primary-subtle text-primary me-3">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <div>
                                            <h3 class="fw-bold mb-0"><?php echo (int)($stats['total'] ?? 0); ?></h3>
                                            <span class="text-muted small">Total Inquiries</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="kpi-icon bg-info-subtle text-info me-3">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div>
                                            <h3 class="fw-bold mb-0 text-info"><?php echo (int)($stats['new'] ?? 0); ?></h3>
                                            <span class="text-muted small">New / Unread</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="kpi-icon bg-warning-subtle text-warning-emphasis me-3">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div>
                                            <h3 class="fw-bold mb-0 text-warning-emphasis"><?php echo (int)($stats['in_progress'] ?? 0); ?></h3>
                                            <span class="text-muted small">In Progress</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card kpi-card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="kpi-icon bg-success-subtle text-success me-3">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div>
                                            <h3 class="fw-bold mb-0 text-success"><?php echo (int)(($stats['resolved'] ?? 0) + ($stats['closed'] ?? 0)); ?></h3>
                                            <span class="text-muted small">Resolved / Closed</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="row align-items-center g-3">
                                <!-- Status Filter Tabs -->
                                <div class="col-lg-7">
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="contact-inquiries.php<?php echo !empty($searchQuery) ? '?search=' . urlencode($searchQuery) : ''; ?>"
                                           class="btn btn-sm tab-pill-btn <?php echo ($statusFilter === '' || $statusFilter === 'all') ? 'btn-primary' : 'btn-light'; ?>">
                                            All <span class="badge bg-white text-dark ms-1"><?php echo (int)($stats['total'] ?? 0); ?></span>
                                        </a>
                                        <a href="contact-inquiries.php?status=new<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>"
                                           class="btn btn-sm tab-pill-btn <?php echo $statusFilter === 'new' ? 'btn-primary' : 'btn-light'; ?>">
                                            <span class="pulse-new"></span> New <span class="badge bg-primary ms-1"><?php echo (int)($stats['new'] ?? 0); ?></span>
                                        </a>
                                        <a href="contact-inquiries.php?status=in_progress<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>"
                                           class="btn btn-sm tab-pill-btn <?php echo $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-light'; ?>">
                                            In Progress <span class="badge bg-warning text-dark ms-1"><?php echo (int)($stats['in_progress'] ?? 0); ?></span>
                                        </a>
                                        <a href="contact-inquiries.php?status=resolved<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>"
                                           class="btn btn-sm tab-pill-btn <?php echo $statusFilter === 'resolved' ? 'btn-primary' : 'btn-light'; ?>">
                                            Resolved <span class="badge bg-success ms-1"><?php echo (int)($stats['resolved'] ?? 0); ?></span>
                                        </a>
                                        <a href="contact-inquiries.php?status=closed<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>"
                                           class="btn btn-sm tab-pill-btn <?php echo $statusFilter === 'closed' ? 'btn-primary' : 'btn-light'; ?>">
                                            Closed <span class="badge bg-secondary ms-1"><?php echo (int)($stats['closed'] ?? 0); ?></span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Search Form -->
                                <div class="col-lg-5">
                                    <form method="GET" action="contact-inquiries.php" class="d-flex gap-2">
                                        <?php if (!empty($statusFilter)): ?>
                                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                                        <?php endif; ?>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search name, email, phone, keyword..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                        </div>
                                        <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                                            <a href="contact-inquiries.php" class="btn btn-outline-secondary" title="Clear Filters">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <?php if (empty($inquiries)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-inbox text-muted" style="font-size: 48px; opacity: 0.5;"></i>
                                    </div>
                                    <h5 class="fw-bold">No Contact Inquiries Found</h5>
                                    <p class="text-muted mb-0">
                                        <?php echo (!empty($searchQuery) || !empty($statusFilter)) ? 'No inquiries matched your filter criteria.' : 'Messages submitted via storefront contact form will appear here.'; ?>
                                    </p>
                                    <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                                        <a href="contact-inquiries.php" class="btn btn-sm btn-outline-primary mt-3">View All Inquiries</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 60px;">#</th>
                                                <th style="min-width: 200px;">Customer Info</th>
                                                <th style="min-width: 160px;">Subject</th>
                                                <th style="min-width: 260px;">Message Snippet</th>
                                                <th style="width: 140px;">Status</th>
                                                <th style="width: 130px;">Received</th>
                                                <th class="text-end" style="min-width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inquiries as $row): 
                                                $statusInfo = $STATUS_LABELS[$row['status']] ?? ['label' => ucfirst($row['status']), 'badge' => 'bg-light text-dark border', 'icon' => 'fa-info-circle'];
                                                $initials = strtoupper(substr($row['name'], 0, 2));
                                                $cleanPhone = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
                                                if (strlen($cleanPhone) === 10) {
                                                    $cleanPhone = '91' . $cleanPhone;
                                                }
                                                $waText = urlencode("Hi " . $row['name'] . ", this is Printmont Support regarding your inquiry: \"" . ($row['subject'] ?: 'Customer Inquiry') . "\". How can we assist you?");
                                                $waLink = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text={$waText}" : "";
                                            ?>
                                                <tr>
                                                    <td class="text-muted fw-semibold">#<?php echo (int)$row['id']; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-circle me-2 flex-shrink-0">
                                                                <?php echo htmlspecialchars($initials); ?>
                                                            </div>
                                                            <div class="text-truncate">
                                                                <strong class="text-dark d-block text-truncate" style="max-width: 180px;">
                                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                                </strong>
                                                                <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-muted small text-decoration-none text-truncate d-block" style="max-width: 180px;">
                                                                    <i class="fas fa-envelope text-primary me-1"></i><?php echo htmlspecialchars($row['email']); ?>
                                                                </a>
                                                                <?php if (!empty($row['phone'])): ?>
                                                                    <span class="text-muted small d-block">
                                                                        <i class="fas fa-phone text-secondary me-1"></i><?php echo htmlspecialchars($row['phone']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong class="text-dark small d-block">
                                                            <?php echo htmlspecialchars($row['subject'] ?: 'No Subject'); ?>
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <div class="msg-cell small">
                                                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                                        </div>
                                                        <?php if (!empty($row['admin_notes'])): ?>
                                                            <small class="text-primary mt-1 d-block">
                                                                <i class="fas fa-sticky-note me-1"></i> Note: <?php echo htmlspecialchars(mb_strimwidth($row['admin_notes'], 0, 45, '...')); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $statusInfo['badge']; ?> py-1 px-2 small">
                                                            <i class="fas <?php echo $statusInfo['icon']; ?> me-1"></i><?php echo $statusInfo['label']; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small" style="white-space: nowrap;">
                                                        <div><?php echo date('d M Y', strtotime($row['created_at'])); ?></div>
                                                        <div style="font-size: 11px;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <!-- View Details Modal Trigger -->
                                                            <button type="button" 
                                                                    class="btn btn-outline-primary" 
                                                                    title="View &amp; Manage" 
                                                                    onclick='openInquiryModal(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                                                <i class="fas fa-eye"></i>
                                                            </button>

                                                            <!-- Direct WhatsApp Chat -->
                                                            <?php if (!empty($waLink)): ?>
                                                                <a href="<?php echo $waLink; ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-outline-success" 
                                                                   title="WhatsApp Customer">
                                                                    <i class="fab fa-whatsapp"></i>
                                                                </a>
                                                            <?php endif; ?>

                                                            <!-- Direct Email -->
                                                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>?subject=<?php echo urlencode('Regarding your inquiry: ' . ($row['subject'] ?: 'Printmont Inquiry')); ?>" 
                                                               class="btn btn-outline-secondary" 
                                                               title="Send Email">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </a>

                                                            <!-- Delete -->
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger" 
                                                                    title="Delete Inquiry"
                                                                    onclick="confirmDelete(<?php echo (int)$row['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer bg-white border-top py-3 text-muted small d-flex align-items-center justify-content-between">
                                    <span>Showing <strong><?php echo count($inquiries); ?></strong> inquiries</span>
                                    <span>Tip: Click <i class="fas fa-eye text-primary"></i> to read full message, update status, and add internal notes.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Inquiry Details & Status Modal -->
    <div class="modal fade" id="inquiryDetailModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="contact-inquiries.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="inquiry_id" id="modalInquiryId" value="">
                    <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($searchQuery); ?>">

                    <div class="modal-header bg-light border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2" id="modalInquiryBadge">#0</span>
                            <h5 class="modal-title fw-bold mb-0" id="inquiryModalLabel">Inquiry Details</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Customer Overview Header -->
                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Customer Name</small>
                                    <strong class="text-dark fs-6" id="modalCustomerName">—</strong>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted d-block">Received Date</small>
                                    <span class="text-dark fw-semibold" id="modalReceivedDate">—</span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Email Address</small>
                                    <a href="#" id="modalEmailLink" class="fw-semibold text-primary text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i><span id="modalCustomerEmail">—</span>
                                    </a>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <small class="text-muted d-block">Phone Number</small>
                                    <span id="modalCustomerPhone" class="fw-semibold text-dark">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold mb-1">Subject</label>
                            <div class="p-2 bg-light rounded border fw-semibold text-dark" id="modalSubject">
                                —
                            </div>
                        </div>

                        <!-- Full Message -->
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold mb-1">Full Message</label>
                            <div class="p-3 bg-white rounded border" style="min-height: 100px; white-space: pre-line; line-height: 1.6; color: #334155;" id="modalMessage">
                                —
                            </div>
                        </div>

                        <!-- Quick Communication Actions -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="#" id="modalDirectEmailBtn" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i> Reply via Email
                            </a>
                            <a href="#" id="modalDirectWaBtn" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="fab fa-whatsapp me-1"></i> Chat on WhatsApp
                            </a>
                        </div>

                        <hr class="my-3">

                        <!-- Status & Internal Notes -->
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark">Update Status <span class="text-danger">*</span></label>
                                <select name="status" id="modalStatusSelect" class="form-select" required>
                                    <option value="new">New (Unread)</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                    <option value="read">Read</option>
                                    <option value="replied">Replied</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-dark">Internal Admin Notes</label>
                                <textarea name="admin_notes" id="modalAdminNotes" rows="3" class="form-control" placeholder="Add internal notes on resolution, phone calls, or customer requirements..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" action="contact-inquiries.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="inquiry_id" id="deleteInquiryId" value="">
        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($statusFilter); ?>">
        <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($searchQuery); ?>">
    </form>

    <script src="js/app.js"></script>
    <script>
        const inquiryModal = new bootstrap.Modal(document.getElementById('inquiryDetailModal'));

        function openInquiryModal(row) {
            document.getElementById('modalInquiryId').value = row.id;
            document.getElementById('modalInquiryBadge').textContent = '#' + row.id;
            document.getElementById('modalCustomerName').textContent = row.name || 'Anonymous';
            document.getElementById('modalCustomerEmail').textContent = row.email || '—';
            document.getElementById('modalEmailLink').href = 'mailto:' + encodeURIComponent(row.email || '');
            document.getElementById('modalCustomerPhone').textContent = row.phone || 'Not Provided';
            document.getElementById('modalReceivedDate').textContent = row.created_at || '—';
            document.getElementById('modalSubject').textContent = row.subject || 'No Subject';
            document.getElementById('modalMessage').textContent = row.message || '';
            document.getElementById('modalStatusSelect').value = row.status || 'new';
            document.getElementById('modalAdminNotes').value = row.admin_notes || '';

            // Email button
            const mailSubject = encodeURIComponent('Regarding your inquiry: ' + (row.subject || 'Printmont Support'));
            document.getElementById('modalDirectEmailBtn').href = 'mailto:' + encodeURIComponent(row.email || '') + '?subject=' + mailSubject;

            // WhatsApp button
            let phoneClean = (row.phone || '').replace(/[^0-9]/g, '');
            if (phoneClean.length === 10) {
                phoneClean = '91' + phoneClean;
            }
            const waBtn = document.getElementById('modalDirectWaBtn');
            if (phoneClean.length >= 10) {
                const waText = encodeURIComponent('Hi ' + row.name + ', this is Printmont Support regarding your inquiry: "' + (row.subject || 'Inquiry') + '".');
                waBtn.href = 'https://wa.me/' + phoneClean + '?text=' + waText;
                waBtn.classList.remove('d-none');
            } else {
                waBtn.classList.add('d-none');
            }

            inquiryModal.show();
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to permanently delete Inquiry #' + id + '?')) {
                document.getElementById('deleteInquiryId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
