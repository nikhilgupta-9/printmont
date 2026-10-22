<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$error   = '';
$success = '';
$nextOrder = $controller->getNextDisplayOrder(1);

function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}
function oldSel($key, $value, $default = '') {
    return (($_POST[$key] ?? $default) === $value) ? 'selected' : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImage($fileKey, $subdir = '') {
        if (empty($_FILES[$fileKey]['name'])) return '';
        $file    = $_FILES[$fileKey];
        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $file['size'] > 5 * 1024 * 1024) return '';
        $subdir = trim($subdir, '/');
        $prefix = $subdir !== '' ? "{$subdir}/" : '';
        $dir = __DIR__ . "/uploads/category/{$prefix}";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . $filename) ? "uploads/category/{$prefix}{$filename}" : '';
    }

    $data = [
        // 1 & 2: Category Details
        'name'                 => $name,
        'slug'                 => $slug,
        'description'          => trim($_POST['description'] ?? ''),
        'parent_id'            => 0,
        'level'                => 1,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? $nextOrder),
        'image'                => uploadCatImage('image', ''),

        // Desktop Top Menu Settings
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? $nextOrder),
        'desktop_menu_view'    => $_POST['desktop_menu_view'] ?? 'yes',
        'desktop_menu_design'  => $_POST['desktop_menu_design'] ?? 'design_dm1',
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        'desktop_menu_image'   => uploadCatImage('desktop_menu_image', 'menu'),

        // Mobile Top Bar & Drawer Settings
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? $nextOrder),
        'mobile_menu_view'     => $_POST['mobile_menu_view'] ?? 'yes',
        'mobile_menu_design'   => $_POST['mobile_menu_design'] ?? 'design_mm1',
        'mobile_sidebar_order' => (int)($_POST['mobile_sidebar_order'] ?? 0),

        // SEO & Metadata
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Category name is required.';
    } elseif ($controller->checkCategoryExists($data['name'], $data['slug'])) {
        $error = 'A category with this name or slug already exists. Please choose a different name or slug.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Main category created successfully!';
            header('Location: view-categories.php');
            exit;
        } else {
            $error = 'Failed to create category. Please try again.';
        }
    }
}

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>1. Add New Main Category | Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 1 !important; font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .required-field::after { content: " *"; color: #dc3545; }
        .nav-tabs .nav-link {
            font-size: 14px;
            font-weight: 600;
            padding: 12px 20px;
            color: #4b5563;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            border: 1px solid transparent;
        }
        .nav-tabs .nav-link:hover {
            color: #0d6efd;
            background-color: #f8fafc;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd !important;
            border-color: #dee2e6 #dee2e6 #ffffff !important;
            background-color: #ffffff !important;
            font-weight: 700;
        }
        .tab-content {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 26px;
        }
        .section-header-box {
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 22px;
        }
        .sub-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            background: #ffffff;
            margin-bottom: 20px;
        }
        .sub-card-title {
            font-size: 14.5px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .design-option-card { 
            border: 2px solid #e2e8f0; 
            border-radius: 10px; 
            padding: 12px; 
            text-align: left; 
            transition: all .25s ease-in-out; 
            cursor: pointer; 
            background: #ffffff;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .design-option-card:hover { 
            border-color: #0d6efd; 
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(13, 110, 253, 0.12);
        }
        input[type=radio]:checked + .design-option-card { 
            border-color: #0d6efd; 
            background: #f0f7ff; 
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }
        .design-option-card .check-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            font-size: 16px;
            color: #0d6efd;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            z-index: 2;
        }
        input[type=radio]:checked + .design-option-card .check-badge {
            display: block;
        }
        .design-tag {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .design-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
        }
        .design-desc {
            font-size: 11px;
            color: #64748b;
            line-height: 1.35;
            margin-top: auto;
            margin-bottom: 0;
        }
        .image-preview-box {
            max-height: 130px;
            border-radius: 6px;
            margin-top: 8px;
            display: none;
            border: 1px solid #dee2e6;
        }
        .layout-wireframe {
            height: 70px;
            background: #f8fafc;
            border-radius: 6px;
            padding: 6px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            position: relative;
        }
        .field-num-badge {
            display: inline-block;
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            margin-right: 5px;
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

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary px-2 py-1">Manage Categories &gt; 1. Add New Main Category</span>
                        </div>
                        <h2 class="h3 fw-bold mb-1 mt-1">Add New Main Category</h2>
                        <p class="text-muted small mb-0">Configure Category Name, Desktop Top Menu Settings, Mobile Topbar &amp; Menu Settings, and SEO Metadata.</p>
                    </div>
                    <div>
                        <a href="view-categories.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Main All Category
                        </a>
                        <button type="submit" form="categoryAddForm" class="btn btn-primary px-4 fw-semibold">
                            <i class="fas fa-plus me-1"></i> Create Category
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="categoryAddForm">

                    <!-- TAB NAVIGATION -->
                    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-basic-btn" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button" role="tab">
                                <i class="fas fa-folder me-1 text-primary"></i> 1. Category Details
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-desktop-btn" data-bs-toggle="tab" data-bs-target="#tab-desktop" type="button" role="tab">
                                <i class="fas fa-desktop me-1 text-primary"></i> 2. Desktop Top Menu Settings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-mobile-btn" data-bs-toggle="tab" data-bs-target="#tab-mobile" type="button" role="tab">
                                <i class="fas fa-mobile-alt me-1 text-info"></i> 3. Mobile Top Bar &amp; Drawer Settings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-seo-btn" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                                <i class="fas fa-search me-1 text-warning"></i> 4. SEO &amp; Metadata
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content shadow-sm mb-4">

                        <!-- TAB 1: CATEGORY BASIC DETAILS -->
                        <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                            <div class="section-header-box bg-light border">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>1. Category Basic Information</h5>
                                <p class="text-muted small mb-0">Primary category title, auto-slug URL path, visibility status, image, and catalog options.</p>
                            </div>

                            <div class="row">
                                <!-- Category Name Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">1</span>Category Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" name="name" id="catName" required
                                           value="<?php echo old('name'); ?>" placeholder="e.g. Corporate Gifts">
                                    <small class="text-muted">Display title used across the entire store.</small>
                                </div>
                                <!-- Category Name Slug Add > Box -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field fw-bold">
                                        <span class="field-num-badge">2</span>Category Name Slug
                                    </label>
                                    <input type="text" class="form-control form-control-lg" name="slug" id="catSlug"
                                           value="<?php echo old('slug'); ?>" placeholder="corporate-gifts">
                                    <small class="text-muted">SEO URL path: <code>/category/corporate-gifts</code></small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Category Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active"   <?php echo oldSel('status','active','active'); ?>>Active (Visible)</option>
                                        <option value="inactive" <?php echo oldSel('status','inactive'); ?>>Inactive (Hidden)</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Is Featured?</label>
                                    <select name="is_featured" class="form-select">
                                        <option value="0" <?php echo oldSel('is_featured','0','0'); ?>>No</option>
                                        <option value="1" <?php echo oldSel('is_featured','1'); ?>>Yes (Featured Category)</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">General Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="<?php echo old('display_order', $nextOrder); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label fw-semibold">Icon Class <small class="text-muted">(Optional)</small></label>
                                    <input type="text" class="form-control" name="icon" value="<?php echo old('icon'); ?>" placeholder="fas fa-gift">
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-8">
                                    <label class="form-label fw-semibold">Category Description</label>
                                    <textarea class="form-control" name="description" rows="4" placeholder="Brief category summary..."><?php echo old('description'); ?></textarea>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label fw-semibold">Category Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImg(this,'prevCatImg')">
                                    <small class="text-muted">Category catalog/banner thumbnail.</small>
                                    <img id="prevCatImg" class="image-preview-box mt-2">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: DESKTOP TOP MENU CONFIGURATION -->
                        <div class="tab-pane fade" id="tab-desktop" role="tabpanel">
                            <div class="section-header-box bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                <h5 class="fw-bold mb-1 text-primary"><i class="fas fa-desktop me-2"></i>Desktop Top Menu Settings</h5>
                                <p class="text-muted small mb-0">Configure top navigation menu visibility, sorting sequence, mega dropdown design, badge tags, and showcase banners.</p>
                            </div>

                            <div class="sub-card">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-bars me-2 text-primary"></i>Desktop Top Menu Settings</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border">Top Menu</span>
                                </div>

                                <div class="row">
                                    <!-- Desktop Top menu category status: Show and Hide Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">3</span>Desktop Top Menu Category Status
                                        </label>
                                        <select name="desktop_menu_status" class="form-select">
                                            <option value="show" <?php echo oldSel('desktop_menu_status','show','show'); ?>>Show in Desktop Top Menu</option>
                                            <option value="hide" <?php echo oldSel('desktop_menu_status','hide'); ?>>Hide from Desktop Top Menu</option>
                                        </select>
                                    </div>

                                    <!-- Desktop Top menu category sort order number Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">4</span>Desktop Top Menu Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo old('desktop_menu_order', $nextOrder); ?>" min="0">
                                        <small class="text-muted">Left-to-right sequence in top navbar.</small>
                                    </div>

                                    <!-- Desktop Top menu main category view design selects yes and no Add > Box -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">5</span>Desktop Top Menu View Design Select
                                        </label>
                                        <select name="desktop_menu_view" class="form-select" id="desktopMenuViewSelect">
                                            <option value="yes" <?php echo oldSel('desktop_menu_view','yes','yes'); ?>>Yes — Enable Mega Menu View Design</option>
                                            <option value="no"  <?php echo oldSel('desktop_menu_view','no'); ?>>No — Direct Text Link (No Dropdown)</option>
                                        </select>
                                        <small class="text-muted">Top menu main category ka view design enable/disable.</small>
                                    </div>
                                </div>

                                <!-- Desktop Menu View Design Choices (Conditioned on Yes) -->
                                <div id="desktopMenuViewDesignBlock" class="p-3 bg-light rounded-3 border mt-2">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-palette me-1 text-primary"></i> Top Menu Main Category View Designs</h6>
                                            <p class="text-muted small mb-0">Select which dropdown layout format is rendered when users hover this category in desktop top menu.</p>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border">4 Dropdown Designs</span>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <!-- DM Design 1 -->
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_menu_design" value="design_dm1" id="dmd_design_dm1" class="d-none"
                                                   <?php echo (old('desktop_menu_design', 'design_dm1') === 'design_dm1') ? 'checked' : ''; ?>>
                                            <label for="dmd_design_dm1" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div style="height:7px;background:#0d6efd;border-radius:2px;margin-bottom:3px;"></div>
                                                    <div class="d-flex gap-1 flex-grow-1 overflow-hidden" style="background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                        <div style="flex:1;background:#fff;border-right:1px solid #e2e8f0;padding:1px;">
                                                            <div style="height:3px;width:70%;background:#0d6efd;margin-bottom:2px;"></div>
                                                            <div style="height:2px;width:50%;background:#cbd5e1;"></div>
                                                        </div>
                                                        <div style="flex:1;background:#f8fafc;border-right:1px solid #e2e8f0;padding:1px;">
                                                            <div style="height:3px;width:70%;background:#0d6efd;margin-bottom:2px;"></div>
                                                            <div style="height:2px;width:50%;background:#cbd5e1;"></div>
                                                        </div>
                                                        <div style="width:20px;background:#e0f2fe;border-radius:2px;display:flex;align-items:center;justify-content:center;font-size:7px;color:#0284c7;font-weight:700;">IMG</div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-primary bg-opacity-10 text-primary mb-1">Mega Menu</span>
                                                <div class="design-title">5-Column Mega Menu</div>
                                                <p class="design-desc">Multi-column layout with right showcase poster banner.</p>
                                            </label>
                                        </div>

                                        <!-- DM Design 2 -->
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_menu_design" value="design_dm2" id="dmd_design_dm2" class="d-none"
                                                   <?php echo (old('desktop_menu_design') === 'design_dm2') ? 'checked' : ''; ?>>
                                            <label for="dmd_design_dm2" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div class="d-flex gap-1 h-100">
                                                        <div style="width:40%;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:3px;background:#0d6efd;margin-bottom:2px;"></div>
                                                            <div style="height:2px;background:#cbd5e1;"></div>
                                                        </div>
                                                        <div style="flex-grow:1;background:#f0f9ff;border:1px solid #bae6fd;border-radius:2px;padding:3px;">
                                                            <div style="height:3px;width:60%;background:#0284c7;margin-bottom:2px;"></div>
                                                            <div style="height:2px;width:80%;background:#94a3b8;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-info bg-opacity-10 text-info mb-1">Sidebar Flyout</span>
                                                <div class="design-title">2-Column Flyout</div>
                                                <p class="design-desc">Left column subcategories with hover flyout on right.</p>
                                            </label>
                                        </div>

                                        <!-- DM Design 3 -->
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_menu_design" value="design_dm3" id="dmd_design_dm3" class="d-none"
                                                   <?php echo (old('desktop_menu_design') === 'design_dm3') ? 'checked' : ''; ?>>
                                            <label for="dmd_design_dm3" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div class="d-flex flex-wrap gap-1 h-100 justify-content-between align-content-between">
                                                        <div style="width:23%;height:45%;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:12px;background:#e2e8f0;margin-bottom:1px;"></div>
                                                            <div style="height:2px;background:#d97706;"></div>
                                                        </div>
                                                        <div style="width:23%;height:45%;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:12px;background:#e2e8f0;margin-bottom:1px;"></div>
                                                            <div style="height:2px;background:#d97706;"></div>
                                                        </div>
                                                        <div style="width:23%;height:45%;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:12px;background:#e2e8f0;margin-bottom:1px;"></div>
                                                            <div style="height:2px;background:#d97706;"></div>
                                                        </div>
                                                        <div style="width:23%;height:45%;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:12px;background:#e2e8f0;margin-bottom:1px;"></div>
                                                            <div style="height:2px;background:#d97706;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-warning bg-opacity-10 text-dark mb-1">Visual Tiles</span>
                                                <div class="design-title">Subcategory Photo Grid</div>
                                                <p class="design-desc">Visual grid of category photos and icons.</p>
                                            </label>
                                        </div>

                                        <!-- DM Design 4 -->
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <input type="radio" name="desktop_menu_design" value="design_dm4" id="dmd_design_dm4" class="d-none"
                                                   <?php echo (old('desktop_menu_design') === 'design_dm4') ? 'checked' : ''; ?>>
                                            <label for="dmd_design_dm4" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div class="d-flex gap-1 h-100">
                                                        <div style="flex:1;background:#fff;border:1px solid #cbd5e1;border-radius:2px;padding:2px;">
                                                            <div style="height:3px;background:#0d6efd;margin-bottom:2px;"></div>
                                                            <div style="height:2px;width:70%;background:#94a3b8;"></div>
                                                        </div>
                                                        <div style="width:40%;background:linear-gradient(135deg, #475569, #1e293b);border-radius:2px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:7px;font-weight:700;">
                                                            PROMO
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-dark bg-opacity-10 text-dark mb-1">Feature Strip</span>
                                                <div class="design-title">Links + Feature Banner</div>
                                                <p class="design-desc">Quick links alongside a custom promotional card.</p>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">Desktop Menu Badge Tag (Pill)</label>
                                            <input type="text" class="form-control" name="desktop_menu_tag" value="<?php echo old('desktop_menu_tag'); ?>" placeholder="e.g. New, Hot, 20% Off">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">Dropdown Showcase Poster Image</label>
                                            <input type="file" class="form-control" name="desktop_menu_image" accept="image/*" onchange="previewImg(this,'prevMegaImg')">
                                            <img id="prevMegaImg" class="image-preview-box mt-2">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- TAB 3: MOBILE TOP BAR & DRAWER CONFIGURATION -->
                        <div class="tab-pane fade" id="tab-mobile" role="tabpanel">
                            <div class="section-header-box bg-info bg-opacity-10 border border-info border-opacity-25">
                                <h5 class="fw-bold mb-1 text-info"><i class="fas fa-mobile-alt me-2"></i>Mobile Top Bar &amp; Drawer Settings</h5>
                                <p class="text-muted small mb-0">Manage mobile top icon row display, sort sequence, drawer menu view designs, and sidebar positioning.</p>
                            </div>

                            <div class="sub-card">
                                <div class="sub-card-title">
                                    <span><i class="fas fa-bars me-2 text-info"></i>Mobile Top Bar &amp; Menu Drawer Settings</span>
                                    <span class="badge bg-info bg-opacity-10 text-info border">Mobile Navigation</span>
                                </div>

                                <div class="row">
                                    <!-- Mobile top bar status show and hide Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">6</span>Mobile Top Bar Status
                                        </label>
                                        <select name="mobile_topbar_status" class="form-select">
                                            <option value="show" <?php echo oldSel('mobile_topbar_status','show','show'); ?>>Show in Mobile Top Bar</option>
                                            <option value="hide" <?php echo oldSel('mobile_topbar_status','hide'); ?>>Hide from Mobile Top Bar</option>
                                        </select>
                                    </div>

                                    <!-- Mobile Top bar short order number Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">7</span>Mobile Top Bar Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_topbar_order" value="<?php echo old('mobile_topbar_order', $nextOrder); ?>" min="0">
                                        <small class="text-muted">Order sequence in mobile top icon row.</small>
                                    </div>

                                    <!-- Mobile menu side-bar short order number Add > Box -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">8</span>Mobile Menu Sidebar Sort Order Number
                                        </label>
                                        <input type="number" class="form-control" name="mobile_sidebar_order" value="<?php echo old('mobile_sidebar_order', 0); ?>" min="0">
                                        <small class="text-muted">Vertical sequence in mobile side drawer.</small>
                                    </div>

                                    <!-- Mobile Top menu main category view design selects yes and no Add > Box (2 type design need) -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-bold">
                                            <span class="field-num-badge">9</span>Mobile Top Menu View Design Select
                                        </label>
                                        <select name="mobile_menu_view" class="form-select" id="mobileMenuViewSelect">
                                            <option value="yes" <?php echo oldSel('mobile_menu_view','yes','yes'); ?>>Yes — Enable Mobile View Design (2 Types)</option>
                                            <option value="no"  <?php echo oldSel('mobile_menu_view','no'); ?>>No — Standard List Item</option>
                                        </select>
                                        <small class="text-muted">(2 type design needed for mobile menu)</small>
                                    </div>
                                </div>

                                <!-- Mobile Menu 2-Type Design Choices (Conditioned on Yes) -->
                                <div id="mobileMenuViewDesignBlock" class="p-3 bg-light rounded-3 border mt-2">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-layer-group me-1 text-info"></i> Mobile Top Menu View Designs (2 Types Required)</h6>
                                            <p class="text-muted small mb-0">Select how subcategories are displayed when users tap this category in mobile drawer/top menu.</p>
                                        </div>
                                        <span class="badge bg-info bg-opacity-10 text-info border">2 Menu Designs</span>
                                    </div>

                                    <div class="row g-3">
                                        <!-- Mobile Menu Design 1: Classic Tree Accordion -->
                                        <div class="col-12 col-sm-6">
                                            <input type="radio" name="mobile_menu_design" value="design_mm1" id="mmd_design_mm1" class="d-none"
                                                   <?php echo (old('mobile_menu_design', 'design_mm1') === 'design_mm1') ? 'checked' : ''; ?>>
                                            <label for="mmd_design_mm1" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div style="background:#fff;border:1px solid #cbd5e1;border-radius:3px;padding:4px;height:100%;display:flex;flex-direction:column;justify-content:space-between;">
                                                        <div class="d-flex justify-content-between align-items-center"><div style="height:4px;width:60%;background:#0d6efd;"></div><div style="font-size:8px;color:#94a3b8;">▼</div></div>
                                                        <div style="height:3px;width:75%;background:#cbd5e1;margin-left:8px;"></div>
                                                        <div style="height:3px;width:65%;background:#cbd5e1;margin-left:8px;"></div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-primary bg-opacity-10 text-primary mb-1">Type 1 Design</span>
                                                <div class="design-title">Classic Tree Accordion View</div>
                                                <p class="design-desc">Collapsible category row with clean expandable subcategory lists underneath.</p>
                                            </label>
                                        </div>

                                        <!-- Mobile Menu Design 2: Icon Thumbnail Drawer Grid -->
                                        <div class="col-12 col-sm-6">
                                            <input type="radio" name="mobile_menu_design" value="design_mm2" id="mmd_design_mm2" class="d-none"
                                                   <?php echo (old('mobile_menu_design') === 'design_mm2') ? 'checked' : ''; ?>>
                                            <label for="mmd_design_mm2" class="design-option-card">
                                                <span class="check-badge"><i class="fas fa-check-circle"></i></span>
                                                <div class="layout-wireframe">
                                                    <div style="background:#fff;border:1px solid #cbd5e1;border-radius:3px;padding:4px;height:100%;display:flex;flex-direction:column;justify-content:space-around;">
                                                        <div class="d-flex align-items-center gap-2"><div style="height:10px;width:10px;background:#38bdf8;border-radius:50%;"></div><div style="height:4px;width:60%;background:#0f172a;"></div></div>
                                                        <div class="d-flex align-items-center gap-2"><div style="height:10px;width:10px;background:#38bdf8;border-radius:50%;"></div><div style="height:4px;width:50%;background:#0f172a;"></div></div>
                                                    </div>
                                                </div>
                                                <span class="design-tag bg-info bg-opacity-10 text-info mb-1">Type 2 Design</span>
                                                <div class="design-title">Icon Thumbnail Drawer View</div>
                                                <p class="design-desc">Visual drawer with circular thumbnail icons, badge pills, and direct tap links.</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- TAB 4: SEO & METADATA -->
                        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                            <div class="section-header-box bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-search me-2 text-warning"></i>4. Search Engine Optimization (SEO) &amp; Metadata</h5>
                                <p class="text-muted small mb-0">Optimize search engine snippet titles, keywords, and meta descriptions for this category page.</p>
                            </div>

                            <!-- Meta Title name Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">10</span>Meta Title Name
                                </label>
                                <input type="text" class="form-control form-control-lg" name="meta_title" value="<?php echo old('meta_title'); ?>" placeholder="e.g. Personalized Corporate Gifts & Custom Products Online | Printmont">
                                <small class="text-muted">Target length: 50–60 characters.</small>
                            </div>

                            <!-- Meta Keyword Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">11</span>Meta Keyword
                                </label>
                                <input type="text" class="form-control" name="meta_keywords" value="<?php echo old('meta_keywords'); ?>" placeholder="e.g. custom corporate gifts, promotional items, personalized mugs, engraved pens">
                                <small class="text-muted">Comma-separated target SEO keywords.</small>
                            </div>

                            <!-- Meta Descriptions Add > Box -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="field-num-badge">12</span>Meta Descriptions
                                </label>
                                <textarea class="form-control" name="meta_description" rows="4" placeholder="Brief search engine meta snippet description..."><?php echo old('meta_description'); ?></textarea>
                                <small class="text-muted">Target length: 150–160 characters snippet.</small>
                            </div>
                        </div>

                    </div>

                    <!-- Bottom Action Buttons -->
                    <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded border shadow-sm">
                        <a href="view-categories.php" class="btn btn-secondary px-3">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fas fa-plus me-1"></i> Create Main Category
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
// Auto slug generator
const catName = document.getElementById('catName');
const catSlug = document.getElementById('catSlug');
if (catName && catSlug) {
    catName.addEventListener('input', function() {
        if (!catSlug.dataset.manual) {
            catSlug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }
    });
    catSlug.addEventListener('input', function() {
        this.dataset.manual = 'true';
    });
}

// Image previewer
function previewImg(input, targetId) {
    const preview = document.getElementById(targetId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

// Dynamic Conditional Display for Yes/No selects
function toggleConditionalBlocks() {
    // Desktop Menu View Design
    const dMenuSelect = document.getElementById('desktopMenuViewSelect');
    const dMenuBlock  = document.getElementById('desktopMenuViewDesignBlock');
    if (dMenuSelect && dMenuBlock) {
        dMenuBlock.style.display = (dMenuSelect.value === 'yes') ? 'block' : 'none';
    }

    // Mobile Menu View Design
    const mMenuSelect = document.getElementById('mobileMenuViewSelect');
    const mMenuBlock  = document.getElementById('mobileMenuViewDesignBlock');
    if (mMenuSelect && mMenuBlock) {
        mMenuBlock.style.display = (mMenuSelect.value === 'yes') ? 'block' : 'none';
    }
}

document.getElementById('desktopMenuViewSelect')?.addEventListener('change', toggleConditionalBlocks);
document.getElementById('mobileMenuViewSelect')?.addEventListener('change', toggleConditionalBlocks);
toggleConditionalBlocks();
</script>
</body>
</html>
