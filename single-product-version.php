<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/ProductController.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view-products.php');
    exit;
}

$productController = new ProductController($db);
$categoryController = new CategoryController($db);

$product = $productController->getProductById($_GET['id']);

if (!$product) {
    $_SESSION['error_message'] = "Product not found!";
    header('Location: view-products.php');
    exit;
}

// Get breadcrumb categories (you might need to adjust this based on your category structure)
$categories = $categoryController->getAllCategories();
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
    <title><?php echo htmlspecialchars($product['name']); ?> | PrintMont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .product-gallery img { width: 100%; border-radius: 10px; cursor: pointer; }
        .thumbnail { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; }
        .thumbnail.active { border-color: #007bff; }
        .breadcrumb { background: transparent; padding: 0; margin-bottom: 20px; }
        .price-section { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .original-price { text-decoration: line-through; color: #6c757d; font-size: 1.1rem; }
        .discount-price { color: #dc3545; font-size: 1.8rem; font-weight: bold; }
        .current-price { color: #198754; font-size: 1.8rem; font-weight: bold; }
        .discount-badge { background: #dc3545; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem; margin-left: 10px; }
        .size-option { border: 2px solid #dee2e6; border-radius: 8px; padding: 12px 20px; text-align: center; cursor: pointer; margin: 5px; transition: all 0.3s; }
        .size-option:hover { border-color: #007bff; }
        .size-option.selected { border-color: #007bff; background-color: #007bff; color: white; }
        .coupon-card { border: 2px dashed #28a745; border-radius: 10px; padding: 15px; margin: 10px 0; background: #f8fff9; }
        .offer-card { border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin: 8px 0; }
        .delivery-info { background: #e7f3ff; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .rating-stars { color: #ffc107; }
        .service-badge { background: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; margin-right: 10px; }
        .color-option { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; margin: 5px; }
        .color-option.selected { border-color: #007bff; }
        .action-buttons .btn { margin-right: 10px; margin-bottom: 10px; }
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
                            <h3><strong>Product</strong> Details</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-products.php" class="btn btn-light bg-white me-2">Back to Products</a>
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Edit Product</a>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Product Images Gallery -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="product-gallery">
                                        <div class="main-image mb-3">
                                            <img src="<?php echo $product['images'][0]['image_url'] ?? 'https://via.placeholder.com/500x500'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 id="mainImage" class="img-fluid">
                                        </div>
                                        <div class="thumbnail-list d-flex flex-wrap gap-2">
                                            <?php foreach ($product['images'] as $index => $image): ?>
                                                <img src="<?php echo $image['image_url']; ?>" 
                                                     alt="Thumbnail <?php echo $index + 1; ?>" 
                                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                     onclick="changeImage(this, '<?php echo $image['image_url']; ?>')">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Brand and Name -->
                                    <h2 class="h4"><?php echo htmlspecialchars($product['brand']); ?></h2>
                                    <h1 class="h3 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                                    <!-- Rating -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rating-stars me-2">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                        </div>
                                        <!-- <span class="text-muted">25,600 ratings and 990 reviews</span> -->
                                    </div>

                                    <!-- Price Section -->
                                    <div class="price-section">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($product['discount_price']): ?>
                                                <span class="discount-price">&#8377; <?php echo number_format($product['discount_price'], 2); ?></span>
                                                <span class="original-price ms-2">&#8377; <?php echo number_format($product['price'], 2); ?></span>
                                                <span class="discount-badge">
                                                    <?php 
                                                        $discount = (($product['price'] - $product['discount_price']) / $product['price']) * 100;
                                                        echo round($discount) . '% off';
                                                    ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="current-price">¥<?php echo number_format($product['price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">Inclusive of all taxes</small>
                                    </div>

                                    <!-- Coupon Section -->
                                    <div class="coupon-card">
                                        <h6 class="mb-2"><i class="fas fa-tag text-success me-2"></i>Coupons for you</h6>
                                        <p class="mb-1">Special Price Get extra 18% off upto ¥360 on 50 item(s) (price inclusive of cashback/coupon)</p>
                                        <small class="text-muted">**T&C**</small>
                                    </div>

                                    <!-- Available Offers -->
                                    <div class="mb-4">
                                        <h6 class="mb-3">Available offers</h6>
                                        <div class="offer-list">
                                            <div class="offer-card">
                                                <strong>Bank Offer</strong> 5% cashback on Axis Bank Flipkart Debit Card up to ¥750 <strong>T&C</strong>
                                            </div>
                                            <div class="offer-card">
                                                <strong>Bank Offer</strong> 5% cashback on Flipkart SBI Credit Card upto ¥4,000 per calendar quarter <strong>T&C</strong>
                                            </div>
                                            <div class="offer-card">
                                                <strong>Bank Offer</strong> Flat ¥50 off on Flipkart Bajaj Finserv Insta EMI Card. Min Booking Amount: ¥2,500 <strong>T&C</strong>
                                            </div>
                                            <a href="#" class="text-primary">+11 more offers</a>
                                        </div>
                                    </div>

                                    <!-- Delivery Information -->
                                    <div class="delivery-info">
                                        <h6 class="mb-3"><i class="fas fa-truck me-2"></i>Deliver to</h6>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" placeholder="Enter delivery pincode">
                                            <button class="btn btn-primary" type="button">Check</button>
                                        </div>
                                        <p class="mb-1"><strong>Delivery by 20 Nov, Thursday</strong></p>
                                        <p class="text-muted mb-2">If ordered before 7:59 AM</p>
                                        <a href="#" class="text-primary">View Details</a>
                                    </div>

                                    <!-- Color Selection -->
                                    <div class="mb-4">
                                        <h6 class="mb-3">Color</h6>
                                        <div class="d-flex">
                                            <div class="color-option selected" style="background-color: #6c757d;" title="Grey"></div>
                                            <div class="color-option" style="background-color: #000000;" title="Black"></div>
                                            <div class="color-option" style="background-color: #dc3545;" title="Red"></div>
                                            <div class="color-option" style="background-color: #0d6efd;" title="Blue"></div>
                                        </div>
                                    </div>

                                    <!-- Size Selection -->
                                    <div class="mb-4">
                                        <h6 class="mb-3">Size - UK/India</h6>
                                        <div class="d-flex flex-wrap">
                                            <?php 
                                            $sizes = [6, 7, 8, 9, 10, 11, 12];
                                            foreach ($sizes as $size): 
                                            ?>
                                                <div class="size-option" onclick="selectSize(this)">
                                                    <?php echo $size; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <a href="#" class="text-primary mt-2 d-inline-block">Size Chart</a>
                                    </div>

                                    <!-- Services -->
                                    <div class="mb-4">
                                        <h6 class="mb-3">Services</h6>
                                        <span class="service-badge"><i class="fas fa-money-bill-wave me-1"></i> Cash on Delivery available</span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-lg"><i class="fas fa-shopping-cart me-2"></i>ADD TO CART</button>
                                        <button class="btn btn-success btn-lg"><i class="fas fa-bolt me-2"></i>BUY NOW</button>
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
    <script>
        function changeImage(thumbElement, imageUrl) {
            // Update main image
            document.getElementById('mainImage').src = imageUrl;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbElement.classList.add('active');
        }

        function selectSize(sizeElement) {
            // Remove selected class from all sizes
            document.querySelectorAll('.size-option').forEach(size => {
                size.classList.remove('selected');
            });
            
            // Add selected class to clicked size
            sizeElement.classList.add('selected');
        }

        // Select first size by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstSize = document.querySelector('.size-option');
            if (firstSize) {
                firstSize.classList.add('selected');
            }
        });
    </script>
</body>
</html>