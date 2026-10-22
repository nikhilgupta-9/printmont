<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$db = new Database();
$conn = $db->getConnection();

// Handle Form Submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'update_settings') {
        $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
        $subtitle = $conn->real_escape_string(trim($_POST['subtitle'] ?? ''));
        $enabled = isset($_POST['enabled']) ? '1' : '0';

        $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('title', '$title', 'text', 'active') ON DUPLICATE KEY UPDATE setting_value = '$title'");
        $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('subtitle', '$subtitle', 'text', 'active') ON DUPLICATE KEY UPDATE setting_value = '$subtitle'");
        $conn->query("INSERT INTO gift_finder_settings (setting_key, setting_value, setting_type, status) VALUES ('enabled', '$enabled', 'boolean', 'active') ON DUPLICATE KEY UPDATE setting_value = '$enabled'");

        // Handle Occasions
        $occ_labels = $_POST['occ_label'] ?? [];
        $occ_queries = $_POST['occ_query'] ?? [];
        $occasions = [];
        for ($i = 0; $i < count($occ_labels); $i++) {
            $lbl = trim($occ_labels[$i]);
            if (!empty($lbl)) {
                $occasions[] = [
                    'id' => strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $lbl)),
                    'label' => $lbl,
                    'query' => trim($occ_queries[$i] ?? $lbl)
                ];
            }
        }
        $occ_json = $conn->real_escape_string(json_encode($occasions));
        $chk_occ = $conn->query("SELECT id FROM gift_finder_options WHERE question = 'Occasion'")->fetch_assoc();
        if ($chk_occ) {
            $conn->query("UPDATE gift_finder_options SET options = '$occ_json' WHERE id = " . (int)$chk_occ['id']);
        } else {
            $conn->query("INSERT INTO gift_finder_options (question, option_type, options, display_order, status) VALUES ('Occasion', 'single', '$occ_json', 1, 'active')");
        }

        // Handle Gift Types
        $gt_labels = $_POST['gt_label'] ?? [];
        $gt_cats = $_POST['gt_category_id'] ?? [];
        $gt_queries = $_POST['gt_query'] ?? [];
        $gift_types = [];
        for ($i = 0; $i < count($gt_labels); $i++) {
            $lbl = trim($gt_labels[$i]);
            if (!empty($lbl)) {
                $gift_types[] = [
                    'id' => strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $lbl)),
                    'label' => $lbl,
                    'category_id' => trim($gt_cats[$i] ?? ''),
                    'query' => trim($gt_queries[$i] ?? $lbl)
                ];
            }
        }
        $gt_json = $conn->real_escape_string(json_encode($gift_types));
        $chk_gt = $conn->query("SELECT id FROM gift_finder_options WHERE question = 'Gift Type'")->fetch_assoc();
        if ($chk_gt) {
            $conn->query("UPDATE gift_finder_options SET options = '$gt_json' WHERE id = " . (int)$chk_gt['id']);
        } else {
            $conn->query("INSERT INTO gift_finder_options (question, option_type, options, display_order, status) VALUES ('Gift Type', 'single', '$gt_json', 2, 'active')");
        }

        $message = 'Gift Finder configuration updated successfully!';
        $message_type = 'success';
    }
}

// Fetch Current Settings
$settings_res = $conn->query("SELECT setting_key, setting_value FROM gift_finder_settings WHERE status = 'active'");
$settings = [];
while ($r = $settings_res->fetch_assoc()) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$title = $settings['title'] ?? 'Find Gift for your Business and Loved Ones';
$subtitle = $settings['subtitle'] ?? 'Curated custom gifts and corporate merchandise delivered across India';
$enabled = ($settings['enabled'] ?? '1') === '1';

// Fetch Options
$options_res = $conn->query("SELECT id, question, options FROM gift_finder_options WHERE status = 'active' ORDER BY display_order ASC");
$occasions = [];
$gift_types = [];
while ($r = $options_res->fetch_assoc()) {
    $opts = json_decode($r['options'], true) ?: [];
    if (strtolower(trim($r['question'])) === 'occasion') {
        $occasions = $opts;
    } elseif (strtolower(trim($r['question'])) === 'gift type' || strtolower(trim($r['question'])) === 'gift types') {
        $gift_types = $opts;
    }
}

