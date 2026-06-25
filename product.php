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
$category = new CategoryController($db);
$categories = $category->getMainCategories();

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
    <!-- Include CKEditor or other WYSIWYG editor -->
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="js/settings.js"></script>
    <style>
        body {
            opacity: 0;
        }

        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }

        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.completed {
            background-color: #28a745;
            color: white;
        }

        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
        }

        .tab-pane {
            min-height: 400px;
        }

        .progress {
            height: 10px;
            margin-bottom: 20px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 10px;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background-color: #0d6efd;
        }

        .step.completed:not(:last-child)::after {
            background-color: #28a745;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        .step.active .step-number {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed .step-number {
            background-color: #28a745;
            color: white;
        }

        .step-title {
            font-size: 14px;
            font-weight: 500;
        }

        .btn-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .tab-preview {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
        }

        .tab-preview.active {
            display: block;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #dee2e6;
        }

        .gallery-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .file-info {
            margin-top: 5px;
            font-size: 12px;
            color: #6c757d;
        }

        .remove-image {
            color: #dc3545;
            cursor: pointer;
            font-size: 12px;
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
                                    <h6 class="card-subtitle text-muted">Step-by-step product creation</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($error_message): ?>
                                        <div class="alert alert-danger alert-dismissible" role="alert">
                                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Step Indicator -->
                                    <div class="step-indicator">
                                        <div class="step active" data-step="1">
                                            <div class="step-number">1</div>
                                            <div class="step-title">General Info</div>
                                        </div>
                                        <div class="step" data-step="2">
                                            <div class="step-number">2</div>
                                            <div class="step-title">Filters</div>
                                        </div>
                                        <div class="step" data-step="3">
                                            <div class="step-number">3</div>
                                            <div class="step-title">Categories</div>
                                        </div>
                                        <div class="step" data-step="4">
                                            <div class="step-number">4</div>
                                            <div class="step-title">Images</div>
                                        </div>
                                        <div class="step" data-step="5">
                                            <div class="step-number">5</div>
                                            <div class="step-title">Review</div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: 20%"
                                            aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>

                                    <form method="POST" id="productForm" enctype="multipart/form-data">
                                        <!-- Tab Navigation (Hidden - controlled by JavaScript) -->
                                        <ul class="nav nav-tabs d-none" id="productTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab"
                                                    data-bs-target="#general" type="button" role="tab">General</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="filters-tab" data-bs-toggle="tab"
                                                    data-bs-target="#filters" type="button" role="tab">Filters</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="links-tab" data-bs-toggle="tab"
                                                    data-bs-target="#links" type="button" role="tab">Categories</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="images-tab" data-bs-toggle="tab"
                                                    data-bs-target="#images" type="button" role="tab">Images</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="productTabsContent">

                                            <!-- Step 1: General Tab -->
                                            <div class="tab-pane fade show active" id="general" role="tabpanel"
                                                data-step="1">
                                                <h4 class="mb-4">Step 1: General Information</h4>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label required-field"
                                                            for="product_name">Product Name</label>
                                                        <input type="text" class="form-control" id="product_name"
                                                            name="product_name" required
                                                            placeholder="Enter product name">
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label required-field"
                                                            for="product_slug">Product Slug</label>
                                                        <input type="text" class="form-control" id="product_slug"
                                                            name="product_slug" required
                                                            placeholder="product-slug-name">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label required-field" for="sku_code">SKU
                                                            Code</label>
                                                        <input type="text" class="form-control" id="sku_code"
                                                            name="sku_code" required placeholder="SKU-001">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label required-field"
                                                            for="product_quantity">Product Quantity</label>
                                                        <input type="number" class="form-control" id="product_quantity"
                                                            name="product_quantity" required value="0">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="minimum_quantity">Minimum Product
                                                            Quantity</label>
                                                        <input type="number" class="form-control" id="minimum_quantity"
                                                            name="minimum_quantity" value="1" min="1">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label required-field"
                                                            for="regular_price">Product Regular Price (MRP)</label>
                                                        <input type="number" step="0.01" class="form-control"
                                                            id="regular_price" name="regular_price" required
                                                            placeholder="0.00">
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="offer_price">Product Offer
                                                            Price</label>
                                                        <input type="number" step="0.01" class="form-control"
                                                            id="offer_price" name="offer_price" placeholder="0.00">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="sort_order">Sort Order</label>
                                                        <input type="number" class="form-control" id="sort_order"
                                                            name="sort_order" value="0">
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="out_of_stock_status">Product Out
                                                            of Stock Status</label>
                                                        <select class="form-control" id="out_of_stock_status"
                                                            name="out_of_stock_status">
                                                            <option value="in_stock">In Stock</option>
                                                            <option value="out_of_stock">Out of Stock</option>
                                                            <option value="pre_order">Pre Order</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="bulk_price_variance">Bulk Order Price
                                                        Variance</label>
                                                    <textarea class="form-control" id="bulk_price_variance"
                                                        name="bulk_price_variance" rows="3"
                                                        placeholder="Enter bulk pricing details, e.g., 10-50 pieces: ₹X, 51-100 pieces: ₹Y"></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="description">Short
                                                        Description</label>
                                                    <textarea class="form-control" id="description" name="description"
                                                        rows="3" required></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="long_description">Product Long
                                                        Description</label>
                                                    <textarea class="form-control" id="long_description"
                                                        name="long_description" rows="5"></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="instructions">Long
                                                        Instructions</label>
                                                    <textarea class="form-control" id="instructions" name="instructions"
                                                        rows="5"></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="delivery_info">Delivery
                                                        Information</label>
                                                    <textarea class="form-control" id="delivery_info"
                                                        name="delivery_info" rows="5"></textarea>
                                                </div>

                                                <script>
                                                    CKEDITOR.replace('description');
                                                    CKEDITOR.replace('long_description');
                                                    CKEDITOR.replace('instructions');
                                                    CKEDITOR.replace('delivery_info');
                                                </script>
                                            </div>

                                            <!-- Step 2: Filters Tab -->
                                            <div class="tab-pane fade" id="filters" role="tabpanel" data-step="2">
                                                <h4 class="mb-4">Step 2: Product Filters & Attributes</h4>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="color">Color</label>
                                                        <select class="form-control" id="color" name="color">
                                                            <option value="">Select Color</option>
                                                            <option value="red">Red</option>
                                                            <option value="blue">Blue</option>
                                                            <option value="green">Green</option>
                                                            <option value="black">Black</option>
                                                            <option value="white">White</option>
                                                            <option value="yellow">Yellow</option>
                                                            <option value="multi">Multi-color</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="type">Type</label>
                                                        <input type="text" class="form-control" id="type" name="type"
                                                            placeholder="e.g., T-shirt, Mug, Notebook">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="material">Material</label>
                                                        <input type="text" class="form-control" id="material"
                                                            name="material" placeholder="e.g., Cotton, Ceramic, Paper">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="occasion">Occasion</label>
                                                        <input type="text" class="form-control" id="occasion"
                                                            name="occasion"
                                                            placeholder="e.g., Birthday, Anniversary, Wedding">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="discount_type">Discount
                                                            Type</label>
                                                        <select class="form-control" id="discount_type"
                                                            name="discount_type">
                                                            <option value="">Select Discount</option>
                                                            <option value="percentage">Percentage</option>
                                                            <option value="fixed">Fixed Amount</option>
                                                            <option value="buy_one_get_one">Buy One Get One</option>
                                                            <option value="seasonal">Seasonal Offer</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="brand">Brand</label>
                                                        <input type="text" class="form-control" id="brand" name="brand"
                                                            placeholder="Enter brand name">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="shape">Shape</label>
                                                        <input type="text" class="form-control" id="shape" name="shape"
                                                            placeholder="e.g., Round, Square, Rectangle">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="gender">Gender</label>
                                                        <select class="form-control" id="gender" name="gender">
                                                            <option value="">Select Gender</option>
                                                            <option value="male">Male</option>
                                                            <option value="female">Female</option>
                                                            <option value="unisex">Unisex</option>
                                                            <option value="kids">Kids</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="gift_type">Gift Type</label>
                                                        <input type="text" class="form-control" id="gift_type"
                                                            name="gift_type" placeholder="e.g., Personalized, Packaged">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="ideal_for">Ideal For</label>
                                                        <input type="text" class="form-control" id="ideal_for"
                                                            name="ideal_for" placeholder="e.g., Men, Women, Students">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="customization_tech">Customization
                                                            Technology</label>
                                                        <input type="text" class="form-control" id="customization_tech"
                                                            name="customization_tech"
                                                            placeholder="e.g., Screen Printing, Embroidery, Digital Print">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label"
                                                            for="customization_location">Customization Location</label>
                                                        <input type="text" class="form-control"
                                                            id="customization_location" name="customization_location"
                                                            placeholder="e.g., Front, Back, Sleeve">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="capacity">Capacity</label>
                                                        <input type="text" class="form-control" id="capacity"
                                                            name="capacity"
                                                            placeholder="e.g., 500ml, A4 Size, 100 pages">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="ink_color">Ink Color</label>
                                                        <input type="text" class="form-control" id="ink_color"
                                                            name="ink_color" placeholder="e.g., Black, CMYK, Gold">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label class="form-label" for="features">Features</label>
                                                        <textarea class="form-control" id="features" name="features"
                                                            rows="2"
                                                            placeholder="e.g., Waterproof, Eco-friendly, Washable"></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Step 3: Categories Tab -->
                                            <div class="tab-pane fade" id="links" role="tabpanel" data-step="3">
                                                <h4 class="mb-4">Step 3: Category Selection</h4>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label required-field" for="category_id">Main
                                                            Category</label>
                                                        <select class="form-control" id="category_id" name="category_id"
                                                            required onchange="loadSubCategories(this.value)">
                                                            <option value="">Select Main Category</option>
                                                            <?php foreach ($categories as $cat): ?>
                                                                <option value="<?php echo $cat['id']; ?>">
                                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="sub_category_id">Sub
                                                            Category</label>
                                                        <select class="form-control" id="sub_category_id"
                                                            name="sub_category_id"
                                                            onchange="loadSubSubCategories(this.value)">
                                                            <option value="">Select Main Category First</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-3 col-md-6">
                                                        <label class="form-label" for="sub_sub_category_id">Sub Sub
                                                            Category</label>
                                                        <select class="form-control" id="sub_sub_category_id"
                                                            name="sub_sub_category_id">
                                                            <option value="">Select Sub Category First</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-6">
                                                        <div class="form-check mt-4 pt-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="featured" name="featured" value="1">
                                                            <label class="form-check-label" for="featured">
                                                                Featured Product
                                                            </label>
                                                        </div>
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="top_selection" name="top_selection" value="1">
                                                            <label class="form-check-label" for="top_selection">
                                                                Top Selection
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="status">Status</label>
                                                    <select class="form-control" id="status" name="status" required>
                                                        <option value="active" selected>Active</option>
                                                        <option value="inactive">Inactive</option>
                                                        <option value="draft">Draft</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Step 4: Images Tab -->
                                            <div class="tab-pane fade" id="images" role="tabpanel" data-step="4">
                                                <h4 class="mb-4">Step 4: Product Images</h4>

                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="alt_tag">ALT
                                                        Tag</label>
                                                    <input type="text" class="form-control" id="alt_tag" name="alt_tag"
                                                        required placeholder="Alternative text for images">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label required-field"
                                                        for="thumbnail_image">Thumbnail Image</label>
                                                    <input type="file" class="form-control" id="thumbnail_image"
                                                        name="thumbnail_image" accept="image/*" required
                                                        onchange="previewImage(this, 'thumbnailPreview')">
                                                    <small class="text-muted">Recommended size: 300x300 pixels</small>
                                                    <div id="thumbnailPreview" class="image-preview-container"></div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label required-field" for="main_images">Main
                                                        Product Images</label>
                                                    <input type="file" class="form-control" id="main_images"
                                                        name="main_images[]" multiple accept="image/*" required
                                                        onchange="previewMultipleImages(this, 'mainImagesPreview')">
                                                    <small class="text-muted">You can select multiple images (JPEG, PNG,
                                                        WebP). First image will be primary.</small>
                                                    <div id="mainImagesPreview" class="image-preview-container"></div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Additional Gallery Images
                                                        (Optional)</label>
                                                    <div class="input-group mb-2">
                                                        <input type="file" class="form-control" id="gallery_images"
                                                            name="gallery_images[]" multiple accept="image/*"
                                                            onchange="previewMultipleImages(this, 'galleryPreview')">
                                                        <button class="btn btn-outline-secondary" type="button"
                                                            onclick="document.getElementById('gallery_images').value = ''; document.getElementById('galleryPreview').innerHTML = '';">
                                                            Clear
                                                        </button>
                                                    </div>
                                                    <small class="text-muted">Optional additional images for product
                                                        gallery</small>
                                                    <div id="galleryPreview" class="image-preview-container"></div>
                                                </div>
                                            </div>

                                            <!-- Step 5: Review Tab -->
                                            <div class="tab-pane fade" id="review" role="tabpanel" data-step="5">
                                                <h4 class="mb-4">Step 5: Review & Submit</h4>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="card mb-3">
                                                            <div class="card-header bg-primary text-white">
                                                                <h6 class="mb-0">Product Details</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <td><strong>Product Name:</strong></td>
                                                                        <td id="review-name"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>SKU Code:</strong></td>
                                                                        <td id="review-sku"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Regular Price:</strong></td>
                                                                        <td id="review-price"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Offer Price:</strong></td>
                                                                        <td id="review-offer-price"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Quantity:</strong></td>
                                                                        <td id="review-quantity"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Status:</strong></td>
                                                                        <td id="review-status"></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card mb-3">
                                                            <div class="card-header bg-success text-white">
                                                                <h6 class="mb-0">Category Details</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <td><strong>Main Category:</strong></td>
                                                                        <td id="review-category"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Sub Category:</strong></td>
                                                                        <td id="review-sub-category"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Sub Sub Category:</strong></td>
                                                                        <td id="review-sub-sub-category"></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card mb-3">
                                                    <div class="card-header bg-info text-white">
                                                        <h6 class="mb-0">Selected Images</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div id="review-images" class="image-preview-container"></div>
                                                    </div>
                                                </div>

                                                <div class="alert alert-info p-2">
                                                    <h6><i class="fas fa-info-circle"></i> Please Review Before
                                                        Submitting</h6>
                                                    <p class="mb-0">Check all information carefully. Once submitted, you
                                                        can edit the product from the products list.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Navigation Buttons -->
                                        <div class="btn-navigation">
                                            <button type="button" class="btn btn-secondary" id="prevBtn"
                                                onclick="prevStep()" style="display: none;">
                                                <i class="fas fa-arrow-left"></i> Previous
                                            </button>
                                            <div>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    id="saveDraftBtn" style="display: none;">
                                                    Save as Draft
                                                </button>
                                                <button type="button" class="btn btn-primary" id="nextBtn"
                                                    onclick="nextStep()">
                                                    Next <i class="fas fa-arrow-right"></i>
                                                </button>
                                                <button type="submit" class="btn btn-success" id="submitBtn"
                                                    style="display: none;">
                                                    <i class="fas fa-check"></i> Submit Product
                                                </button>
                                            </div>
                                        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Step management
        let currentStep = 1;
        const totalSteps = 5;

        function updateStepIndicator() {
            // Update step indicators
            document.querySelectorAll('.step').forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active', 'completed');
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                }
            });

            // Update progress bar
            const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.querySelector('.progress-bar').style.width = `${progressPercent}%`;

            // Show/hide navigation buttons
            document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-block' : 'none';
            document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'inline-block' : 'none';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-block' : 'none';
            document.getElementById('saveDraftBtn').style.display = currentStep > 1 ? 'inline-block' : 'none';

            // Switch tabs
            const tabs = document.querySelectorAll('.tab-pane');
            tabs.forEach(tab => {
                tab.classList.remove('show', 'active');
                if (parseInt(tab.dataset.step) === currentStep) {
                    tab.classList.add('show', 'active');
                }
            });

            // Update review data on step 5
            if (currentStep === 5) {
                updateReviewData();
            }
        }

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepIndicator();
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepIndicator();
            }
        }

        function validateCurrentStep() {
            const currentTab = document.querySelector(`.tab-pane[data-step="${currentStep}"]`);
            const requiredFields = currentTab.querySelectorAll('[required]');

            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    alert(`Please fill in the required field: ${field.labels[0]?.textContent || field.placeholder}`);
                    return false;
                }
            }

            return true;
        }

        // Auto-generate slug from product name
        document.getElementById('product_name').addEventListener('input', function () {
            const slugField = document.getElementById('product_slug');
            if (!slugField.value) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim();
                slugField.value = slug;
            }
        });

        // Image preview functions
        function previewImage(input, previewContainerId) {
            const previewContainer = document.getElementById(previewContainerId);
            previewContainer.innerHTML = '';

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewMultipleImages(input, previewContainerId) {
            const previewContainer = document.getElementById(previewContainerId);
            previewContainer.innerHTML = '';

            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = previewContainerId === 'galleryPreview' ? 'gallery-preview' : 'image-preview';
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(input.files[i]);
                }
            }
        }

        // Category loading functions
        function loadSubCategories(mainCategoryId) {
            if (!mainCategoryId) {
                document.getElementById('sub_category_id').innerHTML = '<option value="">Select Main Category First</option>';
                document.getElementById('sub_sub_category_id').innerHTML = '<option value="">Select Sub Category First</option>';
                return;
            }

            fetch(`ajax/get-sub-categories.php?parent_id=${mainCategoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('sub_category_id');
                    select.innerHTML = '<option value="">Select Sub Category</option>';

                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });

                    document.getElementById('sub_sub_category_id').innerHTML = '<option value="">Select Sub Category First</option>';
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function loadSubSubCategories(subCategoryId) {
            if (!subCategoryId) {
                document.getElementById('sub_sub_category_id').innerHTML = '<option value="">Select Sub Category First</option>';
                return;
            }

            fetch(`ajax/get-sub-sub-categories.php?parent_id=${subCategoryId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('sub_sub_category_id');
                    select.innerHTML = '<option value="">Select Sub Sub Category</option>';

                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Update review data
        function updateReviewData() {
            // Product details
            document.getElementById('review-name').textContent = document.getElementById('product_name').value || 'Not set';
            document.getElementById('review-sku').textContent = document.getElementById('sku_code').value || 'Not set';
            document.getElementById('review-price').textContent = '₹' + (document.getElementById('regular_price').value || '0.00');
            document.getElementById('review-offer-price').textContent = document.getElementById('offer_price').value ? '₹' + document.getElementById('offer_price').value : 'Not set';
            document.getElementById('review-quantity').textContent = document.getElementById('product_quantity').value || '0';
            document.getElementById('review-status').textContent = document.getElementById('status').options[document.getElementById('status').selectedIndex].text;

            // Category details
            const mainCatSelect = document.getElementById('category_id');
            const subCatSelect = document.getElementById('sub_category_id');
            const subSubCatSelect = document.getElementById('sub_sub_category_id');

            document.getElementById('review-category').textContent = mainCatSelect.options[mainCatSelect.selectedIndex]?.text || 'Not set';
            document.getElementById('review-sub-category').textContent = subCatSelect.options[subCatSelect.selectedIndex]?.text || 'Not set';
            document.getElementById('review-sub-sub-category').textContent = subSubCatSelect.options[subSubCatSelect.selectedIndex]?.text || 'Not set';

            // Update images preview
            updateReviewImages();
        }

        function updateReviewImages() {
            const reviewContainer = document.getElementById('review-images');
            reviewContainer.innerHTML = '';

            // Thumbnail
            const thumbnailInput = document.getElementById('thumbnail_image');
            if (thumbnailInput.files && thumbnailInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'gallery-preview';
                    img.title = 'Thumbnail';
                    reviewContainer.appendChild(img);
                }
                reader.readAsDataURL(thumbnailInput.files[0]);
            }

            // Main images
            const mainImagesInput = document.getElementById('main_images');
            if (mainImagesInput.files) {
                for (let i = 0; i < mainImagesInput.files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'gallery-preview';
                        img.title = `Main Image ${i + 1}`;
                        reviewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(mainImagesInput.files[i]);
                }
            }

            // Gallery images
            const galleryInput = document.getElementById('gallery_images');
            if (galleryInput.files) {
                for (let i = 0; i < galleryInput.files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'gallery-preview';
                        img.title = `Gallery Image ${i + 1}`;
                        reviewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(galleryInput.files[i]);
                }
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            updateStepIndicator();

            // Save as draft functionality
            document.getElementById('saveDraftBtn').addEventListener('click', function () {
                document.getElementById('status').value = 'draft';
                if (validateCurrentStep()) {
                    document.getElementById('productForm').submit();
                }
            });
        });
    </script>
</body>

</html>