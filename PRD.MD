# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Momoji — Online Store Ordering & Checkout System

**Versi:** 1.0  
**Status:** Draft untuk pengembangan  
**Platform:** Web Application  
**Bahasa:** Indonesia  
**Mata uang:** Rupiah (IDR)  
**Target pengguna:** Admin / Pemilik Toko dan Customer

---

## 1. Ringkasan Produk

Momoji adalah aplikasi toko online berbasis web yang memungkinkan pelanggan melihat katalog produk, memasukkan produk ke keranjang, melakukan checkout, memilih metode pembayaran, dan memantau status pesanan.

Admin atau pemilik toko dapat mengelola produk, stok, pesanan, pembayaran, pelanggan, voucher, serta laporan penjualan melalui dashboard admin.

Aplikasi dikembangkan menggunakan PHP Native dengan pendekatan MVC, MySQL, JavaScript, dan Bootstrap 5. Pengembangan dimulai secara lokal menggunakan XAMPP dan Visual Studio Code sebelum nantinya dipublikasikan secara online.

## 2. Latar Belakang dan Masalah

Proses pemesanan yang belum terintegrasi dapat menyebabkan:

- Pengelolaan pesanan kurang terorganisasi.
- Informasi stok tidak selalu akurat.
- Pelanggan kesulitan melihat produk dan informasi harga.
- Proses pembayaran dan konfirmasi pesanan kurang praktis.
- Pemilik toko kesulitan memantau transaksi dan penjualan.

Momoji dibuat untuk mendigitalisasi proses tersebut melalui satu sistem toko online sederhana dan terintegrasi.

## 3. Tujuan Produk

1. Menyediakan katalog produk online.
2. Memudahkan pelanggan melakukan pemesanan dan checkout.
3. Menyediakan pilihan pembayaran COD dan QRIS.
4. Membantu admin mengelola produk, stok, pesanan, dan pembayaran.
5. Menyediakan informasi status pesanan kepada pelanggan.
6. Menyediakan dashboard untuk memantau penjualan dan performa toko.
7. Mengurangi kesalahan pencatatan pesanan dan stok.

## 4. Target Pengguna

### 4.1 Admin / Pemilik Toko

Admin bertanggung jawab untuk:

- Mengelola produk dan kategori.
- Mengatur harga, promo, dan stok.
- Memproses pesanan.
- Memverifikasi pembayaran QRIS.
- Mengelola pelanggan.
- Mengelola voucher.
- Melihat laporan penjualan.
- Melakukan backup database.

### 4.2 Customer

Customer dapat:

- Mendaftar dan login.
- Melihat katalog produk.
- Mencari dan memfilter produk.
- Menambahkan produk ke wishlist dan keranjang.
- Mengelola alamat.
- Melakukan checkout.
- Membayar dengan COD atau QRIS.
- Melihat status dan riwayat pesanan.
- Memberikan rating dan ulasan setelah transaksi selesai.

## 5. Ruang Lingkup Produk

### 5.1 Fitur Utama (MVP)

Fitur yang harus diselesaikan terlebih dahulu:

1. Autentikasi pelanggan dan admin.
2. Homepage dan katalog produk.
3. Pencarian dan filter produk.
4. Detail produk.
5. Keranjang belanja.
6. Checkout.
7. Pemilihan pengiriman.
8. Pembayaran COD dan QRIS.
9. Manajemen pesanan.
10. Manajemen produk.
11. Manajemen stok.
12. Dashboard admin dasar.
13. Riwayat pesanan pelanggan.
14. Notifikasi dalam aplikasi.

### 5.2 Fitur Pengembangan Berikutnya

- Wishlist.
- Rating dan ulasan.
- Voucher dan diskon lanjutan.
- Grafik penjualan.
- Laporan transaksi.
- Export laporan Excel/PDF.
- Backup database dari dashboard.
- Analisis produk terlaris.

## 6. Spesifikasi Fungsional

### 6.1 Autentikasi dan Hak Akses

Sistem menyediakan:

- Registrasi customer.
- Login customer.
- Login admin.
- Logout.
- Lupa dan reset password.
- Pengelolaan session.
- Pembatasan halaman berdasarkan role.

Ketentuan:

- Hanya admin yang dapat mengakses dashboard admin.
- Registrasi admin tidak tersedia secara publik.
- Password disimpan menggunakan `password_hash()`.
- Verifikasi password menggunakan `password_verify()`.
- Database diakses menggunakan PDO dan prepared statements.

