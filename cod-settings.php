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
    $action = $_POST['action'] ?? 'update_cod_settings';

    if ($action === 'toggle_cod') {
        $currentSettings = $paymentController->getSettings(false);
        $newStatus = ($currentSettings['cod_enabled'] === '1' || $currentSettings['cod_enabled'] === 1) ? '0' : '1';
        $res = $paymentController->updateSettings(['cod_enabled' => $newStatus]);
        $_SESSION['success_message'] = ($newStatus === '1') ? 'Cash on Delivery activated across storefront!' : 'Cash on Delivery disabled.';
        header("Location: cod-settings.php");
        exit;
    }

    if ($action === 'reset_defaults') {
        $defaults = [
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
            'cod_restricted_pincodes'        => ''
        ];
        $res = $paymentController->updateSettings($defaults);
        $_SESSION['success_message'] = 'Cash on Delivery defaults restored successfully!';
        header("Location: cod-settings.php");
        exit;
    }

    // Save full COD settings
    $formData = [
        'cod_enabled'                    => isset($_POST['cod_enabled']) ? '1' : '0',
        'cod_advance_type'               => in_array($_POST['cod_advance_type'] ?? '', ['percentage', 'fixed']) ? $_POST['cod_advance_type'] : 'percentage',
        'cod_advance_percent'            => max(0, min(100, (int)($_POST['cod_advance_percent'] ?? 30))),
        'cod_advance_fixed_amount'       => max(0, (float)($_POST['cod_advance_fixed_amount'] ?? 150)),
        'cod_extra_fee'                  => max(0, (float)($_POST['cod_extra_fee'] ?? 0)),
        'cod_free_threshold'             => max(0, (float)($_POST['cod_free_threshold'] ?? 999)),
        'cod_min_amount'                 => max(0, (float)($_POST['cod_min_amount'] ?? 0)),
        'cod_max_amount'                 => max(0, (float)($_POST['cod_max_amount'] ?? 50000)),
        'cod_customized_advance_percent' => max(0, min(100, (int)($_POST['cod_customized_advance_percent'] ?? 50))),
        'cod_instructions'               => trim($_POST['cod_instructions'] ?? 'Pay remaining balance upon delivery in cash or via delivery partner UPI QR.'),
        'cod_restricted_pincodes'        => trim($_POST['cod_restricted_pincodes'] ?? '')
    ];

    $res = $paymentController->updateSettings($formData);
    $_SESSION[$res['success'] ? 'success_message' : 'error_message'] = $res['message'];
    header("Location: cod-settings.php");
    exit;
}

