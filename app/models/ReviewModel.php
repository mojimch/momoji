<?php

class ReviewModel extends Model
{
    public function approvedForProduct(int $productId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.rating, r.comment, r.created_at, u.name AS customer_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id AND r.status = :status
             ORDER BY r.created_at DESC'
        );
        $statement->execute(['product_id' => $productId, 'status' => 'approved']);
        return $statement->fetchAll();
    }

    public function summaryForProduct(int $productId): array
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating
             FROM reviews
             WHERE product_id = :product_id AND status = :status'
        );
        $statement->execute(['product_id' => $productId, 'status' => 'approved']);
        return $statement->fetch();
    }

    public function submit(
        int $userId,
        int $orderItemId,
        int $rating,
        string $comment
    ): void {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating harus antara 1 sampai 5.');
        }
        if (strlen($comment) > 5000) {
            throw new InvalidArgumentException('Ulasan maksimal 5.000 karakter.');
        }

        $orderItem = $this->db->prepare(
            'SELECT oi.product_id, o.id AS order_id, o.invoice_number
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.id = :item_id AND o.user_id = :user_id AND o.status = :status
               AND oi.product_id IS NOT NULL
             LIMIT 1'
        );
        $orderItem->execute([
            'item_id' => $orderItemId,
            'user_id' => $userId,
            'status' => 'Selesai',
        ]);
        $item = $orderItem->fetch();
        if (!$item) {
            throw new RuntimeException('Ulasan hanya dapat diberikan untuk item pesanan selesai milik Anda.');
        }

        $existing = $this->db->prepare(
            'SELECT id, status FROM reviews
             WHERE order_item_id = :order_item_id AND user_id = :user_id
             LIMIT 1'
        );
        $existing->execute(['order_item_id' => $orderItemId, 'user_id' => $userId]);
        $review = $existing->fetch();
        if ($review && $review['status'] !== 'rejected') {
            throw new RuntimeException('Ulasan untuk produk ini sudah dikirim.');
        }

        if ($review) {
            $save = $this->db->prepare(
                'UPDATE reviews
                 SET rating = :rating, comment = :comment, status = :status
                 WHERE id = :id'
            );
            $save->execute([
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'status' => 'pending',
                'id' => (int) $review['id'],
            ]);
        } else {
            $save = $this->db->prepare(
                'INSERT INTO reviews (user_id, product_id, order_item_id, rating, comment)
                 VALUES (:user_id, :product_id, :order_item_id, :rating, :comment)'
            );
            $save->execute([
                'user_id' => $userId,
                'product_id' => (int) $item['product_id'],
                'order_item_id' => $orderItemId,
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
            ]);
        }

        $this->notifyAdmins(
            'Ulasan produk baru',
            'Ulasan untuk pesanan ' . $item['invoice_number'] . ' menunggu moderasi.',
            '/admin/reviews?status=pending'
        );
    }

    public function adminReviews(string $status = ''): array
    {
        $where = '';
        $params = [];
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where = 'WHERE r.status = :status';
            $params['status'] = $status;
        }
        $statement = $this->db->prepare(
            'SELECT r.id, r.rating, r.comment, r.status, r.created_at,
                    u.id AS user_id, u.name AS customer_name,
                    p.name AS product_name, p.slug, o.invoice_number
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             JOIN products p ON p.id = r.product_id
             JOIN order_items oi ON oi.id = r.order_item_id
             JOIN orders o ON o.id = oi.order_id
             ' . $where . '
             ORDER BY FIELD(r.status, "pending", "approved", "rejected"), r.created_at DESC'
        );
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function setStatus(int $reviewId, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Status moderasi tidak valid.');
        }
        $this->db->beginTransaction();
        try {
            $find = $this->db->prepare(
                'SELECT r.user_id, p.slug FROM reviews r
                 JOIN products p ON p.id = r.product_id
                 WHERE r.id = :id FOR UPDATE'
            );
            $find->execute(['id' => $reviewId]);
            $review = $find->fetch();
            if (!$review) {
                $this->db->commit();
                return false;
            }
            $update = $this->db->prepare('UPDATE reviews SET status = :status WHERE id = :id');
            $update->execute(['status' => $status, 'id' => $reviewId]);
            $notify = $this->db->prepare(
                'INSERT INTO notifications (user_id, title, message, link, type)
                 VALUES (:user_id, :title, :message, :link, :type)'
            );
            $notify->execute([
                'user_id' => (int) $review['user_id'],
                'title' => 'Status ulasan diperbarui',
                'message' => $status === 'approved'
                    ? 'Ulasan Anda telah disetujui dan tampil pada produk.'
                    : 'Ulasan Anda belum disetujui. Anda dapat memperbaiki dan mengirim ulang ulasan.',
                'link' => '/products/detail/' . rawurlencode($review['slug']),
                'type' => 'review',
            ]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function notifyAdmins(string $title, string $message, string $link): void
    {
        $admins = $this->db->prepare(
            'SELECT id FROM users WHERE role = :role AND status = :status'
        );
        $admins->execute(['role' => 'admin', 'status' => 'active']);
        $insert = $this->db->prepare(
            'INSERT INTO notifications (user_id, title, message, link, type)
             VALUES (:user_id, :title, :message, :link, :type)'
        );
        foreach ($admins->fetchAll() as $admin) {
            $insert->execute([
                'user_id' => (int) $admin['id'],
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'type' => 'review',
            ]);
        }
    }
}
