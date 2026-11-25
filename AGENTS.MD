# AGENTS.md — Panduan Pembangunan Aplikasi Momoji (End-to-End hingga Deploy Dokploy)

Dokumen ini adalah panduan instruksi (_step-by-step prompts_) yang dirancang khusus untuk **AI Coding Agent** (seperti Cursor, Windsurf, Cline, GitHub Copilot, dll.) guna membangun aplikasi toko online **Momoji** berdasarkan spesifikasi pada `PRD.MD`, mulai dari inisialisasi lokal hingga _deployment_ menggunakan **Dokploy**.

---

## 📌 Aturan Global & Konteks Sistem (System Context)

Sebelum menjalankan prompt mana pun, pastikan AI Agent selalu mematuhi aturan dasar berikut:

1. **Tech Stack:** PHP Native (versi 8.2+), Arsitektur MVC custom, MySQL 8.0+, PDO (Prepared Statements wajib), HTML5, CSS3, Vanilla JS, dan Bootstrap 5.
2. **Keamanan Wajib:**
   - Semua query SQL wajib menggunakan PDO Prepared Statements.
   - Output HTML wajib di-escape (`htmlspecialchars`) untuk mencegah XSS.
   - Semua form POST wajib dilindungi token CSRF.
   - File sensitif (`config/`, `app/`, `storage/`) tidak boleh bisa diakses langsung dari URL publik (`public/` adalah satu-satunya Document Root).
   - Validasi ketat untuk file upload (MIME type, ekstensi, ukuran maksimal, dan rename file acak).
3. **Tema UI/UX:** Modern marketplace dengan nuansa alam, hutan, dan laut.
   - Forest Green: `#234C3B`
   - Ocean Teal: `#397C83`
   - Soft Sand: `#F3EBDD`
   - Pure White: `#FFFFFF`
   - Sage Green: `#A8B99A`
   - Warm Sand: `#D9A66B`
4. **Bahasa & Mata Uang:** Antarmuka bahasa Indonesia, format mata uang Rupiah (`Rp 10.000`).

---

## 🚀 Fase 1: Fondasi Arsitektur & Database

### Prompt 1.1 — Setup Struktur Proyek & Routing MVC

> **Instruksi ke Agent:**
> "Berdasarkan `PRD.MD` Bagian 9, buat struktur folder proyek `momoji/` dengan arsitektur PHP Native MVC yang bersih.
>
> 1. Buat direktori: `app/controllers`, `app/models`, `app/views/admin`, `app/views/customer`, `app/views/layouts`, `app/helpers`, `config`, `public/assets/css`, `public/assets/js`, `public/assets/images`, `storage/uploads`, dan `database`.
> 2. Buat file `.htaccess` di root proyek yang mengarahkan semua request ke folder `public/`, serta `.htaccess` di dalam `public/` untuk menangani _clean URL routing_ menuju `public/index.php`.
> 3. Buat router sederhana dan core class `Controller`, `Model`, dan `App` di dalam folder `app/core/` (atau `app/helpers/`).
> 4. Buat helper global untuk: CSRF token generator & validator, flash message, format Rupiah (`format_rupiah()`), escape HTML (`e()`), dan redirect.
> 5. Buat file `config/config.php` dan `config/database.php` yang mendukung pembacaan environment variables (`getenv()`) dengan fallback ke konfigurasi lokal XAMPP agar siap untuk Docker/Dokploy."

### Prompt 1.2 — Skema Database Relasional & Seeder Awal

