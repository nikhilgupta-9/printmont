<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/CategoryController.php';

$database = new Database();
$db = $database->getConnection();
$categoryController = new CategoryController($db);

$category_id = intval($_GET['id'] ?? 0);
$category_result = $categoryController->getCategoryById($category_id);

if ($category_result->num_rows == 0) {
    header("Location: view-categories.php");
    exit();
}

$category = $category_result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Category</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        body { opacity: 0; }
        .image-preview { max-width: 200px; max-height: 150px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
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
                            <h3><strong>Edit</strong> Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-categories.php" class="btn btn-secondary">Back to Categories</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-subtitle text-muted">Edit category information.</h6>
                                </div>
                                <div class="card-body">
                                    <form id="categoryForm" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="name">Category Name</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="parent_id">Parent Category</label>
                                                <select class="form-control" id="parent_id" name="parent_id">
                                                    <option value="">-- Select Parent Category --</option>
                                                    <?php
                                                    $mainCategories = $categoryController->getMainCategories();
                                                    while ($cat = $mainCategories->fetch_assoc()) {
                                                        if ($cat['id'] != $category_id) {
                                                            $selected = $cat['id'] == $category['parent_id'] ? 'selected' : '';
                                                            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . $cat['name'] . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter category description"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image">Category Image</label>
                                                <?php if ($category['image']): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo $category['image']; ?>" class="image-preview" id="currentImage">
                                                        <div class="form-text">Current image</div>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                <div id="imagePreview" class="image-preview"></div>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="icon">Category Icon</label>
                                                <?php if ($category['icon']): ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo $category['icon']; ?>" class="image-preview" id="currentIcon">
                                                        <div class="form-text">Current icon</div>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" id="icon" name="icon" accept="image/*">
                                                <div id="iconPreview" class="image-preview"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo $category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="display_order">Display Order</label>
                                                <input type="number" class="form-control" id="display_order"
                                                    name="display_order" value="<?php echo $category['display_order']; ?>">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label">&nbsp;</label>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                                        <?php echo isset($category['is_featured']) && $category['is_featured'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_featured">
                                                        Featured Category
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Category</button>
                                        <a href="view-categories.php" class="btn btn-secondary">Cancel</a>
                                        <button type="button" onclick="deleteCategory(<?php echo $category['id']; ?>)" class="btn btn-danger">Delete</button>
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
    <script src="js/main.js"></script>

    <script>
        // Image preview functionality
        document.getElementById("image").addEventListener("change", function(e) {
            const preview = document.getElementById("imagePreview");
            preview.innerHTML = "";
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.style.maxWidth = "100%";
                    img.style.maxHeight = "100%";
                    preview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        document.getElementById("icon").addEventListener("change", function(e) {
            const preview = document.getElementById("iconPreview");
            preview.innerHTML = "";
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    img.style.maxWidth = "100%";
                    img.style.maxHeight = "100%";
                    preview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Form submission
        document.getElementById("categoryForm").addEventListener("submit", async function(e){
            e.preventDefault();

            const formData = new FormData(this);
            formData.append("action", "update");

            try {
                const response = await fetch("api/category-api.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert("Category updated successfully!");
                } else {
                    alert("Error: " + result.message);
                }
            } catch(error) {
                console.error("Error:", error);
                alert("An error occurred while updating the category.");
            }
        });

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
                        window.location.href = "view-categories.php";
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