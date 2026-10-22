<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/customization-options-schema.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

ensureCustomizationOptionsTable($db);
$inputTypes  = customizationInputTypes();
$choiceTypes = customizationChoiceTypes();

function coRedirect() {
    header('Location: customization-options.php');
    exit();
}

// Products using an option (customization_fields holds [{"option_id":N,...}])
function coUsageCount($db, $id) {
    $needle = json_encode(['option_id' => (int) $id]);
    $stmt = $db->prepare("SELECT COUNT(*) AS n FROM products WHERE JSON_VALID(customization_fields) AND JSON_CONTAINS(customization_fields, ?)");
    $stmt->bind_param("s", $needle);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['n'] ?? 0);
}

// -------------------------------------------------------------
// ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $status = (($_POST['status'] ?? '') === 'active') ? 'active' : 'inactive';
        $stmt = $db->prepare("UPDATE customization_options SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM customization_options WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Option deleted.' : 'Could not delete option.';
        coRedirect();
    }

    if ($action === 'import_starter') {
        $existing = [];
        $res = $db->query("SELECT LOWER(name) AS n FROM customization_options");
        while ($res && ($r = $res->fetch_assoc())) $existing[$r['n']] = true;

        $added = 0;
        $order = (int) (($db->query("SELECT COALESCE(MAX(display_order),0) AS m FROM customization_options")->fetch_assoc()['m']) ?? 0);
        $stmt = $db->prepare("INSERT INTO customization_options (name, input_type, option_values, help, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach (customizationStarterSet() as [$name, $type, $req, $values, $help]) {
            if (isset($existing[mb_strtolower($name)])) continue;
            $order++;
            $json = json_encode(sanitizeCustomizationValues($values));
            $stmt->bind_param("ssssii", $name, $type, $json, $help, $req, $order);
            if ($stmt->execute()) $added++;
        }
        $_SESSION['success_message'] = $added ? "$added starter options added." : 'All starter options already exist.';
        coRedirect();
    }

    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $name     = mb_substr(trim($_POST['name'] ?? ''), 0, 150);
        $type     = $_POST['input_type'] ?? 'select';
        $help     = mb_substr(trim($_POST['help'] ?? ''), 0, 255);
        $required = !empty($_POST['is_required']) ? 1 : 0;
        $order    = (int) ($_POST['display_order'] ?? 0);
        $status   = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';
        if (!isset($inputTypes[$type])) $type = 'select';

        $values = in_array($type, $choiceTypes, true)
            ? sanitizeCustomizationValues(json_decode($_POST['option_values'] ?? '[]', true) ?: [])
            : [];

        if ($name === '') {
            $_SESSION['error_message'] = 'Option name is required.';
            coRedirect();
        }
        if (in_array($type, $choiceTypes, true) && empty($values)) {
            $_SESSION['error_message'] = 'Add at least one value for a ' . $inputTypes[$type] . '.';
            coRedirect();
        }

        $json = json_encode($values);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE customization_options SET name = ?, input_type = ?, option_values = ?, help = ?, is_required = ?, display_order = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssiisi", $name, $type, $json, $help, $required, $order, $status, $id);
        } else {
            $stmt = $db->prepare("INSERT INTO customization_options (name, input_type, option_values, help, is_required, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiis", $name, $type, $json, $help, $required, $order, $status);
        }
        $ok = $stmt->execute();
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? ($id > 0 ? 'Option updated.' : 'Option added.') : 'Could not save option.';
        coRedirect();
    }
}

