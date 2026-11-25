<?php

class AuthController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function home(): void
    {
        $this->view('customer/home', [], 'customer_layout');
    }

    public function register(): void
    {
        $this->view('customer/auth/register', [], 'customer_layout');
    }

    public function registerPost(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
            $this->redirect('/register');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        with_old(['name' => $name, 'email' => $email, 'phone' => $phone]);

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
            flash('error', 'Nama, email, dan nomor telepon wajib diisi dengan benar.');
            $this->redirect('/register');
        }

        if (strlen($password) < 8 || $password !== $passwordConfirmation) {
            flash('error', 'Password minimal 8 karakter dan harus sama dengan konfirmasi.');
            $this->redirect('/register');
        }

        if ($this->users->emailExists($email)) {
            flash('error', 'Email sudah terdaftar.');
            $this->redirect('/register');
        }

        $this->users->createCustomer([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ]);
        clear_old();
        flash('success', 'Registrasi berhasil. Silakan login.');
        $this->redirect('/login');
    }

    public function login(): void
    {
        $this->view('customer/auth/login', [], 'customer_layout');
    }

    public function adminLogin(): void
    {
        $this->view('admin/auth/login', [], 'admin_layout');
    }

    public function loginPost(): void
    {
        $this->authenticate(false);
    }

    public function adminLoginPost(): void
    {
        $this->authenticate(true);
    }

    private function authenticate(bool $adminOnly): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi login tidak valid.');
            $this->redirect($adminOnly ? '/admin/login' : '/login');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $user = $this->users->findByEmail($email);

        if (
            !$user
            || ($adminOnly && $user['role'] !== 'admin')
            || (!$adminOnly && $user['role'] !== 'customer')
            || $user['status'] !== 'active'
            || !password_verify($password, $user['password'])
        ) {
            flash('error', 'Email atau password salah, atau akun tidak aktif.');
            $this->redirect($adminOnly ? '/admin/login' : '/login');
        }

        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        clear_old();
        flash('success', 'Berhasil login.');
        $this->redirect($adminOnly ? '/admin/dashboard' : '/');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        session_start();
        flash('success', 'Anda telah logout.');
        redirect('/login');
    }

    public function forgotPassword(): void
    {
        $this->view('customer/auth/forgot_password', [], 'customer_layout');
    }

    public function forgotPasswordPost(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/forgot-password');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $user = $this->users->findByEmail($email);
        $resetToken = null;

        if ($user) {
            $resetToken = $this->users->createPasswordReset((int) $user['id']);
        }

        flash('success', 'Jika email terdaftar, tautan reset telah dibuat.');
        if (APP_ENV === 'local' && $resetToken !== null) {
            flash('reset_token', $resetToken);
        }
        $this->redirect('/forgot-password');
    }

    public function resetPassword(string $token = ''): void
    {
        $this->view('customer/auth/reset_password', ['token' => $token], 'customer_layout');
    }

    public function resetPasswordPost(string $token = ''): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/reset-password/' . rawurlencode($token));
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8 || $password !== $confirmation) {
            flash('error', 'Password minimal 8 karakter dan konfirmasi harus sama.');
            $this->redirect('/reset-password/' . rawurlencode($token));
        }

        if (!$this->users->resetPassword($token, $password)) {
            flash('error', 'Tautan reset tidak valid atau sudah kedaluwarsa.');
            $this->redirect('/forgot-password');
        }

        flash('success', 'Password berhasil diperbarui. Silakan login.');
        $this->redirect('/login');
    }

    public function dashboard(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/dashboard', [
            'auth' => AuthMiddleware::check(),
        ], 'admin_layout');
    }
}