### 6.2 Homepage

Homepage menampilkan:

- Logo dan navigasi Momoji.
- Banner promosi.
- Kolom pencarian.
- Kategori produk.
- Produk unggulan.
- Produk terbaru.
- Produk terlaris.
- Informasi promo.
- Footer dan informasi toko.

### 6.3 Katalog dan Detail Produk

Informasi produk meliputi:

- Nama produk.
- Foto produk (dapat lebih dari satu).
- Deskripsi.
- Kategori.
- Harga normal.
- Harga promo.
- Stok.
- Berat.
- Varian produk.
- Label produk baru, promo, atau bestseller.
- Status ketersediaan.

Pelanggan dapat melakukan pencarian dan filter berdasarkan:

- Nama produk.
- Kategori.
- Rentang harga.
- Popularitas.
- Status promo.

### 6.4 Keranjang Belanja

Fitur keranjang:

- Tambah produk.
- Hapus produk.
- Ubah jumlah produk.
- Pilih varian produk.
- Hitung subtotal.
- Validasi stok.
- Pilih produk yang akan dibeli.
- Hitung total belanja.

Keranjang disimpan di database sehingga tetap tersedia setelah pelanggan login kembali atau berpindah perangkat.

Harga dan stok harus divalidasi ulang di server saat checkout.

### 6.5 Checkout

Halaman checkout berisi:

- Ringkasan produk.
- Informasi penerima.
- Nomor telepon.
- Alamat pengiriman.
- Pilihan pengiriman.
- Biaya pengiriman.
- Voucher.
- Diskon.
- Catatan pesanan.
- Metode pembayaran.
- Total pembayaran.
- Tombol konfirmasi pesanan.

Alamat pelanggan menggunakan satu alamat utama yang dapat diedit.

Pilihan pengiriman:

1. Pickup di toko.
2. Kurir toko Momoji.

Biaya kurir ditentukan berdasarkan zona wilayah, yang dapat diatur admin.

### 6.6 Sistem Pembayaran

**COD (Cash on Delivery)**

- Customer memilih pembayaran COD.
- Pesanan diproses setelah dikonfirmasi admin.
- Pembayaran dicatat ketika uang diterima oleh pihak toko atau kurir.

**QRIS Static**

- Admin menyediakan gambar QRIS resmi milik toko.
- Customer memindai QRIS menggunakan aplikasi pembayaran yang mendukung QRIS.
- Customer mengunggah bukti pembayaran.
- Admin memeriksa pembayaran melalui catatan transaksi merchant.
- Admin mengonfirmasi pembayaran yang benar.

Catatan keamanan: unggahan screenshot bukan bukti otomatis bahwa transaksi berhasil. Admin wajib memverifikasi transaksi melalui catatan merchant.

### 6.7 Manajemen Pesanan

Admin dapat:

- Melihat semua pesanan.
- Menerima pesanan.
- Menolak pesanan.
- Memverifikasi pembayaran.
- Memproses pesanan.
- Mengubah status pesanan.
- Menangani pembatalan.

Status pesanan yang disarankan:

1. Menunggu konfirmasi.
2. Dikonfirmasi.
3. Menunggu pembayaran.
4. Diproses.
5. Dikemas.
6. Siap diambil.
7. Sedang dikirim.
8. Selesai.
9. Dibatalkan.

Status pembayaran dipisahkan dari status pesanan:

- Belum dibayar.
- Menunggu verifikasi.
- Dibayar.
- Gagal / ditolak.
- Dikembalikan, jika fitur pengembalian dana diterapkan.

Pelanggan dapat membatalkan pesanan selama pesanan belum diproses. Admin juga dapat membatalkan pesanan dengan mencatat alasan pembatalan.

### 6.8 Manajemen Stok

Sistem stok menyediakan:

- Penambahan stok.
- Pengurangan stok.
- Penyesuaian stok manual.
- Validasi stok saat checkout.
- Riwayat perubahan stok.
- Notifikasi stok menipis.
- Pengembalian stok ketika pesanan dibatalkan sesuai aturan reservasi.

Rekomendasi mekanisme:

- Ketika pesanan dibuat, sistem memeriksa ketersediaan stok.
- Ketika admin mengonfirmasi pesanan, stok direservasi.
- Pesanan yang dibatalkan sebelum diproses melepaskan reservasi.
- Sistem harus mencegah jumlah stok yang dipesan melebihi stok tersedia.

