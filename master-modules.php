<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'models/MasterModule.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$masterModule = new MasterModule($db);

// AJAX Handlers
if (isset($_GET['ajax'])) {
    $ajaxAction = $_GET['ajax'];
    header('Content-Type: application/json; charset=utf-8');

    if ($ajaxAction === 'get_module') {
        $id = (int)($_GET['id'] ?? 0);
        $module = $masterModule->getById($id);
        if ($module) {
            echo json_encode(['success' => true, 'module' => $module]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Module not found.']);
        }
        exit;
    }

    if ($ajaxAction === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $masterModule->toggleStatus($id);
        if ($newStatus !== false) {
            echo json_encode(['success' => true, 'status' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to toggle status.']);
        }
        exit;
    }
}

// POST Form Submissions (Create, Edit, Delete, Duplicate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'module_name'  => trim($_POST['module_name'] ?? ''),
            'module_type'  => $_POST['module_type'] ?? 'banner',
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
            'is_sponsored' => !empty($_POST['is_sponsored']) ? 1 : 0,
            'status'       => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive',
            'design_data'  => trim($_POST['design_data'] ?? '{}')
        ];

        if (!empty($data['module_name'])) {
            if ($masterModule->create($data)) {
                $_SESSION['success_message'] = "Module \"{$data['module_name']}\" created successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to create module in database.";
            }
        } else {
            $_SESSION['error_message'] = "Please enter a valid module name.";
        }
        header("Location: master-modules.php");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'module_name'  => trim($_POST['module_name'] ?? ''),
            'module_type'  => $_POST['module_type'] ?? 'banner',
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
            'is_sponsored' => !empty($_POST['is_sponsored']) ? 1 : 0,
            'status'       => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive',
            'design_data'  => trim($_POST['design_data'] ?? '{}')
        ];

        if ($id > 0 && !empty($data['module_name'])) {
            if ($masterModule->update($id, $data)) {
                $_SESSION['success_message'] = "Module #{$id} \"{$data['module_name']}\" updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update module.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid module ID or missing required name.";
        }
        header("Location: master-modules.php");
        exit;
    }

    if ($action === 'duplicate') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $masterModule->duplicate($id)) {
            $_SESSION['success_message'] = "Module duplicated successfully as a draft!";
        } else {
            $_SESSION['error_message'] = "Could not duplicate module.";
        }
        header("Location: master-modules.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $masterModule->delete($id)) {
            $_SESSION['success_message'] = "Module #{$id} deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Could not delete module.";
        }
        header("Location: master-modules.php");
        exit;
    }
}

$modules = $masterModule->getAll();

// KPI Counters
$totalModules = count($modules);
$activeModules = 0;
$sponsoredModules = 0;
$typeCounts = [
    'slider' => 0,
    'category' => 0,
    'carousel' => 0,
    'banner' => 0,
    'banner_carousel' => 0
];

