<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h1 class="h4 mb-0">Ganti Password Admin</h1></div>
            <div class="card-body">
                <p class="text-secondary">Gunakan password unik dengan minimal 12 karakter.</p>
                <form method="post" action="<?= e(rtrim(APP_URL, '/')) ?>/admin/password">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="current-password">Password saat ini</label>
                        <input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new-password">Password baru</label>
                        <input class="form-control" id="new-password" type="password" name="new_password" minlength="12" maxlength="4096" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new-password-confirmation">Konfirmasi password baru</label>
                        <input class="form-control" id="new-password-confirmation" type="password" name="new_password_confirmation" minlength="12" maxlength="4096" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Perbarui Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
