<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();
$sections = $bannerController->getAllSections();

$error_message   = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $bannerController->createBanner($_POST, $_FILES);
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
    <title>Add Banner | Printmont</title>
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
                        <h3><strong>Add</strong> Banner</h3>
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
                        <h5 class="card-title mb-0">New Banner</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label required-field">Title</label>
                                    <input type="text" class="form-control" name="title" required
                                           placeholder="Banner title (used as alt text)">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Target URL</label>
                                    <input type="text" class="form-control" name="target_url"
                                           placeholder="https://example.com/page">
                                    <small class="text-muted">Where the user goes when clicking this banner</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control" name="description" rows="2"
                                          placeholder="Short description"></textarea>
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
                                                            data-slider="<?php echo $s['is_slider'] ? 1 : 0; ?>">
                                                        <?php echo htmlspecialchars($s['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>

                                    <!-- Live section info -->
                                    <div class="section-info" id="sectionInfo">
                                        <strong id="sectionInfoType"></strong>
                                        &nbsp;|&nbsp; Columns in this section: <strong id="sectionInfoCols"></strong>
                                        <div class="col-preview" id="colPreview"></div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="display_order" value="0" min="0">
                                    <small class="text-muted">Lower = shown first within section</small>
                                </div>
                                <div class="mb-3 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header"><h6 class="mb-0">Desktop Image <span class="text-danger">*</span></h6></div>
                                        <div class="card-body">
                                            <input type="file" class="form-control mb-2" name="image_desktop"
                                                   accept="image/*" required id="desktopFile">
                                            <small class="text-muted">Recommended 1920×600 px · max 5 MB</small>
                                            <div class="image-preview-container mt-2" id="desktopPreview">
                                                <small class="text-muted">Preview will appear here</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header"><h6 class="mb-0">Mobile Image <small class="text-muted">(optional)</small></h6></div>
                                        <div class="card-body">
                                            <input type="file" class="form-control mb-2" name="image_mobile"
                                                   accept="image/*" id="mobileFile">
                                            <small class="text-muted">Recommended 768×400 px · max 5 MB<br>
                                                If blank, desktop image is used on mobile.</small>
                                            <div class="image-preview-container mt-2" id="mobilePreview">
                                                <small class="text-muted">Preview will appear here</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule -->
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Start Date <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" class="form-control" name="start_date">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">End Date <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" class="form-control" name="end_date">
                                </div>
                            </div>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-lg">Create Banner</button>
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
// Section picker — show columns info & column preview
const sectionSelect = document.getElementById('sectionSelect');
const sectionInfo   = document.getElementById('sectionInfo');
const infoType      = document.getElementById('sectionInfoType');
const infoCols      = document.getElementById('sectionInfoCols');
const colPreview    = document.getElementById('colPreview');

sectionSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
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
});

// Image previews
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