> **Instruksi ke Agent:**
> "Berdasarkan `PRD.MD` Bagian 10, buat file SQL lengkap di `database/momoji.sql`.
>
> 1. Buat tabel-tabel berikut lengkap dengan `PRIMARY KEY`, `FOREIGN KEY` (`ON DELETE`/`ON UPDATE` yang tepat), tipe data yang presisi (`DECIMAL(12,2)` untuk harga), `INDEX` pada kolom pencarian/relasi, dan timestamp (`created_at`, `updated_at`):
>    - `users` (id, name, email, phone, password, role enum['admin','customer'], status, created_at, updated_at)
>    - `customer_addresses` (id, user_id, recipient_name, phone, address_line, city, postal_code, shipping_zone_id)
>    - `shipping_zones` (id, zone_name, description, cost, is_active)
>    - `categories` (id, name, slug, icon, created_at)
>    - `products` (id, category_id, name, slug, description, price, promo_price, stock, weight_grams, label enum['none','new','promo','bestseller'], is_active, created_at, updated_at)
>    - `product_images` (id, product_id, image_path, is_primary)
>    - `product_variants` (id, product_id, variant_name, additional_price, stock)
>    - `carts` & `cart_items`
>    - `wishlists`
>    - `vouchers` & `voucher_usages`
>    - `orders` (menyimpan nomor invoice unik, status pesanan 9 tahap sesuai PRD 6.7, status pembayaran terpisah, ongkir, diskon, total, catatan)
>    - `order_items`
>    - `payments` & `payment_proofs`
>    - `stock_movements` (pencatatan riwayat perubahan stok beserta user_id, tipe perubahan, jumlah, dan alasan)
>    - `reviews`
>    - `notifications`
>    - `store_settings` (untuk menyimpan info toko, alamat pickup, dan path gambar QRIS statis resmi)
> 2. Tambahkan data _dummy/seeder_ di bagian bawah `momoji.sql`: 1 akun Admin default (`admin@momoji.id`), 2 zona pengiriman, 3 kategori produk, 5 produk contoh beserta gambar placeholder, dan 1 voucher aktif."

---

## 🔐 Fase 2: Sistem Autentikasi & UI Base Layout

### Prompt 2.1 — Template Layout & Tema Visual Momoji

> **Instruksi ke Agent:**
> "Buat sistem _templating_ dasar di `app/views/layouts/` menggunakan Bootstrap 5 (CDN) dan custom CSS di `public/assets/css/style.css`.
>
> 1. Terapkan palet warna dari `PRD.MD` Bagian 8.1 menggunakan CSS Variables (`--forest-green: #234C3B`, `--ocean-teal: #397C83`, `--soft-sand: #F3EBDD`, `--pure-white: #FFFFFF`, `--sage-green: #A8B99A`, `--warm-sand: #D9A66B`).
> 2. Buat 2 layout utama:
>    - `customer_layout.php`: Navbar responsif dengan logo Momoji, search bar, menu katalog, badge jumlah keranjang & notifikasi, dropdown profil/login, serta footer informasi toko.
>    - `admin_layout.php`: Sidebar navigasi lengkap sesuai `PRD.MD` Bagian 8.3 (Dashboard, Produk, Kategori, Stok, Pesanan, Verifikasi Pembayaran, Pelanggan, Voucher, Ulasan, Laporan, Pengaturan Toko, Backup DB) dan topbar admin.
> 3. Pastikan desain responsif di mobile, tablet, dan desktop."

### Prompt 2.2 — Autentikasi, Session Management & Role Middleware

> **Instruksi ke Agent:**
> "Implementasikan fitur autentikasi sesuai `PRD.MD` Bagian 6.1:
>
> 1. Buat `AuthController` dan `UserModel`.
> 2. Buat halaman dan proses untuk: Registrasi Customer (`/register`), Login Customer (`/login`), Login Admin (`/admin/login`), Logout (`/logout`), serta fitur Lupa/Reset Password sederhana (berbasis token simulasi/email).
> 3. Gunakan `password_hash()` (BCRYPT) dan `password_verify()`. Terapkan regenerasi session ID (`session_regenerate_id(true)`) saat login berhasil.
> 4. Buat middleware/helper pengecekan akses (`AuthMiddleware::requireCustomer()` dan `AuthMiddleware::requireAdmin()`) sehingga customer tidak bisa mengakses URL `/admin/*` dan registrasi admin tertutup dari publik."

