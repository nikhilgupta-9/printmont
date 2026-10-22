<?php
// Server-side price of a cart: the single source of truth for the cart / checkout screens
// (user-api.php?action=checkout_quote) and for the order that gets created (create_order).
// Never trusts prices or discounts sent by the browser.
//
// Unit price follows the product page (ProductDetails.jsx calculateDynamicUnitPrice):
//   selling price (live sale applied) → bulk tier or quantity tier → + size / colour add-on
//   → + customization option extras.

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/ProductController.php');
require_once(__DIR__ . '/../config/gift-vouchers-schema.php');

class CheckoutPricing
{
    const FREE_DELIVERY_ABOVE = 1000; // selling subtotal above this ships free
    const DELIVERY_CHARGE = 160;

    private $db;
    private $productController;

    public function __construct($db = null)
    {
        if (!$db) {
            $db = (new Database())->getConnection();
        }
        $this->db = $db;
        $this->productController = new ProductController($db);
    }

    /**
     * @param array  $rawItems   [{product_id|id, quantity, attributes:{size,color,customization}}]
     * @param string $couponCode
     * @param bool   $useCoins
     * @param ?int   $userId     coins need a logged-in user
     */
    public function quote(array $rawItems, $couponCode = '', $useCoins = false, $userId = null): array
    {
        $lines = [];
        $errors = [];
        $subtotal = 0.0;
        $mrpTotal = 0.0;
        $itemCount = 0;

        foreach ($rawItems as $raw) {
            $productId = (int) ($raw['product_id'] ?? $raw['id'] ?? 0);
            $quantity = max(1, (int) ($raw['quantity'] ?? 1));
            $product = $productId > 0 ? $this->productController->getProductByIdApi($productId) : null;

            if (!$product || ($product['status'] ?? '') !== 'active') {
                $errors[] = "A product in your cart is no longer available (id {$productId}).";
                continue;
            }
            if (($product['out_of_stock_status'] ?? '') === 'out_of_stock'
                || ((int) $product['stock_quantity'] <= 0 && ($product['out_of_stock_status'] ?? '') !== 'pre_order')) {
                $errors[] = "{$product['name']} is out of stock.";
                continue;
            }

            $attributes = $raw['attributes'] ?? [];
            if (is_string($attributes)) $attributes = json_decode($attributes, true) ?: [];

            $unit = $this->unitPrice($product, $quantity, $attributes);
            $mrp = max((float) ($product['regular_price'] ?? 0), $unit);
            $lineTotal = round($unit * $quantity, 2);

            $subtotal += $lineTotal;
            $mrpTotal += round($mrp * $quantity, 2);
            $itemCount += $quantity;

            $lines[] = [
                'product_id'  => $productId,
                'name'        => $product['name'],
                'sku'         => $product['sku'] ?: ('PM-' . $productId),
                'image'       => $product['primary_image'] ?? ($product['images'][0]['image_url'] ?? ($product['images'][0] ?? '')),
                'category_id' => (int) ($product['category_id'] ?? 0),
                'sub_category_id' => (int) ($product['sub_category_id'] ?? 0),
                'sub_sub_category_id' => (int) ($product['sub_sub_category_id'] ?? 0),
                'quantity'    => $quantity,
                'unit_price'  => $unit,
                'mrp'         => $mrp,
                'total_price' => $lineTotal,
                'attributes'  => $attributes,
            ];
        }

        $subtotal = round($subtotal, 2);
        $delivery = ($subtotal > self::FREE_DELIVERY_ABOVE || $subtotal == 0) ? 0 : self::DELIVERY_CHARGE;

        $coupon = $this->applyCoupon(trim((string) $couponCode), $lines, $subtotal, $userId ? (int) $userId : null);
        $afterCoupon = max(0, $subtotal + $delivery - $coupon['amount']);

        $coins = ['requested' => (bool) $useCoins, 'balance' => 0, 'used' => 0, 'amount' => 0.0, 'max_per_order' => 0];
        if ($userId) {
            $coins = array_merge($coins, $this->coins((int) $userId, $afterCoupon, (bool) $useCoins));
        }

        $grandTotal = round(max(0, $afterCoupon - $coins['amount']), 2);

        return [
            'success'     => count($errors) === 0 && count($lines) > 0,
            'errors'      => $errors,
            'items'       => $lines,
            'item_count'  => $itemCount,
            'mrp_total'   => round($mrpTotal, 2),
            'subtotal'    => $subtotal,
            'discount'    => round(max(0, $mrpTotal - $subtotal), 2), // MRP → selling price
            'delivery'    => $delivery,
            'delivery_free_above' => self::FREE_DELIVERY_ABOVE,
            'coupon'      => $coupon,
            'coins'       => $coins,
            'grand_total' => $grandTotal,
            'you_save'    => round(max(0, $mrpTotal - $subtotal) + $coupon['amount'] + $coins['amount'], 2),
        ];
    }

