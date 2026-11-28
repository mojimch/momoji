<?php

final class AuthMiddleware
{
    public static function requireCustomer(): void
    {
        if (empty($_SESSION['auth']['id'])) {
            flash('error', 'Silakan login untuk melanjutkan.');
            redirect('/login');
        }

        $statement = Database::connection()->prepare(
            'SELECT name, email, role, status FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => (int) $_SESSION['auth']['id']]);
        $account = $statement->fetch();
        if (!$account || $account['role'] !== 'customer' || $account['status'] !== 'active') {
            unset($_SESSION['auth']);
            http_response_code(403);
            echo '<h1>403 - Akses ditolak</h1>';
            exit;
        }

        $_SESSION['auth']['name'] = $account['name'];
        $_SESSION['auth']['email'] = $account['email'];
        $_SESSION['auth']['role'] = $account['role'];
    }

    public static function requireAdmin(): void
    {
        if (empty($_SESSION['auth']['id'])) {
            flash('error', 'Silakan login sebagai admin.');
            redirect('/admin/login');
        }

        $statement = Database::connection()->prepare(
            'SELECT name, email, role, status FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => (int) $_SESSION['auth']['id']]);
        $account = $statement->fetch();
        if (!$account || $account['role'] !== 'admin' || $account['status'] !== 'active') {
            unset($_SESSION['auth']);
            http_response_code(403);
            echo '<h1>403 - Akses ditolak</h1>';
            exit;
        }

        $_SESSION['auth']['name'] = $account['name'];
        $_SESSION['auth']['email'] = $account['email'];
        $_SESSION['auth']['role'] = $account['role'];
    }

    public static function check(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }
}
