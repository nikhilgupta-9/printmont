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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing Slip - #<?php echo htmlspecialchars($order['order_number']); ?> | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 35px;
            border: 2px dashed #cbd5e1;
        }
        .no-print {
            max-width: 800px;
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
        }
        .btn-primary { background: #0b53a1; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #475569; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .brand h1 { font-size: 24px; font-weight: 800; color: #0b53a1; }
        .slip-title { text-align: right; }
        .slip-title h2 { font-size: 18px; text-transform: uppercase; color: #0f172a; }

        .barcode-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 20px;
            margin-bottom: 20px;
        }
        .barcode-num { font-size: 18px; font-weight: 800; letter-spacing: 2px; font-family: monospace; color: #0f172a; }

        .dispatch-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .dispatch-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            background: #fff;
        }
        .dispatch-card h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #0f172a;
            padding: 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .check-box {
            width: 18px;
            height: 18px;
            border: 1.5px solid #94a3b8;
            border-radius: 3px;
            display: inline-block;
        }

        .footer-note {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            color: #64748b;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .slip-container {
                box-shadow: none;
                border: 1px solid #000;
                padding: 20px;
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
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Packing Slip
        </button>
    </div>

    <div class="slip-container">
        <div class="header">
            <div class="brand">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Printmont" style="max-height: 42px; max-width: 180px; object-fit: contain; margin-bottom: 6px; display: block;">
                <?php else: ?>
                    <h1>PRINTMONT</h1>
                <?php endif; ?>
                <p style="font-size: 11px; color: #64748b;">Custom Printing & Production Unit</p>
            </div>
            <div class="slip-title">
                <h2>Warehouse Packing Slip</h2>
                <p style="font-size: 12px; color: #64748b;">Date: <?php echo date('d M Y'); ?></p>
            </div>
        </div>

        <div class="barcode-box">
            <div>
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Order Number</div>
                <div class="barcode-num">#<?php echo htmlspecialchars($order['order_number']); ?></div>
            </div>
            <?php if (!empty($order['tracking_number'])): ?>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Printmont Tracking ID</div>
                <div style="font-size: 14px; font-weight: 700; color: #0b53a1;"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dispatch-grid">
            <div class="dispatch-card">
                <h3>Ship From (Origin Hub)</h3>
                <p><strong>Printmont Fulfillment Warehouse</strong></p>
                <p>Plot 45, Sector 59, Phase-II</p>
                <p>Noida, Uttar Pradesh - 201301</p>
                <p>Ph: +91 98765 43210</p>
            </div>
            <div class="dispatch-card">
                <h3>Deliver To (Destination)</h3>
                <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                <?php if (!empty($order['customer_phone'])): ?>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                <?php endif; ?>
                <p style="margin-top: 4px;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <?php if (!empty($order['courier_name'])): ?>
                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-weight: 600; color: #0b53a1;">
                        Carrier: <?php echo htmlspecialchars($order['courier_name']); ?>
                        <?php if (!empty($order['courier_tracking_number'])): ?> | AWB: <?php echo htmlspecialchars($order['courier_tracking_number']); ?><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h3 style="font-size: 13px; font-weight: 700; margin-bottom: 10px; color: #0f172a;">
            Package Items Checklist (Total <?php echo count($orderItems); ?> Line Items)
        </h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">Pick</th>
                    <th style="width: 50px;">#</th>
                    <th>Item Description & Customization Specs</th>
                    <th style="width: 120px;">SKU</th>
                    <th style="width: 70px; text-align: center;">Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                $totalUnits = 0;
                foreach ($orderItems as $item): 
                    $totalUnits += (int)$item['quantity'];
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
                    <td style="text-align: center;"><span class="check-box"></span></td>
                    <td><?php echo $sno++; ?></td>
                    <td>
                        <strong style="color: #0f172a;"><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <?php if (!empty($item['variant'])): ?>
                            <div style="font-size: 11.5px; color: #64748b;">Variant: <?php echo htmlspecialchars($item['variant']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($attrs)): ?>
                            <div style="font-size: 11.5px; color: #0b53a1; font-weight: 600; margin-top: 2px;">
                                [Customizations: <?php echo htmlspecialchars(implode(', ', $attrs)); ?>]
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($item['product_sku'] ?: '-'); ?></td>
                    <td style="text-align: center; font-size: 14px; font-weight: 700;"><?php echo (int)$item['quantity']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-weight: 700;">
                    <td colspan="4" style="text-align: right; padding: 10px;">Total Units Packed:</td>
                    <td style="text-align: center; font-size: 15px; color: #0b53a1; padding: 10px;"><?php echo $totalUnits; ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-note">
            <div>
                <strong>Packed By:</strong> ____________________ &nbsp;&nbsp;&nbsp; <strong>Verified By:</strong> ____________________
            </div>
            <div>
                Payment: <strong><?php echo strtoupper($order['payment_method'] ?? 'COD'); ?></strong> (<?php echo strtoupper($order['payment_status'] ?? 'PENDING'); ?>)
            </div>
        </div>
    </div>

</body>
</html>
