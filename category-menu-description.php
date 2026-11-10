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

$error   = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Handle AJAX Status Toggle (Desktop or Mobile Description)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_desc_status') {
    header('Content-Type: application/json');
    $catId = (int)($_POST['id'] ?? 0);
    $device = $_POST['device'] ?? 'desktop'; // desktop or mobile
    $newStatus = (isset($_POST['status']) && $_POST['status'] === 'show') ? 'show' : 'hide';

    if ($catId > 0) {
        $col = ($device === 'mobile') ? 'mobile_menu_desc_status' : 'desktop_menu_desc_status';
        $stmt = $conn->prepare("UPDATE categories SET `{$col}` = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $catId);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'status' => $newStatus, 
                'device' => $device,
                'message' => ucfirst($device) . ' category description status updated to ' . strtoupper($newStatus)
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    }
    exit;
}

$selectedId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
$selected = ($selectedId > 0) ? $controller->getCategoryById($selectedId) : null;

// Handle Form Submission: Saves desktop & mobile menu description text & status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_description'])) {
    if (!$selectedId || !$selected) {
        $error = 'Please select a category first.';
    } else {
        $deskDesc       = trim($_POST['desktop_menu_desc'] ?? '');
        $deskDescStatus = ($_POST['desktop_menu_desc_status'] ?? 'show') === 'hide' ? 'hide' : 'show';
        
        $mobDesc        = trim($_POST['mobile_menu_desc'] ?? '');
        $mobDescStatus  = ($_POST['mobile_menu_desc_status'] ?? 'show') === 'hide' ? 'hide' : 'show';

        $updateData = [
            'desktop_menu_desc'        => $deskDesc ?: null,
            'desktop_menu_desc_status' => $deskDescStatus,
            'mobile_menu_desc'         => $mobDesc ?: null,
            'mobile_menu_desc_status'  => $mobDescStatus,
        ];

        $controller->updateCategory($selectedId, $updateData);
        $_SESSION['success_message'] = 'Category text description updated successfully for "' . htmlspecialchars($selected['name']) . '".';
        header('Location: category-menu-description.php?category_id=' . $selectedId);
        exit;
    }
}

// Fetch all categories for list
$allCatsRes = $conn->query("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.level ASC, CASE WHEN c.desktop_menu_order > 0 THEN c.desktop_menu_order ELSE c.display_order END ASC, c.name ASC");
$allCategories = [];
$deskDescActiveCount = 0;
$mobDescActiveCount = 0;
if ($allCatsRes) {
    while ($row = $allCatsRes->fetch_assoc()) {
        $allCategories[] = $row;
        if (!empty($row['desktop_menu_desc']) && ($row['desktop_menu_desc_status'] ?? 'show') === 'show') $deskDescActiveCount++;
        if (!empty($row['mobile_menu_desc']) && ($row['mobile_menu_desc_status'] ?? 'show') === 'show') $mobDescActiveCount++;
    }
}

