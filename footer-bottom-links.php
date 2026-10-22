<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/FooterBottomLinkController.php';
require_once 'controllers/LogoController.php';

$database = new Database();
$db = $database->getConnection();
$bottomLinkController = new FooterBottomLinkController($db);
$logoController = new LogoController($db);

// Handle POST actions (Create, Update, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'title'      => trim($_POST['title'] ?? ''),
            'url'        => trim($_POST['url'] ?? ''),
            'icon'       => trim($_POST['icon'] ?? 'fas fa-link'),
            'link_order' => (int)($_POST['link_order'] ?? 1),
            'status'     => $_POST['status'] ?? 'active'
        ];

        if (!empty($data['title']) && !empty($data['url'])) {
            $ok = $bottomLinkController->createBottomLink($data);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Bottom feature link created successfully!' : 'Failed to create bottom link.';
        } else {
            $_SESSION['error_message'] = 'Title and URL are required fields.';
        }
        header("Location: footer-bottom-links.php");
        exit;
    }

    if ($action === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $data = [
            'title'      => trim($_POST['title'] ?? ''),
            'url'        => trim($_POST['url'] ?? ''),
            'icon'       => trim($_POST['icon'] ?? 'fas fa-link'),
            'link_order' => (int)($_POST['link_order'] ?? 1),
            'status'     => $_POST['status'] ?? 'active'
        ];

        if (!empty($data['title']) && !empty($data['url'])) {
            $ok = $bottomLinkController->updateBottomLink($id, $data);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Bottom feature link updated successfully!' : 'Failed to update bottom link.';
        } else {
            $_SESSION['error_message'] = 'Title and URL are required fields.';
        }
        header("Location: footer-bottom-links.php");
        exit;
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $ok = $bottomLinkController->deleteBottomLink($id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Bottom feature link deleted successfully!' : 'Failed to delete bottom link.';
        header("Location: footer-bottom-links.php");
        exit;
    }

    if ($action === 'toggle_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $item = $bottomLinkController->getBottomLinkById($id);
        if ($item) {
            $newStatus = ($item['status'] === 'active') ? 'inactive' : 'active';
            $item['status'] = $newStatus;
            $ok = $bottomLinkController->updateBottomLink($id, $item);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Status changed to {$newStatus}!" : 'Failed to toggle status.';
        }
        header("Location: footer-bottom-links.php");
        exit;
    }
}

$bottomLinks = $bottomLinkController->getAllBottomLinks() ?: [];

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
    <title>Bottom Feature Links | Printmont Admin</title>
    
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
        .icon-preview-box {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
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
                                <i class="fa-solid fa-store fa-lg"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-white mb-1">Bottom Feature Links Management</h3>
                                <p class="text-white-50 mb-0">Manage bottom bar feature shortcuts (e.g. Become a Seller, Business Solutions, Coins, Help Center).</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="footer-management.php" class="btn btn-outline-light fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Footer Manager
                            </a>
                            <button type="button" class="btn btn-light fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addLinkModal">
                                <i class="fa-solid fa-plus text-primary me-1"></i> Add Feature Link
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

                    <!-- Bottom Links Table Card -->
                    <div class="custom-card">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Active Bottom Feature Links (<?php echo count($bottomLinks); ?>)</h5>
                            <span class="badge bg-primary-subtle text-primary">Connected to Frontend &amp; API</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Order</th>
                                            <th style="width: 60px;">Icon</th>
                                            <th>Title</th>
                                            <th>Target Route / URL</th>
                                            <th>Status</th>
                                            <th class="text-end" style="width: 160px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bottomLinks)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fa-solid fa-link-slash fa-2x mb-2 d-block opacity-50"></i>
                                                    No bottom feature links configured yet.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($bottomLinks as $item): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary rounded-pill px-2 py-1">
                                                            #<?php echo (int)$item['link_order']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="icon-preview-box">
                                                            <i class="<?php echo htmlspecialchars($item['icon'] ?: 'fas fa-link'); ?>"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong class="text-dark"><?php echo htmlspecialchars($item['title']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <code class="bg-light px-2 py-1 rounded text-primary"><?php echo htmlspecialchars($item['url']); ?></code>
                                                    </td>
                                                    <td>
                                                        <form action="footer-bottom-links.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                                            <button type="submit" class="btn btn-sm badge border-0 <?php echo $item['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>" style="cursor: pointer;">
                                                                <i class="fa-solid fa-circle-dot me-1"></i> <?php echo ucfirst($item['status']); ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                                onclick='openEditModal(<?php echo json_encode($item); ?>)' title="Edit Link">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <form action="footer-bottom-links.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this bottom feature link?');">
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

    <!-- Add Link Modal -->
    <div class="modal fade" id="addLinkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="footer-bottom-links.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-plus me-2"></i>Add Bottom Feature Link</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Feature Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Become a Seller" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Route / URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" class="form-control" placeholder="e.g. /become-a-seller" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">FontAwesome Icon Class</label>
                            <input type="text" name="icon" class="form-control" placeholder="e.g. fas fa-store" value="fas fa-store">
                            <small class="text-muted">Examples: <code>fas fa-store</code>, <code>fas fa-bullhorn</code>, <code>fas fa-coins</code>, <code>fas fa-question-circle</code></small>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" name="link_order" class="form-control" value="<?php echo count($bottomLinks) + 1; ?>" min="1">
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
                        <button type="submit" class="btn btn-primary px-4">Create Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Link Modal -->
    <div class="modal fade" id="editLinkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="footer-bottom-links.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editLinkId">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Bottom Feature Link</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Feature Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="editLinkTitle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Route / URL <span class="text-danger">*</span></label>
                            <input type="text" name="url" id="editLinkUrl" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">FontAwesome Icon Class</label>
                            <input type="text" name="icon" id="editLinkIcon" class="form-control">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" name="link_order" id="editLinkOrder" class="form-control" min="1">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" id="editLinkStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Update Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function openEditModal(item) {
            document.getElementById('editLinkId').value = item.id;
            document.getElementById('editLinkTitle').value = item.title;
            document.getElementById('editLinkUrl').value = item.url;
            document.getElementById('editLinkIcon').value = item.icon || '';
            document.getElementById('editLinkOrder').value = item.link_order || 1;
            document.getElementById('editLinkStatus').value = item.status || 'active';
            
            new bootstrap.Modal(document.getElementById('editLinkModal')).show();
        }
    </script>
</body>
</html>
