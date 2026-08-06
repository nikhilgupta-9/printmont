<?php
require_once(__DIR__ . '/../config/database.php');

class WishlistModel {
    private Database $db;

    public function __construct() {
        $this->db = new Database();
    }

    // wishlist_items.customer_id is a customers.id, not a users.id — mirror
    // CartModel so both carts and wishlists resolve the same customer row.
    public function getOrCreateCustomerId(int $userId): int {
        $row = $this->db->fetch("SELECT id FROM customers WHERE user_id = ?", [$userId]);
        if ($row) {
            return (int) $row['id'];
        }

        return $this->db->insert(
            "INSERT INTO customers (user_id, customer_type, status, registration_date, created_at)
             VALUES (?, 'individual', 'active', NOW(), NOW())",
            [$userId]
        );
    }

    public function getWishlist(int $customerId): array {
        return $this->db->fetchAll(
            "SELECT wi.id, wi.product_id, wi.created_at,
                    p.name, p.slug, p.price, p.regular_price, p.offer_price, p.discount_price,
                    p.stock_quantity, p.status AS product_status,
                    (SELECT image_url FROM product_images pi
                      WHERE pi.product_id = p.id
                      ORDER BY pi.is_primary DESC, pi.display_order ASC LIMIT 1) AS image_url
             FROM wishlist_items wi
             JOIN products p ON p.id = wi.product_id
             WHERE wi.customer_id = ?
             ORDER BY wi.created_at DESC",
            [$customerId]
        );
    }

    public function findItem(int $customerId, int $productId): ?array {
        $row = $this->db->fetch(
            "SELECT id FROM wishlist_items WHERE customer_id = ? AND product_id = ?",
            [$customerId, $productId]
        );
        return $row ?: null;
    }

    public function addItem(int $customerId, int $productId): int {
        return $this->db->insert(
            "INSERT INTO wishlist_items (customer_id, product_id, created_at) VALUES (?, ?, NOW())",
            [$customerId, $productId]
        );
    }

    public function removeItem(int $customerId, int $productId): bool {
        return $this->db->execute(
            "DELETE FROM wishlist_items WHERE customer_id = ? AND product_id = ?",
            [$customerId, $productId]
        );
    }

    public function productExists(int $productId): bool {
        $row = $this->db->fetch("SELECT id FROM products WHERE id = ? AND status = 'active'", [$productId]);
        return (bool) $row;
    }
}
