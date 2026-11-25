<div class="card auth-card">
    <div class="card-header text-center">
        <h1 class="h4 mb-0">Lupa Password</h1>
    </div>
    <div class="card-body p-4">
        <?php if (APP_ENV === 'local' && ($token = flash('reset_token'))): ?>
            <div class="alert alert-warning">
                Mode simulasi lokal:<br>
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/reset-password/<?= e($token) ?>">Buka tautan reset password</a>
            </div>
        <?php endif; ?>
        <form action="<?= e(rtrim(APP_URL, '/')) ?>/forgot-password" method="post">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label" for="forgot-email">Email terdaftar</label>
                <input class="form-control" id="forgot-email" type="email" name="email" required maxlength="150">
            </div>
            <button class="btn btn-primary w-100" type="submit">Buat Tautan Reset</button>
        </form>
    </div>
</div>
