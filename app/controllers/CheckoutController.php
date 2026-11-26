<?php

class CheckoutController extends Controller
{
    private CartModel $cart;
    private CheckoutModel $checkout;

    public function __construct()
    {
        $this->cart = new CartModel();
        $this->checkout = new CheckoutModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireCustomer();
        $userId = (int) AuthMiddleware::check()['id'];
        $items = $this->cart->selectedItems($userId);
        if ($items === []) {
            flash('error', 'Pilih minimal satu produk untuk checkout.');
            $this->redirect('/cart');
        }
        $this->view('customer/checkout/index', [
            'items' => $items,
            'address' => $this->checkout->address($userId),
            'zones' => $this->checkout->shippingZones(),
        ], 'customer_layout');
    }

    public function voucherValidate(): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode(['valid' => false, 'message' => 'Sesi tidak valid.']);
            return;
        }
        $userId = (int) AuthMiddleware::check()['id'];
        $subtotal = 0.0;
        foreach ($this->cart->selectedItems($userId) as $item) {
            $price = (float) ($item['promo_price'] ?: $item['price']);
            $price += (float) ($item['additional_price'] ?: 0);
            $subtotal += $price * (int) $item['quantity'];
        }
        header('Content-Type: application/json');
        echo json_encode($this->checkout->validateVoucher((string) ($_POST['code'] ?? ''), $subtotal));
    }

    public function confirm(): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/checkout');
        }

        $userId = (int) AuthMiddleware::check()['id'];
        $items = $this->cart->selectedItems($userId);
        if ($items === []) {
            flash('error', 'Produk checkout tidak ditemukan.');
            $this->redirect('/cart');
        }

        $recipientName = trim((string) ($_POST['recipient_name'] ?? ''));
        $recipientPhone = trim((string) ($_POST['recipient_phone'] ?? ''));
        $addressLine = trim((string) ($_POST['address_line'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $shippingMethod = (string) ($_POST['shipping_method'] ?? '');
        $paymentMethod = (string) ($_POST['payment_method'] ?? '');

        if (
            $recipientName === ''
            || $recipientPhone === ''
            || $addressLine === ''
            || $city === ''
            || !in_array($shippingMethod, ['pickup', 'courier'], true)
            || !in_array($paymentMethod, ['COD', 'QRIS'], true)
        ) {
            flash('error', 'Lengkapi data penerima dan alamat.');
            $this->redirect('/checkout');
        }
        if ($shippingMethod === 'courier' && (int) ($_POST['shipping_zone_id'] ?? 0) <= 0) {
            flash('error', 'Pilih zona pengiriman untuk Kurir Momoji.');
            $this->redirect('/checkout');
        }

        try {
            $result = $this->checkout->createOrder($userId, $items, [
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'address_line' => $addressLine . ', ' . $city,
                'shipping_method' => $shippingMethod,
                'shipping_zone_id' => (int) ($_POST['shipping_zone_id'] ?? 0),
                'voucher_code' => (string) ($_POST['voucher_code'] ?? ''),
                'payment_method' => $paymentMethod,
                'notes' => (string) ($_POST['notes'] ?? ''),
            ]);
            flash('success', 'Pesanan berhasil dibuat.');
            $this->redirect('/checkout/complete/' . rawurlencode($result['invoice']));
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            $this->redirect('/checkout');
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Pesanan tidak dapat disimpan karena terjadi kesalahan database. Silakan coba lagi.');
            $this->redirect('/checkout');
        }
    }

    public function complete(string $invoice): void
    {
        AuthMiddleware::requireCustomer();
        $order = $this->checkout->orderSummary(
            (int) AuthMiddleware::check()['id'],
            rawurldecode($invoice)
        );
        if (!$order) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $this->view('customer/checkout/complete', ['order' => $order], 'customer_layout');
    }

    public function zones(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/settings/zones', [
            'zones' => $this->checkout->zonesAdmin(),
        ], 'admin_layout');
    }

    public function zoneSave(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/settings/zones');
        }
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        try {
            $this->checkout->zoneSave([
                'zone_name' => (string) ($_POST['zone_name'] ?? ''),
                'description' => (string) ($_POST['description'] ?? ''),
                'cost' => (string) ($_POST['cost'] ?? '0'),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], $id);
            flash('success', 'Zona pengiriman berhasil disimpan.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Zona tidak dapat disimpan. Periksa nama dan data ongkir.');
        }
        $this->redirect('/admin/settings/zones');
    }

    public function zoneDelete(int $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            $this->redirect('/admin/settings/zones');
        }
        try {
            $this->checkout->zoneDelete($id);
            flash('success', 'Zona pengiriman berhasil dihapus.');
        } catch (PDOException $exception) {
            flash('error', 'Zona tidak dapat dihapus karena masih digunakan.');
        }
        $this->redirect('/admin/settings/zones');
    }

    public function vouchers(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/vouchers/index', [
            'vouchers' => $this->checkout->voucherAdmin(),
        ], 'admin_layout');
    }

    public function voucherSave(): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/admin/vouchers');
        }
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        try {
            $this->checkout->voucherSave([
                'code' => (string) ($_POST['code'] ?? ''),
                'description' => (string) ($_POST['description'] ?? ''),
                'discount_type' => (string) ($_POST['discount_type'] ?? 'fixed'),
                'discount_value' => (string) ($_POST['discount_value'] ?? '0'),
                'minimum_purchase' => (string) ($_POST['minimum_purchase'] ?? '0'),
                'max_discount' => (string) ($_POST['max_discount'] ?? ''),
                'quota' => (string) ($_POST['quota'] ?? ''),
                'start_at' => str_replace('T', ' ', (string) ($_POST['start_at'] ?? '')),
                'end_at' => str_replace('T', ' ', (string) ($_POST['end_at'] ?? '')),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], $id);
            flash('success', 'Voucher berhasil disimpan.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->__toString());
            flash('error', 'Voucher tidak dapat disimpan. Pastikan kode unik dan data valid.');
        }
        $this->redirect('/admin/vouchers');
    }

    public function voucherDelete(int $id): void
    {
        AuthMiddleware::requireAdmin();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            $this->redirect('/admin/vouchers');
        }
        try {
            $this->checkout->voucherDelete($id);
            flash('success', 'Voucher berhasil dihapus.');
        } catch (PDOException $exception) {
            flash('error', 'Voucher tidak dapat dihapus karena sudah dipakai. Nonaktifkan voucher tersebut.');
        }
        $this->redirect('/admin/vouchers');
    }
}
