<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Keranjang Belanja</h1>
</div>
<?php if ($items === []): ?>
    <div class="alert alert-light">Keranjang masih kosong.</div>
<?php else: ?>
    <?php $subtotal = 0.0; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="list-group list-group-flush">
                    <?php foreach ($items as $item):
                        $price = (float) ($item['promo_price'] ?: $item['price']) + (float) ($item['additional_price'] ?: 0);
                        $lineSubtotal = $price * (int) $item['quantity'];
                        if ($item['selected']) $subtotal += $lineSubtotal;
                    ?>
                        <div class="list-group-item py-3">
                            <div class="row align-items-center g-3">
                                <div class="col-auto">
                                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/cart/select">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <input type="checkbox" name="selected" <?= $item['selected'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    </form>
                                </div>
                                <div class="col-2 d-none d-md-block">
                                    <img src="<?= e(asset_url($item['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>" class="rounded w-100" style="height:70px;object-fit:cover;" alt="<?= e($item['name']) ?>">
                                </div>
                                <div class="col">
                                    <strong><?= e($item['name']) ?></strong>
                                    <?php if ($item['variant_name']): ?><div class="small text-secondary"><?= e($item['variant_name']) ?></div><?php endif; ?>
                                    <div class="text-primary"><?= format_rupiah($price) ?></div>
                                </div>
                                <div class="col-md-3">
                                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/cart/quantity" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <input class="form-control" type="number" name="quantity" min="1" value="<?= (int) $item['quantity'] ?>">
                                        <button class="btn btn-outline-primary" type="submit">Ubah</button>
                                    </form>
                                </div>
                                <div class="col-auto">
                                    <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/cart/remove" onsubmit="return confirm('Hapus item ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-body">
                <div class="d-flex justify-content-between"><span>Subtotal terpilih</span><strong><?= format_rupiah($subtotal) ?></strong></div>
                <a class="btn btn-primary w-100 mt-3" href="<?= e(rtrim(APP_URL, '/')) ?>/checkout">Lanjut ke Checkout</a>
            </div>
        </div>
    </div>
<?php endif; ?>
