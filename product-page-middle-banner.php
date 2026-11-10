<?php
// Dedicated editor for the single banner shown on the product page, directly above the
// "Similar Products" carousel (frontend: ProductDetails.jsx / MobileProductDetails.jsx,
// <SmallBannerCarousel sectionKey="product_page_middle" page="product" ...>).
// Writes into the same `banners` / `banner_sections` tables the generic Page Banners screen
// (page-banners.php / add-page-banner.php) uses, just pre-filtered to this one section so there
// is no page/section picker to get wrong. Replaces the old category-banner-texts.php page,
// which controlled an unrelated banner (now moved into Add/Edit Product).
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/models/BannerModel.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$bannerModel = new BannerModel();

define('PPM_PAGE', 'product');
define('PPM_SECTION_KEY', 'product_page_middle');
define('PPM_LABEL', 'Product Page – Middle Banner');
define('PPM_UPLOAD_DIR', 'uploads/product-page-middle-banner/');

function ppmRedirect() {
    header('Location: product-page-middle-banner.php');
    exit();
}

// Returns the stored relative path, '' when no file was sent; sets $error and returns false on failure
function ppmUploadImage($field, &$error) {
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

    $dir = __DIR__ . '/' . PPM_UPLOAD_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    $name = 'ppm_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $error = 'Could not save the image on the server. Check that ' . PPM_UPLOAD_DIR . ' is writable.';
        return false;
    }
    return PPM_UPLOAD_DIR . $name;
}

// Resolve (or create, first time) the dedicated section for this banner slot
$sectionId = 0;
$sectionKey = PPM_SECTION_KEY;
$stmt = $db->prepare("SELECT id FROM banner_sections WHERE section_key = ? LIMIT 1");
$stmt->bind_param('s', $sectionKey);
$stmt->execute();
$sectionRow = $stmt->get_result()->fetch_assoc();
if ($sectionRow) {
    $sectionId = (int) $sectionRow['id'];
} else {
    $sectionId = (int) $bannerModel->createSection(PPM_PAGE, PPM_SECTION_KEY, PPM_LABEL, 0, 1);
}

// -------------------------------------------------------------
// ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = (($_POST['status'] ?? '') === 'active') ? 'active' : 'inactive';
        $ok = $bannerModel->toggleBannerStatus($id, $newStatus);
        echo json_encode(['success' => $ok, 'status' => $newStatus]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($bannerModel->deleteBanner($id)) {
            $_SESSION['success_message'] = 'Banner deleted.';
        } else {
            $_SESSION['error_message'] = 'Could not delete banner.';
        }
        ppmRedirect();
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $status = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        $existing = $id > 0 ? $bannerModel->getBannerById($id) : null;
        $oldImage = $existing['desktop_image'] ?? '';
        $image = $oldImage;

        $uploadError = '';
        $uploaded = ppmUploadImage('image', $uploadError);
        if ($uploaded === false) {
            $_SESSION['error_message'] = $uploadError;
            ppmRedirect();
        }
        if ($uploaded !== '') {
            $image = $uploaded;
        } elseif (!empty($_POST['remove_image'])) {
            $image = '';
        }

        if ($title === '' && $image === '') {
            if ($uploaded !== '' && is_string($uploaded)) @unlink(__DIR__ . '/' . $uploaded);
            $_SESSION['error_message'] = 'A title or a banner image is required.';
            ppmRedirect();
        }

        $data = [
            'title'              => $title !== '' ? $title : PPM_LABEL,
            'description'        => '',
            'target_url'         => $link,
            'section_id'         => $sectionId,
            'layout_format'      => 'desktop_format_1',
            'display_order'      => $displayOrder,
            'status'             => $status,
            'banner_type'        => 'single',
            'image_url_desktop'  => $image,
            'image_url_mobile'   => $image,
            'desktop_image'      => $image,
            'desktop_url'        => $link,
            'desktop_status'     => $status,
            'desktop_sort_order' => $displayOrder,
            'mobile_image'       => $image,
            'mobile_url'         => $link,
            'mobile_status'      => $status,
            'mobile_sort_order'  => $displayOrder,
            'auto_running'       => 'enable',
            'auto_run_speed'     => 3000,
            'button_arrows'      => 'enable',
        ];

        $ok = $id > 0 ? $bannerModel->updateBanner($id, $data) : $bannerModel->createBanner($data);
        if ($ok) {
            if ($oldImage && $oldImage !== $image && strpos($oldImage, PPM_UPLOAD_DIR) === 0 && is_file(__DIR__ . '/' . $oldImage)) {
                @unlink(__DIR__ . '/' . $oldImage);
            }
            $_SESSION['success_message'] = $id > 0 ? 'Banner updated.' : 'Banner added.';
        } else {
            $_SESSION['error_message'] = 'Could not save banner.';
        }
        ppmRedirect();
    }
}

