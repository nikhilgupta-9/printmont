<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/HeaderSettingsController.php');

$headerCtrl = new HeaderSettingsController();

$error = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Current Settings
$searchSettings = $headerCtrl->getSearchSettings();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_search_settings'])) {
    $searchMode = in_array($_POST['search_mode'] ?? '', ['rotating', 'static']) ? $_POST['search_mode'] : 'rotating';
    $rotationInterval = (float)($_POST['rotation_interval'] ?? 3.2);
    if ($rotationInterval < 1.0) $rotationInterval = 1.0;
    if ($rotationInterval > 10.0) $rotationInterval = 10.0;

    $staticPlaceholder = trim($_POST['static_placeholder'] ?? '');
    if (empty($staticPlaceholder)) {
        $staticPlaceholder = 'Search for customized t-shirts, mugs, gifts and corporate items...';
    }

    // Process Placeholders List
    $rawPlaceholders = $_POST['placeholders'] ?? [];
    $placeholders = [];
    if (is_array($rawPlaceholders)) {
        foreach ($rawPlaceholders as $p) {
            $p = trim($p);
            if ($p !== '' && !in_array($p, $placeholders)) {
                $placeholders[] = $p;
            }
        }
    }
    if (empty($placeholders)) {
        $placeholders = [
            'Search for Customized T-Shirts, Hoodies & Polos...',
            'Search for Photo Mugs, Magic Mugs & Drinkware...',
            'Search for Corporate Joining Kits & Gift Combos...',
            'Search for Personalized Photo Frames & Canvas...',
            'Search for Visiting Cards, Diaries & Engraved Pens...',
            'Search for Stickers, Labels, Badges & Signages...'
        ];
    }

    // Process Popular Searches
    $rawPopular = $_POST['popular_searches'] ?? '';
    $popularSearches = [];
    if (is_string($rawPopular)) {
        $tags = explode(',', $rawPopular);
        foreach ($tags as $t) {
            $t = trim($t);
            if ($t !== '' && !in_array($t, $popularSearches)) {
                $popularSearches[] = $t;
            }
        }
    } elseif (is_array($rawPopular)) {
        foreach ($rawPopular as $t) {
            $t = trim($t);
            if ($t !== '' && !in_array($t, $popularSearches)) {
                $popularSearches[] = $t;
            }
        }
    }
    if (empty($popularSearches)) {
        $popularSearches = [
            'Custom T-Shirt',
            'Coffee Mug',
            'Photo Frame',
            'Visiting Cards',
            'Corporate Gift',
            'Hoodies',
            'Water Bottle',
            'Keychains'
        ];
    }

    $searchBoxStyle = in_array($_POST['search_box_style'] ?? '', ['expanded', 'standard', 'compact']) ? $_POST['search_box_style'] : 'expanded';
    $showClearBtn = !empty($_POST['show_clear_btn']);
    $enableHistory = !empty($_POST['enable_history']);
    $maxHistoryItems = (int)($_POST['max_history_items'] ?? 6);
    if ($maxHistoryItems < 1) $maxHistoryItems = 1;
    if ($maxHistoryItems > 15) $maxHistoryItems = 15;

    $newSettings = [
        'search_mode' => $searchMode,
        'rotation_interval' => $rotationInterval,
        'static_placeholder' => $staticPlaceholder,
        'placeholders' => $placeholders,
        'popular_searches' => $popularSearches,
        'search_box_style' => $searchBoxStyle,
        'show_clear_btn' => $showClearBtn,
        'enable_history' => $enableHistory,
        'max_history_items' => $maxHistoryItems
    ];

    if ($headerCtrl->saveSearchSettings($newSettings)) {
        $_SESSION['success_message'] = 'Search Box Text settings saved successfully!';
        header('Location: header-search-settings.php');
        exit;
    } else {
        $error = 'Failed to save search settings. Please check directory write permissions.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Search Box Text Setting &bull; Header Category Menu | PrintMont Admin</title>
    
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
            --pm-card-bg: #ffffff;
            --pm-border: #e2e8f0;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6fa; }
        .config-box { background: #fff; border: 1px solid var(--pm-border); border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(11,83,161,0.03); }
        .config-box-header { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--pm-border); display: flex; align-items: center; justify-content: space-between; }
        .config-box-title { font-size: 1.05rem; font-weight: 700; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .config-box-body { padding: 1.4rem; }

        /* Module Top Navigation Pills (Standard across all 7 Header Category Menu pages) */
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid var(--pm-border); }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: var(--pm-primary); background: var(--pm-primary-light); }
        .module-nav-link.active { color: #fff; background: var(--pm-primary); }

        /* KPI Cards */
        .kpi-card { background: #ffffff; border: 1px solid var(--pm-border); border-radius: 10px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .kpi-icon-box { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }

        /* Live Mockup Preview Box */
        .preview-header-bar {
            background: #0b53a1;
            padding: 16px 20px;
            border-radius: 12px;
            color: #fff;
            position: relative;
        }
        .mock-search-input-wrap {
            background: #ffffff;
            border-radius: 8px;
            padding: 4px 12px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 580px;
            position: relative;
        }
        .mock-search-input {
            border: none;
            outline: none;
            width: 100%;
            padding: 8px 10px;
            font-size: 14px;
            color: #1e293b;
            background: transparent;
        }
        .mock-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            border: 1px solid #e2e8f0;
            padding: 14px;
            z-index: 99;
            color: #334155;
            display: none;
        }

        /* Tag Badges */
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #334155;
            font-size: 12.5px;
            font-weight: 600;
            margin: 3px;
            border: 1px solid #cbd5e1;
        }
        .tag-pill .remove-tag {
            cursor: pointer;
            color: #94a3b8;
            font-size: 12px;
            transition: color 0.15s;
        }
        .tag-pill .remove-tag:hover {
            color: #ef4444;
        }

        /* Sortable List Item */
        .placeholder-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }
        .placeholder-item:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }
        .drag-handle {
            cursor: grab;
            color: #94a3b8;
        }
    </style>
    <link rel="stylesheet" href="css/header-admin-theme.css">
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        
        <main class="content p-3 p-md-4">
            <div class="container-fluid p-0">

                <!-- Header Actions (Standard across Header Category Menu) -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1 small">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted">5 no. Header Category Menu</li>
                                <li class="breadcrumb-item active text-primary fw-bold">5. Search Box Text setting</li>
                            </ol>
                        </nav>
                        <h2 class="h3 fw-bold text-dark mb-1">
                            <i class="fas fa-search text-primary me-2"></i> Header Category Menu &bull; Search Box Text setting
                        </h2>
                        <p class="text-muted small mb-0">
                            Configure dynamic rotating placeholders, static text, trending keywords, and search box behavior across Desktop &amp; Mobile headers.
                        </p>
                    </div>

                    <!-- Top Submenu Links: All 7 Items of Section 5 -->
                    <div class="module-nav-pills">
                        <a href="add-top-icon.php" class="module-nav-link">
                            <i class="fas fa-star text-warning"></i> 1. Top Icon
                        </a>
                        <a href="add-menu-page.php" class="module-nav-link">
                            <i class="fas fa-file-alt text-info"></i> 2. Top Menu Inside Pages
                        </a>
                        <a href="header-menu-design.php" class="module-nav-link">
                            <i class="fas fa-palette text-purple"></i> 3. Select Design
                        </a>
                        <a href="category-menu-description.php" class="module-nav-link">
                            <i class="fas fa-align-left text-primary"></i> 4. Category Text Description
                        </a>
                        <a href="header-search-settings.php" class="module-nav-link active">
                            <i class="fas fa-search text-success"></i> 5. Search Box Text
                        </a>
                        <a href="header-menu-list.php" class="module-nav-link">
                            <i class="fas fa-layer-group text-secondary"></i> 6. All menus
                        </a>
                        <a href="header-settings.php" class="module-nav-link">
                            <i class="fas fa-sliders-h text-dark"></i> 7. Page Rules &amp; Visibility
                        </a>
                    </div>
                </div>

                <!-- Feedback Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- KPI Metric Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-rotate"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Rotating Placeholders</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo count($searchSettings['placeholders']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-success-subtle text-success">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Trending Keywords</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo count($searchSettings['popular_searches']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-info-subtle text-info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Rotation Speed</div>
                                <div class="fs-5 fw-bold text-dark"><?php echo $searchSettings['rotation_interval']; ?>s</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-icon-box bg-warning-subtle text-warning">
                                <i class="fas fa-toggle-on"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold">Current Mode</div>
                                <div class="fs-5 fw-bold text-dark text-capitalize"><?php echo htmlspecialchars($searchSettings['search_mode']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE INTERACTIVE SEARCH BAR PREVIEW -->
                <div class="config-box mb-4" style="border-left: 4px solid var(--pm-primary);">
                    <div class="config-box-header">
                        <h4 class="config-box-title">
                            <i class="fas fa-eye text-primary"></i> Live Header Search Box Simulation &bull; Real-time Preview
                        </h4>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                            Interactive Mockup
                        </span>
                    </div>
                    <div class="config-box-body">
                        <div class="preview-header-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-5 text-white tracking-wide">PRINTMONT</span>
                            </div>

                            <!-- Mock Search Box -->
                            <div class="mock-search-input-wrap flex-grow-1" id="mockSearchWrap">
                                <i class="fas fa-search text-muted me-2"></i>
                                <input type="text" class="mock-search-input" id="liveMockInput" readonly placeholder="<?php echo htmlspecialchars($searchSettings['placeholders'][0] ?? 'Search...'); ?>" onclick="toggleMockDropdown()">
                                <?php if (!empty($searchSettings['show_clear_btn'])): ?>
                                    <i class="fas fa-times-circle text-muted ms-2" style="cursor:pointer;" title="Clear"></i>
                                <?php endif; ?>

                                <!-- Dropdown simulation -->
                                <div class="mock-search-dropdown" id="mockDropdown">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="small fw-bold text-muted text-uppercase"><i class="fas fa-history me-1"></i> Recent Searches</span>
                                        <a href="javascript:void(0)" class="small text-danger text-decoration-none">Clear</a>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-clock text-muted me-1"></i> Custom Coffee Mug</span>
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-clock text-muted me-1"></i> Round Neck T-Shirt</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                        <span class="small fw-bold text-muted text-uppercase"><i class="fas fa-fire text-danger me-1"></i> Trending &amp; Popular Searches</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1" id="liveMockPopularTags">
                                        <?php foreach ($searchSettings['popular_searches'] as $pTag): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?php echo htmlspecialchars($pTag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 text-white small">
                                <div><i class="fas fa-heart fs-6"></i></div>
                                <div><i class="fas fa-shopping-cart fs-6"></i></div>
                                <div><i class="fas fa-user-circle fs-6"></i></div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle text-primary me-1"></i> Click inside the simulated search box to preview the interactive dropdown menu with trending keywords.
                        </small>
                    </div>
                </div>

                <!-- FORM SETTINGS -->
                <form method="POST" id="searchSettingsForm">
                    <input type="hidden" name="save_search_settings" value="1">

                    <div class="row g-4">
                        
                        <!-- LEFT COLUMN: Search Mode & Rotating Placeholders -->
                        <div class="col-lg-7">
                            
                            <!-- Search Mode Box -->
                            <div class="config-box">
                                <div class="config-box-header">
                                    <h5 class="config-box-title">
                                        <i class="fas fa-sliders-h text-primary"></i> 1. Search Box Mode &amp; Rotation Speed
                                    </h5>
                                </div>
                                <div class="config-box-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Placeholder Mode</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="search_mode" id="mode_rotating" value="rotating" <?php echo ($searchSettings['search_mode'] === 'rotating') ? 'checked' : ''; ?> onchange="toggleModePanels()">
                                                <label class="btn btn-outline-primary fw-bold py-2" for="mode_rotating">
                                                    <i class="fas fa-rotate me-1"></i> Rotating (Flipkart style)
                                                </label>

                                                <input type="radio" class="btn-check" name="search_mode" id="mode_static" value="static" <?php echo ($searchSettings['search_mode'] === 'static') ? 'checked' : ''; ?> onchange="toggleModePanels()">
                                                <label class="btn btn-outline-primary fw-bold py-2" for="mode_static">
                                                    <i class="fas fa-minus me-1"></i> Static Text
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-6" id="rotationIntervalCol">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-stopwatch text-info me-1"></i> Rotation Interval (Seconds)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" step="0.1" min="1.0" max="10.0" class="form-control" name="rotation_interval" id="rotationIntervalInput" value="<?php echo htmlspecialchars($searchSettings['rotation_interval']); ?>">
                                                <span class="input-group-text bg-light">seconds</span>
                                            </div>
                                            <small class="text-muted">Recommended: 3.0s &ndash; 3.5s</small>
                                        </div>
                                    </div>

                                    <!-- Static Placeholder Input -->
                                    <div class="mt-3" id="staticPlaceholderWrap" style="<?php echo ($searchSettings['search_mode'] === 'static') ? '' : 'display:none;'; ?>">
                                        <label class="form-label fw-bold">Static Placeholder Text</label>
                                        <input type="text" class="form-control" name="static_placeholder" id="staticPlaceholderInput" value="<?php echo htmlspecialchars($searchSettings['static_placeholder']); ?>" placeholder="Search products...">
                                        <small class="text-muted">Displays fixed placeholder text without animation.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Rotating Placeholders List Manager -->
                            <div class="config-box" id="rotatingPlaceholdersBox" style="<?php echo ($searchSettings['search_mode'] === 'rotating') ? '' : 'opacity:0.6;'; ?>">
                                <div class="config-box-header">
                                    <h5 class="config-box-title">
                                        <i class="fas fa-list-ol text-success"></i> 2. Rotating Placeholders List
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addPlaceholderRow()">
                                        <i class="fas fa-plus me-1"></i> Add Placeholder Text
                                    </button>
                                </div>
                                <div class="config-box-body">
                                    <p class="text-muted small mb-3">
                                        Ye text items search box me har <?php echo $searchSettings['rotation_interval']; ?> second me ek ke baad ek animated transition ke sath show hote hain.
                                    </p>

                                    <div id="placeholdersListContainer">
                                        <?php foreach ($searchSettings['placeholders'] as $idx => $pText): ?>
                                            <div class="placeholder-item" id="pRow_<?php echo $idx; ?>">
                                                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                                                <span class="badge bg-light text-muted border px-2 py-1"><?php echo $idx + 1; ?></span>
                                                <input type="text" class="form-control form-control-sm placeholder-input-val" name="placeholders[]" value="<?php echo htmlspecialchars($pText); ?>" oninput="syncMockPlaceholders()">
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removePlaceholderRow(this)" title="Remove item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPlaceholderRow()">
                                            <i class="fas fa-plus-circle me-1"></i> Add Another Placeholder
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: Popular Searches & Visual Behavior -->
                        <div class="col-lg-5">
                            
                            <!-- Popular / Trending Search Tags -->
                            <div class="config-box">
                                <div class="config-box-header">
                                    <h5 class="config-box-title">
                                        <i class="fas fa-fire text-danger"></i> 3. Popular &amp; Trending Searches
                                    </h5>
                                </div>
                                <div class="config-box-body">
                                    <p class="text-muted small mb-2">
                                        Clicking inside search bar opens these clickable quick-search suggestions:
                                    </p>

                                    <!-- Tag Container -->
                                    <div class="p-2 bg-light rounded border mb-3 min-vh-25" id="tagCloudContainer">
                                        <?php foreach ($searchSettings['popular_searches'] as $tag): ?>
                                            <span class="tag-pill">
                                                <i class="fas fa-tag text-primary small"></i>
                                                <span><?php echo htmlspecialchars($tag); ?></span>
                                                <input type="hidden" name="popular_searches[]" value="<?php echo htmlspecialchars($tag); ?>">
                                                <i class="fas fa-times remove-tag" onclick="removeTag(this)"></i>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Add New Tag Input -->
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="newTagInput" class="form-control" placeholder="e.g. Sipper Bottle, T-Shirt..." onkeydown="if(event.key==='Enter'){event.preventDefault();addNewTag();}">
                                        <button type="button" class="btn btn-success" onclick="addNewTag()">
                                            <i class="fas fa-plus me-1"></i> Add Tag
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">Press Enter or click "Add Tag" to add keyword.</small>
                                </div>
                            </div>

                            <!-- Appearance & Behavior Settings -->
                            <div class="config-box">
                                <div class="config-box-header">
                                    <h5 class="config-box-title">
                                        <i class="fas fa-cog text-secondary"></i> 4. Appearance &amp; History
                                    </h5>
                                </div>
                                <div class="config-box-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Search Box Style</label>
                                        <select class="form-select form-select-sm" name="search_box_style">
                                            <option value="expanded" <?php echo ($searchSettings['search_box_style'] === 'expanded') ? 'selected' : ''; ?>>Expanded Full Width</option>
                                            <option value="standard" <?php echo ($searchSettings['search_box_style'] === 'standard') ? 'selected' : ''; ?>>Standard Center Aligned</option>
                                            <option value="compact" <?php echo ($searchSettings['search_box_style'] === 'compact') ? 'selected' : ''; ?>>Compact Pill</option>
                                        </select>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="show_clear_btn" id="showClearBtn" value="1" <?php echo !empty($searchSettings['show_clear_btn']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-semibold" for="showClearBtn">
                                            Show Clear Button (X) when user enters text
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="enable_history" id="enableHistory" value="1" <?php echo !empty($searchSettings['enable_history']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-semibold" for="enableHistory">
                                            Enable Search History (Store recent searches in user browser)
                                        </label>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold">Max Recent Search Items Saved</label>
                                        <input type="number" class="form-control form-control-sm" name="max_history_items" min="1" max="15" value="<?php echo (int)$searchSettings['max_history_items']; ?>">
                                        <small class="text-muted">Default: 6 items</small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Sticky Bottom Action Footer -->
                    <div class="card p-3 shadow-sm border-0 bg-white rounded-3 sticky-bottom mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="fw-bold text-dark">
                                    <i class="fas fa-check-double text-success me-1"></i> Save Changes to Header Category Menu &bull; Search Box Text
                                </span>
                                <div class="text-muted small">Updated settings will immediately reflect in frontend navigation search bar.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="header-search-settings.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </a>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="fas fa-save me-1"></i> Save Search Settings
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
// Live Mock Rotation Simulation
let mockPlaceholders = <?php echo json_encode($searchSettings['placeholders']); ?>;
let mockMode = "<?php echo $searchSettings['search_mode']; ?>";
let mockStatic = "<?php echo addslashes($searchSettings['static_placeholder']); ?>";
let mockInterval = <?php echo (float)$searchSettings['rotation_interval']; ?> * 1000;
let currentMockIdx = 0;
let mockTimer = null;

function runMockSimulation() {
    if (mockTimer) clearInterval(mockTimer);
    const input = document.getElementById('liveMockInput');
    if (!input) return;

    if (mockMode === 'static') {
        input.placeholder = mockStatic || 'Search products...';
        return;
    }

    if (!mockPlaceholders || mockPlaceholders.length === 0) {
        input.placeholder = 'Search products...';
        return;
    }

    input.placeholder = mockPlaceholders[currentMockIdx % mockPlaceholders.length];

    mockTimer = setInterval(() => {
        currentMockIdx++;
        if (input) {
            input.placeholder = mockPlaceholders[currentMockIdx % mockPlaceholders.length];
        }
    }, mockInterval);
}

function syncMockPlaceholders() {
    const inputs = document.querySelectorAll('.placeholder-input-val');
    mockPlaceholders = [];
    inputs.forEach(inp => {
        const val = inp.value.trim();
        if (val) mockPlaceholders.push(val);
    });
    runMockSimulation();
}

function toggleModePanels() {
    const isStatic = document.getElementById('mode_static').checked;
    mockMode = isStatic ? 'static' : 'rotating';
    document.getElementById('staticPlaceholderWrap').style.display = isStatic ? 'block' : 'none';
    document.getElementById('rotationIntervalCol').style.display = isStatic ? 'none' : 'block';
    document.getElementById('rotatingPlaceholdersBox').style.opacity = isStatic ? '0.6' : '1';
    runMockSimulation();
}

function toggleMockDropdown() {
    const dd = document.getElementById('mockDropdown');
    if (dd) {
        dd.style.display = (dd.style.display === 'block') ? 'none' : 'block';
    }
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('mockSearchWrap');
    const dd = document.getElementById('mockDropdown');
    if (wrap && dd && !wrap.contains(e.target)) {
        dd.style.display = 'none';
    }
});

// Dynamic Placeholder Row Operations
function addPlaceholderRow() {
    const container = document.getElementById('placeholdersListContainer');
    const count = container.querySelectorAll('.placeholder-item').length + 1;
    const row = document.createElement('div');
    row.className = 'placeholder-item';
    row.innerHTML = `
        <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
        <span class="badge bg-light text-muted border px-2 py-1">${count}</span>
        <input type="text" class="form-control form-control-sm placeholder-input-val" name="placeholders[]" value="" placeholder="Search for..." oninput="syncMockPlaceholders()">
        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removePlaceholderRow(this)" title="Remove item">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    container.appendChild(row);
    row.querySelector('input').focus();
}

function removePlaceholderRow(btn) {
    const row = btn.closest('.placeholder-item');
    if (row) {
        row.remove();
        // renumber badges
        const badges = document.querySelectorAll('#placeholdersListContainer .badge');
        badges.forEach((b, i) => b.textContent = i + 1);
        syncMockPlaceholders();
    }
}

// Popular Tags Operations
function addNewTag() {
    const input = document.getElementById('newTagInput');
    const tag = input.value.trim();
    if (!tag) return;

    // Check duplicate
    const existing = Array.from(document.querySelectorAll('#tagCloudContainer input[name="popular_searches[]"]')).map(i => i.value.toLowerCase());
    if (existing.includes(tag.toLowerCase())) {
        alert('This keyword tag already exists.');
        return;
    }

    const pill = document.createElement('span');
    pill.className = 'tag-pill';
    pill.innerHTML = `
        <i class="fas fa-tag text-primary small"></i>
        <span>${escapeHtml(tag)}</span>
        <input type="hidden" name="popular_searches[]" value="${escapeHtml(tag)}">
        <i class="fas fa-times remove-tag" onclick="removeTag(this)"></i>
    `;
    document.getElementById('tagCloudContainer').appendChild(pill);
    input.value = '';

    // Also update preview tags
    updatePreviewTags();
}

function removeTag(elem) {
    const pill = elem.closest('.tag-pill');
    if (pill) {
        pill.remove();
        updatePreviewTags();
    }
}

function updatePreviewTags() {
    const tags = Array.from(document.querySelectorAll('#tagCloudContainer input[name="popular_searches[]"]')).map(i => i.value);
    const previewWrap = document.getElementById('liveMockPopularTags');
    if (previewWrap) {
        previewWrap.innerHTML = tags.map(t => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">${escapeHtml(t)}</span>`).join(' ');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Init simulation on load
document.addEventListener('DOMContentLoaded', () => {
    runMockSimulation();
});
</script>
</body>
</html>
