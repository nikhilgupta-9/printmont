<?php
session_start();
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/BannerLayoutController.php');

$bannerController = new BannerController();

$knownPages = [
    'category'    => 'Category Pages',
    'product'     => 'Product Page',
    'cart'        => 'Cart Page',
    'checkout'    => 'Checkout Page',
    'orders'      => 'Orders Page',
    'about'       => 'About Page',
    'contact'     => 'Contact Page',
    'blog'        => 'Blog Page',
    'bulk-orders' => 'Bulk Orders Page',
];

$sections = $bannerController->getPageSections();
$pageLabels = $knownPages;
foreach ($sections as $s) {
    if (!isset($pageLabels[$s['page']])) {
        $pageLabels[$s['page']] = ucwords(str_replace(['-', '_'], ' ', $s['page'])) . ' Page';
    }
}
asort($pageLabels);

$error_message = '';
$formPage = $_GET['page'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $bannerController->createPageBanners($_POST, $_FILES);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: page-banners.php?section_id=' . (int)$result['section_id']);
        exit;
    }
    $error_message = $result['error'] ?? 'Something went wrong.';
    $formPage = $_POST['page'] ?? '';
}

$sectionsJson = json_encode(array_map(fn($s) => [
    'id'     => (int)$s['id'],
    'page'   => $s['page'],
    'key'    => $s['section_key'],
    'label'  => $s['label'],
    'count'  => (int)$s['banner_count'],
    'slider' => (int)$s['is_slider'],
], $sections), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$apiBase = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/api/banner_api.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Page Banner / Carousel | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; background-color: #f1f5f9; }
        .pb-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.04); margin-bottom:20px; overflow:hidden; }
        .pb-card-head { padding:14px 20px; background:#fafbfc; border-bottom:1px solid #eef2f6; display:flex; align-items:center; gap:10px; }
        .pb-step { width:26px; height:26px; border-radius:50%; background:#0b53a1; color:#fff; font-size:13px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
        .pb-card-head h5 { margin:0; font-size:15px; font-weight:700; color:#1e293b; }
        .pb-card-head small { color:#64748b; margin-left:auto; }
        .pb-card-body { padding:22px 20px; }
        .pb-label { font-size:13px; font-weight:600; color:#334155; margin-bottom:6px; display:block; }
        .pb-hint { font-size:12px; color:#64748b; margin-top:4px; }

        .pb-type-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .pb-type { border:2px solid #e2e8f0; border-radius:12px; padding:16px; cursor:pointer; display:flex; gap:14px; align-items:flex-start; transition:all .2s; background:#fff; position:relative; }
        .pb-type:hover { border-color:#93c5fd; }
        .pb-type input { position:absolute; opacity:0; pointer-events:none; }
        .pb-type.active { border-color:#0b53a1; background:#f0f7ff; box-shadow:0 0 0 3px rgba(11,83,161,.12); }
        .pb-type-icon { width:44px; height:44px; border-radius:10px; background:#e0edff; color:#0b53a1; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
        .pb-type strong { display:block; color:#0f172a; font-size:14.5px; }
        .pb-type span.d { font-size:12.5px; color:#64748b; }

        .pb-section-info { margin-top:12px; padding:10px 14px; border-radius:8px; background:#f8fafc; border:1px dashed #cbd5e1; font-size:12.5px; color:#475569; }
        .pb-section-info code { background:#e2e8f0; padding:1px 6px; border-radius:4px; color:#0f172a; font-size:12px; }

        .pb-slide { border:1px solid #e2e8f0; border-radius:12px; margin-bottom:16px; background:#fff; }
        .pb-slide-head { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0; }
        .pb-slide-head strong { font-size:13.5px; color:#1e293b; }
        .pb-slide-body { padding:16px; }
        .pb-drop { border:2px dashed #cbd5e1; border-radius:10px; background:#f8fafc; padding:12px; text-align:center; position:relative; cursor:pointer; transition:all .2s; min-height:150px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
        .pb-drop:hover { border-color:#0b53a1; background:#f0f7ff; }
        .pb-drop input[type=file] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        .pb-drop i { font-size:24px; color:#94a3b8; margin-bottom:6px; }
        .pb-drop .t { font-size:13px; font-weight:600; color:#334155; }
        .pb-drop .s { font-size:11.5px; color:#94a3b8; }
        .pb-drop img { max-width:100%; max-height:130px; border-radius:6px; display:none; object-fit:contain; }
        .pb-drop.has-img i, .pb-drop.has-img .t, .pb-drop.has-img .s { display:none; }
        .pb-drop.has-img img { display:block; }
        .pb-device { font-size:12px; font-weight:700; color:#0b53a1; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; display:flex; align-items:center; gap:6px; }

        .pb-actionbar { position:sticky; bottom:0; z-index:20; background:rgba(255,255,255,.96); backdrop-filter:blur(6px); border-top:1px solid #e2e8f0; padding:12px 20px; margin:0 -12px -12px; display:flex; justify-content:flex-end; gap:10px; }
        @media (max-width:767.98px) { .pb-type-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-images text-primary me-2"></i>Add Page Banner / Carousel</h3>
                        <p class="text-muted small mb-0">Place a banner or a carousel on any page (except Home) &mdash; choose the page, the section, and the format.</p>
                    </div>
                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                        <a href="page-banners.php" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i> All Page Banners</a>
                        <a href="add-banner.php" class="btn btn-outline-primary"><i class="fas fa-home me-1"></i> Home Page Banners</a>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="pageBannerForm">

                    <!-- STEP 1: Placement -->
                    <div class="pb-card">
                        <div class="pb-card-head"><span class="pb-step">1</span><h5>Where should it appear?</h5><small>Page &amp; section</small></div>
                        <div class="pb-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="pb-label">Page</label>
                                    <select name="page" id="pbPage" class="form-select" required>
                                        <option value="">Select a page…</option>
                                        <?php foreach ($pageLabels as $key => $label): ?>
                                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $formPage === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                        <option value="__custom">+ Another page (type a name)…</option>
                                    </select>
                                    <div id="pbCustomPageWrap" class="mt-2" style="display:none;">
                                        <input type="text" name="custom_page" id="pbCustomPage" class="form-control" placeholder="e.g. franchise, careers, gift-cards">
                                        <div class="pb-hint">Used as the page key. The frontend page must read banners for this key.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pb-label">Section on that page</label>
                                    <select name="section_id" id="pbSection" class="form-select"></select>
                                    <div id="pbNewSectionWrap" class="mt-2" style="display:none;">
                                        <input type="text" name="new_section_label" id="pbNewSection" class="form-control" placeholder="New section name, e.g. Top Banner, Mid Banner">
                                    </div>
                                </div>
                            </div>
                            <div class="pb-section-info" id="pbSectionInfo" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- STEP 2: Type -->
                    <div class="pb-card">
                        <div class="pb-card-head"><span class="pb-step">2</span><h5>What do you want to add?</h5><small>Single banner or carousel</small></div>
                        <div class="pb-card-body">
                            <div class="pb-type-grid">
                                <label class="pb-type active" data-type="single">
                                    <input type="radio" name="display_type" value="single" checked>
                                    <span class="pb-type-icon"><i class="fas fa-image"></i></span>
                                    <span><strong>Single Banner</strong><span class="d">One static banner with a desktop and a mobile image.</span></span>
                                </label>
                                <label class="pb-type" data-type="carousel">
                                    <input type="radio" name="display_type" value="carousel">
                                    <span class="pb-type-icon"><i class="fas fa-layer-group"></i></span>
                                    <span><strong>Carousel / Slider</strong><span class="d">Multiple slides that rotate automatically, each with desktop &amp; mobile images.</span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Creatives -->
                    <div class="pb-card">
                        <div class="pb-card-head"><span class="pb-step">3</span><h5>Banner images &amp; links</h5><small id="pbSlideHint">Upload both desktop and mobile versions</small></div>
                        <div class="pb-card-body">
                            <div class="mb-3">
                                <label class="pb-label">Name (for your reference)</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Diwali Bulk Offer">
                            </div>
                            <div id="pbSlides"></div>
                            <button type="button" id="pbAddSlide" class="btn btn-outline-primary" style="display:none;"><i class="fas fa-plus me-1"></i> Add another slide</button>
                        </div>
                    </div>

                    <!-- STEP 4: Settings -->
                    <div class="pb-card">
                        <div class="pb-card-head"><span class="pb-step">4</span><h5>Visibility &amp; schedule</h5><small>Optional</small></div>
                        <div class="pb-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="pb-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active (visible)</option>
                                        <option value="inactive">Inactive (hidden)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="pb-label">Start date</label>
                                    <input type="date" name="start_date" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="pb-label">End date</label>
                                    <input type="date" name="end_date" class="form-control">
                                </div>
                            </div>
                            <div class="row g-3 mt-1" id="pbCarouselOpts" style="display:none;">
                                <div class="col-md-4">
                                    <label class="pb-label">Auto-play</label>
                                    <select name="auto_running" class="form-select"><option value="enable">Enabled</option><option value="disable">Disabled</option></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="pb-label">Slide speed (ms)</label>
                                    <input type="number" name="auto_run_speed" class="form-control" value="3000" min="1000" step="500">
                                </div>
                                <div class="col-md-4">
                                    <label class="pb-label">Arrow buttons</label>
                                    <select name="button_arrows" class="form-select"><option value="enable">Show</option><option value="disable">Hide</option></select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pb-actionbar">
                        <a href="page-banners.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save</button>
                    </div>
                </form>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
<script>
const SECTIONS = <?php echo $sectionsJson; ?>;
const API_BASE = <?php echo json_encode($apiBase); ?>;
const pageSel = document.getElementById('pbPage');
const sectionSel = document.getElementById('pbSection');
const slidesBox = document.getElementById('pbSlides');
const addBtn = document.getElementById('pbAddSlide');
let slideCounter = 0;
let currentType = 'single';

function esc(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function currentPage() {
    return pageSel.value;
}

function rebuildSections() {
    const page = currentPage();
    document.getElementById('pbCustomPageWrap').style.display = page === '__custom' ? 'block' : 'none';
    let html = '';
    const list = SECTIONS.filter(s => s.page === page);
    list.forEach(s => {
        html += `<option value="${s.id}">${esc(s.label)} (${s.count} banner${s.count === 1 ? '' : 's'})</option>`;
    });
    html += '<option value="0">+ Create a new section…</option>';
    sectionSel.innerHTML = html;
    if (!list.length) sectionSel.value = '0';
    onSectionChange();
}

function onSectionChange() {
    const isNew = sectionSel.value === '0';
    document.getElementById('pbNewSectionWrap').style.display = isNew ? 'block' : 'none';
    const info = document.getElementById('pbSectionInfo');
    if (isNew || !currentPage()) { info.style.display = 'none'; return; }
    const s = SECTIONS.find(x => String(x.id) === sectionSel.value);
    if (!s) { info.style.display = 'none'; return; }
    info.style.display = 'block';
    info.innerHTML = `Section key: <code>${esc(s.key)}</code> &nbsp;·&nbsp; Currently: <strong>${s.slider ? 'Carousel' : 'Banner'}</strong> with ${s.count} item${s.count === 1 ? '' : 's'}<br>`
        + `Frontend reads it from <code>${esc(API_BASE)}?section=${esc(s.key)}</code>. Saving here replaces nothing &mdash; new items are added to this section.`;
}

function dropZone(device, idx) {
    const isDesktop = device === 'desktop';
    return `
    <div class="col-md-${isDesktop ? '7' : '5'}">
        <div class="pb-device"><i class="fas fa-${isDesktop ? 'desktop' : 'mobile-alt'}"></i> ${isDesktop ? 'Desktop image' : 'Mobile image'}</div>
        <div class="pb-drop" data-drop>
            <input type="file" name="slide_${device}[${idx}]" accept="image/*">
            <i class="fas fa-cloud-upload-alt"></i>
            <div class="t">Click or drop an image</div>
            <div class="s">${isDesktop ? 'Wide, e.g. 1920 × 600 px' : 'Portrait/square, e.g. 750 × 600 px'} · JPG, PNG, WebP · max 10 MB</div>
            <img alt="preview">
        </div>
        ${isDesktop ? '' : '<div class="pb-hint">Optional &mdash; desktop image is used if left empty.</div>'}
    </div>`;
}

function addSlide() {
    const idx = slideCounter++;
    const n = slidesBox.children.length + 1;
    const el = document.createElement('div');
    el.className = 'pb-slide';
    el.innerHTML = `
        <div class="pb-slide-head">
            <strong class="pb-slide-title">${currentType === 'carousel' ? 'Slide ' + n : 'Banner'}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger pb-remove" style="display:none;"><i class="fas fa-trash"></i></button>
        </div>
        <div class="pb-slide-body">
            <div class="row g-3">
                ${dropZone('desktop', idx)}
                ${dropZone('mobile', idx)}
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-8">
                    <label class="pb-label">Link (where it goes on click)</label>
                    <input type="text" name="slides[${idx}][url]" class="form-control" placeholder="/category/visiting-cards or https://…">
                </div>
                <div class="col-md-4">
                    <label class="pb-label">Slide title <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="slides[${idx}][title]" class="form-control">
                </div>
            </div>
        </div>`;
    el.querySelectorAll('[data-drop] input').forEach(inp => inp.addEventListener('change', () => previewInto(inp)));
    el.querySelector('.pb-remove').addEventListener('click', () => { el.remove(); refreshSlides(); });
    slidesBox.appendChild(el);
    refreshSlides();
}

function previewInto(input) {
    const zone = input.closest('[data-drop]');
    const img = zone.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; zone.classList.add('has-img'); };
        reader.readAsDataURL(input.files[0]);
    } else {
        zone.classList.remove('has-img');
    }
}

function refreshSlides() {
    const slides = [...slidesBox.children];
    slides.forEach((el, i) => {
        el.querySelector('.pb-slide-title').textContent = currentType === 'carousel' ? 'Slide ' + (i + 1) : 'Banner';
        el.querySelector('.pb-remove').style.display = (currentType === 'carousel' && slides.length > 1) ? 'inline-block' : 'none';
    });
}

function setType(type) {
    currentType = type;
    document.querySelectorAll('.pb-type').forEach(c => c.classList.toggle('active', c.dataset.type === type));
    addBtn.style.display = type === 'carousel' ? 'inline-block' : 'none';
    document.getElementById('pbCarouselOpts').style.display = type === 'carousel' ? 'flex' : 'none';
    document.getElementById('pbSlideHint').textContent = type === 'carousel' ? 'Add as many slides as you need' : 'Upload both desktop and mobile versions';
    if (type === 'single') {
        while (slidesBox.children.length > 1) slidesBox.lastElementChild.remove();
    } else if (slidesBox.children.length < 2) {
        addSlide();
    }
    refreshSlides();
}

pageSel.addEventListener('change', rebuildSections);
sectionSel.addEventListener('change', onSectionChange);
document.querySelectorAll('.pb-type input').forEach(r => r.addEventListener('change', () => setType(r.value)));
addBtn.addEventListener('click', addSlide);

document.getElementById('pageBannerForm').addEventListener('submit', e => {
    if (!currentPage()) { e.preventDefault(); alert('Please choose a page.'); return; }
    if (currentPage() === '__custom' && !document.getElementById('pbCustomPage').value.trim()) {
        e.preventDefault(); alert('Please type the page name.'); return;
    }
    if (sectionSel.value === '0' && !document.getElementById('pbNewSection').value.trim()) {
        e.preventDefault(); alert('Please enter a name for the new section.'); return;
    }
    const hasImage = [...document.querySelectorAll('.pb-slide [data-drop] input')].some(i => i.files && i.files.length);
    if (!hasImage) { e.preventDefault(); alert('Please upload at least one image.'); }
});

addSlide();
rebuildSections();
</script>
</body>
</html>