---

## 🛍️ Fase 3: Manajemen Katalog, Produk & Stok (Admin & Customer)

### Prompt 3.1 — CRUD Kategori, Produk & Manajemen Stok (Admin)

> **Instruksi ke Agent:**
> "Bangun modul pengelolaan produk untuk Admin sesuai `PRD.MD` Bagian 6.3 dan 6.8:
>
> 1. **Manajemen Kategori:** CRUD kategori di `/admin/categories`.
> 2. **Manajemen Produk:** CRUD produk di `/admin/products` yang mendukung upload multi-gambar (disimpan di `storage/uploads/products/` dengan validasi MIME/ukuran dan endpoint proxy/symlink aman untuk menampilkannya), pengelolaan varian produk, harga normal, harga promo, berat, serta label (Baru/Promo/Bestseller).
> 3. **Manajemen Stok:** Buat halaman `/admin/stocks` yang menampilkan daftar stok produk/varian, indikator peringatan stok menipis, form penyesuaian stok manual (tambah/kurang dengan alasan wajib), dan tabel riwayat perubahan stok (`stock_movements`)."

### Prompt 3.2 — Homepage, Katalog, Pencarian & Detail Produk (Customer)

> **Instruksi ke Agent:**
> "Bangun halaman eksplorasi produk untuk Customer sesuai `PRD.MD` Bagian 6.2 dan 6.3:
>
> 1. **Homepage (`/`):** Tampilkan hero banner promosi, daftar kategori, bagian Produk Unggulan, Produk Terbaru, Produk Terlaris, dan banner informasi promo.
> 2. **Katalog (`/products`):** Tampilkan daftar produk dengan pagination. Implementasikan pencarian berdasarkan nama dan filter berdasarkan kategori, rentang harga (min-max), popularitas, dan status promo.
> 3. **Detail Produk (`/products/detail/{slug}`):** Tampilkan galeri foto produk, deskripsi, pemilihan varian (update harga & stok dinamis via JS), informasi berat, ulasan pembeli, tombol tambah ke keranjang, dan tombol tambah ke wishlist."

---

## 🛒 Fase 4: Keranjang Belanja, Checkout & Voucher

### Prompt 4.1 — Keranjang Belanja Persisten Database

> **Instruksi ke Agent:**
> "Implementasikan fitur keranjang belanja sesuai `PRD.MD` Bagian 6.4:
>
> 1. Buat `CartController` dan `CartModel`. Keranjang harus tersimpan di tabel `carts` dan `cart_items` untuk user yang login (jika belum login, arahkan ke login saat menambah keranjang atau sinkronkan dari session).
> 2. Pada halaman `/cart`, pelanggan dapat: mencentang item tertentu yang ingin di-checkout, mengubah kuantitas (dengan validasi batas stok via AJAX/form), menghapus item, dan melihat ringkasan subtotal otomatis."

### Prompt 4.2 — Profil Alamat, Zona Pengiriman & Sistem Voucher

> **Instruksi ke Agent:**
> "Siapkan komponen pendukung checkout sesuai `PRD.MD` Bagian 6.5 dan 6.9:
>
> 1. **Admin Zona Pengiriman & Voucher:** Buat CRUD Zona Pengiriman (nama zona & ongkir) di pengaturan admin, serta CRUD Voucher (`/admin/vouchers`) yang mendukung diskon persentase, potongan tetap, dan gratis ongkir, lengkap dengan minimal belanja, masa berlaku, dan kuota pemakaian.
> 2. **Profil & Alamat Customer (`/profile`):** Buat halaman bagi customer untuk mengedit data diri dan mengelola 1 alamat utama yang terhubung ke pilihan Zona Pengiriman."

### Prompt 4.3 — Proses Checkout & Reservasi Stok Atomik

