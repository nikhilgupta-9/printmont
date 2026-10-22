<?php
// Coupon API for Storefront (Flipkart-like Coupon & Discount System)
//   GET  ?action=available    List all currently active & usable platform coupons
//   GET  ?action=all          List all coupons (active, used, expired) for customer account
//   POST ?action=validate     Validate a coupon code with a cart / subtotal
header('Content-Type: application/json');
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../controllers/CheckoutPricing.php';

function jsonOut($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$db = (new Database())->getConnection();
$controller = new CouponController();
$action = $_GET['action'] ?? 'available';

// Format coupon item for consumer display
function formatCustomerCoupon(array $c): array {
    $format = $c['discount_format'] ?? 'percentage';
    $val = (float)($c['discount_value'] ?? 0);
    $minOrder = (float)($c['min_order_value'] ?? 0);
    
    $discountLabel = ($format === 'percentage') 
        ? (rtrim(rtrim(number_format($val, 2), '0'), '.') . '% OFF')
        : ('₹' . number_format($val, 0) . ' OFF');

    $minOrderLabel = ($minOrder > 0)
        ? ('Orders of ₹' . number_format($minOrder, 0) . ' or more')
        : 'On any order amount';

    $applies = array_filter([
        $c['category_name']         ?? null,
        $c['sub_category_name']     ?? null,
        $c['sub_sub_category_name'] ?? null,
    ]);
    $appliesTo = !empty($applies) ? implode(' › ', $applies) : 'All Products';

    $isExpired = false;
    $daysLeft = null;
    if (!empty($c['end_date'])) {
        $today = date('Y-m-d');
        if ($c['end_date'] < $today) {
            $isExpired = true;
            $daysLeft = 0;
        } else {
            $diff = (strtotime($c['end_date']) - strtotime($today)) / 86400;
            $daysLeft = max(0, (int)round($diff));
        }
    }

    $status = ($isExpired || ($c['status'] ?? '') === 'expired') ? 'expired' : ($c['status'] ?? 'active');

    $terms = [];
    if ($format === 'percentage') {
        $terms[] = "Get {$discountLabel} on eligible cart total.";
    } else {
        $terms[] = "Flat ₹" . number_format($val, 0) . " instant discount.";
    }
    if ($minOrder > 0) {
        $terms[] = "Minimum cart value required is ₹" . number_format($minOrder, 0) . ".";
    }
    $terms[] = "Applicable on {$appliesTo}.";
    if (!empty($c['quantity']) && (int)$c['quantity'] > 0) {
        $terms[] = "Limited quantity offer (" . (int)$c['quantity'] . " total redemptions).";
    }
    if (!empty($c['end_date'])) {
        $terms[] = "Valid till " . date('d M Y', strtotime($c['end_date'])) . ".";
    }

    return [
        'id'               => (int)$c['id'],
        'code'             => strtoupper(trim($c['coupon_code'])),
        'title'            => $c['title'] ?: ($discountLabel . ' on ' . $appliesTo),
        'description'      => $c['description'] ?: implode(' ', $terms),
        'discount_format'  => $format,
        'discount_value'   => $val,
        'discount_label'   => $discountLabel,
        'min_order_value'  => $minOrder,
        'min_order_label'  => $minOrderLabel,
        'applies_to'       => $appliesTo,
        'start_date'       => $c['start_date'],
        'end_date'         => $c['end_date'],
        'days_left'        => $daysLeft,
        'status'           => $status,
        'terms'            => $terms,
        'used_count'       => (int)($c['used_count'] ?? 0),
        'quantity'         => (int)($c['quantity'] ?? 0),
    ];
}

switch ($action) {
    case 'available':
        $raw = $controller->getActiveCouponsForCustomer();
        $formatted = array_values(array_map('formatCustomerCoupon', $raw));
        jsonOut([
            'success' => true,
            'count'   => count($formatted),
            'data'    => $formatted,
        ]);
        break;

    case 'all':
        $raw = $controller->getAllCouponsForCustomer();
        $formatted = array_values(array_map('formatCustomerCoupon', $raw));
        
        $activeCount = 0;
        $expiredCount = 0;
        foreach ($formatted as $c) {
            if ($c['status'] === 'active') $activeCount++;
            else $expiredCount++;
        }

        jsonOut([
            'success' => true,
            'count'   => count($formatted),
            'summary' => [
                'active'  => $activeCount,
                'expired' => $expiredCount,
                'total'   => count($formatted),
            ],
            'data'    => $formatted,
        ]);
        break;

    case 'validate':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $code = trim($input['code'] ?? $input['coupon_code'] ?? '');
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $useCoins = !empty($input['use_coins']);

        if (!$code) {
            jsonOut(['success' => false, 'error' => 'Please enter a coupon code'], 400);
        }

        $quote = (new CheckoutPricing($db))->quote($items, $code, $useCoins);
        if (!empty($quote['coupon']['valid'])) {
            jsonOut([
                'success' => true,
                'coupon'  => $quote['coupon'],
                'quote'   => $quote,
            ]);
        } else {
            jsonOut([
                'success' => false,
                'message' => $quote['coupon']['message'] ?: 'Invalid coupon code',
                'coupon'  => $quote['coupon'],
            ], 400);
        }
        break;

    default:
        jsonOut(['success' => false, 'error' => "Unknown action '{$action}'"], 404);
}
