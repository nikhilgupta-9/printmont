<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'models/Watermark.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$watermarkModel = new Watermark($db);

// AJAX Endpoints
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxAction = $_GET['ajax'];

    if ($ajaxAction === 'get_watermark') {
        $id = (int)($_GET['id'] ?? 0);
        $wm = $watermarkModel->getById($id);
        if ($wm) {
            echo json_encode(['success' => true, 'watermark' => $wm]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Watermark not found.']);
        }
        exit;
    }

    if ($ajaxAction === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $watermarkModel->toggleStatus($id);
        if ($newStatus !== false) {
            echo json_encode(['success' => true, 'status' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not toggle status.']);
        }
        exit;
    }

    if ($ajaxAction === 'set_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($watermarkModel->setActive($id)) {
            echo json_encode(['success' => true, 'message' => "Watermark #{$id} set as active!"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to set active.']);
        }
        exit;
    }

    if ($ajaxAction === 'clear_cache') {
        $cacheDir = __DIR__ . '/uploads/cache/watermarked/';
        $count = 0;
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                    $count++;
                }
            }
        }
        echo json_encode(['success' => true, 'cleared_count' => $count, 'message' => "Cleared {$count} cached watermarked image(s)."]);
        exit;
    }
}

// POST Form Submissions (Create, Edit, Delete, Quick Save Designer)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $file = $_FILES['image'] ?? null;
        if ($watermarkModel->create($_POST, $file)) {
            $_SESSION['success_message'] = "Watermark preset created successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to create watermark in database.";
        }
        header("Location: watermarks.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $file = $_FILES['image'] ?? null;
        if ($id > 0 && $watermarkModel->update($id, $_POST, $file)) {
            $_SESSION['success_message'] = "Watermark #{$id} updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update watermark configuration.";
        }
        header("Location: watermarks.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $watermarkModel->delete($id)) {
            $_SESSION['success_message'] = "Watermark #{$id} deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Could not delete watermark.";
        }
        header("Location: watermarks.php");
        exit;
    }

    if ($action === 'quick_save_active') {
        $active = $watermarkModel->getActiveWatermark();
        $data = [
            'title' => trim($_POST['title'] ?? ($active['title'] ?? 'Printmont Brand Copyright')),
            'type' => $_POST['type'] ?? 'text',
            'watermark_text' => trim($_POST['watermark_text'] ?? '© Printmont • www.printmont.com'),
            'font_color' => $_POST['font_color'] ?? '#FFFFFF',
            'font_size' => (int)($_POST['font_size'] ?? 24),
            'opacity' => (int)($_POST['opacity'] ?? 65),
            'position' => $_POST['position'] ?? 'bottom_right',
            'is_tiled' => !empty($_POST['is_tiled']) ? 1 : 0,
            'status' => 'active'
        ];

        if ($active) {
            $watermarkModel->update($active['id'], $data);
            $_SESSION['success_message'] = "Active watermark updated and live protection refreshed!";
        } else {
            $watermarkModel->create($data);
            $_SESSION['success_message'] = "New active watermark created and live protection refreshed!";
        }
        header("Location: watermarks.php");
        exit;
    }
}

$allWatermarks = $watermarkModel->getAll();
$activeWatermark = $watermarkModel->getActiveWatermark();

