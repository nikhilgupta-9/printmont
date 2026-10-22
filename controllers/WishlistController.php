<?php
require_once(__DIR__ . '/../models/WishlistModel.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/product-sales-schema.php');

class WishlistController {
    private WishlistModel $model;

    public function __construct() {
        $this->model = new WishlistModel();
    }

    public function getWishlist(int $userId): array {
        $customerId = $this->model->getOrCreateCustomerId($userId);
        $items = $this->model->getWishlist($customerId);

        // Live timed sales (admin: product-sales.php), so the wishlist shows the same price as the product page
        $salesDb = null;
        try {
            $salesDb = (new Database())->getConnection();
        } catch (Throwable $e) {
            $salesDb = null;
        }

        // The frontend matches on id OR product_id, and renders name/image/price.
        $formatted = array_map(function ($row) use ($salesDb) {
            // Same selling price rule as ProductController::formatProductForApi
            $price = !empty($row['offer_price']) ? (float) $row['offer_price']
                : (!empty($row['discount_price']) ? (float) $row['discount_price'] : (float) $row['price']);
            $liveSale = $salesDb ? getLiveSaleForProduct($salesDb, (int) $row['product_id']) : null;
            if ($liveSale) {
                $salePrice = salePriceFor($liveSale, $price);
                if ($salePrice > 0 && $salePrice < $price) $price = $salePrice;
            }

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
                'in_stock'     => $row['product_status'] === 'active'
                                  && ((int) $row['stock_quantity'] > 0 || ($row['out_of_stock_status'] ?? '') === 'pre_order'),
                'rating'       => $row['rating_avg'] !== null ? (float) $row['rating_avg'] : null,
                'rating_count' => (int) $row['rating_count'],
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
