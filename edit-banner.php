<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

// Create banner controller
$bannerController = new BannerController();
$positions = $bannerController->getAvailablePositions();

// Check if editing existing banner
$banner = null;
$is_edit = false;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $banner = $bannerController->getBannerById($_GET['id']);
    $is_edit = true;
}

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_edit) {
        $result = $bannerController->updateBanner($_GET['id'], $_POST, $_FILES);
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

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Banner | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .preview-image { max-width: 200px; max-height: 150px; margin: 10px 0; border-radius: 4px; }
        .image-preview-container { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
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
                            <h3><strong><?php echo $is_edit ? 'Edit' : 'Add'; ?> Banner</strong></h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="view-banners.php" class="btn btn-light bg-white me-2">View Banners</a>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><?php echo $is_edit ? 'Edit' : 'Add New'; ?> Banner</h5>
                                    <h6 class="card-subtitle text-muted"><?php echo $is_edit ? 'Update' : 'Create'; ?> banner information.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="title">Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" required
                                                       value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>"
                                                       placeholder="Enter banner title">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="display_order">Display Order *</label>
                                                <input type="number" class="form-control" id="display_order" name="display_order" required
                                                       value="<?php echo htmlspecialchars($banner['display_order'] ?? 0); ?>"
                                                       placeholder="Enter display order">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                      placeholder="Enter banner description"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="position">Position *</label>
                                                <select id="position" name="position" class="form-control" required>
                                                    <option value="">Select Position</option>
                                                    <?php foreach ($positions as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                            <?php echo (isset($banner['position']) && $banner['position'] == $value) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="target_url">Target URL</label>
                                                <input type="text" class="form-control" id="target_url" name="target_url"
                                                       value="<?php echo htmlspecialchars($banner['target_url'] ?? ''); ?>"
                                                       placeholder="Enter target URL">
                                            </div>
                                        </div>

                                        <!-- Current Images (for edit) -->
                                        <?php if ($is_edit && (!empty($banner['image_url_desktop']) || !empty($banner['image_url_mobile']))): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Current Images</label>
                                                <div class="row">
                                                    <?php if (!empty($banner['image_url_desktop'])): ?>
                                                        <div class="col-md-6">
                                                            <p><strong>Desktop Image:</strong></p>
                                                            <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" 
                                                                 alt="Desktop Banner" class="preview-image">
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($banner['image_url_mobile'])): ?>
                                                        <div class="col-md-6">
                                                            <p><strong>Mobile Image:</strong></p>
                                                            <img src="<?php echo htmlspecialchars($banner['image_url_mobile']); ?>" 
                                                                 alt="Mobile Banner" class="preview-image">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_desktop">
                                                    <?php echo $is_edit ? 'Update Desktop Image' : 'Desktop Image'; ?>
                                                </label>
                                                <input type="file" class="form-control" id="image_url_desktop" name="image_url_desktop" accept="image/*">
                                                <div id="desktopPreview" class="image-preview-container"></div>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_mobile">
                                                    <?php echo $is_edit ? 'Update Mobile Image' : 'Mobile Image'; ?>
                                                </label>
                                                <input type="file" class="form-control" id="image_url_mobile" name="image_url_mobile" accept="image/*">
                                                <div id="mobilePreview" class="image-preview-container"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo (isset($banner['status']) && $banner['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($banner['status']) && $banner['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="draft" <?php echo (isset($banner['status']) && $banner['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="start_date">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date"
                                                       value="<?php echo htmlspecialchars($banner['start_date'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="end_date">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date"
                                                       value="<?php echo htmlspecialchars($banner['end_date'] ?? ''); ?>">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <?php echo $is_edit ? 'Update Banner' : 'Create Banner'; ?>
                                        </button>
                                        <a href="view-banners.php" class="btn btn-secondary">Cancel</a>
                                    </form>
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
        // Image preview functionality
        document.getElementById('image_url_desktop').addEventListener('change', function(e) {
            const preview = document.getElementById('desktopPreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        document.getElementById('image_url_mobile').addEventListener('change', function(e) {
            const preview = document.getElementById('mobilePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Set minimum end date based on start date
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
        });
    </script>
</body>
</html>