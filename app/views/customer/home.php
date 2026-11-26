<section class="rounded-4 p-5 mb-4" style="background: var(--soft-sand);">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1 class="display-6 fw-bold text-primary">Selamat Datang di Momoji</h1>
            <p class="lead">Temukan produk pilihan dengan nuansa alam dan rasa yang menenangkan.</p>
            <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/products">Lihat Katalog</a>
        </div>
    </div>
</section>

<section class="mb-5">
    <h2 class="h4 mb-3">Kategori</h2>
    <div class="row g-3">
        <?php foreach ($categories as $category): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a class="card text-decoration-none h-100" href="<?= e(rtrim(APP_URL, '/')) ?>/products?category=<?= (int) $category['id'] ?>">
                    <div class="card-body text-center">
                        <span class="fs-3"><?= e($category['icon'] ?: '•') ?></span>
                        <h3 class="h6 mt-2 mb-0 text-dark"><?= e($category['name']) ?></h3>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Produk Unggulan</h2>
        <a href="<?= e(rtrim(APP_URL, '/')) ?>/products">Lihat semua</a>
    </div>
    <div class="row g-3">
        <?php $shown = []; ?>
        <?php foreach (array_merge($featured, $bestsellers) as $product): ?>
            <?php if (!isset($shown[$product['id']])): ?>
                <?php $shown[$product['id']] = true; ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100">
                        <img class="card-img-top" src="<?= e(asset_url($product['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>" alt="<?= e($product['name']) ?>" style="height:180px;object-fit:cover;">
                        <div class="card-body">
                            <h3 class="h6"><?= e($product['name']) ?></h3>
                            <strong class="text-primary"><?= format_rupiah($product['promo_price'] ?: $product['price']) ?></strong>
                        </div>
                        <div class="card-footer bg-white border-0"><a class="btn btn-sm btn-outline-primary w-100" href="<?= e(rtrim(APP_URL, '/')) ?>/products/detail/<?= e($product['slug']) ?>">Detail</a></div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-4">
    <h2 class="h4 mb-3">Produk Terbaru</h2>
    <div class="row g-3">
        <?php foreach ($latest as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100">
                    <img class="card-img-top" src="<?= e(asset_url($product['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>" alt="<?= e($product['name']) ?>" style="height:180px;object-fit:cover;">
                    <div class="card-body">
                        <h3 class="h6"><?= e($product['name']) ?></h3>
                        <strong class="text-primary"><?= format_rupiah($product['promo_price'] ?: $product['price']) ?></strong>
                    </div>
                    <div class="card-footer bg-white border-0"><a class="btn btn-sm btn-outline-primary w-100" href="<?= e(rtrim(APP_URL, '/')) ?>/products/detail/<?= e($product['slug']) ?>">Detail</a></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
