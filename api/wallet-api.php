<?php
// printmont-backend/api/wallet-api.php - customer cash wallet
//
// Customers:  Authorization: Bearer <access_token> (same JWT as the rest of the site).
//   get_wallet        balance, coins, passbook
//   add_money         starts a top-up: creates a Razorpay order (no money is added yet)
//   verify_add_money  checks Razorpay's signature, then credits the wallet (once per payment)
// Admin:      logged-in admin panel session (wallets-manager.php uses WalletService directly).
//   admin_reverse_wallet, admin_adjust
header('Content-Type: application/json');
require_once __DIR__ . '/cors.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/WalletService.php';
require_once __DIR__ . '/../services/CoinService.php';
require_once __DIR__ . '/../services/RazorpayService.php';
require_once __DIR__ . '/../controllers/AuthControllerAPI.php';

if (!function_exists('getBearerToken')) {
    function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if (!$header && function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $name => $value) {
                if (strtolower($name) === 'authorization') { $header = $value; break; }
            }
        }
        if ($header && preg_match('/Bearer\s(\S+)/i', trim($header), $m)) {
            return $m[1];
        }
        return null;
    }
}

function walletJson($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$walletService = new WalletService($db);
$coinService = new CoinService($db);

$action = $_GET['action'] ?? 'get_wallet';
$method = $_SERVER['REQUEST_METHOD'];

// ── Admin actions: admin panel session only ───────────────────────────
if (in_array($action, ['admin_reverse_wallet', 'admin_adjust'], true)) {
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $isAdmin = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !empty($_SESSION['user_id']);
    if (!$isAdmin) {
        walletJson(['success' => false, 'error' => 'Admin login required'], 401);
    }
    if ($method !== 'POST') {
        walletJson(['success' => false, 'error' => 'POST method required'], 405);
    }
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $adminId = (int) $_SESSION['user_id'];

    if ($action === 'admin_reverse_wallet') {
        $orderId = (int) ($input['order_id'] ?? 0);
        $amount = isset($input['amount']) && (float) $input['amount'] > 0 ? (float) $input['amount'] : null;
        $reason = trim($input['reason'] ?? 'Admin Cancellation Reversal to Wallet');
        if (!$orderId) walletJson(['success' => false, 'error' => 'Order ID is required'], 400);
        walletJson($walletService->reverseOrderToWallet($orderId, $amount, $reason, $adminId));
    }

    $targetUserId = (int) ($input['user_id'] ?? 0);
    if (!$targetUserId) walletJson(['success' => false, 'error' => 'User ID is required'], 400);
    walletJson($walletService->adminAdjust(
        $targetUserId,
        trim($input['type'] ?? 'credit'),
        (float) ($input['amount'] ?? 0),
        trim($input['reason'] ?? 'Admin manual adjustment'),
        $adminId
    ));
}

// ── Customer actions: logged-in customer (JWT) ────────────────────────
$authController = new AuthController($db);
$auth = $authController->verifyToken(getBearerToken());
if (empty($auth['success'])) {
    walletJson(['success' => false, 'error' => 'Please log in to use your wallet', 'require_login' => true], 401);
}
$userId = (int) $auth['user']['id'];

// Pending online top-ups, so a payment can only be credited once
$db->query("CREATE TABLE IF NOT EXISTS wallet_topups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    razorpay_order_id VARCHAR(64) NOT NULL UNIQUE,
    razorpay_payment_id VARCHAR(64) NULL,
    status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_topup_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($action) {
    case 'get_wallet':
    case 'balance':
        walletJson([
            'success' => true,
            'data' => [
                'wallet' => $walletService->getWallet($userId),
                'coins' => $coinService->getUserCoins($userId),
                'transactions' => $walletService->getTransactions($userId, 40),
            ],
        ]);

    case 'add_money':
        if ($method !== 'POST') walletJson(['success' => false, 'error' => 'POST method required'], 405);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $amount = round((float) ($input['amount'] ?? 0), 2);

        if ($amount < 10) walletJson(['success' => false, 'error' => 'Minimum top-up amount is ₹10'], 400);
        if ($amount > 100000) walletJson(['success' => false, 'error' => 'Maximum top-up amount is ₹1,00,000 per transaction'], 400);

        $razorpay = new RazorpayService($db);
        if (!$razorpay->enabled() || !$razorpay->configured()) {
            walletJson(['success' => false, 'error' => 'Online payment is not set up yet, so money cannot be added right now.'], 400);
        }

        $receipt = 'WALLET-' . $userId . '-' . time();
        $rzp = $razorpay->createOrder($amount, $receipt, ['purpose' => 'wallet_topup', 'user_id' => (string) $userId]);
        if (!$rzp['success']) {
            walletJson(['success' => false, 'error' => 'Could not start the payment. Please try again.'], 502);
        }

        $stmt = $db->prepare("INSERT INTO wallet_topups (user_id, amount, razorpay_order_id) VALUES (?, ?, ?)");
        $stmt->bind_param('ids', $userId, $amount, $rzp['id']);
        $stmt->execute();

        walletJson([
            'success' => true,
            'message' => 'Complete the payment to add money to your wallet',
            'razorpay' => [
                'key_id'   => $razorpay->keyId(),
                'order_id' => $rzp['id'],
                'amount'   => (int) round($amount * 100),
                'currency' => 'INR',
            ],
        ]);

    case 'verify_add_money':
        if ($method !== 'POST') walletJson(['success' => false, 'error' => 'POST method required'], 405);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $rzpOrderId = (string) ($input['razorpay_order_id'] ?? '');
        $paymentId = (string) ($input['razorpay_payment_id'] ?? '');
        $signature = (string) ($input['razorpay_signature'] ?? '');

        $stmt = $db->prepare("SELECT * FROM wallet_topups WHERE razorpay_order_id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param('si', $rzpOrderId, $userId);
        $stmt->execute();
        $topup = $stmt->get_result()->fetch_assoc();
        if (!$topup) walletJson(['success' => false, 'error' => 'Top-up not found.'], 404);

        if ($topup['status'] === 'paid') {
            $wallet = $walletService->getWallet($userId);
            walletJson(['success' => true, 'already_credited' => true, 'new_balance' => (float) $wallet['balance']]);
        }

        if (!(new RazorpayService($db))->verifySignature($rzpOrderId, $paymentId, $signature)) {
            $stmt = $db->prepare("UPDATE wallet_topups SET status = 'failed' WHERE id = ?");
            $stmt->bind_param('i', $topup['id']);
            $stmt->execute();
            walletJson(['success' => false, 'error' => 'Payment verification failed. If money was deducted it will be refunded.'], 400);
        }

        // Mark paid first (only one request can switch pending → paid), then credit
        $stmt = $db->prepare("UPDATE wallet_topups SET status = 'paid', razorpay_payment_id = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('si', $paymentId, $topup['id']);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            $wallet = $walletService->getWallet($userId);
            walletJson(['success' => true, 'already_credited' => true, 'new_balance' => (float) $wallet['balance']]);
        }

        $result = $walletService->addMoney($userId, (float) $topup['amount'], 'Razorpay Online Top-up', $paymentId);
        walletJson($result, !empty($result['success']) ? 200 : 500);

    default:
        walletJson(['success' => false, 'error' => 'Unknown action: ' . $action], 400);
}