Setiap perubahan stok harus memiliki catatan waktu, produk, jumlah perubahan, alasan, dan pengguna yang melakukan perubahan.

### 6.9 Voucher dan Diskon

Admin dapat membuat:

- Voucher diskon persentase.
- Voucher potongan harga tetap.
- Voucher gratis ongkir.

Pengaturan voucher:

- Kode voucher.
- Jenis diskon.
- Nilai diskon.
- Minimal pembelian.
- Masa berlaku.
- Batas penggunaan.
- Status aktif atau nonaktif.

Sistem harus memvalidasi voucher di server sebelum menerapkan diskon.

### 6.10 Wishlist dan Ulasan

Wishlist:

- Customer dapat menyimpan produk favorit.
- Customer dapat menghapus produk dari wishlist.

Rating dan ulasan:

- Customer dapat memberikan rating dan ulasan setelah pesanan selesai.
- Admin dapat melihat ulasan.
- Ulasan terkait dengan produk dan transaksi yang valid.

### 6.11 Notifikasi

Notifikasi ditampilkan di dalam aplikasi.

Notifikasi customer:

- Pesanan berhasil dibuat.
- Pesanan dikonfirmasi.
- Pembayaran diverifikasi.
- Pesanan dikemas.
- Pesanan siap diambil.
- Pesanan dikirim.
- Pesanan selesai atau dibatalkan.

Notifikasi admin:

- Pesanan baru.
- Pembayaran perlu diverifikasi.
- Permintaan pembatalan.
- Ulasan produk baru.

Notifikasi memiliki indikator jumlah notifikasi yang belum dibaca.

### 6.12 Dashboard Admin

Dashboard menampilkan:

- Jumlah produk.
- Jumlah pelanggan.
- Jumlah pesanan.
- Total transaksi.
- Total pendapatan.
- Grafik penjualan harian, mingguan, dan bulanan.
- Produk terlaris.
- Pemantauan stok.
- Pesanan terbaru.

Admin juga dapat mengelola data pelanggan dan melihat laporan transaksi.

### 6.13 Backup Database

Admin dapat melakukan backup database melalui dashboard.

File backup dapat diunduh dan disimpan secara aman. Fitur backup harus dibatasi untuk admin yang berwenang.

## 7. Kebutuhan Non-Fungsional

### 7.1 Kemudahan Penggunaan

- Antarmuka sederhana dan mudah dipahami.
- Navigasi konsisten.
- Form memiliki validasi.
- Pesan kesalahan jelas.

### 7.2 Responsivitas

Aplikasi harus mendukung:

- Smartphone.
- Tablet.
- Desktop dan laptop.

### 7.3 Keamanan

- Password menggunakan hashing.
- Query database menggunakan PDO prepared statements.
- Validasi input di sisi server.
- Proteksi akses berdasarkan role.
- Proteksi CSRF untuk form penting.
- Escape output untuk mencegah XSS.
- Validasi jenis dan ukuran file upload.
- Session dikelola secara aman.
- File konfigurasi database tidak dapat diakses publik.

### 7.4 Performa

- Query database dibuat efisien.
- Gambar produk dioptimalkan.
- Pagination digunakan untuk katalog dan daftar pesanan.
- Indeks database diterapkan pada kolom pencarian yang sering digunakan.

### 7.5 Kompatibilitas

Aplikasi ditujukan untuk browser modern seperti Chrome, Firefox, dan Edge.

## 8. Desain UI/UX

### 8.1 Konsep Desain

Gaya visual: modern marketplace dengan nuansa alam, hutan, dan laut.

Palet warna:

- Forest Green: `#234C3B`
- Ocean Teal: `#397C83`
- Soft Sand: `#F3EBDD`
- Pure White: `#FFFFFF`
- Sage Green: `#A8B99A`
- Warm Sand: `#D9A66B`

### 8.2 Halaman Customer

1. Homepage.
2. Katalog produk.
3. Detail produk.
4. Login.
5. Registrasi.
6. Wishlist.
7. Keranjang.
8. Checkout.
9. Pembayaran QRIS.
10. Riwayat pesanan.
11. Detail pesanan.
12. Profil dan alamat.
13. Ulasan produk.

### 8.3 Halaman Admin

1. Login admin.
2. Dashboard.
3. Manajemen produk.
4. Manajemen kategori.
5. Manajemen stok.
6. Manajemen pesanan.
7. Verifikasi pembayaran.
8. Manajemen pelanggan.
9. Manajemen voucher.
10. Manajemen ulasan.
11. Laporan penjualan.
12. Pengaturan toko.
13. Backup database.

