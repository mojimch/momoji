<?php

class OrderController extends Controller
{
    private OrderModel $orders;

    public function __construct()
    {
        $this->orders = new OrderModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireCustomer();
        $this->view('customer/orders/index', [
            'orders' => $this->orders->customerOrders((int) AuthMiddleware::check()['id']),
        ], 'customer_layout');
    }

    public function detail(string $invoice): void
    {
        AuthMiddleware::requireCustomer();
        $order = $this->orders->findCustomerOrder(
            (int) AuthMiddleware::check()['id'],
            rawurldecode($invoice)
        );
        if (!$order) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $this->view('customer/orders/detail', ['order' => $order], 'customer_layout');
    }

    public function payment(string $invoice): void
    {
        AuthMiddleware::requireCustomer();
        $order = $this->orders->findCustomerOrder(
            (int) AuthMiddleware::check()['id'],
            rawurldecode($invoice)
        );
        if (!$order || $order['payment_method'] !== 'QRIS') {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $this->view('customer/orders/payment', [
            'order' => $order,
            'store' => $this->orders->storeSettings(),
        ], 'customer_layout');
    }

    public function uploadProof(string $invoice): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/orders/payment/' . rawurlencode($invoice));
        }

        if (!isset($_FILES['proof'])) {
            flash('error', 'Pilih gambar bukti pembayaran.');
            $this->redirect('/orders/payment/' . rawurlencode($invoice));
        }

