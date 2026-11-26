<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Detail Pesanan</h1>
    <a class="btn btn-outline-secondary" href="<?= e(rtrim(APP_URL, '/')) ?>/orders">Kembali</a>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <strong><?= e($order['invoice_number']) ?></strong>
                <span><?= e($order['created_at']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><span class="text-secondary">Status pesanan</span><div class="fw-semibold"><?= e($order['status']) ?></div></div>
                    <div class="col-md-6"><span class="text-secondary">Status pembayaran</span><div class="fw-semibold"><?= e($order['payment_status']) ?></div></div>
                    <div class="col-md-6"><span class="text-secondary">Metode pembayaran</span><div><?= e($order['payment_method']) ?></div></div>
                    <div class="col-md-6"><span class="text-secondary">Metode pengiriman</span><div><?= $order['shipping_method'] === 'pickup' ? 'Pickup di toko' : 'Kurir Momoji' ?></div></div>
                    <div class="col-12"><span class="text-secondary">Alamat</span><div><?= nl2br(e($order['shipping_address'])) ?></div></div>
                    <?php if ($order['admin_note']): ?><div class="col-12"><span class="text-secondary">Catatan admin</span><div class="alert alert-warning mb-0"><?= e($order['admin_note']) ?></div></div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Item Pesanan</div>
            <div class="list-group list-group-flush">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <?php if ($item['variant_name']): ?><div class="small text-secondary"><?= e($item['variant_name']) ?></div><?php endif; ?>
                            <div class="small text-secondary"><?= format_rupiah($item['unit_price']) ?> × <?= (int) $item['quantity'] ?></div>
                        </div>
                        <strong><?= format_rupiah($item['subtotal']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= format_rupiah($order['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Ongkir</span><span><?= format_rupiah($order['shipping_cost']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Diskon</span><span><?= format_rupiah($order['discount_amount']) ?></span></div>
                <hr><div class="d-flex justify-content-between h5"><span>Total</span><strong><?= format_rupiah($order['total_amount']) ?></strong></div>
            </div>
        </div>
    </div>
    <aside class="col-lg-4">
        <?php if ($order['payment_method'] === 'QRIS' && in_array($order['payment_status'], ['Belum dibayar', 'Gagal / ditolak'], true)): ?>
            <a class="btn btn-primary w-100 mb-3" href="<?= e(rtrim(APP_URL, '/')) ?>/orders/payment/<?= e(rawurlencode($order['invoice_number'])) ?>">Instruksi Pembayaran QRIS</a>
        <?php endif; ?>
        <?php if (
            in_array($order['status'], ['Menunggu konfirmasi', 'Menunggu pembayaran'], true)
            && $order['payment_status'] !== 'Dibayar'
        ): ?>
            <div class="card card-body border-danger">
                <h2 class="h6">Batalkan pesanan</h2>
                <p class="small text-secondary">Stok pesanan akan dikembalikan.</p>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/orders/cancel" onsubmit="return confirm('Batalkan pesanan ini?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>">
                    <button class="btn btn-outline-danger w-100" type="submit">Batalkan Pesanan</button>
                </form>
            </div>
        <?php elseif (in_array($order['status'], ['Menunggu konfirmasi', 'Menunggu pembayaran'], true) && $order['payment_status'] === 'Dibayar'): ?>
            <div class="alert alert-warning">Pesanan sudah dibayar. Hubungi admin untuk pembatalan dan proses pengembalian dana.</div>
        <?php endif; ?>
    </aside>
</div>
