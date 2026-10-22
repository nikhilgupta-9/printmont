<?php
require_once(__DIR__ . '/../config/database.php');
// require_once 'models/Database.php';
require_once(__DIR__ . '/../models/Order.php');
require_once(__DIR__ . '/../services/MailService.php');

class OrderController
{
    private $db;
    private $order;

    public function __construct()
    {
        $this->db = new Database();
        $this->order = new Order($this->db->getConnection());
    }

    public function getAllOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->getAllOrders($page, $limit, $filters);
    }

    /**
     * Orders belonging to one customer. $userId must come from a verified
     * token, not from the request, or customers can read each other's orders.
     */
    public function getOrdersForUser($userId, $params = [])
    {
        try {
            $page = isset($params['page']) ? (int) $params['page'] : 1;
            $limit = isset($params['limit']) ? (int) $params['limit'] : 10;

            $filters = ['customer_user_id' => (int) $userId];
            foreach (['status', 'payment_status', 'search', 'date_from', 'date_to'] as $key) {
                if (!empty($params[$key])) {
                    $filters[$key] = $params[$key];
                }
            }

            $result = $this->order->getAllOrders($page, $limit, $filters);

            // Attach line items so My Orders can show real product names and
            // thumbnails. Without this the list carries only order-level
            // columns, so every row rendered as a generic "Order #13" with a
            // placeholder image. One batched query, not one per order.
            $orders = $result['orders'];
            $itemsByOrder = $this->order->getItemsForOrders(array_column($orders, 'id'));
            foreach ($orders as &$order) {
                $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
            }
            unset($order);

            return [
                'success' => true,
                'data' => $orders,
                'total_count' => $result['total_count'],
                'total_pages' => $result['total_pages'],
                'current_page' => $result['current_page'],
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    public function getOrderById($id)
    {
        return $this->order->getOrderById($id);
    }

    /**
     * Returns the order only when it belongs to $userId.
     */
    public function getOrderForUser($id, $userId)
    {
        try {
            $order = $this->order->getOrderByIdOrNumber($id);
            if (!$order) {
                return ['success' => false, 'error' => 'Order not found', 'data' => null];
            }

            $column = $this->order->getCustomerColumn();
            if ($column === null || (int) $order[$column] !== (int) $userId) {
                return ['success' => false, 'error' => 'Order not found', 'data' => null];
            }

            $items = $this->order->getOrderItems($order['id']);
            $conn = $this->db->getConnection();
            if (!empty($items) && is_array($items) && $conn) {
                $userEmailStr = (string)($order['customer_email'] ?? '');
                foreach ($items as &$it) {
                    $pid = (int)($it['product_id'] ?? 0);
                    $it['is_reviewed'] = false;
                    $it['review_id'] = null;
                    $it['review_rating'] = null;
                    if ($pid > 0) {
                        $revStmt = $conn->prepare("SELECT id, rating, title, status FROM product_reviews WHERE product_id = ? AND (user_id = ? OR (customer_email IS NOT NULL AND customer_email != '' AND customer_email = ?)) ORDER BY id DESC LIMIT 1");
                        if ($revStmt) {
                            $revStmt->bind_param("iis", $pid, $userId, $userEmailStr);
                            $revStmt->execute();
                            $rRes = $revStmt->get_result();
                            if ($rRow = $rRes->fetch_assoc()) {
                                $it['is_reviewed'] = true;
                                $it['review_id'] = (int)$rRow['id'];
                                $it['review_rating'] = (int)$rRow['rating'];
                                $it['review_title'] = $rRow['title'];
                                $it['review_status'] = $rRow['status'];
                            }
                            $revStmt->close();
                        }
                    }
                }
                unset($it);
            }
            $order['items'] = $items;
            $order['status_history'] = $this->order->getStatusHistory($order['id']);

            return ['success' => true, 'data' => $order];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Order tracking for both logged-in users and public guests.
     * Logged-in users can track via order/AWB number directly.
     * Public guests verify with order number + contact (email or phone).
     */
    public function trackOrder($reference, $contact = '', $userId = null)
    {
        try {
            $reference = trim((string) $reference);
            $contact = trim((string) $contact);

            if ($reference === '') {
                return [
                    'success' => false,
                    'reason' => 'invalid',
                    'error' => 'Order ID or Tracking Number is required.',
                ];
            }

            $order = null;
            if (!empty($userId)) {
                $order = $this->order->findForUserTracking($reference, $userId);
            }

            if (!$order && !empty($contact)) {
                $order = $this->order->findForTracking($reference, $contact);
            }

            if (!$order && empty($userId) && empty($contact)) {
                return [
                    'success' => false,
                    'reason' => 'invalid',
                    'error' => 'Please provide your registered Email address or 10-digit Mobile Number along with the Order ID.',
                ];
            }

            if (!$order) {
                return [
                    'success' => false,
                    'reason' => 'not_found',
                    'error' => 'No order matches those details. Please check the Order ID / Tracking number and the email/mobile used.',
                ];
            }

            // Expected delivery calculation (typically 4-6 days from order placement)
            $placedTime = strtotime($order['created_at']);
            $expectedDeliveryTs = $placedTime + (5 * 86400);
            $expectedDeliveryDate = date('D, d M Y', $expectedDeliveryTs);

            return [
                'success' => true,
                'data' => [
                    'id'               => (int)$order['id'],
                    'order_number'     => $order['order_number'],
                    'customer_name'    => $order['customer_name'],
                    'customer_email'   => $order['customer_email'],
                    'customer_phone'   => $order['customer_phone'],
                    'status'           => $order['status'] ?: 'pending',
                    'payment_status'   => $order['payment_status'],
                    'subtotal'         => $order['subtotal'] ?? $order['grand_total'],
                    'tax_amount'       => $order['tax_amount'] ?? 0,
                    'shipping_cost'    => $order['shipping_cost'] ?? 0,
                    'discount_amount'  => $order['discount_amount'] ?? 0,
                    'grand_total'      => $order['grand_total'],
                    'shipping_method'  => $order['shipping_method'],
                    'shipping_address' => $order['shipping_address'],
                    'courier_name'     => $order['courier_name'] ?: 'Printmont Logistics Express',
                    'tracking_number'  => $order['tracking_number'],
                    'courier_tracking_number' => $order['courier_tracking_number'],
                    'tracking_url'     => $order['tracking_url'],
                    'placed_at'        => $order['created_at'],
                    'updated_at'       => $order['updated_at'],
                    'expected_delivery' => $expectedDeliveryDate,
                    'items'            => $this->order->getOrderItems($order['id']),
                    'history'          => $this->order->getStatusHistory($order['id']),
                ],
            ];
        } catch (Exception $e) {
            return ['success' => false, 'reason' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function getOrderItems($order_id)
    {
        return $this->order->getOrderItems($order_id);
    }

    public function getStatusHistory($order_id)
    {
        return $this->order->getStatusHistory($order_id);
    }

    public function updateOrderStatus($order_id, $status, $notes = '', $user_id = null)
    {
        $result = $this->order->updateOrderStatus($order_id, $status, $notes, $user_id);
        if ($result && in_array(strtolower($status), ['cancelled', 'declined', 'delivered', 'shipped'])) {
            try {
                require_once __DIR__ . '/NotificationController.php';
                $notifController = new NotificationController();
                $type = in_array(strtolower($status), ['cancelled', 'declined']) ? 'warning' : 'success';
                $icon = in_array(strtolower($status), ['cancelled', 'declined']) ? 'alert-circle' : 'check-circle';
                $notifController->createNotification([
                    'title' => "Order #{$order_id} " . ucfirst($status),
                    'message' => "Order #{$order_id} was updated to " . ucfirst($status) . ($notes ? ". Notes: {$notes}" : ""),
                    'type' => $type,
                    'icon' => $icon,
                    'link' => "view-order.php?id={$order_id}"
                ]);
            } catch (Exception $e) {
                error_log('Order status notification error: ' . $e->getMessage());
            }
        }

        // Award purchase coins on delivery
        if ($result && strtolower($status) === 'delivered') {
            try {
                require_once __DIR__ . '/../services/CoinService.php';
                $coinService = new CoinService($this->db->getConnection());
                $coinService->awardPurchaseCoins($order_id);
            } catch (\Throwable $ce) {
                error_log('Delivery coin award error: ' . $ce->getMessage());
            }
        }

        return $result;
    }

    public function updatePaymentStatus($order_id, $payment_status)
    {
        return $this->order->updatePaymentStatus($order_id, $payment_status);
    }

    public function updateCourierTracking($order_id, $courier_name, $courier_tracking_number, $tracking_url = '', $auto_status_ship = false, $user_id = null)
    {
        return $this->order->updateCourierTracking($order_id, $courier_name, $courier_tracking_number, $tracking_url, $auto_status_ship, $user_id);
    }

    public function deleteOrder($order_id)
    {
        return $this->order->deleteOrder($order_id);
    }

    public function getOrderStats()
    {
        return $this->order->getOrderStats();
    }

    // Add this method to your OrderController.php
    public function updateOrder($order_id, $data)
    {
        return $this->order->updateOrder($order_id, $data);
    }

    public function getPendingOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->pendingOrders($page, $limit, $filters);
    }

    public function getProcessingOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->processingOrders($page, $limit, $filters);
    }

    public function getShippedOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->shippedOrders($page, $limit, $filters);
    }

    public function getDeliveredOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->deliveredOrders($page, $limit, $filters);
    }

    public function getCancelledOrders($page = 1, $limit = 10, $filters = [])
    {
        return $this->order->cancelledOrders($page, $limit, $filters);
    }

    public function getOrderCountsByStatus()
    {
        return $this->order->getOrderCountsByStatus();
    }

    public function getRecentPendingOrders($limit = 5)
    {
        return $this->order->getRecentPendingOrders($limit);
    }

    /**
     * Create an order from the checkout payload.
     *
     * Accepts the shape CheckoutContext sends
     * ({buyerDetails, address, paymentMethod, items, totalAmount}) as well as
     * flat {customer_name, customer_email, ...} keys.
     *
     * Line prices are re-read from the products table — the client is never
     * trusted on price, only on which product and how many.
     */
    /**
     * Places an order from the cart. Prices, coupon and coins are worked out on the server
     * (CheckoutPricing) — amounts sent by the browser are ignored.
     *
     * COD:    the order is created right away (admin COD rules apply).
     * Online: a Razorpay order is created for the server total; the order stays "payment pending"
     *         until verifyPayment() checks Razorpay's signature.
     */
    public function createOrder($data, $userId = null)
    {
        try {
            $buyer = $data['buyerDetails'] ?? [];
            $address = $data['address'] ?? [];

            $customerName  = trim($data['customer_name']  ?? $buyer['name']   ?? '');
            $customerEmail = trim($data['customer_email'] ?? $buyer['email']  ?? '');
            $customerPhone = trim($data['customer_phone'] ?? $buyer['mobile'] ?? $buyer['phone'] ?? '');

            if ($customerName === '')  throw new Exception('Customer name is required');
            if ($customerEmail === '') throw new Exception('Customer email is required');
            if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Customer email is not valid');
            }
            if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $customerPhone))) {
                throw new Exception('A valid 10-digit mobile number is required');
            }
            if (empty($address['pincode']) || !preg_match('/^\d{6}$/', (string) $address['pincode'])) {
                throw new Exception('A valid delivery address with a 6-digit pincode is required');
            }

            $rawItems = $data['items'] ?? [];
            if (!is_array($rawItems) || count($rawItems) === 0) {
                throw new Exception('Order must contain at least one item');
            }

            require_once __DIR__ . '/CheckoutPricing.php';
            require_once __DIR__ . '/PaymentSettingsController.php';
            $conn = $this->db->getConnection();
            $quote = (new CheckoutPricing($conn))->quote(
                $rawItems,
                (string) ($data['coupon_code'] ?? ''),
                !empty($data['use_coins']),
                $userId
            );
            if (!$quote['success']) {
                throw new Exception($quote['errors'][0] ?? 'Your cart could not be priced. Please refresh and try again.');
            }
            if (!empty($data['coupon_code']) && !$quote['coupon']['valid']) {
                throw new Exception($quote['coupon']['message'] ?: 'This coupon code is not valid.');
            }

            $paymentMethod = strtolower((string) ($data['paymentMethod'] ?? $data['payment_method'] ?? 'cod'));
            $isCod = $paymentMethod === 'cod';
            $settings = (new PaymentSettingsController($conn))->getSettings(true);
            $grandTotal = (float) $quote['grand_total'];

            if ($isCod) {
                $this->assertCodAllowed($settings, $grandTotal, (string) $address['pincode']);
            } else {
                if (($settings['razorpay_enabled'] ?? '0') !== '1') {
                    throw new Exception('Online payment is switched off. Please choose Cash on Delivery.');
                }
                if (!$this->razorpayConfigured($settings)) {
                    throw new Exception('Online payment is not set up yet. Please choose Cash on Delivery or contact support.');
                }
            }

            $items = array_map(function ($l) {
                return [
                    'product_id'    => $l['product_id'],
                    'product_name'  => $l['name'],
                    'product_sku'   => $l['sku'],
                    'quantity'      => $l['quantity'],
                    'unit_price'    => $l['unit_price'],
                    'total_price'   => $l['total_price'],
                    'product_image' => $l['image'],
                    'attributes'    => $l['attributes'] ? json_encode($l['attributes']) : null,
                ];
            }, $quote['items']);

            $shippingAddress = $this->formatAddress($address);
            $discount = round($quote['coupon']['amount'] + $quote['coins']['amount'], 2);

            $orderId = $this->order->createOrder([
                'user_id'          => $userId,
                'customer_name'    => $customerName,
                'customer_email'   => $customerEmail,
                'customer_phone'   => $customerPhone,
                'subtotal'         => $quote['subtotal'],
                'total_amount'     => $quote['subtotal'],
                'tax_amount'       => 0,
                'shipping_cost'    => $quote['delivery'],
                'discount_amount'  => $discount,
                'grand_total'      => $grandTotal,
                'payment_method'   => $paymentMethod,
                'notes'            => $address['orderNotes'] ?? $data['notes'] ?? '',
                'shipping_address' => $shippingAddress,
                'billing_address'  => $shippingAddress,
                'shipping_method'  => 'standard',
                'coupon_code'      => $quote['coupon']['valid'] ? $quote['coupon']['code'] : '',
                'items'            => $items,
            ]);

            $this->ensureCoinsColumn($conn);
            if ($quote['coins']['used'] > 0) {
                $stmt = $conn->prepare("UPDATE orders SET coins_used = ? WHERE id = ?");
                $used = (int) $quote['coins']['used'];
                $stmt->bind_param('ii', $used, $orderId['id']);
                $stmt->execute();
                $stmt->close();
            }

            $response = [
                'success'         => true,
                'order_id'        => $orderId['id'],
                'order_number'    => $orderId['order_number'],
                'tracking_number' => $orderId['tracking_number'] ?? null,
                'grand_total'     => $grandTotal,
                'quote'           => $quote,
            ];

            if ($isCod) {
                $response['mail_sent'] = $this->finalizeOrder((int) $orderId['id']);
                $response['message'] = 'Order placed successfully';
                return $response;
            }

            // Online: Razorpay order for the server total; the browser opens checkout with it
            $rzp = $this->createRazorpayOrder($settings, $grandTotal, $orderId['order_number']);
            if (!$rzp['success']) {
                $this->order->updateOrderStatus($orderId['id'], 'cancelled', 'Payment gateway error: ' . $rzp['error']);
                throw new Exception('Could not start the payment. Please try again or choose Cash on Delivery.');
            }
            $stmt = $conn->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
            $rzpOrderId = $rzp['id'];
            $stmt->bind_param('si', $rzpOrderId, $orderId['id']);
            $stmt->execute();
            $stmt->close();

            $response['message'] = 'Complete the payment to confirm your order';
            $response['razorpay'] = [
                'key_id'   => $settings['active_key_id'],
                'order_id' => $rzpOrderId,
                'amount'   => (int) round($grandTotal * 100),
                'currency' => 'INR',
            ];
            return $response;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirms an online payment: checks Razorpay's signature with the secret key, then marks
     * the order paid + confirmed. $userId (from the token) must own the order.
     */
    public function verifyPayment($data, $userId = null)
    {
        try {
            $orderIdOrNumber = $data['order_id'] ?? '';
            $rzpOrderId = (string) ($data['razorpay_order_id'] ?? '');
            $rzpPaymentId = (string) ($data['razorpay_payment_id'] ?? '');
            $signature = (string) ($data['razorpay_signature'] ?? '');
            if ($orderIdOrNumber === '' || $rzpOrderId === '' || $rzpPaymentId === '' || $signature === '') {
                throw new Exception('Payment details are missing.');
            }

            $order = $this->order->getOrderByIdOrNumber($orderIdOrNumber);
            if (!$order) throw new Exception('Order not found.');
            $owner = $order['user_id'] ?? $order['customer_id'] ?? null;
            if ($userId && $owner && (int) $owner !== (int) $userId) {
                throw new Exception('Order not found.');
            }
            if (($order['payment_status'] ?? '') === 'paid') {
                return ['success' => true, 'order_id' => (int) $order['id'], 'order_number' => $order['order_number'], 'already_paid' => true];
            }
            if (($order['transaction_id'] ?? '') !== $rzpOrderId) {
                throw new Exception('Payment does not match this order.');
            }

            $conn = $this->db->getConnection();
            require_once __DIR__ . '/../services/RazorpayService.php';
            if (!(new RazorpayService($conn))->verifySignature($rzpOrderId, $rzpPaymentId, $signature)) {
                $this->order->updatePaymentStatus($order['id'], 'failed');
                throw new Exception('Payment verification failed. If money was deducted it will be refunded.');
            }

            $ref = $rzpOrderId . '|' . $rzpPaymentId;
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed', transaction_id = ?, updated_at = NOW() WHERE id = ?");
            $oid = (int) $order['id'];
            $stmt->bind_param('si', $ref, $oid);
            $stmt->execute();
            $stmt->close();

            $mailSent = $this->finalizeOrder($oid);
            return [
                'success'      => true,
                'order_id'     => $oid,
                'order_number' => $order['order_number'],
                'grand_total'  => (float) $order['grand_total'],
                'mail_sent'    => $mailSent,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * After an order is confirmed: use the coupon and coins now; email the customer and notify
     * admin after the response is sent (SMTP takes seconds — see sendJsonAndFinish in user-api.php).
     */
    private function finalizeOrder(int $orderId): bool
    {
        $conn = $this->db->getConnection();
        $this->ensureCoinsColumn($conn);
        $order = $this->order->getOrderById($orderId);
        if (!$order) return false;

        require_once __DIR__ . '/../config/gift-vouchers-schema.php';
        $owner = (int) ($order['user_id'] ?? $order['customer_id'] ?? 0);

        if (!empty($order['coupon_code'])) {
            if (stripos($order['coupon_code'], 'PMGV') === 0) {
                // Gift voucher redeemed on this order
                ensureGiftVoucherTables($conn);
                $stmt = $conn->prepare("UPDATE user_gift_vouchers SET status = 'used', used_order_id = ?, used_at = NOW() WHERE UPPER(code) = UPPER(?) AND user_id = ? AND status = 'active'");
                $stmt->bind_param('isi', $orderId, $order['coupon_code'], $owner);
            } else {
                $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE UPPER(coupon_code) = UPPER(?)");
                $stmt->bind_param('s', $order['coupon_code']);
            }
            $stmt->execute();
            $stmt->close();
        }

        // Gift voucher earned by this order (admin rules in gift-vouchers.php)
        if ($owner > 0) {
            try {
                issueGiftVoucherForOrder($conn, $orderId);
            } catch (Throwable $gvEx) {
                error_log('Gift voucher issue error: ' . $gvEx->getMessage());
            }
        }

        $coinsUsed = (int) ($order['coins_used'] ?? 0);
        if ($coinsUsed > 0 && $owner > 0) {
            $stmt = $conn->prepare("UPDATE user_coins SET balance = GREATEST(0, balance - ?), lifetime_spent = lifetime_spent + ? WHERE user_id = ?");
            $stmt->bind_param('iii', $coinsUsed, $coinsUsed, $owner);
            $stmt->execute();
            $stmt->close();
        }

        register_shutdown_function(function () use ($orderId) {
            $this->sendOrderMessages($orderId);
        });
        return true; // mail is queued for after the response
    }

    private function sendOrderMessages(int $orderId): void
    {
        $order = $this->order->getOrderById($orderId);
        if (!$order) return;
        $items = $this->order->getOrderItems($orderId) ?: [];
        $mailSent = false;
        try {
            $mailer = new MailService();
            $mailSent = $mailer->sendOrderConfirmation([
                'customer_name'    => $order['customer_name'],
                'customer_email'   => $order['customer_email'],
                'order_number'     => $order['order_number'],
                'tracking_number'  => $order['tracking_number'] ?? null,
                'grand_total'      => (float) $order['grand_total'],
                'shipping_address' => $order['shipping_address'],
                'payment_method'   => $order['payment_method'],
            ], $items);
            if (!$mailSent) {
                error_log('Order confirmation not sent for ' . $order['order_number'] . ': ' . $mailer->getLastError());
            }
        } catch (Throwable $mailEx) {
            error_log('Order confirmation mail error: ' . $mailEx->getMessage());
        }

        try {
            require_once __DIR__ . '/NotificationController.php';
            $notifController = new NotificationController();
            $orderNum = $order['order_number'] ?? ('#' . $orderId);
            $notifController->createNotification([
                'title' => 'New Order ' . $orderNum,
                'message' => "Order {$orderNum} received from {$order['customer_name']} for ₹" . number_format((float) $order['grand_total'], 2)
                    . ' (' . strtoupper((string) $order['payment_method']) . ')',
                'type' => 'order',
                'icon' => 'shopping-cart',
                'link' => 'view-order.php?id=' . $orderId
            ]);
            foreach ($items as $itm) {
                $prodCheck = $this->order->getProductForOrder($itm['product_id']);
                if ($prodCheck && isset($prodCheck['stock_quantity']) && (int) $prodCheck['stock_quantity'] <= 5) {
                    $notifController->createNotification([
                        'title' => 'Low Stock Alert',
                        'message' => "Product '{$prodCheck['name']}' is low on stock ({$prodCheck['stock_quantity']} remaining).",
                        'type' => 'warning',
                        'icon' => 'alert-triangle',
                        'link' => 'edit-product.php?id=' . $itm['product_id']
                    ]);
                }
            }
        } catch (Throwable $notifEx) {
            error_log('Order notification error: ' . $notifEx->getMessage());
        }
    }

    private function assertCodAllowed(array $settings, float $total, string $pincode): void
    {
        if (($settings['cod_enabled'] ?? '0') !== '1') {
            throw new Exception('Cash on Delivery is not available right now. Please pay online.');
        }
        $min = (float) ($settings['cod_min_amount'] ?? 0);
        $max = (float) ($settings['cod_max_amount'] ?? 0);
        if ($min > 0 && $total < $min) {
            throw new Exception('Cash on Delivery is available on orders above ₹' . number_format($min, 0) . '.');
        }
        if ($max > 0 && $total > $max) {
            throw new Exception('Cash on Delivery is available on orders up to ₹' . number_format($max, 0) . '. Please pay online.');
        }
        $restricted = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) ($settings['cod_restricted_pincodes'] ?? ''))));
        if ($pincode !== '' && in_array($pincode, $restricted, true)) {
            throw new Exception('Cash on Delivery is not available for pincode ' . $pincode . '. Please pay online.');
        }
    }

    // Razorpay calls live in services/RazorpayService.php (shared with wallet top-ups)
    private function razorpayConfigured(array $settings): bool
    {
        require_once __DIR__ . '/../services/RazorpayService.php';
        return (new RazorpayService($this->db->getConnection()))->configured();
    }

    private function createRazorpayOrder(array $settings, float $amount, string $receipt): array
    {
        require_once __DIR__ . '/../services/RazorpayService.php';
        return (new RazorpayService($this->db->getConnection()))->createOrder($amount, $receipt);
    }

    private function ensureCoinsColumn($conn): void
    {
        static $done = false;
        if ($done) return;
        $res = $conn->query("SHOW COLUMNS FROM orders LIKE 'coins_used'");
        if ($res && $res->num_rows === 0) {
            $conn->query("ALTER TABLE orders ADD COLUMN coins_used INT NOT NULL DEFAULT 0 AFTER discount_amount");
        }
        $done = true;
    }

    private function formatAddress($address)
    {
        if (!is_array($address) || empty($address)) {
            return '';
        }

        $parts = array_filter([
            $address['company']  ?? null,
            $address['address']  ?? $address['addressArea'] ?? null,
            $address['landmark'] ?? null,
            $address['city']     ?? null,
            $address['state']    ?? null,
            $address['pincode']  ?? null,
        ], fn($v) => $v !== null && trim((string) $v) !== '');

        return implode(', ', $parts);
    }

    // Update order status
    public function updateOrderStatusAPI($orderId, $status, $notes = '', $createdBy = null) {
        try {
            $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid order status");
            }

            $success = $this->order->updateOrderStatus($orderId, $status, $notes, $createdBy);
            
            return [
                'success' => $success,
                'message' => 'Order status updated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

     // Get customer orders
    public function getCustomerOrders($userId) {
        try {
            $orders = $this->order->getOrdersByCustomer($userId);
            
            return [
                'success' => true,
                'data' => $orders,
                'total' => count($orders)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
     // Get dashboard statistics
    public function getDashboardStats() {
        try {
            $stats = $this->order->getDashboardStats();
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel an order requested by the verified customer.
     * Allowed only for pending, confirmed, or processing orders.
     */
    public function cancelOrder($orderIdOrNumber, $userId, $data = [])
    {
        try {
            $order = $this->order->getOrderByIdOrNumber($orderIdOrNumber);
            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $column = $this->order->getCustomerColumn();
            if ($column !== null && !empty($order[$column]) && (int)$order[$column] !== (int)$userId) {
                return ['success' => false, 'error' => 'Unauthorized: This order does not belong to your account'];
            }

            $currentStatus = strtolower($order['status'] ?: 'pending');
            $cancellableStatuses = ['pending', 'confirmed', 'processing'];
            
            if (in_array($currentStatus, ['cancelled', 'refunded'])) {
                return ['success' => false, 'error' => 'This order is already cancelled'];
            }
            if ($currentStatus === 'delivered' || $currentStatus === 'shipped') {
                return [
                    'success' => false, 
                    'error' => "Order cannot be cancelled as it is already {$currentStatus}. You may request a return or replacement after delivery."
                ];
            }
            if (!in_array($currentStatus, $cancellableStatuses)) {
                return ['success' => false, 'error' => "Order with status '{$currentStatus}' cannot be cancelled"];
            }

            $reason = trim($data['reason'] ?? 'Cancelled by customer');
            $refundMethod = trim($data['refund_method'] ?? 'original');
            $comments = trim($data['comments'] ?? '');

            $notes = "Cancellation Reason: {$reason}";
            if (!empty($refundMethod)) {
                $notes .= " | Refund Mode: " . ($refundMethod === 'wallet' ? 'Printmont Coins / Wallet' : 'Original Payment Source');
            }
            if (!empty($comments)) {
                $notes .= " | Comments: {$comments}";
            }

            // Update order status
            $this->order->updateOrderStatus($order['id'], 'cancelled', $notes, $userId);

            // Restore any coins used on this order
            $coinsUsed = (int)($order['coins_used'] ?? 0);
            if ($coinsUsed > 0 && $userId > 0) {
                try {
                    $conn = $this->db->getConnection();
                    $cStmt = $conn->prepare("UPDATE user_coins SET balance = balance + ?, lifetime_spent = GREATEST(0, lifetime_spent - ?) WHERE user_id = ?");
                    $cStmt->bind_param("iii", $coinsUsed, $coinsUsed, $userId);
                    $cStmt->execute();
                    $cStmt->close();

                    $orderNumStr = (string)($order['order_number'] ?: ('#' . $order['id']));
                    $tx = $conn->prepare("INSERT INTO coin_transactions (user_id, amount, type, title, description, order_id) VALUES (?, ?, 'credit', 'Coins Refunded: Cancelled Order', 'SuperCoins returned to balance due to order cancellation', ?)");
                    $tx->bind_param("iiss", $userId, $coinsUsed, $orderNumStr);
                    $tx->execute();
                    $tx->close();
                } catch (\Throwable $ce) {
                    error_log('Cancel coin refund error: ' . $ce->getMessage());
                }
            }

            // Reverse payment to user wallet if prepaid or wallet refund requested
            $currentPaymentStatus = strtolower($order['payment_status'] ?? 'pending');
            if ($currentPaymentStatus === 'paid' || $refundMethod === 'wallet') {
                try {
                    require_once __DIR__ . '/../services/WalletService.php';
                    $walletService = new WalletService($this->db->getConnection());
                    $walletService->reverseOrderToWallet(
                        (int)$order['id'],
                        null,
                        "Cancelled by customer. Reason: {$reason}",
                        $userId
                    );
                } catch (\Throwable $we) {
                    error_log('Cancel wallet reversal error: ' . $we->getMessage());
                    $this->order->updatePaymentStatus($order['id'], 'refund_processing');
                }
            }

            // Create user and admin notifications
            try {
                require_once __DIR__ . '/NotificationController.php';
                $notifController = new NotificationController();
                $orderNum = $order['order_number'] ?: ('#' . $order['id']);
                
                // Admin notification
                $notifController->createNotification([
                    'title' => "Order {$orderNum} Cancelled",
                    'message' => "Order {$orderNum} was cancelled by customer. Reason: {$reason}",
                    'type' => 'warning',
                    'icon' => 'alert-circle',
                    'link' => "view-order.php?id=" . $order['id']
                ]);

                // User notification
                $notifController->createNotification([
                    'title' => "Order {$orderNum} Cancelled Successfully",
                    'message' => "Your order {$orderNum} has been cancelled. If any payment was made, refund is initiated to your " . ($refundMethod === 'wallet' ? 'Printmont Wallet Coins' : 'original payment method') . ".",
                    'type' => 'order',
                    'icon' => 'check-circle',
                    'user_id' => $userId,
                    'link' => "/orders/{$order['id']}"
                ]);
            } catch (Exception $e) {
                error_log('Cancel notification error: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Your order has been cancelled successfully.',
                'status' => 'cancelled',
                'order_id' => $order['id'],
                'order_number' => $order['order_number']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Request Return or Replacement for a delivered order within the policy window (15 days).
     */
    public function requestReturnReplacement($orderIdOrNumber, $userId, $data = [])
    {
        try {
            $order = $this->order->getOrderByIdOrNumber($orderIdOrNumber);
            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $column = $this->order->getCustomerColumn();
            if ($column !== null && !empty($order[$column]) && (int)$order[$column] !== (int)$userId) {
                return ['success' => false, 'error' => 'Unauthorized: This order does not belong to your account'];
            }

            $currentStatus = strtolower($order['status'] ?: '');
            if ($currentStatus !== 'delivered') {
                return ['success' => false, 'error' => 'Return/Replacement requests are only available for Delivered orders.'];
            }

            // Check 15-day policy window
            $deliveredDate = !empty($order['updated_at']) ? strtotime($order['updated_at']) : strtotime($order['created_at']);
            $daysSinceDelivery = floor((time() - $deliveredDate) / 86400);
            if ($daysSinceDelivery > 15) {
                return ['success' => false, 'error' => "The 15-day return/replacement window has expired for this order (delivered {$daysSinceDelivery} days ago)."];
            }

            $requestType = strtolower($data['request_type'] ?? 'return');
            if (!in_array($requestType, ['return', 'replacement'])) {
                $requestType = 'return';
            }

            $reason = trim($data['reason'] ?? '');
            if (empty($reason)) {
                return ['success' => false, 'error' => 'Please select a reason for your request.'];
            }

            $comments = trim($data['comments'] ?? '');
            $refundMethod = trim($data['refund_method'] ?? 'original');

            // Handle defect photos if passed
            $uploadedPhotos = [];
            $uploadDir = __DIR__ . '/../uploads/returns/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $imgData) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                        $raw = substr($imgData, strpos($imgData, ',') + 1);
                        $ext = strtolower($type[1]);
                        if ($ext === 'jpeg') $ext = 'jpg';
                        $decoded = base64_decode($raw);
                        if ($decoded !== false) {
                            $fn = 'return_' . time() . '_' . uniqid() . '.' . $ext;
                            if (@file_put_contents($uploadDir . $fn, $decoded)) {
                                $uploadedPhotos[] = 'uploads/returns/' . $fn;
                            }
                        }
                    } elseif (is_string($imgData) && !empty($imgData)) {
                        $uploadedPhotos[] = $imgData;
                    }
                }
            }

            $newStatus = ($requestType === 'replacement') ? 'replacement_requested' : 'return_requested';
            $typeLabel = ($requestType === 'replacement') ? 'Replacement / Exchange' : 'Return & Refund';

            $notes = "{$typeLabel} Requested | Reason: {$reason}";
            if ($requestType === 'return') {
                $notes .= " | Refund Preference: " . ($refundMethod === 'wallet' ? 'Printmont Coins / Wallet' : 'Original Payment Source');
            }
            if (!empty($comments)) {
                $notes .= " | Customer Notes: {$comments}";
            }
            if (!empty($uploadedPhotos)) {
                $notes .= " | Photos Attached: " . count($uploadedPhotos);
            }

            // Update order status & history
            $this->order->updateOrderStatus($order['id'], $newStatus, $notes, $userId);

            // Create Notifications
            try {
                require_once __DIR__ . '/NotificationController.php';
                $notifController = new NotificationController();
                $orderNum = $order['order_number'] ?: ('#' . $order['id']);

                // Admin Notification
                $notifController->createNotification([
                    'title' => "{$typeLabel} Request: {$orderNum}",
                    'message' => "Customer submitted a {$typeLabel} request for Order {$orderNum}. Reason: {$reason}",
                    'type' => 'warning',
                    'icon' => 'refresh-cw',
                    'link' => "view-order.php?id=" . $order['id']
                ]);

                // User Notification
                $notifController->createNotification([
                    'title' => "{$typeLabel} Request Received",
                    'message' => "We received your {$typeLabel} request for Order {$orderNum}. Our support team will review and arrange pickup/dispatch within 24-48 hours.",
                    'type' => 'order',
                    'icon' => 'check-circle',
                    'user_id' => $userId,
                    'link' => "/orders/{$order['id']}"
                ]);
            } catch (Exception $e) {
                error_log('Return request notification error: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => "Your {$typeLabel} request has been submitted successfully. Our team will verify and initiate the process shortly.",
                'status' => $newStatus,
                'order_id' => $order['id'],
                'order_number' => $order['order_number']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>