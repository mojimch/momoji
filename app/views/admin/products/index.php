<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manajemen Produk</h1>
    <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/create">Tambah Produk</a>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Gambar</th><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><img src="<?= e(asset_url($product['image_path'] ?: '/assets/images/placeholder-product.svg')) ?>" alt="" width="64" height="64" style="object-fit:cover;"></td>
                <td><strong><?= e($product['name']) ?></strong><div class="small text-secondary"><?= e($product['slug']) ?></div></td>
                <td><?= e($product['category_name'] ?: '-') ?></td>
                <td><?= format_rupiah($product['promo_price'] ?: $product['price']) ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><span class="badge <?= $product['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $product['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/edit/<?= (int) $product['id'] ?>">Edit</a>
                    <form class="d-inline" method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/products/delete/<?= (int) $product['id'] ?>" onsubmit="return confirm('Hapus produk ini?')">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
