<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manajemen Stok</h1>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-body">
            <h2 class="h5">Penyesuaian stok</h2>
            <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/stocks/adjust">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="stock-product">Produk</label><select class="form-select" id="stock-product" name="product_id" required><option value="">Pilih produk</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> (<?= (int) $product['stock'] ?>)</option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label" for="stock-variant">Varian (opsional)</label><input class="form-control" id="stock-variant" type="number" name="variant_id" min="0" placeholder="Kosongkan untuk stok produk"></div>
                <div class="mb-3"><label class="form-label" for="stock-change">Perubahan (+ tambah / - kurangi)</label><input class="form-control" id="stock-change" type="number" name="quantity_change" required></div>
                <div class="mb-3"><label class="form-label" for="stock-reason">Alasan wajib</label><textarea class="form-control" id="stock-reason" name="reason" rows="3" required></textarea></div>
                <button class="btn btn-primary" type="submit">Simpan Penyesuaian</button>
            </form>
        </div>
        <div class="card mt-4"><div class="card-header">Ringkasan stok</div><div class="list-group list-group-flush">
            <?php foreach ($products as $product): ?>
                <div class="list-group-item d-flex justify-content-between"><span><?= e($product['name']) ?></span><span class="badge <?= (int) $product['stock'] <= 5 ? 'text-bg-danger' : 'text-bg-success' ?>"><?= (int) $product['stock'] ?></span></div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header">Riwayat perubahan stok</div><div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Waktu</th><th>Produk</th><th>Perubahan</th><th>Stok</th><th>Alasan</th><th>User</th></tr></thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= e($movement['created_at']) ?></td>
                            <td><?= e($movement['product_name'] ?: '-') ?><?= $movement['variant_name'] ? ' / ' . e($movement['variant_name']) : '' ?></td>
                            <td><?= (int) $movement['quantity_change'] >= 0 ? '+' : '' ?><?= (int) $movement['quantity_change'] ?></td>
                            <td><?= (int) $movement['stock_before'] ?> → <?= (int) $movement['stock_after'] ?></td>
                            <td><?= e($movement['reason']) ?></td>
                            <td><?= e($movement['user_name'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>
