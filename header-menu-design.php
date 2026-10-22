<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$conn = $database->getConnection();

$configFile = __DIR__ . '/config/header_menu_design.json';

// Design Templates for Home Page Desktop
$homeDesktopTemplates = [
    'design1' => [
        'name' => 'Design 1: Flipkart-Style Full Mega Menu',
        'badge' => 'Recommended / Standard',
        'badge_class' => 'bg-primary',
        'desc' => 'Multi-column category dropdown with brand logo, delivery location selector, live search bar, user utility icons, and horizontal category menu strip.',
        'features' => ['Hover Mega-Menu Flyout', 'Prominent Search Bar', 'Category Icon Bar', 'Full Responsive Canvas'],
        'type' => 'desktop'
    ],
    'design2' => [
        'name' => 'Design 2: Modern Compact Navbar',
        'badge' => 'Minimalist & Fast',
        'badge_class' => 'bg-success',
        'desc' => 'Streamlined single-tier header with inline category dropdowns, quick search bar, and subtle hover menus.',
        'features' => ['Compact Single-Tier', 'Clean White Background', 'Inline Category Dropdowns', 'Fast Visual Scanning'],
        'type' => 'desktop'
    ],
    'design3' => [
        'name' => 'Design 3: Two-Tier Mega Store Navigation',
        'badge' => 'High Conversion',
        'badge_class' => 'bg-warning text-dark',
        'desc' => 'High-contrast header featuring promotional announcement strip, corporate inquiry button, and high-visibility category pills.',
        'features' => ['Top Announcement Bar', 'Category Pill Badges', 'Corporate Quick Action', 'Multi-level Flyout'],
        'type' => 'desktop'
    ],
    'design4' => [
        'name' => 'Design 4: Visual Category Strip (App-Like)',
        'badge' => 'Touch & Image First',
        'badge_class' => 'bg-info text-dark',
        'desc' => 'Circular category image avatars with labels underneath header for instantaneous visual recognition and touch navigation.',
        'features' => ['Circular Category Avatars', 'Horizontal Scroll Strip', 'Touch Friendly', 'Fast Visual Recognition'],
        'type' => 'desktop'
    ]
];

// Design Templates for Home Page Mobile
$homeMobileTemplates = [
    'design1' => [
        'name' => 'Mobile Design 1: Circular Avatar Strip',
        'badge' => 'Popular / Flipkart-Style',
        'badge_class' => 'bg-primary',
        'desc' => 'Horizontal smooth-scrolling strip of circular category icons with badge tags right below the mobile top bar.',
        'features' => ['Smooth Touch Scrolling', 'Circular Image Avatars', 'Live Cart & Search Strip', 'Fast 1-Tap Category Drilldown'],
        'type' => 'mobile'
    ],
    'design2' => [
        'name' => 'Mobile Design 2: 2-Row Compact Grid Pills',
        'badge' => 'High Density',
        'badge_class' => 'bg-success',
        'desc' => 'Two rows of compact rounded category pills for maximum density and one-tap browsing without vertical page push.',
        'features' => ['2-Tier Category Pills', 'Compact Vertical Footprint', 'Active Category Highlight', 'Direct Catalog Filtering'],
        'type' => 'mobile'
    ],
    'design3' => [
        'name' => 'Mobile Design 3: Offcanvas Drawer Accordion',
        'badge' => 'Clean & Focused',
        'badge_class' => 'bg-secondary',
        'desc' => 'Clean minimal topbar; tapping hamburger menu opens full hierarchical category accordion drawer with instant search.',
        'features' => ['Zero Clutter Topbar', 'Multi-level Subcategory Drilldown', 'Sticky Header Bar', 'Full Screen Nav Drawer'],
        'type' => 'mobile'
    ],
    'design4' => [
        'name' => 'Mobile Design 4: Sticky Tab Bar',
        'badge' => 'App-Like Native',
        'badge_class' => 'bg-info text-dark',
        'desc' => 'Sticky minimal category chip bar that remains pinned to the top during scroll for instant category switching.',
        'features' => ['Sticky Position Navigation', 'Active Indicator Pill', 'Micro-interactions', 'Bottom Nav Compatible'],
        'type' => 'mobile'
    ]
];

// Design Templates for Inner Pages Desktop
$innerDesktopTemplates = [
    'design1' => [
        'name' => 'Inner Design 1: Clean White Category Strip',
        'badge' => 'Clean & Standard',
        'badge_class' => 'bg-primary',
        'desc' => 'Clean white horizontal category navigation bar under header with dropdowns and category pathing for inner pages.',
        'features' => ['Consistent Header Frame', 'White Crisp Background', 'Flyout Mega Menus', 'Breadcrumb Co-existence'],
        'type' => 'desktop'
    ],
    'design2' => [
        'name' => 'Inner Design 2: Brand Blue Top Navigation Bar',
        'badge' => 'High Contrast',
        'badge_class' => 'bg-success',
        'desc' => 'Corporate blue background category strip matching site accent colors with crisp white typography and dropdown menus.',
        'features' => ['Printmont Brand Blue', 'High Visual Hierarchy', 'Contrasting Submenus', 'Instant Brand Recognition'],
        'type' => 'desktop'
    ],
    'design3' => [
        'name' => 'Inner Design 3: Compact Breadcrumb & Pills',
        'badge' => 'Space Efficient',
        'badge_class' => 'bg-warning text-dark',
        'desc' => 'Space-saving single-line breadcrumb combined with horizontal category chips for fast shopping context and catalog browsing.',
        'features' => ['Minimal Vertical Space', 'Integrated Breadcrumbs', 'Quick Category Switching', 'Clean Catalog Context'],
        'type' => 'desktop'
    ],
    'design4' => [
        'name' => 'Inner Design 4: Multi-Column Mega Catalog Bar',
        'badge' => 'Full Catalog',
        'badge_class' => 'bg-info text-dark',
        'desc' => 'Full catalog drawer with left vertical category list and right preview sub-category panels on hover or click.',
        'features' => ['Deep Hierarchy Browsing', 'Featured Promo Panels', 'Subcategory Badges', 'Catalog Overview'],
        'type' => 'desktop'
    ]
];

// Design Templates for Inner Pages Mobile
$innerMobileTemplates = [
    'design1' => [
        'name' => 'Inner Mobile Design 1: Back Button + Dynamic Title + Search',
        'badge' => 'Standard E-Commerce',
        'badge_class' => 'bg-primary',
        'desc' => 'Left back arrow button, current dynamic page title (e.g. "Custom T-Shirts" or "Cart"), search trigger, and cart counter badge.',
        'features' => ['Intuitive Back Navigation', 'Dynamic Page Title', 'Search & Cart Icons', 'Zero Navigation Clutter'],
        'type' => 'mobile'
    ],
    'design2' => [
        'name' => 'Inner Mobile Design 2: Subcategory Filter Pill Bar',
        'badge' => 'Deep Browsing',
        'badge_class' => 'bg-success',
        'desc' => 'Inner page header plus an extra horizontal scrolling strip of child subcategories for instantaneous in-category filtering.',
        'features' => ['In-Category Subcategory Pills', 'Instant Filter Trigger', 'Active Pill State', 'Back Arrow Header'],
        'type' => 'mobile'
    ],
    'design3' => [
        'name' => 'Inner Mobile Design 3: App-Style Header with Direct Drawer',
        'badge' => 'App-Like Native',
        'badge_class' => 'bg-secondary',
        'desc' => 'Native mobile app feel with hamburger menu directly opening filtered category navigation, search bar, and cart.',
        'features' => ['Native App Aesthetic', 'Direct Flyout Menu', 'Cart & Wishlist Badges', 'Consistent Action Bar'],
        'type' => 'mobile'
    ],
    'design4' => [
        'name' => 'Inner Mobile Design 4: Minimal Header with Quick Actions',
        'badge' => 'Ultra Compact',
        'badge_class' => 'bg-info text-dark',
        'desc' => 'Ultra-compact header with integrated search input and category filter chips to preserve maximum screen estate for products.',
        'features' => ['Inline Search Input', 'Maximum Product Real Estate', 'Sticky Header Bar', 'Lightweight Footprint'],
        'type' => 'mobile'
    ]
];

