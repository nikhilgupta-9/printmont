<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$database = new Database();
$conn = $database->getConnection();
$controller = new CategoryController();

$error   = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// -------------------------------------------------------------
// AJAX API ENDPOINTS
// -------------------------------------------------------------
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Toggle Desktop / Mobile Home Show
    if ($action === 'toggle_home_show') {
        $catId = (int)($_POST['id'] ?? 0);
        $device = $_POST['device'] ?? 'desktop'; // desktop or mobile
        $col = ($device === 'mobile') ? 'mobile_home_show' : 'desktop_home_show';
        $newVal = (isset($_POST['show']) && $_POST['show'] === 'yes') ? 'yes' : 'no';

        if ($catId > 0) {
            $stmt = $conn->prepare("UPDATE categories SET `{$col}` = ? WHERE id = ?");
            $stmt->bind_param("si", $newVal, $catId);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'value' => $newVal,
                    'device' => $device,
                    'message' => ucfirst($device) . ' home page display updated to ' . strtoupper($newVal)
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        }
        exit;
    }

    // Update Home Sort Order
    if ($action === 'update_home_order') {
        $catId = (int)($_POST['id'] ?? 0);
        $device = $_POST['device'] ?? 'desktop';
        $order = (int)($_POST['order'] ?? 0);
        $col = ($device === 'mobile') ? 'mobile_home_order' : 'desktop_home_order';

        if ($catId > 0) {
            $stmt = $conn->prepare("UPDATE categories SET `{$col}` = ? WHERE id = ?");
            $stmt->bind_param("ii", $order, $catId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
        exit;
    }

    // Update Home Design
    if ($action === 'update_home_design') {
        $catId = (int)($_POST['id'] ?? 0);
        $device = $_POST['device'] ?? 'desktop';
        $design = $_POST['design'] ?? 'home_design_1';
        $col = ($device === 'mobile') ? 'mobile_home_design' : 'desktop_home_design';

        if ($catId > 0 && in_array($design, ['home_design_1','home_design_2','home_design_3','home_design_4'])) {
            $stmt = $conn->prepare("UPDATE categories SET `{$col}` = ? WHERE id = ?");
            $stmt->bind_param("si", $design, $catId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'design' => $design]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
        exit;
    }

    // Update Mobile Format (4, 6, 8)
    if ($action === 'update_mobile_format') {
        $catId = (int)($_POST['id'] ?? 0);
        $format = $_POST['format'] ?? '4';
        if ($catId > 0 && in_array($format, ['4','6','8'])) {
            $stmt = $conn->prepare("UPDATE categories SET mobile_home_format = ? WHERE id = ?");
            $stmt->bind_param("si", $format, $catId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'format' => $format]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
        exit;
    }

    // Search Products for Extra Products Assignment
    if ($action === 'search_products') {
        $q = trim($_POST['q'] ?? '');
        $limit = 20;
        if (!empty($q)) {
            $like = "%{$q}%";
            $isNum = is_numeric($q) ? (int)$q : 0;
            $pSql = "SELECT id, name, sku, price, offer_price, thumbnail_image FROM products 
                     WHERE status = 'active' AND (name LIKE ? OR sku LIKE ? OR id = ?) 
                     ORDER BY (name LIKE ?) DESC, id DESC LIMIT ?";
            $stmt = $conn->prepare($pSql);
            $startLike = "{$q}%";
            $stmt->bind_param("ssisi", $like, $like, $isNum, $startLike, $limit);
        } else {
            $pSql = "SELECT id, name, sku, price, offer_price, thumbnail_image FROM products WHERE status = 'active' ORDER BY id DESC LIMIT ?";
            $stmt = $conn->prepare($pSql);
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        $prods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $prods]);
        exit;
    }

    // Save Extra Products ("extra product ke liye")
    if ($action === 'save_extra_products') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $rawIds = $_POST['product_ids'] ?? [];
        $pIds = [];
        if (is_array($rawIds)) {
            $pIds = array_filter(array_map('intval', $rawIds));
        } elseif (is_string($rawIds)) {
            $pIds = array_filter(array_map('intval', explode(',', $rawIds)));
        }
        $pIdsStr = !empty($pIds) ? implode(',', $pIds) : null;

        if ($catId > 0) {
            $stmt = $conn->prepare("UPDATE categories SET home_featured_product_ids = ? WHERE id = ?");
            $stmt->bind_param("si", $pIdsStr, $catId);
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'count' => count($pIds),
                    'product_ids' => $pIdsStr,
                    'message' => count($pIds) . ' extra products saved for homepage showcase!'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
        exit;
    }

    // Get Extra Products Details for a Category
    if ($action === 'get_extra_products') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $res = $conn->query("SELECT home_featured_product_ids FROM categories WHERE id = $catId");
            $row = $res ? $res->fetch_assoc() : null;
            $rawIds = $row['home_featured_product_ids'] ?? '';
            $ids = array_filter(array_map('intval', explode(',', $rawIds)));
            $products = [];
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $pSql = "SELECT id, name, sku, price, offer_price, thumbnail_image FROM products 
                         WHERE id IN ($placeholders) 
                         ORDER BY FIELD(id, " . implode(',', $ids) . ")";
                $stmt = $conn->prepare($pSql);
                $types = str_repeat('i', count($ids));
                $stmt->bind_param($types, ...$ids);
                $stmt->execute();
                $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            echo json_encode(['success' => true, 'data' => $products, 'ids' => $rawIds]);
            exit;
        }
    }
}