// Sample product images available in store for preview
$productSamples = [];
$prodDir = __DIR__ . '/uploads/products/';
if (is_dir($prodDir)) {
    $files = glob($prodDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $f) {
        $productSamples[] = 'uploads/products/' . basename($f);
        if (count($productSamples) >= 8) break;
    }
}

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
    <title>Watermark & Image Anti-Theft Protection | Printmont Admin</title>

    <!-- Rule 1: Dynamic Brand Favicon -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-primary-dark: #083c75;
            --pm-primary-light: #e8f0fe;
            --pm-accent: #f8991d;
            --pm-success: #10b981;
            --pm-danger: #ef4444;
            --pm-warning: #f59e0b;
            --pm-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .custom-card-header {
            padding: 18px 24px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-card-body {
            padding: 24px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-inactive { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .type-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .type-text { background-color: #e0f2fe; color: #0369a1; }
        .type-image { background-color: #ede9fe; color: #6d28d9; }

        .form-switch .form-check-input {
            width: 2.4em;
            height: 1.25em;
            cursor: pointer;
        }
        .form-switch .form-check-input:checked {
            background-color: var(--pm-primary);
            border-color: var(--pm-primary);
        }

        /* Toast Container */
        #toastContainer {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
        }

        .pm-toast {
            min-width: 280px;
            padding: 12px 18px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Simulator Canvas Styles */
        .simulator-canvas-wrapper {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 380px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
        }

        #simulatorCanvas {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .pos-btn.active {
            background-color: var(--pm-primary) !important;
            color: #fff !important;
            border-color: var(--pm-primary) !important;
        }
    </style>
</head>

<body>
    <div id="toastContainer"></div>

    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: var(--pm-primary-light); color: var(--pm-primary); font-size: 12px; font-weight: 700;">
                                    ANTI-THEFT & BRAND PROTECTION
                                </span>
                                <span class="text-muted fs-sm">• Automated Dynamic Watermarking</span>
                            </div>
                            <h2 class="h3 font-weight-bold text-dark mt-1 mb-0">Watermark Management</h2>
                            <p class="text-muted mb-0">Apply automatic copyright watermarks on images downloaded via Google, browser inspect, or right-click.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="clearCacheBtn">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> Flush Cache
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWatermarkModal" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                <i class="fa-solid fa-plus me-1"></i> New Watermark Preset
                            </button>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                            <i class="fa-solid fa-circle-check fs-5 me-3 text-success"></i>
                            <div class="flex-grow-1"><?php echo $success_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                            <i class="fa-solid fa-circle-exclamation fs-5 me-3 text-danger"></i>
                            <div class="flex-grow-1"><?php echo $error_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Protection Status</span>
                                        <h4 class="fw-bold mb-0 mt-1 <?php echo $activeWatermark ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $activeWatermark ? '<i class="fa-solid fa-shield-check me-1"></i> ACTIVE' : '<i class="fa-solid fa-shield-slash me-1"></i> DISABLED'; ?>
                                        </h4>
                                        <small class="text-muted"><?php echo $activeWatermark ? 'Protected across store' : 'Images served raw'; ?></small>
                                    </div>
                                    <div class="stat-icon" style="background-color: <?php echo $activeWatermark ? '#ecfdf5' : '#fef2f2'; ?>; color: <?php echo $activeWatermark ? '#10b981' : '#ef4444'; ?>;">
                                        <i class="fa-solid <?php echo $activeWatermark ? 'fa-shield-halved' : 'fa-triangle-exclamation'; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Active Preset</span>
                                        <h5 class="fw-bold mb-0 mt-1 text-dark text-truncate" style="max-width: 140px;">
                                            <?php echo $activeWatermark ? htmlspecialchars($activeWatermark['title']) : 'None'; ?>
                                        </h5>
                                        <small class="text-muted"><?php echo $activeWatermark ? ucfirst($activeWatermark['type']) . ' Watermark' : 'Configure below'; ?></small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="fa-solid fa-stamp"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Position & Opacity</span>
                                        <h5 class="fw-bold mb-0 mt-1 text-dark">
                                            <?php echo $activeWatermark ? ($activeWatermark['opacity'] . '% Opacity') : 'N/A'; ?>
                                        </h5>
                                        <small class="text-muted"><?php echo $activeWatermark ? ucwords(str_replace('_', ' ', $activeWatermark['position'])) : 'Inactive'; ?></small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #fef3c7; color: #d97706;">
                                        <i class="fa-solid fa-sliders"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Protection Scope</span>
                                        <h5 class="fw-bold mb-0 mt-1 text-dark font-monospace fs-sm">
                                            Google + Inspect
                                        </h5>
                                        <small class="text-muted">Direct URL, Inspect & Save</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #f1f5f9; color: var(--pm-primary);">
                                        <i class="fa-solid fa-lock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Live Watermark Studio / Designer -->
                    <div class="custom-card border-primary mb-4">
                        <div class="custom-card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles text-primary fs-5"></i>
                                <div>
                                    <h5 class="card-title mb-0 fw-bold">Live Watermark Studio & Interactive Preview</h5>
                                    <small class="text-muted">Adjust text, font size, opacity, and positioning with instant real-time canvas rendering.</small>
                                </div>
                            </div>
                            <form method="post" action="watermarks.php" id="quickSaveForm" class="d-inline">
                                <input type="hidden" name="action" value="quick_save_active">
                                <input type="hidden" name="title" value="Printmont Brand Copyright">
                                <input type="hidden" name="type" id="live_type" value="text">
                                <input type="hidden" name="watermark_text" id="live_form_text" value="<?php echo htmlspecialchars($activeWatermark['watermark_text'] ?? '© Printmont • www.printmont.com'); ?>">
                                <input type="hidden" name="font_color" id="live_form_color" value="<?php echo htmlspecialchars($activeWatermark['font_color'] ?? '#FFFFFF'); ?>">
                                <input type="hidden" name="font_size" id="live_form_size" value="<?php echo $activeWatermark['font_size'] ?? 24; ?>">
                                <input type="hidden" name="opacity" id="live_form_opacity" value="<?php echo $activeWatermark['opacity'] ?? 65; ?>">
                                <input type="hidden" name="position" id="live_form_position" value="<?php echo $activeWatermark['position'] ?? 'bottom_right'; ?>">
                                <input type="hidden" name="is_tiled" id="live_form_tiled" value="<?php echo !empty($activeWatermark['is_tiled']) ? 1 : 0; ?>">
                                
                                <button type="submit" class="btn btn-primary" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Apply & Save Live Watermark
                                </button>
                            </form>
                        </div>

                        <div class="custom-card-body">
                            <div class="row g-4">
                                <!-- Controls Column -->
                                <div class="col-lg-5">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark">Watermark Text</label>
                                        <input type="text" class="form-control" id="sim_text" 
                                               value="<?php echo htmlspecialchars($activeWatermark['watermark_text'] ?? '© Printmont • www.printmont.com'); ?>">
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-dark">Font Size: <span id="val_size" class="text-primary font-monospace"><?php echo $activeWatermark['font_size'] ?? 24; ?>px</span></label>
                                            <input type="range" class="form-range" id="sim_size" min="14" max="52" value="<?php echo $activeWatermark['font_size'] ?? 24; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-dark">Opacity: <span id="val_opacity" class="text-primary font-monospace"><?php echo $activeWatermark['opacity'] ?? 65; ?>%</span></label>
                                            <input type="range" class="form-range" id="sim_opacity" min="10" max="100" value="<?php echo $activeWatermark['opacity'] ?? 65; ?>">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-dark">Text Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="sim_color" 
                                                       value="<?php echo htmlspecialchars($activeWatermark['font_color'] ?? '#FFFFFF'); ?>">
                                                <input type="text" class="form-control font-monospace" id="sim_color_text" 
                                                       value="<?php echo htmlspecialchars($activeWatermark['font_color'] ?? '#FFFFFF'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-dark">Mockup Background</label>
                                            <select class="form-select" id="sim_bg_select">
                                                <option value="mug">Ceramic Coffee Mug</option>
                                                <option value="tshirt">Custom Polo T-Shirt</option>
                                                <option value="backpack">Office Laptop Backpack</option>
                                                <option value="trophy">Crystal Award Trophy</option>
                                                <option value="dark">Dark Gradient Mockup</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark mb-2">Watermark Position</label>
                                        <div class="d-flex gap-1 flex-wrap" id="posBtnGroup">
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo ($activeWatermark['position'] ?? '') === 'top_left' ? 'active' : ''; ?>" data-pos="top_left">Top Left</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo ($activeWatermark['position'] ?? '') === 'top_right' ? 'active' : ''; ?>" data-pos="top_right">Top Right</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo ($activeWatermark['position'] ?? '') === 'center' ? 'active' : ''; ?>" data-pos="center">Center</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo ($activeWatermark['position'] ?? '') === 'bottom_left' ? 'active' : ''; ?>" data-pos="bottom_left">Bottom Left</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo (($activeWatermark['position'] ?? '') === 'bottom_right' || empty($activeWatermark['position'])) ? 'active' : ''; ?>" data-pos="bottom_right">Bottom Right</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary pos-btn <?php echo (!empty($activeWatermark['is_tiled']) || ($activeWatermark['position'] ?? '') === 'repeat') ? 'active' : ''; ?>" data-pos="repeat">
                                                <i class="fa-solid fa-border-none me-1"></i> Diagonal Tile Repeat
                                            </button>
                                        </div>
                                    </div>

                                    <div class="alert alert-info border-0 p-3 mb-0" style="background-color: #f0f9ff; color: #0369a1; font-size: 13px;">
                                        <i class="fa-solid fa-info-circle me-1"></i> <strong>Anti-Theft Delivery:</strong> When anyone inspects element, saves image as, or downloads product images directly from browser/Google, the backend automatically stamps this watermark onto the binary.
                                    </div>
                                </div>

                                <!-- Live Simulator Canvas Column -->
                                <div class="col-lg-7">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-dark fs-sm"><i class="fa-solid fa-display me-1 text-primary"></i> Simulated Image Canvas</span>
                                        <button type="button" class="btn btn-link btn-sm text-primary p-0 text-decoration-none" id="downloadTestPreviewBtn">
                                            <i class="fa-solid fa-download me-1"></i> Test Download Watermarked Image
                                        </button>
                                    </div>
                                    <div class="simulator-canvas-wrapper p-3">
                                        <canvas id="simulatorCanvas" width="600" height="400"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configured Watermarks Preset List -->
                    <div class="custom-card">
                        <div class="custom-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-0 fw-bold">Configured Watermark Presets</h5>
                                <small class="text-muted">Manage alternative branding templates or seasonal campaign watermarks.</small>
                            </div>
                            <span class="badge bg-light text-dark border font-monospace"><?php echo count($allWatermarks); ?> Presets Saved</span>
                        </div>

                        <div class="custom-card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Preset Name</th>
                                            <th>Type</th>
                                            <th>Watermark Preview</th>
                                            <th>Position & Opacity</th>
                                            <th>Protection Status</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($allWatermarks as $wm): 
                                        $isActive = ($wm['status'] === 'active');
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($wm['title']); ?></div>
                                                <div class="text-muted fs-xs font-monospace">ID: #<?php echo $wm['id']; ?></div>
                                            </td>

                                            <td>
                                                <span class="type-badge type-<?php echo $wm['type']; ?>">
                                                    <i class="fa-solid <?php echo $wm['type'] === 'image' ? 'fa-image' : 'fa-font'; ?>"></i>
                                                    <?php echo ucfirst($wm['type']); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($wm['type'] === 'image' && !empty($wm['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($wm['image']); ?>" alt="Logo" style="max-height: 28px; max-width: 100px; background: #0f172a; padding: 2px 6px; border-radius: 4px;">
                                                <?php else: ?>
                                                    <span class="badge font-monospace text-dark border" style="background: #f8fafc; font-size: 12px;">
                                                        <?php echo htmlspecialchars($wm['watermark_text']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div class="fs-sm font-monospace fw-semibold text-dark">
                                                    <?php echo ucwords(str_replace('_', ' ', $wm['position'])); ?>
                                                    <?php if (!empty($wm['is_tiled'])): ?>
                                                        <span class="badge bg-secondary ms-1">Tiled</span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?php echo $wm['opacity']; ?>% Opacity &bull; <?php echo $wm['font_size']; ?>px</small>
                                            </td>

                                            <td>
                                                <div class="form-check form-switch d-flex align-items-center gap-2">
                                                    <input class="form-check-input status-toggle" type="checkbox" 
                                                           data-id="<?php echo $wm['id']; ?>" 
                                                           <?php echo $isActive ? 'checked' : ''; ?>>
                                                    <span class="status-badge status-<?php echo $wm['status']; ?> status-text-<?php echo $wm['id']; ?>">
                                                        <?php echo $isActive ? 'Active Primary' : 'Inactive'; ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="text-end pe-4">
                                                <div class="d-inline-flex gap-1">
                                                    <?php if (!$isActive): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success set-active-btn" 
                                                                data-id="<?php echo $wm['id']; ?>" title="Activate as Storewide Watermark">
                                                            <i class="fa-solid fa-circle-check"></i> Activate
                                                        </button>
                                                    <?php endif; ?>

                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-wm-btn" 
                                                            data-id="<?php echo $wm['id']; ?>" title="Edit Preset">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>

                                                    <form method="post" action="watermarks.php" class="d-inline"
                                                          onsubmit="return confirm('Delete watermark preset \'<?php echo htmlspecialchars($wm['title']); ?>\'?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $wm['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Preset">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Create Watermark Preset Modal -->
    <div class="modal fade" id="createWatermarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form method="post" action="watermarks.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fa-solid fa-plus-circle me-2"></i> Create Watermark Preset
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark">Preset Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" placeholder="e.g. Festival 2026 Special Watermark" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Watermark Type</label>
                                <select class="form-select" name="type" id="modal_create_type">
                                    <option value="text" selected>Text Watermark</option>
                                    <option value="image">PNG Logo / Overlay</option>
                                </select>
                            </div>

                            <div class="col-12" id="create_text_block">
                                <label class="form-label fw-bold text-dark">Watermark Text</label>
                                <input type="text" class="form-control" name="watermark_text" value="© Printmont • www.printmont.com">
                            </div>

                            <div class="col-12" id="create_image_block" style="display: none;">
                                <label class="form-label fw-bold text-dark">Upload Logo Image (Transparent PNG recommended)</label>
                                <input type="file" class="form-control" name="image" accept="image/png,image/jpeg,image/webp">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Position</label>
                                <select class="form-select" name="position">
                                    <option value="bottom_right" selected>Bottom Right</option>
                                    <option value="bottom_left">Bottom Left</option>
                                    <option value="center">Center</option>
                                    <option value="top_right">Top Right</option>
                                    <option value="top_left">Top Left</option>
                                    <option value="repeat">Diagonal Tile Repeat</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Opacity (%)</label>
                                <input type="number" class="form-control" name="opacity" min="10" max="100" value="65">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Font Size (px)</label>
                                <input type="number" class="form-control" name="font_size" min="12" max="72" value="24">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Font Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="font_color" value="#FFFFFF">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Initial Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active (Set as primary)</option>
                                    <option value="inactive" selected>Inactive (Save as preset)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Watermark Preset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Watermark Modal -->
    <div class="modal fade" id="editWatermarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form method="post" action="watermarks.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_wm_id">

                    <div class="modal-header bg-dark text-white py-3">
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fa-solid fa-pen-to-square me-2 text-info"></i> Edit Watermark Preset
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark">Preset Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="edit_wm_title" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Watermark Type</label>
                                <select class="form-select" name="type" id="edit_wm_type">
                                    <option value="text">Text Watermark</option>
                                    <option value="image">PNG Logo / Overlay</option>
                                </select>
                            </div>

                            <div class="col-12" id="edit_text_block">
                                <label class="form-label fw-bold text-dark">Watermark Text</label>
                                <input type="text" class="form-control" name="watermark_text" id="edit_wm_text">
                            </div>

                            <div class="col-12" id="edit_image_block" style="display: none;">
                                <label class="form-label fw-bold text-dark">Replace Logo Image (PNG)</label>
                                <input type="file" class="form-control" name="image" accept="image/png,image/jpeg,image/webp">
                                <div id="edit_current_image" class="mt-2"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Position</label>
                                <select class="form-select" name="position" id="edit_wm_position">
                                    <option value="bottom_right">Bottom Right</option>
                                    <option value="bottom_left">Bottom Left</option>
                                    <option value="center">Center</option>
                                    <option value="top_right">Top Right</option>
                                    <option value="top_left">Top Left</option>
                                    <option value="repeat">Diagonal Tile Repeat</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Opacity (%)</label>
                                <input type="number" class="form-control" name="opacity" id="edit_wm_opacity" min="10" max="100">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Font Size (px)</label>
                                <input type="number" class="form-control" name="font_size" id="edit_wm_size" min="12" max="72">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Font Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="font_color" id="edit_wm_color">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Status</label>
                                <select class="form-select" name="status" id="edit_wm_status">
                                    <option value="active">Active (Primary)</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Preset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showToast(msg, isSuccess = true) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'pm-toast';
            toast.innerHTML = `<i class="fa-solid ${isSuccess ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-danger'} fs-5"></i><span>${msg}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Live Simulator Engine
            const canvas = document.getElementById('simulatorCanvas');
            const ctx = canvas.getContext('2d');

            const textInput = document.getElementById('sim_text');
            const sizeInput = document.getElementById('sim_size');
            const opacityInput = document.getElementById('sim_opacity');
            const colorInput = document.getElementById('sim_color');
            const colorTextInput = document.getElementById('sim_color_text');
            const bgSelect = document.getElementById('sim_bg_select');
            const posBtns = document.querySelectorAll('.pos-btn');

            let currentPos = '<?php echo $activeWatermark['position'] ?? 'bottom_right'; ?>';

            // Background mockups
            const backgrounds = {
                mug: 'uploads/products/ceramic_coffee_mug.jpg',
                tshirt: 'uploads/products/corporate_polo_tshirt.jpg',
                backpack: 'uploads/products/office_laptop_backpack.jpg',
                trophy: 'uploads/products/crystal_award_trophy.jpg',
                dark: 'dark'
            };

            const loadedBgImages = {};

            function loadMockupImage(key, callback) {
                if (key === 'dark') {
                    callback(null);
                    return;
                }
                if (loadedBgImages[key]) {
                    callback(loadedBgImages[key]);
                    return;
                }
                const img = new Image();
                img.src = backgrounds[key];
                img.onload = () => {
                    loadedBgImages[key] = img;
                    callback(img);
                };
                img.onerror = () => callback(null);
            }

            function renderSimulator() {
                const text = textInput.value || '© Printmont • www.printmont.com';
                const fontSize = parseInt(sizeInput.value) || 24;
                const opacity = (parseInt(opacityInput.value) || 65) / 100;
                const color = colorInput.value || '#FFFFFF';
                const bgKey = bgSelect.value;

                // Sync with hidden live form inputs
                document.getElementById('live_form_text').value = text;
                document.getElementById('live_form_size').value = fontSize;
                document.getElementById('live_form_opacity').value = Math.round(opacity * 100);
                document.getElementById('live_form_color').value = color;
                document.getElementById('live_form_position').value = currentPos;
                document.getElementById('live_form_tiled').value = (currentPos === 'repeat') ? 1 : 0;

                loadMockupImage(bgKey, function(bgImg) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (bgImg) {
                        ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
                    } else {
                        // Dark gradient fallback
                        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
                        grad.addColorStop(0, '#0B53A1');
                        grad.addColorStop(1, '#031c38');
                        ctx.fillStyle = grad;
                        ctx.fillRect(0, 0, canvas.width, canvas.height);

                        ctx.fillStyle = 'rgba(255,255,255,0.08)';
                        ctx.font = 'bold 70px "Plus Jakarta Sans"';
                        ctx.textAlign = 'center';
                        ctx.fillText('PRINTMONT PRODUCT', canvas.width / 2, canvas.height / 2);
                    }

                    // Render Watermark
                    ctx.save();
                    ctx.font = `bold ${fontSize}px "Plus Jakarta Sans", sans-serif`;
                    ctx.fillStyle = color;
                    ctx.globalAlpha = opacity;

                    const pad = 24;
                    const textMetrics = ctx.measureText(text);
                    const textW = textMetrics.width;
                    const textH = fontSize;

                    if (currentPos === 'repeat') {
                        ctx.rotate(-25 * Math.PI / 180);
                        for (let x = -canvas.width; x < canvas.width * 2; x += textW + 100) {
                            for (let y = -canvas.height; y < canvas.height * 2; y += textH + 80) {
                                // Shadow
                                ctx.shadowColor = 'rgba(0,0,0,0.6)';
                                ctx.shadowBlur = 4;
                                ctx.fillText(text, x, y);
                            }
                        }
                    } else {
                        let x = canvas.width - textW - pad;
                        let y = canvas.height - pad;

                        if (currentPos === 'top_left') {
                            x = pad;
                            y = pad + textH;
                        } else if (currentPos === 'top_right') {
                            x = canvas.width - textW - pad;
                            y = pad + textH;
                        } else if (currentPos === 'bottom_left') {
                            x = pad;
                            y = canvas.height - pad;
                        } else if (currentPos === 'center') {
                            x = (canvas.width - textW) / 2;
                            y = (canvas.height + textH) / 2;
                        }

                        // Drop shadow for legibility
                        ctx.shadowColor = 'rgba(0,0,0,0.8)';
                        ctx.shadowBlur = 6;
                        ctx.fillText(text, x, y);
                    }
                    ctx.restore();
                });
            }

            // Bind live events
            if (textInput) textInput.addEventListener('input', renderSimulator);
            if (sizeInput) {
                sizeInput.addEventListener('input', function() {
                    document.getElementById('val_size').textContent = this.value + 'px';
                    renderSimulator();
                });
            }
            if (opacityInput) {
                opacityInput.addEventListener('input', function() {
                    document.getElementById('val_opacity').textContent = this.value + '%';
                    renderSimulator();
                });
            }
            if (colorInput) {
                colorInput.addEventListener('input', function() {
                    colorTextInput.value = this.value;
                    renderSimulator();
                });
            }
            if (colorTextInput) {
                colorTextInput.addEventListener('input', function() {
                    colorInput.value = this.value;
                    renderSimulator();
                });
            }
            if (bgSelect) bgSelect.addEventListener('change', renderSimulator);

            posBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    posBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentPos = this.getAttribute('data-pos');
                    renderSimulator();
                });
            });

            // Initial render
            renderSimulator();

            // Download preview test
            const downloadBtn = document.getElementById('downloadTestPreviewBtn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    const link = document.createElement('a');
                    link.download = 'printmont_watermarked_sample.jpg';
                    link.href = canvas.toDataURL('image/jpeg', 0.95);
                    link.click();
                    showToast('Watermarked test sample downloaded!');
                });
            }

            // Clear cache AJAX
            const clearCacheBtn = document.getElementById('clearCacheBtn');
            if (clearCacheBtn) {
                clearCacheBtn.addEventListener('click', function() {
                    fetch('watermarks.php?ajax=clear_cache')
                        .then(res => res.json())
                        .then(data => {
                            showToast(data.message || 'Cache cleared successfully!');
                        });
                });
            }

            // AJAX Toggle Status Switch
            document.querySelectorAll('.status-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const id = this.getAttribute('data-id');
                    const isChecked = this.checked;
                    const badge = document.querySelector('.status-text-' + id);

                    const formData = new FormData();
                    formData.append('id', id);

                    fetch('watermarks.php?ajax=toggle_status', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            badge.className = 'status-badge status-' + data.status + ' status-text-' + id;
                            badge.textContent = data.status === 'active' ? 'Active Primary' : 'Inactive';
                            showToast(`Watermark #${id} status changed to ${data.status}`);
                            setTimeout(() => location.reload(), 800);
                        } else {
                            this.checked = !isChecked;
                            showToast(data.error || 'Failed to update status', false);
                        }
                    });
                });
            });

            // Set Active AJAX
            document.querySelectorAll('.set-active-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const formData = new FormData();
                    formData.append('id', id);

                    fetch('watermarks.php?ajax=set_active', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            setTimeout(() => location.reload(), 600);
                        } else {
                            showToast(data.error || 'Could not set active', false);
                        }
                    });
                });
            });

            // Edit Modal AJAX
            const editModal = new bootstrap.Modal(document.getElementById('editWatermarkModal'));
            document.querySelectorAll('.edit-wm-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    fetch('watermarks.php?ajax=get_watermark&id=' + id)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const wm = data.watermark;
                                document.getElementById('edit_wm_id').value = wm.id;
                                document.getElementById('edit_wm_title').value = wm.title;
                                document.getElementById('edit_wm_type').value = wm.type;
                                document.getElementById('edit_wm_text').value = wm.watermark_text || '';
                                document.getElementById('edit_wm_position').value = wm.position;
                                document.getElementById('edit_wm_opacity').value = wm.opacity;
                                document.getElementById('edit_wm_size').value = wm.font_size;
                                document.getElementById('edit_wm_color').value = wm.font_color || '#FFFFFF';
                                document.getElementById('edit_wm_status').value = wm.status;

                                if (wm.type === 'image') {
                                    document.getElementById('edit_text_block').style.display = 'none';
                                    document.getElementById('edit_image_block').style.display = 'block';
                                    if (wm.image) {
                                        document.getElementById('edit_current_image').innerHTML = `<img src="${wm.image}" style="max-height: 40px; background: #000; padding: 4px; border-radius: 4px;">`;
                                    }
                                } else {
                                    document.getElementById('edit_text_block').style.display = 'block';
                                    document.getElementById('edit_image_block').style.display = 'none';
                                }

                                editModal.show();
                            }
                        });
                });
            });

            // Type toggle in Create Modal
            const createTypeSelect = document.getElementById('modal_create_type');
            if (createTypeSelect) {
                createTypeSelect.addEventListener('change', function() {
                    if (this.value === 'image') {
                        document.getElementById('create_text_block').style.display = 'none';
                        document.getElementById('create_image_block').style.display = 'block';
                    } else {
                        document.getElementById('create_text_block').style.display = 'block';
                        document.getElementById('create_image_block').style.display = 'none';
                    }
                });
            }

            // Type toggle in Edit Modal
            const editTypeSelect = document.getElementById('edit_wm_type');
            if (editTypeSelect) {
                editTypeSelect.addEventListener('change', function() {
                    if (this.value === 'image') {
                        document.getElementById('edit_text_block').style.display = 'none';
                        document.getElementById('edit_image_block').style.display = 'block';
                    } else {
                        document.getElementById('edit_text_block').style.display = 'block';
                        document.getElementById('edit_image_block').style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