> **Instruksi ke Agent:**
> "Bangun halaman dan logika pemrosesan `/checkout` sesuai `PRD.MD` Bagian 6.5 dan 6.8:
>
> 1. Tampilkan ringkasan item terpilih dari keranjang, form alamat pengiriman, pilihan metode pengiriman (**Pickup di Toko** = Rp 0 atau **Kurir Momoji** = tarif sesuai zona), input kode voucher (validasi via AJAX ke server), catatan pesanan, dan pilihan metode pembayaran (**COD** atau **QRIS Static**).
> 2. **Logika Transaksi Server (Kritis):** Saat tombol Konfirmasi Pesanan ditekan, gunakan `PDO::beginTransaction()`. Validasi ulang harga terbaru, ketersediaan stok dengan `SELECT ... FOR UPDATE` (mencegah _race condition_ / stok minus), dan validasi ulang voucher di server.
> 3. Simpan ke tabel `orders`, `order_items`, `payments`, kurangi/reservasikan stok, catat ke `stock_movements`, hapus item dari keranjang, dan buat notifikasi untuk Admin serta Customer."

---

## 💳 Fase 5: Pembayaran (COD & QRIS), Manajemen Pesanan & Notifikasi

### Prompt 5.1 — Pembayaran QRIS Static & Riwayat Pesanan Customer

> **Instruksi ke Agent:**
> "Implementasikan alur pembayaran dan pelacakan pesanan untuk Customer sesuai `PRD.MD` Bagian 6.6 dan 6.7:
>
> 1. Jika memilih **QRIS**, arahkan customer ke halaman instruksi pembayaran (`/orders/payment/{invoice}`) yang menampilkan total tagihan, gambar QRIS resmi toko (diambil dari `store_settings`), dan form upload bukti transfer (validasi gambar ketat).
> 2. Buat halaman Riwayat Pesanan (`/orders`) dan Detail Pesanan (`/orders/detail/{invoice}`) yang menampilkan status pesanan dan status pembayaran secara terpisah.
> 3. Tambahkan tombol **Batalkan Pesanan** yang hanya aktif jika status pesanan masih _Menunggu konfirmasi_ atau _Menunggu pembayaran_. Pembatalan otomatis mengembalikan stok dan mencatatnya di `stock_movements`."

### Prompt 5.2 — Manajemen Pesanan & Verifikasi Pembayaran (Admin)

> **Instruksi ke Agent:**
> "Bangun modul operasional pesanan untuk Admin sesuai `PRD.MD` Bagian 6.6 dan 6.7:
>
> 1. Buat halaman `/admin/orders` dengan filter berdasarkan status pesanan dan status pembayaran.
> 2. Pada detail pesanan admin, sediakan panel untuk:
>    - Memverifikasi bukti pembayaran QRIS (Setujui -> status pembayaran menjadi _Dibayar_; Tolak -> status menjadi _Gagal/Ditolak_ dengan catatan).
>    - Mengubah alur status pesanan (Menunggu konfirmasi -> Dikonfirmasi -> Diproses -> Dikemas -> Siap diambil / Sedang dikirim -> Selesai).
>    - Membatalkan pesanan dengan alasan wajib (otomatis mengembalikan reservasi stok).
> 3. Setiap perubahan status harus memicu pembuatan notifikasi baru untuk customer terkait."

### Prompt 5.3 — Sistem Notifikasi Dalam Aplikasi

> **Instruksi ke Agent:**
> "Implementasikan sistem notifikasi internal sesuai `PRD.MD` Bagian 6.11:
>
> 1. Buat `NotificationModel` dan endpoint AJAX/halaman untuk menampilkan daftar notifikasi milik user/admin yang sedang login.
> 2. Tampilkan _badge_ merah berisi jumlah notifikasi yang belum dibaca (`is_read = 0`) pada navbar Customer dan topbar Admin.
> 3. Saat notifikasi diklik, tandai sebagai sudah dibaca dan arahkan user ke halaman terkait (misal: detail pesanan atau halaman verifikasi pembayaran)."

---