        $path = null;
        try {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['proof']['tmp_name']);
            if (!is_string($mime)) {
                throw new RuntimeException('Tipe gambar bukti pembayaran tidak dapat dibaca.');
            }
            $path = safe_upload($_FILES['proof'], 'private/payment-proofs');
            $file = [
                'name' => $_FILES['proof']['name'],
                'mime' => $mime,
                'size' => (int) $_FILES['proof']['size'],
            ];
            $this->orders->uploadPaymentProof(
                (int) AuthMiddleware::check()['id'],
                rawurldecode($invoice),
                $file,
                $path
            );
            flash('success', 'Bukti pembayaran terkirim dan menunggu verifikasi admin.');
        } catch (RuntimeException $exception) {
            if ($path !== null) {
                $relative = substr($path, strlen('/storage/uploads/'));
                $storedFile = UPLOAD_PATH . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (is_file($storedFile)) {
                    unlink($storedFile);
                }
            }
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            if ($path !== null) {
                $relative = substr($path, strlen('/storage/uploads/'));
                $storedFile = UPLOAD_PATH . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (is_file($storedFile)) {
                    unlink($storedFile);
                }
            }
            flash('error', 'Bukti pembayaran tidak dapat disimpan. Coba kembali.');
        }
        $this->redirect('/orders/payment/' . rawurlencode($invoice));
    }

    public function cancel(): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/orders');
        }
        $invoice = trim((string) ($_POST['invoice'] ?? ''));
        try {
            $this->orders->cancelCustomerOrder(
                (int) AuthMiddleware::check()['id'],
                $invoice
            );
            flash('success', 'Pesanan dibatalkan dan stok telah dikembalikan.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        $this->redirect('/orders/detail/' . rawurlencode($invoice));
    }

    public function adminIndex(): void
    {
        AuthMiddleware::requireAdmin();
        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'payment_status' => (string) ($_GET['payment_status'] ?? ''),
        ];
        $this->view('admin/orders/index', [
            'orders' => $this->orders->adminOrders($filters),
            'store' => $this->orders->storeSettings(),
            'filters' => $filters,
            'statuses' => [
                'Menunggu konfirmasi', 'Dikonfirmasi', 'Menunggu pembayaran',
                'Diproses', 'Dikemas', 'Siap diambil', 'Sedang dikirim',
                'Selesai', 'Dibatalkan',
            ],
            'paymentStatuses' => [
                'Belum dibayar', 'Menunggu verifikasi', 'Dibayar',
                'Gagal / ditolak', 'Dikembalikan',
            ],
        ], 'admin_layout');
    }

    public function adminDetail(string $invoice): void
    {
        AuthMiddleware::requireAdmin();
        $order = $this->orders->findAdminOrder(rawurldecode($invoice));
        if (!$order) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $transitions = [
            'Menunggu konfirmasi' => ['Dikonfirmasi'],
            'Dikonfirmasi' => ['Menunggu pembayaran', 'Diproses'],
            'Menunggu pembayaran' => ['Dikonfirmasi'],
            'Diproses' => ['Dikemas'],
            'Dikemas' => $order['shipping_method'] === 'pickup' ? ['Siap diambil'] : ['Sedang dikirim'],
            'Siap diambil' => ['Selesai'],
            'Sedang dikirim' => ['Selesai'],
            'Selesai' => [],
            'Dibatalkan' => [],
        ];
        $availableStatuses = $transitions[$order['status']] ?? [];
        if (
            $order['payment_method'] === 'QRIS'
            && $order['payment_status'] !== 'Dibayar'
        ) {
            $availableStatuses = array_values(array_diff($availableStatuses, ['Dikonfirmasi', 'Diproses']));
        }
        if (!in_array($order['status'], ['Selesai', 'Dibatalkan'], true)) {
            $availableStatuses[] = 'Dibatalkan';
        }
        $this->view('admin/orders/detail', [
            'order' => $order,
            'statuses' => $availableStatuses,
        ], 'admin_layout');
    }

    public function updateStatus(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/orders');
        }
        $invoice = trim((string) ($_POST['invoice'] ?? ''));
        try {
            $this->orders->updateStatus(
                $invoice,
                (string) ($_POST['status'] ?? ''),
                (string) ($_POST['reason'] ?? ''),
                (int) AuthMiddleware::check()['id']
            );
            flash('success', 'Status pesanan berhasil diperbarui.');
        } catch (RuntimeException|InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Status pesanan tidak dapat disimpan.');
        }
        $this->redirect('/admin/orders/detail/' . rawurlencode($invoice));
    }

    public function verifyPayment(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/orders');
        }
        $invoice = trim((string) ($_POST['invoice'] ?? ''));
        try {
            $this->orders->setPaymentStatus(
                $invoice,
                (string) ($_POST['decision'] ?? ''),
                (string) ($_POST['admin_note'] ?? ''),
                (int) AuthMiddleware::check()['id']
            );
            flash('success', 'Status pembayaran diperbarui.');
        } catch (RuntimeException|InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Verifikasi pembayaran tidak dapat disimpan.');
        }
        $this->redirect('/admin/orders/detail/' . rawurlencode($invoice));
    }

    public function markCodPaid(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/orders');
        }
        $invoice = trim((string) ($_POST['invoice'] ?? ''));
        try {
            $this->orders->markCodPaid($invoice, (int) AuthMiddleware::check()['id']);
            flash('success', 'Pembayaran COD ditandai diterima.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        $this->redirect('/admin/orders/detail/' . rawurlencode($invoice));
    }

    public function saveQrisImage(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/orders');
        }
        if (!isset($_FILES['qris_image'])) {
            flash('error', 'Pilih gambar QRIS resmi toko.');
            $this->redirect('/admin/orders');
        }

        $path = null;
        try {
            $path = safe_upload($_FILES['qris_image'], 'settings');
            $this->orders->updateQrisImage($path);
            flash('success', 'Gambar QRIS toko berhasil diperbarui.');
        } catch (RuntimeException $exception) {
            $this->removeUploadedFile($path);
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            $this->removeUploadedFile($path);
            flash('error', 'Gambar QRIS tidak dapat disimpan.');
        }
        $this->redirect('/admin/orders');
    }

    public function proofImage(int $proofId): void
    {
        $auth = AuthMiddleware::check();
        if (!$auth || !in_array($auth['role'], ['admin', 'customer'], true)) {
            http_response_code(403);
            return;
        }
        $proof = $this->orders->proofPath(
            $proofId,
            (int) $auth['id'],
            $auth['role'] === 'admin'
        );
        if (!$proof) {
            http_response_code(404);
            return;
        }
        $relative = str_replace('/storage/uploads/', '', (string) $proof['image_path']);
        $file = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
        $root = realpath(UPLOAD_PATH);
        if (
            $file === false
            || $root === false
            || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)
            || !str_starts_with($relative, 'private/payment-proofs/')
        ) {
            http_response_code(404);
            return;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(415);
            return;
        }
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: inline');
        readfile($file);
    }

    public function qrisImage(): void
    {
        $auth = AuthMiddleware::check();
        if (!$auth || !in_array($auth['role'], ['admin', 'customer'], true)) {
            http_response_code(403);
            return;
        }
        $store = $this->orders->storeSettings();
        $relative = str_replace('/storage/uploads/', '', (string) ($store['qris_image_path'] ?? ''));
        if ($relative === '' || !str_starts_with($relative, 'settings/')) {
            http_response_code(404);
            return;
        }
        $file = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
        $root = realpath(UPLOAD_PATH);
        if ($file === false || $root === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(415);
            return;
        }
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: inline');
        readfile($file);
    }

    private function removeUploadedFile(?string $path): void
    {
        if ($path === null || !str_starts_with($path, '/storage/uploads/')) {
            return;
        }
        $relative = substr($path, strlen('/storage/uploads/'));
        $file = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
