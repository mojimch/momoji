<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Katalog Produk</h1>
</div>
<div class="row">
    <aside class="col-lg-3 mb-4">
        <form class="card card-body" method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/products">
            <div class="mb-3">
                <label class="form-label" for="catalog-q">Cari nama</label>
                <input class="form-control" id="catalog-q" type="search" name="q" value="<?= e($filters['q']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="catalog-category">Kategori</label>
                <select class="form-select" id="catalog-category" name="category">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (string) $filters['category'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6"><input class="form-control" type="number" name="min" min="0" step="100" value="<?= e($filters['min']) ?>" placeholder="Harga minimum"></div>
                <div class="col-6"><input class="form-control" type="number" name="max" min="0" step="100" value="<?= e($filters['max']) ?>" placeholder="Harga maksimum"></div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" id="catalog-promo" type="checkbox" name="promo" <?= $filters['promo'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="catalog-promo">Produk promo</label>
            </div>
            <div class="mb-3">
                <label class="form-label" for="catalog-sort">Urutkan</label>
                <select class="form-select" id="catalog-sort" name="sort">
                    <option value="latest" <?= $filters['sort'] === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Nama</option>
                    <option value="price-low" <?= $filters['sort'] === 'price-low' ? 'selected' : '' ?>>Harga terendah</option>
                    <option value="price-high" <?= $filters['sort'] === 'price-high' ? 'selected' : '' ?>>Harga tertinggi</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </form>
    </aside>
    <section class="col-lg-9">
        <div class="row g-3">
            <?php foreach ($products['items'] as $product): ?>
                <div class="col-6 col-md-4">
                    <div class="card h-100">
                        <img class="card-img-top" src="<?= e(asset_url($product['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>" alt="<?= e($product['name']) ?>" style="height:180px;object-fit:cover;">
                        <div class="card-body">
                            <span class="badge text-bg-light mb-2"><?= e($product['category_name'] ?: 'Tanpa kategori') ?></span>
                            <h2 class="h6"><?= e($product['name']) ?></h2>
                            <?php if ($product['promo_price'] !== null): ?>
                                <div class="text-decoration-line-through small text-secondary"><?= format_rupiah($product['price']) ?></div>
                                <strong class="text-primary"><?= format_rupiah($product['promo_price']) ?></strong>
                            <?php else: ?>
                                <strong class="text-primary"><?= format_rupiah($product['price']) ?></strong>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0"><a class="btn btn-sm btn-outline-primary w-100" href="<?= e(rtrim(APP_URL, '/')) ?>/products/detail/<?= e($product['slug']) ?>">Lihat Detail</a></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($products['items'] === []): ?>
                <div class="col-12"><div class="alert alert-light">Tidak ada produk yang sesuai filter.</div></div>
            <?php endif; ?>
        </div>

        <?php if ($products['pages'] > 1): ?>
            <nav class="mt-4" aria-label="Pagination katalog">
                <ul class="pagination">
                    <?php for ($pageNumber = 1; $pageNumber <= $products['pages']; $pageNumber++): ?>
                        <?php
                        $query = array_filter($filters, fn($value): bool => $value !== '' && $value !== 0);
                        $query['page'] = $pageNumber;
                        ?>
                        <li class="page-item <?= $pageNumber === $products['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(rtrim(APP_URL, '/')) ?>/products?<?= e(http_build_query($query)) ?>"><?= $pageNumber ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </section>
</div>
