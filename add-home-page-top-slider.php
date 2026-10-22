<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/HomePageTopSliderController.php';

$controller = new HomePageTopSliderController();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_top_slider'])) {
    try {
        $createdCount = $controller->createSeparateSliders($_POST, $_FILES);
        if ($createdCount > 0) {
            $_SESSION['success_message'] = "{$createdCount} Top hero slider(s) created successfully!";
            header("Location: home-page-top-slider.php");
            exit;
        } else {
            throw new Exception("Please upload at least one image in Desktop or Mobile slides.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Home Page Top Slider | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section-card { border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-section-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; font-weight: 700; color: #1e293b; border-radius: 8px 8px 0 0; }
        
        .slide-item-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: all 0.2s ease-in-out;
        }
        .slide-item-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(37,99,235,0.08);
        }
        .slide-header-desktop {
            background: #eff6ff;
            border-bottom: 1px solid #dbeafe;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .slide-header-mobile {
            background: #f0fdf4;
            border-bottom: 1px solid #dcfce7;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-img-box {
            width: 100%;
            height: 110px;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            display: none;
            margin-top: 8px;
        }
        .btn-remove-slide {
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            color: #64748b;
            border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            font-weight: 700;
            border-bottom-color: #fff;
        }

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
                            <h1 class="h3 mb-0 text-gray-800 fw-bold">Add Home Page Top Slider</h1>
                            <p class="text-muted mb-0">Upload and configure Top Hero Carousel Sliders (3 items in group) for Desktop and Mobile.</p>
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
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="add-home-page-top-slider.php" method="POST" enctype="multipart/form-data" id="slider-form">
                        
                        <!-- 1. Target Pages & Global Scheduling -->
                        <div class="card form-section-card">
                            <div class="form-section-header d-flex align-items-center">
                                <i class="fas fa-layer-group text-primary me-2"></i> 1. Target Pages & Publishing
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Apply On Pages Selection -->
                                    <div class="col-12">
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
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="home" id="page_home" <?php echo (!isset($_POST['target_pages']) || in_array('home', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_home">🏠 Home Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="category" id="page_category" <?php echo (in_array('category', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_category">📁 Category Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="product" id="page_product" <?php echo (in_array('product', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_product">🛍️ Product Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="cart" id="page_cart" <?php echo (in_array('cart', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_cart">🛒 Cart Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="checkout" id="page_checkout" <?php echo (in_array('checkout', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_checkout">💳 Checkout Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="orders" id="page_orders" <?php echo (in_array('orders', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_orders">📦 Orders Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="blog" id="page_blog" <?php echo (in_array('blog', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_blog">📰 Blog Pages</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="about" id="page_about" <?php echo (in_array('about', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_about">ℹ️ About Us Page</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input page-checkbox" type="checkbox" name="target_pages[]" value="contact" id="page_contact" <?php echo (in_array('contact', $_POST['target_pages'] ?? [])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="page_contact">📞 Contact Us Page</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Section Placement & Positioning (Konse Section Ke Pehle Ya Baad Me Dikhana Hai) -->
                                    <div class="col-12 mt-3" id="section-placement-wrapper">
                                        <div class="card border-primary border-opacity-25 bg-white shadow-none">
                                            <div class="card-header bg-primary bg-opacity-10 py-2.5 px-3 d-flex justify-content-between align-items-center">
                                                <div class="fw-bold text-primary">
                                                    <i class="fas fa-arrows-split-up-and-left me-1"></i> Section Placement & Positioning (Konse Section Ke Pehle Ya Baad Me Dikhana Hai)
                                                </div>
                                                <span class="badge bg-primary rounded-pill px-2.5" id="placement-count-badge">1 Page Configured</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <p class="text-muted small mb-3">
                                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                                    Select karein ki har page par ye Top Slider kis section ke <strong>pehle (before)</strong> ya <strong>baad (after)</strong> me appear hoga.
                                                </p>

                                                <div id="placement-cards-container" class="row g-3">
                                                    <!-- Dynamic placement cards for selected pages inserted by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-semibold">Start Date (Optional)</label>
                                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-semibold">End Date (Optional)</label>
                                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. SEPARATE DESKTOP & MOBILE TABS / SECTIONS -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom p-0">
                                <ul class="nav nav-tabs px-3 pt-2" id="sliderTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active d-flex align-items-center gap-2" id="desktop-tab" data-bs-toggle="tab" data-bs-target="#desktop-pane" type="button" role="tab">
                                            <i class="fas fa-desktop text-primary"></i> 🖥️ Desktop Top Sliders
                                            <span class="badge bg-primary rounded-pill ms-1" id="badge-desk-count">3</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link d-flex align-items-center gap-2" id="mobile-tab" data-bs-toggle="tab" data-bs-target="#mobile-pane" type="button" role="tab">
                                            <i class="fas fa-mobile-alt text-success"></i> 📱 Mobile Top Sliders
                                            <span class="badge bg-success rounded-pill ms-1" id="badge-mob-count">3</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body p-4">
                                <div class="tab-content" id="sliderTabsContent">
                                    
                                    <!-- ================= DESKTOP TAB PANE ================= -->
                                    <div class="tab-pane fade show active" id="desktop-pane" role="tabpanel">
                                        
                                        <!-- Desktop Settings -->
                                        <div class="row g-3 mb-4 p-3 bg-light rounded border border-blue-100">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Desktop Auto-running Time (ms)</label>
                                                <div class="input-group">
                                                    <input type="number" name="desktop_auto_play_speed" class="form-control" placeholder="3000" value="3000" min="500" step="100">
                                                    <span class="input-group-text">ms (3000 = 3s)</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn btn-primary fw-bold" onclick="addDesktopSlide()">
                                                    <i class="fas fa-plus me-1"></i> + Add Desktop Slide
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Desktop Slides Repeater Container -->
                                        <div id="desktop-slides-container">
                                            <!-- Desktop slide cards injected here -->
                                        </div>

                                        <div class="text-center p-3 border border-dashed rounded bg-light mt-3">
                                            <button type="button" class="btn btn-outline-primary fw-bold px-4" onclick="addDesktopSlide()">
                                                <i class="fas fa-plus me-1"></i> + Add Another Desktop Slide
                                            </button>
                                        </div>
                                    </div>


                                    <!-- ================= MOBILE TAB PANE ================= -->
                                    <div class="tab-pane fade" id="mobile-pane" role="tabpanel">
                                        
                                        <!-- Mobile Settings -->
                                        <div class="row g-3 mb-4 p-3 bg-light rounded border border-green-100">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Mobile Default Format Design</label>
                                                <select name="mobile_design" class="form-select">
                                                    <option value="design_1">Design 1 - Rounded Card with Bottom Pill Dots (Standard)</option>
                                                    <option value="design_2">Design 2 - Edge-to-Edge Full Width Slider</option>
                                                    <option value="design_3">Design 3 - Compact Hero Banner</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn btn-success fw-bold text-white" onclick="addMobileSlide()">
                                                    <i class="fas fa-plus me-1"></i> + Add Mobile Slide
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Mobile Slides Repeater Container -->
                                        <div id="mobile-slides-container">
                                            <!-- Mobile slide cards injected here -->
                                        </div>

                                        <div class="text-center p-3 border border-dashed rounded bg-light mt-3">
                                            <button type="button" class="btn btn-outline-success fw-bold px-4" onclick="addMobileSlide()">
                                                <i class="fas fa-plus me-1"></i> + Add Another Mobile Slide
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- 3. Overall Status and Submit -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label fw-bold mb-0 me-2">Overall Publish Status:</label>
                                    <select name="status" class="form-select d-inline-block w-auto">
                                        <option value="active" selected>🟢 Active (Publish Live)</option>
                                        <option value="inactive">🔴 Inactive (Save as Draft)</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="home-page-top-slider.php" class="btn btn-outline-secondary px-4">Cancel</a>
                                    <button type="submit" name="add_top_slider" class="btn btn-primary px-5 fw-bold shadow-sm">
                                        <i class="fas fa-cloud-upload-alt me-2"></i> Save & Publish Top Sliders
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        let desktopSlideCount = 0;
        let mobileSlideCount = 0;

        // ═════════════════════════════════════════════════════════════════════
        // AVAILABLE SECTIONS PER PAGE DEFINITION (Desktop & Mobile Separate)
        // ═════════════════════════════════════════════════════════════════════
        const AVAILABLE_SECTIONS = {
            home_desktop: {
                name: '🖥️ Home Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'home',
                sections: [
                    { optgroup: '⭐ Hero & Header' },
                    { id: 'home_hero', name: '1. Top Hero Slider (Home Hero Carousel)' },
                    { id: 'category_navbar', name: '2. Category Navbar / Top Bar' },
                    { id: 'small_banner_slider', name: '3. Small Banner Slider (Compact Carousel)' },
                    { optgroup: '🛍️ Featured Categories & Banners' },
                    { id: 'circle_categories', name: '4. Circular Categories Grid' },
                    { id: 'offer_banner_grid', name: '5. Offer Banner Grid (2x2 Multi Banners)' },
                    { id: 'home_mid_section_1', name: '6. Mid Promotional Banner Set 1' },
                    { id: 'home_mid_section_2', name: '7. Mid Promotional Banner Set 2' },
                    { optgroup: '📦 Product Showcase Carousels' },
                    { id: 'best_sellers', name: '8. Best Sellers Product Carousel' },
                    { id: 'top_deal', name: '9. Top Deals / Today\'s Specials' },
                    { id: 'new_arrivals', name: '10. New Arrivals Carousel' },
                    { id: 'featured_products', name: '11. Featured Products Slider' },
                    { id: 'trending_custom', name: '12. Trending Custom Gifts' },
                    { optgroup: '🏢 Trust & Bottom Layout' },
                    { id: 'corporate_gifting', name: '13. Corporate Gifting Showcase' },
                    { id: 'why_choose_us', name: '14. Why Choose Us / Trust Badges' },
                    { id: 'customer_reviews', name: '15. Customer Reviews / Testimonials' }
                ]
            },
            home_mobile: {
                name: '📱 Home Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'home',
                sections: [
                    { optgroup: '⭐ Top Hero Flow' },
                    { id: 'circle_categories', name: '1. Categories Circles Bar' },
                    { id: 'home_hero', name: '2. Top Hero Slider (Mobile Single)' },
                    { id: 'small_banner_slider', name: '3. Small Banner Carousel' },
                    { id: 'sectiontwoimg', name: '4. 1-Column Banner & Slider' },
                    { optgroup: '🔥 Offer Zones & Grids' },
                    { id: 'four_banner_grid', name: '5. 2x2 Square Offer Banner Grid' },
                    { id: 'sectionthreeimg', name: '6. Promotional Banner Set (16:6)' },
                    { id: 'responsive_banner_set', name: '7. Responsive Banner Set (16:5)' },
                    { optgroup: '🛍️ Product Sliders' },
                    { id: 'bestseller_slider', name: '8. New Arrival Product Carousel' },
                    { id: 'top_deal_slider', name: '9. Top Deals Carousel' },
                    { id: 'hot_collection', name: '10. Hot Collection Slider' },
                    { id: 'customer_reviews_mob', name: '11. Customer Reviews' }
                ]
            },
            category_desktop: {
                name: '🖥️ Category Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'category',
                sections: [
                    { optgroup: '🖼️ Banners & Visual Sliders' },
                    { id: 'category_small_banner_1', name: '1. Small Banner (First 2 Images - BannerSmall)' },
                    { id: 'category_before_after', name: '2. Before / After Comparison Slider (With/Without Printing)' },
                    { id: 'category_small_banner_2', name: '3. Small Banner (Next 2 Images - BannerSmall)' },
                    { id: 'category_hero_slider', name: '4. Main Category Slider (Hero Carousel)' },
                    { id: 'category_responsive_banner_7', name: '5. Responsive Banner Set (API 7 - 2 Cols)' },
                    { id: 'category_four_img_banner', name: '6. 4-Image Banner Carousel' },
                    { id: 'category_stats_banner', name: '7. Stats Banner (Why Choose Printmont)' },
                    { optgroup: '🛍️ Category Grids & Product Carousels' },
                    { id: 'category_grid_section', name: '8. Sub-Categories & Products Grid (CategoryGridSection)' },
                    { id: 'category_new_arrivals', name: '9. New Arrivals (Product Carousel)' },
                    { id: 'category_bestsellers', name: '10. Best Sellers (Product Carousel)' },
                    { id: 'category_personalized_gifts', name: '11. Personalized Gifts Section' },
                    { id: 'category_special_offers', name: '12. Special Offers (Product Carousel)' },
                    { id: 'category_customer_reviews', name: '13. Customer Reviews Section' }
                ]
            },
            category_mobile: {
                name: '📱 Category Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'category',
                sections: [
                    { optgroup: '🖼️ Top Banner Sliders' },
                    { id: 'category_mob_slider_1', name: '1. Top Single Banner Slider' },
                    { id: 'category_mob_double_1', name: '2. Double Banner Carousel (Side-by-Side 1)' },
                    { id: 'category_mob_banner_slider_1', name: '3. Banner and Slider 1' },
                    { optgroup: '🛍️ Best Sellers & Subcategory 1' },
                    { id: 'category_mob_bestseller', name: '4. Best Seller (Product Carousel)' },
                    { id: 'category_mob_banner_slider_2', name: '5. Banner and Slider 2' },
                    { id: 'category_mob_grid_1', name: '6. Subcategory 1: 3-Col Grid (Collection 1)' },
                    { id: 'category_mob_prod_list_1', name: '7. 2-Col Product List (Top Selection Tag)' },
                    { id: 'category_mob_double_2', name: '8. Double Banner Carousel 2' },
                    { optgroup: '🔥 Offer Zone & Subcategory 2' },
                    { id: 'category_mob_offer_1', name: '9. 🔥 Offer Zone Activated 1 (Top Discounts)' },
                    { id: 'category_mob_banner_slider_3', name: '10. Banner and Slider 3' },
                    { id: 'category_mob_grid_2', name: '11. Subcategory 2: 3-Col Grid (Collection 2)' },
                    { id: 'category_mob_double_3', name: '12. Double Banner Carousel 3' },
                    { optgroup: '🔥 Offer Zone & Subcategory 3 & 4' },
                    { id: 'category_mob_offer_2', name: '13. 🔥 Offer Zone Activated 2 (Discount For You)' },
                    { id: 'category_mob_banner_slider_4', name: '14. Banner and Slider 4' },
                    { id: 'category_mob_grid_3', name: '15. Subcategory 3: 3-Col Grid (Collection 3)' },
                    { id: 'category_mob_double_4', name: '16. Double Banner Carousel 4' },
                    { id: 'category_mob_offer_3', name: '17. 🔥 Offer Zone Activated 3 (Top Selection)' },
                    { id: 'category_mob_banner_slider_5', name: '18. Banner and Slider 5' },
                    { id: 'category_mob_grid_4', name: '19. Subcategory 4: 2-Col Grid (With View All)' },
                    { id: 'category_mob_double_5', name: '20. Double Banner Carousel 5' },
                    { id: 'category_mob_offer_4', name: '21. 🔥 Offer Zone Activated 4 (Top Rated)' },
                    { id: 'category_mob_prod_list_2', name: '22. 2-Col Product List 2 (Top Deals)' },
                    { optgroup: '📝 Category Info & Reviews' },
                    { id: 'category_mob_about', name: '23. About this Category & Tag Pills' },
                    { id: 'category_mob_reviews', name: '24. Customer Reviews Section' },
                    { id: 'category_mob_info_text', name: '25. Category Information Live Text Box' }
                ]
            },
            product_desktop: {
                name: '🖥️ Product Details — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'product',
                sections: [
                    { optgroup: '🛍️ Product & Customization' },
                    { id: 'product_gallery_info', name: 'Product Gallery & Main Info (Title / Price)' },
                    { id: 'product_customization_form', name: 'Customization & Upload Artwork Form' },
                    { id: 'product_buy_actions', name: 'Add to Cart / Buy Now Action Buttons' },
                    { id: 'product_offers_badges', name: 'Offers, Coupon Codes & Trust Badges' },
                    { optgroup: '⭐ Reviews, Q&A & Related' },
                    { id: 'product_frequently_bought', name: 'Frequently Bought Together' },
                    { id: 'product_reviews', name: 'Customer Ratings & Reviews Section' },
                    { id: 'product_qa', name: 'Questions & Answers (Q&A) Section' },
                    { id: 'product_related_carousel', name: 'Related / Recommended Products Carousel' }
                ]
            },
            product_mobile: {
                name: '📱 Product Details — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'product',
                sections: [
                    { optgroup: '🛍️ Mobile Product Details' },
                    { id: 'product_gallery_info', name: 'Mobile Product Image Carousel & Title' },
                    { id: 'product_customization_form', name: 'Mobile Customization / Artwork Options' },
                    { id: 'product_buy_actions', name: 'Mobile Sticky Add to Cart & Buy Now Bar' },
                    { id: 'product_offers_badges', name: 'Mobile Offers & Pincode Checker' },
                    { optgroup: '⭐ Reviews & Related' },
                    { id: 'product_frequently_bought', name: 'Mobile Frequently Bought Together' },
                    { id: 'product_reviews', name: 'Mobile Ratings & Customer Reviews' },
                    { id: 'product_related_carousel', name: 'Mobile Related Products Slider' }
                ]
            },
            cart_desktop: {
                name: '🖥️ Cart Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Cart Header & Navigation' },
                    { id: 'cart_items_list', name: 'Cart Items List Table' },
                    { id: 'cart_coupons', name: 'Apply Coupon / Discount Code Box' },
                    { id: 'cart_summary', name: 'Order Price Summary & Checkout Button' },
                    { id: 'cart_cross_sell', name: 'Recommended Products (You May Also Like)' }
                ]
            },
            cart_mobile: {
                name: '📱 Cart Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'cart',
                sections: [
                    { id: 'cart_header', name: 'Mobile Cart Header' },
                    { id: 'cart_items_list', name: 'Mobile Cart Items Cards' },
                    { id: 'cart_coupons', name: 'Mobile Coupon Code Box' },
                    { id: 'cart_summary', name: 'Mobile Sticky Checkout Bar' },
                    { id: 'cart_cross_sell', name: 'Mobile Recommended Products' }
                ]
            },
            checkout_desktop: {
                name: '🖥️ Checkout — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Checkout Step Header' },
                    { id: 'checkout_address', name: 'Delivery Address Section' },
                    { id: 'checkout_order_review', name: 'Order Review Items' },
                    { id: 'checkout_payment_methods', name: 'Payment Method Selection' },
                    { id: 'checkout_summary', name: 'Order Total & Place Order Button' }
                ]
            },
            checkout_mobile: {
                name: '📱 Checkout — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'checkout',
                sections: [
                    { id: 'checkout_stepper', name: 'Mobile Checkout Step Header' },
                    { id: 'checkout_address', name: 'Mobile Delivery Address Card' },
                    { id: 'checkout_order_review', name: 'Mobile Order Summary Accordion' },
                    { id: 'checkout_payment_methods', name: 'Mobile Payment Options' },
                    { id: 'checkout_summary', name: 'Mobile Sticky Place Order Bar' }
                ]
            },
            blog_desktop: {
                name: '🖥️ Blog Page — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Blog Header & Hero Article' },
                    { id: 'blog_posts_grid', name: 'Recent Articles 3-Column Grid' },
                    { id: 'blog_categories_sidebar', name: 'Blog Categories & Tags Sidebar' },
                    { id: 'blog_newsletter', name: 'Newsletter Subscription Box' }
                ]
            },
            blog_mobile: {
                name: '📱 Blog Page — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'blog',
                sections: [
                    { id: 'blog_header', name: 'Mobile Blog Hero Article' },
                    { id: 'blog_posts_grid', name: 'Mobile Single-Column Articles List' },
                    { id: 'blog_newsletter', name: 'Mobile Newsletter Subscription' }
                ]
            },
            about_desktop: {
                name: '🖥️ About Us — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'About Us Hero Banner' },
                    { id: 'about_story', name: 'Our Story / Mission & Vision' },
                    { id: 'about_features', name: 'Why Choose Us / Features Grid' },
                    { id: 'about_team', name: 'Team Members Section' }
                ]
            },
            about_mobile: {
                name: '📱 About Us — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'about',
                sections: [
                    { id: 'about_hero', name: 'Mobile About Us Banner' },
                    { id: 'about_story', name: 'Mobile Our Story & Stats' },
                    { id: 'about_features', name: 'Mobile Why Choose Us List' }
                ]
            },
            contact_desktop: {
                name: '🖥️ Contact Us — Desktop Layout',
                badgeClass: 'bg-primary text-white',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Contact Header & Location Map' },
                    { id: 'contact_form', name: 'Send Us a Message Form' },
                    { id: 'contact_info_cards', name: 'Contact Info & Address Cards' }
                ]
            },
            contact_mobile: {
                name: '📱 Contact Us — Mobile Layout',
                badgeClass: 'bg-success text-white',
                parentPage: 'contact',
                sections: [
                    { id: 'contact_header', name: 'Mobile Contact Header & Call Button' },
                    { id: 'contact_form', name: 'Mobile Send Message Form' },
                    { id: 'contact_info_cards', name: 'Mobile Contact & Location Cards' }
                ]
            }
        };

        // Track live selected sections in memory so changes are never lost when toggling pages
        const CURRENT_SELECTIONS = {};

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
            updateCheckAllStatus(key);
        }

        function onPositionRadioChange(key, newPos) {
            if (!CURRENT_SELECTIONS[key]) {
                CURRENT_SELECTIONS[key] = { position: newPos, sections: [] };
            } else {
                CURRENT_SELECTIONS[key].position = newPos;
            }
        }

        function toggleAllSectionsForCard(key, selectAll) {
            const container = document.getElementById(`sec_checkboxes_${key}`);
            if (!container) return;
            const checkboxes = container.querySelectorAll('.placement-sec-checkbox');
            const newSections = [];
            checkboxes.forEach(cb => {
                cb.checked = selectAll;
                if (selectAll) newSections.push(cb.value);
            });
            if (!CURRENT_SELECTIONS[key]) {
                CURRENT_SELECTIONS[key] = { position: 'top', sections: [] };
            }
            CURRENT_SELECTIONS[key].sections = selectAll ? newSections : [];
            updateCheckAllStatus(key);
        }

        function updateCheckAllStatus(key) {
            const container = document.getElementById(`sec_checkboxes_${key}`);
            if (!container) return;
            const total = container.querySelectorAll('.placement-sec-checkbox').length;
            const checked = container.querySelectorAll('.placement-sec-checkbox:checked').length;
            const badge = document.getElementById(`selected_badge_${key}`);
            if (badge) {
                badge.textContent = `${checked} / ${total} Selected`;
                badge.className = checked > 0 ? 'badge bg-primary rounded-pill px-2' : 'badge bg-secondary rounded-pill px-2';
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
                        <p class="text-muted small mb-0">Upar "Apply On Pages" me se kam se kam 1 page select karein.</p>
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
                let totalSecCount = 0;
                let checkedSecCount = 0;

                config.sections.forEach((sec, sIdx) => {
                    if (sec.optgroup) {
                        checkboxesHtml += `<div class="p-1 px-2 bg-light text-muted fw-bold small border-bottom border-top mt-1" style="font-size: 11px;">${sec.optgroup}</div>`;
                        return;
                    }
                    totalSecCount++;
                    const isChecked = curSecs.includes(sec.id) || (curSecs.length === 0 && totalSecCount === 1);
                    if (isChecked) checkedSecCount++;
                    
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
                                <span class="${checkedSecCount > 0 ? 'badge bg-primary' : 'badge bg-secondary'} rounded-pill px-2" id="selected_badge_${key}" style="font-size:11px;">
                                    ${checkedSecCount} / ${totalSecCount} Selected
                                </span>
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

                                <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                                    <span class="small fw-bold text-muted">Target Sections:</span>
                                    <div>
                                        <button type="button" class="btn btn-xs btn-link p-0 text-primary fw-bold text-decoration-none me-2" style="font-size:11px;" onclick="toggleAllSectionsForCard('${key}', true)">Select All</button>
                                        <button type="button" class="btn btn-xs btn-link p-0 text-secondary fw-bold text-decoration-none" style="font-size:11px;" onclick="toggleAllSectionsForCard('${key}', false)">Clear</button>
                                    </div>
                                </div>

                                <div id="sec_checkboxes_${key}" class="border rounded bg-white" style="max-height: 220px; overflow-y: auto;">
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

        // =========================================================================
        // DYNAMIC DESKTOP SLIDES REPEATER
        // =========================================================================
        function addDesktopSlide() {
            const container = document.getElementById('desktop-slides-container');
            const index = desktopSlideCount++;
            const slideNum = index + 1;

            const card = document.createElement('div');
            card.className = 'slide-item-card';
            card.id = `desktop-slide-card-${index}`;
            card.innerHTML = `
                <div class="slide-header-desktop">
                    <span class="fw-bold text-primary">
                        <i class="fas fa-image me-1"></i> Desktop Hero Slide #${slideNum}
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-remove-slide" onclick="removeDesktopSlide(${index})">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Slide Title Name <span class="text-danger">*</span></label>
                            <input type="text" name="desktop_slides[${index}][title]" class="form-control" placeholder="e.g. Bespoke Corporate Gifts & Kits" value="Desktop Top Slide #${slideNum}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Sort Order</label>
                            <input type="number" name="desktop_slides[${index}][desktop_sort_order]" class="form-control" placeholder="1" value="${slideNum}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="desktop_slides[${index}][desktop_status]" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Format Design</label>
                            <select name="desktop_slides[${index}][desktop_design]" class="form-select">
                                <option value="format_1" selected>Format 1 - Full Width Banner</option>
                                <option value="format_2">Format 2 - Boxed Container with Shadow</option>
                                <option value="format_3">Format 3 - Rounded Card Hero</option>
                                <option value="format_4">Format 4 - Modern Split Showcase</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Desktop Banner Image Upload <span class="text-danger">*</span></label>
                            <input type="file" name="desktop_slide_image_${index}" class="form-control" accept="image/*" onchange="previewImg(this, 'preview_desk_${index}')">
                            <small class="text-muted" style="font-size:11px;">Recommended: 1920 x 480 px</small>
                            <img id="preview_desk_${index}" class="preview-img-box" alt="Desktop Preview">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Desktop Target URL</label>
                            <input type="text" name="desktop_slides[${index}][desktop_url]" class="form-control" placeholder="e.g. /category/same-day-products or /allproducts">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
            updateDeskCountBadge();
        }

        function removeDesktopSlide(index) {
            const card = document.getElementById(`desktop-slide-card-${index}`);
            if (card) {
                card.remove();
                updateDeskCountBadge();
            }
        }

        function updateDeskCountBadge() {
            const count = document.querySelectorAll('#desktop-slides-container .slide-item-card').length;
            const badge = document.getElementById('badge-desk-count');
            if (badge) badge.textContent = count;
        }

        // =========================================================================
        // DYNAMIC MOBILE SLIDES REPEATER
        // =========================================================================
        function addMobileSlide() {
            const container = document.getElementById('mobile-slides-container');
            const index = mobileSlideCount++;
            const slideNum = index + 1;

            const card = document.createElement('div');
            card.className = 'slide-item-card';
            card.id = `mobile-slide-card-${index}`;
            card.innerHTML = `
                <div class="slide-header-mobile">
                    <span class="fw-bold text-success">
                        <i class="fas fa-mobile-alt me-1"></i> Mobile Hero Slide #${slideNum}
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-remove-slide" onclick="removeMobileSlide(${index})">
                        <i class="fas fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Slide Title Name <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_slides[${index}][title]" class="form-control" placeholder="e.g. Mobile Hero Banner Offer" value="Mobile Top Slide #${slideNum}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Sort Order</label>
                            <input type="number" name="mobile_slides[${index}][mobile_sort_order]" class="form-control" placeholder="1" value="${slideNum}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="mobile_slides[${index}][mobile_status]" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Mobile Design</label>
                            <select name="mobile_slides[${index}][mobile_design]" class="form-select">
                                <option value="design_1" selected>Design 1 - Rounded Card with Bottom Pill Dots</option>
                                <option value="design_2">Design 2 - Edge-to-Edge Full Width Slider</option>
                                <option value="design_3">Design 3 - Compact Hero Banner</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Mobile Banner Image Upload <span class="text-danger">*</span></label>
                            <input type="file" name="mobile_slide_image_${index}" class="form-control" accept="image/*" onchange="previewImg(this, 'preview_mob_${index}')">
                            <small class="text-muted" style="font-size:11px;">Recommended: 750 x 380 px</small>
                            <img id="preview_mob_${index}" class="preview-img-box" alt="Mobile Preview">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Mobile Target URL</label>
                            <input type="text" name="mobile_slides[${index}][mobile_url]" class="form-control" placeholder="e.g. /category/same-day-products or /allproducts">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
            updateMobCountBadge();
        }

        function removeMobileSlide(index) {
            const card = document.getElementById(`mobile-slide-card-${index}`);
            if (card) {
                card.remove();
                updateMobCountBadge();
            }
        }

        function updateMobCountBadge() {
            const count = document.querySelectorAll('#mobile-slides-container .slide-item-card').length;
            const badge = document.getElementById('badge-mob-count');
            if (badge) badge.textContent = count;
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.page-checkbox').forEach(cb => {
                cb.addEventListener('change', updatePlacementCards);
            });
            updatePlacementCards();

            // Default: create 3 Desktop slides and 3 Mobile slides in group
            addDesktopSlide();
            addDesktopSlide();
            addDesktopSlide();

            addMobileSlide();
            addMobileSlide();
            addMobileSlide();
        });
    </script>
</body>
</html>
