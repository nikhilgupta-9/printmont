<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/ErrorPageController.php';
require_once 'controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$errorController = new ErrorPageController($db);
$logoController = new LogoController($db);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'reset_default_image') {
        $res = $errorController->updateSettings(['image_url' => 'uploads/error-pages/404-printmont-error.jpg']);
        if ($res['success']) {
            $_SESSION['success_message'] = 'Default 3D Printmont error illustration restored successfully!';
        } else {
            $_SESSION['error_message'] = $res['message'];
        }
        header("Location: error-background.php");
        exit;
    }

    $formData = [
        'badge'                 => $_POST['badge'] ?? '404 Error',
        'title'                 => $_POST['title'] ?? 'Oops! Page Not Found',
        'message'               => $_POST['message'] ?? 'The page you are looking for has been printed elsewhere, moved, or no longer exists. Let’s get you back on track!',
        'primary_btn_text'      => $_POST['primary_btn_text'] ?? 'Back to Home',
        'primary_btn_url'       => $_POST['primary_btn_url'] ?? '/',
        'secondary_btn_text'    => $_POST['secondary_btn_text'] ?? 'Explore Top Products',
        'secondary_btn_url'     => $_POST['secondary_btn_url'] ?? '/allproducts',
        'show_search_bar'       => isset($_POST['show_search_bar']) ? '1' : '0',
        'show_quick_links'      => isset($_POST['show_quick_links']) ? '1' : '0',
        'contact_support_text'  => $_POST['contact_support_text'] ?? 'Need Help? Reach our 24x7 Customer Desk',
        'support_email'         => $_POST['support_email'] ?? 'support@printmont.com',
        'support_phone'         => $_POST['support_phone'] ?? '+91 9876543210'
    ];

    $file = $_FILES['error_image'] ?? null;
    $res = $errorController->updateSettings($formData, $file);

    if ($res['success']) {
        $_SESSION['success_message'] = $res['message'];
    } else {
        $_SESSION['error_message'] = $res['message'];
    }

    header("Location: error-background.php");
    exit;
}

$settings = $errorController->getSettings();
$logoData = $logoController->getWebsiteLogo();
$logoUrl = '';
if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
    $logoUrl = $logoData['file_path'] . $logoData['file_name'];
}

