<?php
// wallets-manager.php - Admin Management for Customer Cash Wallets & Refund Reversals
session_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'services/WalletService.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);
$walletService = new WalletService($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = 'success';

// Handle Admin Reverse / Credit to Wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_wallet'])) {
    $targetUserId = intval($_POST['target_user_id'] ?? 0);
    $orderId = !empty($_POST['target_order_id']) ? intval($_POST['target_order_id']) : null;
    $amount = floatval($_POST['wallet_amount'] ?? 0);
    $actionType = trim($_POST['wallet_action'] ?? 'credit');
    $reason = trim($_POST['wallet_reason'] ?? 'Admin wallet adjustment');

    if ($targetUserId > 0 && $amount > 0) {
        if ($orderId && $actionType === 'credit') {
            // Reversal tied to an order
            $res = $walletService->reverseOrderToWallet($orderId, $amount, $reason, $_SESSION['user_id'] ?? null);
        } else {
            // Direct adjustment / reversal
            $res = $walletService->adminAdjust($targetUserId, $actionType, $amount, $reason, $_SESSION['user_id'] ?? null);
        }

        if ($res['success']) {
            $message = $res['message'];
        } else {
            $message = $res['error'];
            $message_type = 'danger';
        }
    } else {
        $message = 'Please select a valid customer and amount greater than ₹0.';
        $message_type = 'warning';
    }
}

