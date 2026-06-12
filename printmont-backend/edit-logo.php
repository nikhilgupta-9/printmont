<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

$logo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get logo data
$logo_result = $logoController->getLogoById($logo_id);
if ($logo_result->num_rows == 0) {
    header("Location: logo-management.php");
    exit();
}
$logo = $logo_result->fetch_assoc();

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update') {
    $result = $logoController->updateLogo($logo_id, $_POST, $_FILES['logo_file'] ?? null);
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'danger';
    
    // Refresh logo data after update
    if ($result['success']) {
        $logo_result = $logoController->getLogoById($logo_id);
        $logo = $logo_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <title>Edit Logo - Admin Panel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logo-preview {
            max-width: 300px;
            max-height: 150px;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .current-logo {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Edit</strong> Logo</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="logo-management.php" class="btn btn-light bg-white me-2">
                                <i class="fas fa-arrow-left"></i> Back to Logos
                            </a>
                        </div>
                    </div>

                    <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Edit Logo Details</h5>
                                    <h6 class="card-subtitle text-muted">Update logo information and upload new version.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $logo['id']; ?>">
                                        
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="asset_type">Asset Type</label>
                                                <select class="form-control" id="asset_type" name="asset_type" required disabled>
                                                    <option value="">Select Asset Type</option>
                                                    <option value="mobile_logo" <?php echo $logo['asset_type'] == 'mobile_logo' ? 'selected' : ''; ?>>Mobile Logo</option>
                                                    <option value="desktop_logo" <?php echo $logo['asset_type'] == 'desktop_logo' ? 'selected' : ''; ?>>Desktop Logo</option>
                                                    <option value="favicon" <?php echo $logo['asset_type'] == 'favicon' ? 'selected' : ''; ?>>Favicon</option>
                                                    <option value="email_logo" <?php echo $logo['asset_type'] == 'email_logo' ? 'selected' : ''; ?>>Email Logo</option>
                                                    <option value="footer_logo" <?php echo $logo['asset_type'] == 'footer_logo' ? 'selected' : ''; ?>>Footer Logo</option>
                                                    <option value="social_media_icon" <?php echo $logo['asset_type'] == 'social_media_icon' ? 'selected' : ''; ?>>Social Media Icon</option>
                                                    <option value="payment_icon" <?php echo $logo['asset_type'] == 'payment_icon' ? 'selected' : ''; ?>>Payment Icon</option>
                                                </select>
                                                <div class="form-text">Asset type cannot be changed after creation.</div>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="asset_name">Asset Name</label>
                                                <input type="text" class="form-control" id="asset_name" name="asset_name" 
                                                    value="<?php echo htmlspecialchars($logo['asset_name']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="current-logo">
                                            <h6>Current Logo</h6>
                                            <?php if (in_array($logo['file_extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                            <img src="<?php echo $logo['file_path'] . $logo['file_name']; ?>" 
                                                 class="logo-preview" alt="<?php echo $logo['alt_text']; ?>">
                                            <?php else: ?>
                                            <div class="logo-preview text-center">
                                                <i class="fas fa-file-image fa-3x text-muted"></i>
                                                <div class="mt-2">
                                                    <strong><?php echo strtoupper($logo['file_extension']); ?> File</strong>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    File: <?php echo $logo['file_name']; ?> | 
                                                    Size: <?php echo round($logo['file_size'] / 1024, 2); ?> KB | 
                                                    Version: <?php echo $logo['version']; ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="logo_file">Upload New Version (Optional)</label>
                                            <input type="file" class="form-control" id="logo_file" name="logo_file" 
                                                accept=".jpg,.jpeg,.png,.gif,.svg,.ico,.webp">
                                            <div class="form-text">
                                                Leave empty to keep current file. Allowed formats: JPG, PNG, GIF, SVG, ICO, WebP. Max size: 5MB
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($logo['description']); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="alt_text">Alt Text</label>
                                                <input type="text" class="form-control" id="alt_text" name="alt_text" 
                                                    value="<?php echo htmlspecialchars($logo['alt_text']); ?>">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="target_url">Target URL</label>
                                                <input type="url" class="form-control" id="target_url" name="target_url" 
                                                    value="<?php echo htmlspecialchars($logo['target_url']); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                                    <?php echo $logo['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Set as active version
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                If checked, this will deactivate other versions of the same asset type.
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Logo
                                            </button>
                                            <a href="logo-management.php" class="btn btn-secondary">Cancel</a>
                                            <button type="button" class="btn btn-danger ms-auto" onclick="confirmDelete(<?php echo $logo['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete Logo
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Logo Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Asset Type:</strong><br>
                                        <span class="badge bg-primary"><?php echo str_replace('_', ' ', $logo['asset_type']); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Dimensions:</strong><br>
                                        <?php echo $logo['dimensions'] ?: 'N/A'; ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Extension:</strong><br>
                                        <?php echo strtoupper($logo['file_extension']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Size:</strong><br>
                                        <?php echo round($logo['file_size'] / 1024, 2); ?> KB
                                    </div>
                                    <div class="mb-3">
                                        <strong>Uploaded:</strong><br>
                                        <?php echo date('M j, Y g:i A', strtotime($logo['upload_timestamp'])); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Last Modified:</strong><br>
                                        <?php echo date('M j, Y g:i A', strtotime($logo['last_modified'])); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-<?php echo $logo['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $logo['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
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
        function confirmDelete(logoId) {
            if (confirm('Are you sure you want to delete this logo? This action cannot be undone.')) {
                window.location.href = 'delete-logo.php?id=' + logoId;
            }
        }

        // File preview for new upload
        document.getElementById('logo_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can show preview here if needed
                    console.log('New file selected:', file.name);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>