// Stepper Prev/Next
$prevCat = null;
$nextCat = null;
$currentIndex = -1;
if ($selectedId > 0 && !empty($allCategories)) {
    foreach ($allCategories as $idx => $cItem) {
        if ((int)$cItem['id'] === $selectedId) {
            $currentIndex = $idx;
            $prevCat = $allCategories[$idx - 1] ?? null;
            $nextCat = $allCategories[$idx + 1] ?? null;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Category Text Description &bull; Header Category Menu | PrintMont Admin</title>
    
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="js/settings.js"></script>

    <style>
        :root {
            --pm-primary: var(--bs-primary, #3b7ddd);
            --pm-primary-dark: #2f64b1;
            --pm-primary-light: #d8e5f8;
            --pm-card-bg: #ffffff;
            --pm-border: #dee2e6;
        }
        body { font-family: var(--bs-body-font-family); background-color: var(--bs-body-bg); color: var(--bs-body-color); }
        .config-box { background: #fff; border: 1px solid var(--pm-border); border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(11,83,161,0.03); }
        .config-box-header { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--pm-border); display: flex; align-items: center; justify-content: space-between; }
        .config-box-title { font-size: 1.05rem; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .config-box-body { padding: 1.4rem; }
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid var(--pm-border); }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: var(--pm-primary); background: var(--pm-primary-light); }
        .module-nav-link.active { color: #fff; background: var(--pm-primary); }

        .device-segmented-control { display: flex; background: #e2e8f0; padding: 4px; border-radius: 10px; gap: 4px; margin-bottom: 1.25rem; }
        .device-segment-btn { flex: 1; border: none; background: transparent; padding: 10px 14px; font-weight: 700; font-size: 13.5px; color: #475569; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
        .device-segment-btn.active.desktop-active { background: #0b53a1; color: #fff; box-shadow: 0 2px 6px rgba(11,83,161,0.25); }
        .device-segment-btn.active.mobile-active { background: #10b981; color: #fff; box-shadow: 0 2px 6px rgba(16,185,129,0.25); }

        .preview-sticky-card { position: sticky; top: 85px; border-radius: 14px; border: 1px solid var(--pm-border); background: #fff; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
        .preview-header-bar { background: #0f172a; color: #fff; padding: 12px 16px; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: space-between; }
        .preview-canvas { padding: 16px; background: #f8fafc; min-height: 280px; }
        
        .cat-scroll-container { display: flex; gap: 10px; overflow-x: auto; padding: 8px 2px 14px 2px; }
        .cat-chip-card { flex: 0 0 auto; width: 110px; background: #fff; border: 1px solid var(--pm-border); border-radius: 10px; padding: 8px 6px; text-align: center; text-decoration: none; color: #334155; transition: all 0.2s; }
        .cat-chip-card:hover { transform: translateY(-2px); border-color: var(--pm-primary); color: var(--pm-primary); box-shadow: 0 4px 12px rgba(11,83,161,0.1); }
        .cat-chip-card.active { border: 2px solid var(--pm-primary); background: #eff6ff; color: var(--pm-primary); font-weight: 700; }
        .cat-chip-img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; margin: 0 auto 6px auto; display: block; border: 1px solid #cbd5e1; }
        .cat-chip-name { font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .switch-toggle-btn { border: none; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; }
        .btn-status-show { background: #dcfce7; color: #15803d; }
        .btn-status-hide { background: #fee2e2; color: #b91c1c; }

        /* AdminKit theme alignment */
        .content { background: var(--bs-body-bg); }
        .content > .container-fluid { max-width: 1600px; }
        .content h2, .content h3, .content h4, .content h5 { font-family: var(--bs-body-font-family); }
        .content h2.h3 { font-size: 1.35rem; font-weight: 600 !important; color: #343a40 !important; }
        .content p { font-size: .875rem; }
        .content .small, .content small { font-size: .8125rem; }
        .content .text-muted { color: #6c757d !important; }

        .module-nav-pills { max-width: 100%; flex-wrap: wrap; gap: 4px; padding: 4px; border-color: var(--bs-border-color); border-radius: .3rem; box-shadow: none; }
        .module-nav-link { padding: .45rem .65rem; border-radius: .25rem; font-size: .8125rem; font-weight: 500; white-space: nowrap; }
        .module-nav-link.active { box-shadow: none; }
        .config-box, .preview-sticky-card { border: 1px solid var(--bs-border-color); border-radius: .3rem; box-shadow: 0 .1rem .2rem rgba(0,0,0,.05); }
        .config-box-header { padding: .85rem 1rem; border-color: #dee2e6; }
        .config-box-title { font-size: .95rem; font-weight: 600; color: #343a40; }
        .config-box-body { padding: 1rem; }

        .device-segmented-control { gap: .35rem; padding: .25rem; background: #e9ecef; border-radius: .3rem; margin-bottom: 1rem; }
        .device-segment-btn { min-width: 0; padding: .6rem .7rem; gap: .45rem; border: 1px solid transparent; border-radius: .25rem; font-size: .8125rem; font-weight: 500; }
        .device-segment-btn.active.desktop-active, .device-segment-btn.active.mobile-active { background: var(--bs-primary); color: #fff; box-shadow: none; }

        .preview-sticky-card { top: 1.25rem; }
        .preview-header-bar { background: #343a40; padding: .65rem .85rem; font-size: .8125rem; font-weight: 600; }
        .preview-canvas { padding: 1rem; background: #f8f9fa; }
        .cat-chip-card { width: 108px; padding: .55rem .4rem; border-color: #dee2e6; border-radius: .3rem; }
        .cat-chip-card:hover { transform: translateY(-1px); box-shadow: 0 .2rem .4rem rgba(0,0,0,.08); }
        .cat-chip-card.active { border: 1px solid var(--bs-primary); background: #f8fbff; box-shadow: inset 0 0 0 1px var(--bs-primary); }
        .cat-chip-img { width: 38px; height: 38px; border-color: #dee2e6; border-radius: .25rem; }
        .cat-chip-name { font-size: .75rem; }

        .content .form-label { margin-bottom: .35rem; font-size: .8125rem; font-weight: 600 !important; color: #495057 !important; }
        .content .form-control, .content .form-select, .content .input-group-text { min-height: 38px; padding: .45rem .7rem; border-color: #ced4da; border-radius: .25rem; font-family: var(--bs-body-font-family); font-size: .875rem; line-height: 1.4; }
        .content .form-control-lg, .content .form-select-lg { min-height: 42px; padding: .5rem .75rem; font-size: .9rem; }
        .content textarea.form-control { min-height: 112px; resize: vertical; }
        .content .form-control:focus, .content .form-select:focus { border-color: #9dbce9; box-shadow: 0 0 0 .2rem rgba(59,125,221,.15); }
        .content .btn { border-radius: .25rem; font-family: var(--bs-body-font-family); font-size: .8125rem; font-weight: 500; line-height: 1.4; }
        .content .btn-sm { padding: .3rem .55rem; font-size: .75rem; }
        .content .btn-lg { padding: .55rem .9rem; font-size: .9rem; }
        .content .btn-primary { box-shadow: none; }
        .content .badge { font-weight: 500; }
        .content .switch-toggle-btn { min-height: 32px; padding: .3rem .6rem; border: 1px solid transparent; border-radius: .25rem; font-size: .75rem; font-weight: 500; }
        .btn-status-show { color: #146c43; background: #d1e7dd; border-color: #a3cfbb; }
        .btn-status-hide { color: #b02a37; background: #f8d7da; border-color: #f1aeb5; }
        .switch-toggle-btn:disabled { opacity: .65; cursor: wait; }
        .table { font-size: .8125rem; }
        .table > :not(caption) > * > * { padding: .65rem .75rem; }

        @media (max-width: 991.98px) {
            .content > .container-fluid { padding-right: .75rem !important; padding-left: .75rem !important; }
            .module-nav-pills { width: 100%; flex-wrap: nowrap; overflow-x: auto; }
            .module-nav-link { flex: 0 0 auto; }
            .preview-sticky-card { position: static; }
            .device-segment-btn { padding: .55rem .6rem; }
            .config-box-header { gap: .5rem; }
            .config-box-header .input-group[style*="width"] { width: 100% !important; }
        }
        @media (max-width: 575.98px) {
            .content h2.h3 { font-size: 1.12rem; }
            .content > .container-fluid { padding-right: .5rem !important; padding-left: .5rem !important; }
            .config-box-header, .config-box-body { padding: .75rem; }
            .config-box-title { font-size: .875rem; }
            .device-segment-btn { flex-direction: column; gap: .25rem; padding: .5rem .25rem; font-size: .75rem; }
            .cat-chip-card { width: 100px; }
            .preview-canvas { padding: .75rem; }
            #descForm > .row { --bs-gutter-x: .75rem; }
            #descForm .row.g-3 { --bs-gutter-y: .75rem; }
            #descForm > .row > .col-lg-7 > .d-flex.justify-content-between { align-items: stretch !important; flex-direction: column; gap: .75rem; }
            #descForm > .row > .col-lg-7 > .d-flex.justify-content-between .btn { width: 100%; }
            .config-box-header .badge { white-space: normal; }
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

                <!-- Header Title & Action Navigation Tabs -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1 text-muted small">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item">Header Category Menu</li>
                                <li class="breadcrumb-item active" aria-current="page">Category Text Description</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            <i class="fas fa-align-left text-primary me-2"></i> 4. Category Text Description show inside (Desktop and Mobile)
                        </h2>
                        <p class="text-muted small mb-0">
                            Configure promotional taglines, highlighted badges, and text descriptions shown inside Desktop Mega Menus and Mobile drawers.
                        </p>
                    </div>

                    <!-- Top Submenu Links: All 7 Items of Section 5 in order -->
                    <div class="module-nav-pills">
                        <a href="add-top-icon.php" class="module-nav-link">
                            <i class="fas fa-star text-warning"></i> 1. Top Icon
                        </a>
                        <a href="add-menu-page.php" class="module-nav-link">
                            <i class="fas fa-file-alt text-info"></i> 2. Top Menu Inside Pages
                        </a>
                        <a href="header-menu-design.php" class="module-nav-link">
                            <i class="fas fa-palette text-purple"></i> 3. Select Design
                        </a>
                        <a href="category-menu-description.php" class="module-nav-link active">
                            <i class="fas fa-align-left text-primary"></i> 4. Category Text Description
                        </a>
                        <a href="header-search-settings.php" class="module-nav-link">
                            <i class="fas fa-search text-success"></i> 5. Search Box Text
                        </a>
                        <a href="header-menu-list.php" class="module-nav-link">
                            <i class="fas fa-layer-group text-secondary"></i> 6. All menus
                        </a>
                        <a href="header-settings.php" class="module-nav-link">
                            <i class="fas fa-sliders-h text-dark"></i> 7. Page Rules &amp; Visibility
                        </a>
                    </div>
                </div>

                <!-- Global Feedback Alerts -->
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

                <!-- Step 1: Category Selection Picker -->
                <div class="config-box">
                    <div class="config-box-header">
                        <h4 class="config-box-title">
                            <i class="fas fa-folder-open text-primary"></i>
                            Select Category to Configure Description
                        </h4>
                        <?php if ($selected): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                <i class="fas fa-check-circle me-1"></i> Active: <?php echo htmlspecialchars($selected['name']); ?> (#<?php echo $selected['id']; ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="config-box-body">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark mb-1">
                                    Choose Category from List:
                                </label>
                                <select id="catSelectDropdown" class="form-select form-select-lg" onchange="if(this.value) location.href='category-menu-description.php?category_id='+this.value">
                                    <option value="">— Select Category —</option>
                                    <?php foreach ($allCategories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($selectedId == $c['id']) ? 'selected' : ''; ?>>
                                            [Level <?php echo $c['level']; ?>] <?php echo htmlspecialchars($c['name']); ?> <?php echo !empty($c['parent_name']) ? "({$c['parent_name']})" : ""; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 pt-md-3">
                                <div class="p-2 bg-light rounded text-muted small border">
                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                    Click any chip below for 1-click fast switching.
                                </div>
                            </div>
                        </div>

                        <!-- 1-Click Fast Category Picker -->
                        <div class="cat-scroll-container">
                            <?php foreach (array_slice($allCategories, 0, 30) as $c): 
                                $isSelected = ($selectedId == $c['id']);
                                $cImg = !empty($c['desktop_image']) ? $c['desktop_image'] : (!empty($c['image']) ? $c['image'] : 'img/placeholder.png');
                            ?>
                                <a href="category-menu-description.php?category_id=<?php echo $c['id']; ?>" class="cat-chip-card <?php echo $isSelected ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($cImg); ?>" class="cat-chip-img" alt="<?php echo htmlspecialchars($c['name']); ?>" onerror="this.src='https://placehold.co/40x40?text=PM'">
                                    <div class="cat-chip-name" title="<?php echo htmlspecialchars($c['name']); ?>">
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </div>
                                    <div class="small text-muted" style="font-size:9px;">L<?php echo $c['level']; ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php if ($selected): ?>

                <!-- Form & Live Mockup Workspace -->
                <form method="POST" id="descForm">
                    <input type="hidden" name="category_id" value="<?php echo $selectedId; ?>">
                    <input type="hidden" name="save_description" value="1">

                    <div class="row g-4">
                        <!-- Left: Configuration Form -->
                        <div class="col-lg-7">

                            <!-- Device Switcher Segment -->
                            <div class="device-segmented-control">
                                <button type="button" class="device-segment-btn active desktop-active" id="segDeskBtn" onclick="switchDescTab('desktop')">
                                    <i class="fas fa-desktop fs-6"></i>
                                    <span>Desktop Text Description</span>
                                </button>
                                <button type="button" class="device-segment-btn" id="segMobBtn" onclick="switchDescTab('mobile')">
                                    <i class="fas fa-mobile-screen fs-6"></i>
                                    <span>Mobile Text Description</span>
                                </button>
                            </div>

                            <!-- DESKTOP DESCRIPTION SECTION -->
                            <div id="desktopDescPanel">
                                <div class="config-box" style="border-left: 4px solid #0b53a1;">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-align-left text-primary"></i>
                                            Desktop Menu Text Description
                                        </h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-primary border">Shows Inside Mega Menu</span>
                                        </div>
                                    </div>
                                    <div class="config-box-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark">
                                                Desktop Description / Highlight Text:
                                            </label>
                                            <textarea class="form-control" 
                                                      id="desktop_menu_desc" 
                                                      name="desktop_menu_desc" 
                                                      rows="4" 
                                                      placeholder="e.g. Premium customizable corporate gifts, bulk branded stationery, and fast delivery across India."
                                                      oninput="updateLiveDescPreview()"><?php echo htmlspecialchars($selected['desktop_menu_desc'] ?? ''); ?></textarea>
                                            <small class="text-muted d-block mt-1">
                                                Desktop category mega menu dropdown ya hover flyout me dikhne wala short description.
                                            </small>
                                        </div>

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                                                    Desktop Description Status
                                                </label>
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="desktop_menu_desc_status" id="desk_desc_show" value="show" 
                                                           <?php echo (($selected['desktop_menu_desc_status'] ?? 'show') === 'show') ? 'checked' : ''; ?>
                                                           onchange="updateLiveDescPreview()">
                                                    <label class="btn btn-outline-primary py-2 fw-bold" for="desk_desc_show">
                                                        <i class="fas fa-check-circle me-1"></i> SHOW
                                                    </label>

                                                    <input type="radio" class="btn-check" name="desktop_menu_desc_status" id="desk_desc_hide" value="hide" 
                                                           <?php echo (($selected['desktop_menu_desc_status'] ?? '') === 'hide') ? 'checked' : ''; ?>
                                                           onchange="updateLiveDescPreview()">
                                                    <label class="btn btn-outline-danger py-2 fw-bold" for="desk_desc_hide">
                                                        <i class="fas fa-times-circle me-1"></i> HIDE
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 pt-md-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2" onclick="copyCatalogDesc('desktop')">
                                                    <i class="fas fa-copy me-1"></i> Copy from Catalog Description
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MOBILE DESCRIPTION SECTION -->
                            <div id="mobileDescPanel" style="display: none;">
                                <div class="config-box" style="border-left: 4px solid #10b981;">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-align-left text-success"></i>
                                            Mobile Menu Text Description
                                        </h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-success border">Shows Inside Mobile Drawer</span>
                                        </div>
                                    </div>
                                    <div class="config-box-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold text-dark">
                                                Mobile Description / Highlight Text:
                                            </label>
                                            <textarea class="form-control" 
                                                      id="mobile_menu_desc" 
                                                      name="mobile_menu_desc" 
                                                      rows="4" 
                                                      placeholder="e.g. Explore trending corporate gifts & custom apparel."
                                                      oninput="updateLiveDescPreview()"><?php echo htmlspecialchars($selected['mobile_menu_desc'] ?? ''); ?></textarea>
                                            <small class="text-muted d-block mt-1">
                                                Mobile navigation drawer me category ke sath dikhne wala compact text.
                                            </small>
                                        </div>

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                                                    Mobile Description Status
                                                </label>
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="mobile_menu_desc_status" id="mob_desc_show" value="show" 
                                                           <?php echo (($selected['mobile_menu_desc_status'] ?? 'show') === 'show') ? 'checked' : ''; ?>
                                                           onchange="updateLiveDescPreview()">
                                                    <label class="btn btn-outline-success py-2 fw-bold" for="mob_desc_show">
                                                        <i class="fas fa-check-circle me-1"></i> SHOW
                                                    </label>

                                                    <input type="radio" class="btn-check" name="mobile_menu_desc_status" id="mob_desc_hide" value="hide" 
                                                           <?php echo (($selected['mobile_menu_desc_status'] ?? '') === 'hide') ? 'checked' : ''; ?>
                                                           onchange="updateLiveDescPreview()">
                                                    <label class="btn btn-outline-danger py-2 fw-bold" for="mob_desc_hide">
                                                        <i class="fas fa-times-circle me-1"></i> HIDE
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 pt-md-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2" onclick="copyCatalogDesc('mobile')">
                                                    <i class="fas fa-copy me-1"></i> Copy from Catalog Description
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Save Button -->
                            <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border shadow-sm">
                                <div class="text-muted small">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    Changes save directly to category table without affecting products.
                                </div>
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="fas fa-save me-1"></i> Save Text Description
                                </button>
                            </div>

                        </div>

                        <!-- Right: Live Interactive Mockup Preview -->
                        <div class="col-lg-5">
                            <div class="preview-sticky-card">
                                <div class="preview-header-bar">
                                    <span><i class="fas fa-eye text-warning me-1"></i> Live Description Preview</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-light active" id="btnDeskPrv" onclick="switchPreview('desktop')">Desktop</button>
                                        <button type="button" class="btn btn-sm btn-outline-light" id="btnMobPrv" onclick="switchPreview('mobile')">Mobile</button>
                                    </div>
                                </div>
                                <div class="preview-canvas">
                                    
                                    <!-- Desktop Mega Menu Mockup -->
                                    <div id="prvDeskWrap">
                                        <div class="small fw-bold text-muted mb-2">MEGA MENU DROPDOWN MOCKUP:</div>
                                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                                            <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                                                <span class="fw-bold text-primary fs-6"><?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?></span>
                                                <span class="badge bg-primary-subtle text-primary ms-auto" id="deskStatusPrvBadge">
                                                    <?php echo (($selected['desktop_menu_desc_status'] ?? 'show') === 'show') ? 'SHOW' : 'HIDE'; ?>
                                                </span>
                                            </div>
                                            <p class="text-secondary small mb-3" id="liveDeskDescText" style="line-height:1.4;">
                                                <?php echo htmlspecialchars($selected['desktop_menu_desc'] ?: 'No desktop description configured yet. Enter text on the left.'); ?>
                                            </p>
                                            <div class="d-flex gap-2 text-muted small" style="font-size:11px;">
                                                <span>&bull; Item A</span>
                                                <span>&bull; Item B</span>
                                                <span>&bull; Item C</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mobile Drawer Mockup -->
                                    <div id="prvMobWrap" style="display: none;">
                                        <div class="small fw-bold text-muted mb-2">MOBILE DRAWER ACCORDION MOCKUP:</div>
                                        <div class="p-3 bg-white rounded-3 border shadow-sm">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="rounded bg-light p-1" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-folder text-primary" style="font-size:11px;"></i></span>
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($selected['name']); ?></span>
                                                <span class="badge bg-success-subtle text-success ms-auto" id="mobStatusPrvBadge">
                                                    <?php echo (($selected['mobile_menu_desc_status'] ?? 'show') === 'show') ? 'SHOW' : 'HIDE'; ?>
                                                </span>
                                            </div>
                                            <div class="ps-4">
                                                <small class="text-muted d-block" id="liveMobDescText">
                                                    <?php echo htmlspecialchars($selected['mobile_menu_desc'] ?: 'No mobile description configured yet.'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php endif; ?>

                <!-- Datatable of all categories with description status -->
                <div class="config-box mt-4">
                    <div class="config-box-header flex-wrap gap-2">
                        <div>
                            <h4 class="config-box-title">
                                <i class="fas fa-table text-primary"></i>
                                All Categories Text Description Overview
                            </h4>
                            <small class="text-muted">Total <?php echo count($allCategories); ?> categories &bull; Desktop Live: <?php echo $deskDescActiveCount; ?> &bull; Mobile Live: <?php echo $mobDescActiveCount; ?></small>
                        </div>
                        <div class="input-group input-group-sm" style="width: 260px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="filterInput" class="form-control border-start-0" placeholder="Filter category..." onkeyup="filterTable()">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="descTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Level</th>
                                    <th>Desktop Description Text</th>
                                    <th style="width:120px;">Desktop Status</th>
                                    <th>Mobile Description Text</th>
                                    <th style="width:120px;">Mobile Status</th>
                                    <th style="width:90px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allCategories as $item): 
                                    $deskHas = !empty($item['desktop_menu_desc']);
                                    $mobHas = !empty($item['mobile_menu_desc']);
                                    $isDeskShow = ($item['desktop_menu_desc_status'] ?? 'show') === 'show';
                                    $isMobShow = ($item['mobile_menu_desc_status'] ?? 'show') === 'show';
                                    $isEditing = ($selected && (int)$selected['id'] === (int)$item['id']);
                                ?>
                                    <tr class="desc-row <?php echo $isEditing ? 'table-primary table-opacity-10' : ''; ?>" data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>">
                                        <td>
                                            <a href="category-menu-description.php?category_id=<?php echo $item['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                            <?php if (!empty($item['parent_name'])): ?>
                                                <small class="text-muted d-block">&lsaquo; <?php echo htmlspecialchars($item['parent_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">L<?php echo $item['level']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($deskHas): ?>
                                                <span class="text-dark small"><?php echo htmlspecialchars(mb_strimwidth($item['desktop_menu_desc'], 0, 45, '...')); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">None configured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="switch-toggle-btn <?php echo $isDeskShow ? 'btn-status-show' : 'btn-status-hide'; ?>"
                                                    onclick="toggleDescStatusAjax(<?php echo (int)$item['id']; ?>, 'desktop', '<?php echo $isDeskShow ? 'hide' : 'show'; ?>', this)">
                                                <i class="fas <?php echo $isDeskShow ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <span><?php echo $isDeskShow ? 'Show' : 'Hide'; ?></span>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($mobHas): ?>
                                                <span class="text-dark small"><?php echo htmlspecialchars(mb_strimwidth($item['mobile_menu_desc'], 0, 45, '...')); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">None configured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="switch-toggle-btn <?php echo $isMobShow ? 'btn-status-show' : 'btn-status-hide'; ?>"
                                                    onclick="toggleDescStatusAjax(<?php echo (int)$item['id']; ?>, 'mobile', '<?php echo $isMobShow ? 'hide' : 'show'; ?>', this)">
                                                <i class="fas <?php echo $isMobShow ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <span><?php echo $isMobShow ? 'Show' : 'Hide'; ?></span>
                                            </button>
                                        </td>
                                        <td class="text-end">
                                            <a href="category-menu-description.php?category_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:12px;">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
function switchDescTab(tab) {
    document.getElementById('desktopDescPanel').style.display = (tab === 'desktop') ? 'block' : 'none';
    document.getElementById('mobileDescPanel').style.display = (tab === 'mobile') ? 'block' : 'none';

    const deskBtn = document.getElementById('segDeskBtn');
    const mobBtn = document.getElementById('segMobBtn');
    if (deskBtn) deskBtn.className = 'device-segment-btn' + (tab === 'desktop' ? ' active desktop-active' : '');
    if (mobBtn) mobBtn.className = 'device-segment-btn' + (tab === 'mobile' ? ' active mobile-active' : '');

    switchPreview(tab);
}

function switchPreview(mode) {
    document.getElementById('prvDeskWrap').style.display = (mode === 'desktop') ? 'block' : 'none';
    document.getElementById('prvMobWrap').style.display = (mode === 'mobile') ? 'block' : 'none';

    const btnD = document.getElementById('btnDeskPrv');
    const btnM = document.getElementById('btnMobPrv');
    if (btnD) btnD.classList.toggle('active', mode === 'desktop');
    if (btnM) btnM.classList.toggle('active', mode === 'mobile');
}

function copyCatalogDesc(target) {
    const defaultCatalogDesc = "<?php echo isset($selected['description']) ? addslashes(str_replace(["\r", "\n"], ' ', $selected['description'])) : ''; ?>";
    if (target === 'desktop') {
        document.getElementById('desktop_menu_desc').value = defaultCatalogDesc;
    } else {
        document.getElementById('mobile_menu_desc').value = defaultCatalogDesc;
    }
    updateLiveDescPreview();
}

function updateLiveDescPreview() {
    const dVal = document.getElementById('desktop_menu_desc')?.value.trim();
    const mVal = document.getElementById('mobile_menu_desc')?.value.trim();

    const dText = document.getElementById('liveDeskDescText');
    const mText = document.getElementById('liveMobDescText');

    if (dText) dText.innerText = dVal || 'No desktop description configured yet.';
    if (mText) mText.innerText = mVal || 'No mobile description configured yet.';

    const dStatus = document.querySelector('input[name="desktop_menu_desc_status"]:checked')?.value;
    const mStatus = document.querySelector('input[name="mobile_menu_desc_status"]:checked')?.value;

    const dBadge = document.getElementById('deskStatusPrvBadge');
    const mBadge = document.getElementById('mobStatusPrvBadge');

    if (dBadge) dBadge.innerText = (dStatus === 'show') ? 'SHOW' : 'HIDE';
    if (mBadge) mBadge.innerText = (mStatus === 'show') ? 'SHOW' : 'HIDE';
}

function toggleDescStatusAjax(id, device, newStatus, btn) {
    const formData = new FormData();
    formData.append('action', 'toggle_desc_status');
    formData.append('id', id);
    formData.append('device', device);
    formData.append('status', newStatus);

    btn.disabled = true;
    fetch('category-menu-description.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            if (data.status === 'show') {
                btn.className = 'switch-toggle-btn btn-status-show';
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Show</span>';
                btn.setAttribute('onclick', `toggleDescStatusAjax(${id}, '${device}', 'hide', this)`);
            } else {
                btn.className = 'switch-toggle-btn btn-status-hide';
                btn.innerHTML = '<i class="fas fa-times"></i> <span>Hide</span>';
                btn.setAttribute('onclick', `toggleDescStatusAjax(${id}, '${device}', 'show', this)`);
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

function filterTable() {
    const q = (document.getElementById('filterInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#descTable tbody tr.desc-row');
    rows.forEach(r => {
        const name = r.getAttribute('data-name') || '';
        if (!q || name.indexOf(q) !== -1) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
