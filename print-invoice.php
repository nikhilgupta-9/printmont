<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/OrderController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID");
}

$orderId = (int)$_GET['id'];
$orderController = new OrderController();
$order = $orderController->getOrderById($orderId);
$orderItems = $orderController->getOrderItems($orderId);

if (!$order) {
    die("Order not found");
}

// Retrieve active logo from Logo Management
require_once 'controllers/LogoController.php';
$logoController = new LogoController();
$activeLogoResult = $logoController->getActiveLogoByType('desktop_logo');
$logoUrl = '';
if ($activeLogoResult && $activeLogoRow = $activeLogoResult->fetch_assoc()) {
    $logoUrl = resolveBackendImageUrl($activeLogoRow['file_path'] . $activeLogoRow['file_name']);
}
if (empty($logoUrl) && file_exists(__DIR__ . '/uploads/logos/691aec080786d_1763372040.png')) {
    $logoUrl = 'uploads/logos/691aec080786d_1763372040.png';
}

// Helper to convert number to words for INR
function getIndianCurrencyWords(float $number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two',
        3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
        7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
        13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($decimal) ? "and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return 'Rupees ' . trim($result) . ' ' . $points . ' Only';
}

$grandTotal = $order['grand_total'] ?? $order['total_amount'];
$amountInWords = getIndianCurrencyWords((float)$grandTotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tax Invoice - #<?php echo htmlspecialchars($order['order_number']); ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
            padding: 30px 15px;
        }
        .invoice-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 40px;
            position: relative;
        }
        .no-print {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-primary { background: #0b53a1; color: #fff; }
        .btn-primary:hover { background: #083c75; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }
        .company-brand h1 {
            font-size: 26px;
            font-weight: 800;
            color: #0b53a1;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .company-brand p {
            color: #64748b;
            font-size: 12px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-title .badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 25px;
        }
        .meta-item {
            font-size: 12.5px;
        }
        .meta-item span.label {
            color: #64748b;
            font-weight: 500;
            display: inline-block;
            width: 120px;
        }
        .meta-item span.val {
            color: #0f172a;
            font-weight: 600;
        }

        .addresses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .address-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .address-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0b53a1;
            font-weight: 700;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
        }
        .address-card p {
            color: #334155;
            line-height: 1.6;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background: #0f172a;
            color: #fff;
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
        }
        .items-table th:last-child, .items-table td:last-child {
            text-align: right;
        }
        .items-table th.text-center, .items-table td.text-center {
            text-align: center;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            color: #1e293b;
        }
        .items-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .item-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .item-meta {
            font-size: 11px;
            color: #64748b;
        }

        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
            margin-bottom: 30px;
        }
        .words-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
        }
        .words-box .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .words-box .amount-words {
            font-weight: 600;
            color: #0f172a;
        }

        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .totals-table tr.grand-total {
            background: #0b53a1;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .totals-table tr.grand-total td {
            padding: 10px;
            color: #fff;
        }

        .invoice-footer {
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .terms {
            max-width: 60%;
            font-size: 11px;
            color: #64748b;
            line-height: 1.5;
        }
        .terms strong {
            color: #0f172a;
        }
        .signatory {
            text-align: center;
        }
        .signatory-box {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-style: italic;
            color: #0b53a1;
            font-weight: 700;
        }
        .signatory p {
            font-size: 11px;
            color: #475569;
            border-top: 1px dashed #94a3b8;
            padding-top: 4px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .invoice-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="view-order.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Order
        </a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Tax Invoice
            </button>
        </div>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-brand">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Printmont" style="max-height: 48px; max-width: 200px; object-fit: contain; margin-bottom: 8px; display: block;">
                <?php else: ?>
                    <h1>PRINTMONT</h1>
                <?php endif; ?>
                <p><strong>Printmont Custom & Corporate Gifting Pvt. Ltd.</strong></p>
                <p>123 Business Avenue, Okhla Industrial Area Phase-III</p>
                <p>New Delhi, Delhi - 110020, India</p>
                <p>GSTIN: <strong>07AAAAA0000A1Z5</strong> | CIN: U74999DL2024PTC123456</p>
                <p>Email: support@printmont.com | Web: www.printmont.com</p>
            </div>
            <div class="invoice-title">
                <h2>Tax Invoice</h2>
                <div class="badge">Original for Recipient</div>
                <div style="margin-top: 10px; font-size: 12px; color: #64748b;">
                    <strong>Invoice #:</strong> INV-<?php echo htmlspecialchars($order['order_number']); ?>
                </div>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="meta-grid">
            <div>
                <div class="meta-item"><span class="label">Order Number:</span> <span class="val">#<?php echo htmlspecialchars($order['order_number']); ?></span></div>
                <div class="meta-item"><span class="label">Order Date:</span> <span class="val"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span></div>
                <div class="meta-item"><span class="label">Invoice Date:</span> <span class="val"><?php echo date('d M Y'); ?></span></div>
            </div>
            <div>
                <div class="meta-item"><span class="label">Payment Mode:</span> <span class="val"><?php echo strtoupper($order['payment_method'] ?? 'COD'); ?></span></div>
                <div class="meta-item"><span class="label">Payment Status:</span> <span class="val" style="color: <?php echo $order['payment_status'] === 'paid' ? '#16a34a' : '#ea580c'; ?>;"><?php echo strtoupper($order['payment_status'] ?? 'PENDING'); ?></span></div>
                <?php if (!empty($order['tracking_number'])): ?>
                    <div class="meta-item"><span class="label">Tracking ID:</span> <span class="val"><?php echo htmlspecialchars($order['tracking_number']); ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses-grid">
            <div class="address-card">
                <h3><i class="fas fa-file-invoice me-1"></i> Billed To</h3>
                <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                <?php if (!empty($order['customer_email'])): ?>
                    <p><i class="fas fa-envelope fa-xs text-muted"></i> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <?php endif; ?>
                <?php if (!empty($order['customer_phone'])): ?>
                    <p><i class="fas fa-phone fa-xs text-muted"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                <?php endif; ?>
                <p style="margin-top: 4px;"><?php echo nl2br(htmlspecialchars($order['billing_address'] ?: $order['shipping_address'])); ?></p>
            </div>
            <div class="address-card">
                <h3><i class="fas fa-truck me-1"></i> Shipped To</h3>
                <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                <?php if (!empty($order['customer_phone'])): ?>
                    <p><i class="fas fa-phone fa-xs text-muted"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                <?php endif; ?>
                <p style="margin-top: 4px;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <?php if (!empty($order['courier_name'])): ?>
                    <p style="margin-top: 6px; font-size: 11.5px; color: #0b53a1;">
                        <strong>Courier:</strong> <?php echo htmlspecialchars($order['courier_name']); ?> 
                        <?php if (!empty($order['courier_tracking_number'])): ?> (AWB: <?php echo htmlspecialchars($order['courier_tracking_number']); ?>)<?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th>Product & Description</th>
                    <th style="width: 80px;" class="text-center">HSN/SAC</th>
                    <th style="width: 60px;" class="text-center">Qty</th>
                    <th style="width: 100px; text-align: right;">Rate (₹)</th>
                    <th style="width: 110px; text-align: right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                foreach ($orderItems as $item): 
                    $attrs = [];
                    if (!empty($item['attributes'])) {
                        $parsed = json_decode($item['attributes'], true);
                        if (is_array($parsed)) {
                            foreach ($parsed as $k => $v) {
                                if (!empty($v) && !is_array($v)) $attrs[] = ucfirst($k) . ": " . $v;
                            }
                        }
                    }
                ?>
                <tr>
                    <td class="text-center"><?php echo $sno++; ?></td>
                    <td>
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <?php if (!empty($item['product_sku'])): ?>
                            <div class="item-meta">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['variant'])): ?>
                            <div class="item-meta">Variant: <?php echo htmlspecialchars($item['variant']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($attrs)): ?>
                            <div class="item-meta" style="color: #0b53a1; font-weight: 500;">
                                Specs: <?php echo htmlspecialchars(implode(' | ', $attrs)); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-muted" style="font-size: 11.5px;">491199</td>
                    <td class="text-center"><strong><?php echo (int)$item['quantity']; ?></strong></td>
                    <td style="text-align: right;">₹<?php echo number_format((float)$item['unit_price'], 2); ?></td>
                    <td style="text-align: right;"><strong>₹<?php echo number_format((float)$item['total_price'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary & Totals -->
        <div class="summary-section">
            <div class="words-box">
                <div class="label">Amount in Words</div>
                <div class="amount-words"><?php echo $amountInWords; ?></div>
                <div style="margin-top: 15px; font-size: 11.5px; color: #64748b;">
                    <strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method'] ?? 'COD'); ?><br>
                    <?php if (!empty($order['transaction_id'])): ?>
                        <strong>Transaction / Gateway Ref:</strong> <?php echo htmlspecialchars($order['transaction_id']); ?><br>
                    <?php endif; ?>
                    <strong>Order Status:</strong> <?php echo ucfirst($order['status']); ?>
                </div>
            </div>

            <table class="totals-table">
                <tr>
                    <td>Item Subtotal:</td>
                    <td>₹<?php echo number_format((float)($order['subtotal'] > 0 ? $order['subtotal'] : $order['total_amount']), 2); ?></td>
                </tr>
                <?php if ((float)$order['discount_amount'] > 0): ?>
                <tr style="color: #16a34a;">
                    <td>Discount (<?php echo htmlspecialchars($order['coupon_code'] ?: 'Coupon'); ?>):</td>
                    <td>- ₹<?php echo number_format((float)$order['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Shipping & Handling:</td>
                    <td><?php echo (float)$order['shipping_cost'] > 0 ? '₹' . number_format((float)$order['shipping_cost'], 2) : 'FREE'; ?></td>
                </tr>
                <?php if ((float)$order['tax_amount'] > 0): ?>
                <tr>
                    <td>Estimated GST / Tax:</td>
                    <td>₹<?php echo number_format((float)$order['tax_amount'], 2); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>Taxes:</td>
                    <td><span style="font-size: 11px; color: #64748b;">Inclusive of GST</span></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td>₹<?php echo number_format((float)$grandTotal, 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="terms">
                <strong>Terms & Conditions:</strong><br>
                1. Goods once sold are subject to Printmont return/replacement policy within 7 days of delivery.<br>
                2. Customized printed items cannot be returned unless manufacturing defect is proven.<br>
                3. This is a computer-generated official tax invoice and requires no physical signature.
            </div>
            <div class="signatory">
                <div class="signatory-box">Printmont Authorised</div>
                <p>For Printmont Custom & Corporate Gifting Pvt. Ltd.</p>
            </div>
        </div>
    </div>

</body>
</html>
