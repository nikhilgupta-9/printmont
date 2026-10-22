<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/LoaderController.php';
require_once 'controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$loaderController = new LoaderController($db);
$logoController = new LogoController($db);

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'toggle_loader') {
        $status = !empty($_POST['is_enabled']) ? '1' : '0';
        $res = $loaderController->updateSettings(['is_enabled' => $status]);
        $_SESSION[$res['success'] ? 'success_message' : 'error_message'] = $res['success'] ? 'Loader status toggled successfully!' : $res['message'];
        header("Location: loader-settings.php");
        exit;
    }

    if ($action === 'reset_defaults') {
        $defaults = [
            'is_enabled'          => '1',
            'loader_type'         => 'brand_logo_pulse',
            'custom_loader_image' => '',
            'loading_text'        => 'Printing Ideas Into Reality...',
            'show_text'           => '1',
            'bg_color'            => '#ffffff',
            'bg_opacity'          => '0.96',
            'accent_color'        => '#0B53A1',
            'secondary_color'     => '#f8991d',
            'min_duration'        => '500',
            'blur_backdrop'       => '1',
            'preset'              => 'brand_pulse'
        ];
        $res = $loaderController->updateSettings($defaults);
        $_SESSION['success_message'] = 'Default Printmont Brand Loader settings restored!';
        header("Location: loader-settings.php");
        exit;
    }

    $formData = [
        'is_enabled'       => isset($_POST['is_enabled']) ? '1' : '0',
        'loader_type'      => $_POST['loader_type'] ?? 'brand_logo_pulse',
        'loading_text'     => trim($_POST['loading_text'] ?? ''),
        'show_text'        => isset($_POST['show_text']) ? '1' : '0',
        'bg_color'         => trim($_POST['bg_color'] ?? '#ffffff'),
        'accent_color'     => trim($_POST['accent_color'] ?? '#0B53A1'),
        'secondary_color'  => trim($_POST['secondary_color'] ?? '#f8991d'),
        'min_duration'     => (int)($_POST['min_duration'] ?? 500),
        'blur_backdrop'    => isset($_POST['blur_backdrop']) ? '1' : '0',
        'preset'           => $_POST['preset'] ?? 'custom'
    ];

    $file = $_FILES['loader_file'] ?? null;
    $res = $loaderController->updateSettings($formData, $file);

    if ($res['success']) {
        $_SESSION['success_message'] = $res['message'];
    } else {
        $_SESSION['error_message'] = $res['message'];
    }

    header("Location: loader-settings.php");
    exit;
}

$settings = $loaderController->getSettings();
$logoData = $logoController->getWebsiteLogo();
$logoUrl = '';
if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
    $logoUrl = $logoData['file_path'] . $logoData['file_name'];
}

