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
// $categories = $productController->getCategories();

$category = new CategoryController($db);
$categories = $category->getAllCategories();

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

                                    <form method="POST" enctype="multipart/form-data">
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
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="category_id">Category *</label>
                                                <select id="category_id" name="category_id" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
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
    <script src="js/app.js"></script>
</body>
</html>