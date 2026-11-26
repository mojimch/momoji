<?php

class CartModel extends Model
{
    public function cartForUser(int $userId): array
    {
        $cart = $this->getOrCreateCart($userId);
        return $this->items((int) $cart['id']);
    }

    private function getOrCreateCart(int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM carts WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $cart = $statement->fetch();
        if ($cart) {
            return $cart;
        }
        $insert = $this->db->prepare('INSERT INTO carts (user_id) VALUES (:user_id)');
        $insert->execute(['user_id' => $userId]);
        return ['id' => (int) $this->db->lastInsertId(), 'user_id' => $userId];
    }

    public function add(int $userId, int $productId, ?int $variantId, int $quantity): bool
    {
        $cart = $this->getOrCreateCart($userId);
        $quantity = max(1, $quantity);

        $productStatement = $this->db->prepare(
            'SELECT 1 FROM products WHERE id = :id AND is_active = 1'
        );
        $productStatement->execute(['id' => $productId]);
        if (!$productStatement->fetchColumn()) {
            return false;
        }

        $stockStatement = $this->db->prepare(
            $variantId
                ? 'SELECT stock FROM product_variants WHERE id = :id AND product_id = :product_id'
                : 'SELECT stock FROM products WHERE id = :id'
        );
        $params = $variantId
            ? ['id' => $variantId, 'product_id' => $productId]
            : ['id' => $productId];
        $stockStatement->execute($params);
        $stock = (int) ($stockStatement->fetchColumn() ?: 0);
        if ($stock < $quantity) {
            return false;
        }

        $find = $this->db->prepare(
            'SELECT id, quantity FROM cart_items
             WHERE cart_id = :cart_id
               AND product_id = :product_id
               AND (variant_id = :variant_id OR (:variant_id_is_null IS NULL AND variant_id IS NULL))
             LIMIT 1'
        );
        $find->execute([
            'cart_id' => (int) $cart['id'],
            'product_id' => $productId,
            'variant_id' => $variantId,
            'variant_id_is_null' => $variantId,
        ]);
        $existing = $find->fetch();
        if ($existing) {
            $quantity = (int) $existing['quantity'] + $quantity;
            if ($quantity > $stock) {
                return false;
            }
            $update = $this->db->prepare(
                'UPDATE cart_items SET quantity = :quantity, selected = 1 WHERE id = :id'
            );
            $update->execute(['quantity' => $quantity, 'id' => (int) $existing['id']]);
            return true;
        }

        $statement = $this->db->prepare(
            'INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, selected)
             VALUES (:cart_id, :product_id, :variant_id, :quantity, 1)'
        );
        $statement->execute([
            'cart_id' => (int) $cart['id'],
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
        return true;
    }

    public function items(int $cartId): array
    {
        $statement = $this->db->prepare(
            'SELECT ci.id, ci.product_id, ci.variant_id, ci.quantity, ci.selected,
                    p.name, p.slug, p.price, p.promo_price, p.stock AS product_stock,
                    p.weight_grams,
                    pv.variant_name, pv.additional_price, pv.stock AS variant_stock,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id
                     ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_path
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             LEFT JOIN product_variants pv ON pv.id = ci.variant_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.id DESC'
        );
        $statement->execute(['cart_id' => $cartId]);
        return $statement->fetchAll();
    }

    public function itemsForUser(int $userId): array
    {
        $statement = $this->db->prepare('SELECT id FROM carts WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $cartId = (int) ($statement->fetchColumn() ?: 0);
        return $cartId ? $this->items($cartId) : [];
    }

    public function updateQuantity(int $userId, int $itemId, int $quantity): bool
    {
        $cart = $this->getOrCreateCart($userId);
        $find = $this->db->prepare(
            'SELECT ci.product_id, ci.variant_id
             FROM cart_items ci
             WHERE ci.id = :id AND ci.cart_id = :cart_id'
        );
        $find->execute(['id' => $itemId, 'cart_id' => (int) $cart['id']]);
        $item = $find->fetch();
        if (!$item) {
            return false;
        }

        $stockStatement = $this->db->prepare(
            $item['variant_id']
                ? 'SELECT stock FROM product_variants WHERE id = :id'
                : 'SELECT stock FROM products WHERE id = :id'
        );
        $stockStatement->execute([
            'id' => $item['variant_id'] ?: $item['product_id'],
        ]);
        $stock = (int) ($stockStatement->fetchColumn() ?: 0);
        if ($quantity < 1 || $quantity > $stock) {
            return false;
        }

        $update = $this->db->prepare(
            'UPDATE cart_items SET quantity = :quantity
             WHERE id = :id AND cart_id = :cart_id'
        );
        $update->execute([
            'quantity' => $quantity,
            'id' => $itemId,
            'cart_id' => (int) $cart['id'],
        ]);
        return true;
    }

    public function setSelected(int $userId, int $itemId, bool $selected): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->db->prepare(
            'UPDATE cart_items SET selected = :selected
             WHERE id = :id AND cart_id = :cart_id'
        );
        $statement->execute([
            'selected' => $selected ? 1 : 0,
            'id' => $itemId,
            'cart_id' => (int) $cart['id'],
        ]);
    }

    public function remove(int $userId, int $itemId): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->db->prepare(
            'DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id'
        );
        $statement->execute(['id' => $itemId, 'cart_id' => (int) $cart['id']]);
    }

    public function selectedItems(int $userId): array
    {
        return array_values(array_filter(
            $this->itemsForUser($userId),
            fn(array $item): bool => (bool) $item['selected']
        ));
    }

    public function removeSelected(int $userId): void
    {
        $cart = $this->getOrCreateCart($userId);
        $statement = $this->db->prepare(
            'DELETE FROM cart_items WHERE cart_id = :cart_id AND selected = 1'
        );
        $statement->execute(['cart_id' => (int) $cart['id']]);
    }
}
