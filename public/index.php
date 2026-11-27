<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';

session_start();

require dirname(__DIR__) . '/app/helpers/functions.php';
require dirname(__DIR__) . '/app/core/Database.php';
require dirname(__DIR__) . '/app/core/Model.php';
require dirname(__DIR__) . '/app/core/Controller.php';
require dirname(__DIR__) . '/app/core/App.php';
require dirname(__DIR__) . '/app/helpers/AuthMiddleware.php';
require dirname(__DIR__) . '/app/models/UserModel.php';
require dirname(__DIR__) . '/app/controllers/AuthController.php';
require dirname(__DIR__) . '/app/models/CatalogModel.php';
require dirname(__DIR__) . '/app/controllers/AdminCatalogController.php';
require dirname(__DIR__) . '/app/controllers/CatalogController.php';
require dirname(__DIR__) . '/app/models/CartModel.php';
require dirname(__DIR__) . '/app/models/CheckoutModel.php';
require dirname(__DIR__) . '/app/controllers/CartController.php';
require dirname(__DIR__) . '/app/controllers/ProfileController.php';
require dirname(__DIR__) . '/app/controllers/CheckoutController.php';
require dirname(__DIR__) . '/app/models/OrderModel.php';
require dirname(__DIR__) . '/app/models/NotificationModel.php';
require dirname(__DIR__) . '/app/controllers/OrderController.php';
require dirname(__DIR__) . '/app/controllers/NotificationController.php';
require dirname(__DIR__) . '/app/models/WishlistModel.php';
require dirname(__DIR__) . '/app/models/ReviewModel.php';
require dirname(__DIR__) . '/app/models/AdminModel.php';
require dirname(__DIR__) . '/app/models/DatabaseBackupModel.php';
require dirname(__DIR__) . '/app/controllers/WishlistController.php';
require dirname(__DIR__) . '/app/controllers/ReviewController.php';
require dirname(__DIR__) . '/app/controllers/AdminController.php';

$app = new App();

$app->get('/', [CatalogController::class, 'home']);
$app->get('/register', [AuthController::class, 'register']);
$app->post('/register', [AuthController::class, 'registerPost']);
$app->get('/login', [AuthController::class, 'login']);
$app->post('/login', [AuthController::class, 'loginPost']);
$app->get('/admin/login', [AuthController::class, 'adminLogin']);
$app->post('/admin/login', [AuthController::class, 'adminLoginPost']);
$app->post('/logout', [AuthController::class, 'logout']);
$app->get('/forgot-password', [AuthController::class, 'forgotPassword']);
$app->post('/forgot-password', [AuthController::class, 'forgotPasswordPost']);
$app->get('/reset-password/{token}', [AuthController::class, 'resetPassword']);
$app->post('/reset-password/{token}', [AuthController::class, 'resetPasswordPost']);
$app->get('/admin', [AuthController::class, 'adminHome']);
$app->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$app->get('/products', [CatalogController::class, 'index']);
$app->get('/products/detail/{slug}', [CatalogController::class, 'detail']);

$app->get('/admin/categories', [AdminCatalogController::class, 'categories']);
$app->post('/admin/categories', [AdminCatalogController::class, 'categoryStore']);
$app->post('/admin/categories/update/{id}', [AdminCatalogController::class, 'categoryUpdate']);
$app->post('/admin/categories/delete/{id}', [AdminCatalogController::class, 'categoryDelete']);
$app->get('/admin/products', [AdminCatalogController::class, 'products']);
$app->get('/admin/products/create', [AdminCatalogController::class, 'productCreate']);
$app->post('/admin/products/create', [AdminCatalogController::class, 'productStore']);
$app->get('/admin/products/edit/{id}', [AdminCatalogController::class, 'productEdit']);
$app->post('/admin/products/edit/{id}', [AdminCatalogController::class, 'productUpdate']);
$app->post('/admin/products/delete/{id}', [AdminCatalogController::class, 'productDelete']);
$app->post('/admin/products/{productId}/images/{imageId}/delete', [AdminCatalogController::class, 'productImageDelete']);
$app->post('/admin/products/{productId}/variants', [AdminCatalogController::class, 'variantStore']);
$app->post('/admin/products/{productId}/variants/{variantId}/update', [AdminCatalogController::class, 'variantUpdate']);
$app->post('/admin/products/{productId}/variants/{variantId}/delete', [AdminCatalogController::class, 'variantDelete']);
$app->get('/admin/stocks', [AdminCatalogController::class, 'stocks']);
$app->post('/admin/stocks/adjust', [AdminCatalogController::class, 'stockAdjust']);

