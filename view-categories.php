<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/CategoryController.php';

$database = new Database();
$db = $database->getConnection();
$categoryController = new CategoryController($db);
$categories = $categoryController->getAllCategories();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Categories</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        body { opacity: 0; }
        .category-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .subcategory { margin-left: 30px; background-color: #f8f9fa; }
        .category-item { border-left: 4px solid #007bff; padding: 10px; margin: 5px 0; }
        .subcategory .category-item { border-left-color: #28a745; }
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
                            <h3><strong>View</strong> Categories</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="add-category.php" class="btn btn-primary">Add New Category</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Categories</h5>
                                    <h6 class="card-subtitle text-muted">Manage your product categories.</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    function displayCategories($categories, $parent_id = null, $level = 0) {
                                        $has_children = false;
                                        
                                        while ($category = $categories->fetch_assoc()) {
                                            if ($category['parent_id'] == $parent_id) {
                                                $has_children = true;
                                                ?>
                                                <div class="category-item <?php echo $level > 0 ? 'subcategory' : ''; ?>">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($category['image']): ?>
                                                                <img src="<?php echo $category['image']; ?>" class="category-image me-3" alt="<?php echo $category['name']; ?>">
                                                            <?php else: ?>
                                                                <div class="category-image me-3 bg-light d-flex align-items-center justify-content-center">
                                                                    <i class="fas fa-folder text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div>
                                                                <h6 class="mb-1"><?php echo $category['name']; ?></h6>
                                                                <small class="text-muted">
                                                                    <?php echo $category['description'] ?: 'No description'; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <?php if (isset($category['is_featured']) && $category['is_featured']): ?>
                                                                <span class="badge bg-warning">Featured</span>
                                                            <?php endif; ?>
                                                            <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($category['status']); ?>
                                                            </span>
                                                            <span class="badge bg-info">Order: <?php echo $category['display_order']; ?></span>
                                                            <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                                // Reset pointer and display children
                                                $categories->data_seek(0);
                                                displayCategories($categories, $category['id'], $level + 1);
                                            }
                                        }
                                        
                                        if (!$has_children && $level == 0) {
                                            echo '<div class="text-center text-muted py-4">No categories found. <a href="add-category.php">Add your first category</a></div>';
                                        }
                                    }
                                    
                                    // Reset pointer before displaying
                                    $categories->data_seek(0);
                                    displayCategories($categories);
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
    <script src="js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

    <script>
        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category?')) {
                const formData = new FormData();
                formData.append("action", "delete");
                formData.append("id", categoryId);

                fetch("api/category-api.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert("Category deleted successfully!");
                        location.reload();
                    } else {
                        alert("Error: " + result.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while deleting the category.");
                });
            }
        }
    </script>
</body>
</html>