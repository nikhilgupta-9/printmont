<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/HomeLayoutController.php');

$isAdmin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
if (!$isAdmin) {
    header("Location: index.php");
    exit();
}

$layoutController = new HomeLayoutController();
$desktopSections = $layoutController->getSections('desktop');
$mobileSections = $layoutController->getSections('mobile');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Homepage Layout Manager - Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { opacity: 0; }
        .drag-handle { cursor: grab; padding: 10px; color: #adb5bd; }
        .drag-handle:active { cursor: grabbing; }
        .sortable-item {
            transition: all 0.2s ease;
            background: #fff;
            border-left: 4px solid #495057;
        }
        .sortable-item.sortable-ghost { opacity: 0.4; background: #e9ecef; }
        .sortable-item:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .type-badge { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .tab-icon { margin-right: 6px; }
        .preview-img-thumbnail { max-width: 80px; max-height: 50px; border-radius: 4px; object-fit: cover; }
        .slot-card { background: #f8f9fa; border: 1px dashed #dee2e6; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        .glass-header { background: rgba(255,255,255,0.85); backdrop-filter: blur(10px); }
        .switch { position: relative; display: inline-block; width: 42px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider-switch {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .3s; border-radius: 34px;
        }
        .slider-switch:before {
            position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
            background-color: white; transition: .3s; border-radius: 50%;
        }
        input:checked + .slider-switch { background-color: #28a745; }
        input:checked + .slider-switch:before { transform: translateX(20px); }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>

    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h1 class="h3 mb-2"><strong>Homepage Layout</strong> Manager</h1>
                        <p class="text-muted">Manage layouts, sliders, banners, and product sections for desktop and mobile homepages.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New Section
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs card-header-tabs m-0" id="layoutTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active py-3 px-4" id="desktop-tab" data-bs-toggle="tab" data-bs-target="#desktop-pane" type="button" role="tab"><i class="fas fa-desktop tab-icon text-primary"></i>Desktop Homepage</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link py-3 px-4" id="mobile-tab" data-bs-toggle="tab" data-bs-target="#mobile-pane" type="button" role="tab"><i class="fas fa-mobile-alt tab-icon text-success"></i>Mobile Homepage</button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="layoutTabContent">
                                    <!-- DESKTOP TAB -->
                                    <div class="tab-pane fade show active" id="desktop-pane" role="tabpanel">
                                        <div class="alert alert-info py-2"><i class="fas fa-info-circle me-2"></i>Drag and drop sections to rearrange their order on the desktop homepage. Changes are saved automatically.</div>
                                        <div id="desktop-list" class="list-group">
                                            <?php foreach ($desktopSections as $sec): ?>
                                                <div class="list-group-item sortable-item d-flex align-items-center justify-content-between p-3 mb-2 rounded shadow-sm" data-id="<?php echo $sec['id']; ?>" style="border-left-color: <?php echo getBorderColor($sec['section_type']); ?>">
                                                    <div class="d-flex align-items-center">
                                                        <div class="drag-handle"><i class="fas fa-bars fa-lg"></i></div>
                                                        <div class="ms-3">
                                                            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($sec['label']); ?></div>
                                                            <div class="text-muted small d-flex align-items-center gap-2 mt-1">
                                                                <span class="badge bg-light text-dark border py-1 px-2 type-badge"><?php echo str_replace('_', ' ', $sec['section_type']); ?></span>
                                                                <span>Key: <code><?php echo htmlspecialchars($sec['section_key']); ?></code></span>
                                                                <?php if ($sec['api_action']): ?>
                                                                    <span>Action: <strong><?php echo htmlspecialchars($sec['api_action']); ?></strong></span>
                                                                <?php endif; ?>
                                                                <?php if ($sec['product_limit']): ?>
                                                                    <span>Limit: <strong><?php echo $sec['product_limit']; ?></strong></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($sec['banners'])): ?>
                                                            <div class="d-none d-md-flex align-items-center gap-1 me-2">
                                                                <?php foreach (array_slice($sec['banners'], 0, 4) as $banner): ?>
                                                                    <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" class="preview-img-thumbnail" alt="thumbnail" title="<?php echo htmlspecialchars($banner['title']); ?>">
                                                                <?php endforeach; ?>
                                                                <?php if (count($sec['banners']) > 4): ?>
                                                                    <span class="badge bg-secondary">+<?php echo count($sec['banners']) - 4; ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <label class="switch m-0">
                                                            <input type="checkbox" onchange="toggleSection(<?php echo $sec['id']; ?>, this.checked)" <?php echo $sec['status'] === 'active' ? 'checked' : ''; ?>>
                                                            <span class="slider-switch"></span>
                                                        </label>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sec)); ?>)"><i class="fas fa-edit"></i> Edit</button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSection(<?php echo $sec['id']; ?>)"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- MOBILE TAB -->
                                    <div class="tab-pane fade" id="mobile-pane" role="tabpanel">
                                        <div class="alert alert-info py-2"><i class="fas fa-info-circle me-2"></i>Drag and drop sections to rearrange their order on the mobile homepage. Changes are saved automatically.</div>
                                        <div id="mobile-list" class="list-group">
                                            <?php foreach ($mobileSections as $sec): ?>
                                                <div class="list-group-item sortable-item d-flex align-items-center justify-content-between p-3 mb-2 rounded shadow-sm" data-id="<?php echo $sec['id']; ?>" style="border-left-color: <?php echo getBorderColor($sec['section_type']); ?>">
                                                    <div class="d-flex align-items-center">
                                                        <div class="drag-handle"><i class="fas fa-bars fa-lg"></i></div>
                                                        <div class="ms-3">
                                                            <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($sec['label']); ?></div>
                                                            <div class="text-muted small d-flex align-items-center gap-2 mt-1">
                                                                <span class="badge bg-light text-dark border py-1 px-2 type-badge"><?php echo str_replace('_', ' ', $sec['section_type']); ?></span>
                                                                <span>Key: <code><?php echo htmlspecialchars($sec['section_key']); ?></code></span>
                                                                <?php if ($sec['api_action']): ?>
                                                                    <span>Action: <strong><?php echo htmlspecialchars($sec['api_action']); ?></strong></span>
                                                                <?php endif; ?>
                                                                <?php if ($sec['product_limit']): ?>
                                                                    <span>Limit: <strong><?php echo $sec['product_limit']; ?></strong></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($sec['banners'])): ?>
                                                            <div class="d-none d-md-flex align-items-center gap-1 me-2">
                                                                <?php foreach (array_slice($sec['banners'], 0, 4) as $banner): ?>
                                                                    <img src="<?php echo htmlspecialchars($banner['image_url_desktop']); ?>" class="preview-img-thumbnail" alt="thumbnail" title="<?php echo htmlspecialchars($banner['title']); ?>">
                                                                <?php endforeach; ?>
                                                                <?php if (count($sec['banners']) > 4): ?>
                                                                    <span class="badge bg-secondary">+<?php echo count($sec['banners']) - 4; ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <label class="switch m-0">
                                                            <input type="checkbox" onchange="toggleSection(<?php echo $sec['id']; ?>, this.checked)" <?php echo $sec['status'] === 'active' ? 'checked' : ''; ?>>
                                                            <span class="slider-switch"></span>
                                                        </label>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sec)); ?>)"><i class="fas fa-edit"></i> Edit</button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSection(<?php echo $sec['id']; ?>)"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ================= ADD/EDIT SECTION MODAL ================= -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="sectionForm" onsubmit="saveSection(event)" enctype="multipart/form-data">
                <input type="hidden" id="section-id" name="id">
                <input type="hidden" id="section-page-target" name="page_target" value="desktop">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Core details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required-field">Section Label</label>
                            <input type="text" class="form-control" id="section-label" name="label" required placeholder="e.g. Hot Deals Carousel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Section Type</label>
                            <select class="form-select" id="section-type" name="section_type" onchange="handleTypeChange(this.value)">
                                <option value="product_carousel">Product Carousel</option>
                                <option value="compact_grid">Compact Product Grid (Mobile)</option>
                                <option value="mobile_list">Mobile Product List (Vertical)</option>
                                <option value="slider">Slider (Carousel Banner)</option>
                                <option value="banner">Banner (Static Grid)</option>
                                <option value="category_mosaic">Category Product Mosaic</option>
                                <option value="featured_grid">Featured Product Grid</option>
                                <option value="brand_directory">Brand Directory Section</option>
                                <option value="bulk_widget">Bulk Order Widget</option>
                                <option value="gift_finder">Gift Finder Section</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3" id="section-key-container">
                        <div class="col-12">
                            <label class="form-label">Unique Section Key</label>
                            <input type="text" class="form-control" id="section-key" name="section_key" placeholder="Generate automatically if left blank">
                            <div class="form-text text-muted">Use standard alphanumeric characters & underscores. Must be unique.</div>
                        </div>
                    </div>

                    <!-- BANNERS & SLIDERS SLOTS (Step 2 & 3) -->
                    <div id="banner-slider-options" style="display: none;">
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label required-field" id="columns-label">Grid Columns</label>
                                <select class="form-select" id="section-columns" name="columns_per_row">
                                    <option value="1">1 Slide / 1 Column</option>
                                    <option value="2">2 Columns / Slots</option>
                                    <option value="3">3 Columns / Slots</option>
                                    <option value="4">4 Columns / Slots</option>
                                </select>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-secondary btn-sm" id="add-slide-btn" onclick="addNewSlideSlot()" style="display: none;">
                                    <i class="fas fa-plus"></i> Add Slide
                                </button>
                            </div>
                        </div>
                        
                        <div class="fw-bold mb-2" id="slots-header-text">Upload Banner / Slider Images</div>
                        <div id="slots-container">
                            <!-- Dynamically generated slots -->
                        </div>
                    </div>

                    <!-- PRODUCT CONFIGURATIONS -->
                    <div id="product-options" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label required-field">API Action</label>
                                <select class="form-select" id="product-api-action" name="api_action">
                                    <option value="bestseller">Bestsellers</option>
                                    <option value="top_selection">Top Selection</option>
                                    <option value="discount_for_you">Discount For You</option>
                                    <option value="top_rated">Top Rated</option>
                                    <option value="top_deal">Top Deals</option>
                                    <option value="grouped_categories">Grouped Categories</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Product Limit</label>
                                <input type="number" class="form-control" id="product-limit" name="product_limit" value="10" min="1" max="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Badge Text</label>
                                <input type="text" class="form-control" id="product-badge-text" name="badge_text" placeholder="e.g. Customizable">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Background Image URL (Optional)</label>
                                <input type="text" class="form-control" id="product-bg-image" name="background_image_url" placeholder="e.g. ./bg/flashsale.png">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveBtn">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // Initialize sortable lists
    document.addEventListener("DOMContentLoaded", function() {
        const desktopList = document.getElementById('desktop-list');
        const mobileList = document.getElementById('mobile-list');

        new Sortable(desktopList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                saveOrder('desktop');
            }
        });

        new Sortable(mobileList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                saveOrder('mobile');
            }
        });
        
        document.body.style.opacity = '1';
    });

    // Detect which tab is active and update targets
    const tabElList = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabElList.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', event => {
            const targetId = event.target.id;
            const pageTarget = targetId.includes('desktop') ? 'desktop' : 'mobile';
            document.getElementById('section-page-target').value = pageTarget;
        });
    });

    function saveOrder(target) {
        const listId = target === 'desktop' ? 'desktop-list' : 'mobile-list';
        const ids = Array.from(document.querySelectorAll(`#${listId} [data-id]`)).map(el => el.dataset.id);
        
        fetch('api/home-layout-api.php?action=reorder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'Failed to update section order');
            }
        })
        .catch(err => {
            console.error('Error reordering sections:', err);
        });
    }

    function toggleSection(id, checked) {
        const status = checked ? 'active' : 'inactive';
        fetch('api/home-layout-api.php?action=toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: status })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'Failed to toggle status');
            }
        })
        .catch(err => console.error(err));
    }

    function deleteSection(id) {
        if (confirm('Are you sure you want to delete this section? Banners and images associated will be deleted permanently.')) {
            fetch('api/home-layout-api.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete section');
                }
            })
            .catch(err => console.error(err));
        }
    }

    let slideIndexCounter = 0;

    function handleTypeChange(type) {
        const isSlider = (type === 'slider');
        const isBanner = (type === 'banner');
        const isSliderOrBanner = isSlider || isBanner;
        const isProduct = ['product_carousel', 'compact_grid', 'mobile_list'].includes(type);

        document.getElementById('banner-slider-options').style.display = isSliderOrBanner ? 'block' : 'none';
        document.getElementById('product-options').style.display = isProduct ? 'block' : 'none';

        const addSlideBtn = document.getElementById('add-slide-btn');
        const columnsLabel = document.getElementById('columns-label');
        const slotsHeaderText = document.getElementById('slots-header-text');

        if (isSlider) {
            addSlideBtn.style.display = 'inline-block';
            columnsLabel.textContent = 'Visible Slides per View';
            slotsHeaderText.textContent = 'Upload Slider Slides';
            
            const visibleSlides = parseInt(document.getElementById('section-columns').value) || 1;
            const currentSlots = document.querySelectorAll('#slots-container .slot-card').length;
            if (currentSlots === 0) {
                generateSlotsForSlider(Math.max(3, visibleSlides));
            }
        } else if (isBanner) {
            addSlideBtn.style.display = 'none';
            columnsLabel.textContent = 'Grid Columns';
            slotsHeaderText.textContent = 'Upload Banner Images (Exactly equal to Column count)';
            
            generateSlotsForBanner(document.getElementById('section-columns').value);
        }
    }

    document.getElementById('section-columns').addEventListener('change', function() {
        const type = document.getElementById('section-type').value;
        if (type === 'banner') {
            generateSlotsForBanner(this.value);
        }
    });

    function generateSlotsForBanner(count) {
        const container = document.getElementById('slots-container');
        container.innerHTML = '';
        slideIndexCounter = 0;

        for (let i = 0; i < count; i++) {
            appendSlotHTML(i, `Image #${i + 1}`, true, false);
        }
        slideIndexCounter = count;
    }

    function generateSlotsForSlider(count) {
        const container = document.getElementById('slots-container');
        container.innerHTML = '';
        slideIndexCounter = 0;

        for (let i = 0; i < count; i++) {
            appendSlotHTML(i, `Slide #${i + 1}`, i === 0, true);
        }
        slideIndexCounter = count;
    }

    function addNewSlideSlot() {
        appendSlotHTML(slideIndexCounter, `Slide #${slideIndexCounter + 1}`, false, true);
        slideIndexCounter++;
    }

    function removeSlideSlot(elementId) {
        const slotEl = document.getElementById(elementId);
        if (slotEl) {
            slotEl.remove();
        }
    }

    function appendSlotHTML(index, labelText, isRequired, isDeletable, banner = {}) {
        const container = document.getElementById('slots-container');
        const elementId = `slot-card-${index}`;
        const requiredAttr = isRequired ? 'required' : '';
        const requiredStar = isRequired ? ' <span class="text-danger">*</span>' : '';
        
        let deleteBtnHtml = '';
        if (isDeletable) {
            deleteBtnHtml = `
                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSlideSlot('${elementId}')">
                    <i class="fas fa-times"></i> Remove
                </button>
            `;
        }

        const slotHtml = `
            <div class="slot-card shadow-sm border p-3 mb-2 rounded bg-light" id="${elementId}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">${labelText}</div>
                    ${deleteBtnHtml}
                </div>
                <input type="hidden" name="existing_desktop[${index}]" value="${banner.image_url_desktop || ''}">
                <input type="hidden" name="existing_mobile[${index}]" value="${banner.image_url_mobile || ''}">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">Title / Caption</label>
                        <input type="text" class="form-control form-control-sm" name="banner_titles[${index}]" value="${banner.title || ''}" placeholder="Title/Caption">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">Target URL / Redirect</label>
                        <input type="text" class="form-control form-control-sm" name="banner_targets[${index}]" value="${banner.target_url || ''}" placeholder="e.g. /category/1">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label small">Desktop Image${requiredStar}</label>
                        <input type="file" class="form-control form-control-sm" name="images_desktop[${index}]" accept="image/*" ${requiredAttr}>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Mobile Image (Optional)</label>
                        <input type="file" class="form-control form-control-sm" name="images_mobile[${index}]" accept="image/*">
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', slotHtml);
    }

    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add New Section';
        document.getElementById('section-id').value = '';
        document.getElementById('section-label').value = '';
        document.getElementById('section-key').value = '';
        document.getElementById('section-key-container').style.display = 'block';
        document.getElementById('section-type').disabled = false;
        
        document.getElementById('section-type').value = 'product_carousel';
        handleTypeChange('product_carousel');

        const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
        modal.show();
    }

    function openEditModal(sec) {
        document.getElementById('modalTitle').textContent = 'Edit Section Configurations';
        document.getElementById('section-id').value = sec.id;
        document.getElementById('section-label').value = sec.label;
        document.getElementById('section-key').value = sec.section_key;
        document.getElementById('section-key-container').style.display = 'none';
        document.getElementById('section-type').value = sec.section_type;
        document.getElementById('section-type').disabled = true;

        handleTypeChange(sec.section_type);

        if (sec.section_type === 'slider' || sec.section_type === 'banner') {
            document.getElementById('section-columns').value = sec.columns_per_row;
            generateSlotsForEdit(sec.banners);
        } else if (['product_carousel', 'compact_grid', 'mobile_list'].includes(sec.section_type)) {
            document.getElementById('product-api-action').value = sec.api_action || 'bestseller';
            document.getElementById('product-limit').value = sec.product_limit || 10;
            document.getElementById('product-badge-text').value = sec.badge_text || '';
            document.getElementById('product-bg-image').value = sec.background_image_url || '';
        }

        const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
        modal.show();
    }

    function generateSlotsForEdit(banners) {
        const container = document.getElementById('slots-container');
        container.innerHTML = '';
        const count = banners.length;
        const type = document.getElementById('section-type').value;
        const isSlider = (type === 'slider');

        banners.forEach((banner, i) => {
            const elementId = `slot-card-${i}`;
            const existingDesktopText = banner.image_url_desktop ? `<span class="badge bg-success ms-2">Existing Image Saved</span>` : '';
            
            let deleteBtnHtml = '';
            if (isSlider) {
                deleteBtnHtml = `
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSlideSlot('${elementId}')">
                        <i class="fas fa-times"></i> Remove
                    </button>
                `;
            }

            const slotHtml = `
                <div class="slot-card shadow-sm border p-3 mb-2 rounded bg-light" id="${elementId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">${isSlider ? 'Slide' : 'Image'} #${i + 1}</div>
                        ${deleteBtnHtml}
                    </div>
                    <input type="hidden" name="existing_desktop[${i}]" value="${banner.image_url_desktop || ''}">
                    <input type="hidden" name="existing_mobile[${i}]" value="${banner.image_url_mobile || ''}">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Title / Caption</label>
                            <input type="text" class="form-control form-control-sm" name="banner_titles[${i}]" value="${banner.title || ''}" placeholder="Title/Caption">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Target URL / Redirect</label>
                            <input type="text" class="form-control form-control-sm" name="banner_targets[${i}]" value="${banner.target_url || ''}" placeholder="e.g. /category/1">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="form-label small">Upload Desktop Image ${existingDesktopText}</label>
                            <input type="file" class="form-control form-control-sm" name="images_desktop[${i}]" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Upload Mobile Image</label>
                            <input type="file" class="form-control form-control-sm" name="images_mobile[${i}]" accept="image/*">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', slotHtml);
        });

        slideIndexCounter = count;
    }

    function saveSection(e) {
        e.preventDefault();
        const form = document.getElementById('sectionForm');
        const formData = new FormData(form);
        
        // Re-enable disabled section_type so it gets submitted
        document.getElementById('section-type').disabled = false;
        
        const isEdit = document.getElementById('section-id').value !== '';
        const url = isEdit ? 'api/home-layout-api.php?action=update' : 'api/home-layout-api.php?action=create';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to save section');
            }
        })
        .catch(err => {
            console.error('Error saving section:', err);
        });
    }
</script>
</body>
</html>
<?php
function getBorderColor($type) {
    switch ($type) {
        case 'slider': return '#007bff';
        case 'banner': return '#28a745';
        case 'product_carousel': return '#fd7e14';
        case 'compact_grid': return '#20c997';
        case 'mobile_list': return '#6f42c1';
        case 'category_mosaic': return '#e83e8c';
        case 'featured_grid': return '#17a2b8';
        default: return '#6c757d';
    }
}
?>
