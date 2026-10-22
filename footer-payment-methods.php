<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/FooterPaymentMethodController.php';
require_once 'controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$paymentController = new FooterPaymentMethodController($db);
$logoController = new LogoController($db);

// Handle POST actions (Create, Update, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'icon'          => trim($_POST['icon'] ?? 'fab fa-credit-card'),
            'display_order' => (int)($_POST['display_order'] ?? 1),
            'status'        => $_POST['status'] ?? 'active'
        ];

        if (!empty($data['name'])) {
            $ok = $paymentController->createPaymentMethod($data);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Payment method added successfully!' : 'Failed to add payment method.';
        } else {
            $_SESSION['error_message'] = 'Payment Method Name is required.';
        }
        header("Location: footer-payment-methods.php");
        exit;
    }

    if ($action === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'icon'          => trim($_POST['icon'] ?? 'fab fa-credit-card'),
            'display_order' => (int)($_POST['display_order'] ?? 1),
            'status'        => $_POST['status'] ?? 'active'
        ];

        if (!empty($data['name'])) {
            $ok = $paymentController->updatePaymentMethod($id, $data);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Payment method updated successfully!' : 'Failed to update payment method.';
        } else {
            $_SESSION['error_message'] = 'Payment Method Name is required.';
        }
        header("Location: footer-payment-methods.php");
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $ok = $paymentController->deletePaymentMethod($id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Payment method deleted successfully!' : 'Failed to delete payment method.';
        header("Location: footer-payment-methods.php");
        exit;
    }

    if ($action === 'toggle_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $item = $paymentController->getPaymentMethodById($id);
        if ($item) {
            $newStatus = ($item['status'] === 'active') ? 'inactive' : 'active';
            $item['status'] = $newStatus;
            $ok = $paymentController->updatePaymentMethod($id, $item);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Status changed to {$newStatus}!" : 'Failed to toggle status.';
        }
        header("Location: footer-payment-methods.php");
        exit;
    }
}

$paymentMethods = $paymentController->getAllPaymentMethods() ?: [];
$paymentOptions = $paymentController->getPaymentOptions();

// Dynamic Favicon (Rule 1)
$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';

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
    <title>Payment Methods | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f0f4f9; }
        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            background: #fff;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .header-card {
            background: linear-gradient(135deg, #0B53A1 0%, #083b73 100%);
            border-radius: 16px;
            color: #fff;
            padding: 24px 28px;
            box-shadow: 0 10px 25px rgba(11, 83, 161, 0.18);
            margin-bottom: 24px;
        }
        .pay-icon-preview {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #0B53A1;
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
                    
                    <!-- Header -->
                    <div class="header-card d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-3 bg-white bg-opacity-20 rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fa-solid fa-credit-card fa-lg"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-white mb-1">Footer Payment Methods &amp; Gateways</h3>
                                <p class="text-white-50 mb-0">Manage security &amp; payment badges displayed in the bottom footer strip.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="footer-management.php" class="btn btn-outline-light fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Footer Manager
                            </a>
                            <button type="button" class="btn btn-light fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="fa-solid fa-plus text-primary me-1"></i> Add Payment Badge
                            </button>
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

                    <!-- Payment Methods Table Card -->
                    <div class="custom-card">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Configured Payment Methods (<?php echo count($paymentMethods); ?>)</h5>
                            <span class="badge bg-success-subtle text-success">Live on Footer Strip</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Order</th>
                                            <th style="width: 70px;">Badge</th>
                                            <th>Method Name</th>
                                            <th>Icon Class</th>
                                            <th>Status</th>
                                            <th class="text-end" style="width: 160px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($paymentMethods)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-credit-card fa-2x mb-2 d-block opacity-50"></i>
                                                    No payment methods configured yet.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($paymentMethods as $item): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary rounded-pill px-2 py-1">
                                                            #<?php echo (int)$item['display_order']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="pay-icon-preview">
                                                            <i class="<?php echo htmlspecialchars($item['icon'] ?: 'fas fa-credit-card'); ?>"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong class="text-dark"><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($item['icon']); ?></code>
                                                    </td>
                                                    <td>
                                                        <form action="footer-payment-methods.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                            <button type="submit" class="btn btn-sm badge border-0 <?php echo $item['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>" style="cursor: pointer;">
                                                                <i class="fa-solid fa-circle-dot me-1"></i> <?php echo ucfirst($item['status']); ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                onclick='openEditModal(<?php echo json_encode($item); ?>)' title="Edit">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <form action="footer-payment-methods.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment method?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="footer-payment-methods.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-plus me-2"></i>Add Payment Method</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Method / Gateway Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Visa, UPI, Google Pay" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Icon Class (FontAwesome)</label>
                            <input type="text" name="icon" class="form-control" placeholder="e.g. fab fa-cc-visa" value="fab fa-cc-visa">
                            <small class="text-muted d-block mt-1">
                                Presets: <code>fab fa-cc-visa</code>, <code>fab fa-cc-mastercard</code>, <code>fas fa-credit-card</code>, <code>fab fa-cc-amex</code>, <code>fas fa-university</code>, <code>fas fa-mobile-alt</code>, <code>fas fa-wallet</code>
                            </small>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="<?php echo count($paymentMethods) + 1; ?>" min="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Add Payment Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="footer-payment-methods.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editPayId">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Payment Method</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Method / Gateway Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editPayName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Icon Class (FontAwesome)</label>
                            <input type="text" name="icon" id="editPayIcon" class="form-control">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" name="display_order" id="editPayOrder" class="form-control" min="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" id="editPayStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Update Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function openEditModal(item) {
            document.getElementById('editPayId').value = item.id;
            document.getElementById('editPayName').value = item.name;
            document.getElementById('editPayIcon').value = item.icon || '';
            document.getElementById('editPayOrder').value = item.display_order || 1;
            document.getElementById('editPayStatus').value = item.status || 'active';
            
            new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
        }
    </script>
</body>
</html>
