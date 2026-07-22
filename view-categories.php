<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

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
        echo json_encode(['success' => false, 'error' => $conn->error ?: 'Invalid ID']);
    }
    exit;
}

// Get filter parameters
$level_filter = isset($_GET['level']) ? (int) $_GET['level'] : 0;
$parent_filter = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : 0;

// Get all categories for tree structure
function buildCategoryTree($database, $parent_id = 0)
{
    $sql = "SELECT * FROM categories WHERE parent_id = ? ORDER BY display_order, name";
    $categories = $database->fetchAll($sql, [$parent_id]);

    $tree = [];
    foreach ($categories as $category) {
        $children = buildCategoryTree($database, $category['id']);
        if ($children) {
            $category['children'] = $children;
        }
        $tree[] = $category;
    }
    return $tree;
}

// Get categories based on filter
$categories = [];
$page_title = "All Categories";

if ($level_filter > 0) {
    switch ($level_filter) {
        case 1:
            $sql = "SELECT * FROM categories WHERE level = 1 ORDER BY display_order, name";
            $categories = $database->fetchAll($sql);
            $page_title = "Main Categories";
            break;
        case 2:
            $sql = "SELECT c.*, p.name as parent_name 
                   FROM categories c 
                   LEFT JOIN categories p ON c.parent_id = p.id 
                   WHERE c.level = 2 
                   ORDER BY c.display_order, c.name";
            $categories = $database->fetchAll($sql);
            $page_title = "Sub Categories";
            break;
        case 3:
            $sql = "SELECT c.*, p.name as parent_name, pp.name as grandparent_name 
                   FROM categories c 
                   LEFT JOIN categories p ON c.parent_id = p.id 
                   LEFT JOIN categories pp ON p.parent_id = pp.id 
                   WHERE c.level = 3 
                   ORDER BY c.display_order, c.name";
            $categories = $database->fetchAll($sql);
            $page_title = "Sub Sub Categories";
            break;
    }
} elseif ($parent_filter > 0) {
    $sql = "SELECT * FROM categories WHERE parent_id = ? ORDER BY display_order, name";
    $categories = $database->fetchAll($sql, [$parent_filter]);

    // Get parent category name for title
    $parent_sql = "SELECT name FROM categories WHERE id = ?";
    $parent = $database->fetch($parent_sql, [$parent_filter]);
    $page_title = "Categories under " . ($parent['name'] ?? 'Unknown');
} else {
    // Get all categories for tree view
    $categories = buildCategoryTree($database);
}

// Get counts for stats
$stats_sql = "SELECT 
    COUNT(CASE WHEN level = 1 THEN 1 END) as main_count,
    COUNT(CASE WHEN level = 2 THEN 1 END) as sub_count,
    COUNT(CASE WHEN level = 3 THEN 1 END) as subsub_count,
    COUNT(*) as total_count
    FROM categories WHERE status = 'active'";
$stats = $database->fetch($stats_sql);

// Get main categories for filter dropdown
$main_categories_sql = "SELECT id, name FROM categories WHERE level = 1 ORDER BY display_order, name";
$main_categories = $database->fetchAll($main_categories_sql);

