<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/category-banner-texts-schema.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

ensureCategoryBannerTextsTable($db);

function cbtRedirect($categoryFilter) {
    header('Location: category-banner-texts.php' . ($categoryFilter ? '?category_id=' . (int)$categoryFilter : ''));
    exit();
}

function cbtColor($value, $fallback) {
    $value = trim((string)$value);
    return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ? $value : $fallback;
}

define('CBT_UPLOAD_DIR', 'uploads/category-banners/');

// Returns the stored relative path, '' when no file was sent; sets $error and returns false on failure
function cbtUploadImage($field, &$error) {
    $error = '';
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed (error code ' . (int)$file['error'] . ').';
        return false;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $error = 'Image is larger than 5MB.';
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($file['tmp_name']) === false) {
        $error = 'Invalid image. Use JPG, PNG, WEBP or GIF.';
        return false;
    }

    $dir = __DIR__ . '/' . CBT_UPLOAD_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    $name = 'cbt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $error = 'Could not save the image on the server. Check that ' . CBT_UPLOAD_DIR . ' is writable.';
        return false;
    }
    return CBT_UPLOAD_DIR . $name;
}

function cbtDeleteImage($path) {
    if ($path && strpos($path, CBT_UPLOAD_DIR) === 0 && is_file(__DIR__ . '/' . $path)) {
        @unlink(__DIR__ . '/' . $path);
    }
}

function cbtGetImage($db, $id) {
    $stmt = $db->prepare("SELECT image FROM category_banner_texts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['image'] ?? '';
}

// -------------------------------------------------------------
// ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filterBack = (int)($_POST['filter_category_id'] ?? 0);

    if ($action === 'toggle_status') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = (($_POST['status'] ?? '') === 'active') ? 'active' : 'inactive';
        $stmt = $db->prepare("UPDATE category_banner_texts SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $id);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'status' => $newStatus]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $oldImage = cbtGetImage($db, $id);
        $stmt = $db->prepare("DELETE FROM category_banner_texts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            cbtDeleteImage($oldImage);
            $_SESSION['success_message'] = 'Banner text deleted.';
        } else {
            $_SESSION['error_message'] = 'Could not delete banner text.';
        }
        cbtRedirect($filterBack);
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $buttonText = trim($_POST['button_text'] ?? '');
        $buttonLink = trim($_POST['button_link'] ?? '');
        $bgColor = cbtColor($_POST['bg_color'] ?? '', '#f1f5ff');
        $textColor = cbtColor($_POST['text_color'] ?? '', '#1e293b');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        $oldImage = $id > 0 ? cbtGetImage($db, $id) : '';
        $image = $oldImage;
        $uploadError = '';
        $uploaded = cbtUploadImage('image', $uploadError);
        if ($uploaded === false) {
            $_SESSION['error_message'] = $uploadError;
            cbtRedirect($filterBack);
        }
        if ($uploaded !== '') {
            $image = $uploaded;
        } elseif (!empty($_POST['remove_image'])) {
            $image = '';
        }

        if ($categoryId <= 0 || ($title === '' && $image === '')) {
            if ($uploaded !== '') cbtDeleteImage($uploaded);
            $_SESSION['error_message'] = 'Category and a title or banner image are required.';
            cbtRedirect($filterBack);
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE category_banner_texts SET category_id = ?, title = ?, description = ?, button_text = ?, button_link = ?, image = ?, bg_color = ?, text_color = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("isssssssisi", $categoryId, $title, $description, $buttonText, $buttonLink, $image, $bgColor, $textColor, $displayOrder, $status, $id);
        } else {
            $stmt = $db->prepare("INSERT INTO category_banner_texts (category_id, title, description, button_text, button_link, image, bg_color, text_color, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssis", $categoryId, $title, $description, $buttonText, $buttonLink, $image, $bgColor, $textColor, $displayOrder, $status);
        }

        if ($stmt->execute()) {
            if ($oldImage !== $image) cbtDeleteImage($oldImage);
            $_SESSION['success_message'] = $id > 0 ? 'Banner text updated.' : 'Banner text added.';
        } else {
            $_SESSION['error_message'] = 'Could not save banner text.';
        }
        cbtRedirect($filterBack);
    }
}

// -------------------------------------------------------------
// DATA: Only Main Categories (Level 1)
// -------------------------------------------------------------
$allCategories = [];
$catResult = $db->query("SELECT id, name, level, parent_id FROM categories ORDER BY display_order ASC, name ASC");
while ($catResult && ($row = $catResult->fetch_assoc())) {
    $allCategories[(int)$row['id']] = $row;
}

// Main Categories only (Level 1)
$categoryLabels = [];
$mainCatResult = $db->query("SELECT id, name, display_order FROM categories WHERE level = 1 ORDER BY display_order ASC, name ASC");
while ($mainCatResult && ($row = $mainCatResult->fetch_assoc())) {
    $categoryLabels[(int)$row['id']] = $row['name'];
}

