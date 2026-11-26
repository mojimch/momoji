<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Voucher</h1>
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-body">
            <h2 class="h5">Tambah / Edit Voucher</h2>
            <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/vouchers">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="voucher-id">
                <div class="mb-3"><label class="form-label" for="voucher-code">Kode</label><input class="form-control text-uppercase" id="voucher-code" name="code" required maxlength="50"></div>
                <div class="mb-3"><label class="form-label" for="voucher-description">Deskripsi</label><textarea class="form-control" id="voucher-description" name="description"></textarea></div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label" for="voucher-type">Jenis</label><select class="form-select" id="voucher-type" name="discount_type"><option value="percentage">Persentase</option><option value="fixed">Potongan tetap</option><option value="free_shipping">Gratis ongkir</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="voucher-value">Nilai diskon</label><input class="form-control" id="voucher-value" type="number" name="discount_value" min="0" step="100" value="0"></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label" for="voucher-minimum">Minimal belanja</label><input class="form-control" id="voucher-minimum" type="number" name="minimum_purchase" min="0" step="100" value="0"></div>
                    <div class="col-md-6"><label class="form-label" for="voucher-max">Maksimal diskon</label><input class="form-control" id="voucher-max" type="number" name="max_discount" min="0" step="100"></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label" for="voucher-quota">Kuota</label><input class="form-control" id="voucher-quota" type="number" name="quota" min="0"></div>
                    <div class="col-md-6"><label class="form-label" for="voucher-start">Mulai</label><input class="form-control" id="voucher-start" type="datetime-local" name="start_at"></div>
                    <div class="col-md-6"><label class="form-label" for="voucher-end">Berakhir</label><input class="form-control" id="voucher-end" type="datetime-local" name="end_at"></div>
                </div>
                <div class="form-check mb-3"><input class="form-check-input" id="voucher-active" type="checkbox" name="is_active" checked><label class="form-check-label" for="voucher-active">Aktif</label></div>
                <button class="btn btn-primary" type="submit">Simpan Voucher</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Kode</th><th>Jenis</th><th>Minimum</th><th>Kuota</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><strong><?= e($voucher['code']) ?></strong></td><td><?= e($voucher['discount_type']) ?></td><td><?= format_rupiah($voucher['minimum_purchase']) ?></td><td><?= $voucher['quota'] === null ? 'Unlimited' : (int) $voucher['used_count'] . '/' . (int) $voucher['quota'] ?></td>
                    <td><span class="badge <?= $voucher['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $voucher['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-voucher-edit="<?= e(json_encode($voucher)) ?>">Edit</button>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/vouchers/delete/<?= (int) $voucher['id'] ?>" class="d-inline" onsubmit="return confirm('Hapus voucher ini?')">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div></div>
    </div>
</div>
<script>
document.querySelectorAll("[data-voucher-edit]").forEach(function(button) {
    button.addEventListener("click", function() {
        const voucher = JSON.parse(button.dataset.voucherEdit);
        document.querySelector("#voucher-id").value = voucher.id;
        document.querySelector("#voucher-code").value = voucher.code;
        document.querySelector("#voucher-description").value = voucher.description || "";
        document.querySelector("#voucher-type").value = voucher.discount_type;
        document.querySelector("#voucher-value").value = voucher.discount_value;
        document.querySelector("#voucher-minimum").value = voucher.minimum_purchase;
        document.querySelector("#voucher-max").value = voucher.max_discount || "";
        document.querySelector("#voucher-quota").value = voucher.quota || "";
        document.querySelector("#voucher-start").value = voucher.start_at ? voucher.start_at.replace(" ", "T").substring(0,16) : "";
        document.querySelector("#voucher-end").value = voucher.end_at ? voucher.end_at.replace(" ", "T").substring(0,16) : "";
        document.querySelector("#voucher-active").checked = Boolean(Number(voucher.is_active));
    });
});
</script>