// Fetch all active categories for dropdown
$categories_res = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$all_categories = [];
while ($cat = $categories_res->fetch_assoc()) {
    $all_categories[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gift Finder Management | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; opacity: 0; }
        .card-executive { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        .finder-preview { background: #ffffff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 1.5rem; }
        .option-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content p-3 p-md-4">
                <div class="container-fluid p-0">
                    
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">🎁 Gift Finder Manager</h3>
                            <p class="text-muted mb-0">Control the "Find Gift for your Business and Loved Ones" section on the website homepage.</p>
                        </div>
                        <div>
                            <a href="http://localhost:5173/" target="_blank" class="btn btn-outline-primary">
                                <i data-feather="external-link" class="me-1"></i> Preview On Storefront
                            </a>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show p-2" role="alert">
                            <i data-feather="check-circle" class="me-1"></i> <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="gift-finder.php">
                        <input type="hidden" name="action" value="update_settings">

                        <div class="row g-4">
                            <!-- Main Configuration Settings -->
                            <div class="col-12 col-xl-7">
                                <!-- General Section Settings -->
                                <div class="card card-executive mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="card-title fw-bold mb-0">1. Section Banner & Headings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="enabled">Enable Gift Finder on Homepage</label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Heading / Title</label>
                                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                            <small class="text-muted">Displayed prominently above the gift finder search bar.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Subtitle / Caption</label>
                                            <input type="text" class="form-control" name="subtitle" value="<?php echo htmlspecialchars($subtitle); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Occasions List -->
                                <div class="card card-executive mb-4">
                                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h5 class="card-title fw-bold mb-0">2. Occasions Dropdown Options</h5>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addOccasionRow()">
                                            <i data-feather="plus"></i> Add Occasion
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Customers select an occasion to quickly filter relevant gifts (e.g. Corporate, Birthday, Festive).</p>
                                        <div id="occasions_container">
                                            <?php foreach ($occasions as $idx => $occ): ?>
                                                <div class="option-row d-flex align-items-center gap-2">
                                                    <div class="flex-grow-1">
                                                        <label class="form-label small fw-semibold mb-1">Occasion Name</label>
                                                        <input type="text" class="form-control form-control-sm" name="occ_label[]" value="<?php echo htmlspecialchars($occ['label'] ?? ''); ?>" placeholder="e.g. Birthday Celebration" required>
                                                    </div>
                                                    <div style="width: 140px;">
                                                        <label class="form-label small fw-semibold mb-1">Search Tag</label>
                                                        <input type="text" class="form-control form-control-sm" name="occ_query[]" value="<?php echo htmlspecialchars($occ['query'] ?? ''); ?>" placeholder="Query keyword">
                                                    </div>
                                                    <div class="pt-3">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.option-row').remove()">
                                                            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gift Types / Categories -->
                                <div class="card card-executive mb-4">
                                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h5 class="card-title fw-bold mb-0">3. Gift Types / Categories Options</h5>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addGiftTypeRow()">
                                            <i data-feather="plus"></i> Add Gift Type
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Choose gift categories (e.g. Apparel, Tech, Drinkware, Hampers) linked to catalog categories.</p>
                                        <div id="gift_types_container">
                                            <?php foreach ($gift_types as $idx => $gt): ?>
                                                <div class="option-row d-flex flex-wrap align-items-center gap-2">
                                                    <div class="flex-grow-1" style="min-width: 140px;">
                                                        <label class="form-label small fw-semibold mb-1">Gift Type Name</label>
                                                        <input type="text" class="form-control form-control-sm" name="gt_label[]" value="<?php echo htmlspecialchars($gt['label'] ?? ''); ?>" placeholder="e.g. Custom Drinkware" required>
                                                    </div>
                                                    <div style="width: 160px;">
                                                        <label class="form-label small fw-semibold mb-1">Link Category</label>
                                                        <select class="form-select form-select-sm" name="gt_category_id[]">
                                                            <option value="">-- No Category --</option>
                                                            <?php foreach ($all_categories as $c): ?>
                                                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($gt['category_id']) && $gt['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($c['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div style="width: 120px;">
                                                        <label class="form-label small fw-semibold mb-1">Search Query</label>
                                                        <input type="text" class="form-control form-control-sm" name="gt_query[]" value="<?php echo htmlspecialchars($gt['query'] ?? ''); ?>" placeholder="Keyword">
                                                    </div>
                                                    <div class="pt-3">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.option-row').remove()">
                                                            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid mb-5">
                                    <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                        <i data-feather="save" class="me-1"></i> Save Gift Finder Configuration
                                    </button>
                                </div>
                            </div>

                            <!-- Live Preview & Quick Information -->
                            <div class="col-12 col-xl-5">
                                <div class="card card-executive sticky-top" style="top: 20px;">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="card-title fw-bold mb-0">👁️ Live Storefront Visual Preview</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="finder-preview">
                                            <p class="fw-bold text-center text-primary mb-2" style="font-size: 1.05rem;" id="preview_title">
                                                <?php echo htmlspecialchars($title); ?>
                                            </p>
                                            <p class="text-center text-muted small mb-3" id="preview_subtitle">
                                                <?php echo htmlspecialchars($subtitle); ?>
                                            </p>

                                            <div class="bg-light p-3 rounded border">
                                                <div class="mb-2">
                                                    <label class="form-label small fw-semibold">Delivery Pincode</label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="Enter Pincode (e.g. 110001)" disabled>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small fw-semibold">Select Occasion</label>
                                                    <select class="form-select form-select-sm" disabled>
                                                        <?php foreach ($occasions as $occ): ?>
                                                            <option><?php echo htmlspecialchars($occ['label'] ?? ''); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Select Gift Type</label>
                                                    <select class="form-select form-select-sm" disabled>
                                                        <?php foreach ($gift_types as $gt): ?>
                                                            <option><?php echo htmlspecialchars($gt['label'] ?? ''); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button class="btn btn-primary btn-sm w-100 fw-bold" disabled>
                                                    FIND GIFTS
                                                </button>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85rem;">
                                            <strong>ℹ️ How it works:</strong> When a user enters their pincode and selects their preferred Occasion and Gift Type on the homepage, clicking "FIND GIFTS" redirects them directly to the product catalog filtered by the selected criteria, and preserves their delivery pincode!
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
        function addOccasionRow() {
            var container = document.getElementById('occasions_container');
            var div = document.createElement('div');
            div.className = 'option-row d-flex align-items-center gap-2';
            div.innerHTML = `
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">Occasion Name</label>
                    <input type="text" class="form-control form-control-sm" name="occ_label[]" placeholder="e.g. Wedding Gifts" required>
                </div>
                <div style="width: 140px;">
                    <label class="form-label small fw-semibold mb-1">Search Tag</label>
                    <input type="text" class="form-control form-control-sm" name="occ_query[]" placeholder="Query keyword">
                </div>
                <div class="pt-3">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.option-row').remove()">
                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            if (typeof feather !== 'undefined') feather.replace();
        }

        function addGiftTypeRow() {
            var container = document.getElementById('gift_types_container');
            var div = document.createElement('div');
            div.className = 'option-row d-flex flex-wrap align-items-center gap-2';
            div.innerHTML = `
                <div class="flex-grow-1" style="min-width: 140px;">
                    <label class="form-label small fw-semibold mb-1">Gift Type Name</label>
                    <input type="text" class="form-control form-control-sm" name="gt_label[]" placeholder="e.g. Awards & Trophies" required>
                </div>
                <div style="width: 160px;">
                    <label class="form-label small fw-semibold mb-1">Link Category</label>
                    <select class="form-select form-select-sm" name="gt_category_id[]">
                        <option value="">-- No Category --</option>
                        <?php foreach ($all_categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="width: 120px;">
                    <label class="form-label small fw-semibold mb-1">Search Query</label>
                    <input type="text" class="form-control form-control-sm" name="gt_query[]" placeholder="Keyword">
                </div>
                <div class="pt-3">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.option-row').remove()">
                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            if (typeof feather !== 'undefined') feather.replace();
        }
    </script>
</body>
</html>
