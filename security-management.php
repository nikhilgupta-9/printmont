<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SecurityController.php';

$controller = new SecurityController();

// POST handling lives here (the page includes side-navbar.php, which is where
// the admin auth check runs), so no separate unguarded handler file.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $result = $controller->addSection($_POST);
    } elseif ($action === 'delete') {
        $result = $controller->deleteSection($_POST['id'] ?? 0);
    } else {
        $result = $controller->updateSection($_POST['id'] ?? 0, $_POST);
    }

    $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
    header('Location: security-management.php');
    exit();
}

$sections = $controller->getAllSections();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security Page | Printmont</title>
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .section-box { border: 1px solid #ddd; background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row mb-3">
                        <div class="col-auto">
                            <h3><strong>Security</strong> Page</h3>
                            <p class="text-muted mb-0">
                                Shown on the storefront Security page and served by
                                <code>api/security-api.php</code>.
                            </p>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add new -->
                    <div class="section-box">
                        <h5 class="fw-bold mb-3">Add a section</h5>
                        <form method="POST" action="security-management.php">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="mb-3 col-md-9">
                                    <label class="form-label">Heading</label>
                                    <input type="text" class="form-control" name="heading" required
                                           placeholder="e.g. Is making online payment secure on Printmont?">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order"
                                           value="<?php echo count($sections) + 1; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea class="form-control security-richtext" id="content-new"
                                          name="content" rows="6"></textarea>
                            </div>
                            <button class="btn btn-success">Add Section</button>
                        </form>
                    </div>

                    <!-- Existing -->
                    <?php if (empty($sections)): ?>
                        <div class="section-box text-center py-4">
                            <h5>No sections yet</h5>
                            <p class="text-muted mb-0">Add one above to build the Security page.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections as $s): ?>
                            <div class="section-box">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($s['heading']); ?></h5>
                                    <span class="status-badge status-<?php echo htmlspecialchars($s['status']); ?>">
                                        <?php echo ucfirst($s['status']); ?>
                                    </span>
                                </div>

                                <form method="POST" action="security-management.php">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">

                                    <div class="row">
                                        <div class="mb-3 col-md-7">
                                            <label class="form-label">Heading</label>
                                            <input type="text" class="form-control" name="heading" required
                                                   value="<?php echo htmlspecialchars($s['heading']); ?>">
                                        </div>
                                        <div class="mb-3 col-md-2">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" class="form-control" name="sort_order"
                                                   value="<?php echo (int) $s['sort_order']; ?>">
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active" <?php echo $s['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $s['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Content</label>
                                        <textarea class="form-control security-richtext"
                                                  id="content-<?php echo (int) $s['id']; ?>"
                                                  name="content" rows="6"><?php echo htmlspecialchars($s['content'] ?? ''); ?></textarea>
                                    </div>

                                    <button class="btn btn-primary">Save Changes</button>
                                </form>

                                <form method="POST" action="security-management.php" class="d-inline"
                                      onsubmit="return confirm('Delete this section? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                    <button class="btn btn-outline-danger mt-2">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <!-- Same CKEditor build the rest of the admin uses. -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        (function () {
            if (typeof CKEDITOR === 'undefined') return;

            const CONFIG = {
                height: 200,
                removePlugins: 'elementspath',
                toolbar: [
                    { name: 'styles', items: ['Format'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'tools', items: ['Maximize'] },
                    { name: 'document', items: ['Source'] }
                ]
            };

            // Every editor is visible on load here (no tabs), so plain init is fine.
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.security-richtext').forEach(function (ta) {
                    if (!CKEDITOR.instances[ta.id]) CKEDITOR.replace(ta.id, CONFIG);
                });
            });

            // Push editor content back into the textarea before the form posts.
            document.addEventListener('submit', function (e) {
                if (!e.target.matches('form')) return;
                e.target.querySelectorAll('.security-richtext').forEach(function (ta) {
                    const instance = CKEDITOR.instances[ta.id];
                    if (instance) ta.value = instance.getData();
                });
            }, true);
        })();
    </script>
</body>

</html>
