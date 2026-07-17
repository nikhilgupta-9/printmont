<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/CategoryController.php');

$controller = new CategoryController();
$menuCategories = $controller->getSubAndSubSubCategories();
$error   = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$selectedId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
$selected   = $selectedId ? $controller->getCategoryById($selectedId) : null;

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
            'name'                => trim($_POST['name'] ?? $selected['name']),
            'desktop_menu_design' => $_POST['desktop_menu_design'] ?? '',
            'desktop_menu_image'  => uploadInsideImg('desktop_menu_image', 'menu', $selected['desktop_menu_image'] ?? ''),
            'desktop_menu_order'  => (int)($_POST['desktop_menu_order'] ?? 0),
            'desktop_menu_status' => $_POST['desktop_menu_status'] ?? 'show',
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
                        <label class="form-label required-field">Sub / Sub-Sub Category</label>
                        <select id="categorySelect" class="form-control" onchange="location.href='add-menu-page.php?category_id='+this.value">
                            <option value="">— Select Category —</option>
                            <?php foreach ($menuCategories as $mc): ?>
                                <option value="<?php echo $mc['id']; ?>" <?php echo $selectedId == $mc['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mc['parent_name'] . ' › ' . $mc['name']); ?>
                                    <?php echo $mc['level'] == 3 ? ' (Sub-Sub)' : ' (Sub)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">This controls how the item appears inside its parent's mega-menu dropdown.</small>
                    </div>
                </div>

                <?php if ($selected): ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="category_id" value="<?php echo $selectedId; ?>">
                    <input type="hidden" name="save" value="1">

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Menu Page Settings</h5></div>
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
