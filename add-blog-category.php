<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogCategoryController.php';

$blogController = new BlogCategoryController();

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_category'])) {
        $data = [
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'description' => $_POST['description'],
            'status' => $_POST['status']
        ];
        
        try {
            if ($blogController->createCategory($data)) {
                $_SESSION['success_message'] = "Category created successfully!";
                header("Location: blog-categories.php");
                exit;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? $error_message ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Category | Printmont</title>
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
                            <h3><strong>Add</strong> Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="blog-categories.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Categories
                            </a>
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
                        <div class="col-12 col-md-8 col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Add Category</h5>
                                    <h6 class="card-subtitle text-muted">Create a new blog category.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Category Name *</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Slug *</label>
                                            <input type="text" class="form-control" name="slug" 
                                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" required>
                                            <small class="form-text text-muted">
                                                URL-friendly version of the name (lowercase, hyphens instead of spaces)
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <button type="submit" name="add_category" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Add Category
                                            </button>
                                            <a href="blog-categories.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>