<?php
// printmont-backend/services/CoinService.php - Handles coin settings, welcome bonuses, purchase earning, and admin grants

require_once __DIR__ . '/../config/database.php';

class CoinService
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
    }

    /**
     * Get current coin settings
     */
    public function getSettings(): array
    {
        $res = $this->db->query("SELECT * FROM coin_settings LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'coins_per_100_spent' => (int)$row['coins_per_100_spent'],
                'max_coins_per_order' => (int)$row['max_coins_per_order'],
                'coin_value_inr' => (float)$row['coin_value_inr'],
                'welcome_bonus_coins' => (int)$row['welcome_bonus_coins'],
                'review_bonus_coins' => (int)$row['review_bonus_coins'],
                'min_order_amount_for_coins' => (float)($row['min_order_amount_for_coins'] ?? 100.00),
                'enable_welcome_bonus' => (bool)($row['enable_welcome_bonus'] ?? 1),
                'enable_purchase_coins' => (bool)($row['enable_purchase_coins'] ?? 1)
            ];
        }

        return [
            'coins_per_100_spent' => 2,
            'max_coins_per_order' => 50,
            'coin_value_inr' => 1.00,
            'welcome_bonus_coins' => 100,
            'review_bonus_coins' => 25,
            'min_order_amount_for_coins' => 100.00,
            'enable_welcome_bonus' => true,
            'enable_purchase_coins' => true
        ];
    }

    /**
     * Get or initialize user's coins with welcome bonus
     */
    public function getUserCoins(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_coins WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userCoins = $stmt->get_result()->fetch_assoc();

        if (!$userCoins) {
            $settings = $this->getSettings();
            $welcomeBonus = $settings['welcome_bonus_coins'];

            $init = $this->db->prepare("INSERT INTO user_coins (user_id, balance, tier, lifetime_earned, lifetime_spent) VALUES (?, ?, 'Silver Member', ?, 0)");
            $init->bind_param("iii", $userId, $welcomeBonus, $welcomeBonus);
            $init->execute();

            if ($welcomeBonus > 0) {
                $tx = $this->db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description) VALUES (?, ?, 'credit', 'Signup Welcome Bonus Coins', 'Welcome bonus credited for registering on Printmont')");
                $tx->bind_param("ii", $userId, $welcomeBonus);
                $tx->execute();
            }

            return [
                'user_id' => $userId,
                'balance' => $welcomeBonus,
                'tier' => 'Silver Member',
                'lifetime_earned' => $welcomeBonus,
                'lifetime_spent' => 0
            ];
        }

        return [
            'user_id' => (int)$userCoins['user_id'],
            'balance' => (int)$userCoins['balance'],
            'tier' => $userCoins['tier'] ?? 'Gold Member',
            'lifetime_earned' => (int)$userCoins['lifetime_earned'],
            'lifetime_spent' => (int)$userCoins['lifetime_spent']
        ];
    }

    /**
     * Award coins on order purchase / delivery
     */
    public function awardPurchaseCoins(int $orderId): array
    {
        $oStmt = $this->db->prepare("SELECT id, order_number, user_id, customer_id, grand_total, status FROM orders WHERE id = ? LIMIT 1");
        $oStmt->bind_param("i", $orderId);
        $oStmt->execute();
        $order = $oStmt->get_result()->fetch_assoc();

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $userId = (int)($order['user_id'] ?? $order['customer_id'] ?? 0);
        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Guest order; coins not awarded'];
        }

        $orderNum = $order['order_number'] ?: ('#' . $order['id']);

        // Check if already awarded
        $check = $this->db->prepare("SELECT id FROM coin_transactions WHERE user_id = ? AND order_id = ? AND type = 'credit' LIMIT 1");
        $check->bind_param("is", $userId, $orderNum);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => true, 'message' => 'Coins already awarded for this order', 'awarded' => 0];
        }

        $settings = $this->getSettings();
        if (!$settings['enable_purchase_coins']) {
            return ['success' => false, 'error' => 'Purchase coins disabled in settings'];
        }

        $grandTotal = (float)$order['grand_total'];
        if ($grandTotal < $settings['min_order_amount_for_coins']) {
            return ['success' => false, 'error' => 'Order below minimum required amount for coins'];
        }

        $earnedCoins = (int)floor(($grandTotal / 100.0) * $settings['coins_per_100_spent']);
        if ($earnedCoins > $settings['max_coins_per_order']) {
            $earnedCoins = $settings['max_coins_per_order'];
        }

        if ($earnedCoins <= 0) {
            return ['success' => true, 'awarded' => 0];
        }

        // Ensure user coins exist
        $this->getUserCoins($userId);

        $upd = $this->db->prepare("UPDATE user_coins SET balance = balance + ?, lifetime_earned = lifetime_earned + ? WHERE user_id = ?");
        $upd->bind_param("iii", $earnedCoins, $earnedCoins, $userId);
        $upd->execute();

        $title = "Earned on Order " . $orderNum;
        $desc = "{$settings['coins_per_100_spent']} Coins per ₹100 spent on delivered order (Total: ₹" . number_format($grandTotal, 2) . ")";

        $tx = $this->db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description, order_id) VALUES (?, ?, 'credit', ?, ?, ?)");
        $tx->bind_param("iisss", $userId, $earnedCoins, $title, $desc, $orderNum);
        $tx->execute();

        return [
            'success' => true,
            'awarded' => $earnedCoins,
            'message' => "Awarded {$earnedCoins} SuperCoins to customer!"
        ];
    }

    /**
     * Admin manually give or deduct coins from user
     */
    public function adjustCoins(int $userId, string $type, int $amount, string $reason = '', ?int $adminId = null): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero'];
        }

        $userCoins = $this->getUserCoins($userId);
        $currentBalance = $userCoins['balance'];

        if ($type === 'debit' && $currentBalance < $amount) {
            return ['success' => false, 'error' => "User only has {$currentBalance} coins available"];
        }

        $newBalance = $type === 'credit' ? ($currentBalance + $amount) : ($currentBalance - $amount);

        $updQuery = $type === 'credit'
            ? "UPDATE user_coins SET balance = ?, lifetime_earned = lifetime_earned + ? WHERE user_id = ?"
            : "UPDATE user_coins SET balance = ?, lifetime_spent = lifetime_spent + ? WHERE user_id = ?";

        $upd = $this->db->prepare($updQuery);
        $upd->bind_param("iii", $newBalance, $amount, $userId);
        if (!$upd->execute()) {
            return ['success' => false, 'error' => 'Failed to update user coins'];
        }

        $title = $type === 'credit' ? "Admin Bonus Coins Credited" : "Admin Coin Deduction";
        $desc = !empty($reason) ? $reason : ("Manual adjustment by Admin #" . ($adminId ?? 1));

        $tx = $this->db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description) VALUES (?, ?, ?, ?, ?)");
        $tx->bind_param("iisss", $userId, $amount, $type, $title, $desc);
        $tx->execute();

        return [
            'success' => true,
            'message' => "{$amount} coins " . ($type === 'credit' ? 'credited to' : 'deducted from') . " user successfully!",
            'new_balance' => $newBalance
        ];
    }

    /**
     * Award coins for submitting a verified product review
     */
    public function awardReviewCoins(int $userId, int $productId, ?string $productName = null): array
    {
        $settings = $this->getSettings();
        $bonusCoins = (int)($settings['review_bonus_coins'] ?? 25);

        if ($bonusCoins <= 0) {
            return ['success' => true, 'awarded' => 0, 'message' => 'Review bonus coins disabled in settings'];
        }

        // Check if already awarded for this product to prevent duplicate coin claims
        $checkPattern = "%product #{$productId}%";
        $check = $this->db->prepare("SELECT id FROM coin_transactions WHERE user_id = ? AND type = 'credit' AND (title LIKE '%Review%' OR description LIKE ?) LIMIT 1");
        $check->bind_param("is", $userId, $checkPattern);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => true, 'awarded' => 0, 'message' => 'Coins already awarded for reviewing this product'];
        }

        // Ensure user coins profile exists
        $this->getUserCoins($userId);

        $upd = $this->db->prepare("UPDATE user_coins SET balance = balance + ?, lifetime_earned = lifetime_earned + ? WHERE user_id = ?");
        $upd->bind_param("iii", $bonusCoins, $bonusCoins, $userId);
        if (!$upd->execute()) {
            return ['success' => false, 'error' => 'Failed to update user coins balance'];
        }

        $title = "Earned on Product Review";
        $productLabel = $productName ? "'{$productName}'" : "product #{$productId}";
        $desc = "Bonus {$bonusCoins} SuperCoins credited for reviewing {$productLabel}";

        $tx = $this->db->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description) VALUES (?, ?, 'credit', ?, ?)");
        $tx->bind_param("iiss", $userId, $bonusCoins, $title, $desc);
        $tx->execute();

        return [
            'success' => true,
            'awarded' => $bonusCoins,
            'message' => "Congratulations! You earned {$bonusCoins} SuperCoins for writing a product review!"
        ];
    }
}

