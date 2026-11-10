<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/customizer-schema.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

ensureCustomizerTables($db);

function cmRedirect() {
    header('Location: customizer-manager.php');
    exit();
}

// -------------------------------------------------------------
// POST ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJAX Toggle Template Status
    if ($action === 'toggle_template_status') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $status = (($_POST['status'] ?? '') === 'active') ? 'active' : 'inactive';
        $stmt = $db->prepare("UPDATE customizer_templates SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    // Save Template (Add or Edit)
    if ($action === 'save_template') {
        $id          = (int) ($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $keyword     = mb_strtolower(trim($_POST['category_keyword'] ?? 'general'));
        $description = trim($_POST['description'] ?? '');
        $dpiGuide    = trim($_POST['dpi_guide'] ?? 'Recommended 300 DPI transparent PNG');
        $isDefault   = !empty($_POST['is_default']) ? 1 : 0;
        $order       = (int) ($_POST['display_order'] ?? 0);
        $status      = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        // Tools
        $tools = isset($_POST['tools']) && is_array($_POST['tools']) ? implode(',', array_map('trim', $_POST['tools'])) : 'text,image,clipart,shapes,rotate,arc';

        // Views JSON
        $views = [];
        $viewIds = $_POST['view_id'] ?? [];
        $viewNames = $_POST['view_name'] ?? [];
        $viewMockups = $_POST['view_mockup'] ?? [];
        $viewPx = $_POST['view_px'] ?? [];
        $viewPy = $_POST['view_py'] ?? [];
        $viewPw = $_POST['view_pw'] ?? [];
        $viewPh = $_POST['view_ph'] ?? [];
        $viewTint = $_POST['view_tint'] ?? [];

        foreach ($viewIds as $idx => $vId) {
            $vId = trim($vId);
            if ($vId === '') continue;
            $views[] = [
                'id'         => $vId,
                'name'       => trim($viewNames[$idx] ?? ucfirst($vId)),
                'mockup_url' => trim($viewMockups[$idx] ?? '/mockups/general_gift.svg'),
                'print_area' => [
                    'x'      => (float) ($viewPx[$idx] ?? 25),
                    'y'      => (float) ($viewPy[$idx] ?? 20),
                    'width'  => (float) ($viewPw[$idx] ?? 50),
                    'height' => (float) ($viewPh[$idx] ?? 50),
                    'shape'  => 'rect'
                ],
                'tintable'   => !empty($viewTint[$idx])
            ];
        }

        if (empty($views)) {
            $views[] = [
                'id'         => 'front',
                'name'       => 'Front View',
                'mockup_url' => '/mockups/general_gift.svg',
                'print_area' => ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60, 'shape' => 'rect'],
                'tintable'   => false
            ];
        }

        $viewsConfig = json_encode($views);

        if ($title === '') {
            $_SESSION['error_message'] = 'Template title is required.';
            cmRedirect();
        }

        if ($isDefault) {
            $db->query("UPDATE customizer_templates SET is_default = 0");
        }

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE customizer_templates SET title = ?, category_keyword = ?, description = ?, views_config = ?, allowed_tools = ?, dpi_guide = ?, is_default = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssiisi", $title, $keyword, $description, $viewsConfig, $tools, $dpiGuide, $isDefault, $order, $status, $id);
            $stmt->execute();
            $_SESSION['success_message'] = 'Template updated successfully.';
        } else {
            $stmt = $db->prepare("INSERT INTO customizer_templates (title, category_keyword, description, views_config, allowed_tools, dpi_guide, is_default, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiis", $title, $keyword, $description, $viewsConfig, $tools, $dpiGuide, $isDefault, $order, $status);
            $stmt->execute();
            $_SESSION['success_message'] = 'New template added successfully.';
        }
        cmRedirect();
    }

    // Delete Template
    if ($action === 'delete_template') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM customizer_templates WHERE id = ? AND is_default = 0");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['success_message'] = 'Template removed.';
        cmRedirect();
    }

    // Add Clipart
    if ($action === 'save_clipart') {
        $cat  = trim($_POST['category'] ?? 'Corporate');
        $name = trim($_POST['name'] ?? '');
        $svg  = trim($_POST['svg_content'] ?? '');

        if ($name === '' || $svg === '') {
            $_SESSION['error_message'] = 'Clipart name and SVG code are required.';
            cmRedirect();
        }

        $stmt = $db->prepare("INSERT INTO customizer_cliparts (category, name, svg_content) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $cat, $name, $svg);
        $stmt->execute();
        $_SESSION['success_message'] = 'Clipart icon added.';
        cmRedirect();
    }

    // Delete Clipart
    if ($action === 'delete_clipart') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM customizer_cliparts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['success_message'] = 'Clipart removed.';
        cmRedirect();
    }

    // Re-seed Starter Templates
    if ($action === 'reseed_defaults') {
        seedStarterCustomizerTemplates($db);
        seedStarterCustomizerCliparts($db);
        $_SESSION['success_message'] = 'Starter templates and cliparts re-seeded.';
        cmRedirect();
    }
}

