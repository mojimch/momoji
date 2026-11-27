<?php

class OrderModel extends Model
{
    private const ORDER_STATUSES = [
        'Menunggu konfirmasi',
        'Dikonfirmasi',
        'Menunggu pembayaran',
        'Diproses',
        'Dikemas',
        'Siap diambil',
        'Sedang dikirim',
        'Selesai',
        'Dibatalkan',
    ];

    public function customerOrders(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT o.*, COUNT(oi.id) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = :user_id
             GROUP BY o.id
             ORDER BY o.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function findCustomerOrder(int $userId, string $invoice): ?array
    {
        $statement = $this->db->prepare(
            'SELECT o.*, sz.zone_name, p.id AS payment_id, p.admin_note
             FROM orders o
             LEFT JOIN shipping_zones sz ON sz.id = o.shipping_zone_id
             LEFT JOIN payments p ON p.order_id = o.id
             WHERE o.user_id = :user_id AND o.invoice_number = :invoice
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'invoice' => $invoice]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $order = $this->attachItems($order);
        $order['proofs'] = $this->paymentProofs((int) $order['payment_id']);
        return $order;
    }

    public function adminOrders(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'o.payment_status = :payment_status';
            $params['payment_status'] = $filters['payment_status'];
        }
        $sql = 'SELECT o.*, u.name AS customer_name,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
                FROM orders o
                LEFT JOIN users u ON u.id = o.user_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY o.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findAdminOrder(string $invoice): ?array
    {
        $statement = $this->db->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                    sz.zone_name, p.id AS payment_id, p.admin_note, p.verified_at, p.paid_at
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             LEFT JOIN shipping_zones sz ON sz.id = o.shipping_zone_id
             LEFT JOIN payments p ON p.order_id = o.id
             WHERE o.invoice_number = :invoice
             LIMIT 1'
        );
        $statement->execute(['invoice' => $invoice]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $order = $this->attachItems($order);
        $order['proofs'] = $this->paymentProofs((int) $order['payment_id']);
        return $order;
    }

    private function attachItems(array $order): array
    {
        $statement = $this->db->prepare(
            'SELECT oi.*, p.slug,
                    r.id AS review_id, r.rating AS review_rating,
                    r.comment AS review_comment, r.status AS review_status,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = oi.product_id
                     ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_path
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             LEFT JOIN reviews r ON r.order_item_id = oi.id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id'
        );
        $statement->execute(['order_id' => (int) $order['id']]);
        $order['items'] = $statement->fetchAll();
        return $order;
    }

    public function paymentProofs(int $paymentId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, image_path, original_name, mime_type, file_size, uploaded_at
             FROM payment_proofs
             WHERE payment_id = :payment_id
             ORDER BY uploaded_at DESC'
        );
        $statement->execute(['payment_id' => $paymentId]);
        return $statement->fetchAll();
    }

    public function storeSettings(): array
    {
        $statement = $this->db->prepare(
            'SELECT store_name, phone, pickup_address, qris_image_path
             FROM store_settings WHERE id = 1 LIMIT 1'
        );
        $statement->execute();
        return $statement->fetch() ?: [];
    }

    public function updateQrisImage(string $path): void
    {
        $statement = $this->db->prepare(
            'UPDATE store_settings SET qris_image_path = :path WHERE id = 1'
        );
        $statement->execute(['path' => $path]);
        if ($statement->rowCount() === 0) {
            $check = $this->db->prepare('SELECT id FROM store_settings WHERE id = 1');
            $check->execute();
            if (!$check->fetchColumn()) {
                throw new RuntimeException('Pengaturan toko belum tersedia di database.');
            }
        }
    }

