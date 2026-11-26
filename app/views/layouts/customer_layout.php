<?php
$auth = AuthMiddleware::check();
$flashSuccess = flash('success');
$flashError = flash('error');
$unreadCount = $auth ? (new NotificationModel())->unreadCount((int) $auth['id']) : 0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg customer-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand brand-mark" href="<?= e(rtrim(APP_URL, '/')) ?>/">MOMOJI<span>.</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerMenu" aria-controls="customerMenu" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="customerMenu">
                <form class="d-flex mx-lg-4 my-2 my-lg-0 flex-grow-1" action="<?= e(rtrim(APP_URL, '/')) ?>/products" method="get">
                    <input class="form-control" type="search" name="q" placeholder="Cari produk Momoji" aria-label="Cari produk">
                </form>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/products">Katalog</a></li>
                    <?php if ($auth && $auth['role'] === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/cart">Keranjang <span class="badge rounded-pill bg-danger cart-badge">0</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/notifications">Notifikasi <span class="badge rounded-pill bg-danger notification-badge"><?= $unreadCount ?></span></a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= e($auth['name']) ?></a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= e(rtrim(APP_URL, '/')) ?>/profile">Profil</a></li>
                                <li><a class="dropdown-item" href="<?= e(rtrim(APP_URL, '/')) ?>/orders">Pesanan Saya</a></li>
                                <li><a class="dropdown-item" href="<?= e(rtrim(APP_URL, '/')) ?>/notifications">Notifikasi (<?= $unreadCount ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="<?= e(rtrim(APP_URL, '/')) ?>/logout" method="post"><?= csrf_field() ?><button class="dropdown-item" type="submit">Logout</button></form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL, '/')) ?>/login">Login</a></li>
                        <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="<?= e(rtrim(APP_URL, '/')) ?>/register">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container flex-grow-1 py-4">
        <?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="alert alert-danger"><?= e($flashError) ?></div><?php endif; ?>
        <?= $content ?>
    </main>

    <footer class="footer-store mt-auto py-4">
        <div class="container d-flex flex-column flex-md-row justify-content-between">
            <div>
                <strong>Momoji</strong>
                <div class="small">Marketplace dengan nuansa alam dan laut.</div>
            </div>
            <div class="small mt-2 mt-md-0">Kontak dan alamat pickup dapat dikonfigurasi admin.</div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset_url('/assets/js/app.js')) ?>"></script>
</body>
</html>