// -------------------------------------------------------------
// FETCH ALL CATEGORIES FOR HOME PAGE DASHBOARD
// -------------------------------------------------------------
$allCatsSql = "SELECT c.*, p.name as parent_name,
               (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id AND pr.status = 'active') as catalog_product_count
               FROM categories c 
               LEFT JOIN categories p ON c.parent_id = p.id 
               ORDER BY c.level ASC, 
                        CASE WHEN c.desktop_home_order > 0 THEN c.desktop_home_order ELSE 9999 END ASC, 
                        c.name ASC";
$allCatsRes = $conn->query($allCatsSql);
$categories = [];
$desktopHomeCount = 0;
$mobileHomeCount = 0;
$extraProductsCount = 0;

if ($allCatsRes) {
    while ($row = $allCatsRes->fetch_assoc()) {
        $categories[] = $row;
        if (($row['desktop_home_show'] ?? 'no') === 'yes') $desktopHomeCount++;
        if (($row['mobile_home_show'] ?? 'no') === 'yes') $mobileHomeCount++;
        if (!empty($row['home_featured_product_ids'])) {
            $extraProductsCount += count(explode(',', $row['home_featured_product_ids']));
        }
    }
}

$activeTab = $_GET['tab'] ?? 'desktop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Home Page Category (Desktop &amp; Mobile) | PrintMont Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="js/settings.js"></script>

    <style>
        :root {
            --pm-primary: #0b53a1;
            --pm-primary-dark: #073b75;
            --pm-primary-light: #e0edff;
            --pm-border: #e2e8f0;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6fa; }

        /* Module Top Navigation Pills (Standard across all 7 Manage Categories pages) */
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid var(--pm-border); }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: var(--pm-primary); background: var(--pm-primary-light); }
        .module-nav-link.active { color: #fff; background: var(--pm-primary); }

        /* KPI Cards */
        .kpi-card { background: #ffffff; border: 1px solid var(--pm-border); border-radius: 10px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .kpi-icon-box { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }

        .cat-thumb-box {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            object-fit: cover;
            background: #f8fafc;
        }
        .design-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .design-1 { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .design-2 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .design-3 { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .design-4 { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

        .extra-product-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            font-size: 12px;
            margin: 2px;
        }
        .extra-product-chip img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
        }
        .extra-product-chip .remove-p {
            cursor: pointer;
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        
        <main class="content p-3 p-md-4">
            <div class="container-fluid p-0">

                <!-- Module Navigation Pills (7 items of Manage Categories) -->
                <div class="module-nav-pills mb-4">
                    <a href="add-main-category.php" class="module-nav-link">
                        <i class="fas fa-folder-plus text-primary"></i> 1. Main Category
                    </a>
                    <a href="view-categories.php?level=1" class="module-nav-link">
                        <i class="fas fa-folder text-secondary"></i> 2. Main All Categories
                    </a>
                    <a href="add-sub-category.php" class="module-nav-link">
                        <i class="fas fa-folder-tree text-success"></i> 3. Sub Category
                    </a>
                    <a href="view-categories.php?level=2" class="module-nav-link">
                        <i class="fas fa-list text-secondary"></i> 4. Sub All Category
                    </a>
                    <a href="add-sub-sub-category.php" class="module-nav-link">
                        <i class="fas fa-tags text-info"></i> 5. Sub Sub Category
                    </a>
                    <a href="view-categories.php?level=3" class="module-nav-link">
                        <i class="fas fa-list-check text-secondary"></i> 6. Sub Sub All Category
                    </a>
                    <a href="home-page-categories.php" class="module-nav-link active">
                        <i class="fas fa-home text-warning"></i> 7. Home page Category (Desktop &amp; Mobile)
                    </a>
                </div>

                <!-- Page Header Title -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            <i class="fas fa-home text-warning me-2"></i> Home Page Category (Desktop and Mobile) &bull; Extra Product Management
                        </h2>
                        <p class="text-muted small mb-0">
                            Configure which categories appear on the Home Page, select layout designs (4 Types), mobile display formats (4/6/8), and assign extra featured showcase products.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="add-main-category.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Main Category
                        </a>
                        <a href="view-categories.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list me-1"></i> All Categories
                        </a>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Desktop Home Live</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $desktopHomeCount; ?> Categories</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-success-subtle text-success">
                                <i class="fas fa-mobile-screen"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Mobile Home Live</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $mobileHomeCount; ?> Categories</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-warning-subtle text-warning">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Extra Products Attached</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $extraProductsCount; ?> Products</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-info-subtle text-info">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Total Categories</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo count($categories); ?> Items</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WORKSPACE TABS -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom p-3">
                        <ul class="nav nav-pills" id="homeCatTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold px-3 py-2" id="desktop-tab" data-bs-toggle="tab" data-bs-target="#desktopTabPane" type="button">
                                    <i class="fas fa-desktop me-1 text-primary"></i> 1. Desktop Home Page Categories (<?php echo $desktopHomeCount; ?>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold px-3 py-2" id="mobile-tab" data-bs-toggle="tab" data-bs-target="#mobileTabPane" type="button">
                                    <i class="fas fa-mobile-alt me-1 text-success"></i> 2. Mobile Home Page Categories (<?php echo $mobileHomeCount; ?>)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold px-3 py-2" id="extra-tab" data-bs-toggle="tab" data-bs-target="#extraTabPane" type="button">
                                    <i class="fas fa-star me-1 text-warning"></i> 3. Extra Product Assignment ("extra product ke liye")
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        <div class="tab-content" id="homeCatTabContent">

                            <!-- TAB 1: DESKTOP HOME PAGE CATEGORIES -->
                            <div class="tab-pane fade show active" id="desktopTabPane" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark">Desktop Home Page Category Display</h5>
                                        <p class="text-muted small mb-0">Toggle show/hide, set order sequence, choose view design (1 to 4), and assign extra products.</p>
                                    </div>
                                    <span class="badge bg-light text-primary border px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i> Live on Desktop Homepage
                                    </span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle border">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">Thumb</th>
                                                <th>Category Name &amp; Level</th>
                                                <th>Home Display</th>
                                                <th style="width: 120px;">Sort Order</th>
                                                <th>View Design</th>
                                                <th>Extra Products</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $cat): 
                                                $isDeskShow = ($cat['desktop_home_show'] ?? 'no') === 'yes';
                                                $deskDesign = $cat['desktop_home_design'] ?: 'home_design_1';
                                                $img = $cat['desktop_image'] ?: ($cat['image'] ?: 'img/placeholder.png');
                                                $extraIds = array_filter(explode(',', $cat['home_featured_product_ids'] ?? ''));
                                            ?>
                                                <tr id="row_desk_<?php echo $cat['id']; ?>">
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="cat-thumb-box" alt="" onerror="this.src='https://placehold.co/44x44?text=CAT'">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></div>
                                                        <div class="small text-muted">
                                                            <span class="badge bg-secondary-subtle text-secondary border me-1">Level <?php echo $cat['level']; ?></span>
                                                            <?php if ($cat['parent_name']): ?>
                                                                <span>&lsaquo; <?php echo htmlspecialchars($cat['parent_name']); ?></span>
                                                            <?php endif; ?>
                                                            &bull; <code><?php echo htmlspecialchars($cat['slug']); ?></code>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="toggle_desk_<?php echo $cat['id']; ?>" 
                                                                   <?php echo $isDeskShow ? 'checked' : ''; ?>
                                                                   onchange="toggleHomeShow(<?php echo $cat['id']; ?>, 'desktop', this.checked)">
                                                            <label class="form-check-label small fw-semibold" for="toggle_desk_<?php echo $cat['id']; ?>">
                                                                <?php echo $isDeskShow ? '<span class="text-success">Show</span>' : '<span class="text-muted">Hide</span>'; ?>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold" 
                                                               value="<?php echo (int)($cat['desktop_home_order'] ?? 0); ?>" min="0"
                                                               onchange="updateHomeOrder(<?php echo $cat['id']; ?>, 'desktop', this.value)">
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" onchange="updateHomeDesign(<?php echo $cat['id']; ?>, 'desktop', this.value)">
                                                            <option value="home_design_1" <?php echo $deskDesign === 'home_design_1' ? 'selected' : ''; ?>>Design 1 (Mosaic Grid)</option>
                                                            <option value="home_design_2" <?php echo $deskDesign === 'home_design_2' ? 'selected' : ''; ?>>Design 2 (Slide Carousel)</option>
                                                            <option value="home_design_3" <?php echo $deskDesign === 'home_design_3' ? 'selected' : ''; ?>>Design 3 (Product Matrix)</option>
                                                            <option value="home_design_4" <?php echo $deskDesign === 'home_design_4' ? 'selected' : ''; ?>>Design 4 (Banner Strip)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo count($extraIds) > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border'; ?> px-2 py-1">
                                                            <?php echo count($extraIds); ?> Extra Products
                                                        </span>
                                                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 ms-1" onclick="openExtraProductsModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')">
                                                            <i class="fas fa-edit"></i> Manage
                                                        </button>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="edit-category.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Category Settings">
                                                            <i class="fas fa-cog"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 2: MOBILE HOME PAGE CATEGORIES -->
                            <div class="tab-pane fade" id="mobileTabPane" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark">Mobile Home Page Category Display</h5>
                                        <p class="text-muted small mb-0">Manage mobile home sections, choose format (4, 6, 8 image grid), design wireframes, and sort order.</p>
                                    </div>
                                    <span class="badge bg-light text-success border px-3 py-2">
                                        <i class="fas fa-mobile-screen me-1"></i> Live on Mobile App/Web
                                    </span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle border">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">Thumb</th>
                                                <th>Category Name &amp; Level</th>
                                                <th>Mobile Home Show</th>
                                                <th style="width: 110px;">Sort Order</th>
                                                <th>Mobile Format</th>
                                                <th>Mobile Design</th>
                                                <th>Extra Products</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $cat): 
                                                $isMobShow = ($cat['mobile_home_show'] ?? 'no') === 'yes';
                                                $mobDesign = $cat['mobile_home_design'] ?: 'home_design_1';
                                                $mobFormat = $cat['mobile_home_format'] ?: '4';
                                                $img = $cat['mobile_image'] ?: ($cat['desktop_image'] ?: ($cat['image'] ?: 'img/placeholder.png'));
                                                $extraIds = array_filter(explode(',', $cat['home_featured_product_ids'] ?? ''));
                                            ?>
                                                <tr id="row_mob_<?php echo $cat['id']; ?>">
                                                    <td>
                                                        <img src="<?php echo htmlspecialchars($img); ?>" class="cat-thumb-box" alt="" onerror="this.src='https://placehold.co/44x44?text=CAT'">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></div>
                                                        <div class="small text-muted">
                                                            <span class="badge bg-secondary-subtle text-secondary border me-1">Level <?php echo $cat['level']; ?></span>
                                                            &bull; Topbar: <strong><?php echo strtoupper($cat['mobile_topbar_status'] ?? 'show'); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" role="switch" id="toggle_mob_<?php echo $cat['id']; ?>" 
                                                                   <?php echo $isMobShow ? 'checked' : ''; ?>
                                                                   onchange="toggleHomeShow(<?php echo $cat['id']; ?>, 'mobile', this.checked)">
                                                            <label class="form-check-label small fw-semibold" for="toggle_mob_<?php echo $cat['id']; ?>">
                                                                <?php echo $isMobShow ? '<span class="text-success">Show</span>' : '<span class="text-muted">Hide</span>'; ?>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm text-center fw-bold" 
                                                               value="<?php echo (int)($cat['mobile_home_order'] ?? 0); ?>" min="0"
                                                               onchange="updateHomeOrder(<?php echo $cat['id']; ?>, 'mobile', this.value)">
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" onchange="updateMobileFormat(<?php echo $cat['id']; ?>, this.value)">
                                                            <option value="4" <?php echo $mobFormat === '4' ? 'selected' : ''; ?>>4 Product Box (2x2)</option>
                                                            <option value="6" <?php echo $mobFormat === '6' ? 'selected' : ''; ?>>6 Product Box (2x3)</option>
                                                            <option value="8" <?php echo $mobFormat === '8' ? 'selected' : ''; ?>>8 Product Box (2x4)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" onchange="updateHomeDesign(<?php echo $cat['id']; ?>, 'mobile', this.value)">
                                                            <option value="home_design_1" <?php echo $mobDesign === 'home_design_1' ? 'selected' : ''; ?>>Design 1 (Featured Split)</option>
                                                            <option value="home_design_2" <?php echo $mobDesign === 'home_design_2' ? 'selected' : ''; ?>>Design 2 (Icon Carousel)</option>
                                                            <option value="home_design_3" <?php echo $mobDesign === 'home_design_3' ? 'selected' : ''; ?>>Design 3 (Product Matrix)</option>
                                                            <option value="home_design_4" <?php echo $mobDesign === 'home_design_4' ? 'selected' : ''; ?>>Design 4 (Mini Banner Strip)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo count($extraIds) > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border'; ?> px-2 py-1">
                                                            <?php echo count($extraIds); ?> Extra Products
                                                        </span>
                                                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 ms-1" onclick="openExtraProductsModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="edit-category.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-cog"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 3: EXTRA PRODUCT ASSIGNMENT -->
                            <div class="tab-pane fade" id="extraTabPane" role="tabpanel">
                                <div class="p-3 bg-light rounded-3 border mb-4">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-tags text-warning me-1"></i> "Extra Product Ke Liye" &bull; Featured Product Showcase Assignment</h5>
                                            <p class="text-muted small mb-0">Select any category to assign extra high-converting featured products that display directly inside its Home Page layout.</p>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <select id="quickCatSelector" class="form-select form-select-sm" onchange="if(this.value) loadCategoryExtraProducts(this.value)">
                                                <option value="">-- Choose Category to Manage Extra Products --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?> (Level <?php echo $cat['level']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="extraProductsManagerArea" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-dark mb-0">Currently Attached Extra Products for: <span id="targetCatName" class="text-primary"></span></h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openProductSearchPicker()">
                                            <i class="fas fa-plus me-1"></i> Search &amp; Add More Products
                                        </button>
                                    </div>

                                    <div id="attachedProductsContainer" class="p-3 bg-white border rounded mb-3 min-vh-25">
                                        <!-- Chips loaded via JS -->
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-success fw-bold px-4" onclick="saveAttachedProducts()">
                                            <i class="fas fa-save me-1"></i> Save Extra Products Changes
                                        </button>
                                    </div>
                                </div>

                                <div id="emptySelectNotice" class="text-center py-5 text-muted">
                                    <i class="fas fa-hand-pointer fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                    <h5>Select a category from the dropdown above to manage its extra home page showcase products.</h5>
                                    <p class="small text-muted">Extra products will appear in the chosen Home View Design (e.g. Design 1 split grid, Design 3 matrix, etc.).</p>
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

<!-- EXTRA PRODUCTS PICKER MODAL -->
<div class="modal fade" id="extraProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-box-open me-2"></i> Assign Extra Products to: <span id="modalCatTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="modalActiveCatId">
                
                <!-- Search Box -->
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="modalProductSearchInput" class="form-control" placeholder="Search by product name, SKU, or ID..." oninput="debounceModalSearch()">
                    <button class="btn btn-outline-secondary" type="button" onclick="performModalSearch()">Search</button>
                </div>

                <!-- Product Results -->
                <div class="table-responsive border rounded" style="max-height: 380px;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 40px;">Select</th>
                                <th style="width: 50px;">Img</th>
                                <th>Product Details</th>
                                <th>SKU</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody id="modalSearchResultsBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Loading products...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Selected Items Pill Summary -->
                <div class="mt-3 p-2 bg-light rounded border">
                    <span class="small fw-bold text-muted text-uppercase d-block mb-1">Selected Extra Products (<span id="modalSelectedCount">0</span>):</span>
                    <div id="modalSelectedPills" class="d-flex flex-wrap gap-1"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-bold px-4" onclick="commitModalSelection()">
                    <i class="fas fa-check me-1"></i> Apply &amp; Save Extra Products
                </button>
            </div>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
<script>
let currentActiveCatId = 0;
let currentAttachedIds = [];
let modalSelectedMap = new Map(); // id -> product object
let searchDebounceTimer = null;

// AJAX: Toggle Home Show
function toggleHomeShow(id, device, isChecked) {
    const val = isChecked ? 'yes' : 'no';
    const fd = new FormData();
    fd.append('action', 'toggle_home_show');
    fd.append('id', id);
    fd.append('device', device);
    fd.append('show', val);

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // update label
                const label = document.querySelector(`label[for="toggle_${device==='mobile'?'mob':'desk'}_${id}"]`);
                if (label) {
                    label.innerHTML = isChecked ? '<span class="text-success">Show</span>' : '<span class="text-muted">Hide</span>';
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to update'));
            }
        });
}

// AJAX: Update Home Order
function updateHomeOrder(id, device, order) {
    const fd = new FormData();
    fd.append('action', 'update_home_order');
    fd.append('id', id);
    fd.append('device', device);
    fd.append('order', order);
    fetch('home-page-categories.php', { method: 'POST', body: fd });
}

// AJAX: Update Home Design
function updateHomeDesign(id, device, design) {
    const fd = new FormData();
    fd.append('action', 'update_home_design');
    fd.append('id', id);
    fd.append('device', device);
    fd.append('design', design);
    fetch('home-page-categories.php', { method: 'POST', body: fd });
}

// AJAX: Update Mobile Format
function updateMobileFormat(id, format) {
    const fd = new FormData();
    fd.append('action', 'update_mobile_format');
    fd.append('id', id);
    fd.append('format', format);
    fetch('home-page-categories.php', { method: 'POST', body: fd });
}

// Modal extra products management
function openExtraProductsModal(catId, catName) {
    currentActiveCatId = catId;
    document.getElementById('modalActiveCatId').value = catId;
    document.getElementById('modalCatTitle').textContent = catName;
    modalSelectedMap.clear();

    const myModal = new bootstrap.Modal(document.getElementById('extraProductsModal'));
    myModal.show();

    // Fetch existing attached products
    const fd = new FormData();
    fd.append('action', 'get_extra_products');
    fd.append('category_id', catId);

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                data.data.forEach(p => modalSelectedMap.set(Number(p.id), p));
            }
            renderModalSelectedPills();
            performModalSearch();
        });
}