## 9. Arsitektur Teknologi

| Bagian            | Teknologi                         |
| ----------------- | --------------------------------- |
| Backend           | PHP Native                        |
| Arsitektur        | MVC                               |
| Database          | MySQL                             |
| Database Access   | PDO                               |
| Frontend          | HTML, CSS, JavaScript             |
| UI Framework      | Bootstrap 5                       |
| Interaksi dinamis | JavaScript / AJAX bila diperlukan |
| Local Server      | XAMPP                             |
| Code Editor       | Visual Studio Code                |

### Struktur Folder

```text
momoji/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   │   ├── admin/
│   │   └── customer/
│   └── helpers/
├── config/
│   └── database.php
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php
├── storage/
│   └── uploads/
└── database/
    └── momoji.sql
```

Untuk pengembangan lokal, konfigurasi Apache perlu diarahkan agar folder `public` menjadi document root. Jika belum memungkinkan, gunakan konfigurasi lokal yang memastikan folder sensitif seperti `app`, `config`, dan `storage` tidak dapat diakses langsung melalui browser.

## 10. Rancangan Database

Tabel utama yang direkomendasikan:

| Tabel              | Fungsi                       |
| ------------------ | ---------------------------- |
| users              | Data akun admin dan customer |
| customer_addresses | Alamat pelanggan             |
| categories         | Kategori produk              |
| products           | Data produk                  |
| product_images     | Foto produk                  |
| product_variants   | Varian produk                |
| carts              | Keranjang pelanggan          |
| cart_items         | Detail keranjang             |
| wishlists          | Produk favorit               |
| orders             | Data pesanan                 |
| order_items        | Detail produk pesanan        |
| payments           | Data pembayaran              |
| payment_proofs     | Bukti pembayaran QRIS        |
| shipping_zones     | Zona dan biaya pengiriman    |
| stock_movements    | Riwayat perubahan stok       |
| vouchers           | Data voucher                 |
| voucher_usages     | Riwayat penggunaan voucher   |
| reviews            | Rating dan ulasan            |
| notifications      | Notifikasi pengguna          |

Relasi utama:

- Satu user dapat memiliki satu alamat utama.
- Satu kategori memiliki banyak produk.
- Satu produk dapat memiliki banyak gambar dan varian.
- Satu customer dapat memiliki banyak pesanan.
- Satu pesanan memiliki banyak item pesanan.
- Satu pesanan memiliki data pembayaran.
- Produk memiliki riwayat perubahan stok.
- Customer dapat memiliki banyak notifikasi.

Gunakan foreign key, indeks, dan transaksi database untuk menjaga konsistensi data.

## 11. Alur Penggunaan Utama

### 11.1 Alur Customer

1. Customer membuka homepage.
2. Customer melihat atau mencari produk.
3. Customer membuka detail produk.
4. Customer menambahkan produk ke keranjang.
5. Customer login atau registrasi.
6. Customer membuka checkout.
7. Customer memeriksa alamat dan pilihan pengiriman.
8. Customer memasukkan voucher jika tersedia.
9. Customer memilih COD atau QRIS.
10. Customer mengonfirmasi pesanan.
11. Sistem menyimpan pesanan.
12. Customer memantau status pesanan.

### 11.2 Alur Admin

1. Admin login.
2. Admin membuka dashboard.
3. Admin memeriksa pesanan baru.
4. Admin mengonfirmasi atau menolak pesanan.
5. Admin memverifikasi pembayaran QRIS jika diperlukan.
6. Admin memproses dan mengemas produk.
7. Admin mengubah status menjadi siap diambil atau dikirim.
8. Admin menyelesaikan pesanan setelah transaksi selesai.

## 12. Kriteria Penerimaan

Aplikasi dianggap memenuhi kebutuhan MVP apabila:

- Customer dapat mendaftar dan login.
- Admin dapat login dengan hak akses terpisah.
- Admin dapat melakukan CRUD produk dan kategori.
- Customer dapat melihat dan mencari produk.
- Customer dapat mengelola keranjang.
- Checkout memvalidasi harga dan stok terbaru.
- Customer dapat memilih pickup atau kurir toko.
- Biaya kurir dihitung berdasarkan zona.
- Customer dapat memilih COD atau QRIS.
- Bukti QRIS dapat diunggah dan diverifikasi admin.
- Admin dapat mengelola status pesanan.
- Stok tidak dapat menjadi negatif akibat checkout bersamaan.
- Pesanan yang dibatalkan melepaskan reservasi stok sesuai aturan.
- Customer dapat melihat riwayat dan status pesanan.
- Tampilan responsif di HP, tablet, dan desktop.
- Dashboard admin dapat menampilkan ringkasan data toko.

