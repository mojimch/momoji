<?php

class CatalogController extends Controller
{
    private CatalogModel $catalog;

    public function __construct()
    {
        $this->catalog = new CatalogModel();
    }

    public function home(): void
    {
        $featured = $this->catalog->products(['sort' => 'name'], 1, 8);
        $latest = $this->catalog->products(['sort' => 'name'], 1, 8);
        $this->view('customer/home', [
            'categories' => $this->catalog->categories(),
            'featured' => $featured['items'],
            'latest' => $latest['items'],
            'bestsellers' => array_values(array_filter(
                $featured['items'],
                fn(array $product): bool => $product['label'] === 'bestseller'
            )),
        ], 'customer_layout');
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'min' => $_GET['min'] ?? '',
            'max' => $_GET['max'] ?? '',
            'promo' => isset($_GET['promo']) ? 1 : 0,
            'sort' => (string) ($_GET['sort'] ?? 'latest'),
        ];
        $products = $this->catalog->products($filters, $page, 12);
        $this->view('customer/products/index', [
            'categories' => $this->catalog->categories(),
            'products' => $products,
            'filters' => $filters,
        ], 'customer_layout');
    }

    public function detail(string $slug): void
    {
        $product = $this->catalog->productBySlug(rawurldecode($slug));
        if (!$product) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $auth = AuthMiddleware::check();
        $isCustomer = $auth && ($auth['role'] ?? '') === 'customer';
        $wishlist = new WishlistModel();
        $reviews = new ReviewModel();
        $this->view('customer/products/detail', [
            'product' => $product,
            'images' => $this->catalog->images((int) $product['id']),
            'variants' => $this->catalog->variants((int) $product['id']),
            'auth' => $auth,
            'isCustomer' => $isCustomer,
            'isWishlisted' => $isCustomer
                ? $wishlist->contains((int) $auth['id'], (int) $product['id'])
                : false,
            'reviews' => $reviews->approvedForProduct((int) $product['id']),
            'reviewSummary' => $reviews->summaryForProduct((int) $product['id']),
        ], 'customer_layout');
    }
}
