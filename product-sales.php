<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/config/product-sales-schema.php');
require_once(__DIR__ . '/controllers/AuthController.php');

$database = new Database();
$db = $database->getConnection();

$auth = new AuthController($db);
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

ensureProductSalesTable($db);

function psRedirect($filter = '') {
    header('Location: product-sales.php' . ($filter ? '?filter=' . urlencode($filter) : ''));
    exit();
}

// Current selling price of a product (offer price when set, else price)
function psCurrentPrice($row) {
    $offer = (float) ($row['offer_price'] ?? 0);
    $disc = (float) ($row['discount_price'] ?? 0);
    $price = (float) ($row['price'] ?? 0);
    return $offer > 0 ? $offer : ($disc > 0 ? $disc : $price);
}

function psImg($path) {
    $path = (string) $path;
    if ($path === '') return 'img/icons/icon-48x48.png';
    return (strpos($path, 'http') === 0) ? $path : ltrim($path, '/');
}

// -------------------------------------------------------------
// ACTIONS
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filterBack = $_POST['filter'] ?? '';

    if ($action === 'toggle_status') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $status = (($_POST['status'] ?? '') === 'active') ? 'active' : 'inactive';
        $stmt = $db->prepare("UPDATE product_sales SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM product_sales WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Sale deleted.' : 'Could not delete the sale.';
        psRedirect($filterBack);
    }

    if ($action === 'save') {
        $id         = (int) ($_POST['id'] ?? 0);
        $productIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['product_ids'] ?? [])))));
        $label      = mb_substr(trim($_POST['label'] ?? ''), 0, 60) ?: 'Flash Sale';
        $type       = ($_POST['discount_type'] ?? '') === 'price' ? 'price' : 'percent';
        $value      = round((float) ($_POST['discount_value'] ?? 0), 2);
        $startsAt   = saleNormaliseDatetime($_POST['starts_at'] ?? '');
        $endsAt     = saleNormaliseDatetime($_POST['ends_at'] ?? '');
        $status     = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        $error = '';
        if (empty($productIds)) {
            $error = 'Please choose at least one product.';
        } elseif (!$startsAt || !$endsAt) {
            $error = 'Please set both the start and end date & time.';
        } elseif (saleTimestamp($endsAt) <= saleTimestamp($startsAt)) {
            $error = 'The sale must end after it starts.';
        } elseif ($type === 'percent' && ($value <= 0 || $value >= 100)) {
            $error = 'Discount % must be between 1 and 99.';
        } elseif ($type === 'price' && $value <= 0) {
            $error = 'Sale price must be more than ₹0.';
        }
        if ($error) {
            $_SESSION['error_message'] = $error;
            psRedirect($filterBack);
        }

        // Check every product: the sale price has to be below its current price
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $db->prepare("SELECT id, name, price, offer_price, discount_price FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $stmt->execute();
        $res = $stmt->get_result();
        $valid = [];
        $skipped = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $current = psCurrentPrice($row);
            $salePrice = salePriceFor(['discount_type' => $type, 'discount_value' => $value], $current);
            if ($salePrice > 0 && ($current <= 0 || $salePrice < $current)) {
                $valid[] = (int) $row['id'];
            } else {
                $skipped[] = $row['name'] . ' (₹' . number_format($current, 0) . ')';
            }
        }
        // keep the order the admin picked
        $valid = array_values(array_filter($productIds, fn($pid) => in_array($pid, $valid, true)));

        if (empty($valid)) {
            $_SESSION['error_message'] = 'No sale saved: the sale price is not lower than the current price of ' . implode(', ', $skipped) . '.';
            psRedirect($filterBack);
        }

        $saved = 0;
        $ins = $db->prepare("INSERT INTO product_sales (product_id, label, discount_type, discount_value, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($valid as $i => $productId) {
            if ($i === 0 && $id > 0) {
                // editing: the existing sale takes the first product, any extra products get new sales
                $up = $db->prepare("UPDATE product_sales SET product_id = ?, label = ?, discount_type = ?, discount_value = ?, starts_at = ?, ends_at = ?, status = ? WHERE id = ?");
                $up->bind_param("issdsssi", $productId, $label, $type, $value, $startsAt, $endsAt, $status, $id);
                if ($up->execute()) $saved++;
                continue;
            }
            $ins->bind_param("issdsss", $productId, $label, $type, $value, $startsAt, $endsAt, $status);
            if ($ins->execute()) $saved++;
        }

        $msg = $id > 0
            ? ($saved > 1 ? "Sale updated and added to " . ($saved - 1) . " more product(s)." : 'Sale updated.')
            : ($saved === 1 ? 'Sale created.' : "Sale created for $saved products.");
        if ($skipped) {
            $msg .= ' Skipped (sale price not lower than current price): ' . implode(', ', $skipped) . '.';
        }
        $_SESSION[$saved ? 'success_message' : 'error_message'] = $saved ? $msg : 'Could not save the sale.';
        psRedirect($filterBack);
    }
}

