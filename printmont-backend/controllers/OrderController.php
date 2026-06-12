<?php
require_once(__DIR__ . '/../config/database.php');
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

    public function getOrderById($id)
    {
        return $this->order->getOrderById($id);
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

    public function getcompletedOrders($limit = 10, $page = 1, $filters = [])
    {
        return $this->order->completedOrders($limit, $page, $filters);
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
    public function updateOrderStatusAPI($orderId, $status, $notes = '', $createdBy = null)
    {
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
    public function getCustomerOrders($userId)
    {
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
    public function getDashboardStats()
    {
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

    // Fixed getAllSoldOrders method - calls the Order model instead of trying to run SQL directly
    public function getAllSoldOrders($limit = 15, $page = 1, $filters = [])
    {
        // Simply call the Order model's method
        return $this->order->getAllSoldOrders($limit, $page, $filters);
    }
}
?>