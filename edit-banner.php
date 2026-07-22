<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();
$sections = $bannerController->getAllSections();

$banner = null;
$is_edit = false;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $banner = $bannerController->getBannerById((int)$_GET['id']);
    $is_edit = true;
    if (!$banner) {
        $_SESSION['error_message'] = 'Banner not found.';
        header('Location: view-banner.php');
        exit;
    }
}

$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_edit) {
        $result = $bannerController->updateBanner((int)$_GET['id'], $_POST, $_FILES);
    } else {
        $result = $bannerController->createBanner($_POST, $_FILES);
    }

    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: view-banner.php');
        exit;
    } else {
        $error_message = $result['error'];
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Group sections by page for the dropdown
$sectionsByPage = [];
foreach ($sections as $s) {
    $sectionsByPage[$s['page']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Banner | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .required-field::after { content: " *"; color: #dc3545; }
        .section-info {
            background: #e8f4fd; border-left: 4px solid #0d6efd;
            padding: 10px 14px; border-radius: 4px;
            font-size: 0.875rem; margin-top: 8px; display: none;
        }
        .col-preview { display: flex; gap: 6px; height: 48px; margin-top: 8px; }
        .col-preview span { flex: 1; background: #adb5bd; border-radius: 3px; }
        .image-preview-container { background: #f8f9fa; padding: 12px; border-radius: 6px; min-height: 80px; }
        .preview-image { max-width: 100%; max-height: 160px; border-radius: 4px; }
        .current-image { max-width: 100%; max-height: 120px; border-radius: 4px; border: 1px solid #dee2e6; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-2 mb-xl-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3><strong><?php echo $is_edit ? 'Edit' : 'Add'; ?></strong> Banner</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="view-banner.php" class="btn btn-success">View All Banners</a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo $is_edit ? 'Edit' : 'New'; ?> Banner</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Title</label>
                                    <input type="text" class="form-control" name="title" required
                                           value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>"
                                           placeholder="Banner title (used as alt text)">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Target URL</label>
                                    <input type="text" class="form-control" name="target_url"
                                           value="<?php echo htmlspecialchars($banner['target_url'] ?? ''); ?>"
                                           placeholder="https://example.com/page">
                                    <small class="text-muted">Where the user goes when clicking this banner</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control" name="description" rows="2"
                                          placeholder="Short description"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Layout Format Selection -->
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Layout Format Structure</label>
                                    <?php $currentFmt = $banner['layout_format'] ?? 'desktop_format_1'; ?>
                                    <select name="layout_format" id="layoutFormatSelect" class="form-control" required>
                                        <optgroup label="Desktop Layout Formats (From Diagram)">
                                            <option value="desktop_format_1" data-d-dim="600×600 px (Square)" data-m-dim="600×600 px (Square)" <?php echo $currentFmt==='desktop_format_1'?'selected':''; ?>>Format 1: Single Square Offer Banner (1:1)</option>
                                            <option value="desktop_format_2" data-d-dim="Square: 600×600 px | Tall: 600×900 px" data-m-dim="600×400 px" <?php echo $currentFmt==='desktop_format_2'?'selected':''; ?>>Format 2: Square + Tall Offer Banner</option>
                                            <option value="desktop_format_3" data-d-dim="Squares: 600×600 px | Wide: 1200×400 px" data-m-dim="600×400 px" <?php echo $currentFmt==='desktop_format_3'?'selected':''; ?>>Format 3: 2 Square + 1 Wide Offer Banner</option>
                                            <option value="desktop_format_4" data-d-dim="600×600 px per item (2×3 Grid)" data-m-dim="600×600 px" <?php echo $currentFmt==='desktop_format_4'?'selected':''; ?>>Format 4: 2 Columns × 3 Rows Square Grid</option>
                                            <option value="desktop_format_5" data-d-dim="1920×250 px / 1200×200 px (Strip)" data-m-dim="800×250 px (Strip)" <?php echo $currentFmt==='desktop_format_5'?'selected':''; ?>>Format 5: Single Small Offer Banner (Strip)</option>
                                            <option value="desktop_format_6" data-d-dim="600×600 px per item (3×2 Grid)" data-m-dim="600×600 px" <?php echo $currentFmt==='desktop_format_6'?'selected':''; ?>>Format 6: 3 Columns × 2 Rows Square Grid</option>
                                            <option value="desktop_carousel_1" data-d-dim="1920×600 px / 1200×400 px" data-m-dim="600×400 px" <?php echo $currentFmt==='desktop_carousel_1'?'selected':''; ?>>Desktop Carousel: Single Full Width Banner</option>
                                            <option value="desktop_carousel_multi" data-d-dim="960×450 px (2-Col) / 640×360 px (3-Col)" data-m-dim="600×400 px" <?php echo $currentFmt==='desktop_carousel_multi'?'selected':''; ?>>Desktop Carousel: Multi-Column Carousel</option>
                                        </optgroup>
                                        <optgroup label="Mobile Layout Options">
                                            <option value="mobile_carousel_square" data-d-dim="600×600 px" data-m-dim="600×600 px (Square 1:1)" <?php echo $currentFmt==='mobile_carousel_square'?'selected':''; ?>>Mobile Carousel: Single Square (1:1)</option>
                                            <option value="mobile_carousel_double" data-d-dim="600×400 px" data-m-dim="600×400 px (2-Items)" <?php echo $currentFmt==='mobile_carousel_double'?'selected':''; ?>>Mobile Carousel: Double Image Normal Height</option>
                                            <option value="mobile_carousel_peek" data-d-dim="600×400 px" data-m-dim="600×400 px (1.5 Peek View)" <?php echo $currentFmt==='mobile_carousel_peek'?'selected':''; ?>>Mobile Carousel: 1.5 Peek Carousel</option>
                                            <option value="mobile_carousel_small" data-d-dim="800×250 px" data-m-dim="800×250 px (Small Height)" <?php echo $currentFmt==='mobile_carousel_small'?'selected':''; ?>>Mobile Carousel: Single Small Carousel</option>
                                            <option value="mobile_banner_square" data-d-dim="600×600 px" data-m-dim="600×600 px (Square)" <?php echo $currentFmt==='mobile_banner_square'?'selected':''; ?>>Mobile Static Banner: Single Square Banner</option>
                                            <option value="mobile_banner_two" data-d-dim="600×400 px" data-m-dim="600×400 px (2-Items)" <?php echo $currentFmt==='mobile_banner_two'?'selected':''; ?>>Mobile Static Banner: Two Image Normal</option>
                                            <option value="mobile_banner_small" data-d-dim="800×250 px" data-m-dim="800×250 px (Small Strip)" <?php echo $currentFmt==='mobile_banner_small'?'selected':''; ?>>Mobile Static Banner: Single Small Banner</option>
                                        </optgroup>
                                    </select>
                                    <small class="text-muted">Select the layout structure to display recommended dimensions below</small>
                                </div>
                            </div>

                            <!-- Section picker -->
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Page Section</label>
                                    <select name="section_id" id="sectionSelect" class="form-control" required>
                                        <option value="">— Select a section —</option>
                                        <?php foreach ($sectionsByPage as $page => $pageSections): ?>
                                            <optgroup label="<?php echo strtoupper(htmlspecialchars($page)); ?> page">
                                                <?php foreach ($pageSections as $s): ?>
                                                    <option value="<?php echo $s['id']; ?>"
                                                            data-cols="<?php echo $s['columns_per_row']; ?>"
                                                            data-slider="<?php echo $s['is_slider'] ? 1 : 0; ?>"
                                                            <?php echo (isset($banner['section_id']) && $banner['section_id'] == $s['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($s['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="section-info" id="sectionInfo">
                                        <strong id="sectionInfoType"></strong>
                                        &nbsp;|&nbsp; Columns in this section: <strong id="sectionInfoCols"></strong>
                                        <div class="col-preview" id="colPreview"></div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order"
                                           value="<?php echo (int)($banner['display_order'] ?? 0); ?>" min="0">
                                    <small class="text-muted">Lower = shown first within section</small>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active"   <?php echo (($banner['status'] ?? '') === 'active')   ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (($banner['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Desktop Image <?php echo $is_edit ? '<small class="text-muted">(leave blank to keep current)</small>' : '<span class="text-danger">*</span>'; ?></h6>
                                            <span class="badge bg-primary" id="desktopDimBadge">Recommended: 600×600 px</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($is_edit && !empty($banner['image_url_desktop'])): ?>
                                                <p class="mb-1"><small class="text-muted">Current:</small></p>
                                                <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" class="current-image mb-2">
                                            <?php endif; ?>
                                            <input type="file" class="form-control mb-2" name="image_desktop"
                                                   accept="image/*" <?php echo $is_edit ? '' : 'required'; ?> id="desktopFile">
                                            <small class="text-muted" id="desktopDimGuide">Recommended: 600×600 px · max 5 MB</small>
                                            <div class="image-preview-container mt-2" id="desktopPreview">
                                                <small class="text-muted">New image preview will appear here</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Mobile Image <small class="text-muted">(optional)</small></h6>
                                            <span class="badge bg-info text-dark" id="mobileDimBadge">Recommended: 600×600 px</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($is_edit && !empty($banner['image_url_mobile'])): ?>
                                                <p class="mb-1"><small class="text-muted">Current:</small></p>
                                                <img src="<?php echo htmlspecialchars($banner['image_url_mobile']); ?>" class="current-image mb-2">
                                            <?php endif; ?>
                                            <input type="file" class="form-control mb-2" name="image_mobile"
                                                   accept="image/*" id="mobileFile">
                                            <small class="text-muted">Recommended 768×400 px · max 5 MB<br>
                                                If blank, desktop image is used on mobile.</small>
                                            <div class="image-preview-container mt-2" id="mobilePreview">
                                                <small class="text-muted">New image preview will appear here</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Start Date <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" class="form-control" name="start_date"
                                           value="<?php echo $banner['start_date'] ? date('Y-m-d\TH:i', strtotime($banner['start_date'])) : ''; ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">End Date <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" class="form-control" name="end_date"
                                           value="<?php echo $banner['end_date'] ? date('Y-m-d\TH:i', strtotime($banner['end_date'])) : ''; ?>">
                                </div>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <?php echo $is_edit ? 'Update Banner' : 'Create Banner'; ?>
                                </button>
                                <a href="view-banner.php" class="btn btn-secondary ms-2">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
// Layout Format — update dimension badges & helper guide text
const layoutFormatSelect = document.getElementById('layoutFormatSelect');
const desktopDimBadge = document.getElementById('desktopDimBadge');
const desktopDimGuide = document.getElementById('desktopDimGuide');
const mobileDimBadge  = document.getElementById('mobileDimBadge');
const mobileDimGuide  = document.getElementById('mobileDimGuide');

function updateDimensionGuides() {
    const opt = layoutFormatSelect.options[layoutFormatSelect.selectedIndex];
    if (!opt) return;
    const dDim = opt.dataset.dDim || '1920×600 px';
    const mDim = opt.dataset.mDim || '768×400 px';

    desktopDimBadge.textContent = `Recommended: ${dDim}`;
    desktopDimGuide.innerHTML   = `Recommended: ${dDim} · max 5 MB`;

    mobileDimBadge.textContent  = `Recommended: ${mDim}`;
    mobileDimGuide.innerHTML    = `Recommended: ${mDim} · max 5 MB<br>If blank, desktop image is used on mobile.`;
}

if (layoutFormatSelect) {
    layoutFormatSelect.addEventListener('change', updateDimensionGuides);
    updateDimensionGuides(); // initial trigger
}

const sectionSelect = document.getElementById('sectionSelect');
const sectionInfo   = document.getElementById('sectionInfo');
const infoType      = document.getElementById('sectionInfoType');
const infoCols      = document.getElementById('sectionInfoCols');
const colPreview    = document.getElementById('colPreview');

function updateSectionInfo() {
    const opt = sectionSelect.options[sectionSelect.selectedIndex];
    if (!opt.value) { sectionInfo.style.display = 'none'; return; }

    const cols   = parseInt(opt.dataset.cols) || 1;
    const slider = opt.dataset.slider === '1';

    infoType.textContent = slider ? 'Slider / Carousel' : 'Static Grid';
    infoCols.textContent = cols;

    colPreview.innerHTML = '';
    for (let i = 0; i < cols; i++) {
        colPreview.appendChild(document.createElement('span'));
    }
    sectionInfo.style.display = 'block';
}

sectionSelect.addEventListener('change', updateSectionInfo);
// Show info on load if editing and section already selected
if (sectionSelect.value) updateSectionInfo();

function previewImage(input, containerId) {
    input.addEventListener('change', function () {
        const container = document.getElementById(containerId);
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                container.innerHTML = `<img src="${e.target.result}" class="preview-image">`;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}
previewImage(document.getElementById('desktopFile'), 'desktopPreview');
previewImage(document.getElementById('mobileFile'),  'mobilePreview');
</script>
</body>
</html>