// -------------------------------------------------------------
// DATA
// -------------------------------------------------------------
$banners = $bannerModel->getBannersBySection($sectionId);
// getBannersBySection() only returns active rows; pull inactive ones too so admins can re-enable them
$inactiveRes = $db->prepare("SELECT * FROM banners WHERE section_id = ? AND status = 'inactive' ORDER BY display_order ASC");
$inactiveRes->bind_param('i', $sectionId);
$inactiveRes->execute();
$inactiveRows = $inactiveRes->get_result()->fetch_all(MYSQLI_ASSOC);
$banners = array_merge($banners, $inactiveRows);
usort($banners, fn($a, $b) => ($a['display_order'] ?? 0) <=> ($b['display_order'] ?? 0));

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
    <title>Product Page – Middle Banner | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .ppm-thumb { display: block; max-width: 420px; max-height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 6px; }
        .action-icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 12px; }
        .action-icon-btn.btn-edit:hover { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
        .action-icon-btn.btn-del:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
        #ppm_image_preview { max-width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; }
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
                            <h3 class="mb-0"><strong>Product Page</strong> – Middle Banner</h3>
                            <small class="text-muted">Shown on every product page, directly above the "Similar Products" carousel.</small>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openBannerModal()">
                            <i class="fas fa-plus me-1"></i> Add Banner
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
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Preview</th>
                                            <th style="width: 80px;">Order</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 100px;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($banners)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No banner yet. Click "Add Banner" to create one.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($banners as $i => $b): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td>
                                                    <?php if (!empty($b['desktop_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($b['desktop_image']); ?>" alt="" class="ppm-thumb">
                                                    <?php endif; ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($b['title']); ?></div>
                                                </td>
                                                <td><?php echo (int) $b['display_order']; ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $b['status'] === 'active' ? 'checked' : ''; ?>
                                                            onchange="toggleStatus(<?php echo (int) $b['id']; ?>, this)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="action-icon-btn btn-edit" title="Edit"
                                                        data-banner="<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES); ?>"
                                                        onclick="openBannerModal(JSON.parse(this.dataset.banner))">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this banner?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int) $b['id']; ?>">
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
                <input type="hidden" name="id" id="ppm_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="bannerModalTitle">Add Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Banner Image <small class="text-muted">(JPG, PNG, WEBP, GIF · max 5MB · wide banners work best, e.g. 1200×300)</small></label>
                            <input type="file" name="image" id="ppm_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImageFile(this)">
                            <div id="ppm_image_wrap" class="mt-2 d-none">
                                <img id="ppm_image_preview" src="" alt="Banner image preview">
                                <div class="form-check mt-1" id="ppm_remove_wrap">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="ppm_remove_image">
                                    <label class="form-check-label small" for="ppm_remove_image">Remove current image</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Title <small class="text-muted">(for your reference; required if no image)</small></label>
                            <input type="text" name="title" id="ppm_title" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Link <small class="text-muted">(optional)</small></label>
                            <input type="text" name="link" id="ppm_link" class="form-control" maxlength="500" placeholder="/category/home-decor or https://...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <input type="number" name="display_order" id="ppm_display_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="ppm_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
        function openBannerModal(data) {
            data = data || {};
            document.getElementById('bannerModalTitle').textContent = data.id ? 'Edit Banner' : 'Add Banner';
            document.getElementById('ppm_id').value = data.id || '';
            document.getElementById('ppm_title').value = data.title || '';
            document.getElementById('ppm_link').value = data.desktop_url || data.target_url || '';
            document.getElementById('ppm_display_order').value = data.display_order || 0;
            document.getElementById('ppm_status').value = data.status || 'active';
            document.getElementById('ppm_image').value = '';
            document.getElementById('ppm_remove_image').checked = false;
            setImagePreview(data.desktop_image || '', !!data.desktop_image);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bannerModal')).show();
        }

        function setImagePreview(src, isSaved) {
            const wrap = document.getElementById('ppm_image_wrap');
            document.getElementById('ppm_image_preview').src = src;
            wrap.classList.toggle('d-none', !src);
            document.getElementById('ppm_remove_wrap').classList.toggle('d-none', !isSaved);
        }

        function previewImageFile(input) {
            const file = input.files && input.files[0];
            if (!file) return;
            setImagePreview(URL.createObjectURL(file), false);
            document.getElementById('ppm_remove_image').checked = false;
        }

        document.querySelector('#bannerModal form').addEventListener('submit', function (e) {
            const hasTitle = document.getElementById('ppm_title').value.trim() !== '';
            const hasNewImage = document.getElementById('ppm_image').files.length > 0;
            const hasSavedImage = !document.getElementById('ppm_remove_wrap').classList.contains('d-none')
                && !document.getElementById('ppm_remove_image').checked;
            if (!hasTitle && !hasNewImage && !hasSavedImage) {
                e.preventDefault();
                alert('Please add a title or upload a banner image.');
            }
        });

        function toggleStatus(id, el) {
            const body = new URLSearchParams({ action: 'toggle_status', id: id, status: el.checked ? 'active' : 'inactive' });
            fetch('product-page-middle-banner.php', { method: 'POST', body: body })
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
