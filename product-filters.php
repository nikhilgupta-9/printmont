<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// -------------------------------------------------------------
// AJAX HANDLERS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'toggle_filter_status') {
            $filterId = (int)($_POST['filter_id'] ?? 0);
            $currentStatus = $_POST['status'] ?? 'inactive';
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            
            $stmt = $db->prepare("UPDATE product_filters SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $filterId);
            $stmt->execute();
            echo json_encode(['success' => true, 'status' => $newStatus]);
            exit;
        }

        if ($action === 'add_filter') {
            $name = trim($_POST['filter_name'] ?? '');
            $key = trim($_POST['filter_key'] ?? '');
            $type = in_array($_POST['filter_type'] ?? '', ['single', 'multiple']) ? $_POST['filter_type'] : 'multiple';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Filter Name is required.']);
                exit;
            }
            if (empty($key)) {
                $key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
            }

            // Check duplicate key
            $check = $database->fetch("SELECT id FROM product_filters WHERE filter_key = ?", [$key]);
            if ($check) {
                echo json_encode(['success' => false, 'message' => 'A filter with this key already exists.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO product_filters (filter_name, filter_key, filter_type, sort_order, status, filter_slug) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->bind_param("sssis", $name, $key, $type, $sortOrder, $key);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Filter created successfully!']);
            exit;
        }

        if ($action === 'edit_filter') {
            $filterId = (int)($_POST['filter_id'] ?? 0);
            $name = trim($_POST['filter_name'] ?? '');
            $type = in_array($_POST['filter_type'] ?? '', ['single', 'multiple']) ? $_POST['filter_type'] : 'multiple';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($filterId <= 0 || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid filter parameters.']);
                exit;
            }

            $stmt = $db->prepare("UPDATE product_filters SET filter_name = ?, filter_type = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param("ssii", $name, $type, $sortOrder, $filterId);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Filter updated successfully!']);
            exit;
        }

        if ($action === 'delete_filter') {
            $filterId = (int)($_POST['filter_id'] ?? 0);
            if ($filterId > 0) {
                // Delete values first
                $stmtVal = $db->prepare("DELETE FROM product_filter_values WHERE filter_id = ?");
                $stmtVal->bind_param("i", $filterId);
                $stmtVal->execute();

                // Delete filter
                $stmt = $db->prepare("DELETE FROM product_filters WHERE id = ?");
                $stmt->bind_param("i", $filterId);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Filter deleted successfully!']);
                exit;
            }
        }

        if ($action === 'add_value') {
            $filterId = (int)($_POST['filter_id'] ?? 0);
            $valueName = trim($_POST['value_name'] ?? '');
            $valueData = trim($_POST['value_data'] ?? ''); // e.g. hex code or extra info

            if ($filterId <= 0 || empty($valueName)) {
                echo json_encode(['success' => false, 'message' => 'Value name is required.']);
                exit;
            }

            $valueSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $valueName));

            $stmt = $db->prepare("INSERT INTO product_filter_values (filter_id, value_name, value_slug, value_data, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("isss", $filterId, $valueName, $valueSlug, $valueData);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Filter value added successfully!']);
            exit;
        }

        if ($action === 'delete_value') {
            $valId = (int)($_POST['value_id'] ?? 0);
            if ($valId > 0) {
                $stmt = $db->prepare("DELETE FROM product_filter_values WHERE id = ?");
                $stmt->bind_param("i", $valId);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Value deleted successfully!']);
                exit;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// -------------------------------------------------------------
// FETCH FILTERS & VALUES
// -------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];
if (!empty($search)) {
    $where[] = "(f.filter_name LIKE ? OR f.filter_key LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
}
if (!empty($status_filter)) {
    $where[] = "f.status = ?";
    $params[] = $status_filter;
}

$whereClause = implode(" AND ", $where);
$filtersSql = "SELECT f.*, 
               (SELECT COUNT(*) FROM product_filter_values WHERE filter_id = f.id) as values_count
               FROM product_filters f
               WHERE $whereClause
               ORDER BY f.sort_order ASC, f.id ASC";
$filters = $database->fetchAll($filtersSql, $params) ?: [];

// Get all filter values grouped by filter_id
$allValues = $database->fetchAll("SELECT * FROM product_filter_values ORDER BY sort_order ASC, id ASC") ?: [];
$valuesByFilter = [];
foreach ($allValues as $val) {
    $valuesByFilter[$val['filter_id']][] = $val;
}

// -------------------------------------------------------------
// KPI STATS
// -------------------------------------------------------------
$totalFilters = count($database->fetchAll("SELECT id FROM product_filters") ?: []);
$activeFilters = count($database->fetchAll("SELECT id FROM product_filters WHERE status = 'active'") ?: []);
$totalValues = count($allValues);
$mappedProductsCount = $database->fetch("SELECT COUNT(DISTINCT product_id) as cnt FROM product_filter_map")['cnt'] ?? 0;

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
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Product Filters & Attributes | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; }
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .filter-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        .filter-box-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }
        .filter-box-body {
            padding: 1.25rem;
            background: #fafbfc;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .value-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.35rem 0.75rem;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 500;
            color: #334155;
            transition: all 0.2s;
        }
        .value-chip:hover {
            border-color: #94a3b8;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .value-chip .chip-delete {
            color: #94a3b8;
            cursor: pointer;
            margin-left: 2px;
            transition: color 0.15s;
        }
        .value-chip .chip-delete:hover {
            color: #ef4444;
        }
        .action-icon-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            cursor: pointer;
        }
        .action-icon-btn:hover {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }
        .action-icon-btn.btn-delete:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
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
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800"><strong>Product</strong> Filters &amp; Attributes</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="view-products.php">Products</a></li>
                                    <li class="breadcrumb-item active">Filters</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFilterModal">
                                <i class="fas fa-plus me-1"></i> Add Filter Group
                            </button>
                        </div>
                    </div>

                    <!-- KPI Strip -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Total Filter Groups</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($totalFilters); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
                                    <i class="fas fa-circle-check"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Active Groups</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($activeFilters); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Filter Values</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($totalValues); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stat-card-clean">
                                <div class="stat-icon" style="background: #fdf2f8; color: #db2777;">
                                    <i class="fas fa-boxes-stacked"></i>
                                </div>
                                <div>
                                    <span class="text-muted d-block small fw-semibold text-uppercase">Products Tagged</span>
                                    <h3 class="mb-0 fw-bold text-gray-900"><?php echo number_format($mappedProductsCount); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Filter Bar -->
                    <div class="filter-card">
                        <form method="GET" action="product-filters.php" class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control form-control-sm border-start-0" 
                                           placeholder="Search filters (e.g. Color, Material, Brand, Size)..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active Groups Only</option>
                                    <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive Groups Only</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <a href="product-filters.php" class="btn btn-light btn-sm border" title="Reset">
                                    <i class="fas fa-rotate-left"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Filters List -->
                    <div class="row">
                        <?php if (empty($filters)): ?>
                            <div class="col-12">
                                <div class="card p-5 text-center">
                                    <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                                    <h5>No Product Filters Found</h5>
                                    <p class="text-muted">Create filter groups like Color, Size, Material to let customers filter your store.</p>
                                    <div>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFilterModal">
                                            + Add First Filter Group
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($filters as $filter): 
                                $fId = $filter['id'];
                                $vals = $valuesByFilter[$fId] ?? [];
                                $isActive = ($filter['status'] === 'active');
                            ?>
                                <div class="col-lg-6 mb-3" id="filter-box-<?php echo $fId; ?>">
                                    <div class="filter-box">
                                        <div class="filter-box-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.75rem;">
                                                    #<?php echo $filter['sort_order'] ?: $fId; ?>
                                                </span>
                                                <h5 class="mb-0 fw-bold text-dark">
                                                    <?php echo htmlspecialchars($filter['filter_name']); ?>
                                                </h5>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace" style="font-size: 0.7rem;">
                                                    key: <?php echo htmlspecialchars($filter['filter_key']); ?>
                                                </span>
                                                <span class="badge <?php echo $filter['filter_type'] === 'single' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary'; ?> text-uppercase" style="font-size: 0.65rem;">
                                                    <?php echo $filter['filter_type']; ?>
                                                </span>
                                            </div>

                                            <div class="d-flex align-items-center gap-2">
                                                <!-- Status Toggle -->
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input filter-status-toggle" 
                                                           type="checkbox" 
                                                           role="switch" 
                                                           data-id="<?php echo $fId; ?>"
                                                           data-status="<?php echo $filter['status']; ?>"
                                                           <?php echo $isActive ? 'checked' : ''; ?> 
                                                           title="Toggle Active/Inactive"
                                                           style="cursor: pointer;">
                                                </div>

                                                <!-- Actions -->
                                                <button class="action-icon-btn" 
                                                        title="Add Filter Value" 
                                                        onclick="openAddValueModal(<?php echo $fId; ?>, '<?php echo htmlspecialchars(addslashes($filter['filter_name'])); ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button class="action-icon-btn btn-delete" 
                                                        title="Delete Filter Group"
                                                        onclick="deleteFilter(<?php echo $fId; ?>, '<?php echo htmlspecialchars(addslashes($filter['filter_name'])); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="filter-box-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                                    Filter Values (<?php echo count($vals); ?>)
                                                </span>
                                                <button class="btn btn-link text-primary p-0 small text-decoration-none" 
                                                        style="font-size: 0.75rem;" 
                                                        onclick="openAddValueModal(<?php echo $fId; ?>, '<?php echo htmlspecialchars(addslashes($filter['filter_name'])); ?>')">
                                                    + Add Value
                                                </button>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2" id="values-container-<?php echo $fId; ?>">
                                                <?php if (empty($vals)): ?>
                                                    <span class="text-muted small italic" id="empty-hint-<?php echo $fId; ?>">
                                                        No values added yet. Click "+ Add Value" to add items.
                                                    </span>
                                                <?php else: ?>
                                                    <?php foreach ($vals as $v): ?>
                                                        <span class="value-chip" id="chip-val-<?php echo $v['id']; ?>">
                                                            <?php if (!empty($v['value_data']) && str_starts_with($v['value_data'], '#')): ?>
                                                                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?php echo htmlspecialchars($v['value_data']); ?>;border:1px solid #cbd5e1;"></span>
                                                            <?php endif; ?>
                                                            <?php echo htmlspecialchars($v['value_name']); ?>
                                                            <i class="fas fa-xmark chip-delete" onclick="deleteValue(<?php echo $v['id']; ?>)" title="Remove Value"></i>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Modal: Add Filter Group -->
    <div class="modal fade" id="addFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add New Filter Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addFilterForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Filter Name <span class="text-danger">*</span></label>
                            <input type="text" name="filter_name" class="form-control" placeholder="e.g. Size, Color, Material, Gender" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Filter Key (Optional)</label>
                            <input type="text" name="filter_key" class="form-control" placeholder="e.g. product_size, color_tone (auto-generated if empty)">
                            <small class="text-muted">Unique machine key used for API and query indexing.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Selection Type</label>
                                <select name="filter_type" class="form-select">
                                    <option value="multiple">Multiple (Checkboxes)</option>
                                    <option value="single">Single (Radio / Select)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Filter Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Add Value to Filter -->
    <div class="modal fade" id="addValueModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Add Value to <span id="addValueFilterNameText" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addValueForm">
                    <input type="hidden" name="filter_id" id="addValueFilterId">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value Name <span class="text-danger">*</span></label>
                            <input type="text" name="value_name" class="form-control" placeholder="e.g. Small, Extra Large, Cotton, Red" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value Extra Data / Hex (Optional)</label>
                            <input type="text" name="value_data" class="form-control" placeholder="e.g. #FF0000 for colors, or custom badge text">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Value</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Toast -->
    <div class="toast-container">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                    <i class="fas fa-check-circle text-success"></i> Action completed successfully.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toastMessage');
            if (isSuccess) {
                toastBody.innerHTML = `<i class="fas fa-check-circle text-success me-2"></i> ${message}`;
                toastEl.className = 'toast align-items-center text-white bg-dark border-0';
            } else {
                toastBody.innerHTML = `<i class="fas fa-exclamation-circle text-danger me-2"></i> ${message}`;
                toastEl.className = 'toast align-items-center text-white bg-danger border-0';
            }
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // Add Filter Form Handler
        document.getElementById('addFilterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_filter');

            fetch('product-filters.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast(data.message || 'Error creating filter', false);
                }
            })
            .catch(() => showToast('Network error', false));
        });

        // Add Value Modal
        function openAddValueModal(filterId, filterName) {
            document.getElementById('addValueFilterId').value = filterId;
            document.getElementById('addValueFilterNameText').innerText = filterName;
            const modal = new bootstrap.Modal(document.getElementById('addValueModal'));
            modal.show();
        }

        // Add Value Form Handler
        document.getElementById('addValueForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_value');

            fetch('product-filters.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error adding value', false);
                }
            })
            .catch(() => showToast('Network error', false));
        });

        // Toggle Filter Group Status
        document.querySelectorAll('.filter-status-toggle').forEach(tgl => {
            tgl.addEventListener('change', function() {
                const fId = this.getAttribute('data-id');
                const curr = this.getAttribute('data-status');

                const fd = new FormData();
                fd.append('action', 'toggle_filter_status');
                fd.append('filter_id', fId);
                fd.append('status', curr);

                fetch('product-filters.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.setAttribute('data-status', data.status);
                        showToast(`Filter group is now ${data.status}`);
                    } else {
                        this.checked = !this.checked;
                        showToast(data.message || 'Error updating status', false);
                    }
                })
                .catch(() => {
                    this.checked = !this.checked;
                    showToast('Network error', false);
                });
            });
        });

        // Delete Filter Group
        function deleteFilter(filterId, name) {
            if (!confirm(`Are you sure you want to permanently delete "${name}" and all its values?`)) {
                return;
            }

            const fd = new FormData();
            fd.append('action', 'delete_filter');
            fd.append('filter_id', filterId);

            fetch('product-filters.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    const el = document.getElementById('filter-box-' + filterId);
                    if (el) el.remove();
                } else {
                    showToast(data.message || 'Error deleting filter', false);
                }
            })
            .catch(() => showToast('Network error', false));
        }

        // Delete Value Chip
        function deleteValue(valId) {
            if (!confirm('Remove this filter value?')) return;

            const fd = new FormData();
            fd.append('action', 'delete_value');
            fd.append('value_id', valId);

            fetch('product-filters.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    const chip = document.getElementById('chip-val-' + valId);
                    if (chip) chip.remove();
                } else {
                    showToast(data.message || 'Error deleting value', false);
                }
            })
            .catch(() => showToast('Network error', false));
        }
    </script>
</body>
</html>
