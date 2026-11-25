<div class="card auth-card">
    <div class="card-header text-center">
        <h1 class="h4 mb-0">Reset Password</h1>
    </div>
    <div class="card-body p-4">
        <form action="<?= e(rtrim(APP_URL, '/')) ?>/reset-password/<?= e($token) ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="reset-password">Password baru</label>
                <input class="form-control" id="reset-password" type="password" name="password" minlength="8" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="reset-password-confirmation">Konfirmasi password baru</label>
                <input class="form-control" id="reset-password-confirmation" type="password" name="password_confirmation" minlength="8" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Perbarui Password</button>
        </form>
    </div>
</div>