    public function uploadPaymentProof(int $userId, string $invoice, array $file, string $path): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                '                SELECT o.id, o.status, o.payment_status, o.payment_method, p.id AS payment_id
                 FROM orders o
                 JOIN payments p ON p.order_id = o.id
                 WHERE o.user_id = :user_id AND o.invoice_number = :invoice
                 FOR UPDATE'
            );
            $statement->execute(['user_id' => $userId, 'invoice' => $invoice]);
            $order = $statement->fetch();
            if (!$order || $order['payment_method'] !== 'QRIS') {
                throw new RuntimeException('Pesanan QRIS tidak ditemukan.');
            }
            if (in_array($order['status'], ['Dibatalkan', 'Selesai'], true)) {
                throw new RuntimeException('Pesanan yang telah selesai atau dibatalkan tidak menerima bukti pembayaran.');
            }
            if (!in_array($order['payment_status'], ['Belum dibayar', 'Gagal / ditolak'], true)) {
                throw new RuntimeException('Pesanan ini tidak dapat menerima bukti pembayaran baru.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO payment_proofs (payment_id, image_path, original_name, mime_type, file_size)
                 VALUES (:payment_id, :image_path, :original_name, :mime_type, :file_size)'
            );
            $insert->execute([
                'payment_id' => (int) $order['payment_id'],
                'image_path' => $path,
                'original_name' => basename((string) $file['name']),
                'mime_type' => $file['mime'],
                'file_size' => (int) $file['size'],
            ]);

            $paymentUpdate = $this->db->prepare(
                'UPDATE payments SET status = :status, admin_note = NULL
                 WHERE id = :id'
            );
            $paymentUpdate->execute(['status' => 'Menunggu verifikasi', 'id' => (int) $order['payment_id']]);
            $orderUpdate = $this->db->prepare(
                'UPDATE orders SET payment_status = :status WHERE id = :id'
            );
            $orderUpdate->execute(['status' => 'Menunggu verifikasi', 'id' => (int) $order['id']]);

            $this->notifyAdmins(
                'Bukti pembayaran baru',
                'Bukti pembayaran untuk ' . $invoice . ' menunggu verifikasi.',
                '/admin/orders/detail/' . rawurlencode($invoice),
                'payment'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function proofPath(int $proofId, ?int $userId, bool $admin): ?array
    {
        $sql = 'SELECT pp.image_path, pp.mime_type
                FROM payment_proofs pp
                JOIN payments p ON p.id = pp.payment_id
                JOIN orders o ON o.id = p.order_id
                WHERE pp.id = :proof_id';
        $params = ['proof_id' => $proofId];
        if (!$admin) {
            $sql .= ' AND o.user_id = :user_id';
            $params['user_id'] = $userId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function setPaymentStatus(
        string $invoice,
        string $status,
        string $note,
        int $adminId
    ): void {
        if (!in_array($status, ['Dibayar', 'Gagal / ditolak'], true)) {
            throw new InvalidArgumentException('Status pembayaran tidak valid.');
        }
        if ($status === 'Gagal / ditolak' && trim($note) === '') {
            throw new InvalidArgumentException('Catatan penolakan wajib diisi.');
        }

        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare(
                'SELECT o.id, o.user_id, o.payment_method, o.payment_status, p.id AS payment_id
                 FROM orders o JOIN payments p ON p.order_id = o.id
                 WHERE o.invoice_number = :invoice FOR UPDATE'
            );
            $find->execute(['invoice' => $invoice]);
            $order = $find->fetch();
            if (!$order || $order['payment_method'] !== 'QRIS') {
                throw new RuntimeException('Pesanan QRIS tidak ditemukan.');
            }
            if ($order['payment_status'] !== 'Menunggu verifikasi') {
                throw new RuntimeException('Status pembayaran pesanan ini tidak dapat diubah.');
            }

            $paymentUpdate = $this->db->prepare(
                'UPDATE payments
                 SET status=:status, admin_note=:note, verified_by=:admin_id,
                     verified_at=NOW(), paid_at=:paid_at
                 WHERE id=:id'
            );
            $paymentUpdate->execute([
                'status' => $status,
                'note' => trim($note) !== '' ? trim($note) : null,
                'admin_id' => $adminId,
                'paid_at' => $status === 'Dibayar' ? date('Y-m-d H:i:s') : null,
                'id' => (int) $order['payment_id'],
            ]);
            $orderUpdate = $this->db->prepare(
                'UPDATE orders SET payment_status=:status WHERE id=:id'
            );
            $orderUpdate->execute(['status' => $status, 'id' => (int) $order['id']]);
            $this->notifyUser(
                (int) $order['user_id'],
                $status === 'Dibayar' ? 'Pembayaran diverifikasi' : 'Pembayaran ditolak',
                $status === 'Dibayar'
                    ? 'Pembayaran pesanan ' . $invoice . ' telah diverifikasi.'
                    : 'Pembayaran pesanan ' . $invoice . ' ditolak. Catatan: ' . trim($note),
                '/orders/detail/' . rawurlencode($invoice),
                'payment'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function updateStatus(string $invoice, string $newStatus, string $reason, int $actorId): void
    {
        if (!in_array($newStatus, self::ORDER_STATUSES, true)) {
            throw new InvalidArgumentException('Status pesanan tidak valid.');
        }
        if ($newStatus === 'Dibatalkan' && trim($reason) === '') {
            throw new InvalidArgumentException('Alasan pembatalan wajib diisi.');
        }

        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare(
                'SELECT id, user_id, status, payment_status, payment_method, shipping_method
                 FROM orders WHERE invoice_number=:invoice FOR UPDATE'
            );
            $find->execute(['invoice' => $invoice]);
            $order = $find->fetch();
            if (!$order) {
                throw new RuntimeException('Pesanan tidak ditemukan.');
            }

            if ($newStatus === 'Dibatalkan') {
                if ($order['status'] === 'Dibatalkan' || $order['status'] === 'Selesai') {
                    throw new RuntimeException('Pesanan selesai atau dibatalkan tidak dapat dibatalkan lagi.');
                }
                $this->releaseReservedStock($order, $invoice, $actorId);
            } elseif (!$this->isAllowedTransition($order, $newStatus)) {
                throw new RuntimeException('Perubahan status pesanan tersebut tidak diizinkan.');
            }

            $update = $this->db->prepare(
                'UPDATE orders SET status=:status, cancellation_reason=:reason WHERE id=:id'
            );
            $update->execute([
                'status' => $newStatus,
                'reason' => $newStatus === 'Dibatalkan' ? trim($reason) : null,
                'id' => (int) $order['id'],
            ]);

            if ($newStatus === 'Dibatalkan' && $order['payment_status'] === 'Menunggu verifikasi') {
                $paymentUpdate = $this->db->prepare(
                    'UPDATE payments SET status=:status, admin_note=:note WHERE order_id=:order_id'
                );
                $paymentUpdate->execute([
                    'status' => 'Gagal / ditolak',
                    'note' => trim($reason),
                    'order_id' => (int) $order['id'],
                ]);
                $orderPaymentUpdate = $this->db->prepare(
                    'UPDATE orders SET payment_status=:status WHERE id=:id'
                );
                $orderPaymentUpdate->execute([
                    'status' => 'Gagal / ditolak',
                    'id' => (int) $order['id'],
                ]);
            }

            $this->notifyUser(
                (int) $order['user_id'],
                'Status pesanan diperbarui',
                'Status pesanan ' . $invoice . ' sekarang: ' . $newStatus
                    . ($newStatus === 'Dibatalkan' ? '. Alasan: ' . trim($reason) : '.'),
                '/orders/detail/' . rawurlencode($invoice),
                'order'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function markCodPaid(string $invoice, int $adminId): void
    {
        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare(
                'SELECT o.id, o.user_id, o.status, o.payment_method, o.payment_status, p.id AS payment_id
                 FROM orders o JOIN payments p ON p.order_id=o.id
                 WHERE o.invoice_number=:invoice FOR UPDATE'
            );
            $find->execute(['invoice' => $invoice]);
            $order = $find->fetch();
            if (
                !$order
                || $order['payment_method'] !== 'COD'
                || $order['payment_status'] === 'Dibayar'
                || in_array($order['status'], ['Dibatalkan', 'Selesai'], true)
            ) {
                throw new RuntimeException('Pembayaran COD tidak dapat diperbarui.');
            }
            $payment = $this->db->prepare(
                'UPDATE payments SET status=:status, verified_by=:admin_id,
                 verified_at=NOW(), paid_at=NOW() WHERE id=:id'
            );
            $payment->execute([
                'status' => 'Dibayar',
                'admin_id' => $adminId,
                'id' => (int) $order['payment_id'],
            ]);
            $update = $this->db->prepare(
                'UPDATE orders SET payment_status=:status WHERE id=:id'
            );
            $update->execute(['status' => 'Dibayar', 'id' => (int) $order['id']]);
            $this->notifyUser(
                (int) $order['user_id'],
                'Pembayaran diterima',
                'Pembayaran COD pesanan ' . $invoice . ' telah diterima.',
                '/orders/detail/' . rawurlencode($invoice),
                'payment'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function isAllowedTransition(array $order, string $newStatus): bool
    {
        $allowed = [
            'Menunggu konfirmasi' => ['Dikonfirmasi'],
            'Dikonfirmasi' => ['Menunggu pembayaran', 'Diproses'],
            'Menunggu pembayaran' => ['Dikonfirmasi'],
            'Diproses' => ['Dikemas'],
            'Dikemas' => $order['shipping_method'] === 'pickup' ? ['Siap diambil'] : ['Sedang dikirim'],
            'Siap diambil' => ['Selesai'],
            'Sedang dikirim' => ['Selesai'],
            'Selesai' => [],
            'Dibatalkan' => [],
        ];
        if (!in_array($newStatus, $allowed[$order['status']] ?? [], true)) {
            return false;
        }
        if ($newStatus === 'Diproses' && $order['payment_method'] === 'QRIS' && $order['payment_status'] !== 'Dibayar') {
            return false;
        }
        if ($newStatus === 'Dikonfirmasi' && $order['payment_method'] === 'QRIS' && $order['payment_status'] !== 'Dibayar') {
            return false;
        }
        return true;
    }

    public function cancelCustomerOrder(int $userId, string $invoice): void
    {
        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare(
                'SELECT id, user_id, status, payment_status, payment_method, shipping_method
                 FROM orders WHERE invoice_number=:invoice AND user_id=:user_id FOR UPDATE'
            );
            $find->execute(['invoice' => $invoice, 'user_id' => $userId]);
            $order = $find->fetch();
            if (!$order || !in_array($order['status'], ['Menunggu konfirmasi', 'Menunggu pembayaran'], true)) {
                throw new RuntimeException('Pesanan ini tidak dapat dibatalkan.');
            }
            if ($order['payment_status'] === 'Dibayar') {
                throw new RuntimeException('Pesanan sudah dibayar. Hubungi admin untuk proses pembatalan dan pengembalian dana.');
            }

            $this->releaseReservedStock($order, $invoice, $userId);
            $update = $this->db->prepare(
                'UPDATE orders SET status=:status, cancellation_reason=:reason WHERE id=:id'
            );
            $update->execute([
                'status' => 'Dibatalkan',
                'reason' => 'Dibatalkan oleh customer',
                'id' => (int) $order['id'],
            ]);
            if ($order['payment_status'] === 'Menunggu verifikasi') {
                $paymentUpdate = $this->db->prepare(
                    'UPDATE payments SET status=:status, admin_note=:note WHERE order_id=:order_id'
                );
                $paymentUpdate->execute([
                    'status' => 'Gagal / ditolak',
                    'note' => 'Dibatalkan oleh customer',
                    'order_id' => (int) $order['id'],
                ]);
                $paymentStatusUpdate = $this->db->prepare(
                    'UPDATE orders SET payment_status=:status WHERE id=:id'
                );
                $paymentStatusUpdate->execute([
                    'status' => 'Gagal / ditolak',
                    'id' => (int) $order['id'],
                ]);
            }
            $this->notifyAdmins(
                'Pesanan dibatalkan customer',
                'Pesanan ' . $invoice . ' dibatalkan oleh customer.',
                '/admin/orders/detail/' . rawurlencode($invoice),
                'order'
            );
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function releaseReservedStock(array $order, string $invoice, int $actorId): void
    {
        $items = $this->db->prepare(
            'SELECT oi.product_id, oi.variant_id, oi.quantity
             FROM order_items oi WHERE oi.order_id=:order_id'
        );
        $items->execute(['order_id' => (int) $order['id']]);
        $movement = $this->db->prepare(
            'INSERT INTO stock_movements
             (product_id, variant_id, user_id, movement_type, quantity_change,
              stock_before, stock_after, reason, reference_type, reference_id)
             VALUES
             (:product_id, :variant_id, :user_id, :movement_type, :quantity_change,
              :stock_before, :stock_after, :reason, :reference_type, :reference_id)'
        );
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id'] === null) {
                continue;
            }
            if ($item['variant_id'] !== null) {
                $lock = $this->db->prepare(
                    'SELECT stock FROM product_variants WHERE id=:id FOR UPDATE'
                );
                $lock->execute(['id' => (int) $item['variant_id']]);
                $before = $lock->fetchColumn();
                if ($before === false) {
                    continue;
                }
                $after = (int) $before + (int) $item['quantity'];
                $update = $this->db->prepare('UPDATE product_variants SET stock=:stock WHERE id=:id');
                $update->execute(['stock' => $after, 'id' => (int) $item['variant_id']]);
            } else {
                $lock = $this->db->prepare('SELECT stock FROM products WHERE id=:id FOR UPDATE');
                $lock->execute(['id' => (int) $item['product_id']]);
                $before = $lock->fetchColumn();
                if ($before === false) {
                    continue;
                }
                $after = (int) $before + (int) $item['quantity'];
                $update = $this->db->prepare('UPDATE products SET stock=:stock WHERE id=:id');
                $update->execute(['stock' => $after, 'id' => (int) $item['product_id']]);
            }
            $movement->execute([
                'product_id' => (int) $item['product_id'],
                'variant_id' => $item['variant_id'] !== null ? (int) $item['variant_id'] : null,
                'user_id' => $actorId,
                'movement_type' => 'release',
                'quantity_change' => (int) $item['quantity'],
                'stock_before' => (int) $before,
                'stock_after' => $after,
                'reason' => 'Pelepasan reservasi pesanan ' . $invoice,
                'reference_type' => 'order',
                'reference_id' => (int) $order['id'],
            ]);
        }
    }

    private function notifyUser(int $userId, string $title, string $message, string $link, string $type): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO notifications (user_id, title, message, link, type)
             VALUES (:user_id, :title, :message, :link, :type)'
        );
        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'type' => $type,
        ]);
    }

    private function notifyAdmins(string $title, string $message, string $link, string $type): void
    {
        $admins = $this->db->prepare('SELECT id FROM users WHERE role=:role AND status=:status');
        $admins->execute(['role' => 'admin', 'status' => 'active']);
        foreach ($admins->fetchAll() as $admin) {
            $this->notifyUser((int) $admin['id'], $title, $message, $link, $type);
        }
    }
}
