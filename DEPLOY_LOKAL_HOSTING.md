# Menjalankan Momoji Lokal atau di Shared Hosting

Gunakan panduan ini bila tidak memakai Docker atau Dokploy. Shared hosting gratis berbeda-beda: sebelum upload, pastikan penyedia mengizinkan aplikasi toko online dan menyediakan PHP 8.2+, MySQL/MariaDB, PDO MySQL, URL rewrite, HTTPS, dan folder upload persisten. Paket gratis mungkin membatasi penyimpanan, backup, koneksi database, atau penggunaan transaksi; jangan menyimpan transaksi pelanggan sungguhan sebelum batasan tersebut jelas.

## Pilihan 1: XAMPP lokal

1. Pasang XAMPP yang menyertakan PHP 8.2 atau lebih baru dan MySQL/MariaDB.
2. Salin project ke `C:\xampp\htdocs\momoji`.
3. Jalankan Apache dan MySQL dari XAMPP Control Panel.
4. Buka phpMyAdmin di `http://localhost/phpmyadmin`, buat database bernama `momoji_db`, pilih collation `utf8mb4_unicode_ci`, lalu import `database/momoji.sql` satu kali.
5. Untuk nilai lokal default, konfigurasi database ada di `config/database.php` (MySQL `127.0.0.1:3306`, database `momoji_db`, user `root`, password kosong). Jika instalasi MySQL lokal menggunakan akun/password lain, salin `config/database.local.example.php` menjadi `config/database.local.php` dan isi hanya dengan kredensial lokal.
6. Pastikan folder `storage/uploads` dapat ditulis oleh Apache. Di XAMPP Windows, folder biasanya dapat ditulis secara default; jangan mengubah permission seluruh project menjadi writable.
7. Buka `http://localhost/momoji`. Akun awal database seed adalah `admin@momoji.id` / `Admin123!`; hanya gunakan untuk lokal dan segera ganti melalui menu **Ganti Password**.
8. Sebelum memakai database lokal yang sudah memiliki data, unduh backup dari **Backup Database** dan simpan di lokasi privat. File seed `database/momoji.sql` berisi perintah menghapus tabel lama; jangan import ulang ke database yang berisi data.

## Pilihan 2: Shared hosting PHP (termasuk paket gratis yang memenuhi syarat)

### Cek kelayakan penyedia terlebih dahulu

Pastikan layanan menyediakan semua hal berikut:

- PHP 8.2 atau lebih baru dengan `pdo_mysql`, `fileinfo`, `mbstring`, dan `iconv`.
- MySQL 8.0 atau MariaDB yang mendukung InnoDB, foreign keys, dan transaksi.
- HTTPS/SSL untuk domain atau subdomain.
- URL rewriting (`mod_rewrite`/`.htaccess`) atau kemampuan menetapkan document root domain ke folder `public/`.
- Batas upload paling sedikit 5 MB dan `post_max_size` paling sedikit 8 MB.
- Penyimpanan upload yang persisten dan bisa ditulis oleh PHP.
- Cara mengatur environment variables atau menyimpan file konfigurasi di luar document root.

Jika syarat database, HTTPS, atau storage tidak tersedia, jangan gunakan host tersebut untuk transaksi atau bukti pembayaran pelanggan. Gratis tidak menjamin layanan/backup selalu tersedia; simpan backup database dan upload di tempat privat lain.

### Instalasi

1. Buat database MySQL dan user dari panel hosting. Catat host, port, nama database, user, dan password. Host database shared hosting sering bukan `localhost`; gunakan nilai yang diberikan panel.
2. Upload source project. Cara yang disarankan adalah menempatkan project di folder privat di luar web root dan mengatur document root domain ke `project/public`.
3. Jika panel mengharuskan web root tetap pada folder seperti `public_html`, konfirmasikan bahwa `.htaccess` di folder project dapat menjalankan rewrite ke `public/`, dan bahwa akses langsung ke `app/`, `config/`, serta `storage/` benar-benar ditolak. Jangan mengunggah kredensial ke folder yang bisa diunduh publik.
4. Import `database/momoji.sql` satu kali ke database baru melalui phpMyAdmin atau tool import panel. **Jangan import ke database berisi data** karena skrip seed memiliki `DROP TABLE IF EXISTS`.
5. Atur kredensial dengan urutan pilihan berikut:
   - Gunakan environment variables yang disediakan panel hosting: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASS`.
   - Bila panel tidak menyediakan environment variables, salin `config/database.local.example.php` ke `config/database.local.php`, isi nilai sebenarnya, dan simpan folder `config/` di luar document root. File ini dikecualikan dari Git dan Docker build context. Jangan kirim file kredensial ke repository.
6. Set `APP_ENV=production` dan `APP_DEBUG=false` pada environment aplikasi. Jika `.env` digunakan, catat bahwa PHP Momoji tidak membaca `.env` secara otomatis; `.env` hanya dipakai Docker Compose. Atur nilai melalui panel host atau server config, bukan dengan memindahkan `.env` ke web root.
7. Atur `APP_URL` ke alamat HTTPS lengkap, misalnya `https://toko.example.com` (tanpa `/public`). Untuk instalasi dalam subfolder, gunakan URL subfolder yang benar dan uji seluruh route/asset.
8. Pastikan `storage/uploads` writable oleh proses PHP. Pertahankan folder ini saat source diperbarui, dan jangan jadikan folder upload dapat menjalankan PHP/script.
9. Buka domain melalui HTTPS. Login bootstrap hanya setelah akses domain dibatasi; segera ganti password admin dengan menu **Ganti Password** sebelum membuka akses publik.
10. Uji login customer/admin, katalog, keranjang, checkout, stok, upload QRIS/bukti, alur ulasan, laporan, dan backup. Periksa error log hosting, lalu ambil backup database dan direktori upload.

## Pembaruan dan pemulihan

- Simpan backup sebelum setiap pembaruan source atau perubahan database.
- Upload source code tanpa menimpa `config/database.local.php` dan tanpa menghapus `storage/uploads`.
- Uji backup SQL dengan memulihkannya ke database sementara, bukan menimpa database aktif.
- Jangan menaruh backup SQL, export pelanggan, atau bukti pembayaran di dalam web root atau URL publik.
- Setelah perubahan PHP/web server, ulangi smoke test akses admin/customer dan pemesanan sebelum mengumumkan toko siap.

## Alternatif deployment Docker

Jika kelak ingin mencoba Docker, panduan Dokploy dan Docker Compose tetap ada di [DEPLOY_DOKPLOY.md](./DEPLOY_DOKPLOY.md). Langkah itu opsional; tidak diperlukan untuk XAMPP atau shared hosting PHP yang kompatibel.
