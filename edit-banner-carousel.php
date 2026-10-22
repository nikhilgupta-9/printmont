<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/constants.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');
require_once(__DIR__ . '/includes/banner-helpers.php');

$bannerController = new BannerController();
$categories       = $bannerController->getAllCategories();
$carousels        = $bannerController->getAllCarousels();

$carousel = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $carousel = $bannerController->getBannerCarouselById((int)$_GET['id']);
    if (!$carousel) {
        $_SESSION['error_message'] = 'Banner carousel not found.';
        header('Location: view-banner.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = 'Invalid carousel ID.';
    header('Location: view-banner.php');
    exit;
}

$error_message   = '';
$success_message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $result = $bannerController->updateBannerCarousel((int)$_GET['id'], $_POST, $_FILES);
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

$desktopDesign = $carousel['desktop_design'] ?: 'desktop_format_1';
if ($desktopDesign === 'desktop_format_2') {
    $desktopDesign = 'desktop_format_1';
} elseif ($desktopDesign === 'desktop_format_5') {
    $desktopDesign = 'desktop_format_small';
}
$mobileDesign  = $carousel['mobile_design'] ?: 'mobile_format_1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit Banner Carousel: <?php echo htmlspecialchars($carousel['title']); ?> | Printmont Admin</title>
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
        /* Format 1: 1 single image format design */
        .wf-format-1 {
            width: 100%; height: 46px; background: #e0f2fe; border: 1.5px solid #0284c7;
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            font-size: 10px; color: #0369a1; font-weight: 700;
        }

        /* Format 2: 1.5 image format design */
        .wf-format-1-5 {
            display: flex; gap: 4px; width: 100%; height: 46px; align-items: stretch;
        }
        .wf-format-1-5 .wf-main-slide {
            flex: 1 1 70%; background: #eff6ff; border: 1.5px solid #3b82f6;
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            font-size: 10px; color: #1d4ed8; font-weight: 700;
        }
        .wf-format-1-5 .wf-peek-slide {
            width: 28%; background: #e0e7ff; border: 1.5px dashed #6366f1; border-right: none;
            border-radius: 6px 0 0 6px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #4f46e5; font-weight: 700;
        }

        /* Format 3: 3 image format design */
        .wf-format-3 {
            display: flex; gap: 4px; width: 100%; height: 46px; align-items: stretch;
        }
        .wf-format-3 .wf-card {
            flex: 1; background: #fdf2f8; border: 1.5px solid #ec4899;
            border-radius: 5px; display: flex; align-items: center; justify-content: center;
            font-size: 9px; color: #be185d; font-weight: 700;
        }

        /* Format 4: 2.5 image format design */
        .wf-format-2-5 {
            display: flex; gap: 4px; width: 100%; height: 46px; align-items: stretch;
        }
        .wf-format-2-5 .wf-card {
            flex: 1; background: #fffbeb; border: 1.5px solid #f59e0b;
            border-radius: 5px; display: flex; align-items: center; justify-content: center;
            font-size: 9px; color: #b45309; font-weight: 700;
        }
        .wf-format-2-5 .wf-peek-slide {
            width: 20%; background: #fef3c7; border: 1.5px dashed #d97706; border-right: none;
            border-radius: 5px 0 0 5px; display: flex; align-items: center; justify-content: center;
            font-size: 7px; color: #b45309; font-weight: 700;
        }

        /* Format 5: 4 image format design */
        .wf-format-4 {
            display: flex; gap: 3px; width: 100%; height: 46px; align-items: stretch;
        }
        .wf-format-4 .wf-card {
            flex: 1; background: #f5f3ff; border: 1.5px solid #8b5cf6;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #6d28d9; font-weight: 700;
        }

        /* Format 6: 1 Small size format design */
        .wf-format-small {
            width: 100%; height: 24px; background: #ecfdf5; border: 1.5px solid #10b981;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #047857; font-weight: 700;
        }

        .wf-single-square {
            width: 50px; height: 50px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: #b91c1c; font-weight: 700;
        }
        .wf-two-col { display: flex; gap: 6px; width: 100%; justify-content: center; }
        .wf-two-col span {
            width: 45px; height: 45px; background: #fee2e2; border: 1px solid #ef4444;
            border-radius: 4px; display: flex; align-items: center; justify-content: center;
            font-size: 7px; color: #b91c1c; font-weight: 700; text-align: center;
        }

        .design-title { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .design-badge { font-size: 11px; color: #64748b; font-weight: 500; }

        .control-pill-box {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            user-select: none;
        }
        .control-pill-box:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }
        .control-pill-box input[type="radio"]:checked ~ label,
        .control-pill-box.active {
            border-color: #0b53a1;
            background: #f0f7ff;
        }

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
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;
        }
        .slot-box {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;
            position: relative;
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

                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-edit text-primary me-2"></i>Edit Banner Carousel #<?php echo $carousel['id']; ?>
                        </h3>
                        <p class="text-muted small mb-0">Update carousel slideshow properties, slides, and placements.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="view-banner.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Banners
                        </a>
                        <a href="add-banner-carousel.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New Carousel
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

                    <!-- Title -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5><i class="fas fa-heading text-primary"></i> 1. Banner Carousel Title Name</h5>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label required-field fw-semibold">Banner Carousel Title Name Add</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required
                                           value="<?php echo htmlspecialchars($carousel['title']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device Navigation Tabs -->
                    <div class="device-nav-pills">
                        <button type="button" class="device-pill-btn active" id="tabBtnDesktop" onclick="switchDeviceTab('desktop')">
                            <i class="fas fa-desktop"></i> Desktop Banner Carousel
                        </button>
                        <button type="button" class="device-pill-btn" id="tabBtnMobile" onclick="switchDeviceTab('mobile')">
                            <i class="fas fa-mobile-alt"></i> Mobile Banner Carousel
                        </button>
                    </div>

                    <!-- ======================================================== -->
                    <!-- 🖥️ DESKTOP CAROUSEL SECTION                              -->
                    <!-- ======================================================== -->
                    <div id="desktopSectionPane">
                        
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-primary"></i> Desktop Banner Carousel Select Design</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="design-cards-grid">
                                    <?php
                                    $formats = [
                                        'desktop_format_1'     => ['title' => '1 single image format design', 'badge' => '1 Full-Width Landscape Card', 'class' => 'wf-format-1', 'label' => '1 Full Width Slide', 'dim' => '1920×600 px / 1200×450 px'],
                                        'desktop_format_1_5'   => ['title' => '1.5 image format design', 'badge' => '1 Card + 0.5 Peek Card', 'type' => 'format-1-5', 'dim' => '1200×500 px (1 + 0.5 Peek)'],
                                        'desktop_format_3'     => ['title' => '3 image format design', 'badge' => '3 Cards Side-by-Side', 'type' => 'format-3', 'dim' => '600×400 px each (3 Cards)'],
                                        'desktop_format_2_5'   => ['title' => '2.5 image format design', 'badge' => '2 Cards + 0.5 Peek Card', 'type' => 'format-2-5', 'dim' => '600×400 px each (2 + 0.5 Peek)'],
                                        'desktop_format_4'     => ['title' => '4 image format design', 'badge' => '4 Cards Side-by-Side', 'type' => 'format-4', 'dim' => '450×320 px each (4 Cards)'],
                                        'desktop_format_small' => ['title' => '1 Small size format design', 'badge' => 'Small Height Strip Slider', 'class' => 'wf-format-small', 'label' => '1 Small Strip Banner', 'dim' => '1920×220 px / 1200×180 px'],
                                    ];
                                    foreach ($formats as $fKey => $fData):
                                        $isActive = ($desktopDesign === $fKey);
                                    ?>
                                        <div class="design-card <?php echo $isActive ? 'active' : ''; ?>" data-cformat="<?php echo $fKey; ?>" onclick="selectDesktopCarouselDesign('<?php echo $fKey; ?>')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box">
                                                <?php if ($fKey === 'desktop_format_1_5'): ?>
                                                    <div class="wf-format-1-5">
                                                        <div class="wf-main-slide">1 Card</div>
                                                        <div class="wf-peek-slide">0.5</div>
                                                    </div>
                                                <?php elseif ($fKey === 'desktop_format_3'): ?>
                                                    <div class="wf-format-3">
                                                        <div class="wf-card">1</div>
                                                        <div class="wf-card">2</div>
                                                        <div class="wf-card">3</div>
                                                    </div>
                                                <?php elseif ($fKey === 'desktop_format_2_5'): ?>
                                                    <div class="wf-format-2-5">
                                                        <div class="wf-card">1</div>
                                                        <div class="wf-card">2</div>
                                                        <div class="wf-peek-slide">0.5</div>
                                                    </div>
                                                <?php elseif ($fKey === 'desktop_format_4'): ?>
                                                    <div class="wf-format-4">
                                                        <div class="wf-card">1</div>
                                                        <div class="wf-card">2</div>
                                                        <div class="wf-card">3</div>
                                                        <div class="wf-card">4</div>
                                                    </div>
                                                <?php elseif ($fKey === 'desktop_format_small'): ?>
                                                    <div class="wf-format-small">1 Small Strip Banner</div>
                                                <?php else: ?>
                                                    <div class="wf-format-1">1 Full Width Slide</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="design-title"><?php echo $fData['title']; ?></div>
                                            <div class="design-badge"><?php echo $fData['badge']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="desktop_design" id="desktopCarouselDesignInput" value="<?php echo htmlspecialchars($desktopDesign); ?>">

                                <div class="dimension-info-pill mt-3 mb-0" id="desktopCarouselDimPill">
                                    <i class="fas fa-info-circle fa-lg"></i>
                                    <span id="desktopCarouselDimText">
                                        <strong><?php echo $formats[$desktopDesign]['title'] ?? 'Carousel Design'; ?>:</strong> Recommended Dimensions: <strong><?php echo $formats[$desktopDesign]['dim'] ?? '1920×600 px'; ?></strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Carousel Navigation Buttons & Autoplay Controls -->
                        <div class="admin-card border-primary border-opacity-25 shadow-sm">
                            <div class="admin-card-header bg-light">
                                <h5>
                                    <i class="fas fa-sliders-h text-primary"></i> 
                                    Carousel Navigation Arrows &amp; Auto-running Controls
                                </h5>
                                <span class="badge bg-primary-subtle text-primary fw-semibold">Interactive Controls</span>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-4">
                                    <!-- Left & Right Button Arrows -->
                                    <div class="col-md-6 border-end-md">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-arrows-alt-h text-primary"></i> Left &amp; Right Navigation Button Arrows
                                        </label>
                                        <p class="text-muted small mb-3">Enable or disable previous and next arrow buttons on the carousel sides.</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <label class="control-pill-box flex-fill <?php echo (($carousel['button_arrows'] ?? 'enable') !== 'disable') ? 'active' : ''; ?>">
                                                <input type="radio" name="button_arrows" value="enable" <?php echo (($carousel['button_arrows'] ?? 'enable') !== 'disable') ? 'checked' : ''; ?> class="form-check-input mt-0 me-2">
                                                <div>
                                                    <strong class="d-block text-success small"><i class="fas fa-check-circle me-1"></i> Enable Arrows</strong>
                                                    <span class="text-muted" style="font-size:11px;">Show Left &amp; Right Buttons</span>
                                                </div>
                                            </label>
                                            <label class="control-pill-box flex-fill <?php echo (($carousel['button_arrows'] ?? 'enable') === 'disable') ? 'active' : ''; ?>">
                                                <input type="radio" name="button_arrows" value="disable" <?php echo (($carousel['button_arrows'] ?? 'enable') === 'disable') ? 'checked' : ''; ?> class="form-check-input mt-0 me-2">
                                                <div>
                                                    <strong class="d-block text-danger small"><i class="fas fa-ban me-1"></i> Disable Arrows</strong>
                                                    <span class="text-muted" style="font-size:11px;">Hide Left &amp; Right Buttons</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Auto-running & Speed -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-play-circle text-success"></i> Auto-running (Autoplay Slideshow)
                                        </label>
                                        <p class="text-muted small mb-3">Enable automatic rotating slides or set manual user sliding only.</p>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <label class="control-pill-box flex-fill <?php echo (($carousel['auto_running'] ?? 'enable') !== 'disable') ? 'active' : ''; ?>">
                                                <input type="radio" name="auto_running" value="enable" <?php echo (($carousel['auto_running'] ?? 'enable') !== 'disable') ? 'checked' : ''; ?> onchange="toggleAutoRunSpeed(true)" class="form-check-input mt-0 me-2">
                                                <div>
                                                    <strong class="d-block text-success small"><i class="fas fa-play me-1"></i> Enable Auto-run</strong>
                                                    <span class="text-muted" style="font-size:11px;">Automatic Slide Rotation</span>
                                                </div>
                                            </label>
                                            <label class="control-pill-box flex-fill <?php echo (($carousel['auto_running'] ?? 'enable') === 'disable') ? 'active' : ''; ?>">
                                                <input type="radio" name="auto_running" value="disable" <?php echo (($carousel['auto_running'] ?? 'enable') === 'disable') ? 'checked' : ''; ?> onchange="toggleAutoRunSpeed(false)" class="form-check-input mt-0 me-2">
                                                <div>
                                                    <strong class="d-block text-secondary small"><i class="fas fa-pause me-1"></i> Disable Auto-run</strong>
                                                    <span class="text-muted" style="font-size:11px;">Manual Navigation Only</span>
                                                </div>
                                            </label>
                                        </div>

                                        <div id="autoRunSpeedWrapper" class="p-2 bg-light rounded border" style="<?php echo (($carousel['auto_running'] ?? 'enable') === 'disable') ? 'opacity: 0.4;' : ''; ?>">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto">
                                                    <label class="form-label small fw-bold mb-0 text-dark">
                                                        <i class="fas fa-tachometer-alt text-primary me-1"></i> Speed (ms):
                                                    </label>
                                                </div>
                                                <div class="col-auto">
                                                    <input type="number" class="form-control form-control-sm" name="auto_run_speed" id="autoRunSpeedInput" value="<?php echo htmlspecialchars((string)($carousel['auto_run_speed'] ?: 3000)); ?>" min="1000" step="500" style="width: 105px;" <?php echo (($carousel['auto_running'] ?? 'enable') === 'disable') ? 'disabled' : ''; ?>>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(2000)">2.0s</button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(3000)">3.0s</button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(4000)">4.0s</button>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="setSpeed(5000)">5.0s</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-1">3000ms = 3.0 seconds delay per slide transition.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            </div>
                        </div>

                        <!-- Desktop Placement & Target Page Settings -->
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-map-marker-alt text-primary"></i> Desktop Page, Category &amp; Section Placement</h5>
                                <a href="banner-layouts.php" target="_blank" class="btn btn-outline-secondary btn-sm" title="Add a new slot on any page">
                                    <i class="fas fa-plus-circle me-1"></i> Add Custom Page Slot
                                </a>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <!-- 1. Target Page -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-file-alt text-primary me-1"></i> Desktop Target Page Select
                                        </label>
                                        <?php $curDeskPage = $carousel['desktop_page'] ?? 'home'; ?>
                                        <select name="desktop_page" id="desktopPageSelect" class="form-select" onchange="filterDesktopSections(this.value)">
                                            <option value="home" <?php echo ($curDeskPage === 'home') ? 'selected' : ''; ?>>Home Page (home)</option>
                                            <option value="category" <?php echo ($curDeskPage === 'category') ? 'selected' : ''; ?>>Category Page (category)</option>
                                            <option value="product" <?php echo ($curDeskPage === 'product') ? 'selected' : ''; ?>>Product Page (product)</option>
                                            <option value="blog" <?php echo ($curDeskPage === 'blog') ? 'selected' : ''; ?>>Blog Page (blog)</option>
                                            <option value="cart" <?php echo ($curDeskPage === 'cart') ? 'selected' : ''; ?>>Cart Page (cart)</option>
                                            <option value="checkout" <?php echo ($curDeskPage === 'checkout') ? 'selected' : ''; ?>>Checkout Page (checkout)</option>
                                            <option value="orders" <?php echo ($curDeskPage === 'orders') ? 'selected' : ''; ?>>Orders Page (orders)</option>
                                            <option value="all" <?php echo ($curDeskPage === 'all') ? 'selected' : ''; ?>>All Pages (Global Sitewide)</option>
                                            <option value="other" <?php echo ($curDeskPage === 'other') ? 'selected' : ''; ?>>Other / Custom Pages</option>
                                        </select>
                                        <small class="text-muted">Choose which page this carousel appears on.</small>
                                    </div>

                                    <!-- 2. Dynamic Placement Section / Carousel Slot on That Page -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-layer-group text-primary me-1"></i> Page Section / Slot Select
                                        </label>
                                        <select name="desktop_home_carousel_id" id="desktopCarouselSelect" class="form-select">
                                            <option value="">— Select Section / Carousel Slot —</option>
                                        </select>
                                        <small class="text-muted" id="desktopSectionHelperText">Available slots for chosen page.</small>
                                    </div>

                                    <!-- 3. Target Category -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-tags text-primary me-1"></i> Desktop Category Select
                                        </label>
                                        <select name="desktop_home_category_id" id="desktopCategorySelect" class="form-select">
                                            <option value="">— All Categories (Global on this Page) —</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo ($carousel['desktop_home_category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Apply to a specific category or all categories.</small>
                                    </div>
                                </div>

                                <!-- Multi-Page Application / Show on other pages -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Desktop Show on other pages</label>
                                            <?php $curDeskOther = $carousel['desktop_show_other_pages'] ?? 'no'; ?>
                                            <select name="desktop_show_other_pages" id="desktopShowOtherSelect" class="form-select" onchange="toggleDesktopOtherPages(this.value)">
                                                <option value="no" <?php echo ($curDeskOther === 'no') ? 'selected' : ''; ?>>No (Selected Page Only)</option>
                                                <option value="yes" <?php echo ($curDeskOther === 'yes') ? 'selected' : ''; ?>>Yes (Also Apply on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 desktop-other-group" style="<?php echo ($curDeskOther === 'yes') ? '' : 'display: none;'; ?>">
                                            <label class="form-label fw-semibold">Desktop Other pages Select category</label>
                                            <select name="desktop_other_category_id" class="form-select">
                                                <option value="">— All Other Page Categories —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['desktop_other_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 desktop-other-group" style="<?php echo ($curDeskOther === 'yes') ? '' : 'display: none;'; ?>">
                                            <label class="form-label fw-semibold">Desktop Other pages Select Carousel</label>
                                            <select name="desktop_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Slot —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo (($carousel['desktop_other_carousel_id'] ?? 0) == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <?php
                        $existingDeskImgs = !empty($carousel['desktop_images']) ? json_decode($carousel['desktop_images'], true) : [];
                        if (empty($existingDeskImgs) && !empty($carousel['desktop_image'])) {
                            $existingDeskImgs = [$carousel['desktop_image']];
                        }
                        $existingDeskUrls = !empty($carousel['desktop_urls']) ? json_decode($carousel['desktop_urls'], true) : [];
                        if (empty($existingDeskUrls) && !empty($carousel['desktop_url'])) {
                            $existingDeskUrls = [$carousel['desktop_url']];
                        }
                        $totalDeskSlots = max(count($existingDeskImgs), 4);
                        ?>
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-images text-primary"></i> Desktop Carousel Images &amp; URLs</h5>
                                <div>
                                    <span class="badge bg-secondary me-2" id="dCarouselSlotsBadge"><?php echo $totalDeskSlots; ?> Slide Slots</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAnotherDesktopSlot()">
                                        <i class="fas fa-plus me-1"></i> Add Another Slide Slot
                                    </button>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Each slide rotates automatically or via navigation arrow buttons. Click any image dropzone to change/upload, or enter a target link.
                                </p>
                                <div class="multi-slot-grid" id="desktopSlotsGridContainer">
                                    <?php for ($i = 0; $i < $totalDeskSlots; $i++): 
                                        $img = $existingDeskImgs[$i] ?? '';
                                        $url = $existingDeskUrls[$i] ?? '';
                                        $thumb = resolveAdminThumb($img);
                                    ?>
                                        <div class="slot-box" id="dSlotCard_<?php echo $i + 1; ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="text-dark small"><i class="fas fa-layer-group text-primary me-1"></i> Slide #<?php echo ($i + 1); ?></strong>
                                                <span class="badge bg-light text-secondary border slot-dim-badge"><?php echo $formats[$desktopDesign]['dim'] ?? '1920×600 px'; ?></span>
                                            </div>
                                            <div class="upload-dropzone mb-2" onclick="document.getElementById('dCSlot_<?php echo $i; ?>').click()">
                                                <?php if (!empty($thumb)): ?>
                                                    <img id="dCPrev_<?php echo $i; ?>" src="<?php echo htmlspecialchars($thumb); ?>" class="upload-preview-img" style="display:block;" alt="Slide <?php echo ($i+1); ?> Preview" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                                    <small class="text-muted d-block mt-1"><i class="fas fa-sync-alt me-1"></i> Click to change image</small>
                                                <?php else: ?>
                                                    <i class="fas fa-cloud-upload-alt text-muted fa-lg mb-1"></i>
                                                    <small class="d-block text-dark fw-semibold">Upload Slide <?php echo ($i + 1); ?> Image</small>
                                                    <img id="dCPrev_<?php echo $i; ?>" class="upload-preview-img" alt="Slide <?php echo ($i+1); ?> Preview" style="display:none;">
                                                <?php endif; ?>
                                                <input type="hidden" name="existing_desktop_images[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($img); ?>">
                                                <input type="file" name="desktop_images[<?php echo $i; ?>]" id="dCSlot_<?php echo $i; ?>" accept="image/*" onchange="previewUpload(this, 'dCPrev_<?php echo $i; ?>')">
                                            </div>
                                            <label class="form-label small fw-semibold mb-1">Slide <?php echo ($i + 1); ?> URL</label>
                                            <input type="text" class="form-control form-control-sm" name="desktop_urls[]" value="<?php echo htmlspecialchars($url); ?>" placeholder="https://printmont.com/...">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-calendar-alt text-primary"></i> Desktop Carousel Status, Order &amp; Schedule</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Desktop Status</label>
                                        <select name="desktop_status" class="form-select">
                                            <option value="active" <?php echo ($carousel['desktop_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                            <option value="inactive" <?php echo ($carousel['desktop_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Desktop banner carousel Short Order number</label>
                                        <input type="number" class="form-control" name="desktop_sort_order" value="<?php echo (int)$carousel['desktop_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Desktop Start Date Calendar</label>
                                        <input type="date" class="form-control" name="desktop_start_date" value="<?php echo !empty($carousel['desktop_start_date']) ? date('Y-m-d', strtotime($carousel['desktop_start_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Desktop End Date Calendar</label>
                                        <input type="date" class="form-control" name="desktop_end_date" value="<?php echo !empty($carousel['desktop_end_date']) ? date('Y-m-d', strtotime($carousel['desktop_end_date'])) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- 📱 MOBILE CAROUSEL SECTION                               -->
                    <!-- ======================================================== -->
                    <div id="mobileSectionPane" style="display: none;">
                        
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-success"></i> Mobile Banner Carousel Select Design</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="design-cards-grid">
                                    <?php
                                    $mFormats = [
                                        'mobile_format_1' => ['title' => '1 Format Design', 'badge' => 'Single Square Carousel', 'class' => 'wf-single-square', 'label' => 'Square 1:1'],
                                        'mobile_format_2' => ['title' => '2 Format Design', 'badge' => '16:9 Landscape Slider', 'class' => 'wf-full-rect', 'label' => 'Landscape'],
                                        'mobile_format_3' => ['title' => '3 Format Design', 'badge' => '2 Square Banners Carousel', 'type' => 'two-col'],
                                        'mobile_format_4' => ['title' => '4 Format Design', 'badge' => '2 Columns × 3 Rows Grid', 'type' => 'grid-2x3'],
                                        'mobile_format_5' => ['title' => '5 Format Design', 'badge' => 'Small Height Strip Slider', 'class' => 'wf-slim-strip', 'label' => 'Small Strip'],
                                        'mobile_format_6' => ['title' => '6 Format Design', 'badge' => '3 Columns × 2 Rows Grid', 'type' => 'grid-3x2']
                                    ];
                                    foreach ($mFormats as $fKey => $fData):
                                        $isActive = ($mobileDesign === $fKey);
                                    ?>
                                        <div class="design-card <?php echo $isActive ? 'active' : ''; ?>" data-cmformat="<?php echo $fKey; ?>" onclick="selectMobileCarouselDesign('<?php echo $fKey; ?>')">
                                            <div class="check-indicator"><i class="fas fa-check"></i></div>
                                            <div class="wireframe-box">
                                                <?php if (isset($fData['type']) && $fData['type'] === 'two-col'): ?>
                                                    <div class="wf-two-col"><span>Slide 1</span><span>Slide 2</span></div>
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

                                <input type="hidden" name="mobile_design" id="mobileCarouselDesignInput" value="<?php echo htmlspecialchars($mobileDesign); ?>">
                            </div>
                        </div>

                        <!-- Mobile Placement & Target Page Settings -->
                        <div class="admin-card">
                            <div class="admin-card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-map-marker-alt text-success"></i> Mobile Page, Category &amp; Section Placement</h5>
                                <a href="banner-layouts.php" target="_blank" class="btn btn-outline-secondary btn-sm" title="Add a new slot on any page">
                                    <i class="fas fa-plus-circle me-1"></i> Add Custom Mobile Slot
                                </a>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <!-- 1. Target Page -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-mobile-alt text-success me-1"></i> Mobile Target Page Select
                                        </label>
                                        <?php $curMobPage = $carousel['mobile_page'] ?? 'home'; ?>
                                        <select name="mobile_page" id="mobilePageSelect" class="form-select" onchange="filterMobileSections(this.value)">
                                            <option value="home" <?php echo ($curMobPage === 'home') ? 'selected' : ''; ?>>Home Page (home)</option>
                                            <option value="category" <?php echo ($curMobPage === 'category') ? 'selected' : ''; ?>>Category Page (category)</option>
                                            <option value="product" <?php echo ($curMobPage === 'product') ? 'selected' : ''; ?>>Product Page (product)</option>
                                            <option value="blog" <?php echo ($curMobPage === 'blog') ? 'selected' : ''; ?>>Blog Page (blog)</option>
                                            <option value="cart" <?php echo ($curMobPage === 'cart') ? 'selected' : ''; ?>>Cart Page (cart)</option>
                                            <option value="checkout" <?php echo ($curMobPage === 'checkout') ? 'selected' : ''; ?>>Checkout Page (checkout)</option>
                                            <option value="orders" <?php echo ($curMobPage === 'orders') ? 'selected' : ''; ?>>Orders Page (orders)</option>
                                            <option value="all" <?php echo ($curMobPage === 'all') ? 'selected' : ''; ?>>All Pages (Global Mobile)</option>
                                            <option value="other" <?php echo ($curMobPage === 'other') ? 'selected' : ''; ?>>Other / Custom Pages</option>
                                        </select>
                                        <small class="text-muted">Target mobile page for this carousel.</small>
                                    </div>

                                    <!-- 2. Target Section / Slot on That Page -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-layer-group text-success me-1"></i> Mobile Carousel Section Select
                                        </label>
                                        <select name="mobile_carousel_id" id="mobileCarouselSelect" class="form-select">
                                            <option value="">— Select Mobile Carousel Section —</option>
                                        </select>
                                        <small class="text-muted" id="mobileSectionHelperText">Available slots for chosen mobile page.</small>
                                    </div>

                                    <!-- 3. Target Category -->
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark">
                                            <i class="fas fa-tags text-success me-1"></i> Mobile Category Select
                                        </label>
                                        <select name="mobile_category_id" id="mobileCategorySelect" class="form-select">
                                            <option value="">— All Categories (Global) —</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['mobile_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Apply to a specific category or all categories.</small>
                                    </div>
                                </div>

                                <!-- Multi-Page Application / Show on other pages -->
                                <div class="mt-4 pt-3 border-top">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Mobile show on other pages select</label>
                                            <?php $curMobOther = $carousel['mobile_other_pages'] ?? 'no'; ?>
                                            <select name="mobile_other_pages" id="mobileShowOtherSelect" class="form-select" onchange="toggleMobileOtherPages(this.value)">
                                                <option value="no" <?php echo ($curMobOther === 'no') ? 'selected' : ''; ?>>No (Selected Page Only)</option>
                                                <option value="yes" <?php echo ($curMobOther === 'yes') ? 'selected' : ''; ?>>Yes (Also Apply on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mobile-other-group" style="<?php echo ($curMobOther === 'yes') ? '' : 'display: none;'; ?>">
                                            <label class="form-label fw-semibold">Mobile Other pages Select category</label>
                                            <select name="mobile_other_category_id" class="form-select">
                                                <option value="">— All Other Page Categories —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo (($carousel['mobile_other_category_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mobile-other-group" style="<?php echo ($curMobOther === 'yes') ? '' : 'display: none;'; ?>">
                                            <label class="form-label fw-semibold">Mobile Other pages Select Carousel</label>
                                            <select name="mobile_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Slot —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>" <?php echo (($carousel['mobile_other_carousel_id'] ?? 0) == $car['id']) ? 'selected' : ''; ?>>
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-images text-success"></i> Mobile Carousel Image &amp; URL</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Current / Replace Mobile Image</label>
                                        <div class="upload-dropzone" onclick="document.getElementById('mCarouselImg1').click()">
                                            <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                                            <p class="mb-1 text-dark fw-semibold">Click to upload new mobile image</p>
                                            <small class="text-muted d-block">Leave blank to keep existing image</small>
                                            <input type="file" name="mobile_image" id="mCarouselImg1" accept="image/*" onchange="previewUpload(this, 'mCarouselPrev1')">
                                            <?php 
                                            $mPrevImg = resolveAdminThumb($carousel['mobile_image'] ?? '');
                                            if (!empty($mPrevImg)): ?>
                                                <img id="mCarouselPrev1" src="<?php echo htmlspecialchars($mPrevImg); ?>" class="upload-preview-img" alt="Mobile Preview" onerror="this.onerror=null; this.src='banners/banner-30.jpg';">
                                            <?php else: ?>
                                                <img id="mCarouselPrev1" class="upload-preview-img" alt="Mobile Preview" style="display:none;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Mobile URL</label>
                                        <input type="text" class="form-control" name="mobile_url"
                                               value="<?php echo htmlspecialchars($carousel['mobile_url'] ?: ''); ?>"
                                               placeholder="https://printmont.com/category/...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-calendar-alt text-success"></i> Mobile Carousel Status, Order &amp; Schedule</h5>
                            </div>
                            <div class="admin-card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Mobile Banner carousel status show and hide</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo ($carousel['mobile_status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                            <option value="inactive" <?php echo ($carousel['mobile_status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Mobile banner carousel Short Order number</label>
                                        <input type="number" class="form-control" name="mobile_sort_order" value="<?php echo (int)$carousel['mobile_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Mobile banner carousel other shorts order number</label>
                                        <input type="number" class="form-control" name="mobile_other_sort_order" value="<?php echo (int)$carousel['mobile_other_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Mobile Start date Calendar</label>
                                        <input type="date" class="form-control" name="mobile_start_date" value="<?php echo !empty($carousel['mobile_start_date']) ? date('Y-m-d', strtotime($carousel['mobile_start_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Mobile End date Calendar</label>
                                        <input type="date" class="form-control" name="mobile_end_date" value="<?php echo !empty($carousel['mobile_end_date']) ? date('Y-m-d', strtotime($carousel['mobile_end_date'])) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Submit Bar -->
                    <div class="admin-card">
                        <div class="admin-card-body d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Update will apply to carousel slideshow immediately.</span>
                            <div>
                                <a href="view-banner.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i> Update Carousel
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
const CAROUSEL_SPECS = {
    'desktop_format_1':     { name: '1 single image format design', dim: '1920×600 px / 1200×450 px' },
    'desktop_format_1_5':   { name: '1.5 image format design', dim: '1200×500 px (1 + 0.5 Peek)' },
    'desktop_format_3':     { name: '3 image format design', dim: '600×400 px each (3 Cards)' },
    'desktop_format_2_5':   { name: '2.5 image format design', dim: '600×400 px each (2 + 0.5 Peek)' },
    'desktop_format_4':     { name: '4 image format design', dim: '450×320 px each (4 Cards)' },
    'desktop_format_small': { name: '1 Small size format design', dim: '1920×220 px / 1200×180 px' },

    // Backward compatibility
    'desktop_format_2':     { name: '1 single image format design', dim: '1920×600 px / 1200×450 px' },
    'desktop_format_5':     { name: '1 Small size format design', dim: '1920×220 px / 1200×180 px' },

    'mobile_format_1':      { name: '1 Single Mobile Image', dim: '600×600 px / 800×450 px' },
    'mobile_format_1_5':    { name: '1.5 Mobile Image Peek', dim: '600×400 px (1 + 0.5 Peek)' },
    'mobile_format_2':      { name: 'Mobile Landscape 16:9', dim: '800×450 px' },
    'mobile_format_3':      { name: 'Mobile 2 Columns', dim: '600×600 px each' },
    'mobile_format_small':  { name: 'Mobile Small Strip', dim: '800×200 px' },
    'mobile_format_4':      { name: 'Mobile 2 Columns × 3 Rows', dim: '600×600 px each' },
};

let currentDesktopSlotsCount = <?php echo (int)$totalDeskSlots; ?>;

function toggleAutoRunSpeed(enabled) {
    const wrap = document.getElementById('autoRunSpeedWrapper');
    const input = document.getElementById('autoRunSpeedInput');
    if (wrap) wrap.style.opacity = enabled ? '1' : '0.4';
    if (input) input.disabled = !enabled;
}

function setSpeed(ms) {
    const input = document.getElementById('autoRunSpeedInput');
    if (input) input.value = ms;
}

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

function selectDesktopCarouselDesign(fmt) {
    document.querySelectorAll('.design-card[data-cformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-cformat="${fmt}"]`);
    if (target) target.classList.add('active');
    document.getElementById('desktopCarouselDesignInput').value = fmt;

    const spec = CAROUSEL_SPECS[fmt];
    if (spec) {
        const dimText = document.getElementById('desktopCarouselDimText');
        if (dimText) {
            dimText.innerHTML = `<strong>${spec.name}:</strong> Recommended Dimensions: <strong>${spec.dim}</strong>`;
        }
        document.querySelectorAll('.slot-dim-badge').forEach(el => {
            el.textContent = spec.dim;
        });
    }
}

function addAnotherDesktopSlot() {
    currentDesktopSlotsCount++;
    const grid = document.getElementById('desktopSlotsGridContainer');
    const badge = document.getElementById('dCarouselSlotsBadge');
    const curFmt = document.getElementById('desktopCarouselDesignInput').value || 'desktop_format_1';
    const dim = (CAROUSEL_SPECS[curFmt] && CAROUSEL_SPECS[curFmt].dim) ? CAROUSEL_SPECS[curFmt].dim : '1920×600 px';

    const div = document.createElement('div');
    div.className = 'slot-box';
    div.id = `dSlotCard_${currentDesktopSlotsCount}`;
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="text-dark small"><i class="fas fa-layer-group text-primary me-1"></i> Slide #${currentDesktopSlotsCount}</strong>
            <span class="badge bg-light text-secondary border slot-dim-badge">${dim}</span>
        </div>
        <div class="upload-dropzone mb-2" onclick="document.getElementById('dCSlot_${currentDesktopSlotsCount - 1}').click()">
            <i class="fas fa-cloud-upload-alt text-muted fa-lg mb-1"></i>
            <small class="d-block text-dark fw-semibold">Upload Slide ${currentDesktopSlotsCount} Image</small>
            <input type="file" name="desktop_images[${currentDesktopSlotsCount - 1}]" id="dCSlot_${currentDesktopSlotsCount - 1}" accept="image/*" onchange="previewUpload(this, 'dCPrev_${currentDesktopSlotsCount - 1}')">
            <img id="dCPrev_${currentDesktopSlotsCount - 1}" class="upload-preview-img" alt="Slide ${currentDesktopSlotsCount} Preview" style="display:none;">
        </div>
        <label class="form-label small fw-semibold mb-1">Slide ${currentDesktopSlotsCount} URL</label>
        <input type="text" class="form-control form-control-sm" name="desktop_urls[]" placeholder="https://printmont.com/...">
    `;

    if (grid) {
        grid.appendChild(div);
        if (badge) badge.innerText = `${currentDesktopSlotsCount} Slide Slots`;
    }
}

function selectMobileCarouselDesign(fmt) {
    document.querySelectorAll('.design-card[data-cmformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-cmformat="${fmt}"]`);
    if (target) target.classList.add('active');
    document.getElementById('mobileCarouselDesignInput').value = fmt;
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

const ALL_CAROUSEL_SECTIONS = <?php echo json_encode($carousels); ?>;
const SAVED_DESK_PAGE = <?php echo json_encode($carousel['desktop_page'] ?? 'home'); ?>;
const SAVED_DESK_SLOT = <?php echo (int)($carousel['desktop_home_carousel_id'] ?? 0); ?>;
const SAVED_MOB_PAGE  = <?php echo json_encode($carousel['mobile_page'] ?? 'home'); ?>;
const SAVED_MOB_SLOT  = <?php echo (int)($carousel['mobile_carousel_id'] ?? 0); ?>;

function filterDesktopSections(selectedPage, preselectedId = null) {
    const sel = document.getElementById('desktopCarouselSelect');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Select Section / Carousel Slot —</option>';

    let filtered = ALL_CAROUSEL_SECTIONS.filter(s => {
        if (!selectedPage || selectedPage === 'all') return true;
        if (selectedPage === 'other') return !['home', 'category', 'product', 'blog', 'cart', 'checkout', 'orders'].includes(s.page);
        return s.page === selectedPage;
    });

    if (filtered.length === 0) {
        filtered = ALL_CAROUSEL_SECTIONS;
        const note = document.createElement('option');
        note.disabled = true;
        note.textContent = `(No preset slots for "${selectedPage}" - showing all slots)`;
        sel.appendChild(note);
    }

    const checkId = preselectedId !== null ? preselectedId : SAVED_DESK_SLOT;

    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `[${s.page.toUpperCase()}] ${s.title} (${s.is_slider ? 'Slider' : (s.columns_per_row || 1) + ' Col'})`;
        if (checkId && parseInt(checkId) === parseInt(s.id)) {
            opt.selected = true;
        }
        sel.appendChild(opt);
    });

    const helper = document.getElementById('desktopSectionHelperText');
    if (helper) {
        helper.textContent = `Showing ${filtered.length} available section slots for [${selectedPage.toUpperCase()}] page.`;
    }
}

function filterMobileSections(selectedPage, preselectedId = null) {
    const sel = document.getElementById('mobileCarouselSelect');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Select Mobile Carousel Section —</option>';

    let filtered = ALL_CAROUSEL_SECTIONS.filter(s => {
        if (!selectedPage || selectedPage === 'all') return true;
        if (selectedPage === 'home') return s.page === 'home' || s.page === 'mobile';
        if (selectedPage === 'other') return !['home', 'mobile', 'category', 'product', 'blog', 'cart', 'checkout', 'orders'].includes(s.page);
        return s.page === selectedPage;
    });

    if (filtered.length === 0) {
        filtered = ALL_CAROUSEL_SECTIONS;
    }

    const checkId = preselectedId !== null ? preselectedId : SAVED_MOB_SLOT;

    filtered.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `[${s.page.toUpperCase()}] ${s.title} (${s.is_slider ? 'Slider' : (s.columns_per_row || 1) + ' Col'})`;
        if (checkId && parseInt(checkId) === parseInt(s.id)) {
            opt.selected = true;
        }
        sel.appendChild(opt);
    });

    const helper = document.getElementById('mobileSectionHelperText');
    if (helper) {
        helper.textContent = `Showing ${filtered.length} available slots for [${selectedPage.toUpperCase()}] mobile view.`;
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

document.addEventListener('DOMContentLoaded', function() {
    filterDesktopSections(document.getElementById('desktopPageSelect') ? document.getElementById('desktopPageSelect').value : SAVED_DESK_PAGE, SAVED_DESK_SLOT);
    filterMobileSections(document.getElementById('mobilePageSelect') ? document.getElementById('mobilePageSelect').value : SAVED_MOB_PAGE, SAVED_MOB_SLOT);
});
</script>
</body>
</html>
