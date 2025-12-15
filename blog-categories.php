<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogCategoryController.php';

$categoryController = new BlogCategoryController();

// Handle actions
if (isset($_GET['delete_id'])) {
    try {
        $result = $categoryController->deleteCategory($_GET['delete_id']);
        if ($result) {
            $_SESSION['message'] = 'Category deleted successfully!';
            $_SESSION['message_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    header('Location: blog-categories.php');
    exit();
}

if (isset($_GET['status_id']) && isset($_GET['status'])) {
    $result = $categoryController->updateStatus($_GET['status_id'], $_GET['status']);
    if ($result) {
        $_SESSION['message'] = 'Category status updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating category status!';
        $_SESSION['message_type'] = 'error';
    }
    header('Location: blog-categories.php');
    exit();
}

$categories = $categoryController->getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blog Categories | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
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
                            <h3><strong>Blog</strong> Categories</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-blog-category.php" class="btn btn-primary">
                                <i class="align-middle me-1" data-feather="plus"></i>
                                Add New Category
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">All Categories</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Messages -->
                                    <?php if (isset($_SESSION['message'])): ?>
                                        <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                                            <?php echo $_SESSION['message']; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                                    <?php endif; ?>

                                    <!-- Categories Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Slug</th>
                                                    <th>Status</th>
                                                    <th>Description</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($categories)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-4">
                                                            No categories found. Create your first category!
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($categories as $category): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                                    <?php echo ucfirst($category['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($category['description']): ?>
                                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?><?php echo strlen($category['description']) > 100 ? '...' : ''; ?></small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No description</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="edit-blog-category.php?id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                                                        <i class="align-middle" data-feather="edit-2"></i>
                                                                    </a>
                                                                    <?php if ($category['status'] == 'active'): ?>
                                                                        <a href="blog-categories.php?status_id=<?php echo $category['id']; ?>&status=inactive" class="btn btn-outline-warning" title="Deactivate">
                                                                            <i class="align-middle" data-feather="pause"></i>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="blog-categories.php?status_id=<?php echo $category['id']; ?>&status=active" class="btn btn-outline-success" title="Activate">
                                                                            <i class="align-middle" data-feather="play"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <a href="blog-categories.php?delete_id=<?php echo $category['id']; ?>" 
                                                                       class="btn btn-outline-danger" 
                                                                       onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                                        <i class="align-middle" data-feather="trash-2"></i>
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
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>