foreach ($modules as $m) {
    if ($m['status'] === 'active') $activeModules++;
    if (!empty($m['is_sponsored'])) $sponsoredModules++;
    if (isset($typeCounts[$m['module_type']])) {
        $typeCounts[$m['module_type']]++;
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
    <title>Master Modules | Printmont Admin</title>

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

        .type-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .type-slider { background-color: #e0f2fe; color: #0369a1; }
        .type-category { background-color: #fef3c7; color: #b45309; }
        .type-carousel { background-color: #ede9fe; color: #6d28d9; }
        .type-banner { background-color: #dbeafe; color: #1e40af; }
        .type-banner_carousel { background-color: #fce7f3; color: #be185d; }

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

        .sponsored-badge {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #b45309;
            border: 1px solid #fde68a;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .filter-btn.active {
            background-color: var(--pm-primary) !important;
            color: #ffffff !important;
            border-color: var(--pm-primary) !important;
        }

        .table > :not(caption) > * > * {
            padding: 14px 18px;
            border-bottom-color: #f1f5f9;
        }

        /* Switch Toggle Styling */
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

        .code-box {
            background: #0f172a;
            color: #38bdf8;
            border-radius: 8px;
            padding: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 13px;
            max-height: 380px;
            overflow: auto;
            line-height: 1.5;
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
                                    UI & LAYOUT BUILDER
                                </span>
                                <span class="text-muted fs-sm">• Dynamic Storefront Modules</span>
                            </div>
                            <h2 class="h3 font-weight-bold text-dark mt-1 mb-0">Master Modules Management</h2>
                            <p class="text-muted mb-0">Configure dynamic carousels, sliders, category showcases, and promotional banner layouts.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModuleModal" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                <i class="fa-solid fa-plus me-1"></i> Add New Module
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
                                        <span class="text-muted fw-semibold fs-sm">Total Modules</span>
                                        <h3 class="fw-bold mb-0 mt-1 text-dark"><?php echo $totalModules; ?></h3>
                                        <small class="text-muted">Master layouts</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="fa-solid fa-layer-group"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Active On Store</span>
                                        <h3 class="fw-bold mb-0 mt-1 text-success"><?php echo $activeModules; ?></h3>
                                        <small class="text-muted"><?php echo $totalModules > 0 ? round(($activeModules / $totalModules) * 100) : 0; ?>% live coverage</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #ecfdf5; color: #10b981;">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Sponsored / Promos</span>
                                        <h3 class="fw-bold mb-0 mt-1 text-warning"><?php echo $sponsoredModules; ?></h3>
                                        <small class="text-muted">Special placements</small>
                                    </div>
                                    <div class="stat-icon" style="background-color: #fffbeb; color: #f59e0b;">
                                        <i class="fa-solid fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-lg-3">
                            <div class="stat-card p-3 p-xl-4 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted fw-semibold fs-sm">Types Distribution</span>
                                        <div class="d-flex gap-1 mt-1 flex-wrap">
                                            <span class="badge bg-light text-dark border fs-xs">Sliders: <?php echo $typeCounts['slider']; ?></span>
                                            <span class="badge bg-light text-dark border fs-xs">Grids: <?php echo $typeCounts['category']; ?></span>
                                            <span class="badge bg-light text-dark border fs-xs">Banners: <?php echo $typeCounts['banner'] + $typeCounts['banner_carousel']; ?></span>
                                        </div>
                                    </div>
                                    <div class="stat-icon" style="background-color: #f1f5f9; color: var(--pm-primary);">
                                        <i class="fa-solid fa-chart-pie"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modules Management Card -->
                    <div class="custom-card">
                        <div class="custom-card-header">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-12 col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input type="text" id="moduleSearch" class="form-control border-start-0 ps-0" placeholder="Search modules by name...">
                                    </div>
                                </div>

                                <div class="col-12 col-md-8 d-flex justify-content-md-end gap-2 flex-wrap">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">All (<?php echo $totalModules; ?>)</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="slider">Sliders</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="category">Categories</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="carousel">Carousels</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="banner">Banners</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="sponsored">Sponsored</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="custom-card-body p-0">
                            <?php if (empty($modules)): ?>
                                <div class="text-center py-5">
                                    <div class="stat-icon mx-auto mb-3" style="background: #f1f5f9; color: #94a3b8; width: 64px; height: 64px; font-size: 28px;">
                                        <i class="fa-solid fa-layer-group"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No Master Modules Found</h5>
                                    <p class="text-muted mb-3" style="max-width: 480px; margin: 0 auto;">
                                        Create your first dynamic master module to control carousels, sliders, category pills, or promo banners on the frontend.
                                    </p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                                        <i class="fa-solid fa-plus me-1"></i> Add First Module
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="modulesTable">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4" style="width: 35%;">Module Details</th>
                                                <th>Type</th>
                                                <th class="text-center">Order</th>
                                                <th>Sponsored</th>
                                                <th>Status</th>
                                                <th>Last Updated</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($modules as $m): 
                                            $typeIcon = 'fa-border-all';
                                            if ($m['module_type'] === 'slider') $typeIcon = 'fa-sliders';
                                            if ($m['module_type'] === 'carousel') $typeIcon = 'fa-cubes';
                                            if ($m['module_type'] === 'banner') $typeIcon = 'fa-rectangle-ad';
                                            if ($m['module_type'] === 'banner_carousel') $typeIcon = 'fa-images';

                                            $isSponsored = !empty($m['is_sponsored']);
                                        ?>
                                            <tr class="module-row" 
                                                data-name="<?php echo strtolower($m['module_name']); ?>"
                                                data-type="<?php echo $m['module_type']; ?>"
                                                data-sponsored="<?php echo $isSponsored ? '1' : '0'; ?>"
                                                data-status="<?php echo $m['status']; ?>"
                                                id="row-module-<?php echo $m['id']; ?>">
                                                
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="stat-icon flex-shrink-0" style="width: 40px; height: 40px; font-size: 16px; background: #f8fafc; border: 1px solid #e2e8f0; color: var(--pm-primary);">
                                                            <i class="fa-solid <?php echo $typeIcon; ?>"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($m['module_name']); ?></div>
                                                            <div class="text-muted fs-xs font-monospace">ID: #<?php echo $m['id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="type-badge type-<?php echo $m['module_type']; ?>">
                                                        <i class="fa-solid <?php echo $typeIcon; ?>"></i>
                                                        <?php echo ucwords(str_replace('_', ' ', $m['module_type'])); ?>
                                                    </span>
                                                </td>

                                                <td class="text-center font-monospace fw-bold text-muted">
                                                    <?php echo (int)$m['sort_order']; ?>
                                                </td>

                                                <td>
                                                    <?php if ($isSponsored): ?>
                                                        <span class="sponsored-badge">
                                                            <i class="fa-solid fa-star text-warning"></i> Sponsored
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted fs-sm">Standard</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <div class="form-check form-switch d-flex align-items-center gap-2">
                                                        <input class="form-check-input status-toggle" type="checkbox" 
                                                               data-id="<?php echo $m['id']; ?>" 
                                                               <?php echo $m['status'] === 'active' ? 'checked' : ''; ?>>
                                                        <span class="status-badge status-<?php echo $m['status']; ?> status-text-<?php echo $m['id']; ?>">
                                                            <?php echo ucfirst($m['status']); ?>
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="fs-sm text-muted">
                                                    <?php echo date('M d, Y H:i', strtotime($m['updated_at'])); ?>
                                                </td>

                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-info preview-module-btn" 
                                                                data-id="<?php echo $m['id']; ?>" 
                                                                title="Preview Module Design">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-module-btn" 
                                                                data-id="<?php echo $m['id']; ?>" 
                                                                title="Edit Module">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>

                                                        <form method="post" action="master-modules.php" class="d-inline"
                                                              onsubmit="return confirm('Clone module \'<?php echo htmlspecialchars($m['module_name']); ?>\'?');">
                                                            <input type="hidden" name="action" value="duplicate">
                                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Duplicate / Clone">
                                                                <i class="fa-regular fa-copy"></i>
                                                            </button>
                                                        </form>

                                                        <form method="post" action="master-modules.php" class="d-inline"
                                                              onsubmit="return confirm('Delete module \'<?php echo htmlspecialchars($m['module_name']); ?>\' permanently?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Module">
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
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Create Module Modal -->
    <div class="modal fade" id="createModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form method="post" action="master-modules.php">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fa-solid fa-plus-circle me-2"></i> Add New Master Module
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark">Module Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="module_name" placeholder="e.g. Festive Mega Sale Slider" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Module Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="module_type" id="create_module_type">
                                    <option value="slider">Hero Slider</option>
                                    <option value="category">Category Grid / Pills</option>
                                    <option value="carousel">Product Carousel</option>
                                    <option value="banner">Static Banner</option>
                                    <option value="banner_carousel">Multi-Banner Carousel</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="0">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_sponsored" value="1" id="create_sponsored">
                                    <label class="form-check-label fw-bold text-dark ms-2" for="create_sponsored">Sponsored Placement</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold text-dark mb-0">Design Configuration JSON</label>
                                    <button type="button" class="btn btn-link btn-sm text-primary p-0 text-decoration-none" id="loadPresetBtn">
                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Load Type Preset
                                    </button>
                                </div>
                                <textarea class="form-control font-monospace" name="design_data" id="create_design_data" rows="8" placeholder='{ "autoplay": true, "slides": [...] }'></textarea>
                                <small class="text-muted">Structured styling parameters, slides, image URLs, CTA targets, or product queries.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Module
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Module Modal -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <form method="post" action="master-modules.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="modal-header bg-dark text-white py-3">
                        <h5 class="modal-title text-white fw-bold mb-0">
                            <i class="fa-solid fa-pen-to-square me-2 text-info"></i> Edit Master Module
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark">Module Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="module_name" id="edit_module_name" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Module Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="module_type" id="edit_module_type">
                                    <option value="slider">Hero Slider</option>
                                    <option value="category">Category Grid / Pills</option>
                                    <option value="carousel">Product Carousel</option>
                                    <option value="banner">Static Banner</option>
                                    <option value="banner_carousel">Multi-Banner Carousel</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_sponsored" value="1" id="edit_sponsored">
                                    <label class="form-check-label fw-bold text-dark ms-2" for="edit_sponsored">Sponsored Placement</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-dark">Design Configuration JSON</label>
                                <textarea class="form-control font-monospace" name="design_data" id="edit_design_data" rows="8"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Module
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Module Modal -->
    <div class="modal fade" id="previewModuleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-layer-group text-info"></i>
                        <h5 class="modal-title font-monospace fs-6 mb-0 text-white" id="previewModalTitle">Module Preview</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Dynamic Simulated Banner Preview -->
                    <div id="previewCardSimulation" class="mb-4 p-4 rounded-3 shadow-sm bg-white border"></div>

                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-code me-1 text-primary"></i> JSON Design Payload:</h6>
                    <pre class="code-box m-0" id="previewJsonContent"></pre>
                </div>
                <div class="modal-footer bg-white py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close Preview</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const PRESETS = {
            slider: {
                autoplay: true,
                autoplay_speed: 5000,
                slides: [
                    { title: "Personalized Corporate Gifts", subtitle: "Bulk customized merchandise", image: "/uploads/slider1.jpg", link: "/categories/corporate", button_text: "Shop Now" },
                    { title: "Custom Apparel Printing", subtitle: "High definition prints", image: "/uploads/slider2.jpg", link: "/categories/apparel", button_text: "Explore" }
                ]
            },
            category: {
                layout: "circle_grid",
                columns: 6,
                show_count: true,
                categories: ["Drinkware", "Stationery", "Apparel", "Tech Accessories", "Stickers", "Boxes"]
            },
            carousel: {
                display_source: "bestseller_products",
                limit: 8,
                show_rating: true,
                show_quick_view: true
            },
            banner: {
                headline: "Flat 20% Off on Bulk Orders",
                description: "Customized promotional gifts for corporate events and festivals.",
                cta_text: "Get Quote",
                cta_link: "/bulk-inquiry",
                bg_gradient: "linear-gradient(135deg, #0B53A1 0%, #032b57 100%)"
            },
            banner_carousel: {
                items_per_view: 3,
                gap: 20,
                cards: [
                    { title: "Special Deal", code: "SAVE20", bg: "#f0fdf4" },
                    { title: "Free Shipping", code: "FREESHIP", bg: "#eff6ff" }
                ]
            }
        };

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
            // Live Search & Filtering
            const searchInput = document.getElementById('moduleSearch');
            const rows = document.querySelectorAll('.module-row');
            const filterBtns = document.querySelectorAll('.filter-btn');

            let currentFilter = 'all';

            function filterRows() {
                const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const type = row.getAttribute('data-type') || '';
                    const sponsored = row.getAttribute('data-sponsored') === '1';

                    const matchesQuery = !query || name.includes(query);
                    let matchesFilter = (currentFilter === 'all') || (type === currentFilter);
                    if (currentFilter === 'sponsored') {
                        matchesFilter = sponsored;
                    }

                    if (matchesQuery && matchesFilter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (searchInput) searchInput.addEventListener('input', filterRows);

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    filterRows();
                });
            });

            // Preset loader for Create modal
            const typeSelect = document.getElementById('create_module_type');
            const designTextarea = document.getElementById('create_design_data');
            const loadPresetBtn = document.getElementById('loadPresetBtn');

            function loadPreset() {
                const selected = typeSelect.value;
                if (PRESETS[selected]) {
                    designTextarea.value = JSON.stringify(PRESETS[selected], null, 2);
                }
            }

            if (loadPresetBtn) loadPresetBtn.addEventListener('click', loadPreset);
            if (typeSelect) {
                typeSelect.addEventListener('change', () => {
                    if (!designTextarea.value || designTextarea.value === '{}') {
                        loadPreset();
                    }
                });
                loadPreset();
            }

            // AJAX Toggle Status
            document.querySelectorAll('.status-toggle').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const id = this.getAttribute('data-id');
                    const isChecked = this.checked;
                    const badge = document.querySelector('.status-text-' + id);

                    const formData = new FormData();
                    formData.append('id', id);

                    fetch('master-modules.php?ajax=toggle_status', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            badge.className = 'status-badge status-' + data.status + ' status-text-' + id;
                            badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                            showToast(`Module #${id} status changed to ${data.status}`);
                        } else {
                            this.checked = !isChecked;
                            showToast(data.error || 'Failed to update status', false);
                        }
                    })
                    .catch(() => {
                        this.checked = !isChecked;
                        showToast('Network error updating status', false);
                    });
                });
            });

            // Edit Module AJAX fetch
            const editModal = new bootstrap.Modal(document.getElementById('editModuleModal'));
            document.querySelectorAll('.edit-module-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    fetch('master-modules.php?ajax=get_module&id=' + id)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const m = data.module;
                                document.getElementById('edit_id').value = m.id;
                                document.getElementById('edit_module_name').value = m.module_name;
                                document.getElementById('edit_module_type').value = m.module_type;
                                document.getElementById('edit_sort_order').value = m.sort_order;
                                document.getElementById('edit_status').value = m.status;
                                document.getElementById('edit_sponsored').checked = (m.is_sponsored == 1);
                                
                                try {
                                    const parsed = JSON.parse(m.design_data || '{}');
                                    document.getElementById('edit_design_data').value = JSON.stringify(parsed, null, 2);
                                } catch(e) {
                                    document.getElementById('edit_design_data').value = m.design_data || '{}';
                                }

                                editModal.show();
                            } else {
                                showToast(data.error || 'Could not fetch module details', false);
                            }
                        });
                });
            });

            // Preview Module Simulation
            const previewModal = new bootstrap.Modal(document.getElementById('previewModuleModal'));
            document.querySelectorAll('.preview-module-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    fetch('master-modules.php?ajax=get_module&id=' + id)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const m = data.module;
                                document.getElementById('previewModalTitle').textContent = `[${m.module_type.toUpperCase()}] ${m.module_name}`;

                                let jsonObj = {};
                                try {
                                    jsonObj = JSON.parse(m.design_data || '{}');
                                    document.getElementById('previewJsonContent').textContent = JSON.stringify(jsonObj, null, 2);
                                } catch(e) {
                                    document.getElementById('previewJsonContent').textContent = m.design_data;
                                }

                                // Visual Render Simulation
                                const simContainer = document.getElementById('previewCardSimulation');
                                if (m.module_type === 'banner') {
                                    simContainer.innerHTML = `
                                        <div style="background: ${jsonObj.bg_gradient || '#0B53A1'}; color: #fff; padding: 30px; border-radius: 10px; text-align: center;">
                                            <h4 style="font-weight: 800; margin-bottom: 8px;">${jsonObj.headline || m.module_name}</h4>
                                            <p style="opacity: 0.9; margin-bottom: 20px;">${jsonObj.description || 'Custom printing tailored to your business needs.'}</p>
                                            <button class="btn btn-warning fw-bold px-4 py-2">${jsonObj.cta_text || 'Learn More'}</button>
                                        </div>
                                    `;
                                } else if (m.module_type === 'slider') {
                                    simContainer.innerHTML = `
                                        <div style="background: #0B53A1; color: #fff; padding: 36px 24px; border-radius: 10px; text-align: center; position: relative;">
                                            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-3 fw-bold">HERO SLIDE 1</span>
                                            <h3 style="font-weight: 800;">${(jsonObj.slides && jsonObj.slides[0]?.title) || m.module_name}</h3>
                                            <p style="opacity: 0.85;">${(jsonObj.slides && jsonObj.slides[0]?.subtitle) || 'High definition custom merchandise'}</p>
                                            <button class="btn btn-light fw-bold mt-2">Explore Collection</button>
                                        </div>
                                    `;
                                } else if (m.module_type === 'category') {
                                    const cats = jsonObj.categories || ['Category 1', 'Category 2', 'Category 3', 'Category 4'];
                                    simContainer.innerHTML = `
                                        <div class="text-center mb-3 fw-bold text-dark">Category Grid Display (${cats.length} items)</div>
                                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                                            ${cats.map(c => `<div class="p-3 bg-light border rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 11px; font-weight: 700; color: #0B53A1;">${c}</div>`).join('')}
                                        </div>
                                    `;
                                } else {
                                    simContainer.innerHTML = `
                                        <div class="p-4 bg-light border rounded text-center">
                                            <i class="fa-solid fa-cubes fs-1 text-primary mb-2"></i>
                                            <h5 class="fw-bold text-dark">${m.module_name}</h5>
                                            <p class="text-muted fs-sm mb-0">Interactive Swiper Carousel (${jsonObj.limit || 8} items from ${jsonObj.display_source || 'database'})</p>
                                        </div>
                                    `;
                                }

                                previewModal.show();
                            }
                        });
                });
            });
        });
    </script>
</body>
</html>
