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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$template = $emailController->getTemplateById($id);

if (!$template) {
    $_SESSION['error_message'] = "Email template not found!";
    header("Location: email-templates.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'template_name'    => trim($_POST['template_name'] ?? ''),
        'template_subject' => trim($_POST['template_subject'] ?? ''),
        'template_body'    => trim($_POST['template_body'] ?? ''),
        'template_type'    => in_array($_POST['template_type'] ?? '', ['system', 'custom']) ? $_POST['template_type'] : 'system',
        'status'           => ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive'
    ];
    
    if (!empty($data['template_name']) && !empty($data['template_subject']) && !empty($data['template_body'])) {
        if ($emailController->updateTemplate($id, $data)) {
            $_SESSION['success_message'] = "Email template \"{$data['template_name']}\" updated successfully!";
            header("Location: email-templates.php");
            exit;
        } else {
            $error_message = "Failed to update email template in database.";
        }
    } else {
        $error_message = "Please fill in all required fields (Template Name, Subject, and Body).";
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $error_message ?? ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Email Template | Printmont Admin</title>

    <!-- Rule 1: Dynamic Brand Favicon -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .var-badge {
            cursor: pointer;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-family: monospace;
            display: inline-block;
            margin: 3px;
            transition: all 0.2s ease;
        }

        .var-badge:hover {
            background-color: #0B53A1;
            color: #ffffff;
            border-color: #0B53A1;
            transform: scale(1.04);
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
        }

        .custom-card-body {
            padding: 22px;
        }

        /* Toast notifications */
        #toastContainer {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
        }

        .pm-toast {
            min-width: 280px;
            padding: 12px 18px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
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
                    
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Email Template</h3>
                            <p class="text-muted mb-0 small">Modify template subject line, HTML content, and dynamic placeholder tags.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <a href="email-templates.php" class="btn btn-outline-secondary btn-sm shadow-sm fw-semibold me-2">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Templates
                            </a>
                            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm fw-semibold" onclick="previewLive()">
                                <i class="fa-solid fa-eye me-1"></i> Live Preview
                            </button>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-danger"></i>
                                <div><?= htmlspecialchars($error_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-4">
                            <!-- Left: Form Inputs (8 cols) -->
                            <div class="col-lg-8">
                                <div class="custom-card mb-4">
                                    <div class="custom-card-header">
                                        <h5 class="fw-bold text-dark mb-0">Template Details</h5>
                                    </div>
                                    <div class="custom-card-body">
                                        
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-semibold text-secondary">Template Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="template_name" id="template_name" value="<?= htmlspecialchars($template['template_name']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-semibold text-secondary">Template Type</label>
                                                <select class="form-select" name="template_type">
                                                    <option value="system" <?= $template['template_type'] === 'system' ? 'selected' : '' ?>>System (Transactional)</option>
                                                    <option value="custom" <?= $template['template_type'] === 'custom' ? 'selected' : '' ?>>Custom (Marketing)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?= $template['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="inactive" <?= $template['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Email Subject Line <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="template_subject" id="template_subject" value="<?= htmlspecialchars($template['template_subject']) ?>" required>
                                            <div class="form-text small">Supports variables like <code>#{order_number}</code>, <code>{customer_name}</code>, <code>{otp_code}</code></div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="form-label small fw-semibold text-secondary mb-0">Email HTML Body <span class="text-danger">*</span></label>
                                                <span class="text-muted small">HTML Formatted</span>
                                            </div>
                                            <textarea class="form-control font-monospace" name="template_body" id="template_body" rows="18" required style="font-size: 13px; line-height: 1.5;"><?= htmlspecialchars($template['template_body']) ?></textarea>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between pt-2">
                                            <a href="email-templates.php" class="btn btn-light">Cancel</a>
                                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Right: Variable Tag Helper & Cheat Sheet (4 cols) -->
                            <div class="col-lg-4">
                                <div class="custom-card mb-4 sticky-top" style="top: 20px;">
                                    <div class="custom-card-header bg-light">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-tags text-primary me-2"></i>Dynamic Variables Cheat Sheet</h6>
                                        <small class="text-muted">Click any variable tag to insert or copy</small>
                                    </div>
                                    <div class="custom-card-body">
                                        
                                        <div class="mb-3">
                                            <div class="small fw-bold text-dark mb-1">Customer & Account</div>
                                            <span class="var-badge" onclick="copyTag('{customer_name}')">{customer_name}</span>
                                            <span class="var-badge" onclick="copyTag('{customer_email}')">{customer_email}</span>
                                            <span class="var-badge" onclick="copyTag('{customer_phone}')">{customer_phone}</span>
                                            <span class="var-badge" onclick="copyTag('{otp_code}')">{otp_code}</span>
                                        </div>

                                        <div class="mb-3">
                                            <div class="small fw-bold text-dark mb-1">Orders & Shipping</div>
                                            <span class="var-badge" onclick="copyTag('{order_number}')">{order_number}</span>
                                            <span class="var-badge" onclick="copyTag('{order_total}')">{order_total}</span>
                                            <span class="var-badge" onclick="copyTag('{payment_method}')">{payment_method}</span>
                                            <span class="var-badge" onclick="copyTag('{shipping_address}')">{shipping_address}</span>
                                            <span class="var-badge" onclick="copyTag('{courier_name}')">{courier_name}</span>
                                            <span class="var-badge" onclick="copyTag('{tracking_number}')">{tracking_number}</span>
                                            <span class="var-badge" onclick="copyTag('{tracking_url}')">{tracking_url}</span>
                                        </div>

                                        <div class="mb-3">
                                            <div class="small fw-bold text-dark mb-1">Store & System</div>
                                            <span class="var-badge" onclick="copyTag('{site_name}')">{site_name}</span>
                                            <span class="var-badge" onclick="copyTag('{site_url}')">{site_url}</span>
                                            <span class="var-badge" onclick="copyTag('{support_email}')">{support_email}</span>
                                            <span class="var-badge" onclick="copyTag('{support_phone}')">{support_phone}</span>
                                            <span class="var-badge" onclick="copyTag('{year}')">{year}</span>
                                        </div>

                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="small fw-bold text-dark mb-1"><i class="fa-solid fa-lightbulb text-warning me-1"></i> Pro Tip</div>
                                            <p class="text-muted small mb-0" style="font-size: 11.5px;">
                                                All variable tags are automatically populated at runtime when transactional emails are dispatched to customers.
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Live Preview Modal -->
    <div class="modal fade" id="livePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fs-6"><i class="fa-solid fa-envelope-open-text me-2"></i>Email Rendering Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="mb-3 p-3 bg-white rounded border">
                        <div class="small text-muted">Subject:</div>
                        <div class="fw-bold fs-6 text-dark" id="previewSubjectText"></div>
                    </div>
                    <div class="bg-white rounded border p-3 shadow-xs" id="previewBodyContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Preview</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function copyTag(tag) {
            navigator.clipboard.writeText(tag);
            showToast(`Copied ${tag} to clipboard!`);
        }

        function showToast(msg) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'pm-toast';
            toast.innerHTML = `<i class="fa-solid fa-circle-check text-success"></i> ${msg}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        function previewLive() {
            let subject = document.getElementById('template_subject').value;
            let body = document.getElementById('template_body').value;

            // Replace sample tags
            const samples = {
                '{customer_name}': 'Rahul Sharma',
                '{customer_email}': 'rahul.sharma@example.com',
                '{order_number}': 'ORD-98421',
                '{order_total}': '1,499.00',
                '{payment_method}': 'UPI (Paid)',
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

            document.getElementById('previewSubjectText').innerText = subject;
            document.getElementById('previewBodyContainer').innerHTML = body;

            const modal = new bootstrap.Modal(document.getElementById('livePreviewModal'));
            modal.show();
        }
    </script>
</body>
</html>