function debounceModalSearch() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        performModalSearch();
    }, 300);
}

function performModalSearch() {
    const q = document.getElementById('modalProductSearchInput').value.trim();
    const tbody = document.getElementById('modalSearchResultsBody');
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Searching...</td></tr>`;

    const fd = new FormData();
    fd.append('action', 'search_products');
    fd.append('q', q);

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                tbody.innerHTML = data.data.map(p => {
                    const isChecked = modalSelectedMap.has(Number(p.id));
                    const img = p.thumbnail_image ? (p.thumbnail_image.startsWith('http') ? p.thumbnail_image : '/' + p.thumbnail_image) : 'https://placehold.co/36x36?text=P';
                    return `
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input" ${isChecked ? 'checked' : ''} 
                                       onchange="toggleModalProductSelection(${p.id}, ${escapeJsonStr(JSON.stringify(p))}, this.checked)">
                            </td>
                            <td><img src="${img}" style="width:36px;height:36px;object-fit:cover;border-radius:4px;" onerror="this.src='https://placehold.co/36x36?text=P'"></td>
                            <td>
                                <div class="fw-semibold text-dark">${escapeHtml(p.name)}</div>
                                <small class="text-muted">ID #${p.id}</small>
                            </td>
                            <td><code>${escapeHtml(p.sku || 'N/A')}</code></td>
                            <td><span class="fw-bold text-success">₹${p.offer_price || p.price || 0}</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No products found matching "${escapeHtml(q)}".</td></tr>`;
            }
        });
}

function toggleModalProductSelection(id, pObj, isChecked) {
    if (isChecked) {
        modalSelectedMap.set(Number(id), pObj);
    } else {
        modalSelectedMap.delete(Number(id));
    }
    renderModalSelectedPills();
}

function renderModalSelectedPills() {
    const container = document.getElementById('modalSelectedPills');
    const countSpan = document.getElementById('modalSelectedCount');
    countSpan.textContent = modalSelectedMap.size;

    if (modalSelectedMap.size === 0) {
        container.innerHTML = '<span class="text-muted small">No extra products selected yet.</span>';
        return;
    }

    let html = '';
    modalSelectedMap.forEach((p, id) => {
        const img = p.thumbnail_image ? (p.thumbnail_image.startsWith('http') ? p.thumbnail_image : '/' + p.thumbnail_image) : 'https://placehold.co/20x20?text=P';
        html += `
            <span class="extra-product-chip">
                <img src="${img}" alt="" onerror="this.src='https://placehold.co/20x20?text=P'">
                <span>${escapeHtml(p.name)}</span>
                <span class="remove-p" onclick="removeModalSelected(${id})">&times;</span>
            </span>
        `;
    });
    container.innerHTML = html;
}

function removeModalSelected(id) {
    modalSelectedMap.delete(Number(id));
    renderModalSelectedPills();
    // also uncheck in table if visible
    performModalSearch();
}

function commitModalSelection() {
    const catId = document.getElementById('modalActiveCatId').value;
    const selectedIds = Array.from(modalSelectedMap.keys());

    const fd = new FormData();
    fd.append('action', 'save_extra_products');
    fd.append('category_id', catId);
    selectedIds.forEach(id => fd.append('product_ids[]', id));

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('extraProductsModal'));
                if (modal) modal.hide();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to save'));
            }
        });
}

// Tab 3 Dedicated Area functions
function loadCategoryExtraProducts(catId) {
    const selector = document.getElementById('quickCatSelector');
    const selectedText = selector.options[selector.selectedIndex].text;
    document.getElementById('targetCatName').textContent = selectedText;
    document.getElementById('emptySelectNotice').style.display = 'none';
    document.getElementById('extraProductsManagerArea').style.display = 'block';
    currentActiveCatId = catId;

    const fd = new FormData();
    fd.append('action', 'get_extra_products');
    fd.append('category_id', catId);

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            modalSelectedMap.clear();
            if (data.success && data.data) {
                data.data.forEach(p => modalSelectedMap.set(Number(p.id), p));
            }
            renderAttachedTabProducts();
        });
}

function renderAttachedTabProducts() {
    const container = document.getElementById('attachedProductsContainer');
    if (modalSelectedMap.size === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-box-open fs-2 text-warning mb-2 d-block"></i>
                <div>No extra products attached to this category yet.</div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="openProductSearchPicker()">
                    <i class="fas fa-plus me-1"></i> Add Extra Products Now
                </button>
            </div>
        `;
        return;
    }

    let html = '<div class="d-flex flex-wrap gap-2">';
    modalSelectedMap.forEach((p, id) => {
        const img = p.thumbnail_image ? (p.thumbnail_image.startsWith('http') ? p.thumbnail_image : '/' + p.thumbnail_image) : 'https://placehold.co/24x24?text=P';
        html += `
            <div class="card p-2 d-flex flex-row align-items-center gap-2 border shadow-sm" style="width: 260px;">
                <img src="${img}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" onerror="this.src='https://placehold.co/40x40?text=P'">
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-bold small text-truncate text-dark" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</div>
                    <div class="small text-muted">₹${p.offer_price || p.price || 0} &bull; SKU: ${escapeHtml(p.sku || '')}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeAttachedTabProduct(${id})">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function removeAttachedTabProduct(id) {
    modalSelectedMap.delete(Number(id));
    renderAttachedTabProducts();
}

function openProductSearchPicker() {
    const catName = document.getElementById('targetCatName').textContent;
    openExtraProductsModal(currentActiveCatId, catName);
}

function saveAttachedProducts() {
    const selectedIds = Array.from(modalSelectedMap.keys());
    const fd = new FormData();
    fd.append('action', 'save_extra_products');
    fd.append('category_id', currentActiveCatId);
    selectedIds.forEach(id => fd.append('product_ids[]', id));

    fetch('home-page-categories.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Success: ' + data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to save'));
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function escapeJsonStr(json) {
    return json.replace(/"/g, '&quot;');
}
</script>
</body>
</html>
