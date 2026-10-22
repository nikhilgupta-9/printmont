<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/FooterSectionController.php';
require_once 'controllers/FooterLinkController.php';
require_once 'controllers/FooterCompanyInfoController.php';
require_once 'controllers/FooterSocialLinkController.php';
require_once 'controllers/FooterBottomLinkController.php';
require_once 'controllers/FooterPaymentMethodController.php';
require_once 'controllers/FooterStylingController.php';
require_once 'controllers/LogoController.php';

// Initialize controllers
$database = new Database();
$db = $database->getConnection();

$sectionController = new FooterSectionController();
$linkController = new FooterLinkController();
$companyController = new FooterCompanyInfoController();
$socialController = new FooterSocialLinkController();
$bottomLinkController = new FooterBottomLinkController($db);
$paymentController = new FooterPaymentMethodController($db);
$stylingController = new FooterStylingController($db);
$logoController = new LogoController($db);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'quick_save_company') {
        $data = [
            'company_name'    => trim($_POST['company_name'] ?? ''),
            'address'         => trim($_POST['address'] ?? ''),
            'customer_care'   => trim($_POST['customer_care'] ?? ''),
            'newsletter_text' => trim($_POST['newsletter_text'] ?? ''),
            'copyright_text'  => trim($_POST['copyright_text'] ?? ''),
            'status'          => 'active'
        ];
        $ok = $companyController->updateCompanyInfo($data);
        if ($ok) {
            $_SESSION['success_message'] = 'Company information & footer details updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update company information.';
        }
        header("Location: footer-management.php?tab=company");
        exit;
    }

    if ($action === 'save_footer_styling') {
        $stylingData = [
            'footer_bg_color'          => trim($_POST['footer_bg_color'] ?? '#0e1a35'),
            'footer_text_color'        => trim($_POST['footer_text_color'] ?? '#cbd5e1'),
            'footer_heading_color'     => trim($_POST['footer_heading_color'] ?? '#ffffff'),
            'footer_link_color'        => trim($_POST['footer_link_color'] ?? '#94a3b8'),
            'footer_link_hover_color'  => trim($_POST['footer_link_hover_color'] ?? '#f8991d'),
            'footer_bottom_bg_color'   => trim($_POST['footer_bottom_bg_color'] ?? '#091224'),
            'footer_border_color'      => trim($_POST['footer_border_color'] ?? 'rgba(255, 255, 255, 0.1)'),
            'footer_preset'            => trim($_POST['footer_preset'] ?? 'custom')
        ];
        $res = $stylingController->updateStyling($stylingData);
        if ($res['success']) {
            $_SESSION['success_message'] = 'Footer colors and theme styling saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save footer colors.';
        }
        header("Location: footer-management.php?tab=colors");
        exit;
    }

    if ($action === 'reset_default_styling') {
        $defaults = [
            'footer_bg_color'          => '#0e1a35',
            'footer_text_color'        => '#cbd5e1',
            'footer_heading_color'     => '#ffffff',
            'footer_link_color'        => '#94a3b8',
            'footer_link_hover_color'  => '#f8991d',
            'footer_bottom_bg_color'   => '#091224',
            'footer_border_color'      => 'rgba(255, 255, 255, 0.1)',
            'footer_preset'            => 'printmont_dark'
        ];
        $stylingController->updateStyling($defaults);
        $_SESSION['success_message'] = 'Default Printmont Brand theme colors restored!';
        header("Location: footer-management.php?tab=colors");
        exit;
    }
}

// Get all data
$sections = $sectionController->getAllSections() ?: [];
$links = $linkController->getAllLinks() ?: [];
$companyInfo = $companyController->getCompanyInfo() ?: [
    'company_name'   => 'Xordox International Pvt. Ltd',
    'address'        => "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India.",
    'customer_care'  => '+91-9818532463',
    'newsletter_text'=> 'Get updates on new products and offers. Coupons.',
    'copyright_text' => '© ' . date('Y') . ' printmont.com All Rights Reserved.'
];
$socialLinks = $socialController->getAllSocialLinks() ?: [];
$bottomLinks = $bottomLinkController->getAllBottomLinks() ?: [];
$paymentMethods = $paymentController->getAllPaymentMethods() ?: [];
$styling = $stylingController->getStyling();

// Favicon and Logo
$activeFavicon = $logoController->getFavicon();
$activeFaviconUrl = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';

