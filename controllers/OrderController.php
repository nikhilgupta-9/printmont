<?php
require_once(__DIR__ . '/../config/database.php');
// require_once 'models/Database.php';
require_once(__DIR__ . '/../models/Order.php');

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

            return [
                'success' => true,
                'data' => $result['orders'],
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
            $order = $this->order->getOrderById($id);
            if (!$order) {
                return ['success' => false, 'error' => 'Order not found', 'data' => null];
            }

            $column = $this->order->getCustomerColumn();
            if ($column === null || (int) $order[$column] !== (int) $userId) {
                return ['success' => false, 'error' => 'Order not found', 'data' => null];
            }

            $order['items'] = $this->order->getOrderItems($id);

            return ['success' => true, 'data' => $order];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Public order tracking. Requires the order/tracking number plus the email
     * or mobile the order was placed with.
     *
     * Returns 'not_found' rather than distinguishing "no such order" from
     * "wrong contact" on purpose — telling them apart would confirm which
     * order numbers exist.
     */
    public function trackOrder($reference, $contact)
    {
        try {
            $reference = trim((string) $reference);
            $contact = trim((string) $contact);

            if ($reference === '' || $contact === '') {
                return [
                    'success' => false,
                    'reason' => 'invalid',
                    'error' => 'Order number and the email or mobile used to place it are both required.',
                ];
            }

            $order = $this->order->findForTracking($reference, $contact);

            if (!$order) {
                return [
                    'success' => false,
                    'reason' => 'not_found',
                    'error' => 'No order matches those details. Check the order number and the email or mobile it was placed with.',
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'order_number'     => $order['order_number'],
                    'customer_name'    => $order['customer_name'],
                    'status'           => $order['status'] ?: 'pending',
                    'payment_status'   => $order['payment_status'],
                    'grand_total'      => $order['grand_total'],
                    'shipping_method'  => $order['shipping_method'],
                    'shipping_address' => $order['shipping_address'],
                    'courier_name'     => $order['courier_name'],
                    'tracking_number'  => $order['tracking_number'],
                    'tracking_url'     => $order['tracking_url'],
                    'placed_at'        => $order['created_at'],
                    'updated_at'       => $order['updated_at'],
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
        return $this->order->updateOrderStatus($order_id, $status, $notes, $user_id);
    }

    public function updatePaymentStatus($order_id, $payment_status)
    {
        return $this->order->updatePaymentStatus($order_id, $payment_status);
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

            $rawItems = $data['items'] ?? [];
            if (!is_array($rawItems) || count($rawItems) === 0) {
                throw new Exception('Order must contain at least one item');
            }

            $items = [];
            $subtotal = 0.0;

            foreach ($rawItems as $raw) {
                $productId = (int) ($raw['product_id'] ?? $raw['id'] ?? 0);
                $quantity  = (int) ($raw['quantity'] ?? 1);

                if ($productId < 1) throw new Exception('Each item needs a valid product_id');
                if ($quantity < 1)  throw new Exception('Item quantity must be at least 1');

                $product = $this->order->getProductForOrder($productId);
                if (!$product) {
                    throw new Exception("Product $productId is not available");
                }

                // Mirror the storefront's price precedence.
                $unitPrice = (float) ($product['offer_price'] ?: $product['discount_price'] ?: $product['price']);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;

                $attributes = $raw['attributes'] ?? null;

                $items[] = [
                    'product_id'    => $productId,
                    'product_name'  => $product['name'],
                    'product_sku'   => $product['sku'],
                    'quantity'      => $quantity,
                    'unit_price'    => $unitPrice,
                    'total_price'   => $lineTotal,
                    'product_image' => $product['image_url'],
                    'attributes'    => $attributes ? json_encode($attributes) : null,
                ];
            }

            $subtotal      = round($subtotal, 2);
            $shippingCost  = (float) ($data['shipping_cost'] ?? ($subtotal > 1000 ? 0 : 160));
            $taxAmount     = (float) ($data['tax_amount'] ?? 0);
            $discount      = (float) ($data['discount_amount'] ?? 0);
            $grandTotal    = round($subtotal + $shippingCost + $taxAmount - $discount, 2);

            $shippingAddress = $this->formatAddress($address);

            $orderId = $this->order->createOrder([
                'user_id'          => $userId,
                'customer_name'    => $customerName,
                'customer_email'   => $customerEmail,
                'customer_phone'   => $customerPhone,
                'subtotal'         => $subtotal,
                'total_amount'     => $subtotal,
                'tax_amount'       => $taxAmount,
                'shipping_cost'    => $shippingCost,
                'discount_amount'  => $discount,
                'grand_total'      => $grandTotal,
                'payment_method'   => $data['paymentMethod'] ?? $data['payment_method'] ?? 'cod',
                'notes'            => $address['orderNotes'] ?? $data['notes'] ?? '',
                'shipping_address' => $shippingAddress,
                'billing_address'  => $shippingAddress,
                'shipping_method'  => $data['shipping_method'] ?? 'standard',
                'coupon_code'      => $data['coupon_code'] ?? '',
                'items'            => $items,
            ]);

            return [
                'success'      => true,
                'message'      => 'Order created successfully',
                'order_id'     => $orderId['id'],
                'order_number' => $orderId['order_number'],
                'grand_total'  => $grandTotal,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
}
?>