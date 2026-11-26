<?php

class NotificationModel extends Model
{
    public function unreadCount(int $userId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function forUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, title, message, link, type, is_read, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT 100'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function markRead(int $userId, int $notificationId): ?string
    {
        $statement = $this->db->prepare(
            'SELECT link FROM notifications
             WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute(['id' => $notificationId, 'user_id' => $userId]);
        $link = $statement->fetchColumn();
        if ($link === false) {
            return null;
        }
        $update = $this->db->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $update->execute(['id' => $notificationId, 'user_id' => $userId]);
        return is_string($link) ? $link : null;
    }
}
