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

$controller = new CategoryController();
$database = new Database();
$conn = $database->getConnection();

// AJAX: cascading dropdown data
if (isset($_GET['get_subs']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubCategories((int)$_GET['parent_id']));
    exit;
}
if (isset($_GET['get_subsubs']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubSubCategories((int)$_GET['parent_id']));
    exit;
}

// Handle AJAX Status Toggle
if (isset($_POST['action']) && $_POST['action'] === 'toggle_menu_page_status') {
    header('Content-Type: application/json');
    $catId = (int)($_POST['id'] ?? 0);
    $newStatus = (isset($_POST['status']) && $_POST['status'] === 'show') ? 'show' : 'hide';

    if ($catId > 0) {
        $stmt = $conn->prepare("UPDATE categories SET desktop_menu_status = ?, mobile_topbar_status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $newStatus, $catId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'status' => $newStatus]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    }
    exit;
}

$mainCategories = $controller->getMainCategories();
$error   = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$selectedId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
$selected   = $selectedId ? $controller->getCategoryById($selectedId) : null;

// Resolve ancestor chain for cascading dropdown prefill & breadcrumbs
$mainId = 0; $subId = 0; $subSubId = 0;
$ancestors = [];
if ($selected) {
    if ((int)$selected['level'] === 3) {
        $subSubId = (int)$selected['id'];
        $subCat   = $controller->getCategoryById((int)$selected['parent_id']);
        $subId    = $subCat ? (int)$subCat['id'] : 0;
        $mainCat  = $subCat ? $controller->getCategoryById((int)$subCat['parent_id']) : null;
        $mainId   = $mainCat ? (int)$mainCat['id'] : 0;
        if ($mainCat) $ancestors[] = $mainCat;
        if ($subCat)  $ancestors[] = $subCat;
    } elseif ((int)$selected['level'] === 2) {
        $subId   = (int)$selected['id'];
        $mainCat = $controller->getCategoryById((int)$selected['parent_id']);
        $mainId  = $mainCat ? (int)$mainCat['id'] : 0;
        if ($mainCat) $ancestors[] = $mainCat;
    } else {
        $mainId = (int)$selected['id'];
    }
}
$subCategories    = $mainId ? $controller->getSubCategories($mainId) : [];
$subSubCategories = $subId  ? $controller->getSubSubCategories($subId) : [];

// Next/Prev Stepper among siblings (e.g. within same level & parent)
$prevCat = null;
$nextCat = null;
if ($selected) {
    $parentFilter = (int)$selected['parent_id'];
    $levelFilter  = (int)$selected['level'];
    $sibRes = $conn->query("SELECT id, name, level, parent_id FROM categories WHERE parent_id = {$parentFilter} AND level = {$levelFilter} ORDER BY CASE WHEN desktop_menu_order > 0 THEN desktop_menu_order ELSE display_order END ASC, name ASC");
    if ($sibRes) {
        $sibList = [];
        $currIdx = -1;
        while ($r = $sibRes->fetch_assoc()) {
            $sibList[] = $r;
        }
        foreach ($sibList as $idx => $sItem) {
            if ((int)$sItem['id'] === $selectedId) {
                $currIdx = $idx;
                break;
            }
        }
        if ($currIdx > 0) $prevCat = $sibList[$currIdx - 1];
        if ($currIdx >= 0 && $currIdx < count($sibList) - 1) $nextCat = $sibList[$currIdx + 1];
    }
}

// KPI Metrics for top header stats
$totalCategoriesCount = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0] ?? 0;
$mainCatsCount        = $conn->query("SELECT COUNT(*) FROM categories WHERE level = 1")->fetch_row()[0] ?? 0;
$subCatsCount         = $conn->query("SELECT COUNT(*) FROM categories WHERE level = 2")->fetch_row()[0] ?? 0;
$subSubCatsCount      = $conn->query("SELECT COUNT(*) FROM categories WHERE level = 3")->fetch_row()[0] ?? 0;
$activeInsideCount    = $conn->query("SELECT COUNT(*) FROM categories WHERE desktop_menu_status = 'show'")->fetch_row()[0] ?? 0;

$designOptions = [
    'design1' => ['fa-th-large', 'Design 1 (Grid Cards)', 'Grid cards with thumbnail & title layout'],
    'design2' => ['fa-image', 'Design 2 (Banner Overlay)', 'Wide banner with overlay text & highlight'],
    'design3' => ['fa-list-ul', 'Design 3 (Clean List)', 'Clean vertical grouped list layout'],
    'design4' => ['fa-gem', 'Design 4 (Featured Badge)', 'Featured card with badge highlight & accents'],
];

function uploadInsideImg($fileKey, $subdir, $existing = '') {
    if (empty($_FILES[$fileKey]['name'])) return $existing;
    $file = $_FILES[$fileKey];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) || $file['size'] > 5*1024*1024) return $existing;
    $dir = __DIR__ . "/uploads/category/{$subdir}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if ($existing && file_exists(__DIR__ . '/' . $existing) && strpos($existing, 'http') === false) @unlink(__DIR__ . '/' . $existing);
    $fn = uniqid() . '_' . time() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$subdir}/{$fn}" : $existing;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!$selectedId || !$selected) {
        $error = 'Please select a category first.';
    } else {
        $customUrl = trim($_POST['custom_url'] ?? '');
        $status = $_POST['desktop_menu_status'] ?? 'show';
        $order = (int)($_POST['desktop_menu_order'] ?? 0);
        $design = $_POST['desktop_menu_design'] ?? 'design1';

        $titleName = trim($_POST['name'] ?? '');

        // Inside Image 1
        $img1 = $selected['desktop_menu_image'] ?? '';
        if (!empty($_POST['remove_image_1'])) {
            if ($img1 && file_exists(__DIR__ . '/' . $img1) && strpos($img1, 'http') === false) @unlink(__DIR__ . '/' . $img1);
            $img1 = null;
        } else {
            $uploaded1 = uploadInsideImg('desktop_menu_image', 'menu', $img1);
            if ($uploaded1) $img1 = $uploaded1;
        }

        // Inside Image 2
        $img2 = $selected['desktop_menu_image_2'] ?? '';
        if (!empty($_POST['remove_image_2'])) {
            if ($img2 && file_exists(__DIR__ . '/' . $img2) && strpos($img2, 'http') === false) @unlink(__DIR__ . '/' . $img2);
            $img2 = null;
        } else {
            $uploaded2 = uploadInsideImg('desktop_menu_image_2', 'menu', $img2);
            if ($uploaded2) $img2 = $uploaded2;
        }

        // Inside Image 3
        $img3 = $selected['desktop_menu_image_3'] ?? '';
        if (!empty($_POST['remove_image_3'])) {
            if ($img3 && file_exists(__DIR__ . '/' . $img3) && strpos($img3, 'http') === false) @unlink(__DIR__ . '/' . $img3);
            $img3 = null;
        } else {
            $uploaded3 = uploadInsideImg('desktop_menu_image_3', 'menu', $img3);
            if ($uploaded3) $img3 = $uploaded3;
        }

        $title1 = trim($_POST['desktop_menu_title_1'] ?? '');
        $url1   = trim($_POST['desktop_menu_url_1'] ?? '');
        $title2 = trim($_POST['desktop_menu_title_2'] ?? '');
        $url2   = trim($_POST['desktop_menu_url_2'] ?? '');
        $title3 = trim($_POST['desktop_menu_title_3'] ?? '');
        $url3   = trim($_POST['desktop_menu_url_3'] ?? '');

        $data = [
            'desktop_menu_title'   => $titleName ?: null,
            'desktop_menu_design'  => $design,
            'desktop_menu_image'   => $img1 ?: null,
            'desktop_menu_image_2' => $img2 ?: null,
            'desktop_menu_image_3' => $img3 ?: null,
            'desktop_menu_title_1' => $title1 ?: null,
            'desktop_menu_url_1'   => $url1 ?: null,
            'desktop_menu_title_2' => $title2 ?: null,
            'desktop_menu_url_2'   => $url2 ?: null,
            'desktop_menu_title_3' => $title3 ?: null,
            'desktop_menu_url_3'   => $url3 ?: null,
            'desktop_menu_order'   => $order,
            'desktop_menu_status'  => $status,
            'custom_url'           => $customUrl ?: null,
            'mobile_topbar_status' => $status,
            'mobile_topbar_order'  => $order,
            'mobile_sidebar_order' => $order,
        ];

        if (empty($titleName)) {
            $error = 'Title Name is required.';
        } else {
            $controller->updateCategory($selectedId, $data);
            $_SESSION['success_message'] = 'Top menu inside page settings updated successfully for "' . htmlspecialchars($titleName) . '" (Category catalog unaffected).';
            header('Location: add-menu-page.php?category_id=' . $selectedId);
            exit;
        }
        $selected = $controller->getCategoryById($selectedId);
    }
}

