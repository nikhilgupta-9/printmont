<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');
require_once(__DIR__ . '/controllers/ProductController.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Auth check
$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$productController = new ProductController($db);
$categoryController = new CategoryController($db);

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID supplied (e.g. clicked from sidebar), pick the first active product
if ($productId <= 0) {
    $firstProd = $database->fetch("SELECT id FROM products ORDER BY id DESC LIMIT 1");
    if ($firstProd) {
        $productId = (int)$firstProd['id'];
    }
}

$product = $productId > 0 ? $productController->getProductById($productId) : null;

// Get all products for quick switcher dropdown
$allProductsList = $database->fetchAll("SELECT id, name, sku FROM products ORDER BY name ASC LIMIT 100") ?: [];

// Get product images
$images = [];
if ($product) {
    $images = $database->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC", [$productId]) ?: [];
    if (empty($images) && !empty($product['thumbnail_image'])) {
        $images[] = ['image_url' => $product['thumbnail_image'], 'is_primary' => 1];
    }
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Single Product Preview'; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .main-gallery-frame {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 380px;
            max-height: 480px;
            overflow: hidden;
            position: relative;
        }
        .main-gallery-frame img {
            max-width: 100%;
            max-height: 420px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .thumbnail-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
        }
        .thumb-preview-card {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: #fff;
            padding: 4px;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .thumb-preview-card.active, .thumb-preview-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .thumb-preview-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.9rem;
        }
        .spec-item:last-child {
            border-bottom: none;
        }
        .badge-pill-soft {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .price-hero {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem;
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
                    
                    <!-- Header Bar -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800"><strong>Single Product</strong> Preview</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="view-products.php">Products</a></li>
                                    <li class="breadcrumb-item active"><?php echo $product ? htmlspecialchars($product['name']) : 'Preview'; ?></li>
                                </ol>
                            </nav>
                        </div>

                        <!-- Actions & Quick Switcher -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="d-flex align-items-center">
                                <select class="form-select form-select-sm" style="max-width: 250px;" onchange="if(this.value) location.href='single-product-version.php?id=' + this.value;">
                                    <option value="">Switch Product...</option>
                                    <?php foreach ($allProductsList as $pItem): ?>
                                        <option value="<?php echo $pItem['id']; ?>" <?php echo ($productId == $pItem['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(mb_strimwidth($pItem['name'], 0, 30, '...')); ?> (SKU: <?php echo htmlspecialchars($pItem['sku'] ?: 'N/A'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <a href="view-products.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-list me-1"></i> All Products
                            </a>
                            <?php if ($product): ?>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-pen me-1"></i> Edit Product
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$product): ?>
                        <div class="card p-5 text-center">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <h4>No Product Found</h4>
                            <p class="text-muted">Please add products or select a valid product from the list.</p>
                            <div>
                                <a href="product.php" class="btn btn-primary">Add New Product</a>
                            </div>
                        </div>
                    <?php else: 
                        $primaryImg = !empty($images[0]['image_url']) ? $images[0]['image_url'] : 'img/icons/icon-48x48.png';
                        $price = (float)$product['price'];
                        $discountPrice = (float)($product['discount_price'] ?? 0);
                        $finalPrice = ($discountPrice > 0) ? $discountPrice : $price;
                        $hasDiscount = ($discountPrice > 0 && $discountPrice < $price);
                        $discountPercent = $hasDiscount ? round((($price - $discountPrice) / $price) * 100) : 0;
                        $stock = (int)($product['stock_quantity'] ?? $product['product_quantity'] ?? $product['stock'] ?? 0);
                    ?>
                        <div class="row g-4">
                            <!-- Left: Gallery Section -->
                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="main-gallery-frame mb-3">
                                            <img id="mainProductImg" src="<?php echo htmlspecialchars($primaryImg); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 onerror="this.src='img/icons/icon-48x48.png'">
                                        </div>

                                        <!-- Thumbnails Strip -->
                                        <?php if (!empty($images) && count($images) > 1): ?>
                                            <div class="thumbnail-strip">
                                                <?php foreach ($images as $idx => $img): ?>
                                                    <div class="thumb-preview-card <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                                         onclick="switchMainImage(this, '<?php echo htmlspecialchars($img['image_url']); ?>')">
                                                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="Thumbnail">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Highlights / Flags Card -->
                                <div class="card border-0 shadow-sm mt-3">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Catalog Flags</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if (!empty($product['our_bestseller'])): ?>
                                                <span class="badge-pill-soft" style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a;">
                                                    <i class="fas fa-fire"></i> Bestseller Product
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($product['top_selection'])): ?>
                                                <span class="badge-pill-soft" style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe;">
                                                    <i class="fas fa-crown"></i> Top Selection
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($product['featured'])): ?>
                                                <span class="badge-pill-soft" style="background: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8;">
                                                    <i class="fas fa-star"></i> Featured
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($product['status'] === 'active'): ?>
                                                <span class="badge-pill-soft" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                                                    <i class="fas fa-circle-check"></i> Active in Store
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-soft" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                                    <i class="fas fa-circle-xmark"></i> <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Product Information -->
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-body p-4">
                                        
                                        <!-- Brand & Category -->
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-uppercase fw-semibold text-primary" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                                                <?php echo htmlspecialchars($product['brand'] ?: 'Printmont Signature'); ?>
                                            </span>
                                            <span class="badge bg-light text-secondary border">
                                                <i class="fas fa-folder me-1"></i> <?php echo htmlspecialchars($product['category_name'] ?? 'General'); ?>
                                            </span>
                                        </div>

                                        <!-- Product Title -->
                                        <h2 class="h4 fw-bold text-gray-900 mb-2">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h2>

                                        <!-- SKU & Stock -->
                                        <div class="d-flex align-items-center gap-3 mb-3 text-muted small">
                                            <span>SKU: <strong class="text-dark font-monospace"><?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></strong></span>
                                            <span>•</span>
                                            <span>Stock: 
                                                <?php if ($stock > 10): ?>
                                                    <strong class="text-success"><?php echo $stock; ?> Available</strong>
                                                <?php elseif ($stock > 0): ?>
                                                    <strong class="text-warning"><?php echo $stock; ?> Low Stock</strong>
                                                <?php else: ?>
                                                    <strong class="text-danger">Out of Stock</strong>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <!-- Price Block (Indian Rupees ₹) -->
                                        <div class="price-hero mb-4">
                                            <div class="d-flex align-items-baseline gap-2 mb-1">
                                                <span class="h2 fw-bold text-dark mb-0">₹<?php echo number_format($finalPrice, 2); ?></span>
                                                <?php if ($hasDiscount): ?>
                                                    <span class="h5 text-muted text-decoration-line-through mb-0">₹<?php echo number_format($price, 2); ?></span>
                                                    <span class="badge bg-danger rounded-pill px-2 py-1 ms-2 font-weight-bold">
                                                        <?php echo $discountPercent; ?>% OFF
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-info-circle me-1"></i> Inclusive of GST and all statutory taxes.
                                            </div>
                                        </div>

                                        <!-- Short Description -->
                                        <?php if (!empty($product['short_description'])): ?>
                                            <div class="mb-4">
                                                <h6 class="fw-bold text-dark mb-2">Overview</h6>
                                                <p class="text-secondary leading-relaxed mb-0">
                                                    <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Specifications Table -->
                                        <div class="mb-4">
                                            <h6 class="fw-bold text-dark mb-2">Product Specifications</h6>
                                            <div class="bg-light p-3 rounded-3 border">
                                                <div class="spec-item">
                                                    <span class="text-muted">Product ID</span>
                                                    <span class="fw-medium text-dark">#<?php echo $product['id']; ?></span>
                                                </div>
                                                <div class="spec-item">
                                                    <span class="text-muted">Brand / Collection</span>
                                                    <span class="fw-medium text-dark"><?php echo htmlspecialchars($product['brand'] ?: 'Printmont'); ?></span>
                                                </div>
                                                <div class="spec-item">
                                                    <span class="text-muted">Status</span>
                                                    <span class="fw-medium text-dark"><?php echo ucfirst($product['status']); ?></span>
                                                </div>
                                                <div class="spec-item">
                                                    <span class="text-muted">Added On</span>
                                                    <span class="fw-medium text-dark"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                                                </div>
                                                <?php if (!empty($product['updated_at'])): ?>
                                                    <div class="spec-item">
                                                        <span class="text-muted">Last Modified</span>
                                                        <span class="fw-medium text-dark"><?php echo date('M d, Y H:i', strtotime($product['updated_at'])); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Full Description / Content -->
                                        <?php if (!empty($product['description'])): ?>
                                            <div class="mb-4">
                                                <h6 class="fw-bold text-dark mb-2">Full Description</h6>
                                                <div class="text-secondary border rounded p-3 bg-white" style="font-size: 0.92rem; line-height: 1.6;">
                                                    <?php echo $product['description']; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Quick Action Controls -->
                                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-pen me-1"></i> Edit Product Details
                                            </a>
                                            <a href="view-products.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left me-1"></i> Back to Product List
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function switchMainImage(thumbElement, imgUrl) {
            document.getElementById('mainProductImg').src = imgUrl;
            document.querySelectorAll('.thumb-preview-card').forEach(card => card.classList.remove('active'));
            thumbElement.classList.add('active');
        }
    </script>
</body>
</html>