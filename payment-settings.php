<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/PaymentSettingsController.php';
require_once 'controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$paymentController = new PaymentSettingsController($db);
$logoController = new LogoController($db);

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'toggle_mode') {
        $currentMode = $_POST['current_mode'] ?? 'test';
        $newMode = ($currentMode === 'live') ? 'test' : 'live';
        $res = $paymentController->updateSettings(['razorpay_mode' => $newMode]);
        $_SESSION['success_message'] = "Switched to " . strtoupper($newMode) . " mode successfully!";
        header("Location: payment-settings.php");
        exit;
    }

    if ($action === 'reset_defaults') {
        $defaults = [
            'razorpay_enabled'               => '1',
            'razorpay_mode'                  => 'test',
            'razorpay_test_key_id'           => 'rzp_test_1DP5mmOlF5G5ag',
            'razorpay_test_secret'           => 'rzp_test_secret_sample_key',
            'razorpay_live_key_id'           => '',
            'razorpay_live_secret'           => '',
            'razorpay_webhook_secret'        => '',
            'cod_enabled'                    => '1',
            'cod_advance_type'               => 'percentage',
            'cod_advance_percent'            => '30',
            'cod_advance_fixed_amount'       => '150',
            'cod_extra_fee'                  => '0',
            'cod_free_threshold'             => '999',
            'cod_min_amount'                 => '0',
            'cod_max_amount'                 => '50000',
            'cod_customized_advance_percent' => '50',
            'cod_instructions'               => 'Pay remaining balance upon delivery in cash or via delivery partner UPI QR.',
            'cod_restricted_pincodes'        => '',
            'upi_enabled'                    => '1',
            'cards_enabled'                  => '1',
            'netbanking_enabled'             => '1',
            'emi_enabled'                    => '1',
            'brand_name'                     => 'Printmont',
            'theme_color'                    => '#0B53A1'
        ];
        $res = $paymentController->updateSettings($defaults);
        $_SESSION['success_message'] = 'Payment gateway default settings restored successfully!';
        header("Location: payment-settings.php");
        exit;
    }

    // Save full payment settings
    $formData = [
        'razorpay_enabled'        => isset($_POST['razorpay_enabled']) ? '1' : '0',
        'razorpay_mode'           => in_array($_POST['razorpay_mode'] ?? '', ['test', 'live']) ? $_POST['razorpay_mode'] : 'test',
        'razorpay_test_key_id'    => trim($_POST['razorpay_test_key_id'] ?? ''),
        'razorpay_test_secret'    => trim($_POST['razorpay_test_secret'] ?? ''),
        'razorpay_live_key_id'    => trim($_POST['razorpay_live_key_id'] ?? ''),
        'razorpay_live_secret'    => trim($_POST['razorpay_live_secret'] ?? ''),
        'razorpay_webhook_secret' => trim($_POST['razorpay_webhook_secret'] ?? ''),
        'cod_enabled'             => isset($_POST['cod_enabled']) ? '1' : '0',
        'cod_advance_percent'     => max(0, min(100, (int)($_POST['cod_advance_percent'] ?? 30))),
        'cod_min_amount'          => max(0, (float)($_POST['cod_min_amount'] ?? 0)),
        'cod_max_amount'          => max(0, (float)($_POST['cod_max_amount'] ?? 50000)),
        'upi_enabled'             => isset($_POST['upi_enabled']) ? '1' : '0',
        'cards_enabled'           => isset($_POST['cards_enabled']) ? '1' : '0',
        'netbanking_enabled'      => isset($_POST['netbanking_enabled']) ? '1' : '0',
        'emi_enabled'             => isset($_POST['emi_enabled']) ? '1' : '0',
        'brand_name'              => trim($_POST['brand_name'] ?? 'Printmont'),
        'theme_color'             => trim($_POST['theme_color'] ?? '#0B53A1')
    ];

    $res = $paymentController->updateSettings($formData);
    $_SESSION[$res['success'] ? 'success_message' : 'error_message'] = $res['message'];
    header("Location: payment-settings.php");
    exit;
}

// Fetch current full settings
$settings = $paymentController->getSettings(true);
$logoData = $logoController->getWebsiteLogo();
$logoUrl = '';
if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
    $logoUrl = $logoData['file_path'] . $logoData['file_name'];
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$isLive = ($settings['razorpay_mode'] === 'live');
$isRzpActive = ($settings['razorpay_enabled'] === '1' || $settings['razorpay_enabled'] === 1);
$isCodActive = ($settings['cod_enabled'] === '1' || $settings['cod_enabled'] === 1);

