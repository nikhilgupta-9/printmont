<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid category ID!";
    header("Location: view-categories.php");
    exit();
}

$categoryId = (int)$_GET['id'];
$categoryController = new CategoryController();
$category = $categoryController->getCategoryById($categoryId);

if (!$category) {
    $_SESSION['error_message'] = "Category not found!";
    header("Location: view-categories.php");
    exit();
}

// Handle delete confirmation
if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        // Check if category has subcategories
        $subcategories = $categoryController->getSubcategories($categoryId);
        $hasChildren = !empty($subcategories);
        
        if ($hasChildren && (!isset($_POST['delete_subcategories']) || $_POST['delete_subcategories'] != '1')) {
            $_SESSION['error_message'] = "This category has subcategories. You must delete them first or select the option to delete all subcategories.";
            header("Location: delete-category.php?id=" . $categoryId);
            exit();
        }

        // Delete category image if exists
        if (!empty($category['image']) && file_exists($category['image'])) {
            unlink($category['image']);
        }

        // Delete subcategories if requested
        if (isset($_POST['delete_subcategories']) && $_POST['delete_subcategories'] == '1' && $hasChildren) {
            foreach ($subcategories as $subcategory) {
                // Delete subcategory image if exists
                if (!empty($subcategory['image']) && file_exists($subcategory['image'])) {
                    unlink($subcategory['image']);
                }
                $categoryController->deleteCategory($subcategory['id']);
            }
        }

        // Delete the category
        if ($categoryController->deleteCategory($categoryId)) {
            $_SESSION['success_message'] = "Category '{$category['name']}' deleted successfully!";
            header("Location: view-categories.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to delete category. Please try again.";
            header("Location: delete-category.php?id=" . $categoryId);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: delete-category.php?id=" . $categoryId);
        exit();
    }
}

// Get subcategories for warning display
$subcategories = $categoryController->getSubcategories($categoryId);
$hasChildren = !empty($subcategories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Delete Category | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .danger-card { border-left: 4px solid #dc3545; }
        .category-details { background-color: #f8f9fa; border-radius: 4px; padding: 15px; }
        .subcategory-list { max-height: 200px; overflow-y: auto; }
        .warning-icon { color: #dc3545; font-size: 3rem; }
        .detail-item { margin-bottom: 8px; }
        .detail-label { font-weight: 500; color: #6c757d; }
        .detail-value { color: #212529; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Delete</strong> Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-categories.php" class="btn btn-secondary">View Categories</a>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-6">
                            <div class="card danger-card">
                                <div class="card-header">
                                    <h5 class="card-title text-danger mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Confirm Deletion
                                    </h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-4">
                                        <i class="fas fa-exclamation-circle warning-icon"></i>
                                        <h4 class="text-danger mt-3">Are you sure you want to delete this category?</h4>
                                        <p class="text-muted">This action cannot be undone. All category data will be permanently removed.</p>
                                    </div>

                                    <!-- Category Details -->
                                    <div class="category-details mb-4 text-start">
                                        <div class="detail-item">
                                            <span class="detail-label">Category Name:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($category['name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Slug:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($category['slug']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Status:</span>
                                            <span class="detail-value">
                                                <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Level:</span>
                                            <span class="detail-value">Level <?php echo $category['level']; ?></span>
                                        </div>
                                        <?php if ($category['parent_id'] > 0): 
                                            $parentCategory = $categoryController->getCategoryById($category['parent_id']);
                                        ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Parent Category:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($parentCategory['name'] ?? 'Unknown'); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Subcategories Warning -->
                                    <?php if ($hasChildren): ?>
                                    <div class="alert alert-warning text-start">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Warning: This category has subcategories!
                                        </h6>
                                        <p class="mb-2">The following subcategories will be affected:</p>
                                        <div class="subcategory-list">
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($subcategories as $subcat): ?>
                                                    <li class="mb-1">
                                                        <i class="fas fa-folder text-warning me-2"></i>
                                                        <?php echo htmlspecialchars($subcat['name']); ?>
                                                        <span class="badge bg-<?php echo $subcat['status'] == 'active' ? 'success' : 'secondary'; ?> ms-2">
                                                            <?php echo ucfirst($subcat['status']); ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <hr>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="delete_subcategories" name="delete_subcategories" value="1">
                                            <label class="form-check-label text-danger fw-bold" for="delete_subcategories">
                                                Delete all <?php echo count($subcategories); ?> subcategories along with this category
                                            </label>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Current Image -->
                                    <?php if (!empty($category['image']) && file_exists($category['image'])): ?>
                                    <div class="alert alert-info text-start">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-image me-2"></i>
                                            Category Image
                                        </h6>
                                        <div class="text-center mt-2">
                                            <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 150px; max-height: 150px;"
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                 onerror="this.style.display='none'">
                                            <p class="small text-muted mt-2 mb-0">This image will be permanently deleted</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST" id="deleteForm">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                                                <i class="fas fa-trash me-2"></i>Yes, Delete Category
                                            </button>
                                            <a href="view-categories.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Additional Warning -->
                            <div class="card mt-3">
                                <div class="card-body text-center">
                                    <div class="alert alert-light border">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <strong>Note:</strong> This action will permanently remove the category 
                                        <?php if ($hasChildren): ?>and its subcategories<?php endif; ?> from the system.
                                    </div>
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
    <script>
        // Form validation for subcategories
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            const hasChildren = <?php echo $hasChildren ? 'true' : 'false'; ?>;
            const deleteSubcategories = document.getElementById('delete_subcategories');
            
            if (hasChildren && (!deleteSubcategories || !deleteSubcategories.checked)) {
                e.preventDefault();
                alert('Please confirm that you want to delete all subcategories by checking the checkbox.');
                return false;
            }
            
            if (!confirm('Are you absolutely sure? This action cannot be undone!')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Auto-confirm when delete subcategories is checked
        const deleteSubcategoriesCheckbox = document.getElementById('delete_subcategories');
        if (deleteSubcategoriesCheckbox) {
            deleteSubcategoriesCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    const subcategoryCount = <?php echo count($subcategories); ?>;
                    if (!confirm(`You are about to delete ${subcategoryCount} subcategory(ies) along with the main category. This action cannot be undone. Continue?`)) {
                        this.checked = false;
                    }
                }
            });
        }
    </script>
</body>
</html>