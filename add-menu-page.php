<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();

// AJAX: cascading dropdown data
if (isset($_GET['get_subs']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubCategories((int)$_GET['parent_id']));
    exit;
}
if (isset($_GET['get_subsubs']) && isset($_GET['parent_id'])) {
    header('Content-Type: application/json');
    echo json_encode($controller->getSubSubCategories((int)$_GET['parent_id']));
    exit;
}

$mainCategories = $controller->getMainCategories();
$error   = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$selectedId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
$selected   = $selectedId ? $controller->getCategoryById($selectedId) : null;

// Resolve the Main / Sub / Sub-Sub ancestor chain so the cascade pre-fills correctly
$mainId = 0; $subId = 0; $subSubId = 0;
if ($selected) {
    if ((int)$selected['level'] === 3) {
        $subSubId = (int)$selected['id'];
        $subCat   = $controller->getCategoryById((int)$selected['parent_id']);
        $subId    = $subCat ? (int)$subCat['id'] : 0;
        $mainId   = $subCat ? (int)$subCat['parent_id'] : 0;
    } elseif ((int)$selected['level'] === 2) {
        $subId  = (int)$selected['id'];
        $mainId = (int)$selected['parent_id'];
    }
}
$subCategories    = $mainId ? $controller->getSubCategories($mainId) : [];
$subSubCategories = $subId  ? $controller->getSubSubCategories($subId) : [];

$designOptions = [
    'design1' => ['📱', 'Design 1'],
    'design2' => ['🖼️', 'Design 2'],
    'design3' => ['🗂️', 'Design 3'],
    'design4' => ['🎨', 'Design 4'],
];

function uploadInsideImg($fileKey, $subdir, $existing = '') {
    if (empty($_FILES[$fileKey]['name'])) return $existing;
    $file = $_FILES[$fileKey];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp']) || $file['size'] > 5*1024*1024) return $existing;
    $dir = __DIR__ . "/uploads/category/{$subdir}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if ($existing && file_exists(__DIR__ . '/' . $existing)) @unlink(__DIR__ . '/' . $existing);
    $fn = uniqid() . '_' . time() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dir . $fn) ? "uploads/category/{$subdir}/{$fn}" : $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!$selectedId || !$selected) {
        $error = 'Please select a category first.';
    } else {
        $data = [
            'name'                 => trim($_POST['name'] ?? $selected['name']),
            'desktop_menu_design'  => $_POST['desktop_menu_design'] ?? '',
            'desktop_menu_image'   => uploadInsideImg('desktop_menu_image', 'menu', $selected['desktop_menu_image'] ?? ''),
            'desktop_menu_order'   => (int)($_POST['desktop_menu_order'] ?? 0),
            'desktop_menu_status'  => $_POST['desktop_menu_status'] ?? 'show',
            'mobile_topbar_status' => $_POST['mobile_topbar_status'] ?? 'show',
            'mobile_topbar_order'  => (int)($_POST['mobile_topbar_order'] ?? 0),
            'mobile_sidebar_order' => (int)($_POST['mobile_sidebar_order'] ?? 0),
        ];
        if (empty($data['name'])) {
            $error = 'Title Name is required.';
        } else {
            $controller->updateCategory($selectedId, $data);
            $_SESSION['success_message'] = 'Menu page updated for "' . htmlspecialchars($data['name']) . '".';
            header('Location: add-menu-page.php?category_id=' . $selectedId);
            exit;
        }
        $selected = $controller->getCategoryById($selectedId);
    }
}

