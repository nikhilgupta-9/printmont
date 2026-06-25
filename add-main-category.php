<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

$categoryController = new CategoryController();

// Handle form submission
if ($_POST) {
    try {
        $image_path = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/category/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $file_size = $_FILES['image']['size'];
                if ($file_size <= 5 * 1024 * 1024) {
                    $new_file_name = uniqid('category_', true) . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        $image_path = $destination;
                    } else {
                        throw new Exception("Failed to upload image.");
                    }
                } else {
                    throw new Exception("Image size too large. Maximum size is 5MB.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP are allowed.");
            }
        }

        $data = [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug']),
            'description' => trim($_POST['description']),
            'parent_id' => 0, // Main category always has parent_id = 0
            'image' => $image_path,
            'icon' => trim($_POST['icon']),
            'status' => $_POST['status'],
            'display_order' => isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'level' => 1 // Main category
        ];

        if ($categoryController->createCategory($data)) {
            $_SESSION['success_message'] = "Main Category created successfully!";
            header("Location: add-main-category.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to create category.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Main Category | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .form-label {
            font-weight: 500;
        }

        .required:after {
            content: " *";
            color: red;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 4px;
            display: none;
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
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
                            <h3><strong>Add Main</strong> Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-categories.php?level=1" class="btn btn-secondary">View Main Categories</a>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-2">
                            <ul class="nav nav-tabs" id="categoryTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" href="add-main-category.php">
                                        <i class="fas fa-layer-group me-1"></i> Main Category
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="add-sub-category.php">
                                        <i class="fas fa-sitemap me-1"></i> Sub Category
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="add-sub-sub-category.php">
                                        <i class="fas fa-project-diagram me-1"></i> Sub Sub Category
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>


                    <!-- Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Main Category Information</h5>
                                    <h6 class="card-subtitle text-white">Add new main category (Level 1)</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="categoryForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label required">Category Name</label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                        required maxlength="255">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="slug" class="form-label required">Slug</label>
                                                    <input type="text" class="form-control" id="slug" name="slug"
                                                        required maxlength="255">
                                                    <small class="form-text text-muted">URL-friendly version</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                maxlength="500"></textarea>
                                        </div>

                                        <!-- Image Upload -->
                                        <div class="mb-3">
                                            <label class="form-label">Category Image</label>
                                            <div class="upload-area" id="uploadArea">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-1">Click to upload or drag and drop</p>
                                                <p class="small text-muted mb-0">PNG, JPG, GIF, WebP (Max. 5MB)</p>
                                                <input type="file" id="image" name="image"
                                                    accept=".jpg,.jpeg,.png,.gif,.webp" style="display: none;">
                                            </div>
                                            <div class="file-info" id="fileInfo"></div>
                                            <img id="imagePreview" class="image-preview" alt="Image preview">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order"
                                                        name="display_order" value="0" min="0">
                                                    <small class="form-text text-muted">Lower numbers display
                                                        first</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label required">Status</label>
                                                    <select class="form-control" id="status" name="status" required>
                                                        <option value="active" selected>Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="icon" class="form-label">Icon Class</label>
                                                    <input type="text" class="form-control" id="icon" name="icon"
                                                        placeholder="fas fa-folder" maxlength="100">
                                                    <small class="form-text text-muted">Font Awesome icon</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured"
                                                    name="is_featured" value="1">
                                                <label class="form-check-label" for="is_featured">Featured
                                                    Category</label>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">Create Main Category</button>
                                            <a href="view-categories.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-info text-dark">
                                    <h5 class="card-title mb-0">Quick Tips</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info p-2">
                                        <h6 class="fw-bold mb-2">
                                            <i class="bi bi-diagram-3"></i> Main Category
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>This is a <strong>Level 1</strong> category</li>
                                            <li>Will appear in main navigation</li>
                                            <li>Can have multiple sub-categories</li>
                                            <li>Parent ID will be set to <strong>0</strong></li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-warning p-2">
                                        <h6 class="fw-bold mb-2">
                                            <i class="bi bi-link-45deg"></i> Slug Guidelines
                                        </h6>
                                        <p class="mb-0">Use lowercase with hyphens:<br>
                                            Example: <code>men-clothing</code></p>
                                    </div>

                                    <div class="text-center mt-3">
                                        <div class="btn-group" role="group">
                                            <a href="add-main-category.php" class="btn btn-primary btn-sm">Add Main
                                                Category</a>
                                            <a href="add-sub-category.php" class="btn btn-success btn-sm">Add Sub
                                                Category</a>
                                            <a href="add-sub-sub-category.php" class="btn btn-warning btn-sm">Add Sub
                                                Sub</a>
                                        </div>
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
        // Auto-generate slug
        document.getElementById('name').addEventListener('input', function () {
            const name = this.value;
            const slug = name.toLowerCase()
                .trim()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            document.getElementById('slug').value = slug;
        });

        // Image upload functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('image');
        const fileInfo = document.getElementById('fileInfo');
        const imagePreview = document.getElementById('imagePreview');

        uploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function (e) {
            handleFileSelection(this.files[0]);
        });

        function handleFileSelection(file) {
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    return;
                }

                fileInfo.innerHTML = `
                    <strong>Selected file:</strong> ${file.name} 
                    <span class="text-danger ms-2" onclick="removeImage()" style="cursor:pointer">
                        <i class="fas fa-times"></i> Remove
                    </span>
                `;

                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            fileInput.value = '';
            fileInfo.innerHTML = '';
            imagePreview.style.display = 'none';
        }

        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function (e) {
            const name = document.getElementById('name').value.trim();
            const slug = document.getElementById('slug').value.trim();

            if (!name) {
                e.preventDefault();
                alert('Please enter a category name');
                document.getElementById('name').focus();
                return;
            }

            if (!/^[a-z0-9-]+$/.test(slug)) {
                e.preventDefault();
                alert('Slug can only contain lowercase letters, numbers, and hyphens');
                document.getElementById('slug').focus();
                return;
            }
        });
    </script>
</body>

</html>