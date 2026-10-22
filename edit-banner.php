<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');
require_once(__DIR__ . '/includes/banner-helpers.php');

$bannerController = new BannerController();
$categories       = $bannerController->getAllCategories();
$carousels        = $bannerController->getAllCarousels();

$banner = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $banner = $bannerController->getBannerById((int)$_GET['id']);
    if (!$banner) {
        $_SESSION['error_message'] = 'Banner not found.';
        header('Location: view-banner.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = 'Invalid banner ID.';
    header('Location: view-banner.php');
    exit;
}

$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $bannerController->updateBanner((int)$_GET['id'], $_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: view-banner.php');
        exit;
    } else {
        $error_message = $result['error'];
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);

$desktopDesign = $banner['desktop_design'] ?: $banner['layout_format'] ?: 'desktop_format_1';
$mobileDesign  = $banner['mobile_design'] ?: 'mobile_format_1';

$desktopImagesList = !empty($banner['desktop_images']) ? json_decode($banner['desktop_images'], true) : [];
$mobileImagesList  = !empty($banner['mobile_images']) ? json_decode($banner['mobile_images'], true) : [];
$desktopUrlsList   = !empty($banner['desktop_urls']) ? json_decode($banner['desktop_urls'], true) : [];
$mobileUrlsList    = !empty($banner['mobile_urls']) ? json_decode($banner['mobile_urls'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit Banner: <?php echo htmlspecialchars($banner['title']); ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .required-field::after { content: " *"; color: #dc3545; font-weight: bold; }
        
        .admin-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .admin-card-header {
            padding: 16px 20px;
            background: #fafbfc;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-card-header h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-card-body { padding: 24px 20px; }

        .design-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .design-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .design-card:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.08);
        }
        .design-card.active {
            border-color: #0b53a1;
            background: #f0f7ff;
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15);
        }
        .design-card .check-indicator {
            position: absolute;
            top: 8px; right: 8px;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .design-card.active .check-indicator { background: #0b53a1; }

        .wireframe-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
            min-height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .design-card.active .wireframe-box { border-color: #3b82f6; background: #ffffff; }
        .wf-single-square {
            width: 50px; height: 50px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #b91c1c; font-weight: 700;
        }
        .wf-full-rect {
            width: 100%; height: 44px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 9px; color: #b91c1c; font-weight: 700;
        }
        .wf-two-col { display: flex; gap: 6px; width: 100%; justify-content: center; }
        .wf-two-col span {
            width: 45px; height: 45px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 7px; color: #b91c1c; font-weight: 700; text-align: center;
        }
        .wf-grid-2x3 {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; width: 70px; margin: 0 auto;
        }
        .wf-grid-2x3 span {
            height: 22px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 2px;
            font-size: 6px; color: #b91c1c; display: flex; align-items: center; justify-content: center;
        }
        .wf-slim-strip {
            width: 100%; height: 22px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 3px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #b91c1c; font-weight: 700;
        }
        .wf-grid-3x2 {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; width: 90px; margin: 0 auto;
        }
        .wf-grid-3x2 span {
            height: 24px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 2px;
            font-size: 6px; color: #b91c1c; display: flex; align-items: center; justify-content: center;
        }
        .design-title { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .design-badge { font-size: 11px; color: #64748b; }

        .dimension-info-pill {
            background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af;
            border-radius: 8px; padding: 10px 14px; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
        }

        .upload-dropzone {
            border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center;
            background: #f8fafc; transition: all 0.2s; cursor: pointer; position: relative;
        }
        .upload-dropzone:hover { border-color: #0b53a1; background: #f0f7ff; }
        .upload-dropzone input[type="file"] {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        .upload-preview-img {
            max-width: 100%; max-height: 180px; border-radius: 6px; object-fit: contain;
            margin-top: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: block;
        }

        .multi-slot-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;
        }
        .slot-box {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;
        }

        .device-nav-pills {
            display: flex; gap: 10px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px;
        }
        .device-pill-btn {
            background: transparent; border: none; padding: 10px 20px; font-weight: 600;
            font-size: 14px; color: #64748b; border-bottom: 3px solid transparent;
            margin-bottom: -2px; cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all 0.2s;
        }
        .device-pill-btn.active { color: #0b53a1; border-bottom-color: #0b53a1; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <!-- Header Actions -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-edit text-primary me-2"></i>Edit Banner #<?php echo $banner['id']; ?>
                        </h3>
                        <p class="text-muted small mb-0">Update banner details, placement, images, and dimensions.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="view-banner.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Banners
                        </a>
                        <a href="add-banner.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New Banner
                        </a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="banner_type" value="<?php echo htmlspecialchars($banner['banner_type'] ?? 'single'); ?>">

                    <!-- Title -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5><i class="fas fa-heading text-primary"></i> 1. Banner Title &amp; General Information</h5>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label required-field fw-semibold">Banner Title Name</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required
                                           value="<?php echo htmlspecialchars($banner['title']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device Navigation Tabs -->
                    <div class="device-nav-pills">
                        <button type="button" class="device-pill-btn active" id="tabBtnDesktop" onclick="switchDeviceTab('desktop')">
                            <i class="fas fa-desktop"></i> Desktop Banner Configuration
                        </button>
                        <button type="button" class="device-pill-btn" id="tabBtnMobile" onclick="switchDeviceTab('mobile')">
                            <i class="fas fa-mobile-alt"></i> Mobile Banner Configuration
                        </button>
                    </div>

                    <!-- ======================================================== -->
                    <!-- 🖥️ DESKTOP SECTION CONFIGURATION                         -->
                    <!-- ======================================================== -->
                    <div id="desktopSectionPane">
                        
                        <!-- Select Desktop Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-primary"></i> Desktop Banner Select Design</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="design-cards-grid">
                                    <?php
                                    $formats = [
                                        'desktop_format_1' => ['title' => '1 Format Design', 'badge' => 'Single Square (1:1)', 'class' => 'wf-single-square', 'label' => 'Square Offer'],
                                        'desktop_format_2' => ['title' => '2 Format Design', 'badge' => 'Full Width Rectangular', 'class' => 'wf-full-rect', 'label' => 'Offer Banner (Wide)'],
                                        'desktop_format_3' => ['title' => '3 Format Design', 'badge' => '2 Square Banners Side-by-Side', 'type' => 'two-col'],
                                        'desktop_format_4' => ['title' => '4 Format Design', 'badge' => '2 Columns × 3 Rows Grid', 'type' => 'grid-2x3'],
                                        'desktop_format_5' => ['title' => '5 Format Design', 'badge' => 'Small Slim Strip Banner', 'class' => 'wf-slim-strip', 'label' => 'Small Offer Banner (Strip)'],
                                        'desktop_format_6' => ['title' => '6 Format Design', 'badge' => '3 Columns × 2 Rows Grid', 'type' => 'grid-3x2']
                                    ];
                                    foreach ($formats as $fKey => $fData):
                                        $isActive = ($desktopDesign === $fKey);
                                    ?>
                                        <div class="design-card <?php echo $isActive ? 'active' : ''; ?>" data-format="<?php echo $fKey; ?>" onclick="selectDesktopDesign('<?php echo $fKey; ?>')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box">
                                                <?php if (isset($fData['type']) && $fData['type'] === 'two-col'): ?>
                                                    <div class="wf-two-col"><span>Square 1</span><span>Square 2</span></div>
                                                <?php elseif (isset($fData['type']) && $fData['type'] === 'grid-2x3'): ?>
                                                    <div class="wf-grid-2x3"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div>
                                                <?php elseif (isset($fData['type']) && $fData['type'] === 'grid-3x2'): ?>
                                                    <div class="wf-grid-3x2"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div>
                                                <?php else: ?>
                                                    <div class="<?php echo $fData['class']; ?>"><?php echo $fData['label']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="design-title"><?php echo $fData['title']; ?></div>
                                            <div class="design-badge"><?php echo $fData['badge']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="desktop_design" id="desktopDesignInput" value="<?php echo htmlspecialchars($desktopDesign); ?>">
                            </div>
                        </div>

                        <!-- Dependent Desktop Fields -->
                        <div id="desktopDependentFields">
                            <div class="dimension-info-pill" id="desktopDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="desktopDimText">Dimensions specified based on design</span>
                            </div>

                            <!-- Placement Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-primary"></i> Desktop Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home pages Category Select</label>
                                            <select name="desktop_home_category_id" class="form-select">
                                                <option value="">— Select Category for Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($banner['desktop_home_category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home page Carousel Select</label>
                                            <select name="desktop_home_carousel_id" class="form-select">
                                                <option value="">— Select Placement Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo (($banner['desktop_home_carousel_id'] ?? $banner['section_id']) == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Desktop Show on other pages</label>
                                            <select name="desktop_show_other_pages" id="desktopShowOtherSelect" class="form-select" onchange="toggleDesktopOtherPages(this.value)">
                                                <option value="no" <?php echo ($banner['desktop_show_other_pages'] === 'no') ? 'selected' : ''; ?>>No (Home Page Only)</option>
                                                <option value="yes" <?php echo ($banner['desktop_show_other_pages'] === 'yes') ? 'selected' : ''; ?>>Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 desktop-other-group" style="<?php echo ($banner['desktop_show_other_pages'] === 'yes') ? 'display:block;' : 'display:none;'; ?>">
                                            <label class="form-label fw-semibold">Desktop Other pages Select category</label>
                                            <select name="desktop_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($banner['desktop_other_category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 desktop-other-group" style="<?php echo ($banner['desktop_show_other_pages'] === 'yes') ? 'display:block;' : 'display:none;'; ?>">
                                            <label class="form-label fw-semibold">Desktop Other pages Select Carousel</label>
                                            <select name="desktop_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo ($banner['desktop_other_carousel_id'] == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Box(es) & URL(s) -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-primary"></i> Desktop Banner Image &amp; URL Configuration</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Current / Replace Desktop Image</label>
                                            <div class="upload-dropzone" onclick="document.getElementById('desktopImgInput1').click()">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-1 text-dark fw-semibold">Click to upload new desktop image</p>
                                                <small class="text-muted d-block">Leave blank to keep existing image</small>
                                                <input type="file" name="desktop_image" id="desktopImgInput1" accept="image/*" onchange="previewUpload(this, 'desktopPreview1')">
                                                <?php 
                                                $curDesktopImg = resolveAdminThumb($banner['desktop_image'] ?: $banner['image_url_desktop']);
                                                if ($curDesktopImg): 
                                                ?>
                                                    <img id="desktopPreview1" src="<?php echo htmlspecialchars($curDesktopImg); ?>" class="upload-preview-img" alt="Desktop Preview" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                <?php else: ?>
                                                    <img id="desktopPreview1" class="upload-preview-img" alt="Desktop Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop URL</label>
                                            <input type="text" class="form-control" name="desktop_url"
                                                   value="<?php echo htmlspecialchars($banner['desktop_url'] ?: $banner['target_url'] ?: ''); ?>"
                                                   placeholder="https://printmont.com/category/...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop Schedule, Status & Sort Order -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-primary"></i> Desktop Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Status</label>
                                            <select name="desktop_status" class="form-select">
                                                <option value="active" <?php echo ($banner['desktop_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                                <option value="inactive" <?php echo ($banner['desktop_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop banner Sort Order number</label>
                                            <input type="number" class="form-control" name="desktop_sort_order" value="<?php echo (int)($banner['desktop_sort_order'] ?: $banner['display_order']); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Start Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_start_date" value="<?php echo !empty($banner['desktop_start_date']) ? date('Y-m-d', strtotime($banner['desktop_start_date'])) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop End Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_end_date" value="<?php echo !empty($banner['desktop_end_date']) ? date('Y-m-d', strtotime($banner['desktop_end_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ======================================================== -->
                    <!-- 📱 MOBILE SECTION CONFIGURATION                          -->
                    <!-- ======================================================== -->
                    <div id="mobileSectionPane" style="display: none;">
                        
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-success"></i> Mobile Banner Select Design</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="design-cards-grid">
                                    <?php
                                    $mFormats = [
                                        'mobile_format_1' => ['title' => '1 Format Design', 'badge' => 'Single Square (1:1)', 'class' => 'wf-single-square', 'label' => 'Square 1:1'],
                                        'mobile_format_2' => ['title' => '2 Format Design', 'badge' => '16:9 Landscape Banner', 'class' => 'wf-full-rect', 'label' => 'Landscape'],
                                        'mobile_format_3' => ['title' => '3 Format Design', 'badge' => '2 Square Banners Grid', 'type' => 'two-col'],
                                        'mobile_format_4' => ['title' => '4 Format Design', 'badge' => '2 Columns × 3 Rows Grid', 'type' => 'grid-2x3'],
                                        'mobile_format_5' => ['title' => '5 Format Design', 'badge' => 'Small Height Strip Banner', 'class' => 'wf-slim-strip', 'label' => 'Small Strip'],
                                        'mobile_format_6' => ['title' => '6 Format Design', 'badge' => '3 Columns × 2 Rows Grid', 'type' => 'grid-3x2']
                                    ];
                                    foreach ($mFormats as $fKey => $fData):
                                        $isActive = ($mobileDesign === $fKey);
                                    ?>
                                        <div class="design-card <?php echo $isActive ? 'active' : ''; ?>" data-mformat="<?php echo $fKey; ?>" onclick="selectMobileDesign('<?php echo $fKey; ?>')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box">
                                                <?php if (isset($fData['type']) && $fData['type'] === 'two-col'): ?>
                                                    <div class="wf-two-col"><span>Sq 1</span><span>Sq 2</span></div>
                                                <?php elseif (isset($fData['type']) && $fData['type'] === 'grid-2x3'): ?>
                                                    <div class="wf-grid-2x3"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div>
                                                <?php elseif (isset($fData['type']) && $fData['type'] === 'grid-3x2'): ?>
                                                    <div class="wf-grid-3x2"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span></div>
                                                <?php else: ?>
                                                    <div class="<?php echo $fData['class']; ?>"><?php echo $fData['label']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="design-title"><?php echo $fData['title']; ?></div>
                                            <div class="design-badge"><?php echo $fData['badge']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="mobile_design" id="mobileDesignInput" value="<?php echo htmlspecialchars($mobileDesign); ?>">
                            </div>
                        </div>

                        <!-- Dependent Mobile Fields -->
                        <div id="mobileDependentFields">
                            <div class="dimension-info-pill" id="mobileDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="mobileDimText">Dimensions specified based on design</span>
                            </div>

                            <!-- Placement Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-success"></i> Mobile Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page category select</label>
                                            <select name="mobile_home_category_id" class="form-select">
                                                <option value="">— Select Category for Mobile Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($banner['mobile_home_category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page carousel Select</label>
                                            <select name="mobile_home_carousel_id" class="form-select">
                                                <option value="">— Select Mobile Home Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo ($banner['mobile_home_carousel_id'] == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Mobile Show on Other pages Select</label>
                                            <select name="mobile_show_other_pages" id="mobileShowOtherSelect" class="form-select" onchange="toggleMobileOtherPages(this.value)">
                                                <option value="no" <?php echo ($banner['mobile_show_other_pages'] === 'no') ? 'selected' : ''; ?>>No (Home Page Only)</option>
                                                <option value="yes" <?php echo ($banner['mobile_show_other_pages'] === 'yes') ? 'selected' : ''; ?>>Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mobile-other-group" style="<?php echo ($banner['mobile_show_other_pages'] === 'yes') ? 'display:block;' : 'display:none;'; ?>">
                                            <label class="form-label fw-semibold">Mobile Other pages Select category</label>
                                            <select name="mobile_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($banner['mobile_other_category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mobile-other-group" style="<?php echo ($banner['mobile_show_other_pages'] === 'yes') ? 'display:block;' : 'display:none;'; ?>">
                                            <label class="form-label fw-semibold">Mobile Other pages Select Carousel</label>
                                            <select name="mobile_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo ($banner['mobile_other_carousel_id'] == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Upload & URL -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-success"></i> Mobile Banner Image &amp; URL Configuration</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Current / Replace Mobile Image</label>
                                            <div class="upload-dropzone" onclick="document.getElementById('mobileImgInput1').click()">
                                                <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-1 text-dark fw-semibold">Click to upload new mobile image</p>
                                                <small class="text-muted d-block">Leave blank to keep current image</small>
                                                <input type="file" name="mobile_image" id="mobileImgInput1" accept="image/*" onchange="previewUpload(this, 'mobilePreview1')">
                                                <?php 
                                                $curMobileImg = resolveAdminThumb($banner['mobile_image'] ?: $banner['image_url_mobile']);
                                                if ($curMobileImg): 
                                                ?>
                                                    <img id="mobilePreview1" src="<?php echo htmlspecialchars($curMobileImg); ?>" class="upload-preview-img" alt="Mobile Preview" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                <?php else: ?>
                                                    <img id="mobilePreview1" class="upload-preview-img" alt="Mobile Preview" style="display:none;">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile URL</label>
                                            <input type="text" class="form-control" name="mobile_url"
                                                   value="<?php echo htmlspecialchars($banner['mobile_url'] ?: ''); ?>"
                                                   placeholder="https://printmont.com/category/...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Schedule, Status & Sort Order -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-success"></i> Mobile Banner Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile Banner status</label>
                                            <select name="mobile_status" class="form-select">
                                                <option value="active" <?php echo ($banner['mobile_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                                <option value="inactive" <?php echo ($banner['mobile_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile banner Short Order number</label>
                                            <input type="number" class="form-control" name="mobile_sort_order" value="<?php echo (int)$banner['mobile_sort_order']; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile Start date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_start_date" value="<?php echo !empty($banner['mobile_start_date']) ? date('Y-m-d', strtotime($banner['mobile_start_date'])) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile End date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_end_date" value="<?php echo !empty($banner['mobile_end_date']) ? date('Y-m-d', strtotime($banner['mobile_end_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Submit Bar -->
                    <div class="admin-card">
                        <div class="admin-card-body d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Update will apply across storefront immediately.</span>
                            <div>
                                <a href="view-banner.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i> Update Banner
                                </button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
function switchDeviceTab(tab) {
    const dPane = document.getElementById('desktopSectionPane');
    const mPane = document.getElementById('mobileSectionPane');
    const dBtn  = document.getElementById('tabBtnDesktop');
    const mBtn  = document.getElementById('tabBtnMobile');

    if (tab === 'desktop') {
        dPane.style.display = 'block';
        mPane.style.display = 'none';
        dBtn.classList.add('active');
        mBtn.classList.remove('active');
    } else {
        dPane.style.display = 'none';
        mPane.style.display = 'block';
        dBtn.classList.remove('active');
        mBtn.classList.add('active');
    }
}

function selectDesktopDesign(fmt) {
    document.querySelectorAll('.design-card[data-format]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-format="${fmt}"]`);
    if (target) target.classList.add('active');
    document.getElementById('desktopDesignInput').value = fmt;
}

function selectMobileDesign(fmt) {
    document.querySelectorAll('.design-card[data-mformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-mformat="${fmt}"]`);
    if (target) target.classList.add('active');
    document.getElementById('mobileDesignInput').value = fmt;
}

function previewUpload(input, previewImgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewImgId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleDesktopOtherPages(val) {
    document.querySelectorAll('.desktop-other-group').forEach(el => {
        el.style.display = (val === 'yes') ? 'block' : 'none';
    });
}
function toggleMobileOtherPages(val) {
    document.querySelectorAll('.mobile-other-group').forEach(el => {
        el.style.display = (val === 'yes') ? 'block' : 'none';
    });
}
</script>
</body>
</html>
