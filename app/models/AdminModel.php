<?php

class AdminModel extends Model
{
    public function dashboardStats(): array
    {
        $statement = $this->db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM products) AS product_count,
                (SELECT COUNT(*) FROM users WHERE role = 'customer') AS customer_count,
                (SELECT COUNT(*) FROM orders) AS order_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders
                 WHERE payment_status = 'Dibayar' OR status = 'Selesai') AS revenue"
        );
        $statement->execute();
        return $statement->fetch();
    }

    public function recentOrders(int $limit = 8): array
    {
        $statement = $this->db->prepare(
            'SELECT o.invoice_number, o.total_amount, o.status, o.payment_status,
                    o.created_at, u.name AS customer_name
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             ORDER BY o.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min(30, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function lowStockItems(): array
    {
        $settings = $this->db->query(
            'SELECT low_stock_threshold FROM store_settings WHERE id = 1'
        )->fetch();
        $threshold = max(0, (int) ($settings['low_stock_threshold'] ?? 5));
        $statement = $this->db->prepare(
            'SELECT p.name AS item_name, p.stock, :threshold AS stock_threshold, "Produk" AS item_type
             FROM products p
             WHERE p.is_active = 1 AND p.stock <= :threshold_products
             UNION ALL
             SELECT CONCAT(p.name, " / ", pv.variant_name) AS item_name,
                    pv.stock, :threshold_variant AS stock_threshold, "Varian" AS item_type
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE p.is_active = 1 AND pv.stock <= :threshold_variants
             ORDER BY stock ASC, item_name ASC
             LIMIT 30'
        );
        $statement->execute([
            'threshold' => $threshold,
            'threshold_products' => $threshold,
            'threshold_variant' => $threshold,
            'threshold_variants' => $threshold,
        ]);
        return $statement->fetchAll();
    }

    public function topProducts(int $limit = 5): array
    {
        $statement = $this->db->prepare(
            "SELECT oi.product_name, SUM(oi.quantity) AS units_sold
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status <> 'Dibatalkan'
             GROUP BY oi.product_id, oi.product_name
             ORDER BY units_sold DESC, oi.product_name ASC
             LIMIT :limit"
        );
        $statement->bindValue('limit', max(1, min(20, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function salesSeries(string $period): array
    {
        $today = new DateTimeImmutable('today');
        if ($period === 'weekly') {
            $start = $today->modify('monday this week')->modify('-7 weeks');
            $keys = [];
            $labels = [];
            for ($offset = 0; $offset < 8; $offset++) {
                $week = $start->modify('+' . $offset . ' weeks');
                $keys[] = $week->format('oW');
                $labels[] = $week->format('d M');
            }
            $sql = "SELECT YEARWEEK(created_at, 3) AS bucket, SUM(total_amount) AS amount
                    FROM orders
                    WHERE (payment_status = 'Dibayar' OR status = 'Selesai')
                      AND created_at >= :start
                    GROUP BY YEARWEEK(created_at, 3)";
            $startDate = $start->format('Y-m-d 00:00:00');
        } elseif ($period === 'monthly') {
            $start = $today->modify('first day of this month')->modify('-5 months');
            $keys = [];
            $labels = [];
            for ($offset = 0; $offset < 6; $offset++) {
                $month = $start->modify('+' . $offset . ' months');
                $keys[] = $month->format('Y-m');
                $labels[] = $month->format('M Y');
            }
            $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS bucket, SUM(total_amount) AS amount
                    FROM orders
                    WHERE (payment_status = 'Dibayar' OR status = 'Selesai')
                      AND created_at >= :start
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')";
            $startDate = $start->format('Y-m-d 00:00:00');
        } else {
            $start = $today->modify('-6 days');
            $keys = [];
            $labels = [];
            for ($offset = 0; $offset < 7; $offset++) {
                $day = $start->modify('+' . $offset . ' days');
                $keys[] = $day->format('Y-m-d');
                $labels[] = $day->format('D j M');
            }
            $sql = "SELECT DATE(created_at) AS bucket, SUM(total_amount) AS amount
                    FROM orders
                    WHERE (payment_status = 'Dibayar' OR status = 'Selesai')
                      AND created_at >= :start
                    GROUP BY DATE(created_at)";
            $startDate = $start->format('Y-m-d 00:00:00');
        }

        $statement = $this->db->prepare($sql);
        $statement->execute(['start' => $startDate]);
        $amounts = array_fill_keys($keys, 0.0);
        foreach ($statement->fetchAll() as $row) {
            $bucket = (string) $row['bucket'];
            if (array_key_exists($bucket, $amounts)) {
                $amounts[$bucket] = (float) $row['amount'];
            }
        }

        return ['labels' => $labels, 'values' => array_values($amounts)];
    }

    public function customers(string $search, int $page = 1, int $perPage = 20): array
    {
        $where = "u.role = 'customer'";
        $params = [];
        if ($search !== '') {
            $where .= ' AND (u.name LIKE :name OR u.email LIKE :email OR u.phone LIKE :phone)';
            $params['name'] = '%' . $search . '%';
            $params['email'] = '%' . $search . '%';
            $params['phone'] = '%' . $search . '%';
        }

        $count = $this->db->prepare('SELECT COUNT(*) FROM users u WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $statement = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
                    COUNT(DISTINCT o.id) AS order_count,
                    COALESCE(SUM(
                        CASE WHEN o.payment_status = "Dibayar" OR o.status = "Selesai"
                             THEN o.total_amount ELSE 0 END
                    ), 0) AS spent
             FROM users u
             LEFT JOIN orders o ON o.user_id = u.id
             WHERE ' . $where . '
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function reportRows(string $from, string $to): array
    {
        $statement = $this->db->prepare(
            'SELECT o.invoice_number, o.created_at, u.name AS customer_name, u.email,
                    o.status, o.payment_status, o.payment_method, o.subtotal,
                    o.shipping_cost, o.discount_amount, o.total_amount
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.created_at >= :from_date
               AND o.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)
             ORDER BY o.created_at DESC'
        );
        $statement->execute([
            'from_date' => $from . ' 00:00:00',
            'to_date' => $to . ' 00:00:00',
        ]);
        return $statement->fetchAll();
    }

    public function storeSettings(): array
    {
        $settings = $this->db->query(
            'SELECT store_name, phone, email, pickup_address, qris_image_path,
                    low_stock_threshold
             FROM store_settings WHERE id = 1'
        )->fetch();
        if ($settings) {
            return $settings;
        }
        return [
            'store_name' => 'Momoji',
            'phone' => '',
            'email' => '',
            'pickup_address' => '',
            'qris_image_path' => null,
            'low_stock_threshold' => 5,
        ];
    }

    public function saveStoreSettings(array $data, ?string $qrisPath): void
    {
        $statement = $this->db->prepare(
            'UPDATE store_settings
             SET store_name = :store_name, phone = :phone, email = :email,
                 pickup_address = :pickup_address, low_stock_threshold = :threshold,
                 qris_image_path = COALESCE(:qris_path, qris_image_path)
             WHERE id = 1'
        );
        $statement->execute([
            'store_name' => $data['store_name'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'pickup_address' => $data['pickup_address'] !== '' ? $data['pickup_address'] : null,
            'threshold' => $data['low_stock_threshold'],
            'qris_path' => $qrisPath,
        ]);
        if ($statement->rowCount() === 0) {
            $check = $this->db->query('SELECT id FROM store_settings WHERE id = 1')->fetchColumn();
            if ($check === false) {
                throw new RuntimeException('Pengaturan toko tidak ditemukan.');
            }
        }
    }
}
