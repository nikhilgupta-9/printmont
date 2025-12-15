<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');
require_once(__DIR__ . '/controllers/ProductController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Pass the database connection to the controller
$productController = new ProductController($db);
$categoryController = new CategoryController($db);
$categories = $categoryController->getAllCategories();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $productController->addProduct($_POST, $_FILES);
    
    if ($result['success']) {
        $_SESSION['success_message'] = "Product added successfully!";
        header('Location: view-products.php');
        exit;
    } else {
        $error_message = $result['error'];
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
    <title>Add Product | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        /* Minimal custom CSS for category checkboxes */
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
                            <h3><strong>Add</strong> Product</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-products.php" class="btn btn-light bg-success me-2">View Products</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Add New Product</h5>
                                    <h6 class="card-subtitle text-muted">Add new product to your store.</h6>
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
                                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                                       placeholder="Enter product name">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="sku">SKU *</label>
                                                <input type="text" class="form-control" id="sku" name="sku" required 
                                                       value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>"
                                                       placeholder="Enter SKU">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                      placeholder="Enter product description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        </div>

                                         <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="meta_title">Meta Title</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                    value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>"
                                                    placeholder="SEO meta title (50-60 characters)">
                                                <small class="form-text text-muted">Recommended: 50-60 characters</small>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="meta_description">Meta Description</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                                                        placeholder="SEO meta description (150-160 characters)"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                                                <small class="form-text text-muted">Recommended: 150-160 characters</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="meta_keywords">Meta Keywords</label>
                                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                                value="<?php echo isset($_POST['meta_keywords']) ? htmlspecialchars($_POST['meta_keywords']) : ''; ?>"
                                                placeholder="Enter keywords separated by commas">
                                            <small class="form-text text-muted">Separate keywords with commas</small>
                                        </div>


                                        <!-- Categories Section - Checkbox Based -->
                                        <div class="mb-3">
                                            <label class="form-label">Categories * <span id="selectedCategoriesCount" class="badge bg-primary selected-count-badge">0 selected</span></label>
                                            <div class="category-group">
                                                <div class="row">
                                                    <div class="col-md-3 mb-2">
                                                        <div class="form-check category-select-all">
                                                            <input type="checkbox" class="form-check-input" id="selectAllCategories">
                                                            <label class="form-check-label" for="selectAllCategories">
                                                                Select All Categories
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <?php 
                                                    // Group categories if you have parent-child structure, or display all
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
                                                                   if (isset($_POST['categories']) && in_array($cat['id'], $_POST['categories'])) {
                                                                       echo 'checked';
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
                                                                <?php echo (isset($_POST['top_selection']) && $_POST['top_selection'] == 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="top_selection">Top Selection</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="our_bestseller" name="our_bestseller" value="1"
                                                                <?php echo (isset($_POST['our_bestseller']) && $_POST['our_bestseller'] == 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="our_bestseller">Our Bestseller</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="top_rated" name="top_rated" value="1"
                                                                <?php echo (isset($_POST['top_rated']) && $_POST['top_rated'] == 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="top_rated">Top Rated</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mb-3">
                                                            <input type="checkbox" class="form-check-input" id="top_deal_by_categories" name="top_deal_by_categories" value="1"
                                                                <?php echo (isset($_POST['top_deal_by_categories']) && $_POST['top_deal_by_categories'] == 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="top_deal_by_categories">Top Deal by Category</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="brand">Brand</label>
                                                <input type="text" class="form-control" id="brand" name="brand" 
                                                       value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>"
                                                       placeholder="Enter brand name">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="price">Price *</label>
                                                <input type="number" step="0.01" class="form-control" id="price" name="price" required 
                                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                                                       placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="discount_price">Discount Price</label>
                                                <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price" 
                                                       value="<?php echo isset($_POST['discount_price']) ? htmlspecialchars($_POST['discount_price']) : ''; ?>"
                                                       placeholder="0.00">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required 
                                                       value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : ''; ?>"
                                                       placeholder="0">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="images">Product Images</label>
                                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                                            <small class="form-text text-muted">First image will be set as primary. You can select multiple images (JPEG, PNG, GIF, WebP).</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1" 
                                                       <?php echo (isset($_POST['featured']) && $_POST['featured'] == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="featured">Featured Product</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Add Product</button>
                                        <button type="reset" class="btn btn-secondary">Reset</button>
                                        <a href="product-list.php" class="btn btn-outline-secondary">Cancel</a>
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
            
            // Auto-generate SKU if empty when product name is entered
            const nameInput = document.getElementById('name');
            const skuInput = document.getElementById('sku');
            
            nameInput.addEventListener('blur', function() {
                if (nameInput.value.trim() && !skuInput.value.trim()) {
                    // Generate a simple SKU from product name
                    const name = nameInput.value.trim();
                    const skuPrefix = name.substring(0, 3).toUpperCase().replace(/\s/g, '');
                    const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
                    const timestamp = Date.now().toString().slice(-4);
                    skuInput.value = skuPrefix + '-' + randomNum + '-' + timestamp;
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
            
            // Initialize selected count on page load
            updateSelectedCount();
        });
    </script>
    
    <script src="js/app.js"></script>
</body>
</html>