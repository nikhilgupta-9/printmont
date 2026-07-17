<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller    = new CategoryController();
$mainCategories = $controller->getMainCategories();
$error   = '';

// AJAX: suggest the next display order for the selected main category
if (isset($_GET['get_next_order']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['next_order' => $controller->getNextDisplayOrder(2, (int)$_GET['parent_id'])]);
    exit;
}

function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}
function oldSel($key, $value, $default = '') {
    return (($_POST[$key] ?? $default) === $value) ? 'selected' : '';
}
function oldChecked($key, $value) {
    return (($_POST[$key] ?? '') === $value) ? 'checked' : '';
}

// Pre-select parent when arriving from "Add Sub Category" on a specific main category row
$selectedParentId = (int)($_POST['parent_id'] ?? $_GET['parent_id'] ?? 0);
$nextOrder = $selectedParentId ? $controller->getNextDisplayOrder(2, $selectedParentId) : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    function uploadCatImg($fileKey, $subdir = 'general') {
        if (empty($_FILES[$fileKey]['name'])) return '';
        $file = $_FILES[$fileKey];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp']) || $file['size'] > 5*1024*1024) return '';
        $dir = __DIR__ . "/uploads/category/{$subdir}/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fn = uniqid().'_'.time().'.'.$ext;
        return move_uploaded_file($file['tmp_name'], $dir.$fn) ? "uploads/category/{$subdir}/{$fn}" : '';
    }

    $parentId = (int)($_POST['parent_id'] ?? 0);
    $data = [
        'name'                 => $name,
        'slug'                 => $slug,
        'description'          => trim($_POST['description'] ?? ''),
        'parent_id'            => $parentId,
        'level'                => 2,
        'status'               => $_POST['status'] ?? 'active',
        'is_featured'          => (int)($_POST['is_featured'] ?? 0),
        'icon'                 => trim($_POST['icon'] ?? ''),
        'display_order'        => (int)($_POST['display_order'] ?? $controller->getNextDisplayOrder(2, $parentId)),
        // Desktop menu
        'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
        'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? 0),
        'desktop_menu_tag'     => trim($_POST['desktop_menu_tag'] ?? ''),
        // Desktop home
        'desktop_home_show'    => $_POST['desktop_home_show'] ?? 'no',
        'desktop_home_design'  => $_POST['desktop_home_design'] ?? '',
        'desktop_home_order'   => (int)($_POST['desktop_home_order'] ?? 0),
        'desktop_bg_color'     => $_POST['desktop_bg_color'] ?? '',
        'desktop_bg_image'     => uploadCatImg('desktop_bg_image', 'bg'),
        'desktop_image'        => uploadCatImg('desktop_image', 'desktop'),
        // Mobile menu
        'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
        'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? 0),
        'mobile_sidebar_order' => (int)($_POST['mobile_sidebar_order'] ?? 0),
        // Mobile home
        'mobile_home_show'     => $_POST['mobile_home_show'] ?? 'no',
        'mobile_home_design'   => $_POST['mobile_home_design'] ?? '',
        'mobile_home_format'   => $_POST['mobile_home_format'] ?? '4',
        'mobile_home_order'    => (int)($_POST['mobile_home_order'] ?? 0),
        'mobile_bg_color'      => $_POST['mobile_bg_color'] ?? '',
        'mobile_bg_image'      => uploadCatImg('mobile_bg_image', 'mobile-bg'),
        'mobile_image'         => uploadCatImg('mobile_image', 'mobile'),
        // SEO
        'meta_title'           => trim($_POST['meta_title'] ?? ''),
        'meta_keywords'        => trim($_POST['meta_keywords'] ?? ''),
        'meta_description'     => trim($_POST['meta_description'] ?? ''),
    ];

    if (empty($data['name'])) {
        $error = 'Sub category name is required.';
    } elseif (!$parentId) {
        $error = 'Please select a main category.';
    } elseif ($controller->checkCategoryExists($data['name'], $data['slug'])) {
        $error = 'A category with this name or slug already exists. Please choose a different name or slug.';
    } else {
        $result = $controller->createCategory($data);
        if ($result) {
            $_SESSION['success_message'] = 'Sub category created successfully!';
            header('Location: view-categories.php');
            exit;
        } else {
            $error = 'Failed to create sub category.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Sub Category | Printmont</title>
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
                        <h3><strong>Add</strong> Sub Category</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-categories.php" class="btn btn-success">View All Categories</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- BASIC INFO -->
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Basic Information</h5></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required-field">Main Category</label>
                                <select name="parent_id" id="parentCatSelect" class="form-control" required>
                                    <option value="">— Select Main Category —</option>
                                    <?php foreach ($mainCategories as $mc): ?>
                                        <option value="<?php echo $mc['id']; ?>" <?php echo ($selectedParentId == $mc['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Sub Category Name</label>
                                    <input type="text" class="form-control" name="name" id="catName" required
                                           placeholder="e.g. Laptops" value="<?php echo old('name'); ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Slug</label>
                                    <input type="text" class="form-control" name="slug" id="catSlug"
                                           placeholder="auto-generated" value="<?php echo old('slug'); ?>">
                                    <small class="text-muted">Auto-fills from name.</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo oldSel('status', 'active', 'active'); ?>>Active</option>
                                        <option value="inactive" <?php echo oldSel('status', 'inactive', 'active'); ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Featured</label>
                                    <select name="is_featured" class="form-control">
                                        <option value="0" <?php echo oldSel('is_featured', '0', '0'); ?>>No</option>
                                        <option value="1" <?php echo oldSel('is_featured', '1', '0'); ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" id="displayOrderInput" value="<?php echo old('display_order', $nextOrder); ?>" min="0">
                                    <small class="text-muted">Auto-suggested based on the selected main category.</small>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Icon <small class="text-muted">(optional)</small></label>
                                    <input type="text" class="form-control" name="icon" placeholder="fas fa-tag" value="<?php echo old('icon'); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"><?php echo old('description'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- DESKTOP TOP MENU (sub category has tag field) -->
                    <div class="card mb-4 section-card">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="mb-0 text-primary"><i class="fas fa-desktop me-2"></i>Desktop – Top Menu Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Inside Status</label>
                                    <select name="desktop_menu_status" class="form-control">
                                        <option value="show" <?php echo oldSel('desktop_menu_status', 'show', 'show'); ?>>Show</option>
                                        <option value="hide" <?php echo oldSel('desktop_menu_status', 'hide', 'show'); ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Inside Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_menu_order" value="<?php echo old('desktop_menu_order', '0'); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Menu Tag <small class="text-muted">(e.g. New, Hot)</small></label>
                                    <input type="text" class="form-control" name="desktop_menu_tag" placeholder="New" maxlength="100" value="<?php echo old('desktop_menu_tag'); ?>">
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
                                        <option value="no" <?php echo oldSel('desktop_home_show', 'no', 'no'); ?>>No</option>
                                        <option value="yes" <?php echo oldSel('desktop_home_show', 'yes', 'no'); ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Home Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_home_order" value="<?php echo old('desktop_home_order', '0'); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="desktop_bg_color" value="<?php echo old('desktop_bg_color', '#ffffff'); ?>" style="width:50px" id="desktopBgColorPicker">
                                        <input type="text" class="form-control" id="desktopBgColorText" value="<?php echo old('desktop_bg_color'); ?>" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <div class="conditional-block" id="desktopHomeBlock">
                                <label class="form-label">Select Home Design (Desktop)</label>
                                <div class="row g-3 mb-3">
                                    <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon,$lbl]): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_home_design" value="<?php echo $val; ?>" id="dhd_<?php echo $val; ?>" class="d-none" <?php echo oldChecked('desktop_home_design', $val); ?>>
                                        <label for="dhd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px"><?php echo $icon; ?></div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Desktop Category Image</label>
                                        <input type="file" class="form-control" name="desktop_image" accept="image/*" onchange="previewImg(this,'prvDeskImg')">
                                        <img id="prvDeskImg" class="image-preview">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Desktop Background Image</label>
                                        <input type="file" class="form-control" name="desktop_bg_image" accept="image/*" onchange="previewImg(this,'prvDeskBg')">
                                        <img id="prvDeskBg" class="image-preview">
                                    </div>
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
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Mobile Top Bar Inside Status</label>
                                    <select name="mobile_topbar_status" class="form-control">
                                        <option value="show" <?php echo oldSel('mobile_topbar_status', 'show', 'show'); ?>>Show</option>
                                        <option value="hide" <?php echo oldSel('mobile_topbar_status', 'hide', 'show'); ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Bar Inside Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_topbar_order" value="<?php echo old('mobile_topbar_order', '0'); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Sidebar Inside Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_sidebar_order" value="<?php echo old('mobile_sidebar_order', '0'); ?>" min="0">
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
                                    <label class="form-label">Show on Mobile Home?</label>
                                    <select name="mobile_home_show" class="form-control" id="mobileHomeShow">
                                        <option value="no" <?php echo oldSel('mobile_home_show', 'no', 'no'); ?>>No</option>
                                        <option value="yes" <?php echo oldSel('mobile_home_show', 'yes', 'no'); ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Mobile Home Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_home_order" value="<?php echo old('mobile_home_order', '0'); ?>" min="0">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Background Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="mobile_bg_color" value="<?php echo old('mobile_bg_color', '#ffffff'); ?>" style="width:50px" id="mobileBgColorPicker">
                                        <input type="text" class="form-control" id="mobileBgColorText" value="<?php echo old('mobile_bg_color'); ?>" placeholder="#ffffff" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <!-- Mobile images always visible -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Category Image</label>
                                    <input type="file" class="form-control" name="mobile_image" accept="image/*" onchange="previewImg(this,'prvMobImg')">
                                    <img id="prvMobImg" class="image-preview">
                                    <small class="text-muted">Max 5 MB · JPG/PNG/WebP</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Background Image <small class="text-muted">(optional)</small></label>
                                    <input type="file" class="form-control" name="mobile_bg_image" accept="image/*" onchange="previewImg(this,'prvMobBg')">
                                    <img id="prvMobBg" class="image-preview">
                                </div>
                            </div>
                            <div class="conditional-block" id="mobileHomeBlock">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Mobile Home Design</label>
                                        <div class="row g-2">
                                            <?php foreach (['design1'=>['📱','Design 1'],'design2'=>['🖼️','Design 2'],'design3'=>['🗂️','Design 3'],'design4'=>['🎨','Design 4']] as $val => [$icon,$lbl]): ?>
                                            <div class="col-3">
                                                <input type="radio" name="mobile_home_design" value="<?php echo $val; ?>" id="mhd_<?php echo $val; ?>" class="d-none" <?php echo oldChecked('mobile_home_design', $val); ?>>
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
                                            <option value="4" <?php echo oldSel('mobile_home_format', '4', '4'); ?>>4 Image Product Box</option>
                                            <option value="6" <?php echo oldSel('mobile_home_format', '6', '4'); ?>>6 Image Product Box</option>
                                            <option value="8" <?php echo oldSel('mobile_home_format', '8', '4'); ?>>8 Image Product Box</option>
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
                                <input type="text" class="form-control" name="meta_title" maxlength="255" placeholder="SEO title" value="<?php echo old('meta_title'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" name="meta_keywords" placeholder="keyword1, keyword2" value="<?php echo old('meta_keywords'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea class="form-control" name="meta_description" rows="3" maxlength="160" placeholder="Max 160 chars"><?php echo old('meta_description'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Create Sub Category</button>
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
const catName = document.getElementById('catName');
const catSlug = document.getElementById('catSlug');
catName.addEventListener('input', function () {
    if (!catSlug._manual) catSlug.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});
catSlug.addEventListener('input', function () { this._manual = !!this.value; });

// Auto-suggest the next display order when the main category changes
const parentSelect = document.getElementById('parentCatSelect');
const displayOrderInput = document.getElementById('displayOrderInput');
displayOrderInput.addEventListener('input', function () { this._manual = true; });
parentSelect.addEventListener('change', function () {
    if (displayOrderInput._manual || !this.value) return;
    fetch(`add-sub-category.php?get_next_order=1&parent_id=${this.value}`)
        .then(r => r.json())
        .then(data => { displayOrderInput.value = data.next_order; });
});

function bindToggle(selectId, blockId) {
    const sel = document.getElementById(selectId);
    const blk = document.getElementById(blockId);
    if (!sel || !blk) return;
    const update = () => blk.style.display = sel.value === 'yes' ? 'block' : 'none';
    sel.addEventListener('change', update);
    update();
}
bindToggle('desktopHomeShow', 'desktopHomeBlock');
bindToggle('mobileHomeShow',  'mobileHomeBlock');

function syncColor(pickerId, textId) {
    const p = document.getElementById(pickerId), t = document.getElementById(textId);
    if (!p||!t) return;
    p.addEventListener('input', () => t.value = p.value);
    t.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(t.value)) p.value = t.value; });
}
syncColor('desktopBgColorPicker','desktopBgColorText');
syncColor('mobileBgColorPicker','mobileBgColorText');

function previewImg(input, id) {
    const el = document.getElementById(id);
    if (!input.files||!input.files[0]) return;
    const r = new FileReader();
    r.onload = e => { el.src = e.target.result; el.style.display = 'block'; };
    r.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
