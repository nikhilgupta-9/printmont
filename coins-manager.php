<?php
// printmont-backend/coins-manager.php - Enterprise SuperCoins & Loyalty Manager
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'services/CoinService.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);
$coinService = new CoinService($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// ============================================================================
// AJAX ENDPOINT: High-Performance Live User Search (Scalable for 10,000+ users)
// ============================================================================
if (isset($_GET['ajax_search_users'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    $results = [];

    if (strlen($q) >= 1) {
        $searchTerm = "%{$q}%";
        $intId = intval($q);
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone,
                   COALESCE(uc.balance, 0) as balance,
                   COALESCE(uc.tier, 'Silver Member') as tier
            FROM users u
            LEFT JOIN user_coins uc ON u.id = uc.user_id
            WHERE u.first_name LIKE ? 
               OR u.last_name LIKE ? 
               OR u.email LIKE ? 
               OR u.phone LIKE ? 
               OR u.username LIKE ? 
               OR u.id = ?
            ORDER BY u.id DESC
            LIMIT 15
        ");
        $stmt->bind_param("sssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $intId);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if (empty($name)) $name = $row['username'];
            $results[] = [
                'id' => (int)$row['id'],
                'name' => $name,
                'email' => $row['email'],
                'phone' => $row['phone'] ?: 'N/A',
                'balance' => (int)$row['balance'],
                'tier' => $row['tier']
            ];
        }
    }

    echo json_encode($results);
    exit();
}

$message = '';
$message_type = 'success';

// Active Tab Selection (URL-driven)
$activeTab = trim($_GET['tab'] ?? 'overview');
$validTabs = ['overview', 'users', 'transactions', 'rewards', 'settings'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'overview';
}

// ============================================================================
// ACTION HANDLERS
// ============================================================================

// 1. Update Settings
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_settings'])) {
    $coinsPer100 = intval($_POST['coins_per_100_spent'] ?? 2);
    $maxCoins = intval($_POST['max_coins_per_order'] ?? 50);
    $coinVal = floatval($_POST['coin_value_inr'] ?? 1.00);
    $welcomeBonus = intval($_POST['welcome_bonus_coins'] ?? 100);
    $reviewBonus = intval($_POST['review_bonus_coins'] ?? 25);
    $minOrderAmt = floatval($_POST['min_order_amount_for_coins'] ?? 100.00);
    $enableWelcome = isset($_POST['enable_welcome_bonus']) ? 1 : 0;
    $enablePurchase = isset($_POST['enable_purchase_coins']) ? 1 : 0;

    $upd = $db->prepare("UPDATE coin_settings SET coins_per_100_spent = ?, max_coins_per_order = ?, coin_value_inr = ?, welcome_bonus_coins = ?, review_bonus_coins = ?, min_order_amount_for_coins = ?, enable_welcome_bonus = ?, enable_purchase_coins = ? WHERE id = 1");
    $upd->bind_param("iididiii", $coinsPer100, $maxCoins, $coinVal, $welcomeBonus, $reviewBonus, $minOrderAmt, $enableWelcome, $enablePurchase);
    if ($upd->execute()) {
        $message = 'Coin earning rules & welcome bonus updated successfully!';
    } else {
        $message = 'Failed to update settings: ' . $db->error;
        $message_type = 'danger';
    }
}

// 2. Adjust User Coins (Manual Admin Grant / Deduction)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['adjust_user_coins'])) {
    $targetUserId = intval($_POST['target_user_id'] ?? 0);
    $amount = intval($_POST['coin_amount'] ?? 0);
    $adjType = trim($_POST['adjustment_type'] ?? 'credit');
    $reason = trim($_POST['adjustment_reason'] ?? 'Admin manual loyalty adjustment');

    if ($targetUserId > 0 && $amount > 0) {
        $res = $coinService->adjustCoins($targetUserId, $adjType, $amount, $reason, $_SESSION['user_id'] ?? null);
        if ($res['success']) {
            $message = $res['message'];
        } else {
            $message = $res['error'];
            $message_type = 'danger';
        }
    } else {
        $message = 'Please select a valid user and enter a coin amount greater than 0.';
        $message_type = 'warning';
    }
}

// 3. Add Reward Voucher
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['add_reward'])) {
    $title = trim($_POST['title'] ?? '');
    $coinsReq = intval($_POST['coins_required'] ?? 100);
    $discAmt = floatval($_POST['discount_amount'] ?? 100);
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $cat = trim($_POST['category'] ?? 'General');

    if (!empty($title) && !empty($code)) {
        $ins = $db->prepare("INSERT INTO coin_rewards (title, coins_required, discount_amount, coupon_code, description, category, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $ins->bind_param("sidsss", $title, $coinsReq, $discAmt, $code, $desc, $cat);
        if ($ins->execute()) {
            $message = "Reward voucher '{$code}' added successfully!";
        } else {
            $message = 'Failed to add reward: ' . $db->error;
            $message_type = 'danger';
        }
    }
}

// 4. Toggle Reward Voucher Status
if (isset($_GET['toggle_reward'])) {
    $rewId = intval($_GET['toggle_reward']);
    $toggleStmt = $db->prepare("UPDATE coin_rewards SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
    $toggleStmt->bind_param("i", $rewId);
    $toggleStmt->execute();
    $message = "Voucher status toggled.";
}

// 5. Delete Reward Voucher
if (isset($_GET['delete_reward'])) {
    $rewId = intval($_GET['delete_reward']);
    $del = $db->prepare("DELETE FROM coin_rewards WHERE id = ?");
    $del->bind_param("i", $rewId);
    $del->execute();
    $message = "Reward voucher removed.";
}

// ============================================================================
// GLOBAL METRICS & SETTINGS (Lightweight cached/aggregate stats)
// ============================================================================
$settings = $coinService->getSettings();

$statsRes = $db->query("
    SELECT COUNT(*) as total_users,
           COALESCE(SUM(balance), 0) as total_active_coins,
           COALESCE(SUM(lifetime_earned), 0) as total_earned,
           COALESCE(SUM(lifetime_spent), 0) as total_redeemed
    FROM user_coins
");
$coinStats = $statsRes ? $statsRes->fetch_assoc() : [
    'total_users' => 0,
    'total_active_coins' => 0,
    'total_earned' => 0,
    'total_redeemed' => 0
];

// Total registered platform users count
$totalPlatformUsers = (int)($db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0);
$totalActiveRewards = (int)($db->query("SELECT COUNT(*) as c FROM coin_rewards WHERE is_active = 1")->fetch_assoc()['c'] ?? 0);
$totalTransactionsCount = (int)($db->query("SELECT COUNT(*) as c FROM coin_transactions")->fetch_assoc()['c'] ?? 0);

// ============================================================================
// TAB 1: OVERVIEW TAB DATA
// ============================================================================
$recentTransactions = [];
$topRewards = [];
$tierBreakdown = [];

if ($activeTab === 'overview') {
    // Recent 10 transactions
    $recentTxRes = $db->query("
        SELECT ct.*, u.username, u.email, u.first_name, u.last_name
        FROM coin_transactions ct
        LEFT JOIN users u ON ct.user_id = u.id
        ORDER BY ct.created_at DESC
        LIMIT 10
    ");
    if ($recentTxRes) {
        while ($trow = $recentTxRes->fetch_assoc()) {
            $recentTransactions[] = $trow;
        }
    }

    // Top rewards
    $topRewardsRes = $db->query("SELECT * FROM coin_rewards ORDER BY coins_required ASC LIMIT 6");
    if ($topRewardsRes) {
        while ($rrow = $topRewardsRes->fetch_assoc()) {
            $topRewards[] = $rrow;
        }
    }

    // Tier distribution
    $tierRes = $db->query("SELECT tier, COUNT(*) as cnt FROM user_coins GROUP BY tier");
    if ($tierRes) {
        while ($trow = $tierRes->fetch_assoc()) {
            $tierBreakdown[$trow['tier'] ?: 'Silver Member'] = (int)$trow['cnt'];
        }
    }
}

// ============================================================================
// TAB 2: PAGINATED & SEARCHABLE USERS TAB DATA
// ============================================================================
$userPage = max(1, intval($_GET['page'] ?? 1));
$userLimit = in_array(intval($_GET['limit'] ?? 20), [10, 20, 50, 100]) ? intval($_GET['limit'] ?? 20) : 20;
$userOffset = ($userPage - 1) * $userLimit;

$userSearch = trim($_GET['user_search'] ?? '');
$userTier = trim($_GET['user_tier'] ?? '');
$userSort = trim($_GET['user_sort'] ?? 'balance_desc');
$userBalanceFilter = trim($_GET['balance_filter'] ?? '');

$usersList = [];
$totalUserMatches = 0;
$userTotalPages = 1;

if ($activeTab === 'users') {
    $whereParts = ["1=1"];
    $bindTypes = "";
    $bindVals = [];

    if (!empty($userSearch)) {
        $whereParts[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.username LIKE ? OR u.id = ?)";
        $sTerm = "%{$userSearch}%";
        $intId = intval($userSearch);
        $bindTypes .= "sssssi";
        $bindVals[] = $sTerm;
        $bindVals[] = $sTerm;
        $bindVals[] = $sTerm;
        $bindVals[] = $sTerm;
        $bindVals[] = $sTerm;
        $bindVals[] = $intId;
    }

    if (!empty($userTier)) {
        $whereParts[] = "uc.tier = ?";
        $bindTypes .= "s";
        $bindVals[] = $userTier;
    }

    if ($userBalanceFilter === 'positive') {
        $whereParts[] = "uc.balance > 0";
    } elseif ($userBalanceFilter === 'zero') {
        $whereParts[] = "(uc.balance IS NULL OR uc.balance = 0)";
    }

    $whereSQL = implode(" AND ", $whereParts);

    // Count matching users
    $countQuery = "
        SELECT COUNT(*) as cnt
        FROM users u
        LEFT JOIN user_coins uc ON u.id = uc.user_id
        WHERE {$whereSQL}
    ";
    $cStmt = $db->prepare($countQuery);
    if (!empty($bindTypes)) {
        $cStmt->bind_param($bindTypes, ...$bindVals);
    }
    $cStmt->execute();
    $totalUserMatches = (int)($cStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $userTotalPages = max(1, ceil($totalUserMatches / $userLimit));

    // Sort order mapping
    $orderSQL = "COALESCE(uc.balance, 0) DESC, u.id DESC";
    if ($userSort === 'balance_asc') {
        $orderSQL = "COALESCE(uc.balance, 0) ASC, u.id DESC";
    } elseif ($userSort === 'earned_desc') {
        $orderSQL = "COALESCE(uc.lifetime_earned, 0) DESC";
    } elseif ($userSort === 'spent_desc') {
        $orderSQL = "COALESCE(uc.lifetime_spent, 0) DESC";
    } elseif ($userSort === 'newest') {
        $orderSQL = "u.id DESC";
    }

    $query = "
        SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone, u.created_at as registered_at,
               COALESCE(uc.balance, 0) as balance,
               COALESCE(uc.tier, 'Silver Member') as tier,
               COALESCE(uc.lifetime_earned, 0) as lifetime_earned,
               COALESCE(uc.lifetime_spent, 0) as lifetime_spent,
               COALESCE(uw.balance, 0.00) as wallet_balance
        FROM users u
        LEFT JOIN user_coins uc ON u.id = uc.user_id
        LEFT JOIN user_wallets uw ON u.id = uw.user_id
        WHERE {$whereSQL}
        ORDER BY {$orderSQL}
        LIMIT ?, ?
    ";
    $uStmt = $db->prepare($query);
    $userBindTypes = $bindTypes . "ii";
    $userBindVals = array_merge($bindVals, [$userOffset, $userLimit]);
    $uStmt->bind_param($userBindTypes, ...$userBindVals);
    $uStmt->execute();
    $uRes = $uStmt->get_result();

    while ($row = $uRes->fetch_assoc()) {
        $usersList[] = $row;
    }
}

// ============================================================================
// TAB 3: PAGINATED & FILTERABLE TRANSACTIONS TAB DATA
// ============================================================================
$txPage = max(1, intval($_GET['tx_page'] ?? 1));
$txLimit = in_array(intval($_GET['tx_limit'] ?? 25), [15, 25, 50, 100]) ? intval($_GET['tx_limit'] ?? 25) : 25;
$txOffset = ($txPage - 1) * $txLimit;

$txTypeFilter = trim($_GET['tx_type'] ?? '');
$txUserFilter = intval($_GET['user_id'] ?? 0);
$txSearch = trim($_GET['tx_search'] ?? '');

$transactionsList = [];
$totalTxMatches = 0;
$txTotalPages = 1;

if ($activeTab === 'transactions') {
    $txWhere = ["1=1"];
    $txBindTypes = "";
    $txBindVals = [];

    if (!empty($txTypeFilter) && in_array($txTypeFilter, ['credit', 'debit'])) {
        $txWhere[] = "ct.type = ?";
        $txBindTypes .= "s";
        $txBindVals[] = $txTypeFilter;
    }

    if ($txUserFilter > 0) {
        $txWhere[] = "ct.user_id = ?";
        $txBindTypes .= "i";
        $txBindVals[] = $txUserFilter;
    }

    if (!empty($txSearch)) {
        $txWhere[] = "(ct.title LIKE ? OR ct.description LIKE ? OR ct.order_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $sTerm = "%{$txSearch}%";
        $txBindTypes .= "ssssss";
        $txBindVals[] = $sTerm;
        $txBindVals[] = $sTerm;
        $txBindVals[] = $sTerm;
        $txBindVals[] = $sTerm;
        $txBindVals[] = $sTerm;
        $txBindVals[] = $sTerm;
    }

    $txWhereSQL = implode(" AND ", $txWhere);

    // Count
    $txCountStmt = $db->prepare("
        SELECT COUNT(*) as cnt
        FROM coin_transactions ct
        LEFT JOIN users u ON ct.user_id = u.id
        WHERE {$txWhereSQL}
    ");
    if (!empty($txBindTypes)) {
        $txCountStmt->bind_param($txBindTypes, ...$txBindVals);
    }
    $txCountStmt->execute();
    $totalTxMatches = (int)($txCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $txTotalPages = max(1, ceil($totalTxMatches / $txLimit));

    // Fetch Paginated
    $txStmt = $db->prepare("
        SELECT ct.*, u.username, u.email, u.first_name, u.last_name, u.phone
        FROM coin_transactions ct
        LEFT JOIN users u ON ct.user_id = u.id
        WHERE {$txWhereSQL}
        ORDER BY ct.created_at DESC
        LIMIT ?, ?
    ");
    $txFullBindTypes = $txBindTypes . "ii";
    $txFullBindVals = array_merge($txBindVals, [$txOffset, $txLimit]);
    $txStmt->bind_param($txFullBindTypes, ...$txFullBindVals);
    $txStmt->execute();
    $txRes = $txStmt->get_result();

    while ($trow = $txRes->fetch_assoc()) {
        $transactionsList[] = $trow;
    }
}

// ============================================================================
// TAB 4: REWARD VOUCHERS TAB DATA
// ============================================================================
$allRewards = [];
if ($activeTab === 'rewards') {
    $rewRes = $db->query("SELECT * FROM coin_rewards ORDER BY is_active DESC, coins_required ASC");
    if ($rewRes) {
        while ($r = $rewRes->fetch_assoc()) {
            $allRewards[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SuperCoins Loyalty Manager | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        .badge-coin {
            background: linear-gradient(135deg, #fef08a, #f59e0b);
            color: #78350f;
            font-weight: 700;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #64748b;
            padding: 12px 20px;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.15s ease-in-out;
        }
        .nav-tabs .nav-link:hover {
            color: #0b53a1;
            border-bottom-color: #cbd5e1;
        }
        .nav-tabs .nav-link.active {
            color: #0b53a1;
            border-bottom: 3px solid #0b53a1;
            background: transparent;
        }
        .kpi-card {
            border-radius: 12px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        .user-avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e0f2fe;
            color: #0284c7;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .live-search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 280px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 1060;
            display: none;
        }
        .live-search-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s ease;
        }
        .live-search-item:hover {
            background: #f8fafc;
        }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>

        <main class="content">
            <div class="container-fluid p-0">

                <!-- Page Header with Context & Instant Modal Trigger -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <div>
                        <h1 class="h3 mb-1">
                            <strong><i class="fas fa-coins text-warning me-2"></i>SuperCoins Loyalty Manager</strong>
                        </h1>
                        <p class="text-muted mb-0 small">
                            High-scale management for <strong><?php echo number_format($totalPlatformUsers); ?></strong> users, instant coin grants, reward vouchers &amp; full transaction ledger.
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                        <button class="btn btn-warning fw-bold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#giveCoinsModal">
                            <i class="fas fa-gift me-1"></i> Give / Adjust Coins
                        </button>
                        <a href="wallets-manager.php" class="btn btn-outline-primary shadow-sm">
                            <i class="fas fa-wallet me-1"></i> Customer Wallets (₹)
                        </a>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Primary Modular Navigation Tabs -->
                <ul class="nav nav-tabs mb-4 bg-white rounded shadow-sm px-3 pt-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" href="coins-manager.php?tab=overview">
                            <i class="fas fa-chart-pie me-2"></i>Overview &amp; KPIs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>" href="coins-manager.php?tab=users">
                            <i class="fas fa-users me-2"></i>Customer Wallets
                            <span class="badge bg-secondary ms-1"><?php echo number_format($coinStats['total_users']); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeTab === 'transactions' ? 'active' : ''; ?>" href="coins-manager.php?tab=transactions">
                            <i class="fas fa-history me-2"></i>Audit Ledger
                            <span class="badge bg-light text-dark ms-1"><?php echo number_format($totalTransactionsCount); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeTab === 'rewards' ? 'active' : ''; ?>" href="coins-manager.php?tab=rewards">
                            <i class="fas fa-ticket-alt me-2"></i>Reward Vouchers
                            <span class="badge bg-success ms-1"><?php echo $totalActiveRewards; ?> Active</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" href="coins-manager.php?tab=settings">
                            <i class="fas fa-sliders-h me-2"></i>Earning Rules
                        </a>
                    </li>
                </ul>

                <!-- ========================================================== -->
                <!-- TAB 1: OVERVIEW & KPIS -->
                <!-- ========================================================== -->
                <?php if ($activeTab === 'overview'): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="card kpi-card shadow-sm border-start border-warning border-4 p-3 mb-0 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Active Circulating Coins</div>
                                        <div class="h2 mb-0 text-warning fw-bold mt-1"><?php echo number_format($coinStats['total_active_coins'] ?? 0); ?></div>
                                        <div class="small text-muted mt-1">
                                            ₹<?php echo number_format(($coinStats['total_active_coins'] ?? 0) * ($settings['coin_value_inr'] ?? 1), 2); ?> Liability Value
                                        </div>
                                    </div>
                                    <div class="p-3 bg-warning-subtle rounded-circle text-warning">
                                        <i class="fas fa-coins fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card kpi-card shadow-sm border-start border-primary border-4 p-3 mb-0 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">First-Time Welcome Bonus</div>
                                        <div class="h2 mb-0 text-primary fw-bold mt-1"><?php echo number_format($settings['welcome_bonus_coins']); ?> <span class="fs-6 fw-normal">Coins</span></div>
                                        <div class="small text-success mt-1">
                                            <i class="fas fa-check-circle me-1"></i><?php echo $settings['enable_welcome_bonus'] ? 'Auto-Credit Active' : 'Disabled'; ?>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-primary-subtle rounded-circle text-primary">
                                        <i class="fas fa-user-plus fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card kpi-card shadow-sm border-start border-success border-4 p-3 mb-0 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Purchase Earn Rate</div>
                                        <div class="h2 mb-0 text-success fw-bold mt-1"><?php echo $settings['coins_per_100_spent']; ?> <span class="fs-6 fw-normal">/ ₹100</span></div>
                                        <div class="small text-muted mt-1">
                                            Cap: <?php echo $settings['max_coins_per_order']; ?> coins/order
                                        </div>
                                    </div>
                                    <div class="p-3 bg-success-subtle rounded-circle text-success">
                                        <i class="fas fa-shopping-bag fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-xl-3">
                            <div class="card kpi-card shadow-sm border-start border-info border-4 p-3 mb-0 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small fw-semibold">Total Redeemed Coins</div>
                                        <div class="h2 mb-0 text-info fw-bold mt-1"><?php echo number_format($coinStats['total_redeemed'] ?? 0); ?></div>
                                        <div class="small text-muted mt-1">
                                            Across <?php echo $totalActiveRewards; ?> active vouchers
                                        </div>
                                    </div>
                                    <div class="p-3 bg-info-subtle rounded-circle text-info">
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Navigation & Feature Highlights -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-8">
                            <!-- Recent Activity Preview -->
                            <div class="card shadow-sm mb-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                                    <h5 class="card-title mb-0"><i class="fas fa-history text-primary me-2"></i>Recent Coin Transactions</h5>
                                    <a href="coins-manager.php?tab=transactions" class="btn btn-sm btn-outline-primary">
                                        View Full Ledger (<?php echo number_format($totalTransactionsCount); ?>) &rarr;
                                    </a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Customer</th>
                                                    <th>Event / Description</th>
                                                    <th>Coins</th>
                                                    <th>Ref Order</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($recentTransactions)): ?>
                                                    <?php foreach ($recentTransactions as $tx): ?>
                                                        <?php 
                                                            $isCredit = $tx['type'] === 'credit';
                                                            $custName = trim(($tx['first_name'] ?? '') . ' ' . ($tx['last_name'] ?? ''));
                                                            if (empty($custName)) $custName = $tx['username'] ?: 'User #' . $tx['user_id'];
                                                        ?>
                                                        <tr>
                                                            <td class="small text-muted"><?php echo date('d M, h:i A', strtotime($tx['created_at'])); ?></td>
                                                            <td>
                                                                <a href="coins-manager.php?tab=transactions&user_id=<?php echo $tx['user_id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                                    <?php echo htmlspecialchars($custName); ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div class="fw-semibold small"><?php echo htmlspecialchars($tx['title']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($tx['description'] ?? ''); ?></small>
                                                            </td>
                                                            <td class="fw-bold <?php echo $isCredit ? 'text-success' : 'text-danger'; ?>">
                                                                <?php echo $isCredit ? '+' : '−'; ?><?php echo number_format($tx['amount']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($tx['order_id'])): ?>
                                                                    <span class="badge bg-dark font-monospace"><?php echo htmlspecialchars($tx['order_id']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="5" class="text-center py-4 text-muted">No coin transactions logged yet.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Quick Shortcuts Card -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-light py-3">
                                    <h5 class="card-title mb-0"><i class="fas fa-bolt text-warning me-2"></i>Quick Management</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-warning text-dark fw-bold py-2 text-start" data-bs-toggle="modal" data-bs-target="#giveCoinsModal">
                                            <i class="fas fa-gift me-2"></i> Give / Deduct Coins for User
                                        </button>
                                        <a href="coins-manager.php?tab=users" class="btn btn-outline-primary py-2 text-start">
                                            <i class="fas fa-search me-2"></i> Search Customer Coin Balances
                                        </a>
                                        <button class="btn btn-outline-success py-2 text-start" data-bs-toggle="modal" data-bs-target="#addRewardModal">
                                            <i class="fas fa-plus-circle me-2"></i> Create SuperCoin Voucher
                                        </button>
                                        <a href="coins-manager.php?tab=settings" class="btn btn-outline-secondary py-2 text-start">
                                            <i class="fas fa-cog me-2"></i> Change Welcome Bonus &amp; Rates
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Loyalty Tiers Breakdown Card -->
                            <div class="card shadow-sm mb-0">
                                <div class="card-header bg-light py-3">
                                    <h5 class="card-title mb-0"><i class="fas fa-crown text-warning me-2"></i>Loyalty Tiers Breakdown</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span><i class="fas fa-medal text-secondary me-2"></i>Silver Members</span>
                                        <span class="badge bg-secondary rounded-pill"><?php echo number_format($tierBreakdown['Silver Member'] ?? 0); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span><i class="fas fa-medal text-warning me-2"></i>Gold Members</span>
                                        <span class="badge bg-warning text-dark rounded-pill"><?php echo number_format($tierBreakdown['Gold Member'] ?? 0); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-gem text-info me-2"></i>Platinum Members</span>
                                        <span class="badge bg-info rounded-pill"><?php echo number_format($tierBreakdown['Platinum Member'] ?? 0); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ========================================================== -->
                <!-- TAB 2: CUSTOMER COIN WALLETS (Paginated & Searchable) -->
                <!-- ========================================================== -->
                <?php if ($activeTab === 'users'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <form method="GET" class="row g-2 align-items-center">
                                <input type="hidden" name="tab" value="users">
                                
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" name="user_search" class="form-control" placeholder="Search name, email, phone, ID..." value="<?php echo htmlspecialchars($userSearch); ?>">
                                    </div>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <select name="user_tier" class="form-select">
                                        <option value="">All Tiers</option>
                                        <option value="Silver Member" <?php echo $userTier === 'Silver Member' ? 'selected' : ''; ?>>Silver Member</option>
                                        <option value="Gold Member" <?php echo $userTier === 'Gold Member' ? 'selected' : ''; ?>>Gold Member</option>
                                        <option value="Platinum Member" <?php echo $userTier === 'Platinum Member' ? 'selected' : ''; ?>>Platinum Member</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <select name="balance_filter" class="form-select">
                                        <option value="">All Balances</option>
                                        <option value="positive" <?php echo $userBalanceFilter === 'positive' ? 'selected' : ''; ?>>Coins &gt; 0</option>
                                        <option value="zero" <?php echo $userBalanceFilter === 'zero' ? 'selected' : ''; ?>>0 Coins</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-sm-6">
                                    <select name="user_sort" class="form-select">
                                        <option value="balance_desc" <?php echo $userSort === 'balance_desc' ? 'selected' : ''; ?>>Coins: High &rarr; Low</option>
                                        <option value="balance_asc" <?php echo $userSort === 'balance_asc' ? 'selected' : ''; ?>>Coins: Low &rarr; High</option>
                                        <option value="earned_desc" <?php echo $userSort === 'earned_desc' ? 'selected' : ''; ?>>Lifetime Earned</option>
                                        <option value="newest" <?php echo $userSort === 'newest' ? 'selected' : ''; ?>>Newest Users</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-sm-6 d-flex gap-1">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                                    <?php if (!empty($userSearch) || !empty($userTier) || !empty($userBalanceFilter) || $userSort !== 'balance_desc'): ?>
                                        <a href="coins-manager.php?tab=users" class="btn btn-outline-secondary" title="Reset Filters"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light-subtle border-bottom small text-muted">
                                <div>
                                    Showing <strong><?php echo number_format(count($usersList)); ?></strong> of <strong><?php echo number_format($totalUserMatches); ?></strong> matching customers
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span>Per Page:</span>
                                    <select class="form-select form-select-sm w-auto" onchange="location.href='?<?php echo http_build_query(array_merge($_GET, ['page' => 1, 'limit' => ''])); ?>'+this.value">
                                        <option value="10" <?php echo $userLimit == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo $userLimit == 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $userLimit == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $userLimit == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Coin Balance</th>
                                            <th>Cash Wallet</th>
                                            <th>Lifetime Earned</th>
                                            <th>Lifetime Spent</th>
                                            <th>Tier</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($usersList)): ?>
                                            <?php foreach ($usersList as $u): ?>
                                                <?php 
                                                    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                                                    if (empty($name)) $name = $u['username'];
                                                    $initials = strtoupper(substr($name, 0, 1));
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="user-avatar-initials"><?php echo $initials; ?></div>
                                                            <div>
                                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($name); ?></div>
                                                                <small class="text-muted">User ID: #<?php echo $u['id']; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="small"><?php echo htmlspecialchars($u['email']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($u['phone'] ?: 'No phone'); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-coin px-2 py-1 fs-6">
                                                            <i class="fas fa-coins me-1"></i><?php echo number_format($u['balance']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                                                            ₹<?php echo number_format($u['wallet_balance'], 2); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-success fw-semibold small">+<?php echo number_format($u['lifetime_earned']); ?></td>
                                                    <td class="text-danger fw-semibold small">−<?php echo number_format($u['lifetime_spent']); ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            <i class="fas fa-crown text-warning me-1"></i><?php echo htmlspecialchars($u['tier']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-warning text-dark fw-bold btn-quick-gift"
                                                                    data-user-id="<?php echo $u['id']; ?>"
                                                                    data-user-name="<?php echo htmlspecialchars($name); ?>"
                                                                    data-user-email="<?php echo htmlspecialchars($u['email']); ?>"
                                                                    data-user-balance="<?php echo $u['balance']; ?>"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#giveCoinsModal"
                                                                    title="Credit or deduct coins">
                                                                <i class="fas fa-gift me-1"></i> Adjust
                                                            </button>
                                                            <a href="coins-manager.php?tab=transactions&user_id=<?php echo $u['id']; ?>" 
                                                               class="btn btn-outline-secondary" 
                                                               title="View transaction ledger for this customer">
                                                                <i class="fas fa-list-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="fas fa-search fa-2x mb-2 d-block opacity-50"></i>
                                                    No customers found matching your search filters.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Users Server-Side Pagination Bar -->
                            <?php if ($userTotalPages > 1): ?>
                                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                    <div class="small text-muted">
                                        Page <?php echo $userPage; ?> of <?php echo $userTotalPages; ?>
                                    </div>
                                    <nav aria-label="Users pagination">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?php echo $userPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" title="First Page">
                                                    <i class="fas fa-angles-left"></i>
                                                </a>
                                            </li>
                                            <li class="page-item <?php echo $userPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $userPage - 1)])); ?>" title="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>

                                            <?php
                                                $startP = max(1, $userPage - 2);
                                                $endP = min($userTotalPages, $userPage + 2);
                                                for ($i = $startP; $i <= $endP; $i++):
                                            ?>
                                                <li class="page-item <?php echo $i == $userPage ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?php echo $userPage >= $userTotalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($userTotalPages, $userPage + 1)])); ?>" title="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                            <li class="page-item <?php echo $userPage >= $userTotalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $userTotalPages])); ?>" title="Last Page">
                                                    <i class="fas fa-angles-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ========================================================== -->
                <!-- TAB 3: AUDIT LEDGER & TRANSACTIONS (Paginated) -->
                <!-- ========================================================== -->
                <?php if ($activeTab === 'transactions'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <form method="GET" class="row g-2 align-items-center">
                                <input type="hidden" name="tab" value="transactions">

                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" name="tx_search" class="form-control" placeholder="Search title, order #, user name, email..." value="<?php echo htmlspecialchars($txSearch); ?>">
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <select name="tx_type" class="form-select">
                                        <option value="">All Types (Credit &amp; Debit)</option>
                                        <option value="credit" <?php echo $txTypeFilter === 'credit' ? 'selected' : ''; ?>>Credit (+) Earned / Awarded</option>
                                        <option value="debit" <?php echo $txTypeFilter === 'debit' ? 'selected' : ''; ?>>Debit (−) Redeemed / Deducted</option>
                                    </select>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                                        <input type="number" name="user_id" class="form-control" placeholder="Filter by User ID" value="<?php echo $txUserFilter > 0 ? $txUserFilter : ''; ?>">
                                    </div>
                                </div>

                                <div class="col-md-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                                    <?php if (!empty($txSearch) || !empty($txTypeFilter) || $txUserFilter > 0): ?>
                                        <a href="coins-manager.php?tab=transactions" class="btn btn-outline-secondary" title="Clear Filters"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light-subtle border-bottom small text-muted">
                                <div>
                                    Showing <strong><?php echo number_format(count($transactionsList)); ?></strong> of <strong><?php echo number_format($totalTxMatches); ?></strong> audit records
                                    <?php if ($txUserFilter > 0): ?>
                                        <span class="badge bg-primary ms-2">Filtered for User #<?php echo $txUserFilter; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span>Per Page:</span>
                                    <select class="form-select form-select-sm w-auto" onchange="location.href='?<?php echo http_build_query(array_merge($_GET, ['tx_page' => 1, 'tx_limit' => ''])); ?>'+this.value">
                                        <option value="15" <?php echo $txLimit == 15 ? 'selected' : ''; ?>>15</option>
                                        <option value="25" <?php echo $txLimit == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $txLimit == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $txLimit == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Customer</th>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Coins</th>
                                            <th>Ref Order</th>
                                            <th>Audit Reason / Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactionsList)): ?>
                                            <?php foreach ($transactionsList as $tx): ?>
                                                <?php 
                                                    $isCredit = $tx['type'] === 'credit';
                                                    $custName = trim(($tx['first_name'] ?? '') . ' ' . ($tx['last_name'] ?? ''));
                                                    if (empty($custName)) $custName = $tx['username'] ?: 'User #' . $tx['user_id'];
                                                ?>
                                                <tr>
                                                    <td class="small text-muted" style="white-space: nowrap;">
                                                        <?php echo date('d M Y, h:i A', strtotime($tx['created_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold small"><?php echo htmlspecialchars($custName); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($tx['email'] ?? ('ID #' . $tx['user_id'])); ?></small>
                                                    </td>
                                                    <td class="fw-semibold small"><?php echo htmlspecialchars($tx['title']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $isCredit ? 'success' : 'danger'; ?>">
                                                            <?php echo strtoupper($tx['type']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold fs-6 <?php echo $isCredit ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $isCredit ? '+' : '−'; ?><?php echo number_format($tx['amount']); ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($tx['order_id'])): ?>
                                                            <span class="badge bg-dark font-monospace"><?php echo htmlspecialchars($tx['order_id']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="small text-muted"><?php echo htmlspecialchars($tx['description'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="fas fa-history fa-2x mb-2 d-block opacity-50"></i>
                                                    No coin transaction records found for this query.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Transactions Pagination Bar -->
                            <?php if ($txTotalPages > 1): ?>
                                <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                    <div class="small text-muted">
                                        Page <?php echo $txPage; ?> of <?php echo $txTotalPages; ?>
                                    </div>
                                    <nav aria-label="Transactions pagination">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?php echo $txPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['tx_page' => 1])); ?>" title="First">
                                                    <i class="fas fa-angles-left"></i>
                                                </a>
                                            </li>
                                            <li class="page-item <?php echo $txPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['tx_page' => max(1, $txPage - 1)])); ?>" title="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>

                                            <?php
                                                $startTxP = max(1, $txPage - 2);
                                                $endTxP = min($txTotalPages, $txPage + 2);
                                                for ($j = $startTxP; $j <= $endTxP; $j++):
                                            ?>
                                                <li class="page-item <?php echo $j == $txPage ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['tx_page' => $j])); ?>"><?php echo $j; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?php echo $txPage >= $txTotalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['tx_page' => min($txTotalPages, $txPage + 1)])); ?>" title="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                            <li class="page-item <?php echo $txPage >= $txTotalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['tx_page' => $txTotalPages])); ?>" title="Last">
                                                    <i class="fas fa-angles-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ========================================================== -->
                <!-- TAB 4: REWARD VOUCHERS CATALOG -->
                <!-- ========================================================== -->
                <?php if ($activeTab === 'rewards'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h5 class="card-title mb-0"><i class="fas fa-ticket me-2 text-primary"></i>SuperCoin Reward Vouchers</h5>
                                <small class="text-muted">Coupons that customers can redeem on the storefront using their SuperCoins.</small>
                            </div>
                            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addRewardModal">
                                <i class="fas fa-plus me-1"></i> Create Reward Voucher
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Voucher Title &amp; Category</th>
                                            <th>Coupon Code</th>
                                            <th>Coins Required</th>
                                            <th>Discount Value (₹)</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($allRewards)): ?>
                                            <?php foreach ($allRewards as $r): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($r['title']); ?></div>
                                                        <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($r['category'] ?? 'General'); ?></span>
                                                        <?php if (!empty($r['description'])): ?>
                                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($r['description']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-dark font-monospace fs-6 px-2 py-1">
                                                            <?php echo htmlspecialchars($r['coupon_code']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-coin fs-6 px-2 py-1">
                                                            <i class="fas fa-coins me-1"></i><?php echo number_format($r['coins_required']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-success fw-bold fs-6">
                                                        ₹<?php echo number_format($r['discount_amount'], 2); ?>
                                                    </td>
                                                    <td>
                                                        <a href="coins-manager.php?tab=rewards&toggle_reward=<?php echo $r['id']; ?>" class="badge text-decoration-none bg-<?php echo !empty($r['is_active']) ? 'success' : 'secondary'; ?>">
                                                            <?php echo !empty($r['is_active']) ? 'Active' : 'Disabled'; ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="coins-manager.php?tab=rewards&delete_reward=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this reward voucher?');" title="Delete Voucher">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fas fa-ticket-alt fa-2x mb-2 d-block opacity-50"></i>
                                                    No reward vouchers configured yet. Click "Create Reward Voucher" above to add one.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ========================================================== -->
                <!-- TAB 5: EARNING RULES & ENGINE SETTINGS -->
                <!-- ========================================================== -->
                <?php if ($activeTab === 'settings'): ?>
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <h5 class="card-title mb-0"><i class="fas fa-sliders-h text-primary me-2"></i>SuperCoin Earning Rules &amp; Global Rates</h5>
                                        <small class="text-muted">These settings dynamically control coin issuance on signup, order delivery, and checkout redemptions.</small>
                                    </div>
                                    <span class="badge bg-success">Live Engine Active</span>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST">
                                        <input type="hidden" name="tab" value="settings">

                                        <div class="p-3 bg-light rounded mb-4 border">
                                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-plus me-1"></i>1. First-Time Registration Bonus</h6>
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label fw-bold">Welcome Bonus Coins *</label>
                                                    <div class="input-group">
                                                        <input type="number" name="welcome_bonus_coins" class="form-control" value="<?php echo htmlspecialchars($settings['welcome_bonus_coins']); ?>" required min="0">
                                                        <span class="input-group-text bg-warning-subtle fw-bold"><i class="fas fa-coins text-warning me-1"></i>Coins</span>
                                                    </div>
                                                    <small class="text-muted">Credited immediately to new customers upon signing up.</small>
                                                </div>
                                                <div class="col-md-4 d-flex align-items-center pt-md-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_welcome_bonus" id="enable_welcome_bonus" <?php echo !empty($settings['enable_welcome_bonus']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="enable_welcome_bonus">Enable Welcome Bonus</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-3 bg-light rounded mb-4 border">
                                            <h6 class="fw-bold text-success mb-3"><i class="fas fa-shopping-bag me-1"></i>2. Purchase Earning &amp; Conversion Rates</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Coins Earned per ₹100 Spent *</label>
                                                    <div class="input-group">
                                                        <input type="number" name="coins_per_100_spent" class="form-control" value="<?php echo htmlspecialchars($settings['coins_per_100_spent']); ?>" required min="1">
                                                        <span class="input-group-text">Coins / ₹100</span>
                                                    </div>
                                                    <small class="text-muted">e.g. 2 coins for every ₹100 spent</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">1 SuperCoin Value in INR (₹) *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" step="0.10" name="coin_value_inr" class="form-control" value="<?php echo htmlspecialchars($settings['coin_value_inr']); ?>" required min="0.1">
                                                    </div>
                                                    <small class="text-muted">Conversion value at storefront checkout</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Min Cart Purchase Amount (₹)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" step="10" name="min_order_amount_for_coins" class="form-control" value="<?php echo htmlspecialchars($settings['min_order_amount_for_coins'] ?? 100); ?>" required min="0">
                                                    </div>
                                                    <small class="text-muted">Minimum order value required to earn coins</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Max Coins Earn Cap per Order</label>
                                                    <div class="input-group">
                                                        <input type="number" name="max_coins_per_order" class="form-control" value="<?php echo htmlspecialchars($settings['max_coins_per_order']); ?>" required min="1">
                                                        <span class="input-group-text">Coins</span>
                                                    </div>
                                                    <small class="text-muted">Prevents excess coin distribution on bulk orders</small>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="enable_purchase_coins" id="enable_purchase_coins" <?php echo !empty($settings['enable_purchase_coins']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-semibold" for="enable_purchase_coins">Award Coins Automatically when Order Status becomes 'Delivered'</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-3 bg-light rounded mb-4 border">
                                            <h6 class="fw-bold text-info mb-3"><i class="fas fa-star me-1"></i>3. Customer Review &amp; Feedback Rewards</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Approved Product Review Bonus</label>
                                                    <div class="input-group">
                                                        <input type="number" name="review_bonus_coins" class="form-control" value="<?php echo htmlspecialchars($settings['review_bonus_coins']); ?>" required min="0">
                                                        <span class="input-group-text">Coins</span>
                                                    </div>
                                                    <small class="text-muted">Awarded when customer submits an approved product photo/review</small>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" name="update_settings" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                            <i class="fas fa-save me-1"></i> Save &amp; Apply All Coin Earning Rules
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<!-- ========================================================================== -->
<!-- MODAL: Give / Adjust Coins (With High-Speed Debounced User Search)          -->
<!-- ========================================================================== -->
<div class="modal fade" id="giveCoinsModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning-subtle py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-coins text-warning me-2"></i>Give / Adjust Coins for Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <!-- User Selector with Live AJAX Search -->
                <div class="mb-3 position-relative">
                    <label class="form-label fw-bold">Select Customer *</label>
                    
                    <!-- Search input for finding any of 10,000+ users -->
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="modal_user_search_input" class="form-control" placeholder="Type customer name, email or phone...">
                    </div>
                    
                    <!-- Floating live search suggestions list -->
                    <div id="modal_search_dropdown" class="live-search-dropdown"></div>

                    <!-- Selected User Information Display Box -->
                    <div id="modal_selected_user_card" class="alert alert-light border mt-2 py-2 px-3 d-flex align-items-center justify-content-between mb-0" style="display: none !important;">
                        <div>
                            <div class="fw-bold text-dark" id="modal_selected_name">Selected Customer</div>
                            <small class="text-muted" id="modal_selected_details">email@example.com</small>
                        </div>
                        <span class="badge badge-coin fs-6" id="modal_selected_balance"><i class="fas fa-coins me-1"></i>0 Coins</span>
                    </div>

                    <!-- Hidden target user id input for form submission -->
                    <input type="hidden" name="target_user_id" id="modal_target_user_id" value="" required>
                    <small class="text-muted" id="modal_select_hint">Search and select a customer above.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Action *</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="credit">Credit (+) Give Coins</option>
                            <option value="debit">Debit (−) Deduct Coins</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Amount of Coins *</label>
                        <input type="number" name="coin_amount" class="form-control" required min="1" placeholder="e.g. 100">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Reason / Title</label>
                    <input type="text" name="adjustment_reason" class="form-control" placeholder="e.g. Festive loyalty gift, Support compensation" value="Loyalty Reward Coins credited by Admin">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="adjust_user_coins" class="btn btn-warning fw-bold text-dark shadow-sm">
                    <i class="fas fa-paper-plane me-1"></i> Confirm &amp; Update Coins
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================== -->
<!-- MODAL: Add SuperCoin Reward Voucher                                        -->
<!-- ========================================================================== -->
<div class="modal fade" id="addRewardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-ticket-alt me-2"></i>Create SuperCoin Reward Voucher</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Voucher Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. ₹150 Off on Custom Ceramic Mugs">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Coupon Code *</label>
                        <input type="text" name="coupon_code" class="form-control text-uppercase font-monospace fw-bold" required placeholder="MUG150">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Coins Required *</label>
                        <input type="number" name="coins_required" class="form-control" required value="150" min="10">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Discount Value (₹) *</label>
                        <input type="number" step="1" name="discount_amount" class="form-control" required value="150" min="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="Drinkware">Drinkware</option>
                            <option value="Apparel">Apparel</option>
                            <option value="Shipping">Shipping</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Bulk">Bulk</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description / Terms</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief terms, min cart value or eligible items"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_reward" class="btn btn-primary fw-bold shadow-sm">
                    <i class="fas fa-plus me-1"></i> Create Voucher
                </button>
            </div>
        </form>
    </div>
</div>

<script src="js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('modal_user_search_input');
    var dropdown = document.getElementById('modal_search_dropdown');
    var targetInput = document.getElementById('modal_target_user_id');
    var userCard = document.getElementById('modal_selected_user_card');
    var selectedName = document.getElementById('modal_selected_name');
    var selectedDetails = document.getElementById('modal_selected_details');
    var selectedBalance = document.getElementById('modal_selected_balance');
    var hint = document.getElementById('modal_select_hint');

    var searchTimer = null;

    function selectUser(user) {
        targetInput.value = user.id;
        selectedName.textContent = user.name + ' (#' + user.id + ')';
        selectedDetails.textContent = user.email + (user.phone !== 'N/A' ? ' • ' + user.phone : '');
        selectedBalance.innerHTML = '<i class="fas fa-coins me-1"></i>' + Number(user.balance).toLocaleString() + ' Coins';
        
        userCard.style.setProperty('display', 'flex', 'important');
        dropdown.style.display = 'none';
        searchInput.value = '';
        if (hint) hint.textContent = 'Selected user will receive or be deducted coins.';
    }

    // Direct Pre-select from Customer table buttons
    document.querySelectorAll('.btn-quick-gift').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var user = {
                id: this.getAttribute('data-user-id'),
                name: this.getAttribute('data-user-name'),
                email: this.getAttribute('data-user-email'),
                phone: '',
                balance: this.getAttribute('data-user-balance') || 0
            };
            selectUser(user);
        });
    });

    // Debounced AJAX search for 10,000+ users
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimer);

            if (q.length === 0) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(function() {
                fetch('coins-manager.php?ajax_search_users=1&q=' + encodeURIComponent(q))
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        dropdown.innerHTML = '';
                        if (data && data.length > 0) {
                            data.forEach(function(u) {
                                var item = document.createElement('div');
                                item.className = 'live-search-item d-flex justify-content-between align-items-center';
                                item.innerHTML = '<div>' +
                                    '<div class="fw-bold text-dark">' + escapeHtml(u.name) + ' <small class="text-muted">#' + u.id + '</small></div>' +
                                    '<small class="text-muted">' + escapeHtml(u.email) + ' • ' + escapeHtml(u.phone) + '</small>' +
                                    '</div>' +
                                    '<span class="badge badge-coin"><i class="fas fa-coins me-1"></i>' + Number(u.balance).toLocaleString() + '</span>';
                                
                                item.addEventListener('click', function() {
                                    selectUser(u);
                                });
                                dropdown.appendChild(item);
                            });
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.innerHTML = '<div class="p-3 text-muted text-center small"><i class="fas fa-user-slash me-1"></i>No matching customers found</div>';
                            dropdown.style.display = 'block';
                        }
                    })
                    .catch(function(err) {
                        console.error('User search failed:', err);
                    });
            }, 250);
        });
    }

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdown && !dropdown.contains(e.target) && e.target !== searchInput) {
            dropdown.style.display = 'none';
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
</body>
</html>