// -------------------------------------------------------------
// DATA
// -------------------------------------------------------------
$options = [];
$res = $db->query("SELECT * FROM customization_options ORDER BY display_order ASC, name ASC");
while ($res && ($row = $res->fetch_assoc())) {
    $row['values'] = json_decode($row['option_values'] ?? '[]', true) ?: [];
    $row['usage']  = coUsageCount($db, $row['id']);
    $options[] = $row;
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
    <title>Customization Options | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .co-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; margin: 0 4px 4px 0; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 12px; background: #fff; }
        .co-dot { width: 12px; height: 12px; border-radius: 50%; border: 1px solid #ccc; display: inline-block; }
        .co-price { color: #198754; font-weight: 600; }
        .action-icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 12px; }
        .action-icon-btn.btn-edit:hover { background: #e0f2fe; color: #0284c7; }
        .action-icon-btn.btn-del:hover { background: #fee2e2; color: #dc2626; }
        .value-row { display: grid; grid-template-columns: 1fr 48px 130px 36px; gap: 8px; align-items: center; margin-bottom: 8px; }
        .value-row:not(.has-color) { grid-template-columns: 1fr 130px 36px; }
        .value-row:not(.has-color) .v-hex { display: none; }
        .value-row .form-control-color { width: 48px; height: 34px; padding: 2px; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            <main class="content">
                <div class="container-fluid p-0">

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="mb-0"><strong>Customization</strong> Options</h3>
                            <small class="text-muted">Create each option once (name, input type, values). Then tick it on any product in Add / Edit Product → section 7.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="post" onsubmit="return confirm('Add the common starter options (T-shirt, mug, pen, bag, frame, trophy)? Existing names are skipped.');">
                                <input type="hidden" name="action" value="import_starter">
                                <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-wand-magic-sparkles me-1"></i> Import starter set</button>
                            </form>
                            <button type="button" class="btn btn-primary" onclick="openOptionModal()"><i class="fas fa-plus me-1"></i> Add Option</button>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:60px">Order</th>
                                            <th>Option name</th>
                                            <th>Input type</th>
                                            <th>Values</th>
                                            <th style="width:110px">Used in</th>
                                            <th style="width:90px">Status</th>
                                            <th style="width:90px" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($options)): ?>
                                            <tr><td colspan="7" class="text-center text-muted py-4">No options yet. Click "Import starter set" or "Add Option".</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($options as $o): ?>
                                            <tr>
                                                <td><?php echo (int) $o['display_order']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($o['name']); ?></strong>
                                                    <?php if ($o['is_required']): ?><span class="badge bg-danger-subtle text-danger ms-1">Required</span><?php endif; ?>
                                                    <?php if ($o['help']): ?><div class="text-muted small"><?php echo htmlspecialchars($o['help']); ?></div><?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($inputTypes[$o['input_type']] ?? $o['input_type']); ?></td>
                                                <td style="max-width:420px">
                                                    <?php if (empty($o['values'])): ?>
                                                        <span class="text-muted small">Customer enters it</span>
                                                    <?php endif; ?>
                                                    <?php foreach ($o['values'] as $val): ?>
                                                        <span class="co-chip">
                                                            <?php if ($o['input_type'] === 'color' && $val['hex']): ?><span class="co-dot" style="background:<?php echo htmlspecialchars($val['hex']); ?>"></span><?php endif; ?>
                                                            <?php echo htmlspecialchars($val['label']); ?>
                                                            <?php if ($val['price'] > 0): ?><span class="co-price">+₹<?php echo rtrim(rtrim(number_format($val['price'], 2), '0'), '.'); ?></span><?php endif; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td><?php echo $o['usage']; ?> product<?php echo $o['usage'] === 1 ? '' : 's'; ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $o['status'] === 'active' ? 'checked' : ''; ?> onchange="toggleOptionStatus(<?php echo (int) $o['id']; ?>, this)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="action-icon-btn btn-edit" title="Edit"
                                                        data-option="<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES); ?>"
                                                        onclick="openOptionModal(JSON.parse(this.dataset.option))"><i class="fas fa-pen"></i></button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo $o['usage'] ? 'This option is used in ' . $o['usage'] . ' product(s); it will disappear from them. ' : ''; ?>Delete this option?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int) $o['id']; ?>">
                                                        <button type="submit" class="action-icon-btn btn-del" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="optionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="post" class="modal-content" id="optionForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="o_id">
                <input type="hidden" name="option_values" id="o_values_json" value="[]">
                <div class="modal-header">
                    <h5 class="modal-title" id="optionModalTitle">Add Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Option name (shown to customer) <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="o_name" class="form-control" maxlength="150" required placeholder="e.g. Fabric Colour">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Input type</label>
                            <select name="input_type" id="o_type" class="form-select" onchange="onTypeChange()">
                                <?php foreach ($inputTypes as $k => $l): ?>
                                    <option value="<?php echo $k; ?>"><?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Help text <small class="text-muted">(optional, shown under the field)</small></label>
                            <input type="text" name="help" id="o_help" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Display order</label>
                            <input type="number" name="display_order" id="o_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" id="o_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_required" id="o_required" value="1">
                                <label class="form-check-label" for="o_required">Required by default</label>
                            </div>
                        </div>

                        <div class="col-12" id="valuesBox">
                            <label class="form-label fw-semibold mb-1">Values the customer can choose</label>
                            <div class="text-muted small mb-2">+₹ is the extra price per piece (can be changed per product). Leave empty for no extra charge.</div>
                            <div id="valuesList"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addValueRow()">+ Add value</button>
                        </div>
                        <div class="col-12 text-muted small" id="typeNote"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Option</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const CHOICE_TYPES = <?php echo json_encode($choiceTypes); ?>;
        const TYPE_NOTES = {
            text: 'Customer types a short text (e.g. name to print).',
            textarea: 'Customer types a longer message (e.g. engraving text).',
            number: 'Customer enters a number (e.g. jersey number).',
            image: 'Customer uploads an image or PDF (e.g. logo, photo).',
            both: 'Customer can type text and/or upload an image.'
        };

        function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

        function addValueRow(v) {
            v = v || {};
            const isColor = document.getElementById('o_type').value === 'color';
            const hex = /^#[0-9a-f]{6}$/i.test(v.hex || '') ? v.hex : '#000000';
            document.getElementById('valuesList').insertAdjacentHTML('beforeend', `
                <div class="value-row ${isColor ? 'has-color' : ''}">
                    <input type="text" class="form-control v-label" placeholder="Value (e.g. White)" value="${esc(v.label || '')}">
                    <input type="color" class="form-control form-control-color v-hex" value="${hex}" title="Colour">
                    <div class="input-group"><span class="input-group-text">+₹</span><input type="number" step="0.01" min="0" class="form-control v-price" value="${Number(v.price) > 0 ? esc(v.price) : ''}" placeholder="0"></div>
                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.value-row').remove()">×</button>
                </div>`);
        }

        function onTypeChange() {
            const type = document.getElementById('o_type').value;
            const isChoice = CHOICE_TYPES.includes(type);
            document.getElementById('valuesBox').style.display = isChoice ? '' : 'none';
            document.querySelectorAll('#valuesList .value-row').forEach(r => r.classList.toggle('has-color', type === 'color'));
            if (isChoice && !document.querySelector('#valuesList .value-row')) { addValueRow(); addValueRow(); }
            document.getElementById('typeNote').textContent = TYPE_NOTES[type] || '';
        }

        function openOptionModal(o) {
            o = o || {};
            document.getElementById('optionModalTitle').textContent = o.id ? 'Edit Option' : 'Add Option';
            document.getElementById('o_id').value = o.id || '';
            document.getElementById('o_name').value = o.name || '';
            document.getElementById('o_type').value = o.input_type || 'select';
            document.getElementById('o_help').value = o.help || '';
            document.getElementById('o_order').value = o.display_order || 0;
            document.getElementById('o_status').value = o.status || 'active';
            document.getElementById('o_required').checked = Number(o.is_required) === 1;
            document.getElementById('valuesList').innerHTML = '';
            (o.values || []).forEach(addValueRow);
            onTypeChange();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('optionModal')).show();
        }

        document.getElementById('optionForm').addEventListener('submit', function () {
            const type = document.getElementById('o_type').value;
            const rows = [...document.querySelectorAll('#valuesList .value-row')].map(r => ({
                label: r.querySelector('.v-label').value.trim(),
                hex: type === 'color' ? r.querySelector('.v-hex').value : '',
                price: parseFloat(r.querySelector('.v-price').value) || 0
            })).filter(v => v.label);
            document.getElementById('o_values_json').value = JSON.stringify(rows);
        });

        function toggleOptionStatus(id, el) {
            fetch('customization-options.php', { method: 'POST', body: new URLSearchParams({ action: 'toggle_status', id: id, status: el.checked ? 'active' : 'inactive' }) })
                .then(r => r.json())
                .then(res => { if (!res.success) { el.checked = !el.checked; alert('Could not update status'); } })
                .catch(() => { el.checked = !el.checked; alert('Could not update status'); });
        }

        document.addEventListener('DOMContentLoaded', function () { document.body.style.opacity = 1; });
    </script>
</body>
</html>
