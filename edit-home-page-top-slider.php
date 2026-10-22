<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/HomePageTopSliderController.php';

$controller = new HomePageTopSliderController();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$slider = $controller->getSliderById($id);

if (!$slider) {
    $_SESSION['error_message'] = "Slider record not found.";
    header("Location: home-page-top-slider.php");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_top_slider'])) {
    try {
        if (empty($_POST['title'])) {
            throw new Exception("Slider Title is required.");
        }

        if ($controller->updateSlider($id, $_POST, $_FILES)) {
            $_SESSION['success_message'] = "Home page top slider updated successfully!";
            header("Location: home-page-top-slider.php");
            exit;
        } else {
            throw new Exception("Failed to update slider record.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Decode existing placement rules if present
$currentPlacementRules = [];
if (!empty($slider['placement_rules'])) {
    $decoded = json_decode($slider['placement_rules'], true);
    if (is_array($decoded)) {
        $currentPlacementRules = $decoded;
    }
}
$targetPagesList = !empty($slider['target_pages']) ? explode(',', $slider['target_pages']) : ['home'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Home Page Top Slider | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section-card { border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-section-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; font-weight: 700; color: #1e293b; border-radius: 8px 8px 0 0; }
        .preview-box { width: 100%; max-height: 160px; object-fit: contain; background: #f1f5f9; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 10px; }

        /* Checkbox & Selection Card Styles */
        .section-check-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 8px 12px !important;
            margin: 0 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            cursor: pointer !important;
            background: #ffffff;
            transition: background 0.15s ease-in-out;
        }
        .section-check-item:hover {
            background: #f8fafc !important;
        }
        .section-check-item:last-child {
            border-bottom: none !important;
        }
        .placement-sec-checkbox {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            margin: 0 !important;
            float: none !important;
            cursor: pointer !important;
            accent-color: #2563eb !important;
            position: static !important;
            opacity: 1 !important;
            display: inline-block !important;
            -webkit-appearance: checkbox !important;
            appearance: checkbox !important;
        }
        .page-checkbox {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            margin-right: 8px !important;
            cursor: pointer !important;
            accent-color: #2563eb !important;
            opacity: 1 !important;
            display: inline-block !important;
            -webkit-appearance: checkbox !important;
            appearance: checkbox !important;
        }
        .section-check-label {
            cursor: pointer !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            user-select: none;
            line-height: 1.35;
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
                    <div class="row mb-3 align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-0 text-gray-800 fw-bold">Edit Home Page Top Slider</h1>
                            <p class="text-muted mb-0">Update hero slider banner for Desktop and Mobile homepage.</p>
                        </div>
                        <div class="col-auto">
                            <a href="home-page-top-slider.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Sliders
                            </a>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        
                        <!-- 1. General Slider Details & Target Pages -->
                        <div class="card form-section-card">
                            <div class="form-section-header">
                                <i class="fas fa-heading text-primary me-2"></i> 1. Slider Basic Information & Target Pages
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Title Slider Name <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($slider['title']); ?>">
                                    </div>

                                    <!-- Apply On Pages Selection -->
                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                            <span>Apply On Pages (Kis Kis Page Par Apply Karna Hai)</span>
                                            <div>
                                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" onclick="toggleAllPages(true)">Select All</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAllPages(false)">Clear</button>
                                            </div>
                                        </label>
                                        <div class="p-3 bg-light rounded border">
                                            <div class="row g-2">
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="home" id="page_home" <?php echo (in_array('home', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_home">🏠 Home Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="category" id="page_category" <?php echo (in_array('category', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_category">📁 Category Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="product" id="page_product" <?php echo (in_array('product', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_product">🛍️ Product Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="cart" id="page_cart" <?php echo (in_array('cart', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_cart">🛒 Cart Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="checkout" id="page_checkout" <?php echo (in_array('checkout', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_checkout">💳 Checkout Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="orders" id="page_orders" <?php echo (in_array('orders', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_orders">📦 Orders Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="blog" id="page_blog" <?php echo (in_array('blog', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_blog">📰 Blog Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="about" id="page_about" <?php echo (in_array('about', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_about">ℹ️ About Us Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="contact" id="page_contact" <?php echo (in_array('contact', $targetPagesList) || in_array('all', $targetPagesList)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_contact">📞 Contact Us Page</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section Placement Cards -->
                                    <div class="col-12 mt-3" id="section-placement-wrapper">
                                        <div class="card border-primary border-opacity-25 bg-white shadow-none">
                                            <div class="card-header bg-primary bg-opacity-10 py-2.5 px-3 d-flex justify-content-between align-items-center">
                                                <div class="fw-bold text-primary">
                                                    <i class="fas fa-arrows-split-up-and-left me-1"></i> Section Placement & Positioning (Target Sections)
                                                </div>
                                                <span class="badge bg-primary rounded-pill px-2.5" id="placement-count-badge">Configured</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div id="placement-cards-container" class="row g-3">
                                                    <!-- Dynamic placement cards for selected pages inserted by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- 2. Desktop Settings -->
                        <div class="card form-section-card">
                            <div class="form-section-header">
                                <i class="fas fa-desktop text-primary me-2"></i> 2. Desktop Slider Settings
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Format Design</label>
                                        <select name="desktop_design" class="form-select">
                                            <option value="format_1" <?php echo ($slider['desktop_design'] === 'format_1') ? 'selected' : ''; ?>>Format 1 - Full Width Banner (Default)</option>
                                            <option value="format_2" <?php echo ($slider['desktop_design'] === 'format_2') ? 'selected' : ''; ?>>Format 2 - Boxed Container with Shadow</option>
                                            <option value="format_3" <?php echo ($slider['desktop_design'] === 'format_3') ? 'selected' : ''; ?>>Format 3 - Rounded Card Hero</option>
                                            <option value="format_4" <?php echo ($slider['desktop_design'] === 'format_4') ? 'selected' : ''; ?>>Format 4 - Modern Split Showcase</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Sort Order</label>
                                        <input type="number" name="desktop_sort_order" class="form-control" min="0" value="<?php echo (int)$slider['desktop_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Desktop Status</label>
                                        <select name="desktop_status" class="form-select">
                                            <option value="active" <?php echo (($slider['desktop_status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (($slider['desktop_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Desktop Slider Image Upload</label>
                                        <input type="file" name="desktop_image" class="form-control" accept="image/*" onchange="previewImg(this, 'desktop_preview')">
                                        <small class="text-muted">Recommended: 1920 x 480 px (Leave empty to keep existing image)</small>
                                        <?php if (!empty($slider['desktop_image'])): ?>
                                            <img id="desktop_preview" src="<?php echo htmlspecialchars($slider['desktop_image']); ?>" class="preview-box p-1" alt="Desktop Preview">
                                        <?php else: ?>
                                            <img id="desktop_preview" class="preview-box p-1" style="display:none;" alt="Desktop Preview">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Desktop Target URL</label>
                                        <input type="text" name="desktop_url" class="form-control" placeholder="e.g. /category/same-day-products" value="<?php echo htmlspecialchars($slider['desktop_url'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Mobile Settings -->
                        <div class="card form-section-card">
                            <div class="form-section-header">
                                <i class="fas fa-mobile-alt text-success me-2"></i> 3. Mobile Slider Settings
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Format Design</label>
                                        <select name="mobile_design" class="form-select">
                                            <option value="design_1" <?php echo ($slider['mobile_design'] === 'design_1') ? 'selected' : ''; ?>>Design 1 - Rounded Card with Bottom Pill Dots</option>
                                            <option value="design_2" <?php echo ($slider['mobile_design'] === 'design_2') ? 'selected' : ''; ?>>Design 2 - Edge-to-Edge Full Width Slider</option>
                                            <option value="design_3" <?php echo ($slider['mobile_design'] === 'design_3') ? 'selected' : ''; ?>>Design 3 - Compact Hero Banner</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Sort Order</label>
                                        <input type="number" name="mobile_sort_order" class="form-control" min="0" value="<?php echo (int)$slider['mobile_sort_order']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile Status</label>
                                        <select name="mobile_status" class="form-select">
                                            <option value="active" <?php echo (($slider['mobile_status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (($slider['mobile_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Mobile Slider Image Upload</label>
                                        <input type="file" name="mobile_image" class="form-control" accept="image/*" onchange="previewImg(this, 'mobile_preview')">
                                        <small class="text-muted">Recommended: 750 x 380 px (Leave empty to keep existing image)</small>
                                        <?php if (!empty($slider['mobile_image'])): ?>
                                            <img id="mobile_preview" src="<?php echo htmlspecialchars($slider['mobile_image']); ?>" class="preview-box p-1" alt="Mobile Preview">
                                        <?php else: ?>
                                            <img id="mobile_preview" class="preview-box p-1" style="display:none;" alt="Mobile Preview">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Mobile Target URL</label>
                                        <input type="text" name="mobile_url" class="form-control" placeholder="e.g. /category/same-day-products" value="<?php echo htmlspecialchars($slider['mobile_url'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Timing, Scheduling & Status -->
                        <div class="card form-section-card">
                            <div class="form-section-header">
                                <i class="fas fa-sliders-h text-primary me-2"></i> 4. Display, Timing & Scheduling
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Status Show and hide</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo ($slider['status'] === 'active') ? 'selected' : ''; ?>>Show (Active)</option>
                                            <option value="inactive" <?php echo ($slider['status'] === 'inactive') ? 'selected' : ''; ?>>Hide (Inactive)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Auto Running time setting (ms)</label>
                                        <input type="number" name="auto_play_speed" class="form-control" min="1000" step="500" value="<?php echo htmlspecialchars($slider['auto_play_speed'] ?? '3000'); ?>">
                                        <small class="text-muted">3000 ms = 3 seconds auto slide</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($slider['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($slider['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-4">
                            <a href="home-page-top-slider.php" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" name="update_top_slider" class="btn btn-primary px-4 shadow-sm">
                                <i class="fas fa-save me-1"></i> Update Top Slider
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
        const AVAILABLE_SECTIONS = {
            home_desktop: {
                name: '🖥️ Home Page — Desktop Layout',
                parentPage: 'home',
                sections: [
                    { id: 'home_hero', name: '1. Top Hero Slider' },
                    { id: 'category_navbar', name: '2. Category Navbar / Top Bar' },
                    { id: 'small_banner_slider', name: '3. Small Banner Slider' },
                    { id: 'best_sellers', name: '4. Best Sellers Carousel' },
                    { id: 'top_deal', name: '5. Top Deals Carousel' },
                    { id: 'new_arrivals', name: '6. New Arrivals Carousel' }
                ]
            },
            home_mobile: {
                name: '📱 Home Page — Mobile Layout',
                parentPage: 'home',
                sections: [
                    { id: 'circle_categories', name: '1. Categories Circles Bar' },
                    { id: 'home_hero', name: '2. Top Hero Slider (Mobile Single)' },
                    { id: 'small_banner_slider', name: '3. Small Banner Carousel' },
                    { id: 'sectiontwoimg', name: '4. 1-Column Banner & Slider' },
                    { id: 'bestseller_slider', name: '5. New Arrival Product Carousel' }
                ]
            },
            category_desktop: {
                name: '🖥️ Category Page — Desktop Layout',
                parentPage: 'category',
                sections: [
                    { id: 'category_hero_slider', name: '1. Main Category Slider (Hero Carousel)' },
                    { id: 'category_small_banner_1', name: '2. Small Banner' },
                    { id: 'category_grid_section', name: '3. Sub-Categories & Products Grid' }
                ]
            },
            category_mobile: {
                name: '📱 Category Page — Mobile Layout',
                parentPage: 'category',
                sections: [
                    { id: 'category_mob_slider_1', name: '1. Top Single Banner Slider' },
                    { id: 'category_mob_double_1', name: '2. Double Banner Carousel' },
                    { id: 'category_mob_bestseller', name: '3. Best Seller Carousel' }
                ]
            },
            product_desktop: {
                name: '🖥️ Product Details — Desktop Layout',
                parentPage: 'product',
                sections: [
                    { id: 'product_gallery_info', name: 'Product Gallery & Main Info' },
                    { id: 'product_related_carousel', name: 'Related Products Carousel' }
                ]
            },
            product_mobile: {
                name: '📱 Product Details — Mobile Layout',
                parentPage: 'product',
                sections: [
                    { id: 'product_gallery_info', name: 'Mobile Product Image Carousel' },
                    { id: 'product_related_carousel', name: 'Mobile Related Products Slider' }
                ]
            },
            cart_desktop: {
                name: '🖥️ Cart Page — Desktop Layout',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Cart Header & Navigation' },
                    { id: 'cart_cross_sell', name: 'Recommended Products' }
                ]
            },
            cart_mobile: {
                name: '📱 Cart Page — Mobile Layout',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Mobile Cart Header' },
                    { id: 'cart_cross_sell', name: 'Mobile Recommended Products' }
                ]
            },
            checkout_desktop: {
                name: '🖥️ Checkout — Desktop Layout',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Checkout Step Header' }
                ]
            },
            checkout_mobile: {
                name: '📱 Checkout — Mobile Layout',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Mobile Checkout Step Header' }
                ]
            },
            blog_desktop: {
                name: '🖥️ Blog Page — Desktop Layout',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Blog Header & Hero Article' }
                ]
            },
            blog_mobile: {
                name: '📱 Blog Page — Mobile Layout',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Mobile Blog Hero Article' }
                ]
            },
            about_desktop: {
                name: '🖥️ About Us — Desktop Layout',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'About Us Hero Banner' }
                ]
            },
            about_mobile: {
                name: '📱 About Us — Mobile Layout',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'Mobile About Us Banner' }
                ]
            },
            contact_desktop: {
                name: '🖥️ Contact Us — Desktop Layout',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Contact Header & Location Map' }
                ]
            },
            contact_mobile: {
                name: '📱 Contact Us — Mobile Layout',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Mobile Contact Header & Call Button' }
                ]
            }
        };

        const INITIAL_RULES = <?php echo json_encode($currentPlacementRules); ?>;
        const CURRENT_SELECTIONS = {};

        Object.keys(INITIAL_RULES).forEach(k => {
            const rule = INITIAL_RULES[k];
            let secs = [];
            if (rule.sections && Array.isArray(rule.sections)) {
                secs = rule.sections;
            } else if (rule.section) {
                secs = rule.section.split(',');
            }
            CURRENT_SELECTIONS[k] = {
                position: rule.position || 'top',
                sections: secs
            };
        });

        function onSecCheckboxChange(key, secId, isChecked) {
            if (!CURRENT_SELECTIONS[key]) {
                CURRENT_SELECTIONS[key] = { position: 'top', sections: [] };
            }
            if (isChecked) {
                if (!CURRENT_SELECTIONS[key].sections.includes(secId)) {
                    CURRENT_SELECTIONS[key].sections.push(secId);
                }
            } else {
                CURRENT_SELECTIONS[key].sections = CURRENT_SELECTIONS[key].sections.filter(s => s !== secId);
            }
        }

        function onPositionRadioChange(key, newPos) {
            if (!CURRENT_SELECTIONS[key]) {
                CURRENT_SELECTIONS[key] = { position: newPos, sections: [] };
            } else {
                CURRENT_SELECTIONS[key].position = newPos;
            }
        }

        function updatePlacementCards() {
            const container = document.getElementById('placement-cards-container');
            const selectedPages = Array.from(document.querySelectorAll('.page-checkbox:checked')).map(cb => cb.value);
            
            const badge = document.getElementById('placement-count-badge');
            if (badge) {
                badge.textContent = `${selectedPages.length} Page(s) Selected`;
            }

            if (selectedPages.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-4 bg-light rounded border border-dashed">
                        <i class="fas fa-exclamation-circle text-warning fa-2x mb-2"></i>
                        <div class="fw-bold text-dark">Koi bhi page select nahi hai</div>
                    </div>
                `;
                return;
            }

            const relevantKeys = Object.keys(AVAILABLE_SECTIONS).filter(key => {
                const cfg = AVAILABLE_SECTIONS[key];
                return selectedPages.includes(cfg.parentPage);
            });

            let html = '';
            relevantKeys.forEach(key => {
                const config = AVAILABLE_SECTIONS[key];
                const mem = CURRENT_SELECTIONS[key] || { position: 'top', sections: [] };
                const curPos = mem.position || 'top';
                const curSecs = mem.sections || [];

                let checkboxesHtml = '';
                config.sections.forEach((sec, sIdx) => {
                    const isChecked = curSecs.includes(sec.id) || (curSecs.length === 0 && sIdx === 0);
                    const uniqueId = `chk_${key}_${sec.id}_${sIdx}`;
                    checkboxesHtml += `
                        <label class="section-check-item" for="${uniqueId}">
                            <input 
                                type="checkbox" 
                                name="page_placement[${key}][sections][]" 
                                value="${sec.id}" 
                                id="${uniqueId}"
                                class="placement-sec-checkbox"
                                ${isChecked ? 'checked' : ''}
                                onchange="onSecCheckboxChange('${key}', '${sec.id}', this.checked)"
                            >
                            <span class="section-check-label">${sec.name}</span>
                        </label>
                    `;
                });

                html += `
                    <div class="col-md-6">
                        <div class="card h-100 border shadow-none" style="background:#ffffff;">
                            <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                                <span class="fw-bold text-dark" style="font-size:13.5px;">${config.name}</span>
                            </div>
                            <div class="card-body p-2.5">
                                <div class="mb-2 p-2 bg-light rounded border">
                                    <div class="small fw-bold text-muted mb-1">Placement Mode:</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="page_placement[${key}][position]" id="pos_top_${key}" value="top" ${curPos === 'top' ? 'checked' : ''} onchange="onPositionRadioChange('${key}', 'top')">
                                            <label class="form-check-label small fw-semibold" for="pos_top_${key}">⬆️ Top (Above Section)</label>
                                        </div>
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="radio" name="page_placement[${key}][position]" id="pos_after_${key}" value="after" ${curPos === 'after' ? 'checked' : ''} onchange="onPositionRadioChange('${key}', 'after')">
                                            <label class="form-check-label small fw-semibold" for="pos_after_${key}">⬇️ Bottom (After Section)</label>
                                        </div>
                                    </div>
                                </div>
                                <div id="sec_checkboxes_${key}" class="border rounded bg-white" style="max-height: 200px; overflow-y: auto;">
                                    ${checkboxesHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function toggleAllPages(selectAll) {
            document.querySelectorAll('.page-checkbox').forEach(cb => {
                cb.checked = selectAll;
            });
            updatePlacementCards();
        }

        function previewImg(input, targetId) {
            const preview = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.page-checkbox').forEach(cb => {
                cb.addEventListener('change', updatePlacementCards);
            });
            updatePlacementCards();
        });
    </script>
</body>
</html>
