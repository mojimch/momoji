<?php $editing = $product !== null; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $editing ? 'Edit Produk' : 'Tambah Produk' ?></h1>
    <a class="btn btn-outline-secondary" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/products">Kembali</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products<?= $editing ? '/edit/' . (int) $product['id'] : '/create' ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-body">
                <div class="mb-3"><label class="form-label" for="product-name">Nama produk</label><input class="form-control" id="product-name" name="name" value="<?= e($editing ? $product['name'] : '') ?>" required maxlength="180"></div>
                <div class="mb-3"><label class="form-label" for="product-description">Deskripsi</label><textarea class="form-control" id="product-description" name="description" rows="7"><?= e($editing ? $product['description'] : '') ?></textarea></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="product-price">Harga</label><input class="form-control" id="product-price" type="number" name="price" min="0" step="100" value="<?= e($editing ? $product['price'] : '') ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="product-promo-price">Harga promo</label><input class="form-control" id="product-promo-price" type="number" name="promo_price" min="0" step="100" value="<?= e($editing ? ($product['promo_price'] ?? '') : '') ?>"></div>
                    <div class="col-md-4"><label class="form-label" for="product-stock">Stok</label><input class="form-control" id="product-stock" type="number" name="stock" min="0" value="<?= e($editing ? $product['stock'] : 0) ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="product-weight">Berat (gram)</label><input class="form-control" id="product-weight" type="number" name="weight_grams" min="0" value="<?= e($editing ? $product['weight_grams'] : 0) ?>" required></div>
                    <div class="col-md-4">
                        <label class="form-label" for="product-label">Label</label>
                        <select class="form-select" id="product-label" name="label">
                            <?php foreach (['none','new','promo','bestseller'] as $label): ?>
                                <option value="<?= e($label) ?>" <?= $editing && $product['label'] === $label ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label" for="product-category">Kategori</label><select class="form-select" id="product-category" name="category_id"><option value="0">Tanpa kategori</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $editing && (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="form-check mt-3"><input class="form-check-input" id="product-active" type="checkbox" name="is_active" <?= !$editing || $product['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="product-active">Produk aktif</label></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-body">
                <label class="form-label" for="product-images">Gambar produk</label>
                <input class="form-control" id="product-images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                <div class="small text-secondary mt-2">Maksimal 5 MB per gambar.</div>
            </div>
        </div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Produk' ?></button>
</form>

<?php if ($editing): ?>
    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card"><div class="card-header">Gambar</div><div class="card-body">
                <div class="row g-2">
                    <?php foreach ($images as $image): ?>
                        <div class="col-6 col-md-4">
                            <img class="w-100 rounded" src="<?= e(asset_url($image['image_path'])) ?>" alt="Gambar produk" style="height:120px;object-fit:cover;">
                            <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/<?= (int) $product['id'] ?>/images/<?= (int) $image['id'] ?>/delete" class="mt-2">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100" type="submit">Hapus</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-header">Varian</div><div class="card-body">
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/<?= (int) $product['id'] ?>/variants" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <div class="col-md-5"><input class="form-control" name="variant_name" placeholder="Nama varian" required></div>
                    <div class="col-md-3"><input class="form-control" type="number" name="additional_price" min="0" step="100" value="0" required></div>
                    <div class="col-md-2"><input class="form-control" type="number" name="stock" min="0" value="0" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Tambah</button></div>
                </form>
                <?php foreach ($variants as $variant): ?>
                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/<?= (int) $product['id'] ?>/variants/<?= (int) $variant['id'] ?>/update" class="row g-2 mb-2">
                        <?= csrf_field() ?>
                        <div class="col-md-5"><input class="form-control" name="variant_name" value="<?= e($variant['variant_name']) ?>" required></div>
                        <div class="col-md-3"><input class="form-control" type="number" name="additional_price" min="0" step="100" value="<?= e($variant['additional_price']) ?>" required></div>
                        <div class="col-md-2"><input class="form-control" type="number" name="stock" min="0" value="<?= (int) $variant['stock'] ?>" required></div>
                        <div class="col-md-2 d-flex gap-1">
                            <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                    </form>
                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/<?= (int) $product['id'] ?>/variants/<?= (int) $variant['id'] ?>/delete">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                    </form>
                        </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </div>
<?php endif; ?>