// Helper to read config
function readHeaderConfig($configFile) {
    $defaults = [
        'home_desktop_design'   => 'design1',
        'home_mobile_design'    => 'design1',
        'inner_desktop_design'  => 'design1',
        'inner_mobile_design'   => 'design1',
        'design'                => 'design1',
        'category_image_shape'  => 'circle',
        'category_image_size'   => 56,
        'sticky_header'         => true,
        'announcement_bar'      => true,
        'announcement_text'     => '🎉 Free Delivery on Custom Orders over ₹999 | Corporate & Bulk Discounts Available!',
        'category_bar_style'    => 'with_icons',
        'search_bar_style'      => 'expanded',
        'show_wishlist'         => true,
        'show_cart'             => true
    ];

    if (!file_exists($configFile)) {
        return $defaults;
    }
    $content = file_get_contents($configFile);
    $data = json_decode($content, true);
    if (!is_array($data)) {
        return $defaults;
    }
    return array_merge($defaults, $data);
}

$headerConfig = readHeaderConfig($configFile);
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homeDesktop = $_POST['home_desktop_design'] ?? 'design1';
    $homeMobile  = $_POST['home_mobile_design'] ?? 'design1';
    $innerDesktop= $_POST['inner_desktop_design'] ?? 'design1';
    $innerMobile = $_POST['inner_mobile_design'] ?? 'design1';

    if (!array_key_exists($homeDesktop, $homeDesktopTemplates)) $homeDesktop = 'design1';
    if (!array_key_exists($homeMobile, $homeMobileTemplates)) $homeMobile = 'design1';
    if (!array_key_exists($innerDesktop, $innerDesktopTemplates)) $innerDesktop = 'design1';
    if (!array_key_exists($innerMobile, $innerMobileTemplates)) $innerMobile = 'design1';

    $imgShape = $_POST['category_image_shape'] ?? 'circle';
    if (!in_array($imgShape, ['circle', 'square', 'rounded_square'])) $imgShape = 'circle';
    $imgSize = (int)($_POST['category_image_size'] ?? 56);
    if ($imgSize < 36 || $imgSize > 120) $imgSize = 56;

    $newConfig = [
        'home_desktop_design'   => $homeDesktop,
        'home_mobile_design'    => $homeMobile,
        'inner_desktop_design'  => $innerDesktop,
        'inner_mobile_design'   => $innerMobile,
        'design'                => $homeDesktop, // Fallback
        'category_image_shape'  => $imgShape,
        'category_image_size'   => $imgSize,
        'sticky_header'         => isset($_POST['sticky_header']),
        'announcement_bar'      => isset($_POST['announcement_bar']),
        'announcement_text'     => trim($_POST['announcement_text'] ?? ''),
        'category_bar_style'    => $_POST['category_bar_style'] ?? 'with_icons',
        'search_bar_style'      => $_POST['search_bar_style'] ?? 'expanded',
        'show_wishlist'         => isset($_POST['show_wishlist']),
        'show_cart'             => isset($_POST['show_cart']),
        'updated_at'            => date('Y-m-d H:i:s')
    ];

    file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));
    $_SESSION['success_message'] = 'Header Menu Designs for Home Page & Inner Pages (Desktop & Mobile) updated successfully!';
    header('Location: header-menu-design.php');
    exit;
}

