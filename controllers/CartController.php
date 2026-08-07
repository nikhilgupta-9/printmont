<?php
require_once(__DIR__ . '/../models/CartModel.php');

class CartController {
    private CartModel $model;

    public function __construct() {
        $this->model = new CartModel();
    }

    public function getCart(int $userId): array {
        $customerId = $this->model->getOrCreateCustomerId($userId);
        $items = $this->model->getCart($customerId);

        $subtotal = 0;
        $formatted = array_map(function ($row) use (&$subtotal) {
            $price = $row['offer_price'] ?? $row['discount_price'] ?? $row['price'];
            $lineTotal = round((float) $price * (int) $row['quantity'], 2);
            $subtotal += $lineTotal;

            return [
                'item_id'     => (int) $row['id'],
                'product_id'  => (int) $row['product_id'],
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'image'       => $row['image_url'],
                'price'       => (float) $price,
                'quantity'    => (int) $row['quantity'],
                'line_total'  => $lineTotal,
                'attributes'  => $row['attributes'] ? json_decode($row['attributes'], true) : null,
                'in_stock'    => $row['product_status'] === 'active' && (int) $row['stock_quantity'] > 0,
                'min_quantity'=> (int) $row['min_quantity'],
                'added_at'    => $row['created_at'],
            ];
        }, $items);

        return [
            'success' => true,
            'data' => [
                'items'    => $formatted,
                'subtotal' => round($subtotal, 2),
                'count'    => count($formatted),
            ],
        ];
    }

    public function addToCart(int $userId, int $productId, int $quantity, ?array $attributes = null): array {
        if ($quantity < 1) {
            return ['success' => false, 'error' => 'Quantity must be at least 1'];
        }
        if (!$this->model->productExists($productId)) {
            return ['success' => false, 'error' => 'Product not found or unavailable'];
        }

        $customerId = $this->model->getOrCreateCustomerId($userId);
        $attributesJson = $attributes ? json_encode($attributes) : null;

        $existing = $this->model->findExistingItem($customerId, $productId, $attributesJson);
        if ($existing) {
            $newQuantity = (int) $existing['quantity'] + $quantity;
            $this->model->updateQuantity((int) $existing['id'], $newQuantity);
            return ['success' => true, 'message' => 'Cart updated', 'item_id' => (int) $existing['id'], 'quantity' => $newQuantity];
        }

        $itemId = $this->model->addItem($customerId, $productId, $quantity, $attributesJson);
        if (!$itemId) {
            return ['success' => false, 'error' => 'Failed to add item to cart'];
        }

        return ['success' => true, 'message' => 'Item added to cart', 'item_id' => $itemId, 'quantity' => $quantity];
    }

    public function updateCartItem(int $userId, int $itemId, int $quantity): array {
        if ($quantity < 1) {
            return ['success' => false, 'error' => 'Quantity must be at least 1'];
        }

        $item = $this->assertOwnership($userId, $itemId);
        if (isset($item['error'])) {
            return $item;
        }

        if (!$this->model->updateQuantity($itemId, $quantity)) {
            return ['success' => false, 'error' => 'Failed to update cart item'];
        }

        return ['success' => true, 'message' => 'Cart item updated', 'item_id' => $itemId, 'quantity' => $quantity];
    }

    /** Empty the whole cart — used after checkout and by "remove all". */
    public function clearCart(int $userId): array {
        $customerId = $this->model->getOrCreateCustomerId($userId);
        $removed = $this->model->countItems($customerId);

        if ($removed === 0) {
            return ['success' => true, 'message' => 'Cart is already empty', 'removed' => 0];
        }

        if (!$this->model->clearCart($customerId)) {
            return ['success' => false, 'error' => 'Failed to clear cart'];
        }

        return ['success' => true, 'message' => 'Cart cleared', 'removed' => $removed];
    }

    public function removeFromCart(int $userId, int $itemId): array {
        $item = $this->assertOwnership($userId, $itemId);
        if (isset($item['error'])) {
            return $item;
        }

        if (!$this->model->removeItem($itemId)) {
            return ['success' => false, 'error' => 'Failed to remove cart item'];
        }

        return ['success' => true, 'message' => 'Item removed from cart'];
    }

    // Confirms the cart item belongs to this user's own customer record
    // before allowing update/delete. Returns ['error'=>...] on failure.
    private function assertOwnership(int $userId, int $itemId): array {
        $item = $this->model->getItemById($itemId);
        if (!$item) {
            return ['success' => false, 'error' => 'Cart item not found'];
        }

        $customerId = $this->model->getOrCreateCustomerId($userId);
        if ((int) $item['customer_id'] !== $customerId) {
            return ['success' => false, 'error' => 'Cart item not found'];
        }

        return $item;
    }
}
?>