$filterCategoryId = (int)($_GET['category_id'] ?? 0);

if ($filterCategoryId > 0) {
    $stmt = $db->prepare("SELECT * FROM category_banner_texts WHERE category_id = ? ORDER BY display_order ASC, id ASC");
    $stmt->bind_param("i", $filterCategoryId);
    $stmt->execute();
    $listResult = $stmt->get_result();
} else {
    $listResult = $db->query("SELECT * FROM category_banner_texts ORDER BY category_id ASC, display_order ASC, id ASC");
}
$bannerTexts = [];
while ($listResult && ($row = $listResult->fetch_assoc())) {
    $bannerTexts[] = $row;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Category Banner Texts | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .cbt-preview {
            border-radius: 10px;
            padding: 12px 16px;
            min-width: 260px;
            max-width: 420px;
        }
        .cbt-preview .cbt-title { font-weight: 700; font-size: 14px; margin-bottom: 2px; }
        .cbt-preview .cbt-desc { font-size: 12px; opacity: .85; margin: 0; }
        .cbt-preview .cbt-btn {
            display: inline-block; margin-top: 6px; font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px; border: 1px solid currentColor;
        }
        .action-icon-btn {
            width: 30px; height: 30px; border-radius: 6px; display: inline-flex;
            align-items: center; justify-content: center; border: 1px solid #e2e8f0;
            background: #fff; color: #64748b; font-size: 12px;
        }
        .action-icon-btn.btn-edit:hover { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
        .action-icon-btn.btn-del:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
        .form-control-color { width: 48px; padding: 4px; }
        .cbt-thumb { display: block; max-width: 420px; max-height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 6px; }
        #bt_image_preview { max-width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="mb-0"><strong>Category</strong> Banner Texts</h3>
                            <small class="text-muted">Shown on the product page below "About the product", based on the product's category.</small>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openBannerModal()">
                            <i class="fas fa-plus me-1"></i> Add Banner Text
                        </button>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header pb-0">
                            <form method="get" class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small text-muted mb-1">Filter by Main Category</label>
                                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">All Main Categories</option>
                                        <?php foreach ($categoryLabels as $cid => $label): ?>
                                            <option value="<?php echo $cid; ?>" <?php echo $filterCategoryId === $cid ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if ($filterCategoryId): ?>
                                    <div class="col-auto">
                                        <a href="category-banner-texts.php" class="btn btn-sm btn-light">Clear</a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Category</th>
                                            <th>Preview</th>
                                            <th style="width: 80px;">Order</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 100px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bannerTexts)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">No banner texts yet. Click "Add Banner Text" to create one.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($bannerTexts as $i => $bt): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php echo htmlspecialchars($categoryLabels[(int)$bt['category_id']] ?? ($allCategories[(int)$bt['category_id']]['name'] ?? ('#' . $bt['category_id'] . ' (deleted)'))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($bt['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($bt['image']); ?>" alt="" class="cbt-thumb">
                                                    <?php endif; ?>
                                                    <?php if ($bt['title'] !== ''): ?>
                                                    <div class="cbt-preview" style="background: <?php echo htmlspecialchars($bt['bg_color']); ?>; color: <?php echo htmlspecialchars($bt['text_color']); ?>;">
                                                        <div class="cbt-title"><?php echo htmlspecialchars($bt['title']); ?></div>
                                                        <?php if ($bt['description'] !== null && $bt['description'] !== ''): ?>
                                                            <p class="cbt-desc"><?php echo nl2br(htmlspecialchars($bt['description'])); ?></p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($bt['button_text'])): ?>
                                                            <span class="cbt-btn"><?php echo htmlspecialchars($bt['button_text']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo (int)$bt['display_order']; ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $bt['status'] === 'active' ? 'checked' : ''; ?>
                                                            onchange="toggleBannerStatus(<?php echo (int)$bt['id']; ?>, this)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="action-icon-btn btn-edit" title="Edit"
                                                        data-banner="<?php echo htmlspecialchars(json_encode($bt), ENT_QUOTES); ?>"
                                                        onclick="openBannerModal(JSON.parse(this.dataset.banner))">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this banner text?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int)$bt['id']; ?>">
                                                        <input type="hidden" name="filter_category_id" value="<?php echo $filterCategoryId; ?>">
                                                        <button type="submit" class="action-icon-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="post" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="bt_id" value="">
                <input type="hidden" name="filter_category_id" value="<?php echo $filterCategoryId; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="bannerModalTitle">Add Banner Text</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Main Category (Level 1) <span class="text-danger">*</span></label>
                            <select name="category_id" id="bt_category_id" class="form-select" required>
                                <option value="">Select Main Category</option>
                                <?php foreach ($categoryLabels as $cid => $label): ?>
                                    <option value="<?php echo $cid; ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <input type="number" name="display_order" id="bt_display_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="bt_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Banner Image <small class="text-muted">(JPG, PNG, WEBP, GIF · max 5MB · wide banners work best, e.g. 1200×300)</small></label>
                            <input type="file" name="image" id="bt_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImageFile(this)">
                            <div id="bt_image_wrap" class="mt-2 d-none">
                                <img id="bt_image_preview" src="" alt="Banner image preview">
                                <div class="form-check mt-1" id="bt_remove_wrap">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="bt_remove_image">
                                    <label class="form-check-label small" for="bt_remove_image">Remove current image</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Title <small class="text-muted">(required if no image)</small></label>
                            <input type="text" name="title" id="bt_title" class="form-control" maxlength="255" oninput="renderPreview()">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="bt_description" class="form-control" rows="3" oninput="renderPreview()"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button Text <small class="text-muted">(optional)</small></label>
                            <input type="text" name="button_text" id="bt_button_text" class="form-control" maxlength="100" oninput="renderPreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button Link <small class="text-muted">(optional)</small></label>
                            <input type="text" name="button_link" id="bt_button_link" class="form-control" maxlength="500" placeholder="/category/home-decor or https://...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Background</label>
                            <input type="color" name="bg_color" id="bt_bg_color" class="form-control form-control-color" value="#f1f5ff" oninput="renderPreview()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Text Color</label>
                            <input type="color" name="text_color" id="bt_text_color" class="form-control form-control-color" value="#1e293b" oninput="renderPreview()">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Preview</label>
                            <div class="cbt-preview" id="bt_preview" style="max-width: 100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const defaultCategoryId = '<?php echo $filterCategoryId ?: ''; ?>';

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        function renderPreview() {
            const preview = document.getElementById('bt_preview');
            const title = document.getElementById('bt_title').value || 'Banner title';
            const desc = document.getElementById('bt_description').value;
            const btn = document.getElementById('bt_button_text').value;
            preview.style.background = document.getElementById('bt_bg_color').value;
            preview.style.color = document.getElementById('bt_text_color').value;
            preview.innerHTML = '<div class="cbt-title">' + escapeHtml(title) + '</div>'
                + (desc ? '<p class="cbt-desc">' + escapeHtml(desc).replace(/\n/g, '<br>') + '</p>' : '')
                + (btn ? '<span class="cbt-btn">' + escapeHtml(btn) + '</span>' : '');
        }

        function openBannerModal(data) {
            data = data || {};
            document.getElementById('bannerModalTitle').textContent = data.id ? 'Edit Banner Text' : 'Add Banner Text';
            document.getElementById('bt_id').value = data.id || '';
            document.getElementById('bt_category_id').value = data.category_id || defaultCategoryId;
            document.getElementById('bt_title').value = data.title || '';
            document.getElementById('bt_description').value = data.description || '';
            document.getElementById('bt_button_text').value = data.button_text || '';
            document.getElementById('bt_button_link').value = data.button_link || '';
            document.getElementById('bt_bg_color').value = data.bg_color || '#f1f5ff';
            document.getElementById('bt_text_color').value = data.text_color || '#1e293b';
            document.getElementById('bt_display_order').value = data.display_order || 0;
            document.getElementById('bt_status').value = data.status || 'active';
            document.getElementById('bt_image').value = '';
            document.getElementById('bt_remove_image').checked = false;
            setImagePreview(data.image || '', !!data.image);
            renderPreview();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bannerModal')).show();
        }

        function setImagePreview(src, isSaved) {
            const wrap = document.getElementById('bt_image_wrap');
            document.getElementById('bt_image_preview').src = src;
            wrap.classList.toggle('d-none', !src);
            document.getElementById('bt_remove_wrap').classList.toggle('d-none', !isSaved);
        }

        function previewImageFile(input) {
            const file = input.files && input.files[0];
            if (!file) return;
            setImagePreview(URL.createObjectURL(file), false);
            document.getElementById('bt_remove_image').checked = false;
        }

        document.querySelector('#bannerModal form').addEventListener('submit', function (e) {
            const hasTitle = document.getElementById('bt_title').value.trim() !== '';
            const hasNewImage = document.getElementById('bt_image').files.length > 0;
            const hasSavedImage = !document.getElementById('bt_remove_wrap').classList.contains('d-none')
                && !document.getElementById('bt_remove_image').checked;
            if (!hasTitle && !hasNewImage && !hasSavedImage) {
                e.preventDefault();
                alert('Please add a title or upload a banner image.');
            }
        });

        function toggleBannerStatus(id, el) {
            const body = new URLSearchParams({ action: 'toggle_status', id: id, status: el.checked ? 'active' : 'inactive' });
            fetch('category-banner-texts.php', { method: 'POST', body: body })
                .then(r => r.json())
                .then(res => { if (!res.success) { el.checked = !el.checked; alert('Could not update status'); } })
                .catch(() => { el.checked = !el.checked; alert('Could not update status'); });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.body.style.opacity = 1;
        });
    </script>
</body>
</html>
