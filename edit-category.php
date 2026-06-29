<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CategoryController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid category ID!";
    header("Location: view-categories.php");
    exit();
}

$categoryId = (int)$_GET['id'];
$categoryController = new CategoryController();
$category = $categoryController->getCategoryById($categoryId);

if (!$category) {
    $_SESSION['error_message'] = "Category not found!";
    header("Location: view-categories.php");
    exit();
}

// Image upload helper
function uploadEditImg($fileKey, $subdir, $existing = '') {
    if (empty($_FILES[$fileKey]['name'])) return $existing;
    $file = $_FILES[$fileKey];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp']) || $file['size'] > 5*1024*1024) return $existing;
    $dir = __DIR__ . "/uploads/category/{$subdir}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    // Remove old file
    if ($existing && file_exists(__DIR__ . '/' . $existing)) @unlink(__DIR__ . '/' . $existing);
    $fn = uniqid() . '_' . time() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$subdir}/{$fn}" : $existing;
}

function removeImg($col, $category) {
    if (!empty($category[$col]) && file_exists(__DIR__ . '/' . $category[$col])) {
        @unlink(__DIR__ . '/' . $category[$col]);
    }
    return '';
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    // Handle each image: remove > upload > keep
    $desktop_menu_image = isset($_POST['remove_desktop_menu_image']) ? removeImg('desktop_menu_image', $category) : uploadEditImg('desktop_menu_image', 'menu',       $category['desktop_menu_image'] ?? '');
    $desktop_image      = isset($_POST['remove_desktop_image'])      ? removeImg('desktop_image',      $category) : uploadEditImg('desktop_image',      'desktop',    $category['desktop_image'] ?? '');
    $desktop_bg_image   = isset($_POST['remove_desktop_bg_image'])   ? removeImg('desktop_bg_image',   $category) : uploadEditImg('desktop_bg_image',   'bg',         $category['desktop_bg_image'] ?? '');
    $mobile_image       = isset($_POST['remove_mobile_image'])       ? removeImg('mobile_image',       $category) : uploadEditImg('mobile_image',       'mobile',     $category['mobile_image'] ?? '');
    $mobile_bg_image    = isset($_POST['remove_mobile_bg_image'])    ? removeImg('mobile_bg_image',    $category) : uploadEditImg('mobile_bg_image',    'mobile-bg',  $category['mobile_bg_image'] ?? '');

    $data = [
        'name'                 => $name,
        'slug'                 => $slug,
        'description'          => trim($_POST['description'] ?? ''),
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? 0),
        // Desktop menu
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? 0),
        'desktop_menu_view'    => $_POST['desktop_menu_view'] ?? 'no',
        'desktop_menu_design'  => $_POST['desktop_menu_design'] ?? '',
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        'desktop_menu_image'   => $desktop_menu_image,
        // Desktop home
        'desktop_home_show'    => $_POST['desktop_home_show'] ?? 'no',
        'desktop_home_design'  => $_POST['desktop_home_design'] ?? '',
        'desktop_home_order'   => (int)($_POST['desktop_home_order'] ?? 0),
        'desktop_bg_color'     => $_POST['desktop_bg_color'] ?? '',
        'desktop_image'        => $desktop_image,
        'desktop_bg_image'     => $desktop_bg_image,
        // Mobile menu
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? 0),
        'mobile_menu_view'     => $_POST['mobile_menu_view'] ?? 'no',
        'mobile_menu_design'   => $_POST['mobile_menu_design'] ?? '',
        'mobile_sidebar_order' => (int)($_POST['mobile_sidebar_order'] ?? 0),
        // Mobile home
        'mobile_home_show'     => $_POST['mobile_home_show'] ?? 'no',
        'mobile_home_design'   => $_POST['mobile_home_design'] ?? '',
        'mobile_home_format'   => $_POST['mobile_home_format'] ?? '4',
        'mobile_home_order'    => (int)($_POST['mobile_home_order'] ?? 0),
        'mobile_bg_color'      => $_POST['mobile_bg_color'] ?? '',
        'mobile_image'         => $mobile_image,
        'mobile_bg_image'      => $mobile_bg_image,
        // SEO
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Category name is required.';
    } else {
        if ($categoryController->updateCategory($categoryId, $data)) {
            $_SESSION['success_message'] = 'Category updated successfully!';
            header('Location: view-categories.php');
            exit;
        } else {
            $error = 'Failed to update category. Please try again.';
        }
    }

    // Refresh for re-display
    $category = $categoryController->getCategoryById($categoryId);
}

$level = (int)($category['level'] ?? 1);

