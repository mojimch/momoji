<div class="row g-4">
    <div class="col-lg-6">
        <div id="productGallery" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner rounded-4 border">
                <?php foreach ($images ?: [['image_path' => '/assets/images/placeholder-product.svg']] as $index => $image): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= e(asset_url($image['image_path'])) ?>" class="d-block w-100" alt="<?= e($product['name']) ?>" style="height:460px;object-fit:cover;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <span class="badge text-bg-light"><?= e($product['category_name'] ?: 'Tanpa kategori') ?></span>
        <?php if ($product['label'] !== 'none'): ?><span class="badge bg-warning text-dark"><?= e(strtoupper($product['label'])) ?></span><?php endif; ?>
        <h1 class="h2 mt-2"><?= e($product['name']) ?></h1>
        <?php if ($product['promo_price'] !== null): ?>
            <div class="text-decoration-line-through text-secondary"><?= format_rupiah($product['price']) ?></div>
        <?php endif; ?>
        <strong class="d-block h3 text-primary mb-3" id="product-price"><?= format_rupiah($product['promo_price'] ?: $product['price']) ?></strong>
        <p><?= nl2br(e($product['description'] ?: '')) ?></p>
        <p class="small text-secondary">Berat: <?= (int) $product['weight_grams'] ?> gram | Stok: <span id="product-stock"><?= (int) $product['stock'] ?></span></p>

        <?php if ($variants): ?>
            <div class="mb-3">
                <label class="form-label" for="variant-select">Pilih varian</label>
                <select class="form-select" id="variant-select">
                    <option value="" data-price="<?= e((float) ($product['promo_price'] ?: $product['price'])) ?>" data-stock="<?= (int) $product['stock'] ?>">Tanpa varian</option>
                    <?php foreach ($variants as $variant): ?>
                        <?php $variantPrice = (float) ($product['promo_price'] ?: $product['price']) + (float) $variant['additional_price']; ?>
                        <option value="<?= (int) $variant['id'] ?>" data-price="<?= e($variantPrice) ?>" data-stock="<?= (int) $variant['stock'] ?>">
                            <?= e($variant['variant_name']) ?> (+<?= format_rupiah($variant['additional_price']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <form action="<?= e(rtrim(APP_URL, '/')) ?>/cart/add" method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="variant_id" id="cart-variant-id" value="">
            <input type="hidden" name="redirect" value="/products/detail/<?= e($product['slug']) ?>">
            <input class="form-control" type="number" name="quantity" min="1" value="1" style="max-width:100px;">
            <button class="btn btn-primary flex-grow-1" type="submit">Tambah ke Keranjang</button>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const select = document.querySelector("#variant-select");
    if (!select) return;
    select.addEventListener("change", function () {
        const option = select.options[select.selectedIndex];
        const price = Number(option.dataset.price);
        const stock = Number(option.dataset.stock);
        document.querySelector("#cart-variant-id").value = select.value;
        document.querySelector("#product-price").textContent =
            "Rp " + new Intl.NumberFormat("id-ID").format(price);
        document.querySelector("#product-stock").textContent = String(stock);
    });
});
</script>
