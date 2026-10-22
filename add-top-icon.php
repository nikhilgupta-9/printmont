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

// Handle AJAX Status Toggle (Desktop or Mobile)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_top_icon_status') {
    header('Content-Type: application/json');
    $catId = (int)($_POST['id'] ?? 0);
    $device = $_POST['device'] ?? 'desktop'; // desktop or mobile
    $newStatus = (isset($_POST['status']) && $_POST['status'] === 'show') ? 'show' : 'hide';

    if ($catId > 0) {
        $col = ($device === 'mobile') ? 'mobile_topbar_status' : 'desktop_menu_status';
        $stmt = $conn->prepare("UPDATE categories SET `{$col}` = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $catId);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'status' => $newStatus, 
                'device' => $device,
                'message' => ucfirst($device) . ' top icon status updated to ' . strtoupper($newStatus)
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

// File Upload Helper
function uploadNavbarIconImg($fileKey, $subdir, $existing = '') {
    if (empty($_FILES[$fileKey]['name'])) return $existing;
    $file = $_FILES[$fileKey];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg']) || $file['size'] > 5*1024*1024) return $existing;
    $dir = __DIR__ . "/uploads/category/{$subdir}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if ($existing && file_exists(__DIR__ . '/' . $existing) && strpos($existing, 'http') === false) {
        @unlink(__DIR__ . '/' . $existing);
    }
    $fn = uniqid() . '_' . time() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$subdir}/{$fn}" : $existing;
}

// Handle Form Submission: ONLY updates navbar columns, NEVER changes category name or main catalog image!
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_top_icon'])) {
    if (!$selectedId || !$selected) {
        $error = 'Please select a category first.';
    } else {
        // Desktop Navbar fields
        $desktopTitle = trim($_POST['desktop_menu_title'] ?? '');
        $desktopStatus = $_POST['desktop_menu_status'] ?? 'show';
        $desktopOrder = (int)($_POST['desktop_menu_order'] ?? 0);
        
        // Remove or Upload Desktop Icon Image
        if (!empty($_POST['remove_desktop_image'])) {
            if (!empty($selected['desktop_image']) && file_exists(__DIR__ . '/' . $selected['desktop_image']) && strpos($selected['desktop_image'], 'http') === false) {
                @unlink(__DIR__ . '/' . $selected['desktop_image']);
            }
            $desktopIconImg = null;
        } else {
            $desktopIconImg = uploadNavbarIconImg('desktop_image', 'top-icon-desktop', $selected['desktop_image'] ?? '');
        }

        // Remove or Upload Desktop Mega Menu Image
        if (!empty($_POST['remove_desktop_menu_image'])) {
            if (!empty($selected['desktop_menu_image']) && file_exists(__DIR__ . '/' . $selected['desktop_menu_image']) && strpos($selected['desktop_menu_image'], 'http') === false) {
                @unlink(__DIR__ . '/' . $selected['desktop_menu_image']);
            }
            $desktopMenuImg = null;
        } else {
            $desktopMenuImg = uploadNavbarIconImg('desktop_menu_image', 'menu', $selected['desktop_menu_image'] ?? '');
        }

        // Mobile Navbar fields
        $mobileTitle = trim($_POST['mobile_topbar_title'] ?? '');
        $mobileStatus = $_POST['mobile_topbar_status'] ?? 'show';
        $mobileOrder = (int)($_POST['mobile_topbar_order'] ?? 0);
        
        // Remove or Upload Mobile Icon Image
        if (!empty($_POST['remove_mobile_image'])) {
            if (!empty($selected['mobile_image']) && file_exists(__DIR__ . '/' . $selected['mobile_image']) && strpos($selected['mobile_image'], 'http') === false) {
                @unlink(__DIR__ . '/' . $selected['mobile_image']);
            }
            $mobileIconImg = null;
        } else {
            $mobileIconImg = uploadNavbarIconImg('mobile_image', 'top-icon-mobile', $selected['mobile_image'] ?? '');
        }

        // 🔒 SAFETY: ONLY navbar columns are updated. Original 'name' and 'image' are NEVER touched!
        $updateData = [
            'desktop_menu_title'   => $desktopTitle ?: null,
            'desktop_menu_image'   => $desktopMenuImg ?: null,
            'desktop_menu_status'  => $desktopStatus,
            'desktop_menu_order'   => $desktopOrder,
            'mobile_topbar_title'  => $mobileTitle ?: null,
            'mobile_topbar_status' => $mobileStatus,
            'mobile_topbar_order'  => $mobileOrder,
        ];

        $controller->updateCategory($selectedId, $updateData);
        $_SESSION['success_message'] = 'Top Icon navbar configuration updated successfully for "' . htmlspecialchars($selected['name']) . '". Category catalog data remains 100% protected.';
        header('Location: add-top-icon.php?category_id=' . $selectedId);
        exit;
    }
}

// Fetch all Main Categories (Level 1)
$mainCategories = $controller->getMainCategories();

// Fetch all categories for top icon list
$topIconsListRes = $conn->query("SELECT * FROM categories WHERE level = 1 ORDER BY CASE WHEN desktop_menu_order > 0 THEN desktop_menu_order ELSE display_order END ASC, name ASC");
$allTopIcons = [];
$desktopActiveCount = 0;
$mobileActiveCount = 0;
if ($topIconsListRes) {
    while ($row = $topIconsListRes->fetch_assoc()) {
        $allTopIcons[] = $row;
        if (($row['desktop_menu_status'] ?? 'show') === 'show') $desktopActiveCount++;
        if (($row['mobile_topbar_status'] ?? 'show') === 'show') $mobileActiveCount++;
    }
}

