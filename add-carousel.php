<?php
require_once __DIR__ . '/includes/image-upload-helper.php';
/**
 * Add / Edit Product Carousel — Printmont Admin
 * 
 * Manages desktop and mobile product carousel sections on Home and Other pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/includes/banner-helpers.php';

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

// "Position relative to an existing section" picker (Manage Carousels spec): the admin picks
// an anchor type + a specific section of that type + before/after, and JS fills the sort_order
// number field with a suggested value. Desktop and mobile get their own anchor lists since each
// device has its own independent order.
$anchorSectionTypes = ANCHOR_SECTION_TYPES();
$anchorOptionsByDevice = [
    'desktop' => [],
    'mobile'  => [],
];
foreach (array_keys($anchorSectionTypes) as $anchorType) {
    $anchorOptionsByDevice['desktop'][$anchorType] = getAnchorSectionOptions($db, $anchorType, 'desktop');
    $anchorOptionsByDevice['mobile'][$anchorType] = getAnchorSectionOptions($db, $anchorType, 'mobile');
}

// Load categories for dropdowns
$categories = [];
$catRes = $db->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($catRes) {
    while ($row = $catRes->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Load products for Extra Featured Product block reference
$productList = [];
$prodRes = $db->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name ASC LIMIT 100");
if ($prodRes) {
    while ($p = $prodRes->fetch_assoc()) {
        $productList[] = $p;
    }
}

// Default values
$requestedType = isset($_GET['type']) && $_GET['type'] === 'category' ? 'category' : 'product';

$item = [
    'title'                     => '',
    'carousel_type'             => $requestedType,
    'page_target'               => 'both',
    'slogan'                    => '',
    'show_slogan'               => 1,
    'slug'                      => '',
    'extra_product_id'          => '',
    'desktop_home_category_id'  => '',
    'desktop_home_show'         => 'yes',
    'desktop_home_design'       => 'design-1',
    'desktop_bg_color'          => '#ffffff',
    'desktop_bg_image'          => '',
    'desktop_show_other_pages'  => 'no',
    'desktop_other_category_id' => '',
    'desktop_sort_order'        => 0,
    'desktop_anchor_type'       => '',
    'desktop_anchor_id'         => '',
    'desktop_anchor_position'   => 'after',
    'desktop_status'            => 'active',
    'mobile_home_show'          => 'yes',
    'mobile_home_category_id'   => '',
    'mobile_home_design'        => 'design-1',
    'mobile_carousel_format'    => '2.5',
    'mobile_bg_color'           => '#ffffff',
    'mobile_bg_image'           => '',
    'mobile_show_other_pages'   => 'no',
    'mobile_other_category_id'  => '',
    'mobile_sort_order'         => 0,
    'mobile_anchor_type'        => '',
    'mobile_anchor_id'          => '',
    'mobile_anchor_position'    => 'after',
    'mobile_status'             => 'active',
    'show_in_review'            => 0,
    'review_design_type'        => '',
    'show_in_offer'             => 0,
    'offer_design_type'         => '',
    'alt_text'                  => '',
    'description'               => '',
    'target_page'               => 'home',
    'other_pages_display'       => '[]',
    'show_on_category'          => 'no',
    'show_on_product'           => 'no',
    'show_on_product_detail'    => 'no',
    'show_on_franchise'         => 'no',
    'show_on_affiliate'         => 'no',
    'show_on_career'            => 'no',
    'show_on_business_solutions'=> 'no',
    'show_on_advertising'       => 'no',
    'show_on_about_us'          => 'no',
    'meta_title'                => '',
    'meta_keywords'             => '',
    'meta_description'          => '',
    'status'                    => 'active'
];

// If editing, load existing carousel
if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM carousels WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $item = array_merge($item, $row);
    } else {
        $_SESSION['error_message'] = "Carousel #$id not found.";
        header('Location: carousels.php');
        exit();
    }
    $stmt->close();
}

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $carousel_type = ($_POST['carousel_type'] ?? 'product') === 'category' ? 'category' : 'product';
    $page_target   = in_array($_POST['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $_POST['page_target'] : 'both';
    $slogan = trim($_POST['slogan'] ?? '');
    $show_slogan = isset($_POST['show_slogan']) ? 1 : 0;
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }
    $extra_product_id          = !empty($_POST['extra_product_id']) ? (int)$_POST['extra_product_id'] : null;

    $desktop_home_show         = ($_POST['desktop_home_show'] ?? 'yes') === 'no' ? 'no' : 'yes';
    $desktop_home_category_id  = !empty($_POST['desktop_home_category_id']) ? (int)$_POST['desktop_home_category_id'] : null;
    $desktop_home_design       = $_POST['desktop_home_design'] ?? 'design-1';
    $desktop_bg_color          = trim($_POST['desktop_bg_color'] ?? '#ffffff');
    $desktop_show_other_pages  = ($_POST['desktop_show_other_pages'] ?? 'no') === 'yes' ? 'yes' : 'no';
    $desktop_other_category_id = !empty($_POST['desktop_other_category_id']) ? (int)$_POST['desktop_other_category_id'] : null;
    $desktop_sort_order        = is_numeric($_POST['desktop_sort_order'] ?? null) ? (float)$_POST['desktop_sort_order'] : 0;
    $desktop_anchor_type       = trim($_POST['desktop_anchor_type'] ?? '');
    $desktop_anchor_id         = !empty($_POST['desktop_anchor_id']) ? (int)$_POST['desktop_anchor_id'] : null;
    $desktop_anchor_position   = ($_POST['desktop_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';
    $desktop_status            = ($_POST['desktop_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $mobile_home_show          = ($_POST['mobile_home_show'] ?? 'yes') === 'no' ? 'no' : 'yes';
    $mobile_home_category_id   = !empty($_POST['mobile_home_category_id']) ? (int)$_POST['mobile_home_category_id'] : null;
    $mobile_home_design        = $_POST['mobile_home_design'] ?? 'design-1';
    $mobile_carousel_format    = in_array($_POST['mobile_carousel_format'] ?? '', ['1.1', '5.2', '2.5']) ? $_POST['mobile_carousel_format'] : '2.5';
    $mobile_bg_color           = trim($_POST['mobile_bg_color'] ?? '#ffffff');
    $mobile_show_other_pages   = ($_POST['mobile_show_other_pages'] ?? 'no') === 'yes' ? 'yes' : 'no';
    $mobile_other_category_id  = !empty($_POST['mobile_other_category_id']) ? (int)$_POST['mobile_other_category_id'] : null;
    $mobile_sort_order         = is_numeric($_POST['mobile_sort_order'] ?? null) ? (float)$_POST['mobile_sort_order'] : 0;
    $mobile_anchor_type        = trim($_POST['mobile_anchor_type'] ?? '');
    $mobile_anchor_id          = !empty($_POST['mobile_anchor_id']) ? (int)$_POST['mobile_anchor_id'] : null;
    $mobile_anchor_position    = ($_POST['mobile_anchor_position'] ?? 'after') === 'before' ? 'before' : 'after';
    $mobile_status             = ($_POST['mobile_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $show_in_review            = isset($_POST['show_in_review']) ? 1 : 0;
    $review_design_type        = trim($_POST['review_design_type'] ?? '');
    $show_in_offer             = isset($_POST['show_in_offer']) ? 1 : 0;
    $offer_design_type         = trim($_POST['offer_design_type'] ?? '');

    $meta_title                = trim($_POST['meta_title'] ?? '');
    $meta_keywords             = trim($_POST['meta_keywords'] ?? '');
    $meta_description          = trim($_POST['meta_description'] ?? '');
    $status                    = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $alt_text                  = trim($_POST['alt_text'] ?? '');
    $description               = trim($_POST['description'] ?? '');
    $target_page               = trim($_POST['target_page'] ?? 'home');
    if (empty($target_page)) $target_page = 'home';

    $show_on_category          = (!empty($_POST['show_on_category']) && $_POST['show_on_category'] !== 'no') ? 'yes' : 'no';
    $show_on_product           = (!empty($_POST['show_on_product']) && $_POST['show_on_product'] !== 'no') ? 'yes' : 'no';
    $show_on_product_detail    = (!empty($_POST['show_on_product_detail']) && $_POST['show_on_product_detail'] !== 'no') ? 'yes' : 'no';
    $show_on_franchise         = (!empty($_POST['show_on_franchise']) && $_POST['show_on_franchise'] !== 'no') ? 'yes' : 'no';
    $show_on_affiliate         = (!empty($_POST['show_on_affiliate']) || !empty($_POST['show_on_affiliate_program'])) ? 'yes' : 'no';
    $show_on_career            = (!empty($_POST['show_on_career']) && $_POST['show_on_career'] !== 'no') ? 'yes' : 'no';
    $show_on_business_solutions= (!empty($_POST['show_on_business_solutions']) && $_POST['show_on_business_solutions'] !== 'no') ? 'yes' : 'no';
    $show_on_advertising       = (!empty($_POST['show_on_advertising']) && $_POST['show_on_advertising'] !== 'no') ? 'yes' : 'no';
    $show_on_about_us          = (!empty($_POST['show_on_about_us']) && $_POST['show_on_about_us'] !== 'no') ? 'yes' : 'no';

    $opList = [];
    if ($show_on_category === 'yes') $opList[] = 'category';
    if ($show_on_product === 'yes') $opList[] = 'product';
    if ($show_on_product_detail === 'yes') $opList[] = 'product_detail';
    if ($show_on_franchise === 'yes') $opList[] = 'franchise';
    if ($show_on_affiliate === 'yes') $opList[] = 'affiliate_program';
    if ($show_on_career === 'yes') $opList[] = 'career';
    if ($show_on_business_solutions === 'yes') $opList[] = 'business_solutions';
    if ($show_on_advertising === 'yes') $opList[] = 'advertising';
    if ($show_on_about_us === 'yes') $opList[] = 'about_us';
    $other_pages_display = json_encode($opList);

    // File upload helper
    $uploadDir = __DIR__ . '/uploads/carousels/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $desktop_bg_image = $item['desktop_bg_image'];
    if (!empty($_FILES['desktop_bg_image']['name'])) {
        $file = $_FILES['desktop_bg_image'];
        $result = saveUploadedImageAsWebp($file, $uploadDir, 'uploads/carousels');
        if ($result['success']) {
            $desktop_bg_image = $result['path'];
        }
    }

    $mobile_bg_image = $item['mobile_bg_image'];
    if (!empty($_FILES['mobile_bg_image']['name'])) {
        $file = $_FILES['mobile_bg_image'];
        $result = saveUploadedImageAsWebp($file, $uploadDir, 'uploads/carousels');
        if ($result['success']) {
            $mobile_bg_image = $result['path'];
        }
    }

    // [column => [value, mysqli bind type]] — built as a list so the bind_param type string
    // and argument order can never drift apart (this table has grown to 40+ columns).
    $fields = [
        'title'                     => [$title, 's'],
        'carousel_type'             => [$carousel_type, 's'],
        'page_target'               => [$page_target, 's'],
        'slogan'                    => [$slogan, 's'],
        'show_slogan'               => [$show_slogan, 'i'],
        'slug'                      => [$slug, 's'],
        'extra_product_id'          => [$extra_product_id, 'i'],
        'desktop_home_category_id'  => [$desktop_home_category_id, 'i'],
        'desktop_home_show'         => [$desktop_home_show, 's'],
        'desktop_home_design'       => [$desktop_home_design, 's'],
        'desktop_bg_color'          => [$desktop_bg_color, 's'],
        'desktop_bg_image'          => [$desktop_bg_image, 's'],
        'desktop_show_other_pages'  => [$desktop_show_other_pages, 's'],
        'desktop_other_pages'       => [$desktop_show_other_pages, 's'],
        'desktop_other_category_id' => [$desktop_other_category_id, 'i'],
        'desktop_sort_order'        => [$desktop_sort_order, 'd'],
        'desktop_anchor_type'       => [$desktop_anchor_type, 's'],
        'desktop_anchor_id'         => [$desktop_anchor_id, 'i'],
        'desktop_anchor_position'   => [$desktop_anchor_position, 's'],
        'desktop_status'            => [$desktop_status, 's'],
        'mobile_home_show'          => [$mobile_home_show, 's'],
        'mobile_home_category_id'   => [$mobile_home_category_id, 'i'],
        'mobile_home_design'        => [$mobile_home_design, 's'],
        'mobile_carousel_format'    => [$mobile_carousel_format, 's'],
        'mobile_bg_color'           => [$mobile_bg_color, 's'],
        'mobile_bg_image'           => [$mobile_bg_image, 's'],
        'mobile_show_other_pages'   => [$mobile_show_other_pages, 's'],
        'mobile_other_pages'        => [$mobile_show_other_pages, 's'],
        'mobile_other_category_id'  => [$mobile_other_category_id, 'i'],
        'mobile_sort_order'         => [$mobile_sort_order, 'd'],
        'mobile_anchor_type'        => [$mobile_anchor_type, 's'],
        'mobile_anchor_id'          => [$mobile_anchor_id, 'i'],
        'mobile_anchor_position'    => [$mobile_anchor_position, 's'],
        'mobile_status'             => [$mobile_status, 's'],
        'show_in_review'            => [$show_in_review, 'i'],
        'review_design_type'        => [$review_design_type, 's'],
        'show_in_offer'             => [$show_in_offer, 'i'],
        'offer_design_type'         => [$offer_design_type, 's'],
        'meta_title'                => [$meta_title, 's'],
        'meta_keywords'             => [$meta_keywords, 's'],
        'meta_description'          => [$meta_description, 's'],
        'alt_text'                  => [$alt_text, 's'],
        'description'               => [$description, 's'],
        'target_page'               => [$target_page, 's'],
        'other_pages_display'       => [$other_pages_display, 's'],
        'show_on_category'          => [$show_on_category, 's'],
        'show_on_product'           => [$show_on_product, 's'],
        'show_on_product_detail'    => [$show_on_product_detail, 's'],
        'show_on_franchise'         => [$show_on_franchise, 's'],
        'show_on_affiliate'         => [$show_on_affiliate, 's'],
        'show_on_career'            => [$show_on_career, 's'],
        'show_on_business_solutions'=> [$show_on_business_solutions, 's'],
        'show_on_advertising'       => [$show_on_advertising, 's'],
        'show_on_about_us'          => [$show_on_about_us, 's'],
        'status'                    => [$status, 's'],
    ];

    if (empty($title)) {
        $error = "Carousel Title is required.";
    } else {
        $columns = array_keys($fields);
        $types = implode('', array_column($fields, 1));
        $values = array_column($fields, 0);

        if ($is_edit) {
            $setClause = implode(', ', array_map(fn($c) => "$c = ?", $columns));
            $stmt = $db->prepare("UPDATE carousels SET $setClause, updated_at = NOW() WHERE id = ?");
            $updateValues = [...$values, $id];
            $stmt->bind_param($types . 'i', ...$updateValues);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Carousel updated successfully!";
                header('Location: carousels.php');
                exit();
            } else {
                $error = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $columnList = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $stmt = $db->prepare("INSERT INTO carousels ($columnList, created_at) VALUES ($placeholders, NOW())");
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Carousel added successfully!";
                header('Location: carousels.php');
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
    <title><?php echo $is_edit ? 'Edit Carousel' : 'Add Carousel'; ?> | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Module Top Navigation Pills (Document 8 Manage Carousels) */
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #00529b; background: #e0edff; }
        .module-nav-link.active { color: #fff; background: #00529b; }

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
        .design-choice-card {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
        }
        .design-choice-card:hover {
            border-color: #00529b;
            background: #f0f7fc;
        }
        .design-choice-card input[type="radio"] {
            display: none;
        }
        .design-choice-card.selected {
            border-color: #00529b;
            background: #e8f4fc;
            box-shadow: 0 0 0 2px rgba(0, 82, 155, 0.2);
        }
        .design-choice-card .badge-icon {
            font-size: 24px;
            color: #00529b;
            margin-bottom: 6px;
        }
        .preview-thumb {
            max-width: 140px;
            max-height: 80px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            object-fit: cover;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Document 8 Carousel Navigation Pills -->
                    <div class="module-nav-pills mb-4">
                        <a href="add-carousel.php?type=product" class="module-nav-link <?php echo $item['carousel_type'] === 'product' && empty($_GET['target']) ? 'active' : ''; ?>">
                            <i class="fas fa-boxes"></i> 1. Add New Products Carousel
                        </a>
                        <a href="add-carousel.php?type=category" class="module-nav-link <?php echo $item['carousel_type'] === 'category' ? 'active' : ''; ?>">
                            <i class="fas fa-folder"></i> 2. Add New Category Carousel
                        </a>
                        <a href="carousels.php" class="module-nav-link">
                            <i class="fas fa-list"></i> 3. All Carousels
                        </a>
                        <a href="add-carousel.php?type=product&target=dashboard" class="module-nav-link <?php echo ($_GET['target'] ?? '') === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> 4. User Dashboard: Product &amp; Category Carousel
                        </a>
                    </div>

                    <div class="row mb-3">
                        <div class="col-auto">
                            <h1 class="h3 mb-0"><strong><?php echo $is_edit ? 'Edit' : 'Add New'; ?></strong> <?php echo $item['carousel_type'] === 'category' ? 'Category' : 'Products'; ?> Carousel</h1>
                            <p class="text-muted small">Configure responsive dynamic carousels across Desktop &amp; Mobile views.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="carousels.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Carousels
                            </a>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Card 1: Basic Information -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-info-circle"></i> Carousel General Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Carousel Type <span class="text-danger">*</span></label>
                                        <select name="carousel_type" id="carouselTypeSelect" class="form-select" onchange="toggleCarouselTypeFields(this.value)">
                                            <option value="product" <?php echo $item['carousel_type'] === 'product' ? 'selected' : ''; ?>>Products Carousel</option>
                                            <option value="category" <?php echo $item['carousel_type'] === 'category' ? 'selected' : ''; ?>>Category Carousel</option>
                                        </select>
                                        <small class="text-muted">Products shows items from category; Category shows category cards.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Primary Target Page <span class="text-danger">*</span></label>
                                        <?php $ctp = $item['target_page'] ?? 'home'; ?>
                                        <select name="target_page" class="form-select">
                                            <option value="home" <?php echo ($ctp === 'home' || empty($ctp)) ? 'selected' : ''; ?>>Home Page (Main Storefront)</option>
                                            <option value="category" <?php echo ($ctp === 'category') ? 'selected' : ''; ?>>Category Page</option>
                                            <option value="product" <?php echo ($ctp === 'product') ? 'selected' : ''; ?>>Product Page</option>
                                            <option value="product_detail" <?php echo ($ctp === 'product_detail') ? 'selected' : ''; ?>>Product Detail Page</option>
                                            <option value="franchise" <?php echo ($ctp === 'franchise') ? 'selected' : ''; ?>>Franchise Page</option>
                                            <option value="affiliate_program" <?php echo ($ctp === 'affiliate_program') ? 'selected' : ''; ?>>Affiliate Program Page</option>
                                            <option value="career" <?php echo ($ctp === 'career') ? 'selected' : ''; ?>>Career Page</option>
                                            <option value="business_solutions" <?php echo ($ctp === 'business_solutions') ? 'selected' : ''; ?>>Business Solutions Page</option>
                                            <option value="advertising" <?php echo ($ctp === 'advertising') ? 'selected' : ''; ?>>Advertising Page</option>
                                        </select>
                                        <small class="text-muted">Primary page for this carousel.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Carousel Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Most Popular Products, Corporate Gifts" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Image Alt Text (SEO &amp; Accessibility)</label>
                                        <input type="text" name="alt_text" class="form-control" placeholder="e.g. Most Popular Corporate Gifts Collection" value="<?php echo htmlspecialchars($item['alt_text'] ?? ''); ?>">
                                        <small class="text-muted">Alt tag text for carousel cards &amp; backgrounds.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Carousel Slogan</label>
                                        <div class="input-group">
                                            <input type="text" name="slogan" class="form-control" placeholder="e.g. Handpicked for your brand" value="<?php echo htmlspecialchars($item['slogan']); ?>">
                                            <div class="input-group-text bg-light">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="show_slogan" id="showSlogan" value="1" <?php echo !empty($item['show_slogan']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small ms-1" for="showSlogan">Show</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">URL Slug (Auto if empty)</label>
                                        <input type="text" name="slug" class="form-control" placeholder="most-popular-products" value="<?php echo htmlspecialchars($item['slug']); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Carousel Description</label>
                                        <textarea name="description" class="form-control" rows="2" placeholder="Brief carousel description, subtitle copy, or notes..."><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Home page Extra Product Select (Optional)</label>
                                        <select name="extra_product_id" class="form-select">
                                            <option value="">— None (No extra featured product) —</option>
                                            <?php foreach ($productList as $p): ?>
                                                <option value="<?php echo (int)$p['id']; ?>" <?php echo ((int)$item['extra_product_id'] === (int)$p['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['name'] . (!empty($p['sku']) ? ' (' . $p['sku'] . ')' : '')); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Doc 8: Links carousel relative to extra home product.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Master Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $item['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Multi-Page Visibility (Show on Other Pages) -->
                        <?php
                        $copd = [];
                        if (!empty($item['other_pages_display'])) {
                            $decoded = json_decode($item['other_pages_display'], true);
                            if (is_array($decoded)) $copd = $decoded;
                        }
                        ?>
                        <div class="card mb-4 shadow-sm border-0 border-top border-primary border-3">
                            <div class="card-body">
                                <div class="form-section-title d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-layer-group text-primary me-2"></i> Also Show on Other Pages (Multi-Page Display)
                                        <div class="text-muted fw-normal small">Client Requirement: Use checkboxes to easily show this carousel on other storefront pages.</div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1">8 Specific Pages</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_category" id="cChkCat" value="yes" <?php echo (($item['show_on_category'] ?? '') === 'yes' || in_array('category', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkCat">
                                                    <i class="fas fa-th-large text-primary me-1"></i> Category Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Shop category pages</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_product" id="cChkProd" value="yes" <?php echo (($item['show_on_product'] ?? '') === 'yes' || in_array('product', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkProd">
                                                    <i class="fas fa-box text-success me-1"></i> Product Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">All products listing</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_product_detail" id="cChkPDet" value="yes" <?php echo (($item['show_on_product_detail'] ?? '') === 'yes' || in_array('product_detail', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkPDet">
                                                    <i class="fas fa-tag text-info me-1"></i> Product Detail
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Product details page</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_franchise" id="cChkFran" value="yes" <?php echo (($item['show_on_franchise'] ?? '') === 'yes' || in_array('franchise', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkFran">
                                                    <i class="fas fa-store text-warning me-1"></i> Franchise Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Franchise portal</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_affiliate" id="cChkAff" value="yes" <?php echo (($item['show_on_affiliate'] ?? '') === 'yes' || in_array('affiliate_program', $copd) || in_array('affiliate', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkAff">
                                                    <i class="fas fa-handshake text-purple me-1"></i> Affiliate Program
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Affiliate landing</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_career" id="cChkCar" value="yes" <?php echo (($item['show_on_career'] ?? '') === 'yes' || in_array('career', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkCar">
                                                    <i class="fas fa-user-tie text-danger me-1"></i> Career Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Jobs &amp; careers</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_business_solutions" id="cChkBiz" value="yes" <?php echo (($item['show_on_business_solutions'] ?? '') === 'yes' || in_array('business_solutions', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkBiz">
                                                    <i class="fas fa-briefcase text-primary me-1"></i> Business Solutions
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Corporate gifting / B2B</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_advertising" id="cChkAdv" value="yes" <?php echo (($item['show_on_advertising'] ?? '') === 'yes' || in_array('advertising', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkAdv">
                                                    <i class="fas fa-bullhorn text-secondary me-1"></i> Advertising Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">Brand ads &amp; promo</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card p-3 h-100 border bg-light-subtle shadow-none">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" role="switch" name="show_on_about_us" id="cChkAbout" value="yes" <?php echo (($item['show_on_about_us'] ?? '') === 'yes' || in_array('about_us', $copd)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark ms-1" for="cChkAbout">
                                                    <i class="fas fa-info-circle text-info me-1"></i> About Us Page
                                                </label>
                                            </div>
                                            <small class="text-muted ps-4">About Printmont page</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Review / Offer badge (field set depends on Carousel Type above) -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-star"></i> Review / Offer Badge
                                </div>
                                <div class="row g-3" id="productReviewFields" style="<?php echo $item['carousel_type'] === 'category' ? 'display:none;' : ''; ?>">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_in_review" id="showInReview" value="1" <?php echo !empty($item['show_in_review']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="showInReview">Show in Review</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Review Type Design</label>
                                        <select name="review_design_type" class="form-select">
                                            <option value="">-- None --</option>
                                            <option value="stars" <?php echo $item['review_design_type'] === 'stars' ? 'selected' : ''; ?>>Star Rating Strip</option>
                                            <option value="testimonial" <?php echo $item['review_design_type'] === 'testimonial' ? 'selected' : ''; ?>>Testimonial Card</option>
                                            <option value="badge" <?php echo $item['review_design_type'] === 'badge' ? 'selected' : ''; ?>>Rating Badge</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3" id="categoryOfferFields" style="<?php echo $item['carousel_type'] === 'product' ? 'display:none;' : ''; ?>">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" role="switch" name="show_in_offer" id="showInOffer" value="1" <?php echo !empty($item['show_in_offer']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="showInOffer">Show in Offer</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Offer / Percentage Type Design</label>
                                        <select name="offer_design_type" class="form-select">
                                            <option value="">-- None --</option>
                                            <option value="percentage_tag" <?php echo $item['offer_design_type'] === 'percentage_tag' ? 'selected' : ''; ?>>Percentage Off Tag</option>
                                            <option value="flat_badge" <?php echo $item['offer_design_type'] === 'flat_badge' ? 'selected' : ''; ?>>Flat Discount Badge</option>
                                            <option value="ribbon" <?php echo $item['offer_design_type'] === 'ribbon' ? 'selected' : ''; ?>>Corner Ribbon</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Desktop Configuration -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-desktop"></i> Desktop View Settings (Home & Other Pages)
                                </div>
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Desktop Home Category</label>
                                        <select name="desktop_home_category_id" class="form-select">
                                            <option value="">-- Choose Category (Optional) --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)$item['desktop_home_category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Products from this category will populate the carousel if automatic.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Show on Home Pages</label>
                                        <select name="desktop_home_show" class="form-select">
                                            <option value="yes" <?php echo $item['desktop_home_show'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo $item['desktop_home_show'] === 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Desktop Status</label>
                                        <select name="desktop_status" class="form-select">
                                            <option value="active" <?php echo $item['desktop_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['desktop_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Position relative to an existing section — "full freedom" placement (desktop) -->
                                <div class="row g-3 align-items-end p-3 mb-4" style="background:#f8f9fa;border-radius:8px;">
                                    <div class="col-12">
                                        <label class="form-label fw-bold mb-1"><i class="fas fa-arrows-alt-v me-1"></i> Position This Carousel (optional)</label>
                                        <div class="text-muted small mb-2">Pick an existing section to position this carousel before or after it. Leave blank to just use the Sort Order number above.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Relative to section type</label>
                                        <select class="form-select anchor-type-select" data-device="desktop" onchange="pmOnAnchorTypeChange('desktop', this.value)">
                                            <option value="">-- None --</option>
                                            <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                <option value="<?php echo $aKey; ?>" <?php echo $item['desktop_anchor_type'] === $aKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($aLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Specific section</label>
                                        <select name="desktop_anchor_id" id="desktopAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                            <option value="">-- Select section type first --</option>
                                        </select>
                                        <input type="hidden" name="desktop_anchor_type" id="desktopAnchorType" value="<?php echo htmlspecialchars($item['desktop_anchor_type']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Position</label>
                                        <select name="desktop_anchor_position" id="desktopAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('desktop')">
                                            <option value="before" <?php echo $item['desktop_anchor_position'] === 'before' ? 'selected' : ''; ?>>Before it</option>
                                            <option value="after" <?php echo $item['desktop_anchor_position'] === 'after' ? 'selected' : ''; ?>>After it</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Desktop Sort Order</label>
                                        <input type="number" step="0.01" name="desktop_sort_order" id="desktopSortOrder" class="form-control" value="<?php echo htmlspecialchars((string) $item['desktop_sort_order']); ?>">
                                        <small class="text-muted">Auto-filled when you pick a section above; editable by hand too.</small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold d-block">Desktop Home Design Pattern (4 Types)</label>
                                    <div class="row g-2">
                                        <?php 
                                        $deskDesigns = [
                                            'design-1' => ['name' => 'Design 1: Grid 4 Box', 'icon' => 'fa-th-large', 'desc' => 'Classic 4-column balanced product grid'],
                                            'design-2' => ['name' => 'Design 2: Scroll Carousel', 'icon' => 'fa-sliders-h', 'desc' => 'Fluid horizontal scroll cards with navigation arrows'],
                                            'design-3' => ['name' => 'Design 3: Cards & Badges', 'icon' => 'fa-id-badge', 'desc' => 'Elevated cards with discount % and rating pills'],
                                            'design-4' => ['name' => 'Design 4: Hero Highlight', 'icon' => 'fa-star', 'desc' => '1 Large featured spotlight + 3 compact cards']
                                        ];
                                        foreach ($deskDesigns as $dKey => $dInfo): 
                                            $isSelected = ($item['desktop_home_design'] === $dKey);
                                        ?>
                                            <div class="col-md-3 col-6">
                                                <label class="design-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectDesign(this, 'desktop_home_design')">
                                                    <input type="radio" name="desktop_home_design" value="<?php echo $dKey; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                                    <div class="badge-icon"><i class="fas <?php echo $dInfo['icon']; ?>"></i></div>
                                                    <div class="fw-bold small"><?php echo $dInfo['name']; ?></div>
                                                    <div class="text-muted" style="font-size:11px;"><?php echo $dInfo['desc']; ?></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Background Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['desktop_bg_color'] ?: '#ffffff'); ?>" onchange="document.getElementById('desk_bg_hex').value = this.value">
                                            <input type="text" id="desk_bg_hex" name="desktop_bg_color" class="form-control" value="<?php echo htmlspecialchars($item['desktop_bg_color'] ?: '#ffffff'); ?>" placeholder="#ffffff">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Background Image</label>
                                        <input type="file" name="desktop_bg_image" class="form-control" accept="image/*">
                                        <?php if (!empty($item['desktop_bg_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['desktop_bg_image']); ?>" class="preview-thumb" alt="Desktop BG">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Show on Other Pages?</label>
                                        <select name="desktop_show_other_pages" class="form-select">
                                            <option value="no" <?php echo $item['desktop_show_other_pages'] === 'no' ? 'selected' : ''; ?>>No</option>
                                            <option value="yes" <?php echo $item['desktop_show_other_pages'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Mobile Configuration -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-mobile-alt"></i> Mobile View Settings
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Show on Mobile Home?</label>
                                        <select name="mobile_home_show" class="form-select">
                                            <option value="yes" <?php echo $item['mobile_home_show'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo $item['mobile_home_show'] === 'no' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">Mobile Home Category</label>
                                        <select name="mobile_home_category_id" class="form-select">
                                            <option value="">-- Choose Category (Optional) --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo (int)$item['mobile_home_category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Mobile Status</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo $item['mobile_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $item['mobile_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Position relative to an existing section — "full freedom" placement (mobile) -->
                                <div class="row g-3 align-items-end p-3 mb-4" style="background:#f8f9fa;border-radius:8px;">
                                    <div class="col-12">
                                        <label class="form-label fw-bold mb-1"><i class="fas fa-arrows-alt-v me-1"></i> Position This Carousel (optional)</label>
                                        <div class="text-muted small mb-2">Pick an existing section to position this carousel before or after it on mobile. Leave blank to just use the Sort Order number below.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Relative to section type</label>
                                        <select class="form-select anchor-type-select" data-device="mobile" onchange="pmOnAnchorTypeChange('mobile', this.value)">
                                            <option value="">-- None --</option>
                                            <?php foreach ($anchorSectionTypes as $aKey => $aLabel): ?>
                                                <option value="<?php echo $aKey; ?>" <?php echo $item['mobile_anchor_type'] === $aKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($aLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold">Specific section</label>
                                        <select name="mobile_anchor_id" id="mobileAnchorSection" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                            <option value="">-- Select section type first --</option>
                                        </select>
                                        <input type="hidden" name="mobile_anchor_type" id="mobileAnchorType" value="<?php echo htmlspecialchars($item['mobile_anchor_type']); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Position</label>
                                        <select name="mobile_anchor_position" id="mobileAnchorPosition" class="form-select" onchange="pmApplyAnchorOrder('mobile')">
                                            <option value="before" <?php echo $item['mobile_anchor_position'] === 'before' ? 'selected' : ''; ?>>Before it</option>
                                            <option value="after" <?php echo $item['mobile_anchor_position'] === 'after' ? 'selected' : ''; ?>>After it</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Mobile Sort Order</label>
                                        <input type="number" step="0.01" name="mobile_sort_order" id="mobileSortOrder" class="form-control" value="<?php echo htmlspecialchars((string) $item['mobile_sort_order']); ?>">
                                        <small class="text-muted">Auto-filled when you pick a section above; editable by hand too.</small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold d-block">Mobile Home Design Pattern (3 Types)</label>
                                    <div class="row g-2">
                                        <?php 
                                        $mobDesigns = [
                                            'design-1' => ['name' => 'Design 1: 2-Column Grid', 'icon' => 'fa-columns', 'desc' => 'Dual column compact cards optimized for touch'],
                                            'design-2' => ['name' => 'Design 2: Single Card Swiper', 'icon' => 'fa-arrows-alt-h', 'desc' => 'Horizontal snap-scroll carousel'],
                                            'design-3' => ['name' => 'Design 3: List Stack', 'icon' => 'fa-list-ul', 'desc' => 'Vertical stacked rows with side thumbnail & price']
                                        ];
                                        foreach ($mobDesigns as $dKey => $dInfo): 
                                            $isSelected = ($item['mobile_home_design'] === $dKey);
                                        ?>
                                            <div class="col-md-4 col-12">
                                                <label class="design-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectDesign(this, 'mobile_home_design')">
                                                    <input type="radio" name="mobile_home_design" value="<?php echo $dKey; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                                    <div class="badge-icon"><i class="fas <?php echo $dInfo['icon']; ?>"></i></div>
                                                    <div class="fw-bold small"><?php echo $dInfo['name']; ?></div>
                                                    <div class="text-muted" style="font-size:11px;"><?php echo $dInfo['desc']; ?></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold d-block">Mobile Carousel Format Design (Doc 8 Spec: 1:1, 5:2, 2.5 slider)</label>
                                    <div class="row g-2">
                                        <?php 
                                        $formats = [
                                            '1.1' => ['name' => '1:1 Square Card', 'icon' => 'fa-square', 'desc' => 'Aspect ratio 1:1 square card format'],
                                            '5.2' => ['name' => '5:2 Panoramic Card', 'icon' => 'fa-clone', 'desc' => 'Wide panoramic rectangular aspect format'],
                                            '2.5' => ['name' => '2.5 Slider', 'icon' => 'fa-layer-group', 'desc' => '2 full cards + half peeking card swipe scroll']
                                        ];
                                        foreach ($formats as $fKey => $fInfo): 
                                            $isFmtSelected = (($item['mobile_carousel_format'] ?? '2.5') === $fKey);
                                        ?>
                                            <div class="col-md-4 col-12">
                                                <label class="design-choice-card <?php echo $isFmtSelected ? 'selected' : ''; ?>" onclick="selectDesign(this, 'mobile_carousel_format')">
                                                    <input type="radio" name="mobile_carousel_format" value="<?php echo $fKey; ?>" <?php echo $isFmtSelected ? 'checked' : ''; ?>>
                                                    <div class="badge-icon"><i class="fas <?php echo $fInfo['icon']; ?>"></i></div>
                                                    <div class="fw-bold small"><?php echo $fInfo['name']; ?></div>
                                                    <div class="text-muted" style="font-size:11px;"><?php echo $fInfo['desc']; ?></div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Background Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($item['mobile_bg_color'] ?: '#ffffff'); ?>" onchange="document.getElementById('mob_bg_hex').value = this.value">
                                            <input type="text" id="mob_bg_hex" name="mobile_bg_color" class="form-control" value="<?php echo htmlspecialchars($item['mobile_bg_color'] ?: '#ffffff'); ?>" placeholder="#ffffff">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Background Image</label>
                                        <input type="file" name="mobile_bg_image" class="form-control" accept="image/*">
                                        <?php if (!empty($item['mobile_bg_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['mobile_bg_image']); ?>" class="preview-thumb" alt="Mobile BG">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Show on Mobile Other Pages?</label>
                                        <select name="mobile_show_other_pages" class="form-select">
                                            <option value="no" <?php echo $item['mobile_show_other_pages'] === 'no' ? 'selected' : ''; ?>>No</option>
                                            <option value="yes" <?php echo $item['mobile_show_other_pages'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: SEO Metadata -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-body">
                                <div class="form-section-title">
                                    <i class="fas fa-search"></i> SEO Meta Information
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Meta Title</label>
                                        <input type="text" name="meta_title" class="form-control" placeholder="Meta Title for search engines" value="<?php echo htmlspecialchars($item['meta_title']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Meta Keywords</label>
                                        <input type="text" name="meta_keywords" class="form-control" placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($item['meta_keywords']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Meta Description</label>
                                        <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief SEO description"><?php echo htmlspecialchars($item['meta_description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <a href="carousels.php" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold">
                                <i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'Update Carousel' : 'Create Carousel'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function selectDesign(labelEl, radioName) {
            document.querySelectorAll(`input[name="${radioName}"]`).forEach(input => {
                const parent = input.closest('.design-choice-card');
                if (parent) parent.classList.remove('selected');
            });
            const radio = labelEl.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                labelEl.classList.add('selected');
            }
        }

        // Product Carousel vs Category Carousel: swap the Review/Offer badge field set
        function toggleCarouselTypeFields(type) {
            document.getElementById('productReviewFields').style.display = type === 'category' ? 'none' : '';
            document.getElementById('categoryOfferFields').style.display = type === 'product' ? 'none' : '';
        }

        // Sections available to position against, per device + anchor type — see
        // getAnchorSectionOptions() in includes/banner-helpers.php for how these are built.
        const PM_ANCHOR_OPTIONS = <?php echo json_encode($anchorOptionsByDevice, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const PM_CURRENT_ANCHOR = {
            desktop: { type: <?php echo json_encode($item['desktop_anchor_type']); ?>, id: <?php echo json_encode($item['desktop_anchor_id']); ?> },
            mobile:  { type: <?php echo json_encode($item['mobile_anchor_type']); ?>, id: <?php echo json_encode($item['mobile_anchor_id']); ?> }
        };

        function pmOnAnchorTypeChange(device, anchorType) {
            document.getElementById(`${device}AnchorType`).value = anchorType;
            const sectionSelect = document.getElementById(`${device}AnchorSection`);
            const options = (PM_ANCHOR_OPTIONS[device] && PM_ANCHOR_OPTIONS[device][anchorType]) || [];
            sectionSelect.innerHTML = '<option value="">-- Select --</option>' +
                options.map(o => `<option value="${o.id}">${o.label.replace(/</g, '&lt;')}</option>`).join('');
            pmApplyAnchorOrder(device);
        }

        // Fills the Sort Order number field from the chosen anchor + before/after — admin can
        // still hand-edit the number afterwards, this is just a starting suggestion.
        function pmApplyAnchorOrder(device) {
            const anchorType = document.getElementById(`${device}AnchorType`).value;
            const anchorId = document.getElementById(`${device}AnchorSection`).value;
            if (!anchorType || !anchorId) return;
            const position = document.getElementById(`${device}AnchorPosition`).value;
            const options = (PM_ANCHOR_OPTIONS[device] && PM_ANCHOR_OPTIONS[device][anchorType]) || [];
            const idx = options.findIndex(o => String(o.id) === String(anchorId));
            if (idx === -1) return;
            const anchorOrder = parseFloat(options[idx].sort_order) || 0;
            let suggested;
            if (position === 'before') {
                const prevOrder = idx > 0 ? parseFloat(options[idx - 1].sort_order) || 0 : null;
                suggested = prevOrder !== null ? (prevOrder + anchorOrder) / 2 : anchorOrder - 1;
            } else {
                const nextOrder = idx < options.length - 1 ? parseFloat(options[idx + 1].sort_order) || 0 : null;
                suggested = nextOrder !== null ? (anchorOrder + nextOrder) / 2 : anchorOrder + 1;
            }
            document.getElementById(`${device}SortOrder`).value = suggested.toFixed(2);
        }

        // Pre-populate the "specific section" dropdown on edit, and restore the saved pick
        document.addEventListener('DOMContentLoaded', () => {
            ['desktop', 'mobile'].forEach(device => {
                const current = PM_CURRENT_ANCHOR[device];
                if (!current.type) return;
                const typeSelect = document.querySelector(`.anchor-type-select[data-device="${device}"]`);
                if (typeSelect) typeSelect.value = current.type;
                const sectionSelect = document.getElementById(`${device}AnchorSection`);
                const options = (PM_ANCHOR_OPTIONS[device] && PM_ANCHOR_OPTIONS[device][current.type]) || [];
                sectionSelect.innerHTML = '<option value="">-- Select --</option>' +
                    options.map(o => `<option value="${o.id}" ${String(o.id) === String(current.id) ? 'selected' : ''}>${o.label.replace(/</g, '&lt;')}</option>`).join('');
            });
        });
    </script>
</body>
</html>