    // Same rules as the product page
    private function unitPrice(array $product, int $quantity, array $attributes): float
    {
        $unit = (float) ($product['selling_price'] ?? $product['price'] ?? 0);

        $bulkTier = null;
        foreach (($product['bulk_order_pricing'] ?? []) as $tier) {
            $min = (float) ($tier['min_qty'] ?? $tier['qty'] ?? 0);
            $max = (float) ($tier['max_qty'] ?? 0) > 0 ? (float) $tier['max_qty'] : INF;
            if ($quantity >= $min && $quantity <= $max && (float) ($tier['price'] ?? 0) > 0) {
                $bulkTier = $tier;
                break;
            }
        }
        if ($bulkTier) {
            $unit = (float) $bulkTier['price'];
        } else {
            $best = null;
            foreach (($product['quantity_pricing'] ?? []) as $tier) {
                $q = (float) ($tier['qty'] ?? $tier['min_qty'] ?? 0);
                if ((float) ($tier['price'] ?? 0) > 0 && $quantity >= $q && ($best === null || $q > $best[0])) {
                    $best = [$q, (float) $tier['price']];
                }
            }
            if ($best) $unit = $best[1];
        }

        $unit += $this->optionAddOn($product['sizes'] ?? [], $attributes['size'] ?? null);
        $unit += $this->optionAddOn($product['colors'] ?? [], $attributes['color'] ?? null);

        if (!empty($product['customization_label_status']) || !empty($product['customization_data'])) {
            $unit += $this->customizationExtra($product['customization_data'] ?? [], $attributes['customization'] ?? []);
        }

        return round($unit, 2);
    }

    private static function optionValue($item)
    {
        if (!is_array($item)) return (string) $item;
        foreach (['name', 'label', 'size', 'material', 'color'] as $k) {
            if (isset($item[$k]) && $item[$k] !== '') return (string) $item[$k];
        }
        return '';
    }

    private function optionAddOn($list, $selected): float
    {
        if ($selected === null || $selected === '' || !is_array($list)) return 0.0;
        foreach ($list as $opt) {
            if (is_array($opt) && self::optionValue($opt) === (string) $selected) {
                return (float) ($opt['price'] ?? 0);
            }
        }
        return 0.0;
    }

    // ProductCustomizationForm.jsx customizationExtraPrice()
    private function customizationExtra($fields, $values): float
    {
        if (!is_array($fields) || !is_array($values)) return 0.0;
        $sum = 0.0;
        foreach ($fields as $f) {
            $label = $f['label'] ?? '';
            $type = $f['type'] ?? '';
            $opts = is_array($f['options'] ?? null) ? $f['options'] : [];
            $v = $values[$label] ?? [];
            if (!is_array($v)) continue;
            if ($type === 'multi') {
                $chosen = is_array($v['values'] ?? null) ? $v['values'] : [];
                foreach ($opts as $o) {
                    if (in_array($o['label'] ?? null, $chosen, true)) $sum += (float) ($o['price'] ?? 0);
                }
            } elseif (in_array($type, ['select', 'radio', 'color'], true)) {
                foreach ($opts as $o) {
                    if (($o['label'] ?? null) === ($v['value'] ?? null)) {
                        $sum += (float) ($o['price'] ?? 0);
                        break;
                    }
                }
            }
        }
        return $sum;
    }

