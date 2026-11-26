<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manajemen Pesanan</h1>
</div>
<div class="card card-body mb-4">
    <h2 class="h5">QRIS Resmi Toko</h2>
    <?php if (!empty($store['qris_image_path'])): ?>
        <img src="<?= e(rtrim(APP_URL, '/')) ?>/store/qris" alt="QRIS resmi toko" class="img-thumbnail mb-3" style="max-width:220px;">
    <?php else: ?>
        <p class="text-secondary">QRIS belum diunggah. Customer tidak dapat melihat instruksi pembayaran QRIS sebelum gambar resmi disetel.</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/qris" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-8"><label class="form-label" for="store-qris-image">Ganti gambar QRIS (JPG, PNG, WebP; maks. 5 MB)</label><input class="form-control" id="store-qris-image" type="file" name="qris_image" accept="image/jpeg,image/png,image/webp" required></div>
        <div class="col-md-4"><button class="btn btn-primary" type="submit">Simpan QRIS</button></div>
    </form>
</div>
<form class="card card-body mb-4" method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="order-status-filter">Status pesanan</label>
            <select class="form-select" id="order-status-filter" name="status"><option value="">Semua status</option><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="payment-status-filter">Status pembayaran</label>
            <select class="form-select" id="payment-status-filter" name="payment_status"><option value="">Semua pembayaran</option><?php foreach ($paymentStatuses as $status): ?><option value="<?= e($status) ?>" <?= $filters['payment_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filter</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Item</th><th>Total</th><th>Status Pesanan</th><th>Pembayaran</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong><?= e($order['invoice_number']) ?></strong><div class="small text-secondary"><?= e($order['created_at']) ?></div></td>
                <td><?= e($order['customer_name'] ?: 'Customer dihapus') ?></td>
                <td><?= (int) $order['item_count'] ?></td>
                <td><?= format_rupiah($order['total_amount']) ?></td>
                <td><?= e($order['status']) ?></td>
                <td><?= e($order['payment_status']) ?><div class="small text-secondary"><?= e($order['payment_method']) ?></div></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/detail/<?= e(rawurlencode($order['invoice_number'])) ?>">Kelola</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($orders === []): ?><tr><td colspan="7" class="text-center text-secondary py-4">Belum ada pesanan.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
