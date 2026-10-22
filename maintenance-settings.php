<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/MaintenanceController.php';
require_once 'controllers/LogoController.php';

// Initialize Controllers
$database = new Database();
$db = $database->getConnection();
$maintenanceController = new MaintenanceController($db);
$logoController = new LogoController($db);

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'quick_toggle') {
        $newStatus = isset($_POST['is_enabled']) && ($_POST['is_enabled'] === '1' || $_POST['is_enabled'] === 'on');
        $result = $maintenanceController->toggleMaintenance($newStatus);
        
        $_SESSION['success_message'] = $newStatus 
            ? '🚨 Maintenance mode is now ACTIVATED! Storefront is currently offline for visitors.' 
            : '🟢 Maintenance mode has been DEACTIVATED! Storefront is now LIVE to the public.';

        header('Location: maintenance-settings.php');
        exit();
    }

    if ($action === 'save_settings') {
        $settingsData = [
            'is_enabled'         => isset($_POST['is_enabled']) ? '1' : '0',
            'title'              => trim($_POST['title'] ?? 'Under Scheduled Maintenance'),
            'message'            => trim($_POST['message'] ?? 'We are currently performing routine maintenance. We will be back shortly!'),
            'estimated_end_time' => trim($_POST['estimated_end_time'] ?? ''),
            'contact_email'      => trim($_POST['contact_email'] ?? ''),
            'contact_phone'      => trim($_POST['contact_phone'] ?? ''),
            'secret_bypass_key'  => trim($_POST['secret_bypass_key'] ?? 'printmont_preview'),
            'allowed_ips'        => trim($_POST['allowed_ips'] ?? ''),
            'show_countdown'     => isset($_POST['show_countdown']) ? '1' : '0'
        ];

        $result = $maintenanceController->updateSettings($settingsData);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Maintenance settings saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save maintenance settings.';
        }

        header('Location: maintenance-settings.php');
        exit();
    }
}

// Get current settings
$settings = $maintenanceController->getSettings();
$isMaintenanceActive = ($settings['is_enabled'] === '1' || $settings['is_enabled'] === 1 || $settings['is_enabled'] === true);

