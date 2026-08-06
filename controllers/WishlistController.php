<?php
require_once(__DIR__ . '/../models/WishlistModel.php');

class WishlistController {
    private WishlistModel $model;

    public function __construct() {
        $this->model = new WishlistModel();
    }

    public function getWishlist(int $userId): array {
        $customerId = $this->model->getOrCreateCustomerId($userId);
        $items = $this->model->getWishlist($customerId);

        // The frontend matches on id OR product_id, and renders name/image/price.
        $formatted = array_map(function ($row) {
            $price = $row['offer_price'] ?? $row['discount_price'] ?? $row['price'];

            return [
                'id'           => (int) $row['product_id'],
                'item_id'      => (int) $row['id'],
                'product_id'   => (int) $row['product_id'],
                'name'         => $row['name'],
                'slug'         => $row['slug'],
                'image'        => $row['image_url'],
                'image_url'    => $row['image_url'],
                'price'        => (float) $price,
                'regular_price' => $row['regular_price'] !== null ? (float) $row['regular_price'] : null,
                'in_stock'     => $row['product_status'] === 'active' && (int) $row['stock_quantity'] > 0,
                'added_at'     => $row['created_at'],
            ];
        }, $items);

        return ['success' => true, 'data' => $formatted, 'count' => count($formatted)];
    }

    public function addToWishlist(int $userId, int $productId): array {
        if (!$this->model->productExists($productId)) {
            return ['success' => false, 'error' => 'Product not found or unavailable'];
        }

        $customerId = $this->model->getOrCreateCustomerId($userId);

        // Adding twice is a no-op, not an error — the UI treats it as idempotent.
        if ($this->model->findItem($customerId, $productId)) {
            return ['success' => true, 'message' => 'Already in wishlist'];
        }

        $this->model->addItem($customerId, $productId);

        return ['success' => true, 'message' => 'Added to wishlist'];
    }

    public function removeFromWishlist(int $userId, int $productId): array {
        $customerId = $this->model->getOrCreateCustomerId($userId);

        if (!$this->model->findItem($customerId, $productId)) {
            return ['success' => false, 'error' => 'Item not in wishlist'];
        }

        $this->model->removeItem($customerId, $productId);

        return ['success' => true, 'message' => 'Removed from wishlist'];
    }
}