$app->get('/cart', [CartController::class, 'index']);
$app->post('/cart/add', [CartController::class, 'add']);
$app->post('/cart/quantity', [CartController::class, 'updateQuantity']);
$app->post('/cart/select', [CartController::class, 'select']);
$app->post('/cart/remove', [CartController::class, 'remove']);
$app->get('/profile', [ProfileController::class, 'index']);
$app->post('/profile', [ProfileController::class, 'update']);
$app->get('/checkout', [CheckoutController::class, 'index']);
$app->post('/checkout/voucher', [CheckoutController::class, 'voucherValidate']);
$app->post('/checkout', [CheckoutController::class, 'confirm']);
$app->get('/checkout/complete/{invoice}', [CheckoutController::class, 'complete']);
$app->get('/admin/settings/zones', [CheckoutController::class, 'zones']);
$app->post('/admin/settings/zones', [CheckoutController::class, 'zoneSave']);
$app->post('/admin/settings/zones/delete/{id}', [CheckoutController::class, 'zoneDelete']);
$app->get('/admin/vouchers', [CheckoutController::class, 'vouchers']);
$app->post('/admin/vouchers', [CheckoutController::class, 'voucherSave']);
$app->post('/admin/vouchers/delete/{id}', [CheckoutController::class, 'voucherDelete']);
$app->get('/orders', [OrderController::class, 'index']);
$app->get('/orders/detail/{invoice}', [OrderController::class, 'detail']);
$app->get('/orders/payment/{invoice}', [OrderController::class, 'payment']);
$app->post('/orders/payment/{invoice}', [OrderController::class, 'uploadProof']);
$app->post('/orders/cancel', [OrderController::class, 'cancel']);
$app->get('/admin/orders', [OrderController::class, 'adminIndex']);
$app->get('/admin/orders/detail/{invoice}', [OrderController::class, 'adminDetail']);
$app->post('/admin/orders/status', [OrderController::class, 'updateStatus']);
$app->post('/admin/orders/payment/verify', [OrderController::class, 'verifyPayment']);
$app->post('/admin/orders/payment/cod-paid', [OrderController::class, 'markCodPaid']);
$app->post('/admin/orders/qris', [OrderController::class, 'saveQrisImage']);
$app->get('/payment-proofs/{proofId}', [OrderController::class, 'proofImage']);
$app->get('/store/qris', [OrderController::class, 'qrisImage']);
$app->get('/notifications', [NotificationController::class, 'index']);
$app->get('/notifications/open/{id}', [NotificationController::class, 'open']);
$app->get('/wishlist', [WishlistController::class, 'index']);
$app->post('/wishlist/add', [WishlistController::class, 'add']);
$app->post('/wishlist/remove', [WishlistController::class, 'remove']);
$app->post('/wishlist/move', [WishlistController::class, 'moveToCart']);
$app->post('/reviews', [ReviewController::class, 'submit']);
$app->get('/admin/reviews', [ReviewController::class, 'adminIndex']);
$app->post('/admin/reviews/{id}/status', [ReviewController::class, 'updateStatus']);
$app->get('/admin/customers', [AdminController::class, 'customers']);
$app->get('/admin/reports', [AdminController::class, 'reports']);
$app->get('/admin/settings', [AdminController::class, 'settings']);
$app->post('/admin/settings', [AdminController::class, 'saveSettings']);
$app->get('/admin/password', [AdminController::class, 'password']);
$app->post('/admin/password', [AdminController::class, 'changePassword']);
$app->get('/admin/backup', [AdminController::class, 'backup']);
$app->post('/admin/backup', [AdminController::class, 'downloadBackup']);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uploadPrefix = '/storage/uploads/';
$uploadPosition = strpos($requestPath, $uploadPrefix);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $uploadPosition !== false) {
    $relativePath = substr($requestPath, $uploadPosition + strlen($uploadPrefix));
    if (!str_starts_with($relativePath, 'products/')) {
        http_response_code(404);
        exit;
    }
    $file = realpath(UPLOAD_PATH . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
    $uploadRoot = realpath(UPLOAD_PATH);
    if ($file !== false && $uploadRoot !== false && str_starts_with($file, $uploadRoot . DIRECTORY_SEPARATOR)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($file);
            exit;
        }
    }
    http_response_code(404);
    exit;
}

$app->run();