## 📊 Fase 6: Fitur Lanjutan, Dashboard Analitik & Backup DB

### Prompt 6.1 — Wishlist & Ulasan Produk

> **Instruksi ke Agent:**
> "Implementasikan fitur Wishlist dan Ulasan sesuai `PRD.MD` Bagian 6.10:
>
> 1. **Wishlist (`/wishlist`):** Customer dapat menambah/menghapus produk favorit dan memindahkannya ke keranjang.
> 2. **Rating & Ulasan:** Pada detail pesanan customer yang berstatus _Selesai_, tampilkan tombol beri ulasan (bintang 1-5 dan komentar) untuk setiap produk yang dibeli. Tampilkan rata-rata rating dan daftar ulasan di halaman Detail Produk, serta buat halaman moderasi ulasan di `/admin/reviews`."

### Prompt 6.2 — Dashboard Admin, Laporan Penjualan & Pengaturan Toko

> **Instruksi ke Agent:**
> "Bangun halaman utama Dashboard Admin dan Laporan sesuai `PRD.MD` Bagian 6.12:
>
> 1. **Dashboard (`/admin/dashboard`):** Tampilkan kartu statistik (Total Produk, Total Pelanggan, Total Pesanan, Total Pendapatan dari pesanan berstatus _Dibayar/Selesai_), daftar pesanan terbaru, peringatan stok menipis, daftar produk terlaris, dan grafik penjualan (harian/mingguan/bulanan) menggunakan Chart.js.
> 2. **Laporan & Manajemen Pelanggan:** Buat halaman daftar pelanggan (`/admin/customers`) dan halaman laporan transaksi (`/admin/reports`) dengan filter rentang tanggal serta fitur _export_ laporan ke CSV/Excel sederhana dan tampilan siap cetak PDF.
> 3. **Pengaturan Toko (`/admin/settings`):** Form untuk mengubah nama toko, nomor kontak, alamat pickup, dan upload gambar QRIS statis resmi toko."

### Prompt 6.3 — Fitur Backup Database dari Dashboard

> **Instruksi ke Agent:**
> "Implementasikan fitur Backup Database di `/admin/backup` sesuai `PRD.MD` Bagian 6.13:
>
> 1. Buat script PHP aman (hanya bisa diakses oleh role `admin` dan divalidasi dengan CSRF token) yang menghasilkan dump seluruh struktur tabel dan data database MySQL menjadi file `.sql`.
> 2. File `.sql` langsung diunduh (_force download_) oleh browser admin dengan format nama `backup_momoji_YYYY-MM-DD_HH-ii-ss.sql` tanpa menyimpan file dump secara permanen di folder publik."

---

## 🐳 Fase 7: Containerization & Deployment Menggunakan Dokploy

### Prompt 7.1 — Menyiapkan Dockerfile, Konfigurasi Apache & Docker Compose

> **Instruksi ke Agent:**
> "Siapkan konfigurasi _production-ready_ agar aplikasi PHP Native Momoji dapat berjalan di dalam container Docker dan di-deploy melalui **Dokploy**:
>
> 1. Buat `Dockerfile` berbasis `php:8.2-apache`:
>    - Install ekstensi PHP yang dibutuhkan: `pdo`, `pdo_mysql`, `mysqli`, `gd` (untuk pengolahan gambar), dan `zip`.
>    - Aktifkan modul Apache `rewrite` dan `headers`.
>    - Konfigurasikan VirtualHost Apache (`docker/apache-vhost.conf`) agar `DocumentRoot` mengarah langsung ke `/var/www/html/public` dengan `AllowOverride All`, sehingga folder `app/`, `config/`, dan `storage/` terlindungi sepenuhnya.
>    - Atur kepemilikan folder `storage/uploads` agar _writable_ oleh user `www-data`.
>    - Set konfigurasi `php.ini` produksi (`display_errors = Off`, `upload_max_filesize = 5M`, `post_max_size = 8M`).
> 2. Buat file `.dockerignore` (abaikan `.git`, `node_modules`, file lokal sementara).
> 3. Buat file `docker-compose.yml` untuk simulasi pengujian lokal sebelum ke Dokploy (menggunakan service `app` dan `db` MySQL 8.0, dengan volume persisten untuk database dan `storage/uploads`)."