    /** Coupon from admin coupons.php. Returns ['code','amount','valid','message','id'] */
    private function applyCoupon(string $code, array $lines, float $subtotal, ?int $userId = null): array
    {
        $result = ['code' => $code, 'amount' => 0.0, 'valid' => false, 'message' => '', 'id' => null, 'source' => 'coupon'];
        if ($code === '') return $result;

        // Gift voucher earned on an earlier order (account Gift Card page)
        if ($userId && stripos($code, 'PMGV') === 0) {
            return $this->applyGiftVoucher($code, $subtotal, $userId, $result);
        }

        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE UPPER(coupon_code) = UPPER(?) LIMIT 1");
        if (!$stmt) {
            $result['message'] = 'Coupons are unavailable right now.';
            return $result;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $today = date('Y-m-d');
        if (!$coupon || ($coupon['status'] ?? '') !== 'active') {
            $result['message'] = 'This coupon code is not valid.';
            return $result;
        }
        if (!empty($coupon['start_date']) && $coupon['start_date'] > $today) {
            $result['message'] = 'This coupon is not active yet.';
            return $result;
        }
        if (!empty($coupon['end_date']) && $coupon['end_date'] < $today) {
            $result['message'] = 'This coupon has expired.';
            return $result;
        }
        $limit = (int) ($coupon['max_uses'] ?: $coupon['quantity'] ?: 0);
        if ($limit > 0 && (int) $coupon['used_count'] >= $limit) {
            $result['message'] = 'This coupon has been fully used.';
            return $result;
        }

        // Category coupons only count the matching products
        $eligible = $subtotal;
        $catId = (int) ($coupon['sub_sub_category_id'] ?: $coupon['sub_category_id'] ?: $coupon['category_id'] ?: 0);
        if ($catId > 0) {
            $eligible = 0.0;
            foreach ($lines as $l) {
                if (in_array($catId, [$l['category_id'], $l['sub_category_id'], $l['sub_sub_category_id']], true)) {
                    $eligible += $l['total_price'];
                }
            }
            if ($eligible <= 0) {
                $result['message'] = 'This coupon does not apply to the products in your cart.';
                return $result;
            }
        }

        $minOrder = (float) ($coupon['min_order_value'] ?? 0);
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $result['message'] = 'Add items worth ₹' . number_format($minOrder - $subtotal, 0) . ' more to use this coupon.';
            return $result;
        }

        $type = (!empty($coupon['discount_format'])) ? $coupon['discount_format'] : ((!empty($coupon['discount_type'])) ? $coupon['discount_type'] : 'percentage');
        $value = (float) $coupon['discount_value'];
        $amount = $type === 'fixed' ? min($value, $eligible) : round($eligible * $value / 100, 2);

        $result['code'] = $coupon['coupon_code'];
        $result['id'] = (int) $coupon['id'];
        $result['amount'] = round(max(0, $amount), 2);
        $result['valid'] = $result['amount'] > 0;
        $result['message'] = $result['valid'] ? 'Coupon applied' : 'This coupon gives no discount on this cart.';
        return $result;
    }

    private function applyGiftVoucher(string $code, float $subtotal, int $userId, array $result): array
    {
        $result['source'] = 'gift_voucher';
        $v = findUsableGiftVoucher($this->db, $userId, $code);
        if (!$v) {
            $result['message'] = 'This gift voucher code is not valid for your account.';
            return $result;
        }
        if ($v['status'] === 'active' && strtotime($v['expires_at']) < time()) $v['status'] = 'expired';
        if ($v['status'] !== 'active') {
            $result['message'] = [
                'used' => 'This gift voucher has already been used.',
                'expired' => 'This gift voucher has expired.',
                'cancelled' => 'This gift voucher was cancelled because its order was cancelled.',
            ][$v['status']] ?? 'This gift voucher cannot be used.';
            return $result;
        }
        $minOrder = (float) $v['min_order_to_use'];
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $result['message'] = 'Add items worth ₹' . number_format($minOrder - $subtotal, 0) . ' more to use this gift voucher.';
            return $result;
        }
        $result['code'] = $v['code'];
        $result['id'] = (int) $v['id'];
        $result['amount'] = giftVoucherDiscount($v, $subtotal);
        $result['valid'] = $result['amount'] > 0;
        $result['message'] = $result['valid'] ? 'Gift voucher applied' : 'This gift voucher gives no discount on this cart.';
        return $result;
    }

    /** Printmont coins: 1 coin = coin_value_inr, capped by max_coins_per_order and the payable amount */
    private function coins(int $userId, float $payable, bool $useCoins): array
    {
        $balance = 0;
        $stmt = $this->db->prepare("SELECT balance FROM user_coins WHERE user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $balance = $row ? max(0, (int) $row['balance']) : 0;
        }

        $maxPerOrder = 50;
        $coinValue = 1.0;
        $res = $this->db->query("SELECT max_coins_per_order, coin_value_inr FROM coin_settings ORDER BY id LIMIT 1");
        if ($res && ($s = $res->fetch_assoc())) {
            $maxPerOrder = (int) $s['max_coins_per_order'];
            $coinValue = (float) $s['coin_value_inr'] ?: 1.0;
        }

        $usable = (int) min($balance, $maxPerOrder, floor($payable / $coinValue));
        $used = $useCoins ? max(0, $usable) : 0;

        return [
            'balance'       => $balance,
            'usable'        => max(0, $usable),
            'max_per_order' => $maxPerOrder,
            'used'          => $used,
            'amount'        => round($used * $coinValue, 2),
        ];
    }
}
