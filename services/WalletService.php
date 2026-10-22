<?php
// printmont-backend/services/WalletService.php - Handles customer wallet balance, topups, and refund reversals

require_once __DIR__ . '/../config/database.php';

class WalletService
{
    private $db;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS user_wallets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_added DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_spent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_refunded DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('active','frozen') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_id INT NULL,
            order_number VARCHAR(50) NULL,
            type ENUM('credit','debit') NOT NULL,
            category ENUM('topup','refund_reversal','order_payment','admin_adjustment','cashback') NOT NULL DEFAULT 'topup',
            amount DECIMAL(10,2) NOT NULL,
            balance_after DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            reference_id VARCHAR(100) NULL,
            status ENUM('success','pending','failed') NOT NULL DEFAULT 'success',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (order_id),
            INDEX (order_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Get or create wallet for user
     */
    public function getWallet(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_wallets WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $wallet = $res->fetch_assoc();

        if (!$wallet) {
            $ins = $this->db->prepare("INSERT INTO user_wallets (user_id, balance, total_added, total_spent, total_refunded) VALUES (?, 0.00, 0.00, 0.00, 0.00)");
            $ins->bind_param("i", $userId);
            $ins->execute();

            return [
                'user_id' => $userId,
                'balance' => 0.00,
                'total_added' => 0.00,
                'total_spent' => 0.00,
                'total_refunded' => 0.00,
                'status' => 'active'
            ];
        }

        return [
            'user_id' => (int)$wallet['user_id'],
            'balance' => (float)$wallet['balance'],
            'total_added' => (float)$wallet['total_added'],
            'total_spent' => (float)$wallet['total_spent'],
            'total_refunded' => (float)$wallet['total_refunded'],
            'status' => $wallet['status'] ?? 'active'
        ];
    }

    /**
     * Get transactions for user
     */
    public function getTransactions(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();

        $list = [];
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'id' => (int)$row['id'],
                'order_id' => $row['order_id'] ? (int)$row['order_id'] : null,
                'order_number' => $row['order_number'],
                'type' => $row['type'],
                'category' => $row['category'],
                'amount' => (float)$row['amount'],
                'balance_after' => (float)$row['balance_after'],
                'title' => $row['title'],
                'description' => $row['description'],
                'reference_id' => $row['reference_id'],
                'status' => $row['status'],
                'date' => date('d M Y, h:i A', strtotime($row['created_at'])),
                'created_at' => $row['created_at']
            ];
        }
        return $list;
    }

    /**
     * Add money to wallet (user topup)
     */
    public function addMoney(int $userId, float $amount, string $paymentMethod = 'Online Payment', string $referenceId = ''): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero'];
        }

        $wallet = $this->getWallet($userId);
        $newBalance = round($wallet['balance'] + $amount, 2);

        $upd = $this->db->prepare("UPDATE user_wallets SET balance = ?, total_added = total_added + ? WHERE user_id = ?");
        $upd->bind_param("ddi", $newBalance, $amount, $userId);
        if (!$upd->execute()) {
            return ['success' => false, 'error' => 'Database error updating wallet balance'];
        }

        $title = "Money Added to Wallet";
        $desc = "Added ₹" . number_format($amount, 2) . " via " . $paymentMethod;
        $ref = !empty($referenceId) ? $referenceId : ('TOPUP-' . strtoupper(substr(md5(uniqid()), 0, 10)));

        $tx = $this->db->prepare("INSERT INTO wallet_transactions (user_id, type, category, amount, balance_after, title, description, reference_id, status) VALUES (?, 'credit', 'topup', ?, ?, ?, ?, ?, 'success')");
        $tx->bind_param("iddsss", $userId, $amount, $newBalance, $title, $desc, $ref);
        $tx->execute();

        return [
            'success' => true,
            'message' => "₹" . number_format($amount, 2) . " added to wallet successfully!",
            'new_balance' => $newBalance,
            'transaction_id' => $this->db->insert_id,
            'reference_id' => $ref
        ];
    }

    /**
     * Reverse payment to customer wallet on order cancellation / refund
     */
    public function reverseOrderToWallet(int $orderId, ?float $amount = null, string $reason = 'Order Cancellation Reversal', ?int $adminId = null): array
    {
        // Fetch order details
        $oStmt = $this->db->prepare("SELECT id, order_number, user_id, customer_id, customer_name, grand_total, payment_status, status FROM orders WHERE id = ? LIMIT 1");
        $oStmt->bind_param("i", $orderId);
        $oStmt->execute();
        $order = $oStmt->get_result()->fetch_assoc();

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $userId = (int)($order['user_id'] ?? $order['customer_id'] ?? 0);
        if ($userId <= 0) {
            return ['success' => false, 'error' => 'No registered user attached to this order for wallet refund'];
        }

        $reversalAmount = ($amount !== null && $amount > 0) ? round($amount, 2) : (float)$order['grand_total'];
        if ($reversalAmount <= 0) {
            return ['success' => false, 'error' => 'Reversal amount must be greater than zero'];
        }

        $wallet = $this->getWallet($userId);
        $newBalance = round($wallet['balance'] + $reversalAmount, 2);

        $upd = $this->db->prepare("UPDATE user_wallets SET balance = ?, total_refunded = total_refunded + ? WHERE user_id = ?");
        $upd->bind_param("ddi", $newBalance, $reversalAmount, $userId);
        if (!$upd->execute()) {
            return ['success' => false, 'error' => 'Failed to credit user wallet'];
        }

        $orderNum = $order['order_number'] ?: ('#' . $order['id']);
        $title = "Refund Reversal: Order " . $orderNum;
        $desc = "Payment reversed to wallet: ₹" . number_format($reversalAmount, 2) . ". Reason: " . $reason;
        $ref = 'REF-' . $orderNum;

        $tx = $this->db->prepare("INSERT INTO wallet_transactions (user_id, order_id, order_number, type, category, amount, balance_after, title, description, reference_id, status) VALUES (?, ?, ?, 'credit', 'refund_reversal', ?, ?, ?, ?, ?, 'success')");
        $tx->bind_param("iisddsss", $userId, $orderId, $orderNum, $reversalAmount, $newBalance, $title, $desc, $ref);
        $tx->execute();

        // Update order payment status to refunded
        $pUpd = $this->db->prepare("UPDATE orders SET payment_status = 'refunded', updated_at = NOW() WHERE id = ?");
        $pUpd->bind_param("i", $orderId);
        $pUpd->execute();

        // Add to order status history
        $historyNote = "Refund of ₹" . number_format($reversalAmount, 2) . " reversed to customer wallet. " . $reason;
        $hStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by) VALUES (?, (SELECT status FROM orders WHERE id = ?), ?, ?)");
        $hStmt->bind_param("iisi", $orderId, $orderId, $historyNote, $adminId);
        $hStmt->execute();

        // Send customer notification if possible
        try {
            require_once __DIR__ . '/../controllers/NotificationController.php';
            $notif = new NotificationController();
            $notif->createNotification([
                'title' => "₹" . number_format($reversalAmount, 2) . " Refunded to Wallet",
                'message' => "Payment of ₹" . number_format($reversalAmount, 2) . " for order {$orderNum} has been credited to your Printmont Wallet balance.",
                'type' => 'order',
                'icon' => 'wallet',
                'user_id' => $userId,
                'link' => "/user/wallet"
            ]);
        } catch (\Throwable $t) {
            error_log("Wallet refund notification error: " . $t->getMessage());
        }

        return [
            'success' => true,
            'message' => "₹" . number_format($reversalAmount, 2) . " successfully reversed to customer wallet!",
            'new_balance' => $newBalance,
            'order_id' => $orderId,
            'order_number' => $orderNum
        ];
    }

    /**
     * Admin manual credit or debit to user wallet
     */
    public function adminAdjust(int $userId, string $type, float $amount, string $reason = '', ?int $adminId = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero'];
        }

        $wallet = $this->getWallet($userId);
        $currentBalance = $wallet['balance'];

        if ($type === 'debit' && $currentBalance < $amount) {
            return ['success' => false, 'error' => "Insufficient wallet balance (Current: ₹{$currentBalance})"];
        }

        $newBalance = round($type === 'credit' ? ($currentBalance + $amount) : ($currentBalance - $amount), 2);

        $updQuery = $type === 'credit'
            ? "UPDATE user_wallets SET balance = ?, total_added = total_added + ? WHERE user_id = ?"
            : "UPDATE user_wallets SET balance = ?, total_spent = total_spent + ? WHERE user_id = ?";

        $upd = $this->db->prepare($updQuery);
        $upd->bind_param("ddi", $newBalance, $amount, $userId);
        if (!$upd->execute()) {
            return ['success' => false, 'error' => 'Failed to update wallet balance'];
        }

        $title = $type === 'credit' ? "Admin Credit Adjustment" : "Admin Debit Adjustment";
        $desc = !empty($reason) ? $reason : ("Manual adjustment by Admin #" . ($adminId ?? 1));
        $ref = 'ADJ-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $tx = $this->db->prepare("INSERT INTO wallet_transactions (user_id, type, category, amount, balance_after, title, description, reference_id, status) VALUES (?, ?, 'admin_adjustment', ?, ?, ?, ?, ?, 'success')");
        $tx->bind_param("isddsss", $userId, $type, $amount, $newBalance, $title, $desc, $ref);
        $tx->execute();

        return [
            'success' => true,
            'message' => "Wallet " . ($type === 'credit' ? 'credited' : 'debited') . " by ₹" . number_format($amount, 2) . " successfully!",
            'new_balance' => $newBalance
        ];
    }
}
