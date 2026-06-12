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
$category = new CategoryController($db);
$categories = $category->getAllCategories();

// Get product categories
$productCategories = $productController->getAllProductsApi($_GET['id']);
$selectedCategoryIds = [];
foreach ($productCategories as $pc) {
    $selectedCategoryIds[] = $pc['category_id'];
}

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
        $productCategories = $productController->getProductCategories($_GET['id']);
        $selectedCategoryIds = [];
        foreach ($productCategories as $pc) {
            $selectedCategoryIds[] = $pc['category_id'];
        }
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        /* Category checkboxes */
        .category-group {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }

        .category-group h6 {
            color: #495057;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .form-check {
            margin-bottom: 0.5rem;
        }

        .form-check-label {
            cursor: pointer;
        }

        .category-select-all {
            font-weight: 600;
            color: #0d6efd;
        }

        .selected-count-badge {
            font-size: 0.75rem;
            vertical-align: middle;
            margin-left: 0.5rem;
        }

        .category-helper-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .discount-helper {
            font-size: 0.875rem;
            display: block;
            margin-top: 0.25rem;
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

                                    <form method="POST" enctype="multipart/form-data" id="productForm">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="name">Product Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" required
                                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($product['name']); ?>"
                                                    placeholder="Enter product name">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="sku">SKU *</label>
                                                <input type="text" class="form-control" id="sku" name="sku" required
                                                    value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : htmlspecialchars($product['sku']); ?>"
                                                    placeholder="Enter SKU">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter product description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($product['description']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="meta_title">Meta Title</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title"
                                                    value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : htmlspecialchars($product['meta_title'] ?? ''); ?>"
                                                    placeholder="SEO meta title (50-60 characters)">
                                                <small class="form-text text-muted">Recommended: 50-60 characters</small>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="meta_description">Meta Description</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                                                    placeholder="SEO meta description (150-160 characters)"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
                                                <small class="form-text text-muted">Recommended: 150-160 characters</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="meta_keywords">Meta Keywords</label>
                                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords"
                                                value="<?php echo isset($_POST['meta_keywords']) ? htmlspecialchars($_POST['meta_keywords']) : htmlspecialchars($product['meta_keywords'] ?? ''); ?>"
                                                placeholder="Enter keywords separated by commas">
                                            <small class="form-text text-muted">Separate keywords with commas</small>
                                        </div>

                                        <!-- Categories Section - Checkbox Based -->
                                        <div class="mb-3">
                                            <label class="form-label">Categories * <span id="selectedCategoriesCount" class="badge bg-primary selected-count-badge"><?php echo count($selectedCategoryIds); ?> selected</span></label>
                                            <div class="category-group">
                                                <div class="row">
                                                    <div class="col-md-3 mb-2">
                                                        <div class="form-check category-select-all">
                                                            <input type="checkbox" class="form-check-input" id="selectAllCategories" <?php echo (count($selectedCategoryIds) == count($categories)) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="selectAllCategories">
                                                                Select All Categories
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <?php 
                                                    $chunked_categories = array_chunk($categories, ceil(count($categories) / 4));
                                                    foreach ($chunked_categories as $category_chunk): 
                                                    ?>
                                                    <div class="col-md-3">
                                                        <?php foreach ($category_chunk as $cat): ?>
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   class="form-check-input category-checkbox" 
                                                                   name="categories[]" 
                                                                   value="<?php echo $cat['id']; ?>" 
                                                                   id="category_<?php echo $cat['id']; ?>"
                                                                   <?php 
                                                                   if (isset($_POST['categories'])) {
                                                                       echo in_array($cat['id'], $_POST['categories']) ? 'checked' : '';
                                                                   } else {
                                                                       echo in_array($cat['id'], $selectedCategoryIds) ? 'checked' : '';
                                                                   }
                                                                   ?>>
                                                            <label class="form-check-label" for="category_<?php echo $cat['id']; ?>">
                                                                <?php echo htmlspecialchars($cat['name']); ?>
                                                            </label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                
                                                <div class="category-helper-text">
                                                    <small>Select one or more categories. First selected will be considered as primary category.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Add promotional fields -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h5 class="card-title">Promotional Settings</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="top_selection" name="top_selection" value="1"
                                                                <?php echo (isset($_POST['top_selection']) && $_POST['top_selection'] == 1) ? 'checked' : (($product['top_selection'] ?? 0) == 1 ? 'checked' : ''); ?>>
                                                            <label class="form-check-label" for="top_selection">Top Selection</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="our_bestseller" name="our_bestseller" value="1"
                                                                <?php echo (isset($_POST['our_bestseller']) && $_POST['our_bestseller'] == 1) ? 'checked' : (($product['our_bestseller'] ?? 0) == 1 ? 'checked' : ''); ?>>
                                                            <label class="form-check-label" for="our_bestseller">Our Bestseller</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="top_rated" name="top_rated" value="1"
                                                                <?php echo (isset($_POST['top_rated']) && $_POST['top_rated'] == 1) ? 'checked' : (($product['top_rated'] ?? 0) == 1 ? 'checked' : ''); ?>>
                                                            <label class="form-check-label" for="top_rated">Top Rated</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="top_deal_by_categories" name="top_deal_by_categories" value="1"
                                                                <?php echo (isset($_POST['top_deal_by_categories']) && $_POST['top_deal_by_categories'] == 1) ? 'checked' : (($product['top_deal_by_categories'] ?? 0) == 1 ? 'checked' : ''); ?>>
                                                            <label class="form-check-label" for="top_deal_by_categories">Top Deal by Category</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Promotional Content -->
                                        <div class="mb-3">
                                            <label class="form-label" for="promotional_content">Promotional Content</label>
                                            <textarea class="form-control" id="promotional_content" name="promotional_content" rows="4"
                                                placeholder="Enter promotional content for marketing..."><?php echo isset($_POST['promotional_content']) ? htmlspecialchars($_POST['promotional_content']) : htmlspecialchars($product['promotional_content'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">This content will be used for marketing and promotional materials</small>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="brand">Brand</label>
                                                <input type="text" class="form-control" id="brand" name="brand"
                                                    value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : htmlspecialchars($product['brand']); ?>"
                                                    placeholder="Enter brand name">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ($product['status'] == 'active' ? 'selected' : ''); ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ($product['status'] == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                                                    <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ($product['status'] == 'draft' ? 'selected' : ''); ?>>Draft</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="price">Price *</label>
                                                <input type="number" step="0.01" class="form-control" id="price" name="price" required
                                                    value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : htmlspecialchars($product['price']); ?>"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="discount_price">Discount Price</label>
                                                <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price"
                                                    value="<?php echo isset($_POST['discount_price']) ? htmlspecialchars($_POST['discount_price']) : htmlspecialchars($product['discount_price'] ?? ''); ?>"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required
                                                    value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : htmlspecialchars($product['stock_quantity']); ?>"
                                                    placeholder="0">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Current Images</label>
                                            <div id="current-images-container">
                                                <?php if (!empty($product['images'])): ?>
                                                    <?php foreach ($product['images'] as $index => $image): ?>
                                                        <div class="current-image-wrapper">
                                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                                                alt="Product Image" class="current-image">
                                                            <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image['image_url']); ?>">
                                                            <?php if ($index == 0): ?>
                                                                <small class="text-success d-block text-center">Primary</small>
                                                            <?php endif; ?>
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
                                                    <?php echo (isset($_POST['featured']) && $_POST['featured'] == 1) ? 'checked' : ($product['featured'] == 1 ? 'checked' : ''); ?>>
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
            // Elements
            const selectAllCheckbox = document.getElementById('selectAllCategories');
            const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
            const selectedCountBadge = document.getElementById('selectedCategoriesCount');
            const productForm = document.getElementById('productForm');
            
            // Update selected count
            function updateSelectedCount() {
                const selectedCount = document.querySelectorAll('.category-checkbox:checked').length;
                selectedCountBadge.textContent = selectedCount + ' selected';
                
                // Update select all checkbox state
                if (selectedCount === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (selectedCount === categoryCheckboxes.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
            
            // Handle select all checkbox
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateSelectedCount();
            });
            
            // Handle individual category checkbox changes
            categoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
            
            // Form validation
            productForm.addEventListener('submit', function(e) {
                // Check if at least one category is selected
                const selectedCategories = document.querySelectorAll('.category-checkbox:checked');
                if (selectedCategories.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one category');
                    return false;
                }
                
                // Check discount price validation
                const price = parseFloat(document.getElementById('price').value) || 0;
                const discountPrice = parseFloat(document.getElementById('discount_price').value) || 0;
                if (discountPrice > 0 && discountPrice >= price) {
                    e.preventDefault();
                    alert('Discount price must be less than regular price');
                    return false;
                }
                
                // Check stock quantity
                const stockQuantity = parseInt(document.getElementById('stock_quantity').value) || 0;
                if (stockQuantity < 0) {
                    e.preventDefault();
                    alert('Stock quantity cannot be negative');
                    return false;
                }
            });
            
            // Auto-generate SEO fields if empty
            const nameInput = document.getElementById('name');
            const metaTitleInput = document.getElementById('meta_title');
            const metaDescriptionInput = document.getElementById('meta_description');
            const metaKeywordsInput = document.getElementById('meta_keywords');
            
            nameInput.addEventListener('blur', function() {
                const name = this.value.trim();
                if (!name) return;
                
                // Generate meta title if empty
                if (!metaTitleInput.value.trim()) {
                    metaTitleInput.value = name + ' | Printmont';
                }
                
                // Generate meta description if empty
                if (!metaDescriptionInput.value.trim()) {
                    metaDescriptionInput.value = 'Buy ' + name + ' from Printmont. High quality products with great discounts and fast delivery.';
                }
                
                // Generate meta keywords if empty
                if (!metaKeywordsInput.value.trim()) {
                    const keywords = name.toLowerCase().split(' ').filter(word => word.length > 2);
                    const brand = document.getElementById('brand').value ? document.getElementById('brand').value.toLowerCase() : '';
                    
                    let keywordList = [];
                    keywordList = keywordList.concat(keywords);
                    if (brand) keywordList.push(brand);
                    
                    // Add selected categories to keywords
                    const selectedCategories = document.querySelectorAll('.category-checkbox:checked');
                    selectedCategories.forEach(checkbox => {
                        const label = checkbox.nextElementSibling.textContent.toLowerCase();
                        keywordList.push(label);
                    });
                    
                    keywordList.push('printmont', 'online shopping', 'best price', 'buy online');
                    
                    // Remove duplicates
                    keywordList = [...new Set(keywordList)];
                    metaKeywordsInput.value = keywordList.join(', ');
                }
            });
            
            // Auto-calculate and display discount percentage
            const priceInput = document.getElementById('price');
            const discountPriceInput = document.getElementById('discount_price');
            
            function calculateDiscount() {
                const price = parseFloat(priceInput.value) || 0;
                const discountPrice = parseFloat(discountPriceInput.value) || 0;
                
                if (discountPrice > 0 && price > 0 && discountPrice < price) {
                    const discountPercent = Math.round(((price - discountPrice) / price) * 100);
                    const savings = (price - discountPrice).toFixed(2);
                    
                    // Create or update helper text
                    let helper = discountPriceInput.nextElementSibling;
                    if (!helper || !helper.classList.contains('discount-helper')) {
                        helper = document.createElement('small');
                        helper.className = 'form-text discount-helper';
                        discountPriceInput.parentNode.appendChild(helper);
                    }
                    helper.textContent = `${discountPercent}% discount (Save ₹${savings})`;
                    helper.style.color = '#198754';
                } else if (discountPrice >= price && discountPrice > 0) {
                    let helper = discountPriceInput.nextElementSibling;
                    if (!helper || !helper.classList.contains('discount-helper')) {
                        helper = document.createElement('small');
                        helper.className = 'form-text discount-helper';
                        discountPriceInput.parentNode.appendChild(helper);
                    }
                    helper.textContent = 'Discount price should be less than regular price';
                    helper.style.color = '#dc3545';
                } else {
                    // Remove helper text if exists
                    const helper = discountPriceInput.nextElementSibling;
                    if (helper && helper.classList.contains('discount-helper')) {
                        helper.remove();
                    }
                }
            }
            
            priceInput.addEventListener('input', calculateDiscount);
            discountPriceInput.addEventListener('input', calculateDiscount);
            
            // Initialize discount calculation on page load
            calculateDiscount();

            // Drag and Drop functionality
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