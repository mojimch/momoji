<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h1 class="h4 mb-0">Instruksi Pembayaran QRIS</h1></div>
            <div class="card-body">
                <p>Invoice: <strong><?= e($order['invoice_number']) ?></strong></p>
                <p>Total tagihan: <strong class="text-primary fs-4"><?= format_rupiah($order['total_amount']) ?></strong></p>
                <?php if (!empty($store['qris_image_path'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?= e(rtrim(APP_URL, '/')) ?>/store/qris" alt="QRIS resmi <?= e($store['store_name'] ?? 'Momoji') ?>" class="img-fluid border rounded p-2" style="max-height:360px;">
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">QRIS toko belum dikonfigurasi. Hubungi admin sebelum melakukan pembayaran.</div>
                <?php endif; ?>
                <div class="alert alert-info">
                    Unggah bukti setelah membayar. Screenshot bukan konfirmasi pembayaran otomatis; admin akan memeriksa transaksi melalui catatan merchant.
                </div>
                <?php if (in_array($order['status'], ['Dibatalkan', 'Selesai'], true)): ?>
                    <div class="alert alert-secondary">Pesanan ini sudah <?= e(strtolower($order['status'])) ?> dan tidak menerima pembayaran baru.</div>
                <?php elseif ($order['payment_status'] === 'Menunggu verifikasi'): ?>
                    <div class="alert alert-warning">Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi.</div>
                <?php elseif (in_array($order['payment_status'], ['Belum dibayar', 'Gagal / ditolak'], true)): ?>
                    <?php if ($order['payment_status'] === 'Gagal / ditolak' && $order['admin_note']): ?>
                        <div class="alert alert-danger">Catatan admin: <?= e($order['admin_note']) ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" action="<?= e(rtrim(APP_URL, '/')) ?>/orders/payment/<?= e(rawurlencode($order['invoice_number'])) ?>" class="mt-4">
                        <?= csrf_field() ?>
                        <label class="form-label" for="payment-proof">Bukti pembayaran (JPG, PNG, atau WebP, maks. 5 MB)</label>
                        <input class="form-control mb-3" id="payment-proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp" required>
                        <button class="btn btn-primary" type="submit">Kirim Bukti Pembayaran</button>
                    </form>
                <?php endif; ?>
                <a class="btn btn-link mt-3 px-0" href="<?= e(rtrim(APP_URL, '/')) ?>/orders/detail/<?= e(rawurlencode($order['invoice_number'])) ?>">Kembali ke detail pesanan</a>
            </div>
        </div>
    </div>
</div>
