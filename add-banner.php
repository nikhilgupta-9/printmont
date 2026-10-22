<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();
$categories       = $bannerController->getAllCategories();
$carousels        = $bannerController->getAllCarousels();

$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $bannerController->createBanner($_POST, $_FILES);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add New Banner | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .required-field::after { content: " *"; color: #dc3545; font-weight: bold; }
        
        /* Modern Glass/Card Styling */
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
        .admin-card-body {
            padding: 24px 20px;
        }

        /* Format Design Selector Cards */
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
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.2s;
        }
        .design-card.active .check-indicator {
            background: #0b53a1;
        }

        /* Diagram Wireframes matching user reference image */
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
        .design-card.active .wireframe-box {
            border-color: #3b82f6;
            background: #ffffff;
        }
        .wf-single-square {
            width: 50px;
            height: 50px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-full-rect {
            width: 100%;
            height: 44px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-two-col {
            display: flex;
            gap: 6px;
            width: 100%;
            justify-content: center;
        }
        .wf-two-col span {
            width: 45px;
            height: 45px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #b91c1c;
            font-weight: 700;
            text-align: center;
        }
        .wf-grid-2x3 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
            width: 70px;
            margin: 0 auto;
        }
        .wf-grid-2x3 span {
            height: 22px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 2px;
            font-size: 6px;
            color: #b91c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wf-slim-strip {
            width: 100%;
            height: 22px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #b91c1c;
            font-weight: 700;
        }
        .wf-grid-3x2 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            width: 90px;
            margin: 0 auto;
        }
        .wf-grid-3x2 span {
            height: 24px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 2px;
            font-size: 6px;
            color: #b91c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .design-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .design-badge {
            font-size: 11px;
            color: #64748b;
        }

        /* Dimension Alert Notice */
        .dimension-info-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        /* Image Upload Box with Drag & Drop Preview */
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
        }
        .upload-dropzone:hover {
            border-color: #0b53a1;
            background: #f0f7ff;
        }
        .upload-dropzone input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .upload-preview-img {
            max-width: 100%;
            max-height: 180px;
            border-radius: 6px;
            object-fit: contain;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: none;
        }

        /* Multi upload slots container */
        .multi-slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .slot-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }

        /* Empty state prompt before design selected */
        .empty-design-prompt {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
        }
        .empty-design-prompt i {
            font-size: 36px;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        /* Device Tab Pills */
        .device-nav-pills {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .device-pill-btn {
            background: transparent;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .device-pill-btn.active {
            color: #0b53a1;
            border-bottom-color: #0b53a1;
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

                <!-- Header Breadcrumb & Actions -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-plus-circle text-primary me-2"></i>Add New Banner
                        </h3>
                        <p class="text-muted small mb-0">Create and configure desktop &amp; mobile responsive banners across pages.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-2 mt-md-0">
                        <a href="view-banner.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> All Banners
                        </a>
                        <a href="add-banner-carousel.php" class="btn btn-outline-primary">
                            <i class="fas fa-layer-group me-1"></i> Add Banner Carousel
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

                <form method="POST" enctype="multipart/form-data" id="addBannerForm">
                    <input type="hidden" name="banner_type" value="single">

                    <!-- Basic Banner Information Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h5><i class="fas fa-heading text-primary"></i> 1. Banner Title &amp; General Information</h5>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label required-field fw-semibold">Banner Title Name</label>
                                    <input type="text" class="form-control form-control-lg" name="title" required
                                           placeholder="e.g. Mega Festival Offer - Up to 50% Off Personalised Gifts">
                                    <small class="text-muted">Descriptive name for admin reference and accessibility alt text.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device Navigation Tabs (Desktop & Mobile) -->
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
                        
                        <!-- 1. Select Desktop Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-primary"></i> Desktop Banner Select Design</h5>
                                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Required to reveal fields</span>
                            </div>
                            <div class="admin-card-body">
                                <p class="text-muted small mb-3">Choose the layout format design as per the diagram. The upload boxes and URL fields will adapt automatically.</p>

                                <div class="design-cards-grid">
                                    <!-- Format 1 -->
                                    <div class="design-card" data-format="desktop_format_1" onclick="selectDesktopDesign('desktop_format_1')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-single-square">Square Offer</div>
                                        </div>
                                        <div class="design-title">1 Format Design</div>
                                        <div class="design-badge">Single Square (1:1)</div>
                                    </div>

                                    <!-- Format 2 -->
                                    <div class="design-card" data-format="desktop_format_2" onclick="selectDesktopDesign('desktop_format_2')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-full-rect">Offer Banner (Wide)</div>
                                        </div>
                                        <div class="design-title">2 Format Design</div>
                                        <div class="design-badge">Full Width Rectangular</div>
                                    </div>

                                    <!-- Format 3 -->
                                    <div class="design-card" data-format="desktop_format_3" onclick="selectDesktopDesign('desktop_format_3')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-two-col">
                                                <span>Square 1</span>
                                                <span>Square 2</span>
                                            </div>
                                        </div>
                                        <div class="design-title">3 Format Design</div>
                                        <div class="design-badge">2 Square Banners Side-by-Side</div>
                                    </div>

                                    <!-- Format 4 -->
                                    <div class="design-card" data-format="desktop_format_4" onclick="selectDesktopDesign('desktop_format_4')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-2x3">
                                                <span>1</span><span>2</span>
                                                <span>3</span><span>4</span>
                                                <span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">4 Format Design</div>
                                        <div class="design-badge">2 Columns × 3 Rows Grid</div>
                                    </div>

                                    <!-- Format 5 -->
                                    <div class="design-card" data-format="desktop_format_5" onclick="selectDesktopDesign('desktop_format_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-slim-strip">Small Offer Banner (Strip)</div>
                                        </div>
                                        <div class="design-title">5 Format Design</div>
                                        <div class="design-badge">Small Slim Strip Banner</div>
                                    </div>

                                    <!-- Format 6 -->
                                    <div class="design-card" data-format="desktop_format_6" onclick="selectDesktopDesign('desktop_format_6')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-3x2">
                                                <span>1</span><span>2</span><span>3</span>
                                                <span>4</span><span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">6 Format Design</div>
                                        <div class="design-badge">3 Columns × 2 Rows Grid</div>
                                    </div>
                                </div>

                                <input type="hidden" name="desktop_design" id="desktopDesignInput" value="">
                            </div>
                        </div>

                        <!-- 2. Conditional Section: Reveals ONLY when Desktop Design is chosen -->
                        <div id="desktopDependentFields" style="display: none;">

                            <!-- Dimension Recommendation Badge -->
                            <div class="dimension-info-pill" id="desktopDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="desktopDimText">Recommended Size: 600×600 px (Square 1:1)</span>
                            </div>

                            <!-- Placement Settings (Home Category, Carousel & Other Pages) -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-primary"></i> Desktop Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- Home page Category Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home pages Category Select</label>
                                            <select name="desktop_home_category_id" class="form-select">
                                                <option value="">— Select Category for Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Category context under which banner is displayed.</small>
                                        </div>

                                        <!-- Home page Carousel Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Desktop Home page Carousel Select</label>
                                            <select name="desktop_home_carousel_id" class="form-select">
                                                <option value="">— Select Placement Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Target section slot on home page.</small>
                                        </div>

                                        <!-- Show on other pages Switch -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Desktop Show on other pages</label>
                                            <select name="desktop_show_other_pages" id="desktopShowOtherSelect" class="form-select" onchange="toggleDesktopOtherPages(this.value)">
                                                <option value="no">No (Home Page Only)</option>
                                                <option value="yes">Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <!-- Other pages Select category -->
                                        <div class="col-md-4 desktop-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Desktop Other pages Select category</label>
                                            <select name="desktop_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Other pages Select Carousel -->
                                        <div class="col-md-4 desktop-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Desktop Other pages Select Carousel</label>
                                            <select name="desktop_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Box(es) & URL(s) matching selected design -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-primary"></i> Desktop Banner Image &amp; URL Configuration</h5>
                                    <span class="badge bg-secondary" id="desktopSlotsBadge">1 Image Slot</span>
                                </div>
                                <div class="admin-card-body">
                                    <!-- Dynamic Upload Containers populated by JS based on selected design -->
                                    <div id="desktopUploadContainer">
                                        <!-- Default Single Image Upload Slot -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label required-field fw-semibold">Desktop Banner Image Uploading</label>
                                                <div class="upload-dropzone" onclick="document.getElementById('desktopImgInput1').click()">
                                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Click to upload desktop image</p>
                                                    <small class="text-muted d-block" id="desktopDropzoneHint">Recommended size: 600×600 px (Square)</small>
                                                    <input type="file" name="desktop_image" id="desktopImgInput1" accept="image/*" onchange="previewUpload(this, 'desktopPreview1')">
                                                    <img id="desktopPreview1" class="upload-preview-img" alt="Desktop Preview">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Desktop URL</label>
                                                <input type="text" class="form-control" name="desktop_url" placeholder="https://printmont.com/category/visiting-cards">
                                                <small class="text-muted">Target landing page URL when user clicks this banner.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop Schedule, Status & Sort Order -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-calendar-alt text-primary"></i> Desktop Banner Status, Order &amp; Schedule</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Status</label>
                                            <select name="desktop_status" class="form-select">
                                                <option value="active">Show (Active)</option>
                                                <option value="inactive">Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop banner Sort Order number</label>
                                            <input type="number" class="form-control" name="desktop_sort_order" value="0" min="0">
                                            <small class="text-muted">Lower number displays first.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop Start Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_start_date">
                                            <small class="text-muted">Leave blank for immediate show.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Desktop End Date Calendar</label>
                                            <input type="date" class="form-control" name="desktop_end_date">
                                            <small class="text-muted">Leave blank for no expiration.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Empty state when no desktop design is selected -->
                        <div id="desktopEmptyPrompt" class="empty-design-prompt">
                            <i class="fas fa-mouse-pointer d-block"></i>
                            <h5 class="fw-bold text-dark">Please Select a Desktop Format Design Above</h5>
                            <p class="mb-0 text-muted">Click on any of the 6 Format Designs above to unlock image uploading, URL setup, and page placements.</p>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- 📱 MOBILE SECTION CONFIGURATION                          -->
                    <!-- ======================================================== -->
                    <div id="mobileSectionPane" style="display: none;">
                        
                        <!-- 1. Select Mobile Design -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h5><i class="fas fa-palette text-success"></i> Mobile Banner Select Design</h5>
                                <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">Required to reveal fields</span>
                            </div>
                            <div class="admin-card-body">
                                <p class="text-muted small mb-3">Select the mobile banner layout format. You can customize mobile independently from desktop.</p>

                                <div class="design-cards-grid">
                                    <!-- Mobile Format 1 -->
                                    <div class="design-card" data-mformat="mobile_format_1" onclick="selectMobileDesign('mobile_format_1')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-single-square">Square 1:1</div>
                                        </div>
                                        <div class="design-title">1 Format Design</div>
                                        <div class="design-badge">Single Square (1:1)</div>
                                    </div>

                                    <!-- Mobile Format 2 -->
                                    <div class="design-card" data-mformat="mobile_format_2" onclick="selectMobileDesign('mobile_format_2')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-full-rect">Landscape Banner</div>
                                        </div>
                                        <div class="design-title">2 Format Design</div>
                                        <div class="design-badge">16:9 Landscape Banner</div>
                                    </div>

                                    <!-- Mobile Format 3 -->
                                    <div class="design-card" data-mformat="mobile_format_3" onclick="selectMobileDesign('mobile_format_3')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-two-col">
                                                <span>Sq 1</span>
                                                <span>Sq 2</span>
                                            </div>
                                        </div>
                                        <div class="design-title">3 Format Design</div>
                                        <div class="design-badge">2 Square Banners Grid</div>
                                    </div>

                                    <!-- Mobile Format 4 -->
                                    <div class="design-card" data-mformat="mobile_format_4" onclick="selectMobileDesign('mobile_format_4')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-2x3">
                                                <span>1</span><span>2</span>
                                                <span>3</span><span>4</span>
                                                <span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">4 Format Design</div>
                                        <div class="design-badge">2 Columns × 3 Rows Grid</div>
                                    </div>

                                    <!-- Mobile Format 5 -->
                                    <div class="design-card" data-mformat="mobile_format_5" onclick="selectMobileDesign('mobile_format_5')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-slim-strip">Small Strip Banner</div>
                                        </div>
                                        <div class="design-title">5 Format Design</div>
                                        <div class="design-badge">Small Height Strip Banner</div>
                                    </div>

                                    <!-- Mobile Format 6 -->
                                    <div class="design-card" data-mformat="mobile_format_6" onclick="selectMobileDesign('mobile_format_6')">
                                        <div class="check-indicator"><i class="fas fa-check"></i></div>
                                        <div class="wireframe-box">
                                            <div class="wf-grid-3x2">
                                                <span>1</span><span>2</span><span>3</span>
                                                <span>4</span><span>5</span><span>6</span>
                                            </div>
                                        </div>
                                        <div class="design-title">6 Format Design</div>
                                        <div class="design-badge">3 Columns × 2 Rows Grid</div>
                                    </div>
                                </div>

                                <input type="hidden" name="mobile_design" id="mobileDesignInput" value="">
                            </div>
                        </div>

                        <!-- 2. Conditional Section: Reveals ONLY when Mobile Design is chosen -->
                        <div id="mobileDependentFields" style="display: none;">

                            <!-- Dimension Recommendation Badge -->
                            <div class="dimension-info-pill" id="mobileDimPill">
                                <i class="fas fa-info-circle fa-lg"></i>
                                <span id="mobileDimText">Recommended Size: 600×600 px (Square 1:1)</span>
                            </div>

                            <!-- Mobile Placement Settings -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-map-marker-alt text-success"></i> Mobile Placement &amp; Category Settings</h5>
                                </div>
                                <div class="admin-card-body">
                                    <div class="row g-3">
                                        <!-- Mobile Home page Category Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page category select</label>
                                            <select name="mobile_home_category_id" class="form-select">
                                                <option value="">— Select Category for Mobile Home Page —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Home page carousel Select -->
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mobile Home page carousel Select</label>
                                            <select name="mobile_home_carousel_id" class="form-select">
                                                <option value="">— Select Mobile Home Carousel / Section —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Show on Other pages Select -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Mobile Show on Other pages Select</label>
                                            <select name="mobile_show_other_pages" id="mobileShowOtherSelect" class="form-select" onchange="toggleMobileOtherPages(this.value)">
                                                <option value="no">No (Home Page Only)</option>
                                                <option value="yes">Yes (Show on Category / Other Pages)</option>
                                            </select>
                                        </div>

                                        <!-- Mobile Other pages Select category -->
                                        <div class="col-md-4 mobile-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Mobile Other pages Select category</label>
                                            <select name="mobile_other_category_id" class="form-select">
                                                <option value="">— Select Other Page Category —</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Mobile Other pages Select Carousel -->
                                        <div class="col-md-4 mobile-other-group" style="display: none;">
                                            <label class="form-label fw-semibold">Mobile Other pages Select Carousel</label>
                                            <select name="mobile_other_carousel_id" class="form-select">
                                                <option value="">— Select Other Page Section / Carousel —</option>
                                                <?php foreach ($carousels as $car): ?>
                                                    <option value="<?php echo $car['id']; ?>">
                                                        [<?php echo strtoupper($car['page']); ?>] <?php echo htmlspecialchars($car['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Upload Box(es) & URL(s) -->
                            <div class="admin-card">
                                <div class="admin-card-header">
                                    <h5><i class="fas fa-cloud-upload-alt text-success"></i> Mobile Banner Image &amp; URL Configuration</h5>
                                    <span class="badge bg-secondary" id="mobileSlotsBadge">1 Image Slot</span>
                                </div>
                                <div class="admin-card-body">
                                    <div id="mobileUploadContainer">
                                        <!-- Default Single Mobile Image Upload -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile Banner image uploading</label>
                                                <div class="upload-dropzone" onclick="document.getElementById('mobileImgInput1').click()">
                                                    <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                                                    <p class="mb-1 text-dark fw-semibold">Click to upload mobile image</p>
                                                    <small class="text-muted d-block" id="mobileDropzoneHint">Recommended size: 600×600 px (Square)</small>
                                                    <input type="file" name="mobile_image" id="mobileImgInput1" accept="image/*" onchange="previewUpload(this, 'mobilePreview1')">
                                                    <img id="mobilePreview1" class="upload-preview-img" alt="Mobile Preview">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile URL</label>
                                                <input type="text" class="form-control" name="mobile_url" placeholder="https://printmont.com/category/mugs">
                                                <small class="text-muted">Target landing page on mobile devices.</small>
                                            </div>
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
                                                <option value="active">Show (Active)</option>
                                                <option value="inactive">Hide (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile banner Short Order number</label>
                                            <input type="number" class="form-control" name="mobile_sort_order" value="0" min="0">
                                            <small class="text-muted">Sort order index for mobile.</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile Start date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_start_date">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Mobile End date Calendar</label>
                                            <input type="date" class="form-control" name="mobile_end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Empty state when no mobile design is selected -->
                        <div id="mobileEmptyPrompt" class="empty-design-prompt">
                            <i class="fas fa-mobile-alt d-block"></i>
                            <h5 class="fw-bold text-dark">Please Select a Mobile Format Design Above</h5>
                            <p class="mb-0 text-muted">Click on any of the Mobile Format Designs above to configure mobile banner images and placements.</p>
                        </div>

                    </div>

                    <!-- Submit & Cancel Bar -->
                    <div class="admin-card">
                        <div class="admin-card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Please verify all selected formats, images, and target links before saving.</span>
                            </div>
                            <div>
                                <a href="view-banner.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                                    <i class="fas fa-save me-1"></i> Save &amp; Publish Banner
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
// Format Specifications metadata (Recommended dimensions & slot counts)
const FORMAT_SPECS = {
    'desktop_format_1': { slots: 1, name: '1 Format Design', dim: '600×600 px (Square 1:1)', label: 'Single Square Offer Banner' },
    'desktop_format_2': { slots: 1, name: '2 Format Design', dim: '1200×450 px / 1920×600 px (Landscape)', label: 'Full Width Offer Banner' },
    'desktop_format_3': { slots: 2, name: '3 Format Design', dim: '600×600 px each (2 Items)', label: '2 Square Banners Side-by-Side' },
    'desktop_format_4': { slots: 6, name: '4 Format Design', dim: '600×600 px each (2 Columns × 3 Rows)', label: '2×3 Square Grid (6 Banners)' },
    'desktop_format_5': { slots: 1, name: '5 Format Design', dim: '1920×220 px / 1200×180 px (Slim Strip)', label: 'Small Offer Banner Strip (Low height, full width)' },
    'desktop_format_6': { slots: 6, name: '6 Format Design', dim: '600×600 px each (3 Columns × 2 Rows)', label: '3×2 Square Grid (6 Banners)' },

    'mobile_format_1': { slots: 1, name: '1 Format Design', dim: '600×600 px (Square 1:1)', label: 'Single Square Mobile Banner' },
    'mobile_format_2': { slots: 1, name: '2 Format Design', dim: '800×450 px (16:9 Landscape)', label: 'Mobile Full Width Banner' },
    'mobile_format_3': { slots: 2, name: '3 Format Design', dim: '600×600 px each (2 Items)', label: '2 Square Mobile Banners' },
    'mobile_format_4': { slots: 6, name: '4 Format Design', dim: '600×600 px each (2 Columns × 3 Rows)', label: '2×3 Mobile Grid (6 Banners)' },
    'mobile_format_5': { slots: 1, name: '5 Format Design', dim: '800×200 px (Slim Ribbon)', label: 'Small Height Mobile Strip Banner' },
    'mobile_format_6': { slots: 6, name: '6 Format Design', dim: '600×600 px each (3 Columns × 2 Rows)', label: '3×2 Mobile Grid (6 Banners)' }
};

// Switch between Desktop & Mobile configuration tabs
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

// 🖥️ Select Desktop Design
function selectDesktopDesign(fmt) {
    document.querySelectorAll('.design-card[data-format]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-format="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('desktopDesignInput').value = fmt;

    // Show dependent fields & hide empty prompt
    document.getElementById('desktopDependentFields').style.display = 'block';
    document.getElementById('desktopEmptyPrompt').style.display = 'none';

    // Update dimensions pill
    const spec = FORMAT_SPECS[fmt];
    if (spec) {
        document.getElementById('desktopDimText').innerHTML = `<strong>${spec.name}:</strong> ${spec.label} &bull; Recommended Dimensions: <strong>${spec.dim}</strong>`;
        renderDesktopSlots(spec);
    }
}

// Render dynamic upload slots based on format design
function renderDesktopSlots(spec) {
    const container = document.getElementById('desktopUploadContainer');
    const badge = document.getElementById('desktopSlotsBadge');
    badge.innerText = `${spec.slots} Image Slot${spec.slots > 1 ? 's' : ''}`;

    if (spec.slots === 1) {
        container.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required-field fw-semibold">Desktop Banner Image Uploading</label>
                    <div class="upload-dropzone" onclick="document.getElementById('desktopImgInput1').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1 text-dark fw-semibold">Click to upload desktop image</p>
                        <small class="text-muted d-block">Recommended size: ${spec.dim}</small>
                        <input type="file" name="desktop_image" id="desktopImgInput1" accept="image/*" onchange="previewUpload(this, 'desktopPreview1')">
                        <img id="desktopPreview1" class="upload-preview-img" alt="Desktop Preview">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Desktop URL</label>
                    <input type="text" class="form-control" name="desktop_url" placeholder="https://printmont.com/category/visiting-cards">
                    <small class="text-muted">Target landing page URL when user clicks this banner.</small>
                </div>
            </div>
        `;
    } else {
        // Multi-image slots (e.g. 2 for Format 3, 6 for Format 4 and 6)
        let html = `<div class="multi-slot-grid">`;
        for (let i = 1; i <= spec.slots; i++) {
            html += `
                <div class="slot-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small">Slot #${i}</strong>
                        <span class="badge bg-light text-secondary border">${spec.dim}</span>
                    </div>
                    <div class="upload-dropzone mb-2" onclick="document.getElementById('dSlotInput_${i}').click()">
                        <i class="fas fa-image text-muted"></i>
                        <small class="d-block text-dark mt-1">Upload Slot ${i}</small>
                        <input type="file" name="desktop_images[]" id="dSlotInput_${i}" accept="image/*" onchange="previewUpload(this, 'dSlotPrev_${i}')">
                        <img id="dSlotPrev_${i}" class="upload-preview-img" alt="Slot ${i} Preview">
                    </div>
                    <label class="form-label small fw-semibold mb-1">Slot ${i} URL</label>
                    <input type="text" class="form-control form-control-sm" name="desktop_urls[]" placeholder="https://printmont.com/...">
                </div>
            `;
        }
        html += `</div>`;
        container.innerHTML = html;
    }
}

// 📱 Select Mobile Design
function selectMobileDesign(fmt) {
    document.querySelectorAll('.design-card[data-mformat]').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.design-card[data-mformat="${fmt}"]`);
    if (target) target.classList.add('active');

    document.getElementById('mobileDesignInput').value = fmt;

    // Show dependent fields & hide empty prompt
    document.getElementById('mobileDependentFields').style.display = 'block';
    document.getElementById('mobileEmptyPrompt').style.display = 'none';

    // Update dimensions pill
    const spec = FORMAT_SPECS[fmt];
    if (spec) {
        document.getElementById('mobileDimText').innerHTML = `<strong>${spec.name}:</strong> ${spec.label} &bull; Recommended Dimensions: <strong>${spec.dim}</strong>`;
        renderMobileSlots(spec);
    }
}

function renderMobileSlots(spec) {
    const container = document.getElementById('mobileUploadContainer');
    const badge = document.getElementById('mobileSlotsBadge');
    badge.innerText = `${spec.slots} Image Slot${spec.slots > 1 ? 's' : ''}`;

    if (spec.slots === 1) {
        container.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mobile Banner image uploading</label>
                    <div class="upload-dropzone" onclick="document.getElementById('mobileImgInput1').click()">
                        <i class="fas fa-mobile-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1 text-dark fw-semibold">Click to upload mobile image</p>
                        <small class="text-muted d-block">Recommended size: ${spec.dim}</small>
                        <input type="file" name="mobile_image" id="mobileImgInput1" accept="image/*" onchange="previewUpload(this, 'mobilePreview1')">
                        <img id="mobilePreview1" class="upload-preview-img" alt="Mobile Preview">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mobile URL</label>
                    <input type="text" class="form-control" name="mobile_url" placeholder="https://printmont.com/category/mugs">
                    <small class="text-muted">Target landing page on mobile devices.</small>
                </div>
            </div>
        `;
    } else {
        let html = `<div class="multi-slot-grid">`;
        for (let i = 1; i <= spec.slots; i++) {
            html += `
                <div class="slot-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark small">Mobile Slot #${i}</strong>
                        <span class="badge bg-light text-secondary border">${spec.dim}</span>
                    </div>
                    <div class="upload-dropzone mb-2" onclick="document.getElementById('mSlotInput_${i}').click()">
                        <i class="fas fa-mobile text-muted"></i>
                        <small class="d-block text-dark mt-1">Upload Slot ${i}</small>
                        <input type="file" name="mobile_images[]" id="mSlotInput_${i}" accept="image/*" onchange="previewUpload(this, 'mSlotPrev_${i}')">
                        <img id="mSlotPrev_${i}" class="upload-preview-img" alt="Slot ${i} Preview">
                    </div>
                    <label class="form-label small fw-semibold mb-1">Slot ${i} URL</label>
                    <input type="text" class="form-control form-control-sm" name="mobile_urls[]" placeholder="https://printmont.com/...">
                </div>
            `;
        }
        html += `</div>`;
        container.innerHTML = html;
    }
}

// Live image preview helper
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

// Toggle other pages dropdowns
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