// Helper: show current image with remove checkbox
function currentImgBlock($col, $label, $category) {
    $path = $category[$col] ?? '';
    if (!$path) return;
    echo '<div class="mb-2">';
    echo '<small class="text-muted d-block mb-1">Current ' . htmlspecialchars($label) . ':</small>';
    echo '<img src="' . htmlspecialchars($path) . '" style="max-height:80px;border-radius:4px;" onerror="this.style.display=\'none\'">';
    echo '<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_' . $col . '" id="rm_' . $col . '" value="1">';
    echo '<label class="form-check-label text-danger small" for="rm_' . $col . '">Remove this image</label></div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Edit Category | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .required-field::after { content: " *"; color: #dc3545; }
        .section-card { border-left: 4px solid #0d6efd; }
        .section-card.mobile { border-left-color: #198754; }
        .section-card.seo { border-left-color: #ffc107; }
        .design-option { border: 2px solid #dee2e6; border-radius: 8px; padding: 10px; text-align: center; transition: all .2s; cursor: pointer; }
        .design-option:hover { border-color: #0d6efd; }
        input[type=radio]:checked + .design-option { border-color: #0d6efd; background: #e8f4fd; }
        .image-preview { max-height: 120px; border-radius: 6px; margin-top: 8px; display: none; }
        .conditional-block { display: none; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3><strong>Edit</strong> Category
                            <span class="badge bg-<?php echo $level==1?'primary':($level==2?'success':'warning'); ?> ms-2">
                                Level <?php echo $level; ?> – <?php echo $level==1?'Main':($level==2?'Sub':'Sub Sub'); ?>
                            </span>
                        </h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-categories.php" class="btn btn-secondary">View All Categories</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- BASIC INFO -->
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Basic Information</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Category Name</label>
                                    <input type="text" class="form-control" name="name" id="catName" required
                                           value="<?php echo htmlspecialchars($category['name']); ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="catSlug"
                                           value="<?php echo htmlspecialchars($category['slug']); ?>">
                                    <small class="text-muted">Lowercase letters and hyphens only.</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active"   <?php echo $category['status']=='active'  ?'selected':''; ?>>Active</option>
                                        <option value="inactive" <?php echo $category['status']=='inactive'?'selected':''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Featured</label>
                                    <select name="is_featured" class="form-control">
                                        <option value="0" <?php echo !$category['is_featured']?'selected':''; ?>>No</option>
                                        <option value="1" <?php echo  $category['is_featured']?'selected':''; ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="<?php echo (int)$category['display_order']; ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Icon Class <small class="text-muted">(optional)</small></label>
                                    <input type="text" class="form-control" name="icon" value="<?php echo htmlspecialchars($category['icon']); ?>" placeholder="fas fa-tag">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- DESKTOP TOP MENU -->
                    <div class="card mb-4 section-card">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="mb-0 text-primary"><i class="fas fa-desktop me-2"></i>Desktop – Top Menu Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Status</label>
                                    <select name="desktop_menu_status" class="form-control">
                                        <option value="show" <?php echo ($category['desktop_menu_status']??'show')=='show'?'selected':''; ?>>Show</option>
                                        <option value="hide" <?php echo ($category['desktop_menu_status']??'')=='hide'?'selected':''; ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo (int)($category['desktop_menu_order']??0); ?>" min="0">
                                </div>
                                <?php if ($level == 1): ?>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Show Category View Design?</label>
                                    <select name="desktop_menu_view" class="form-control" id="desktopMenuView">
                                        <option value="no"  <?php echo ($category['desktop_menu_view']??'no')=='no' ?'selected':''; ?>>No</option>
                                        <option value="yes" <?php echo ($category['desktop_menu_view']??'')=='yes'?'selected':''; ?>>Yes</option>
                                    </select>
                                </div>
                                <?php else: ?>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Menu Tag <small class="text-muted">(e.g. New, Hot)</small></label>
                                    <input type="text" class="form-control" name="desktop_menu_tag" value="<?php echo htmlspecialchars($category['desktop_menu_tag']??''); ?>" placeholder="New">
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($level == 1): ?>
                            <div class="conditional-block" id="desktopMenuDesignBlock">
                                <label class="form-label">Select Desktop Menu Design</label>
                                <div class="row g-3 mb-3">
                                    <?php foreach (['design_dm1'=>'Menu Design 1','design_dm2'=>'Menu Design 2','design_dm3'=>'Menu Design 3','design_dm4'=>'Menu Design 4'] as $val => $lbl): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_menu_design" value="<?php echo $val; ?>" id="dmd_<?php echo $val; ?>" class="d-none"
                                               <?php echo ($category['desktop_menu_design']??'')==$val?'checked':''; ?>>
                                        <label for="dmd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px">🖥️</div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Desktop Menu Image -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Desktop Menu Category Image</label>
                                        <?php currentImgBlock('desktop_menu_image', 'Desktop Menu Image', $category); ?>
                                        <input type="file" class="form-control mt-1" name="desktop_menu_image" accept="image/*" onchange="previewImg(this,'prvDeskMenu')">
                                        <img id="prvDeskMenu" class="image-preview">
                                        <small class="text-muted">Image shown in top menu dropdown · Max 5 MB</small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DESKTOP HOME PAGE -->
                    <div class="card mb-4 section-card">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="mb-0 text-primary"><i class="fas fa-home me-2"></i>Desktop – Home Page Display</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Show on Desktop Home Page?</label>
                                    <select name="desktop_home_show" class="form-control" id="desktopHomeShow">
                                        <option value="no"  <?php echo ($category['desktop_home_show']??'no')=='no' ?'selected':''; ?>>No</option>
                                        <option value="yes" <?php echo ($category['desktop_home_show']??'')=='yes'?'selected':''; ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Home Page Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_home_order" value="<?php echo (int)($category['desktop_home_order']??0); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="desktop_bg_color" id="desktopBgColorPicker"
                                               value="<?php echo htmlspecialchars($category['desktop_bg_color'] ?: '#ffffff'); ?>" style="width:50px">
                                        <input type="text" class="form-control" id="desktopBgColorText"
                                               value="<?php echo htmlspecialchars($category['desktop_bg_color']??''); ?>" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <!-- Desktop images always visible -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Desktop Category Image</label>
                                    <?php currentImgBlock('desktop_image', 'Desktop Image', $category); ?>
                                    <input type="file" class="form-control mt-1" name="desktop_image" accept="image/*" onchange="previewImg(this,'prevDesktopImg')">
                                    <img id="prevDesktopImg" class="image-preview">
                                    <small class="text-muted">Max 5 MB · JPG/PNG/WebP</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Desktop Background Image <small class="text-muted">(optional)</small></label>
                                    <?php currentImgBlock('desktop_bg_image', 'Desktop BG Image', $category); ?>
                                    <input type="file" class="form-control mt-1" name="desktop_bg_image" accept="image/*" onchange="previewImg(this,'prevDesktopBg')">
                                    <img id="prevDesktopBg" class="image-preview">
                                </div>
                            </div>
                            <div class="conditional-block" id="desktopHomeBlock">
                                <label class="form-label">Select Home Page Design (Desktop)</label>
                                <div class="row g-3">
                                    <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon,$lbl]): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_home_design" value="<?php echo $val; ?>" id="dhd_<?php echo $val; ?>" class="d-none"
                                               <?php echo ($category['desktop_home_design']??'')==$val?'checked':''; ?>>
                                        <label for="dhd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px"><?php echo $icon; ?></div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MOBILE TOP BAR -->
                    <div class="card mb-4 section-card mobile">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="fas fa-mobile-alt me-2"></i>Mobile – Top Bar Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Mobile Top Bar Status</label>
                                    <select name="mobile_topbar_status" class="form-control">
                                        <option value="show" <?php echo ($category['mobile_topbar_status']??'show')=='show'?'selected':''; ?>>Show</option>
                                        <option value="hide" <?php echo ($category['mobile_topbar_status']??'')=='hide'?'selected':''; ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Top Bar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_topbar_order" value="<?php echo (int)($category['mobile_topbar_order']??0); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Sidebar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_sidebar_order" value="<?php echo (int)($category['mobile_sidebar_order']??0); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Show Menu View Design?</label>
                                    <select name="mobile_menu_view" class="form-control" id="mobileMenuView">
                                        <option value="no"  <?php echo ($category['mobile_menu_view']??'no')=='no' ?'selected':''; ?>>No</option>
                                        <option value="yes" <?php echo ($category['mobile_menu_view']??'')=='yes'?'selected':''; ?>>Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="conditional-block" id="mobileMenuDesignBlock">
                                <label class="form-label">Select Mobile Menu Design</label>
                                <div class="row g-3">
                                    <?php foreach (['mobile_design1'=>'Mobile Design 1','mobile_design2'=>'Mobile Design 2'] as $val => $lbl): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="mobile_menu_design" value="<?php echo $val; ?>" id="mmd_<?php echo $val; ?>" class="d-none"
                                               <?php echo ($category['mobile_menu_design']??'')==$val?'checked':''; ?>>
                                        <label for="mmd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px">📱</div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MOBILE HOME PAGE -->
                    <div class="card mb-4 section-card mobile">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="fas fa-home me-2"></i>Mobile – Home Page Display</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Show on Mobile Home Page?</label>
                                    <select name="mobile_home_show" class="form-control" id="mobileHomeShow">
                                        <option value="no"  <?php echo ($category['mobile_home_show']??'no')=='no' ?'selected':''; ?>>No</option>
                                        <option value="yes" <?php echo ($category['mobile_home_show']??'')=='yes'?'selected':''; ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Mobile Home Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_home_order" value="<?php echo (int)($category['mobile_home_order']??0); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="mobile_bg_color" id="mobileBgColorPicker"
                                               value="<?php echo htmlspecialchars($category['mobile_bg_color'] ?: '#ffffff'); ?>" style="width:50px">
                                        <input type="text" class="form-control" id="mobileBgColorText"
                                               value="<?php echo htmlspecialchars($category['mobile_bg_color']??''); ?>" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <!-- Mobile images always visible -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Category Image</label>
                                    <?php currentImgBlock('mobile_image', 'Mobile Image', $category); ?>
                                    <input type="file" class="form-control mt-1" name="mobile_image" accept="image/*" onchange="previewImg(this,'prevMobileImg')">
                                    <img id="prevMobileImg" class="image-preview">
                                    <small class="text-muted">Max 5 MB · JPG/PNG/WebP</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Background Image <small class="text-muted">(optional)</small></label>
                                    <?php currentImgBlock('mobile_bg_image', 'Mobile BG Image', $category); ?>
                                    <input type="file" class="form-control mt-1" name="mobile_bg_image" accept="image/*" onchange="previewImg(this,'prevMobileBg')">
                                    <img id="prevMobileBg" class="image-preview">
                                </div>
                            </div>
                            <div class="conditional-block" id="mobileHomeBlock">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Mobile Home Design</label>
                                        <div class="row g-2">
                                            <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon,$lbl]): ?>
                                            <div class="col-3">
                                                <input type="radio" name="mobile_home_design" value="<?php echo $val; ?>" id="mhd_<?php echo $val; ?>" class="d-none"
                                                       <?php echo ($category['mobile_home_design']??'')==$val?'checked':''; ?>>
                                                <label for="mhd_<?php echo $val; ?>" class="design-option d-block">
                                                    <div class="bg-light rounded mb-1" style="height:50px;display:flex;align-items:center;justify-content:center;font-size:20px"><?php echo $icon; ?></div>
                                                    <small><?php echo $lbl; ?></small>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Product Box Format</label>
                                        <select name="mobile_home_format" class="form-control">
                                            <option value="4" <?php echo ($category['mobile_home_format']??'4')=='4'?'selected':''; ?>>4 Image Product Box</option>
                                            <option value="6" <?php echo ($category['mobile_home_format']??'')=='6'?'selected':''; ?>>6 Image Product Box</option>
                                            <option value="8" <?php echo ($category['mobile_home_format']??'')=='8'?'selected':''; ?>>8 Image Product Box</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card mb-4 section-card seo">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h5 class="mb-0"><i class="fas fa-search me-2"></i>SEO Meta</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" class="form-control" name="meta_title" maxlength="255"
                                       value="<?php echo htmlspecialchars($category['meta_title']??''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" name="meta_keywords"
                                       value="<?php echo htmlspecialchars($category['meta_keywords']??''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea class="form-control" name="meta_description" rows="3" maxlength="160"><?php echo htmlspecialchars($category['meta_description']??''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Update Category</button>
                        <a href="view-categories.php" class="btn btn-secondary">Cancel</a>
                        <a href="delete-category.php?id=<?php echo $categoryId; ?>" class="btn btn-outline-danger ms-auto"
                           onclick="return confirm('Delete this category? This cannot be undone.')">
                            <i class="fas fa-trash me-1"></i>Delete Category
                        </a>
                    </div>

                </form>
            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
<script>
// Slug auto-generate
const catName = document.getElementById('catName');
const catSlug = document.getElementById('catSlug');
catName.addEventListener('input', function () {
    if (!catSlug._manual) catSlug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});
catSlug.addEventListener('input', function () { this._manual = !!this.value; });

// Conditional show/hide blocks
function bindToggle(selectId, blockId, triggerVal = 'yes') {
    const sel = document.getElementById(selectId);
    const blk = document.getElementById(blockId);
    if (!sel || !blk) return;
    const update = () => blk.style.display = (sel.value === triggerVal) ? 'block' : 'none';
    sel.addEventListener('change', update);
    update();
}
<?php if ($level == 1): ?>
bindToggle('desktopMenuView',  'desktopMenuDesignBlock');
<?php endif; ?>
bindToggle('desktopHomeShow',  'desktopHomeBlock');
bindToggle('mobileMenuView',   'mobileMenuDesignBlock');
bindToggle('mobileHomeShow',   'mobileHomeBlock');

// Color picker sync
function syncColor(pickerId, textId) {
    const picker = document.getElementById(pickerId);
    const text   = document.getElementById(textId);
    if (!picker || !text) return;
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
}
syncColor('desktopBgColorPicker', 'desktopBgColorText');
syncColor('mobileBgColorPicker',  'mobileBgColorText');

// Image preview
function previewImg(input, previewId) {
    const el = document.getElementById(previewId);
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { el.src = e.target.result; el.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
