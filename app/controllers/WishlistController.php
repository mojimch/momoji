<?php

class WishlistController extends Controller
{
    private WishlistModel $wishlist;
    private CartModel $cart;

    public function __construct()
    {
        $this->wishlist = new WishlistModel();
        $this->cart = new CartModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireCustomer();
        $this->view('customer/wishlist/index', [
            'products' => $this->wishlist->forUser((int) AuthMiddleware::check()['id']),
        ], 'customer_layout');
    }

    public function add(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $userId = (int) AuthMiddleware::check()['id'];
        $productId = (int) ($_POST['product_id'] ?? 0);
        $added = $this->wishlist->add($userId, $productId);
        if ($added || $this->wishlist->contains($userId, $productId)) {
            flash('success', $added ? 'Produk ditambahkan ke wishlist.' : 'Produk sudah ada di wishlist.');
        } else {
            flash('error', 'Produk tidak tersedia untuk ditambahkan ke wishlist.');
        }
        $returnTo = (string) ($_POST['redirect'] ?? '/wishlist');
        $this->redirect(
            str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//')
                ? $returnTo
                : '/wishlist'
        );
    }

    public function remove(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $this->wishlist->remove(
            (int) AuthMiddleware::check()['id'],
            (int) ($_POST['product_id'] ?? 0)
        );
        flash('success', 'Produk dihapus dari wishlist.');
        $this->redirect('/wishlist');
    }

    public function moveToCart(): void
    {
        AuthMiddleware::requireCustomer();
        $this->validatePost();
        $userId = (int) AuthMiddleware::check()['id'];
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = (int) ($_POST['variant_id'] ?? 0) ?: null;
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($this->cart->add($userId, $productId, $variantId, $quantity)) {
            $this->wishlist->remove($userId, $productId);
            flash('success', 'Produk dipindahkan ke keranjang.');
            $this->redirect('/cart');
        }
        flash('error', 'Stok tidak mencukupi atau varian tidak sesuai. Wishlist tidak diubah.');
        $this->redirect('/wishlist');
    }

    private function validatePost(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/wishlist');
        }
    }
}
