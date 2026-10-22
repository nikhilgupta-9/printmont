<?php
// Gift vouchers for the storefront (admin rules: gift-vouchers.php)
//   GET ?action=my                  logged-in customer's vouchers (JWT)
//   GET ?action=offers              active "buy ₹X → get voucher" offers
//   GET ?action=product&id=123      offers that this product counts towards
header('Content-Type: application/json');
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/gift-vouchers-schema.php';
require_once __DIR__ . '/../controllers/AuthControllerAPI.php';

if (!function_exists('getBearerToken')) {
    function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if (!$header && function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $name => $value) {
                if (strtolower($name) === 'authorization') { $header = $value; break; }
            }
        }
        return ($header && preg_match('/Bearer\s(\S+)/i', trim($header), $m)) ? $m[1] : null;
    }
}

function gvOut($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Public shape of a rule
function gvOffer(array $r): array {
    $max = $r['max_discount'] !== null ? (float) $r['max_discount'] : null;
    return [
        'id' => (int) $r['id'],
        'title' => $r['title'],
        'min_spend' => (float) $r['min_spend'],
        'voucher_type' => $r['voucher_type'],
        'voucher_value' => (float) $r['voucher_value'],
        'max_discount' => $max,
        'value_label' => giftVoucherValueLabel($r['voucher_type'], $r['voucher_value'], $max),
        'min_order_to_use' => (float) $r['min_order_to_use'],
        'validity_days' => (int) $r['validity_days'],
        'applies_to' => $r['applies_to'],
    ];
}

$database = new Database();
$db = $database->getConnection();
ensureGiftVoucherTables($db);
$action = $_GET['action'] ?? 'my';

switch ($action) {
    case 'offers':
        gvOut(['success' => true, 'data' => array_map('gvOffer', giftActiveRules($db))]);

    case 'product':
        $productId = (int) ($_GET['id'] ?? 0);
        if ($productId <= 0) gvOut(['success' => false, 'error' => 'Product id is required'], 400);
        $cats = giftProductCategories($db, [$productId])[$productId] ?? null;
        if ($cats === null) gvOut(['success' => true, 'data' => []]);
        $offers = [];
        foreach (giftActiveRules($db) as $rule) {
            if (giftRuleCoversProduct($rule, $productId, $cats)) $offers[] = gvOffer($rule);
        }
        gvOut(['success' => true, 'data' => $offers]);

    case 'my':
        $auth = (new AuthController($db))->verifyToken(getBearerToken());
        if (empty($auth['success'])) {
            gvOut(['success' => false, 'error' => 'Please log in to see your gift vouchers', 'require_login' => true], 401);
        }
        $userId = (int) $auth['user']['id'];
        expireGiftVouchers($db);

        $stmt = $db->prepare("SELECT v.*, o.order_number AS earned_order_number, u.order_number AS used_order_number
                              FROM user_gift_vouchers v
                              LEFT JOIN orders o ON o.id = v.order_id
                              LEFT JOIN orders u ON u.id = v.used_order_id
                              WHERE v.user_id = ?
                              ORDER BY FIELD(v.status, 'active', 'used', 'expired', 'cancelled'), v.created_at DESC");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();

        $vouchers = [];
        $summary = ['active' => 0, 'used' => 0, 'expired' => 0, 'cancelled' => 0, 'active_value' => 0.0];
        while ($v = $res->fetch_assoc()) {
            $max = $v['max_discount'] !== null ? (float) $v['max_discount'] : null;
            $summary[$v['status']]++;
            if ($v['status'] === 'active' && $v['voucher_type'] === 'fixed') $summary['active_value'] += (float) $v['voucher_value'];
            $vouchers[] = [
                'id' => (int) $v['id'],
                'code' => $v['code'],
                'title' => $v['title'],
                'voucher_type' => $v['voucher_type'],
                'voucher_value' => (float) $v['voucher_value'],
                'max_discount' => $max,
                'value_label' => giftVoucherValueLabel($v['voucher_type'], $v['voucher_value'], $max),
                'min_order_to_use' => (float) $v['min_order_to_use'],
                'status' => $v['status'],
                'expires_at' => $v['expires_at'],
                'created_at' => $v['created_at'],
                'used_at' => $v['used_at'],
                'earned_order_number' => $v['earned_order_number'],
                'used_order_number' => $v['used_order_number'],
            ];
        }
        gvOut(['success' => true, 'data' => $vouchers, 'summary' => $summary]);

    default:
        gvOut(['success' => false, 'error' => 'Unknown action'], 400);
}
