<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'all';
$userId = null;

// Resolve Authorization Bearer Token if present
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
    $stmt = $db->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW() LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $userId = (int)$row['user_id'];
        }
    }
}

// Fallback to user_id parameter if authenticated or demo
if (!$userId && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
}

switch ($action) {
    case 'settings':
    case 'rules':
        $res = $db->query("SELECT * FROM coin_settings LIMIT 1");
        $settings = $res ? $res->fetch_assoc() : [
            'coins_per_100_spent' => 2,
            'max_coins_per_order' => 50,
            'coin_value_inr' => 1.00,
            'welcome_bonus_coins' => 100,
            'review_bonus_coins' => 25
        ];
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        break;

    case 'rewards':
        $res = $db->query("SELECT * FROM coin_rewards WHERE is_active = 1 ORDER BY coins_required ASC");
        $rewards = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rewards[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'],
                    'coins' => (int)$row['coins_required'],
                    'discount_amount' => (float)$row['discount_amount'],
                    'code' => $row['coupon_code'],
                    'desc' => $row['description'],
                    'category' => $row['category'] ?? 'General'
                ];
            }
        }
        echo json_encode([
            'success' => true,
            'data' => $rewards
        ]);
        break;

    case 'claim_reward':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST method required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $rewardId = (int)($input['reward_id'] ?? 0);

        if (!$rewardId) {
            echo json_encode(['success' => false, 'message' => 'Invalid reward ID']);
            exit;
        }

        $rewStmt = $db->prepare("SELECT * FROM coin_rewards WHERE id = ? AND is_active = 1 LIMIT 1");
        $rewStmt->bind_param("i", $rewardId);
        $rewStmt->execute();
        $reward = $rewStmt->get_result()->fetch_assoc();

        if (!$reward) {
            echo json_encode(['success' => false, 'message' => 'Reward voucher not found or expired']);
            exit;
        }

        $requiredCoins = (int)$reward['coins_required'];

        // If user logged in, check & deduct
        if ($userId) {
            $uStmt = $db->prepare("SELECT balance FROM user_coins WHERE user_id = ? LIMIT 1");
            $uStmt->bind_param("i", $userId);
            $uStmt->execute();
            $uRes = $uStmt->get_result()->fetch_assoc();
            $currentBalance = $uRes ? (int)$uRes['balance'] : 100;

            if ($currentBalance < $requiredCoins) {
                echo json_encode([
                    'success' => false,
                    'message' => "Insufficient coins balance. You need {$requiredCoins} coins to claim this reward."
                ]);
                exit;
            }

            $newBalance = $currentBalance - $requiredCoins;
            $upd = $db->prepare("UPDATE user_coins SET balance = ?, lifetime_spent = lifetime_spent + ? WHERE user_id = ?");
            $upd->bind_param("iii", $newBalance, $requiredCoins, $userId);
            $upd->execute();

            // Insert transaction
            $title = "Redeemed: " . $reward['title'];
            $desc = "Coupon code " . $reward['coupon_code'] . " claimed.";
            $tx = $db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description) VALUES (?, ?, 'debit', ?, ?)");
            $tx->bind_param("iiss", $userId, $requiredCoins, $title, $desc);
            $tx->execute();

            echo json_encode([
                'success' => true,
                'message' => "Reward claimed successfully! Use coupon code: {$reward['coupon_code']}",
                'coupon_code' => $reward['coupon_code'],
                'new_balance' => $newBalance
            ]);
        } else {
            // Guest mode simulation
            echo json_encode([
                'success' => true,
                'message' => "Reward claimed! Use coupon code: {$reward['coupon_code']}",
                'coupon_code' => $reward['coupon_code'],
                'new_balance' => max(0, 445 - $requiredCoins)
            ]);
        }
        break;

    case 'admin_give_coins':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST method required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $targetUserId = (int)($input['user_id'] ?? 0);
        $amount = (int)($input['amount'] ?? 0);
        $type = trim($input['type'] ?? 'credit');
        $reason = trim($input['reason'] ?? 'Admin manual coin grant');

        if (!$targetUserId || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID or amount']);
            exit;
        }

        require_once __DIR__ . '/../services/CoinService.php';
        $cs = new CoinService($db);
        $res = $cs->adjustCoins($targetUserId, $type, $amount, $reason);
        echo json_encode($res);
        break;

    case 'all':
    default:
        // Fetch coin rules/settings
        $setRes = $db->query("SELECT * FROM coin_settings LIMIT 1");
        $settings = $setRes ? $setRes->fetch_assoc() : [
            'coins_per_100_spent' => 2,
            'max_coins_per_order' => 50,
            'coin_value_inr' => 1.00,
            'welcome_bonus_coins' => 100,
            'review_bonus_coins' => 25
        ];

        $welcomeBonus = (int)($settings['welcome_bonus_coins'] ?? 100);

        // Fetch user balance
        $balance = $welcomeBonus;
        $tier = 'Gold Member';
        $lifetimeEarned = $welcomeBonus;
        $lifetimeSpent = 0;
        $walletBalance = 0.00;

        if ($userId) {
            $uStmt = $db->prepare("SELECT * FROM user_coins WHERE user_id = ? LIMIT 1");
            $uStmt->bind_param("i", $userId);
            $uStmt->execute();
            $userCoinRow = $uStmt->get_result()->fetch_assoc();

            if ($userCoinRow) {
                $balance = (int)$userCoinRow['balance'];
                $tier = $userCoinRow['tier'] ?? 'Gold Member';
                $lifetimeEarned = (int)$userCoinRow['lifetime_earned'];
                $lifetimeSpent = (int)$userCoinRow['lifetime_spent'];
            } else {
                // Initialize new user with welcome bonus from settings
                $init = $db->prepare("INSERT INTO user_coins (user_id, balance, tier, lifetime_earned, lifetime_spent) VALUES (?, ?, 'Silver Member', ?, 0)");
                $init->bind_param("iii", $userId, $welcomeBonus, $welcomeBonus);
                $init->execute();
                $balance = $welcomeBonus;
                $tier = 'Silver Member';
                $lifetimeEarned = $welcomeBonus;
                $lifetimeSpent = 0;

                // Add welcome transaction
                if ($welcomeBonus > 0) {
                    $tx = $db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description) VALUES (?, ?, 'credit', 'Signup Welcome Bonus Coins', 'Welcome bonus credited for registering on Printmont')");
                    $tx->bind_param("ii", $userId, $welcomeBonus);
                    $tx->execute();
                }
            }

            // Fetch cash wallet balance
            $wStmt = $db->prepare("SELECT balance FROM user_wallets WHERE user_id = ? LIMIT 1");
            $wStmt->bind_param("i", $userId);
            $wStmt->execute();
            $wRow = $wStmt->get_result()->fetch_assoc();
            if ($wRow) {
                $walletBalance = (float)$wRow['balance'];
            }
        }

        // Fetch transactions history
        $transactions = [];
        if ($userId) {
            $tStmt = $db->prepare("SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $tStmt->bind_param("i", $userId);
            $tStmt->execute();
            $tRes = $tStmt->get_result();
            while ($t = $tRes->fetch_assoc()) {
                $transactions[] = [
                    'id' => (int)$t['id'],
                    'text' => $t['title'],
                    'change' => ($t['type'] === 'credit' ? 1 : -1) * (int)$t['amount'],
                    'date' => ($t['type'] === 'credit' ? 'Credited on ' : 'Debited on ') . date('d M Y', strtotime($t['created_at'])),
                    'type' => $t['type'],
                    'description' => $t['description']
                ];
            }
        }

        // Fallback realistic demo transactions if empty
        if (empty($transactions)) {
            $transactions = [
                ['text' => 'Redeemed: ₹55 Discount on Custom Photo Mug', 'change' => -55, 'date' => 'Debited on 02 Oct 2026', 'type' => 'debit'],
                ['text' => 'Earned: Order Placement Reward (Custom T-Shirts)', 'change' => 50, 'date' => 'Credited on 28 Sep 2026', 'type' => 'credit'],
                ['text' => 'Earned: Product Review & Photo Upload Bonus', 'change' => 25, 'date' => 'Credited on 15 Sep 2026', 'type' => 'credit'],
                ['text' => 'Earned: Signup Welcome Bonus Coins', 'change' => $welcomeBonus, 'date' => 'Credited on Account Creation', 'type' => 'credit']
            ];
        }

        // Fetch reward vouchers
        $rewRes = $db->query("SELECT * FROM coin_rewards WHERE is_active = 1 ORDER BY coins_required ASC");
        $rewards = [];
        if ($rewRes) {
            while ($r = $rewRes->fetch_assoc()) {
                $rewards[] = [
                    'id' => (int)$r['id'],
                    'title' => $r['title'],
                    'coins' => (int)$r['coins_required'],
                    'discount_amount' => (float)$r['discount_amount'],
                    'code' => $r['coupon_code'],
                    'desc' => $r['description'],
                    'category' => $r['category'] ?? 'General'
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'wallet_balance' => $walletBalance,
                'tier' => $tier,
                'lifetime_earned' => $lifetimeEarned,
                'lifetime_spent' => $lifetimeSpent,
                'rules' => [
                    'coins_per_100_spent' => (int)$settings['coins_per_100_spent'],
                    'max_coins_per_order' => (int)$settings['max_coins_per_order'],
                    'coin_value_inr' => (float)$settings['coin_value_inr'],
                    'welcome_bonus' => $welcomeBonus,
                    'review_bonus' => (int)$settings['review_bonus_coins']
                ],
                'transactions' => $transactions,
                'rewards' => $rewards
            ]
        ]);
        break;
}
