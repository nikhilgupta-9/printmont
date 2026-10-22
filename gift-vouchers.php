<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/gift-vouchers-schema.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

ensureGiftVoucherTables($db);
expireGiftVouchers($db);

function gvRedirect($query = '') {
    header('Location: gift-vouchers.php' . ($query ? '?' . $query : ''));
    exit();
}

function gvImg($path) {
    $path = (string) $path;
    if ($path === '') return 'img/icons/icon-48x48.png';
    return (strpos($path, 'http') === 0) ? $path : ltrim($path, '/');
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
        $stmt = $db->prepare("UPDATE gift_voucher_rules SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM gift_voucher_rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Voucher rule deleted. Vouchers already given to customers stay valid.' : 'Could not delete the rule.';
        gvRedirect();
    }

    if ($action === 'cancel_voucher') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE user_gift_vouchers SET status = 'cancelled' WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION[$stmt->affected_rows ? 'success_message' : 'error_message'] = $stmt->affected_rows ? 'Voucher cancelled.' : 'Only an active voucher can be cancelled.';
        gvRedirect('tab=issued');
    }

    if ($action === 'save') {
        $id         = (int) ($_POST['id'] ?? 0);
        $title      = mb_substr(trim($_POST['title'] ?? ''), 0, 100);
        $minSpend   = round(max(0, (float) ($_POST['min_spend'] ?? 0)), 2);
        $type       = ($_POST['voucher_type'] ?? '') === 'percent' ? 'percent' : 'fixed';
        $value      = round((float) ($_POST['voucher_value'] ?? 0), 2);
        $maxRaw     = trim((string) ($_POST['max_discount'] ?? ''));
        $max        = ($type === 'percent' && $maxRaw !== '' && (float) $maxRaw > 0) ? round((float) $maxRaw, 2) : null;
        $minUse     = round(max(0, (float) ($_POST['min_order_to_use'] ?? 0)), 2);
        $days       = max(1, min(3650, (int) ($_POST['validity_days'] ?? 30)));
        $appliesTo  = in_array($_POST['applies_to'] ?? '', ['all', 'products', 'categories'], true) ? $_POST['applies_to'] : 'products';
        $productIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['product_ids'] ?? [])))));
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['category_ids'] ?? [])))));
        $status     = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        $error = '';
        if ($title === '') $error = 'Please give the voucher a title.';
        elseif ($minSpend <= 0) $error = 'Minimum purchase must be more than ₹0.';
        elseif ($type === 'fixed' && $value <= 0) $error = 'Voucher amount must be more than ₹0.';
        elseif ($type === 'percent' && ($value <= 0 || $value >= 100)) $error = 'Voucher % must be between 1 and 99.';
        elseif ($appliesTo === 'products' && !$productIds) $error = 'Choose at least one product, or set "Applies to" to all products.';
        elseif ($appliesTo === 'categories' && !$categoryIds) $error = 'Choose at least one category, or set "Applies to" to all products.';
        if ($error) {
            $_SESSION['error_message'] = $error;
            gvRedirect();
        }

        $pJson = json_encode($appliesTo === 'products' ? $productIds : []);
        $cJson = json_encode($appliesTo === 'categories' ? $categoryIds : []);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE gift_voucher_rules SET title=?, min_spend=?, voucher_type=?, voucher_value=?, max_discount=?, min_order_to_use=?, validity_days=?, applies_to=?, product_ids=?, category_ids=?, status=? WHERE id=?");
            $stmt->bind_param("sdsdddissssi", $title, $minSpend, $type, $value, $max, $minUse, $days, $appliesTo, $pJson, $cJson, $status, $id);
        } else {
            $stmt = $db->prepare("INSERT INTO gift_voucher_rules (title, min_spend, voucher_type, voucher_value, max_discount, min_order_to_use, validity_days, applies_to, product_ids, category_ids, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sdsdddissss", $title, $minSpend, $type, $value, $max, $minUse, $days, $appliesTo, $pJson, $cJson, $status);
        }
        $ok = $stmt->execute();
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? ($id > 0 ? 'Voucher rule updated.' : 'Voucher rule created.') : 'Could not save the rule.';
        gvRedirect();
    }
}

