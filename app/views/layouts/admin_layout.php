<?php
$auth = AuthMiddleware::check();
$flashSuccess = flash('success');
$flashError = flash('error');
$isAuthenticatedAdmin = $auth && ($auth['role'] ?? '') === 'admin';
$unreadCount = $auth ? (new NotificationModel())->unreadCount((int) $auth['id']) : 0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Momoji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<?php if ($isAuthenticatedAdmin): ?>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="p-3 border-bottom border-light border-opacity-25">
                <a class="navbar-brand brand-mark text-white" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/dashboard">MOMOJI ADMIN</a>
            </div>
            <nav class="nav nav-pills flex-column py-3">
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/dashboard">Dashboard</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/products">Produk</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/categories">Kategori</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/stocks">Stok</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/settings/zones">Zona Pengiriman</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/vouchers">Voucher</a>
                <a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders">Pesanan & Verifikasi Pembayaran</a>
                <span class="nav-link disabled opacity-50">Pesanan <small>(Tahap 5)</small></span>
                <span class="nav-link disabled opacity-50">Verifikasi Pembayaran <small>(Tahap 5)</small></span>
                <span class="nav-link disabled opacity-50">Pelanggan <small>(Tahap 6)</small></span>
                <span class="nav-link disabled opacity-50">Ulasan <small>(Tahap 7)</small></span>
                <span class="nav-link disabled opacity-50">Laporan <small>(Tahap 6)</small></span>
                <span class="nav-link disabled opacity-50">Pengaturan Toko <small>(Tahap 6)</small></span>
                <span class="nav-link disabled opacity-50">Backup DB <small>(Tahap 7)</small></span>
            </nav>
        </aside>
        <div class="admin-main">
            <header class="admin-topbar d-flex align-items-center justify-content-between px-3 px-lg-4">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-sidebar aria-label="Menu">Menu</button>
                <span class="fw-semibold">Panel Administrasi</span>
                <div class="d-flex align-items-center gap-3">
                    <a class="text-decoration-none" href="<?= e(rtrim(APP_URL, '/')) ?>/notifications">Notifikasi <span class="badge bg-danger"><?= $unreadCount ?></span></a>
                    <form action="<?= e(rtrim(APP_URL, '/')) ?>/logout" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Logout</button></form>
                </div>
            </header>
            <main class="admin-content">
                <?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
                <?php if ($flashError): ?><div class="alert alert-danger"><?= e($flashError) ?></div><?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
<?php else: ?>
    <main class="container">
        <?php if ($flashSuccess): ?><div class="alert alert-success mt-3"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="alert alert-danger mt-3"><?= e($flashError) ?></div><?php endif; ?>
        <?= $content ?>
    </main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
