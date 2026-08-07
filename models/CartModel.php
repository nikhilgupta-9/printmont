<?php
require_once(__DIR__ . '/../config/database.php');

class CartModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Get (or create) the customers.id row for a given users.id.
    // Every user created via registration already gets one, but this is a
    // safe fallback in case a user row predates the customers table sync.
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

    public function getCart(int $customerId): array {
        return $this->db->fetchAll(
            "SELECT ci.id, ci.product_id, ci.quantity, ci.attributes, ci.created_at,
                    p.name, p.slug, p.price, p.regular_price, p.offer_price, p.discount_price,
                    p.stock_quantity, p.status AS product_status, p.min_quantity,
                    (SELECT image_url FROM product_images pi
                      WHERE pi.product_id = p.id
                      ORDER BY pi.is_primary DESC, pi.display_order ASC LIMIT 1) AS image_url
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.customer_id = ?
             ORDER BY ci.created_at DESC",
            [$customerId]
        );
    }

    public function getItemById(int $itemId): ?array {
        $row = $this->db->fetch("SELECT * FROM cart_items WHERE id = ?", [$itemId]);
        return $row ?: null;
    }

    // Same product + same selected attributes (color/size/etc.) = same line item.
    public function findExistingItem(int $customerId, int $productId, ?string $attributes): ?array {
        $row = $this->db->fetch(
            "SELECT * FROM cart_items
             WHERE customer_id = ? AND product_id = ?
               AND attributes <=> ?",
            [$customerId, $productId, $attributes]
        );
        return $row ?: null;
    }

    public function addItem(int $customerId, int $productId, int $quantity, ?string $attributes): int {
        return $this->db->insert(
            "INSERT INTO cart_items (customer_id, product_id, quantity, attributes, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$customerId, $productId, $quantity, $attributes]
        );
    }

    public function updateQuantity(int $itemId, int $quantity): bool {
        return (bool) $this->db->execute(
            "UPDATE cart_items SET quantity = ? WHERE id = ?",
            [$quantity, $itemId]
        );
    }

    public function removeItem(int $itemId): bool {
        return (bool) $this->db->execute("DELETE FROM cart_items WHERE id = ?", [$itemId]);
    }

    /** Scoped by customer_id so a clear can never reach another user's cart. */
    public function clearCart(int $customerId): bool {
        return (bool) $this->db->execute("DELETE FROM cart_items WHERE customer_id = ?", [$customerId]);
    }

    public function countItems(int $customerId): int {
        $row = $this->db->fetch("SELECT COUNT(*) AS n FROM cart_items WHERE customer_id = ?", [$customerId]);
        return (int) ($row['n'] ?? 0);
    }

    public function productExists(int $productId): bool {
        $row = $this->db->fetch("SELECT id FROM products WHERE id = ? AND status = 'active'", [$productId]);
        return (bool) $row;
    }
}
?>
