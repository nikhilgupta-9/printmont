<?php
/**
 * All Home Services — Printmont Admin
 * 
 * View, manage, reorder, and toggle home page feature/service highlight cards.
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

$message = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $delStmt = $db->prepare("DELETE FROM home_services WHERE id = ?");
    $delStmt->bind_param('i', $delId);
    if ($delStmt->execute()) {
        $message = "Home service #$delId deleted successfully!";
    } else {
        $error = "Failed to delete service: " . $delStmt->error;
    }
    $delStmt->close();
}

// Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && !empty($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    $tStmt = $db->prepare("UPDATE home_services SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $tStmt->bind_param('i', $toggleId);
    if ($tStmt->execute()) {
        $message = "Service #$toggleId status updated!";
    }
    $tStmt->close();
}

$services = [];
$res = $db->query("SELECT * FROM home_services ORDER BY order_number ASC, id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $services[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home Services | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .service-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-decoration: none;
        }
        .status-badge.active {
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-badge.inactive {
            background-color: #fee2e2;
            color: #b91c1c;
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
                            <h1 class="h3 mb-0"><strong>Home Page</strong> Services</h1>
                            <p class="text-muted small">Manage trust pillars & service feature cards displayed on the home page.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="add-home-service.php" class="btn btn-primary px-3">
                                <i class="fas fa-plus me-1"></i> Add Home Service
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

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Active Home Services (<?php echo count($services); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($services)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-concierge-bell text-muted mb-3" style="font-size: 48px;"></i>
                                    <h5>No Services Configured</h5>
                                    <p class="text-muted small mb-3">Add trust badges such as Free Delivery, Assured Quality, or Design Assistance.</p>
                                    <a href="add-home-service.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Add Service
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">Order</th>
                                                <th>Icon & Service</th>
                                                <th>Description</th>
                                                <th>CTA Button & Link</th>
                                                <th>Status</th>
                                                <th class="text-end" style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $s): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo (int)$s['order_number']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="service-icon-box" style="background-color: <?php echo htmlspecialchars($s['background_color'] ?: '#f1f5f9'); ?>; color: <?php echo htmlspecialchars($s['text_color'] ?: '#0284c7'); ?>;">
                                                                <i class="<?php echo htmlspecialchars($s['icon'] ?: 'fas fa-star'); ?>"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['title']); ?></div>
                                                                <?php if (!empty($s['image'])): ?>
                                                                    <small class="text-muted"><i class="fas fa-image me-1"></i>Custom banner attached</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($s['description'] ?? '', 0, 75, '...')); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($s['button_text'])): ?>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                                <?php echo htmlspecialchars($s['button_text']); ?>
                                                            </span>
                                                            <div style="font-size: 11px;" class="text-muted"><?php echo htmlspecialchars($s['button_url'] ?? '#'); ?></div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="home-services.php?action=toggle&id=<?php echo $s['id']; ?>" class="status-badge <?php echo $s['status']; ?>" title="Click to toggle">
                                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i><?php echo ucfirst($s['status']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="add-home-service.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="home-services.php?action=delete&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service?');" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
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
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