// Fetch all templates
$templatesRes = $db->query("SELECT * FROM customizer_templates ORDER BY is_default DESC, display_order ASC, id ASC");
$templates = [];
while ($templatesRes && ($row = $templatesRes->fetch_assoc())) {
    $row['views'] = json_decode($row['views_config'] ?? '[]', true) ?: [];
    $templates[] = $row;
}

// Fetch cliparts
$clipartsRes = $db->query("SELECT * FROM customizer_cliparts ORDER BY category ASC, display_order ASC, id ASC");
$cliparts = [];
while ($clipartsRes && ($row = $clipartsRes->fetch_assoc())) {
    $cliparts[] = $row;
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Master Design Customizer | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .cm-card-mockup { width: 54px; height: 54px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden; padding: 4px; }
        .cm-card-mockup img, .cm-card-mockup svg { max-width: 100%; max-height: 100%; object-fit: contain; }
        .cm-badge-view { display: inline-block; font-size: 11px; padding: 2px 7px; border-radius: 4px; background: #e0f2fe; color: #0369a1; font-weight: 500; margin-right: 4px; margin-bottom: 3px; }
        .clipart-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; background: #fff; transition: all .2s; }
        .clipart-box:hover { border-color: #0b53a1; box-shadow: 0 4px 12px rgba(11,83,161,0.08); }
        .clipart-icon-render { width: 48px; height: 48px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; color: #0b53a1; }
        .clipart-icon-render svg { width: 100%; height: 100%; fill: currentColor; }
        .view-editor-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .print-box-preview-wrap { width: 140px; height: 140px; border: 1px solid #cbd5e1; border-radius: 6px; position: relative; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .print-box-preview-wrap img { max-width: 100%; max-height: 100%; }
        .print-box-rect { position: absolute; border: 2px dashed #0284c7; background: rgba(2, 132, 199, 0.15); pointer-events: none; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">

                    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                        <div>
                            <h3 class="mb-1"><strong>Master Design Customizer</strong></h3>
                            <small class="text-muted">Manage the customer design studio: product canvases, print safe areas, available tools, templates, and clipart library.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="post" onsubmit="return confirm('Restore/Refresh starter templates (T-Shirts, Mugs, Bottles, Pens, Bags, Laptops, Mobile Covers, Diaries)? Existing templates are preserved.');">
                                <input type="hidden" name="action" value="reseed_defaults">
                                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-rotate me-1"></i> Sync Starter Templates</button>
                            </form>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openTemplateModal()"><i class="fas fa-plus me-1"></i> Add Customizer Template</button>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- TABS -->
                    <ul class="nav nav-pills mb-3" id="customizerTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="templates-tab" data-bs-toggle="pill" data-bs-target="#templates-pane" type="button" role="tab">
                                <i class="fas fa-layer-group me-1"></i> Product Canvas Templates (<?php echo count($templates); ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="cliparts-tab" data-bs-toggle="pill" data-bs-target="#cliparts-pane" type="button" role="tab">
                                <i class="fas fa-icons me-1"></i> Corporate Clipart Library (<?php echo count($cliparts); ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="fonts-tab" data-bs-toggle="pill" data-bs-target="#fonts-pane" type="button" role="tab">
                                <i class="fas fa-font me-1"></i> Curated Fonts & Guidelines
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="customizerTabsContent">
                        <!-- TAB 1: TEMPLATES -->
                        <div class="tab-pane fade show active" id="templates-pane" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">Mockup</th>
                                                    <th>Template Name</th>
                                                    <th>Category Match Keyword</th>
                                                    <th>Configured Sides / Views</th>
                                                    <th>Print DPI Guide</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($templates as $t): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="cm-card-mockup">
                                                                <?php 
                                                                    $firstMockup = $t['views'][0]['mockup_url'] ?? '/mockups/general_gift.svg';
                                                                ?>
                                                                <img src="<?php echo htmlspecialchars($firstMockup); ?>" alt="Mockup" onerror="this.src='/mockups/general_gift.svg'">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark">
                                                                <?php echo htmlspecialchars($t['title']); ?>
                                                                <?php if (!empty($t['is_default'])): ?>
                                                                    <span class="badge bg-warning-subtle text-warning border border-warning ms-1" style="font-size: 10px;">Universal Default</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small class="text-muted d-block"><?php echo htmlspecialchars($t['description']); ?></small>
                                                        </td>
                                                        <td>
                                                            <code class="px-2 py-1 bg-light rounded text-primary fw-bold"><?php echo htmlspecialchars($t['category_keyword']); ?></code>
                                                        </td>
                                                        <td>
                                                            <?php foreach ($t['views'] as $v): ?>
                                                                <span class="cm-badge-view">
                                                                    <i class="fas fa-eye me-1 text-muted"></i><?php echo htmlspecialchars($v['name'] ?? $v['id']); ?>
                                                                    (<?php echo round($v['print_area']['width'] ?? 50); ?>x<?php echo round($v['print_area']['height'] ?? 50); ?>%)
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo htmlspecialchars($t['dpi_guide'] ?: '300 DPI recommended'); ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch m-0">
                                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                                    onchange="toggleTemplateStatus(<?php echo $t['id']; ?>, this.checked)"
                                                                    <?php echo $t['status'] === 'active' ? 'checked' : ''; ?>>
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='editTemplate(<?php echo json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </button>
                                                            <?php if (empty($t['is_default'])): ?>
                                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this customizer template?');">
                                                                    <input type="hidden" name="action" value="delete_template">
                                                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: CLIPARTS -->
                        <div class="tab-pane fade" id="cliparts-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-0">Corporate Gifting Clipart & Icon Library</h5>
                                    <small class="text-muted">Icons customers can drag and place directly onto their mugs, t-shirts, diaries, pens, or bottles.</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openClipartModal()">
                                    <i class="fas fa-plus me-1"></i> Add Clipart Icon
                                </button>
                            </div>

                            <div class="row g-3">
                                <?php foreach ($cliparts as $c): ?>
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                        <div class="clipart-box position-relative">
                                            <form method="post" class="position-absolute top-0 end-0 m-1" onsubmit="return confirm('Delete this clipart?');">
                                                <input type="hidden" name="action" value="delete_clipart">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete icon" style="font-size: 11px;"><i class="fas fa-times"></i></button>
                                            </form>
                                            <div class="clipart-icon-render">
                                                <?php if (!empty($c['svg_content'])): ?>
                                                    <?php echo $c['svg_content']; ?>
                                                <?php else: ?>
                                                    <i class="fas fa-star fa-2x"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fw-semibold text-truncate small"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <span class="badge bg-light text-muted border" style="font-size: 9px;"><?php echo htmlspecialchars($c['category']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- TAB 3: FONTS -->
                        <div class="tab-pane fade" id="fonts-pane" role="tabpanel">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">Curated Google Fonts for Customizer Studio</h5>
                                    <p class="text-muted small">These fonts are loaded automatically in the customer canvas editor. Optimized for printing, embroidery, laser engraving, and screen printing.</p>
                                    <div class="row g-3">
                                        <?php 
                                        $fontDemos = [
                                            ['Inter', 'The quick brown fox jumps over the lazy dog', 'Modern Corporate UI Sans'],
                                            ['Roboto', 'PREMIUM CORPORATE GIFTING 2026', 'Clean Neutral Sans-Serif'],
                                            ['Poppins', 'Design with Creativity & Impact', 'Geometric Modern Bold'],
                                            ['Montserrat', 'EXCELLENCE & DEDICATION AWARD', 'High-end Luxury Branding'],
                                            ['Playfair Display', 'Celebrating Milestones with Elegance', 'Classic Serif / Luxury Gifts'],
                                            ['Bebas Neue', 'PRINTMONT APPAREL & WORKWEAR', 'Condensed Impact Display'],
                                            ['Pacifico', 'Happy Holidays & Warm Wishes', 'Friendly Brush Script'],
                                            ['Oswald', 'ENGINEERED FOR PERFORMANCE', 'Condensed Technical Sans'],
                                            ['Dancing Script', 'Special Thanks for Your Commitment', 'Graceful Cursive Signature'],
                                            ['Courier Prime', 'ID: PM-2026-CORP-GIFT', 'Precision Monospace Engraving']
                                        ];
                                        foreach ($fontDemos as [$fName, $sample, $desc]):
                                        ?>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3 bg-light">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-primary"><?php echo $fName; ?></span>
                                                        <small class="text-muted"><?php echo $desc; ?></small>
                                                    </div>
                                                    <div style="font-family: '<?php echo $fName; ?>', sans-serif; font-size: 18px; color: #1e293b; line-height: 1.4;">
                                                        <?php echo $sample; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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

    <!-- TEMPLATE EDIT/ADD MODAL -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="save_template">
                    <input type="hidden" name="id" id="tpl_id" value="0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tplModalTitle"><i class="fas fa-layer-group text-primary me-2"></i> Customizer Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold">Template Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="tpl_title" class="form-control" placeholder="e.g. Custom Apparel & T-Shirts" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Category Match Keyword <span class="text-danger">*</span></label>
                                <input type="text" name="category_keyword" id="tpl_keyword" class="form-control" placeholder="e.g. t-shirt, mug, pen, bottle, bag, laptop, mobile, diary" required>
                                <small class="text-muted" style="font-size: 11px;">Matches if category or product name contains this keyword.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description / Product Scope</label>
                                <input type="text" name="description" id="tpl_desc" class="form-control" placeholder="Short description of products this applies to">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">DPI & Print Resolution Guideline</label>
                                <input type="text" name="dpi_guide" id="tpl_dpi" class="form-control" placeholder="e.g. 300 DPI transparent PNG recommended">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" id="tpl_order" class="form-control" value="0">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Allowed Customizer Tools</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="text" checked> Text Tool</label>
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="image" checked> Logo/Image Upload</label>
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="clipart" checked> Clipart Library</label>
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="shapes" checked> Shapes</label>
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="rotate" checked> Rotation</label>
                                    <label class="form-check"><input class="form-check-input tpl-tool" type="checkbox" name="tools[]" value="arc" checked> Curved/Arc Text</label>
                                </div>
                            </div>

                            <!-- DYNAMIC VIEWS / SIDES BUILDER -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold mb-0">Product Sides / Views & Printable Areas</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addViewRow()"><i class="fas fa-plus me-1"></i> Add Another View</button>
                                </div>
                                <div id="viewsContainer"></div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="tpl_default" value="1">
                                    <label class="form-check-label" for="tpl_default">Set as Universal Fallback Default (used when no specific category match is found)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Customizer Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CLIPART MODAL -->
    <div class="modal fade" id="clipartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="save_clipart">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-icons text-primary me-2"></i> Add Clipart Icon</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Icon Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Star Badge, Diya Lamp" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <option value="Corporate">Corporate & Badges</option>
                                <option value="Lifestyle">Lifestyle & Gifts</option>
                                <option value="Festive">Festive & Celebration</option>
                                <option value="Tech">Tech & Startups</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">SVG Vector Code <span class="text-danger">*</span></label>
                            <textarea name="svg_content" class="form-control font-monospace" rows="4" placeholder='<svg viewBox="0 0 100 100" fill="currentColor">...</svg>' required></textarea>
                            <small class="text-muted">Use <code>fill="currentColor"</code> or scalable path so color can adapt.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Add to Library</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        function toggleTemplateStatus(id, isChecked) {
            fetch('customizer-manager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_template_status&id=${id}&status=${isChecked ? 'active' : 'inactive'}`
            });
        }

        function openTemplateModal() {
            document.getElementById('tplModalTitle').innerHTML = '<i class="fas fa-plus text-primary me-2"></i> Add Customizer Template';
            document.getElementById('tpl_id').value = '0';
            document.getElementById('tpl_title').value = '';
            document.getElementById('tpl_keyword').value = '';
            document.getElementById('tpl_desc').value = '';
            document.getElementById('tpl_dpi').value = 'Recommended 300 DPI transparent PNG';
            document.getElementById('tpl_order').value = '0';
            document.getElementById('tpl_default').checked = false;
            document.getElementById('viewsContainer').innerHTML = '';
            addViewRow({ id: 'front', name: 'Front View', mockup_url: '/mockups/general_gift.svg', print_area: { x: 25, y: 25, width: 50, height: 50 }, tintable: false });
            new bootstrap.Modal(document.getElementById('templateModal')).show();
        }

        function editTemplate(t) {
            document.getElementById('tplModalTitle').innerHTML = '<i class="fas fa-edit text-primary me-2"></i> Edit Customizer Template';
            document.getElementById('tpl_id').value = t.id;
            document.getElementById('tpl_title').value = t.title;
            document.getElementById('tpl_keyword').value = t.category_keyword;
            document.getElementById('tpl_desc').value = t.description || '';
            document.getElementById('tpl_dpi').value = t.dpi_guide || '';
            document.getElementById('tpl_order').value = t.display_order || 0;
            document.getElementById('tpl_default').checked = !!Number(t.is_default);

            const activeTools = (t.allowed_tools || '').split(',').map(s => s.trim());
            document.querySelectorAll('.tpl-tool').forEach(cb => {
                cb.checked = activeTools.includes(cb.value);
            });

            document.getElementById('viewsContainer').innerHTML = '';
            (t.views || []).forEach(v => addViewRow(v));

            new bootstrap.Modal(document.getElementById('templateModal')).show();
        }

        function addViewRow(v = null) {
            const vId = v?.id || 'side_' + Date.now();
            const vName = v?.name || 'Front View';
            const vMockup = v?.mockup_url || '/mockups/general_gift.svg';
            const px = v?.print_area?.x ?? 25;
            const py = v?.print_area?.y ?? 20;
            const pw = v?.print_area?.width ?? 50;
            const ph = v?.print_area?.height ?? 50;
            const tint = v?.tintable ? 'checked' : '';

            const row = document.createElement('div');
            row.className = 'view-editor-row';
            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark small"><i class="fas fa-camera text-primary me-1"></i> Side / View</span>
                    <button type="button" class="btn btn-link text-danger p-0 small" onclick="this.closest('.view-editor-row').remove()"><i class="fas fa-trash me-1"></i> Remove View</button>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">View ID (Key)</label>
                        <input type="text" name="view_id[]" class="form-control form-control-sm" value="${vId}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">View Label (e.g. Front View)</label>
                        <input type="text" name="view_name[]" class="form-control form-control-sm" value="${vName}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small text-muted mb-1">Mockup Image / SVG URL</label>
                        <input type="text" name="view_mockup[]" class="form-control form-control-sm mockup-input" value="${vMockup}" onchange="updateRowPreview(this)" required>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="small text-muted mb-1">Safe Printable Box (% of mockup canvas)</label>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="small">X: <input type="number" name="view_px[]" class="form-control form-control-sm d-inline-block p-box-x" style="width: 60px;" value="${px}" onchange="updateRowPreview(this)">%</span>
                            <span class="small">Y: <input type="number" name="view_py[]" class="form-control form-control-sm d-inline-block p-box-y" style="width: 60px;" value="${py}" onchange="updateRowPreview(this)">%</span>
                            <span class="small">Width: <input type="number" name="view_pw[]" class="form-control form-control-sm d-inline-block p-box-w" style="width: 60px;" value="${pw}" onchange="updateRowPreview(this)">%</span>
                            <span class="small">Height: <input type="number" name="view_ph[]" class="form-control form-control-sm d-inline-block p-box-h" style="width: 60px;" value="${ph}" onchange="updateRowPreview(this)">%</span>
                            <label class="form-check ms-3 small mb-0"><input class="form-check-input" type="checkbox" name="view_tint[]" value="1" ${tint}> Allow Color Tinting</label>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('viewsContainer').appendChild(row);
        }

        function openClipartModal() {
            new bootstrap.Modal(document.getElementById('clipartModal')).show();
        }
    </script>
</body>
</html>
