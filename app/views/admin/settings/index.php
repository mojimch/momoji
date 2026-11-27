<div class="mb-4">
    <h1 class="h3 mb-1">Pengaturan Toko</h1>
    <p class="text-secondary mb-0">Informasi ini digunakan pada kontak, pickup, instruksi QRIS, dan pemantauan stok.</p>
</div>
<div class="card"><div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/settings">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="store-name">Nama toko</label>
                <input class="form-control" id="store-name" name="store_name" maxlength="150" value="<?= e($settings['store_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="store-phone">Nomor kontak</label>
                <input class="form-control" id="store-phone" name="phone" maxlength="30" value="<?= e($settings['phone']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="store-email">Email toko</label>
                <input class="form-control" id="store-email" name="email" type="email" maxlength="150" value="<?= e($settings['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="stock-threshold">Batas peringatan stok menipis</label>
                <input class="form-control" id="stock-threshold" name="low_stock_threshold" type="number" min="0" max="100000" value="<?= (int) $settings['low_stock_threshold'] ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="pickup-address">Alamat pickup</label>
                <textarea class="form-control" id="pickup-address" name="pickup_address" rows="3" maxlength="10000"><?= e($settings['pickup_address']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="store-qris">Gambar QRIS resmi (JPG, PNG, WebP; maks. 5 MB)</label>
                <input class="form-control" id="store-qris" name="qris_image" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($settings['qris_image_path'])): ?>
                    <div class="mt-3"><img src="<?= e(rtrim(APP_URL, '/')) ?>/store/qris" alt="QRIS resmi toko" class="img-thumbnail" style="max-width:220px;"></div>
                <?php else: ?>
                    <div class="form-text">Belum ada gambar QRIS. File gambar baru hanya akan tersedia untuk customer yang login.</div>
                <?php endif; ?>
            </div>
        </div>
        <button class="btn btn-primary mt-4" type="submit">Simpan Pengaturan</button>
    </form>
</div></div>
