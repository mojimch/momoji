<?php

class CartController extends Controller
{
    private CartModel $cart;

    public function __construct()
    {
        $this->cart = new CartModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireCustomer();
        $userId = (int) AuthMiddleware::check()['id'];
        $this->view('customer/cart/index', [
            'items' => $this->cart->itemsForUser($userId),
        ], 'customer_layout');
    }

    public function add(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $success = $this->cart->add(
            (int) AuthMiddleware::check()['id'],
            (int) ($_POST['product_id'] ?? 0),
            (int) ($_POST['variant_id'] ?? 0) ?: null,
            (int) ($_POST['quantity'] ?? 1)
        );
        flash($success ? 'success' : 'error', $success ? 'Produk ditambahkan ke keranjang.' : 'Stok produk tidak mencukupi.');
        $returnTo = (string) ($_POST['redirect'] ?? '/cart');
        $this->redirect(str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//') ? $returnTo : '/cart');
    }

    public function updateQuantity(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $success = $this->cart->updateQuantity(
            (int) AuthMiddleware::check()['id'],
            (int) ($_POST['item_id'] ?? 0),
            (int) ($_POST['quantity'] ?? 0)
        );
        flash($success ? 'success' : 'error', $success ? 'Jumlah diperbarui.' : 'Jumlah melebihi stok atau tidak valid.');
        $this->redirect('/cart');
    }

    public function select(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $this->cart->setSelected(
            (int) AuthMiddleware::check()['id'],
            (int) ($_POST['item_id'] ?? 0),
            isset($_POST['selected'])
        );
        $this->redirect('/cart');
    }

    public function remove(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $this->cart->remove(
            (int) AuthMiddleware::check()['id'],
            (int) ($_POST['item_id'] ?? 0)
        );
        flash('success', 'Produk dihapus dari keranjang.');
        $this->redirect('/cart');
    }

    private function validatePost(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/cart');
        }
    }
}
