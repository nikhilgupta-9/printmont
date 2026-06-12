<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

$categoryController = new CategoryController();
$categories = $categoryController->getAllCategories();

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
    <title>Categories | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .category-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .category-item { border-left: 4px solid #007bff; margin: 5px 0; transition: all 0.3s ease; }
        .subcategory { margin-left: 30px; border-left-color: #28a745; }
        .sub-subcategory { margin-left: 60px; border-left-color: #fd7e14; }
        .level-0 { background-color: #ffffff; }
        .level-1 { background-color: #f8f9fa; }
        .level-2 { background-color: #e9ecef; }
        .category-item:hover { background-color: #f1f3f4; }
        .toggle-children { cursor: pointer; margin-right: 10px; }
        .children { display: block; }
        .children.collapsed { display: none; }
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
                            <h3><strong>Category</strong> Management</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-category.php" class="btn btn-primary">Add New Category</a>
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
                                    <h5 class="card-title">All Categories</h5>
                                    <h6 class="card-subtitle text-muted">Manage your product categories hierarchy.</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    function displayCategoriesTree($categories, $level = 0) {
                                        if (empty($categories)) {
                                            echo '<div class="text-center text-muted py-4">No categories found. <a href="add-category.php">Add your first category</a></div>';
                                            return;
                                        }

                                        foreach ($categories as $category) {
                                            $hasChildren = !empty($category['children']);
                                            $levelClass = 'level-' . $level;
                                            $indentClass = $level > 0 ? ($level == 1 ? 'subcategory' : 'sub-subcategory') : '';
                                            ?>
                                            <div class="category-item <?php echo $levelClass . ' ' . $indentClass; ?> p-3 rounded">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($hasChildren): ?>
                                                            <span class="toggle-children" onclick="toggleChildren(this)">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="toggle-children" style="visibility: hidden;">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($category['image']): ?>
                                                            <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                                                 class="category-image me-3" 
                                                                 alt="<?php echo htmlspecialchars($category['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="category-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                                <?php if ($category['icon']): ?>
                                                                    <i class="<?php echo htmlspecialchars($category['icon']); ?> text-muted"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-folder text-muted"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo $category['description'] ? htmlspecialchars($category['description']) : 'No description'; ?>
                                                                <br>
                                                                <strong>Slug:</strong> <?php echo htmlspecialchars($category['slug']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <?php if ($category['is_featured']): ?>
                                                            <span class="badge bg-warning">Featured</span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($category['status']); ?>
                                                        </span>
                                                        <span class="badge bg-info">Order: <?php echo $category['display_order']; ?></span>
                                                        <span class="badge bg-dark">Level: <?php echo $category['level']; ?></span>
                                                        <a href="edit-category.php?id=<?php echo $category['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                                                class="btn btn-sm btn-outline-danger" title="Delete"
                                                                <?php echo $hasChildren ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($hasChildren): ?>
                                                    <div class="children mt-2">
                                                        <?php displayCategoriesTree($category['children'], $level + 1); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                        }
                                    }
                                    
                                    displayCategoriesTree($categories);
                                    ?>
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
        function toggleChildren(element) {
            const childrenContainer = element.closest('.category-item').querySelector('.children');
            const icon = element.querySelector('i');
            
            if (childrenContainer.classList.contains('collapsed')) {
                childrenContainer.classList.remove('collapsed');
                icon.className = 'fas fa-chevron-down';
            } else {
                childrenContainer.classList.add('collapsed');
                icon.className = 'fas fa-chevron-right';
            }
        }

        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                window.location.href = 'delete-category.php?id=' + categoryId;
            }
        }

        // Initialize - collapse all subcategories
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.children').forEach(function(child) {
                if (child.querySelector('.children')) {
                    child.classList.add('collapsed');
                }
            });
        });
    </script>
</body>
</html>