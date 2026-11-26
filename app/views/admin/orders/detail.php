<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pesanan <?= e($order['invoice_number']) ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders">Kembali</a>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4"><div class="card-header">Rincian Customer & Status</div><div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><span class="text-secondary">Customer</span><div><?= e($order['customer_name'] ?: 'Customer dihapus') ?> <small><?= e($order['customer_email'] ?: '') ?></small></div></div>
                <div class="col-md-6"><span class="text-secondary">Penerima</span><div><?= e($order['recipient_name']) ?> / <?= e($order['recipient_phone']) ?></div></div>
                <div class="col-md-6"><span class="text-secondary">Status pesanan</span><div class="fw-semibold"><?= e($order['status']) ?></div></div>
                <div class="col-md-6"><span class="text-secondary">Status pembayaran</span><div class="fw-semibold"><?= e($order['payment_status']) ?> (<?= e($order['payment_method']) ?>)</div></div>
                <div class="col-12"><span class="text-secondary">Alamat</span><div><?= nl2br(e($order['shipping_address'])) ?></div></div>
                <?php if ($order['notes']): ?><div class="col-12"><span class="text-secondary">Catatan customer</span><div><?= nl2br(e($order['notes'])) ?></div></div><?php endif; ?>
                <?php if ($order['cancellation_reason']): ?><div class="col-12"><span class="text-secondary">Alasan pembatalan</span><div><?= e($order['cancellation_reason']) ?></div></div><?php endif; ?>
            </div>
        </div></div>
        <div class="card"><div class="card-header">Item Pesanan</div><div class="list-group list-group-flush">
            <?php foreach ($order['items'] as $item): ?>
                <div class="list-group-item d-flex justify-content-between">
                    <span><?= e($item['product_name']) ?><?= $item['variant_name'] ? ' / ' . e($item['variant_name']) : '' ?> × <?= (int) $item['quantity'] ?></span>
                    <strong><?= format_rupiah($item['subtotal']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div><div class="card-body">
            <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= format_rupiah($order['subtotal']) ?></span></div>
            <div class="d-flex justify-content-between"><span>Ongkir</span><span><?= format_rupiah($order['shipping_cost']) ?></span></div>
            <div class="d-flex justify-content-between"><span>Diskon</span><span><?= format_rupiah($order['discount_amount']) ?></span></div>
            <hr><div class="d-flex justify-content-between h5"><span>Total</span><strong><?= format_rupiah($order['total_amount']) ?></strong></div>
        </div></div>
    </div>
    <aside class="col-lg-5">
        <?php if ($order['payment_method'] === 'QRIS' && $order['payment_status'] === 'Menunggu verifikasi'): ?>
            <div class="card mb-4"><div class="card-header">Verifikasi Bukti QRIS</div><div class="card-body">
                <?php foreach ($order['proofs'] as $proof): ?>
                    <a href="<?= e(rtrim(APP_URL, '/')) ?>/payment-proofs/<?= (int) $proof['id'] ?>" target="_blank" rel="noopener">
                        <img class="img-fluid rounded border mb-2" src="<?= e(rtrim(APP_URL, '/')) ?>/payment-proofs/<?= (int) $proof['id'] ?>" alt="Bukti pembayaran <?= e($order['invoice_number']) ?>">
                    </a>
                    <div class="small text-secondary mb-3">Diunggah <?= e($proof['uploaded_at']) ?> · <?= e($proof['original_name'] ?: 'Bukti pembayaran') ?></div>
                <?php endforeach; ?>
                <p class="small text-secondary">Pastikan pembayaran cocok dengan catatan transaksi merchant sebelum menyetujui.</p>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/payment/verify" class="mb-3">
                    <?= csrf_field() ?><input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>"><input type="hidden" name="decision" value="Dibayar">
                    <button class="btn btn-success w-100" type="submit">Setujui Pembayaran</button>
                </form>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/payment/verify">
                    <?= csrf_field() ?><input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>"><input type="hidden" name="decision" value="Gagal / ditolak">
                    <label class="form-label" for="payment-rejection-note">Catatan penolakan wajib</label>
                    <textarea class="form-control mb-2" id="payment-rejection-note" name="admin_note" rows="2" required></textarea>
                    <button class="btn btn-outline-danger w-100" type="submit">Tolak Pembayaran</button>
                </form>
            </div></div>
        <?php elseif ($order['admin_note']): ?>
            <div class="alert alert-warning"><?= e($order['admin_note']) ?></div>
        <?php endif; ?>

        <?php if ($order['payment_method'] === 'COD' && $order['payment_status'] !== 'Dibayar' && $order['status'] !== 'Dibatalkan'): ?>
            <div class="card card-body mb-4">
                <h2 class="h6">Pembayaran COD</h2>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/payment/cod-paid">
                    <?= csrf_field() ?><input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>">
                    <button class="btn btn-outline-success w-100" type="submit">Tandai Pembayaran Diterima</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!in_array($order['status'], ['Selesai', 'Dibatalkan'], true)): ?>
            <div class="card card-body">
                <h2 class="h6">Perbarui status pesanan</h2>
                <?php if ($order['payment_status'] === 'Dibayar'): ?>
                    <div class="alert alert-warning small">Pembayaran sudah diterima. Jika pesanan dibatalkan, pengembalian dana harus diproses sesuai kebijakan toko.</div>
                <?php endif; ?>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/status">
                    <?= csrf_field() ?><input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>">
                    <label class="form-label" for="next-status">Status baru</label>
                    <select class="form-select mb-3" id="next-status" name="status" required>
                        <option value="">Pilih status</option>
                        <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?>
                    </select>
                    <div class="mb-3"><label class="form-label" for="cancellation-reason">Alasan (wajib saat membatalkan)</label><textarea class="form-control" id="cancellation-reason" name="reason" rows="2"></textarea></div>
                    <button class="btn btn-primary w-100" type="submit">Simpan Status</button>
                </form>
            </div>
        <?php endif; ?>
    </aside>
</div>
