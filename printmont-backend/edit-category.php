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
$parentCategories = $categoryController->getParentCategories();

if (!$category) {
    $_SESSION['error_message'] = "Category not found!";
    header("Location: view-categories.php");
    exit();
}

// Handle form submission
if ($_POST) {
    try {
        $image_path = $category['image']; // Keep existing image by default
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/category/';
            
            // Create upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_size = $_FILES['image']['size'];
            $file_error = $_FILES['image']['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Validate file type
            if (in_array($file_ext, $allowed_ext)) {
                // Validate file size (max 5MB)
                if ($file_size <= 5 * 1024 * 1024) {
                    // Delete old image if exists and new image is being uploaded
                    if (!empty($category['image']) && file_exists($category['image'])) {
                        unlink($category['image']);
                    }
                    
                    // Generate unique file name
                    $new_file_name = uniqid('category_', true) . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $image_path = $destination;
                    } else {
                        throw new Exception("Failed to upload image. Please try again.");
                    }
                } else {
                    throw new Exception("Image size too large. Maximum size is 5MB.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP are allowed.");
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            // Remove existing image if requested
            if (!empty($category['image']) && file_exists($category['image'])) {
                unlink($category['image']);
            }
            $image_path = '';
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle upload errors
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            
            $error_code = $_FILES['image']['error'];
            $error_message = $upload_errors[$error_code] ?? 'Unknown upload error.';
            throw new Exception("Upload error: " . $error_message);
        }
        
        $data = [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug']),
            'description' => trim($_POST['description']),
            'parent_id' => isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0,
            'image' => $image_path,
            'icon' => trim($_POST['icon']),
            'status' => $_POST['status'],
            'display_order' => isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        if ($categoryController->updateCategory($categoryId, $data)) {
            $_SESSION['success_message'] = "Category updated successfully!";
            header("Location: view-categories.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to update category.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Refresh category data after update
    $category = $categoryController->getCategoryById($categoryId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Category | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        .form-label { font-weight: 500; }
        .required:after { content: " *"; color: red; }
        .image-preview { max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 4px; display: none; }
        .upload-area { border: 2px dashed #dee2e6; border-radius: 4px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .upload-area:hover { border-color: #007bff; background-color: #f8f9fa; }
        .upload-area.dragover { border-color: #007bff; background-color: #e7f3ff; }
        .file-info { margin-top: 10px; font-size: 0.875rem; color: #6c757d; }
        .remove-image { color: #dc3545; cursor: pointer; margin-left: 10px; }
        .current-image { max-width: 200px; max-height: 200px; border-radius: 4px; margin-bottom: 10px; }
        .image-actions { margin-top: 10px; }
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
                            <a href="view-categories.php" class="btn btn-secondary">View Categories</a>
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
                                    <h5 class="card-title">Category Information</h5>
                                    <h6 class="card-subtitle text-muted">Update category information.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="categoryForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label required">Category Name</label>
                                                    <input type="text" class="form-control" id="name" name="name" 
                                                           value="<?php echo htmlspecialchars($category['name']); ?>" 
                                                           required maxlength="255">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="slug" class="form-label required">Slug</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" 
                                                           value="<?php echo htmlspecialchars($category['slug']); ?>" 
                                                           required maxlength="255">
                                                    <small class="form-text text-muted">URL-friendly version of the name</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="3" maxlength="500"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                        </div>

                                        <!-- Current Image Display -->
                                        <?php if (!empty($category['image']) && file_exists($category['image'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current Image</label>
                                            <div>
                                                <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                                     class="current-image" 
                                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                     onerror="this.style.display='none'">
                                                <div class="image-actions">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                                        <label class="form-check-label text-danger" for="remove_image">
                                                            Remove current image
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Image Upload Section -->
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo empty($category['image']) ? 'Category Image' : 'Upload New Image'; ?></label>
                                            <div class="upload-area" id="uploadArea">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-1">Click to upload or drag and drop</p>
                                                <p class="small text-muted mb-0">PNG, JPG, GIF, WebP (Max. 5MB)</p>
                                                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" style="display: none;">
                                            </div>
                                            <div class="file-info" id="fileInfo"></div>
                                            <img id="imagePreview" class="image-preview" alt="Image preview">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="parent_id" class="form-label">Parent Category</label>
                                                    <select class="form-control" id="parent_id" name="parent_id">
                                                        <option value="0">No Parent (Main Category)</option>
                                                        <?php foreach ($parentCategories as $parent): ?>
                                                            <?php if ($parent['id'] != $category['id']): ?>
                                                                <option value="<?php echo $parent['id']; ?>" 
                                                                    <?php echo $parent['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($parent['name']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order" 
                                                           value="<?php echo $category['display_order']; ?>" min="0">
                                                    <small class="form-text text-muted">Lower numbers display first</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="icon" class="form-label">Icon Class</label>
                                                    <input type="text" class="form-control" id="icon" name="icon" 
                                                           value="<?php echo htmlspecialchars($category['icon']); ?>" 
                                                           placeholder="fas fa-folder" maxlength="100">
                                                    <small class="form-text text-muted">Font Awesome icon class (e.g., fas fa-folder)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label required">Status</label>
                                                    <select class="form-control" id="status" name="status" required>
                                                        <option value="active" <?php echo $category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                                       <?php echo $category['is_featured'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    Featured Category
                                                </label>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">Update Category</button>
                                            <a href="view-categories.php" class="btn btn-secondary">Cancel</a>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">Delete Category</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header text-dark">
                                    <h5 class="card-title mb-0">Quick Tips</h5>
                                </div>

                                <div class="card-body">

                                    <!-- Category Tips -->
                                    <div class="alert alert-info p-2">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-diagram-3"></i> Category Hierarchy
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Select <strong>No Parent</strong> for main categories</li>
                                            <li>Choose a parent category for subcategories</li>
                                            <li>You can create unlimited subcategory levels</li>
                                        </ul>
                                    </div>

                                    <!-- Image Guidelines -->
                                    <div class="alert alert-warning p-2">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-image"></i> Image Guidelines
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Supported: JPG, PNG, GIF, WebP</li>
                                            <li>Max file size: 5MB</li>
                                            <li>Recommended ratio: <strong>1:1 (square)</strong></li>
                                        </ul>
                                    </div>

                                    <!-- Slug Guidelines -->
                                    <div class="alert alert-secondary p-2">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-link"></i> Slug Guidelines
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Use lowercase letters, numbers, and hyphens</li>
                                            <li>Must be URL-friendly and descriptive</li>
                                        </ul>
                                    </div>

                                </div>
                            </div>

                            <!-- Category Details -->
                            <div class="card mt-3 shadow-sm border-0">
                                <div class="card-header text-dark">
                                    <h5 class="card-title mb-0">Category Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">Created:</small>
                                        <div class="fw-semibold"><?php echo date('M j, Y g:i A', strtotime($category['created_at'])); ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Last Updated:</small>
                                        <div class="fw-semibold"><?php echo date('M j, Y g:i A', strtotime($category['updated_at'])); ?></div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Category Level:</small>
                                        <div>
                                            <span class="badge bg-info">Level <?php echo $category['level']; ?></span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Current Status:</small>
                                        <div>
                                            <span class="badge bg-<?php echo $category['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($category['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($category['is_featured']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Featured:</small>
                                        <div>
                                            <span class="badge bg-warning">Yes</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Subcategories -->
                            <?php
                            $subcategories = $categoryController->getSubcategories($categoryId);
                            if (!empty($subcategories)):
                            ?>
                            <div class="card mt-3 shadow-sm border-0">
                                <div class="card-header text-dark">
                                    <h5 class="card-title mb-0">Subcategories</h5>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">This category has <?php echo count($subcategories); ?> subcategory(ies):</p>
                                    <ul class="list-unstyled mb-3">
                                        <?php foreach ($subcategories as $subcat): ?>
                                            <li class="mb-1">
                                                <i class="fas fa-folder text-muted me-2"></i>
                                                <a href="edit-category.php?id=<?php echo $subcat['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                                </a>
                                                <span class="badge bg-<?php echo $subcat['status'] == 'active' ? 'success' : 'secondary'; ?> ms-2">
                                                    <?php echo ucfirst($subcat['status']); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="add-category.php?parent_id=<?php echo $categoryId; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> Add Subcategory
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
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
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
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
        const removeImageCheckbox = document.getElementById('remove_image');

        // Click to upload
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            handleFileSelection(this.files[0]);
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection(e.dataTransfer.files[0]);
            }
        });

        function handleFileSelection(file) {
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    return;
                }

                // Display file info
                fileInfo.innerHTML = `
                    <strong>Selected file:</strong> ${file.name} 
                    <span class="remove-image" onclick="removeNewImage()">
                        <i class="fas fa-times"></i> Remove
                    </span>
                `;

                // Uncheck remove current image if uploading new one
                if (removeImageCheckbox) {
                    removeImageCheckbox.checked = false;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function removeNewImage() {
            fileInput.value = '';
            fileInfo.innerHTML = '';
            imagePreview.style.display = 'none';
        }

        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const slug = document.getElementById('slug').value.trim();
            
            if (!name) {
                e.preventDefault();
                alert('Please enter a category name');
                document.getElementById('name').focus();
                return;
            }
            
            if (!slug) {
                e.preventDefault();
                alert('Please enter a slug');
                document.getElementById('slug').focus();
                return;
            }
            
            // Validate slug format
            if (!/^[a-z0-9-]+$/.test(slug)) {
                e.preventDefault();
                alert('Slug can only contain lowercase letters, numbers, and hyphens');
                document.getElementById('slug').focus();
                return;
            }

            // Validate file if selected
            const file = fileInput.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('File size must be less than 5MB.');
                    return;
                }
            }
        });

        function confirmDelete() {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                window.location.href = 'delete-category.php?id=<?php echo $categoryId; ?>';
            }
        }

        // Prevent selecting self as parent
        document.addEventListener('DOMContentLoaded', function() {
            const parentSelect = document.getElementById('parent_id');
            const currentCategoryId = <?php echo $categoryId; ?>;
            
            for (let i = 0; i < parentSelect.options.length; i++) {
                if (parseInt(parentSelect.options[i].value) === currentCategoryId) {
                    parentSelect.options[i].disabled = true;
                    parentSelect.options[i].textContent += ' (Current)';
                    break;
                }
            }
        });
    </script>
</body>
</html>