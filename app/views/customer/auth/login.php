<div class="card auth-card">
    <div class="card-header text-center">
        <h1 class="h4 mb-0">Login Customer</h1>
    </div>
    <div class="card-body p-4">
        <form action="<?= e(rtrim(APP_URL, '/')) ?>/login" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="login-email">Email</label>
                <input class="form-control" id="login-email" type="email" name="email" required maxlength="150">
            </div>
            <div class="mb-4">
                <label class="form-label" for="login-password">Password</label>
                <input class="form-control" id="login-password" type="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Masuk</button>
        </form>
        <div class="d-flex justify-content-between mt-3">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/forgot-password">Lupa password?</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/register">Daftar</a>
        </div>
    </div>
</div>
