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
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_special_offer'])) {
    try {
        $count = $controller->createSpecialOffers($_POST, $_FILES);
        if ($count > 0) {
            $_SESSION['success_message'] = "{$count} Special Offer banner(s) added successfully!";
            header("Location: special-offers.php");
            exit;
        } else {
            throw new Exception("Please upload at least one image or provide an image URL.");
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
    <title>Add Special Offer | Printmont</title>
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="img/icons/icon-48x48.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .choice-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        .choice-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18);
        }
        .choice-card input[type="radio"] {
            position: absolute;
            top: 12px;
            right: 12px;
        }
        .choice-icon {
            font-size: 2rem;
            margin-bottom: 8px;
            color: #2563eb;
        }
        .choice-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .choice-desc {
            font-size: 0.78rem;
            color: #64748b;
        }

        .slot-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .slot-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
        }
        .img-preview-box {
            width: 100%;
            height: 95px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            display: none;
            margin-top: 8px;
        }
        .img-preview-box.square {
            height: 95px;
            width: 95px;
            margin: 8px auto 0;
            display: none;
        }

        .preset-badge-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 15px;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 2px 8px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .preset-badge-btn:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .preview-layout-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }
        .preview-grid-2x2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-width: 320px;
            margin: 0 auto;
        }
        .preview-grid-item-square {
            aspect-ratio: 1/1;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            border: 1px solid #cbd5e1;
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
                                    <h1 class="h3 mb-0 text-gray-800 fw-bold">Add Special Offer</h1>
                                    <p class="text-muted mb-0 small">Create special offer banners, carousels, 1.5 peeking sliders, or 2x2 square image grids.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="add-special-offer.php" enctype="multipart/form-data" id="specialOfferForm">
                        
                        <!-- ================= STEP 1: SELECT DISPLAY TYPE ================= -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <span class="badge bg-primary rounded-circle me-2">1</span>
                                    Step 1: Choose Display Mode (Banner vs Carousel)
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6 col-12">
                                        <label class="choice-card selected" id="card-type-banner" onclick="selectDisplayType('banner')">
                                            <input type="radio" name="display_type" value="banner" checked id="radio-type-banner">
                                            <div class="choice-icon text-primary">
                                                <i class="fas fa-image"></i>
                                            </div>
                                            <div class="choice-title">Static Banner Grid</div>
                                            <div class="choice-desc">Fixed banners side-by-side or square grid (e.g. 1 Full width, 2 columns, or 4 square images in 2 rows).</div>
                                        </label>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="choice-card" id="card-type-carousel" onclick="selectDisplayType('carousel')">
                                            <input type="radio" name="display_type" value="carousel" id="radio-type-carousel">
                                            <div class="choice-icon text-warning">
                                                <i class="fas fa-sliders"></i>
                                            </div>
                                            <div class="choice-title">Sliding Carousel / Slider</div>
                                            <div class="choice-desc">Horizontal auto-sliding or draggable banner carousel (1 slide, 1.5 peeking slide, or 2 slides).</div>
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
                                    Step 2: Choose Layout Format
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card selected" id="card-layout-1" onclick="selectLayoutFormat('1')">
                                            <input type="radio" name="layout_format" value="1" checked id="radio-layout-1">
                                            <div class="choice-icon text-primary">
                                                <i class="fas fa-square"></i>
                                            </div>
                                            <div class="choice-title">1 Column (Full Width)</div>
                                            <div class="choice-desc">1 Single large promotional banner / full width slide.</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card" id="card-layout-1.5" onclick="selectLayoutFormat('1.5')">
                                            <input type="radio" name="layout_format" value="1.5" id="radio-layout-1.5">
                                            <div class="choice-icon text-warning">
                                                <i class="fas fa-film"></i>
                                            </div>
                                            <div class="choice-title">1.5 Peeking Carousel</div>
                                            <div class="choice-desc">1 Full banner + 50% preview peek of next slide.</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card" id="card-layout-2" onclick="selectLayoutFormat('2')">
                                            <input type="radio" name="layout_format" value="2" id="radio-layout-2">
                                            <div class="choice-icon text-success">
                                                <i class="fas fa-columns"></i>
                                            </div>
                                            <div class="choice-title">2 Columns</div>
                                            <div class="choice-desc">2 Banners side-by-side or 2 slides visible in one row.</div>
                                        </label>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-12">
                                        <label class="choice-card" id="card-layout-2x2" onclick="selectLayoutFormat('2x2')">
                                            <input type="radio" name="layout_format" value="2x2" id="radio-layout-2x2">
                                            <div class="choice-icon text-danger">
                                                <i class="fas fa-th-large"></i>
                                            </div>
                                            <div class="choice-title">4 Images Square (2x2 Grid)</div>
                                            <div class="choice-desc">4 Square shape images arranged in 2 rows (2 per row).</div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= STEP 3: SPECIAL OFFER DETAILS & SLOTS ================= -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold text-dark">
                                    <span class="badge bg-primary rounded-circle me-2">3</span>
                                    Step 3: Upload Banner Images &amp; Offer Details
                                </h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-extra-slot" onclick="addExtraSlot()" style="display: none;">
                                    <i class="fas fa-plus me-1"></i> Add Another Slide
                                </button>
                            </div>
                            <div class="card-body p-4">

                                <!-- Global Offer Settings -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Offer Title / Heading</label>
                                        <input type="text" class="form-control" name="title" id="global-title" placeholder="e.g. Special Offers &amp; Discounts" oninput="syncGlobalValues()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Offer Badge Tag</label>
                                        <input type="text" class="form-control" name="offer_badge" id="global-badge" value="Special Offer" placeholder="e.g. Flat 50% Off">
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <button type="button" class="preset-badge-btn" onclick="setGlobalBadge('Special Offer')">Special Offer</button>
                                            <button type="button" class="preset-badge-btn" onclick="setGlobalBadge('Flat 50% Off')">Flat 50% Off</button>
                                            <button type="button" class="preset-badge-btn" onclick="setGlobalBadge('Min. 30% Off')">Min 30% Off</button>
                                            <button type="button" class="preset-badge-btn" onclick="setGlobalBadge('Deal of the Day')">Deal of Day</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label fw-semibold">Badge Color</label>
                                        <input type="color" class="form-control form-control-color w-100" name="badge_color" value="#ef4444" title="Choose badge color">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label fw-semibold">Target Device</label>
                                        <select class="form-select fw-semibold" name="device_target">
                                            <option value="all">All Devices</option>
                                            <option value="desktop">Desktop Only</option>
                                            <option value="mobile">Mobile Only</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Offer Start Date (Optional)</label>
                                        <input type="date" class="form-control" name="start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Offer End Date / Expiry (Optional)</label>
                                        <input type="date" class="form-control" name="end_date">
                                    </div>
                                </div>

                                <hr class="my-4 text-muted">

                                <!-- Dynamic Slots Container -->
                                <div class="fw-bold text-dark mb-3" id="slots-header-text">
                                    <i class="fas fa-images text-primary me-2"></i> Banner Slots &amp; Uploads:
                                </div>

                                <div id="slotsContainer">
                                    <!-- Populated dynamically via JS based on selected layout -->
                                </div>

                            </div>
                        </div>

                        <!-- Submit Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <a href="special-offers.php" class="btn btn-light border px-4">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" name="save_special_offer" class="btn btn-primary btn-lg px-5 shadow">
                                <i class="fas fa-check-circle me-2"></i> Save Special Offer Banner
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
        let currentDisplayType = 'banner';
        let currentLayoutFormat = '1';
        let slotCounter = 0;

        function selectDisplayType(type) {
            currentDisplayType = type;
            document.querySelectorAll('[id^="card-type-"]').forEach(c => c.classList.remove('selected'));
            document.getElementById('card-type-' + type)?.classList.add('selected');
            document.getElementById('radio-type-' + type).checked = true;

            const addExtraBtn = document.getElementById('btn-add-extra-slot');
            if (type === 'carousel') {
                if (addExtraBtn) addExtraBtn.style.display = 'inline-block';
            } else {
                if (addExtraBtn) addExtraBtn.style.display = 'none';
            }

            renderSlotsForLayout();
        }

        function selectLayoutFormat(format) {
            currentLayoutFormat = format;
            document.querySelectorAll('[id^="card-layout-"]').forEach(c => c.classList.remove('selected'));
            document.getElementById('card-layout-' + format)?.classList.add('selected');
            document.getElementById('radio-layout-' + format).checked = true;

            renderSlotsForLayout();
        }

        function setGlobalBadge(badgeText) {
            document.getElementById('global-badge').value = badgeText;
            document.querySelectorAll('.slot-badge-input').forEach(inp => {
                if (!inp.value || inp.value === 'Special Offer') inp.value = badgeText;
            });
        }

        function syncGlobalValues() {
            // Optional live syncing
        }

        function renderSlotsForLayout() {
            const container = document.getElementById('slotsContainer');
            container.innerHTML = '';
            slotCounter = 0;

            const headerText = document.getElementById('slots-header-text');

            if (currentLayoutFormat === '2x2') {
                headerText.innerHTML = '<i class="fas fa-th-large text-danger me-2"></i> 4 Square Shape Images (2 in Row 1, 2 in Row 2):';
                // Render 4 square image slots
                for (let i = 0; i < 4; i++) {
                    const rowNum = i < 2 ? 1 : 2;
                    const colNum = (i % 2) + 1;
                    appendSlotCard(i, `Square Image #${i + 1} (Row ${rowNum}, Col ${colNum})`, true, false);
                }
                slotCounter = 4;
            } else if (currentLayoutFormat === '2') {
                headerText.innerHTML = '<i class="fas fa-columns text-success me-2"></i> 2 Column Banner Slots (Side-by-Side):';
                for (let i = 0; i < 2; i++) {
                    appendSlotCard(i, `Banner #${i + 1}`, false, false);
                }
                slotCounter = 2;
            } else if (currentLayoutFormat === '1.5') {
                headerText.innerHTML = '<i class="fas fa-film text-warning me-2"></i> 1.5 Peeking Carousel Slide Slots:';
                for (let i = 0; i < 2; i++) {
                    appendSlotCard(i, `Slide #${i + 1}`, false, i > 0);
                }
                slotCounter = 2;
            } else {
                // 1 Column
                headerText.innerHTML = '<i class="fas fa-square text-primary me-2"></i> Banner Image Slots:';
                appendSlotCard(0, `Banner #1 (Full Width)`, false, false);
                slotCounter = 1;
            }
        }

        function appendSlotCard(idx, title, isSquare = false, canRemove = false) {
            const container = document.getElementById('slotsContainer');
            const card = document.createElement('div');
            card.className = 'slot-card mb-3';
            card.id = `slot-card-${idx}`;

            const defBadge = document.getElementById('global-badge')?.value || 'Special Offer';

            card.innerHTML = `
                <div class="slot-header">
                    <span><i class="fas ${isSquare ? 'fa-th-large text-danger' : 'fa-image text-primary'} me-2"></i> ${title}</span>
                    ${canRemove ? `<button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSlot(${idx})"><i class="fas fa-trash me-1"></i> Remove</button>` : ''}
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-desktop text-primary me-1"></i> Desktop Image ${isSquare ? '(1:1 Square recommended)' : ''} <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" name="slots_image_desktop_${idx}" accept="image/*" onchange="previewSlotImage(this, 'preview_desk_${idx}')">
                            <input type="text" class="form-control form-control-sm mt-1" name="slots[${idx}][image_desktop_url]" placeholder="Or enter Image URL (e.g. ./uploads/...)">
                            <img id="preview_desk_${idx}" class="${isSquare ? 'img-preview-box square' : 'img-preview-box'}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-mobile-screen text-success me-1"></i> Mobile Image (Optional)
                            </label>
                            <input type="file" class="form-control" name="slots_image_mobile_${idx}" accept="image/*" onchange="previewSlotImage(this, 'preview_mob_${idx}')">
                            <input type="text" class="form-control form-control-sm mt-1" name="slots[${idx}][image_mobile_url]" placeholder="Or enter Mobile Image URL">
                            <img id="preview_mob_${idx}" class="${isSquare ? 'img-preview-box square' : 'img-preview-box'}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Title / Headline</label>
                            <input type="text" class="form-control" name="slots[${idx}][title]" placeholder="e.g. Buy 1 Get 1 Free">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Offer Badge / Tag</label>
                            <input type="text" class="form-control slot-badge-input" name="slots[${idx}][offer_badge]" value="${defBadge}" placeholder="e.g. Flat 50% Off">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Redirect Link URL</label>
                            <input type="text" class="form-control font-monospace" name="slots[${idx}][link_url]" value="/allproducts?filter=discount" placeholder="e.g. /allproducts?filter=discount">
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        }

        function addExtraSlot() {
            appendSlotCard(slotCounter, `Slide #${slotCounter + 1}`, currentLayoutFormat === '2x2', true);
            slotCounter++;
        }

        function removeSlot(idx) {
            document.getElementById(`slot-card-${idx}`)?.remove();
        }

        function previewSlotImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            renderSlotsForLayout();
        });
    </script>
</body>
</html>
