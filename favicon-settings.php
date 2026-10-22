<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

// Handle Favicon Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_favicon') {
        $postData = [
            'asset_type'  => 'favicon',
            'asset_name'  => !empty(trim($_POST['asset_name'] ?? '')) ? trim($_POST['asset_name']) : 'Printmont Favicon',
            'description' => trim($_POST['description'] ?? 'Official website favicon icon'),
            'alt_text'    => trim($_POST['alt_text'] ?? 'Printmont Favicon'),
            'target_url'  => trim($_POST['target_url'] ?? ''),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'uploaded_by' => $_SESSION['user_id'] ?? 1
        ];

        if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === 0) {
            $result = $logoController->createLogo($postData, $_FILES['favicon_file']);
            if ($result['success']) {
                $_SESSION['success_message'] = 'Favicon uploaded and synced across Frontend & Admin successfully!';
            } else {
                $_SESSION['error_message'] = 'Favicon upload failed: ' . $result['message'];
            }
        } else {
            $_SESSION['error_message'] = 'Please select a valid image file to upload.';
        }

        header('Location: favicon-settings.php');
        exit();
    }
}

// Fetch active favicon and all favicon versions
$activeFavicon = $logoController->getFavicon();
$allFaviconsResult = $logoController->getLogosByType('favicon');
$faviconsList = [];
if ($allFaviconsResult && $allFaviconsResult->num_rows > 0) {
    while ($row = $allFaviconsResult->fetch_assoc()) {
        $faviconsList[] = $row;
    }
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
    <title>Favicon Settings | Printmont Admin</title>
    <link rel="shortcut icon" href="<?php echo !empty($activeFavicon['file_path']) ? htmlspecialchars($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png'; ?>?v=<?php echo time(); ?>" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .favicon-card-preview {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0 auto;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
        }
        .favicon-card-preview img {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }
        .browser-mockup {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .browser-header {
            background: #e2e8f0;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .browser-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
        }
        .browser-dot-red { background: #ef4444; }
        .browser-dot-yellow { background: #f59e0b; }
        .browser-dot-green { background: #10b981; }
        .browser-tab {
            background: #ffffff;
            padding: 6px 14px;
            border-radius: 8px 8px 0 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            margin-left: 12px;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.04);
            max-width: 320px;
        }
        .browser-tab-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            border-radius: 2px;
        }
        .browser-tab-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .browser-address-bar {
            background: #ffffff;
            margin: 10px 14px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pulse-active-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 rgba(34, 197, 94, 0.4);
            animation: pulse-ring 1.8s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .table-thumb {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .table-thumb img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .upload-dropzone {
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .upload-dropzone:hover {
            border-color: #3b82f6;
            background: #eff6ff;
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
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-1"><strong>Favicon</strong> Settings</h3>
                            <p class="text-muted mb-0">Upload and configure the official browser icon synchronized dynamically across Storefront &amp; Admin.</p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                            <a href="logo-management.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-images me-1"></i> Logo Management
                            </a>
                            <a href="general-settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-sliders-h me-1"></i> General Settings
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Active Favicon & Live Browser Preview -->
                    <div class="row">
                        <!-- Left: Active Favicon Spotlight -->
                        <div class="col-lg-5 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom py-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="card-title mb-0 fw-bold">Active Favicon</h5>
                                        <?php if (!empty($activeFavicon)): ?>
                                            <span class="pulse-active-badge">
                                                <span class="pulse-dot"></span> Live in Production
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">No Active Icon</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body text-center p-4">
                                    <div class="favicon-card-preview mb-3">
                                        <?php 
                                        $activeImgSrc = !empty($activeFavicon['file_path']) ? ($activeFavicon['file_path'] . $activeFavicon['file_name']) : 'favicon.png';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($activeImgSrc); ?>?v=<?php echo time(); ?>" alt="Active Favicon" id="spotlightFaviconImg">
                                    </div>

                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($activeFavicon['asset_name'] ?? 'Default Brand Favicon'); ?></h5>
                                    <p class="text-muted small mb-3">Version <?php echo (int)($activeFavicon['version'] ?? 1); ?> &bull; <?php echo strtoupper($activeFavicon['file_extension'] ?? 'PNG'); ?></p>

                                    <div class="row g-2 text-start mb-4 bg-light p-3 rounded-3 border">
                                        <div class="col-6">
                                            <small class="text-muted d-block">File Size</small>
                                            <span class="fw-semibold small"><?php echo !empty($activeFavicon['file_size']) ? round($activeFavicon['file_size'] / 1024, 2) . ' KB' : '~9.5 KB'; ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Dimensions</small>
                                            <span class="fw-semibold small"><?php echo htmlspecialchars($activeFavicon['dimensions'] ?? '512x512 px'); ?></span>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted d-block">Asset Path</small>
                                            <span class="fw-semibold small text-truncate d-block" title="<?php echo htmlspecialchars($activeImgSrc); ?>">
                                                <?php echo htmlspecialchars($activeImgSrc); ?>
                                            </span>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted d-block">Sync Status</small>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                <i class="fas fa-check-double me-1"></i> Synced (Admin + Store)
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Live Browser Mockup -->
                                    <div class="text-start">
                                        <label class="form-label small fw-bold text-muted mb-2">
                                            <i class="fas fa-desktop me-1"></i> Live Browser Tab Simulation
                                        </label>
                                        <div class="browser-mockup">
                                            <div class="browser-header">
                                                <span class="browser-dot browser-dot-red"></span>
                                                <span class="browser-dot browser-dot-yellow"></span>
                                                <span class="browser-dot browser-dot-green"></span>
                                                <div class="browser-tab">
                                                    <img src="<?php echo htmlspecialchars($activeImgSrc); ?>?v=<?php echo time(); ?>" class="browser-tab-icon" alt="Favicon">
                                                    <span class="browser-tab-title">Printmont | Custom Clothing &amp; Gifts</span>
                                                    <i class="fas fa-times ms-1 text-muted" style="font-size:10px;"></i>
                                                </div>
                                            </div>
                                            <div class="browser-address-bar">
                                                <i class="fas fa-lock text-success" style="font-size:11px;"></i>
                                                <span>https://printmont.com/</span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($activeFavicon)): ?>
                                    <div class="mt-3 text-center">
                                        <a href="<?php echo htmlspecialchars($activeImgSrc); ?>" download="favicon.<?php echo $activeFavicon['file_extension'] ?? 'png'; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-download me-1"></i> Download Active Favicon
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Upload New Favicon -->
                        <div class="col-lg-7 mb-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="card-title mb-0 fw-bold">Upload New Favicon</h5>
                                    <h6 class="card-subtitle text-muted mt-1">Upload a new icon to instantly update storefront &amp; admin branding.</h6>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" enctype="multipart/form-data" id="faviconUploadForm">
                                        <input type="hidden" name="action" value="create_favicon">

                                        <!-- Upload Dropzone -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Select Favicon File <span class="text-danger">*</span></label>
                                            <div class="upload-dropzone" onclick="document.getElementById('faviconFileInput').click();">
                                                <input type="file" id="faviconFileInput" name="favicon_file" accept=".png,.ico,.svg,.webp,.jpg,.jpeg" style="display:none;" required>
                                                <div id="dropzoneContent">
                                                    <i class="fas fa-cloud-upload-alt text-primary mb-2" style="font-size: 36px;"></i>
                                                    <p class="mb-1 fw-semibold text-dark">Click to browse or drag &amp; drop your favicon image</p>
                                                    <span class="text-muted small">Supported: <strong>.ICO, .PNG, .SVG, .WEBP</strong> (Max: 5MB)</span>
                                                </div>
                                                <div id="dropzonePreview" class="d-none mt-2">
                                                    <img id="previewImg" src="#" alt="Preview" style="max-height: 60px; max-width: 60px; border-radius: 6px; object-fit: contain;">
                                                    <p class="small text-success mt-2 mb-0 fw-semibold" id="previewFileName"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Asset Name</label>
                                                <input type="text" class="form-control" name="asset_name" placeholder="e.g. Printmont Brand Favicon v<?php echo count($faviconsList) + 1; ?>" value="Printmont Favicon v<?php echo count($faviconsList) + 1; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Alt / Label Text</label>
                                                <input type="text" class="form-control" name="alt_text" placeholder="e.g. Printmont Official Favicon" value="Printmont Favicon">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Internal Notes / Description</label>
                                            <textarea class="form-control" name="description" rows="2" placeholder="Optional notes for this favicon update..."></textarea>
                                        </div>

                                        <div class="mb-4 bg-light p-3 rounded-3 border">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="isActiveCheck" name="is_active" value="1" checked>
                                                <label class="form-check-label fw-semibold ms-2" for="isActiveCheck">
                                                    Activate immediately &amp; synchronize everywhere
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ps-4 ms-2">
                                                When checked, this icon will instantly overwrite the active favicon across the React storefront, Admin dashboard, and HTML headers.
                                            </small>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="reset" class="btn btn-light px-3" onclick="resetDropzone()">Reset</button>
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-upload me-1"></i> Upload &amp; Sync Favicon
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon History & Version Management -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">Favicon Versions &amp; History</h5>
                                        <h6 class="card-subtitle text-muted mt-1">Manage past uploaded versions. You can switch the active favicon with 1-click.</h6>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo count($faviconsList); ?> Versions</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 70px;">Icon</th>
                                                    <th>Asset Name</th>
                                                    <th>Format</th>
                                                    <th>Dimensions</th>
                                                    <th>File Size</th>
                                                    <th>Version</th>
                                                    <th>Status</th>
                                                    <th>Uploaded Date</th>
                                                    <th class="text-end" style="width: 140px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($faviconsList)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4 text-muted">
                                                            <i class="fas fa-info-circle me-1"></i> No favicon records found. Upload one above.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($faviconsList as $fav): ?>
                                                        <tr class="<?php echo $fav['is_active'] ? 'table-success-subtle' : ''; ?>">
                                                            <td>
                                                                <div class="table-thumb">
                                                                    <img src="<?php echo htmlspecialchars($fav['file_path'] . $fav['file_name']); ?>?v=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($fav['alt_text'] ?? 'Favicon'); ?>">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($fav['asset_name']); ?></strong>
                                                                <?php if (!empty($fav['description'])): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($fav['description']); ?></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border">
                                                                    <?php echo strtoupper($fav['file_extension']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($fav['dimensions'] ?: '—'); ?></td>
                                                            <td><?php echo round($fav['file_size'] / 1024, 2); ?> KB</td>
                                                            <td>
                                                                <span class="badge bg-primary-subtle text-primary">
                                                                    v<?php echo (int)$fav['version']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($fav['is_active']): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check me-1"></i> Active
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-muted small">
                                                                <?php echo date('d M Y, H:i', strtotime($fav['upload_timestamp'])); ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <?php if (!$fav['is_active']): ?>
                                                                    <a href="activate-logo.php?id=<?php echo (int)$fav['id']; ?>&redirect=favicon-settings.php" 
                                                                       class="btn btn-sm btn-outline-success" 
                                                                       title="Make Active &amp; Sync"
                                                                       onclick="return confirm('Activate this version as the official brand favicon?');">
                                                                        <i class="fas fa-check"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <a href="<?php echo htmlspecialchars($fav['file_path'] . $fav['file_name']); ?>" download class="btn btn-sm btn-outline-secondary" title="Download">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                                <a href="delete-logo.php?id=<?php echo (int)$fav['id']; ?>&redirect=favicon-settings.php" 
                                                                   class="btn btn-sm btn-outline-danger" 
                                                                   title="Delete"
                                                                   onclick="return confirm('Are you sure you want to delete this favicon version?');">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Dropzone preview handler
        const fileInput = document.getElementById('faviconFileInput');
        const dropzoneContent = document.getElementById('dropzoneContent');
        const dropzonePreview = document.getElementById('dropzonePreview');
        const previewImg = document.getElementById('previewImg');
        const previewFileName = document.getElementById('previewFileName');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                    dropzoneContent.classList.add('d-none');
                    dropzonePreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });

        function resetDropzone() {
            fileInput.value = '';
            dropzoneContent.classList.remove('d-none');
            dropzonePreview.classList.add('d-none');
        }

        // Drag & drop support
        const dropzone = document.querySelector('.upload-dropzone');
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = '#3b82f6';
                dropzone.style.background = '#eff6ff';
            }, false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = '#94a3b8';
                dropzone.style.background = '#f8fafc';
            }, false);
        });
        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                fileInput.files = files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>
