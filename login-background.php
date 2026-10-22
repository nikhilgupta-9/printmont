<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';

$db = (new Database())->getConnection();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Helper to reliably insert or update settings in site_settings table
function saveSetting($db, $group, $key, $value, $type = 'text') {
    // Check if key already exists
    $checkStmt = $db->prepare("SELECT id FROM site_settings WHERE setting_group = ? AND setting_key = ?");
    $checkStmt->bind_param("ss", $group, $key);
    $checkStmt->execute();
    $res = $checkStmt->get_result();

    if ($res && $res->num_rows > 0) {
        $updateStmt = $db->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_group = ? AND setting_key = ?");
        $updateStmt->bind_param("sss", $value, $group, $key);
        return $updateStmt->execute();
    } else {
        $insertStmt = $db->prepare("INSERT INTO site_settings (setting_group, setting_key, setting_value, setting_type, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $insertStmt->bind_param("ssss", $group, $key, $value, $type);
        return $insertStmt->execute();
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadDir = __DIR__ . '/uploads/auth/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        $frontendUploadDir = dirname(__DIR__) . '/frontend/public/uploads/auth/';
        if (!is_dir($frontendUploadDir)) {
            @mkdir($frontendUploadDir, 0777, true);
        }

        $filesUploaded = 0;

        // 1. Handle Desktop Auth Artwork Upload
        if (isset($_FILES['desktop_auth_image']) && !empty($_FILES['desktop_auth_image']['name'])) {
            $file = $_FILES['desktop_auth_image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Desktop image upload failed with error code: " . $file['error']);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid desktop image format. Allowed: JPG, PNG, WebP, SVG.");
            }

            $newFilename = 'auth_desktop_' . time() . '.' . $ext;
            $destPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                @chmod($destPath, 0666);
                // Also copy to frontend public directory for instant Vite sync
                if (is_dir($frontendUploadDir)) {
                    @copy($destPath, $frontendUploadDir . $newFilename);
                }
                $val = 'uploads/auth/' . $newFilename;
                saveSetting($db, 'login', 'desktop_auth_image', $val, 'image');
                $filesUploaded++;
            } else {
                throw new Exception("Could not move uploaded desktop image to $destPath. Check directory write permissions.");
            }
        }

        // 2. Handle Mobile Promo Banner Upload
        if (isset($_FILES['mobile_promo_banner']) && !empty($_FILES['mobile_promo_banner']['name'])) {
            $file = $_FILES['mobile_promo_banner'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Mobile banner upload failed with error code: " . $file['error']);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid mobile banner format. Allowed: JPG, PNG, WebP, SVG.");
            }

            $newFilename = 'mobile_banner_' . time() . '.' . $ext;
            $destPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                @chmod($destPath, 0666);
                if (is_dir($frontendUploadDir)) {
                    @copy($destPath, $frontendUploadDir . $newFilename);
                }
                $val = 'uploads/auth/' . $newFilename;
                saveSetting($db, 'login', 'mobile_promo_banner', $val, 'image');
                $filesUploaded++;
            } else {
                throw new Exception("Could not move uploaded mobile banner to $destPath. Check directory write permissions.");
            }
        }

        // 3. Handle Text & Toggle Settings
        saveSetting($db, 'login', 'login_heading', trim($_POST['login_heading'] ?? 'Login'), 'text');
        saveSetting($db, 'login', 'login_subheading', trim($_POST['login_subheading'] ?? 'Get access to your Orders, Wishlist and Recommendations'), 'text');
        saveSetting($db, 'login', 'signup_heading', trim($_POST['signup_heading'] ?? "Looks like you're new here!"), 'text');
        saveSetting($db, 'login', 'signup_subheading', trim($_POST['signup_subheading'] ?? 'Sign up with your mobile number to get started'), 'text');
        saveSetting($db, 'login', 'mobile_banner_enabled', isset($_POST['mobile_banner_enabled']) ? '1' : '0', 'boolean');

        $msg = 'Settings saved successfully!';
        if ($filesUploaded > 0) {
            $msg .= " ($filesUploaded image" . ($filesUploaded > 1 ? 's' : '') . " uploaded & updated)";
        }
        $_SESSION['success_message'] = $msg;
        header('Location: login-background.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Upload Error: ' . $e->getMessage();
        header('Location: login-background.php');
        exit;
    }
}

// Fetch Current Settings
$currentSettings = [
    'desktop_auth_image'   => 'uploads/auth/login_products_art.png',
    'mobile_promo_banner'  => 'uploads/auth/mobile_offer_banner.jpg',
    'mobile_banner_enabled'=> '1',
    'login_heading'        => 'Login',
    'login_subheading'     => 'Get access to your Orders, Wishlist and Recommendations',
    'signup_heading'       => "Looks like you're new here!",
    'signup_subheading'    => 'Sign up with your mobile number to get started'
];

