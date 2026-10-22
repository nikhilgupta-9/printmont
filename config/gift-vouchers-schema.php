<?php
// Gift vouchers earned by buying products — admin page: gift-vouchers.php.
//
// Admin creates rules: "spend ₹X on these products (or categories, or anything) → get a voucher
// worth ₹Y / Y% (max ₹Z), usable on an order of ₹M+, valid N days".
// When an order is confirmed (OrderController::finalizeOrder) the best matching rule issues one
// voucher to the customer. It shows on the account Gift Card page and is redeemed in the checkout
// coupon box (CheckoutPricing). Cancelling / refunding the order cancels an unused voucher.

if (!function_exists('ensureGiftVoucherTables')) {
    function ensureGiftVoucherTables($db) {
        static $done = false;
        if ($done) return;
        $db->query("CREATE TABLE IF NOT EXISTS gift_voucher_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            min_spend DECIMAL(10,2) NOT NULL DEFAULT 0,
            voucher_type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
            voucher_value DECIMAL(10,2) NOT NULL DEFAULT 0,
            max_discount DECIMAL(10,2) NULL,
            min_order_to_use DECIMAL(10,2) NOT NULL DEFAULT 0,
            validity_days INT NOT NULL DEFAULT 30,
            applies_to ENUM('all','products','categories') NOT NULL DEFAULT 'products',
            product_ids TEXT NULL,
            category_ids TEXT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS user_gift_vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            rule_id INT NULL,
            order_id INT NULL,
            code VARCHAR(20) NOT NULL UNIQUE,
            title VARCHAR(100) NOT NULL,
            voucher_type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
            voucher_value DECIMAL(10,2) NOT NULL,
            max_discount DECIMAL(10,2) NULL,
            min_order_to_use DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('active','used','expired','cancelled') NOT NULL DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            used_order_id INT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ugv_user (user_id, status),
            KEY idx_ugv_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done = true;
    }

    function giftIdList($json) {
        $list = json_decode((string) $json, true);
        return is_array($list) ? array_values(array_unique(array_map('intval', $list))) : [];
    }

    // Does a product (id + its category ids) fall under a rule?
    function giftRuleCoversProduct(array $rule, int $productId, array $categoryIds) {
        if ($rule['applies_to'] === 'all') return true;
        if ($rule['applies_to'] === 'products') return in_array($productId, giftIdList($rule['product_ids']), true);
        return (bool) array_intersect(giftIdList($rule['category_ids']), array_map('intval', $categoryIds));
    }

    // Value of a voucher as text, e.g. "₹100" or "10% (up to ₹200)"
    function giftVoucherValueLabel($type, $value, $max = null) {
        if ($type === 'percent') {
            return rtrim(rtrim(number_format((float) $value, 2), '0'), '.') . '% off' . ((float) $max > 0 ? ' (up to ₹' . number_format((float) $max, 0) . ')' : '');
        }
        return '₹' . number_format((float) $value, 0);
    }

    // Discount a voucher gives on a cart subtotal
    function giftVoucherDiscount(array $v, float $subtotal) {
        if ($v['voucher_type'] === 'percent') {
            $amount = round($subtotal * (float) $v['voucher_value'] / 100);
            if ((float) $v['max_discount'] > 0) $amount = min($amount, (float) $v['max_discount']);
        } else {
            $amount = (float) $v['voucher_value'];
        }
        return round(max(0, min($amount, $subtotal)), 2);
    }

    function giftActiveRules($db) {
        ensureGiftVoucherTables($db);
        $rules = [];
        $res = $db->query("SELECT * FROM gift_voucher_rules WHERE status = 'active' ORDER BY min_spend ASC, id ASC");
        while ($res && ($row = $res->fetch_assoc())) $rules[] = $row;
        return $rules;
    }

    // Category ids of products: [product_id => [cat, sub, subsub]]
    function giftProductCategories($db, array $productIds) {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if (!$productIds) return [];
        $in = implode(',', $productIds);
        $out = [];
        $res = $db->query("SELECT id, category_id, sub_category_id, sub_sub_category_id FROM products WHERE id IN ($in)");
        while ($res && ($r = $res->fetch_assoc())) {
            $out[(int) $r['id']] = array_values(array_filter([(int) $r['category_id'], (int) $r['sub_category_id'], (int) $r['sub_sub_category_id']]));
        }
        return $out;
    }

    /**
     * Issues the best voucher for a confirmed order (once per order). Returns the voucher row or null.
     */
    function issueGiftVoucherForOrder($db, int $orderId) {
        ensureGiftVoucherTables($db);
        $stmt = $db->prepare("SELECT id, user_id FROM orders WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order || empty($order['user_id'])) return null;

        $stmt = $db->prepare("SELECT id FROM user_gift_vouchers WHERE order_id = ? LIMIT 1");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) return null; // already issued

        $rules = giftActiveRules($db);
        if (!$rules) return null;

        $lines = [];
        $stmt = $db->prepare("SELECT product_id, total_price FROM order_items WHERE order_id = ?");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $lines[] = ['product_id' => (int) $r['product_id'], 'total' => (float) $r['total_price']];
        $cats = giftProductCategories($db, array_column($lines, 'product_id'));

        // Best rule = biggest voucher the order qualifies for
        $best = null;
        $bestWorth = 0;
        foreach ($rules as $rule) {
            $spend = 0;
            foreach ($lines as $l) {
                if (giftRuleCoversProduct($rule, $l['product_id'], $cats[$l['product_id']] ?? [])) $spend += $l['total'];
            }
            if ($spend <= 0 || $spend < (float) $rule['min_spend']) continue;
            // compare vouchers by what they are worth on their own minimum order
            $worth = $rule['voucher_type'] === 'percent'
                ? ((float) $rule['max_discount'] > 0 ? (float) $rule['max_discount'] : max((float) $rule['min_order_to_use'], 1000) * (float) $rule['voucher_value'] / 100)
                : (float) $rule['voucher_value'];
            if ($worth > $bestWorth) { $best = $rule; $bestWorth = $worth; }
        }
        if (!$best) return null;

        $userId = (int) $order['user_id'];
        $expires = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->modify('+' . max(1, (int) $best['validity_days']) . ' day')->format('Y-m-d 23:59:59');
        $max = $best['max_discount'] !== null ? (float) $best['max_discount'] : null;

        for ($i = 0; $i < 5; $i++) {
            $code = 'PMGV' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
            $stmt = $db->prepare("INSERT INTO user_gift_vouchers (user_id, rule_id, order_id, code, title, voucher_type, voucher_value, max_discount, min_order_to_use, expires_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ruleId = (int) $best['id'];
            $minUse = (float) $best['min_order_to_use'];
            $value = (float) $best['voucher_value'];
            $stmt->bind_param('iiisssddds', $userId, $ruleId, $orderId, $code, $best['title'], $best['voucher_type'], $value, $max, $minUse, $expires);
            if ($stmt->execute()) {
                return ['code' => $code, 'title' => $best['title'], 'value_label' => giftVoucherValueLabel($best['voucher_type'], $value, $max)];
            }
            if ($stmt->errno !== 1062) break; // not a duplicate code → give up
        }
        return null;
    }

    // Order cancelled / refunded → its unused voucher is cancelled
    function cancelGiftVouchersForOrder($db, int $orderId) {
        ensureGiftVoucherTables($db);
        $stmt = $db->prepare("UPDATE user_gift_vouchers SET status = 'cancelled' WHERE order_id = ? AND status = 'active'");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
    }

    // Marks vouchers past their date as expired
    function expireGiftVouchers($db) {
        ensureGiftVoucherTables($db);
        $db->query("UPDATE user_gift_vouchers SET status = 'expired' WHERE status = 'active' AND expires_at < NOW()");
    }

    // An active voucher of this user by code (for checkout), or null
    function findUsableGiftVoucher($db, int $userId, string $code) {
        ensureGiftVoucherTables($db);
        $stmt = $db->prepare("SELECT * FROM user_gift_vouchers WHERE UPPER(code) = UPPER(?) AND user_id = ? LIMIT 1");
        $stmt->bind_param('si', $code, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }
}
