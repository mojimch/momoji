<?php

class ProfileController extends Controller
{
    private CheckoutModel $checkout;
    private UserModel $users;

    public function __construct()
    {
        $this->checkout = new CheckoutModel();
        $this->users = new UserModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireCustomer();
        $auth = AuthMiddleware::check();
        $this->view('customer/profile/index', [
            'user' => $this->users->findById((int) $auth['id']),
            'address' => $this->checkout->address((int) $auth['id']),
            'zones' => $this->checkout->shippingZones(),
        ], 'customer_layout');
    }

    public function update(): void
    {
        AuthMiddleware::requireCustomer();
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/profile');
        }

        $userId = (int) AuthMiddleware::check()['id'];
        $recipientName = trim((string) ($_POST['recipient_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $addressLine = trim((string) ($_POST['address_line'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $postalCode = trim((string) ($_POST['postal_code'] ?? ''));
        $zoneId = (int) ($_POST['shipping_zone_id'] ?? 0);

        if ($name === '' || $recipientName === '' || $phone === '' || $addressLine === '' || $city === '' || $zoneId <= 0) {
            flash('error', 'Nama, nama penerima, telepon, alamat, kota, dan zona pengiriman wajib diisi.');
            $this->redirect('/profile');
        }

        $this->users->updateProfile($userId, $name, $phone);
        $_SESSION['auth']['name'] = $name;
        $this->checkout->saveAddress($userId, [
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'address_line' => $addressLine,
            'city' => $city,
            'postal_code' => $postalCode,
            'shipping_zone_id' => $zoneId,
        ]);
        flash('success', 'Profil dan alamat berhasil diperbarui.');
        $this->redirect('/profile');
    }
}