// Favicon (Rule 1)
$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$isLoaderActive = ($settings['is_enabled'] === '1' || $settings['is_enabled'] === 1 || $settings['is_enabled'] === true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Global Loader &amp; Animation Settings | Printmont Admin</title>
    
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

        .animation-preset-card {
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .animation-preset-card:hover {
            border-color: var(--pm-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(11, 83, 161, 0.08);
        }

        .animation-preset-card.active {
            border-color: var(--pm-primary);
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15);
        }

        .sim-stage-window {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .sim-top-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sim-dots {
            display: flex;
            gap: 6px;
        }

        .sim-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }

        .sim-canvas {
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 30px 20px;
            transition: all 0.3s ease;
        }

        /* Animation Styles in Preview */
        .pm-pulse-ring {
            position: absolute;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 2px solid;
            opacity: 0;
            animation: pm-pulse-expand 2.2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        .pm-pulse-ring-2 {
            animation-delay: 0.8s;
        }

        @keyframes pm-pulse-expand {
            0% { transform: scale(0.6); opacity: 0.8; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        @keyframes pm-scale-breath {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }

        .pm-circular-spinner {
            animation: pm-rotate 1.6s linear infinite;
        }
        .pm-spinner-path {
            stroke-linecap: round;
            animation: pm-dash 1.5s ease-in-out infinite;
        }

        @keyframes pm-rotate {
            100% { transform: rotate(360deg); }
        }
        @keyframes pm-dash {
            0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
            50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
            100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
        }

        .pm-dual-ring {
            display: inline-block;
            position: relative;
        }
        .pm-dual-ring div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-radius: 50%;
            animation: pm-dual-ring-spin 1.1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        @keyframes pm-dual-ring-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .pm-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            animation: pm-dots-bounce 1.4s ease-in-out infinite both;
        }
        .pm-dot-1 { animation-delay: -0.32s; }
        .pm-dot-2 { animation-delay: -0.16s; }
        .pm-dot-3 { animation-delay: 0s; }

        @keyframes pm-dots-bounce {
            0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }

        .pm-scan-bar {
            width: 45%;
            height: 100%;
            border-radius: 10px;
            position: absolute;
            animation: pm-scan-anim 1.5s ease-in-out infinite;
        }

        @keyframes pm-scan-anim {
            0% { left: -45%; }
            100% { left: 100%; }
        }

        .btn-pm-primary {
            background: var(--pm-primary);
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 22px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-pm-primary:hover {
            background: var(--pm-primary-dark);
            color: #fff;
        }

        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .upload-dropzone:hover {
            border-color: var(--pm-primary);
            background: #eff6ff;
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
                                <i class="fa-solid fa-spinner fa-xl"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-white mb-1">Global Preloader &amp; Loader Settings</h3>
                                <p class="text-white-50 mb-0">Configure website loading animation, brand pulsing logo, custom GIF, text, and transition duration.</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="http://localhost:5173/?preview=loader" target="_blank" class="btn btn-light fw-bold shadow-sm d-flex align-items-center gap-2">
                                <i class="fa-solid fa-arrow-up-right-from-square text-primary"></i> Test Loader on Storefront
                            </a>
                            <a href="api/loader-api.php" target="_blank" class="btn btn-outline-light fw-bold d-flex align-items-center gap-2">
                                <i class="fa-solid fa-code"></i> API Endpoint
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

                    <!-- Master Switch Status Hero -->
                    <div class="custom-card mb-4 p-4 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: <?php echo $isLoaderActive ? '#eff6ff' : '#f8fafc'; ?>; border-color: <?php echo $isLoaderActive ? '#93c5fd' : '#e2e8f0'; ?>;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: <?php echo $isLoaderActive ? '#dbeafe' : '#f1f5f9'; ?>; color: <?php echo $isLoaderActive ? '#0B53A1' : '#64748b'; ?>;">
                                <i class="fa-solid fa-power-off fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    Global Preloader Status: 
                                    <span class="badge <?php echo $isLoaderActive ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $isLoaderActive ? 'Active &amp; Enabled' : 'Disabled'; ?>
                                    </span>
                                </h5>
                                <p class="text-muted small mb-0">When enabled, visitors will see this branded smooth transition animation while pages load.</p>
                            </div>
                        </div>
                        <form action="loader-settings.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_loader">
                            <input type="hidden" name="is_enabled" value="<?php echo $isLoaderActive ? '0' : '1'; ?>">
                            <button type="submit" class="btn <?php echo $isLoaderActive ? 'btn-danger' : 'btn-success'; ?> fw-bold px-4 shadow-sm">
                                <i class="fa-solid <?php echo $isLoaderActive ? 'fa-stop' : 'fa-play'; ?> me-1"></i>
                                <?php echo $isLoaderActive ? 'Turn Off Loader' : 'Activate Loader'; ?>
                            </button>
                        </form>
                    </div>

                    <div class="row">
                        <!-- Left Column: Settings Form -->
                        <div class="col-lg-6">
                            <form action="loader-settings.php" method="POST" enctype="multipart/form-data" id="loaderConfigForm">
                                <input type="hidden" name="action" value="update_settings">
                                <input type="hidden" name="loader_type" id="inputLoaderType" value="<?php echo htmlspecialchars($settings['loader_type']); ?>">

                                <!-- Animation Presets -->
                                <div class="custom-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Choose Animation Style</h5>
                                        <span class="badge bg-primary-subtle text-primary">6 Preset Styles</span>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <!-- Preset 1 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'brand_logo_pulse') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('brand_logo_pulse')">
                                                    <i class="fa-solid fa-fingerprint fa-2x text-primary mb-2"></i>
                                                    <strong class="small text-dark d-block">Brand Logo Pulse</strong>
                                                    <small class="text-muted" style="font-size: 11px;">Expanding Rings</small>
                                                </div>
                                            </div>

                                            <!-- Preset 2 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'circular_spinner') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('circular_spinner')">
                                                    <i class="fa-solid fa-circle-notch fa-2x text-primary mb-2"></i>
                                                    <strong class="small text-dark d-block">Circular Spinner</strong>
                                                    <small class="text-muted" style="font-size: 11px;">Smooth SVG Dash</small>
                                                </div>
                                            </div>

                                            <!-- Preset 3 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'dual_ring') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('dual_ring')">
                                                    <i class="fa-solid fa-arrows-rotate fa-2x text-warning mb-2"></i>
                                                    <strong class="small text-dark d-block">Dual Ring</strong>
                                                    <small class="text-muted" style="font-size: 11px;">Counter Rotating</small>
                                                </div>
                                            </div>

                                            <!-- Preset 4 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'dots_wave') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('dots_wave')">
                                                    <i class="fa-solid fa-ellipsis fa-2x text-info mb-2"></i>
                                                    <strong class="small text-dark d-block">Dots Wave</strong>
                                                    <small class="text-muted" style="font-size: 11px;">Bouncing Pulses</small>
                                                </div>
                                            </div>

                                            <!-- Preset 5 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'printing_progress_bar') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('printing_progress_bar')">
                                                    <i class="fa-solid fa-print fa-2x text-danger mb-2"></i>
                                                    <strong class="small text-dark d-block">Printing Bar</strong>
                                                    <small class="text-muted" style="font-size: 11px;">Scanner Beam</small>
                                                </div>
                                            </div>

                                            <!-- Preset 6 -->
                                            <div class="col-sm-4 col-6">
                                                <div class="animation-preset-card <?php echo ($settings['loader_type'] === 'custom_gif') ? 'active' : ''; ?>" 
                                                     onclick="selectLoaderPreset('custom_gif')">
                                                    <i class="fa-solid fa-file-image fa-2x text-purple mb-2" style="color: #9333ea;"></i>
                                                    <strong class="small text-dark d-block">Custom Upload</strong>
                                                    <small class="text-muted" style="font-size: 11px;">GIF / SVG File</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Custom GIF Upload Section (shown when custom_gif selected) -->
                                        <div id="customUploadContainer" class="mt-4 p-3 bg-light rounded-3 border" style="<?php echo ($settings['loader_type'] === 'custom_gif') ? '' : 'display:none;'; ?>">
                                            <label class="form-label fw-bold small text-muted">Upload Custom GIF / SVG Preloader</label>
                                            <div class="upload-dropzone" onclick="document.getElementById('loaderFileInput').click();">
                                                <i class="fa-solid fa-cloud-arrow-up fa-2x text-primary mb-2"></i>
                                                <p class="mb-1 fw-bold small text-dark">Click to browse or drop animated GIF/SVG</p>
                                                <small class="text-muted">Allowed: GIF, SVG, PNG, WEBP (Max 4MB)</small>
                                                <input type="file" name="loader_file" id="loaderFileInput" class="d-none" accept=".gif,.svg,.png,.webp,.jpg" onchange="handleCustomFilePreview(this)">
                                            </div>
                                            <?php if (!empty($settings['custom_loader_image'])): ?>
                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                    <img src="<?php echo htmlspecialchars($settings['custom_loader_image']); ?>" alt="Current Custom Loader" style="height: 36px; object-fit: contain;">
                                                    <span class="small text-muted"><?php echo htmlspecialchars($settings['custom_loader_image']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Text & Color Styling -->
                                <div class="custom-card">
                                    <div class="card-header">
                                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-palette text-warning me-2"></i>Colors, Text &amp; Timing</h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold small text-muted">Loading Message</label>
                                                <input type="text" name="loading_text" id="inputLoadingText" class="form-control" value="<?php echo htmlspecialchars($settings['loading_text']); ?>" oninput="updateLiveSimulation()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small text-muted">Minimum Duration</label>
                                                <div class="input-group">
                                                    <input type="number" name="min_duration" id="inputDuration" class="form-control" value="<?php echo (int)$settings['min_duration']; ?>" min="0" max="5000" step="100">
                                                    <span class="input-group-text">ms</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-4">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small text-muted">Background Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="pickerBg" value="<?php echo htmlspecialchars($settings['bg_color']); ?>" oninput="syncColorInput('pickerBg', 'textBg')">
                                                    <input type="text" name="bg_color" id="textBg" class="form-control font-monospace" value="<?php echo htmlspecialchars($settings['bg_color']); ?>" oninput="syncColorPicker('textBg', 'pickerBg')">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small text-muted">Primary Accent Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="pickerAccent" value="<?php echo htmlspecialchars($settings['accent_color']); ?>" oninput="syncColorInput('pickerAccent', 'textAccent')">
                                                    <input type="text" name="accent_color" id="textAccent" class="form-control font-monospace" value="<?php echo htmlspecialchars($settings['accent_color']); ?>" oninput="syncColorPicker('textAccent', 'pickerAccent')">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-bold small text-muted">Secondary Accent Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="pickerSecondary" value="<?php echo htmlspecialchars($settings['secondary_color']); ?>" oninput="syncColorInput('pickerSecondary', 'textSecondary')">
                                                    <input type="text" name="secondary_color" id="textSecondary" class="form-control font-monospace" value="<?php echo htmlspecialchars($settings['secondary_color']); ?>" oninput="syncColorPicker('textSecondary', 'pickerSecondary')">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="show_text" id="switchShowText" <?php echo ($settings['show_text'] === '1' || $settings['show_text'] === true) ? 'checked' : ''; ?> onchange="updateLiveSimulation()">
                                                    <label class="form-check-label fw-bold small" for="switchShowText">
                                                        Show Loading Message
                                                        <div class="text-muted fw-normal" style="font-size: 11px;">Displays custom slogan below animation</div>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="blur_backdrop" id="switchBlur" <?php echo ($settings['blur_backdrop'] === '1' || $settings['blur_backdrop'] === true) ? 'checked' : ''; ?> onchange="updateLiveSimulation()">
                                                    <label class="form-check-label fw-bold small" for="switchBlur">
                                                        Frosted Glassmorphism Blur
                                                        <div class="text-muted fw-normal" style="font-size: 11px;">Soft background blur overlay</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('resetDefaultsForm').submit();">
                                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Defaults
                                        </button>
                                        <button type="submit" class="btn btn-pm-primary px-4 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Loader Configuration
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form action="loader-settings.php" method="POST" id="resetDefaultsForm" class="d-none">
                                <input type="hidden" name="action" value="reset_defaults">
                            </form>
                        </div>

                        <!-- Right Column: Live Interactive Simulation Frame -->
                        <div class="col-lg-6">
                            <div class="sticky-top" style="top: 24px;">
                                <div class="custom-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary me-2">
                                                <i class="fa-solid fa-tv"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-0">Live Preloader Simulation</h5>
                                                <small class="text-muted">Interactive real-time preview of the loading screen</small>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="simulateLoaderTrigger()">
                                            <i class="fa-solid fa-play me-1"></i> Trigger Test Animation
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="sim-stage-window">
                                            <div class="sim-top-bar">
                                                <div class="sim-dots">
                                                    <span class="sim-dot dot-red"></span>
                                                    <span class="sim-dot dot-yellow"></span>
                                                    <span class="sim-dot dot-green"></span>
                                                </div>
                                                <div class="small text-muted ms-2">
                                                    <i class="fa-solid fa-lock text-success me-1" style="font-size: 10px;"></i>
                                                    printmont.com
                                                </div>
                                            </div>

                                            <div class="sim-canvas" id="liveSimCanvas" style="background: <?php echo htmlspecialchars($settings['bg_color']); ?>;">
                                                <!-- Dynamic Animation Container -->
                                                <div id="simAnimationBox" class="mb-3"></div>

                                                <!-- Dynamic Loading Text -->
                                                <div id="simLoadingText" class="fw-bold small text-muted" style="letter-spacing: 0.5px; animation: pm-fade-pulse 1.8s ease-in-out infinite;">
                                                    <?php echo htmlspecialchars($settings['loading_text']); ?>
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
        const activeLogoUrl = <?php echo json_encode(!empty($logoUrl) ? $logoUrl : 'img/photos/logo.png'); ?>;
        let currentCustomImgUrl = <?php echo json_encode($settings['custom_loader_image'] ?? ''); ?>;

        function selectLoaderPreset(preset) {
            document.getElementById('inputLoaderType').value = preset;
            document.querySelectorAll('.animation-preset-card').forEach(c => c.classList.remove('active'));
            event.currentTarget.classList.add('active');

            const customUpload = document.getElementById('customUploadContainer');
            if (preset === 'custom_gif') {
                customUpload.style.display = 'block';
            } else {
                customUpload.style.display = 'none';
            }

            updateLiveSimulation();
        }

        function syncColorInput(pickerId, textId) {
            document.getElementById(textId).value = document.getElementById(pickerId).value;
            updateLiveSimulation();
        }

        function syncColorPicker(textId, pickerId) {
            const val = document.getElementById(textId).value;
            if (/^#([0-9A-F]{3}){1,2}$/i.test(val)) {
                document.getElementById(pickerId).value = val;
            }
            updateLiveSimulation();
        }

        function handleCustomFilePreview(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentCustomImgUrl = e.target.result;
                    updateLiveSimulation();
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updateLiveSimulation() {
            const loaderType = document.getElementById('inputLoaderType').value;
            const bg = document.getElementById('textBg').value;
            const accent = document.getElementById('textAccent').value;
            const secondary = document.getElementById('textSecondary').value;
            const text = document.getElementById('inputLoadingText').value;
            const showText = document.getElementById('switchShowText').checked;
            const blur = document.getElementById('switchBlur').checked;

            const canvas = document.getElementById('liveSimCanvas');
            canvas.style.backgroundColor = bg;
            canvas.style.backdropFilter = blur ? 'blur(6px)' : 'none';

            const animBox = document.getElementById('simAnimationBox');
            let html = '';

            switch(loaderType) {
                case 'brand_logo_pulse':
                    html = `
                        <div style="position:relative; display:inline-flex; align-items:center; justify-content:center;">
                            <div class="pm-pulse-ring" style="border-color:${accent};"></div>
                            <div class="pm-pulse-ring pm-pulse-ring-2" style="border-color:${secondary};"></div>
                            <div style="background:#ffffff; padding:12px 20px; border-radius:18px; box-shadow:0 10px 25px rgba(11,83,161,0.15); position:relative; z-index:2; animation:pm-scale-breath 2s ease-in-out infinite;">
                                <img src="${activeLogoUrl}" alt="Printmont" style="height:36px; max-width:140px; object-fit:contain;">
                            </div>
                        </div>
                    `;
                    break;

                case 'circular_spinner':
                    html = `
                        <div style="width:52px; height:52px;">
                            <svg class="pm-circular-spinner" viewBox="0 0 50 50" style="width:100%; height:100%;">
                                <circle class="pm-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="${accent}" stroke-width="4"></circle>
                            </svg>
                        </div>
                    `;
                    break;

                case 'dual_ring':
                    html = `
                        <div class="pm-dual-ring" style="width:50px; height:50px;">
                            <div style="border-top-color:${accent}; border-bottom-color:${secondary};"></div>
                        </div>
                    `;
                    break;

                case 'dots_wave':
                    html = `
                        <div style="display:flex; gap:8px; align-items:center; justify-content:center;">
                            <span class="pm-dot pm-dot-1" style="background-color:${accent};"></span>
                            <span class="pm-dot pm-dot-2" style="background-color:${secondary};"></span>
                            <span class="pm-dot pm-dot-3" style="background-color:${accent};"></span>
                        </div>
                    `;
                    break;

                case 'printing_progress_bar':
                    html = `
                        <div style="width:200px; text-align:center;">
                            <div style="width:100%; height:6px; background-color:#e2e8f0; border-radius:10px; overflow:hidden; position:relative;">
                                <div class="pm-scan-bar" style="background:linear-gradient(90deg, ${accent}, ${secondary});"></div>
                            </div>
                        </div>
                    `;
                    break;

                case 'custom_gif':
                    if (currentCustomImgUrl) {
                        html = `<img src="${currentCustomImgUrl}" alt="Custom Loader" style="max-height:80px; max-width:150px; object-fit:contain;">`;
                    } else {
                        html = `
                            <div class="pm-dual-ring" style="width:46px; height:46px;">
                                <div style="border-top-color:${accent}; border-bottom-color:${secondary};"></div>
                            </div>
                        `;
                    }
                    break;
            }

            animBox.innerHTML = html;

            const textEl = document.getElementById('simLoadingText');
            if (showText && text) {
                textEl.innerText = text;
                textEl.style.display = 'block';
            } else {
                textEl.style.display = 'none';
            }
        }

        function simulateLoaderTrigger() {
            const canvas = document.getElementById('liveSimCanvas');
            canvas.style.opacity = '0';
            setTimeout(() => {
                canvas.style.opacity = '1';
            }, 300);
        }

        // Initialize simulation on load
        document.addEventListener('DOMContentLoaded', () => {
            updateLiveSimulation();
        });
    </script>
</body>
</html>
