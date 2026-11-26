<?php

class NotificationController extends Controller
{
    private NotificationModel $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationModel();
    }

    public function index(): void
    {
        $auth = AuthMiddleware::check();
        if (!$auth) {
            flash('error', 'Silakan login untuk melihat notifikasi.');
            $this->redirect('/login');
        }
        $this->view('shared/notifications/index', [
            'notifications' => $this->notifications->forUser((int) $auth['id']),
            'auth' => $auth,
        ], $auth['role'] === 'admin' ? 'admin_layout' : 'customer_layout');
    }

    public function open(int $id): void
    {
        $auth = AuthMiddleware::check();
        if (!$auth) {
            flash('error', 'Silakan login untuk membuka notifikasi.');
            $this->redirect('/login');
        }
        $link = $this->notifications->markRead((int) $auth['id'], $id);
        if ($link === null || !str_starts_with($link, '/') || str_starts_with($link, '//')) {
            $this->redirect('/notifications');
        }
        $this->redirect($link);
    }
}
