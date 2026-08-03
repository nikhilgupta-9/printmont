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

    // Create new order
    public function createOrder($data)
    {
        try {
            $required = ['customer_name', 'customer_email', 'total_amount', 'grand_total', 'items'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }

            $orderId = $this->order->createOrder($data);

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'order_number' => $data['order_number'] ?? ''
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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