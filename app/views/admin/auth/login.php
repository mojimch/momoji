<div class="card auth-card">
    <div class="card-header text-center">
        <h1 class="h4 mb-0">Login Administrator</h1>
    </div>
    <div class="card-body p-4">
        <form action="<?= e(rtrim(APP_URL, '/')) ?>/admin/login" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="admin-login-email">Email admin</label>
                <input class="form-control" id="admin-login-email" type="email" name="email" required maxlength="150">
            </div>
            <div class="mb-4">
                <label class="form-label" for="admin-login-password">Password</label>
                <input class="form-control" id="admin-login-password" type="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Masuk ke Dashboard</button>
        </form>
    </div>
</div>
