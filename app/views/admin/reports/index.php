<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h1 class="h3 mb-0">Laporan Transaksi</h1>
    <a class="btn btn-outline-primary" href="?from=<?= e(rawurlencode($from)) ?>&to=<?= e(rawurlencode($to)) ?>&format=csv">Unduh CSV</a>
</div>
<form class="card card-body mb-4" method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/reports">
    <div class="row g-3 align-items-end">
        <div class="col-sm-5"><label class="form-label" for="report-from">Dari tanggal</label><input class="form-control" id="report-from" type="date" name="from" value="<?= e($from) ?>" required></div>
        <div class="col-sm-5"><label class="form-label" for="report-to">Sampai tanggal</label><input class="form-control" id="report-to" type="date" name="to" value="<?= e($to) ?>" required></div>
        <div class="col-sm-2"><button class="btn btn-primary w-100" type="submit">Tampilkan</button></div>
    </div>
</form>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card card-body"><span class="text-secondary">Jumlah transaksi</span><strong class="h4 mb-0"><?= count($rows) ?></strong></div></div>
    <div class="col-md-6"><div class="card card-body"><span class="text-secondary">Pendapatan dibayar / selesai</span><strong class="h4 mb-0"><?= format_rupiah($paidTotal) ?></strong></div></div>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Invoice / Tanggal</th><th>Pelanggan</th><th>Status</th><th>Pembayaran</th><th>Subtotal</th><th>Ongkir</th><th>Diskon</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['invoice_number']) ?><div class="small text-secondary"><?= e($row['created_at']) ?></div></td>
                <td><?= e($row['customer_name'] ?: 'Customer dihapus') ?></td>
                <td><?= e($row['status']) ?></td>
                <td><?= e($row['payment_status']) ?><div class="small text-secondary"><?= e($row['payment_method']) ?></div></td>
                <td><?= format_rupiah($row['subtotal']) ?></td>
                <td><?= format_rupiah($row['shipping_cost']) ?></td>
                <td><?= format_rupiah($row['discount_amount']) ?></td>
                <td><?= format_rupiah($row['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?><tr><td colspan="8" class="text-center text-secondary py-4">Tidak ada transaksi pada rentang tanggal ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