// Default URL calculation
$defaultCategoryUrl = $selected ? ('/category/' . $selected['slug']) : '';
$currentUrlValue = ($selected && !empty($selected['custom_url'])) ? $selected['custom_url'] : $defaultCategoryUrl;

// Fetch active inside subcategories and sub-subcategories for table
$insideMenusRes = $conn->query("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id WHERE c.level IN (2, 3) ORDER BY c.level ASC, CASE WHEN c.desktop_menu_order > 0 THEN c.desktop_menu_order ELSE c.display_order END ASC, c.name ASC LIMIT 50");
$insideMenuList = [];
if ($insideMenusRes) {
    while ($row = $insideMenusRes->fetch_assoc()) {
        $insideMenuList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Dynamic Brand Favicon -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Inside Page Mega Menu Manager &bull; PrintMont Executive CRM</title>
    
    <!-- Modern Professional Fonts -->
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
            --pm-accent: #0284c7;
            --pm-success: #10b981;
            --pm-warning: #f59e0b;
            --pm-danger: #ef4444;
            --pm-dark: #0f172a;
            --pm-border: #e2e8f0;
            --pm-bg-subtle: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            opacity: 1 !important;
        }

        /* Top Module Header Navigation */
        .module-nav-pills {
            display: inline-flex;
            gap: 6px;
            background: #ffffff;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid var(--pm-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .module-nav-link {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .module-nav-link:hover {
            color: var(--pm-primary);
            background: var(--pm-bg-subtle);
        }

        .module-nav-link.active {
            background: var(--pm-primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.25);
        }

        /* KPI Quick Metric Cards */
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
        }
        .kpi-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        /* Executive Category Hero Banner */
        .category-hero-banner {
            background: linear-gradient(135deg, #0b53a1 0%, #073b75 60%, #031b33 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: #ffffff;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 24px rgba(11, 83, 161, 0.18);
            position: relative;
            overflow: hidden;
        }
        .category-hero-banner::after {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, rgba(14, 165, 233, 0) 70%);
            pointer-events: none;
        }
        .hero-cat-avatar {
            width: 68px;
            height: 68px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            background: #ffffff;
        }
        .stepper-nav-btn {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(8px);
        }
        .stepper-nav-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.45);
            transform: translateY(-1px);
        }

        /* Modern Box Container */
        .config-box {
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: border-color 0.2s ease;
        }
        .config-box-header {
            padding: 14px 20px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .config-box-title {
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .config-box-body {
            padding: 20px;
        }

        /* Professional CRM Tabs */
        .crm-nav-tabs {
            border-bottom: 1px solid var(--pm-border);
            gap: 4px;
            background: #f8fafc;
            padding: 8px 16px 0 16px;
            border-radius: 14px 14px 0 0;
        }
        .crm-nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 13.5px;
            padding: 10px 18px;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .crm-nav-tabs .nav-link:hover {
            color: var(--pm-primary);
            background: rgba(255,255,255,0.7);
        }
        .crm-nav-tabs .nav-link.active {
            color: var(--pm-primary);
            background: #ffffff;
            border-bottom: 2px solid var(--pm-primary);
            box-shadow: 0 -2px 6px rgba(0,0,0,0.02);
        }

        /* Design layout cards */
        .design-option {
            border: 2px solid var(--pm-border);
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
            transition: all .2s ease-in-out;
            cursor: pointer;
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .design-option:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.15);
        }
        input[type=radio]:checked + .design-option {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
        }

        /* Showcase Banner Upload Card */
        .showcase-upload-card {
            border: 1.5px dashed var(--pm-border);
            border-radius: 12px;
            padding: 16px;
            background: #f8fafc;
            transition: all 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .showcase-upload-card:hover {
            border-color: var(--pm-primary);
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }
        .banner-preview-box {
            width: 100%;
            height: 140px;
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
            position: relative;
        }
        .banner-preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Live Mega Menu Dropdown Mockup Preview */
        .mega-preview-window {
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .mega-preview-bar {
            background: #0b53a1;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
        }
        .mega-preview-body {
            display: flex;
            min-height: 220px;
        }
        .mega-preview-cols {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            padding: 14px;
            gap: 12px;
            background: #ffffff;
            border-right: 1px solid #f1f5f9;
        }
        .mega-preview-showcase {
            flex: 0 0 200px;
            padding: 12px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .badge-status { font-size: 11.5px; padding: 4px 8px; border-radius: 6px; font-weight: 600; }
        .badge-show { background-color: #dcfce7; color: #15803d; }
        .badge-hide { background-color: #fee2e2; color: #b91c1c; }
        .icon-thumb { width: 38px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; }
        .required-field::after { content: " *"; color: #dc3545; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Top Executive Header: Title & Clean Navigation Pills -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="mb-0 fw-bold text-dark">Inside Page Mega Menu Manager</h3>
                            <span class="badge bg-primary text-white" style="font-size:11px;">Desktop &bull; Level 2 &amp; 3</span>
                        </div>
                        <p class="text-muted small mb-0">Configure Desktop Top Bar Category Dropdown, Multi-Column Subcategories &amp; Showcase Banners</p>
                    </div>

                    <!-- Clean CRM Nav Pills (Direct Switch Between Header Modules) -->
                    <div class="module-nav-pills">
                        <a href="header-menu-list.php" class="module-nav-link">
                            <i class="fas fa-layer-group"></i> All Menus List
                        </a>
                        <a href="add-top-icon.php" class="module-nav-link">
                            <i class="fas fa-icons"></i> Top Icon Settings
                        </a>
                        <a href="add-menu-page.php" class="module-nav-link active">
                            <i class="fas fa-folder-tree"></i> Inside Page Mega Menu
                        </a>
                        <a href="header-menu-design.php" class="module-nav-link">
                            <i class="fas fa-palette"></i> Header Layouts
                        </a>
                    </div>
                </div>

                <!-- KPI Quick Metric Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Main Categories</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $mainCatsCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-info-subtle text-info">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Sub Categories</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $subCatsCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-warning-subtle text-warning">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Sub-Sub Items</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $subSubCatsCount; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-success-subtle text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Live in Top Navbar</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $activeInsideCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Step 1: Category Selection Card (Clean Cascading Picker) -->
                <div class="config-box">
                    <div class="config-box-header">
                        <h4 class="config-box-title">
                            <i class="fas fa-sitemap text-primary"></i>
                            Select Category to Configure
                        </h4>
                        <?php if ($selected): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                Currently Editing: <?php echo htmlspecialchars($selected['name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="config-box-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1 required-field">Main Category (Level 1)</label>
                                <select id="mainCatSelect" class="form-select form-select-md">
                                    <option value="">— Choose Main Category —</option>
                                    <?php foreach ($mainCategories as $mc): ?>
                                        <option value="<?php echo $mc['id']; ?>" <?php echo $mainId == $mc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto text-muted d-none d-md-block pb-2">
                                <i class="fas fa-chevron-right text-black-50"></i>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Sub Category (Level 2)</label>
                                <select id="subCatSelect" class="form-select form-select-md" <?php echo $mainId ? '' : 'disabled'; ?>>
                                    <option value="">— Optional: Sub Category —</option>
                                    <?php foreach ($subCategories as $sc): ?>
                                        <option value="<?php echo $sc['id']; ?>" <?php echo $subId == $sc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto text-muted d-none d-md-block pb-2">
                                <i class="fas fa-chevron-right text-black-50"></i>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Sub-Sub Category (Level 3)</label>
                                <select id="subSubCatSelect" class="form-select form-select-md" <?php echo $subId ? '' : 'disabled'; ?>>
                                    <option value="">— Optional: Sub-Sub Category —</option>
                                    <?php foreach ($subSubCategories as $ssc): ?>
                                        <option value="<?php echo $ssc['id']; ?>" <?php echo $subSubId == $ssc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ssc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="configureSubBtn" class="btn btn-primary w-100 py-2" <?php echo ($mainId || $subId) ? '' : 'disabled'; ?>>
                                    <i class="fas fa-sliders-h me-1"></i> Configure
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($selected): ?>
                <!-- Category Executive Hero Banner with Prev/Next Steppers -->
                <div class="category-hero-banner">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <?php 
                                $heroImg = $selected['desktop_menu_image'] ?: ($selected['desktop_image'] ?: $selected['image']);
                            ?>
                            <?php if ($heroImg): ?>
                                <img src="<?php echo htmlspecialchars($heroImg); ?>" class="hero-cat-avatar" alt="Cat Avatar" onerror="this.src='/default-img.jpg'">
                            <?php else: ?>
                                <div class="hero-cat-avatar d-flex align-items-center justify-content-center text-primary fs-3">
                                    <i class="fas fa-folder"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h4 class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?></h4>
                                    <span class="badge bg-white text-primary fw-bold" style="font-size:11px;">
                                        Level <?php echo (int)$selected['level']; ?>
                                    </span>
                                    <span class="badge bg-<?php echo ($selected['desktop_menu_status'] ?? 'show') === 'show' ? 'success' : 'danger'; ?>" style="font-size:11px;">
                                        <?php echo ($selected['desktop_menu_status'] ?? 'show') === 'show' ? 'Navbar: Active' : 'Navbar: Hidden'; ?>
                                    </span>
                                </div>
                                <div class="small text-white-50">
                                    Catalog Original: <strong><?php echo htmlspecialchars($selected['name']); ?></strong> 
                                    <?php if (!empty($ancestors)): ?>
                                        &bull; Path: 
                                        <?php foreach ($ancestors as $aIdx => $anc): ?>
                                            <span><?php echo htmlspecialchars($anc['name']); ?> &rsaquo; </span>
                                        <?php endforeach; ?>
                                        <strong><?php echo htmlspecialchars($selected['name']); ?></strong>
                                    <?php endif; ?>
                                    &bull; Slug: <code><?php echo htmlspecialchars($selected['slug']); ?></code>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper Controls (Quick Prev/Next between items) -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($prevCat): ?>
                                <a href="add-menu-page.php?category_id=<?php echo $prevCat['id']; ?>" class="stepper-nav-btn" title="Previous: <?php echo htmlspecialchars($prevCat['name']); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($nextCat): ?>
                                <a href="add-menu-page.php?category_id=<?php echo $nextCat['id']; ?>" class="stepper-nav-btn" title="Next: <?php echo htmlspecialchars($nextCat['name']); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Settings Form Divided into Clean Structured Tabs -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" value="<?php echo $selectedId; ?>">
                    <input type="hidden" name="save" value="1">

                    <div class="config-box">
                        <!-- CRM Navigation Tabs Header -->
                        <ul class="nav nav-tabs crm-nav-tabs" id="menuSettingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                                    <i class="fas fa-edit"></i> 1. Basic &amp; Routing
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-showcase-btn" data-bs-toggle="tab" data-bs-target="#tab-showcase" type="button" role="tab">
                                    <i class="fas fa-images text-danger"></i> 2. Showcase Banners (Up to 3 Cols)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-design-btn" data-bs-toggle="tab" data-bs-target="#tab-design" type="button" role="tab">
                                    <i class="fas fa-palette text-purple"></i> 3. Layout Design
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-preview-btn" data-bs-toggle="tab" data-bs-target="#tab-preview" type="button" role="tab">
                                    <i class="fas fa-desktop text-success"></i> 4. Live Mega Menu Preview
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content config-box-body" id="menuSettingsTabContent">
                            
                            <!-- TAB 1: Basic & Routing Settings -->
                            <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold required-field">
                                            <i class="fas fa-heading text-primary me-1"></i> Title Name Add &gt; Box
                                        </label>
                                        <input type="text" class="form-control form-control-lg" name="name" required
                                               placeholder="e.g. <?php echo htmlspecialchars($selected['name']); ?>"
                                               value="<?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?>">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-shield-alt text-success me-1"></i>Overrides navbar title only. Original catalog name (<code><?php echo htmlspecialchars($selected['name']); ?></code>) stays 100% untouched.
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-link text-info me-1"></i> URL Add &gt; Box
                                        </label>
                                        <input type="text" class="form-control form-control-lg" name="custom_url"
                                               placeholder="<?php echo htmlspecialchars($defaultCategoryUrl); ?>"
                                               value="<?php echo htmlspecialchars($currentUrlValue); ?>">
                                        <small class="text-muted d-block mt-1">
                                            Target link in header (default: <code><?php echo htmlspecialchars($defaultCategoryUrl); ?></code>).
                                        </small>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-sort-numeric-down text-secondary me-1"></i> Short Order Number Add &gt; Box
                                        </label>
                                        <input type="number" class="form-control" name="desktop_menu_order" min="0"
                                               placeholder="0"
                                               value="<?php echo (int)($selected['desktop_menu_order'] ?? 0); ?>">
                                        <small class="text-muted">Display sequence order inside the desktop top menu (1, 2, 3...).</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-eye text-success me-1"></i> Status Show and Hide Add &gt; Box
                                        </label>
                                        <select name="desktop_menu_status" class="form-select">
                                            <option value="show" <?php echo (($selected['desktop_menu_status'] ?? 'show') === 'show') ? 'selected' : ''; ?>>
                                                Show (दिखाएं)
                                            </option>
                                            <option value="hide" <?php echo (($selected['desktop_menu_status'] ?? '') === 'hide') ? 'selected' : ''; ?>>
                                                Hide (छिपाएं)
                                            </option>
                                        </select>
                                        <small class="text-muted">Show or Hide this item inside the top mega menu panel.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: Showcase Banners (Up to 3 Columns) -->
                            <div class="tab-pane fade" id="tab-showcase" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <div>
                                        <h5 class="mb-0 fw-bold text-dark">Inside Mega Menu Showcase Banners</h5>
                                        <p class="text-muted small mb-0">Mega menu dropdown me right-side showcase image tiles upload karein (1 se 3 column tak adapt hota hai).</p>
                                    </div>
                                    <span class="badge bg-light text-primary border px-3 py-2">
                                        <i class="fas fa-magic me-1"></i> Multi-Column Adaptive
                                    </span>
                                </div>

                                <div class="row g-3">
                                    <!-- Banner Column 1 (Primary) -->
                                    <div class="col-md-4">
                                        <div class="showcase-upload-card">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-primary px-2 py-1">Column 1 (Primary Banner)</span>
                                                <?php if (!empty($selected['desktop_menu_image'])): ?>
                                                    <span class="badge bg-success-subtle text-success small"><i class="fas fa-check"></i> Active</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="banner-preview-box">
                                                <?php if (!empty($selected['desktop_menu_image'])): ?>
                                                    <img id="prvInsideImg1" src="<?php echo htmlspecialchars($selected['desktop_menu_image']); ?>" class="banner-preview-img" alt="Banner 1">
                                                <?php else: ?>
                                                    <img id="prvInsideImg1" class="banner-preview-img" style="display:none;" alt="Preview 1">
                                                    <div id="phInsideImg1" class="text-muted text-center p-2">
                                                        <i class="fas fa-cloud-upload-alt fs-2 text-primary opacity-50 mb-1 d-block"></i>
                                                        <span class="small">No Banner Uploaded</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($selected['desktop_menu_image'])): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="remove_image_1" value="1" id="remImg1">
                                                    <label class="form-check-label text-danger small fw-semibold" for="remImg1">Delete Banner 1</label>
                                                </div>
                                            <?php endif; ?>

                                            <label class="form-label small fw-bold mb-1">Upload Banner Image 1</label>
                                            <input type="file" class="form-control form-control-sm mb-2" name="desktop_menu_image" accept="image/*" onchange="previewBannerImg(this, 'prvInsideImg1', 'phInsideImg1')">

                                            <label class="form-label small fw-bold mb-1">Banner 1 Footer Label</label>
                                            <input type="text" class="form-control form-control-sm mb-2" name="desktop_menu_title_1" placeholder="e.g. <?php echo htmlspecialchars($selected['name']); ?>" value="<?php echo htmlspecialchars($selected['desktop_menu_title_1'] ?? ''); ?>">

                                            <label class="form-label small fw-bold mb-1">Banner 1 Target URL</label>
                                            <input type="text" class="form-control form-control-sm" name="desktop_menu_url_1" placeholder="/category/..." value="<?php echo htmlspecialchars($selected['desktop_menu_url_1'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Banner Column 2 (Optional) -->
                                    <div class="col-md-4">
                                        <div class="showcase-upload-card">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-secondary px-2 py-1">Column 2 (Optional Banner)</span>
                                                <?php if (!empty($selected['desktop_menu_image_2'])): ?>
                                                    <span class="badge bg-success-subtle text-success small"><i class="fas fa-check"></i> Active</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="banner-preview-box">
                                                <?php if (!empty($selected['desktop_menu_image_2'])): ?>
                                                    <img id="prvInsideImg2" src="<?php echo htmlspecialchars($selected['desktop_menu_image_2']); ?>" class="banner-preview-img" alt="Banner 2">
                                                <?php else: ?>
                                                    <img id="prvInsideImg2" class="banner-preview-img" style="display:none;" alt="Preview 2">
                                                    <div id="phInsideImg2" class="text-muted text-center p-2">
                                                        <i class="fas fa-cloud-upload-alt fs-2 text-secondary opacity-50 mb-1 d-block"></i>
                                                        <span class="small">No Banner Uploaded</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($selected['desktop_menu_image_2'])): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="remove_image_2" value="1" id="remImg2">
                                                    <label class="form-check-label text-danger small fw-semibold" for="remImg2">Delete Banner 2</label>
                                                </div>
                                            <?php endif; ?>

                                            <label class="form-label small fw-bold mb-1">Upload Banner Image 2</label>
                                            <input type="file" class="form-control form-control-sm mb-2" name="desktop_menu_image_2" accept="image/*" onchange="previewBannerImg(this, 'prvInsideImg2', 'phInsideImg2')">

                                            <label class="form-label small fw-bold mb-1">Banner 2 Footer Label</label>
                                            <input type="text" class="form-control form-control-sm mb-2" name="desktop_menu_title_2" placeholder="e.g. Featured Products" value="<?php echo htmlspecialchars($selected['desktop_menu_title_2'] ?? ''); ?>">

                                            <label class="form-label small fw-bold mb-1">Banner 2 Target URL</label>
                                            <input type="text" class="form-control form-control-sm" name="desktop_menu_url_2" placeholder="/category/..." value="<?php echo htmlspecialchars($selected['desktop_menu_url_2'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Banner Column 3 (Optional) -->
                                    <div class="col-md-4">
                                        <div class="showcase-upload-card">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-secondary px-2 py-1">Column 3 (Optional Banner)</span>
                                                <?php if (!empty($selected['desktop_menu_image_3'])): ?>
                                                    <span class="badge bg-success-subtle text-success small"><i class="fas fa-check"></i> Active</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="banner-preview-box">
                                                <?php if (!empty($selected['desktop_menu_image_3'])): ?>
                                                    <img id="prvInsideImg3" src="<?php echo htmlspecialchars($selected['desktop_menu_image_3']); ?>" class="banner-preview-img" alt="Banner 3">
                                                <?php else: ?>
                                                    <img id="prvInsideImg3" class="banner-preview-img" style="display:none;" alt="Preview 3">
                                                    <div id="phInsideImg3" class="text-muted text-center p-2">
                                                        <i class="fas fa-cloud-upload-alt fs-2 text-secondary opacity-50 mb-1 d-block"></i>
                                                        <span class="small">No Banner Uploaded</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($selected['desktop_menu_image_3'])): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="remove_image_3" value="1" id="remImg3">
                                                    <label class="form-check-label text-danger small fw-semibold" for="remImg3">Delete Banner 3</label>
                                                </div>
                                            <?php endif; ?>

                                            <label class="form-label small fw-bold mb-1">Upload Banner Image 3</label>
                                            <input type="file" class="form-control form-control-sm mb-2" name="desktop_menu_image_3" accept="image/*" onchange="previewBannerImg(this, 'prvInsideImg3', 'phInsideImg3')">

                                            <label class="form-label small fw-bold mb-1">Banner 3 Footer Label</label>
                                            <input type="text" class="form-control form-control-sm mb-2" name="desktop_menu_title_3" placeholder="e.g. Special Offers" value="<?php echo htmlspecialchars($selected['desktop_menu_title_3'] ?? ''); ?>">

                                            <label class="form-label small fw-bold mb-1">Banner 3 Target URL</label>
                                            <input type="text" class="form-control form-control-sm" name="desktop_menu_url_3" placeholder="/category/..." value="<?php echo htmlspecialchars($selected['desktop_menu_url_3'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: Layout Design -->
                            <div class="tab-pane fade" id="tab-design" role="tabpanel">
                                <label class="form-label fw-bold mb-2">
                                    <i class="fas fa-layer-group text-purple me-1"></i> Select Mega Menu Presentation Layout
                                </label>
                                <div class="row g-3">
                                    <?php foreach ($designOptions as $val => [$iconClass, $lbl, $desc]): ?>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="desktop_menu_design" value="<?php echo $val; ?>" id="dmd_<?php echo $val; ?>" class="d-none"
                                                   <?php echo ($selected['desktop_menu_design'] ?? 'design1') == $val ? 'checked' : ''; ?>>
                                            <label for="dmd_<?php echo $val; ?>" class="design-option">
                                                <div class="bg-light rounded mb-2 text-primary w-100" style="height:48px;display:flex;align-items:center;justify-content:center;font-size:20px">
                                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                                </div>
                                                <strong class="d-block text-dark small mb-1"><?php echo $lbl; ?></strong>
                                                <span class="text-muted d-block" style="font-size:11px;line-height:1.2;"><?php echo $desc; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- TAB 4: Live Mega Menu Preview Mockup -->
                            <div class="tab-pane fade" id="tab-preview" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0 fw-bold text-dark">Live Mega Menu Dropdown Mockup</h5>
                                    <span class="badge bg-primary">Matches Storefront Rendering</span>
                                </div>

                                <div class="mega-preview-window">
                                    <div class="mega-preview-bar">
                                        <div class="d-flex align-items-center gap-2">
                                            <span><?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?></span>
                                            <i class="fas fa-chevron-down" style="font-size:10px;"></i>
                                        </div>
                                        <span class="badge bg-white text-primary" style="font-size:10px;">Dropdown Active View</span>
                                    </div>
                                    <div class="mega-preview-body">
                                        <div class="mega-preview-cols">
                                            <?php 
                                                // Preview children of selected or dummy list
                                                $prevSubs = !empty($subCategories) ? $subCategories : $subSubCategories;
                                                if (empty($prevSubs)) {
                                                    $prevSubs = [
                                                        ['name' => 'Popular Products', 'slug' => 'sample-1'],
                                                        ['name' => 'Trending Collections', 'slug' => 'sample-2'],
                                                        ['name' => 'Custom Branding', 'slug' => 'sample-3'],
                                                    ];
                                                }
                                            ?>
                                            <?php foreach (array_slice($prevSubs, 0, 3) as $ps): ?>
                                                <div>
                                                    <div class="fw-bold text-dark small mb-2 border-bottom pb-1"><?php echo htmlspecialchars($ps['name']); ?></div>
                                                    <div class="text-muted small mb-1">&bull; Standard Item A</div>
                                                    <div class="text-muted small mb-1">&bull; Premium Item B <span class="badge bg-danger" style="font-size:8px;">New</span></div>
                                                    <div class="text-muted small">&bull; Eco-friendly Item C</div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mega-preview-showcase">
                                            <?php if (!empty($selected['desktop_menu_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($selected['desktop_menu_image']); ?>" style="max-height:120px;max-width:100%;object-fit:contain;border-radius:6px;" alt="Banner 1">
                                                <div class="badge bg-danger w-100 py-1" style="font-size:11px;">
                                                    <?php echo htmlspecialchars($selected['desktop_menu_title_1'] ?: ($selected['desktop_menu_title'] ?: $selected['name'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted small text-center p-3">
                                                    <i class="fas fa-image fs-3 opacity-25 d-block mb-1"></i>
                                                    No Showcase Banner
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Sticky Clean Footer Actions -->
                        <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between">
                            <span class="text-muted small">
                                <i class="fas fa-lock text-success me-1"></i> Changes auto-sync immediately to frontend storefront (<code>http://localhost:5175/</code>).
                            </span>
                            <div class="d-flex gap-2">
                                <a href="header-menu-list.php" class="btn btn-outline-secondary px-3">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-save me-1"></i> Save Menu Settings
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
                <?php else: ?>
                    <!-- Empty State Prompt -->
                    <div class="config-box p-5 text-center">
                        <div class="avatar-lg bg-primary-subtle text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
                            <i class="fas fa-hand-pointer fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Please Select a Category Above</h4>
                        <p class="text-muted mb-0" style="max-width:480px;margin:0 auto;">
                            Main Category, Sub Category ya Sub-Sub Category select karein aur <strong>"Configure"</strong> button par click karein. Aap category ka title, routing, aur 3 showcase image banners set kar sakenge.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Table of Configured Inside Page Menu Items (Clean CRM Data View) -->
                <div class="config-box">
                    <div class="config-box-header">
                        <h4 class="config-box-title">
                            <i class="fas fa-table text-primary"></i>
                            Inside Page Category Menus
                            <span class="badge bg-primary text-white ms-1"><?php echo count($insideMenuList); ?> Listed</span>
                        </h4>
                        <a href="header-menu-list.php" class="btn btn-sm btn-outline-primary">
                            Full Registry <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:70px">Banners</th>
                                    <th>Title Name</th>
                                    <th>Parent Category</th>
                                    <th>Design</th>
                                    <th style="width:80px">Order</th>
                                    <th style="width:110px">Status</th>
                                    <th>URL Route</th>
                                    <th style="width:100px" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($insideMenuList)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No inside page category menus found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($insideMenuList as $item): ?>
                                        <?php 
                                            $isShow = ($item['desktop_menu_status'] ?? 'show') === 'show'; 
                                            $isCurrentlyEditing = ($selected && (int)$selected['id'] === (int)$item['id']);
                                            $itemUrl = !empty($item['custom_url']) ? $item['custom_url'] : ('/category/' . $item['slug']);
                                        ?>
                                        <tr class="<?php echo $isCurrentlyEditing ? 'table-primary table-opacity-10' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <?php 
                                                        $tImgs = array_filter([$item['desktop_menu_image'] ?? null, $item['desktop_menu_image_2'] ?? null, $item['desktop_menu_image_3'] ?? null]);
                                                        if (empty($tImgs) && !empty($item['image'])) $tImgs[] = $item['image'];
                                                    ?>
                                                    <?php if (!empty($tImgs)): ?>
                                                        <?php foreach ($tImgs as $ti): ?>
                                                            <img src="<?php echo htmlspecialchars($ti); ?>" class="icon-thumb" alt="Banner">
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="icon-thumb d-flex align-items-center justify-content-center text-muted">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['desktop_menu_title'] ?: $item['name']); ?></div>
                                                <span class="badge bg-<?php echo (int)$item['level'] == 3 ? 'warning' : 'secondary'; ?> text-dark" style="font-size:10px">
                                                    Level <?php echo (int)$item['level']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted fw-semibold"><?php echo htmlspecialchars($item['parent_name'] ?? '—'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border">
                                                    <?php echo htmlspecialchars(ucfirst($item['desktop_menu_design'] ?? 'design1')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo (int)($item['desktop_menu_order'] ?? 0); ?></span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm badge-status <?php echo $isShow ? 'badge-show' : 'badge-hide'; ?> border-0"
                                                        onclick="toggleInsideMenuStatus(<?php echo (int)$item['id']; ?>, '<?php echo $isShow ? 'hide' : 'show'; ?>', this)">
                                                    <i class="fas <?php echo $isShow ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                                    <?php echo $isShow ? 'Show' : 'Hide'; ?>
                                                </button>
                                            </td>
                                            <td>
                                                <small class="text-muted text-truncate d-inline-block" style="max-width:180px" title="<?php echo htmlspecialchars($itemUrl); ?>">
                                                    <code><?php echo htmlspecialchars($itemUrl); ?></code>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <a href="add-menu-page.php?category_id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
<script>
const mainSel        = document.getElementById('mainCatSelect');
const subSel         = document.getElementById('subCatSelect');
const subSubSel      = document.getElementById('subSubCatSelect');
const configureBtn   = document.getElementById('configureSubBtn');

mainSel.addEventListener('change', function () {
    const pId = this.value;
    subSel.innerHTML = '<option value="">— Select Sub Category —</option>';
    subSubSel.innerHTML = '<option value="">— None —</option>';
    subSel.disabled = true;
    subSubSel.disabled = true;
    configureBtn.disabled = !pId;

    if (!pId) return;
    fetch('add-menu-page.php?get_subs=1&parent_id=' + pId)
        .then(r => r.json())
        .then(data => {
            if (data.length) {
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    subSel.appendChild(opt);
                });
                subSel.disabled = false;
            }
        });
});

subSel.addEventListener('change', function () {
    const pId = this.value;
    subSubSel.innerHTML = '<option value="">— None (Edit Sub Category) —</option>';
    subSubSel.disabled = true;
    configureBtn.disabled = !pId && !mainSel.value;

    if (!pId) return;
    fetch('add-menu-page.php?get_subsubs=1&parent_id=' + pId)
        .then(r => r.json())
        .then(data => {
            if (data.length) {
                data.forEach(ss => {
                    const opt = document.createElement('option');
                    opt.value = ss.id;
                    opt.textContent = ss.name;
                    subSubSel.appendChild(opt);
                });
                subSubSel.disabled = false;
            }
        });
});

subSubSel.addEventListener('change', function () {
    configureBtn.disabled = !subSubSel.value && !subSel.value && !mainSel.value;
});

configureBtn.addEventListener('click', function () {
    const targetId = subSubSel.value || subSel.value || mainSel.value;
    if (targetId) {
        window.location.href = 'add-menu-page.php?category_id=' + targetId;
    }
});

function previewBannerImg(input, imgId, phId) {
    const img = document.getElementById(imgId);
    const ph = phId ? document.getElementById(phId) : null;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = 'block';
            if (ph) ph.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleInsideMenuStatus(id, newStatus, btn) {
    const formData = new FormData();
    formData.append('action', 'toggle_menu_page_status');
    formData.append('id', id);
    formData.append('status', newStatus);

    btn.disabled = true;
    fetch('add-menu-page.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            if (data.status === 'show') {
                btn.className = 'btn btn-sm badge-status badge-show border-0';
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Show';
                btn.setAttribute('onclick', `toggleInsideMenuStatus(${id}, 'hide', this)`);
            } else {
                btn.className = 'btn btn-sm badge-status badge-hide border-0';
                btn.innerHTML = '<i class="fas fa-times me-1"></i> Hide';
                btn.setAttribute('onclick', `toggleInsideMenuStatus(${id}, 'show', this)`);
            }
        } else {
            alert('Error updating status: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        alert('Server request failed.');
    });
}
</script>
</body>
</html>
