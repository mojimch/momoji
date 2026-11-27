<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Wishlist Saya</h1>
    <a class="btn btn-outline-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/products">Lanjut belanja</a>
</div>
<?php if ($products === []): ?>
    <div class="alert alert-info">Wishlist masih kosong.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <?php
            $basePrice = (float) ($product['promo_price'] ?: $product['price']);
            $hasAvailableChoice = (int) $product['stock'] > 0;
            foreach ($product['variants'] as $variant) {
                if ((int) $variant['stock'] > 0) {
                    $hasAvailableChoice = true;
                }
            }
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="card h-100">
                    <a href="<?= e(rtrim(APP_URL, '/')) ?>/products/detail/<?= e(rawurlencode($product['slug'])) ?>">
                        <img src="<?= e(asset_url($product['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>"
                             class="card-img-top" alt="<?= e($product['name']) ?>" style="height:220px;object-fit:cover;">
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5"><?= e($product['name']) ?></h2>
                        <p class="fw-semibold"><?= format_rupiah($basePrice) ?></p>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/wishlist/move" class="mt-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <?php if ($product['variants'] !== []): ?>
                                <label class="form-label" for="wishlist-variant-<?= (int) $product['id'] ?>">Varian</label>
                                <select class="form-select mb-2" name="variant_id" id="wishlist-variant-<?= (int) $product['id'] ?>">
                                    <?php if ((int) $product['stock'] > 0): ?>
                                        <option value="">Produk utama (stok <?= (int) $product['stock'] ?>)</option>
                                    <?php endif; ?>
                                    <?php foreach ($product['variants'] as $variant): ?>
                                        <option value="<?= (int) $variant['id'] ?>" <?= (int) $variant['stock'] < 1 ? 'disabled' : '' ?>>
                                            <?= e($variant['variant_name']) ?> · <?= format_rupiah($basePrice + (float) $variant['additional_price']) ?> · stok <?= (int) $variant['stock'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="variant_id" value="">
                                <p class="small text-secondary">Stok tersedia: <?= (int) $product['stock'] ?></p>
                            <?php endif; ?>
                            <button class="btn btn-primary w-100 mb-2" type="submit" <?= $hasAvailableChoice ? '' : 'disabled' ?>>
                                Pindahkan ke keranjang
                            </button>
                        </form>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/wishlist/remove">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <button class="btn btn-outline-danger w-100" type="submit">Hapus dari wishlist</button>
                        </form>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