$logoData = $logoController->getWebsiteLogo();
$logoUrl = '';
if (!empty($logoData['file_path']) && !empty($logoData['file_name'])) {
    $logoUrl = $logoData['file_path'] . $logoData['file_name'];
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$currentTab = $_GET['tab'] ?? 'preview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Footer Management &amp; Theme Colors | Printmont Admin</title>
    
    <!-- Dynamic Brand Favicon (Rule 1) -->
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($activeFaviconUrl); ?>?v=<?php echo time(); ?>" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-primary-dark: #083b73;
            --pm-accent: #f8991d;
            --pm-foot-bg: <?php echo htmlspecialchars($styling['footer_bg_color']); ?>;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f4f9;
        }

        .header-card {
            background: linear-gradient(135deg, #0B53A1 0%, #083b73 100%);
            border-radius: 16px;
            color: #fff;
            padding: 24px 28px;
            box-shadow: 0 10px 25px rgba(11, 83, 161, 0.18);
            margin-bottom: 24px;
        }

        .kpi-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 18px 20px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(11, 83, 161, 0.08);
            border-color: #bfdbfe;
        }

        .kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            background: #fff;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .custom-card .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 24px;
        }

        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            gap: 6px;
            padding: 0 20px;
            background: #fff;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 13.5px;
            padding: 12px 18px;
            border-radius: 8px 8px 0 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            transition: all 0.2s ease;
        }

        .nav-tabs-custom .nav-link:hover {
            color: var(--pm-primary);
            background: #f8fafc;
        }

        .nav-tabs-custom .nav-link.active {
            color: var(--pm-primary);
            border-bottom: 3px solid var(--pm-primary);
            background: #eff6ff;
        }

        .footer-preview-wrap {
            background-color: <?php echo htmlspecialchars($styling['footer_bg_color']); ?>;
            color: <?php echo htmlspecialchars($styling['footer_text_color']); ?>;
            padding: 36px 30px 20px;
            border-radius: 14px;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.3);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .footer-preview-wrap a {
            color: <?php echo htmlspecialchars($styling['footer_link_color']); ?>;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-preview-wrap a:hover {
            color: <?php echo htmlspecialchars($styling['footer_link_hover_color']); ?>;
        }

        .footer-sec-heading {
            color: <?php echo htmlspecialchars($styling['footer_heading_color']); ?>;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .mobile-preview-box {
            max-width: 400px;
            margin: 0 auto;
            border: 10px solid #1e293b;
            border-radius: 36px;
            overflow: hidden;
            background: <?php echo htmlspecialchars($styling['footer_bg_color']); ?>;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            transition: background 0.3s ease;
        }

        .mobile-preview-content {
            padding: 20px 16px;
            color: <?php echo htmlspecialchars($styling['footer_text_color']); ?>;
            font-size: 12px;
            text-align: center;
        }

        .btn-pm-primary {
            background: var(--pm-primary);
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 9px 18px;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-pm-primary:hover {
            background: var(--pm-primary-dark);
            color: #fff;
        }

        .pill-action-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .pill-action-btn:hover {
            background: #eff6ff;
            color: var(--pm-primary);
            border-color: #93c5fd;
        }

        .color-preset-card {
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .color-preset-card:hover {
            border-color: var(--pm-primary);
            transform: translateY(-2px);
        }

        .color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid rgba(0,0,0,0.1);
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
                    
                    <!-- Header Banner -->
                    <div class="header-card d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-3 bg-white bg-opacity-20 rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                <i class="fa-solid fa-palette fa-xl"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-white mb-1">Footer Management &amp; Theme Color Customizer</h3>
                                <p class="text-white-50 mb-0">Control column sections, navigation routes, bottom feature links, payment methods, and live theme palette colors.</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="http://localhost:5173/" target="_blank" class="btn btn-light fw-bold shadow-sm d-flex align-items-center gap-2">
                                <i class="fa-solid fa-arrow-up-right-from-square text-primary"></i> View Storefront Footer
                            </a>
                            <a href="api/footer-api.php" target="_blank" class="btn btn-outline-light fw-bold d-flex align-items-center gap-2">
                                <i class="fa-solid fa-code"></i> Test Footer API
                            </a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-check fa-lg me-2 text-success"></i>
                                <div><strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-exclamation fa-lg me-2 text-danger"></i>
                                <div><strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Sections</span>
                                    <div class="kpi-icon bg-primary-subtle text-primary">
                                        <i class="fa-solid fa-folder-tree"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo count($sections); ?></h3>
                                <small class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Active Columns</small>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Footer Links</span>
                                    <div class="kpi-icon bg-success-subtle text-success">
                                        <i class="fa-solid fa-link"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo count($links); ?></h3>
                                <small class="text-muted">Direct Nav Links</small>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Social Handles</span>
                                    <div class="kpi-icon bg-info-subtle text-info">
                                        <i class="fa-brands fa-instagram"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo count($socialLinks); ?></h3>
                                <small class="text-muted">Channels Linked</small>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="footer-bottom-links.php" class="text-decoration-none">
                                <div class="kpi-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-semibold">Bottom Links</span>
                                        <div class="kpi-icon bg-warning-subtle text-warning">
                                            <i class="fa-solid fa-bullhorn"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-0 text-dark"><?php echo count($bottomLinks); ?></h3>
                                    <small class="text-primary fw-semibold"><i class="fa-solid fa-pen-to-square me-1"></i> Manage Links &rarr;</small>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6">
                            <a href="footer-payment-methods.php" class="text-decoration-none">
                                <div class="kpi-card">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-semibold">Payment Badges</span>
                                        <div class="kpi-icon bg-danger-subtle text-danger">
                                            <i class="fa-regular fa-credit-card"></i>
                                        </div>
                                    </div>
                                    <h3 class="fw-bold mb-0 text-dark"><?php echo count($paymentMethods); ?></h3>
                                    <small class="text-primary fw-semibold"><i class="fa-solid fa-pen-to-square me-1"></i> Manage Badges &rarr;</small>
                                </div>
                            </a>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-semibold">Theme Palette</span>
                                    <div class="kpi-icon" style="background:<?php echo htmlspecialchars($styling['footer_bg_color']); ?>; color:#fff;">
                                        <i class="fa-solid fa-droplet"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold mb-0 text-truncate" style="color: <?php echo htmlspecialchars($styling['footer_bg_color']); ?>;">
                                    <?php echo htmlspecialchars($styling['footer_bg_color']); ?>
                                </h6>
                                <small class="text-muted">Live Brand Preset</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Sub-Module Nav Pills -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="footer-sections.php" class="pill-action-btn">
                            <i class="fa-solid fa-table-columns text-primary"></i> Manage Sections
                        </a>
                        <a href="footer-links.php" class="pill-action-btn">
                            <i class="fa-solid fa-bars-staggered text-success"></i> Manage Links
                        </a>
                        <a href="footer-bottom-links.php" class="pill-action-btn" style="background: #fff7ed; border-color: #fed7aa; color: #c2410c;">
                            <i class="fa-solid fa-store text-danger"></i> Bottom Feature Links (Edit)
                        </a>
                        <a href="footer-payment-methods.php" class="pill-action-btn" style="background: #f0fdf4; border-color: #bbf7d0; color: #15803d;">
                            <i class="fa-solid fa-credit-card text-success"></i> Payment Methods (Edit)
                        </a>
                        <a href="footer-company-info.php" class="pill-action-btn">
                            <i class="fa-solid fa-location-dot text-info"></i> Company Address &amp; Info
                        </a>
                        <a href="social-links.php" class="pill-action-btn">
                            <i class="fa-brands fa-facebook text-warning"></i> Social Handles
                        </a>
                    </div>

                    <!-- Main Navigation Tabs -->
                    <div class="custom-card">
                        <ul class="nav nav-tabs nav-tabs-custom" id="footerTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($currentTab === 'preview') ? 'active' : ''; ?>" id="preview-tab" data-bs-toggle="tab" data-bs-target="#tab-preview" type="button" role="tab">
                                    <i class="fa-solid fa-desktop me-1"></i> Live Visual Simulation
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($currentTab === 'colors') ? 'active' : ''; ?>" id="colors-tab" data-bs-toggle="tab" data-bs-target="#tab-colors" type="button" role="tab">
                                    <i class="fa-solid fa-palette me-1"></i> Footer Colors &amp; Theme Styling
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($currentTab === 'company') ? 'active' : ''; ?>" id="company-tab" data-bs-toggle="tab" data-bs-target="#tab-company" type="button" role="tab">
                                    <i class="fa-solid fa-building me-1"></i> Company Info &amp; Address
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($currentTab === 'sections') ? 'active' : ''; ?>" id="sections-tab" data-bs-toggle="tab" data-bs-target="#tab-sections" type="button" role="tab">
                                    <i class="fa-solid fa-layer-group me-1"></i> Sections &amp; Link Mapping
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($currentTab === 'social') ? 'active' : ''; ?>" id="social-tab" data-bs-toggle="tab" data-bs-target="#tab-social" type="button" role="tab">
                                    <i class="fa-solid fa-share-nodes me-1"></i> Social &amp; Feature Links
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-4" id="footerTabContent">
                            
                            <!-- TAB 1: Live Simulation Preview -->
                            <div class="tab-pane fade <?php echo ($currentTab === 'preview') ? 'show active' : ''; ?>" id="tab-preview" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-0">Live Footer Visual Simulation</h5>
                                        <small class="text-muted">Real-time dynamic preview reflecting chosen theme colors and navigation data.</small>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary active" id="btnDesktopView" onclick="switchViewMode('desktop')">
                                            <i class="fa-solid fa-laptop me-1"></i> Desktop
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMobileView" onclick="switchViewMode('mobile')">
                                            <i class="fa-solid fa-mobile-screen me-1"></i> Mobile
                                        </button>
                                    </div>
                                </div>

                                <!-- Desktop Preview Frame -->
                                <div id="desktopPreviewContainer">
                                    <div class="footer-preview-wrap" id="liveDeskFooterWrap">
                                        <div class="row g-4">
                                            <!-- Sections Columns -->
                                            <div class="col-lg-6">
                                                <div class="row g-3">
                                                    <?php foreach ($sections as $sec): 
                                                        $secLinks = array_filter($links, function($l) use ($sec) {
                                                            return $l['section_id'] == $sec['id'] && $l['status'] === 'active';
                                                        });
                                                    ?>
                                                        <div class="col-6 col-md-3">
                                                            <div class="footer-sec-heading live-heading-target"><?php echo htmlspecialchars($sec['title']); ?></div>
                                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-1" style="font-size: 13px;">
                                                                <?php foreach ($secLinks as $l): ?>
                                                                    <li>
                                                                        <a href="<?php echo htmlspecialchars($l['url']); ?>" target="_blank" class="live-link-target">
                                                                            <?php echo htmlspecialchars($l['title']); ?>
                                                                        </a>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- Right: Company Address, Social & Newsletter -->
                                            <div class="col-lg-6 border-start ps-lg-4" id="liveDeskColRight" style="border-color: <?php echo htmlspecialchars($styling['footer_border_color']); ?> !important;">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="footer-sec-heading live-heading-target">Registered Address</div>
                                                        <address style="font-size: 12.5px; line-height: 1.6;" class="live-text-target">
                                                            <strong><?php echo htmlspecialchars($companyInfo['company_name'] ?? 'Printmont'); ?></strong><br>
                                                            <?php echo nl2br(htmlspecialchars($companyInfo['address'] ?? '')); ?><br>
                                                            Customer Care: <span class="fw-bold live-heading-target"><?php echo htmlspecialchars($companyInfo['customer_care'] ?? ''); ?></span>
                                                        </address>

                                                        <div class="mt-3">
                                                            <span class="small opacity-75 d-block mb-1 live-text-target">Follow Us:</span>
                                                            <div class="d-flex gap-2">
                                                                <?php foreach ($socialLinks as $soc): 
                                                                    if ($soc['status'] === 'active'): ?>
                                                                        <a href="<?php echo htmlspecialchars($soc['url']); ?>" target="_blank" class="btn btn-sm btn-dark text-white rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                                            <i class="<?php echo htmlspecialchars($soc['icon']); ?>"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="footer-sec-heading live-heading-target">Newsletter</div>
                                                        <p class="small opacity-75 mb-2 live-text-target"><?php echo htmlspecialchars($companyInfo['newsletter_text'] ?? ''); ?></p>
                                                        <div class="input-group mb-2">
                                                            <input type="email" class="form-control form-control-sm" placeholder="Email Address" readonly>
                                                            <button class="btn btn-sm btn-secondary" type="button">Submit</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4 opacity-50" id="liveDeskHr" style="border-color: <?php echo htmlspecialchars($styling['footer_border_color']); ?> !important;">

                                        <!-- Bottom Strip -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2" style="font-size: 12.5px;">
                                            <div class="d-flex flex-wrap gap-3">
                                                <?php foreach ($bottomLinks as $bot): 
                                                    if ($bot['status'] === 'active'): ?>
                                                        <a href="<?php echo htmlspecialchars($bot['url']); ?>" class="d-flex align-items-center gap-1 live-link-target">
                                                            <i class="<?php echo htmlspecialchars($bot['icon']); ?> text-warning"></i>
                                                            <span><?php echo htmlspecialchars($bot['title']); ?></span>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="live-text-target">
                                                <?php echo htmlspecialchars($companyInfo['copyright_text'] ?? ''); ?>
                                            </div>
                                            <div class="d-flex gap-2 fs-5 opacity-75">
                                                <?php foreach ($paymentMethods as $pay): 
                                                    if ($pay['status'] === 'active'): ?>
                                                        <i class="<?php echo htmlspecialchars($pay['icon']); ?>" title="<?php echo htmlspecialchars($pay['name']); ?>"></i>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile View Simulation Frame -->
                                <div id="mobilePreviewContainer" style="display: none;">
                                    <div class="mobile-preview-box" id="liveMobFooterBox">
                                        <div class="mobile-preview-content">
                                            <!-- Trust Strip -->
                                            <div class="d-flex justify-content-around py-3 border-bottom mb-3" style="border-color: <?php echo htmlspecialchars($styling['footer_border_color']); ?> !important;">
                                                <div>
                                                    <i class="fa-solid fa-truck-fast fa-lg text-warning mb-1"></i>
                                                    <div style="font-size: 10px; font-weight: 700;" class="live-heading-target">Free Shipping</div>
                                                </div>
                                                <div>
                                                    <i class="fa-solid fa-shield-halved fa-lg text-success mb-1"></i>
                                                    <div style="font-size: 10px; font-weight: 700;" class="live-heading-target">100% Safe Pay</div>
                                                </div>
                                                <div>
                                                    <i class="fa-solid fa-ribbon fa-lg text-primary mb-1"></i>
                                                    <div style="font-size: 10px; font-weight: 700;" class="live-heading-target">Guaranteed</div>
                                                </div>
                                            </div>

                                            <!-- Pill Buttons -->
                                            <div class="d-flex justify-content-center gap-2 mb-3">
                                                <span class="badge bg-secondary p-2">CONTACT US</span>
                                                <span class="badge bg-secondary p-2">QUICK LINKS</span>
                                                <span class="badge bg-secondary p-2">HELP CENTER</span>
                                            </div>

                                            <!-- Brand Logo -->
                                            <div class="mb-3">
                                                <?php if (!empty($logoUrl)): ?>
                                                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Printmont" style="height: 26px; object-fit: contain;">
                                                <?php else: ?>
                                                    <span class="fw-bold fs-5 text-white">PRINTMONT</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Security Text -->
                                            <div class="text-success small mb-3">
                                                <i class="fa-solid fa-lock me-1"></i> 100% Safe and secure payments.
                                            </div>

                                            <!-- Social Row -->
                                            <div class="d-flex justify-content-center gap-2 mb-3">
                                                <?php foreach ($socialLinks as $soc): 
                                                    if ($soc['status'] === 'active'): ?>
                                                        <span class="btn btn-sm btn-dark text-white rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 11px;">
                                                            <i class="<?php echo htmlspecialchars($soc['icon']); ?>"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Copyright -->
                                            <div class="live-text-target" style="font-size: 10px;">
                                                <?php echo htmlspecialchars($companyInfo['copyright_text'] ?? ''); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: Footer Theme & Color Customizer -->
                            <div class="tab-pane fade <?php echo ($currentTab === 'colors') ? 'show active' : ''; ?>" id="tab-colors" role="tabpanel">
                                <form action="footer-management.php" method="POST" id="footerColorForm">
                                    <input type="hidden" name="action" value="save_footer_styling">
                                    <input type="hidden" name="footer_preset" id="inputFooterPreset" value="<?php echo htmlspecialchars($styling['footer_preset']); ?>">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="fw-bold mb-0">1-Click Theme Presets</h5>
                                            <small class="text-muted">Click any preset to instantly apply matched brand colors.</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('resetColorForm').submit();">
                                            <i class="fa-solid fa-rotate-left me-1"></i> Reset to Default Brand Theme
                                        </button>
                                    </div>

                                    <!-- Quick Presets Row -->
                                    <div class="row g-3 mb-4">
                                        <!-- Preset 1: Printmont Dark (Default) -->
                                        <div class="col-md-4 col-sm-6">
                                            <div class="color-preset-card border <?php echo ($styling['footer_preset'] === 'printmont_dark') ? 'border-primary shadow-sm' : ''; ?>" 
                                                 onclick="applyColorPreset('printmont_dark', '#0e1a35', '#cbd5e1', '#ffffff', '#94a3b8', '#f8991d', '#091224', 'rgba(255, 255, 255, 0.1)')">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark small">🌟 Printmont Dark (Default)</strong>
                                                    <span class="badge bg-primary">Recommended</span>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <span class="color-swatch" style="background:#0e1a35;" title="Background"></span>
                                                    <span class="color-swatch" style="background:#091224;" title="Bottom Strip"></span>
                                                    <span class="color-swatch" style="background:#ffffff;" title="Heading"></span>
                                                    <span class="color-swatch" style="background:#f8991d;" title="Accent Hover"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preset 2: Royal Blue -->
                                        <div class="col-md-4 col-sm-6">
                                            <div class="color-preset-card border <?php echo ($styling['footer_preset'] === 'royal_blue') ? 'border-primary shadow-sm' : ''; ?>" 
                                                 onclick="applyColorPreset('royal_blue', '#0B53A1', '#e0edff', '#ffffff', '#bfdbfe', '#f8991d', '#083b73', 'rgba(255, 255, 255, 0.15)')">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark small">🔷 Printmont Royal Blue</strong>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <span class="color-swatch" style="background:#0B53A1;"></span>
                                                    <span class="color-swatch" style="background:#083b73;"></span>
                                                    <span class="color-swatch" style="background:#ffffff;"></span>
                                                    <span class="color-swatch" style="background:#f8991d;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preset 3: Deep Slate -->
                                        <div class="col-md-4 col-sm-6">
                                            <div class="color-preset-card border <?php echo ($styling['footer_preset'] === 'deep_slate') ? 'border-primary shadow-sm' : ''; ?>" 
                                                 onclick="applyColorPreset('deep_slate', '#0f172a', '#94a3b8', '#f8fafc', '#64748b', '#38bdf8', '#020617', 'rgba(255, 255, 255, 0.08)')">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-dark small">🌌 Deep Navy Slate</strong>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <span class="color-swatch" style="background:#0f172a;"></span>
                                                    <span class="color-swatch" style="background:#020617;"></span>
                                                    <span class="color-swatch" style="background:#f8fafc;"></span>
                                                    <span class="color-swatch" style="background:#38bdf8;"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4 text-muted opacity-25">

                                    <h5 class="fw-bold mb-3">Custom Palette Colors</h5>
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Footer Background Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerBg" value="<?php echo htmlspecialchars($styling['footer_bg_color']); ?>" oninput="syncColorInput('pickerBg', 'textBg')">
                                                <input type="text" name="footer_bg_color" id="textBg" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_bg_color']); ?>" required oninput="syncColorPicker('textBg', 'pickerBg')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Heading Text Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerHeading" value="<?php echo htmlspecialchars($styling['footer_heading_color']); ?>" oninput="syncColorInput('pickerHeading', 'textHeading')">
                                                <input type="text" name="footer_heading_color" id="textHeading" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_heading_color']); ?>" required oninput="syncColorPicker('textHeading', 'pickerHeading')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Body Text Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerText" value="<?php echo htmlspecialchars($styling['footer_text_color']); ?>" oninput="syncColorInput('pickerText', 'textText')">
                                                <input type="text" name="footer_text_color" id="textText" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_text_color']); ?>" required oninput="syncColorPicker('textText', 'pickerText')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Links Normal Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerLink" value="<?php echo htmlspecialchars($styling['footer_link_color']); ?>" oninput="syncColorInput('pickerLink', 'textLink')">
                                                <input type="text" name="footer_link_color" id="textLink" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_link_color']); ?>" required oninput="syncColorPicker('textLink', 'pickerLink')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Links Hover / Accent Color</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerHover" value="<?php echo htmlspecialchars($styling['footer_link_hover_color']); ?>" oninput="syncColorInput('pickerHover', 'textHover')">
                                                <input type="text" name="footer_link_hover_color" id="textHover" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_link_hover_color']); ?>" required oninput="syncColorPicker('textHover', 'pickerHover')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Bottom Strip Background</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="pickerBottomBg" value="<?php echo htmlspecialchars($styling['footer_bottom_bg_color']); ?>" oninput="syncColorInput('pickerBottomBg', 'textBottomBg')">
                                                <input type="text" name="footer_bottom_bg_color" id="textBottomBg" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_bottom_bg_color']); ?>" required oninput="syncColorPicker('textBottomBg', 'pickerBottomBg')">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold small text-muted">Dividers / Border Color</label>
                                            <input type="text" name="footer_border_color" id="textBorder" class="form-control font-monospace" value="<?php echo htmlspecialchars($styling['footer_border_color']); ?>" required oninput="updateLiveColors()">
                                            <small class="text-muted">e.g. <code>rgba(255, 255, 255, 0.1)</code> or <code>#1e293b</code></small>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                        <span class="small text-muted"><i class="fa-solid fa-circle-nodes text-success me-1"></i> Changes will immediately sync to Frontend via <code>footer-api.php</code></span>
                                        <button type="submit" class="btn btn-pm-primary px-4 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Theme Colors
                                        </button>
                                    </div>
                                </form>

                                <form action="footer-management.php" method="POST" id="resetColorForm" class="d-none">
                                    <input type="hidden" name="action" value="reset_default_styling">
                                </form>
                            </div>

                            <!-- TAB 3: Company Info & Address Form -->
                            <div class="tab-pane fade <?php echo ($currentTab === 'company') ? 'show active' : ''; ?>" id="tab-company" role="tabpanel">
                                <form action="footer-management.php" method="POST">
                                    <input type="hidden" name="action" value="quick_save_company">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Company Legal Name</label>
                                            <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($companyInfo['company_name'] ?? ''); ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Customer Care Phone</label>
                                            <input type="text" name="customer_care" class="form-control" value="<?php echo htmlspecialchars($companyInfo['customer_care'] ?? ''); ?>" required>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-muted">Registered Headquarter Address</label>
                                            <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($companyInfo['address'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Newsletter Subscription Headline</label>
                                            <input type="text" name="newsletter_text" class="form-control" value="<?php echo htmlspecialchars($companyInfo['newsletter_text'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small text-muted">Copyright Notice Text</label>
                                            <input type="text" name="copyright_text" class="form-control" value="<?php echo htmlspecialchars($companyInfo['copyright_text'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn btn-pm-primary px-4 shadow-sm">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Company Details
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- TAB 4: Sections & Links Overview -->
                            <div class="tab-pane fade <?php echo ($currentTab === 'sections') ? 'show active' : ''; ?>" id="tab-sections" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="fw-bold mb-0">Footer Column Sections &amp; Link Mapping</h5>
                                        <small class="text-muted">Manage the 4 primary footer link columns and their target routes.</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="footer-sections.php" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-plus me-1"></i> Manage Sections
                                        </a>
                                        <a href="footer-links.php" class="btn btn-sm btn-success">
                                            <i class="fa-solid fa-plus me-1"></i> Add / Edit Links
                                        </a>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <?php foreach ($sections as $sec): 
                                        $secLinks = array_filter($links, function($l) use ($sec) {
                                            return $l['section_id'] == $sec['id'];
                                        });
                                    ?>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="card h-100 border shadow-none bg-light">
                                                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
                                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($sec['title']); ?></span>
                                                    <span class="badge <?php echo $sec['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($sec['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="card-body p-3">
                                                    <ul class="list-group list-group-flush small bg-transparent">
                                                        <?php if (empty($secLinks)): ?>
                                                            <li class="list-group-item bg-transparent text-muted py-2 px-0">No links added yet.</li>
                                                        <?php else: ?>
                                                            <?php foreach ($secLinks as $l): ?>
                                                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-2 px-0">
                                                                    <span><?php echo htmlspecialchars($l['title']); ?></span>
                                                                    <code class="text-muted small"><?php echo htmlspecialchars($l['url']); ?></code>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div class="card-footer bg-white border-top py-2 text-end">
                                                    <a href="footer-links.php?section_id=<?php echo (int)$sec['id']; ?>" class="btn btn-sm btn-link text-decoration-none p-0">
                                                        Edit Section Links &rarr;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- TAB 5: Social & Bottom Links -->
                            <div class="tab-pane fade <?php echo ($currentTab === 'social') ? 'show active' : ''; ?>" id="tab-social" role="tabpanel">
                                <div class="row g-4">
                                    <!-- Social Links Table -->
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0"><i class="fa-brands fa-share-nodes text-primary me-2"></i>Social Channels</h6>
                                            <a href="social-links.php" class="btn btn-sm btn-outline-primary">Manage Social Links</a>
                                        </div>
                                        <div class="table-responsive border rounded-3 bg-white">
                                            <table class="table table-hover align-middle mb-0 small">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Platform</th>
                                                        <th>URL</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($socialLinks as $soc): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="<?php echo htmlspecialchars($soc['icon']); ?> me-2 text-primary"></i>
                                                                <strong><?php echo ucfirst(htmlspecialchars($soc['platform'])); ?></strong>
                                                            </td>
                                                            <td class="text-truncate" style="max-width: 160px;">
                                                                <a href="<?php echo htmlspecialchars($soc['url']); ?>" target="_blank" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($soc['url']); ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $soc['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary'; ?>">
                                                                    <?php echo ucfirst($soc['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Bottom Strip Links Table -->
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-store text-danger me-2"></i>Bottom Feature Links</h6>
                                            <a href="footer-bottom-links.php" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Bottom Links
                                            </a>
                                        </div>
                                        <div class="table-responsive border rounded-3 bg-white">
                                            <table class="table table-hover align-middle mb-0 small">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Feature</th>
                                                        <th>Target URL</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($bottomLinks as $bot): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="<?php echo htmlspecialchars($bot['icon']); ?> me-2 text-danger"></i>
                                                                <strong><?php echo htmlspecialchars($bot['title']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($bot['url']); ?></code>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $bot['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary'; ?>">
                                                                    <?php echo ucfirst($bot['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
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
        function switchViewMode(mode) {
            const desk = document.getElementById('desktopPreviewContainer');
            const mob = document.getElementById('mobilePreviewContainer');
            const btnDesk = document.getElementById('btnDesktopView');
            const btnMob = document.getElementById('btnMobileView');

            if (mode === 'mobile') {
                desk.style.display = 'none';
                mob.style.display = 'block';
                btnMob.classList.add('active');
                btnDesk.classList.remove('active');
            } else {
                mob.style.display = 'none';
                desk.style.display = 'block';
                btnDesk.classList.add('active');
                btnMob.classList.remove('active');
            }
        }

        function syncColorInput(pickerId, textId) {
            const val = document.getElementById(pickerId).value;
            document.getElementById(textId).value = val;
            updateLiveColors();
        }

        function syncColorPicker(textId, pickerId) {
            const val = document.getElementById(textId).value;
            if (/^#([0-9A-F]{3}){1,2}$/i.test(val)) {
                document.getElementById(pickerId).value = val;
            }
            updateLiveColors();
        }

        function applyColorPreset(presetKey, bg, text, heading, link, hover, bottomBg, border) {
            document.getElementById('inputFooterPreset').value = presetKey;
            
            document.getElementById('textBg').value = bg;
            document.getElementById('pickerBg').value = bg;

            document.getElementById('textText').value = text;
            document.getElementById('pickerText').value = text;

            document.getElementById('textHeading').value = heading;
            document.getElementById('pickerHeading').value = heading;

            document.getElementById('textLink').value = link;
            document.getElementById('pickerLink').value = link;

            document.getElementById('textHover').value = hover;
            document.getElementById('pickerHover').value = hover;

            document.getElementById('textBottomBg').value = bottomBg;
            document.getElementById('pickerBottomBg').value = bottomBg;

            document.getElementById('textBorder').value = border;

            updateLiveColors();
        }

        function updateLiveColors() {
            const bg = document.getElementById('textBg').value;
            const text = document.getElementById('textText').value;
            const heading = document.getElementById('textHeading').value;
            const link = document.getElementById('textLink').value;
            const border = document.getElementById('textBorder').value;

            // Update live desktop preview
            const deskWrap = document.getElementById('liveDeskFooterWrap');
            if (deskWrap) {
                deskWrap.style.backgroundColor = bg;
                deskWrap.style.color = text;
            }

            const mobBox = document.getElementById('liveMobFooterBox');
            if (mobBox) {
                mobBox.style.backgroundColor = bg;
            }

            document.querySelectorAll('.live-heading-target').forEach(el => el.style.color = heading);
            document.querySelectorAll('.live-text-target').forEach(el => el.style.color = text);
            document.querySelectorAll('.live-link-target').forEach(el => el.style.color = link);
            
            const deskColRight = document.getElementById('liveDeskColRight');
            if (deskColRight) deskColRight.style.borderColor = border;

            const deskHr = document.getElementById('liveDeskHr');
            if (deskHr) deskHr.style.borderColor = border;
        }
    </script>
</body>
</html>