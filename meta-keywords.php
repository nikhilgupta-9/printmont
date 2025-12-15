<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SeoController.php';

$seoController = new SeoController();
$metaKeywords = $seoController->getAllMetaKeywords();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_meta'])) {
        $data = [
            'page_url' => $_POST['page_url'],
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'keywords' => $_POST['keywords']
        ];
        
        if ($seoController->createMetaKeyword($data)) {
            $_SESSION['success_message'] = "Meta keywords added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add meta keywords.";
        }
        header("Location: meta-keywords.php");
        exit;
    }
}

// Check for messages
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
    <title>Meta Keywords | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
        .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Meta</strong> Keywords</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMetaModal">
                                <i class="fas fa-plus"></i> Add Meta
                            </button>
                        </div>
                    </div>

                    <!-- Messages -->
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

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Meta Keywords & Descriptions</h5>
                                    <h6 class="card-subtitle text-muted">Manage SEO meta tags for different pages.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($metaKeywords)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                            <h5>No Meta Keywords</h5>
                                            <p class="text-muted">Get started by adding your first meta keywords configuration.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMetaModal">
                                                <i class="fas fa-plus"></i> Add Meta
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Page URL</th>
                                                        <th>Meta Title</th>
                                                        <th>Meta Description</th>
                                                        <th>Keywords</th>
                                                        <th>Created</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($metaKeywords as $meta): ?>
                                                        <tr>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($meta['page_url']); ?></code>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($meta['meta_title']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate-2" title="<?php echo htmlspecialchars($meta['meta_description']); ?>">
                                                                    <?php echo htmlspecialchars($meta['meta_description']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate-2" title="<?php echo htmlspecialchars($meta['keywords']); ?>">
                                                                    <?php echo htmlspecialchars($meta['keywords']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($meta['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td class="action-buttons">
                                                                <a href="edit-meta-keywords.php?id=<?php echo $meta['id']; ?>" 
                                                                   class="btn btn-sm btn-warning" title="Edit">
                                                                   <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="delete-meta-keywords.php?id=<?php echo $meta['id']; ?>" 
                                                                   class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this meta configuration?')"
                                                                   title="Delete">
                                                                   <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Meta Modal -->
    <div class="modal fade" id="addMetaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Meta Keywords</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Page URL *</label>
                                <input type="text" class="form-control" name="page_url" 
                                       placeholder="/about, /contact, /products" required>
                                <small class="form-text text-muted">Relative URL path without domain</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meta Title *</label>
                                <input type="text" class="form-control" name="meta_title" 
                                       placeholder="Page Title - Website Name" required maxlength="60">
                                <small class="form-text text-muted">Max 60 characters</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description *</label>
                            <textarea class="form-control" name="meta_description" rows="3" 
                                      placeholder="Brief description of the page..." required maxlength="160"></textarea>
                            <small class="form-text text-muted">Max 160 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keywords</label>
                            <textarea class="form-control" name="keywords" rows="3" 
                                      placeholder="keyword1, keyword2, keyword3"></textarea>
                            <small class="form-text text-muted">Comma separated keywords</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_meta" class="btn btn-primary">Add Meta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>