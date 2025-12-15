<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: product-list.php');
    exit;
}

$productController = new ProductController($db);
$product = $productController->getProductById($_GET['id']);
// $categories = $productController->getCategories();

$category = new CategoryController($db);
$categories = $category->getAllCategories();

if (!$product) {
    $_SESSION['error_message'] = "Product not found!";
    header('Location: view-products.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $productController->updateProduct($_GET['id'], $_POST, $_FILES);

    if ($result['success']) {
        $_SESSION['success_message'] = "Product updated successfully!";
        header('Location: view-products.php');
        exit;
    } else {
        $error_message = $result['error'];
        // Refresh product data
        $product = $productController->getProductById($_GET['id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="Nikhil">
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit Product | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body {
            opacity: 0;
        }

        .current-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin: 5px;
        }

        /* Drag and Drop Styles */
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    cursor: pointer;
}

.drop-zone:hover {
    border-color: #007bff;
    background-color: #e9f5ff;
}

.drop-zone.dragover {
    border-color: #007bff;
    background-color: #e9f5ff;
    transform: scale(1.02);
}

.drop-zone-content {
    pointer-events: none;
}

.drop-zone-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
}

.drop-zone-text {
    color: #6c757d;
    margin-bottom: 0;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.preview-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.preview-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    color: #dc3545;
    transition: all 0.2s ease;
}

.preview-remove:hover {
    background: #fff;
    transform: scale(1.1);
}

.current-image-wrapper {
    position: relative;
    display: inline-block;
    margin: 5px;
}

.current-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.file-info {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 0.875rem;
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
                            <h3><strong>Edit</strong> Product</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-products.php" class="btn btn-light bg-success me-2">View Products</a>
                            <a href="product.php" class="btn btn-primary">Add New Product</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Edit Product</h5>
                                    <h6 class="card-subtitle text-muted">Update product information.</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($error_message): ?>
                                        <div class="alert alert-danger alert-dismissible" role="alert">
                                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="name">Product Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" required
                                                    value="<?php echo htmlspecialchars($product['name']); ?>"
                                                    placeholder="Enter product name">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="sku">SKU *</label>
                                                <input type="text" class="form-control" id="sku" name="sku" required
                                                    value="<?php echo htmlspecialchars($product['sku']); ?>"
                                                    placeholder="Enter SKU">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter product description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="category_id">Category *</label>
                                                <select id="category_id" name="category_id" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>"
                                                            <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="brand">Brand</label>
                                                <input type="text" class="form-control" id="brand" name="brand"
                                                    value="<?php echo htmlspecialchars($product['brand']); ?>"
                                                    placeholder="Enter brand name">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="draft" <?php echo ($product['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="price">Price *</label>
                                                <input type="number" step="0.01" class="form-control" id="price" name="price" required
                                                    value="<?php echo htmlspecialchars($product['price']); ?>"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="discount_price">Discount Price</label>
                                                <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price"
                                                    value="<?php echo htmlspecialchars($product['discount_price'] ?? ''); ?>"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required
                                                    value="<?php echo htmlspecialchars($product['stock_quantity']); ?>"
                                                    placeholder="0">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Current Images</label>
                                            <div id="current-images-container">
                                                <?php if (!empty($product['images'])): ?>
                                                    <?php foreach ($product['images'] as $image): ?>
                                                        <div class="current-image-wrapper">
                                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                                                alt="Product Image" class="current-image">
                                                            <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image['image_url']); ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p class="text-muted">No images uploaded</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="images">Update Product Images</label>

                                            <!-- Drag and Drop Area -->
                                            <div class="drop-zone" id="dropZone">
                                                <div class="drop-zone-content">
                                                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                                                    <p class="drop-zone-title">Drag & Drop your images here</p>
                                                    <p class="drop-zone-text">or click to browse</p>
                                                    <input type="file" class="drop-zone-input" id="images" name="images[]" multiple accept="image/*" hidden>
                                                    <button type="button" class="btn btn-outline-primary mt-2" onclick="document.getElementById('images').click()">
                                                        Browse Files
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Preview Container -->
                                            <div class="preview-container mt-3" id="previewContainer" style="display: none;">
                                                <h6>New Images Preview:</h6>
                                                <div class="preview-grid" id="previewGrid"></div>
                                            </div>

                                            <small class="form-text text-muted">Drag and drop images or click to browse. First image will be set as primary.</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                                                    <?php echo ($product['featured'] == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="featured">Featured Product</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Product</button>
                                        <a href="view-products.php" class="btn btn-secondary">Cancel</a>
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

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('images');
    const previewContainer = document.getElementById('previewContainer');
    const previewGrid = document.getElementById('previewGrid');
    const currentFiles = new DataTransfer();

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    // Handle file input change
    fileInput.addEventListener('change', handleFileSelect, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.classList.add('dragover');
    }

    function unhighlight() {
        dropZone.classList.remove('dragover');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFileSelect(e) {
        const files = e.target.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        if (files.length > 0) {
            previewContainer.style.display = 'block';
            
            for (let file of files) {
                if (file.type.startsWith('image/')) {
                    // Add to FileList
                    currentFiles.items.add(file);
                    
                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        createPreview(file, e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            }
            
            // Update file input
            fileInput.files = currentFiles.files;
        }
    }

    function createPreview(file, src) {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item';
        
        const img = document.createElement('img');
        img.src = src;
        img.className = 'preview-image';
        img.alt = 'Preview';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'preview-remove';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = function() {
            removePreview(previewItem, file);
        };
        
        previewItem.appendChild(img);
        previewItem.appendChild(removeBtn);
        previewGrid.appendChild(previewItem);
    }

    function removePreview(previewItem, file) {
        // Remove from preview
        previewGrid.removeChild(previewItem);
        
        // Remove from FileList
        const newFiles = new DataTransfer();
        for (let i = 0; i < currentFiles.files.length; i++) {
            if (currentFiles.files[i] !== file) {
                newFiles.items.add(currentFiles.files[i]);
            }
        }
        
        // Update current files and input
        currentFiles.files = newFiles.files;
        fileInput.files = currentFiles.files;
        
        // Hide preview container if no files left
        if (currentFiles.files.length === 0) {
            previewContainer.style.display = 'none';
        }
    }

    // Add click functionality to drop zone
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
});
</script>
</body>

</html>