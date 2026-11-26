<?php

class CatalogModel extends Model
{
    public function categories(): array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, slug, icon, created_at
             FROM categories
             ORDER BY name ASC'
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    public function category(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function createCategory(string $name, string $icon = ''): int
    {
        $baseSlug = slugify($name);
        $slug = $baseSlug;
        $counter = 1;
        while ($this->slugExistsCategory($slug)) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $statement = $this->db->prepare(
            'INSERT INTO categories (name, slug, icon) VALUES (:name, :slug, :icon)'
        );
        $statement->execute(['name' => trim($name), 'slug' => $slug, 'icon' => trim($icon)]);
        return (int) $this->db->lastInsertId();
    }

    public function updateCategory(int $id, string $name, string $icon = ''): void
    {
        $slug = slugify($name);
        $statement = $this->db->prepare(
            'UPDATE categories SET name = :name, slug = :slug, icon = :icon
             WHERE id = :id'
        );
        try {
            $statement->execute([
                'name' => trim($name),
                'slug' => $slug,
                'icon' => trim($icon),
                'id' => $id,
            ]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('Nama kategori menghasilkan slug yang sudah digunakan.');
            }
            throw $exception;
        }
    }

    public function deleteCategory(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function slugExistsCategory(string $slug): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM categories WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);
        return (bool) $statement->fetchColumn();
    }

