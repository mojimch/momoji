<div class="card auth-card">
    <div class="card-header text-center">
        <h1 class="h4 mb-0">Daftar Customer</h1>
    </div>
    <div class="card-body p-4">
        <form action="<?= e(rtrim(APP_URL, '/')) ?>/register" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="register-name">Nama lengkap</label>
                <input class="form-control" id="register-name" name="name" value="<?= e(old('name')) ?>" required maxlength="150">
            </div>
            <div class="mb-3">
                <label class="form-label" for="register-email">Email</label>
                <input class="form-control" id="register-email" type="email" name="email" value="<?= e(old('email')) ?>" required maxlength="150">
            </div>
            <div class="mb-3">
                <label class="form-label" for="register-phone">Nomor telepon</label>
                <input class="form-control" id="register-phone" type="tel" name="phone" value="<?= e(old('phone')) ?>" required maxlength="30">
            </div>
            <div class="mb-3">
                <label class="form-label" for="register-password">Password</label>
                <input class="form-control" id="register-password" type="password" name="password" minlength="8" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="register-password-confirmation">Konfirmasi password</label>
                <input class="form-control" id="register-password-confirmation" type="password" name="password_confirmation" minlength="8" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Daftar Sekarang</button>
        </form>
        <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="<?= e(rtrim(APP_URL, '/')) ?>/login">Masuk</a></p>
    </div>
</div>