$selectedUrl = $selected ? '/category/' . $selected['slug'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Header Menu — Top Menu Inside Pages | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .required-field::after { content: " *"; color: #dc3545; }
        .design-option { border: 2px solid #dee2e6; border-radius: 8px; padding: 10px; text-align: center; transition: all .2s; cursor: pointer; }
        .design-option:hover { border-color: #0d6efd; }
        input[type=radio]:checked + .design-option { border-color: #0d6efd; background: #e8f4fd; }
        .image-preview { max-height: 120px; border-radius: 6px; margin-top: 8px; display: none; }
        .cascade-arrow { align-self: center; color: #adb5bd; font-size: 20px; padding-top: 28px; }
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
                        <h3><strong>Header Menu</strong> — Top Menu Inside Pages</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="header-menu-list.php" class="btn btn-success">All Menus</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Select Category</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label required-field">Main Category</label>
                                <select id="mainCatSelect" class="form-control">
                                    <option value="">— Select Main Category —</option>
                                    <?php foreach ($mainCategories as $mc): ?>
                                        <option value="<?php echo $mc['id']; ?>" <?php echo $mainId == $mc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto cascade-arrow">›</div>
                            <div class="col-md-3">
                                <label class="form-label">Sub Category</label>
                                <select id="subCatSelect" class="form-control" <?php echo $mainId ? '' : 'disabled'; ?>>
                                    <option value="">— Select Sub Category —</option>
                                    <?php foreach ($subCategories as $sc): ?>
                                        <option value="<?php echo $sc['id']; ?>" <?php echo $subId == $sc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto cascade-arrow">›</div>
                            <div class="col-md-3">
                                <label class="form-label">Sub-Sub Category <small class="text-muted">(optional)</small></label>
                                <select id="subSubCatSelect" class="form-control" <?php echo $subId ? '' : 'disabled'; ?>>
                                    <option value="">— None —</option>
                                    <?php foreach ($subSubCategories as $ssc): ?>
                                        <option value="<?php echo $ssc['id']; ?>" <?php echo $subSubId == $ssc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ssc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto" style="padding-top:28px">
                                <button type="button" id="configureSubBtn" class="btn btn-outline-primary" <?php echo $subId ? '' : 'disabled'; ?>>
                                    Configure this level
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Pick a Main Category, then its Sub Category. Choose a Sub-Sub Category to drill deeper, or click "Configure this level" to edit the Sub Category itself.</small>
                    </div>
                </div>

                <?php if ($selected): ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" value="<?php echo $selectedId; ?>">
                    <input type="hidden" name="save" value="1">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                Menu Page Settings
                                <span class="badge bg-<?php echo (int)$selected['level'] == 3 ? 'warning' : 'success'; ?> ms-2">
                                    <?php echo (int)$selected['level'] == 3 ? 'Sub-Sub Category' : 'Sub Category'; ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Title Name</label>
                                    <input type="text" class="form-control" name="name" required
                                           value="<?php echo htmlspecialchars($selected['name']); ?>">
                                    <small class="text-muted">Updates this category's display name.</small>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">URL</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($selectedUrl); ?>" readonly>
                                    <small class="text-muted">Auto-generated from the category slug.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-left:4px solid #0d6efd">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="mb-0 text-primary"><i class="fas fa-desktop me-2"></i>Desktop Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="desktop_menu_status" class="form-control">
                                        <option value="show" <?php echo ($selected['desktop_menu_status'] ?? 'show') == 'show' ? 'selected' : ''; ?>>Show</option>
                                        <option value="hide" <?php echo ($selected['desktop_menu_status'] ?? '') == 'hide' ? 'selected' : ''; ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="desktop_menu_order" min="0"
                                           value="<?php echo (int)($selected['desktop_menu_order'] ?? 0); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Design</label>
                                <div class="row g-3">
                                    <?php foreach ($designOptions as $val => [$icon, $lbl]): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" name="desktop_menu_design" value="<?php echo $val; ?>" id="dmd_<?php echo $val; ?>" class="d-none"
                                               <?php echo ($selected['desktop_menu_design'] ?? '') == $val ? 'checked' : ''; ?>>
                                        <label for="dmd_<?php echo $val; ?>" class="design-option d-block">
                                            <div class="bg-light rounded mb-2" style="height:60px;display:flex;align-items:center;justify-content:center;font-size:24px"><?php echo $icon; ?></div>
                                            <small><?php echo $lbl; ?></small>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Inside Image Upload</label>
                                <?php if (!empty($selected['desktop_menu_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($selected['desktop_menu_image']); ?>" style="max-height:80px;border-radius:4px;display:block;margin-bottom:6px" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <input type="file" class="form-control" name="desktop_menu_image" accept="image/*" onchange="previewImg(this,'prvMenuImage')">
                                <img id="prvMenuImage" class="image-preview">
                                <small class="text-muted">Image shown for this item inside the mega-menu panel. Max 5 MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-left:4px solid #198754">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="fas fa-mobile-alt me-2"></i>Mobile Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Bar Status</label>
                                    <select name="mobile_topbar_status" class="form-control">
                                        <option value="show" <?php echo ($selected['mobile_topbar_status'] ?? 'show') == 'show' ? 'selected' : ''; ?>>Show</option>
                                        <option value="hide" <?php echo ($selected['mobile_topbar_status'] ?? '') == 'hide' ? 'selected' : ''; ?>>Hide</option>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Top Bar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_topbar_order" min="0"
                                           value="<?php echo (int)($selected['mobile_topbar_order'] ?? 0); ?>">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label class="form-label">Sidebar Sort Order</label>
                                    <input type="number" class="form-control" name="mobile_sidebar_order" min="0"
                                           value="<?php echo (int)($selected['mobile_sidebar_order'] ?? 0); ?>">
                                </div>
                            </div>
                            <small class="text-muted">Mobile uses the same design and image as Desktop above — sub/sub-sub categories don't get a separate mobile menu design per your original spec.</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Save Menu Page</button>
                        <a href="header-menu-list.php" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
<script>
const mainSel        = document.getElementById('mainCatSelect');
const subSel         = document.getElementById('subCatSelect');
const subSubSel      = document.getElementById('subSubCatSelect');
const configureBtn   = document.getElementById('configureSubBtn');

mainSel.addEventListener('change', function () {
    subSel.innerHTML = '<option value="">Loading...</option>';
    subSel.disabled = true;
    subSubSel.innerHTML = '<option value="">— None —</option>';
    subSubSel.disabled = true;
    configureBtn.disabled = true;
    if (!this.value) { subSel.innerHTML = '<option value="">— Select Sub Category —</option>'; return; }
    fetch(`add-menu-page.php?get_subs=1&parent_id=${this.value}`)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '<option value="">— Select Sub Category —</option>';
            data.forEach(s => subSel.innerHTML += `<option value="${s.id}">${s.name}</option>`);
            subSel.disabled = false;
        });
});

subSel.addEventListener('change', function () {
    subSubSel.innerHTML = '<option value="">— None —</option>';
    if (!this.value) { subSubSel.disabled = true; configureBtn.disabled = true; return; }
    configureBtn.disabled = false;
    subSubSel.innerHTML = '<option value="">Loading...</option>';
    subSubSel.disabled = true;
    fetch(`add-menu-page.php?get_subsubs=1&parent_id=${this.value}`)
        .then(r => r.json())
        .then(data => {
            subSubSel.innerHTML = '<option value="">— None —</option>';
            data.forEach(s => subSubSel.innerHTML += `<option value="${s.id}">${s.name}</option>`);
            subSubSel.disabled = false;
        });
});

configureBtn.addEventListener('click', function () {
    if (subSel.value) location.href = 'add-menu-page.php?category_id=' + subSel.value;
});

subSubSel.addEventListener('change', function () {
    if (!this.value) return;
    location.href = 'add-menu-page.php?category_id=' + this.value;
});

function previewImg(input, id) {
    const el = document.getElementById(id);
    if (!input.files || !input.files[0]) return;
    const r = new FileReader();
    r.onload = e => { el.src = e.target.result; el.style.display = 'block'; };
    r.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