// Adjacent categories stepper (Prev / Next)
$prevCat = null;
$nextCat = null;
$currentIndex = -1;
if ($selectedId > 0 && !empty($allTopIcons)) {
    foreach ($allTopIcons as $idx => $catItem) {
        if ((int)$catItem['id'] === $selectedId) {
            $currentIndex = $idx;
            $prevCat = $allTopIcons[$idx - 1] ?? null;
            $nextCat = $allTopIcons[$idx + 1] ?? null;
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
    <title>Header Category Menu &bull; Top Icon Manager | PrintMont</title>
    
    <!-- Fonts: Plus Jakarta Sans & Inter -->
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
            --pm-card-bg: #ffffff;
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
        .config-box:hover {
            border-color: #cbd5e1;
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

        /* Executive Focus Hero Banner for Active Category */
        .category-hero-banner {
            background: linear-gradient(135deg, #0b53a1 0%, #073b75 60%, #031b33 100%);
            border-radius: 16px;
            padding: 20px 24px;
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
            width: 72px;
            height: 72px;
            border-radius: 14px;
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

        /* Horizontal Category Picker Bar */
        .cat-scroll-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 6px 2px 12px 2px;
            scroll-behavior: smooth;
        }
        .cat-scroll-container::-webkit-scrollbar {
            height: 6px;
        }
        .cat-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .cat-chip-card {
            flex: 0 0 auto;
            width: 130px;
            background: #ffffff;
            border: 1.5px solid var(--pm-border);
            border-radius: 10px;
            padding: 10px 8px;
            text-align: center;
            text-decoration: none;
            color: #334155;
            transition: all 0.2s ease;
            position: relative;
        }
        .cat-chip-card:hover {
            border-color: var(--pm-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 83, 161, 0.1);
            color: var(--pm-primary);
        }
        .cat-chip-card.active {
            border-color: var(--pm-primary);
            background: #eff6ff;
            color: var(--pm-primary);
            box-shadow: 0 0 0 2px rgba(11, 83, 161, 0.3);
        }
        .cat-chip-img {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: cover;
            margin: 0 auto 6px auto;
            display: block;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .cat-chip-name {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
            max-height: 28px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Segmented Device Switcher */
        .device-segmented-control {
            display: flex;
            background: #e2e8f0;
            padding: 4px;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .device-segment-btn {
            flex: 1;
            padding: 10px 18px;
            border: none;
            background: transparent;
            font-size: 13.5px;
            font-weight: 700;
            color: #64748b;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .device-segment-btn.active.desktop-active {
            background: #0b53a1;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.3);
        }
        .device-segment-btn.active.mobile-active {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
        }

        /* Designer Checkerboard Canvas for Transparent Graphics */
        .checkerboard-canvas {
            background-color: #f8fafc;
            background-image: linear-gradient(45deg, #e2e8f0 25%, transparent 25%), 
                              linear-gradient(-45deg, #e2e8f0 25%, transparent 25%), 
                              linear-gradient(45deg, transparent 75%, #e2e8f0 75%), 
                              linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
            background-size: 16px 16px;
            background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        /* Modern Upload Dropzone Card */
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.2s ease;
            position: relative;
        }
        .upload-dropzone:hover {
            border-color: var(--pm-primary);
            background: #f0f7ff;
        }
        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 5;
        }

        /* Live Header Preview Mockup (Sticky Sidebar) */
        .preview-sticky-card {
            position: sticky;
            top: 20px;
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .preview-header-bar {
            background: #03172c;
            color: #ffffff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 600;
        }
        .preview-canvas {
            background: #f8fafc;
            padding: 20px 14px;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Mock Desktop Header Strip */
        .mock-desktop-header {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .mock-desktop-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            overflow-x: hidden;
        }
        .mock-icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 6px 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
        }
        .mock-icon-item.highlighted {
            background: #eff6ff;
            border: 1.5px solid var(--pm-primary);
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15);
        }
        .mock-icon-img {
            width: 34px;
            height: 34px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 4px;
            background: #fff;
        }
        .mock-icon-text {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
        }

        /* Mock Mobile Device */
        .mock-mobile-device {
            max-width: 320px;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .mock-mobile-topbar {
            background: #0b53a1;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ffffff;
            font-size: 12px;
        }
        .mock-mobile-strip {
            display: flex;
            gap: 10px;
            padding: 10px;
            overflow-x: hidden;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }
        .mock-mobile-avatar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 60px;
            flex-shrink: 0;
        }
        .mock-mobile-avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #e2e8f0;
            margin-bottom: 4px;
            background: #fff;
        }
        .mock-mobile-avatar-item.highlighted .mock-mobile-avatar-img {
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.35);
        }
        .mock-mobile-avatar-text {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        /* Sticky Action Floating Bar */
        .sticky-action-bar {
            position: sticky;
            bottom: 12px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border: 1px solid var(--pm-border);
            border-radius: 12px;
            padding: 12px 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        /* Toast Popup Alert */
        #pmToast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideInToast 0.3s ease;
        }
        @keyframes slideInToast {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">

<!-- Toast Notification Element -->
<div id="pmToast">
    <i class="fas fa-check-circle text-success fs-5"></i>
    <span id="pmToastMsg">Action completed successfully</span>
</div>

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
                                <li class="breadcrumb-item active" aria-current="page">Top Icon Settings</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            <i class="fas fa-cubes text-primary me-2"></i> Header Category Menu &bull; Top Icon
                        </h2>
                        <p class="text-muted small mb-0">
                            Configure navbar-specific titles and icons for <strong>Desktop &amp; Mobile</strong>. Original category catalog data is 100% protected and untouched.
                        </p>
                    </div>

                    <!-- Top Submenu Links -->
                    <div class="module-nav-pills">
                        <a href="add-top-icon.php" class="module-nav-link active">
                            <i class="fas fa-star text-warning"></i> Top Icon
                        </a>
                        <a href="add-menu-page.php" class="module-nav-link">
                            <i class="fas fa-file-alt"></i> Top Menu Inside Pages
                        </a>
                        <a href="header-menu-design.php" class="module-nav-link">
                            <i class="fas fa-palette"></i> Select Design
                        </a>
                        <a href="header-menu-list.php" class="module-nav-link">
                            <i class="fas fa-layer-group"></i> All menus
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

                <?php if ($selected): ?>
                <!-- ======================================================== -->
                <!-- EXECUTIVE HERO BANNER FOR ACTIVE CATEGORY               -->
                <!-- ======================================================== -->
                <div class="category-hero-banner">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index: 2;">
                        
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?php echo htmlspecialchars($selected['image'] ?: 'img/placeholder.png'); ?>" 
                                 class="hero-cat-avatar" 
                                 alt="<?php echo htmlspecialchars($selected['name']); ?>"
                                 onerror="this.src='https://placehold.co/72x72?text=Cat'">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($selected['name']); ?></h3>
                                    <span class="badge bg-white text-primary fw-bold" style="font-size:11px;">
                                        ID #<?php echo $selected['id']; ?>
                                    </span>
                                    <span class="badge bg-success bg-opacity-75 text-white" style="font-size:11px;">
                                        <i class="fas fa-lock me-1"></i> Catalog Protected
                                    </span>
                                </div>
                                <div class="text-white-50 small">
                                    Slug: <code><?php echo htmlspecialchars($selected['slug']); ?></code> &bull; 
                                    Position: <strong>#<?php echo ($currentIndex + 1); ?> of <?php echo count($allTopIcons); ?></strong> Main Categories &bull;
                                    Desktop: <span class="text-white fw-bold"><?php echo strtoupper($selected['desktop_menu_status'] ?? 'show'); ?></span> &bull; 
                                    Mobile: <span class="text-white fw-bold"><?php echo strtoupper($selected['mobile_topbar_status'] ?? 'show'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper Controls (Prev / Next Category) -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($prevCat): ?>
                                <a href="add-top-icon.php?category_id=<?php echo $prevCat['id']; ?>" class="stepper-nav-btn" title="Previous: <?php echo htmlspecialchars($prevCat['name']); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                    <span>Prev: <?php echo htmlspecialchars(mb_strimwidth($prevCat['name'], 0, 14, '...')); ?></span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($nextCat): ?>
                                <a href="add-top-icon.php?category_id=<?php echo $nextCat['id']; ?>" class="stepper-nav-btn" title="Next: <?php echo htmlspecialchars($nextCat['name']); ?>">
                                    <span>Next: <?php echo htmlspecialchars(mb_strimwidth($nextCat['name'], 0, 14, '...')); ?></span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>

                            <a href="add-top-icon.php" class="stepper-nav-btn ms-1" title="Change Category">
                                <i class="fas fa-th-large"></i> All
                            </a>
                        </div>

                    </div>
                </div>
                <?php endif; ?>

                <!-- ======================================================== -->
                <!-- BOX 1: Category Select Add > Box (कैटेगरी चुनें)          -->
                <!-- ======================================================== -->
                <div class="config-box">
                    <div class="config-box-header">
                        <h4 class="config-box-title">
                            <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                <i class="fas fa-folder-open"></i>
                            </span>
                            1. Category Select Add &gt; Box (कैटेगरी चुनें)
                        </h4>
                        <?php if ($selected): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i> Selected: <?php echo htmlspecialchars($selected['name']); ?> (#<?php echo $selected['id']; ?>)
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="config-box-body">
                        
                        <!-- Search / Select Dropdown -->
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-dark mb-1">
                                    Select Main Category to Configure:
                                </label>
                                <select id="categorySelectDropdown" class="form-select form-select-lg" onchange="if(this.value) location.href='add-top-icon.php?category_id='+this.value">
                                    <option value="">— Click to Select Category from List —</option>
                                    <?php foreach ($mainCategories as $mc): ?>
                                        <option value="<?php echo $mc['id']; ?>" <?php echo ($selectedId == $mc['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mc['name']); ?> (ID #<?php echo $mc['id']; ?>) &bull; Desktop: <?php echo strtoupper($mc['desktop_menu_status'] ?? 'show'); ?> | Mobile: <?php echo strtoupper($mc['mobile_topbar_status'] ?? 'show'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 pt-md-3">
                                <div class="p-2 bg-light rounded text-muted small border">
                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                    Click any category chip below for 1-click instant selection.
                                </div>
                            </div>
                        </div>

                        <!-- 1-Click Visual Category Carousel / Chips -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.5px">
                                    Quick 1-Click Category Picker:
                                </span>
                                <small class="text-muted"><?php echo count($mainCategories); ?> Categories</small>
                            </div>
                            <div class="cat-scroll-container">
                                <?php foreach ($mainCategories as $mc): 
                                    $isSelected = ($selectedId == $mc['id']);
                                    $chipImg = !empty($mc['desktop_image']) ? $mc['desktop_image'] : (!empty($mc['image']) ? $mc['image'] : 'img/placeholder.png');
                                    $deskIsShow = ($mc['desktop_menu_status'] ?? 'show') === 'show';
                                    $mobIsShow = ($mc['mobile_topbar_status'] ?? 'show') === 'show';
                                ?>
                                    <a href="add-top-icon.php?category_id=<?php echo $mc['id']; ?>" class="cat-chip-card <?php echo $isSelected ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($chipImg); ?>" class="cat-chip-img" alt="<?php echo htmlspecialchars($mc['name']); ?>" onerror="this.src='https://placehold.co/42x42?text=PM'">
                                        <div class="cat-chip-name" title="<?php echo htmlspecialchars($mc['name']); ?>">
                                            <?php echo htmlspecialchars($mc['name']); ?>
                                        </div>
                                        <div class="mt-1 d-flex justify-content-center gap-1" style="font-size:9px;">
                                            <span class="badge <?php echo $deskIsShow ? 'bg-primary' : 'bg-light text-muted border'; ?>" title="Desktop status">D</span>
                                            <span class="badge <?php echo $mobIsShow ? 'bg-success' : 'bg-light text-muted border'; ?>" title="Mobile status">M</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <?php if ($selected): ?>

                <!-- MAIN WORKSPACE: Form Configuration on Left + Real-time Live Header Preview on Right -->
                <form method="POST" enctype="multipart/form-data" id="topIconForm">
                    <input type="hidden" name="category_id" value="<?php echo $selectedId; ?>">
                    <input type="hidden" name="save_top_icon" value="1">

                    <div class="row g-4">
                        
                        <!-- Left Column: Device Switcher & Configuration Boxes -->
                        <div class="col-lg-7">

                            <!-- Device Segmented Control: Desktop vs Mobile -->
                            <div class="device-segmented-control">
                                <button type="button" class="device-segment-btn active desktop-active" id="segDesktopBtn" onclick="switchDeviceTab('desktop')">
                                    <i class="fas fa-desktop fs-6"></i>
                                    <span>Desktop Navbar Settings</span>
                                    <span class="badge bg-white text-primary ms-1" style="font-size:10px;">Order #<?php echo (int)($selected['desktop_menu_order'] ?? 0); ?></span>
                                </button>
                                <button type="button" class="device-segment-btn" id="segMobileBtn" onclick="switchDeviceTab('mobile')">
                                    <i class="fas fa-mobile-screen fs-6"></i>
                                    <span>Mobile Navbar Settings</span>
                                    <span class="badge bg-white text-success ms-1" style="font-size:10px;">Order #<?php echo (int)($selected['mobile_topbar_order'] ?? 0); ?></span>
                                </button>
                            </div>

                            <!-- ============================================== -->
                            <!-- SECTION A: DESKTOP NAVBAR SETTINGS             -->
                            <!-- ============================================== -->
                            <div id="desktopConfigPanel">

                                <!-- Box 2 (Desktop): Title Icon Name Add > Box -->
                                <div class="config-box" style="border-left: 4px solid #0b53a1;">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-heading text-primary"></i>
                                            2. Title Icon Name Add &gt; Box (Desktop)
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11px;" onclick="copyDefaultTitle('desktop')">
                                            <i class="fas fa-magic me-1"></i> Use "<?php echo htmlspecialchars($selected['name']); ?>"
                                        </button>
                                    </div>
                                    <div class="config-box-body">
                                        <div class="input-group input-group-lg mb-2">
                                            <span class="input-group-text bg-white text-muted">
                                                <i class="fas fa-signature"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="desktop_menu_title"
                                                   name="desktop_menu_title"
                                                   placeholder="<?php echo htmlspecialchars($selected['name']); ?> (Default)"
                                                   value="<?php echo htmlspecialchars($selected['desktop_menu_title'] ?? ''); ?>"
                                                   oninput="updateLivePreview()">
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Desktop header icon ke niche dikhne wala custom title. Khali chhodne par original category name dikhega.
                                            </small>
                                            <small class="text-primary fw-semibold">
                                                Current: <span id="currentDeskTitleDisplay"><?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box 5 & 6 (Desktop): Status & Sort Order -->
                                <div class="row g-3 mb-4">
                                    
                                    <!-- Box 5: Status Show and Hide -->
                                    <div class="col-md-7">
                                        <div class="config-box h-100">
                                            <div class="config-box-header">
                                                <h5 class="config-box-title">
                                                    <i class="fas fa-eye text-info"></i>
                                                    5. Status Show and Hide (Desktop)
                                                </h5>
                                            </div>
                                            <div class="config-box-body">
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="desktop_menu_status" id="desk_status_show" value="show" 
                                                           <?php echo (($selected['desktop_menu_status'] ?? 'show') === 'show') ? 'checked' : ''; ?> 
                                                           onchange="updateLivePreview()">
                                                    <label class="btn btn-outline-success fw-bold py-2" for="desk_status_show">
                                                        <i class="fas fa-check-circle me-1"></i> SHOW on Desktop
                                                    </label>

                                                    <input type="radio" class="btn-check" name="desktop_menu_status" id="desk_status_hide" value="hide" 
                                                           <?php echo (($selected['desktop_menu_status'] ?? '') === 'hide') ? 'checked' : ''; ?> 
                                                           onchange="updateLivePreview()">
                                                    <label class="btn btn-outline-danger fw-bold py-2" for="desk_status_hide">
                                                        <i class="fas fa-eye-slash me-1"></i> HIDE from Desktop
                                                    </label>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    Desktop top category bar me dikhana ya chhipana.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Box 6: Sort Order Number -->
                                    <div class="col-md-5">
                                        <div class="config-box h-100">
                                            <div class="config-box-header">
                                                <h5 class="config-box-title">
                                                    <i class="fas fa-sort-numeric-down text-secondary"></i>
                                                    Sort Order (Desktop)
                                                </h5>
                                            </div>
                                            <div class="config-box-body">
                                                <div class="input-group input-group-lg">
                                                    <button type="button" class="btn btn-outline-secondary px-3" onclick="stepNumber('desktop_menu_order', -1)">-</button>
                                                    <input type="number" 
                                                           class="form-control text-center fw-bold fs-5" 
                                                           id="desktop_menu_order"
                                                           name="desktop_menu_order" 
                                                           min="0" 
                                                           value="<?php echo (int)($selected['desktop_menu_order'] ?? 0); ?>">
                                                    <button type="button" class="btn btn-outline-secondary px-3" onclick="stepNumber('desktop_menu_order', 1)">+</button>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    Sequence position in header.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Box 3 (Desktop): Upload Image Add > Box (Top Icon Graphic) -->
                                <div class="config-box">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-cloud-arrow-up text-success"></i>
                                            3. Upload Image Add &gt; Box (Desktop Top Icon)
                                        </h5>
                                        <span class="badge bg-light text-muted border">1:1 Square &bull; Max 5MB</span>
                                    </div>
                                    <div class="config-box-body">
                                        <?php 
                                            $currDeskImg = !empty($selected['desktop_image']) ? $selected['desktop_image'] : $selected['image'];
                                            $hasCustomDesk = !empty($selected['desktop_image']);
                                        ?>
                                        <div class="row align-items-center g-3 mb-3">
                                            <div class="col-auto">
                                                <div class="checkerboard-canvas" style="width:72px;height:72px;">
                                                    <img src="<?php echo htmlspecialchars($currDeskImg ?: 'https://placehold.co/60x60?text=Icon'); ?>" 
                                                         id="activeDeskThumb"
                                                         style="max-width:56px;max-height:56px;object-fit:contain;border-radius:6px;" 
                                                         alt="Current Desktop Icon">
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="fw-bold text-dark mb-1">
                                                    Active Desktop Icon:
                                                    <?php if ($hasCustomDesk): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Custom Navbar Icon</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border ms-1">Falling back to Category Image</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small">
                                                    Desktop header strip me icon ke roop me dikhai deta hai.
                                                </div>
                                                <?php if ($hasCustomDesk): ?>
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="remove_desktop_image" value="1" id="rmDeskImg">
                                                        <label class="form-check-label text-danger small" for="rmDeskImg">
                                                            <i class="fas fa-trash-can me-1"></i> Remove custom icon (revert to category image)
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Upload Dropzone -->
                                        <div class="upload-dropzone">
                                            <input type="file" name="desktop_image" accept="image/*" onchange="handleImgPreview(this, 'prvDeskIcon', 'liveDeskMockImg')">
                                            <div class="py-2">
                                                <i class="fas fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                                <div class="fw-bold text-dark">Click to browse or Drag &amp; Drop new Desktop icon</div>
                                                <div class="text-muted small">PNG, WEBP, SVG or JPG (Square 1:1 recommended)</div>
                                            </div>
                                            <img id="prvDeskIcon" class="dropzone-thumb-preview mt-2" style="display:none;max-height:60px;" alt="Preview">
                                        </div>
                                    </div>
                                </div>

                                <!-- Box 4 (Desktop): Icon image Uploading Add > Box (Dropdown Mega Menu Graphic) -->
                                <div class="config-box">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-panorama text-purple"></i>
                                            4. Icon image Uploading Add &gt; Box (Desktop Dropdown Inside)
                                        </h5>
                                        <span class="badge bg-light text-muted border">Mega Menu Banner Graphic</span>
                                    </div>
                                    <div class="config-box-body">
                                        <?php if (!empty($selected['desktop_menu_image'])): ?>
                                            <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded border">
                                                <img src="<?php echo htmlspecialchars($selected['desktop_menu_image']); ?>" style="max-height:55px;max-width:100px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;" alt="Current Dropdown Image">
                                                <div class="flex-grow-1">
                                                    <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i> Active Dropdown Banner Image</span>
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="remove_desktop_menu_image" value="1" id="rmDeskMenuImg">
                                                        <label class="form-check-label text-danger small" for="rmDeskMenuImg">
                                                            <i class="fas fa-trash-can me-1"></i> Remove dropdown banner
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="upload-dropzone">
                                            <input type="file" name="desktop_menu_image" accept="image/*" onchange="handleImgPreview(this, 'prvDeskMenu', null)">
                                            <div class="py-2">
                                                <i class="fas fa-image fs-2 text-secondary mb-2"></i>
                                                <div class="fw-bold text-dark">Upload Dropdown Mega Menu Graphic</div>
                                                <div class="text-muted small">Dikhata hai desktop category dropdown hover flyout card me</div>
                                            </div>
                                            <img id="prvDeskMenu" class="dropzone-thumb-preview mt-2" style="display:none;width:auto;height:60px;" alt="Dropdown Banner Preview">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ============================================== -->
                            <!-- SECTION B: MOBILE NAVBAR SETTINGS              -->
                            <!-- ============================================== -->
                            <div id="mobileConfigPanel" style="display: none;">

                                <!-- Box 2 (Mobile): Title Icon Name Add > Box -->
                                <div class="config-box" style="border-left: 4px solid #10b981;">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-heading text-success"></i>
                                            2. Title Icon Name Add &gt; Box (Mobile)
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:11px;" onclick="copyDefaultTitle('mobile')">
                                            <i class="fas fa-magic me-1"></i> Use "<?php echo htmlspecialchars($selected['name']); ?>"
                                        </button>
                                    </div>
                                    <div class="config-box-body">
                                        <div class="input-group input-group-lg mb-2">
                                            <span class="input-group-text bg-white text-muted">
                                                <i class="fas fa-mobile-alt"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="mobile_topbar_title"
                                                   name="mobile_topbar_title"
                                                   placeholder="<?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?> (Default)"
                                                   value="<?php echo htmlspecialchars($selected['mobile_topbar_title'] ?? ''); ?>"
                                                   oninput="updateLivePreview()">
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Mobile horizontal touch strip me circle icon ke niche dikhne wala short title.
                                            </small>
                                            <small class="text-success fw-semibold">
                                                Current: <span id="currentMobTitleDisplay"><?php echo htmlspecialchars($selected['mobile_topbar_title'] ?: ($selected['desktop_menu_title'] ?: $selected['name'])); ?></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box 5 & 6 (Mobile): Status & Sort Order -->
                                <div class="row g-3 mb-4">
                                    
                                    <!-- Mobile Status Show and Hide -->
                                    <div class="col-md-7">
                                        <div class="config-box h-100">
                                            <div class="config-box-header">
                                                <h5 class="config-box-title">
                                                    <i class="fas fa-eye text-info"></i>
                                                    5. Status Show and Hide (Mobile)
                                                </h5>
                                            </div>
                                            <div class="config-box-body">
                                                <div class="btn-group w-100" role="group">
                                                    <input type="radio" class="btn-check" name="mobile_topbar_status" id="mob_status_show" value="show" 
                                                           <?php echo (($selected['mobile_topbar_status'] ?? 'show') === 'show') ? 'checked' : ''; ?> 
                                                           onchange="updateLivePreview()">
                                                    <label class="btn btn-outline-success fw-bold py-2" for="mob_status_show">
                                                        <i class="fas fa-check-circle me-1"></i> SHOW on Mobile
                                                    </label>

                                                    <input type="radio" class="btn-check" name="mobile_topbar_status" id="mob_status_hide" value="hide" 
                                                           <?php echo (($selected['mobile_topbar_status'] ?? '') === 'hide') ? 'checked' : ''; ?> 
                                                           onchange="updateLivePreview()">
                                                    <label class="btn btn-outline-danger fw-bold py-2" for="mob_status_hide">
                                                        <i class="fas fa-eye-slash me-1"></i> HIDE from Mobile
                                                    </label>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    Mobile phone horizontal bar me dikhana ya chhipana.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mobile Sort Order -->
                                    <div class="col-md-5">
                                        <div class="config-box h-100">
                                            <div class="config-box-header">
                                                <h5 class="config-box-title">
                                                    <i class="fas fa-sort-numeric-down text-secondary"></i>
                                                    Sort Order (Mobile)
                                                </h5>
                                            </div>
                                            <div class="config-box-body">
                                                <div class="input-group input-group-lg">
                                                    <button type="button" class="btn btn-outline-secondary px-3" onclick="stepNumber('mobile_topbar_order', -1)">-</button>
                                                    <input type="number" 
                                                           class="form-control text-center fw-bold fs-5" 
                                                           id="mobile_topbar_order"
                                                           name="mobile_topbar_order" 
                                                           min="0" 
                                                           value="<?php echo (int)($selected['mobile_topbar_order'] ?? 0); ?>">
                                                    <button type="button" class="btn btn-outline-secondary px-3" onclick="stepNumber('mobile_topbar_order', 1)">+</button>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    Mobile touch sequence order.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Box 3 (Mobile): Upload Image Add > Box (Mobile Icon Graphic) -->
                                <div class="config-box">
                                    <div class="config-box-header">
                                        <h5 class="config-box-title">
                                            <i class="fas fa-cloud-arrow-up text-success"></i>
                                            3. Upload Image Add &gt; Box (Mobile Top Icon)
                                        </h5>
                                        <span class="badge bg-light text-muted border">Circular 1:1 Avatar &bull; Max 5MB</span>
                                    </div>
                                    <div class="config-box-body">
                                        <?php 
                                            $currMobImg = !empty($selected['mobile_image']) ? $selected['mobile_image'] : (!empty($selected['desktop_image']) ? $selected['desktop_image'] : $selected['image']);
                                            $hasCustomMob = !empty($selected['mobile_image']);
                                        ?>
                                        <div class="row align-items-center g-3 mb-3">
                                            <div class="col-auto">
                                                <div class="checkerboard-canvas" style="width:72px;height:72px;border-radius:50%;">
                                                    <img src="<?php echo htmlspecialchars($currMobImg ?: 'https://placehold.co/60x60?text=Icon'); ?>" 
                                                         id="activeMobThumb"
                                                         style="max-width:56px;max-height:56px;object-fit:cover;border-radius:50%;" 
                                                         alt="Current Mobile Icon">
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="fw-bold text-dark mb-1">
                                                    Active Mobile Icon:
                                                    <?php if ($hasCustomMob): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Custom Mobile Icon</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border ms-1">Falling back to Desktop/Catalog</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small">
                                                    Mobile touch horizontal scrollbar me circular avatar ke roop me dikhta hai.
                                                </div>
                                                <?php if ($hasCustomMob): ?>
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="remove_mobile_image" value="1" id="rmMobImg">
                                                        <label class="form-check-label text-danger small" for="rmMobImg">
                                                            <i class="fas fa-trash-can me-1"></i> Remove custom mobile icon
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Mobile Upload Dropzone -->
                                        <div class="upload-dropzone">
                                            <input type="file" name="mobile_image" accept="image/*" onchange="handleImgPreview(this, 'prvMobIcon', 'liveMobMockImg')">
                                            <div class="py-2">
                                                <i class="fas fa-cloud-arrow-up fs-2 text-success mb-2"></i>
                                                <div class="fw-bold text-dark">Click to browse or Drag &amp; Drop mobile icon</div>
                                                <div class="text-muted small">PNG, WEBP, SVG (Square 1:1, looks best circular)</div>
                                            </div>
                                            <img id="prvMobIcon" class="dropzone-thumb-preview mt-2" style="display:none;border-radius:50%;max-height:60px;" alt="Preview">
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <!-- Right Column: Interactive Real-Time Live Header Preview -->
                        <div class="col-lg-5">
                            <div class="preview-sticky-card">
                                
                                <div class="preview-header-bar">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-eye text-warning"></i>
                                        <span>Live Header Navbar Preview</span>
                                    </div>
                                    <!-- Preview device toggle -->
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-light active" id="btnPrvDesktop" onclick="switchPreviewMode('desktop')">
                                            <i class="fas fa-desktop me-1"></i> Desktop
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-light" id="btnPrvMobile" onclick="switchPreviewMode('mobile')">
                                            <i class="fas fa-mobile-alt me-1"></i> Mobile
                                        </button>
                                    </div>
                                </div>

                                <div class="preview-canvas">
                                    
                                    <!-- 1. DESKTOP LIVE MOCKUP -->
                                    <div id="mockupDesktopWrap">
                                        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                            <span class="text-muted fw-bold" style="font-size:11px;">STOREFRONT DESKTOP TOP BAR:</span>
                                            <span class="badge <?php echo (($selected['desktop_menu_status'] ?? 'show') === 'show') ? 'bg-primary' : 'bg-danger'; ?>" style="font-size:10px;" id="mockDeskStatusBadge">
                                                <?php echo (($selected['desktop_menu_status'] ?? 'show') === 'show') ? 'SHOW (ACTIVE)' : 'HIDE (INACTIVE)'; ?>
                                            </span>
                                        </div>

                                        <div class="mock-desktop-header">
                                            <div class="mock-desktop-strip">
                                                
                                                <!-- Mock Prior Item -->
                                                <div class="mock-icon-item opacity-50">
                                                    <div style="width:34px;height:34px;border-radius:6px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:13px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-gift"></i>
                                                    </div>
                                                    <span class="mock-icon-text">Gifts</span>
                                                </div>

                                                <!-- ACTIVE EDITING ITEM (Real-time Updated) -->
                                                <div class="mock-icon-item highlighted" id="mockDeskHighlightCard" style="opacity: <?php echo (($selected['desktop_menu_status'] ?? 'show') === 'show') ? '1' : '0.4'; ?>;">
                                                    <img src="<?php echo htmlspecialchars($currDeskImg ?: 'https://placehold.co/34x34?text=Icon'); ?>" 
                                                         id="liveDeskMockImg" 
                                                         class="mock-icon-img" 
                                                         alt="Live Desktop Mock Icon"
                                                         onerror="this.src='https://placehold.co/34x34?text=Icon'">
                                                    <span class="mock-icon-text text-primary" id="liveDeskMockTitle">
                                                        <?php echo htmlspecialchars($selected['desktop_menu_title'] ?: $selected['name']); ?>
                                                    </span>
                                                    <span class="badge bg-primary position-absolute top-0 end-0 translate-middle p-1 rounded-circle" style="font-size:6px;"></span>
                                                </div>

                                                <!-- Mock Subsequent Items -->
                                                <div class="mock-icon-item opacity-50">
                                                    <div style="width:34px;height:34px;border-radius:6px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:13px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                    <span class="mock-icon-text">Packaging</span>
                                                </div>

                                                <div class="mock-icon-item opacity-50">
                                                    <div style="width:34px;height:34px;border-radius:6px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:13px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-bullhorn"></i>
                                                    </div>
                                                    <span class="mock-icon-text">Signage</span>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <small class="text-muted" style="font-size:11px;">
                                                <i class="fas fa-sync-alt me-1 text-primary"></i> Live sync with Title &amp; Upload boxes above.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- 2. MOBILE LIVE MOCKUP -->
                                    <div id="mockupMobileWrap" style="display: none;">
                                        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                            <span class="text-muted fw-bold" style="font-size:11px;">STOREFRONT MOBILE TOUCH BAR:</span>
                                            <span class="badge <?php echo (($selected['mobile_topbar_status'] ?? 'show') === 'show') ? 'bg-success' : 'bg-danger'; ?>" style="font-size:10px;" id="mockMobStatusBadge">
                                                <?php echo (($selected['mobile_topbar_status'] ?? 'show') === 'show') ? 'SHOW (ACTIVE)' : 'HIDE (INACTIVE)'; ?>
                                            </span>
                                        </div>

                                        <div class="mock-mobile-device">
                                            <div class="mock-mobile-topbar">
                                                <i class="fas fa-bars"></i>
                                                <span class="fw-bold">PrintMont</span>
                                                <div class="ms-auto d-flex gap-2">
                                                    <i class="fas fa-search"></i>
                                                    <i class="fas fa-shopping-bag"></i>
                                                </div>
                                            </div>

                                            <div class="mock-mobile-strip">
                                                
                                                <!-- Mock Mobile Item 1 -->
                                                <div class="mock-mobile-avatar-item opacity-50">
                                                    <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:14px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-gift"></i>
                                                    </div>
                                                    <span class="mock-mobile-avatar-text">Gifts</span>
                                                </div>

                                                <!-- ACTIVE EDITING MOBILE ITEM (Real-time Updated) -->
                                                <div class="mock-mobile-avatar-item highlighted" id="mockMobHighlightCard" style="opacity: <?php echo (($selected['mobile_topbar_status'] ?? 'show') === 'show') ? '1' : '0.4'; ?>;">
                                                    <img src="<?php echo htmlspecialchars($currMobImg ?: 'https://placehold.co/40x40?text=Icon'); ?>" 
                                                         id="liveMobMockImg" 
                                                         class="mock-mobile-avatar-img" 
                                                         alt="Live Mobile Mock Icon"
                                                         onerror="this.src='https://placehold.co/40x40?text=Icon'">
                                                    <span class="mock-mobile-avatar-text text-success fw-bold" id="liveMobMockTitle">
                                                        <?php echo htmlspecialchars($selected['mobile_topbar_title'] ?: ($selected['desktop_menu_title'] ?: $selected['name'])); ?>
                                                    </span>
                                                </div>

                                                <!-- Mock Mobile Item 3 -->
                                                <div class="mock-mobile-avatar-item opacity-50">
                                                    <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:14px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                    <span class="mock-mobile-avatar-text">Boxes</span>
                                                </div>

                                                <!-- Mock Mobile Item 4 -->
                                                <div class="mock-mobile-avatar-item opacity-50">
                                                    <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:14px;color:#64748b;margin-bottom:4px;">
                                                        <i class="fas fa-bullhorn"></i>
                                                    </div>
                                                    <span class="mock-mobile-avatar-text">Signs</span>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <small class="text-muted" style="font-size:11px;">
                                                <i class="fas fa-mobile-alt me-1 text-success"></i> Smartphone horizontal circular scroll strip.
                                            </small>
                                        </div>
                                    </div>

                                </div>

                                <div class="p-3 bg-light border-top">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt text-success me-1"></i> Isolated Header Data
                                        </small>
                                        <a href="http://localhost:5175/" target="_blank" class="text-primary small fw-semibold text-decoration-none">
                                            Open Storefront <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Sticky Floating Action Save Bar -->
                    <div class="sticky-action-bar">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-primary fs-4"></i>
                            <div>
                                <span class="fw-bold text-dark">
                                    Save Top Icon Settings for "<?php echo htmlspecialchars($selected['name']); ?>"
                                </span>
                                <small class="text-muted d-block" style="font-size:11.5px;">
                                    Category catalog details remain 100% protected. Only header navigation display updates.
                                </small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="add-top-icon.php" class="btn btn-outline-secondary px-3">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Save Top Icon Settings
                            </button>
                        </div>
                    </div>

                </form>

                <?php else: ?>

                <!-- Empty State Helper when no category selected -->
                <div class="config-box p-4 text-center border-0 bg-white shadow-sm mb-4">
                    <div style="width:64px;height:64px;background:#eff6ff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#0b53a1;font-size:26px;margin-bottom:1rem;">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Please Select a Category Above</h4>
                    <p class="text-muted mx-auto mb-3" style="max-width:550px;">
                        Upar diye gaye <strong>Category Select Add &gt; Box</strong> ya <strong>Quick 1-Click Cards</strong> me se kisi bhi category par click karein. Uske baad Desktop aur Mobile ke liye Title, Image aur Status customize karne ka panel open ho jayega.
                    </p>
                    <div class="d-inline-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('categorySelectDropdown').focus()">
                            <i class="fas fa-list me-1"></i> Choose from Dropdown
                        </button>
                    </div>
                </div>

                <?php endif; ?>

                <!-- ======================================================== -->
                <!-- ALL TOP ICONS TABLE: Searchable & Instant AJAX Toggles   -->
                <!-- ======================================================== -->
                <div class="config-box mt-4">
                    <div class="config-box-header flex-wrap gap-2">
                        <div>
                            <h4 class="config-box-title">
                                <i class="fas fa-table-list text-primary"></i>
                                All Top Icons Overview Table (सभी टॉप आइकॉन)
                            </h4>
                            <small class="text-muted">Total <?php echo count($allTopIcons); ?> Categories &bull; Instant live status toggle supported</small>
                        </div>

                        <!-- Live Search & Filter Controls -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 240px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="tableFilterInput" class="form-control border-start-0" placeholder="Filter category by name..." onkeyup="filterTopIconsTable()">
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary active" onclick="filterDeviceView('all', this)">All</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="filterDeviceView('desk-show', this)">Desktop Active</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="filterDeviceView('mob-show', this)">Mobile Active</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="topIconsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Category (🔒 Catalog Safe)</th>
                                    <th>Desktop Title &amp; Icon</th>
                                    <th style="width:100px;">Desktop Order</th>
                                    <th style="width:130px;">Desktop Status</th>
                                    <th>Mobile Title &amp; Icon</th>
                                    <th style="width:100px;">Mobile Order</th>
                                    <th style="width:130px;">Mobile Status</th>
                                    <th style="width:90px;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allTopIcons)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No categories found in database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allTopIcons as $item): ?>
                                        <?php 
                                            $isDeskShow = ($item['desktop_menu_status'] ?? 'show') === 'show'; 
                                            $isMobShow = ($item['mobile_topbar_status'] ?? 'show') === 'show'; 
                                            $isCurrentlyEditing = ($selected && (int)$selected['id'] === (int)$item['id']);
                                            
                                            $deskTitle = $item['desktop_menu_title'] ?: $item['name'];
                                            $mobTitle = $item['mobile_topbar_title'] ?: $deskTitle;

                                            $deskImg = !empty($item['desktop_image']) ? $item['desktop_image'] : $item['image'];
                                            $mobImg = !empty($item['mobile_image']) ? $item['mobile_image'] : $deskImg;
                                        ?>
                                        <tr class="top-icon-row <?php echo $isCurrentlyEditing ? 'table-primary table-opacity-10' : ''; ?>"
                                            data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>"
                                            data-desk-status="<?php echo $isDeskShow ? 'show' : 'hide'; ?>"
                                            data-mob-status="<?php echo $isMobShow ? 'show' : 'hide'; ?>">
                                            
                                            <!-- Category Column -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" style="width:38px;height:38px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;" alt="Catalog Image" onerror="this.src='https://placehold.co/38x38?text=Cat'">
                                                    <?php else: ?>
                                                        <div style="width:38px;height:38px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                                                            <i class="fas fa-folder"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <a href="add-top-icon.php?category_id=<?php echo (int)$item['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </a>
                                                        <div class="text-muted" style="font-size:11px;">
                                                            ID: #<?php echo $item['id']; ?> &bull; <code><?php echo htmlspecialchars($item['slug']); ?></code>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Desktop Title & Icon -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if (!empty($deskImg)): ?>
                                                        <img src="<?php echo htmlspecialchars($deskImg); ?>" style="width:30px;height:30px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e1;" alt="Desktop Icon" onerror="this.src='https://placehold.co/30x30?text=Desk'">
                                                    <?php endif; ?>
                                                    <span class="fw-semibold text-primary"><?php echo htmlspecialchars($deskTitle); ?></span>
                                                    <?php if (!empty($item['desktop_menu_title'])): ?>
                                                        <span class="badge bg-light text-primary border" style="font-size:9px;">Custom</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Desktop Order -->
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <?php echo (int)($item['desktop_menu_order'] ?? 0); ?>
                                                </span>
                                            </td>

                                            <!-- Desktop Status Toggle -->
                                            <td>
                                                <button type="button" 
                                                        class="switch-toggle-btn <?php echo $isDeskShow ? 'btn-status-show' : 'btn-status-hide'; ?>"
                                                        onclick="toggleTopIconStatusAjax(<?php echo (int)$item['id']; ?>, 'desktop', '<?php echo $isDeskShow ? 'hide' : 'show'; ?>', this)">
                                                    <i class="fas <?php echo $isDeskShow ? 'fa-check' : 'fa-times'; ?>"></i>
                                                    <span><?php echo $isDeskShow ? 'Show' : 'Hide'; ?></span>
                                                </button>
                                            </td>

                                            <!-- Mobile Title & Icon -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if (!empty($mobImg)): ?>
                                                        <img src="<?php echo htmlspecialchars($mobImg); ?>" style="width:30px;height:30px;object-fit:cover;border-radius:50%;border:1px solid #cbd5e1;" alt="Mobile Icon" onerror="this.src='https://placehold.co/30x30?text=Mob'">
                                                    <?php endif; ?>
                                                    <span class="fw-semibold text-success"><?php echo htmlspecialchars($mobTitle); ?></span>
                                                    <?php if (!empty($item['mobile_topbar_title'])): ?>
                                                        <span class="badge bg-light text-success border" style="font-size:9px;">Custom</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Mobile Order -->
                                            <td>
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <?php echo (int)($item['mobile_topbar_order'] ?? 0); ?>
                                                </span>
                                            </td>

                                            <!-- Mobile Status Toggle -->
                                            <td>
                                                <button type="button" 
                                                        class="switch-toggle-btn <?php echo $isMobShow ? 'btn-status-show' : 'btn-status-hide'; ?>"
                                                        onclick="toggleTopIconStatusAjax(<?php echo (int)$item['id']; ?>, 'mobile', '<?php echo $isMobShow ? 'hide' : 'show'; ?>', this)">
                                                    <i class="fas <?php echo $isMobShow ? 'fa-check' : 'fa-times'; ?>"></i>
                                                    <span><?php echo $isMobShow ? 'Show' : 'Hide'; ?></span>
                                                </button>
                                            </td>

                                            <!-- Action Button -->
                                            <td class="text-end">
                                                <a href="add-top-icon.php?category_id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:12px;">
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
// Switch between Desktop and Mobile Settings Panels
function switchDeviceTab(tab) {
    const isDesktop = (tab === 'desktop');
    
    document.getElementById('desktopConfigPanel').style.display = isDesktop ? 'block' : 'none';
    document.getElementById('mobileConfigPanel').style.display = isDesktop ? 'none' : 'block';

    const deskBtn = document.getElementById('segDesktopBtn');
    const mobBtn = document.getElementById('segMobileBtn');

    if (isDesktop) {
        deskBtn.className = 'device-segment-btn active desktop-active';
        mobBtn.className = 'device-segment-btn';
        switchPreviewMode('desktop');
    } else {
        deskBtn.className = 'device-segment-btn';
        mobBtn.className = 'device-segment-btn active mobile-active';
        switchPreviewMode('mobile');
    }
}

// Switch Live Preview Mockup Mode
function switchPreviewMode(mode) {
    const isDesk = (mode === 'desktop');
    document.getElementById('mockupDesktopWrap').style.display = isDesk ? 'block' : 'none';
    document.getElementById('mockupMobileWrap').style.display = isDesk ? 'none' : 'block';

    const btnDesk = document.getElementById('btnPrvDesktop');
    const btnMob = document.getElementById('btnPrvMobile');
    if (btnDesk && btnMob) {
        btnDesk.classList.toggle('active', isDesk);
        btnMob.classList.toggle('active', !isDesk);
    }
}

// Copy Default Category Title
function copyDefaultTitle(device) {
    const defaultName = "<?php echo isset($selected['name']) ? addslashes($selected['name']) : ''; ?>";
    if (device === 'desktop') {
        const input = document.getElementById('desktop_menu_title');
        if (input) {
            input.value = defaultName;
            updateLivePreview();
        }
    } else {
        const input = document.getElementById('mobile_topbar_title');
        if (input) {
            input.value = defaultName;
            updateLivePreview();
        }
    }
    showToast(`Copied default category name "${defaultName}"`);
}

// Number Stepper Helper (+ / -)
function stepNumber(inputId, change) {
    const input = document.getElementById(inputId);
    if (input) {
        let val = parseInt(input.value || 0, 10);
        val = Math.max(0, val + change);
        input.value = val;
    }
}

// Real-Time Live Preview Updates
function updateLivePreview() {
    const defaultName = "<?php echo isset($selected['name']) ? addslashes($selected['name']) : ''; ?>";

    // Desktop Title & Status
    const deskTitleInput = document.getElementById('desktop_menu_title');
    const liveDeskTitle = document.getElementById('liveDeskMockTitle');
    const deskTitleDisplay = document.getElementById('currentDeskTitleDisplay');
    const deskStatusChecked = document.querySelector('input[name="desktop_menu_status"]:checked');
    const deskBadge = document.getElementById('mockDeskStatusBadge');
    const deskHighlight = document.getElementById('mockDeskHighlightCard');

    if (deskTitleInput && liveDeskTitle) {
        const txt = deskTitleInput.value.trim() || defaultName;
        liveDeskTitle.innerText = txt;
        if (deskTitleDisplay) deskTitleDisplay.innerText = txt;
    }
    if (deskStatusChecked && deskBadge && deskHighlight) {
        const isShow = (deskStatusChecked.value === 'show');
        deskBadge.innerText = isShow ? 'SHOW (ACTIVE)' : 'HIDE (INACTIVE)';
        deskBadge.className = isShow ? 'badge bg-primary' : 'badge bg-danger';
        deskHighlight.style.opacity = isShow ? '1' : '0.4';
    }

    // Mobile Title & Status
    const mobTitleInput = document.getElementById('mobile_topbar_title');
    const liveMobTitle = document.getElementById('liveMobMockTitle');
    const mobTitleDisplay = document.getElementById('currentMobTitleDisplay');
    const mobStatusChecked = document.querySelector('input[name="mobile_topbar_status"]:checked');
    const mobBadge = document.getElementById('mockMobStatusBadge');
    const mobHighlight = document.getElementById('mockMobHighlightCard');

    if (mobTitleInput && liveMobTitle) {
        const txt = mobTitleInput.value.trim() || (deskTitleInput ? deskTitleInput.value.trim() : '') || defaultName;
        liveMobTitle.innerText = txt;
        if (mobTitleDisplay) mobTitleDisplay.innerText = txt;
    }
    if (mobStatusChecked && mobBadge && mobHighlight) {
        const isShow = (mobStatusChecked.value === 'show');
        mobBadge.innerText = isShow ? 'SHOW (ACTIVE)' : 'HIDE (INACTIVE)';
        mobBadge.className = isShow ? 'badge bg-success' : 'badge bg-danger';
        mobHighlight.style.opacity = isShow ? '1' : '0.4';
    }
}

// Handle Image File Input Live Preview
function handleImgPreview(input, targetThumbId, liveMockImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (targetThumbId) {
                const target = document.getElementById(targetThumbId);
                if (target) {
                    target.src = e.target.result;
                    target.style.display = 'inline-block';
                }
            }
            if (liveMockImgId) {
                const mock = document.getElementById(liveMockImgId);
                if (mock) {
                    mock.src = e.target.result;
                }
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Toast Notification
function showToast(msg) {
    const toast = document.getElementById('pmToast');
    const toastMsg = document.getElementById('pmToastMsg');
    if (toast && toastMsg) {
        toastMsg.innerText = msg;
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3200);
    }
}

// Ajax Toggle for Table Status
function toggleTopIconStatusAjax(id, device, newStatus, btn) {
    const formData = new FormData();
    formData.append('action', 'toggle_top_icon_status');
    formData.append('id', id);
    formData.append('device', device);
    formData.append('status', newStatus);

    btn.disabled = true;
    fetch('add-top-icon.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            const row = btn.closest('tr');
            if (data.status === 'show') {
                btn.className = 'switch-toggle-btn btn-status-show';
                btn.innerHTML = '<i class="fas fa-check"></i> <span>Show</span>';
                btn.setAttribute('onclick', `toggleTopIconStatusAjax(${id}, '${device}', 'hide', this)`);
                if (row) {
                    if (device === 'desktop') row.setAttribute('data-desk-status', 'show');
                    if (device === 'mobile') row.setAttribute('data-mob-status', 'show');
                }
            } else {
                btn.className = 'switch-toggle-btn btn-status-hide';
                btn.innerHTML = '<i class="fas fa-times"></i> <span>Hide</span>';
                btn.setAttribute('onclick', `toggleTopIconStatusAjax(${id}, '${device}', 'show', this)`);
                if (row) {
                    if (device === 'desktop') row.setAttribute('data-desk-status', 'hide');
                    if (device === 'mobile') row.setAttribute('data-mob-status', 'hide');
                }
            }
            showToast(data.message || 'Status updated successfully.');
        } else {
            alert('Error updating status: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        alert('Server request failed.');
    });
}

// Filter Overview Table by Category Name
function filterTopIconsTable() {
    const q = (document.getElementById('tableFilterInput').value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#topIconsTable tbody tr.top-icon-row');
    rows.forEach(r => {
        const name = r.getAttribute('data-name') || '';
        if (!q || name.indexOf(q) !== -1) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Filter Overview Table by Device Status View
function filterDeviceView(mode, btn) {
    const btnGroup = btn.parentElement;
    if (btnGroup) {
        btnGroup.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    const rows = document.querySelectorAll('#topIconsTable tbody tr.top-icon-row');
    rows.forEach(r => {
        const desk = r.getAttribute('data-desk-status');
        const mob = r.getAttribute('data-mob-status');

        if (mode === 'all') {
            r.style.display = '';
        } else if (mode === 'desk-show') {
            r.style.display = (desk === 'show') ? '' : 'none';
        } else if (mode === 'mob-show') {
            r.style.display = (mob === 'show') ? '' : 'none';
        }
    });
}
</script>
</body>
</html>
