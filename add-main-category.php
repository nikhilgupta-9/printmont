<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImage($fileKey, $subdir = 'general') {
        if (empty($_FILES[$fileKey]['name'])) return '';
        $file    = $_FILES[$fileKey];
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $file['size'] > 5 * 1024 * 1024) return '';
        $dir = __DIR__ . "/uploads/category/{$subdir}/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . $filename) ? "uploads/category/{$subdir}/{$filename}" : '';
    }

    $data = [
        'name'                 => $name,
        'slug'                 => $slug,
        'description'          => trim($_POST['description'] ?? ''),
        'parent_id'            => 0,
        'level'                => 1,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? 0),
        // Desktop menu
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? 0),
        'desktop_menu_view'    => $_POST['desktop_menu_view'] ?? 'no',
        'desktop_menu_design'  => $_POST['desktop_menu_design'] ?? '',
        'desktop_menu_image'   => uploadCatImage('desktop_menu_image', 'menu'),
        // Desktop home
        'desktop_home_show'    => $_POST['desktop_home_show'] ?? 'no',
        'desktop_home_design'  => $_POST['desktop_home_design'] ?? '',
        'desktop_home_order'   => (int)($_POST['desktop_home_order'] ?? 0),
        'desktop_bg_color'     => $_POST['desktop_bg_color'] ?? '',
        'desktop_bg_image'     => uploadCatImage('desktop_bg_image', 'bg'),
        'desktop_image'        => uploadCatImage('desktop_image', 'desktop'),
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
        'mobile_bg_image'      => uploadCatImage('mobile_bg_image', 'mobile-bg'),
        'mobile_image'         => uploadCatImage('mobile_image', 'mobile'),
        // SEO
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Category name is required.';
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
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Main Category | Printmont</title>
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
                        <h3><strong>Add</strong> Main Category</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-categories.php" class="btn btn-success">View All Categories</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($success); ?></div>
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
                                           placeholder="e.g. Electronics" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="catSlug"
                                           placeholder="auto-generated" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                                    <small class="text-muted">Lowercase letters and hyphens only. Auto-fills from name.</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Featured</label>
                                    <select name="is_featured" class="form-control">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Icon Class <small class="text-muted">(optional)</small></label>
                                    <input type="text" class="form-control" name="icon" placeholder="fas fa-tag">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Short description"></textarea>
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
                                        <option value="show">Show</option>
                                        <option value="hide">Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_menu_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Show Category View Design?</label>
                                    <select name="desktop_menu_view" class="form-control" id="desktopMenuView">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="conditional-block" id="desktopMenuDesignBlock">
                                <label class="form-label">Select Desktop Menu Design</label>
                                <div class="row g-3 mb-3">
                                    <?php foreach (['design_dm1'=>'Menu Design 1','design_dm2'=>'Menu Design 2','design_dm3'=>'Menu Design 3','design_dm4'=>'Menu Design 4'] as $val => $lbl): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_menu_design" value="<?php echo $val; ?>" id="dmd_<?php echo $val; ?>" class="d-none">
                                        <label for="dmd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px">🖥️</div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Desktop Menu Image Upload -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Desktop Menu Category Image</label>
                                        <input type="file" class="form-control" name="desktop_menu_image" accept="image/*" onchange="previewImg(this,'prvDeskMenu')">
                                        <img id="prvDeskMenu" class="image-preview">
                                        <small class="text-muted">Image shown in top menu dropdown · Max 5 MB</small>
                                    </div>
                                </div>
                            </div>
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
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Home Page Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_home_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="desktop_bg_color" value="#ffffff" style="width:50px" id="desktopBgColorPicker">
                                        <input type="text" class="form-control" id="desktopBgColorText" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <!-- Desktop Home images always visible -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Desktop Category Image</label>
                                    <input type="file" class="form-control" name="desktop_image" accept="image/*" onchange="previewImg(this,'previewDesktopImg')">
                                    <img id="previewDesktopImg" class="image-preview">
                                    <small class="text-muted">Max 5 MB · JPG/PNG/WebP</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Desktop Background Image <small class="text-muted">(optional)</small></label>
                                    <input type="file" class="form-control" name="desktop_bg_image" accept="image/*" onchange="previewImg(this,'previewDesktopBg')">
                                    <img id="previewDesktopBg" class="image-preview">
                                    <small class="text-muted">Used as section background</small>
                                </div>
                            </div>
                            <div class="conditional-block" id="desktopHomeBlock">
                                <label class="form-label">Select Home Page Design (Desktop)</label>
                                <div class="row g-3">
                                    <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon, $lbl]): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_home_design" value="<?php echo $val; ?>" id="dhd_<?php echo $val; ?>" class="d-none">
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
                                        <option value="show">Show</option>
                                        <option value="hide">Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Top Bar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_topbar_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Sidebar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_sidebar_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Show Menu View Design?</label>
                                    <select name="mobile_menu_view" class="form-control" id="mobileMenuView">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="conditional-block" id="mobileMenuDesignBlock">
                                <label class="form-label">Select Mobile Menu Design</label>
                                <div class="row g-3">
                                    <?php foreach (['mobile_design1'=>'Mobile Design 1','mobile_design2'=>'Mobile Design 2'] as $val => $lbl): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="mobile_menu_design" value="<?php echo $val; ?>" id="mmd_<?php echo $val; ?>" class="d-none">
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
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Mobile Home Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_home_order" value="0" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="mobile_bg_color" value="#ffffff" style="width:50px" id="mobileBgColorPicker">
                                        <input type="text" class="form-control" id="mobileBgColorText" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <!-- Mobile images always visible -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Category Image</label>
                                    <input type="file" class="form-control" name="mobile_image" accept="image/*" onchange="previewImg(this,'previewMobileImg')">
                                    <img id="previewMobileImg" class="image-preview">
                                    <small class="text-muted">Max 5 MB · JPG/PNG/WebP</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Background Image <small class="text-muted">(optional)</small></label>
                                    <input type="file" class="form-control" name="mobile_bg_image" accept="image/*" onchange="previewImg(this,'previewMobileBg')">
                                    <img id="previewMobileBg" class="image-preview">
                                </div>
                            </div>
                            <div class="conditional-block" id="mobileHomeBlock">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Mobile Home Design</label>
                                        <div class="row g-2">
                                            <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon, $lbl]): ?>
                                            <div class="col-3">
                                                <input type="radio" name="mobile_home_design" value="<?php echo $val; ?>" id="mhd_<?php echo $val; ?>" class="d-none">
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
                                            <option value="4">4 Image Product Box</option>
                                            <option value="6">6 Image Product Box</option>
                                            <option value="8">8 Image Product Box</option>
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
                                       placeholder="SEO title (leave blank to use category name)">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" name="meta_keywords"
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea class="form-control" name="meta_description" rows="3" maxlength="160"
                                          placeholder="Brief description for search engines (max 160 chars)"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Create Main Category</button>
                        <a href="view-categories.php" class="btn btn-secondary ms-2">Cancel</a>
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
bindToggle('desktopMenuView',  'desktopMenuDesignBlock');
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
