<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller     = new CategoryController();
$mainCategories = $controller->getMainCategories();
$error   = '';
$success = '';

// Pre-select parent when arriving from "Add Sub Category" on a specific main category row
$selectedParentId = (int)($_POST['parent_id'] ?? $_GET['parent_id'] ?? 0);
$nextOrder = $selectedParentId ? $controller->getNextDisplayOrder(2, $selectedParentId) : 1;

if (!function_exists('old')) {
function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}
}
if (!function_exists('oldSel')) {
function oldSel($key, $value, $default = '') {
    return (($_POST[$key] ?? $default) === $value) ? 'selected' : '';
}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImg($fileKey, $subdir = 'sub') {
        if (empty($_FILES[$fileKey]['name'])) return '';
        $file    = $_FILES[$fileKey];
        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $file['size'] > 5*1024*1024) return '';
        $subdir  = trim($subdir, '/');
        $prefix  = $subdir !== '' ? "{$subdir}/" : '';
        $dir     = __DIR__ . "/uploads/category/{$prefix}";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn      = uniqid() . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$prefix}{$fn}" : '';
    }

    $parentId = (int)($_POST['parent_id'] ?? 0);
    $additionalParentIds = isset($_POST['additional_parent_ids']) && is_array($_POST['additional_parent_ids']) 
        ? implode(',', array_filter(array_map('intval', $_POST['additional_parent_ids']))) 
        : null;

    $data = [
        // Tab 1: Category Details
        'name'                 => $name,
        'slug'                 => $slug,
        'page_target'          => in_array($_POST['page_target'] ?? '', ['home_page', 'other_pages', 'both']) ? $_POST['page_target'] : 'both',
        'description'          => trim($_POST['description'] ?? ''),
        'parent_id'            => $parentId,
        'additional_parent_ids'=> $additionalParentIds,
        'level'                => 2,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? $controller->getNextDisplayOrder(2, $parentId)),
        'image'                => uploadCatImg('image', 'sub'),

        // Tab 2: Desktop Top Menu & Home Page Settings
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        'desktop_menu_tag_bg'  => trim($_POST['desktop_menu_tag_bg'] ?? ''),
        'desktop_home_show'    => $_POST['desktop_home_show'] ?? 'no',
        'desktop_home_design'  => $_POST['desktop_home_design'] ?? 'home_design_1',
        'desktop_home_order'   => (int)($_POST['desktop_home_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'desktop_bg_color'     => trim($_POST['desktop_bg_color'] ?? ''),
        'desktop_bg_image'     => uploadCatImg('desktop_bg_image', 'bg'),
        'desktop_image'        => uploadCatImg('desktop_image', 'home'),

        // Tab 3: Mobile Format Settings
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'mobile_sidebar_status'=> $_POST['mobile_sidebar_status'] ?? 'yes',
        'mobile_sidebar_order' => (int)($_POST['mobile_sidebar_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'mobile_home_show'     => $_POST['mobile_home_show'] ?? 'no',
        'mobile_home_design'   => $_POST['mobile_home_design'] ?? 'home_design_1',
        'mobile_home_format'   => in_array($_POST['mobile_home_format'] ?? '', ['4','6','8']) ? $_POST['mobile_home_format'] : '4',
        'mobile_home_order'    => (int)($_POST['mobile_home_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'mobile_bg_color'      => trim($_POST['mobile_bg_color'] ?? ''),
        'mobile_bg_image'      => uploadCatImg('mobile_bg_image', 'bg'),
        'mobile_image'         => uploadCatImg('mobile_image', 'home'),

        // Tab 4: SEO Metadata
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Subcategory name is required.';
    } elseif (!$parentId) {
        $error = 'Please select a main parent category.';
    } elseif ($controller->checkCategoryExists($data['name'], $data['slug'])) {
        $error = 'A category with this name or slug already exists. Please choose a different name or slug.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Subcategory created successfully!';
            header('Location: view-categories.php?level=2');
            exit;
        } else {
            $error = 'Failed to create subcategory.';
        }
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
    <title>2. Add New Sub Category | Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .required-field::after { content: " *"; color: #dc3545; }
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #198754; background: #dcfce7; }
        .module-nav-link.active { color: #fff; background: #198754; }
        .nav-tabs .nav-link {
            font-size: 14px;
            font-weight: 600;
            padding: 12px 18px;
            color: #4b5563;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .nav-tabs .nav-link.active {
            color: #198754 !important;
            border-color: #dee2e6 #dee2e6 #ffffff !important;
            background-color: #ffffff !important;
            font-weight: 700;
        }
        .tab-content {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 24px;
        }
        .section-header-box {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .field-num-badge {
            display: inline-block;
            background: #198754;
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            font-size: 11px;
            line-height: 20px;
            font-weight: 700;
            margin-right: 6px;
        }
        .design-option-card {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            background: #ffffff;
            display: block;
            height: 100%;
        }
        .design-option-card:hover {
            border-color: #198754;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        input[type="radio"]:checked + .design-option-card {
            border-color: #198754;
            background-color: #f0fdf4;
            box-shadow: 0 0 0 1px #198754;
        }
        .design-option-card .check-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            color: #198754;
            font-size: 16px;
        }
        input[type="radio"]:checked + .design-option-card .check-badge {
            display: block;
        }
        .layout-wireframe {
            height: 70px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            margin-bottom: 8px;
            padding: 4px;
        }
        .design-title { font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 2px; }
        .design-desc { font-size: 11px; color: #64748b; margin-bottom: 0; line-height: 1.3; }
        .sub-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .sub-card-title {
            font-weight: 700;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .image-preview-box {
            max-width: 120px;
            max-height: 80px;
            object-fit: contain;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            display: none;
            background: #f8fafc;
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

                <!-- Top Module Navigation Pills: 4 no. Manage Categories -->
                <div class="module-nav-pills mb-4">
                    <a href="add-main-category.php" class="module-nav-link">
                        <i class="fas fa-folder-plus text-primary"></i> 1. Main Category
                    </a>
                    <a href="view-categories.php?level=1" class="module-nav-link">
                        <i class="fas fa-folder text-secondary"></i> 2. Main All Categories
                    </a>
                    <a href="add-sub-category.php" class="module-nav-link active">
                        <i class="fas fa-folder-tree text-success"></i> 3. Sub Category
                    </a>
                    <a href="view-categories.php?level=2" class="module-nav-link">
                        <i class="fas fa-list text-secondary"></i> 4. Sub All Category
                    </a>
                    <a href="add-sub-sub-category.php" class="module-nav-link">
                        <i class="fas fa-tags text-info"></i> 5. Sub Sub Category
                    </a>
                    <a href="view-categories.php?level=3" class="module-nav-link">
                        <i class="fas fa-list-check text-secondary"></i> 6. Sub Sub All Category
                    </a>
                    <a href="home-page-categories.php" class="module-nav-link">
                        <i class="fas fa-home text-warning"></i> 7. Home page Category (Desktop &amp; Mobile)
                    </a>
                </div>

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success px-2 py-1">Manage Categories &gt; 2. Add New Sub Category</span>
                        </div>
                        <h2 class="h3 fw-bold mb-1 mt-1">Add New Sub Category</h2>
                        <p class="text-muted small mb-0">Configure Subcategory details, Top Menu dropdown position &amp; tag color, Desktop &amp; Mobile Home display designs, and SEO.</p>
                    </div>
                    <div>
                        <a href="view-categories.php?level=2" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Sub All Category
                        </a>
                        <button type="submit" form="subCatForm" class="btn btn-success px-4 fw-semibold">
                            <i class="fas fa-plus me-1"></i> Create Sub Category
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="subCatForm">

                    <!-- SEPARATE TAB NAVIGATION -->
                    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                                <i class="fas fa-folder me-1 text-success"></i> 1. Sub Category Details
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-desktop-btn" data-bs-toggle="tab" data-bs-target="#tab-desktop" type="button" role="tab">
                                <i class="fas fa-desktop me-1 text-primary"></i> 2. Desktop Top Menu &amp; Home Settings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-mobile-btn" data-bs-toggle="tab" data-bs-target="#tab-mobile" type="button" role="tab">
                                <i class="fas fa-mobile-alt me-1 text-info"></i> 3. Mobile Format Tab Section
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-seo-btn" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                                <i class="fas fa-search me-1 text-warning"></i> 4. SEO &amp; Metadata
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content shadow-sm mb-4">

                        <!-- TAB 1: BASIC INFO -->
                        <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">

                            <!-- Target Page Selection: Select Home pages and Other pages -->
                            <div class="card p-3 mb-4 bg-light border" style="border-left: 4px solid #198754 !important;">
                                <label class="form-label fw-bold text-dark mb-2">
                                    <i class="fas fa-bullseye text-success me-1"></i> Target Page Selection: Select Home Pages and Other Pages
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="sub_pt_both" value="both" <?php echo (old('page_target', 'both') === 'both') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="sub_pt_both">
                                                Both Home Page &amp; Other Pages
                                                <small class="d-block text-muted fw-normal">Visible across home &amp; all subcategory pages</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="sub_pt_home" value="home_page" <?php echo (old('page_target') === 'home_page') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="sub_pt_home">
                                                Home Pages Only
                                                <small class="d-block text-muted fw-normal">Dedicated home subcategory showcase</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="sub_pt_other" value="other_pages" <?php echo (old('page_target') === 'other_pages') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="sub_pt_other">
                                                Other Pages Only
                                                <small class="d-block text-muted fw-normal">Standard catalog &amp; filter pages</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle text-success me-1"></i> Uske according hi subcategory options activate honge.
                                </small>
                            </div>

                            <div class="section-header-box bg-light border">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-folder-open me-2 text-success"></i>1. Sub Category Basic Information</h5>
                                <p class="text-muted small mb-0">Select Main category parent, name, slug URL, visibility status, and thumbnail image.</p>
                            </div>

                            <div class="row">
                                <!-- 1. Main category select Add > Box (Multiple category select support) -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">1</span>Main Category Select Add &gt; Box
                                    </label>
                                    <select name="parent_id" class="form-select form-select-lg mb-2" required>
                                        <option value="">-- Select Primary Main Category --</option>
                                        <?php foreach ($mainCategories as $main): ?>
                                            <option value="<?php echo $main['id']; ?>" <?php echo ($selectedParentId == $main['id'] || old('parent_id') == $main['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($main['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <!-- Multiple Main Category association support -->
                                    <div class="mt-2">
                                        <a class="small text-success text-decoration-none fw-semibold" data-bs-toggle="collapse" href="#multiParentCollapse" role="button">
                                            <i class="fas fa-plus-circle me-1"></i> + Map to Multiple Main Categories (Optional)
                                        </a>
                                        <div class="collapse mt-2 p-2 bg-light rounded border" id="multiParentCollapse">
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Also Associate with Additional Main Categories:</label>
                                            <div class="row g-2" style="max-height: 140px; overflow-y: auto;">
                                                <?php foreach ($mainCategories as $main): ?>
                                                    <div class="col-6">
                                                        <div class="form-check small">
                                                            <input class="form-check-input" type="checkbox" name="additional_parent_ids[]" value="<?php echo $main['id']; ?>" id="add_main_<?php echo $main['id']; ?>">
                                                            <label class="form-check-label" for="add_main_<?php echo $main['id']; ?>">
                                                                <?php echo htmlspecialchars($main['name']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Primary parent Main Category under which this subcategory belongs.</small>
                                </div>

                                <!-- 2. Sub Category name Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">2</span>Sub Category Name Add &gt; Box
                                    </label>
                                    <input type="text" class="form-control form-control-lg" name="name" required
                                           value="<?php echo old('name'); ?>" placeholder="e.g. Diarise &amp; Executive Notebooks">
                                </div>
                            </div>

                            <div class="row">
                                <!-- 3. Sub Category name slug Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-bold">
                                        <span class="field-num-badge">3</span>Sub Category Name Slug (URL)
                                    </label>
                                    <input type="text" class="form-control" name="slug" value="<?php echo old('slug'); ?>" placeholder="e.g. diaries-notebooks">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo oldSel('status', 'active', 'active'); ?>>Active (Visible)</option>
                                        <option value="inactive" <?php echo oldSel('status', 'inactive', 'active'); ?>>Inactive (Hidden)</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Catalog Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="<?php echo old('display_order', $nextOrder); ?>" min="0">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Subcategory Icon Class / Emoji</label>
                                    <input type="text" class="form-control" name="icon" value="<?php echo old('icon'); ?>" placeholder="fas fa-book">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Subcategory Thumbnail Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImg(this,'prevSubImg')">
                                    <img id="prevSubImg" class="image-preview-box mt-2">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Brief description of subcategory..."><?php echo old('description'); ?></textarea>
                            </div>
                        </div>

                        <!-- TAB 2: DESKTOP TOP MENU & HOME SETTINGS -->
                        <div class="tab-pane fade" id="tab-desktop" role="tabpanel">
                            <!-- TOP MENU SETTINGS -->
                            <div class="sub-card">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-bars me-2 text-primary"></i>Top Menu Inside Subcategory Settings</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border">Dropdown Navigation</span>
                                </div>

                                <div class="row">
                                    <!-- 4. Top menu category inside Status Show and Hide Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">4</span>Top Menu Category Inside Status
                                        </label>
                                        <select name="desktop_menu_status" class="form-select">
                                            <option value="show" <?php echo oldSel('desktop_menu_status', 'show', 'show'); ?>>Show in Top Menu Dropdown</option>
                                            <option value="hide" <?php echo oldSel('desktop_menu_status', 'hide'); ?>>Hide from Top Menu Dropdown</option>
                                        </select>
                                    </div>

                                    <!-- 5. Top menu category inside shorts order number Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">5</span>Top Menu Category Inside Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo old('desktop_menu_order', $nextOrder); ?>" min="0">
                                        <small class="text-muted">Column display position (1 to 5) in mega menu.</small>
                                    </div>

                                    <!-- 6. Top menu category inside text tags Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">6</span>Top Menu Category Inside Text Tag
                                        </label>
                                        <input type="text" class="form-control" name="desktop_menu_tag" value="<?php echo old('desktop_menu_tag'); ?>" placeholder="e.g. New, Hot, 20% Off">
                                        <small class="text-muted">Tag pill text shown next to subcategory link.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- 7. Top menu category text tags background colour select Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">7</span>Top Menu Text Tag Background Colour Select
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subTagBgPicker" value="<?php echo old('desktop_menu_tag_bg', '#e11d48'); ?>" onchange="document.getElementById('subTagBgText').value=this.value">
                                            <input type="text" class="form-control" name="desktop_menu_tag_bg" id="subTagBgText" value="<?php echo old('desktop_menu_tag_bg'); ?>" placeholder="#e11d48">
                                        </div>
                                        <small class="text-muted">Background color for badge pill in top menu.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- HOME PAGES SETTINGS -->
                            <div class="sub-card mt-3">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-home me-2 text-primary"></i>Show on Home Pages Category Settings</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border">Home Page Display</span>
                                </div>

                                <div class="row">
                                    <!-- 8. Show on home pages Category yes and no Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">8</span>Show on Home Pages Category
                                        </label>
                                        <select name="desktop_home_show" class="form-select">
                                            <option value="yes" <?php echo oldSel('desktop_home_show', 'yes'); ?>>Yes — Show on Desktop Home Page</option>
                                            <option value="no"  <?php echo oldSel('desktop_home_show', 'no', 'no'); ?>>No — Do Not Show on Home Page</option>
                                        </select>
                                    </div>

                                    <!-- 10. Home pages category shorts order number Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">10</span>Home Pages Category Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="desktop_home_order" value="<?php echo old('desktop_home_order', $nextOrder); ?>" min="0">
                                        <small class="text-muted">Sequence on desktop homepage.</small>
                                    </div>
                                </div>

                                <!-- 9. Show on home pages category select design Add > Box (4 types design) -->
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-th-large me-1 text-primary"></i> 9. Show on Home Pages Category Select Design (4 Types)</h6>
                                            <p class="text-muted small mb-0">Select the display layout format for this subcategory on desktop homepage.</p>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border">4 Home Designs</span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_home_design" value="home_design_1" id="sub_dhd_1" class="d-none"
                                                   <?php echo (old('desktop_home_design', 'home_design_1') === 'home_design_1') ? 'checked' : ''; ?>>
                                            <label for="sub_dhd_1" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div class="d-flex gap-1 h-100">
                                                        <div style="width:40%;background:#0d6efd;border-radius:2px;"></div>
                                                        <div class="flex-grow-1 bg-white border rounded p-1"></div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-primary bg-opacity-10 text-primary mb-1">Design 1</span>
                                                <div class="design-title">Mosaic Split Grid</div>
                                            </label>
                                        </div>

                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_home_design" value="home_design_2" id="sub_dhd_2" class="d-none"
                                                   <?php echo (old('desktop_home_design') === 'home_design_2') ? 'checked' : ''; ?>>
                                            <label for="sub_dhd_2" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe d-flex align-items-center justify-content-around">
                                                    <div style="width:18px;height:18px;border-radius:50%;background:#0284c7;"></div>
                                                    <div style="width:18px;height:18px;border-radius:50%;background:#0284c7;"></div>
                                                    <div style="width:18px;height:18px;border-radius:50%;background:#0284c7;"></div>
                                                </div>
                                                <span class="design-tag bg-info bg-opacity-10 text-info mb-1">Design 2</span>
                                                <div class="design-title">Horizontal Carousel</div>
                                            </label>
                                        </div>

                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_home_design" value="home_design_3" id="sub_dhd_3" class="d-none"
                                                   <?php echo (old('desktop_home_design') === 'home_design_3') ? 'checked' : ''; ?>>
                                            <label for="sub_dhd_3" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe d-flex flex-wrap gap-1 p-1">
                                                    <div style="width:45%;height:45%;background:#cbd5e1;border-radius:2px;"></div>
                                                    <div style="width:45%;height:45%;background:#cbd5e1;border-radius:2px;"></div>
                                                </div>
                                                <span class="design-tag bg-warning bg-opacity-10 text-dark mb-1">Design 3</span>
                                                <div class="design-title">Product Tile Grid</div>
                                            </label>
                                        </div>

                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_home_design" value="home_design_4" id="sub_dhd_4" class="d-none"
                                                   <?php echo (old('desktop_home_design') === 'home_design_4') ? 'checked' : ''; ?>>
                                            <label for="sub_dhd_4" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div style="height:35%;background:#1e293b;border-radius:2px;margin-bottom:2px;"></div>
                                                    <div style="height:55%;background:#e2e8f0;border-radius:2px;"></div>
                                                </div>
                                                <span class="design-tag bg-dark bg-opacity-10 text-dark mb-1">Design 4</span>
                                                <div class="design-title">Banner + Showcase</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- 12. Home pages Category images uploading Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">12</span>Home Pages Category Image
                                        </label>
                                        <input type="file" class="form-control" name="desktop_image" accept="image/*" onchange="previewImg(this,'prevSubHomeImg')">
                                        <small class="text-muted">Image shown in selected home design.</small>
                                        <img id="prevSubHomeImg" class="image-preview-box mt-2">
                                    </div>

                                    <!-- 11. Home page category background Colour Add and upload Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">11</span>Home Category Background Colour
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subBgColorPicker" value="<?php echo old('desktop_bg_color', '#ffffff'); ?>" onchange="document.getElementById('subBgColorText').value=this.value">
                                            <input type="text" class="form-control" name="desktop_bg_color" id="subBgColorText" value="<?php echo old('desktop_bg_color'); ?>" placeholder="#hex color">
                                        </div>
                                        <small class="text-muted">Custom background color.</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Upload Background Image</label>
                                        <input type="file" class="form-control" name="desktop_bg_image" accept="image/*" onchange="previewImg(this,'prevSubBgImg')">
                                        <small class="text-muted">Optional background texture.</small>
                                        <img id="prevSubBgImg" class="image-preview-box mt-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: MOBILE FORMAT TAB SECTION -->
                        <div class="tab-pane fade" id="tab-mobile" role="tabpanel">
                            <div class="section-header-box bg-info bg-opacity-10 border border-info border-opacity-25">
                                <h5 class="fw-bold mb-1 text-info"><i class="fas fa-mobile-alt me-2"></i>Mobile Format Tab Section</h5>
                                <p class="text-muted small mb-0">Manage mobile top bar, side drawer, mobile homepage designs (4 types), and product box formats (4/6/8).</p>
                            </div>

                            <div class="sub-card">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-bars me-2 text-info"></i>Mobile Menu &amp; Sidebar Settings</span>
                                    <span class="badge bg-info bg-opacity-10 text-info border">Mobile Navigation</span>
                                </div>

                                <div class="row">
                                    <!-- 1. Top bar inside Category status show and hide Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">1</span>Top Bar Inside Category Status
                                        </label>
                                        <select name="mobile_topbar_status" class="form-select">
                                            <option value="show" <?php echo oldSel('mobile_topbar_status', 'show', 'show'); ?>>Show in Mobile Top Bar</option>
                                            <option value="hide" <?php echo oldSel('mobile_topbar_status', 'hide'); ?>>Hide from Mobile Top Bar</option>
                                        </select>
                                    </div>

                                    <!-- 2. Top bar inside category short order number Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">2</span>Top Bar Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_topbar_order" value="<?php echo old('mobile_topbar_order', $nextOrder); ?>" min="0">
                                    </div>

                                    <!-- 3. Side category menu bar status show and hide Add > Add box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">3</span>Side Category Menu Bar Status
                                        </label>
                                        <select name="mobile_sidebar_status" class="form-select">
                                            <option value="yes" <?php echo oldSel('mobile_sidebar_status', 'yes', 'yes'); ?>>Yes — Show in Side Menu</option>
                                            <option value="no"  <?php echo oldSel('mobile_sidebar_status', 'no'); ?>>No — Hide from Side Menu</option>
                                        </select>
                                    </div>

                                    <!-- 4. Side category menu bar inside short order number Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">4</span>Side Menu Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_sidebar_order" value="<?php echo old('mobile_sidebar_order', $nextOrder); ?>" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="sub-card mt-3">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-home me-2 text-info"></i>Mobile Home Page Display Settings</span>
                                    <span class="badge bg-info bg-opacity-10 text-info border">Mobile Home Display</span>
                                </div>

                                <div class="row">
                                    <!-- 5. Show on home pages sub category design yes and no Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">5</span>Show on Mobile Home Pages
                                        </label>
                                        <select name="mobile_home_show" class="form-select">
                                            <option value="yes" <?php echo oldSel('mobile_home_show', 'yes'); ?>>Yes — Show on Mobile Home Page</option>
                                            <option value="no"  <?php echo oldSel('mobile_home_show', 'no', 'no'); ?>>No — Do Not Show on Home Page</option>
                                        </select>
                                    </div>

                                    <!-- 7. Home pages category select format design > Add like 4 image product box and 6 image product box and 8-image product box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">7</span>Mobile Select Format Design
                                        </label>
                                        <select name="mobile_home_format" class="form-select">
                                            <option value="4" <?php echo oldSel('mobile_home_format', '4', '4'); ?>>4 Image Product Box Grid (2x2)</option>
                                            <option value="6" <?php echo oldSel('mobile_home_format', '6'); ?>>6 Image Product Box Grid (2x3)</option>
                                            <option value="8" <?php echo oldSel('mobile_home_format', '8'); ?>>8 Image Product Box Grid (2x4)</option>
                                        </select>
                                    </div>

                                    <!-- 10. Home pages category shorts order number Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">10</span>Mobile Home Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_home_order" value="<?php echo old('mobile_home_order', $nextOrder); ?>" min="0">
                                    </div>
                                </div>

                                <!-- 6. Home pages category select deign Add > Box (4 type design) -->
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-th-large me-1 text-info"></i> 6. Mobile Category Select Design (4 Types)</h6>
                                        <span class="badge bg-info bg-opacity-10 text-info border">4 Mobile Designs</span>
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_1" id="sub_mhd_1" class="d-none"
                                                   <?php echo (old('mobile_home_design', 'home_design_1') === 'home_design_1') ? 'checked' : ''; ?>>
                                            <label for="sub_mhd_1" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 1</div>
                                                <p class="design-desc">Featured Split Grid</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_2" id="sub_mhd_2" class="d-none"
                                                   <?php echo (old('mobile_home_design') === 'home_design_2') ? 'checked' : ''; ?>>
                                            <label for="sub_mhd_2" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 2</div>
                                                <p class="design-desc">Icon Circle Carousel</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_3" id="sub_mhd_3" class="d-none"
                                                   <?php echo (old('mobile_home_design') === 'home_design_3') ? 'checked' : ''; ?>>
                                            <label for="sub_mhd_3" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 3</div>
                                                <p class="design-desc">Product Box Matrix</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_4" id="sub_mhd_4" class="d-none"
                                                   <?php echo (old('mobile_home_design') === 'home_design_4') ? 'checked' : ''; ?>>
                                            <label for="sub_mhd_4" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 4</div>
                                                <p class="design-desc">Mini Banner + Split</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- 8. Home pages category images uploading Add > Add box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">8</span>Mobile Category Image Upload
                                        </label>
                                        <input type="file" class="form-control" name="mobile_image" accept="image/*" onchange="previewImg(this,'prevSubMobHomeImg')">
                                        <img id="prevSubMobHomeImg" class="image-preview-box mt-2">
                                    </div>

                                    <!-- 9. Home category background Colour Add and upload Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">9</span>Mobile Background Colour
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subMobBgColorPicker" value="<?php echo old('mobile_bg_color', '#ffffff'); ?>" onchange="document.getElementById('subMobBgColorText').value=this.value">
                                            <input type="text" class="form-control" name="mobile_bg_color" id="subMobBgColorText" value="<?php echo old('mobile_bg_color'); ?>" placeholder="#hex color">
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Mobile Background Image</label>
                                        <input type="file" class="form-control" name="mobile_bg_image" accept="image/*" onchange="previewImg(this,'prevSubMobBgImg')">
                                        <img id="prevSubMobBgImg" class="image-preview-box mt-2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: SEO -->
                        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                            <div class="section-header-box bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-search me-2 text-warning"></i>4. SEO &amp; Metadata</h5>
                                <p class="text-muted small mb-0">Search engine meta title, description, and target keywords.</p>
                            </div>

                            <!-- 11. Meta Title name Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">11</span>Meta Title Name
                                </label>
                                <input type="text" class="form-control form-control-lg" name="meta_title" value="<?php echo old('meta_title'); ?>" placeholder="e.g. Corporate Diaries &amp; Notebooks Online | Printmont">
                            </div>

                            <!-- 13. Meta Keyword Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">13</span>Meta Keyword
                                </label>
                                <input type="text" class="form-control" name="meta_keywords" value="<?php echo old('meta_keywords'); ?>" placeholder="e.g. diaries, executive notebooks, corporate stationery">
                            </div>

                            <!-- 12. Meta Descriptions Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">12</span>Meta Descriptions
                                </label>
                                <textarea class="form-control" name="meta_description" rows="3" placeholder="Brief SEO snippet summary..."><?php echo old('meta_description'); ?></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                        <a href="view-categories.php?level=2" class="btn btn-secondary px-3"><i class="fas fa-times me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="fas fa-plus me-1"></i> Create Sub Category</button>
                    </div>

                </form>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
function previewImg(input, targetId) {
    const el = document.getElementById(targetId);
    if (!el) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            el.src = e.target.result;
            el.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        el.src = '';
        el.style.display = 'none';
    }
}
</script>
</body>
</html>