// Get active logo
$websiteLogo = $logoController->getWebsiteLogo();
$logoUrl = 'favicon.png';
if ($websiteLogo && !empty($websiteLogo['file_path']) && !empty($websiteLogo['file_name'])) {
    $logoUrl = $websiteLogo['file_path'] . $websiteLogo['file_name'];
} elseif (file_exists(__DIR__ . '/uploads/logos/')) {
    $anyLogo = $logoController->getActiveLogoByType('mobile_logo');
    if ($anyLogo && $anyLogo->num_rows > 0) {
        $row = $anyLogo->fetch_assoc();
        $logoUrl = $row['file_path'] . $row['file_name'];
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$bypassUrl = 'http://localhost:5173/?bypass=' . urlencode($settings['secret_bypass_key']);

$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Maintenance Settings | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .hero-status-card {
            border-radius: 16px;
            padding: 24px 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .hero-status-card.status-active {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 2px solid #f43f5e;
            box-shadow: 0 10px 25px -5px rgba(244, 63, 94, 0.15);
        }
        .hero-status-card.status-live {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #22c55e;
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.15);
        }
        .pulse-beacon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        .pulse-beacon-red {
            background: #ef4444;
            box-shadow: 0 0 0 rgba(239, 68, 68, 0.5);
            animation: pulse-red 1.6s infinite;
        }
        .pulse-beacon-green {
            background: #22c55e;
            box-shadow: 0 0 0 rgba(34, 197, 94, 0.5);
            animation: pulse-green 1.6s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .switch-toggle-lg {
            width: 68px;
            height: 36px;
            cursor: pointer;
        }
        .switch-toggle-lg:checked {
            background-color: #ef4444;
            border-color: #ef4444;
        }
        /* Live Screen Mockup (Printmont Theme) */
        .preview-canvas {
            background: #f0f2f5;
            border-radius: 16px;
            padding: 24px 16px;
            color: #1e293b;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(11, 83, 161, 0.08);
            border: 1px solid #e2e8f0;
            min-height: 520px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .preview-inner-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 24px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 480px;
        }
        .preview-logo {
            max-height: 44px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 16px;
        }
        .gear-animation {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #e0edff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #0B53A1;
            box-shadow: 0 4px 12px rgba(11, 83, 161, 0.15);
        }
        @keyframes spin-slow {
            100% { transform: rotate(360deg); }
        }
        .preview-countdown-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            display: inline-flex;
            gap: 12px;
            margin: 14px 0;
        }
        .preview-count-item {
            text-align: center;
        }
        .preview-count-item span {
            font-size: 18px;
            font-weight: 800;
            color: #0B53A1;
            display: block;
            line-height: 1;
        }
        .preview-count-item small {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .contact-pill {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .contact-pill:hover {
            background: #e2e8f0;
            color: #0B53A1;
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
                    
                    <!-- Page Header -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1"><strong>Website Maintenance</strong> Mode</h3>
                            <p class="text-muted mb-0">One-click toggle to put the storefront under maintenance. Customize notices, estimated completion time, and preview the live maintenance screen.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                            <a href="http://localhost:5173/?preview=maintenance" target="_blank" class="btn btn-outline-danger me-2">
                                <i class="fas fa-eye me-1"></i> View Maintenance Page
                            </a>
                            <a href="<?php echo htmlspecialchars($bypassUrl); ?>" target="_blank" class="btn btn-outline-primary me-2">
                                <i class="fas fa-external-link-alt me-1"></i> Open Store (Admin Bypass)
                            </a>
                            <a href="general-settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sliders-h me-1"></i> General Settings
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 1-Click Master Toggle Banner -->
                    <div class="card mb-4 border-0">
                        <div class="hero-status-card <?php echo $isMaintenanceActive ? 'status-active' : 'status-live'; ?>">
                            <div class="row align-items-center g-3">
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="pulse-beacon <?php echo $isMaintenanceActive ? 'pulse-beacon-red' : 'pulse-beacon-green'; ?>"></span>
                                        <h4 class="fw-bold mb-0 <?php echo $isMaintenanceActive ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $isMaintenanceActive ? 'MAINTENANCE MODE IS ACTIVE' : 'STOREFRONT IS CURRENTLY LIVE'; ?>
                                        </h4>
                                    </div>
                                    <p class="mb-0 text-muted" style="font-size: 14.5px;">
                                        <?php if ($isMaintenanceActive): ?>
                                            Public storefront visitors are currently redirected to the branded Maintenance Screen. Team members can use the <strong>Secret Bypass Key</strong> to browse normally.
                                        <?php else: ?>
                                            All storefront pages, checkout, and shopping carts are fully operational and open to public customers.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <form method="POST" action="maintenance-settings.php" id="quickToggleForm" class="d-inline-flex align-items-center gap-3">
                                        <input type="hidden" name="action" value="quick_toggle">
                                        <span class="fw-bold small text-dark d-none d-sm-inline">
                                            <?php echo $isMaintenanceActive ? 'Turn OFF Maintenance' : 'Turn ON Maintenance'; ?>
                                        </span>
                                        <div class="form-check form-switch p-0 m-0">
                                            <input class="form-check-input switch-toggle-lg" 
                                                   type="checkbox" 
                                                   name="is_enabled" 
                                                   value="1" 
                                                   id="quickToggleSwitch" 
                                                   <?php echo $isMaintenanceActive ? 'checked' : ''; ?>
                                                   onchange="confirmQuickToggle(this)">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column: Settings Configuration Form -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-tools text-primary me-2"></i> Maintenance Page Configuration
                                    </h5>
                                    <h6 class="card-subtitle text-muted mt-1">Configure what users see during website maintenance.</h6>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="maintenance-settings.php" id="settingsForm">
                                        <input type="hidden" name="action" value="save_settings">

                                        <!-- Maintenance State Toggle -->
                                        <div class="mb-3 p-3 bg-light rounded-3 border">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="isEnabledForm" name="is_enabled" value="1" <?php echo $isMaintenanceActive ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold ms-2 text-dark" for="isEnabledForm">
                                                    Enable Maintenance Mode
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ps-4 ms-2">
                                                When checked, all storefront visitors see the maintenance page instead of the store.
                                            </small>
                                        </div>

                                        <!-- Maintenance Headline -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Maintenance Headline / Title <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="title" 
                                                   id="inputTitle" 
                                                   placeholder="e.g. Under Scheduled Maintenance" 
                                                   value="<?php echo htmlspecialchars($settings['title']); ?>" 
                                                   required 
                                                   oninput="updateLivePreview()">
                                        </div>

                                        <!-- Notice Description -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Customer Notice / Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" 
                                                      name="message" 
                                                      id="inputMessage" 
                                                      rows="3" 
                                                      placeholder="Explain reason for maintenance..." 
                                                      required 
                                                      oninput="updateLivePreview()"><?php echo htmlspecialchars($settings['message']); ?></textarea>
                                        </div>

                                        <!-- Estimated End Time & Countdown -->
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-7">
                                                <label class="form-label fw-semibold">Estimated Completion Time</label>
                                                <input type="datetime-local" 
                                                       class="form-control" 
                                                       name="estimated_end_time" 
                                                       id="inputEndTime" 
                                                       value="<?php echo !empty($settings['estimated_end_time']) ? date('Y-m-d\TH:i', strtotime($settings['estimated_end_time'])) : ''; ?>"
                                                       onchange="updateLivePreview()">
                                                <div class="form-text">Leave blank if completion time is not yet announced.</div>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label fw-semibold">Live Countdown</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" id="showCountdownCheck" name="show_countdown" value="1" <?php echo ($settings['show_countdown'] === '1') ? 'checked' : ''; ?> onchange="updateLivePreview()">
                                                    <label class="form-check-label fw-semibold ms-1" for="showCountdownCheck">Show Timer</label>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <h6 class="fw-bold text-dark mb-3">
                                            <i class="fas fa-headset text-secondary me-2"></i> Support Contact Shortcuts
                                        </h6>

                                        <!-- Contact Email & Phone -->
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Support Email</label>
                                                <input type="email" 
                                                       class="form-control" 
                                                       name="contact_email" 
                                                       id="inputEmail" 
                                                       placeholder="support@printmont.com" 
                                                       value="<?php echo htmlspecialchars($settings['contact_email']); ?>"
                                                       oninput="updateLivePreview()">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Support Phone / WhatsApp</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="contact_phone" 
                                                       id="inputPhone" 
                                                       placeholder="+91 9876543210" 
                                                       value="<?php echo htmlspecialchars($settings['contact_phone']); ?>"
                                                       oninput="updateLivePreview()">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <h6 class="fw-bold text-dark mb-3">
                                            <i class="fas fa-key text-secondary me-2"></i> Developer &amp; Admin Access
                                        </h6>

                                        <!-- Secret Bypass Key -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Secret Bypass Key</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                                <input type="text" 
                                                       class="form-control font-monospace" 
                                                       name="secret_bypass_key" 
                                                       id="inputBypassKey" 
                                                       placeholder="e.g. printmont_preview" 
                                                       value="<?php echo htmlspecialchars($settings['secret_bypass_key']); ?>">
                                                <button class="btn btn-outline-secondary" type="button" onclick="generateRandomBypassKey()">
                                                    <i class="fas fa-sync-alt me-1"></i> Generate
                                                </button>
                                            </div>
                                            <div class="form-text">
                                                Add <code>?bypass=<?php echo htmlspecialchars($settings['secret_bypass_key']); ?></code> to the storefront URL to test the live store while maintenance is active.
                                            </div>
                                        </div>

                                        <!-- IP Whitelist -->
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">IP Whitelist (Optional)</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="allowed_ips" 
                                                   placeholder="e.g. 127.0.0.1, 192.168.1.5" 
                                                   value="<?php echo htmlspecialchars($settings['allowed_ips']); ?>">
                                            <div class="form-text">Comma-separated IP addresses that can bypass maintenance automatically.</div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-save me-1"></i> Save Maintenance Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Live Screen Simulation with Printmont Logo -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">
                                            <i class="fas fa-desktop text-success me-2"></i> Live Storefront Screen Mockup
                                        </h5>
                                        <h6 class="card-subtitle text-muted mt-1">Real-time simulation of customer view with Printmont Logo</h6>
                                    </div>
                                    <span class="badge bg-dark-subtle text-dark border">Storefront Preview</span>
                                </div>
                                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                                    <div class="preview-canvas w-100">
                                        <div class="preview-inner-card">
                                            <!-- Brand Logo -->
                                            <img src="<?php echo htmlspecialchars($logoUrl); ?>?v=<?php echo time(); ?>" 
                                                 alt="Printmont Brand Logo" 
                                                 class="preview-logo" 
                                                 id="previewLogoImg"
                                                 onerror="this.src='favicon.png';">

                                            <!-- Animated Gear / Tool Icon -->
                                            <div class="gear-animation">
                                                <i class="fas fa-cog"></i>
                                            </div>

                                            <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-bold" style="font-size: 11px; border-radius: 20px;">
                                                <i class="fas fa-tools me-1"></i> SCHEDULED UPGRADE IN PROGRESS
                                            </div>

                                            <!-- Headline -->
                                            <h4 class="fw-bold text-dark mb-2" id="previewHeadline">
                                                <?php echo htmlspecialchars($settings['title']); ?>
                                            </h4>

                                            <!-- Message -->
                                            <p class="text-muted small mb-3 px-2" style="max-width: 440px; line-height: 1.5;" id="previewMessage">
                                                <?php echo htmlspecialchars($settings['message']); ?>
                                            </p>

                                            <!-- Countdown Simulation -->
                                            <div id="previewCountdownBox" class="preview-countdown-box <?php echo ($settings['show_countdown'] === '1' && !empty($settings['estimated_end_time'])) ? '' : 'd-none'; ?>">
                                                <div class="preview-count-item">
                                                    <span id="prevHours">02</span>
                                                    <small>Hours</small>
                                                </div>
                                                <div class="preview-count-item">
                                                    <span id="prevMins">45</span>
                                                    <small>Mins</small>
                                                </div>
                                                <div class="preview-count-item">
                                                    <span id="prevSecs">30</span>
                                                    <small>Secs</small>
                                                </div>
                                            </div>

                                            <!-- Support Pills -->
                                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-2" id="previewSupportBar">
                                                <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>" class="contact-pill" id="previewEmailPill">
                                                    <i class="fas fa-envelope text-primary"></i> <span id="prevEmailText"><?php echo htmlspecialchars($settings['contact_email'] ?: 'support@printmont.com'); ?></span>
                                                </a>
                                                <a href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>" class="contact-pill" id="previewPhonePill">
                                                    <i class="fab fa-whatsapp text-success"></i> <span id="prevPhoneText"><?php echo htmlspecialchars($settings['contact_phone'] ?: '+91 9876543210'); ?></span>
                                                </a>
                                                <a href="#" class="contact-pill" onclick="alert('Storefront reload simulation'); return false;">
                                                    <i class="fas fa-redo-alt text-primary"></i> Check Status
                                                </a>
                                            </div>

                                            <div class="mt-3 pt-2 border-top text-muted" style="font-size: 11px;">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <span><i class="fas fa-shield-alt text-primary me-1"></i> 100% Secure</span>
                                                    <span><i class="fas fa-box text-primary me-1"></i> Orders Safe</span>
                                                    <span><i class="fas fa-headset text-primary me-1"></i> 24x7 Help</span>
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
        function confirmQuickToggle(switchEl) {
            const isChecked = switchEl.checked;
            const actionText = isChecked 
                ? 'Are you sure you want to ACTIVATE Maintenance Mode?\n\nThe public website will immediately be blocked for general visitors.' 
                : 'Are you sure you want to DEACTIVATE Maintenance Mode?\n\nThe public storefront will go LIVE immediately.';
            
            if (confirm(actionText)) {
                document.getElementById('quickToggleForm').submit();
            } else {
                switchEl.checked = !isChecked;
            }
        }

        function updateLivePreview() {
            const title = document.getElementById('inputTitle').value || 'Under Scheduled Maintenance';
            const message = document.getElementById('inputMessage').value || 'We are currently performing routine maintenance.';
            const email = document.getElementById('inputEmail').value || 'support@printmont.com';
            const phone = document.getElementById('inputPhone').value || '+91 9876543210';
            const showCountdown = document.getElementById('showCountdownCheck').checked;
            const endTime = document.getElementById('inputEndTime').value;

            document.getElementById('previewHeadline').textContent = title;
            document.getElementById('previewMessage').textContent = message;
            document.getElementById('prevEmailText').textContent = email;
            document.getElementById('prevPhoneText').textContent = phone;
            document.getElementById('previewEmailPill').href = 'mailto:' + email;
            document.getElementById('previewPhonePill').href = 'tel:' + phone;

            const countBox = document.getElementById('previewCountdownBox');
            if (showCountdown && endTime) {
                countBox.classList.remove('d-none');
            } else {
                countBox.classList.add('d-none');
            }
        }

        function generateRandomBypassKey() {
            const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
            let key = 'pm_';
            for (let i = 0; i < 12; i++) {
                key += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('inputBypassKey').value = key;
        }

        // Live seconds ticker in preview
        setInterval(() => {
            const secsEl = document.getElementById('prevSecs');
            if (secsEl) {
                let s = parseInt(secsEl.textContent, 10) - 1;
                if (s < 0) s = 59;
                secsEl.textContent = s < 10 ? '0' + s : s;
            }
        }, 1000);
    </script>
</body>
</html>