// Check for messages
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
    <title>Categories | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .category-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .category-item { border-left: 4px solid #007bff; margin: 5px 0; transition: all 0.3s ease; }
        .category-level-1 { border-left-color: #007bff; background-color: #f8f9fa; }
        .category-level-2 { border-left-color: #28a745; background-color: #e9ecef; margin-left: 20px; }
        .category-level-3 { border-left-color: #fd7e14; background-color: #dee2e6; margin-left: 40px; }
        .category-item:hover { background-color: #f1f3f4; transform: translateX(5px); }
        .toggle-children { cursor: pointer; margin-right: 10px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        .children { display: block; }
        .children.collapsed { display: none; }
        .level-badge-1 { background-color: #007bff; }
        .level-badge-2 { background-color: #28a745; }
        .level-badge-3 { background-color: #fd7e14; }
        .filter-btn.active { background-color: #0d6efd; color: white; }
        .stats-card { border-left: 4px solid; padding: 15px; margin-bottom: 15px; }
        .main-cat { border-left-color: #007bff; }
        .sub-cat { border-left-color: #28a745; }
        .subsub-cat { border-left-color: #fd7e14; }
        .total-cat { border-left-color: #6c757d; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Category</strong> Management</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <div class="btn-group" role="group">
                                <a href="add-main-category.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add Main
                                </a>
                                <a href="add-sub-category.php" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Add Sub
                                </a>
                                <a href="add-sub-sub-category.php" class="btn btn-warning">
                                    <i class="fas fa-plus me-1"></i> Add Sub Sub
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card main-cat">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1">Main Categories</h5>
                                            <h2 class="mb-0"><?php echo $stats['main_count'] ?? 0; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-layer-group fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-0">Level 1 Categories</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card sub-cat">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1">Sub Categories</h5>
                                            <h2 class="mb-0"><?php echo $stats['sub_count'] ?? 0; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-sitemap fa-2x text-success"></i>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-0">Level 2 Categories</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card subsub-cat">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1">Sub Sub Categories</h5>
                                            <h2 class="mb-0"><?php echo $stats['subsub_count'] ?? 0; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-project-diagram fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-0">Level 3 Categories</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card total-cat">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1">Total Categories</h5>
                                            <h2 class="mb-0"><?php echo $stats['total_count'] ?? 0; ?></h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-folder fa-2x text-secondary"></i>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted mb-0">All Active Categories</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Filter Categories</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="btn-group mb-3" role="group">
                                        <a href="view-categories.php" 
                                           class="btn btn-outline-primary <?php echo $level_filter == 0 ? 'active' : ''; ?>">
                                            All Categories
                                        </a>
                                        <a href="view-categories.php?level=1" 
                                           class="btn btn-outline-primary <?php echo $level_filter == 1 ? 'active' : ''; ?>">
                                            Main (Level 1)
                                        </a>
                                        <a href="view-categories.php?level=2" 
                                           class="btn btn-outline-success <?php echo $level_filter == 2 ? 'active' : ''; ?>">
                                            Sub (Level 2)
                                        </a>
                                        <a href="view-categories.php?level=3" 
                                           class="btn btn-outline-warning <?php echo $level_filter == 3 ? 'active' : ''; ?>">
                                            Sub Sub (Level 3)
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <form method="GET" class="row g-2">
                                        <div class="col-md-8">
                                            <select name="parent_id" class="form-control" onchange="this.form.submit()">
                                                <option value="">Filter by Main Category</option>
                                                <?php foreach ($main_categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>" 
                                                            <?php echo $parent_filter == $cat['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="view-categories.php" class="btn btn-secondary w-100">Clear Filter</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?php echo $page_title; ?></h5>
                                    <div class="card-subtitle text-muted">
                                        <?php if ($level_filter > 0): ?>
                                                Showing <?php echo count($categories); ?> categories
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                <h5>No categories found</h5>
                                                <p class="text-muted">
                                                    <?php if ($level_filter > 0 || $parent_filter > 0): ?>
                                                            No categories match your filter criteria.
                                                    <?php else: ?>
                                                            You haven't created any categories yet.
                                                    <?php endif; ?>
                                                </p>
                                                <div class="mt-3">
                                                    <a href="add-main-category.php" class="btn btn-primary me-2">
                                                        <i class="fas fa-plus me-1"></i> Add Main Category
                                                    </a>
                                                    <a href="view-categories.php" class="btn btn-outline-secondary">
                                                        Show All Categories
                                                    </a>
                                                </div>
                                            </div>
                                    <?php else: ?>
                                            <?php if ($level_filter == 0): ?>
                                                    <!-- Tree View for all categories -->
                                                    <?php
                                                    function displayCategoryTree($categories, $level = 1)
                                                    {
                                                        foreach ($categories as $category) {
                                                            $hasChildren = !empty($category['children']);
                                                            ?>
                                                                    <div class="category-item category-level-<?php echo $level; ?> p-3 rounded mb-2">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <div class="d-flex align-items-center">
                                                                                <?php if ($hasChildren): ?>
                                                                                        <span class="toggle-children" onclick="toggleChildren(this)">
                                                                                            <i class="fas fa-chevron-down"></i>
                                                                                        </span>
                                                                                <?php else: ?>
                                                                                        <span class="toggle-children" style="visibility: hidden;">
                                                                                            <i class="fas fa-chevron-right"></i>
                                                                                        </span>
                                                                                <?php endif; ?>
                                                                
                                                                                <?php $thumb = $category['image'] ?: $category['image']; ?>
                                                                                <?php if ($thumb): ?>
                                                                                        <img src="<?php echo htmlspecialchars($thumb); ?>"
                                                                                             class="category-image me-3"
                                                                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                                                             onerror="this.style.display='none'">
                                                                                <?php else: ?>
                                                                                        <div class="category-image me-3 bg-light d-flex align-items-center justify-content-center rounded">
                                                                                            <?php if ($category['icon']): ?>
                                                                                                    <i class="<?php echo htmlspecialchars($category['icon']); ?> fa-lg text-muted"></i>
                                                                                            <?php else: ?>
                                                                                                    <i class="fas fa-folder fa-lg text-muted"></i>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                <?php endif; ?>
                                                                
                                                                                <div>
                                                                                    <h6 class="mb-1 d-flex align-items-center">
                                                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                                                        <span class="badge level-badge-<?php echo $category['level']; ?> ms-2">
                                                                                            Level <?php echo $category['level']; ?>
                                                                                        </span>
                                                                                    </h6>
                                                                                    <small class="text-muted">
                                                                                        <?php if ($category['description']): ?>
                                                                                                <?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>
                                                                                                <?php if (strlen($category['description']) > 100)
                                                                                                    echo '...'; ?>
                                                                                                <br>
                                                                                        <?php endif; ?>
                                                                                        <strong>Slug:</strong> <?php echo htmlspecialchars($category['slug']); ?>
                                                                                        <span class="mx-2">•</span>
                                                                                        <strong>ID:</strong> <?php echo $category['id']; ?>
                                                                                    </small>
                                                                                </div>
                                                                            </div>
                                                            
                                                                            <div class="d-flex gap-2 align-items-center">                                                                                 <?php if ($category['is_featured']): ?>
                                                                                         <span class="badge bg-warning">
                                                                                             <i class="fas fa-star me-1"></i> Featured
                                                                                         </span>
                                                                                 <?php endif; ?>                                                                                  <div class="form-check form-switch mb-0 d-inline-flex align-items-center me-1" style="cursor: pointer;" title="Toggle Category Active/Inactive Status">
                                                                                      <input class="form-check-input me-1" 
                                                                                             type="checkbox" 
                                                                                             role="switch" 
                                                                                             id="statusSwitch_tree_<?php echo $category['id']; ?>"
                                                                                             <?php echo $category['status'] === 'active' ? 'checked' : ''; ?>
                                                                                             onchange="toggleCategoryStatus(<?php echo $category['id']; ?>, this)"
                                                                                             style="cursor: pointer; width: 2.2em; height: 1.1em;">
                                                                                      <label class="form-check-label badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'danger'; ?>" 
                                                                                             id="statusBadge_tree_<?php echo $category['id']; ?>" 
                                                                                             for="statusSwitch_tree_<?php echo $category['id']; ?>"
                                                                                             style="cursor: pointer;">
                                                                                          <?php echo ucfirst($category['status']); ?>
                                                                                      </label>
                                                                                  </div>
                                                                                <span class="badge bg-info">
                                                                                    <i class="fas fa-sort-numeric-up me-1"></i> <?php echo $category['display_order']; ?>
                                                                                </span>
                                                                
                                                                                <div class="btn-group btn-group-sm" role="group">
                                                                                    <?php if ($category['level'] < 3): ?>
                                                                                            <a href="add-sub-category.php?parent_id=<?php echo $category['id']; ?>" 
                                                                                               class="btn btn-outline-success" title="Add Sub Category">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                    <?php endif; ?>
                                                                                    <a href="edit-category.php?id=<?php echo $category['id']; ?>" 
                                                                                       class="btn btn-outline-primary" title="Edit">
                                                                                        <i class="fas fa-edit"></i>
                                                                                    </a>
                                                                                    <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                                                                            class="btn btn-outline-danger" title="Delete"
                                                                                            <?php echo $hasChildren ? 'disabled' : ''; ?>>
                                                                                        <i class="fas fa-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                        
                                                                        <?php if ($hasChildren): ?>
                                                                                <div class="children mt-2">
                                                                                    <?php displayCategoryTree($category['children'], $level + 1); ?>
                                                                                </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php
                                                        }
                                                    }

                                                    displayCategoryTree($categories);
                                                    ?>
                                            <?php else: ?>
                                                    <!-- Table View for filtered categories -->
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th width="50">Image</th>
                                                                    <th>Name & Description</th>
                                                                    <th>Level</th>
                                                                    <th>Parent</th>
                                                                    <th>Order</th>
                                                                    <th>Status</th>
                                                                    <th>Featured</th>
                                                                    <th width="150">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($categories as $category): ?>
                                                                        <tr class="category-level-<?php echo $category['level']; ?>">
                                                                            <td>
                                                                                <?php $thumb = $category['desktop_image'] ?: $category['image']; ?>
                                                                                <?php if ($thumb): ?>
                                                                                        <img src="<?php echo htmlspecialchars($thumb); ?>"
                                                                                             class="category-image"
                                                                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                                                             onerror="this.style.display='none'">
                                                                                <?php else: ?>
                                                                                        <div class="category-image bg-light d-flex align-items-center justify-content-center rounded">
                                                                                            <?php if ($category['icon']): ?>
                                                                                                    <i class="<?php echo htmlspecialchars($category['icon']); ?> text-muted"></i>
                                                                                            <?php else: ?>
                                                                                                    <i class="fas fa-folder text-muted"></i>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                                                <br>
                                                                                <small class="text-muted">
                                                                                    <?php if ($category['description']): ?>
                                                                                            <?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>
                                                                                            <?php if (strlen($category['description']) > 100)
                                                                                                echo '...'; ?>
                                                                                            <br>
                                                                                    <?php endif; ?>
                                                                                    <strong>Slug:</strong> <?php echo htmlspecialchars($category['slug']); ?>
                                                                                    <span class="mx-1">•</span>
                                                                                    <strong>ID:</strong> <?php echo $category['id']; ?>
                                                                                </small>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge level-badge-<?php echo $category['level']; ?>">
                                                                                    Level <?php echo $category['level']; ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($category['level'] > 1): ?>
                                                                                        <?php if (isset($category['parent_name'])): ?>
                                                                                                <?php echo htmlspecialchars($category['parent_name']); ?>
                                                                                                <?php if ($category['level'] == 3 && isset($category['grandparent_name'])): ?>
                                                                                                        <br>
                                                                                                        <small class="text-muted">
                                                                                                            ← <?php echo htmlspecialchars($category['grandparent_name']); ?>
                                                                                                        </small>
                                                                                                <?php endif; ?>
                                                                                        <?php else: ?>
                                                                                                <span class="text-danger">Parent not found</span>
                                                                                        <?php endif; ?>
                                                                                <?php else: ?>
                                                                                        <span class="text-muted">None</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge bg-info">
                                                                                    <?php echo $category['display_order']; ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <div class="form-check form-switch mb-0 d-inline-flex align-items-center" style="cursor: pointer;">
                                                                                     <input class="form-check-input me-1" 
                                                                                            type="checkbox" 
                                                                                            role="switch" 
                                                                                            id="statusSwitch_tbl_<?php echo $category['id']; ?>"
                                                                                            <?php echo $category['status'] === 'active' ? 'checked' : ''; ?>
                                                                                            onchange="toggleCategoryStatus(<?php echo $category['id']; ?>, this)"
                                                                                            title="Toggle Active/Inactive"
                                                                                            style="cursor: pointer; width: 2.2em; height: 1.1em;">
                                                                                     <label class="form-check-label badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'danger'; ?>" 
                                                                                            id="statusBadge_tbl_<?php echo $category['id']; ?>" 
                                                                                            for="statusSwitch_tbl_<?php echo $category['id']; ?>"
                                                                                            style="cursor: pointer;">
                                                                                         <?php echo ucfirst($category['status']); ?>
                                                                                     </label>
                                                                                 </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="form-check form-switch mb-0 d-inline-flex align-items-center" style="cursor: pointer;" title="Toggle Header Menu Visibility">
                                                                                     <input class="form-check-input me-1" 
                                                                                            type="checkbox" 
                                                                                            role="switch" 
                                                                                            id="headerSwitch_tbl_<?php echo $category['id']; ?>"
                                                                                            <?php echo ($category['desktop_menu_status'] ?? 'show') === 'show' ? 'checked' : ''; ?>
                                                                                            onchange="toggleHeaderStatus(<?php echo $category['id']; ?>, this)"
                                                                                            style="cursor: pointer; width: 2.2em; height: 1.1em;">
                                                                                     <label class="form-check-label badge bg-<?php echo ($category['desktop_menu_status'] ?? 'show') === 'show' ? 'primary' : 'secondary'; ?>" 
                                                                                            id="headerBadge_tbl_<?php echo $category['id']; ?>" 
                                                                                            for="headerSwitch_tbl_<?php echo $category['id']; ?>"
                                                                                            style="cursor: pointer;">
                                                                                         <?php echo ucfirst($category['desktop_menu_status'] ?? 'show'); ?>
                                                                                     </label>
                                                                                 </div>
                                                                             </td>
                                                                            <td>
                                                                                <?php if ($category['is_featured']): ?>
                                                                                        <span class="badge bg-warning">
                                                                                            <i class="fas fa-star"></i> Yes
                                                                                        </span>
                                                                                <?php else: ?>
                                                                                        <span class="badge bg-secondary">No</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <div class="btn-group btn-group-sm" role="group">
                                                                                    <?php if ($category['level'] < 3): ?>
                                                                                            <a href="add-sub-category.php?parent_id=<?php echo $category['id']; ?>" 
                                                                                               class="btn btn-outline-success" title="Add Child">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                    <?php endif; ?>
                                                                                    <a href="edit-category.php?id=<?php echo $category['id']; ?>" 
                                                                                       class="btn btn-outline-primary" title="Edit">
                                                                                        <i class="fas fa-edit"></i>
                                                                                    </a>
                                                                                    <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                                                                            class="btn btn-outline-danger" title="Delete">
                                                                                        <i class="fas fa-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                            <?php endif; ?>
                                    <?php endif; ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        function toggleChildren(element) {
            const childrenContainer = element.closest('.category-item').querySelector('.children');
            const icon = element.querySelector('i');
            
            if (childrenContainer.classList.contains('collapsed')) {
                childrenContainer.classList.remove('collapsed');
                icon.className = 'fas fa-chevron-down';
            } else {
                childrenContainer.classList.add('collapsed');
                icon.className = 'fas fa-chevron-right';
            }
        }

        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                window.location.href = 'delete-category.php?id=' + categoryId;
            }
        }

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
                    // Update badges matching this category ID
                    document.querySelectorAll(`#statusBadge_tree_${categoryId}, #statusBadge_tbl_${categoryId}`).forEach(badge => {
                        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        if (newStatus === 'active') {
                            badge.classList.remove('bg-danger');
                            badge.classList.add('bg-success');
                        } else {
                            badge.classList.remove('bg-success');
                            badge.classList.add('bg-danger');
                        }
                    });
                } else {
                    alert('Error updating status: ' + (data.error || 'Unknown error'));
                    switchElem.checked = !isChecked;
                }
            })
            .catch(err => {
                switchElem.disabled = false;
                alert('Network error while updating status.');
                switchElem.checked = !isChecked;
            });
        }

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
                    document.querySelectorAll(`#headerBadge_tree_${categoryId}, #headerBadge_tbl_${categoryId}`).forEach(badge => {
                        badge.textContent = (badge.id.includes('tree') ? 'Header: ' : '') + newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        if (newStatus === 'show') {
                            badge.classList.remove('bg-secondary');
                            badge.classList.add('bg-primary');
                        } else {
                            badge.classList.remove('bg-primary');
                            badge.classList.add('bg-secondary');
                        }
                    });
                } else {
                    alert('Error updating header status: ' + (data.error || 'Unknown error'));
                    switchElem.checked = !isChecked;
                }
            })
            .catch(err => {
                switchElem.disabled = false;
                alert('Network error while updating header status.');
                switchElem.checked = !isChecked;
            });
        }

        // Initialize - collapse all subcategories in tree view
        document.addEventListener('DOMContentLoaded', function() {
            // Collapse all children containers by default
            document.querySelectorAll('.children').forEach(function(child) {
                child.classList.add('collapsed');
            });
            
            // Update toggle icons
            document.querySelectorAll('.toggle-children').forEach(function(toggle) {
                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-chevron-right';
                }
            });
            
            // Auto-expand if we're filtering by parent
            <?php if ($parent_filter > 0): ?>
                    // Find and expand the parent category
                    const parentId = <?php echo $parent_filter; ?>;
                    const parentItem = document.querySelector(`[data-category-id="${parentId}"]`);
                    if (parentItem) {
                        const toggleBtn = parentItem.querySelector('.toggle-children');
                        if (toggleBtn) {
                            toggleChildren(toggleBtn);
                        }
                    }
            <?php endif; ?>
        });
    </script>
</body>
</html>