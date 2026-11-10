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

// AJAX: get sub categories for selected main cat
if (isset($_GET['get_sub']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubCategories((int)$_GET['parent_id']));
    exit;
}

$selectedMainId = (int)($_POST['main_parent_id'] ?? $_GET['main_parent_id'] ?? ($mainCategories[0]['id'] ?? 0));
$subCategories  = $selectedMainId ? $controller->getSubCategories($selectedMainId) : [];
$selectedSubId  = (int)($_POST['sub_parent_id'] ?? $_GET['sub_parent_id'] ?? 0);
$nextOrder      = $selectedSubId ? $controller->getNextDisplayOrder(3, $selectedSubId) : 1;

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

    function uploadCatImg3($fileKey, $subdir = 'subsub') {
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

    $parentId = (int)($_POST['sub_parent_id'] ?? 0);
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
        'level'                => 3,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? $controller->getNextDisplayOrder(3, $parentId)),
        'image'                => uploadCatImg3('image', 'subsub'),

        // Tab 2: Desktop Top Menu & Home Settings
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        'desktop_menu_tag_bg'  => trim($_POST['desktop_menu_tag_bg'] ?? ''),
        'desktop_home_show'    => $_POST['desktop_home_show'] ?? 'no',
        'desktop_home_design'  => $_POST['desktop_home_design'] ?? 'home_design_1',
        'desktop_home_order'   => (int)($_POST['desktop_home_order'] ?? (int)($_POST['display_order'] ?? 0)),
        'desktop_bg_color'     => trim($_POST['desktop_bg_color'] ?? ''),
        'desktop_bg_image'     => uploadCatImg3('desktop_bg_image', 'bg'),
        'desktop_image'        => uploadCatImg3('desktop_image', 'home'),

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
        'mobile_bg_image'      => uploadCatImg3('mobile_bg_image', 'bg'),
        'mobile_image'         => uploadCatImg3('mobile_image', 'home'),

        // Tab 4: SEO Metadata
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Sub-sub category name is required.';
    } elseif (!$parentId) {
        $error = 'Please select a parent subcategory.';
    } elseif ($controller->checkCategoryExists($data['name'], $data['slug'])) {
        $error = 'A category with this name or slug already exists. Please choose a different name or slug.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Sub-sub category created successfully!';
            header('Location: view-categories.php?level=3');
            exit;
        } else {
            $error = 'Failed to create sub-sub category.';
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
    <title>3. Add New Sub Sub Category | Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .required-field::after { content: " *"; color: #dc3545; }
        .module-nav-pills { display: flex; gap: 8px; flex-wrap: wrap; background: #fff; padding: 6px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .module-nav-link { padding: 8px 14px; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; border-radius: 7px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .module-nav-link:hover { color: #0b53a1; background: #e0edff; }
        .module-nav-link.active { color: #fff; background: #0b53a1; }
        .nav-tabs .nav-link {
            font-size: 14px;
            font-weight: 600;
            padding: 12px 18px;
            color: #4b5563;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .nav-tabs .nav-link.active {
            color: #0b53a1 !important;
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
            background: #0b53a1;
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
            border-color: #0b53a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        input[type="radio"]:checked + .design-option-card {
            border-color: #0b53a1;
            background-color: #f0f7ff;
            box-shadow: 0 0 0 1px #0b53a1;
        }
        .design-option-card .check-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            color: #0b53a1;
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
                    <a href="add-sub-category.php" class="module-nav-link">
                        <i class="fas fa-folder-tree text-success"></i> 3. Sub Category
                    </a>
                    <a href="view-categories.php?level=2" class="module-nav-link">
                        <i class="fas fa-list text-secondary"></i> 4. Sub All Category
                    </a>
                    <a href="add-sub-sub-category.php" class="module-nav-link active">
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
                            <span class="badge bg-primary px-2 py-1">Manage Categories &gt; 3. Add New Sub Sub Category</span>
                        </div>
                        <h2 class="h3 fw-bold mb-1 mt-1">Add New Sub Sub Category</h2>
                        <p class="text-muted small mb-0">Select Main Category &amp; Subcategory parents, configure Top Menu leaf link tags, Desktop &amp; Mobile Home display designs, and SEO.</p>
                    </div>
                    <div>
                        <a href="view-categories.php?level=3" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Sub Sub All Category
                        </a>
                        <button type="submit" form="subSubCatForm" class="btn btn-primary px-4 fw-semibold">
                            <i class="fas fa-plus me-1"></i> Create Sub Sub Category
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="subSubCatForm">

                    <!-- SEPARATE TAB NAVIGATION -->
                    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                                <i class="fas fa-folder me-1 text-primary"></i> 1. Sub Sub Category Details
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
                            <div class="card p-3 mb-4 bg-light border" style="border-left: 4px solid #0b53a1 !important;">
                                <label class="form-label fw-bold text-dark mb-2">
                                    <i class="fas fa-bullseye text-primary me-1"></i> Target Page Selection: Select Home Pages and Other Pages
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="subsub_pt_both" value="both" <?php echo (old('page_target', 'both') === 'both') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="subsub_pt_both">
                                                Both Home Page &amp; Other Pages
                                                <small class="d-block text-muted fw-normal">Visible on Home &amp; all sub-sub category pages</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="subsub_pt_home" value="home_page" <?php echo (old('page_target') === 'home_page') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="subsub_pt_home">
                                                Home Pages Only
                                                <small class="d-block text-muted fw-normal">Dedicated home sub-sub category section</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check p-2 bg-white rounded border">
                                            <input class="form-check-input ms-1" type="radio" name="page_target" id="subsub_pt_other" value="other_pages" <?php echo (old('page_target') === 'other_pages') ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold text-dark ms-2" for="subsub_pt_other">
                                                Other Pages Only
                                                <small class="d-block text-muted fw-normal">Standard catalog &amp; filter pages</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Uske according hi sub-sub category options activate honge.
                                </small>
                            </div>

                            <div class="section-header-box bg-light border">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-folder-open me-2 text-primary"></i>1. Sub Sub Category Basic Information</h5>
                                <p class="text-muted small mb-0">Select Main &amp; Sub parent categories, item name, slug URL, and catalog options.</p>
                            </div>

                            <div class="row">
                                <!-- 1. Main category select Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">1</span>Main Category Select Add &gt; Box
                                    </label>
                                    <select name="main_parent_id" id="mainCatSelect" class="form-select form-select-lg" onchange="loadSubCats(this.value)">
                                        <option value="">-- Select Main Category --</option>
                                        <?php foreach ($mainCategories as $main): ?>
                                            <option value="<?php echo $main['id']; ?>" <?php echo ($selectedMainId == $main['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($main['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Top-level category to narrow down subcategories.</small>
                                </div>

                                <!-- 2. Sub category select Add > Box (Multiple category select support) -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">2</span>Sub Category Select Add &gt; Box
                                    </label>
                                    <select name="sub_parent_id" id="subCatSelect" class="form-select form-select-lg mb-2" required>
                                        <option value="">-- Select Primary Sub Category --</option>
                                        <?php foreach ($subCategories as $sub): ?>
                                            <option value="<?php echo $sub['id']; ?>" <?php echo ($selectedSubId == $sub['id'] || old('sub_parent_id') == $sub['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sub['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <!-- Multiple Sub Category association support -->
                                    <div class="mt-2">
                                        <a class="small text-primary text-decoration-none fw-semibold" data-bs-toggle="collapse" href="#multiSubCollapse" role="button">
                                            <i class="fas fa-plus-circle me-1"></i> + Map to Multiple Sub Categories (Optional)
                                        </a>
                                        <div class="collapse mt-2 p-2 bg-light rounded border" id="multiSubCollapse">
                                            <label class="small fw-bold text-muted text-uppercase mb-1">Also Associate with Additional Sub Categories:</label>
                                            <div class="row g-2" id="additionalSubsContainer" style="max-height: 140px; overflow-y: auto;">
                                                <?php foreach ($subCategories as $sub): ?>
                                                    <div class="col-6">
                                                        <div class="form-check small">
                                                            <input class="form-check-input" type="checkbox" name="additional_parent_ids[]" value="<?php echo $sub['id']; ?>" id="add_sub_<?php echo $sub['id']; ?>">
                                                            <label class="form-check-label" for="add_sub_<?php echo $sub['id']; ?>">
                                                                <?php echo htmlspecialchars($sub['name']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Direct parent subcategory under which this leaf item resides.</small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- 3. Sub Sub Category Name Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">3</span>Sub Sub Category Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" name="name" required
                                           value="<?php echo old('name'); ?>" placeholder="e.g. Leather Diaries, Spiral Notebooks">
                                </div>

                                <!-- 4. Sub Sub Category name slug Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-bold">
                                        <span class="field-num-badge">4</span>Sub Sub Category Name Slug (URL)
                                    </label>
                                    <input type="text" class="form-control" name="slug" value="<?php echo old('slug'); ?>" placeholder="e.g. leather-diaries">
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo oldSel('status', 'active', 'active'); ?>>Active (Visible)</option>
                                        <option value="inactive" <?php echo oldSel('status', 'inactive', 'active'); ?>>Inactive (Hidden)</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">Catalog Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="<?php echo old('display_order', $nextOrder); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">Icon Class / Emoji</label>
                                    <input type="text" class="form-control" name="icon" value="<?php echo old('icon'); ?>" placeholder="fas fa-tag">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Sub-Sub Category Thumbnail Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImg(this,'prevSubSubImg')">
                                    <img id="prevSubSubImg" class="image-preview-box mt-2">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control" name="description" rows="2" placeholder="Brief description..."><?php echo old('description'); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: DESKTOP TOP MENU & HOME SETTINGS -->
                        <div class="tab-pane fade" id="tab-desktop" role="tabpanel">
                            <!-- TOP MENU SETTINGS -->
                            <div class="sub-card">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-bars me-2 text-primary"></i>Top Menu Inside Sub-Sub Category Settings</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border">Mega Menu Links</span>
                                </div>

                                <div class="row">
                                    <!-- 5. Top menu inside sub sub category Status Show and Hide Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">5</span>Top Menu Inside Status
                                        </label>
                                        <select name="desktop_menu_status" class="form-select">
                                            <option value="show" <?php echo oldSel('desktop_menu_status', 'show', 'show'); ?>>Show in Mega Menu Links</option>
                                            <option value="hide" <?php echo oldSel('desktop_menu_status', 'hide'); ?>>Hide from Mega Menu Links</option>
                                        </select>
                                    </div>

                                    <!-- 6. Top menu inside sub sub category shorts order number Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">6</span>Top Menu Inside Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo old('desktop_menu_order', $nextOrder); ?>" min="0">
                                        <small class="text-muted">Vertical ordering under its subcategory column.</small>
                                    </div>

                                    <!-- 7. Top menu inside sub sub category text and tag Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">7</span>Top Menu Inside Text Tag
                                        </label>
                                        <input type="text" class="form-control" name="desktop_menu_tag" value="<?php echo old('desktop_menu_tag'); ?>" placeholder="e.g. New, Hot, 20% Off">
                                        <small class="text-muted">Small badge shown next to the link.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- 8. Top menu inside sub sub category text tags background colour select Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">8</span>Top Menu Text Tag Background Colour Select
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subSubTagBgPicker" value="<?php echo old('desktop_menu_tag_bg', '#e11d48'); ?>" onchange="document.getElementById('subSubTagBgText').value=this.value">
                                            <input type="text" class="form-control" name="desktop_menu_tag_bg" id="subSubTagBgText" value="<?php echo old('desktop_menu_tag_bg'); ?>" placeholder="#e11d48">
                                        </div>
                                        <small class="text-muted">Custom background color for link badge.</small>
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
                                    <!-- 9. Show on home pages design yes and no Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">9</span>Show on Home Pages Design
                                        </label>
                                        <select name="desktop_home_show" class="form-select">
                                            <option value="yes" <?php echo oldSel('desktop_home_show', 'yes'); ?>>Yes — Show on Desktop Home Page</option>
                                            <option value="no"  <?php echo oldSel('desktop_home_show', 'no', 'no'); ?>>No — Do Not Show on Home Page</option>
                                        </select>
                                    </div>

                                    <!-- 13. Home pages sub sub category shorts order number Add > Box -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">13</span>Home Pages Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="desktop_home_order" value="<?php echo old('desktop_home_order', $nextOrder); ?>" min="0">
                                    </div>
                                </div>

                                <!-- 10. Show on home pages sub sub category select design Add > Box (4 types design) -->
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-th-large me-1 text-primary"></i> 10. Show on Home Pages Select Design (4 Types)</h6>
                                            <p class="text-muted small mb-0">Select layout design format for this sub-sub category on desktop homepage.</p>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border">4 Home Designs</span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_home_design" value="home_design_1" id="subsub_dhd_1" class="d-none"
                                                   <?php echo (old('desktop_home_design', 'home_design_1') === 'home_design_1') ? 'checked' : ''; ?>>
                                            <label for="subsub_dhd_1" class="design-option-card">
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
                                            <input type="radio" name="desktop_home_design" value="home_design_2" id="subsub_dhd_2" class="d-none"
                                                   <?php echo (old('desktop_home_design', 'home_design_2') === 'home_design_2') ? 'checked' : ''; ?>>
                                            <label for="subsub_dhd_2" class="design-option-card">
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
                                            <input type="radio" name="desktop_home_design" value="home_design_3" id="subsub_dhd_3" class="d-none"
                                                   <?php echo (old('desktop_home_design', 'home_design_3') === 'home_design_3') ? 'checked' : ''; ?>>
                                            <label for="subsub_dhd_3" class="design-option-card">
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
                                            <input type="radio" name="desktop_home_design" value="home_design_4" id="subsub_dhd_4" class="d-none"
                                                   <?php echo (old('desktop_home_design', 'home_design_4') === 'home_design_4') ? 'checked' : ''; ?>>
                                            <label for="subsub_dhd_4" class="design-option-card">
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
                                    <!-- 11. Home pages sub sub Category images uploading Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">11</span>Home Pages Category Image
                                        </label>
                                        <input type="file" class="form-control" name="desktop_image" accept="image/*" onchange="previewImg(this,'prevSubSubHomeImg')">
                                        <small class="text-muted">Featured/card image displayed in home design.</small>
                                        <img id="prevSubSubHomeImg" class="image-preview-box mt-2">
                                    </div>

                                    <!-- 12. Home pages category background Colour Add and upload Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">12</span>Home Category Background Colour
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subSubBgColorPicker" value="<?php echo old('desktop_bg_color', '#ffffff'); ?>" onchange="document.getElementById('subSubBgColorText').value=this.value">
                                            <input type="text" class="form-control" name="desktop_bg_color" id="subSubBgColorText" value="<?php echo old('desktop_bg_color'); ?>" placeholder="#hex color">
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Upload Background Image</label>
                                        <input type="file" class="form-control" name="desktop_bg_image" accept="image/*" onchange="previewImg(this,'prevSubSubBgImg')">
                                        <img id="prevSubSubBgImg" class="image-preview-box mt-2">
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
                                    <!-- 1. Top bar inside menu status show and hide Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">1</span>Top Bar Inside Status
                                        </label>
                                        <select name="mobile_topbar_status" class="form-select">
                                            <option value="show" <?php echo oldSel('mobile_topbar_status', 'show', 'show'); ?>>Show in Mobile Top Bar</option>
                                            <option value="hide" <?php echo oldSel('mobile_topbar_status', 'hide'); ?>>Hide from Mobile Top Bar</option>
                                        </select>
                                    </div>

                                    <!-- 2. Top bar inside short order number Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">2</span>Top Bar Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_topbar_order" value="<?php echo old('mobile_topbar_order', $nextOrder); ?>" min="0">
                                    </div>

                                    <!-- 3. Side category menu bar status show and hide add > Add box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">3</span>Side Category Menu Status
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
                                    <!-- 5. Show on home pages design yes and no Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">5</span>Show on Mobile Home Pages
                                        </label>
                                        <select name="mobile_home_show" class="form-select">
                                            <option value="yes" <?php echo oldSel('mobile_home_show', 'yes'); ?>>Yes — Show on Mobile Home Page</option>
                                            <option value="no"  <?php echo oldSel('mobile_home_show', 'no', 'no'); ?>>No — Do Not Show on Home Page</option>
                                        </select>
                                    </div>

                                    <!-- 7. home pages category select format like 4 image product box and 6 image product box and 8 image product boxes -->
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
                                            <input type="radio" name="mobile_home_design" value="home_design_1" id="subsub_mhd_1" class="d-none"
                                                   <?php echo (old('mobile_home_design', 'home_design_1') === 'home_design_1') ? 'checked' : ''; ?>>
                                            <label for="subsub_mhd_1" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 1</div>
                                                <p class="design-desc">Featured Split Grid</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_2" id="subsub_mhd_2" class="d-none"
                                                   <?php echo (old('mobile_home_design', 'home_design_2') === 'home_design_2') ? 'checked' : ''; ?>>
                                            <label for="subsub_mhd_2" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 2</div>
                                                <p class="design-desc">Icon Circle Carousel</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_3" id="subsub_mhd_3" class="d-none"
                                                   <?php echo (old('mobile_home_design', 'home_design_3') === 'home_design_3') ? 'checked' : ''; ?>>
                                            <label for="subsub_mhd_3" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 3</div>
                                                <p class="design-desc">Product Box Matrix</p>
                                            </label>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="radio" name="mobile_home_design" value="home_design_4" id="subsub_mhd_4" class="d-none"
                                                   <?php echo (old('mobile_home_design', 'home_design_4') === 'home_design_4') ? 'checked' : ''; ?>>
                                            <label for="subsub_mhd_4" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="design-title">Design 4</div>
                                                <p class="design-desc">Mini Banner + Split</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- 9. Home pages category image uploading Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">9</span>Mobile Category Image Upload
                                        </label>
                                        <input type="file" class="form-control" name="mobile_image" accept="image/*" onchange="previewImg(this,'prevSubSubMobHomeImg')">
                                        <img id="prevSubSubMobHomeImg" class="image-preview-box mt-2">
                                    </div>

                                    <!-- 8. Home pages category background Colour Add and upload Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">8</span>Mobile Background Colour
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="subSubMobBgColorPicker" value="<?php echo old('mobile_bg_color', '#ffffff'); ?>" onchange="document.getElementById('subSubMobBgColorText').value=this.value">
                                            <input type="text" class="form-control" name="mobile_bg_color" id="subSubMobBgColorText" value="<?php echo old('mobile_bg_color'); ?>" placeholder="#hex color">
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Mobile Background Image</label>
                                        <input type="file" class="form-control" name="mobile_bg_image" accept="image/*" onchange="previewImg(this,'prevSubSubMobBgImg')">
                                        <img id="prevSubSubMobBgImg" class="image-preview-box mt-2">
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
                                <input type="text" class="form-control form-control-lg" name="meta_title" value="<?php echo old('meta_title'); ?>" placeholder="e.g. Leather Diaries &amp; Planners Online | Printmont">
                            </div>

                            <!-- 13. Meta Keyword Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">13</span>Meta Keyword
                                </label>
                                <input type="text" class="form-control" name="meta_keywords" value="<?php echo old('meta_keywords'); ?>" placeholder="e.g. leather diaries, planners, customized notebooks">
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
                        <a href="view-categories.php?level=3" class="btn btn-secondary px-3"><i class="fas fa-times me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="fas fa-plus me-1"></i> Create Sub Sub Category</button>
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

function loadSubCats(mainId) {
    const subSelect = document.getElementById('subCatSelect');
    subSelect.innerHTML = '<option value="">Loading subcategories...</option>';
    if (!mainId) {
        subSelect.innerHTML = '<option value="">-- Select Main Category First --</option>';
        return;
    }
    fetch('add-sub-sub-category.php?get_sub=1&parent_id=' + encodeURIComponent(mainId))
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- Select Sub Category --</option>';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(item => {
                    html += `<option value="${item.id}">${item.name}</option>`;
                });
            } else {
                html += '<option value="" disabled>No subcategories found for this main category</option>';
            }
            subSelect.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            subSelect.innerHTML = '<option value="">Error loading subcategories</option>';
        });
}
</script>
</body>
</html>
