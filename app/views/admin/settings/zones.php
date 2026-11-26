<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Zona Pengiriman</h1>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-body">
            <h2 class="h5">Tambah / Edit Zona</h2>
            <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/settings/zones">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="zone-id">
                <div class="mb-3"><label class="form-label" for="zone-name">Nama zona</label><input class="form-control" id="zone-name" name="zone_name" required></div>
                <div class="mb-3"><label class="form-label" for="zone-description">Deskripsi</label><textarea class="form-control" id="zone-description" name="description"></textarea></div>
                <div class="mb-3"><label class="form-label" for="zone-cost">Biaya</label><input class="form-control" id="zone-cost" type="number" name="cost" min="0" step="100" required></div>
                <div class="form-check mb-3"><input class="form-check-input" id="zone-active" type="checkbox" name="is_active" checked><label class="form-check-label" for="zone-active">Aktif</label></div>
                <button class="btn btn-primary" type="submit">Simpan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Zona</th><th>Deskripsi</th><th>Ongkir</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($zones as $zone): ?>
                <tr>
                    <td><?= e($zone['zone_name']) ?></td><td><?= e($zone['description']) ?></td><td><?= format_rupiah($zone['cost']) ?></td>
                    <td><span class="badge <?= $zone['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $zone['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-zone-edit="<?= e(json_encode($zone)) ?>">Edit</button>
                        <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/settings/zones/delete/<?= (int) $zone['id'] ?>" class="d-inline" onsubmit="return confirm('Hapus zona ini?')">
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
document.querySelectorAll("[data-zone-edit]").forEach(function(button) {
    button.addEventListener("click", function() {
        const zone = JSON.parse(button.dataset.zoneEdit);
        document.querySelector("#zone-id").value = zone.id;
        document.querySelector("#zone-name").value = zone.zone_name;
        document.querySelector("#zone-description").value = zone.description || "";
        document.querySelector("#zone-cost").value = zone.cost;
        document.querySelector("#zone-active").checked = Boolean(Number(zone.is_active));
    });
});
</script>