// Fetch Wallet Stats Overview
$statsRes = $db->query("
    SELECT COUNT(*) as total_wallets,
           COALESCE(SUM(balance), 0) as total_circulating_balance,
           COALESCE(SUM(total_added), 0) as total_money_added,
           COALESCE(SUM(total_refunded), 0) as total_refunds_reversed,
           COALESCE(SUM(total_spent), 0) as total_spent
    FROM user_wallets
");
$stats = $statsRes ? $statsRes->fetch_assoc() : [
    'total_wallets' => 0,
    'total_circulating_balance' => 0.00,
    'total_money_added' => 0.00,
    'total_refunds_reversed' => 0.00,
    'total_spent' => 0.00
];

// Fetch Customers with their Wallet Balance & Coin Balance
$customersRes = $db->query("
    SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone,
           COALESCE(uw.balance, 0.00) as wallet_balance,
           COALESCE(uw.total_added, 0.00) as total_added,
           COALESCE(uw.total_refunded, 0.00) as total_refunded,
           COALESCE(uw.total_spent, 0.00) as total_spent,
           COALESCE(uc.balance, 0) as coin_balance
    FROM users u
    LEFT JOIN user_wallets uw ON u.id = uw.user_id
    LEFT JOIN user_coins uc ON u.id = uc.user_id
    ORDER BY COALESCE(uw.balance, 0.00) DESC, u.id DESC
");
$customersList = [];
if ($customersRes) {
    while ($row = $customersRes->fetch_assoc()) {
        $customersList[] = $row;
    }
}

// Fetch Recent Orders for dropdown reference
$recentOrdersRes = $db->query("
    SELECT id, order_number, user_id, customer_name, grand_total, status, payment_status
    FROM orders
    ORDER BY created_at DESC
    LIMIT 50
");
$recentOrders = [];
if ($recentOrdersRes) {
    while ($orow = $recentOrdersRes->fetch_assoc()) {
        $recentOrders[] = $orow;
    }
}

// Fetch Recent Wallet Transactions
$transactionsRes = $db->query("
    SELECT wt.*, u.username, u.email, u.first_name, u.last_name
    FROM wallet_transactions wt
    LEFT JOIN users u ON wt.user_id = u.id
    ORDER BY wt.created_at DESC
    LIMIT 35
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Wallets &amp; Refund Reversals | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-7">
                        <h1 class="h3 mb-1"><strong><i class="fas fa-wallet text-success me-2"></i>Customer Wallets &amp; Refund Reversals (₹)</strong></h1>
                        <p class="text-muted mb-0">Separate real money cash wallet system: manage balances, process order cancellation refund reversals, and audit logs.</p>
                    </div>
                    <div class="col-md-5 text-end">
                        <button class="btn btn-success fw-bold me-2" data-bs-toggle="modal" data-bs-target="#reverseWalletModal">
                            <i class="fas fa-rotate-left me-1"></i> Reverse / Credit to Wallet
                        </button>
                        <a href="coins-manager.php" class="btn btn-outline-warning text-dark fw-semibold">
                            <i class="fas fa-coins text-warning me-1"></i> SuperCoins Manager
                        </a>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card p-3 shadow-sm border-start border-success border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Circulating Wallet Cash</div>
                                    <div class="h3 mb-0 text-success fw-bold">₹<?php echo number_format($stats['total_circulating_balance'] ?? 0, 2); ?></div>
                                    <small class="text-muted">Available across all users</small>
                                </div>
                                <i class="fas fa-wallet fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 shadow-sm border-start border-primary border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Order Cancellation Refunds</div>
                                    <div class="h3 mb-0 text-primary fw-bold">₹<?php echo number_format($stats['total_refunds_reversed'] ?? 0, 2); ?></div>
                                    <small class="text-success"><i class="fas fa-rotate-left me-1"></i>Reversed to Wallets</small>
                                </div>
                                <i class="fas fa-undo-alt fa-2x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 shadow-sm border-start border-info border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Total Money Added by Users</div>
                                    <div class="h3 mb-0 text-info fw-bold">₹<?php echo number_format($stats['total_money_added'] ?? 0, 2); ?></div>
                                    <small class="text-muted">Direct top-ups</small>
                                </div>
                                <i class="fas fa-arrow-down-to-bracket fa-2x text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 shadow-sm border-start border-warning border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small">Active Customer Wallets</div>
                                    <div class="h3 mb-0 text-warning fw-bold"><?php echo number_format($stats['total_wallets'] ?? 0); ?></div>
                                    <small class="text-muted">Registered accounts</small>
                                </div>
                                <i class="fas fa-users-viewfinder fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Reversal & Adjustment Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-hand-holding-dollar me-2 text-success"></i>Reverse Payment / Credit Money to Customer Wallet</h5>
                        <span class="badge bg-success">Instant Reversal Engine</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Select Customer *</label>
                                    <select name="target_user_id" id="quick_user_select" class="form-select" required>
                                        <option value="">-- Choose Customer --</option>
                                        <?php foreach ($customersList as $c): ?>
                                            <?php 
                                                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                                                if (empty($name)) $name = $c['username'];
                                            ?>
                                            <option value="<?php echo $c['id']; ?>">
                                                <?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($c['email']); ?>) - Balance: ₹<?php echo number_format($c['wallet_balance'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Related Order (Optional)</label>
                                    <select name="target_order_id" class="form-select">
                                        <option value="">-- None / General Reversal --</option>
                                        <?php foreach ($recentOrders as $ro): ?>
                                            <option value="<?php echo $ro['id']; ?>">
                                                Order #<?php echo htmlspecialchars($ro['order_number']); ?> (₹<?php echo number_format($ro['grand_total'], 2); ?> - <?php echo ucfirst($ro['status']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Auto-updates order payment status</small>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Action *</label>
                                    <select name="wallet_action" class="form-select" required>
                                        <option value="credit">Credit (+) Reverse / Add</option>
                                        <option value="debit">Debit (−) Deduct</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Amount (₹) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" name="wallet_amount" class="form-control" placeholder="0.00" required min="1">
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <label class="form-label fw-bold">Reason / Note</label>
                                    <input type="text" name="wallet_reason" class="form-control" placeholder="e.g. Order #PM-12345 cancelled, refund reversed to customer wallet" value="Order cancellation refund reversed to wallet">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" name="adjust_wallet" class="btn btn-success fw-bold w-100">
                                        <i class="fas fa-check-circle me-1"></i> Submit Reversal
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customer Wallets List Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0"><i class="fas fa-users me-2 text-primary"></i>All Customer Cash Wallets</h5>
                            <small class="text-muted">Separate cash balances in ₹ (distinct from reward coins).</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Wallet Balance (₹)</th>
                                        <th>Total Added (₹)</th>
                                        <th>Refunds Reversed (₹)</th>
                                        <th>Total Spent (₹)</th>
                                        <th>SuperCoins</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customersList)): ?>
                                        <?php foreach ($customersList as $c): ?>
                                            <?php 
                                                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                                                if (empty($name)) $name = $c['username'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($name); ?></div>
                                                    <small class="text-muted">User ID: #<?php echo $c['id']; ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($c['email']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($c['phone'] ?: 'N/A'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success fs-6 px-3 py-1 fw-bold">
                                                        ₹<?php echo number_format($c['wallet_balance'], 2); ?>
                                                    </span>
                                                </td>
                                                <td class="text-info fw-semibold">₹<?php echo number_format($c['total_added'], 2); ?></td>
                                                <td class="text-primary fw-semibold">₹<?php echo number_format($c['total_refunded'], 2); ?></td>
                                                <td class="text-danger fw-semibold">₹<?php echo number_format($c['total_spent'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning fw-bold">
                                                        <i class="fas fa-coins text-warning me-1"></i><?php echo number_format($c['coin_balance']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-success fw-bold btn-quick-reverse"
                                                            data-user-id="<?php echo $c['id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($name); ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#reverseWalletModal">
                                                        <i class="fas fa-rotate-left me-1"></i> Reverse / Credit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">No customer wallets found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Wallet Transactions History -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-history me-2 text-primary"></i>Wallet Passbook &amp; Reversal Audit Log</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>Customer</th>
                                        <th>Category</th>
                                        <th>Title</th>
                                        <th>Amount (₹)</th>
                                        <th>Balance After</th>
                                        <th>Order Ref</th>
                                        <th>Reference ID</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactionsRes && $transactionsRes->num_rows > 0): ?>
                                        <?php while ($tx = $transactionsRes->fetch_assoc()): ?>
                                            <?php 
                                                $isCredit = $tx['type'] === 'credit';
                                                $custName = trim(($tx['first_name'] ?? '') . ' ' . ($tx['last_name'] ?? ''));
                                                if (empty($custName)) $custName = $tx['username'] ?: 'User #' . $tx['user_id'];
                                                $badgeClass = 'bg-secondary';
                                                if ($tx['category'] === 'refund_reversal') $badgeClass = 'bg-primary';
                                                if ($tx['category'] === 'topup') $badgeClass = 'bg-success';
                                                if ($tx['category'] === 'order_payment') $badgeClass = 'bg-danger';
                                            ?>
                                            <tr>
                                                <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($tx['created_at'])); ?></td>
                                                <td>
                                                    <div class="fw-bold small"><?php echo htmlspecialchars($custName); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tx['email'] ?? ''); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo strtoupper(str_replace('_', ' ', $tx['category'])); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold">
                                                    <?php echo htmlspecialchars($tx['title']); ?>
                                                    <?php if (!empty($tx['description'])): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($tx['description']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold <?php echo $isCredit ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $isCredit ? '+' : '−'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                                </td>
                                                <td class="small fw-semibold">₹<?php echo number_format($tx['balance_after'], 2); ?></td>
                                                <td>
                                                    <?php if (!empty($tx['order_number'])): ?>
                                                        <span class="badge bg-dark font-monospace"><?php echo htmlspecialchars($tx['order_number']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted font-monospace"><?php echo htmlspecialchars($tx['reference_id'] ?? '—'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $tx['status'] === 'success' ? 'success' : 'warning'; ?>">
                                                        <?php echo strtoupper($tx['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center py-4 text-muted">No wallet transactions logged yet.</td></tr>
                                    <?php endif; ?>
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

<!-- Modal for Reversal / Credit -->
<div class="modal fade" id="reverseWalletModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold text-white"><i class="fas fa-rotate-left me-2"></i>Reverse / Credit Money to Wallet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Customer *</label>
                    <select name="target_user_id" id="modal_wallet_user_id" class="form-select" required>
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customersList as $c): ?>
                            <?php 
                                $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                                if (empty($name)) $name = $c['username'];
                            ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($c['email']); ?>) - Balance: ₹<?php echo number_format($c['wallet_balance'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Action *</label>
                        <select name="wallet_action" class="form-select" required>
                            <option value="credit">Credit (+) Reverse / Refund</option>
                            <option value="debit">Debit (−) Deduct</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Amount (₹) *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="wallet_amount" class="form-control" required min="1" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Related Order (Optional)</label>
                    <select name="target_order_id" class="form-select">
                        <option value="">-- None / General Adjustment --</option>
                        <?php foreach ($recentOrders as $ro): ?>
                            <option value="<?php echo $ro['id']; ?>">
                                Order #<?php echo htmlspecialchars($ro['order_number']); ?> (₹<?php echo number_format($ro['grand_total'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason / Note</label>
                    <input type="text" name="wallet_reason" class="form-control" value="Order cancellation payment reversed to wallet">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="adjust_wallet" class="btn btn-success fw-bold">Process Wallet Reversal</button>
            </div>
        </form>
    </div>
</div>

<script src="js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-quick-reverse').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var uid = this.getAttribute('data-user-id');
            var select = document.getElementById('modal_wallet_user_id');
            if (select && uid) {
                select.value = uid;
            }
        });
    });
});
</script>
</body>
</html>