// Fetch current settings
$settings = $paymentController->getSettings(false);
$logoData = $logoController->getWebsiteLogo();
$logoUrl = '';
if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
    $logoUrl = $logoData['file_path'] . $logoData['file_name'];
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$isCodActive = ($settings['cod_enabled'] === '1' || $settings['cod_enabled'] === 1 || $settings['cod_enabled'] === true);
$advType = $settings['cod_advance_type'] ?? 'percentage';
$advPercent = (int)($settings['cod_advance_percent'] ?? 30);
$advFixed = (float)($settings['cod_advance_fixed_amount'] ?? 150);
$extraFee = (float)($settings['cod_extra_fee'] ?? 0);
$customAdv = (int)($settings['cod_customized_advance_percent'] ?? 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Manage Cash on Delivery (COD) Rules, Partial Advance Payments, and Thresholds for Printmont">
    <title>Cash on Delivery (COD) Settings | Printmont Admin</title>

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

        .cod-header-gradient {
            background: linear-gradient(135deg, #0B53A1 0%, #1e40af 50%, #0d9488 100%);
            border-radius: 16px;
            color: #ffffff;
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(11, 83, 161, 0.25);
        }

        .cod-header-gradient::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 220px;
            height: 220px;
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

        /* Interactive Simulator */
        .simulator-box {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .sim-header {
            background: #0B53A1;
            color: #ffffff;
            padding: 18px 20px;
        }

        .sim-body {
            padding: 20px;
            background: #f8fafc;
        }

        .sim-breakdown-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13.5px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .sim-breakdown-row:last-child {
            border-bottom: none;
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

        /* Toast Notifications */
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
                    
                    <!-- Header Actions -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Cash on Delivery (COD) Settings</h3>
                            <p class="text-muted mb-0 small">Configure partial advance rules, order value thresholds, convenience fees, and delivery partner instructions.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <form id="toggleCodTopForm" method="POST" action="cod-settings.php" class="d-inline">
                                <input type="hidden" name="action" value="toggle_cod">
                                <button type="submit" class="btn <?= $isCodActive ? 'btn-outline-danger' : 'btn-success' ?> btn-sm me-2 shadow-sm fw-semibold">
                                    <i class="fa-solid fa-power-off me-1"></i> <?= $isCodActive ? 'Disable Storefront COD' : 'Activate Storefront COD' ?>
                                </button>
                            </form>
                            <a href="payment-settings.php" class="btn btn-outline-primary btn-sm shadow-sm fw-semibold">
                                <i class="fa-solid fa-credit-card me-1"></i> Full Payment Settings
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

                    <!-- Gradient Banner -->
                    <div class="cod-header-gradient mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-white text-primary fw-bold px-2 py-1" style="font-size: 11px;">PRINTMONT RISK MITIGATION</span>
                                    <span class="badge <?= $isCodActive ? 'bg-success text-white' : 'bg-danger text-white' ?> fw-bold" id="badge_banner_status">
                                        <span class="pulse-dot"></span> <?= $isCodActive ? 'COD LIVE ON STOREFRONT' : 'COD OFFLINE' ?>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-white mb-1">Smart Cash on Delivery & Partial Advance Protection</h4>
                                <p class="text-white-50 mb-0 small">
                                    Advance Model: <strong><?= $advType === 'percentage' ? "$advPercent% Partial Advance" : "₹$advFixed Flat Advance" ?></strong>
                                    • Handling Fee: <strong><?= $extraFee > 0 ? "₹$extraFee" : "₹0 Free" ?></strong>
                                    • Order Limit: <strong>₹<?= number_format($settings['cod_min_amount']) ?> - ₹<?= number_format($settings['cod_max_amount']) ?></strong>
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                                <a href="http://localhost:5173/cart" target="_blank" class="btn btn-light text-primary fw-bold px-3 py-2 shadow-sm">
                                    <i class="fa-solid fa-cart-shopping me-1"></i> View Live Checkout
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
                                        <div class="text-muted small fw-semibold">COD Status</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $isCodActive ? 'text-success' : 'text-danger' ?>" id="kpi_cod_active">
                                            <?= $isCodActive ? 'Active' : 'Disabled' ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Storefront Availability</div>
                                    </div>
                                    <div class="kpi-icon-box <?= $isCodActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" id="kpi_cod_icon">
                                        <i class="fa-solid fa-truck-ramp-box"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Advance Requirement</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-primary" id="kpi_adv_val">
                                            <?= $advType === 'percentage' ? "$advPercent%" : "₹$advFixed" ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Paid Online At Checkout</div>
                                    </div>
                                    <div class="kpi-icon-box bg-primary-subtle text-primary">
                                        <i class="fa-solid fa-percent"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Custom Orders Advance</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-warning" id="kpi_custom_adv"><?= $customAdv ?>%</h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">For Customized Products</div>
                                    </div>
                                    <div class="kpi-icon-box bg-warning-subtle text-warning">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">COD Convenience Fee</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $extraFee > 0 ? 'text-danger' : 'text-success' ?>" id="kpi_fee_val">
                                            <?= $extraFee > 0 ? "₹$extraFee" : "₹0 Free" ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Extra courier handling</div>
                                    </div>
                                    <div class="kpi-icon-box bg-info-subtle text-info">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Form & Simulator -->
                    <form id="codSettingsForm" method="POST" action="cod-settings.php">
                        <input type="hidden" name="action" value="update_cod_settings">

                        <div class="row g-4">
                            <!-- Left Column (8 Cols) -->
                            <div class="col-xl-8">
                                
                                <!-- Card 1: COD Master & Partial Advance Model -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i>Partial Advance Payment Rules</h5>
                                            <span class="text-muted small">Configure how advance payments protect against fake/RTO orders</span>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="cod_enabled" name="cod_enabled" value="1" <?= $isCodActive ? 'checked' : '' ?> onchange="onCodToggle(this.checked)">
                                            <label class="form-check-label fw-semibold small text-dark" for="cod_enabled">Enable COD</label>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        
                                        <!-- Advance Mode Selection -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold text-dark mb-2">Advance Calculation Model</label>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check p-3 rounded-3 border bg-light cursor-pointer h-100" onclick="document.getElementById('adv_percentage').checked = true; toggleAdvType('percentage'); updateSim();">
                                                        <input class="form-check-input ms-0 me-2" type="radio" name="cod_advance_type" id="adv_percentage" value="percentage" <?= $advType === 'percentage' ? 'checked' : '' ?> onchange="toggleAdvType('percentage'); updateSim();">
                                                        <label class="form-check-label fw-bold text-dark cursor-pointer d-block" for="adv_percentage">
                                                            <i class="fa-solid fa-percent text-primary me-1"></i> Percentage-based Advance
                                                            <span class="d-block text-muted fw-normal small mt-1">Calculates a percentage (e.g. 30%) of the total order value up-front at checkout.</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check p-3 rounded-3 border bg-light cursor-pointer h-100" onclick="document.getElementById('adv_fixed').checked = true; toggleAdvType('fixed'); updateSim();">
                                                        <input class="form-check-input ms-0 me-2" type="radio" name="cod_advance_type" id="adv_fixed" value="fixed" <?= $advType === 'fixed' ? 'checked' : '' ?> onchange="toggleAdvType('fixed'); updateSim();">
                                                        <label class="form-check-label fw-bold text-dark cursor-pointer d-block" for="adv_fixed">
                                                            <i class="fa-solid fa-indian-rupee-sign text-success me-1"></i> Flat Fixed Advance
                                                            <span class="d-block text-muted fw-normal small mt-1">Requires a constant fixed amount (e.g. ₹150) irrespective of total order value.</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Percentage Slider Box -->
                                        <div id="box_percent" class="p-3 border rounded-3 mb-3 bg-white" style="<?= $advType !== 'percentage' ? 'display:none;' : '' ?>">
                                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                                                <span>Standard Products Advance Requirement (%)</span>
                                                <span class="badge bg-primary fs-6 fw-bold" id="adv_percent_badge"><?= $advPercent ?>%</span>
                                            </label>
                                            <input type="range" class="form-range" min="0" max="100" step="5" name="cod_advance_percent" id="cod_advance_percent" value="<?= $advPercent ?>" oninput="syncAdvPercent(this.value)">
                                            <div class="d-flex justify-content-between text-muted small mt-1">
                                                <span>0% (Full COD)</span>
                                                <span>30% (Standard Recommendation)</span>
                                                <span>50% (High Protection)</span>
                                                <span>100% (Prepaid Only)</span>
                                            </div>
                                        </div>

                                        <!-- Fixed Amount Box -->
                                        <div id="box_fixed" class="p-3 border rounded-3 mb-3 bg-white" style="<?= $advType !== 'fixed' ? 'display:none;' : '' ?>">
                                            <label class="form-label fw-bold text-dark">Flat Fixed Advance Amount (₹)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="cod_advance_fixed_amount" id="cod_advance_fixed_amount" value="<?= $advFixed ?>" min="0" oninput="updateSim()">
                                            </div>
                                            <div class="form-text small">This amount will be collected online via Razorpay before placing the COD order.</div>
                                        </div>

                                        <!-- Customized Merchandise Advance -->
                                        <div class="p-3 bg-warning-subtle-light border border-warning rounded-3 mb-2">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="form-label fw-bold text-dark mb-0">
                                                    <i class="fa-solid fa-wand-magic-sparkles text-warning me-1"></i> Customized / Printed Merchandise Advance (%)
                                                </label>
                                                <span class="badge bg-warning text-dark fw-bold" id="custom_adv_badge"><?= $customAdv ?>%</span>
                                            </div>
                                            <input type="range" class="form-range" min="0" max="100" step="5" name="cod_customized_advance_percent" id="cod_customized_advance_percent" value="<?= $customAdv ?>" oninput="syncCustomAdv(this.value)">
                                            <div class="form-text small text-dark">
                                                Since personalized items with custom logos cannot be restocked if rejected, a higher advance (e.g. 50% or 100%) is recommended.
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Card 2: Order Value Thresholds & Fees -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-wallet text-primary me-2"></i>Order Thresholds & Handling Fees</h5>
                                            <span class="text-muted small">Set minimum/maximum order limits and COD handling fees</span>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Minimum Order Value for COD (₹)</label>
                                                <input type="number" class="form-control" name="cod_min_amount" id="cod_min_amount" value="<?= htmlspecialchars($settings['cod_min_amount']) ?>" min="0" oninput="updateSim()">
                                                <div class="form-text small">Orders below this value cannot use COD (e.g. ₹0).</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Maximum Order Value for COD (₹)</label>
                                                <input type="number" class="form-control" name="cod_max_amount" id="cod_max_amount" value="<?= htmlspecialchars($settings['cod_max_amount']) ?>" min="0" oninput="updateSim()">
                                                <div class="form-text small">Orders exceeding this limit must be prepaid online (e.g. ₹50,000).</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">COD Handling Fee (₹)</label>
                                                <input type="number" class="form-control" name="cod_extra_fee" id="cod_extra_fee" value="<?= htmlspecialchars($settings['cod_extra_fee']) ?>" min="0" oninput="updateSim()">
                                                <div class="form-text small">Extra fee charged for courier cash handling (₹0 for free).</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Free COD Threshold (₹)</label>
                                                <input type="number" class="form-control" name="cod_free_threshold" id="cod_free_threshold" value="<?= htmlspecialchars($settings['cod_free_threshold']) ?>" min="0" oninput="updateSim()">
                                                <div class="form-text small">Orders above this amount get ₹0 COD handling fee.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Instructions & Non-Serviceable Pincodes -->
                                <div class="custom-card">
                                    <div class="custom-card-header">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-circle-info text-primary me-2"></i>Delivery Instructions & Pincode Restrictions</h5>
                                            <span class="text-muted small">Customize customer guidelines and restrict risky postal codes</span>
                                        </div>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Customer-Facing COD Instructions</label>
                                            <textarea class="form-control" rows="2" name="cod_instructions" id="cod_instructions" oninput="updateSim()"><?= htmlspecialchars($settings['cod_instructions']) ?></textarea>
                                            <div class="form-text small">Displayed on the checkout payment screen under Cash on Delivery.</div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold text-secondary">Restricted / Blocked COD Pincodes (Comma separated)</label>
                                            <textarea class="form-control font-monospace" rows="2" name="cod_restricted_pincodes" placeholder="e.g. 110001, 400001, 560001"><?= htmlspecialchars($settings['cod_restricted_pincodes']) ?></textarea>
                                            <div class="form-text small">Orders shipping to these pincodes will have COD disabled automatically.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex align-items-center justify-content-between p-3 bg-white border rounded-3 shadow-sm mb-4">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="confirmResetCodDefaults()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Reset COD Defaults
                                    </button>

                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save COD Settings
                                    </button>
                                </div>

                            </div>

                            <!-- Right Column: Interactive Order Simulator (4 Cols) -->
                            <div class="col-xl-4">
                                <div class="sticky-top" style="top: 20px; z-index: 10;">
                                    
                                    <div class="simulator-box mb-3">
                                        <div class="sim-header">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold fs-6"><i class="fa-solid fa-calculator me-2"></i>COD Order Simulator</span>
                                                <span class="badge bg-white text-primary fw-bold" style="font-size: 10px;">Interactive</span>
                                            </div>
                                            <div class="text-white-50 small mt-1">Test how customers experience COD calculations</div>
                                        </div>
                                        <div class="sim-body">
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold text-dark">Simulate Cart Total (₹)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" class="form-control fw-bold fs-6" id="sim_cart_input" value="1499" min="1" oninput="updateSim()">
                                                </div>
                                            </div>

                                            <div class="p-3 bg-white rounded-3 border mb-3">
                                                <div class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 11px;">Payment Breakdown</div>
                                                
                                                <div class="sim-breakdown-row">
                                                    <span class="text-muted">Cart Order Total:</span>
                                                    <span class="fw-bold text-dark" id="sim_total_val">₹1,499</span>
                                                </div>

                                                <div class="sim-breakdown-row">
                                                    <span class="text-muted">COD Convenience Fee:</span>
                                                    <span class="fw-semibold text-danger" id="sim_fee_val">₹0 (Free)</span>
                                                </div>

                                                <div class="sim-breakdown-row text-primary fw-bold">
                                                    <span>Pay Online Now (Advance):</span>
                                                    <span class="fs-6" id="sim_advance_val">₹450</span>
                                                </div>

                                                <div class="sim-breakdown-row text-success fw-bold">
                                                    <span>Pay in Cash on Delivery:</span>
                                                    <span class="fs-6" id="sim_balance_val">₹1,049</span>
                                                </div>
                                            </div>

                                            <!-- Storefront Checkout Card Preview -->
                                            <div class="p-3 bg-white rounded-3 border">
                                                <div class="small fw-bold text-muted mb-2 text-uppercase" style="font-size: 10.5px;">Storefront Checkout UI Preview</div>
                                                
                                                <div class="p-2 border rounded-2 bg-light mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                                                        <span class="fw-bold text-dark" style="font-size: 13px;">Cash on Delivery</span>
                                                        <span class="badge bg-primary text-white ms-auto" style="font-size: 10px;" id="sim_ui_badge">30% Advance</span>
                                                    </div>
                                                    <div class="text-secondary mt-1" style="font-size: 11.5px;" id="sim_ui_instructions">
                                                        Pay ₹450 as advance and balance amount as Cash on delivery
                                                    </div>
                                                </div>

                                                <button type="button" class="btn btn-primary w-100 fw-bold py-2 shadow-xs" style="font-size: 13px;">
                                                    CONFIRM COD ORDER (PAY ₹<span id="sim_btn_amt">450</span>)
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="p-3 bg-white rounded-3 border shadow-sm">
                                        <div class="small fw-bold text-dark mb-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> RTO Protection Advantage</div>
                                        <p class="text-muted small mb-0" style="font-size: 12px;">
                                            Partial advance models reduce return-to-origin (RTO) courier loss by over 85% compared to zero-advance COD.
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </form>

                    <!-- Hidden Standalone Reset Form -->
                    <form id="standaloneResetCodForm" method="POST" action="cod-settings.php" style="display: none;">
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

        function confirmResetCodDefaults() {
            if (confirm('Are you sure you want to reset Cash on Delivery settings to system defaults?')) {
                document.getElementById('standaloneResetCodForm').submit();
            }
        }

        function toggleAdvType(type) {
            document.getElementById('box_percent').style.display = (type === 'percentage') ? '' : 'none';
            document.getElementById('box_fixed').style.display = (type === 'fixed') ? '' : 'none';
            const kpiAdv = document.getElementById('kpi_adv_val');
            if (kpiAdv) {
                if (type === 'percentage') {
                    const pct = document.getElementById('cod_advance_percent').value || '30';
                    kpiAdv.innerText = pct + '%';
                } else {
                    const fxd = document.getElementById('cod_advance_fixed_amount').value || '150';
                    kpiAdv.innerText = '₹' + fxd;
                }
            }
        }

        function syncAdvPercent(val) {
            document.getElementById('adv_percent_badge').innerText = val + '%';
            const kpiAdv = document.getElementById('kpi_adv_val');
            if (kpiAdv) kpiAdv.innerText = val + '%';
            updateSim();
        }

        function syncCustomAdv(val) {
            document.getElementById('custom_adv_badge').innerText = val + '%';
            const kpiCustom = document.getElementById('kpi_custom_adv');
            if (kpiCustom) kpiCustom.innerText = val + '%';
        }

        async function onCodToggle(isChecked) {
            updateSim();
            try {
                const res = await fetch('api/payment-settings-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cod_enabled: isChecked ? '1' : '0' })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Cash on Delivery ${isChecked ? 'Activated' : 'Disabled'} on Storefront!`, 'success');
                    const kpi = document.getElementById('kpi_cod_active');
                    const icon = document.getElementById('kpi_cod_icon');
                    if (kpi) {
                        kpi.innerText = isChecked ? 'Active' : 'Disabled';
                        kpi.className = `fw-bold mb-0 mt-1 ${isChecked ? 'text-success' : 'text-danger'}`;
                    }
                    if (icon) {
                        icon.className = `kpi-icon-box ${isChecked ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}`;
                    }
                }
            } catch (err) {
                console.warn('COD toggle background save error:', err);
            }
        }

        function updateSim() {
            const cartTotal = parseFloat(document.getElementById('sim_cart_input').value) || 0;
            const isEnabled = document.getElementById('cod_enabled').checked;
            const isPercent = document.getElementById('adv_percentage').checked;
            const percent = parseFloat(document.getElementById('cod_advance_percent').value) || 30;
            const fixedAmt = parseFloat(document.getElementById('cod_advance_fixed_amount').value) || 150;
            const extraFee = parseFloat(document.getElementById('cod_extra_fee').value) || 0;
            const freeThreshold = parseFloat(document.getElementById('cod_free_threshold').value) || 999;

            let applicableFee = 0;
            if (extraFee > 0 && cartTotal < freeThreshold) {
                applicableFee = extraFee;
            }

            let advance = 0;
            if (isPercent) {
                advance = Math.round(cartTotal * (percent / 100));
            } else {
                advance = Math.min(cartTotal, fixedAmt);
            }

            const totalPayable = cartTotal + applicableFee;
            const balance = Math.max(0, totalPayable - advance);

            document.getElementById('sim_total_val').innerText = '₹' + cartTotal.toLocaleString('en-IN');
            document.getElementById('sim_fee_val').innerText = applicableFee > 0 ? ('+₹' + applicableFee.toLocaleString('en-IN')) : '₹0 (Free)';
            document.getElementById('sim_advance_val').innerText = '₹' + advance.toLocaleString('en-IN');
            document.getElementById('sim_balance_val').innerText = '₹' + balance.toLocaleString('en-IN');
            document.getElementById('sim_btn_amt').innerText = advance.toLocaleString('en-IN');

            const badgeText = isPercent ? (percent + '% Advance') : ('₹' + fixedAmt + ' Advance');
            document.getElementById('sim_ui_badge').innerText = badgeText;
            document.getElementById('sim_ui_instructions').innerText = `Pay ₹${advance.toLocaleString('en-IN')} as advance and balance amount as Cash on delivery`;
        }

        // Initialize simulator on load
        document.addEventListener('DOMContentLoaded', updateSim);
    </script>
</body>
</html>