// -------------------------------------------------------------
// DATA
// -------------------------------------------------------------
$filter = $_GET['filter'] ?? '';
$res = $db->query("SELECT s.*, p.name AS product_name, p.sku, p.price, p.offer_price, p.discount_price, p.regular_price, p.thumbnail_image,
                          COALESCE((SELECT i.image_url FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.display_order ASC LIMIT 1), p.thumbnail_image) AS image
                   FROM product_sales s LEFT JOIN products p ON p.id = s.product_id
                   ORDER BY s.ends_at DESC, s.id DESC");
$sales = [];
$counts = ['live' => 0, 'upcoming' => 0, 'ended' => 0, 'off' => 0];
$now = time();
while ($res && ($row = $res->fetch_assoc())) {
    $row['state'] = saleState($row, $now);
    $row['current_price'] = psCurrentPrice($row);
    $row['sale_price'] = salePriceFor($row, $row['current_price']);
    $row['ends_ts'] = saleTimestamp($row['ends_at']);
    $row['starts_ts'] = saleTimestamp($row['starts_at']);
    $counts[$row['state']]++;
    if ($filter && $row['state'] !== $filter) continue;
    $sales[] = $row;
}

// Every product for the multi-select picker (searched in the browser, no round trips)
$pickerProducts = [];
$pr = $db->query("SELECT p.id, p.name, p.sku, p.status, p.price, p.offer_price, p.discount_price,
                         COALESCE((SELECT i.image_url FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_primary DESC, i.display_order ASC LIMIT 1), p.thumbnail_image) AS image
                  FROM products p ORDER BY p.name ASC");
while ($pr && ($row = $pr->fetch_assoc())) {
    $pickerProducts[] = [
        'id'     => (int) $row['id'],
        'name'   => $row['name'],
        'sku'    => $row['sku'] ?? '',
        'status' => $row['status'],
        'price'  => psCurrentPrice($row),
        'image'  => psImg($row['image']),
    ];
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stateBadge = [
    'live'     => ['bg-success', 'Live'],
    'upcoming' => ['bg-info text-dark', 'Upcoming'],
    'ended'    => ['bg-secondary', 'Ended'],
    'off'      => ['bg-light text-dark border', 'Off'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Product Sales | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .ps-thumb { width: 44px; height: 44px; border-radius: 6px; object-fit: contain; border: 1px solid #e2e8f0; background: #fff; }
        .ps-old { color: #94a3b8; text-decoration: line-through; font-size: 12px; }
        .ps-new { font-weight: 700; color: #15803d; }
        .ps-off { font-size: 11px; color: #15803d; }
        .ps-left { font-size: 11px; color: #15803d; font-weight: 600; }
        .action-icon-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 12px; }
        .action-icon-btn.btn-edit:hover { background: #e0f2fe; color: #0284c7; }
        .action-icon-btn.btn-del:hover { background: #fee2e2; color: #dc2626; }
        /* Multi-select product dropdown */
        .ps-picker { position: relative; }
        .ps-picker-toggle { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; background: #fff; border: 1px solid #ced4da; border-radius: 6px; padding: 8px 12px; color: #495057; }
        .ps-picker-toggle:focus { outline: 0; border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
        .ps-picker-panel { position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,.12); }
        .ps-picker-head { padding: 8px; border-bottom: 1px solid #eef0f2; }
        .ps-picker-tools { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; }
        .ps-picker-list { max-height: 300px; overflow-y: auto; padding: 4px 0; }
        .ps-opt { display: flex; align-items: center; gap: 10px; padding: 6px 12px; cursor: pointer; margin: 0; }
        .ps-opt:hover { background: #f1f5f9; }
        .ps-opt input { flex-shrink: 0; width: 16px; height: 16px; }
        .ps-opt img { width: 34px; height: 34px; object-fit: contain; border-radius: 4px; border: 1px solid #eee; background: #fff; flex-shrink: 0; }
        .ps-opt .ps-opt-name { font-size: 13px; font-weight: 600; line-height: 1.2; }
        .ps-opt .ps-opt-meta { font-size: 11px; color: #6c757d; }
        .ps-picker-empty { padding: 16px; text-align: center; color: #6c757d; font-size: 13px; }
        .ps-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .ps-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 4px 3px 10px; border: 1px solid #c7d7fe; background: #f5f8ff; border-radius: 16px; font-size: 12px; }
        .ps-chip button { border: 0; background: none; color: #64748b; padding: 0 4px; font-size: 14px; line-height: 1; }
        .ps-preview table { width: 100%; font-size: 13px; }
        .ps-preview td { padding: 3px 0; }
        .ps-preview { padding: 12px; border-radius: 8px; background: #f0faf2; border: 1px solid #b7dfc0; font-size: 14px; }
        .ps-filter .btn { font-size: 13px; }
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
                            <h3 class="mb-0"><strong>Product</strong> Sales</h3>
                            <small class="text-muted">Run a timed sale on any product. While it is live the product shows the sale price and a countdown above its name. Times are India time (IST).</small>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openSaleModal()"><i class="fas fa-bolt me-1"></i> Start a Sale</button>
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

                    <div class="ps-filter d-flex flex-wrap gap-2 mb-3">
                        <?php foreach (['' => 'All', 'live' => 'Live', 'upcoming' => 'Upcoming', 'ended' => 'Ended', 'off' => 'Off'] as $k => $l): ?>
                            <a href="product-sales.php<?php echo $k ? '?filter=' . $k : ''; ?>" class="btn btn-sm <?php echo $filter === $k ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <?php echo $l; ?>
                                <?php if ($k): ?><span class="badge bg-white text-dark ms-1"><?php echo $counts[$k]; ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Label</th>
                                            <th>Price</th>
                                            <th>Starts (IST)</th>
                                            <th>Ends (IST)</th>
                                            <th>Status</th>
                                            <th style="width:70px">On</th>
                                            <th style="width:90px" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sales)): ?>
                                            <tr><td colspan="8" class="text-center text-muted py-4">No sales here yet. Click "Start a Sale".</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($sales as $s): ?>
                                            <?php
                                                $pct = $s['current_price'] > 0 ? round((1 - $s['sale_price'] / $s['current_price']) * 100) : 0;
                                                [$badgeClass, $badgeText] = $stateBadge[$s['state']];
                                                $editData = [
                                                    'id' => (int) $s['id'], 'product_id' => (int) $s['product_id'], 'product_name' => $s['product_name'],
                                                    'image' => psImg($s['image']), 'current_price' => $s['current_price'], 'label' => $s['label'],
                                                    'discount_type' => $s['discount_type'], 'discount_value' => (float) $s['discount_value'],
                                                    'starts_at' => str_replace(' ', 'T', substr($s['starts_at'], 0, 16)),
                                                    'ends_at' => str_replace(' ', 'T', substr($s['ends_at'], 0, 16)), 'status' => $s['status'],
                                                ];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img class="ps-thumb" src="<?php echo htmlspecialchars(psImg($s['image'])); ?>" alt="" onerror="this.src='img/icons/icon-48x48.png'">
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($s['product_name'] ?? ('#' . $s['product_id'] . ' (deleted)')); ?></div>
                                                            <div class="text-muted small">#<?php echo (int) $s['product_id']; ?><?php echo $s['sku'] ? ' · ' . htmlspecialchars($s['sku']) : ''; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-danger-subtle text-danger"><?php echo htmlspecialchars($s['label']); ?></span></td>
                                                <td>
                                                    <div class="ps-old">₹<?php echo number_format($s['current_price'], 0); ?></div>
                                                    <div class="ps-new">₹<?php echo number_format($s['sale_price'], 0); ?></div>
                                                    <div class="ps-off"><?php echo $s['discount_type'] === 'percent' ? (float) $s['discount_value'] . '% off' : $pct . '% off'; ?></div>
                                                </td>
                                                <td class="small"><?php echo date('d M Y, h:i A', strtotime($s['starts_at'])); ?></td>
                                                <td class="small"><?php echo date('d M Y, h:i A', strtotime($s['ends_at'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                                                    <?php if ($s['state'] === 'live'): ?>
                                                        <div class="ps-left" data-ends="<?php echo (int) $s['ends_ts']; ?>"></div>
                                                    <?php elseif ($s['state'] === 'upcoming'): ?>
                                                        <div class="ps-left text-info" data-starts="<?php echo (int) $s['starts_ts']; ?>"></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" <?php echo $s['status'] === 'active' ? 'checked' : ''; ?> onchange="toggleSaleStatus(<?php echo (int) $s['id']; ?>, this)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="action-icon-btn btn-edit" title="Edit"
                                                        data-sale="<?php echo htmlspecialchars(json_encode($editData), ENT_QUOTES); ?>"
                                                        onclick="openSaleModal(JSON.parse(this.dataset.sale))"><i class="fas fa-pen"></i></button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this sale?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
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

    <!-- Start / Edit Sale -->
    <div class="modal fade" id="saleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="post" class="modal-content" id="saleForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="s_id">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="saleModalTitle">Start a Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Products <span class="text-danger">*</span> <small class="text-muted">(select one or more — each gets this sale)</small></label>
                            <div class="ps-picker" id="psPicker">
                                <button type="button" class="ps-picker-toggle" id="psToggle" onclick="togglePicker()">
                                    <span id="psToggleText">Select products</span>
                                    <i class="fas fa-chevron-down small"></i>
                                </button>
                                <div class="ps-picker-panel d-none" id="psPanel">
                                    <div class="ps-picker-head">
                                        <input type="search" class="form-control form-control-sm" id="psSearch" placeholder="Search by name, SKU or ID..." autocomplete="off" oninput="renderPickerList()">
                                        <div class="ps-picker-tools">
                                            <span class="text-muted" id="psShownCount"></span>
                                            <span>
                                                <button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="selectAllShown(true)">Select all shown</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="selectAllShown(false)">Clear</button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ps-picker-list" id="psList"></div>
                                </div>
                            </div>
                            <div class="ps-chips" id="psChips"></div>
                            <div id="psHidden"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sale label (shown with the countdown)</label>
                            <input type="text" class="form-control" name="label" id="s_label" maxlength="60" placeholder="Flash Sale">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount</label>
                            <select class="form-select" name="discount_type" id="s_type" onchange="renderPreview()">
                                <option value="percent">% off</option>
                                <option value="price">Fixed sale price (₹)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="s_value_label">% off</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="discount_value" id="s_value" required oninput="renderPreview()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Starts (IST) <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="starts_at" id="s_starts" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ends (IST) <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="ends_at" id="s_ends" required>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="text-muted small w-100">Quick duration from start:</span>
                                <?php foreach (['6' => '6 hours', '24' => '1 day', '72' => '3 days', '168' => '7 days'] as $hrs => $lbl): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDuration(<?php echo $hrs; ?>)"><?php echo $lbl; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="s_status">
                                <option value="active">On</option>
                                <option value="inactive">Off</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="ps-preview" id="s_preview">Choose products to see the sale price.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Sale</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const PICKER_PRODUCTS = <?php echo json_encode($pickerProducts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const PRODUCT_BY_ID = Object.fromEntries(PICKER_PRODUCTS.map(p => [p.id, p]));
        let selectedIds = [];

        function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
        function fmt(n) { return Number(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }

        // datetime-local value for "now" in India time
        function istNowLocal(offsetHours) {
            const d = new Date(Date.now() + (offsetHours || 0) * 3600000);
            const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Kolkata', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(d);
            const g = t => parts.find(p => p.type === t).value;
            return `${g('year')}-${g('month')}-${g('day')}T${g('hour')}:${g('minute')}`;
        }

        function setDuration(hours) {
            const start = document.getElementById('s_starts').value || istNowLocal();
            document.getElementById('s_starts').value = start;
            const [d, t] = start.split('T');
            const base = new Date(`${d}T${t}:00Z`);           // treat as wall-clock time, add hours, format back
            const end = new Date(base.getTime() + hours * 3600000).toISOString().slice(0, 16);
            document.getElementById('s_ends').value = end;
        }

        // ── Multi-select product dropdown ──
        function togglePicker(force) {
            const panel = document.getElementById('psPanel');
            const open = typeof force === 'boolean' ? force : panel.classList.contains('d-none');
            panel.classList.toggle('d-none', !open);
            if (open) { renderPickerList(); document.getElementById('psSearch').focus(); }
        }

        document.addEventListener('click', function (e) {
            const picker = document.getElementById('psPicker');
            if (picker && !picker.contains(e.target)) document.getElementById('psPanel').classList.add('d-none');
        });
        document.getElementById('psSearch').addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); togglePicker(false); document.getElementById('psToggle').focus(); }
        });

        function shownProducts() {
            const q = document.getElementById('psSearch').value.trim().toLowerCase();
            if (!q) return PICKER_PRODUCTS;
            return PICKER_PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q) || String(p.id) === q);
        }

        function renderPickerList() {
            const list = shownProducts();
            const shown = list.slice(0, 300);
            document.getElementById('psShownCount').textContent = `${list.length} product${list.length === 1 ? '' : 's'}${list.length > shown.length ? ' (first 300 shown, refine search)' : ''}`;
            document.getElementById('psList').innerHTML = shown.length ? shown.map(p => `
                <label class="ps-opt">
                    <input type="checkbox" class="form-check-input m-0" value="${p.id}" ${selectedIds.includes(p.id) ? 'checked' : ''} onchange="toggleProduct(${p.id}, this.checked)">
                    <img src="${esc(p.image)}" alt="" loading="lazy" onerror="this.src='img/icons/icon-48x48.png'">
                    <span class="flex-grow-1">
                        <span class="d-block ps-opt-name">${esc(p.name)}</span>
                        <span class="ps-opt-meta">#${p.id}${p.sku ? ' · ' + esc(p.sku) : ''} · ₹${fmt(p.price)}${p.status !== 'active' ? ' · <span class="text-danger">' + esc(p.status) + '</span>' : ''}</span>
                    </span>
                </label>`).join('') : '<div class="ps-picker-empty">No products match.</div>';
        }

        function toggleProduct(id, on) {
            id = Number(id);
            if (on && !selectedIds.includes(id)) selectedIds.push(id);
            if (!on) selectedIds = selectedIds.filter(x => x !== id);
            syncSelection();
        }

        function selectAllShown(on) {
            const ids = shownProducts().slice(0, 300).map(p => p.id);
            if (on) ids.forEach(id => { if (!selectedIds.includes(id)) selectedIds.push(id); });
            else selectedIds = selectedIds.filter(id => !ids.includes(id));
            renderPickerList();
            syncSelection();
        }

        // Chips, dropdown label, hidden inputs and preview follow the selection
        function syncSelection() {
            document.getElementById('psToggleText').textContent = selectedIds.length
                ? `${selectedIds.length} product${selectedIds.length === 1 ? '' : 's'} selected`
                : 'Select products';
            document.getElementById('psChips').innerHTML = selectedIds.map(id => {
                const p = PRODUCT_BY_ID[id] || { name: '#' + id };
                return `<span class="ps-chip">${esc(p.name)} <button type="button" onclick="toggleProduct(${id}, false); renderPickerList();" aria-label="Remove">×</button></span>`;
            }).join('');
            document.getElementById('psHidden').innerHTML = selectedIds.map(id => `<input type="hidden" name="product_ids[]" value="${id}">`).join('');
            renderPreview();
        }

        function salePriceFor(price) {
            const type = document.getElementById('s_type').value;
            const v = parseFloat(document.getElementById('s_value').value) || 0;
            return type === 'price' ? v : Math.round(price * (1 - v / 100)); // % off → whole rupees, same as the server
        }

        function renderPreview() {
            const type = document.getElementById('s_type').value;
            document.getElementById('s_value_label').textContent = type === 'price' ? 'Sale price (₹)' : '% off';
            const box = document.getElementById('s_preview');
            if (!selectedIds.length) { box.textContent = 'Choose products to see the sale price.'; return; }
            const rows = selectedIds.map(id => {
                const p = PRODUCT_BY_ID[id] || { name: '#' + id, price: 0 };
                const sp = salePriceFor(p.price);
                const ok = sp > 0 && (p.price <= 0 || sp < p.price);
                const pct = p.price > 0 ? Math.round((1 - sp / p.price) * 100) : 0;
                return `<tr><td>${esc(p.name)}</td><td class="text-end text-nowrap">${ok
                    ? `<s class="text-muted">₹${fmt(p.price)}</s> → <b class="text-success">₹${fmt(sp)}</b> <span class="text-success">(${pct}% off)</span>`
                    : `<span class="text-danger">will be skipped (₹${fmt(p.price)})</span>`}</td></tr>`;
            });
            const more = rows.length > 5 ? `<div class="text-muted small mt-1">+${rows.length - 5} more product(s)</div>` : '';
            box.innerHTML = `<div class="fw-semibold mb-1">During the sale</div><table>${rows.slice(0, 5).join('')}</table>${more}`;
        }

        function openSaleModal(s) {
            s = s || {};
            document.getElementById('saleModalTitle').textContent = s.id ? 'Edit Sale' : 'Start a Sale';
            document.getElementById('s_id').value = s.id || '';
            document.getElementById('s_label').value = s.label || 'Flash Sale';
            document.getElementById('s_type').value = s.discount_type || 'percent';
            document.getElementById('s_value').value = s.discount_value || '';
            document.getElementById('s_starts').value = s.starts_at || istNowLocal();
            document.getElementById('s_ends').value = s.ends_at || istNowLocal(24);
            document.getElementById('s_status').value = s.status || 'active';
            selectedIds = s.product_id ? [Number(s.product_id)] : [];
            document.getElementById('psSearch').value = '';
            togglePicker(false);
            syncSelection();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('saleModal')).show();
        }

        document.getElementById('saleForm').addEventListener('submit', function (e) {
            if (!selectedIds.length) {
                e.preventDefault();
                alert('Please choose at least one product.');
                togglePicker(true);
            }
        });

        function toggleSaleStatus(id, el) {
            fetch('product-sales.php', { method: 'POST', body: new URLSearchParams({ action: 'toggle_status', id: id, status: el.checked ? 'active' : 'inactive' }) })
                .then(r => r.json())
                .then(res => { if (!res.success) { el.checked = !el.checked; alert('Could not update'); } else { location.reload(); } })
                .catch(() => { el.checked = !el.checked; alert('Could not update'); });
        }

        // Live "time left" / "starts in" next to each status badge
        function tickLeft() {
            const now = Date.now() / 1000;
            document.querySelectorAll('.ps-left').forEach(el => {
                const target = Number(el.dataset.ends || el.dataset.starts);
                let s = Math.max(0, Math.floor(target - now));
                const d = Math.floor(s / 86400); s %= 86400;
                const h = Math.floor(s / 3600); s %= 3600;
                const m = Math.floor(s / 60); s %= 60;
                el.textContent = (el.dataset.ends ? 'ends in ' : 'starts in ') + (d ? d + 'd ' : '') + `${h}h ${m}m ${s}s`;
            });
        }
        tickLeft();
        setInterval(tickLeft, 1000);

        document.addEventListener('DOMContentLoaded', function () { document.body.style.opacity = 1; });
    </script>
</body>
</html>