    public function products(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = 'p.name LIKE :q';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['category'])) {
            $where[] = 'p.category_id = :category';
            $params['category'] = (int) $filters['category'];
        }
        if (($filters['min'] ?? null) !== null && ($filters['min'] ?? '') !== '') {
            $where[] = 'COALESCE(p.promo_price, p.price) >= :min';
            $params['min'] = (float) $filters['min'];
        }
        if (($filters['max'] ?? null) !== null && ($filters['max'] ?? '') !== '') {
            $where[] = 'COALESCE(p.promo_price, p.price) <= :max';
            $params['max'] = (float) $filters['max'];
        }
        if (!empty($filters['promo'])) {
            $where[] = 'p.promo_price IS NOT NULL';
        }

        $sort = 'p.created_at DESC';
        if (($filters['sort'] ?? '') === 'price-low') {
            $sort = 'COALESCE(p.promo_price, p.price) ASC';
        } elseif (($filters['sort'] ?? '') === 'price-high') {
            $sort = 'COALESCE(p.promo_price, p.price) DESC';
        } elseif (($filters['sort'] ?? '') === 'name') {
            $sort = 'p.name ASC';
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countSql = 'SELECT COUNT(*) FROM products p WHERE ' . implode(' AND ', $where);
        $countStatement = $this->db->prepare($countSql);
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $sql = 'SELECT p.*, c.name AS category_name,
                       (SELECT pi.image_path FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_path
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $sort . '
                LIMIT :limit OFFSET :offset';
        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'page' => $page,
        ];
    }

    public function adminProducts(): array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, c.name AS category_name,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id
                     ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_path
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.created_at DESC'
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    public function product(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function productBySlug(string $slug): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        return $statement->fetch() ?: null;
    }

    public function images(int $productId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, image_path, is_primary
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY is_primary DESC, id ASC'
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function variants(int $productId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, variant_name, additional_price, stock
             FROM product_variants
             WHERE product_id = :product_id
             ORDER BY id ASC'
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }

    public function createProduct(array $data): int
    {
        $slug = $this->uniqueProductSlug($data['name']);
        $statement = $this->db->prepare(
            'INSERT INTO products
             (category_id, name, slug, description, price, promo_price, stock, weight_grams, label, is_active)
             VALUES
             (:category_id, :name, :slug, :description, :price, :promo_price, :stock, :weight_grams, :label, :is_active)'
        );
        $statement->execute([
            'category_id' => (int) $data['category_id'] ?: null,
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => trim($data['description']),
            'price' => (float) $data['price'],
            'promo_price' => $data['promo_price'] !== '' ? (float) $data['promo_price'] : null,
            'stock' => (int) $data['stock'],
            'weight_grams' => (int) $data['weight_grams'],
            'label' => $data['label'],
            'is_active' => (int) $data['is_active'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProduct(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE products SET
             category_id=:category_id, name=:name, slug=:slug, description=:description,
             price=:price, promo_price=:promo_price, stock=:stock,
             weight_grams=:weight_grams, label=:label, is_active=:is_active
             WHERE id=:id'
        );
        $statement->execute([
            'category_id' => (int) $data['category_id'] ?: null,
            'name' => trim($data['name']),
            'slug' => $this->uniqueProductSlug($data['name'], $id),
            'description' => trim($data['description']),
            'price' => (float) $data['price'],
            'promo_price' => $data['promo_price'] !== '' ? (float) $data['promo_price'] : null,
            'stock' => (int) $data['stock'],
            'weight_grams' => (int) $data['weight_grams'],
            'label' => $data['label'],
            'is_active' => (int) $data['is_active'],
            'id' => $id,
        ]);
    }

    public function deleteProduct(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function uniqueProductSlug(string $name, ?int $excludeId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $counter = 1;
        while (true) {
            $sql = 'SELECT 1 FROM products WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($excludeId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $excludeId;
            }
            $statement = $this->db->prepare($sql);
            $statement->execute($params);
            if (!$statement->fetchColumn()) {
                return $slug;
            }
            $slug = $base . '-' . $counter++;
        }
    }

    public function addImage(int $productId, string $path): void
    {
        $primary = $this->images($productId) === [];
        $statement = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, is_primary)
             VALUES (:product_id, :image_path, :is_primary)'
        );
        $statement->execute([
            'product_id' => $productId,
            'image_path' => $path,
            'is_primary' => $primary ? 1 : 0,
        ]);
    }

    public function deleteImage(int $imageId, int $productId): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM product_images WHERE id = :id AND product_id = :product_id'
        );
        $statement->execute(['id' => $imageId, 'product_id' => $productId]);
    }

    public function saveVariant(int $productId, array $data, ?int $variantId = null): void
    {
        if ($variantId) {
            $statement = $this->db->prepare(
                'UPDATE product_variants
                 SET variant_name=:variant_name, additional_price=:additional_price, stock=:stock
                 WHERE id=:id AND product_id=:product_id'
            );
            $statement->execute([
                'variant_name' => trim($data['variant_name']),
                'additional_price' => (float) $data['additional_price'],
                'stock' => (int) $data['stock'],
                'id' => $variantId,
                'product_id' => $productId,
            ]);
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO product_variants (product_id, variant_name, additional_price, stock)
                 VALUES (:product_id, :variant_name, :additional_price, :stock)'
            );
            $statement->execute([
                'product_id' => $productId,
                'variant_name' => trim($data['variant_name']),
                'additional_price' => (float) $data['additional_price'],
                'stock' => (int) $data['stock'],
            ]);
        }
    }

    public function deleteVariant(int $variantId, int $productId): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM product_variants WHERE id = :id AND product_id = :product_id'
        );
        $statement->execute(['id' => $variantId, 'product_id' => $productId]);
    }

    public function adjustStock(int $productId, ?int $variantId, int $quantityChange, string $reason, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            if ($variantId) {
                $statement = $this->db->prepare(
                    'SELECT stock FROM product_variants
                     WHERE id = :id AND product_id = :product_id
                     FOR UPDATE'
                );
                $statement->execute(['id' => $variantId, 'product_id' => $productId]);
                $before = (int) ($statement->fetchColumn() ?: 0);
                $after = $before + $quantityChange;
                if ($after < 0) {
                    $this->db->rollBack();
                    return false;
                }
                $update = $this->db->prepare(
                    'UPDATE product_variants SET stock = :stock WHERE id = :id'
                );
                $update->execute(['stock' => $after, 'id' => $variantId]);
            } else {
                $statement = $this->db->prepare(
                    'SELECT stock FROM products WHERE id = :id FOR UPDATE'
                );
                $statement->execute(['id' => $productId]);
                $before = (int) ($statement->fetchColumn() ?: 0);
                $after = $before + $quantityChange;
                if ($after < 0) {
                    $this->db->rollBack();
                    return false;
                }
                $update = $this->db->prepare(
                    'UPDATE products SET stock = :stock WHERE id = :id'
                );
                $update->execute(['stock' => $after, 'id' => $productId]);
            }

            $movement = $this->db->prepare(
                'INSERT INTO stock_movements
                 (product_id, variant_id, user_id, movement_type, quantity_change,
                  stock_before, stock_after, reason)
                 VALUES
                 (:product_id, :variant_id, :user_id, :movement_type, :quantity_change,
                  :stock_before, :stock_after, :reason)'
            );
            $movement->execute([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'user_id' => $userId,
                'movement_type' => 'adjustment',
                'quantity_change' => $quantityChange,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => trim($reason),
            ]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function stockMovements(): array
    {
        $statement = $this->db->prepare(
            'SELECT sm.*, p.name AS product_name, pv.variant_name, u.name AS user_name
             FROM stock_movements sm
             LEFT JOIN products p ON p.id = sm.product_id
             LEFT JOIN product_variants pv ON pv.id = sm.variant_id
             LEFT JOIN users u ON u.id = sm.user_id
             ORDER BY sm.created_at DESC'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
}
