<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manajemen Kategori</h1>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h2 class="h5">Tambah kategori</h2>
            <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/categories">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="category-name">Nama</label><input class="form-control" id="category-name" name="name" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label" for="category-icon">Icon / emoji</label><input class="form-control" id="category-icon" name="icon" maxlength="255"></div>
                <button class="btn btn-primary" type="submit">Tambah</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nama</th><th>Slug</th><th>Icon</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><code><?= e($category['slug']) ?></code></td>
                        <td><?= e($category['icon']) ?></td>
                        <td>
                            <details>
                                <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                <form class="mt-2" method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/categories/update/<?= (int) $category['id'] ?>">
                                    <?= csrf_field() ?>
                                    <input class="form-control mb-2" name="name" value="<?= e($category['name']) ?>" required>
                                    <input class="form-control mb-2" name="icon" value="<?= e($category['icon']) ?>">
                                    <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                                </form>
                            </details>
                            <form class="d-inline" method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/categories/delete/<?= (int) $category['id'] ?>" onsubmit="return confirm('Hapus kategori ini?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>
