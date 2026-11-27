<?php

class AdminController extends Controller
{
    private AdminModel $admin;
    private UserModel $users;

    public function __construct()
    {
        $this->admin = new AdminModel();
        $this->users = new UserModel();
    }

    public function dashboard(): void
    {
        AuthMiddleware::requireAdmin();
        $period = (string) ($_GET['period'] ?? 'daily');
        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }
        $this->view('admin/dashboard', [
            'auth' => AuthMiddleware::check(),
            'stats' => $this->admin->dashboardStats(),
            'recentOrders' => $this->admin->recentOrders(),
            'lowStock' => $this->admin->lowStockItems(),
            'topProducts' => $this->admin->topProducts(),
            'sales' => $this->admin->salesSeries($period),
            'period' => $period,
        ], 'admin_layout');
    }

    public function customers(): void
    {
        AuthMiddleware::requireAdmin();
        $query = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->view('admin/customers/index', [
            'customers' => $this->admin->customers($query, $page),
            'query' => $query,
        ], 'admin_layout');
    }

    public function reports(): void
    {
        AuthMiddleware::requireAdmin();
        [$from, $to] = $this->reportDates();
        $rows = $this->admin->reportRows($from, $to);
        if (($_GET['format'] ?? '') === 'csv') {
            $this->downloadReport($rows);
        }
        $total = 0.0;
        $paidTotal = 0.0;
        foreach ($rows as $row) {
            $total += (float) $row['total_amount'];
            if ($row['payment_status'] === 'Dibayar' || $row['status'] === 'Selesai') {
                $paidTotal += (float) $row['total_amount'];
            }
        }
        $this->view('admin/reports/index', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'paidTotal' => $paidTotal,
        ], 'admin_layout');
    }

    public function settings(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/settings/index', [
            'settings' => $this->admin->storeSettings(),
        ], 'admin_layout');
    }

    public function saveSettings(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/settings');
        }

        $data = [
            'store_name' => trim((string) ($_POST['store_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'pickup_address' => trim((string) ($_POST['pickup_address'] ?? '')),
            'low_stock_threshold' => filter_var(
                $_POST['low_stock_threshold'] ?? null,
                FILTER_VALIDATE_INT
            ),
        ];
        if (
            $data['store_name'] === ''
            || strlen($data['store_name']) > 150
            || strlen($data['phone']) > 30
            || ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            || strlen($data['pickup_address']) > 10000
            || $data['low_stock_threshold'] === false
            || $data['low_stock_threshold'] < 0
            || $data['low_stock_threshold'] > 100000
        ) {
            flash('error', 'Data toko atau batas stok tidak valid.');
            $this->redirect('/admin/settings');
        }

        $oldPath = $this->admin->storeSettings()['qris_image_path'] ?? null;
        $newPath = null;
        try {
            if (isset($_FILES['qris_image']) && ($_FILES['qris_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $newPath = safe_upload($_FILES['qris_image'], 'settings');
            }
            $this->admin->saveStoreSettings($data, $newPath);
            if ($newPath !== null && is_string($oldPath) && $oldPath !== $newPath) {
                $this->removeSettingsImage($oldPath);
            }
            flash('success', 'Pengaturan toko berhasil disimpan.');
        } catch (RuntimeException $exception) {
            $this->removeSettingsImage($newPath);
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            $this->removeSettingsImage($newPath);
            flash('error', 'Pengaturan toko tidak dapat disimpan.');
        }
        $this->redirect('/admin/settings');
    }

    public function backup(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/backup/index', [], 'admin_layout');
    }

    public function password(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/password/index', [], 'admin_layout');
    }

    public function changePassword(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/password');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
        if (strlen($newPassword) < 12 || strlen($newPassword) > 4096) {
            flash('error', 'Password baru harus terdiri dari 12 sampai 4.096 karakter.');
            $this->redirect('/admin/password');
        }
        if ($newPassword !== $confirmation) {
            flash('error', 'Konfirmasi password baru tidak cocok.');
            $this->redirect('/admin/password');
        }

        try {
            if (!$this->users->changeAdminPassword(
                (int) AuthMiddleware::check()['id'],
                $currentPassword,
                $newPassword
            )) {
                flash('error', 'Password saat ini tidak sesuai.');
                $this->redirect('/admin/password');
            }
            session_regenerate_id(true);
            flash('success', 'Password admin berhasil diperbarui.');
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Password admin tidak dapat diperbarui.');
        }
        $this->redirect('/admin/password');
    }

    public function downloadBackup(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/backup');
        }

        $filename = 'backup_momoji_' . date('Y-m-d_H-i-s') . '.sql';
        $output = fopen('php://temp/maxmemory:5242880', 'w+b');
        if ($output === false) {
            throw new RuntimeException('File sementara untuk backup tidak dapat dibuat.');
        }
        try {
            (new DatabaseBackupModel())->writeSqlDump($output);
            rewind($output);
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, private');
            fpassthru($output);
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            if (!headers_sent()) {
                http_response_code(500);
            }
        } finally {
            fclose($output);
        }
        exit;
    }

    private function reportDates(): array
    {
        $today = date('Y-m-d');
        $defaultFrom = date('Y-m-01');
        $from = trim((string) ($_GET['from'] ?? $defaultFrom));
        $to = trim((string) ($_GET['to'] ?? $today));
        if (!$this->isDate($from) || !$this->isDate($to) || $from > $to) {
            flash('error', 'Rentang tanggal laporan tidak valid.');
            return [$defaultFrom, $today];
        }
        return [$from, $to];
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function downloadReport(array $rows): never
    {
        $filename = 'laporan_momoji_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, private');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new RuntimeException('File laporan tidak dapat dibuat.');
        }
        fputcsv($output, [
            'Invoice', 'Tanggal', 'Pelanggan', 'Email', 'Status Pesanan',
            'Status Pembayaran', 'Metode Pembayaran', 'Subtotal', 'Ongkir',
            'Diskon', 'Total',
        ]);
        foreach ($rows as $row) {
            $values = [
                $row['invoice_number'],
                $row['created_at'],
                $row['customer_name'],
                $row['email'],
                $row['status'],
                $row['payment_status'],
                $row['payment_method'],
                $row['subtotal'],
                $row['shipping_cost'],
                $row['discount_amount'],
                $row['total_amount'],
            ];
            foreach ($values as &$value) {
                if (is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value)) {
                    $value = "'" . $value;
                }
            }
            unset($value);
            fputcsv($output, $values, ',', '"', '\\');
        }
        fclose($output);
        exit;
    }

    private function removeSettingsImage(?string $path): void
    {
        if ($path === null || !str_starts_with($path, '/storage/uploads/settings/')) {
            return;
        }
        $relative = substr($path, strlen('/storage/uploads/'));
        $file = UPLOAD_PATH . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
