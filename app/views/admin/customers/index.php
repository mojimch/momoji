<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 mb-0">Pelanggan</h1>
    <form method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/customers" class="d-flex gap-2">
        <input class="form-control" type="search" name="q" value="<?= e($query) ?>" placeholder="Nama, email, telepon">
        <button class="btn btn-primary" type="submit">Cari</button>
    </form>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Bergabung</th><th>Status</th><th>Pesanan</th><th>Total dibayar</th></tr></thead>
        <tbody>
        <?php foreach ($customers['items'] as $customer): ?>
            <tr>
                <td><?= e($customer['name']) ?></td>
                <td><?= e($customer['email']) ?><div class="small text-secondary"><?= e($customer['phone']) ?></div></td>
                <td><?= e($customer['created_at']) ?></td>
                <td><span class="badge <?= $customer['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($customer['status']) ?></span></td>
                <td><?= (int) $customer['order_count'] ?></td>
                <td><?= format_rupiah($customer['spent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($customers['items'] === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Pelanggan tidak ditemukan.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php if ($customers['pages'] > 1): ?>
    <nav class="mt-3" aria-label="Halaman pelanggan"><ul class="pagination justify-content-center">
        <?php for ($page = 1; $page <= $customers['pages']; $page++): ?>
            <li class="page-item <?= $page === $customers['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?q=<?= e(rawurlencode($query)) ?>&page=<?= $page ?>"><?= $page ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
