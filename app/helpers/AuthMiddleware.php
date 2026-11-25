<?php

final class AuthMiddleware
{
    public static function requireCustomer(): void
    {
        if (empty($_SESSION['auth']['id'])) {
            flash('error', 'Silakan login untuk melanjutkan.');
            redirect('/login');
        }

        if (($_SESSION['auth']['role'] ?? '') !== 'customer') {
            http_response_code(403);
            echo '<h1>403 - Akses ditolak</h1>';
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (empty($_SESSION['auth']['id'])) {
            flash('error', 'Silakan login sebagai admin.');
            redirect('/admin/login');
        }

        if (($_SESSION['auth']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<h1>403 - Akses ditolak</h1>';
            exit;
        }
    }

    public static function check(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }
}