### Prompt 7.2 — Panduan Step-by-Step Deployment ke Dokploy

> **Instruksi ke Agent:**
> "Buatkan file `DEPLOY_DOKPLOY.md` yang berisi panduan operasional lengkap untuk men-deploy aplikasi Momoji ke server menggunakan **Dokploy**, mencakup langkah-langkah berikut:
>
> 1. **Persiapan Repository Git:** Push seluruh kode sumber (termasuk `Dockerfile` dan `database/momoji.sql`) ke GitHub/GitLab.
> 2. **Setup Database di Dokploy:**
>    - Buat Project baru bernama `Momoji` di dashboard Dokploy.
>    - Tambahkan layanan **Database -> MySQL**, atur nama database (`momoji_db`), user, dan password yang kuat, lalu catat _Internal Host_ database tersebut.
>    - Import file `database/momoji.sql` ke database MySQL Dokploy (melalui terminal database Dokploy atau koneksi remote/phpMyAdmin sementara).
> 3. **Setup Aplikasi Web di Dokploy:**
>    - Tambahkan layanan **Application** di dalam project `Momoji` dan hubungkan ke repository Git.
>    - Pada bagian **Build Type**, pilih **Dockerfile** (pastikan path mengarah ke `./Dockerfile`).
> 4. **Konfigurasi Environment Variables:**
>    - Masukkan variabel berikut pada tab _Environment_ aplikasi di Dokploy:
>      - `APP_ENV=production`
>      - `APP_URL=https://domain-anda.com`
>      - `DB_HOST=<internal-host-mysql-dokploy>`
>      - `DB_PORT=3306`
>      - `DB_NAME=momoji_db`
>      - `DB_USER=<user-mysql>`
>      - `DB_PASS=<password-mysql>`
> 5. **Konfigurasi Persistent Storage (Volumes) — SANGAT PENTING:**
>    - Pada tab _Mounts / Volumes_ aplikasi di Dokploy, tambahkan _Volume Mount_ ke `/var/www/html/storage/uploads` agar foto produk, gambar QRIS, dan bukti pembayaran pelanggan tidak hilang saat aplikasi di-rebuild/redeploy.
> 6. **Domain, SSL & Verifikasi Akhir:**
>    - Tambahkan custom domain pada tab _Domains_, arahkan port container ke `80`, dan aktifkan sertifikat SSL otomatis (Let's Encrypt).
>    - Klik tombol **Deploy**, pantau _Build Logs_ hingga selesai, lalu lakukan _smoke test_ (login admin, ganti password default admin, upload gambar QRIS toko, dan uji transaksi)."

---

## ✅ Checklist Audit Akhir (Quality Assurance Prompt)

Gunakan prompt terakhir ini setelah semua fase selesai untuk memverifikasi kepatuhan terhadap `PRD.MD`:

> **Instruksi ke Agent:**
> "Lakukan audit menyeluruh pada seluruh kode aplikasi Momoji terhadap kriteria penerimaan di `PRD.MD` Bagian 12 dan keamanan di Bagian 7.3:
>
> 1. Periksa apakah semua form POST sudah memiliki validasi token CSRF.
> 2. Periksa apakah semua output variabel di file `app/views/` sudah menggunakan fungsi escape HTML (`e()`).
> 3. Pastikan tidak ada query SQL mentah tanpa prepared statement.
> 4. Verifikasi bahwa transaksi checkout menggunakan database transaction dan mengunci baris stok (`FOR UPDATE`) sehingga stok tidak pernah menjadi minus.
> 5. Perbaiki jika ditemukan celah keamanan atau bug sebelum proses _deployment_ final."