// Count active payment channels
$activeChannelsCount = 0;
if (!empty($settings['upi_enabled'])) $activeChannelsCount++;
if (!empty($settings['cards_enabled'])) $activeChannelsCount++;
if (!empty($settings['netbanking_enabled'])) $activeChannelsCount++;
if (!empty($settings['emi_enabled'])) $activeChannelsCount++;
if ($isCodActive) $activeChannelsCount++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Manage Razorpay API credentials, Cash on Delivery rules, and payment gateways for Printmont">
    <title>Payment Gateway Settings | Printmont Admin</title>

    <!-- Rule 1: Dynamic Brand Favicon Synchronization -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-accent: #f8991d;
            --pm-success: #10b981;
            --pm-danger: #ef4444;
            --pm-warning: #f59e0b;
            --pm-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        /* Custom Prominent Alerts */
        .pm-alert {
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }

        .pm-alert-success {
            background-color: #ecfdf5;
            border: 1.5px solid #a7f3d0;
            color: #065f46;
        }

        .pm-alert-danger {
            background-color: #fef2f2;
            border: 1.5px solid #fecaca;
            color: #991b1b;
        }

        .pm-alert .btn-close {
            filter: grayscale(1) opacity(0.7);
            cursor: pointer;
        }

        .payment-header-gradient {
            background: linear-gradient(135deg, #0B53A1 0%, #1e40af 50%, #0369a1 100%);
            border-radius: 16px;
            color: #ffffff;
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(11, 83, 161, 0.25);
        }

        .payment-header-gradient::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 20px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .kpi-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .mode-badge-live {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .mode-badge-test {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fcd34d;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .custom-card-header {
            padding: 18px 22px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-card-body {
            padding: 22px;
        }

        .form-switch .form-check-input {
            width: 48px;
            height: 24px;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .form-switch .form-check-input:checked {
            background-color: #0B53A1;
            border-color: #0B53A1;
        }

        .channel-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            transition: all 0.2s ease;
            background: #ffffff;
            cursor: pointer;
            user-select: none;
        }

        .channel-card:hover {
            border-color: #cbd5e1;
        }

        .channel-card.active {
            border-color: #0B53A1;
            background: #f8faff;
        }

        .input-group-password {
            position: relative;
        }

        .btn-toggle-secret {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        /* Razorpay Mockup Simulation */
        .rzp-preview-box {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
        }

        .rzp-header-mock {
            padding: 20px;
            color: #ffffff;
            position: relative;
            background: <?= htmlspecialchars($settings['theme_color'] ?? '#0B53A1') ?>;
            transition: background 0.3s ease;
        }

        .rzp-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }

        .rzp-body-mock {
            padding: 16px;
            background: #fcfcfc;
        }

        .rzp-option-item {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            transition: all 0.2s ease;
        }

        .rzp-option-item.selected {
            border-color: #0B53A1;
            background: #eff6ff;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }

        /* Toast Container */
        #toastContainer {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .pm-toast {
            pointer-events: auto;
            min-width: 280px;
            max-width: 360px;
            padding: 14px 18px;
            border-radius: 10px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: toastIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .pm-toast.success { background: #065f46; border-left: 4px solid #10b981; }
        .pm-toast.error { background: #7f1d1d; border-left: 4px solid #ef4444; }
        .pm-toast.info { background: #0B53A1; border-left: 4px solid #38bdf8; }

        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div id="toastContainer"></div>

    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <!-- Top Page Title & Actions -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-credit-card text-primary me-2"></i>Payment Gateway Settings</h3>
                            <p class="text-muted mb-0 small">Configure Razorpay API keys, Live/Test mode, COD advance rules, and enabled storefront payment channels.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <form id="modeToggleForm" method="POST" action="payment-settings.php" class="d-inline">
                                <input type="hidden" name="action" value="toggle_mode">
                                <input type="hidden" name="current_mode" value="<?= htmlspecialchars($settings['razorpay_mode']) ?>">
                                <button type="submit" class="btn <?= $isLive ? 'btn-outline-danger' : 'btn-outline-warning' ?> btn-sm me-2 shadow-sm fw-semibold">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Switch to <?= $isLive ? 'Sandbox (Test)' : 'Production (Live)' ?>
                                </button>
                            </form>
                            <a href="http://localhost:5173/cart" target="_blank" class="btn btn-outline-primary btn-sm shadow-sm fw-semibold">
                                <i class="fa-solid fa-cart-shopping me-1"></i> Test Storefront Cart
                            </a>
                        </div>
                    </div>

                    <!-- Flash Message / Prominent Alerts -->
                    <?php if ($success_message): ?>
                        <div class="pm-alert pm-alert-success" id="serverAlert" role="alert">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-circle-check fs-4 text-success"></i>
                                <div>
                                    <div class="fw-bold">Success!</div>
                                    <div class="small"><?= htmlspecialchars($success_message); ?></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" onclick="this.parentElement.remove();"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="pm-alert pm-alert-danger" id="serverAlert" role="alert">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-triangle-exclamation fs-4 text-danger"></i>
                                <div>
                                    <div class="fw-bold">Attention Required</div>
                                    <div class="small"><?= htmlspecialchars($error_message); ?></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" onclick="this.parentElement.remove();"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Header Banner -->
                    <div class="payment-header-gradient mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-white text-primary fw-bold px-2 py-1" style="font-size: 11px;">RAZORPAY INTEGRATED</span>
                                    <?php if ($isLive): ?>
                                        <span class="mode-badge-live"><span class="pulse-dot"></span> LIVE PRODUCTION</span>
                                    <?php else: ?>
                                        <span class="mode-badge-test"><span class="pulse-dot"></span> TEST SANDBOX MODE</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="fw-bold text-white mb-1">Razorpay Payment & COD Gateway Hub</h4>
                                <p class="text-white-50 mb-0 small">
                                    Active Key: <code class="bg-dark text-white px-2 py-0.5 rounded font-monospace"><?= htmlspecialchars($settings['active_key_id'] ?: 'Not configured') ?></code>
                                    • Active Mode: <strong><?= strtoupper($settings['razorpay_mode']) ?></strong>
                                    • COD Advance: <strong><?= htmlspecialchars($settings['cod_advance_percent']) ?>%</strong>
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                                <a href="https://dashboard.razorpay.com/" target="_blank" class="btn btn-light text-primary fw-bold px-3 py-2 shadow-sm">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Razorpay Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Gateway Status</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $isRzpActive ? 'text-success' : 'text-danger' ?>" id="kpi_rzp_status">
                                            <?= $isRzpActive ? 'Active' : 'Disabled' ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Online Checkout API</div>
                                    </div>
                                    <div class="kpi-icon-box <?= $isRzpActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" id="kpi_rzp_icon">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Active Environment</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $isLive ? 'text-danger' : 'text-warning' ?>">
                                            <?= $isLive ? 'Live Production' : 'Sandbox (Test)' ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">
                                            <?= $isLive ? 'Real payments enabled' : 'Test simulation keys' ?>
                                        </div>
                                    </div>
                                    <div class="kpi-icon-box <?= $isLive ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' ?>">
                                        <i class="fa-solid fa-bolt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Cash on Delivery (COD)</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $isCodActive ? 'text-primary' : 'text-secondary' ?>" id="kpi_cod_status">
                                            <?= $isCodActive ? $settings['cod_advance_percent'] . '% Advance' : 'Disabled' ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">
                                            <?= $isCodActive ? 'Partial advance rule active' : 'Offline' ?>
                                        </div>
                                    </div>
                                    <div class="kpi-icon-box bg-primary-subtle text-primary">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Active Channels</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-dark" id="kpi_active_channels"><?= $activeChannelsCount ?> / 5 Active</h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">UPI, Cards, Netbanking, EMI, COD</div>
                                    </div>
                                    <div class="kpi-icon-box bg-info-subtle text-info">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Settings Form -->
                    <form id="paymentSettingsForm" method="POST" action="payment-settings.php">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row g-4">
                            <!-- Left Settings Column (8 Cols) -->
                            <div class="col-xl-8">
                                
                                <!-- Card 1: Razorpay API Credentials -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-brands fa-razorpay text-primary me-2"></i>Razorpay Gateway Configuration</h5>
                                            <span class="text-muted small">Manage API Key ID, Secret, and Environment mode</span>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="razorpay_enabled" name="razorpay_enabled" value="1" <?= $isRzpActive ? 'checked' : '' ?> onchange="onToggleChange('razorpay_enabled', this.checked)">
                                            <label class="form-check-label fw-semibold small text-dark" for="razorpay_enabled">Enable Gateway</label>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        
                                        <!-- Mode Switcher Radio -->
                                        <div class="p-3 rounded-3 mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                            <label class="form-label fw-bold text-dark mb-2">Gateway Mode / Environment</label>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check p-3 rounded-3 border bg-white cursor-pointer h-100" onclick="document.getElementById('mode_test').checked = true; updatePreview();">
                                                        <input class="form-check-input ms-0 me-2" type="radio" name="razorpay_mode" id="mode_test" value="test" <?= !$isLive ? 'checked' : '' ?> onchange="updatePreview()">
                                                        <label class="form-check-label fw-bold text-dark cursor-pointer d-block" for="mode_test">
                                                            <i class="fa-solid fa-flask text-warning me-1"></i> Sandbox / Test Mode
                                                            <span class="d-block text-muted fw-normal small mt-1">Simulate payments safely using Razorpay test cards, test UPI VPAs, and test netbanking without charging real money.</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check p-3 rounded-3 border bg-white cursor-pointer h-100" onclick="document.getElementById('mode_live').checked = true; updatePreview();">
                                                        <input class="form-check-input ms-0 me-2" type="radio" name="razorpay_mode" id="mode_live" value="live" <?= $isLive ? 'checked' : '' ?> onchange="updatePreview()">
                                                        <label class="form-check-label fw-bold text-dark cursor-pointer d-block" for="mode_live">
                                                            <i class="fa-solid fa-circle-check text-danger me-1"></i> Production / Live Mode
                                                            <span class="d-block text-muted fw-normal small mt-1">Accept real payments from customers directly into your verified Razorpay merchant bank account.</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Test Credentials Section -->
                                        <div class="border rounded-3 p-3 mb-4 <?= !$isLive ? 'border-warning bg-warning-subtle-light' : 'bg-white' ?>">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h6 class="fw-bold text-dark mb-0">
                                                    <i class="fa-solid fa-vial text-warning me-1"></i> Test Credentials (Sandbox)
                                                </h6>
                                                <span class="badge bg-warning text-dark fw-bold">Test Mode Keys</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold text-secondary">Test Key ID <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control font-monospace" name="razorpay_test_key_id" id="razorpay_test_key_id" value="<?= htmlspecialchars($settings['razorpay_test_key_id']) ?>" placeholder="rzp_test_..." required>
                                                    <div class="form-text small">Public test key sent to client checkout.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold text-secondary">Test Key Secret <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control font-monospace" name="razorpay_test_secret" id="razorpay_test_secret" value="<?= htmlspecialchars($settings['razorpay_test_secret']) ?>" placeholder="Enter test key secret">
                                                        <button class="btn btn-outline-secondary btn-toggle-secret" type="button" onclick="togglePasswordVisibility('razorpay_test_secret', this)">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text small">Used for server-side HMAC signature verification.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Live Credentials Section -->
                                        <div class="border rounded-3 p-3 mb-4 <?= $isLive ? 'border-danger bg-danger-subtle-light' : 'bg-white' ?>">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h6 class="fw-bold text-dark mb-0">
                                                    <i class="fa-solid fa-bolt text-danger me-1"></i> Live Production Credentials
                                                </h6>
                                                <span class="badge bg-danger text-white fw-bold">Production Keys</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold text-secondary">Live Key ID</label>
                                                    <input type="text" class="form-control font-monospace" name="razorpay_live_key_id" id="razorpay_live_key_id" value="<?= htmlspecialchars($settings['razorpay_live_key_id']) ?>" placeholder="rzp_live_...">
                                                    <div class="form-text small">Public live key for real payments.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold text-secondary">Live Key Secret</label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control font-monospace" name="razorpay_live_secret" id="razorpay_live_secret" value="<?= htmlspecialchars($settings['razorpay_live_secret']) ?>" placeholder="Enter live key secret">
                                                        <button class="btn btn-outline-secondary btn-toggle-secret" type="button" onclick="togglePasswordVisibility('razorpay_live_secret', this)">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text small">Kept encrypted on server for verification.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Webhook Secret -->
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold text-secondary">Razorpay Webhook Secret (Optional)</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control font-monospace" name="razorpay_webhook_secret" id="razorpay_webhook_secret" value="<?= htmlspecialchars($settings['razorpay_webhook_secret']) ?>" placeholder="Enter webhook secret">
                                                <button class="btn btn-outline-secondary btn-toggle-secret" type="button" onclick="togglePasswordVisibility('razorpay_webhook_secret', this)">
                                                            <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text small">Webhook endpoint for payment events: <code>http://localhost/printmont/printmont-backend/api/payment-webhook.php</code></div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Card 2: Cash on Delivery (COD) Rules -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Cash on Delivery (COD) Rules</h5>
                                            <span class="text-muted small">Control partial advance payments and order limitations for COD</span>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="cod_enabled" name="cod_enabled" value="1" <?= $isCodActive ? 'checked' : '' ?> onchange="onToggleChange('cod_enabled', this.checked)">
                                            <label class="form-check-label fw-semibold small text-dark" for="cod_enabled">Enable COD</label>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark d-flex justify-content-between">
                                                    <span>Advance Payment Requirement (%)</span>
                                                    <span class="badge bg-primary fs-6 fw-bold" id="advance_percent_badge"><?= htmlspecialchars($settings['cod_advance_percent']) ?>%</span>
                                                </label>
                                                <input type="range" class="form-range" min="0" max="100" step="5" id="cod_advance_percent" name="cod_advance_percent" value="<?= htmlspecialchars($settings['cod_advance_percent']) ?>" oninput="updateAdvanceDisplay(this.value)">
                                                <div class="d-flex justify-content-between text-muted small mt-1">
                                                    <span>0% (Full COD)</span>
                                                    <span>30% (Recommended)</span>
                                                    <span>100% (Prepaid Only)</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-3 bg-light rounded-3 border">
                                                    <div class="fw-bold text-dark small mb-1"><i class="fa-solid fa-calculator text-primary me-1"></i> Live Calculation Example</div>
                                                    <p class="text-muted small mb-0" id="cod_calc_text">
                                                        On a ₹1,000 order: customer pays <strong>₹<?= (1000 * ((int)$settings['cod_advance_percent'] / 100)) ?></strong> now online, and the balance <strong>₹<?= 1000 - (1000 * ((int)$settings['cod_advance_percent'] / 100)) ?></strong> in cash upon delivery.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Minimum Order Value for COD (₹)</label>
                                                <input type="number" class="form-control" name="cod_min_amount" value="<?= htmlspecialchars($settings['cod_min_amount']) ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Maximum Order Value for COD (₹)</label>
                                                <input type="number" class="form-control" name="cod_max_amount" value="<?= htmlspecialchars($settings['cod_max_amount']) ?>" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Payment Channels & Methods -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Active Payment Channels</h5>
                                            <span class="text-muted small">Enable or disable specific payment instruments shown at checkout</span>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="row g-3">
                                            
                                            <!-- UPI -->
                                            <div class="col-md-6">
                                                <div class="channel-card <?= !empty($settings['upi_enabled']) ? 'active' : '' ?>" id="card_upi" onclick="toggleCardSwitch('upi_enabled', event)">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="kpi-icon-box bg-primary-subtle text-primary">
                                                                <i class="fa-solid fa-mobile-screen-button"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark">UPI Payments</div>
                                                                <div class="text-muted small">GPay, PhonePe, Paytm, BHIM</div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" name="upi_enabled" id="upi_enabled" value="1" <?= !empty($settings['upi_enabled']) ? 'checked' : '' ?> onchange="onToggleChange('upi_enabled', this.checked)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Cards -->
                                            <div class="col-md-6">
                                                <div class="channel-card <?= !empty($settings['cards_enabled']) ? 'active' : '' ?>" id="card_cards" onclick="toggleCardSwitch('cards_enabled', event)">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="kpi-icon-box bg-success-subtle text-success">
                                                                <i class="fa-regular fa-credit-card"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark">Credit & Debit Cards</div>
                                                                <div class="text-muted small">Visa, Mastercard, RuPay, Maestro</div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" name="cards_enabled" id="cards_enabled" value="1" <?= !empty($settings['cards_enabled']) ? 'checked' : '' ?> onchange="onToggleChange('cards_enabled', this.checked)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Net Banking -->
                                            <div class="col-md-6">
                                                <div class="channel-card <?= !empty($settings['netbanking_enabled']) ? 'active' : '' ?>" id="card_netbanking" onclick="toggleCardSwitch('netbanking_enabled', event)">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="kpi-icon-box bg-info-subtle text-info">
                                                                <i class="fa-solid fa-building-columns"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark">Net Banking</div>
                                                                <div class="text-muted small">50+ Major Indian Banks</div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" name="netbanking_enabled" id="netbanking_enabled" value="1" <?= !empty($settings['netbanking_enabled']) ? 'checked' : '' ?> onchange="onToggleChange('netbanking_enabled', this.checked)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- EMI -->
                                            <div class="col-md-6">
                                                <div class="channel-card <?= !empty($settings['emi_enabled']) ? 'active' : '' ?>" id="card_emi" onclick="toggleCardSwitch('emi_enabled', event)">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="kpi-icon-box bg-warning-subtle text-warning">
                                                                <i class="fa-solid fa-calendar-days"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark">EMI Options</div>
                                                                <div class="text-muted small">Credit card & Cardless EMIs</div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" name="emi_enabled" id="emi_enabled" value="1" <?= !empty($settings['emi_enabled']) ? 'checked' : '' ?> onchange="onToggleChange('emi_enabled', this.checked)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Card 4: Branding & Modal UI Theme -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-palette text-primary me-2"></i>Checkout UI & Branding</h5>
                                            <span class="text-muted small">Customize gateway title and theme colors displayed on checkout modals</span>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Gateway Display Brand Name</label>
                                                <input type="text" class="form-control" name="brand_name" id="brand_name" value="<?= htmlspecialchars($settings['brand_name']) ?>" oninput="updatePreview()">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Primary Theme Color</label>
                                                <div class="d-flex gap-2">
                                                    <input type="color" class="form-control form-control-color" id="theme_color_picker" value="<?= htmlspecialchars($settings['theme_color']) ?>" onchange="syncColorInput(this.value)" style="width: 48px; height: 38px;">
                                                    <input type="text" class="form-control font-monospace" name="theme_color" id="theme_color" value="<?= htmlspecialchars($settings['theme_color']) ?>" oninput="syncColorPicker(this.value)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded-3 shadow-sm mb-4">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="confirmResetDefaults()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Reset to Defaults
                                    </button>

                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Payment Settings
                                    </button>
                                </div>

                            </div>

                            <!-- Right Column: Live Checkout Preview (4 Cols) -->
                            <div class="col-xl-4">
                                <div class="sticky-top" style="top: 20px; z-index: 10;">
                                    <div class="custom-card">
                                        <div class="custom-card-header bg-light">
                                            <div class="fw-bold text-dark"><i class="fa-solid fa-eye text-primary me-2"></i>Live Storefront Preview</div>
                                            <span class="badge bg-primary-subtle text-primary fw-semibold" style="font-size: 11px;">Real-time</span>
                                        </div>
                                        <div class="custom-card-body text-center bg-light-subtle">
                                            
                                            <!-- Razorpay Checkout Modal Simulation -->
                                            <div class="rzp-preview-box text-start">
                                                <!-- Header -->
                                                <div class="rzp-header-mock" id="mock_rzp_header" style="background: <?= htmlspecialchars($settings['theme_color']) ?>;">
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <?php if ($logoUrl): ?>
                                                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="height: 24px; max-width: 80px; object-fit: contain; filter: brightness(0) invert(1);">
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-bag-shopping fs-5"></i>
                                                            <?php endif; ?>
                                                            <span class="fw-bold text-white fs-6" id="mock_brand_name"><?= htmlspecialchars($settings['brand_name']) ?></span>
                                                        </div>
                                                        <span class="rzp-badge" id="mock_mode_badge"><?= strtoupper($settings['razorpay_mode']) ?></span>
                                                    </div>
                                                    <div class="text-white-50 small">Amount to pay</div>
                                                    <div class="fs-4 fw-bold text-white">₹1,499.00</div>
                                                </div>

                                                <!-- Body Options -->
                                                <div class="rzp-body-mock">
                                                    <div class="small fw-semibold text-muted mb-2 text-uppercase" style="font-size: 11px;">Select Payment Option</div>
                                                    
                                                    <div class="rzp-option-item selected" id="mock_opt_upi" style="<?= empty($settings['upi_enabled']) ? 'display:none;' : '' ?>">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                                                            <span>UPI (GPay / PhonePe)</span>
                                                        </div>
                                                        <span class="badge bg-success text-white" style="font-size: 10px;">Fast</span>
                                                    </div>

                                                    <div class="rzp-option-item" id="mock_opt_cards" style="<?= empty($settings['cards_enabled']) ? 'display:none;' : '' ?>">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa-regular fa-credit-card text-secondary"></i>
                                                            <span>Cards (Credit / Debit)</span>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-right text-muted" style="font-size: 10px;"></i>
                                                    </div>

                                                    <div class="rzp-option-item" id="mock_opt_netbanking" style="<?= empty($settings['netbanking_enabled']) ? 'display:none;' : '' ?>">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa-solid fa-building-columns text-secondary"></i>
                                                            <span>Net Banking</span>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-right text-muted" style="font-size: 10px;"></i>
                                                    </div>

                                                    <div class="rzp-option-item" id="mock_opt_emi" style="<?= empty($settings['emi_enabled']) ? 'display:none;' : '' ?>">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa-solid fa-calendar-days text-secondary"></i>
                                                            <span>EMI</span>
                                                        </div>
                                                        <i class="fa-solid fa-chevron-right text-muted" style="font-size: 10px;"></i>
                                                    </div>

                                                    <div class="rzp-option-item" id="mock_opt_cod" style="<?= !$isCodActive ? 'display:none;' : '' ?>">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                                                            <span>Cash on Delivery</span>
                                                        </div>
                                                        <span class="badge bg-primary text-white" id="mock_cod_badge" style="font-size: 10px;"><?= htmlspecialchars($settings['cod_advance_percent']) ?>% Adv</span>
                                                    </div>

                                                    <div class="mt-3 text-center">
                                                        <button type="button" class="btn btn-primary w-100 fw-bold py-2 shadow-xs" id="mock_pay_btn" style="background: <?= htmlspecialchars($settings['theme_color']) ?>; border-color: <?= htmlspecialchars($settings['theme_color']) ?>;">
                                                            Pay ₹1,499
                                                        </button>
                                                        <div class="text-muted mt-2" style="font-size: 10.5px;">
                                                            <i class="fa-solid fa-lock me-1"></i> Secured by Razorpay 256-bit Encryption
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="mt-3 p-3 bg-white rounded border text-start">
                                                <div class="small fw-bold text-dark mb-1"><i class="fa-solid fa-circle-info text-primary me-1"></i> Storefront Synchronization</div>
                                                <p class="text-muted small mb-0" style="font-size: 12px;">
                                                    Changes saved here take effect immediately in the React Storefront checkout component (<code>PaymentGateway.jsx</code>).
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                    <!-- Hidden Standalone Reset Form -->
                    <form id="standaloneResetForm" method="POST" action="payment-settings.php" style="display: none;">
                        <input type="hidden" name="action" value="reset_defaults">
                    </form>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Auto-dismiss server alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', () => {
            const alert = document.getElementById('serverAlert');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            }
        });

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `pm-toast ${type}`;
            const icon = (type === 'success') ? '<i class="fa-solid fa-circle-check fs-5 text-success"></i>' : (type === 'error' ? '<i class="fa-solid fa-circle-xmark fs-5 text-danger"></i>' : '<i class="fa-solid fa-circle-info fs-5 text-info"></i>');
            toast.innerHTML = `${icon} <span>${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'all 0.3s ease';
                toast.style.transform = 'translateX(100%)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function confirmResetDefaults() {
            if (confirm('Are you sure you want to reset all payment gateway settings to system default?')) {
                document.getElementById('standaloneResetForm').submit();
            }
        }

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function toggleCardSwitch(switchId, event) {
            // Avoid double toggle when clicking directly on the switch
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'LABEL') {
                return;
            }
            const el = document.getElementById(switchId);
            if (el) {
                el.checked = !el.checked;
                onToggleChange(switchId, el.checked);
            }
        }

        // Handle toggle change with instant AJAX save + live feedback
        async function onToggleChange(key, isChecked) {
            const cardMap = {
                'upi_enabled': 'card_upi',
                'cards_enabled': 'card_cards',
                'netbanking_enabled': 'card_netbanking',
                'emi_enabled': 'card_emi'
            };

            if (cardMap[key]) {
                const card = document.getElementById(cardMap[key]);
                if (card) {
                    if (isChecked) card.classList.add('active');
                    else card.classList.remove('active');
                }
            }

            // Update UI Previews
            updatePreview();

            // Instant Background Save via API
            try {
                const payload = {};
                payload[key] = isChecked ? '1' : '0';

                const res = await fetch('api/payment-settings-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    const label = key.replace('_enabled', '').toUpperCase();
                    showToast(`${label} status updated to ${isChecked ? 'Enabled' : 'Disabled'}!`, 'success');
                } else {
                    showToast('Failed to update: ' + (data.message || 'Error'), 'error');
                }
            } catch (err) {
                console.warn('Background toggle save error:', err);
            }
        }

        function updateAdvanceDisplay(val) {
            document.getElementById('advance_percent_badge').innerText = val + '%';
            const sampleOrder = 1000;
            const advance = (sampleOrder * (val / 100)).toFixed(0);
            const balance = (sampleOrder - advance).toFixed(0);
            document.getElementById('cod_calc_text').innerHTML = `On a ₹1,000 order: customer pays <strong>₹${advance}</strong> now online, and the balance <strong>₹${balance}</strong> in cash upon delivery.`;
            
            const mockBadge = document.getElementById('mock_cod_badge');
            if (mockBadge) mockBadge.innerText = val + '% Adv';
        }

        function syncColorInput(val) {
            document.getElementById('theme_color').value = val;
            updatePreview();
        }

        function syncColorPicker(val) {
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                document.getElementById('theme_color_picker').value = val;
                updatePreview();
            }
        }

        function updatePreview() {
            const brand = document.getElementById('brand_name').value || 'Printmont';
            const color = document.getElementById('theme_color').value || '#0B53A1';
            
            document.getElementById('mock_brand_name').innerText = brand;
            document.getElementById('mock_rzp_header').style.background = color;
            
            const payBtn = document.getElementById('mock_pay_btn');
            if (payBtn) {
                payBtn.style.background = color;
                payBtn.style.borderColor = color;
            }

            // Sync visible options in mock
            const upiCheck = document.getElementById('upi_enabled');
            if (upiCheck) document.getElementById('mock_opt_upi').style.display = upiCheck.checked ? '' : 'none';

            const cardsCheck = document.getElementById('cards_enabled');
            if (cardsCheck) document.getElementById('mock_opt_cards').style.display = cardsCheck.checked ? '' : 'none';

            const netbankingCheck = document.getElementById('netbanking_enabled');
            if (netbankingCheck) document.getElementById('mock_opt_netbanking').style.display = netbankingCheck.checked ? '' : 'none';

            const emiCheck = document.getElementById('emi_enabled');
            if (emiCheck) document.getElementById('mock_opt_emi').style.display = emiCheck.checked ? '' : 'none';

            const codCheck = document.getElementById('cod_enabled');
            if (codCheck) {
                document.getElementById('mock_opt_cod').style.display = codCheck.checked ? '' : 'none';
                const kpiCod = document.getElementById('kpi_cod_status');
                if (kpiCod) {
                    const adv = document.getElementById('cod_advance_percent').value || '30';
                    kpiCod.innerText = codCheck.checked ? `${adv}% Advance` : 'Disabled';
                    kpiCod.className = `fw-bold mb-0 mt-1 ${codCheck.checked ? 'text-primary' : 'text-secondary'}`;
                }
            }

            const rzpCheck = document.getElementById('razorpay_enabled');
            if (rzpCheck) {
                const kpiRzp = document.getElementById('kpi_rzp_status');
                const kpiIcon = document.getElementById('kpi_rzp_icon');
                if (kpiRzp) {
                    kpiRzp.innerText = rzpCheck.checked ? 'Active' : 'Disabled';
                    kpiRzp.className = `fw-bold mb-0 mt-1 ${rzpCheck.checked ? 'text-success' : 'text-danger'}`;
                }
                if (kpiIcon) {
                    kpiIcon.className = `kpi-icon-box ${rzpCheck.checked ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}`;
                }
            }

            // Count enabled channels
            let count = 0;
            if (upiCheck && upiCheck.checked) count++;
            if (cardsCheck && cardsCheck.checked) count++;
            if (netbankingCheck && netbankingCheck.checked) count++;
            if (emiCheck && emiCheck.checked) count++;
            if (codCheck && codCheck.checked) count++;
            const kpiChannels = document.getElementById('kpi_active_channels');
            if (kpiChannels) kpiChannels.innerText = `${count} / 5 Active`;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
</body>
</html>
