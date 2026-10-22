<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'models/Watermark.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$watermarkModel = new Watermark($db);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    if (!empty($title)) {
        $file = $_FILES['image'] ?? null;
        if ($watermarkModel->create($_POST, $file)) {
            $_SESSION['success_message'] = "Watermark preset \"{$title}\" created successfully!";
            header("Location: watermarks.php");
            exit();
        } else {
            $error_message = "Failed to save watermark in database.";
        }
    } else {
        $error_message = "Please enter a valid title for the watermark.";
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $error_message ?? ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Watermark | Printmont Admin</title>

    <!-- Rule 1: Dynamic Brand Favicon -->
    <link rel="shortcut icon" href="favicon.png" />
    <link rel="icon" type="image/png" href="favicon.png" />

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --pm-primary: #0B53A1;
            --pm-primary-dark: #083c75;
            --pm-primary-light: #e8f0fe;
            --pm-accent: #f8991d;
            --pm-success: #10b981;
            --pm-danger: #ef4444;
            --pm-warning: #f59e0b;
            --pm-dark: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }

        .custom-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .custom-card-header {
            padding: 18px 24px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-card-body {
            padding: 24px;
        }

        .simulator-canvas-wrapper {
            position: relative;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 380px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
        }

        #simulatorCanvas {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .pos-btn.active {
            background-color: var(--pm-primary) !important;
            color: #fff !important;
            border-color: var(--pm-primary) !important;
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
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: var(--pm-primary-light); color: var(--pm-primary); font-size: 12px; font-weight: 700;">
                                    MEDIA WATERMARKS
                                </span>
                                <span class="text-muted fs-sm">• Design & Configure</span>
                            </div>
                            <h2 class="h3 font-weight-bold text-dark mt-1 mb-0">Create New Watermark</h2>
                            <p class="text-muted mb-0">Design a custom copyright text or logo watermark with real-time interactive preview.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="watermarks.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i> All Watermarks
                            </a>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                            <i class="fa-solid fa-circle-check fs-5 me-3 text-success"></i>
                            <div class="flex-grow-1"><?php echo $success_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                            <i class="fa-solid fa-circle-exclamation fs-5 me-3 text-danger"></i>
                            <div class="flex-grow-1"><?php echo $error_message; ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="add-watermark.php" enctype="multipart/form-data">
                        <div class="row g-4">
                            <!-- Left Controls -->
                            <div class="col-lg-6">
                                <div class="custom-card h-100">
                                    <div class="custom-card-header">
                                        <h5 class="card-title mb-0 fw-bold">Watermark Properties</h5>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark">Preset Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="title" placeholder="e.g. Festival 2026 Special Protection" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Watermark Type</label>
                                                <select class="form-select" name="type" id="wm_type">
                                                    <option value="text" selected>Text Watermark</option>
                                                    <option value="image">PNG Logo / Overlay</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Initial Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" selected>Active (Set as primary watermark)</option>
                                                    <option value="inactive">Inactive (Save as draft preset)</option>
                                                </select>
                                            </div>

                                            <div class="col-12" id="text_block">
                                                <label class="form-label fw-bold text-dark">Watermark Text</label>
                                                <input type="text" class="form-control" name="watermark_text" id="sim_text" value="© Printmont • www.printmont.com">
                                            </div>

                                            <div class="col-12" id="image_block" style="display: none;">
                                                <label class="form-label fw-bold text-dark">Upload Logo Image (Transparent PNG)</label>
                                                <input type="file" class="form-control" name="image" id="wm_file" accept="image/png,image/jpeg,image/webp">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Font Size: <span id="val_size" class="text-primary font-monospace">24px</span></label>
                                                <input type="range" class="form-range" name="font_size" id="sim_size" min="14" max="52" value="24">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Opacity: <span id="val_opacity" class="text-primary font-monospace">65%</span></label>
                                                <input type="range" class="form-range" name="opacity" id="sim_opacity" min="10" max="100" value="65">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Text Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" name="font_color" id="sim_color" value="#FFFFFF">
                                                    <input type="text" class="form-control font-monospace" id="sim_color_text" value="#FFFFFF">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-dark">Mockup Preview</label>
                                                <select class="form-select" id="sim_bg_select">
                                                    <option value="mug">Ceramic Coffee Mug</option>
                                                    <option value="tshirt">Custom Polo T-Shirt</option>
                                                    <option value="backpack">Office Laptop Backpack</option>
                                                    <option value="trophy">Crystal Award Trophy</option>
                                                    <option value="dark">Dark Canvas</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-bold text-dark mb-2">Watermark Position</label>
                                                <input type="hidden" name="position" id="wm_position" value="bottom_right">
                                                <div class="d-flex gap-1 flex-wrap" id="posBtnGroup">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn" data-pos="top_left">Top Left</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn" data-pos="top_right">Top Right</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn" data-pos="center">Center</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn" data-pos="bottom_left">Bottom Left</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn active" data-pos="bottom_right">Bottom Right</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary pos-btn" data-pos="repeat">
                                                        <i class="fa-solid fa-border-none me-1"></i> Diagonal Tile Repeat
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-12 pt-3">
                                                <button type="submit" class="btn btn-primary w-100 py-2" style="background-color: var(--pm-primary); border-color: var(--pm-primary);">
                                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save & Apply Watermark
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Live Simulator Canvas -->
                            <div class="col-lg-6">
                                <div class="custom-card h-100">
                                    <div class="custom-card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-display text-primary me-1"></i> Live Simulator Output</h5>
                                        <span class="badge bg-light text-dark border">Real-Time Render</span>
                                    </div>
                                    <div class="custom-card-body">
                                        <div class="simulator-canvas-wrapper p-3">
                                            <canvas id="simulatorCanvas" width="600" height="400"></canvas>
                                        </div>
                                        <div class="mt-3 text-muted fs-sm text-center">
                                            <i class="fa-solid fa-info-circle me-1"></i> Images on the website display clean without watermark, and automatically apply this watermark when downloaded or saved.
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
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('simulatorCanvas');
            const ctx = canvas.getContext('2d');

            const textInput = document.getElementById('sim_text');
            const sizeInput = document.getElementById('sim_size');
            const opacityInput = document.getElementById('sim_opacity');
            const colorInput = document.getElementById('sim_color');
            const colorTextInput = document.getElementById('sim_color_text');
            const bgSelect = document.getElementById('sim_bg_select');
            const typeSelect = document.getElementById('wm_type');
            const posBtns = document.querySelectorAll('.pos-btn');
            const posInput = document.getElementById('wm_position');

            let currentPos = 'bottom_right';

            const backgrounds = {
                mug: 'uploads/products/ceramic_coffee_mug.jpg',
                tshirt: 'uploads/products/corporate_polo_tshirt.jpg',
                backpack: 'uploads/products/office_laptop_backpack.jpg',
                trophy: 'uploads/products/crystal_award_trophy.jpg',
                dark: 'dark'
            };

            const loadedBgImages = {};

            function loadMockupImage(key, callback) {
                if (key === 'dark') {
                    callback(null);
                    return;
                }
                if (loadedBgImages[key]) {
                    callback(loadedBgImages[key]);
                    return;
                }
                const img = new Image();
                img.src = backgrounds[key];
                img.onload = () => {
                    loadedBgImages[key] = img;
                    callback(img);
                };
                img.onerror = () => callback(null);
            }

            function renderSimulator() {
                const text = textInput ? (textInput.value || '© Printmont • www.printmont.com') : '© Printmont';
                const fontSize = parseInt(sizeInput.value) || 24;
                const opacity = (parseInt(opacityInput.value) || 65) / 100;
                const color = colorInput.value || '#FFFFFF';
                const bgKey = bgSelect.value;

                loadMockupImage(bgKey, function(bgImg) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (bgImg) {
                        ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
                    } else {
                        const grad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
                        grad.addColorStop(0, '#0B53A1');
                        grad.addColorStop(1, '#031c38');
                        ctx.fillStyle = grad;
                        ctx.fillRect(0, 0, canvas.width, canvas.height);

                        ctx.fillStyle = 'rgba(255,255,255,0.08)';
                        ctx.font = 'bold 70px "Plus Jakarta Sans"';
                        ctx.textAlign = 'center';
                        ctx.fillText('PRINTMONT PRODUCT', canvas.width / 2, canvas.height / 2);
                    }

                    // Render Watermark
                    ctx.save();
                    ctx.font = `bold ${fontSize}px "Plus Jakarta Sans", sans-serif`;
                    ctx.fillStyle = color;
                    ctx.globalAlpha = opacity;

                    const pad = 24;
                    const textMetrics = ctx.measureText(text);
                    const textW = textMetrics.width;
                    const textH = fontSize;

                    if (currentPos === 'repeat') {
                        ctx.rotate(-25 * Math.PI / 180);
                        for (let x = -canvas.width; x < canvas.width * 2; x += textW + 100) {
                            for (let y = -canvas.height; y < canvas.height * 2; y += textH + 80) {
                                ctx.shadowColor = 'rgba(0,0,0,0.6)';
                                ctx.shadowBlur = 4;
                                ctx.fillText(text, x, y);
                            }
                        }
                    } else {
                        let x = canvas.width - textW - pad;
                        let y = canvas.height - pad;

                        if (currentPos === 'top_left') {
                            x = pad;
                            y = pad + textH;
                        } else if (currentPos === 'top_right') {
                            x = canvas.width - textW - pad;
                            y = pad + textH;
                        } else if (currentPos === 'bottom_left') {
                            x = pad;
                            y = canvas.height - pad;
                        } else if (currentPos === 'center') {
                            x = (canvas.width - textW) / 2;
                            y = (canvas.height + textH) / 2;
                        }

                        ctx.shadowColor = 'rgba(0,0,0,0.8)';
                        ctx.shadowBlur = 6;
                        ctx.fillText(text, x, y);
                    }
                    ctx.restore();
                });
            }

            if (textInput) textInput.addEventListener('input', renderSimulator);
            if (sizeInput) {
                sizeInput.addEventListener('input', function() {
                    document.getElementById('val_size').textContent = this.value + 'px';
                    renderSimulator();
                });
            }
            if (opacityInput) {
                opacityInput.addEventListener('input', function() {
                    document.getElementById('val_opacity').textContent = this.value + '%';
                    renderSimulator();
                });
            }
            if (colorInput) {
                colorInput.addEventListener('input', function() {
                    colorTextInput.value = this.value;
                    renderSimulator();
                });
            }
            if (colorTextInput) {
                colorTextInput.addEventListener('input', function() {
                    colorInput.value = this.value;
                    renderSimulator();
                });
            }
            if (bgSelect) bgSelect.addEventListener('change', renderSimulator);

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    if (this.value === 'image') {
                        document.getElementById('text_block').style.display = 'none';
                        document.getElementById('image_block').style.display = 'block';
                    } else {
                        document.getElementById('text_block').style.display = 'block';
                        document.getElementById('image_block').style.display = 'none';
                    }
                });
            }

            posBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    posBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentPos = this.getAttribute('data-pos');
                    posInput.value = currentPos;
                    renderSimulator();
                });
            });

            renderSimulator();
        });
    </script>
</body>
</html>
