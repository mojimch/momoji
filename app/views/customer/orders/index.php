<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Riwayat Pesanan</h1>
</div>
<?php if ($orders === []): ?>
    <div class="alert alert-light">Belum ada pesanan.</div>
<?php else: ?>
    <div class="card"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Invoice</th><th>Tanggal</th><th>Total</th><th>Status Pesanan</th><th>Status Pembayaran</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?= e($order['invoice_number']) ?></strong></td>
                    <td><?= e($order['created_at']) ?></td>
                    <td><?= format_rupiah($order['total_amount']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e($order['status']) ?></span></td>
                    <td><span class="badge <?= $order['payment_status'] === 'Dibayar' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($order['payment_status']) ?></span></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/orders/detail/<?= e(rawurlencode($order['invoice_number'])) ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
<?php endif; ?>
