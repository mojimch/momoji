<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 mb-0">Moderasi Ulasan</h1>
    <form method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/reviews" class="d-flex gap-2">
        <select class="form-select" name="status" aria-label="Filter status ulasan">
            <option value="">Semua status</option>
            <?php foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary" type="submit">Filter</button>
    </form>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Tanggal</th><th>Customer</th><th>Produk</th><th>Pesanan</th><th>Rating</th><th>Ulasan</th><th>Status / Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?= e($review['created_at']) ?></td>
                <td><?= e($review['customer_name']) ?></td>
                <td><a href="<?= e(rtrim(APP_URL, '/')) ?>/products/detail/<?= e(rawurlencode($review['slug'])) ?>" target="_blank" rel="noopener"><?= e($review['product_name']) ?></a></td>
                <td><?= e($review['invoice_number']) ?></td>
                <td><?= (int) $review['rating'] ?>/5</td>
                <td class="text-break"><?= nl2br(e($review['comment'] ?: '—')) ?></td>
                <td>
                    <span class="badge <?= $review['status'] === 'pending' ? 'text-bg-warning' : ($review['status'] === 'approved' ? 'text-bg-success' : 'text-bg-secondary') ?>">
                        <?= e($review['status']) ?>
                    </span>
                    <?php if ($review['status'] !== 'approved'): ?>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/reviews/<?= (int) $review['id'] ?>/status" class="mt-2">
                            <?= csrf_field() ?><input type="hidden" name="status" value="approved">
                            <button class="btn btn-sm btn-success w-100" type="submit">Setujui</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($review['status'] !== 'rejected'): ?>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/reviews/<?= (int) $review['id'] ?>/status" class="mt-2">
                            <?= csrf_field() ?><input type="hidden" name="status" value="rejected">
                            <button class="btn btn-sm btn-outline-danger w-100" type="submit">Tolak</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($reviews === []): ?><tr><td colspan="7" class="text-center text-secondary py-4">Belum ada ulasan untuk filter ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
