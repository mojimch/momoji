<?php

class AdminCatalogController extends Controller
{
    private CatalogModel $catalog;

    public function __construct()
    {
        $this->catalog = new CatalogModel();
    }

    public function categories(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/categories/index', [
            'categories' => $this->catalog->categories(),
            'auth' => AuthMiddleware::check(),
        ], 'admin_layout');
    }

    public function categoryStore(): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Nama kategori wajib diisi.');
            $this->redirect('/admin/categories');
        }
        try {
            $this->catalog->createCategory($name, (string) ($_POST['icon'] ?? ''));
            flash('success', 'Kategori berhasil ditambahkan.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        $this->redirect('/admin/categories');
    }

    public function categoryUpdate(int $id): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Nama kategori wajib diisi.');
            $this->redirect('/admin/categories');
        }
        try {
            $this->catalog->updateCategory($id, $name, (string) ($_POST['icon'] ?? ''));
            flash('success', 'Kategori berhasil diperbarui.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        $this->redirect('/admin/categories');
    }

    public function categoryDelete(int $id): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $this->catalog->deleteCategory($id);
        flash('success', 'Kategori berhasil dihapus.');
        $this->redirect('/admin/categories');
    }

    public function products(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/products/index', [
            'products' => $this->catalog->adminProducts(),
        ], 'admin_layout');
    }

    public function productCreate(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/products/form', [
            'product' => null,
            'categories' => $this->catalog->categories(),
        ], 'admin_layout');
    }

    public function productStore(): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $data = $this->productData();
        if (
            $data['name'] === ''
            || $data['price'] < 0
            || $data['stock'] < 0
            || $data['weight_grams'] < 0
            || ($data['promo_price'] !== '' && (float) $data['promo_price'] < 0)
        ) {
            flash('error', 'Nama produk dan nilai stok/harga/berat tidak boleh kosong atau negatif.');
            $this->redirect('/admin/products/create');
        }

        try {
            $productId = $this->catalog->createProduct($data);
            $this->storeImages($productId);
            flash('success', 'Produk berhasil ditambahkan.');
            $this->redirect('/admin/products/edit/' . $productId);
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
            $this->redirect('/admin/products/create');
        }
    }

    public function productEdit(int $id): void
    {
        AuthMiddleware::requireAdmin();
        $product = $this->catalog->product($id);
        if (!$product) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }
        $this->view('admin/products/form', [
            'product' => $product,
            'categories' => $this->catalog->categories(),
            'images' => $this->catalog->images($id),
            'variants' => $this->catalog->variants($id),
        ], 'admin_layout');
    }

    public function productUpdate(int $id): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $data = $this->productData();
        if (
            $data['name'] === ''
            || $data['price'] < 0
            || $data['stock'] < 0
            || $data['weight_grams'] < 0
            || ($data['promo_price'] !== '' && (float) $data['promo_price'] < 0)
        ) {
            flash('error', 'Nama produk dan nilai stok/harga/berat tidak boleh kosong atau negatif.');
            $this->redirect('/admin/products/edit/' . $id);
        }

        try {
            $this->catalog->updateProduct($id, $data);
            $this->storeImages($id);
            flash('success', 'Produk berhasil diperbarui.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        $this->redirect('/admin/products/edit/' . $id);
    }

    public function productDelete(int $id): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $this->catalog->deleteProduct($id);
        flash('success', 'Produk berhasil dihapus.');
        $this->redirect('/admin/products');
    }

    public function productImageDelete(int $productId, int $imageId): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $this->catalog->deleteImage($imageId, $productId);
        flash('success', 'Gambar berhasil dihapus.');
        $this->redirect('/admin/products/edit/' . $productId);
    }

    public function variantStore(int $productId): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $name = trim((string) ($_POST['variant_name'] ?? ''));
        $stock = (int) ($_POST['stock'] ?? -1);
        $additionalPrice = (float) ($_POST['additional_price'] ?? -1);
        if ($name === '' || $stock < 0 || $additionalPrice < 0) {
            flash('error', 'Nama varian, stok, dan harga tambahan wajib valid.');
            $this->redirect('/admin/products/edit/' . $productId);
        }
        $this->catalog->saveVariant($productId, [
            'variant_name' => $name,
            'additional_price' => $additionalPrice,
            'stock' => $stock,
        ]);
        flash('success', 'Varian berhasil ditambahkan.');
        $this->redirect('/admin/products/edit/' . $productId);
    }

    public function variantUpdate(int $productId, int $variantId): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $this->catalog->saveVariant($productId, [
            'variant_name' => (string) ($_POST['variant_name'] ?? ''),
            'additional_price' => (float) ($_POST['additional_price'] ?? 0),
            'stock' => (int) ($_POST['stock'] ?? 0),
        ], $variantId);
        flash('success', 'Varian berhasil diperbarui.');
        $this->redirect('/admin/products/edit/' . $productId);
    }

    public function variantDelete(int $productId, int $variantId): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $this->catalog->deleteVariant($variantId, $productId);
        flash('success', 'Varian berhasil dihapus.');
        $this->redirect('/admin/products/edit/' . $productId);
    }

    public function stocks(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('admin/stocks/index', [
            'products' => $this->catalog->adminProducts(),
            'movements' => $this->catalog->stockMovements(),
        ], 'admin_layout');
    }

    public function stockAdjust(): void
    {
        AuthMiddleware::requireAdmin();
        $this->validatePost();
        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $quantityChange = (int) ($_POST['quantity_change'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($productId <= 0 || $quantityChange === 0 || $reason === '') {
            flash('error', 'Produk, perubahan stok, dan alasan wajib diisi.');
            $this->redirect('/admin/stocks');
        }

        $success = $this->catalog->adjustStock(
            $productId,
            $variantId ?: null,
            $quantityChange,
            $reason,
            (int) AuthMiddleware::check()['id']
        );
        flash($success ? 'success' : 'error', $success ? 'Stok berhasil disesuaikan.' : 'Stok tidak boleh menjadi negatif.');
        $this->redirect('/admin/stocks');
    }

    private function validatePost(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            flash('error', 'Sesi formulir tidak valid.');
            $this->redirect('/');
        }
    }

    private function productData(): array
    {
        return [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => (float) ($_POST['price'] ?? -1),
            'promo_price' => (string) ($_POST['promo_price'] ?? ''),
            'stock' => (int) ($_POST['stock'] ?? -1),
            'weight_grams' => (int) ($_POST['weight_grams'] ?? -1),
            'label' => (string) ($_POST['label'] ?? 'none'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function storeImages(int $productId): void
    {
        if (empty($_FILES['images']['name'][0])) {
            return;
        }

        $names = $_FILES['images']['name'];
        $types = $_FILES['images']['type'];
        $tmpNames = $_FILES['images']['tmp_name'];
        $errors = $_FILES['images']['error'];
        $sizes = $_FILES['images']['size'];

        $count = min(count($names), 10);
        for ($index = 0; $index < $count; $index++) {
            $path = safe_upload([
                'name' => $names[$index],
                'type' => $types[$index],
                'tmp_name' => $tmpNames[$index],
                'error' => $errors[$index],
                'size' => $sizes[$index],
            ], 'products');
            $this->catalog->addImage($productId, $path);
        }
    }
}
