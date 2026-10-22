<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/EmailController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$emailController = new EmailController();

// Handle Form Submissions (Add Template, Send Test Email, AJAX toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add New Template
    if (isset($_POST['add_template']) || $action === 'add_template') {
        $data = [
            'template_name'    => trim($_POST['template_name'] ?? ''),
            'template_subject' => trim($_POST['template_subject'] ?? ''),
            'template_body'    => trim($_POST['template_body'] ?? ''),
            'template_type'    => in_array($_POST['template_type'] ?? '', ['system', 'custom']) ? $_POST['template_type'] : 'system',
            'status'           => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive'
        ];
        
        if (!empty($data['template_name']) && !empty($data['template_subject']) && !empty($data['template_body'])) {
            if ($emailController->createTemplate($data)) {
                $_SESSION['success_message'] = "Email template \"{$data['template_name']}\" created successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to create email template in database.";
            }
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields (Name, Subject, and Body).";
        }
        header("Location: email-templates.php");
        exit;
    }

    // 2. Send Live Test Email with Template
    if ($action === 'send_template_test') {
        header('Content-Type: application/json');
        $recipient = trim($_POST['test_recipient'] ?? '');
        $templateId = (int)($_POST['template_id'] ?? 0);
        $template = $emailController->getTemplateById($templateId);
        $activeConfig = $emailController->getActiveConfiguration();

        if (!$template) {
            echo json_encode(['success' => false, 'message' => 'Template not found.']);
            exit;
        }

        if (!$activeConfig) {
            echo json_encode(['success' => false, 'message' => 'No active SMTP email configuration found. Please configure SMTP in Email Configurations.']);
            exit;
        }

        if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid recipient email address.']);
            exit;
        }

        // Render template with mock test variables
        $body = $template['template_body'];
        $subject = $template['template_subject'];
        $samples = [
            '{customer_name}'   => 'Rahul Sharma',
            '{customer_email}'  => $recipient,
            '{customer_phone}'  => '+91 98765 43210',
            '{order_number}'    => 'ORD-' . rand(10000, 99999),
            '{order_total}'     => '1,499.00',
            '{payment_method}'  => 'Razorpay UPI (Paid)',
            '{shipping_address}'=> 'Flat 402, Sunshine Heights, MG Road, Bengaluru, 560001',
            '{courier_name}'    => 'BlueDart Express',
            '{tracking_number}' => 'BD' . rand(10000000, 99999999),
            '{tracking_url}'    => 'http://localhost:5173/track-order',
            '{otp_code}'        => (string)rand(100000, 999999),
            '{site_name}'       => 'Printmont',
            '{site_url}'        => 'http://localhost:5173',
            '{support_email}'   => 'support@printmont.com',
            '{support_phone}'   => '+91 98765 43210',
            '{year}'            => date('Y')
        ];

        foreach ($samples as $key => $val) {
            $subject = str_replace($key, $val, $subject);
            $body = str_replace($key, $val, $body);
        }

        $mailData = [
            'mail_host'        => $activeConfig['mail_host'] ?? '',
            'mail_port'        => $activeConfig['mail_port'] ?? '',
            'mail_username'    => $activeConfig['mail_username'] ?? '',
            'mail_password'    => $activeConfig['mail_password'] ?? '',
            'mail_encryption'  => $activeConfig['mail_encryption'] ?? ($activeConfig['encryption'] ?? 'tls'),
            'mail_from_address'=> $activeConfig['mail_from_address'] ?? '',
            'mail_from_name'   => $activeConfig['mail_from_name'] ?? 'Printmont'
        ];

        $res = $emailController->sendTestEmail($mailData, $recipient, $subject, $body);
        if (!empty($res['success'])) {
            echo json_encode(['success' => true, 'message' => "Test email successfully delivered to {$recipient}!"]);
        } else {
            echo json_encode(['success' => false, 'message' => "SMTP Delivery Error: " . ($res['error'] ?: 'Could not send test email.')]);
        }
        exit;
    }

    // 3. Toggle Status AJAX
    if ($action === 'toggle_status') {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $res = $emailController->updateTemplate($id, ['status' => $status]);
        echo json_encode(['success' => (bool)$res, 'status' => $status]);
        exit;
    }
}

