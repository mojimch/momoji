CREATE DATABASE IF NOT EXISTS momoji_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE momoji_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS payment_proofs;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS voucher_usages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customer_addresses;
DROP TABLE IF EXISTS shipping_zones;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS store_settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE shipping_zones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    zone_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_shipping_zones_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE customer_addresses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address_line TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NULL,
    shipping_zone_id INT UNSIGNED NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_addresses_user_primary (user_id, is_primary),
    INDEX idx_customer_addresses_zone (shipping_zone_id),
    CONSTRAINT fk_customer_addresses_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_customer_addresses_zone FOREIGN KEY (shipping_zone_id)
        REFERENCES shipping_zones(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token_hash (token_hash),
    INDEX idx_password_reset_user (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    icon VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    INDEX idx_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    promo_price DECIMAL(12,2) NULL,
    stock INT NOT NULL DEFAULT 0,
    weight_grams INT UNSIGNED NOT NULL DEFAULT 0,
    label ENUM('none','new','promo','bestseller') NOT NULL DEFAULT 'none',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    INDEX idx_products_category (category_id),
    INDEX idx_products_name (name),
    INDEX idx_products_label_active (label, is_active),
    INDEX idx_products_price (price),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_product_images_product (product_id),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    additional_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_product_variants_product (product_id),
    INDEX idx_product_variants_name (variant_name),
    CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE carts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carts_user (user_id),
    INDEX idx_carts_session (session_id),
    CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    selected TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cart_items_cart_product_variant (cart_id, product_id, variant_id),
    INDEX idx_cart_items_product (product_id),
    INDEX idx_cart_items_variant (variant_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
        REFERENCES carts(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id)
        REFERENCES product_variants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wishlists_user_product (user_id, product_id),
    INDEX idx_wishlists_product (product_id),
    CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vouchers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    discount_type ENUM('percentage','fixed','free_shipping') NOT NULL,
    discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    minimum_purchase DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    max_discount DECIMAL(12,2) NULL,
    quota INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vouchers_code (code),
    INDEX idx_vouchers_active_period (is_active, start_at, end_at)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(40) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    shipping_zone_id INT UNSIGNED NULL,
    recipient_name VARCHAR(150) NOT NULL,
    recipient_phone VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_method ENUM('pickup','courier') NOT NULL,
    shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    voucher_code VARCHAR(50) NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'Menunggu konfirmasi',
        'Dikonfirmasi',
        'Menunggu pembayaran',
        'Diproses',
        'Dikemas',
        'Siap diambil',
        'Sedang dikirim',
        'Selesai',
        'Dibatalkan'
    ) NOT NULL DEFAULT 'Menunggu konfirmasi',
    payment_status ENUM(
        'Belum dibayar',
        'Menunggu verifikasi',
        'Dibayar',
        'Gagal / ditolak',
        'Dikembalikan'
    ) NOT NULL DEFAULT 'Belum dibayar',
    payment_method ENUM('COD','QRIS') NOT NULL,
    notes TEXT NULL,
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_invoice (invoice_number),
    INDEX idx_orders_user_status (user_id, status),
    INDEX idx_orders_payment_status (payment_status),
    INDEX idx_orders_created_at (created_at),
    INDEX idx_orders_shipping_zone (shipping_zone_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_orders_shipping_zone FOREIGN KEY (shipping_zone_id)
        REFERENCES shipping_zones(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    variant_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL,
    variant_name VARCHAR(100) NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    weight_grams INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id),
    INDEX idx_order_items_variant (variant_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id)
        REFERENCES product_variants(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE voucher_usages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    voucher_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voucher_usages_order (order_id),
    INDEX idx_voucher_usages_voucher (voucher_id),
    INDEX idx_voucher_usages_user (user_id),
    CONSTRAINT fk_voucher_usages_voucher FOREIGN KEY (voucher_id)
        REFERENCES vouchers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_voucher_usages_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_voucher_usages_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method ENUM('COD','QRIS') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Belum dibayar','Menunggu verifikasi','Dibayar','Gagal / ditolak','Dikembalikan') NOT NULL DEFAULT 'Belum dibayar',
    admin_note TEXT NULL,
    verified_by BIGINT UNSIGNED NULL,
    verified_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_order (order_id),
    INDEX idx_payments_status (status),
    INDEX idx_payments_verified_by (verified_by),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payment_proofs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_payment_proofs_payment (payment_id),
    CONSTRAINT fk_payment_proofs_payment FOREIGN KEY (payment_id)
        REFERENCES payments(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NULL,
    variant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    movement_type ENUM('in','out','adjustment','sale','cancel','reservation','release') NOT NULL,
    quantity_change INT NOT NULL,
    stock_before INT NOT NULL,
    stock_after INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_stock_movements_product (product_id),
    INDEX idx_stock_movements_variant (variant_id),
    INDEX idx_stock_movements_user (user_id),
    INDEX idx_stock_movements_created (created_at),
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_stock_movements_variant FOREIGN KEY (variant_id)
        REFERENCES product_variants(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_order_item (order_item_id),
    INDEX idx_reviews_product_status (product_id, status),
    INDEX idx_reviews_user (user_id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_order_item FOREIGN KEY (order_item_id)
        REFERENCES order_items(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'general',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    PRIMARY KEY (id),
    INDEX idx_notifications_user_read (user_id, is_read),
    INDEX idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE store_settings (
    id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    store_name VARCHAR(150) NOT NULL DEFAULT 'Momoji',
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    pickup_address TEXT NULL,
    qris_image_path VARCHAR(255) NULL,
    low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT chk_store_settings_singleton CHECK (id = 1)
) ENGINE=InnoDB;

-- Data awal aplikasi
INSERT INTO users (name, email, phone, password, role, status)
VALUES (
    'Administrator Momoji',
    'admin@momoji.id',
    '081200000000',
    '$2y$12$RRkEoKV2asxGXA5E728SaO4zbkKb1Qmhp2SFg.xV/AtlMOINetn16',
    'admin',
    'active'
);

INSERT INTO shipping_zones (zone_name, description, cost, is_active) VALUES
    ('Dalam Kota', 'Pengiriman di wilayah kota Momoji', 15000.00, 1),
    ('Luar Kota', 'Pengiriman ke wilayah luar kota', 28000.00, 1);

INSERT INTO categories (name, slug, icon) VALUES
    ('Kopi', 'kopi', 'bi-cup-hot'),
    ('Makanan Ringan', 'makanan-ringan', 'bi-cookie'),
    ('Merchandise', 'merchandise', 'bi-bag');

INSERT INTO store_settings (
    id, store_name, phone, email, pickup_address, qris_image_path, low_stock_threshold
) VALUES (
    1,
    'Momoji',
    '081200000000',
    'hello@momoji.id',
    'Alamat pickup Momoji belum dikonfigurasi',
    NULL,
    5
);

INSERT INTO products (
    category_id, name, slug, description, price, promo_price, stock,
    weight_grams, label, is_active
) VALUES
    (1, 'Momoji Arabica', 'momoji-arabica', 'Biji kopi Arabica pilihan dengan cita rasa cokelat dan karamel.', 65000.00, NULL, 30, 250, 'new', 1),
    (1, 'Momoji Robusta', 'momoji-robusta', 'Biji kopi Robusta dengan body kuat dan aroma gurih.', 55000.00, 50000.00, 25, 250, 'promo', 1),
    (2, 'Momoji Cookies', 'momoji-cookies', 'Cookies renyah dengan rasa cokelat premium.', 35000.00, NULL, 40, 150, 'bestseller', 1),
    (2, 'Momoji Granola', 'momoji-granola', 'Granola gurih-manis untuk sarapan dan camilan sehat.', 48000.00, 45000.00, 20, 200, 'promo', 1),
    (3, 'Tumbler Momoji', 'tumbler-momoji', 'Tumbler eksklusif Momoji dengan tutup kedap.', 85000.00, NULL, 15, 350, 'none', 1);

INSERT INTO product_images (product_id, image_path, is_primary) VALUES
    (1, '/assets/images/placeholder-product.svg', 1),
    (2, '/assets/images/placeholder-product.svg', 1),
    (3, '/assets/images/placeholder-product.svg', 1),
    (4, '/assets/images/placeholder-product.svg', 1),
    (5, '/assets/images/placeholder-product.svg', 1);

INSERT INTO product_variants (product_id, variant_name, additional_price, stock) VALUES
    (1, 'Biji Utuh', 0.00, 15),
    (1, 'Giling Halus', 0.00, 15),
    (2, 'Biji Utuh', 0.00, 12),
    (2, 'Giling Halus', 0.00, 13),
    (3, 'Cokelat', 0.00, 20),
    (3, 'Kacang', 3000.00, 20);

INSERT INTO vouchers (
    code, description, discount_type, discount_value, minimum_purchase,
    max_discount, quota, used_count, start_at, end_at, is_active
) VALUES (
    'MOMOJI10',
    'Diskon 10 persen untuk pembelian pertama',
    'percentage',
    10.00,
    50000.00,
    20000.00,
    100,
    0,
    '2026-01-01 00:00:00',
    '2027-12-31 23:59:59',
    1
);
