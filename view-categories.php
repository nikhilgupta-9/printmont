<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/CategoryController.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

$auth = new AuthController($conn);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$categoryController = new CategoryController();

// Handle AJAX status toggle request
if (isset($_POST['action']) && ($_POST['action'] === 'toggle_status' || $_POST['action'] === 'toggle_header_status')) {
    header('Content-Type: application/json');
    $catId = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'toggle_status') {
        $newStatus = (isset($_POST['status']) && $_POST['status'] === 'active') ? 'active' : 'inactive';
        $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $catId);
    } else {
        $newStatus = (isset($_POST['status']) && $_POST['status'] === 'show') ? 'show' : 'hide';
        $stmt = $conn->prepare("UPDATE categories SET desktop_menu_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $catId);
    }

    if ($catId > 0 && $stmt->execute()) {
        echo json_encode(['success' => true, 'status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error ?: 'Invalid category ID']);
    }
    exit;
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$level_filter = isset($_GET['level']) && $_GET['level'] !== '' ? (int)$_GET['level'] : 0;
$parent_filter = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int)$_GET['parent_id'] : 0;
$status_filter = trim($_GET['status'] ?? '');
$home_show_filter = '';
$view_mode = ($_GET['view'] ?? 'table') === 'tree' ? 'tree' : 'table';
$limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// -------------------------------------------------------------
// STATS OVERVIEW
// -------------------------------------------------------------
$stats_sql = "SELECT 
    COUNT(CASE WHEN level = 1 THEN 1 END) as main_count,
    COUNT(CASE WHEN level = 2 THEN 1 END) as sub_count,
    COUNT(CASE WHEN level = 3 THEN 1 END) as subsub_count,
    COUNT(*) as total_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count,
    COUNT(CASE WHEN desktop_menu_status = 'show' THEN 1 END) as menu_show_count
    FROM categories";
$stats = $database->fetch($stats_sql) ?: [
    'main_count' => 0, 'sub_count' => 0, 'subsub_count' => 0,
    'total_count' => 0, 'active_count' => 0, 'inactive_count' => 0, 'menu_show_count' => 0
];

// -------------------------------------------------------------
// PARENT CATEGORIES LIST FOR FILTER DROPDOWN
// -------------------------------------------------------------
$parent_options_sql = "SELECT id, name, level, parent_id FROM categories WHERE level IN (1, 2) ORDER BY level ASC, display_order ASC, name ASC";
$parent_options = $database->fetchAll($parent_options_sql) ?: [];

// Helper function to generate query strings for pagination & filters
function buildQueryUrl($newParams = []) {
    $current = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return 'view-categories.php?' . http_build_query($current);
}

// -------------------------------------------------------------
// BUILD DYNAMIC SQL QUERY
// -------------------------------------------------------------
$where = ["1=1"];
$params = [];
$types = "";

if ($q !== '') {
    $where[] = "(c.name LIKE ? OR c.slug LIKE ? OR c.id = ?)";
    $qLike = "%" . $q . "%";
    $qId = is_numeric($q) ? (int)$q : 0;
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qId;
    $types .= "ssi";
}

if ($level_filter > 0) {
    $where[] = "c.level = ?";
    $params[] = $level_filter;
    $types .= "i";
}

if ($parent_filter > 0) {
    $where[] = "c.parent_id = ?";
    $params[] = $parent_filter;
    $types .= "i";
}

if ($status_filter === 'active' || $status_filter === 'inactive') {
    $where[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Home layout filter removed

$whereSql = implode(" AND ", $where);

// Count Total Matching Categories
$countSql = "SELECT COUNT(*) as total FROM categories c WHERE $whereSql";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalItems = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalItems / $limit));
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch Paginated Category List for Table View
$tableCategories = [];
if ($view_mode === 'table') {
    $selectSql = "SELECT c.*, 
        p.name as parent_name, 
        p.slug as parent_slug,
        gp.name as grandparent_name,
        gp.slug as grandparent_slug,
        (SELECT COUNT(*) FROM categories ch WHERE ch.parent_id = c.id) as children_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        LEFT JOIN categories gp ON p.parent_id = gp.id
        WHERE $whereSql
        ORDER BY c.level ASC, c.display_order ASC, c.name ASC
        LIMIT ? OFFSET ?";
    
    $selectParams = $params;
    $selectParams[] = $limit;
    $selectParams[] = $offset;
    $selectTypes = $types . "ii";

    $selectStmt = $conn->prepare($selectSql);
    $selectStmt->bind_param($selectTypes, ...$selectParams);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tableCategories[] = $row;
    }
}

// Fetch Tree Hierarchy for Tree View
function fetchCategoryHierarchy($database, $parent_id = 0, $whereCustom = '') {
    $sql = "SELECT c.*, (SELECT COUNT(*) FROM categories ch WHERE ch.parent_id = c.id) as children_count 
            FROM categories c 
            WHERE c.parent_id = ? " . ($whereCustom ? " AND ($whereCustom)" : "") . " 
            ORDER BY c.display_order ASC, c.name ASC";
    $cats = $database->fetchAll($sql, [$parent_id]);
    $tree = [];
    foreach ($cats as $cat) {
        $children = fetchCategoryHierarchy($database, (int)$cat['id'], $whereCustom);
        if ($children) {
            $cat['children'] = $children;
        }
        $tree[] = $cat;
    }
    return $tree;
}

$treeCategories = [];
if ($view_mode === 'tree') {
    $treeCategories = fetchCategoryHierarchy($database, $parent_filter > 0 ? $parent_filter : 0);
}

// Flash Messages
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
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Manage Categories | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        :root {
            --bs-primary: #0d6efd;
            --bs-success: #198754;
            --bs-warning: #ffc107;
            --bs-info: #0dcaf0;
        }
        body { opacity: 1 !important; }
        .kpi-card {
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .kpi-card.active-kpi {
            border: 2px solid #0d6efd;
            background: #f8fafd;
            box-shadow: 0 4px 16px rgba(13,110,253,0.15);
        }
        .kpi-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .cat-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: transform 0.2s;
        }
        .cat-thumb:hover {
            transform: scale(1.15);
            z-index: 10;
        }
        .cat-thumb-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 1px solid #e2e8f0;
        }

        .level-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        .level-1-pill { background: #e0edff; color: #0d6efd; border: 1px solid #b6d4fe; }
        .level-2-pill { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .level-3-pill { background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; }

        .tree-node {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .tree-node:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .tree-level-1 { border-left: 4px solid #0d6efd; }
        .tree-level-2 { border-left: 4px solid #198754; margin-left: 24px; }
        .tree-level-3 { border-left: 4px solid #fd7e14; margin-left: 48px; }
        
        .tree-toggle-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tree-toggle-btn:hover {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .table-custom th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
        }
        .table-custom td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        .filter-panel {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }

        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1080;
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

                    <!-- PAGE HEADER -->
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 col-12 mb-2 mb-md-0">
                            <h3 class="mb-1"><strong>Category</strong> Management</h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Categories</li>
                                    <?php if ($level_filter > 0): ?>
                                        <li class="breadcrumb-item active text-primary">Level <?php echo $level_filter; ?></li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-12 text-md-end">
                            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                                <!-- View Switcher -->
                                <div class="btn-group" role="group" aria-label="View Switch">
                                    <a href="<?php echo buildQueryUrl(['view' => 'table', 'page' => 1]); ?>" 
                                       class="btn btn-sm <?php echo $view_mode === 'table' ? 'btn-primary' : 'btn-outline-secondary'; ?>" 
                                       title="Table View">
                                        <i class="fas fa-list me-1"></i> Table
                                    </a>
                                    <a href="<?php echo buildQueryUrl(['view' => 'tree', 'page' => 1]); ?>" 
                                       class="btn btn-sm <?php echo $view_mode === 'tree' ? 'btn-primary' : 'btn-outline-secondary'; ?>" 
                                       title="Tree View">
                                        <i class="fas fa-sitemap me-1"></i> Hierarchy Tree
                                    </a>
                                </div>

                                <!-- Add Category Dropdown / Buttons -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-success dropdown-toggle px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-plus me-1"></i> Add Category
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item py-2" href="add-main-category.php">
                                                <i class="fas fa-layer-group text-primary me-2"></i><strong>Add Main Category</strong> <small class="text-muted d-block ps-4">Level 1 Parent</small>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item py-2" href="add-sub-category.php">
                                                <i class="fas fa-sitemap text-success me-2"></i><strong>Add Sub Category</strong> <small class="text-muted d-block ps-4">Level 2 Child</small>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item py-2" href="add-sub-sub-category.php">
                                                <i class="fas fa-project-diagram text-warning me-2"></i><strong>Add Sub-Sub Category</strong> <small class="text-muted d-block ps-4">Level 3 Leaf</small>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FLASH ALERTS -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI STATS CARDS -->
                    <div class="row g-3 mb-4">
                        <!-- Total Categories -->
                        <div class="col-6 col-md-3">
                            <a href="<?php echo buildQueryUrl(['level' => null, 'parent_id' => null, 'page' => 1]); ?>" class="text-decoration-none">
                                <div class="card kpi-card p-3 <?php echo ($level_filter === 0 && empty($parent_filter)) ? 'active-kpi' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted small fw-semibold">All Categories</span>
                                            <h3 class="fw-bold my-1 text-dark"><?php echo $stats['total_count']; ?></h3>
                                            <small class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i><?php echo $stats['active_count']; ?> Active</small>
                                        </div>
                                        <div class="kpi-icon-box bg-secondary bg-opacity-10 text-secondary">
                                            <i class="fas fa-folder-open"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Main Categories (Level 1) -->
                        <div class="col-6 col-md-3">
                            <a href="<?php echo buildQueryUrl(['level' => 1, 'parent_id' => null, 'page' => 1]); ?>" class="text-decoration-none">
                                <div class="card kpi-card p-3 <?php echo $level_filter === 1 ? 'active-kpi' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted small fw-semibold">Main Categories</span>
                                            <h3 class="fw-bold my-1 text-primary"><?php echo $stats['main_count']; ?></h3>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">Level 1</span>
                                        </div>
                                        <div class="kpi-icon-box bg-primary bg-opacity-10 text-primary">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Sub Categories (Level 2) -->
                        <div class="col-6 col-md-3">
                            <a href="<?php echo buildQueryUrl(['level' => 2, 'parent_id' => null, 'page' => 1]); ?>" class="text-decoration-none">
                                <div class="card kpi-card p-3 <?php echo $level_filter === 2 ? 'active-kpi' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted small fw-semibold">Sub Categories</span>
                                            <h3 class="fw-bold my-1 text-success"><?php echo $stats['sub_count']; ?></h3>
                                            <span class="badge bg-success bg-opacity-10 text-success">Level 2</span>
                                        </div>
                                        <div class="kpi-icon-box bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-sitemap"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Sub-Sub Categories (Level 3) -->
                        <div class="col-6 col-md-3">
                            <a href="<?php echo buildQueryUrl(['level' => 3, 'parent_id' => null, 'page' => 1]); ?>" class="text-decoration-none">
                                <div class="card kpi-card p-3 <?php echo $level_filter === 3 ? 'active-kpi' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted small fw-semibold">Sub-Sub Categories</span>
                                            <h3 class="fw-bold my-1 text-warning"><?php echo $stats['subsub_count']; ?></h3>
                                            <span class="badge bg-warning bg-opacity-10 text-warning">Level 3</span>
                                        </div>
                                        <div class="kpi-icon-box bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-project-diagram"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ADVANCED FILTER & SEARCH BAR -->
                    <div class="card filter-panel p-3 mb-4">
                        <form method="GET" action="view-categories.php" class="row g-2 align-items-center">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                            
                            <!-- Search Query -->
                            <div class="col-12 col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name, slug, ID...">
                                </div>
                            </div>

                            <!-- Level Selector -->
                            <div class="col-6 col-md-2">
                                <select name="level" class="form-select">
                                    <option value="">All Levels</option>
                                    <option value="1" <?php echo $level_filter === 1 ? 'selected' : ''; ?>>Level 1 (Main)</option>
                                    <option value="2" <?php echo $level_filter === 2 ? 'selected' : ''; ?>>Level 2 (Sub)</option>
                                    <option value="3" <?php echo $level_filter === 3 ? 'selected' : ''; ?>>Level 3 (Sub-Sub)</option>
                                </select>
                            </div>

                            <!-- Parent Category Dropdown -->
                            <div class="col-6 col-md-2">
                                <select name="parent_id" class="form-select">
                                    <option value="">All Parent Groups</option>
                                    <?php foreach ($parent_options as $pOpt): ?>
                                        <option value="<?php echo $pOpt['id']; ?>" <?php echo $parent_filter == $pOpt['id'] ? 'selected' : ''; ?>>
                                            <?php echo ($pOpt['level'] == 2 ? '↳ ' : '● ') . htmlspecialchars($pOpt['name']) . " (L{$pOpt['level']})"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-6 col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">Status: All</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Home Show Filter -->
                            <div class="col-6 col-md-2">
                                <select name="home_show" class="form-select">
                                    <option value="">Home Page: All</option>
                                    <option value="yes" <?php echo $home_show_filter === 'yes' ? 'selected' : ''; ?>>Show on Home</option>
                                    <option value="no" <?php echo $home_show_filter === 'no' ? 'selected' : ''; ?>>Hide on Home</option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-12 col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-primary w-100" title="Apply Filter">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <?php if ($q !== '' || $level_filter > 0 || $parent_filter > 0 || $status_filter !== ''): ?>
                                    <a href="view-categories.php?view=<?php echo $view_mode; ?>" class="btn btn-outline-danger" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- MAIN CONTENT CONTAINER -->
                    <?php if ($view_mode === 'table'): ?>
                        <!-- ================= TABLE VIEW ================= -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="card-title mb-0 fw-bold">Categories Table</h5>
                                    <span class="badge bg-light text-dark border"><?php echo number_format($totalItems); ?> Results</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                                    <label class="small text-muted mb-0">Show:</label>
                                    <select class="form-select form-select-sm" style="width: 80px;" onchange="location = this.value;">
                                        <?php foreach ([10, 15, 25, 50, 100] as $lim): ?>
                                            <option value="<?php echo buildQueryUrl(['limit' => $lim, 'page' => 1]); ?>" <?php echo $limit == $lim ? 'selected' : ''; ?>>
                                                <?php echo $lim; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="small text-muted">per page</span>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <?php if (empty($tableCategories)): ?>
                                    <div class="text-center py-5">
                                        <div class="mb-3 text-muted">
                                            <i class="fas fa-folder-open fa-3x text-secondary opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold">No Categories Found</h5>
                                        <p class="text-muted small">No categories match your search criteria.</p>
                                        <a href="view-categories.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-undo me-1"></i> Reset Filters
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-custom table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 70px;">Media</th>
                                                    <th>Category Details</th>
                                                    <th>Level</th>
                                                    <th>Parent Path</th>
                                                    <th>Mobile Topbar</th>
                                                    <th>Menu Show</th>
                                                    <th>Status</th>
                                                    <th>Order</th>
                                                    <th style="width: 140px;" class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tableCategories as $cat): ?>
                                                    <?php 
                                                        $thumb = $cat['desktop_menu_image'] ?: ($cat['image'] ?: '');
                                                    ?>
                                                    <tr>
                                                        <!-- Media / Image -->
                                                        <td>
                                                            <?php if ($thumb): ?>
                                                                <img src="<?php echo htmlspecialchars($thumb); ?>" class="cat-thumb shadow-sm" alt="<?php echo htmlspecialchars($cat['name']); ?>" onerror="this.src='img/icons/icon-48x48.png';">
                                                            <?php else: ?>
                                                                <div class="cat-thumb-placeholder">
                                                                    <i class="fas <?php echo $cat['icon'] ? htmlspecialchars($cat['icon']) : 'fa-folder'; ?>"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>

                                                        <!-- Name, Slug, Description -->
                                                        <td>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                                                <?php if ($cat['is_featured']): ?>
                                                                    <span class="badge bg-warning text-dark" title="Featured Category"><i class="fas fa-star"></i></span>
                                                                <?php endif; ?>
                                                                <?php if ($cat['children_count'] > 0): ?>
                                                                    <span class="badge bg-light text-secondary border" title="Has <?php echo $cat['children_count']; ?> child categories">
                                                                        <i class="fas fa-sitemap me-1"></i><?php echo $cat['children_count']; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="small text-muted mt-1">
                                                                <code class="text-primary bg-light px-1 rounded">/<?php echo htmlspecialchars($cat['slug']); ?></code>
                                                                <span class="mx-1 text-muted">•</span>
                                                                <span class="text-muted">ID: #<?php echo $cat['id']; ?></span>
                                                            </div>
                                                        </td>

                                                        <!-- Level Pill -->
                                                        <td>
                                                            <span class="level-pill level-<?php echo $cat['level']; ?>-pill">
                                                                Level <?php echo $cat['level']; ?>
                                                            </span>
                                                        </td>

                                                        <!-- Parent Lineage -->
                                                        <td>
                                                            <?php if ($cat['level'] == 1): ?>
                                                                <span class="text-muted small"><i class="fas fa-globe me-1"></i>Root Parent</span>
                                                            <?php elseif ($cat['level'] == 2): ?>
                                                                <span class="small fw-semibold text-dark">
                                                                    <i class="fas fa-arrow-right text-primary me-1"></i><?php echo htmlspecialchars($cat['parent_name'] ?? 'Unknown'); ?>
                                                                </span>
                                                            <?php elseif ($cat['level'] == 3): ?>
                                                                <span class="small text-muted">
                                                                    <?php echo htmlspecialchars($cat['grandparent_name'] ?? ''); ?> 
                                                                    <i class="fas fa-chevron-right text-muted mx-1" style="font-size: 9px;"></i>
                                                                </span>
                                                                <span class="small fw-semibold text-dark d-block">
                                                                    <?php echo htmlspecialchars($cat['parent_name'] ?? 'Unknown'); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>

                                                        <!-- Mobile Topbar Display -->
                                                        <td>
                                                            <?php if (($cat['mobile_topbar_status'] ?? 'show') === 'show'): ?>
                                                                <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                                    <i class="fas fa-mobile-alt me-1"></i>Show
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border">Hide</span>
                                                            <?php endif; ?>
                                                        </td>

                                                        <!-- Header Menu Switch (AJAX) -->
                                                        <td>
                                                            <div class="form-check form-switch mb-0" title="Toggle Desktop Top Menu visibility">
                                                                <input class="form-check-input" type="checkbox" role="switch"
                                                                       id="headerSwitch_<?php echo $cat['id']; ?>"
                                                                       <?php echo ($cat['desktop_menu_status'] ?? 'show') === 'show' ? 'checked' : ''; ?>
                                                                       onchange="toggleHeaderStatus(<?php echo $cat['id']; ?>, this)">
                                                                <label class="form-check-label small ms-1 text-muted" id="headerLabel_<?php echo $cat['id']; ?>" for="headerSwitch_<?php echo $cat['id']; ?>">
                                                                    <?php echo ($cat['desktop_menu_status'] ?? 'show') === 'show' ? 'Show' : 'Hide'; ?>
                                                                </label>
                                                            </div>
                                                        </td>

                                                        <!-- Active Status Switch (AJAX) -->
                                                        <td>
                                                            <div class="form-check form-switch mb-0" title="Toggle Active/Inactive">
                                                                <input class="form-check-input" type="checkbox" role="switch"
                                                                       id="statusSwitch_<?php echo $cat['id']; ?>"
                                                                       <?php echo $cat['status'] === 'active' ? 'checked' : ''; ?>
                                                                       onchange="toggleCategoryStatus(<?php echo $cat['id']; ?>, this)">
                                                                <label class="form-check-label small ms-1 fw-semibold text-<?php echo $cat['status'] === 'active' ? 'success' : 'danger'; ?>" 
                                                                       id="statusLabel_<?php echo $cat['id']; ?>" for="statusSwitch_<?php echo $cat['id']; ?>">
                                                                    <?php echo ucfirst($cat['status']); ?>
                                                                </label>
                                                            </div>
                                                        </td>

                                                        <!-- Display Order -->
                                                        <td>
                                                            <span class="badge bg-light text-dark border">
                                                                <?php echo $cat['display_order']; ?>
                                                            </span>
                                                        </td>

                                                        <!-- Actions -->
                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <?php if ($cat['level'] == 1): ?>
                                                                    <a href="add-sub-category.php?parent_id=<?php echo $cat['id']; ?>" class="btn btn-outline-success" title="Add Sub Category">
                                                                        <i class="fas fa-plus"></i>
                                                                    </a>
                                                                <?php elseif ($cat['level'] == 2): ?>
                                                                    <a href="add-sub-sub-category.php?parent_id=<?php echo $cat['id']; ?>" class="btn btn-outline-warning" title="Add Sub-Sub Category">
                                                                        <i class="fas fa-plus"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="edit-category.php?id=<?php echo $cat['id']; ?>" class="btn btn-outline-primary" title="Edit Category">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-category.php?id=<?php echo $cat['id']; ?>" class="btn btn-outline-danger" title="Delete Category">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- PAGINATION FOOTER -->
                                    <div class="card-footer bg-white border-top py-3">
                                        <div class="row align-items-center g-3">
                                            <div class="col-12 col-md-5 text-center text-md-start">
                                                <p class="small text-muted mb-0">
                                                    Showing <strong><?php echo $totalItems > 0 ? ($offset + 1) : 0; ?></strong> to 
                                                    <strong><?php echo min($totalItems, $offset + $limit); ?></strong> of 
                                                    <strong><?php echo number_format($totalItems); ?></strong> categories
                                                </p>
                                            </div>
                                            <div class="col-12 col-md-7">
                                                <?php if ($totalPages > 1): ?>
                                                    <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0">
                                                        <!-- First & Prev -->
                                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="<?php echo buildQueryUrl(['page' => 1]); ?>" aria-label="First">
                                                                <i class="fas fa-angle-double-left"></i>
                                                            </a>
                                                        </li>
                                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="<?php echo buildQueryUrl(['page' => $page - 1]); ?>" aria-label="Previous">
                                                                <i class="fas fa-angle-left"></i>
                                                            </a>
                                                        </li>

                                                        <!-- Page Numbers with Ellipsis -->
                                                        <?php
                                                            $startPage = max(1, $page - 2);
                                                            $endPage = min($totalPages, $page + 2);
                                                            if ($startPage > 1) {
                                                                echo '<li class="page-item"><a class="page-link" href="' . buildQueryUrl(['page' => 1]) . '">1</a></li>';
                                                                if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                                            }
                                                            for ($p = $startPage; $p <= $endPage; $p++) {
                                                                $active = ($p == $page) ? 'active' : '';
                                                                echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . buildQueryUrl(['page' => $p]) . '">' . $p . '</a></li>';
                                                            }
                                                            if ($endPage < $totalPages) {
                                                                if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                                                echo '<li class="page-item"><a class="page-link" href="' . buildQueryUrl(['page' => $totalPages]) . '">' . $totalPages . '</a></li>';
                                                            }
                                                        ?>

                                                        <!-- Next & Last -->
                                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="<?php echo buildQueryUrl(['page' => $page + 1]); ?>" aria-label="Next">
                                                                <i class="fas fa-angle-right"></i>
                                                            </a>
                                                        </li>
                                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                            <a class="page-link" href="<?php echo buildQueryUrl(['page' => $totalPages]); ?>" aria-label="Last">
                                                                <i class="fas fa-angle-double-right"></i>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- ================= HIERARCHY TREE VIEW ================= -->
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="card-title mb-0 fw-bold">Interactive Hierarchy Tree</h5>
                                    <span class="badge bg-light text-dark border"><?php echo count($treeCategories); ?> Top Branches</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="expandAllTree()">
                                        <i class="fas fa-expand-arrows-alt me-1"></i> Expand All
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="collapseAllTree()">
                                        <i class="fas fa-compress-arrows-alt me-1"></i> Collapse All
                                    </button>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <?php if (empty($treeCategories)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                                        <h5>No Hierarchy Found</h5>
                                        <p class="text-muted small">No category nodes available to display.</p>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    function renderCategoryTreeNode($nodes, $depth = 1) {
                                        foreach ($nodes as $node) {
                                            $hasChildren = !empty($node['children']);
                                            $thumb = !empty($node['desktop_menu_image']) ? $node['desktop_menu_image'] : (!empty($node['image']) ? $node['image'] : '');
                                            ?>
                                            <div class="tree-node tree-level-<?php echo $depth; ?> p-3 mb-2" data-cat-id="<?php echo $node['id']; ?>">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php if ($hasChildren): ?>
                                                            <button class="tree-toggle-btn" onclick="toggleTreeNode(this)" title="Expand / Collapse">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="tree-toggle-btn text-muted opacity-50" style="cursor: default;">
                                                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($thumb): ?>
                                                            <img src="<?php echo htmlspecialchars($thumb); ?>" class="cat-thumb" style="width: 36px; height: 36px;" onerror="this.src='img/icons/icon-48x48.png';">
                                                        <?php else: ?>
                                                            <div class="cat-thumb-placeholder" style="width: 36px; height: 36px; font-size: 14px;">
                                                                <i class="fas <?php echo $node['icon'] ? htmlspecialchars($node['icon']) : 'fa-folder'; ?>"></i>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($node['name']); ?></span>
                                                                <span class="level-pill level-<?php echo $node['level']; ?>-pill">Level <?php echo $node['level']; ?></span>
                                                                <?php if ($hasChildren): ?>
                                                                    <span class="badge bg-light text-secondary border"><?php echo count($node['children']); ?> Children</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <code>/<?php echo htmlspecialchars($node['slug']); ?></code> · ID: #<?php echo $node['id']; ?> · Order: <?php echo $node['display_order']; ?>
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex align-items-center gap-3">
                                                        <!-- Desktop Menu display badge -->
                                                        <?php if (!empty($node['desktop_menu_status']) && $node['desktop_menu_status'] === 'show'): ?>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">
                                                                <i class="fas fa-desktop me-1"></i>Menu: #<?php echo (int)($node['desktop_menu_order'] ?? 0); ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <!-- Status Switch -->
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                   id="statusSwitch_tree_<?php echo $node['id']; ?>"
                                                                   <?php echo $node['status'] === 'active' ? 'checked' : ''; ?>
                                                                   onchange="toggleCategoryStatus(<?php echo $node['id']; ?>, this)">
                                                        </div>

                                                        <!-- Action Buttons -->
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($node['level'] == 1): ?>
                                                                <a href="add-sub-category.php?parent_id=<?php echo $node['id']; ?>" class="btn btn-outline-success" title="Add Sub Category">
                                                                    <i class="fas fa-plus"></i>
                                                                </a>
                                                            <?php elseif ($node['level'] == 2): ?>
                                                                <a href="add-sub-sub-category.php?parent_id=<?php echo $node['id']; ?>" class="btn btn-outline-warning" title="Add Sub-Sub Category">
                                                                    <i class="fas fa-plus"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="edit-category.php?id=<?php echo $node['id']; ?>" class="btn btn-outline-primary" title="Edit Category">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="delete-category.php?id=<?php echo $node['id']; ?>" class="btn btn-outline-danger" title="Delete Category">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($hasChildren): ?>
                                                    <div class="tree-children mt-2 pt-2 border-top">
                                                        <?php renderCategoryTreeNode($node['children'], $depth + 1); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                        }
                                    }
                                    renderCategoryTreeNode($treeCategories, 1);
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container">
        <div id="statusToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-primary" id="toastIcon"></i>
                    <span id="toastMsg">Status updated successfully</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        // Toast Helper
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('statusToast');
            const msgEl = document.getElementById('toastMsg');
            const iconEl = document.getElementById('toastIcon');
            msgEl.textContent = message;
            iconEl.className = isSuccess ? 'fas fa-check-circle text-success' : 'fas fa-exclamation-circle text-danger';
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // Tree Toggle Node
        function toggleTreeNode(btn) {
            const node = btn.closest('.tree-node');
            const children = node.querySelector('.tree-children');
            const icon = btn.querySelector('i');
            if (!children) return;

            if (children.style.display === 'none') {
                children.style.display = 'block';
                icon.className = 'fas fa-chevron-down';
            } else {
                children.style.display = 'none';
                icon.className = 'fas fa-chevron-right';
            }
        }

        function expandAllTree() {
            document.querySelectorAll('.tree-children').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.tree-toggle-btn i').forEach(el => {
                if (el.classList.contains('fa-chevron-right')) el.className = 'fas fa-chevron-down';
            });
        }

        function collapseAllTree() {
            document.querySelectorAll('.tree-children').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.tree-toggle-btn i').forEach(el => {
                if (el.classList.contains('fa-chevron-down')) el.className = 'fas fa-chevron-right';
            });
        }

        // Toggle Active / Inactive Status AJAX
        function toggleCategoryStatus(categoryId, switchElem) {
            const isChecked = switchElem.checked;
            const newStatus = isChecked ? 'active' : 'inactive';
            switchElem.disabled = true;

            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', categoryId);
            formData.append('status', newStatus);

            fetch('view-categories.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                switchElem.disabled = false;
                if (data.success) {
                    const label = document.getElementById(`statusLabel_${categoryId}`);
                    if (label) {
                        label.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        label.className = `form-check-label small ms-1 fw-semibold text-${newStatus === 'active' ? 'success' : 'danger'}`;
                    }
                    showToast(`Category #${categoryId} status set to ${newStatus}`);
                } else {
                    switchElem.checked = !isChecked;
                    showToast('Error: ' + (data.error || 'Failed to update status'), false);
                }
            })
            .catch(err => {
                switchElem.disabled = false;
                switchElem.checked = !isChecked;
                showToast('Network error while updating status', false);
            });
        }

        // Toggle Header Show / Hide Status AJAX
        function toggleHeaderStatus(categoryId, switchElem) {
            const isChecked = switchElem.checked;
            const newStatus = isChecked ? 'show' : 'hide';
            switchElem.disabled = true;

            const formData = new FormData();
            formData.append('action', 'toggle_header_status');
            formData.append('id', categoryId);
            formData.append('status', newStatus);

            fetch('view-categories.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                switchElem.disabled = false;
                if (data.success) {
                    const label = document.getElementById(`headerLabel_${categoryId}`);
                    if (label) {
                        label.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }
                    showToast(`Category #${categoryId} header menu set to ${newStatus}`);
                } else {
                    switchElem.checked = !isChecked;
                    showToast('Error: ' + (data.error || 'Failed to update header menu status'), false);
                }
            })
            .catch(err => {
                switchElem.disabled = false;
                switchElem.checked = !isChecked;
                showToast('Network error while updating header menu status', false);
            });
        }
    </script>
</body>
</html>