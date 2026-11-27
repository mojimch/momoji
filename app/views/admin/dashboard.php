<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard Admin</h1>
        <p class="text-secondary mb-0">Selamat datang, <?= e($auth['name'] ?? 'Administrator') ?>.</p>
    </div>
    <form method="get" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/dashboard" class="d-flex gap-2">
        <label class="visually-hidden" for="sales-period">Periode grafik</label>
        <select class="form-select" id="sales-period" name="period">
            <?php foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $period === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary" type="submit">Tampilkan</button>
    </form>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Produk', (int) $stats['product_count']],
        ['Pelanggan', (int) $stats['customer_count']],
        ['Pesanan', (int) $stats['order_count']],
        ['Pendapatan', format_rupiah($stats['revenue'])],
    ] as [$label, $value]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100"><div class="card-body">
                <span class="text-secondary"><?= e($label) ?></span>
                <h2 class="h4 mb-0 mt-2"><?= e($value) ?></h2>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Grafik Penjualan <?= e(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'][$period]) ?></h2>
            </div>
            <div class="card-body"><div style="height:300px"><canvas id="sales-chart" aria-label="Grafik nilai penjualan" role="img"></canvas></div></div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0">Produk Terlaris</h2></div>
            <div class="list-group list-group-flush">
                <?php foreach ($topProducts as $product): ?>
                    <div class="list-group-item d-flex justify-content-between gap-2">
                        <span><?= e($product['product_name']) ?></span><strong><?= (int) $product['units_sold'] ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if ($topProducts === []): ?><div class="list-group-item text-secondary">Belum ada data penjualan.</div><?php endif; ?>
            </div>
        </section>
    </div>
</div>
<div class="row g-4">
    <div class="col-xl-7">
        <section class="card">
            <div class="card-header"><h2 class="h5 mb-0">Pesanan Terbaru</h2></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/orders/detail/<?= e(rawurlencode($order['invoice_number'])) ?>"><?= e($order['invoice_number']) ?></a><div class="small text-secondary"><?= e($order['created_at']) ?></div></td>
                        <td><?= e($order['customer_name'] ?: 'Customer dihapus') ?></td>
                        <td><?= format_rupiah($order['total_amount']) ?></td>
                        <td><?= e($order['status']) ?><div class="small text-secondary"><?= e($order['payment_status']) ?></div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recentOrders === []): ?><tr><td colspan="4" class="text-center text-secondary py-4">Belum ada pesanan.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="card">
            <div class="card-header"><h2 class="h5 mb-0">Peringatan Stok Menipis</h2></div>
            <div class="list-group list-group-flush">
                <?php foreach ($lowStock as $item): ?>
                    <div class="list-group-item d-flex justify-content-between gap-2">
                        <span><?= e($item['item_name']) ?><small class="d-block text-secondary"><?= e($item['item_type']) ?></small></span>
                        <span class="badge text-bg-warning align-self-center"><?= (int) $item['stock'] ?> / <?= (int) $item['stock_threshold'] ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($lowStock === []): ?><div class="list-group-item text-secondary">Tidak ada stok di bawah batas peringatan.</div><?php endif; ?>
            </div>
        </section>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("sales-chart");
    if (!canvas || typeof Chart === "undefined") return;
    new Chart(canvas, {
        type: "line",
        data: {
            labels: <?= json_encode($sales['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [{
                label: "Pendapatan (Rp)",
                data: <?= json_encode($sales['values'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                borderColor: "#397C83",
                backgroundColor: "rgba(57, 124, 131, 0.15)",
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