// -------------------------------------------------------------
// DATA
// -------------------------------------------------------------
$tab = ($_GET['tab'] ?? '') === 'issued' ? 'issued' : 'rules';

$rules = [];
$res = $db->query("SELECT r.*, (SELECT COUNT(*) FROM user_gift_vouchers v WHERE v.rule_id = r.id) AS issued_count,
                          (SELECT COUNT(*) FROM user_gift_vouchers v WHERE v.rule_id = r.id AND v.status = 'used') AS used_count
                   FROM gift_voucher_rules r ORDER BY r.status = 'active' DESC, r.min_spend ASC, r.id DESC");
while ($res && ($row = $res->fetch_assoc())) $rules[] = $row;

// Products for the picker
$pickerProducts = [];
$pr = $db->query("SELECT p.id, p.name, p.sku, p.status, p.price, p.offer_price, p.discount_price,
                         COALESCE((SELECT i.image_url FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.display_order ASC LIMIT 1), p.thumbnail_image) AS image
                  FROM products p ORDER BY p.name ASC");
while ($pr && ($row = $pr->fetch_assoc())) {
    $offer = (float) $row['offer_price']; $disc = (float) $row['discount_price'];
    $pickerProducts[] = [
        'id' => (int) $row['id'], 'name' => $row['name'], 'sku' => $row['sku'] ?? '', 'status' => $row['status'],
        'price' => $offer > 0 ? $offer : ($disc > 0 ? $disc : (float) $row['price']), 'image' => gvImg($row['image']),
    ];
}

// Categories with their full path ("Parent › Child")
$catRows = [];
$cr = $db->query("SELECT id, name, parent_id FROM categories WHERE status = 'active' ORDER BY display_order, name");
while ($cr && ($row = $cr->fetch_assoc())) $catRows[(int) $row['id']] = $row;
$categories = [];
foreach ($catRows as $cid => $c) {
    $path = [$c['name']];
    $p = (int) $c['parent_id'];
    $guard = 0;
    while ($p && isset($catRows[$p]) && $guard++ < 6) { array_unshift($path, $catRows[$p]['name']); $p = (int) $catRows[$p]['parent_id']; }
    $categories[] = ['id' => $cid, 'name' => implode(' › ', $path)];
}
usort($categories, fn($a, $b) => strcmp($a['name'], $b['name']));
$catName = array_column($categories, 'name', 'id');

// Issued vouchers
$vStatus = $_GET['vstatus'] ?? '';
$vSearch = trim($_GET['q'] ?? '');
$where = '1=1';
$types = '';
$params = [];
if (in_array($vStatus, ['active', 'used', 'expired', 'cancelled'], true)) { $where .= ' AND v.status = ?'; $types .= 's'; $params[] = $vStatus; }
if ($vSearch !== '') {
    $where .= ' AND (v.code LIKE ? OR u.email LIKE ? OR o.order_number LIKE ?)';
    $like = '%' . $vSearch . '%';
    $types .= 'sss'; array_push($params, $like, $like, $like);
}
$sql = "SELECT v.*, u.email, CONCAT_WS(' ', u.first_name, u.last_name) AS customer, o.order_number AS earned_order, uo.order_number AS used_order
        FROM user_gift_vouchers v
        LEFT JOIN users u ON u.id = v.user_id
        LEFT JOIN orders o ON o.id = v.order_id
        LEFT JOIN orders uo ON uo.id = v.used_order_id
        WHERE $where ORDER BY v.id DESC LIMIT 200";
$stmt = $db->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$issued = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$vCounts = ['active' => 0, 'used' => 0, 'expired' => 0, 'cancelled' => 0];
$cres = $db->query("SELECT status, COUNT(*) c FROM user_gift_vouchers GROUP BY status");
while ($cres && ($row = $cres->fetch_assoc())) $vCounts[$row['status']] = (int) $row['c'];

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$statusBadge = [
    'active' => 'bg-success', 'used' => 'bg-primary', 'expired' => 'bg-secondary', 'cancelled' => 'bg-danger',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Gift Vouchers | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .gv-value { font-weight: 700; color: #0c53a1; }
        .gv-scope { font-size: 12px; color: #64748b; max-width: 320px; }
        .gv-code { font-family: ui-monospace, Menlo, monospace; font-weight: 700; letter-spacing: .5px; }
        .action-icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 12px; }
        .action-icon-btn.btn-edit:hover { background: #e0f2fe; color: #0284c7; }
        .action-icon-btn.btn-del:hover { background: #fee2e2; color: #dc2626; }
        .gv-preview { padding: 12px; border-radius: 8px; background: #eef4fd; border: 1px solid #cfe0f5; font-size: 14px; }
        /* Multi-select pickers (same as Product Sales) */
        .ps-picker { position: relative; }
        .ps-picker-toggle { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; background: #fff; border: 1px solid #ced4da; border-radius: 6px; padding: 8px 12px; color: #495057; }
        .ps-picker-panel { position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,.12); }
        .ps-picker-head { padding: 8px; border-bottom: 1px solid #eef0f2; }
        .ps-picker-tools { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; }
        .ps-picker-list { max-height: 280px; overflow-y: auto; padding: 4px 0; }
        .ps-opt { display: flex; align-items: center; gap: 10px; padding: 6px 12px; cursor: pointer; margin: 0; }
        .ps-opt:hover { background: #f1f5f9; }
        .ps-opt input { flex-shrink: 0; width: 16px; height: 16px; }
        .ps-opt img { width: 34px; height: 34px; object-fit: contain; border-radius: 4px; border: 1px solid #eee; background: #fff; flex-shrink: 0; }
        .ps-opt-name { font-size: 13px; font-weight: 600; line-height: 1.2; }
        .ps-opt-meta { font-size: 11px; color: #6c757d; }
        .ps-picker-empty { padding: 16px; text-align: center; color: #6c757d; font-size: 13px; }
        .ps-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .ps-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 4px 3px 10px; border: 1px solid #c7d7fe; background: #f5f8ff; border-radius: 16px; font-size: 12px; }
        .ps-chip button { border: 0; background: none; color: #64748b; padding: 0 4px; font-size: 14px; line-height: 1; }
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
                            <h3 class="mb-0"><strong>Gift</strong> Vouchers</h3>
                            <small class="text-muted">Reward customers with a gift voucher when they buy chosen products. The voucher is given when the order is confirmed, shows on the customer's Gift Card page and works in the checkout coupon box. Cancelling the order cancels an unused voucher.</small>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openRuleModal()"><i class="fas fa-gift me-1"></i> New Voucher Rule</button>
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

                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item"><a class="nav-link <?php echo $tab === 'rules' ? 'active' : ''; ?>" href="gift-vouchers.php">Voucher Rules <span class="badge bg-light text-dark border ms-1"><?php echo count($rules); ?></span></a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $tab === 'issued' ? 'active' : ''; ?>" href="gift-vouchers.php?tab=issued">Vouchers Given <span class="badge bg-light text-dark border ms-1"><?php echo array_sum($vCounts); ?></span></a></li>
                    </ul>

                    <?php if ($tab === 'rules'): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Voucher</th>
                                            <th>Customer buys</th>
                                            <th>Gets voucher</th>
                                            <th>Use on order of</th>
                                            <th>Valid</th>
                                            <th>Given / Used</th>
                                            <th style="width:70px">On</th>
                                            <th style="width:90px" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($rules)): ?>
                                            <tr><td colspan="8" class="text-center text-muted py-4">No voucher rules yet. Click "New Voucher Rule".</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($rules as $r):
                                            $pids = giftIdList($r['product_ids']);
                                            $cids = giftIdList($r['category_ids']);
                                            if ($r['applies_to'] === 'all') $scope = 'Any product';
                                            elseif ($r['applies_to'] === 'products') $scope = count($pids) . ' selected product' . (count($pids) === 1 ? '' : 's');
                                            else $scope = implode(', ', array_map(fn($c) => $catName[$c] ?? ('#' . $c), $cids));
                                            $max = $r['max_discount'] !== null ? (float) $r['max_discount'] : null;
                                            $edit = [
                                                'id' => (int) $r['id'], 'title' => $r['title'], 'min_spend' => (float) $r['min_spend'],
                                                'voucher_type' => $r['voucher_type'], 'voucher_value' => (float) $r['voucher_value'], 'max_discount' => $max,
                                                'min_order_to_use' => (float) $r['min_order_to_use'], 'validity_days' => (int) $r['validity_days'],
                                                'applies_to' => $r['applies_to'], 'product_ids' => $pids, 'category_ids' => $cids, 'status' => $r['status'],
                                            ];
                                        ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></td>
                                                <td>
                                                    <div>₹<?php echo number_format((float) $r['min_spend'], 0); ?>+</div>
                                                    <div class="gv-scope">of <?php echo htmlspecialchars($scope); ?></div>
                                                </td>
                                                <td class="gv-value"><?php echo htmlspecialchars(giftVoucherValueLabel($r['voucher_type'], $r['voucher_value'], $max)); ?></td>
                                                <td><?php echo (float) $r['min_order_to_use'] > 0 ? '₹' . number_format((float) $r['min_order_to_use'], 0) . '+' : 'Any amount'; ?></td>
                                                <td><?php echo (int) $r['validity_days']; ?> days</td>
                                                <td><?php echo (int) $r['issued_count']; ?> / <?php echo (int) $r['used_count']; ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $r['status'] === 'active' ? 'checked' : ''; ?> onchange="toggleRule(<?php echo (int) $r['id']; ?>, this)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="action-icon-btn btn-edit" title="Edit"
                                                        data-rule="<?php echo htmlspecialchars(json_encode($edit), ENT_QUOTES); ?>"
                                                        onclick="openRuleModal(JSON.parse(this.dataset.rule))"><i class="fas fa-pen"></i></button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this voucher rule? Vouchers already given stay valid.');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
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
                    <?php else: ?>
                    <form class="d-flex flex-wrap gap-2 mb-3" method="get">
                        <input type="hidden" name="tab" value="issued">
                        <?php foreach (['' => 'All', 'active' => 'Active', 'used' => 'Used', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $k => $l): ?>
                            <a href="gift-vouchers.php?tab=issued<?php echo $k ? '&vstatus=' . $k : ''; ?>" class="btn btn-sm <?php echo $vStatus === $k ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <?php echo $l; ?><?php if ($k): ?> <span class="badge bg-white text-dark ms-1"><?php echo $vCounts[$k]; ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        <input type="hidden" name="vstatus" value="<?php echo htmlspecialchars($vStatus); ?>">
                        <input type="search" name="q" class="form-control form-control-sm ms-auto" style="max-width:260px" placeholder="Search code, email or order..." value="<?php echo htmlspecialchars($vSearch); ?>">
                    </form>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Customer</th>
                                            <th>Value</th>
                                            <th>Earned on</th>
                                            <th>Expires</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($issued)): ?>
                                            <tr><td colspan="7" class="text-center text-muted py-4">No vouchers given yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($issued as $v): ?>
                                            <tr>
                                                <td>
                                                    <div class="gv-code"><?php echo htmlspecialchars($v['code']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($v['title']); ?></div>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars(trim($v['customer'] ?? '') ?: ('User #' . $v['user_id'])); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($v['email'] ?? ''); ?></div>
                                                </td>
                                                <td class="gv-value"><?php echo htmlspecialchars(giftVoucherValueLabel($v['voucher_type'], $v['voucher_value'], $v['max_discount'])); ?></td>
                                                <td class="small">
                                                    <?php echo $v['earned_order'] ? 'Order #' . htmlspecialchars($v['earned_order']) : '—'; ?>
                                                    <div class="text-muted"><?php echo date('d M Y', strtotime($v['created_at'])); ?></div>
                                                </td>
                                                <td class="small"><?php echo date('d M Y', strtotime($v['expires_at'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $statusBadge[$v['status']] ?? 'bg-secondary'; ?>"><?php echo ucfirst($v['status']); ?></span>
                                                    <?php if ($v['status'] === 'used' && $v['used_order']): ?><div class="small text-muted">on #<?php echo htmlspecialchars($v['used_order']); ?></div><?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($v['status'] === 'active'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Cancel this voucher? The customer can no longer use it.');">
                                                            <input type="hidden" name="action" value="cancel_voucher">
                                                            <input type="hidden" name="id" value="<?php echo (int) $v['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
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
                    <?php endif; ?>

                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- New / Edit Rule -->
    <div class="modal fade" id="ruleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="post" class="modal-content" id="ruleForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="r_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="ruleModalTitle">New Voucher Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title (shown to customers) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="r_title" maxlength="100" placeholder="e.g. Bulk Order Reward" required oninput="renderPreview()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="r_status">
                                <option value="active">On</option>
                                <option value="inactive">Off</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1"><div class="fw-semibold">When the customer buys</div></div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum purchase (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" class="form-control" name="min_spend" id="r_min_spend" required oninput="renderPreview()">
                            <small class="text-muted">Total of the eligible products in one order</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Of these products</label>
                            <select class="form-select" name="applies_to" id="r_applies" onchange="syncApplies(); renderPreview()">
                                <option value="products">Selected products only</option>
                                <option value="categories">Products in selected categories</option>
                                <option value="all">Any product</option>
                            </select>
                        </div>
                        <div class="col-12" id="r_products_box">
                            <div class="ps-picker" id="pPicker">
                                <button type="button" class="ps-picker-toggle" onclick="togglePanel('p')"><span id="pToggleText">Select products</span><i class="fas fa-chevron-down small"></i></button>
                                <div class="ps-picker-panel d-none" id="pPanel">
                                    <div class="ps-picker-head">
                                        <input type="search" class="form-control form-control-sm" id="pSearch" placeholder="Search by name, SKU or ID..." autocomplete="off" oninput="renderList('p')">
                                        <div class="ps-picker-tools"><span class="text-muted" id="pCount"></span>
                                            <span><button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="selectShown('p', true)">Select all shown</button><button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="selectShown('p', false)">Clear</button></span>
                                        </div>
                                    </div>
                                    <div class="ps-picker-list" id="pList"></div>
                                </div>
                            </div>
                            <div class="ps-chips" id="pChips"></div>
                        </div>
                        <div class="col-12 d-none" id="r_categories_box">
                            <div class="ps-picker" id="cPicker">
                                <button type="button" class="ps-picker-toggle" onclick="togglePanel('c')"><span id="cToggleText">Select categories</span><i class="fas fa-chevron-down small"></i></button>
                                <div class="ps-picker-panel d-none" id="cPanel">
                                    <div class="ps-picker-head">
                                        <input type="search" class="form-control form-control-sm" id="cSearch" placeholder="Search categories..." autocomplete="off" oninput="renderList('c')">
                                        <div class="ps-picker-tools"><span class="text-muted" id="cCount"></span>
                                            <span><button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="selectShown('c', true)">Select all shown</button><button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="selectShown('c', false)">Clear</button></span>
                                        </div>
                                    </div>
                                    <div class="ps-picker-list" id="cList"></div>
                                </div>
                            </div>
                            <div class="ps-chips" id="cChips"></div>
                            <small class="text-muted">A product counts when its category, sub-category or sub-sub-category is selected.</small>
                        </div>
                        <div id="r_hidden"></div>

                        <div class="col-12"><hr class="my-1"><div class="fw-semibold">They get a voucher</div></div>
                        <div class="col-md-4">
                            <label class="form-label">Voucher type</label>
                            <select class="form-select" name="voucher_type" id="r_type" onchange="syncType(); renderPreview()">
                                <option value="fixed">Flat amount (₹)</option>
                                <option value="percent">% off</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" id="r_value_label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="voucher_value" id="r_value" required oninput="renderPreview()">
                        </div>
                        <div class="col-md-4" id="r_max_box">
                            <label class="form-label">Max discount (₹)</label>
                            <input type="number" step="1" min="0" class="form-control" name="max_discount" id="r_max" placeholder="No limit" oninput="renderPreview()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usable on orders of (₹)</label>
                            <input type="number" step="1" min="0" class="form-control" name="min_order_to_use" id="r_min_use" placeholder="0 = any amount" oninput="renderPreview()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valid for (days)</label>
                            <input type="number" step="1" min="1" max="3650" class="form-control" name="validity_days" id="r_days" value="30" oninput="renderPreview()">
                        </div>
                        <div class="col-12">
                            <div class="gv-preview" id="r_preview"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const DATA = {
            p: <?php echo json_encode($pickerProducts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            c: <?php echo json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        };
        const BY_ID = { p: Object.fromEntries(DATA.p.map(x => [x.id, x])), c: Object.fromEntries(DATA.c.map(x => [x.id, x])) };
        const selected = { p: [], c: [] };

        function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
        function fmt(n) { return Number(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }

        function togglePanel(k, force) {
            const panel = document.getElementById(k + 'Panel');
            const open = typeof force === 'boolean' ? force : panel.classList.contains('d-none');
            panel.classList.toggle('d-none', !open);
            if (open) { renderList(k); document.getElementById(k + 'Search').focus(); }
        }
        document.addEventListener('click', function (e) {
            ['p', 'c'].forEach(k => {
                const picker = document.getElementById(k + 'Picker');
                if (picker && !picker.contains(e.target)) document.getElementById(k + 'Panel').classList.add('d-none');
            });
        });

        function shown(k) {
            const q = document.getElementById(k + 'Search').value.trim().toLowerCase();
            if (!q) return DATA[k];
            return DATA[k].filter(x => x.name.toLowerCase().includes(q) || (x.sku || '').toLowerCase().includes(q) || String(x.id) === q);
        }

        function renderList(k) {
            const list = shown(k);
            const visible = list.slice(0, 300);
            document.getElementById(k + 'Count').textContent = `${list.length} ${k === 'p' ? 'product' : 'categor'}${list.length === 1 ? (k === 'p' ? '' : 'y') : (k === 'p' ? 's' : 'ies')}`;
            document.getElementById(k + 'List').innerHTML = visible.length ? visible.map(x => `
                <label class="ps-opt">
                    <input type="checkbox" class="form-check-input m-0" ${selected[k].includes(x.id) ? 'checked' : ''} onchange="toggleItem('${k}', ${x.id}, this.checked)">
                    ${k === 'p' ? `<img src="${esc(x.image)}" alt="" loading="lazy" onerror="this.src='img/icons/icon-48x48.png'">` : ''}
                    <span class="flex-grow-1">
                        <span class="d-block ps-opt-name">${esc(x.name)}</span>
                        ${k === 'p' ? `<span class="ps-opt-meta">#${x.id}${x.sku ? ' · ' + esc(x.sku) : ''} · ₹${fmt(x.price)}${x.status !== 'active' ? ' · <span class="text-danger">' + esc(x.status) + '</span>' : ''}</span>` : ''}
                    </span>
                </label>`).join('') : '<div class="ps-picker-empty">Nothing matches.</div>';
        }

        function toggleItem(k, id, on) {
            id = Number(id);
            if (on && !selected[k].includes(id)) selected[k].push(id);
            if (!on) selected[k] = selected[k].filter(x => x !== id);
            sync();
        }
        function selectShown(k, on) {
            const ids = shown(k).slice(0, 300).map(x => x.id);
            if (on) ids.forEach(id => { if (!selected[k].includes(id)) selected[k].push(id); });
            else selected[k] = selected[k].filter(id => !ids.includes(id));
            renderList(k);
            sync();
        }

        function sync() {
            ['p', 'c'].forEach(k => {
                const n = selected[k].length;
                document.getElementById(k + 'ToggleText').textContent = n ? `${n} ${k === 'p' ? 'product' : 'categor'}${n === 1 ? (k === 'p' ? '' : 'y') : (k === 'p' ? 's' : 'ies')} selected` : (k === 'p' ? 'Select products' : 'Select categories');
                document.getElementById(k + 'Chips').innerHTML = selected[k].map(id => `<span class="ps-chip">${esc((BY_ID[k][id] || { name: '#' + id }).name)} <button type="button" onclick="toggleItem('${k}', ${id}, false); renderList('${k}');" aria-label="Remove">×</button></span>`).join('');
            });
            document.getElementById('r_hidden').innerHTML =
                selected.p.map(id => `<input type="hidden" name="product_ids[]" value="${id}">`).join('') +
                selected.c.map(id => `<input type="hidden" name="category_ids[]" value="${id}">`).join('');
            renderPreview();
        }

        function syncApplies() {
            const a = document.getElementById('r_applies').value;
            document.getElementById('r_products_box').classList.toggle('d-none', a !== 'products');
            document.getElementById('r_categories_box').classList.toggle('d-none', a !== 'categories');
        }
        function syncType() {
            const pct = document.getElementById('r_type').value === 'percent';
            document.getElementById('r_value_label').innerHTML = (pct ? 'Percent off (%)' : 'Amount (₹)') + ' <span class="text-danger">*</span>';
            document.getElementById('r_max_box').classList.toggle('d-none', !pct);
        }

        function renderPreview() {
            const v = id => document.getElementById(id).value;
            const pct = v('r_type') === 'percent';
            const value = parseFloat(v('r_value')) || 0;
            const max = parseFloat(v('r_max')) || 0;
            const min = parseFloat(v('r_min_spend')) || 0;
            const use = parseFloat(v('r_min_use')) || 0;
            const a = v('r_applies');
            const scope = a === 'all' ? 'any products' : a === 'products' ? `${selected.p.length} selected product(s)` : `products in ${selected.c.length} selected categor${selected.c.length === 1 ? 'y' : 'ies'}`;
            const reward = pct ? `${value}% off${max ? ' (up to ₹' + fmt(max) + ')' : ''}` : `₹${fmt(value)}`;
            document.getElementById('r_preview').innerHTML = `<b>Customer sees:</b> Buy ₹${fmt(min)}+ of ${scope} → get a <b>${reward}</b> gift voucher, usable ${use ? 'on orders of ₹' + fmt(use) + '+' : 'on any order'} within ${parseInt(v('r_days')) || 30} days.`;
        }

        function openRuleModal(r) {
            r = r || {};
            document.getElementById('ruleModalTitle').textContent = r.id ? 'Edit Voucher Rule' : 'New Voucher Rule';
            document.getElementById('r_id').value = r.id || '';
            document.getElementById('r_title').value = r.title || '';
            document.getElementById('r_status').value = r.status || 'active';
            document.getElementById('r_min_spend').value = r.min_spend || '';
            document.getElementById('r_applies').value = r.applies_to || 'products';
            document.getElementById('r_type').value = r.voucher_type || 'fixed';
            document.getElementById('r_value').value = r.voucher_value || '';
            document.getElementById('r_max').value = r.max_discount || '';
            document.getElementById('r_min_use').value = r.min_order_to_use || '';
            document.getElementById('r_days').value = r.validity_days || 30;
            selected.p = (r.product_ids || []).map(Number);
            selected.c = (r.category_ids || []).map(Number);
            ['p', 'c'].forEach(k => { document.getElementById(k + 'Search').value = ''; togglePanel(k, false); });
            syncApplies(); syncType(); sync();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ruleModal')).show();
        }

        document.getElementById('ruleForm').addEventListener('submit', function (e) {
            const a = document.getElementById('r_applies').value;
            if (a === 'products' && !selected.p.length) { e.preventDefault(); alert('Choose at least one product.'); togglePanel('p', true); }
            if (a === 'categories' && !selected.c.length) { e.preventDefault(); alert('Choose at least one category.'); togglePanel('c', true); }
        });

        function toggleRule(id, el) {
            fetch('gift-vouchers.php', { method: 'POST', body: new URLSearchParams({ action: 'toggle_status', id: id, status: el.checked ? 'active' : 'inactive' }) })
                .then(r => r.json())
                .then(res => { if (!res.success) { el.checked = !el.checked; alert('Could not update'); } })
                .catch(() => { el.checked = !el.checked; alert('Could not update'); });
        }

        document.addEventListener('DOMContentLoaded', function () { document.body.style.opacity = 1; });
    </script>
</body>
</html>
