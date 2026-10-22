<?php
/**
 * Add / Edit Home Product — Printmont Admin
 * 
 * Assigns products to specific home page carousels and display patterns.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$error = '';
$success = '';

// Load active products for picker
$products = [];
$pRes = $db->query("SELECT id, name, sku, price, regular_price, offer_price, thumbnail_image, category_id FROM products WHERE status = 'active' ORDER BY name ASC LIMIT 500");
if ($pRes) {
    while ($r = $pRes->fetch_assoc()) {
        $products[] = $r;
    }
}

// Load carousels
$carousels = [];
$cRes = $db->query("SELECT id, title FROM carousels WHERE status = 'active' ORDER BY title ASC");
if ($cRes) {
    while ($r = $cRes->fetch_assoc()) {
        $carousels[] = $r;
    }
}

// Load categories
$categories = [];
$catRes = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($catRes) {
    while ($r = $catRes->fetch_assoc()) {
        $categories[] = $r;
    }
}

$item = [
    'product_id'               => '',
    'home_carousel_id'         => '',
    'desktop_home_carousel_id' => '',
    'home_category_id'         => '',
    'desktop_home_design'      => 'design-1',
    'desktop_home_show'        => 'yes',
    'mobile_home_show'         => 'yes',
    'mobile_home_design'       => 'design-1',
    'mobile_home_carousel_id'  => '',
    'is_sponsored'             => 0,
    'sort_order'               => 0,
    'status'                   => 'active'
];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM home_products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $item = array_merge($item, $row);
    } else {
        $_SESSION['error_message'] = "Home product record #$id not found.";
        header('Location: home-products.php');
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id               = (int)($_POST['product_id'] ?? 0);
    $carousel_id              = !empty($_POST['home_carousel_id']) ? (int)$_POST['home_carousel_id'] : null;
    $home_category_id         = !empty($_POST['home_category_id']) ? (int)$_POST['home_category_id'] : null;
    $desktop_home_design      = $_POST['desktop_home_design'] ?? 'design-1';
    $desktop_home_show        = ($_POST['desktop_home_show'] ?? 'yes') === 'no' ? 'no' : 'yes';
    $mobile_home_show         = ($_POST['mobile_home_show'] ?? 'yes') === 'no' ? 'no' : 'yes';
    $mobile_home_design       = $_POST['mobile_home_design'] ?? 'design-1';
    $mobile_home_carousel_id  = !empty($_POST['mobile_home_carousel_id']) ? (int)$_POST['mobile_home_carousel_id'] : $carousel_id;
    $is_sponsored             = isset($_POST['is_sponsored']) ? 1 : 0;
    $sort_order               = (int)($_POST['sort_order'] ?? 0);
    $status                   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($product_id <= 0) {
        $error = "Please select a product.";
    } else {
        if ($is_edit) {
            $stmt = $db->prepare("UPDATE home_products SET
                product_id = ?,
                desktop_home_carousel_id = ?, home_carousel_id = ?,
                home_category_id = ?,
                desktop_home_design = ?, desktop_home_show = ?,
                mobile_home_show = ?, mobile_home_design = ?, mobile_home_carousel_id = ?,
                is_sponsored = ?, sort_order = ?, display_order = ?,
                status = ?, updated_at = NOW()
                WHERE id = ?");

            $stmt->bind_param('iiiissssiiissi',
                $product_id,
                $carousel_id, $carousel_id,
                $home_category_id,
                $desktop_home_design, $desktop_home_show,
                $mobile_home_show, $mobile_home_design, $mobile_home_carousel_id,
                $is_sponsored, $sort_order, $sort_order,
                $status,
                $id
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Home product assignment updated successfully!";
                header('Location: home-products.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $stmt = $db->prepare("INSERT INTO home_products (
                product_id,
                desktop_home_carousel_id, home_carousel_id,
                home_category_id,
                desktop_home_design, desktop_home_show,
                mobile_home_show, mobile_home_design, mobile_home_carousel_id,
                is_sponsored, sort_order, display_order,
                status, created_at
            ) VALUES (
                ?,
                ?, ?,
                ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, NOW()
            )");

            $stmt->bind_param('iiiissssiiiss',
                $product_id,
                $carousel_id, $carousel_id,
                $home_category_id,
                $desktop_home_design, $desktop_home_show,
                $mobile_home_show, $mobile_home_design, $mobile_home_carousel_id,
                $is_sponsored, $sort_order, $sort_order,
                $status
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Product assigned to Home Page successfully!";
                header('Location: home-products.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
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
    <title><?php echo $is_edit ? 'Edit Home Product' : 'Assign Product to Home'; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #03172c;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title i { color: #00529b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3 align-items-center">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong><?php echo $is_edit ? 'Edit' : 'Assign'; ?></strong> Home Product</h1>
                            <p class="text-muted small">Place products into featured carousels and home display grids.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="home-products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Home Products
                            </a>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-box"></i> Product Selection & Placement
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Select Product <span class="text-danger">*</span></label>
                                        <select name="product_id" class="form-select" required>
                                            <option value="">-- Choose Product --</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo (int)$item['product_id'] === (int)$p['id'] ? 'selected' : ''; ?>>
                                                    #<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['name']); ?> (₹<?php echo number_format($p['offer_price'] ?: $p['price'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Assign to Carousel</label>
                                        <select name="home_carousel_id" class="form-select">
                                            <option value="">-- Standalone (No Carousel) --</option>
                                            <?php foreach ($carousels as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" <?php echo ((int)$item['home_carousel_id'] === (int)$c['id'] || (int)$item['desktop_home_carousel_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($c['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Home Category Section</label>
                                        <select name="home_category_id" class="form-select">
                                            <option value="">-- Match Product Category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)$item['home_category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Display Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$item['sort_order']; ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Desktop & Mobile Visibility -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-sliders-h"></i> Display Patterns & Device Visibility
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Show on Desktop Home?</label>
                                        <select name="desktop_home_show" class="form-select">
                                            <option value="yes" <?php echo $item['desktop_home_show'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo $item['desktop_home_show'] === 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Desktop Design Pattern</label>
                                        <select name="desktop_home_design" class="form-select">
                                            <option value="design-1" <?php echo $item['desktop_home_design'] === 'design-1' ? 'selected' : ''; ?>>Design 1 (4-Grid Card)</option>
                                            <option value="design-2" <?php echo $item['desktop_home_design'] === 'design-2' ? 'selected' : ''; ?>>Design 2 (Scroll Slider)</option>
                                            <option value="design-3" <?php echo $item['desktop_home_design'] === 'design-3' ? 'selected' : ''; ?>>Design 3 (Cards with Badges)</option>
                                            <option value="design-4" <?php echo $item['desktop_home_design'] === 'design-4' ? 'selected' : ''; ?>>Design 4 (Hero Spotlight)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Show on Mobile Home?</label>
                                        <select name="mobile_home_show" class="form-select">
                                            <option value="yes" <?php echo $item['mobile_home_show'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo $item['mobile_home_show'] === 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Mobile Design Pattern</label>
                                        <select name="mobile_home_design" class="form-select">
                                            <option value="design-1" <?php echo $item['mobile_home_design'] === 'design-1' ? 'selected' : ''; ?>>Design 1 (2-Column Grid)</option>
                                            <option value="design-2" <?php echo $item['mobile_home_design'] === 'design-2' ? 'selected' : ''; ?>>Design 2 (Single Swiper)</option>
                                            <option value="design-3" <?php echo $item['mobile_home_design'] === 'design-3' ? 'selected' : ''; ?>>Design 3 (Stacked Rows)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_sponsored" id="is_sponsored" value="1" <?php echo $item['is_sponsored'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="is_sponsored">Mark as Sponsored / Featured Ad Product</label>
                                            <div class="text-muted small">Adds a subtle "Sponsored" pill on the storefront card.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <a href="home-products.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'Update Assignment' : 'Assign Product'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