// Fetch all templates & stats
$templates = $emailController->getAllTemplates();
$activeConfig = $emailController->getActiveConfiguration();

$totalCount = count($templates);
$systemCount = 0;
$customCount = 0;
$activeCount = 0;

foreach ($templates as $t) {
    if ($t['template_type'] === 'system') $systemCount++;
    if ($t['template_type'] === 'custom') $customCount++;
    if ($t['status'] === 'active') $activeCount++;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Manage transactional and promotional email templates for Printmont e-commerce">
    <title>Email Templates | Printmont Admin</title>

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

        .email-header-gradient {
            background: linear-gradient(135deg, #0B53A1 0%, #1e40af 50%, #7c3aed 100%);
            border-radius: 16px;
            color: #ffffff;
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(11, 83, 161, 0.25);
        }

        .email-header-gradient::after {
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

        .table-custom th {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1.5px solid #e2e8f0;
            padding: 14px 18px;
        }

        .table-custom td {
            padding: 16px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-custom tr:hover {
            background-color: #f8faff;
        }

        .form-switch .form-check-input {
            width: 42px;
            height: 22px;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #0B53A1;
            border-color: #0B53A1;
        }

        .var-chip {
            cursor: pointer;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-family: monospace;
            display: inline-block;
            margin: 2px;
            transition: all 0.2s ease;
        }

        .var-chip:hover {
            background: #0B53A1;
            color: #ffffff;
            border-color: #0B53A1;
            transform: scale(1.05);
        }

        /* Email Mockup Preview */
        .email-mockup-frame {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
        }

        .email-mockup-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
        }

        .email-mockup-body {
            padding: 24px;
            background: #f1f5f9;
            min-height: 250px;
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
            max-width: 380px;
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
                            <h3 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i>Email Templates Hub</h3>
                            <p class="text-muted mb-0 small">Manage, preview, and customize automated transactional and marketing email notifications.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <a href="email-configurations.php" class="btn btn-outline-secondary btn-sm me-2 shadow-sm fw-semibold">
                                <i class="fa-solid fa-server me-1"></i> SMTP Server Configs
                            </a>
                            <button type="button" class="btn btn-primary btn-sm shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                <i class="fa-solid fa-plus me-1"></i> Create New Template
                            </button>
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
                    <div class="email-header-gradient mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-white text-primary fw-bold px-2 py-1" style="font-size: 11px;">TRANSACTIONAL ENGINE</span>
                                    <?php if ($activeConfig): ?>
                                        <span class="badge bg-success text-white fw-bold">
                                            <i class="fa-solid fa-circle-check me-1"></i> SMTP ACTIVE: <?= htmlspecialchars($activeConfig['config_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark fw-bold">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> NO ACTIVE SMTP CONFIG
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="fw-bold text-white mb-1">Automated E-Commerce Notifications & Receipts</h4>
                                <p class="text-white-50 mb-0 small">
                                    Total Templates: <strong><?= $totalCount ?></strong>
                                    • Active System Triggers: <strong><?= $systemCount ?></strong>
                                    • Active SMTP Host: <code><?= htmlspecialchars($activeConfig['mail_host'] ?? 'Not Connected') ?></code>
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                                <a href="email-configurations.php" class="btn btn-light text-primary fw-bold px-3 py-2 shadow-sm">
                                    <i class="fa-solid fa-sliders me-1"></i> Manage SMTP Server
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
                                        <div class="text-muted small fw-semibold">Total Templates</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-dark"><?= $totalCount ?></h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Registered templates</div>
                                    </div>
                                    <div class="kpi-icon-box bg-primary-subtle text-primary">
                                        <i class="fa-solid fa-envelopes-bulk"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">System (Transactional)</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-success"><?= $systemCount ?> Active</h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Orders, OTP & Security</div>
                                    </div>
                                    <div class="kpi-icon-box bg-success-subtle text-success">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Custom / Marketing</div>
                                        <h4 class="fw-bold mb-0 mt-1 text-purple" style="color: #7c3aed;"><?= $customCount ?> Templates</h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">Welcome & Promotions</div>
                                    </div>
                                    <div class="kpi-icon-box bg-warning-subtle text-warning">
                                        <i class="fa-solid fa-bullhorn"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="kpi-card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">SMTP Engine Status</div>
                                        <h4 class="fw-bold mb-0 mt-1 <?= $activeConfig ? 'text-success' : 'text-danger' ?>">
                                            <?= $activeConfig ? 'Connected' : 'Offline' ?>
                                        </h4>
                                        <div class="text-muted" style="font-size: 11px; margin-top: 4px;">
                                            <?= $activeConfig ? htmlspecialchars($activeConfig['config_name']) : 'Action needed' ?>
                                        </div>
                                    </div>
                                    <div class="kpi-icon-box <?= $activeConfig ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table & Filter Controls -->
                    <div class="custom-card mb-4">
                        <div class="custom-card-header">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="position-relative" style="min-width: 260px;">
                                    <input type="text" class="form-control form-control-sm ps-4" id="tableSearchInput" placeholder="Search by name, subject..." oninput="filterTemplates()">
                                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="top: 9px; left: 10px; font-size: 12px;"></i>
                                </div>

                                <select class="form-select form-select-sm" id="typeFilterSelect" style="width: auto;" onchange="filterTemplates()">
                                    <option value="all">All Template Types</option>
                                    <option value="system">System (Transactional)</option>
                                    <option value="custom">Custom (Marketing)</option>
                                </select>

                                <select class="form-select form-select-sm" id="statusFilterSelect" style="width: auto;" onchange="filterTemplates()">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                </select>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                <i class="fa-solid fa-plus me-1"></i> Add Template
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-custom mb-0" id="templatesTable">
                                <thead>
                                    <tr>
                                        <th>Template Details</th>
                                        <th>Subject Line</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($templates)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="fa-regular fa-envelope-open fs-1 text-muted mb-3 d-block"></i>
                                                <h5 class="fw-bold text-dark">No Email Templates Found</h5>
                                                <p class="text-muted small">Create your first transactional or marketing template.</p>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                                    <i class="fa-solid fa-plus me-1"></i> Add Template
                                                </button>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($templates as $tpl): ?>
                                            <tr class="template-row" 
                                                data-name="<?= htmlspecialchars(strtolower($tpl['template_name'])) ?>"
                                                data-subject="<?= htmlspecialchars(strtolower($tpl['template_subject'])) ?>"
                                                data-type="<?= htmlspecialchars($tpl['template_type']) ?>"
                                                data-status="<?= htmlspecialchars($tpl['status']) ?>">
                                                
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="kpi-icon-box bg-light text-primary" style="width: 40px; height: 40px; font-size: 16px;">
                                                            <i class="fa-solid <?= $tpl['template_type'] === 'system' ? 'fa-bolt' : 'fa-envelope' ?>"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($tpl['template_name']) ?></div>
                                                            <div class="text-muted" style="font-size: 11px;">ID: #<?= $tpl['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="text-dark fw-medium text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($tpl['template_subject']) ?>">
                                                        <?= htmlspecialchars($tpl['template_subject']) ?>
                                                    </div>
                                                </td>

                                                <td>
                                                    <?php if ($tpl['template_type'] === 'system'): ?>
                                                        <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size: 11px;">
                                                            <i class="fa-solid fa-gear me-1"></i> System
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-dark fw-bold" style="font-size: 11px;">
                                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Custom
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" id="status_<?= $tpl['id'] ?>" <?= $tpl['status'] === 'active' ? 'checked' : '' ?> onchange="toggleTemplateStatus(<?= $tpl['id'] ?>, this.checked)">
                                                        <label class="form-check-label small fw-semibold" for="status_<?= $tpl['id'] ?>">
                                                            <?= ucfirst($tpl['status']) ?>
                                                        </label>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="small text-muted">
                                                        <?= date('M d, Y', strtotime($tpl['created_at'] ?? 'now')) ?>
                                                    </div>
                                                    <div style="font-size: 10px;" class="text-muted"><?= date('h:i A', strtotime($tpl['created_at'] ?? 'now')) ?></div>
                                                </td>

                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <!-- Preview Button -->
                                                        <button type="button" class="btn btn-outline-info btn-sm shadow-xs" title="Preview Email" onclick="openPreviewModal(<?= htmlspecialchars(json_encode($tpl), ENT_QUOTES, 'UTF-8') ?>)">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>

                                                        <!-- Send Test Email -->
                                                        <button type="button" class="btn btn-outline-success btn-sm shadow-xs" title="Send Test Email" onclick="openTestModal(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['template_name'], ENT_QUOTES) ?>')">
                                                            <i class="fa-solid fa-paper-plane"></i>
                                                        </button>

                                                        <!-- Edit Button -->
                                                        <a href="edit-email-template.php?id=<?= $tpl['id'] ?>" class="btn btn-outline-primary btn-sm shadow-xs" title="Edit Template">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </a>

                                                        <!-- Delete Button -->
                                                        <a href="delete-email-template.php?id=<?= $tpl['id'] ?>" class="btn btn-outline-danger btn-sm shadow-xs" onclick="return confirm('Delete template \'<?= htmlspecialchars($tpl['template_name'], ENT_QUOTES) ?>\'?')" title="Delete Template">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    </div>
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

    <!-- 1. Add Template Modal -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST" action="email-templates.php">
                    <input type="hidden" name="action" value="add_template">
                    
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fs-6"><i class="fa-solid fa-plus me-2"></i>Create New Email Template</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" placeholder="e.g. Order Placed Notification" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Template Type</label>
                                <select class="form-select" name="template_type">
                                    <option value="system">System (Transactional)</option>
                                    <option value="custom">Custom (Marketing)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Subject Line <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="template_subject" placeholder="e.g. Your Printmont Order #{order_number} is Confirmed" required>
                        </div>

                        <!-- Variables Tag Helper -->
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <div class="small fw-bold text-dark mb-1"><i class="fa-solid fa-tags text-primary me-1"></i> Click to Insert Placeholder Tags</div>
                            <div>
                                <span class="var-chip" onclick="insertTagIntoBody('{customer_name}')">{customer_name}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{order_number}')">{order_number}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{order_total}')">{order_total}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{payment_method}')">{payment_method}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{shipping_address}')">{shipping_address}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{tracking_number}')">{tracking_number}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{tracking_url}')">{tracking_url}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{otp_code}')">{otp_code}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{site_url}')">{site_url}</span>
                                <span class="var-chip" onclick="insertTagIntoBody('{support_email}')">{support_email}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">HTML Email Content <span class="text-danger">*</span></label>
                            <textarea class="form-control font-monospace" name="template_body" id="new_template_body" rows="12" placeholder="<div style='font-family: Arial, sans-serif;'>...</div>" required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_template" class="btn btn-primary px-4 fw-bold">Create Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Realistic Email Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <h5 class="modal-title fs-6 mb-0" id="previewModalTitle">Template Preview</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 bg-light">
                    <!-- Email Client Mockup -->
                    <div class="email-mockup-frame">
                        <div class="email-mockup-header">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                                        PM
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 13.5px;">Printmont &lt;noreply@printmont.com&gt;</div>
                                        <div class="text-muted" style="font-size: 11px;">to: rahul.sharma@example.com</div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-muted border">Today, <?= date('h:i A') ?></span>
                            </div>
                            <div class="fw-bold text-dark fs-6" id="previewSubject"></div>
                        </div>

                        <div class="email-mockup-body">
                            <div class="bg-white rounded border p-4 shadow-xs" id="previewBody"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Preview</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Send Test Email Modal -->
    <div class="modal fade" id="sendTestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <form id="sendTestForm" onsubmit="handleSendTest(event)">
                    <input type="hidden" id="test_template_id" name="template_id">
                    
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fs-6"><i class="fa-solid fa-paper-plane me-2"></i>Send Test Email</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Target Template</label>
                            <input type="text" class="form-control bg-light" id="test_template_name" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Recipient Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="test_recipient_email" placeholder="e.g. yourname@gmail.com" required>
                            <div class="form-text small">This will send an actual live email through your active SMTP server.</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4" id="sendTestBtn">
                            <i class="fa-solid fa-paper-plane me-1"></i> Dispatch Test
                        </button>
                    </div>
                </form>
            </div>
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

        // Live Table Search & Filters
        function filterTemplates() {
            const query = document.getElementById('tableSearchInput').value.toLowerCase().trim();
            const typeFilter = document.getElementById('typeFilterSelect').value;
            const statusFilter = document.getElementById('statusFilterSelect').value;
            const rows = document.querySelectorAll('.template-row');

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const subject = row.getAttribute('data-subject') || '';
                const type = row.getAttribute('data-type') || '';
                const status = row.getAttribute('data-status') || '';

                const matchesQuery = !query || name.includes(query) || subject.includes(query);
                const matchesType = (typeFilter === 'all') || (type === typeFilter);
                const matchesStatus = (statusFilter === 'all') || (status === statusFilter);

                if (matchesQuery && matchesType && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // 1-Click Status Toggle AJAX
        async function toggleTemplateStatus(id, isChecked) {
            const newStatus = isChecked ? 'active' : 'inactive';
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('id', id);
                formData.append('status', newStatus);

                const res = await fetch('email-templates.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Template #${id} is now ${newStatus.toUpperCase()}`, 'success');
                    // Update row attribute
                    const row = document.querySelector(`.template-row:has(#status_${id})`);
                    if (row) row.setAttribute('data-status', newStatus);
                } else {
                    showToast('Failed to update status', 'error');
                }
            } catch (e) {
                console.warn('Status toggle error:', e);
            }
        }

        // Insert placeholder tag into create modal textarea
        function insertTagIntoBody(tag) {
            const textarea = document.getElementById('new_template_body');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + tag + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            showToast(`Inserted ${tag}!`);
        }

        // Open Realistic Email Preview Modal
        function openPreviewModal(tpl) {
            document.getElementById('previewModalTitle').innerText = tpl.template_name;

            let subject = tpl.template_subject;
            let body = tpl.template_body;

            // Mock variables
            const samples = {
                '{customer_name}': 'Rahul Sharma',
                '{customer_email}': 'rahul.sharma@example.com',
                '{order_number}': 'ORD-98421',
                '{order_total}': '1,499.00',
                '{payment_method}': 'Razorpay UPI (Paid)',
                '{shipping_address}': 'Flat 402, Sunshine Heights, MG Road, Bengaluru, 560001',
                '{courier_name}': 'BlueDart Express',
                '{tracking_number}': 'BD784920119',
                '{tracking_url}': '#',
                '{otp_code}': '849201',
                '{site_name}': 'Printmont',
                '{site_url}': 'http://localhost:5173',
                '{support_email}': 'support@printmont.com',
                '{support_phone}': '+91 98765 43210',
                '{year}': new Date().getFullYear()
            };

            for (const [k, v] of Object.entries(samples)) {
                subject = subject.replaceAll(k, v);
                body = body.replaceAll(k, v);
            }

            document.getElementById('previewSubject').innerText = subject;
            document.getElementById('previewBody').innerHTML = body;

            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }

        // Open Send Test Modal
        function openTestModal(id, name) {
            document.getElementById('test_template_id').value = id;
            document.getElementById('test_template_name').value = name;
            const modal = new bootstrap.Modal(document.getElementById('sendTestModal'));
            modal.show();
        }

        // Handle Send Test Submission
        async function handleSendTest(e) {
            e.preventDefault();
            const btn = document.getElementById('sendTestBtn');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Dispatching...';

            const templateId = document.getElementById('test_template_id').value;
            const recipient = document.getElementById('test_recipient_email').value;

            try {
                const formData = new FormData();
                formData.append('action', 'send_template_test');
                formData.append('template_id', templateId);
                formData.append('test_recipient', recipient);

                const res = await fetch('email-templates.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('sendTestModal')).hide();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to dispatch test email', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    </script>
</body>
</html>