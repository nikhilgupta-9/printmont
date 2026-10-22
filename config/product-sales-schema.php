<?php
// Timed sales on products — admin page: product-sales.php.
// While a sale is live the product API returns the sale price as the selling price and a `sale`
// block (label + end time) that the product page shows as a countdown above the product name.
//
// Times are entered and stored as India time (IST). PHP here runs in another timezone, so every
// conversion goes through SALE_TIMEZONE explicitly and the frontend only ever receives epoch seconds.

if (!defined('SALE_TIMEZONE')) define('SALE_TIMEZONE', 'Asia/Kolkata');

if (!function_exists('ensureProductSalesTable')) {
    function ensureProductSalesTable($db) {
        $db->query("CREATE TABLE IF NOT EXISTS product_sales (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id INT(11) NOT NULL,
            label VARCHAR(60) NOT NULL DEFAULT 'Flash Sale',
            discount_type ENUM('price','percent') NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ps_product (product_id, status, starts_at, ends_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('saleTimestamp')) {
    // "2026-10-20 18:00:00" (IST) → epoch seconds; false when unparsable
    function saleTimestamp($datetime) {
        if (!$datetime) return false;
        try {
            return (new DateTime($datetime, new DateTimeZone(SALE_TIMEZONE)))->getTimestamp();
        } catch (Exception $e) {
            return false;
        }
    }

    // Normalises an admin "datetime-local" value (IST) to "Y-m-d H:i:s"; null when invalid
    function saleNormaliseDatetime($value) {
        $value = trim((string) $value);
        if ($value === '') return null;
        try {
            return (new DateTime(str_replace('T', ' ', $value), new DateTimeZone(SALE_TIMEZONE)))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    // Price during the sale. percent = off the product's current selling price.
    function salePriceFor(array $sale, $currentPrice) {
        $currentPrice = (float) $currentPrice;
        $value = (float) $sale['discount_value'];
        if ($sale['discount_type'] === 'price') return max(0, round($value, 2));
        return max(0, round($currentPrice * (1 - $value / 100))); // % off → whole rupees
    }

    // upcoming | live | ended | off
    function saleState(array $sale, $now = null) {
        $now = $now ?? time();
        if (($sale['status'] ?? 'active') !== 'active') return 'off';
        $start = saleTimestamp($sale['starts_at']);
        $end = saleTimestamp($sale['ends_at']);
        if ($start === false || $end === false) return 'off';
        if ($now < $start) return 'upcoming';
        if ($now >= $end) return 'ended';
        return 'live';
    }
}

if (!function_exists('getLiveSaleForProduct')) {
    // The sale running right now for a product (latest-starting one if several overlap), or null.
    // All active sales are read once per request, so product listings don't query per product.
    function getLiveSaleForProduct($db, $productId) {
        static $liveByProduct = null;
        if ($liveByProduct === null) {
            $liveByProduct = [];
            ensureProductSalesTable($db);
            $res = $db->query("SELECT * FROM product_sales WHERE status = 'active' ORDER BY starts_at DESC");
            $now = time();
            while ($res && ($row = $res->fetch_assoc())) {
                $pid = (int) $row['product_id'];
                if (!isset($liveByProduct[$pid]) && saleState($row, $now) === 'live') {
                    $liveByProduct[$pid] = $row;
                }
            }
        }
        return $liveByProduct[(int) $productId] ?? null;
    }
}
