<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SpecialOfferBannerController.php';

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$controller = new SpecialOfferBannerController();
$id = intval($_GET['id'] ?? 0);
$banner = $controller->getBannerById($id);

if (!$banner) {
    $_SESSION['error_message'] = "Special Offer banner not found.";
    header("Location: special-offers.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_special_offer'])) {
    try {
        if ($controller->updateSpecialOffer($id, $_POST, $_FILES)) {
            $_SESSION['success_message'] = "Special Offer banner updated successfully!";
            header("Location: special-offers.php");
            exit;
        } else {
            throw new Exception("Failed to update special offer banner.");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Special Offer #<?php echo $id; ?> | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .choice-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: #ffffff;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }
        .choice-card:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        .choice-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18);
        }
        .choice-card input[type="radio"] {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .choice-icon {
            font-size: 1.6rem;
            margin-bottom: 6px;
            color: #2563eb;
        }
        .choice-title {
            font-weight: 700;
            font-size: 0.88rem;
            color: #1e293b;
        }

        .img-preview-box {
            width: 100%;
            max-height: 140px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            margin-top: 8px;
        }

        .preset-badge-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 15px;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 2px 8px;
            cursor: pointer;
        }
        .preset-badge-btn:hover {
            background: #e2e8f0;
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
                    <!-- Page Header -->
                    <div class="row mb-4 align-items-center">
                        <div class="col">
                            <div class="d-flex align-items-center gap-2">
                                <a href="special-offers.php" class="btn btn-outline-secondary btn-sm me-1" title="Back to list">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <div>
                                    <h1 class="h3 mb-0 text-gray-800 fw-bold">Edit Special Offer #<?php echo $id; ?></h1>
                                    <p class="text-muted mb-0 small">Update special offer layout, images, redirect link, and display settings.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="edit-special-offer.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
                        
                        <!-- ================= STEP 1: SELECT DISPLAY TYPE ================= -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <span class="badge bg-primary rounded-circle me-2">1</span>
                                    Step 1: Display Mode (Banner vs Carousel)
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['display_type'] === 'banner' ? 'selected' : ''; ?>" id="card-type-banner" onclick="selectDisplayType('banner')">
                                            <input type="radio" name="display_type" value="banner" <?php echo $banner['display_type'] === 'banner' ? 'checked' : ''; ?> id="radio-type-banner">
                                            <div class="choice-icon text-primary"><i class="fas fa-image"></i></div>
                                            <div class="choice-title">Static Banner Grid</div>
                                        </label>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['display_type'] === 'carousel' ? 'selected' : ''; ?>" id="card-type-carousel" onclick="selectDisplayType('carousel')">
                                            <input type="radio" name="display_type" value="carousel" <?php echo $banner['display_type'] === 'carousel' ? 'checked' : ''; ?> id="radio-type-carousel">
                                            <div class="choice-icon text-warning"><i class="fas fa-sliders"></i></div>
                                            <div class="choice-title">Sliding Carousel / Slider</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= STEP 2: SELECT LAYOUT FORMAT ================= -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <span class="badge bg-primary rounded-circle me-2">2</span>
                                    Step 2: Layout Format
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['layout_format'] === '1' ? 'selected' : ''; ?>" id="card-layout-1" onclick="selectLayoutFormat('1')">
                                            <input type="radio" name="layout_format" value="1" <?php echo $banner['layout_format'] === '1' ? 'checked' : ''; ?> id="radio-layout-1">
                                            <div class="choice-icon text-primary"><i class="fas fa-square"></i></div>
                                            <div class="choice-title">1 Column (Full)</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['layout_format'] === '1.5' ? 'selected' : ''; ?>" id="card-layout-1.5" onclick="selectLayoutFormat('1.5')">
                                            <input type="radio" name="layout_format" value="1.5" <?php echo $banner['layout_format'] === '1.5' ? 'checked' : ''; ?> id="radio-layout-1.5">
                                            <div class="choice-icon text-warning"><i class="fas fa-film"></i></div>
                                            <div class="choice-title">1.5 Peeking Carousel</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['layout_format'] === '2' ? 'selected' : ''; ?>" id="card-layout-2" onclick="selectLayoutFormat('2')">
                                            <input type="radio" name="layout_format" value="2" <?php echo $banner['layout_format'] === '2' ? 'checked' : ''; ?> id="radio-layout-2">
                                            <div class="choice-icon text-success"><i class="fas fa-columns"></i></div>
                                            <div class="choice-title">2 Columns</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card <?php echo $banner['layout_format'] === '2x2' ? 'selected' : ''; ?>" id="card-layout-2x2" onclick="selectLayoutFormat('2x2')">
                                            <input type="radio" name="layout_format" value="2x2" <?php echo $banner['layout_format'] === '2x2' ? 'checked' : ''; ?> id="radio-layout-2x2">
                                            <div class="choice-icon text-danger"><i class="fas fa-th-large"></i></div>
                                            <div class="choice-title">4 Images Square (2x2 Grid)</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= STEP 3: BANNER DETAILS & IMAGES ================= -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <span class="badge bg-primary rounded-circle me-2">3</span>
                                    Step 3: Offer Details &amp; Images
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Offer Title / Heading</label>
                                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>" placeholder="e.g. Special Offers &amp; Discounts">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Subtitle / Description (Optional)</label>
                                        <input type="text" class="form-control" name="subtitle" value="<?php echo htmlspecialchars($banner['subtitle'] ?? ''); ?>" placeholder="e.g. Lowest prices of the season">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Offer Badge Tag</label>
                                        <input type="text" class="form-control" name="offer_badge" id="edit-badge" value="<?php echo htmlspecialchars($banner['offer_badge'] ?? 'Special Offer'); ?>">
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <button type="button" class="preset-badge-btn" onclick="document.getElementById('edit-badge').value='Special Offer'">Special Offer</button>
                                            <button type="button" class="preset-badge-btn" onclick="document.getElementById('edit-badge').value='Flat 50% Off'">Flat 50% Off</button>
                                            <button type="button" class="preset-badge-btn" onclick="document.getElementById('edit-badge').value='Min. 30% Off'">Min 30% Off</button>
                                        </div>
                                    </div>

                                    <div class="col-md-2 col-6">
                                        <label class="form-label fw-semibold">Badge Color</label>
                                        <input type="color" class="form-control form-control-color w-100" name="badge_color" value="<?php echo htmlspecialchars($banner['badge_color'] ?: '#ef4444'); ?>">
                                    </div>

                                    <div class="col-md-3 col-6">
                                        <label class="form-label fw-semibold">Target Device</label>
                                        <select class="form-select fw-semibold" name="device_target">
                                            <option value="all" <?php echo $banner['device_target'] === 'all' ? 'selected' : ''; ?>>All Devices</option>
                                            <option value="desktop" <?php echo $banner['device_target'] === 'desktop' ? 'selected' : ''; ?>>Desktop Only</option>
                                            <option value="mobile" <?php echo $banner['device_target'] === 'mobile' ? 'selected' : ''; ?>>Mobile Only</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select class="form-select fw-semibold" name="status">
                                            <option value="active" <?php echo $banner['status'] === 'active' ? 'selected' : ''; ?>>Active (Visible)</option>
                                            <option value="inactive" <?php echo $banner['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold">Redirect Link URL</label>
                                        <input type="text" class="form-control font-monospace" name="link_url" value="<?php echo htmlspecialchars($banner['link_url'] ?: '/allproducts?filter=discount'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Sort / Display Order</label>
                                        <input type="number" class="form-control" name="sort_order" value="<?php echo (int)$banner['sort_order']; ?>">
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-desktop text-primary me-1"></i> Desktop Image
                                        </label>
                                        <input type="file" class="form-control" name="image_desktop" accept="image/*" onchange="previewImg(this, 'preview_desk')">
                                        <input type="text" class="form-control form-control-sm mt-1" name="image_desktop_url" value="<?php echo htmlspecialchars($banner['image_desktop'] ?? ''); ?>" placeholder="Or enter image URL">
                                        <?php if (!empty($banner['image_desktop'])): ?>
                                            <img id="preview_desk" src="<?php echo htmlspecialchars($banner['image_desktop']); ?>" class="img-preview-box">
                                        <?php else: ?>
                                            <img id="preview_desk" class="img-preview-box" style="display: none;">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-mobile-screen text-success me-1"></i> Mobile Image (Optional)
                                        </label>
                                        <input type="file" class="form-control" name="image_mobile" accept="image/*" onchange="previewImg(this, 'preview_mob')">
                                        <input type="text" class="form-control form-control-sm mt-1" name="image_mobile_url" value="<?php echo htmlspecialchars($banner['image_mobile'] ?? ''); ?>" placeholder="Or enter mobile image URL">
                                        <?php if (!empty($banner['image_mobile'])): ?>
                                            <img id="preview_mob" src="<?php echo htmlspecialchars($banner['image_mobile']); ?>" class="img-preview-box">
                                        <?php else: ?>
                                            <img id="preview_mob" class="img-preview-box" style="display: none;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <a href="special-offers.php" class="btn btn-light border px-4">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" name="update_special_offer" class="btn btn-primary btn-lg px-5 shadow">
                                <i class="fas fa-check-circle me-2"></i> Update Special Offer
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function selectDisplayType(type) {
            document.querySelectorAll('[id^="card-type-"]').forEach(c => c.classList.remove('selected'));
            document.getElementById('card-type-' + type)?.classList.add('selected');
            document.getElementById('radio-type-' + type).checked = true;
        }

        function selectLayoutFormat(format) {
            document.querySelectorAll('[id^="card-layout-"]').forEach(c => c.classList.remove('selected'));
            document.getElementById('card-layout-' + format)?.classList.add('selected');
            document.getElementById('radio-layout-' + format).checked = true;
        }

        function previewImg(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
