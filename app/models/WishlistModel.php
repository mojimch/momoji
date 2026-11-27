<?php

class WishlistModel extends Model
{
    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.name, p.slug, p.price, p.promo_price, p.stock,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id
                     ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_path
             FROM wishlists w
             JOIN products p ON p.id = w.product_id
             WHERE w.user_id = :user_id AND p.is_active = 1
             ORDER BY w.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        $products = $statement->fetchAll();

        if ($products === []) {
            return [];
        }

        $productIds = array_map(static fn(array $product): int => (int) $product['id'], $products);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $variants = $this->db->prepare(
            'SELECT id, product_id, variant_name, additional_price, stock
             FROM product_variants
             WHERE product_id IN (' . $placeholders . ')
             ORDER BY product_id, id'
        );
        $variants->execute($productIds);
        $variantsByProduct = [];
        foreach ($variants->fetchAll() as $variant) {
            $variantsByProduct[(int) $variant['product_id']][] = $variant;
        }

        foreach ($products as &$product) {
            $product['variants'] = $variantsByProduct[(int) $product['id']] ?? [];
        }
        unset($product);
        return $products;
    }

    public function add(int $userId, int $productId): bool
    {
        $statement = $this->db->prepare(
            'INSERT IGNORE INTO wishlists (user_id, product_id)
             SELECT :user_id, id FROM products
             WHERE id = :product_id AND is_active = 1'
        );
        $statement->execute(['user_id' => $userId, 'product_id' => $productId]);
        return $statement->rowCount() > 0;
    }

    public function remove(int $userId, int $productId): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id'
        );
        $statement->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    public function contains(int $userId, int $productId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM wishlists WHERE user_id = :user_id AND product_id = :product_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'product_id' => $productId]);
        return (bool) $statement->fetchColumn();
    }
}
