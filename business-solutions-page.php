<?php
/**
 * Business Solutions Landing Page — Visual Management Center
 * 
 * Complies with Rule 1 (Unified Favicon), Rule 3 (SEO), Rule 4 & 5 (Desktop & Mobile separation), and Rule 6 (Dynamic Page Builder).
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$pageSlug = 'business-solutions';

// Helper function to handle image uploads
function handleUploadedImageFile($fileKey, $targetSubdir = 'landing') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fileKey];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    if (!in_array($ext, $allowed)) {
        return null;
    }
    $filename = uniqid('bs_', true) . '.' . $ext;
    $targetDir = __DIR__ . '/uploads/' . $targetSubdir . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $destination = $targetDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $targetSubdir . '/' . $filename;
    }
    return null;
}

// Fetch or create landing page record
$pageStmt = $db->prepare("SELECT * FROM landing_pages WHERE slug = ? LIMIT 1");
$pageStmt->bind_param('s', $pageSlug);
$pageStmt->execute();
$page = $pageStmt->get_result()->fetch_assoc();
$pageStmt->close();

if (!$page) {
    $db->query("INSERT INTO landing_pages (page_name, slug, meta_title, meta_description, meta_keywords, status) 
                VALUES ('Business Solutions', 'business-solutions', 'Business Solutions & Enterprise Printing - Printmont', 'Custom corporate printing, branded merchandise, employee kits, and wholesale packaging.', 'business solutions, corporate printing', 'active')");
    $pageId = $db->insert_id;
    $page = $db->query("SELECT * FROM landing_pages WHERE id = $pageId")->fetch_assoc();
} else {
    $pageId = (int)$page['id'];
}

$message = '';
$error = '';

// Handle Post Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    // 1. Update SEO Settings
    if ($action === 'update_seo') {
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc = trim($_POST['meta_description'] ?? '');
        $metaKw = trim($_POST['meta_keywords'] ?? '');
        $status = $_POST['status'] ?? 'active';

        $uStmt = $db->prepare("UPDATE landing_pages SET meta_title = ?, meta_description = ?, meta_keywords = ?, status = ? WHERE id = ?");
        $uStmt->bind_param('ssssi', $metaTitle, $metaDesc, $metaKw, $status, $pageId);
        if ($uStmt->execute()) {
            $message = 'SEO settings updated successfully!';
            $page['meta_title'] = $metaTitle;
            $page['meta_description'] = $metaDesc;
            $page['meta_keywords'] = $metaKw;
            $page['status'] = $status;
        } else {
            $error = 'Failed to update SEO settings.';
        }
        $uStmt->close();
    }

    // 2. Update Section Order & Visibility
    if ($action === 'update_sections') {
        $sectionIds = $_POST['section_ids'] ?? [];
        $orders = $_POST['display_orders'] ?? [];
        $titles = $_POST['section_titles'] ?? [];
        $subtitles = $_POST['section_subtitles'] ?? [];
        $devices = $_POST['device_types'] ?? [];
        $activeList = $_POST['is_active'] ?? [];

        foreach ($sectionIds as $secId) {
            $secId = (int)$secId;
            $order = (int)($orders[$secId] ?? 0);
            $title = trim($titles[$secId] ?? '');
            $sub = trim($subtitles[$secId] ?? '');
            $device = in_array($devices[$secId] ?? '', ['mobile', 'desktop', 'both']) ? $devices[$secId] : 'both';
            $isActive = isset($activeList[$secId]) ? 1 : 0;

            $u = $db->prepare("UPDATE landing_page_sections SET display_order = ?, section_title = ?, section_subtitle = ?, device_type = ?, is_active = ? WHERE id = ? AND page_id = ?");
            $u->bind_param('isssiii', $order, $title, $sub, $device, $isActive, $secId, $pageId);
            $u->execute();
            $u->close();
        }
        $message = 'Section layout, display order, and visibility updated successfully!';
    }

    // 3. Save Banner (Add or Edit)
    if ($action === 'save_banner') {
        $sectionKey = trim($_POST['section_key'] ?? 'hero_banner');
        $bannerIndex = isset($_POST['banner_index']) && $_POST['banner_index'] !== '' ? (int)$_POST['banner_index'] : -1;
        
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '#lead-form');
        
        // Handle desktop image
        $uploadedDesktop = handleUploadedImageFile('image_desktop_file');
        $imageDesktop = $uploadedDesktop ?: trim($_POST['image_desktop_url'] ?? '');
        
        // Handle mobile image
        $uploadedMobile = handleUploadedImageFile('image_mobile_file');
        $imageMobile = $uploadedMobile ?: trim($_POST['image_mobile_url'] ?? '');
        
        if (empty($imageMobile) && !empty($imageDesktop)) {
            $imageMobile = $imageDesktop;
        }
        if (empty($imageDesktop) && !empty($imageMobile)) {
            $imageDesktop = $imageMobile;
        }

        if (empty($imageDesktop)) {
            $error = 'Please provide a desktop banner image (upload file or enter URL).';
        } else {
            // Fetch existing section config
            $sRes = $db->query("SELECT id, extra_config_json FROM landing_page_sections WHERE page_id = $pageId AND section_key = '$sectionKey' LIMIT 1");
            if ($sRow = $sRes->fetch_assoc()) {
                $secId = (int)$sRow['id'];
                $config = json_decode($sRow['extra_config_json'], true) ?: [];
                $banners = $config['banners'] ?? [];

                $bannerData = [
                    'image_desktop' => $imageDesktop,
                    'image_mobile' => $imageMobile,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'link_url' => $linkUrl
                ];

                if ($bannerIndex >= 0 && isset($banners[$bannerIndex])) {
                    // Update existing banner
                    $banners[$bannerIndex] = $bannerData;
                    $message = 'Banner updated successfully!';
                } else {
                    // Add new banner
                    $banners[] = $bannerData;
                    $message = 'New banner added successfully!';
                }

                $config['banners'] = array_values($banners);
                $newJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $u = $db->prepare("UPDATE landing_page_sections SET extra_config_json = ? WHERE id = ?");
                $u->bind_param('si', $newJson, $secId);
                $u->execute();
                $u->close();
            } else {
                $error = 'Section not found.';
            }
        }
    }

    // 4. Delete Banner
    if ($action === 'delete_banner') {
        $sectionKey = trim($_POST['section_key'] ?? '');
        $bannerIndex = (int)($_POST['banner_index'] ?? -1);

        if ($bannerIndex >= 0 && !empty($sectionKey)) {
            $sRes = $db->query("SELECT id, extra_config_json FROM landing_page_sections WHERE page_id = $pageId AND section_key = '$sectionKey' LIMIT 1");
            if ($sRow = $sRes->fetch_assoc()) {
                $secId = (int)$sRow['id'];
                $config = json_decode($sRow['extra_config_json'], true) ?: [];
                $banners = $config['banners'] ?? [];

                if (isset($banners[$bannerIndex])) {
                    array_splice($banners, $bannerIndex, 1);
                    $config['banners'] = array_values($banners);
                    $newJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    $u = $db->prepare("UPDATE landing_page_sections SET extra_config_json = ? WHERE id = ?");
                    $u->bind_param('si', $newJson, $secId);
                    $u->execute();
                    $u->close();
                    $message = 'Banner removed successfully!';
                }
            }
        }
    }

    // 5. Save Section Item (Industry Cards, Corporate Depts, Portfolio, Why Printmont, Stats, Reviews)
    if ($action === 'save_item') {
        $sectionKey = trim($_POST['section_key'] ?? '');
        $itemIndex = isset($_POST['item_index']) && $_POST['item_index'] !== '' ? (int)$_POST['item_index'] : -1;
        $arrayKey = trim($_POST['array_key'] ?? 'items');

        $sRes = $db->query("SELECT id, extra_config_json FROM landing_page_sections WHERE page_id = $pageId AND section_key = '$sectionKey' LIMIT 1");
        if ($sRow = $sRes->fetch_assoc()) {
            $secId = (int)$sRow['id'];
            $config = json_decode($sRow['extra_config_json'], true) ?: [];
            $items = $config[$arrayKey] ?? [];

            $itemData = [];

            // Handle Image Upload if present
            $uploadedImg = handleUploadedImageFile('item_image_file');
            $imgUrl = $uploadedImg ?: trim($_POST['item_image_url'] ?? '');

            if ($sectionKey === 'industry_solutions') {
                $itemData = [
                    'tag' => trim($_POST['tag'] ?? ''),
                    'headline' => trim($_POST['headline'] ?? ''),
                    'desc' => trim($_POST['desc'] ?? ''),
                    'image' => $imgUrl ?: (isset($items[$itemIndex]['image']) ? $items[$itemIndex]['image'] : '')
                ];
            } elseif ($sectionKey === 'corporate_departments') {
                $itemData = [
                    'tag' => trim($_POST['tag'] ?? ''),
                    'headline' => trim($_POST['headline'] ?? ''),
                    'desc' => trim($_POST['desc'] ?? '')
                ];
            } elseif ($sectionKey === 'portfolio_showcase') {
                $itemData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'client' => trim($_POST['client'] ?? ''),
                    'image' => $imgUrl ?: (isset($items[$itemIndex]['image']) ? $items[$itemIndex]['image'] : '')
                ];
            } elseif ($sectionKey === 'why_printmont') {
                $itemData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'desc' => trim($_POST['desc'] ?? ''),
                    'icon' => trim($_POST['icon'] ?? 'FaCheckCircle')
                ];
            } elseif ($sectionKey === 'stats_counters') {
                $itemData = [
                    'value' => trim($_POST['value'] ?? ''),
                    'label' => trim($_POST['label'] ?? '')
                ];
            } elseif ($sectionKey === 'customer_reviews') {
                $itemData = [
                    'name' => trim($_POST['name'] ?? ''),
                    'company' => trim($_POST['company'] ?? ''),
                    'rating' => (int)($_POST['rating'] ?? 5),
                    'review' => trim($_POST['review'] ?? '')
                ];
            }

            if ($itemIndex >= 0 && isset($items[$itemIndex])) {
                $items[$itemIndex] = $itemData;
                $message = 'Item updated successfully!';
            } else {
                $items[] = $itemData;
                $message = 'New item added successfully!';
            }

            $config[$arrayKey] = array_values($items);
            $newJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $u = $db->prepare("UPDATE landing_page_sections SET extra_config_json = ? WHERE id = ?");
            $u->bind_param('si', $newJson, $secId);
            $u->execute();
            $u->close();
        }
    }

    // 6. Delete Section Item
    if ($action === 'delete_item') {
        $sectionKey = trim($_POST['section_key'] ?? '');
        $itemIndex = (int)($_POST['item_index'] ?? -1);
        $arrayKey = trim($_POST['array_key'] ?? 'items');

        if ($itemIndex >= 0 && !empty($sectionKey)) {
            $sRes = $db->query("SELECT id, extra_config_json FROM landing_page_sections WHERE page_id = $pageId AND section_key = '$sectionKey' LIMIT 1");
            if ($sRow = $sRes->fetch_assoc()) {
                $secId = (int)$sRow['id'];
                $config = json_decode($sRow['extra_config_json'], true) ?: [];
                $items = $config[$arrayKey] ?? [];

                if (isset($items[$itemIndex])) {
                    array_splice($items, $itemIndex, 1);
                    $config[$arrayKey] = array_values($items);
                    $newJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    $u = $db->prepare("UPDATE landing_page_sections SET extra_config_json = ? WHERE id = ?");
                    $u->bind_param('si', $newJson, $secId);
                    $u->execute();
                    $u->close();
                    $message = 'Item removed successfully!';
                }
            }
        }
    }

    // 7. FAQ Actions (Add / Edit / Delete)
    if ($action === 'add_faq') {
        $q = trim($_POST['question'] ?? '');
        $a = trim($_POST['answer'] ?? '');
        $order = (int)($_POST['display_order'] ?? 1);
        if (!empty($q) && !empty($a)) {
            $fStmt = $db->prepare("INSERT INTO landing_page_faqs (page_id, question, answer, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
            $fStmt->bind_param('issi', $pageId, $q, $a, $order);
            $fStmt->execute();
            $fStmt->close();
            $message = 'New FAQ item added successfully!';
        } else {
            $error = 'Question and Answer are required.';
        }
    }

    if ($action === 'delete_faq') {
        $faqId = (int)($_POST['faq_id'] ?? 0);
        if ($faqId > 0) {
            $db->query("DELETE FROM landing_page_faqs WHERE id = $faqId AND page_id = $pageId");
            $message = 'FAQ item deleted.';
        }
    }
}

// Fetch all sections
$sectionsRes = $db->query("SELECT * FROM landing_page_sections WHERE page_id = $pageId ORDER BY display_order ASC, id ASC");
$allSections = [];
$sectionsMap = [];
while ($r = $sectionsRes->fetch_assoc()) {
    $r['config'] = json_decode($r['extra_config_json'], true) ?: [];
    $allSections[] = $r;
    $sectionsMap[$r['section_key']] = $r;
}

// Fetch FAQs
$faqsRes = $db->query("SELECT * FROM landing_page_faqs WHERE page_id = $pageId ORDER BY display_order ASC, id ASC");
$allFaqs = [];
while ($f = $faqsRes->fetch_assoc()) {
    $allFaqs[] = $f;
}

// Count leads
$leadCountRes = $db->query("SELECT COUNT(*) as cnt FROM lead_inquiries WHERE source_page = 'business-solutions'");
$totalLeads = $leadCountRes ? (int)$leadCountRes->fetch_assoc()['cnt'] : 0;

// Helper to resolve image preview
function resolveAdminImg($path) {
    if (empty($path)) return 'img/photos/unsplash-1.jpg';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, '/') === 0) {
        $clean = ltrim($path, '/');
        if (file_exists(__DIR__ . '/' . $clean)) return $clean;
        if (file_exists(__DIR__ . '/uploads/landing/' . basename($path))) return 'uploads/landing/' . basename($path);
        return '../frontend/public' . $path;
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Business Solutions Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .bs-hero-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #0b53a1 100%);
            color: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .bs-tab-btn {
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            transition: all 0.2s;
            font-size: 13.5px;
        }
        .bs-tab-btn.active {
            background: #0b53a1;
            color: #ffffff;
            border-color: #0b53a1;
            box-shadow: 0 4px 12px rgba(11, 83, 161, 0.25);
        }
        .bs-banner-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .bs-banner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        .bs-banner-img-preview {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background-color: #f1f5f9;
        }
        .bs-order-input {
            width: 70px;
            text-align: center;
            font-weight: 700;
        }
        .bs-badge-device {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .bs-badge-both { background: #e0f2fe; color: #0369a1; }
        .bs-badge-mobile { background: #fef3c7; color: #92400e; }
        .bs-badge-desktop { background: #dcfce7; color: #15803d; }
    </style>
</head>

<body>
<div class="wrapper">
    <?php include_once 'includes/side-navbar.php'; ?>

    <div class="main">
        <?php include_once 'includes/top-navbar.php'; ?>

        <main class="content">
            <div class="container-fluid p-0">

                <!-- Top Header Banner -->
                <div class="bs-hero-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-warning text-dark px-2.5 py-1 fw-bold">B2B Enterprise Hub</span>
                            <span class="badge bg-light text-dark px-2 py-1">Rule 3, 4, 5 &amp; 6 Compliant</span>
                        </div>
                        <h1 class="h2 text-white fw-bold mb-1">Business Solutions Landing Page</h1>
                        <p class="text-white-50 mb-0">Dynamic layout builder, separate mobile/desktop controls, visual banner managers &amp; lead capture.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="lead-inquiries.php" class="btn btn-warning fw-bold px-3 py-2 shadow-sm">
                            <i class="align-middle me-1" data-feather="inbox"></i> View Leads (<?php echo $totalLeads; ?>)
                        </a>
                        <a href="http://localhost:5173/business-solutions" target="_blank" rel="noopener" class="btn btn-light fw-bold px-3 py-2 shadow-sm">
                            <i class="align-middle me-1" data-feather="external-link"></i> View Live Page
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Navigation Tabs -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button class="bs-tab-btn active" onclick="showTab('tab-banners')">
                        <i class="align-middle me-1" data-feather="image"></i> Banner &amp; Slider Manager
                    </button>
                    <button class="bs-tab-btn" onclick="showTab('tab-sections')">
                        <i class="align-middle me-1" data-feather="layers"></i> Page Sections &amp; Ordering
                    </button>
                    <button class="bs-tab-btn" onclick="showTab('tab-content')">
                        <i class="align-middle me-1" data-feather="grid"></i> Industry &amp; Department Solutions
                    </button>
                    <button class="bs-tab-btn" onclick="showTab('tab-portfolio')">
                        <i class="align-middle me-1" data-feather="award"></i> Showcase, Values &amp; Reviews
                    </button>
                    <button class="bs-tab-btn" onclick="showTab('tab-faqs')">
                        <i class="align-middle me-1" data-feather="help-circle"></i> FAQ Accordions (<?php echo count($allFaqs); ?>)
                    </button>
                    <button class="bs-tab-btn" onclick="showTab('tab-seo')">
                        <i class="align-middle me-1" data-feather="globe"></i> SEO &amp; Meta Tags
                    </button>
                </div>

                <!-- ====================================================================
                     TAB 1: BANNER & SLIDER MANAGER (100% Visual Form, NO JSON)
                     ==================================================================== -->
                <div id="tab-banners" class="tab-pane-content">
                    
                    <!-- 1. Hero Banner Slider -->
                    <?php 
                        $heroBanners = $sectionsMap['hero_banner']['config']['banners'] ?? [];
                        $partnerBanners = $sectionsMap['partner_banner']['config']['banners'] ?? [];
                    ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-primary" data-feather="sliders"></i> Top Hero Banner Slider
                                </h5>
                                <small class="text-muted">High-resolution banners shown at the top of the Business Solutions page.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="openAddBannerModal('hero_banner', 'Top Hero Banner Slider')">
                                <i class="align-middle me-1" data-feather="plus"></i> Add New Hero Banner
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($heroBanners)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="align-middle mb-2 text-secondary" data-feather="image" style="width: 36px; height: 36px;"></i>
                                    <p class="mb-0">No banners in Hero Slider yet. Click "Add New Hero Banner" to add one.</p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($heroBanners as $idx => $b): ?>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="bs-banner-card">
                                                <img src="<?php echo htmlspecialchars(resolveAdminImg($b['image_desktop'] ?? '')); ?>" 
                                                     alt="Desktop Banner" class="bs-banner-img-preview"
                                                     onerror="this.src='img/photos/unsplash-1.jpg';">
                                                <div class="p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-primary">Slide #<?php echo $idx + 1; ?></span>
                                                        <small class="text-muted">Target: <?php echo htmlspecialchars($b['link_url'] ?? '#'); ?></small>
                                                    </div>
                                                    <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($b['title'] ?: 'Untitled Banner'); ?></h6>
                                                    <p class="text-muted small mb-3 text-truncate"><?php echo htmlspecialchars($b['subtitle'] ?: 'No subtitle provided'); ?></p>

                                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick='openEditBannerModal("hero_banner", <?php echo $idx; ?>, <?php echo json_encode($b); ?>)'>
                                                            <i class="align-middle me-1" data-feather="edit-2"></i> Edit
                                                        </button>
                                                        <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this banner from Hero Slider?');">
                                                            <input type="hidden" name="form_action" value="delete_banner">
                                                            <input type="hidden" name="section_key" value="hero_banner">
                                                            <input type="hidden" name="banner_index" value="<?php echo $idx; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="align-middle me-1" data-feather="trash-2"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Partner Banner Slider -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-success" data-feather="image"></i> Partner Banner Slider ("Printmont Partner for Small &amp; Large Businesses")
                                </h5>
                                <small class="text-muted">Promotional banner slider displayed before the stats and reviews section.</small>
                            </div>
                            <button type="button" class="btn btn-success btn-sm fw-bold px-3" onclick="openAddBannerModal('partner_banner', 'Partner Banner Slider')">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Partner Banner
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($partnerBanners)): ?>
                                <div class="text-center py-4 text-muted">
                                    <p class="mb-0">No banners in Partner Slider yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($partnerBanners as $idx => $b): ?>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="bs-banner-card">
                                                <img src="<?php echo htmlspecialchars(resolveAdminImg($b['image_desktop'] ?? '')); ?>" 
                                                     alt="Partner Banner" class="bs-banner-img-preview"
                                                     onerror="this.src='img/photos/unsplash-1.jpg';">
                                                <div class="p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-success">Slide #<?php echo $idx + 1; ?></span>
                                                        <small class="text-muted">Target: <?php echo htmlspecialchars($b['link_url'] ?? '#'); ?></small>
                                                    </div>
                                                    <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($b['title'] ?: 'Untitled Banner'); ?></h6>
                                                    <p class="text-muted small mb-3 text-truncate"><?php echo htmlspecialchars($b['subtitle'] ?: 'No subtitle provided'); ?></p>

                                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick='openEditBannerModal("partner_banner", <?php echo $idx; ?>, <?php echo json_encode($b); ?>)'>
                                                            <i class="align-middle me-1" data-feather="edit-2"></i> Edit
                                                        </button>
                                                        <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this banner?');">
                                                            <input type="hidden" name="form_action" value="delete_banner">
                                                            <input type="hidden" name="section_key" value="partner_banner">
                                                            <input type="hidden" name="banner_index" value="<?php echo $idx; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="align-middle me-1" data-feather="trash-2"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- ====================================================================
                     TAB 2: SECTIONS & ORDER (Rule 4, 5, 6)
                     ==================================================================== -->
                <div id="tab-sections" class="tab-pane-content" style="display: none;">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold">Dynamic Section Layout &amp; Ordering</h5>
                                <small class="text-muted">Re-order positions using the display order numbers or toggle visibility on/off.</small>
                            </div>
                            <span class="badge bg-primary px-3 py-1.5"><?php echo count($allSections); ?> Total Sections</span>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="business-solutions-page.php">
                                <input type="hidden" name="form_action" value="update_sections">

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 90px;" class="text-center">Order</th>
                                                <th>Section Key &amp; Name</th>
                                                <th>Title on Website</th>
                                                <th style="width: 130px;">Device</th>
                                                <th style="width: 110px;" class="text-center">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allSections as $sec): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="hidden" name="section_ids[]" value="<?php echo $sec['id']; ?>">
                                                        <input type="number" class="form-control form-control-sm bs-order-input mx-auto" 
                                                               name="display_orders[<?php echo $sec['id']; ?>]" 
                                                               value="<?php echo (int)$sec['display_order']; ?>" min="1" max="99">
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($sec['section_key']); ?></span>
                                                        <small class="text-muted"><?php echo htmlspecialchars($sec['section_subtitle'] ?: 'Dynamic section'); ?></small>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="section_titles[<?php echo $sec['id']; ?>]" 
                                                               value="<?php echo htmlspecialchars($sec['section_title'] ?? ''); ?>">
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" name="device_types[<?php echo $sec['id']; ?>]">
                                                            <option value="both" <?php echo ($sec['device_type'] === 'both') ? 'selected' : ''; ?>>Both</option>
                                                            <option value="mobile" <?php echo ($sec['device_type'] === 'mobile') ? 'selected' : ''; ?>>Mobile Only</option>
                                                            <option value="desktop" <?php echo ($sec['device_type'] === 'desktop') ? 'selected' : ''; ?>>Desktop Only</option>
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="is_active[<?php echo $sec['id']; ?>]" value="1" 
                                                                   <?php echo ($sec['is_active'] == 1) ? 'checked' : ''; ?>>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                                        <i class="align-middle me-1" data-feather="save"></i> Save Section Orders &amp; Visibility
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ====================================================================
                     TAB 3: INDUSTRY & DEPARTMENT SOLUTIONS
                     ==================================================================== -->
                <div id="tab-content" class="tab-pane-content" style="display: none;">
                    
                    <!-- 1. Industry Solutions Cards -->
                    <?php 
                        $industryItems = $sectionsMap['industry_solutions']['config']['items'] ?? [];
                        $corpItems = $sectionsMap['corporate_departments']['config']['items'] ?? [];
                    ?>
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-primary" data-feather="briefcase"></i> Printmont for Business (Industry Solution Cards)
                                </h5>
                                <small class="text-muted">Cards for MNCs, Startups, Restaurants, D2C Brands, Tech Firms, Event Planners, Retail Stores, Graphic Designers.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="openAddIndustryModal()">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Industry Card
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <?php foreach ($industryItems as $idx => $item): ?>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <div class="card h-100 border shadow-none">
                                            <img src="<?php echo htmlspecialchars(resolveAdminImg($item['image'] ?? '')); ?>" 
                                                 class="card-img-top" style="height: 120px; object-fit: cover;"
                                                 onerror="this.src='img/photos/unsplash-1.jpg';">
                                            <div class="card-body p-3 d-flex flex-column">
                                                <span class="badge bg-secondary mb-1 align-self-start"><?php echo htmlspecialchars($item['tag'] ?? 'Industry'); ?></span>
                                                <h6 class="fw-bold text-dark mb-1 small"><?php echo htmlspecialchars($item['headline'] ?? ''); ?></h6>
                                                <p class="text-muted small mb-3 flex-grow-1" style="font-size: 11.5px; line-height: 1.4;">
                                                    <?php echo htmlspecialchars($item['desc'] ?? ''); ?>
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick='openEditIndustryModal(<?php echo $idx; ?>, <?php echo json_encode($item); ?>)'>
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this industry card?');">
                                                        <input type="hidden" name="form_action" value="delete_item">
                                                        <input type="hidden" name="section_key" value="industry_solutions">
                                                        <input type="hidden" name="array_key" value="items">
                                                        <input type="hidden" name="item_index" value="<?php echo $idx; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Corporate Departments -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-info" data-feather="users"></i> Printmont for Corporate (Department Solutions)
                                </h5>
                                <small class="text-muted">Use cases for Marketing, Sales, Customer Experience, and HR teams.</small>
                            </div>
                            <button type="button" class="btn btn-info btn-sm text-white fw-bold px-3" onclick="openAddCorpModal()">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Department Use-case
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <?php foreach ($corpItems as $idx => $item): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="card border p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($item['tag'] ?? 'Department'); ?></span>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-xs btn-outline-primary" 
                                                            onclick='openEditCorpModal(<?php echo $idx; ?>, <?php echo json_encode($item); ?>)'>
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this use case?');">
                                                        <input type="hidden" name="form_action" value="delete_item">
                                                        <input type="hidden" name="section_key" value="corporate_departments">
                                                        <input type="hidden" name="array_key" value="items">
                                                        <input type="hidden" name="item_index" value="<?php echo $idx; ?>">
                                                        <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($item['headline'] ?? ''); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['desc'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ====================================================================
                     TAB 4: PORTFOLIO SHOWCASE, WHY PRINTMONT, STATS & REVIEWS
                     ==================================================================== -->
                <div id="tab-portfolio" class="tab-pane-content" style="display: none;">
                    
                    <?php 
                        $portfolioItems = $sectionsMap['portfolio_showcase']['config']['items'] ?? [];
                        $whyItems = $sectionsMap['why_printmont']['config']['items'] ?? [];
                        $statsItems = $sectionsMap['stats_counters']['config']['stats'] ?? [];
                        $reviewItems = $sectionsMap['customer_reviews']['config']['reviews'] ?? [];
                    ?>

                    <!-- 1. Portfolio Showcase -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-warning" data-feather="package"></i> Portfolio Gallery ("Work We Delivered for India's Largest Brands")
                                </h5>
                                <small class="text-muted">Corporate welcome kits, custom merchandise, packaging examples.</small>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm fw-bold px-3" onclick="openAddPortfolioModal()">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Delivered Product
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <?php foreach ($portfolioItems as $idx => $item): ?>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card border text-center h-100">
                                            <img src="<?php echo htmlspecialchars(resolveAdminImg($item['image'] ?? '')); ?>" 
                                                 style="height: 110px; object-fit: cover;" class="card-img-top"
                                                 onerror="this.src='img/photos/unsplash-1.jpg';">
                                            <div class="p-2">
                                                <p class="fw-bold small text-dark mb-0 text-truncate"><?php echo htmlspecialchars($item['title'] ?? ''); ?></p>
                                                <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($item['client'] ?? 'Enterprise Client'); ?></small>
                                                <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this portfolio item?');">
                                                    <input type="hidden" name="form_action" value="delete_item">
                                                    <input type="hidden" name="section_key" value="portfolio_showcase">
                                                    <input type="hidden" name="array_key" value="items">
                                                    <input type="hidden" name="item_index" value="<?php echo $idx; ?>">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger w-100">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Value Propositions ("Why Printmont?") -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-primary" data-feather="shield"></i> Value Propositions ("Why Printmont?")
                                </h5>
                                <small class="text-muted">GST Invoice, Single POC, Customer Service, Drop-Shipping Facilities.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="openAddWhyModal()">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Value Prop
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <?php foreach ($whyItems as $idx => $item): ?>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <div class="card border p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-light text-dark fw-bold"><?php echo htmlspecialchars($item['icon'] ?? 'Icon'); ?></span>
                                                <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this value proposition?');">
                                                    <input type="hidden" name="form_action" value="delete_item">
                                                    <input type="hidden" name="section_key" value="why_printmont">
                                                    <input type="hidden" name="array_key" value="items">
                                                    <input type="hidden" name="item_index" value="<?php echo $idx; ?>">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['desc'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Customer Reviews -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <i class="align-middle me-1 text-warning" data-feather="star"></i> Customer Reviews
                                </h5>
                                <small class="text-muted">Testimonials from corporate executives and marketing leaders.</small>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm fw-bold px-3" onclick="openAddReviewModal()">
                                <i class="align-middle me-1" data-feather="plus"></i> Add Review
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <?php foreach ($reviewItems as $idx => $rev): ?>
                                    <div class="col-12 col-md-4">
                                        <div class="card border p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-warning fw-bold">★★★★★ (<?php echo $rev['rating'] ?? 5; ?>/5)</span>
                                                <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this review?');">
                                                    <input type="hidden" name="form_action" value="delete_item">
                                                    <input type="hidden" name="section_key" value="customer_reviews">
                                                    <input type="hidden" name="array_key" value="reviews">
                                                    <input type="hidden" name="item_index" value="<?php echo $idx; ?>">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                            <p class="text-dark small fst-italic mb-3">"<?php echo htmlspecialchars($rev['review'] ?? ''); ?>"</p>
                                            <div class="mt-auto">
                                                <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($rev['name'] ?? ''); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($rev['company'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ====================================================================
                     TAB 5: FAQ ACCORDIONS
                     ==================================================================== -->
                <div id="tab-faqs" class="tab-pane-content" style="display: none;">
                    <div class="row g-4">
                        <div class="col-12 col-lg-5">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="card-title mb-0 fw-bold">Add New Corporate FAQ</h5>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="business-solutions-page.php">
                                        <input type="hidden" name="form_action" value="add_faq">

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small">Question *</label>
                                            <input type="text" class="form-control" name="question" required placeholder="e.g. Can you deliver to 50+ offices across India?">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small">Answer *</label>
                                            <textarea class="form-control" name="answer" rows="4" required placeholder="Detailed response for enterprise clients..."></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small">Display Order</label>
                                            <input type="number" class="form-control" name="display_order" value="<?php echo count($allFaqs) + 1; ?>">
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                                            <i class="align-middle me-1" data-feather="plus-circle"></i> Add FAQ Accordion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0 fw-bold">Active FAQ Items (<?php echo count($allFaqs); ?>)</h5>
                                    <span class="badge bg-secondary">Accordion View</span>
                                </div>
                                <div class="card-body p-3">
                                    <?php if (empty($allFaqs)): ?>
                                        <div class="text-center py-4 text-muted">No FAQs created yet.</div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($allFaqs as $faq): ?>
                                                <div class="list-group-item px-3 py-3 d-flex justify-content-between align-items-start gap-3">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="badge bg-dark rounded-pill"><?php echo $faq['display_order']; ?></span>
                                                            <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($faq['question']); ?></h6>
                                                        </div>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($faq['answer']); ?></p>
                                                    </div>
                                                    <form method="POST" action="business-solutions-page.php" onsubmit="return confirm('Delete this FAQ?');">
                                                        <input type="hidden" name="form_action" value="delete_faq">
                                                        <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="align-middle" data-feather="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====================================================================
                     TAB 6: SEO & META TAGS (Rule 3)
                     ==================================================================== -->
                <div id="tab-seo" class="tab-pane-content" style="display: none;">
                    <div class="card shadow-sm border-0" style="max-width: 800px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title mb-0 fw-bold">Dynamic SEO &amp; Meta Tags (Rule 3)</h5>
                            <small class="text-muted">Manage the page document title, meta descriptions, and keywords for search engines.</small>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="business-solutions-page.php">
                                <input type="hidden" name="form_action" value="update_seo">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Page Meta Title</label>
                                    <input type="text" class="form-control" name="meta_title" 
                                           value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>" required>
                                    <small class="text-muted">Recommended: 50-60 characters with key corporate search terms.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Page Meta Description</label>
                                    <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Recommended: 150-160 characters describing business printing capabilities.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Keywords (Comma separated)</label>
                                    <input type="text" class="form-control" name="meta_keywords" 
                                           value="<?php echo htmlspecialchars($page['meta_keywords'] ?? ''); ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Page Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" <?php echo ($page['status'] === 'active') ? 'selected' : ''; ?>>Active (Published on Storefront)</option>
                                        <option value="draft" <?php echo ($page['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                        <option value="inactive" <?php echo ($page['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                                    </select>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                                        <i class="align-middle me-1" data-feather="check-circle"></i> Save SEO Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php include_once 'includes/footer.php'; ?>
    </div>
</div>

<!-- ==========================================================================
     BANNER ADD / EDIT MODAL
     ========================================================================== -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="business-solutions-page.php" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="form_action" value="save_banner">
            <input type="hidden" name="section_key" id="modal_banner_section_key" value="hero_banner">
            <input type="hidden" name="banner_index" id="modal_banner_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modal_banner_title">Add Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    
                    <!-- Desktop Banner Image -->
                    <div class="col-12 col-md-6">
                        <div class="card p-3 bg-light border-0">
                            <h6 class="fw-bold text-dark mb-2">Desktop Banner Image (Wide 16:9 / 3:1)</h6>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Upload File</label>
                                <input type="file" class="form-control form-control-sm" name="image_desktop_file" accept="image/*">
                            </div>
                            <div>
                                <label class="form-label small fw-bold">OR Image URL</label>
                                <input type="text" class="form-control form-control-sm" name="image_desktop_url" id="modal_image_desktop" placeholder="https://... or uploads/...">
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Banner Image -->
                    <div class="col-12 col-md-6">
                        <div class="card p-3 bg-light border-0">
                            <h6 class="fw-bold text-dark mb-2">Mobile Banner Image (Compact / Square)</h6>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Upload File</label>
                                <input type="file" class="form-control form-control-sm" name="image_mobile_file" accept="image/*">
                            </div>
                            <div>
                                <label class="form-label small fw-bold">OR Image URL</label>
                                <input type="text" class="form-control form-control-sm" name="image_mobile_url" id="modal_image_mobile" placeholder="Leave blank to use desktop image">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small">Banner Title / Heading</label>
                        <input type="text" class="form-control" name="title" id="modal_banner_heading" placeholder="e.g. End-to-End Enterprise Printing">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold small">Target Link URL</label>
                        <input type="text" class="form-control" name="link_url" id="modal_banner_link" placeholder="#lead-form or /category/corporate-gifts">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Subtitle / Tagline</label>
                        <textarea class="form-control" name="subtitle" id="modal_banner_sub" rows="2" placeholder="e.g. From startup swag to MNC onboarding kits, deliver perfection at scale."></textarea>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Banner</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     INDUSTRY SOLUTION ADD / EDIT MODAL
     ========================================================================== -->
<div class="modal fade" id="industryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="business-solutions-page.php" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="form_action" value="save_item">
            <input type="hidden" name="section_key" value="industry_solutions">
            <input type="hidden" name="array_key" value="items">
            <input type="hidden" name="item_index" id="modal_ind_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modal_ind_modal_title">Industry Solution Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Target Tag / Industry Name *</label>
                    <input type="text" class="form-control" name="tag" id="modal_ind_tag" required placeholder="e.g. For MNCs, For Startups, For Restaurants">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Problem / Headline Question *</label>
                    <input type="text" class="form-control" name="headline" id="modal_ind_headline" required placeholder="e.g. Are you looking to scale your employee welcome kits?">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Solution Description *</label>
                    <textarea class="form-control" name="desc" id="modal_ind_desc" rows="3" required placeholder="How Printmont solves this industry need..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Card Image</label>
                    <input type="file" class="form-control mb-1" name="item_image_file" accept="image/*">
                    <input type="text" class="form-control form-control-sm" name="item_image_url" id="modal_ind_img_url" placeholder="OR paste image URL">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Card</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     CORPORATE DEPARTMENT ADD / EDIT MODAL
     ========================================================================== -->
<div class="modal fade" id="corpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="business-solutions-page.php" class="modal-content">
            <input type="hidden" name="form_action" value="save_item">
            <input type="hidden" name="section_key" value="corporate_departments">
            <input type="hidden" name="array_key" value="items">
            <input type="hidden" name="item_index" id="modal_corp_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Department Solution Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Department Tag *</label>
                    <input type="text" class="form-control" name="tag" id="modal_corp_tag" required placeholder="e.g. For Marketing, For HR, For Sales">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Department Question / Headline *</label>
                    <input type="text" class="form-control" name="headline" id="modal_corp_headline" required placeholder="e.g. Are you aiming for a seamless onboarding process?">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Solution Description *</label>
                    <textarea class="form-control" name="desc" id="modal_corp_desc" rows="3" required placeholder="How Printmont assists this department..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Use-case</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     PORTFOLIO PRODUCT ADD MODAL
     ========================================================================== -->
<div class="modal fade" id="portfolioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="business-solutions-page.php" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="form_action" value="save_item">
            <input type="hidden" name="section_key" value="portfolio_showcase">
            <input type="hidden" name="array_key" value="items">
            <input type="hidden" name="item_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Delivered Portfolio Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Product Title *</label>
                    <input type="text" class="form-control" name="title" required placeholder="e.g. Custom Welcome Kit, Embroidered Polo">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Client Type / Brand Name</label>
                    <input type="text" class="form-control" name="client" placeholder="e.g. Tech Giant, Global Consulting, Fintech Brand">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Product Image *</label>
                    <input type="file" class="form-control mb-1" name="item_image_file" accept="image/*">
                    <input type="text" class="form-control form-control-sm" name="item_image_url" placeholder="OR paste direct image URL">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     VALUE PROP ADD MODAL
     ========================================================================== -->
<div class="modal fade" id="whyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="business-solutions-page.php" class="modal-content">
            <input type="hidden" name="form_action" value="save_item">
            <input type="hidden" name="section_key" value="why_printmont">
            <input type="hidden" name="array_key" value="items">
            <input type="hidden" name="item_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Value Proposition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Title *</label>
                    <input type="text" class="form-control" name="title" required placeholder="e.g. GST Invoice, Single POC">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Description *</label>
                    <textarea class="form-control" name="desc" rows="3" required placeholder="Explain the benefit..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Icon Type</label>
                    <select class="form-select" name="icon">
                        <option value="FaFileInvoiceDollar">Invoice &amp; Billing (FaFileInvoiceDollar)</option>
                        <option value="FaUserTie">Single POC &amp; Executive (FaUserTie)</option>
                        <option value="FaHeadset">Customer Support (FaHeadset)</option>
                        <option value="FaTruck">Drop-Shipping &amp; Logistics (FaTruck)</option>
                        <option value="FaCheckCircle">Quality &amp; Trust (FaCheckCircle)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Add Value Prop</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     REVIEW ADD MODAL
     ========================================================================== -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="business-solutions-page.php" class="modal-content">
            <input type="hidden" name="form_action" value="save_item">
            <input type="hidden" name="section_key" value="customer_reviews">
            <input type="hidden" name="array_key" value="reviews">
            <input type="hidden" name="item_index" value="-1">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Customer Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Client Name *</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Ankur Priyadarshi">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Designation &amp; Company *</label>
                    <input type="text" class="form-control" name="company" required placeholder="e.g. HR Director, TechCorp">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Rating (Stars)</label>
                    <select class="form-select" name="rating">
                        <option value="5" selected>5 Stars (★★★★★)</option>
                        <option value="4">4 Stars (★★★★☆)</option>
                        <option value="3">3 Stars (★★★☆☆)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Review Content *</label>
                    <textarea class="form-control" name="review" rows="3" required placeholder="Client feedback..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Review</button>
            </div>
        </form>
    </div>
</div>

<script src="js/app.js"></script>
<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-pane-content').forEach(function(pane) {
            pane.style.display = 'none';
        });
        document.querySelectorAll('.bs-tab-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });

        var activePane = document.getElementById(tabId);
        if (activePane) {
            activePane.style.display = 'block';
        }

        if (window.event && window.event.target) {
            var btn = window.event.target.closest('.bs-tab-btn');
            if (btn) btn.classList.add('active');
        }
    }

    // Modal Helpers
    function openAddBannerModal(sectionKey, title) {
        document.getElementById('modal_banner_section_key').value = sectionKey;
        document.getElementById('modal_banner_index').value = -1;
        document.getElementById('modal_banner_title').innerText = 'Add ' + title;
        document.getElementById('modal_image_desktop').value = '';
        document.getElementById('modal_image_mobile').value = '';
        document.getElementById('modal_banner_heading').value = '';
        document.getElementById('modal_banner_sub').value = '';
        document.getElementById('modal_banner_link').value = '#lead-form';
        
        var modal = new bootstrap.Modal(document.getElementById('bannerModal'));
        modal.show();
    }

    function openEditBannerModal(sectionKey, index, bannerData) {
        document.getElementById('modal_banner_section_key').value = sectionKey;
        document.getElementById('modal_banner_index').value = index;
        document.getElementById('modal_banner_title').innerText = 'Edit Banner (Slide #' + (index + 1) + ')';
        document.getElementById('modal_image_desktop').value = bannerData.image_desktop || '';
        document.getElementById('modal_image_mobile').value = bannerData.image_mobile || '';
        document.getElementById('modal_banner_heading').value = bannerData.title || '';
        document.getElementById('modal_banner_sub').value = bannerData.subtitle || '';
        document.getElementById('modal_banner_link').value = bannerData.link_url || '#lead-form';
        
        var modal = new bootstrap.Modal(document.getElementById('bannerModal'));
        modal.show();
    }

    function openAddIndustryModal() {
        document.getElementById('modal_ind_index').value = -1;
        document.getElementById('modal_ind_modal_title').innerText = 'Add Industry Solution Card';
        document.getElementById('modal_ind_tag').value = '';
        document.getElementById('modal_ind_headline').value = '';
        document.getElementById('modal_ind_desc').value = '';
        document.getElementById('modal_ind_img_url').value = '';
        new bootstrap.Modal(document.getElementById('industryModal')).show();
    }

    function openEditIndustryModal(index, item) {
        document.getElementById('modal_ind_index').value = index;
        document.getElementById('modal_ind_modal_title').innerText = 'Edit Industry Card (' + (item.tag || '') + ')';
        document.getElementById('modal_ind_tag').value = item.tag || '';
        document.getElementById('modal_ind_headline').value = item.headline || '';
        document.getElementById('modal_ind_desc').value = item.desc || '';
        document.getElementById('modal_ind_img_url').value = item.image || '';
        new bootstrap.Modal(document.getElementById('industryModal')).show();
    }

    function openAddCorpModal() {
        document.getElementById('modal_corp_index').value = -1;
        document.getElementById('modal_corp_tag').value = '';
        document.getElementById('modal_corp_headline').value = '';
        document.getElementById('modal_corp_desc').value = '';
        new bootstrap.Modal(document.getElementById('corpModal')).show();
    }

    function openEditCorpModal(index, item) {
        document.getElementById('modal_corp_index').value = index;
        document.getElementById('modal_corp_tag').value = item.tag || '';
        document.getElementById('modal_corp_headline').value = item.headline || '';
        document.getElementById('modal_corp_desc').value = item.desc || '';
        new bootstrap.Modal(document.getElementById('corpModal')).show();
    }

    function openAddPortfolioModal() {
        new bootstrap.Modal(document.getElementById('portfolioModal')).show();
    }

    function openAddWhyModal() {
        new bootstrap.Modal(document.getElementById('whyModal')).show();
    }

    function openAddReviewModal() {
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    }
</script>
</body>
</html>