// Fetch 6 sample categories for visual preview
$catRes = $conn->query("SELECT id, name, icon, image FROM categories WHERE status = 'active' AND parent_id = 0 ORDER BY display_order ASC LIMIT 6");
$sampleCategories = [];
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $sampleCategories[] = $c;
    }
}
if (empty($sampleCategories)) {
    $sampleCategories = [
        ['name' => 'Visiting Cards', 'icon' => 'fa-id-card'],
        ['name' => 'Apparel', 'icon' => 'fa-tshirt'],
        ['name' => 'Personalised Gifts', 'icon' => 'fa-gift'],
        ['name' => 'Stationery', 'icon' => 'fa-pen-nib'],
        ['name' => 'Packaging', 'icon' => 'fa-box'],
        ['name' => 'Signage', 'icon' => 'fa-bullhorn']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Header Category Menu &bull; Select Design | PrintMont Admin</title>
    
    <!-- Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="js/settings.js"></script>

    <style>
        :root {
            --pm-primary: #0b53a1;
            --pm-primary-dark: #073b75;
            --pm-primary-light: #e0edff;
            --pm-accent: #0284c7;
            --pm-success: #10b981;
            --pm-warning: #f59e0b;
            --pm-danger: #ef4444;
            --pm-dark: #0f172a;
            --pm-border: #e2e8f0;
            --pm-bg-subtle: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            opacity: 1 !important;
        }

        /* Top Module Header Navigation Pills */
        .module-nav-pills {
            display: inline-flex;
            gap: 6px;
            background: #ffffff;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid var(--pm-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .module-nav-link {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .module-nav-link:hover {
            color: var(--pm-primary);
            background: var(--pm-bg-subtle);
        }

        .module-nav-link.active {
            background: var(--pm-primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.25);
        }

        /* Active Config Summary Strip */
        .active-summary-bar {
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            padding: 16px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
        }

        .summary-badge-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Main Navigation Tab Buttons */
        .main-tab-bar {
            display: flex;
            gap: 8px;
            background: #ffffff;
            padding: 6px;
            border-radius: 12px;
            border: 1px solid var(--pm-border);
            margin-bottom: 1.5rem;
        }

        .main-tab-btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 9px;
            border: none;
            background: transparent;
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .main-tab-btn:hover {
            color: var(--pm-primary);
            background: #f8fafc;
        }

        .main-tab-btn.active {
            background: var(--pm-primary);
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(11, 83, 161, 0.25);
        }

        /* Modern Box Container */
        .config-box {
            background: #ffffff;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .config-box-header {
            padding: 16px 22px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .config-box-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .config-box-body {
            padding: 22px;
        }

        /* ======================================================== */
        /* INTERACTIVE DESIGN CARD                                 */
        /* ======================================================== */
        .design-choice-card {
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            user-select: none;
        }

        .design-choice-card:hover {
            border-color: #38bdf8;
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(11, 83, 161, 0.08);
        }

        /* Checked State */
        input[type="radio"]:checked + .design-choice-card {
            border-color: var(--pm-primary);
            background-color: #fbfdff;
            box-shadow: 0 0 0 3px rgba(11, 83, 161, 0.15), 0 8px 24px rgba(11, 83, 161, 0.08);
        }

        .selection-check-indicator {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-size: 13px;
            transition: all 0.2s ease;
            z-index: 5;
        }

        input[type="radio"]:checked + .design-choice-card .selection-check-indicator {
            background: var(--pm-primary);
            border-color: var(--pm-primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.35);
        }

        /* ======================================================== */
        /* WIREFRAME GRAPHICAL SIMULATORS                          */
        /* ======================================================== */
        .wireframe-canvas {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s ease;
        }

        input[type="radio"]:checked + .design-choice-card .wireframe-canvas {
            border-color: #bfdbfe;
            background: #f0f7ff;
        }

        /* Mini Wireframe Elements */
        .wf-announcement {
            height: 14px;
            border-radius: 4px;
            background: #3b82f6;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.3px;
        }

        .wf-header-bar {
            height: 32px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 0 8px;
            gap: 8px;
            margin-bottom: 6px;
        }

        .wf-logo {
            font-size: 10px;
            font-weight: 800;
            color: var(--pm-primary);
            white-space: nowrap;
        }

        .wf-search-box {
            flex: 1;
            height: 18px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 6px;
            font-size: 8px;
            color: #94a3b8;
        }

        .wf-icons {
            display: flex;
            gap: 6px;
            font-size: 9px;
            color: #64748b;
        }

        .wf-nav-strip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .wf-nav-item {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
        }

        .wf-nav-item.active {
            color: var(--pm-primary);
        }

        .wf-nav-dot {
            width: 8px;
            height: 8px;
            border-radius: 2px;
            background: #93c5fd;
        }

        .wf-pill {
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 700;
            background: #eff6ff;
            color: var(--pm-primary);
            border: 1px solid #bfdbfe;
        }

        .wf-pill.active {
            background: var(--pm-primary);
            color: #ffffff;
        }

        .wf-avatar-circle {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #e0f2fe;
            border: 1.5px solid #38bdf8;
            margin: 0 auto 2px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #0284c7;
        }

        /* Mobile Mini Frames */
        .wf-mobile-frame {
            max-width: 260px;
            margin: 0 auto;
            border: 2px solid #334155;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }

        .wf-mobile-topbar {
            background: #0b53a1;
            color: #ffffff;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9px;
            font-weight: 700;
        }

        .wf-mobile-content {
            padding: 8px 6px;
            background: #ffffff;
        }

        /* Interactive Simulator Showcase Top Banner */
        .interactive-simulator-card {
            background: linear-gradient(135deg, #031b33 0%, #073b75 100%);
            border-radius: 16px;
            padding: 24px;
            color: #ffffff;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(3, 27, 51, 0.15);
            position: relative;
            overflow: hidden;
        }

        .interactive-simulator-card::before {
            content: "";
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, rgba(14, 165, 233, 0) 70%);
            pointer-events: none;
        }

        /* Sticky Action Floating Bar */
        .sticky-action-bar {
            position: sticky;
            bottom: 14px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            padding: 14px 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        /* Live Preview Stage Inside Simulator */
        .live-preview-stage {
            background: #ffffff;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            color: #1e293b;
        }

        /* Category / Mega Menu Image Appearance Styles */
        .shape-picker-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0;
            user-select: none;
        }
        .shape-picker-card:hover {
            border-color: #93c5fd;
            background: #f8fafc;
        }
        .shape-picker-card.active {
            border-color: #0b53a1;
            background: #eff6ff;
            box-shadow: 0 2px 8px rgba(11, 83, 161, 0.12);
        }
        .shape-icon-box {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #0b53a1;
            font-size: 18px;
            flex-shrink: 0;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .shape-icon-box.shape-circle {
            border-radius: 50%;
        }
        .shape-icon-box.shape-square {
            border-radius: 0px;
        }
        .shape-icon-box.shape-rounded-square {
            border-radius: 10px;
        }
        .shape-picker-card.active .shape-icon-box {
            background: #0b53a1;
            color: #ffffff;
            border-color: #0b53a1;
        }
        .shape-check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: transparent;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .shape-picker-card.active .shape-check {
            background: #0b53a1;
            border-color: #0b53a1;
            color: #ffffff;
        }

        /* Size Preset Buttons */
        .size-preset-btn {
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 11px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #ffffff;
            transition: all 0.15s ease;
        }
        .size-preset-btn:hover {
            border-color: #0b53a1;
            color: #0b53a1;
            background: #f0f7ff;
        }
        .size-preset-btn.active {
            background-color: #0b53a1 !important;
            border-color: #0b53a1 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.2);
        }

        /* Live Preview Box */
        .live-preview-box {
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            min-height: 200px;
        }
        .preview-cat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 25%;
            padding: 4px;
        }
        .preview-thumb-wrap {
            width: 56px;
            height: 56px;
            background: #f8fafc;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 0.25s ease, height 0.25s ease, border-radius 0.25s ease, box-shadow 0.2s ease;
        }
        .preview-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            aspect-ratio: 1 / 1;
        }
        .preview-thumb-icon {
            background: #e0f2fe;
        }
        .preview-cat-label {
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 5px;
            white-space: nowrap;
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

                <!-- Header Title & Action Navigation Tabs -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1 text-muted small">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item">Header Category Menu</li>
                                <li class="breadcrumb-item active" aria-current="page">Select Design</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            <i class="fas fa-palette text-primary me-2"></i> Header Category Menu &bull; Select Design
                        </h2>
                        <p class="text-muted small mb-0">
                            Choose visual navigation layouts for <strong>Home Page</strong> and <strong>Inner Pages</strong> across Desktop &amp; Mobile devices.
                        </p>
                    </div>

                    <!-- Top Submenu Links -->
                    <div class="module-nav-pills">
                        <a href="add-top-icon.php" class="module-nav-link">
                            <i class="fas fa-star text-warning"></i> Top Icon
                        </a>
                        <a href="add-menu-page.php" class="module-nav-link">
                            <i class="fas fa-file-alt"></i> Top Menu Inside Pages
                        </a>
                        <a href="header-menu-design.php" class="module-nav-link active">
                            <i class="fas fa-palette"></i> Select Design
                        </a>
                        <a href="header-menu-list.php" class="module-nav-link">
                            <i class="fas fa-layer-group"></i> All menus
                        </a>
                    </div>
                </div>

                <!-- Global Feedback Alerts -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Current Active Selection Summary Strip -->
                <div class="active-summary-bar">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fw-bold text-dark">
                            <i class="fas fa-check-double text-success me-2"></i> Currently Active Layout Configurations:
                        </div>
                        <a href="http://localhost:5175/" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:11.5px;">
                            <i class="fas fa-external-link-alt me-1"></i> Preview Live Storefront
                        </a>
                    </div>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="summary-badge-item">
                                <div class="text-primary fs-4"><i class="fas fa-desktop"></i></div>
                                <div>
                                    <small class="text-muted d-block" style="font-size:10.5px;font-weight:700;">HOME DESKTOP</small>
                                    <strong class="text-dark small" id="summaryHomeDesk"><?php echo htmlspecialchars($homeDesktopTemplates[$headerConfig['home_desktop_design']]['name'] ?? 'Design 1'); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="summary-badge-item">
                                <div class="text-success fs-4"><i class="fas fa-mobile-alt"></i></div>
                                <div>
                                    <small class="text-muted d-block" style="font-size:10.5px;font-weight:700;">HOME MOBILE</small>
                                    <strong class="text-dark small" id="summaryHomeMob"><?php echo htmlspecialchars($homeMobileTemplates[$headerConfig['home_mobile_design']]['name'] ?? 'Mobile Design 1'); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="summary-badge-item">
                                <div class="text-info fs-4"><i class="fas fa-laptop"></i></div>
                                <div>
                                    <small class="text-muted d-block" style="font-size:10.5px;font-weight:700;">INNER DESKTOP</small>
                                    <strong class="text-dark small" id="summaryInnerDesk"><?php echo htmlspecialchars($innerDesktopTemplates[$headerConfig['inner_desktop_design']]['name'] ?? 'Inner Design 1'); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="summary-badge-item">
                                <div class="text-purple fs-4"><i class="fas fa-mobile-screen"></i></div>
                                <div>
                                    <small class="text-muted d-block" style="font-size:10.5px;font-weight:700;">INNER MOBILE</small>
                                    <strong class="text-dark small" id="summaryInnerMob"><?php echo htmlspecialchars($innerMobileTemplates[$headerConfig['inner_mobile_design']]['name'] ?? 'Inner Mobile 1'); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORM BEGINNING -->
                <form method="POST" action="header-menu-design.php" id="designForm">

                    <!-- Main Navigation Tabs: Home vs Inner vs Global -->
                    <div class="main-tab-bar">
                        <button type="button" class="main-tab-btn active" id="tabHomeBtn" onclick="switchMainTab('home')">
                            <i class="fas fa-home"></i>
                            <span>1. Home Page Menu Bar (होम पेज मेनू बार)</span>
                        </button>
                        <button type="button" class="main-tab-btn" id="tabInnerBtn" onclick="switchMainTab('inner')">
                            <i class="fas fa-file-lines"></i>
                            <span>2. Inner Pages Menu Bar (इनर पेज मेनू बार)</span>
                        </button>
                        <button type="button" class="main-tab-btn" id="tabGlobalBtn" onclick="switchMainTab('global')">
                            <i class="fas fa-sliders"></i>
                            <span>3. Global Accessories &amp; Features</span>
                        </button>
                    </div>

                    <!-- ======================================================== -->
                    <!-- SECTION 1: HOME PAGE MENU BAR (Desktop & Mobile)         -->
                    <!-- ======================================================== -->
                    <div id="sectionHome">

                        <!-- 1-PRE: Category & Mega Menu Thumbnail Image Appearance (Shape & Size) -->
                        <div class="config-box mb-4">
                            <div class="config-box-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <h4 class="config-box-title mb-0">
                                    <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                                        <i class="fas fa-shapes"></i>
                                    </span>
                                    Home Page Mega Menu &bull; Category Image Appearance (छवि आकार व माप)
                                </h4>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5" style="font-size:12px;">
                                        Active: <strong id="lblShapeSummary" class="text-primary"><?php echo ucfirst(str_replace('_', ' ', $headerConfig['category_image_shape'] ?? 'circle')); ?></strong> &bull; <strong id="lblSizeSummary" class="text-primary"><?php echo (int)($headerConfig['category_image_size'] ?? 56); ?>px</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4 align-items-stretch">
                                    
                                    <!-- Shape Selection (3 Interactive Radio Cards) -->
                                    <div class="col-lg-5">
                                        <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-vector-square text-primary"></i> 1. Select Thumbnail Image Shape (छवि का आकार)
                                        </label>
                                        <p class="text-muted small mb-3">Admin can choose whether the category images on the homepage appear in Circle, Square, or Rounded Square shape.</p>
                                        
                                        <div class="d-flex flex-column gap-2.5">
                                            <!-- Option 1: Circle -->
                                            <label class="shape-picker-card <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'circle' ? 'active' : ''; ?>" id="shapeCard_circle" onclick="selectImageShape('circle')">
                                                <input type="radio" name="category_image_shape" value="circle" class="d-none" <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'circle' ? 'checked' : ''; ?>>
                                                <div class="shape-icon-box shape-circle">
                                                    <i class="fas fa-circle"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong class="text-dark d-block" style="font-size:13.5px;">Circle (सर्कल / गोल)</strong>
                                                    <span class="text-muted small">Border-radius: 50% (Standard Flipkart style circular badge)</span>
                                                </div>
                                                <span class="shape-check"><i class="fas fa-check"></i></span>
                                            </label>

                                            <!-- Option 2: Square -->
                                            <label class="shape-picker-card <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'square' ? 'active' : ''; ?>" id="shapeCard_square" onclick="selectImageShape('square')">
                                                <input type="radio" name="category_image_shape" value="square" class="d-none" <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'square' ? 'checked' : ''; ?>>
                                                <div class="shape-icon-box shape-square">
                                                    <i class="fas fa-square"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong class="text-dark d-block" style="font-size:13.5px;">Square (स्क्वायर / चौकोर)</strong>
                                                    <span class="text-muted small">Border-radius: 0px (Sharp 1:1 square modern photo card)</span>
                                                </div>
                                                <span class="shape-check"><i class="fas fa-check"></i></span>
                                            </label>

                                            <!-- Option 3: Rounded Square -->
                                            <label class="shape-picker-card <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'rounded_square' ? 'active' : ''; ?>" id="shapeCard_rounded_square" onclick="selectImageShape('rounded_square')">
                                                <input type="radio" name="category_image_shape" value="rounded_square" class="d-none" <?php echo ($headerConfig['category_image_shape'] ?? 'circle') === 'rounded_square' ? 'checked' : ''; ?>>
                                                <div class="shape-icon-box shape-rounded-square">
                                                    <i class="fas fa-square-full"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong class="text-dark d-block" style="font-size:13.5px;">Rounded Square (राउंडेड स्क्वायर / सॉफ्ट कॉर्नर)</strong>
                                                    <span class="text-muted small">Border-radius: 10px (Modern mobile app icon aesthetic)</span>
                                                </div>
                                                <span class="shape-check"><i class="fas fa-check"></i></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Size Selection & Presets -->
                                    <div class="col-lg-3 border-start ps-lg-4">
                                        <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                            <i class="fas fa-up-right-and-down-left-from-center text-primary"></i> 2. Select Image Size (छवि का माप)
                                        </label>
                                        <p class="text-muted small mb-3">Adjust thumbnail width &amp; height in pixels (36px to 100px).</p>

                                        <!-- Presets -->
                                        <div class="mb-3">
                                            <small class="text-muted fw-bold d-block mb-1.5" style="font-size:11px;">QUICK SIZE PRESETS:</small>
                                            <div class="d-flex flex-wrap gap-1.5">
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 44 ? 'active' : ''; ?>" onclick="setImageSize(44)">44px</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 48 ? 'active' : ''; ?>" onclick="setImageSize(48)">48px</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 56 ? 'active' : ''; ?>" onclick="setImageSize(56)">56px <small>(Std)</small></button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 64 ? 'active' : ''; ?>" onclick="setImageSize(64)">64px</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 72 ? 'active' : ''; ?>" onclick="setImageSize(72)">72px</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary size-preset-btn <?php echo ($headerConfig['category_image_size'] ?? 56) == 80 ? 'active' : ''; ?>" onclick="setImageSize(80)">80px</button>
                                            </div>
                                        </div>

                                        <!-- Custom Slider & Input -->
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="small fw-bold text-dark">Custom Size:</span>
                                                <div class="input-group input-group-sm" style="width: 105px;">
                                                    <input type="number" id="numImageSize" name="category_image_size" class="form-control text-center fw-bold text-primary" 
                                                           min="36" max="100" value="<?php echo htmlspecialchars($headerConfig['category_image_size'] ?? 56); ?>"
                                                           oninput="syncImageSizeFromInput(this.value)">
                                                    <span class="input-group-text bg-white small">px</span>
                                                </div>
                                            </div>
                                            <input type="range" class="form-range" id="rangeImageSize" min="36" max="100" step="2" 
                                                   value="<?php echo htmlspecialchars($headerConfig['category_image_size'] ?? 56); ?>"
                                                   oninput="syncImageSizeFromSlider(this.value)">
                                            <div class="d-flex justify-content-between text-muted" style="font-size:10px;">
                                                <span>36px (Small)</span>
                                                <span>56px (Default)</span>
                                                <span>100px (Large)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Interactive Preview -->
                                    <div class="col-lg-4 border-start ps-lg-4 d-flex flex-column">
                                        <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                                            <span class="d-flex align-items-center gap-2">
                                                <i class="fas fa-eye text-success"></i> 3. Live Preview (लाइव प्रिव्यू)
                                            </span>
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold" style="font-size:10px;">Instant Feedback</span>
                                        </label>
                                        <p class="text-muted small mb-3">See how thumbnails and icons will render on the live homepage navbar:</p>

                                        <div class="live-preview-box flex-grow-1 p-3 rounded-3 bg-white border d-flex flex-column justify-content-center">
                                            <div class="d-flex align-items-center justify-content-around text-center w-100 overflow-hidden py-2" id="previewRow">
                                                <!-- Preview Item 1 -->
                                                <div class="preview-cat-item">
                                                    <div class="preview-thumb-wrap mx-auto mb-1" id="previewWrap1">
                                                        <img src="../frontend/public/girl-product-img/subsubcat-104.jpeg" 
                                                             alt="Apparel" class="preview-thumb-img" id="previewImg1"
                                                             onerror="this.src='../frontend/public/default-img.jpg'">
                                                    </div>
                                                    <span class="preview-cat-label">Apparel</span>
                                                </div>
                                                <!-- Preview Item 2 -->
                                                <div class="preview-cat-item">
                                                    <div class="preview-thumb-wrap mx-auto mb-1" id="previewWrap2">
                                                        <img src="../frontend/public/men_shirt/men-shirt-2.jpeg" 
                                                             alt="Gifts" class="preview-thumb-img" id="previewImg2"
                                                             onerror="this.src='../frontend/public/default-img.jpg'">
                                                    </div>
                                                    <span class="preview-cat-label">Custom Gifts</span>
                                                </div>
                                                <!-- Preview Item 3 (Icon Fallback) -->
                                                <div class="preview-cat-item">
                                                    <div class="preview-thumb-wrap preview-thumb-icon mx-auto mb-1" id="previewWrap3">
                                                        <i class="fas fa-id-card text-primary" id="previewIcon3"></i>
                                                    </div>
                                                    <span class="preview-cat-label">Cards</span>
                                                </div>
                                                <!-- Preview Item 4 -->
                                                <div class="preview-cat-item">
                                                    <div class="preview-thumb-wrap mx-auto mb-1" id="previewWrap4">
                                                        <img src="../frontend/public/girl-product-img/plant-1.jpeg" 
                                                             alt="Decor" class="preview-thumb-img" id="previewImg4"
                                                             onerror="this.src='../frontend/public/default-img.jpg'">
                                                    </div>
                                                    <span class="preview-cat-label">Home Decor</span>
                                                </div>
                                            </div>

                                            <div class="text-center mt-3 pt-2 border-top">
                                                <small class="text-muted" style="font-size:11.5px;">
                                                    Active Preview: <strong id="previewActiveDetails" class="text-primary">Circle &bull; 56px</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        
                        <!-- 1A: Home Page Desktop Layout Choices -->
                        <div class="config-box">
                            <div class="config-box-header">
                                <h4 class="config-box-title">
                                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                        <i class="fas fa-desktop"></i>
                                    </span>
                                    Home Page &bull; Desktop View Layout (डेस्कटॉप व्यू मेनू)
                                </h4>
                                <span class="badge bg-primary">Applies to <code>/</code> on Screens &ge; 992px</span>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4">

                                    <!-- Design 1: Flipkart-Style Full Mega Menu -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_desktop_design" id="hd_design1" value="design1" class="d-none"
                                               <?php echo ($headerConfig['home_desktop_design'] ?? 'design1') === 'design1' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_desktop', 'Design 1: Flipkart-Style Full Mega Menu')">
                                        <label for="hd_design1" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Graphical Wireframe Preview -->
                                            <div class="wireframe-canvas">
                                                <div class="wf-announcement">🎉 Free Delivery on Custom Orders over ₹999</div>
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search 10,000+ custom gifts, t-shirts...</div>
                                                    <div class="wf-icons"><i class="fas fa-user"></i> <i class="fas fa-heart"></i> <i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-nav-strip">
                                                    <span class="wf-nav-item active"><span class="wf-nav-dot"></span> Visiting Cards <i class="fas fa-chevron-down ms-1" style="font-size:7px;"></i></span>
                                                    <span class="wf-nav-item"><span class="wf-nav-dot"></span> Apparel <i class="fas fa-chevron-down ms-1" style="font-size:7px;"></i></span>
                                                    <span class="wf-nav-item"><span class="wf-nav-dot"></span> Gifts <i class="fas fa-chevron-down ms-1" style="font-size:7px;"></i></span>
                                                    <span class="wf-nav-item"><span class="wf-nav-dot"></span> Stationery</span>
                                                    <span class="wf-nav-item"><span class="wf-nav-dot"></span> Packaging</span>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold text-dark mb-0 fs-6">Design 1: Flipkart-Style Full Mega Menu</h5>
                                            </div>
                                            <div class="mb-2">
                                                <span class="badge bg-primary">Recommended / Standard</span>
                                                <span class="badge bg-light text-muted border">Mega Flyout</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Multi-column category dropdown with brand logo, live search bar, user utility icons, and dedicated horizontal category menu strip.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Hover Mega-Menu Flyout</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Category Icon Strip</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Prominent Search Bar</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Delivery City Selector</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Design 2: Modern Compact Navbar -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_desktop_design" id="hd_design2" value="design2" class="d-none"
                                               <?php echo ($headerConfig['home_desktop_design'] ?? '') === 'design2' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_desktop', 'Design 2: Modern Compact Navbar')">
                                        <label for="hd_design2" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Graphical Wireframe Preview -->
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar" style="height:38px;">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="d-flex gap-2 ms-2 me-auto">
                                                        <span class="wf-nav-item active">Visiting Cards</span>
                                                        <span class="wf-nav-item">Apparel</span>
                                                        <span class="wf-nav-item">Corporate Gifts</span>
                                                        <span class="wf-nav-item">Packaging</span>
                                                    </div>
                                                    <div class="wf-search-box" style="flex:0 0 120px;"><i class="fas fa-search me-1"></i> Search</div>
                                                    <div class="wf-icons"><i class="fas fa-heart"></i> <i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="p-2 bg-white rounded border text-center" style="font-size:9px;color:#94a3b8;">
                                                    Streamlined Single-Tier: Category links sit directly in the primary header row
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold text-dark mb-0 fs-6">Design 2: Modern Compact Navbar</h5>
                                            </div>
                                            <div class="mb-2">
                                                <span class="badge bg-success">Minimalist &amp; Fast</span>
                                                <span class="badge bg-light text-muted border">Single-Tier</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Streamlined single-tier header with inline category links, quick search bar, and clean whitespace. Saves maximum vertical screen height.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Compact Single-Tier</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Inline Navigation</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Fast Visual Scanning</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Clean Modern Aesthetic</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Design 3: Two-Tier Mega Store Navigation -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_desktop_design" id="hd_design3" value="design3" class="d-none"
                                               <?php echo ($headerConfig['home_desktop_design'] ?? '') === 'design3' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_desktop', 'Design 3: Two-Tier Mega Store Navigation')">
                                        <label for="hd_design3" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Graphical Wireframe Preview -->
                                            <div class="wireframe-canvas">
                                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:8px;color:#64748b;">
                                                    <span>🌟 High Conversion Corporate Bar</span>
                                                    <span class="badge bg-dark text-white py-0 px-1" style="font-size:7px;">Bulk Inquiry 📞</span>
                                                </div>
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search corporate gifts...</div>
                                                    <div class="wf-icons"><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="d-flex gap-1 overflow-hidden py-1">
                                                    <span class="wf-pill active">⚡ Same Day</span>
                                                    <span class="wf-pill">👔 Apparel</span>
                                                    <span class="wf-pill">🎁 Gift Hampers</span>
                                                    <span class="wf-pill">📦 Boxes</span>
                                                    <span class="wf-pill">🪧 Banners</span>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold text-dark mb-0 fs-6">Design 3: Two-Tier Mega Store Navigation</h5>
                                            </div>
                                            <div class="mb-2">
                                                <span class="badge bg-warning text-dark">High Conversion</span>
                                                <span class="badge bg-light text-muted border">Category Pills</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                High-contrast header featuring corporate inquiry action, promotional announcement strip, and rounded category pill badges.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Category Pill Badges</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Corporate Quick Action</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Two-Tier Structure</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Multi-Level Flyout</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Design 4: Visual Category Strip (App-Like) -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_desktop_design" id="hd_design4" value="design4" class="d-none"
                                               <?php echo ($headerConfig['home_desktop_design'] ?? '') === 'design4' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_desktop', 'Design 4: Visual Category Strip (App-Like)')">
                                        <label for="hd_design4" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Graphical Wireframe Preview -->
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search catalog...</div>
                                                    <div class="wf-icons"><i class="fas fa-user"></i> <i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="d-flex justify-content-around py-1 bg-white rounded border">
                                                    <div class="text-center">
                                                        <div class="wf-avatar-circle"><i class="fas fa-id-card"></i></div>
                                                        <span style="font-size:7px;font-weight:700;">Cards</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="wf-avatar-circle"><i class="fas fa-tshirt"></i></div>
                                                        <span style="font-size:7px;font-weight:700;">Apparel</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="wf-avatar-circle"><i class="fas fa-gift"></i></div>
                                                        <span style="font-size:7px;font-weight:700;">Gifts</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="wf-avatar-circle"><i class="fas fa-mug-hot"></i></div>
                                                        <span style="font-size:7px;font-weight:700;">Drinkware</span>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="wf-avatar-circle"><i class="fas fa-box"></i></div>
                                                        <span style="font-size:7px;font-weight:700;">Packaging</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold text-dark mb-0 fs-6">Design 4: Visual Category Strip (App-Like)</h5>
                                            </div>
                                            <div class="mb-2">
                                                <span class="badge bg-info text-dark">Touch &amp; Image First</span>
                                                <span class="badge bg-light text-muted border">Avatar Icons</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Circular category image avatars with labels underneath header for instantaneous visual recognition and mobile-friendly scanning on desktop.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Circular Category Avatars</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Fast Visual Recognition</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> App-Like Experience</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Horizontal Scroll Strip</div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- 1B: Home Page Mobile Layout Choices -->
                        <div class="config-box">
                            <div class="config-box-header">
                                <h4 class="config-box-title">
                                    <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                                        <i class="fas fa-mobile-alt"></i>
                                    </span>
                                    Home Page &bull; Mobile View Layout (मोबाइल व्यू मेनू)
                                </h4>
                                <span class="badge bg-success">Applies to <code>/</code> on Mobile Devices &lt; 992px</span>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4">

                                    <!-- Mobile Design 1: Circular Avatar Strip -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_mobile_design" id="hm_design1" value="design1" class="d-none"
                                               <?php echo ($headerConfig['home_mobile_design'] ?? 'design1') === 'design1' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_mobile', 'Mobile Design 1: Circular Avatar Strip')">
                                        <label for="hm_design1" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Phone Wireframe Simulator -->
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar">
                                                    <i class="fas fa-bars"></i>
                                                    <span>PrintMont</span>
                                                    <div><i class="fas fa-search me-1"></i> <i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content">
                                                    <div class="d-flex justify-content-between overflow-hidden">
                                                        <div class="text-center">
                                                            <div class="wf-avatar-circle" style="width:26px;height:26px;"><i class="fas fa-id-card"></i></div>
                                                            <span style="font-size:7px;">Cards</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="wf-avatar-circle" style="width:26px;height:26px;"><i class="fas fa-tshirt"></i></div>
                                                            <span style="font-size:7px;">Shirts</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="wf-avatar-circle" style="width:26px;height:26px;"><i class="fas fa-gift"></i></div>
                                                            <span style="font-size:7px;">Gifts</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="wf-avatar-circle" style="width:26px;height:26px;"><i class="fas fa-mug-hot"></i></div>
                                                            <span style="font-size:7px;">Mugs</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Mobile Design 1: Circular Avatar Strip</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-primary">Popular / Flipkart-Style</span>
                                                <span class="badge bg-light text-muted border">Touch Strip</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Horizontal smooth-scrolling strip of circular category icons with badge tags right below the mobile top bar.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Smooth Touch Scrolling</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Circular Image Avatars</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Live Cart &amp; Search Strip</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Fast 1-Tap Drilldown</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Mobile Design 2: 2-Row Compact Grid Pills -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_mobile_design" id="hm_design2" value="design2" class="d-none"
                                               <?php echo ($headerConfig['home_mobile_design'] ?? '') === 'design2' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_mobile', 'Mobile Design 2: 2-Row Compact Grid Pills')">
                                        <label for="hm_design2" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Phone Wireframe Simulator -->
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar">
                                                    <i class="fas fa-bars"></i>
                                                    <span>PrintMont</span>
                                                    <div><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content">
                                                    <div class="d-flex gap-1 mb-1">
                                                        <span class="wf-pill active">Visiting Cards</span>
                                                        <span class="wf-pill">T-Shirts</span>
                                                        <span class="wf-pill">Gifts</span>
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <span class="wf-pill">Stationery</span>
                                                        <span class="wf-pill">Packaging</span>
                                                        <span class="wf-pill">Banners</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Mobile Design 2: 2-Row Compact Grid Pills</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-success">High Density</span>
                                                <span class="badge bg-light text-muted border">2-Tier Grid</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Two rows of compact rounded category pills for maximum density and one-tap browsing without pushing banner content down.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> 2-Tier Category Pills</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Compact Vertical Footprint</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Active Category Highlight</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Direct Catalog Filtering</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Mobile Design 3: Offcanvas Drawer Accordion -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_mobile_design" id="hm_design3" value="design3" class="d-none"
                                               <?php echo ($headerConfig['home_mobile_design'] ?? '') === 'design3' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_mobile', 'Mobile Design 3: Offcanvas Drawer Accordion')">
                                        <label for="hm_design3" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Phone Wireframe Simulator -->
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar">
                                                    <div><i class="fas fa-bars me-1"></i> <span>Menu</span></div>
                                                    <span>PrintMont</span>
                                                    <div><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content">
                                                    <div class="p-1 border rounded bg-light mb-1" style="font-size:7.5px;">
                                                        <i class="fas fa-folder-open text-primary me-1"></i> Corporate Apparel ▾
                                                    </div>
                                                    <div class="p-1 border rounded bg-light" style="font-size:7.5px;">
                                                        <i class="fas fa-folder-open text-primary me-1"></i> Custom Drinkware ▾
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Mobile Design 3: Offcanvas Drawer Accordion</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">Clean &amp; Focused</span>
                                                <span class="badge bg-light text-muted border">Flyout Drawer</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Clean minimal topbar; tapping hamburger menu opens full hierarchical category accordion drawer with instant search.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Zero Clutter Topbar</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Multi-level Drilldown</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Sticky Header Bar</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Full Screen Nav Drawer</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Mobile Design 4: Sticky Tab Bar -->
                                    <div class="col-md-6">
                                        <input type="radio" name="home_mobile_design" id="hm_design4" value="design4" class="d-none"
                                               <?php echo ($headerConfig['home_mobile_design'] ?? '') === 'design4' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('home_mobile', 'Mobile Design 4: Sticky Tab Bar')">
                                        <label for="hm_design4" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <!-- Phone Wireframe Simulator -->
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar" style="background:#03172c;">
                                                    <span>PrintMont Store</span>
                                                    <div><i class="fas fa-search"></i></div>
                                                </div>
                                                <div class="wf-mobile-content">
                                                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1" style="font-size:8px;">
                                                        <strong class="text-primary border-bottom border-primary border-2 pb-1">⚡ Trending</strong>
                                                        <span class="text-muted">Cards</span>
                                                        <span class="text-muted">Shirts</span>
                                                        <span class="text-muted">Gifts</span>
                                                    </div>
                                                    <div style="font-size:7px;color:#94a3b8;text-align:center;">
                                                        Sticky pinned navigation + bottom app bar
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Mobile Design 4: Sticky Tab Bar</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-info text-dark">App-Like Native</span>
                                                <span class="badge bg-light text-muted border">Pinned Tabs</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Sticky minimal category chip bar that remains pinned to the top during scroll for instantaneous category switching.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Sticky Position Nav</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Active Indicator Pill</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Micro-interactions</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Bottom Nav Compatible</div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- SECTION 2: INNER PAGES MENU BAR (Desktop & Mobile)       -->
                    <!-- ======================================================== -->
                    <div id="sectionInner" style="display:none;">
                        
                        <!-- 2A: Inner Pages Desktop Layout Choices -->
                        <div class="config-box">
                            <div class="config-box-header">
                                <h4 class="config-box-title">
                                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                        <i class="fas fa-desktop"></i>
                                    </span>
                                    Inner Pages &bull; Desktop View Layout (इनर पेज डेस्कटॉप)
                                </h4>
                                <span class="badge bg-primary">Applies to Catalog, Categories &amp; Product Details Pages</span>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4">

                                    <!-- Inner Desktop 1 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_desktop_design" id="id_design1" value="design1" class="d-none"
                                               <?php echo ($headerConfig['inner_desktop_design'] ?? 'design1') === 'design1' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_desktop', 'Inner Design 1: Clean White Category Strip')">
                                        <label for="id_design1" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search in this category...</div>
                                                    <div class="wf-icons"><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-nav-strip">
                                                    <span class="wf-nav-item active">Category Strip <i class="fas fa-angle-down"></i></span>
                                                    <span class="wf-nav-item">Subcategory <i class="fas fa-angle-down"></i></span>
                                                    <span class="wf-nav-item">Filters</span>
                                                    <span class="text-muted" style="font-size:7.5px;">Home &gt; Apparel &gt; T-Shirts</span>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Design 1: Clean White Category Strip</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-primary">Clean &amp; Standard</span>
                                                <span class="badge bg-light text-muted border">Crisp White</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Clean white horizontal category navigation bar under header with dropdowns and category pathing for inner pages.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Consistent Header Frame</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> White Crisp Background</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Flyout Mega Menus</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Breadcrumb Co-existence</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Desktop 2 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_desktop_design" id="id_design2" value="design2" class="d-none"
                                               <?php echo ($headerConfig['inner_desktop_design'] ?? '') === 'design2' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_desktop', 'Inner Design 2: Brand Blue Top Navigation Bar')">
                                        <label for="id_design2" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search...</div>
                                                    <div class="wf-icons"><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-nav-strip" style="background:#0b53a1;border-color:#073b75;">
                                                    <span class="wf-nav-item text-white">Visiting Cards</span>
                                                    <span class="wf-nav-item text-white fw-bold">Apparel</span>
                                                    <span class="wf-nav-item text-white">Drinkware</span>
                                                    <span class="wf-nav-item text-white">Gifts</span>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Design 2: Brand Blue Top Navigation Bar</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-success">High Contrast</span>
                                                <span class="badge bg-light text-muted border">Brand Blue</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Corporate blue background category strip matching site accent colors with crisp white typography and dropdown menus.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Printmont Brand Blue</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> High Visual Hierarchy</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Contrasting Submenus</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Instant Recognition</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Desktop 3 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_desktop_design" id="id_design3" value="design3" class="d-none"
                                               <?php echo ($headerConfig['inner_desktop_design'] ?? '') === 'design3' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_desktop', 'Inner Design 3: Compact Breadcrumb & Pills')">
                                        <label for="id_design3" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Search...</div>
                                                    <div class="wf-icons"><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between p-1 bg-light rounded border">
                                                    <span style="font-size:8px;color:#64748b;"><i class="fas fa-home me-1"></i> / Catalog / Mugs</span>
                                                    <div class="d-flex gap-1">
                                                        <span class="wf-pill active">Ceramic</span>
                                                        <span class="wf-pill">Magic Mug</span>
                                                        <span class="wf-pill">Flasks</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Design 3: Compact Breadcrumb &amp; Pills</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-warning text-dark">Space Efficient</span>
                                                <span class="badge bg-light text-muted border">Integrated Breadcrumb</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Space-saving single-line breadcrumb combined with horizontal category chips for fast shopping context and catalog browsing.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Minimal Vertical Space</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Integrated Breadcrumbs</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Quick Switching</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Clean Catalog Context</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Desktop 4 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_desktop_design" id="id_design4" value="design4" class="d-none"
                                               <?php echo ($headerConfig['inner_desktop_design'] ?? '') === 'design4' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_desktop', 'Inner Design 4: Multi-Column Mega Catalog Bar')">
                                        <label for="id_design4" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            <div class="wireframe-canvas">
                                                <div class="wf-header-bar">
                                                    <span class="wf-logo">PrintMont</span>
                                                    <span class="badge bg-dark py-1 px-2" style="font-size:8px;"><i class="fas fa-bars me-1"></i> All Categories</span>
                                                    <div class="wf-search-box"><i class="fas fa-search me-1"></i> Full catalog search...</div>
                                                    <div class="wf-icons"><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="p-1 bg-white rounded border d-flex gap-2" style="font-size:7.5px;">
                                                    <span class="text-primary fw-bold">Flyout Drawer Available</span>
                                                    <span class="text-muted">Explore 500+ customized print subcategories</span>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Design 4: Multi-Column Mega Catalog Bar</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-info text-dark">Full Catalog</span>
                                                <span class="badge bg-light text-muted border">Deep Drawer</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Full catalog drawer with left vertical category list and right preview sub-category panels on hover or click.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Deep Hierarchy Browsing</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Featured Promo Panels</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Subcategory Badges</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Catalog Overview</div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- 2B: Inner Pages Mobile Layout Choices -->
                        <div class="config-box">
                            <div class="config-box-header">
                                <h4 class="config-box-title">
                                    <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                                        <i class="fas fa-mobile-alt"></i>
                                    </span>
                                    Inner Pages &bull; Mobile View Layout (इनर पेज मोबाइल)
                                </h4>
                                <span class="badge bg-success">Applies to Inner Pages on Mobile (&lt; 992px)</span>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4">

                                    <!-- Inner Mobile 1 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_mobile_design" id="im_design1" value="design1" class="d-none"
                                               <?php echo ($headerConfig['inner_mobile_design'] ?? 'design1') === 'design1' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_mobile', 'Inner Mobile 1: Back + Title + Search')">
                                        <label for="im_design1" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar" style="background:#ffffff;color:#0f172a;border-bottom:1px solid #e2e8f0;">
                                                    <i class="fas fa-arrow-left text-primary"></i>
                                                    <span class="fw-bold">Custom T-Shirts</span>
                                                    <div><i class="fas fa-search me-2"></i> <i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content text-center" style="font-size:7.5px;color:#94a3b8;">
                                                    Clean header with back navigation and page title
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Mobile 1: Back Button + Dynamic Title + Search</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-primary">Standard E-Commerce</span>
                                                <span class="badge bg-light text-muted border">Intuitive Back</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Left back arrow button, current dynamic page title (e.g. "Custom T-Shirts" or "Cart"), search trigger, and cart counter badge.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Intuitive Back Nav</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Dynamic Page Title</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Search &amp; Cart Icons</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Zero Nav Clutter</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Mobile 2 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_mobile_design" id="im_design2" value="design2" class="d-none"
                                               <?php echo ($headerConfig['inner_mobile_design'] ?? '') === 'design2' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_mobile', 'Inner Mobile 2: Subcategory Filter Pill Bar')">
                                        <label for="im_design2" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar" style="background:#ffffff;color:#0f172a;border-bottom:1px solid #e2e8f0;">
                                                    <i class="fas fa-arrow-left text-primary"></i>
                                                    <span class="fw-bold">Apparel</span>
                                                    <div><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content">
                                                    <div class="d-flex gap-1 overflow-hidden">
                                                        <span class="wf-pill active">All</span>
                                                        <span class="wf-pill">Polo T-Shirts</span>
                                                        <span class="wf-pill">Round Neck</span>
                                                        <span class="wf-pill">Hoodies</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Mobile 2: Subcategory Filter Pill Bar</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-success">Deep Browsing</span>
                                                <span class="badge bg-light text-muted border">Subcategory Pills</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Inner page header plus an extra horizontal scrolling strip of child subcategories for instantaneous in-category filtering.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> In-Category Subcategory Pills</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Instant Filter Trigger</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Active Pill State</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Back Arrow Header</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Mobile 3 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_mobile_design" id="im_design3" value="design3" class="d-none"
                                               <?php echo ($headerConfig['inner_mobile_design'] ?? '') === 'design3' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_mobile', 'Inner Mobile 3: App-Style Header with Direct Drawer')">
                                        <label for="im_design3" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar">
                                                    <i class="fas fa-bars"></i>
                                                    <span>Category Detail</span>
                                                    <div><i class="fas fa-shopping-bag"></i></div>
                                                </div>
                                                <div class="wf-mobile-content text-center" style="font-size:7.5px;color:#94a3b8;">
                                                    Flyout category drawer with filter badges
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Mobile 3: App-Style Header with Direct Drawer</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">App-Like Native</span>
                                                <span class="badge bg-light text-muted border">Slide Drawer</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Native mobile app feel with hamburger menu directly opening filtered category navigation, search bar, and cart.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Native App Aesthetic</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Direct Flyout Menu</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Cart &amp; Wishlist Badges</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Consistent Action Bar</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Inner Mobile 4 -->
                                    <div class="col-md-6">
                                        <input type="radio" name="inner_mobile_design" id="im_design4" value="design4" class="d-none"
                                               <?php echo ($headerConfig['inner_mobile_design'] ?? '') === 'design4' ? 'checked' : ''; ?>
                                               onchange="updateSelectionSummary('inner_mobile', 'Inner Mobile 4: Minimal Header with Quick Actions')">
                                        <label for="im_design4" class="design-choice-card">
                                            <span class="selection-check-indicator"><i class="fas fa-check"></i></span>
                                            
                                            <div class="wf-mobile-frame mb-3">
                                                <div class="wf-mobile-topbar" style="background:#ffffff;border-bottom:1px solid #e2e8f0;color:#0f172a;">
                                                    <i class="fas fa-arrow-left text-muted"></i>
                                                    <div class="wf-search-box mx-1"><i class="fas fa-search me-1"></i> Search...</div>
                                                    <i class="fas fa-filter text-primary"></i>
                                                </div>
                                                <div class="wf-mobile-content text-center" style="font-size:7.5px;color:#94a3b8;">
                                                    Compact header maximizes screen for products
                                                </div>
                                            </div>

                                            <h5 class="fw-bold text-dark mb-1 fs-6">Inner Mobile 4: Minimal Header with Quick Actions</h5>
                                            <div class="mb-2">
                                                <span class="badge bg-info text-dark">Ultra Compact</span>
                                                <span class="badge bg-light text-muted border">Search Focused</span>
                                            </div>
                                            <p class="text-muted small mb-3 flex-grow-1">
                                                Ultra-compact header with integrated search input and category filter chips to preserve maximum screen estate for products.
                                            </p>
                                            <div class="row g-1 small text-secondary border-top pt-2" style="font-size:11.5px;">
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Inline Search Input</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Maximum Product Screen Area</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Sticky Header Bar</div>
                                                <div class="col-6"><i class="fas fa-check text-success me-1"></i> Lightweight Footprint</div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- SECTION 3: GLOBAL ACCESSORIES & SETTINGS                -->
                    <!-- ======================================================== -->
                    <div id="sectionGlobal" style="display:none;">
                        <div class="config-box">
                            <div class="config-box-header">
                                <h4 class="config-box-title">
                                    <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                        <i class="fas fa-sliders-h"></i>
                                    </span>
                                    Global Header Features &amp; Accessories
                                </h4>
                                <span class="badge bg-secondary">Global Store Settings</span>
                            </div>

                            <div class="config-box-body">
                                <div class="row g-4">
                                    
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="sticky_header" id="sticky_header" value="1"
                                                       <?php echo !empty($headerConfig['sticky_header']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark" for="sticky_header">Sticky Navigation Bar</label>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                Scroll karne par header bar screen ke top par pin rehta hai, jisse customers kisi bhi samay search aur cart access kar sakte hain.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="announcement_bar" id="announcement_bar" value="1"
                                                       <?php echo !empty($headerConfig['announcement_bar']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark" for="announcement_bar">Top Announcement Bar</label>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                Main header ke upar promotional banner ribbon display karta hai (Offers, Free delivery, etc.).
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="p-3 border rounded-3 bg-white">
                                            <label class="form-label fw-bold text-dark">Top Announcement Ribbon Text:</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light text-primary"><i class="fas fa-bullhorn"></i></span>
                                                <input type="text" class="form-control" name="announcement_text"
                                                       value="<?php echo htmlspecialchars($headerConfig['announcement_text'] ?? ''); ?>"
                                                       placeholder="🎉 Free Delivery on Custom Orders over ₹999 | Corporate & Bulk Discounts Available!">
                                            </div>
                                            <small class="text-muted d-block mt-1">Dikhaya jata hai storefront ke sabse top promotional strip me.</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" name="show_wishlist" id="show_wishlist" value="1"
                                                       <?php echo !empty($headerConfig['show_wishlist']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark" for="show_wishlist">Show Wishlist Icon &amp; Counter</label>
                                            </div>
                                            <small class="text-muted">Header me customer wishlist heart icon display karein.</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" name="show_cart" id="show_cart" value="1"
                                                       <?php echo !empty($headerConfig['show_cart']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold text-dark" for="show_cart">Show Shopping Cart Icon &amp; Counter</label>
                                            </div>
                                            <small class="text-muted">Live shopping bag/cart count counter display karein.</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Bottom Save Action Bar -->
                    <div class="sticky-action-bar">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:42px;height:42px;border-radius:10px;background:#e0edff;display:flex;align-items:center;justify-content:center;color:#0b53a1;font-size:20px;">
                                <i class="fas fa-save"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark fs-6 d-block">
                                    Save Header Menu Layout Designs
                                </span>
                                <small class="text-muted" style="font-size:11.5px;">
                                    Updates <code>config/header_menu_design.json</code> &bull; Immediately applied across PrintMont Storefront.
                                </small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="header-menu-list.php" class="btn btn-outline-secondary px-3">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> Save Layout Designs
                            </button>
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
// Switch between Main Tabs (Home vs Inner vs Global)
function switchMainTab(tab) {
    document.getElementById('sectionHome').style.display = (tab === 'home') ? 'block' : 'none';
    document.getElementById('sectionInner').style.display = (tab === 'inner') ? 'block' : 'none';
    document.getElementById('sectionGlobal').style.display = (tab === 'global') ? 'block' : 'none';

    document.getElementById('tabHomeBtn').className = 'main-tab-btn ' + (tab === 'home' ? 'active' : '');
    document.getElementById('tabInnerBtn').className = 'main-tab-btn ' + (tab === 'inner' ? 'active' : '');
    document.getElementById('tabGlobalBtn').className = 'main-tab-btn ' + (tab === 'global' ? 'active' : '');
}

// Update Top Selection Summary Badges in Real-Time
function updateSelectionSummary(type, name) {
    if (type === 'home_desktop') {
        const el = document.getElementById('summaryHomeDesk');
        if (el) el.innerText = name;
    } else if (type === 'home_mobile') {
        const el = document.getElementById('summaryHomeMob');
        if (el) el.innerText = name;
    } else if (type === 'inner_desktop') {
        const el = document.getElementById('summaryInnerDesk');
        if (el) el.innerText = name;
    } else if (type === 'inner_mobile') {
        const el = document.getElementById('summaryInnerMob');
        if (el) el.innerText = name;
    }
}

// Category / Mega Menu Image Appearance Interactive Controls
let currentShape = '<?php echo htmlspecialchars($headerConfig['category_image_shape'] ?? 'circle'); ?>';
let currentSize = <?php echo (int)($headerConfig['category_image_size'] ?? 56); ?>;

function selectImageShape(shape) {
    currentShape = shape;
    document.querySelectorAll('.shape-picker-card').forEach(c => c.classList.remove('active'));
    const activeCard = document.getElementById('shapeCard_' + shape);
    if (activeCard) {
        activeCard.classList.add('active');
        const radio = activeCard.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
    updateLivePreview();
}

function setImageSize(size) {
    currentSize = parseInt(size, 10);
    const range = document.getElementById('rangeImageSize');
    const num = document.getElementById('numImageSize');
    if (range) range.value = currentSize;
    if (num) num.value = currentSize;
    updatePresetButtons(currentSize);
    updateLivePreview();
}

function syncImageSizeFromSlider(val) {
    currentSize = parseInt(val, 10);
    const num = document.getElementById('numImageSize');
    if (num) num.value = currentSize;
    updatePresetButtons(currentSize);
    updateLivePreview();
}

function syncImageSizeFromInput(val) {
    let numVal = parseInt(val, 10);
    if (isNaN(numVal)) return;
    if (numVal < 36) numVal = 36;
    if (numVal > 100) numVal = 100;
    currentSize = numVal;
    const range = document.getElementById('rangeImageSize');
    if (range) range.value = currentSize;
    updatePresetButtons(currentSize);
    updateLivePreview();
}

function updatePresetButtons(size) {
    document.querySelectorAll('.size-preset-btn').forEach(btn => {
        const btnVal = parseInt(btn.innerText, 10);
        if (btnVal === size) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function updateLivePreview() {
    let radius = '50%';
    let shapeLabel = 'Circle (50%)';
    if (currentShape === 'square') {
        radius = '0px';
        shapeLabel = 'Square (0%)';
    } else if (currentShape === 'rounded_square') {
        radius = '10px';
        shapeLabel = 'Rounded Square (10px)';
    }

    const previewWraps = [
        document.getElementById('previewWrap1'),
        document.getElementById('previewWrap2'),
        document.getElementById('previewWrap3'),
        document.getElementById('previewWrap4')
    ];

    previewWraps.forEach(wrap => {
        if (!wrap) return;
        wrap.style.width = currentSize + 'px';
        wrap.style.height = currentSize + 'px';
        wrap.style.borderRadius = radius;
    });

    const previewIcon = document.getElementById('previewIcon3');
    if (previewIcon) {
        previewIcon.style.fontSize = Math.max(14, Math.round(currentSize * 0.42)) + 'px';
    }

    const activeDetails = document.getElementById('previewActiveDetails');
    if (activeDetails) {
        activeDetails.innerHTML = `${shapeLabel} &bull; ${currentSize}px`;
    }
    const lblShape = document.getElementById('lblShapeSummary');
    if (lblShape) lblShape.innerText = currentShape.replace('_', ' ').toUpperCase();
    const lblSize = document.getElementById('lblSizeSummary');
    if (lblSize) lblSize.innerText = currentSize + 'px';
}

// Initialise live preview on page load
document.addEventListener('DOMContentLoaded', () => {
    updateLivePreview();
});
</script>
</body>
</html>