$res = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'login'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['setting_value'])) {
            $currentSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login &amp; Signup Background Management | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .auth-preview-box {
            background: #2874f0;
            border-radius: 8px;
            color: #ffffff;
            padding: 24px;
            position: relative;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .auth-preview-img {
            width: 100%;
            max-height: 120px;
            object-fit: contain;
            margin-top: auto;
        }
        .mobile-banner-preview {
            max-height: 90px;
            width: 100%;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .upload-dropzone {
            border: 2px dashed #0d6efd;
            background: #f8faff;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .upload-dropzone:hover {
            background: #edf3ff;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Login &amp; Signup</strong> Background &amp; Banner Manager</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="http://localhost:5173/login" target="_blank" class="btn btn-outline-primary me-1">
                                <i class="align-middle me-1" data-feather="external-link"></i> Live Login Page
                            </a>
                            <a href="http://localhost:5173/signup" target="_blank" class="btn btn-primary">
                                <i class="align-middle me-1" data-feather="external-link"></i> Live Signup Page
                            </a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-message"><strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="alert-message"><strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login-background.php" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Left Column: Settings Form -->
                            <div class="col-12 col-lg-7">
                                <!-- 1. Desktop Artwork Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="card-title mb-0 fw-bold">🖥️ Desktop Auth Artwork (Left Blue Panel)</h5>
                                        <div class="text-muted small mt-1">Upload the merchandise product illustration shown at the bottom of the Desktop Login &amp; Register blue card.</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Upload New Desktop Graphic</label>
                                            <input type="file" name="desktop_auth_image" id="desktopImgInput" class="form-control form-control-lg" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                                            <div class="form-text text-muted">Supports transparent PNG, WebP, JPG, or SVG (Recommended: ~800x260px).</div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Login Heading</label>
                                                <input type="text" name="login_heading" class="form-control" value="<?php echo htmlspecialchars($currentSettings['login_heading']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Signup Heading</label>
                                                <input type="text" name="signup_heading" class="form-control" value="<?php echo htmlspecialchars($currentSettings['signup_heading']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Login Subheading</label>
                                                <textarea name="login_subheading" class="form-control" rows="2"><?php echo htmlspecialchars($currentSettings['login_subheading']); ?></textarea>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Signup Subheading</label>
                                                <textarea name="signup_subheading" class="form-control" rows="2"><?php echo htmlspecialchars($currentSettings['signup_subheading']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Mobile Promo Banner Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="card-title mb-0 fw-bold">📱 Mobile Special Offer Banner</h5>
                                        <div class="text-muted small mt-1">Upload promotional sale banner displayed at the top of the mobile screen.</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="mobileBannerToggle" name="mobile_banner_enabled" value="1" <?php echo $currentSettings['mobile_banner_enabled'] === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="mobileBannerToggle">Display Offer Banner on Mobile View</label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Upload New Mobile Banner Image</label>
                                            <input type="file" name="mobile_promo_banner" id="mobileBannerInput" class="form-control form-control-lg" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                                            <div class="form-text text-muted">Recommended dimension: Wide strip ~800x180px.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm fw-bold">
                                        <i class="align-middle me-1" data-feather="save"></i> Save &amp; Apply Changes
                                    </button>
                                </div>
                            </div>

                            <!-- Right Column: Live Previews -->
                            <div class="col-12 col-lg-5">
                                <!-- Desktop Preview Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0 fw-bold">🖥️ Active Desktop Preview</h5>
                                        <span class="badge bg-primary">Live</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="auth-preview-box shadow-sm">
                                            <div>
                                                <h4 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($currentSettings['login_heading']); ?></h4>
                                                <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($currentSettings['login_subheading']); ?></p>
                                            </div>
                                            <div class="text-center mt-3">
                                                <img src="<?php echo htmlspecialchars($currentSettings['desktop_auth_image']); ?>" alt="Desktop Auth Artwork" class="auth-preview-img" id="desktopPreviewImg">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Banner Preview Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0 fw-bold">📱 Active Mobile Banner Preview</h5>
                                        <?php echo $currentSettings['mobile_banner_enabled'] === '1' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Disabled</span>'; ?>
                                    </div>
                                    <div class="card-body">
                                        <img src="<?php echo htmlspecialchars($currentSettings['mobile_promo_banner']); ?>" alt="Mobile Promo Banner" class="mobile-banner-preview mb-2" id="mobilePreviewImg">
                                        <div class="small text-muted mt-1">
                                            File: <code><?php echo htmlspecialchars($currentSettings['mobile_promo_banner']); ?></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Instant Live preview on file selection
        document.getElementById('desktopImgInput')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('desktopPreviewImg').src = ev.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        document.getElementById('mobileBannerInput')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('mobilePreviewImg').src = ev.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
