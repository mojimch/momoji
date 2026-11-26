<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h1 class="h4 mb-0">Pesanan berhasil dibuat</h1>
            </div>
            <div class="card-body">
                <p>Nomor invoice: <strong><?= e($order['invoice_number']) ?></strong></p>
                <p>Status pesanan: <strong><?= e($order['status']) ?></strong></p>
                <p>Status pembayaran: <strong><?= e($order['payment_status']) ?></strong> (<?= e($order['payment_method']) ?>)</p>
                <div class="list-group mb-3">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="list-group-item d-flex justify-content-between">
                            <span><?= e($item['product_name']) ?><?= $item['variant_name'] ? ' - ' . e($item['variant_name']) : '' ?> × <?= (int) $item['quantity'] ?></span>
                            <strong><?= format_rupiah($item['subtotal']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= format_rupiah($order['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Ongkir</span><span><?= format_rupiah($order['shipping_cost']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Diskon</span><span><?= format_rupiah($order['discount_amount']) ?></span></div>
                <hr>
                <div class="d-flex justify-content-between h5"><span>Total</span><strong><?= format_rupiah($order['total_amount']) ?></strong></div>
                <?php if ($order['payment_method'] === 'QRIS'): ?>
                    <div class="alert alert-info mt-3 mb-0">Instruksi QRIS dan unggah bukti pembayaran akan tersedia pada Tahap 5.</div>
                <?php endif; ?>
                <a class="btn btn-primary mt-3" href="<?= e(rtrim(APP_URL, '/')) ?>/products">Kembali ke katalog</a>
            </div>
        </div>
    </div>
</div>
