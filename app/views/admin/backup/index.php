<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h1 class="h4 mb-0">Backup Database</h1></div>
            <div class="card-body">
                <p>Unduh dump SQL berisi struktur dan data database. File akan dibuat saat permintaan diproses dan tidak disimpan di server.</p>
                <div class="alert alert-warning">Simpan file backup di lokasi tepercaya dan batasi aksesnya karena dump memuat data pelanggan dan transaksi.</div>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/backup">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary" type="submit">Unduh Backup SQL</button>
                </form>
            </div>
        </div>
    </div>
</div>
