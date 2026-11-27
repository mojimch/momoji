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
                        <?php if ($order['status'] === 'Selesai' && $item['product_id'] !== null): ?>
                            <div class="border-top mt-3 pt-3 w-100">
                                <?php if ($item['review_status'] === 'pending'): ?>
                                    <span class="badge text-bg-warning">Ulasan menunggu moderasi</span>
                                <?php elseif ($item['review_status'] === 'approved'): ?>
                                    <span class="badge text-bg-success">Ulasan diterbitkan · <?= (int) $item['review_rating'] ?>/5</span>
                                <?php else: ?>
                                    <?php if ($item['review_status'] === 'rejected'): ?>
                                        <div class="alert alert-warning py-2">Ulasan sebelumnya ditolak. Silakan perbaiki dan kirim ulang.</div>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/reviews" class="row g-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="invoice" value="<?= e($order['invoice_number']) ?>">
                                        <input type="hidden" name="order_item_id" value="<?= (int) $item['id'] ?>">
                                        <div class="col-sm-3">
                                            <label class="form-label" for="rating-<?= (int) $item['id'] ?>">Rating</label>
                                            <select class="form-select" id="rating-<?= (int) $item['id'] ?>" name="rating" required>
                                                <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                                    <option value="<?= $rating ?>" <?= (int) ($item['review_rating'] ?? 5) === $rating ? 'selected' : '' ?>><?= $rating ?>/5</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-9">
                                            <label class="form-label" for="comment-<?= (int) $item['id'] ?>">Ulasan</label>
                                            <textarea class="form-control" id="comment-<?= (int) $item['id'] ?>" name="comment" rows="2" maxlength="5000"><?= e($item['review_status'] === 'rejected' ? $item['review_comment'] : '') ?></textarea>
                                        </div>
                                        <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Kirim ulasan</button></div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