## 13. Tahapan Pengembangan

### Tahap 1 — Persiapan

- Instalasi XAMPP dan VS Code.
- Membuat folder project.
- Membuat database MySQL.
- Membuat koneksi PDO.
- Menyiapkan struktur MVC.

### Tahap 2 — Autentikasi

- Registrasi customer.
- Login customer.
- Login admin.
- Session dan role management.

### Tahap 3 — Produk dan Katalog

- CRUD kategori.
- CRUD produk.
- Upload gambar.
- Katalog dan detail produk.
- Pencarian dan filter.

### Tahap 4 — Keranjang dan Checkout

- Keranjang belanja.
- Validasi stok.
- Alamat pelanggan.
- Pilihan pengiriman.
- Voucher dasar.
- Pembuatan pesanan.

### Tahap 5 — Pembayaran dan Pesanan

- COD.
- QRIS static.
- Upload bukti pembayaran.
- Verifikasi pembayaran.
- Manajemen status pesanan.
- Notifikasi.

### Tahap 6 — Dashboard dan Stok

- Dashboard admin.
- Manajemen stok.
- Riwayat stok.
- Zona pengiriman.
- Laporan dasar.

### Tahap 7 — Fitur Tambahan

- Wishlist.
- Rating dan ulasan.
- Voucher lanjutan.
- Grafik dan laporan.
- Backup database.

### Tahap 8 — Pengujian dan Deployment

- Pengujian fungsi.
- Pengujian keamanan dasar.
- Pengujian responsivitas.
- Perbaikan bug.
- Backup database.
- Persiapan hosting dan deployment online.

## 14. Risiko dan Mitigasi

| Risiko                                 | Mitigasi                                       |
| -------------------------------------- | ---------------------------------------------- |
| Stok tidak sinkron                     | Reservasi stok dan transaksi database          |
| Pembayaran QRIS palsu atau tidak valid | Verifikasi melalui catatan merchant            |
| Akses dashboard oleh customer          | Middleware atau pemeriksaan role               |
| Upload file berbahaya                  | Validasi MIME, ekstensi, ukuran, dan nama file |
| Data pesanan tidak konsisten           | Foreign key dan database transaction           |
| Kehilangan database                    | Backup rutin dan penyimpanan backup yang aman  |
| Scope terlalu besar                    | Prioritaskan MVP dan kerjakan bertahap         |

## 15. Asumsi dan Hal yang Perlu Dikonfirmasi

Hal berikut perlu ditentukan sebelum implementasi terkait:

1. Nama, logo, dan informasi resmi toko Momoji.
2. Daftar produk, kategori, dan varian.
3. Gambar QRIS resmi merchant.
4. Nama zona pengiriman dan nominal tarif sebenarnya.
5. Batas minimal pembelian dan aturan voucher.
6. Batas stok minimum untuk notifikasi.
7. Kebijakan refund jika pembayaran sudah dilakukan.
8. Ketentuan masa berlaku reservasi stok.
9. Informasi kontak dan alamat toko untuk pickup.

## 16. Definisi Selesai (Definition of Done)

Fitur dinyatakan selesai jika:

- Berfungsi sesuai kebutuhan.
- Validasi frontend dan backend berjalan.
- Hak akses sesuai role.
- Data tersimpan dengan benar di database.
- Tidak menyebabkan kesalahan pada fitur lain.
- Sudah diuji pada skenario normal dan kesalahan.
- Tampilan dapat digunakan pada perangkat yang ditargetkan.
- Tidak ada bug kritis yang belum diselesaikan.

---

**Kesimpulan:** Momoji adalah aplikasi toko online berbasis PHP Native MVC dan MySQL dengan dua role pengguna, sistem katalog, keranjang, checkout, COD, QRIS, pengiriman pickup/kurir toko, manajemen stok, dan dashboard admin. Pengembangan dilakukan bertahap dimulai dari lingkungan lokal XAMPP sebelum deployment online.

**Rekomendasi langkah pertama:** Bangun fondasi project, database, koneksi PDO, dan autentikasi sebelum melanjutkan ke modul produk dan transaksi.
