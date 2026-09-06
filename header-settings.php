<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/HeaderSettingsController.php';
require_once __DIR__ . '/controllers/CategoryController.php';

$headerCtrl = new HeaderSettingsController();
$catCtrl = new CategoryController();
$database = new Database();
$conn = $database->getConnection();

$message = '';
$messageType = '';

// Handle manual updates / form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_rule') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        $data = [
            'page_name' => $_POST['page_name'] ?? '',
            'page_group' => $_POST['page_group'] ?? 'commerce',
            'route_patterns' => $_POST['route_patterns'] ?? '',
            'header_type' => $_POST['header_type'] ?? 'inner',
            'show_category_bar' => isset($_POST['show_category_bar']) ? 1 : 0,
            'category_bar_mode' => $_POST['category_bar_mode'] ?? 'text_only',
            'is_sticky' => isset($_POST['is_sticky']) ? 1 : 0,
            'custom_bg' => $_POST['custom_bg'] ?? 'rgb(11, 83, 161)',
            'custom_text_color' => $_POST['custom_text_color'] ?? 'white',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($headerCtrl->updatePageSetting($ruleId, $data)) {
            $message = 'Page rule updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update page rule.';
            $messageType = 'danger';
        }
    } elseif ($action === 'add_rule') {
        $data = [
            'page_name' => $_POST['page_name'] ?? '',
            'page_key' => $_POST['page_key'] ?? '',
            'page_group' => $_POST['page_group'] ?? 'custom',
            'route_patterns' => $_POST['route_patterns'] ?? '',
            'header_type' => $_POST['header_type'] ?? 'inner',
            'show_category_bar' => isset($_POST['show_category_bar']) ? 1 : 0,
            'category_bar_mode' => $_POST['category_bar_mode'] ?? 'text_only',
            'is_sticky' => isset($_POST['is_sticky']) ? 1 : 0,
            'custom_bg' => $_POST['custom_bg'] ?? 'rgb(11, 83, 161)',
            'custom_text_color' => $_POST['custom_text_color'] ?? 'white',
        ];
        if ($headerCtrl->createPageRule($data)) {
            $message = 'New page rule added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add page rule.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete_rule') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        if ($headerCtrl->deletePageRule($ruleId)) {
            $message = 'Page rule removed successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete page rule.';
            $messageType = 'danger';
        }
    } elseif ($action === 'toggle_category_visibility') {
        header('Content-Type: application/json');
        $catId = (int)($_POST['category_id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $val = ($_POST['value'] ?? '0') === '1' ? 'yes' : 'no';
        $statusVal = ($_POST['value'] ?? '0') === '1' ? 'show' : 'hide';

        if ($catId > 0 && in_array($field, ['desktop_home_show', 'desktop_menu_status', 'mobile_topbar_status', 'mobile_home_show'])) {
            $dbVal = ($field === 'desktop_home_show' || $field === 'mobile_home_show') ? $val : $statusVal;
            $stmt = $conn->prepare("UPDATE categories SET `{$field}` = ? WHERE id = ?");
            $stmt->bind_param("si", $dbVal, $catId);
            $res = $stmt->execute();
            echo json_encode(['success' => $res]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    } elseif ($action === 'save_global_design') {
        $design = $_POST['design'] ?? 'design1';
        $configFile = __DIR__ . '/config/header_menu_design.json';
        file_put_contents($configFile, json_encode(['design' => $design], JSON_PRETTY_PRINT));
        $message = 'Global header design layout updated successfully!';
        $messageType = 'success';
    } elseif ($action === 'reset_defaults') {
        $headerCtrl->reseedAllPages();
        $message = 'All page rules reset to default configurations.';
        $messageType = 'info';
    }
}

$pageRules = $headerCtrl->getAllSettings();
$flatCategories = $catCtrl->getAllActiveCategoriesFlat();

// Read current layout design
$configFile = __DIR__ . '/config/header_menu_design.json';
$currentDesign = 'design1';
if (file_exists($configFile)) {
    $designData = json_decode(file_get_contents($configFile), true);
    $currentDesign = $designData['design'] ?? 'design1';
}

$activeTab = $_GET['tab'] ?? 'page_rules';

// Group metadata
$groupMeta = [
    'commerce'  => ['title' => 'Commerce & Shop', 'icon' => 'fa-shopping-bag', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'desc' => 'Home, Shop, Products, Cart, Wishlist, Orders'],
    'account'   => ['title' => 'User Account', 'icon' => 'fa-user-circle', 'color' => '#b45309', 'bg' => '#fef3c7', 'desc' => 'Profile, Address, Coins, Password, Tracking'],
    'corporate' => ['title' => 'Corporate & Info', 'icon' => 'fa-building', 'color' => '#7e22ce', 'bg' => '#f3e8ff', 'desc' => 'About, Careers, Blogs, Seller, Bulk Orders, Sitemap'],
    'support'   => ['title' => 'Support & Help', 'icon' => 'fa-headset', 'color' => '#15803d', 'bg' => '#dcfce7', 'desc' => 'Help Center, Contact, FAQs, Support Desk'],
    'legal'     => ['title' => 'Legal & Policies', 'icon' => 'fa-shield-alt', 'color' => '#b91c1c', 'bg' => '#fee2e2', 'desc' => 'Privacy, Terms, Shipping, Refund Policies'],
    'custom'    => ['title' => 'Custom Pages', 'icon' => 'fa-star', 'color' => '#6d28d9', 'bg' => '#ede9fe', 'desc' => 'Custom landing pages & marketing routes'],
    'fallback'  => ['title' => 'Default Fallback', 'icon' => 'fa-globe', 'color' => '#475569', 'bg' => '#f1f5f9', 'desc' => 'Catch-all rule for other pages']
];

// Group the rules
$groupedRules = [];
foreach ($pageRules as $r) {
    $grp = $r['page_group'] ?? 'commerce';
    if (!isset($groupedRules[$grp])) {
        $groupedRules[$grp] = [];
    }
    $groupedRules[$grp][] = $r;
}

// Group counts
$groupCounts = ['all' => count($pageRules)];
foreach ($groupMeta as $k => $m) {
    $groupCounts[$k] = isset($groupedRules[$k]) ? count($groupedRules[$k]) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Header &amp; Categories | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .page-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .page-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .page-card.disabled-rule {
            opacity: 0.6;
            background: #f8fafc;
        }
        .group-pill {
            cursor: pointer;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            transition: all 0.2s ease;
            user-select: none;
            white-space: nowrap;
        }
        .group-pill:hover {
            border-color: #0b53a1;
            color: #0b53a1;
            background: #f8fafc;
        }
        .group-pill.active {
            background: #0b53a1;
            border-color: #0b53a1;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(11, 83, 161, 0.25);
        }
        .group-header-banner {
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .control-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
        }
        .color-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.15);
        }
        .badge-subtle {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .view-btn {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        /* Mobile optimization */
        @media (max-width: 768px) {
            .page-card {
                padding: 12px;
            }
            .filter-scroll-wrapper {
                overflow-x: auto;
                padding-bottom: 6px;
                -webkit-overflow-scrolling: touch;
            }
            .mobile-stack {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        
        <main class="content p-3 p-md-4">
            <div class="container-fluid p-0">
                
                <!-- Page Top Title & Actions -->
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h1 class="h4 mb-1 fw-bold text-dark">Header &amp; Category Rules</h1>
                        <p class="text-muted small mb-0">Customize header style and category bar display for each page on mobile and desktop.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                            <i class="fas fa-plus me-1"></i> Add Custom Page
                        </button>
                        <form method="POST" onsubmit="return confirm('Reset all page header rules to defaults?');" class="d-inline">
                            <input type="hidden" name="action" value="reset_defaults">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-undo-alt me-1"></i> Reset
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm py-2 px-3 small" role="alert">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-info-circle'; ?> me-1"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Top Tabs -->
                <ul class="nav nav-pills mb-3 gap-2">
                    <li class="nav-item">
                        <a href="?tab=page_rules" class="nav-link btn btn-sm <?php echo $activeTab === 'page_rules' ? 'active bg-primary' : 'bg-white text-dark border'; ?>">
                            <i class="fas fa-layer-group me-1"></i> Pages (<?php echo count($pageRules); ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?tab=category_visibility" class="nav-link btn btn-sm <?php echo $activeTab === 'category_visibility' ? 'active bg-primary' : 'bg-white text-dark border'; ?>">
                            <i class="fas fa-tags me-1"></i> Category Visibility (<?php echo count($flatCategories); ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="?tab=global_styling" class="nav-link btn btn-sm <?php echo $activeTab === 'global_styling' ? 'active bg-primary' : 'bg-white text-dark border'; ?>">
                            <i class="fas fa-paint-brush me-1"></i> Menu Layout
                        </a>
                    </li>
                </ul>

                <!-- TAB 1: Pages Management -->
                <?php if ($activeTab === 'page_rules'): ?>
                    
                    <!-- Search & Quick Filter Pills -->
                    <div class="bg-white p-3 rounded-3 border mb-3 shadow-sm">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" id="pageSearchInput" class="form-control border-start-0" placeholder="Search page name or route..." onkeyup="filterPages()">
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="filter-scroll-wrapper d-flex gap-1 align-items-center">
                                    <span class="group-pill active" onclick="filterByGroup('all', this)">All (<?php echo $groupCounts['all']; ?>)</span>
                                    <span class="group-pill" onclick="filterByGroup('commerce', this)">🛍️ Commerce (<?php echo $groupCounts['commerce']; ?>)</span>
                                    <span class="group-pill" onclick="filterByGroup('account', this)">👤 Account (<?php echo $groupCounts['account']; ?>)</span>
                                    <span class="group-pill" onclick="filterByGroup('corporate', this)">🏢 Corporate (<?php echo $groupCounts['corporate']; ?>)</span>
                                    <span class="group-pill" onclick="filterByGroup('support', this)">💬 Support (<?php echo $groupCounts['support']; ?>)</span>
                                    <span class="group-pill" onclick="filterByGroup('legal', this)">⚖️ Legal (<?php echo $groupCounts['legal']; ?>)</span>
                                    <?php if ($groupCounts['custom'] > 0): ?>
                                        <span class="group-pill" onclick="filterByGroup('custom', this)">⭐ Custom (<?php echo $groupCounts['custom']; ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Cards / Sections by Group -->
                    <div id="pagesContainer">
                        <?php foreach ($groupMeta as $groupKey => $meta): 
                            $rulesInGroup = $groupedRules[$groupKey] ?? [];
                            if (empty($rulesInGroup)) continue;
                        ?>
                        <div class="group-section mb-4" data-group-section="<?php echo $groupKey; ?>">
                            <!-- Section Title -->
                            <div class="group-header-banner" style="background: <?php echo $meta['bg']; ?>; border: 1px solid <?php echo $meta['color']; ?>30;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas <?php echo $meta['icon']; ?>" style="color: <?php echo $meta['color']; ?>;"></i>
                                    <div>
                                        <strong style="color: <?php echo $meta['color']; ?>;"><?php echo $meta['title']; ?></strong>
                                        <span class="badge rounded-pill bg-white ms-2 text-dark shadow-xs"><?php echo count($rulesInGroup); ?> pages</span>
                                    </div>
                                </div>
                                <small class="text-muted d-none d-md-inline"><?php echo $meta['desc']; ?></small>
                            </div>

                            <!-- Page Cards Grid -->
                            <div class="row g-2">
                                <?php foreach ($rulesInGroup as $rule): 
                                    $primaryRoute = explode(',', $rule['route_patterns'])[0];
                                ?>
                                <div class="col-12 col-xl-6 page-card-wrapper" data-group="<?php echo $groupKey; ?>" data-search="<?php echo htmlspecialchars(strtolower($rule['page_name'] . ' ' . $rule['route_patterns'] . ' ' . $rule['page_key'])); ?>">
                                    <div class="page-card <?php echo empty($rule['is_active']) ? 'disabled-rule' : ''; ?>" id="card-<?php echo $rule['id']; ?>">
                                        
                                        <!-- Card Top: Title, Route & Edit Button -->
                                        <div class="d-flex align-items-center justify-content-between mb-3 mobile-stack">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; background: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>;">
                                                    <i class="fas <?php echo $meta['icon']; ?> small"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($rule['page_name']); ?></div>
                                                    <code class="small text-muted"><?php echo htmlspecialchars($primaryRoute); ?></code>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-1">
                                                <button type="button" class="btn btn-sm btn-light border" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($rule)); ?>)" title="Detailed Settings">
                                                    <i class="fas fa-sliders-h text-primary"></i> <span class="d-none d-sm-inline ms-1">Settings</span>
                                                </button>
                                                <?php if (!empty($rule['is_custom'])): ?>
                                                    <button type="button" class="btn btn-sm btn-light border text-danger" onclick="deleteRule(<?php echo $rule['id']; ?>, '<?php echo htmlspecialchars(addslashes($rule['page_name'])); ?>')" title="Delete Custom Page">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Card Controls: Compact & Touch-Friendly -->
                                        <div class="row g-2 pt-2 border-top align-items-center">
                                            
                                            <!-- Control 1: Header Type -->
                                            <div class="col-6 col-md-4">
                                                <span class="control-label">Header Style</span>
                                                <select class="form-select form-select-sm" onchange="quickUpdate(<?php echo $rule['id']; ?>, 'header_type', this.value)">
                                                    <option value="full" <?php echo $rule['header_type'] === 'full' ? 'selected' : ''; ?>>Full Header</option>
                                                    <option value="inner" <?php echo $rule['header_type'] === 'inner' ? 'selected' : ''; ?>>Inner Header</option>
                                                    <option value="minimal" <?php echo $rule['header_type'] === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                                                    <option value="none" <?php echo $rule['header_type'] === 'none' ? 'selected' : ''; ?>>Hide (None)</option>
                                                </select>
                                            </div>

                                            <!-- Control 2: Category Bar Toggle -->
                                            <div class="col-6 col-md-3">
                                                <span class="control-label">Category Bar</span>
                                                <div class="form-check form-switch pt-1">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           id="cat_<?php echo $rule['id']; ?>"
                                                           <?php echo $rule['show_category_bar'] ? 'checked' : ''; ?>
                                                           onchange="quickUpdate(<?php echo $rule['id']; ?>, 'show_category_bar', this.checked ? 1 : 0)">
                                                    <label class="form-check-label small fw-semibold <?php echo $rule['show_category_bar'] ? 'text-success' : 'text-muted'; ?>" for="cat_<?php echo $rule['id']; ?>">
                                                        <?php echo $rule['show_category_bar'] ? 'Visible' : 'Hidden'; ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Control 3: Bar Mode (Images vs Dropdown) -->
                                            <div class="col-6 col-md-3">
                                                <span class="control-label">Bar Style</span>
                                                <select class="form-select form-select-sm" onchange="quickUpdate(<?php echo $rule['id']; ?>, 'category_bar_mode', this.value)">
                                                    <option value="text_only" <?php echo $rule['category_bar_mode'] === 'text_only' ? 'selected' : ''; ?>>📝 Text Bar</option>
                                                    <option value="with_images" <?php echo $rule['category_bar_mode'] === 'with_images' ? 'selected' : ''; ?>>🖼️ Images</option>
                                                </select>
                                            </div>

                                            <!-- Control 4: Sticky Toggle -->
                                            <div class="col-6 col-md-2">
                                                <span class="control-label">Sticky</span>
                                                <div class="form-check form-switch pt-1">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           id="stk_<?php echo $rule['id']; ?>"
                                                           <?php echo $rule['is_sticky'] ? 'checked' : ''; ?>
                                                           onchange="quickUpdate(<?php echo $rule['id']; ?>, 'is_sticky', this.checked ? 1 : 0)">
                                                    <label class="form-check-label small" for="stk_<?php echo $rule['id']; ?>">
                                                        <?php echo $rule['is_sticky'] ? '📌 On' : 'Off'; ?>
                                                    </label>
                                                </div>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

                <!-- TAB 2: Category Visibility Matrix -->
                <?php if ($activeTab === 'category_visibility'): ?>
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-0 fw-bold">Category Visibility Switches</h5>
                                <small class="text-muted">Toggle which categories appear on Home Bar, Top Dropdowns, and Mobile Drawer.</small>
                            </div>
                            <span class="badge bg-success px-3 py-2"><?php echo count($flatCategories); ?> Categories</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Home Icon Bar (Desktop)</th>
                                        <th class="text-center">Top Dropdown Inside Pages</th>
                                        <th class="text-center">Mobile Drawer</th>
                                        <th class="text-center">Mobile Home Strip</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($flatCategories as $cat): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($cat['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($cat['image']); ?>" alt="" class="rounded" style="width: 30px; height: 30px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 30px; height: 30px;">
                                                        <i class="fas fa-folder"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                                    <div class="small text-muted"><code>/category/<?php echo htmlspecialchars($cat['slug']); ?></code></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       <?php echo ($cat['desktop_home_show'] ?? '') === 'yes' ? 'checked' : ''; ?>
                                                       onchange="toggleCat(<?php echo $cat['id']; ?>, 'desktop_home_show', this.checked ? 1 : 0)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       <?php echo ($cat['desktop_menu_status'] ?? '') === 'show' ? 'checked' : ''; ?>
                                                       onchange="toggleCat(<?php echo $cat['id']; ?>, 'desktop_menu_status', this.checked ? 1 : 0)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       <?php echo ($cat['mobile_topbar_status'] ?? '') === 'show' ? 'checked' : ''; ?>
                                                       onchange="toggleCat(<?php echo $cat['id']; ?>, 'mobile_topbar_status', this.checked ? 1 : 0)">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       <?php echo ($cat['mobile_home_show'] ?? '') === 'yes' ? 'checked' : ''; ?>
                                                       onchange="toggleCat(<?php echo $cat['id']; ?>, 'mobile_home_show', this.checked ? 1 : 0)">
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- TAB 3: Global Layout & Design -->
                <?php if ($activeTab === 'global_styling'): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_global_design">
                        <div class="card shadow-sm border-0 rounded-3 mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold">Global Header Menu Template</h5>
                                <small class="text-muted">Select the visual dropdown style for main navigation.</small>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php 
                                    $designs = [
                                        'design1' => ['📱', 'Compact Clean', 'Modern horizontal scrollable categories chips'],
                                        'design2' => ['🖼️', 'Visual Mega-Menu', 'Multi-column mega dropdown with banner images'],
                                        'design3' => ['🗂️', 'Category Grid Tabs', 'Side tabbed category lists with preview tiles'],
                                        'design4' => ['🎨', 'Classic Dropdown', 'Minimalist text-based nested sub-menus'],
                                    ];
                                    foreach ($designs as $val => [$icon, $title, $desc]):
                                    ?>
                                    <div class="col-md-6 col-lg-3">
                                        <input type="radio" name="design" value="<?php echo $val; ?>" id="d_<?php echo $val; ?>" class="d-none" <?php echo $currentDesign === $val ? 'checked' : ''; ?>>
                                        <label for="d_<?php echo $val; ?>" class="page-card d-block h-100 text-center cursor-pointer" style="cursor: pointer;">
                                            <div class="mb-2 fs-1"><?php echo $icon; ?></div>
                                            <div class="fw-bold mb-1"><?php echo $title; ?></div>
                                            <small class="text-muted"><?php echo $desc; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white py-3">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Save Layout
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </main>

        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<!-- Modal: Add New Page Rule -->
<div class="modal fade" id="addRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="add_rule">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Custom Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Page Title</label>
                    <input type="text" name="page_name" class="form-control" placeholder="e.g. Summer Sale 2026" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">URL Route Pattern</label>
                    <input type="text" name="route_patterns" class="form-control" placeholder="e.g. /summer-sale" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Section Category</label>
                    <select name="page_group" class="form-select">
                        <option value="commerce">Commerce &amp; Shop</option>
                        <option value="account">User Account</option>
                        <option value="corporate">Corporate &amp; Discovery</option>
                        <option value="support">Support &amp; Help</option>
                        <option value="legal">Legal &amp; Policies</option>
                        <option value="custom" selected>Custom Campaign / Landing</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Header Style</label>
                        <select name="header_type" class="form-select">
                            <option value="inner" selected>Inner Header</option>
                            <option value="full">Full Header</option>
                            <option value="minimal">Minimal</option>
                            <option value="none">Hide (None)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Category Bar Style</label>
                        <select name="category_bar_mode" class="form-select">
                            <option value="text_only" selected>📝 Text Bar</option>
                            <option value="with_images">🖼️ Images Bar</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_category_bar" id="add_cat" value="1" checked>
                        <label class="form-check-label small" for="add_cat">Show Category Bar</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_sticky" id="add_stk" value="1" checked>
                        <label class="form-check-label small" for="add_stk">Sticky on Scroll</label>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Background</label>
                        <input type="text" name="custom_bg" class="form-control form-control-sm" value="rgb(11, 83, 161)">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Text Color</label>
                        <input type="text" name="custom_text_color" class="form-control form-control-sm" value="white">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Page</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Page Rule -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="save_rule">
            <input type="hidden" name="rule_id" id="edit_rule_id">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editModalTitle">Edit Page Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Page Title</label>
                    <input type="text" name="page_name" id="edit_page_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">URL Route Patterns</label>
                    <input type="text" name="route_patterns" id="edit_route_patterns" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Section Category</label>
                    <select name="page_group" id="edit_page_group" class="form-select">
                        <option value="commerce">Commerce &amp; Shop</option>
                        <option value="account">User Account</option>
                        <option value="corporate">Corporate &amp; Discovery</option>
                        <option value="support">Support &amp; Help</option>
                        <option value="legal">Legal &amp; Policies</option>
                        <option value="custom">Custom Campaign / Landing</option>
                        <option value="fallback">Fallback Catch-all</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Header Style</label>
                        <select name="header_type" id="edit_header_type" class="form-select">
                            <option value="full">Full Header</option>
                            <option value="inner">Inner Header</option>
                            <option value="minimal">Minimal</option>
                            <option value="none">Hide (None)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Category Bar Style</label>
                        <select name="category_bar_mode" id="edit_category_bar_mode" class="form-select">
                            <option value="text_only">📝 Text Bar</option>
                            <option value="with_images">🖼️ Images Bar</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_category_bar" id="edit_show_category_bar" value="1">
                        <label class="form-check-label small" for="edit_show_category_bar">Show Category Bar</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_sticky" id="edit_is_sticky" value="1">
                        <label class="form-check-label small" for="edit_is_sticky">Sticky on Scroll</label>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Background</label>
                        <input type="text" name="custom_bg" id="edit_custom_bg" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Text Color</label>
                        <input type="text" name="custom_text_color" id="edit_custom_text_color" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                    <label class="form-check-label small" for="edit_is_active">Enable this rule</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Rule Form -->
<form id="deleteRuleForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_rule">
    <input type="hidden" name="rule_id" id="delete_rule_id">
</form>

<script src="js/app.js"></script>
<script>
let activeGroup = 'all';

function filterByGroup(group, el) {
    activeGroup = group;
    document.querySelectorAll('.group-pill').forEach(p => p.classList.remove('active'));
    if (el) el.classList.add('active');
    filterPages();
}

function filterPages() {
    const q = (document.getElementById('pageSearchInput')?.value || '').toLowerCase().trim();
    const sections = document.querySelectorAll('.group-section');

    sections.forEach(section => {
        const secGroup = section.getAttribute('data-group-section');
        const cards = section.querySelectorAll('.page-card-wrapper');
        let visibleCount = 0;

        cards.forEach(card => {
            const cardGroup = card.getAttribute('data-group');
            const cardSearch = card.getAttribute('data-search') || '';

            const matchesGroup = activeGroup === 'all' || cardGroup === activeGroup;
            const matchesSearch = !q || cardSearch.includes(q);

            if (matchesGroup && matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Hide the whole section header if no cards match
        section.style.display = visibleCount > 0 ? '' : 'none';
    });
}

function openEditModal(rule) {
    document.getElementById('edit_rule_id').value = rule.id;
    document.getElementById('editModalTitle').innerText = 'Settings: ' + rule.page_name;
    document.getElementById('edit_page_name').value = rule.page_name;
    document.getElementById('edit_route_patterns').value = rule.route_patterns;
    document.getElementById('edit_page_group').value = rule.page_group || 'commerce';
    document.getElementById('edit_header_type').value = rule.header_type;
    document.getElementById('edit_show_category_bar').checked = parseInt(rule.show_category_bar) === 1;
    document.getElementById('edit_category_bar_mode').value = rule.category_bar_mode;
    document.getElementById('edit_is_sticky').checked = parseInt(rule.is_sticky) === 1;
    document.getElementById('edit_custom_bg').value = rule.custom_bg || 'rgb(11, 83, 161)';
    document.getElementById('edit_custom_text_color').value = rule.custom_text_color || 'white';
    document.getElementById('edit_is_active').checked = parseInt(rule.is_active) === 1;
    
    new bootstrap.Modal(document.getElementById('editRuleModal')).show();
}

function deleteRule(id, name) {
    if (confirm('Delete rule for "' + name + '"?')) {
        document.getElementById('delete_rule_id').value = id;
        document.getElementById('deleteRuleForm').submit();
    }
}

function showToast(msg) {
    let toast = document.getElementById('quickToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'quickToast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0b53a1;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,0.18);z-index:9999;transition:all 0.25s ease;display:flex;align-items:center;gap:8px;';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    clearTimeout(window._toastTimeout);
    window._toastTimeout = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 2000);
}

function quickUpdate(id, field, value) {
    // Dynamic label updates
    if (field === 'show_category_bar') {
        const lbl = document.querySelector('label[for="cat_' + id + '"]');
        if (lbl) {
            lbl.className = 'form-check-label small fw-semibold ' + (value ? 'text-success' : 'text-muted');
            lbl.innerText = value ? 'Visible' : 'Hidden';
        }
    } else if (field === 'is_sticky') {
        const lbl = document.querySelector('label[for="stk_' + id + '"]');
        if (lbl) {
            lbl.className = 'form-check-label small ' + (value ? 'fw-semibold text-dark' : 'text-muted');
            lbl.innerText = value ? '📌 On' : 'Off';
        }
    }

    fetch('api/header-settings-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', id: id, field: field, value: value })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Saved successfully');
        } else {
            alert('Failed to update setting: ' + (data.message || 'Error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while saving setting.');
    });
}

function toggleCat(categoryId, field, value) {
    const formData = new FormData();
    formData.append('action', 'toggle_category_visibility');
    formData.append('category_id', categoryId);
    formData.append('field', field);
    formData.append('value', value);

    fetch('header-settings.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Category visibility updated');
        } else {
            alert('Failed to update category visibility.');
        }
    })
    .catch(err => console.error(err));
}
</script>
</body>
</html>
