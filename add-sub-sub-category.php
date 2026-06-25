<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

$categoryController = new CategoryController();
$mainCategories = $categoryController->getMainCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $image_path = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/category/';

            // Create directory if it doesn't exist
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
                    // Generate unique filename
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
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle upload errors
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];

            $error_code = $_FILES['image']['error'];
            $error_message = $upload_errors[$error_code] ?? 'Unknown upload error.';
            throw new Exception("Upload error: " . $error_message);
        }

        // Validate inputs
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $main_category_id = (int) $_POST['main_category_id'];
        $sub_category_id = (int) $_POST['sub_category_id'];

        if (empty($name)) {
            throw new Exception("Sub sub category name is required.");
        }

        if (empty($slug)) {
            throw new Exception("Slug is required.");
        }

        if ($main_category_id <= 0) {
            throw new Exception("Please select a main category.");
        }

        if ($sub_category_id <= 0) {
            throw new Exception("Please select a sub category.");
        }

        // Check if category already exists
        if ($categoryController->checkCategoryExists($name, $slug)) {
            throw new Exception("Category with this name or slug already exists.");
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => trim($_POST['description']),
            'parent_id' => $sub_category_id, // Parent is sub category
            'image' => $image_path,
            'icon' => trim($_POST['icon']),
            'status' => $_POST['status'],
            'display_order' => isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'level' => 3 // Sub sub category
        ];

        if ($categoryController->createCategory($data)) {
            $_SESSION['success_message'] = "Sub Sub Category created successfully!";
            header("Location: add-sub-sub-category.php");
            exit();
        } else {
            throw new Exception("Failed to create sub sub category. Error: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get sub categories for selected main category if posted
$subCategories = [];
if (isset($_POST['main_category_id']) && $_POST['main_category_id'] > 0) {
    $subCategories = $categoryController->getSubCategories($_POST['main_category_id']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Sub Sub Category | Printmont</title>
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

        .upload-area.dragover {
            border-color: #007bff;
            background-color: #e7f3ff;
        }

        .file-info {
            margin-top: 10px;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .remove-image {
            color: #dc3545;
            cursor: pointer;
            margin-left: 10px;
        }

        .card-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
                            <h3><strong>Add Sub Sub</strong> Category</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-categories.php?level=3" class="btn btn-secondary">View Sub Sub Categories</a>
                        </div>
                    </div>

                    <!-- Category Type Tabs -->
                    <div class="card mb-3">
                        <div class="card-body p-2">
                            <ul class="nav nav-tabs" id="categoryTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="add-main-category.php">
                                        <i class="fas fa-layer-group me-1"></i> Main Category
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" href="add-sub-category.php">
                                        <i class="fas fa-sitemap me-1"></i> Sub Category
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" href="add-sub-sub-category.php">
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
                                <div class="bg-primary p-3">
                                    <h5 class="text-light mb-1">Sub Sub Category Information</h5>
                                    <h6 class="card-subtitle text-white">Add new sub sub category (Level 3)</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="categoryForm" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="main_category_id" class="form-label required">Main
                                                        Category</label>
                                                    <select class="form-control" id="main_category_id"
                                                        name="main_category_id" required
                                                        onchange="loadSubCategories(this.value)">
                                                        <option value="">Select Main Category</option>
                                                        <?php foreach ($mainCategories as $category): ?>
                                                                <option value="<?php echo $category['id']; ?>"
                                                                    <?php echo (isset($_POST['main_category_id']) && $_POST['main_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                                </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="sub_category_id" class="form-label required">Sub
                                                        Category</label>
                                                    <select class="form-control" id="sub_category_id"
                                                        name="sub_category_id" required>
                                                        <option value="">Select Main Category First</option>
                                                        <?php if (isset($_POST['main_category_id']) && $_POST['main_category_id'] > 0): ?>
                                                                <?php foreach ($subCategories as $subCat): ?>
                                                                        <option value="<?php echo $subCat['id']; ?>"
                                                                            <?php echo (isset($_POST['sub_category_id']) && $_POST['sub_category_id'] == $subCat['id']) ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($subCat['name']); ?>
                                                                        </option>
                                                                <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label required">Sub Sub Category
                                                        Name</label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                                        required maxlength="255" placeholder="e.g., Round Neck">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="slug" class="form-label required">Slug</label>
                                                    <input type="text" class="form-control" id="slug" name="slug"
                                                        value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                                                        required maxlength="255" placeholder="e.g., round-neck">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description"
                                                rows="3" maxlength="500" placeholder="Brief description of the sub sub category"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        </div>

                                        <!-- Image Upload Section -->
                                        <div class="mb-3">
                                            <label class="form-label">Category Image</label>
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
                                                    <label for="display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order"
                                                        value="<?php echo isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0; ?>" min="0">
                                                    <small class="form-text text-muted">Lower numbers display first</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label required">Status</label>
                                                    <select class="form-control" id="status" name="status" required>
                                                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : 'selected'; ?>>Active</option>
                                                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="icon" class="form-label">Icon Class</label>
                                                    <input type="text" class="form-control" id="icon" name="icon"
                                                        value="<?php echo isset($_POST['icon']) ? htmlspecialchars($_POST['icon']) : ''; ?>"
                                                        placeholder="fas fa-tshirt" maxlength="100">
                                                    <small class="form-text text-muted">Font Awesome icon</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                                    <?php echo (isset($_POST['is_featured']) && $_POST['is_featured']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    <i class="fas fa-star text-warning me-1"></i> Featured Sub Sub Category
                                                </label>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-plus-circle me-1"></i> Create Sub Sub Category
                                            </button>
                                            <a href="view-categories.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="bg-primary p-3">
                                    <h5 class="text-light mb-0">
                                        <i class="fas fa-info-circle me-1"></i> Quick Tips
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning p-3 mb-3">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-project-diagram me-1"></i> Sub Sub Category
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>This is a <strong class="text-warning">Level 3</strong> category</li>
                                            <li>Must have both Main & Sub Category</li>
                                            <li>Final level in hierarchy</li>
                                            <li>Used for detailed categorization</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info p-3 mb-3">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-image me-1"></i> Image Guidelines
                                        </h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Supported: JPG, PNG, GIF, WebP</li>
                                            <li>Max file size: 5MB</li>
                                            <li>Recommended ratio: 1:1 (square)</li>
                                            <li>Optimal size: 500x500 pixels</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info p-3">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Important
                                        </h6>
                                        <p class="mb-2">You need both Main Category and Sub Category before adding Sub Sub Category.</p>
                                        <p class="mb-0"><strong>Hierarchy:</strong> Main → Sub → Sub Sub</p>
                                    </div>

                                    <div class="text-center mt-3">
                                        <div class="btn-group-vertical w-100" role="group">
                                            <a href="add-main-category.php" class="btn btn-primary btn-sm mb-2">
                                                <i class="fas fa-plus me-1"></i> Level 1: Main
                                            </a>
                                            <a href="add-sub-category.php" class="btn btn-success btn-sm mb-2">
                                                <i class="fas fa-plus me-1"></i> Level 2: Sub
                                            </a>
                                            <a href="add-sub-sub-category.php" class="btn btn-warning btn-sm">
                                                <i class="fas fa-plus me-1"></i> Level 3: Sub Sub
                                            </a>
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
        function loadSubCategories(mainCategoryId) {
            if (!mainCategoryId) {
                document.getElementById('sub_category_id').innerHTML = '<option value="">Select Main Category First</option>';
                return;
            }
            
            // AJAX call to fetch sub categories
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajax/get-sub-categories.php?parent_id=' + mainCategoryId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    const select = document.getElementById('sub_category_id');
                    select.innerHTML = '<option value="">Select Sub Category</option>';
                    
                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                    
                    // Auto-generate slug if name exists
                    generateSlug();
                }
            };
            xhr.send();
        }

        // Auto-generate slug
        document.getElementById('name').addEventListener('input', generateSlug);
        document.getElementById('main_category_id').addEventListener('change', generateSlug);
        document.getElementById('sub_category_id').addEventListener('change', generateSlug);

        function generateSlug() {
            const name = document.getElementById('name').value;
            const mainCatSelect = document.getElementById('main_category_id');
            const subCatSelect = document.getElementById('sub_category_id');
            
            if (!name) return;
            
            let baseSlug = name.toLowerCase()
                .trim()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            
            // Add hierarchy to slug if both categories selected
            if (mainCatSelect.value && subCatSelect.value) {
                const mainCatText = mainCatSelect.options[mainCatSelect.selectedIndex].text;
                const subCatText = subCatSelect.options[subCatSelect.selectedIndex].text;
                
                const mainSlug = mainCatText.toLowerCase()
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-');
                    
                const subSlug = subCatText.toLowerCase()
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-');
                
                baseSlug = mainSlug + '-' + subSlug + '-' + baseSlug;
            }
            
            document.getElementById('slug').value = baseSlug;
        }

        // Image upload functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('image');
        const fileInfo = document.getElementById('fileInfo');
        const imagePreview = document.getElementById('imagePreview');

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
                    <div class="alert alert-success p-2 mt-2">
                        <strong>Selected file:</strong> ${file.name} 
                        <span class="float-end text-danger" onclick="removeImage()" style="cursor:pointer">
                            <i class="fas fa-times"></i> Remove
                        </span>
                    </div>
                `;

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
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
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const slug = document.getElementById('slug').value.trim();
            const mainCatId = document.getElementById('main_category_id').value;
            const subCatId = document.getElementById('sub_category_id').value;
            
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
            
            if (!mainCatId) {
                e.preventDefault();
                alert('Please select a main category');
                document.getElementById('main_category_id').focus();
                return;
            }
            
            if (!subCatId) {
                e.preventDefault();
                alert('Please select a sub category');
                document.getElementById('sub_category_id').focus();
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
    </script>
</body>
</html>