$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>404 & Error Page Management | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-primary-dark: #083b73;
            --pm-accent: #f8991d;
            --pm-bg-light: #f4f7fb;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f4f9;
        }

        .header-card {
            background: linear-gradient(135deg, #0B53A1 0%, #083b73 100%);
            border-radius: 16px;
            color: #fff;
            padding: 24px 28px;
            box-shadow: 0 10px 25px rgba(11, 83, 161, 0.18);
            margin-bottom: 24px;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            background: #fff;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .custom-card .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 24px;
        }

        .preview-browser-frame {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .browser-top-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .browser-dots {
            display: flex;
            gap: 6px;
        }

        .browser-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }

        .browser-url-bar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 11px;
            color: #64748b;
            flex-grow: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .preview-canvas {
            min-height: 480px;
            background: #ffffff;
            padding: 36px 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .preview-art-img {
            max-width: 260px;
            max-height: 220px;
            object-fit: contain;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .preview-art-img:hover {
            transform: scale(1.03);
        }

        .preview-badge-pill {
            display: inline-block;
            background: #eef2ff;
            color: var(--pm-primary);
            font-weight: 700;
            font-size: 12px;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .preview-title {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .preview-msg {
            font-size: 13px;
            color: #64748b;
            max-width: 440px;
            margin: 0 auto 18px;
            line-height: 1.5;
        }

        .preview-search-box {
            max-width: 380px;
            width: 100%;
            margin: 0 auto 16px;
            display: flex;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 4px 6px 4px 16px;
            align-items: center;
        }

        .preview-search-box input {
            border: none;
            background: transparent;
            font-size: 12px;
            flex-grow: 1;
            outline: none;
            color: #334155;
        }

        .preview-search-box button {
            background: var(--pm-primary);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .preview-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .preview-chip {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 500;
        }

        .preview-btn-primary {
            background: var(--pm-primary);
            color: #fff;
            padding: 9px 22px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .preview-btn-secondary {
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .upload-dropzone:hover {
            border-color: var(--pm-primary);
            background: #f0f7ff;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 9px 14px;
            font-size: 13px;
        }

        .form-control:focus {
            border-color: var(--pm-primary);
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15);
        }

        .btn-pm-primary {
            background: var(--pm-primary);
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 20px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-pm-primary:hover {
            background: var(--pm-primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        .feature-icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #eef4fc;
            color: var(--pm-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Header Banner -->
                    <div class="header-card d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-3 bg-white bg-opacity-20 rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                <i class="fa-solid fa-triangle-exclamation fa-xl"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-white mb-1">404 Error & Background Artwork</h3>
                                <p class="text-white-50 mb-0">Customize the 3D graphic, copy, interactive search bar, and recovery actions for 404 pages.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="http://localhost:5173/page-not-found-preview-test" target="_blank" class="btn btn-light fw-bold shadow-sm d-flex align-items-center gap-2">
                                <i class="fa-solid fa-arrow-up-right-from-square text-primary"></i> Test 404 on Storefront
                            </a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-check fa-lg me-2 text-success"></i>
                                <div><strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-exclamation fa-lg me-2 text-danger"></i>
                                <div><strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Left Column: Settings Form -->
                        <div class="col-lg-6">
                            <form action="error-background.php" method="POST" enctype="multipart/form-data" id="errorSettingsForm">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <!-- Graphic / Illustration Card -->
                                <div class="custom-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="feature-icon-badge">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0">3D Error Artwork</h5>
                                                <small class="text-muted">Featured Printmont customized illustration</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                                            <img src="<?php echo htmlspecialchars($settings['image_url']); ?>?t=<?php echo time(); ?>" 
                                                 id="currentThumb" 
                                                 alt="Current Error Graphic" 
                                                 class="rounded-3 border bg-white" 
                                                 style="width: 80px; height: 80px; object-fit: contain;">
                                            <div class="flex-grow-1">
                                                <h6 class="fw-bold mb-1">Active Error Illustration</h6>
                                                <p class="text-muted small mb-2">High-res 3D vector illustration with Printmont theme.</p>
                                                <div class="d-flex gap-2">
                                                    <a href="<?php echo htmlspecialchars($settings['image_url']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fa-solid fa-eye me-1"></i> View Full
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('fileUploadInput').click();">
                                                        <i class="fa-solid fa-upload me-1"></i> Replace Image
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="upload-dropzone mb-3" onclick="document.getElementById('fileUploadInput').click();">
                                            <i class="fa-solid fa-cloud-arrow-up fa-2x text-muted mb-2"></i>
                                            <h6 class="fw-bold mb-1">Click to Upload New Custom Image</h6>
                                            <p class="text-muted small mb-0">Supported formats: PNG, JPG, WEBP, SVG (Max 5MB)</p>
                                            <input type="file" name="error_image" id="fileUploadInput" class="d-none" accept="image/*" onchange="previewUploadImage(this)">
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small"><i class="fa-solid fa-shield-halved text-success me-1"></i> Automatically synced to Storefront</span>
                                            <button type="button" class="btn btn-sm btn-link text-decoration-none text-danger" onclick="document.getElementById('resetForm').submit();">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Reset to Default Graphic
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content & Text Card -->
                                <div class="custom-card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <div class="feature-icon-badge">
                                                <i class="fa-solid fa-pen-nib"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0">Error Messaging & Headlines</h5>
                                                <small class="text-muted">Set what customers see when landing on a missing URL</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Badge Text</label>
                                                <input type="text" name="badge" id="inputBadge" class="form-control" value="<?php echo htmlspecialchars($settings['badge']); ?>" required oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Main Heading</label>
                                                <input type="text" name="title" id="inputTitle" class="form-control" value="<?php echo htmlspecialchars($settings['title']); ?>" required oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Descriptive Message</label>
                                                <textarea name="message" id="inputMessage" class="form-control" rows="3" required oninput="updateLivePreview()"><?php echo htmlspecialchars($settings['message']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons & Interactive Features -->
                                <div class="custom-card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <div class="feature-icon-badge">
                                                <i class="fa-solid fa-compass"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0">Interactive Elements & Recovery CTAs</h5>
                                                <small class="text-muted">Buttons and search bar to convert 404 visitors back into buyers</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Primary Button Label</label>
                                                <input type="text" name="primary_btn_text" id="inputPrimaryText" class="form-control" value="<?php echo htmlspecialchars($settings['primary_btn_text']); ?>" required oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Primary Button URL</label>
                                                <input type="text" name="primary_btn_url" id="inputPrimaryUrl" class="form-control" value="<?php echo htmlspecialchars($settings['primary_btn_url']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Secondary Button Label</label>
                                                <input type="text" name="secondary_btn_text" id="inputSecondaryText" class="form-control" value="<?php echo htmlspecialchars($settings['secondary_btn_text']); ?>" oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Secondary Button URL</label>
                                                <input type="text" name="secondary_btn_url" id="inputSecondaryUrl" class="form-control" value="<?php echo htmlspecialchars($settings['secondary_btn_url']); ?>">
                                            </div>
                                        </div>

                                        <hr class="my-3 text-muted opacity-25">

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="show_search_bar" id="switchSearchBar" <?php echo ($settings['show_search_bar'] === '1' || $settings['show_search_bar'] === true) ? 'checked' : ''; ?> onchange="updateLivePreview()">
                                                    <label class="form-check-label fw-bold" for="switchSearchBar">
                                                        Enable Live Product Search
                                                        <div class="text-muted small fw-normal">Visitors can search products directly on 404 page</div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="show_quick_links" id="switchQuickLinks" <?php echo ($settings['show_quick_links'] === '1' || $settings['show_quick_links'] === true) ? 'checked' : ''; ?> onchange="updateLivePreview()">
                                                    <label class="form-check-label fw-bold" for="switchQuickLinks">
                                                        Popular Category Pills
                                                        <div class="text-muted small fw-normal">Shows quick links like T-Shirts, Mugs, Gifts</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Support Email</label>
                                                <input type="email" name="support_email" id="inputEmail" class="form-control" value="<?php echo htmlspecialchars($settings['support_email']); ?>" oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Support Phone</label>
                                                <input type="text" name="support_phone" id="inputPhone" class="form-control" value="<?php echo htmlspecialchars($settings['support_phone']); ?>" oninput="updateLivePreview()">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light p-3 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-pm-primary px-4 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk me-2"></i> Save All Settings
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Hidden Form for Reset -->
                            <form action="error-background.php" method="POST" id="resetForm" class="d-none">
                                <input type="hidden" name="action" value="reset_default_image">
                            </form>
                        </div>

                        <!-- Right Column: Live Interactive Mockup Preview -->
                        <div class="col-lg-6">
                            <div class="sticky-top" style="top: 24px;">
                                <div class="custom-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="feature-icon-badge">
                                                <i class="fa-solid fa-desktop"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0">Live Storefront 404 Simulation</h5>
                                                <small class="text-muted">Real-time dynamic preview of customer experience</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1 rounded-pill">
                                            <i class="fa-solid fa-circle-dot me-1"></i> Interactive
                                        </span>
                                    </div>
                                    <div class="card-body p-3">
                                        <!-- Browser Window Mockup Frame -->
                                        <div class="preview-browser-frame">
                                            <div class="browser-top-bar">
                                                <div class="browser-dots">
                                                    <span class="browser-dot dot-red"></span>
                                                    <span class="browser-dot dot-yellow"></span>
                                                    <span class="browser-dot dot-green"></span>
                                                </div>
                                                <div class="browser-url-bar">
                                                    <i class="fa-solid fa-lock text-success" style="font-size: 10px;"></i>
                                                    <span>printmont.com/collections/custom-page-not-found</span>
                                                </div>
                                            </div>

                                            <div class="preview-canvas">
                                                <!-- Brand Mini Header -->
                                                <div class="mb-3 d-flex align-items-center gap-2">
                                                    <?php if (!empty($logoUrl)): ?>
                                                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Printmont" style="height: 28px; object-fit: contain;">
                                                    <?php else: ?>
                                                        <span class="fw-bold fs-5 text-primary">PRINTMONT</span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- 3D Artwork -->
                                                <div class="mb-3">
                                                    <img src="<?php echo htmlspecialchars($settings['image_url']); ?>?t=<?php echo time(); ?>" 
                                                         id="livePreviewImg" 
                                                         alt="404 Illustration" 
                                                         class="preview-art-img shadow-sm">
                                                </div>

                                                <!-- Badge -->
                                                <span class="preview-badge-pill" id="livePreviewBadge">
                                                    <?php echo htmlspecialchars($settings['badge']); ?>
                                                </span>

                                                <!-- Title -->
                                                <h3 class="preview-title" id="livePreviewTitle">
                                                    <?php echo htmlspecialchars($settings['title']); ?>
                                                </h3>

                                                <!-- Message -->
                                                <p class="preview-msg" id="livePreviewMsg">
                                                    <?php echo htmlspecialchars($settings['message']); ?>
                                                </p>

                                                <!-- Search Bar Simulation -->
                                                <div class="preview-search-box" id="livePreviewSearch" style="<?php echo ($settings['show_search_bar'] === '1' || $settings['show_search_bar'] === true) ? '' : 'display: none !important;'; ?>">
                                                    <input type="text" placeholder="Search customized t-shirts, mugs, gifts..." readonly>
                                                    <button type="button"><i class="fa-solid fa-magnifying-glass"></i></button>
                                                </div>

                                                <!-- Quick Links Simulation -->
                                                <div class="preview-chips" id="livePreviewQuickLinks" style="<?php echo ($settings['show_quick_links'] === '1' || $settings['show_quick_links'] === true) ? '' : 'display: none !important;'; ?>">
                                                    <span class="preview-chip"><i class="fa-solid fa-shirt me-1 text-primary"></i> T-Shirts</span>
                                                    <span class="preview-chip"><i class="fa-solid fa-mug-hot me-1 text-warning"></i> Photo Mugs</span>
                                                    <span class="preview-chip"><i class="fa-solid fa-gift me-1 text-danger"></i> Corporate Gifts</span>
                                                    <span class="preview-chip"><i class="fa-solid fa-vest-patches me-1 text-info"></i> Hoodies</span>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                                                    <span class="preview-btn-primary" id="livePreviewPrimaryBtn">
                                                        <i class="fa-solid fa-house"></i>
                                                        <span id="livePreviewPrimaryText"><?php echo htmlspecialchars($settings['primary_btn_text']); ?></span>
                                                    </span>
                                                    <span class="preview-btn-secondary" id="livePreviewSecondaryBtn">
                                                        <i class="fa-solid fa-bag-shopping"></i>
                                                        <span id="livePreviewSecondaryText"><?php echo htmlspecialchars($settings['secondary_btn_text']); ?></span>
                                                    </span>
                                                </div>

                                                <!-- Footer Support -->
                                                <div class="mt-2 text-muted" style="font-size: 11px;">
                                                    <span>Need assistance? </span>
                                                    <a href="mailto:<?php echo htmlspecialchars($settings['support_email']); ?>" class="text-primary fw-bold text-decoration-none" id="livePreviewEmail">
                                                        <?php echo htmlspecialchars($settings['support_email']); ?>
                                                    </a>
                                                    <span> | </span>
                                                    <span class="fw-bold" id="livePreviewPhone"><?php echo htmlspecialchars($settings['support_phone']); ?></span>
                                                </div>
                                            </div>
                                        </div>
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

    <script src="js/app.js"></script>
    <script>
        function updateLivePreview() {
            const badge = document.getElementById('inputBadge').value;
            const title = document.getElementById('inputTitle').value;
            const msg = document.getElementById('inputMessage').value;
            const primaryText = document.getElementById('inputPrimaryText').value;
            const secondaryText = document.getElementById('inputSecondaryText').value;
            const showSearch = document.getElementById('switchSearchBar').checked;
            const showQuick = document.getElementById('switchQuickLinks').checked;
            const email = document.getElementById('inputEmail').value;
            const phone = document.getElementById('inputPhone').value;

            document.getElementById('livePreviewBadge').innerText = badge || '404 Error';
            document.getElementById('livePreviewTitle').innerText = title || 'Oops! Page Not Found';
            document.getElementById('livePreviewMsg').innerText = msg || '';
            document.getElementById('livePreviewPrimaryText').innerText = primaryText || 'Back to Home';
            document.getElementById('livePreviewSecondaryText').innerText = secondaryText || 'Explore Top Products';
            
            document.getElementById('livePreviewSearch').style.display = showSearch ? 'flex' : 'none';
            document.getElementById('livePreviewQuickLinks').style.display = showQuick ? 'flex' : 'none';
            document.getElementById('livePreviewEmail').innerText = email || 'support@printmont.com';
            document.getElementById('livePreviewPhone').innerText = phone || '+91 9876543210';
        }

        function previewUploadImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('livePreviewImg').src = e.target.result;
                    document.getElementById('currentThumb').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
