<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

// Create banner controller
$bannerController = new BannerController();
$positions = $bannerController->getAvailablePositions();
$layoutTypes = $bannerController->getLayoutTypes();

// Handle form submission
$error_message = '';
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
    <title>Add Banner | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .preview-image { max-width: 200px; max-height: 150px; margin: 10px 0; border-radius: 4px; }
        .image-preview-container { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .layout-preview { 
            background: #e9ecef; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 10px;
            border: 2px dashed #dee2e6;
        }
        .layout-single { height: 100px; }
        .layout-row { height: 80px; display: flex; gap: 10px; }
        .layout-row .banner-preview { flex: 1; background: #adb5bd; border-radius: 4px; }
        .layout-grid-2 { height: 120px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .layout-grid-2 .banner-preview { background: #adb5bd; border-radius: 4px; }
        .layout-grid-3 { height: 100px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .layout-grid-3 .banner-preview { background: #adb5bd; border-radius: 4px; }
        .layout-carousel { height: 150px; background: #adb5bd; border-radius: 8px; position: relative; }
        .layout-carousel::before { 
            content: "Carousel Slides"; 
            position: absolute; 
            top: 50%; left: 50%; 
            transform: translate(-50%, -50%); 
            color: #495057; 
            font-weight: bold; 
        }
        .layout-sidebar { height: 300px; width: 200px; background: #adb5bd; border-radius: 4px; }
        .position-info { 
            background: #d1ecf1; 
            border-left: 4px solid #0dcaf0; 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .required-field::after { content: " *"; color: #dc3545; }
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
                            <a href="view-banner.php" class="btn btn-success me-2">View Banners</a>
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
                                    <h5 class="card-title">Add New Banner</h5>
                                    <h6 class="card-subtitle text-muted">Create banner with mobile and desktop optimized images.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="bannerForm">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label required-field" for="title">Title</label>
                                                <input type="text" class="form-control" id="title" name="title" required
                                                       placeholder="Enter banner title">
                                                <small class="form-text text-muted">This will be used as alt text for accessibility</small>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label required-field" for="display_order">Display Order</label>
                                                <input type="number" class="form-control" id="display_order" name="display_order" required
                                                       value="0" min="0"
                                                       placeholder="Enter display order">
                                                <small class="form-text text-muted">Lower numbers appear first</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="2"
                                                      placeholder="Enter banner description (optional)"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label required-field" for="position">Position</label>
                                                <select id="position" name="position" class="form-control" required>
                                                    <option value="">Select Position</option>
                                                    <?php foreach ($positions as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>">
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div id="positionInfo" class="position-info" style="display: none;">
                                                    <strong>Layout: </strong><span id="layoutType"></span><br>
                                                    <small id="layoutDescription"></small>
                                                </div>
                                                <div id="layoutPreview" class="layout-preview" style="display: none;"></div>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="target_url">Target URL</label>
                                                <input type="url" class="form-control" id="target_url" name="target_url"
                                                       placeholder="https://example.com/page">
                                                <small class="form-text text-muted">Where users will be redirected when clicking the banner</small>
                                            </div>
                                        </div>

                                        <!-- Image Upload Sections -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">
                                                            <i class="fas fa-desktop me-2"></i>Desktop Image
                                                            <span class="required-field"></span>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">Desktop Banner Image</label>
                                                            <input type="file" class="form-control" id="image_url_desktop" name="image_url_desktop" accept="image/*" required>
                                                            <small class="form-text text-muted">
                                                                Recommended: 1920x600px, JPG/PNG/WEBP, max 5MB<br>
                                                                Aspect ratio: 16:5 for best results
                                                            </small>
                                                        </div>
                                                        <div id="desktopPreview" class="image-preview-container">
                                                            <p class="text-muted mb-0">Desktop image preview will appear here</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">
                                                            <i class="fas fa-mobile-alt me-2"></i>Mobile Image
                                                            <span class="text-muted">(Optional)</span>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Mobile Banner Image</label>
                                                            <input type="file" class="form-control" id="image_url_mobile" name="image_url_mobile" accept="image/*">
                                                            <small class="form-text text-muted">
                                                                Recommended: 768x400px, JPG/PNG/WEBP, max 5MB<br>
                                                                Aspect ratio: 2:1 for mobile devices<br>
                                                                <em>If not provided, desktop image will be used</em>
                                                            </small>
                                                        </div>
                                                        <div id="mobilePreview" class="image-preview-container">
                                                            <p class="text-muted mb-0">Mobile image preview will appear here</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                    <option value="draft">Draft</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="start_date">Start Date</label>
                                                <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                                                <small class="form-text text-muted">When the banner should start appearing</small>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="end_date">End Date</label>
                                                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                                                <small class="form-text text-muted">When the banner should stop appearing</small>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-plus me-2"></i>Create Banner
                                            </button>
                                            <a href="view-banners.php" class="btn btn-secondary">Cancel</a>
                                        </div>
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
        // Layout types and their descriptions
        const layoutInfo = {
            'single': { 
                name: 'Single Banner', 
                desc: 'Full width banner, perfect for hero sections',
                class: 'layout-single'
            },
            'row': { 
                name: 'Banner Row', 
                desc: 'Multiple banners in a horizontal row',
                class: 'layout-row',
                items: 3
            },
            'grid_2': { 
                name: '2 Column Grid', 
                desc: 'Two banners side by side',
                class: 'layout-grid-2',
                items: 2
            },
            'grid_3': { 
                name: '3 Column Grid', 
                desc: 'Three banners in a grid layout',
                class: 'layout-grid-3',
                items: 3
            },
            'carousel': { 
                name: 'Image Carousel', 
                desc: 'Rotating banner slideshow',
                class: 'layout-carousel'
            },
            'sidebar': { 
                name: 'Sidebar Banner', 
                desc: 'Vertical banner for sidebar placement',
                class: 'layout-sidebar'
            }
        };

        // Position to layout mapping
        const positionLayouts = {
            'home_hero': 'single',
            'home_above_fold': 'row',
            'home_mid_section_1': 'grid_3',
            'home_mid_section_2': 'grid_2',
            'home_mid_section_3': 'carousel',
            'home_below_fold': 'single',
            'home_before_footer': 'row',
            'category_top': 'single',
            'category_sidebar': 'sidebar',
            'product_page_top': 'single',
            'product_page_middle': 'single',
            'cart_page': 'single',
            'checkout_top': 'single',
            'blog_sidebar': 'sidebar',
            'newsletter_popup': 'single'
        };

        // Image preview functionality
        document.getElementById('image_url_desktop').addEventListener('change', function(e) {
            previewImage(this, 'desktopPreview');
        });

        document.getElementById('image_url_mobile').addEventListener('change', function(e) {
            previewImage(this, 'mobilePreview');
        });

        // Position change handler
        document.getElementById('position').addEventListener('change', function() {
            const position = this.value;
            const layout = positionLayouts[position];
            const infoDiv = document.getElementById('positionInfo');
            const previewDiv = document.getElementById('layoutPreview');

            if (layout && layoutInfo[layout]) {
                const info = layoutInfo[layout];
                
                // Update info
                document.getElementById('layoutType').textContent = info.name;
                document.getElementById('layoutDescription').textContent = info.desc;
                infoDiv.style.display = 'block';

                // Update preview
                previewDiv.className = 'layout-preview ' + info.class;
                previewDiv.innerHTML = '';
                
                if (info.items) {
                    for (let i = 0; i < info.items; i++) {
                        const div = document.createElement('div');
                        div.className = 'banner-preview';
                        previewDiv.appendChild(div);
                    }
                }
                previewDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
                previewDiv.style.display = 'none';
            }
        });

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.innerHTML = '';
                    preview.appendChild(img);

                    // Show image info
                    const file = input.files[0];
                    const info = document.createElement('div');
                    info.className = 'mt-2 small';
                    info.innerHTML = `<strong>File:</strong> ${file.name}<br>
                                     <strong>Size:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                                     <strong>Type:</strong> ${file.type}`;
                    preview.appendChild(info);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Set minimum end date based on start date
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
        });

        // Form validation
        document.getElementById('bannerForm').addEventListener('submit', function(e) {
            const desktopImage = document.getElementById('image_url_desktop').files[0];
            if (!desktopImage) {
                e.preventDefault();
                alert('Please select a desktop image');
                return false;
            }
        });
    </script>
</body>
</html>