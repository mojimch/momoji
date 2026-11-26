<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Notifikasi</h1>
</div>
<?php if ($notifications === []): ?>
    <div class="alert alert-light">Belum ada notifikasi.</div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $notification): ?>
            <a class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'list-group-item-success' ?>"
               href="<?= e(rtrim(APP_URL, '/')) ?>/notifications/open/<?= (int) $notification['id'] ?>">
                <div class="d-flex justify-content-between">
                    <strong><?= e($notification['title']) ?><?= $notification['is_read'] ? '' : ' · Baru' ?></strong>
                    <small><?= e($notification['created_at']) ?></small>
                </div>
                <div><?= e($notification['message']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
