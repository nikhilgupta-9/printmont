<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/CategoryController.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <link rel="canonical" href="index.html" />

    <title>Category Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        body {
            opacity: 0;
        }
        .image-preview {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-120946860-10"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'UA-120946860-10', { 'anonymize_ip': true });
    </script>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php
        include_once "includes/side-navbar.php";
        ?>

        <div class="main">
            <?php
            include_once "includes/top-navbar.php";
            ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Category</strong> Management</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="#" class="btn btn-light bg-white me-2">Help</a>
                            <a href="view-categories.php" class="btn btn-primary">View Categories</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-subtitle text-muted">Add or edit category information.</h6>
                                </div>
                                <div class="card-body">
                                    <form id="categoryForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="name">Category Name</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    placeholder="Enter category name" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="parent_id">Parent Category</label>
                                                <select class="form-control" id="parent_id" name="parent_id">
                                                    <option value="">-- Select Parent Category --</option>
                                                    <?php
                                                    // Load parent categories
                                                    $database = new Database();
                                                    $db = $database->getConnection();
                                                    $categoryController = new CategoryController($db);
                                                    $mainCategories = $categoryController->getMainCategories();
                                                    
                                                    while ($category = $mainCategories->fetch_assoc()) {
                                                        echo '<option value="' . $category['id'] . '">' . $category['name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter category description"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image">Category Image</label>
                                                <input type="file" class="form-control" id="image" name="image"
                                                    accept="image/*">
                                                <div id="imagePreview" class="image-preview"></div>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="icon">Category Icon</label>
                                                <input type="file" class="form-control" id="icon" name="icon"
                                                    accept="image/*">
                                                <div id="iconPreview" class="image-preview"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="display_order">Display Order</label>
                                                <input type="number" class="form-control" id="display_order"
                                                    name="display_order" placeholder="Enter display order" value="0">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label">&nbsp;</label>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                                    <label class="form-check-label" for="is_featured">
                                                        Featured Category
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <button type="reset" class="btn btn-secondary">Reset</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php
            include_once "includes/footer.php";
            ?>
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
            formData.append("action", "create");

            try {
                const response = await fetch("api/category-api.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert("Category created successfully!");
                    this.reset();
                    document.getElementById("imagePreview").innerHTML = "";
                    document.getElementById("iconPreview").innerHTML = "";
                } else {
                    alert("Error: " + result.message);
                }
            } catch(error) {
                console.error("Error:", error);
                alert("An error occurred while creating the category.");
            }
        });
    </script>

</body>
</html>