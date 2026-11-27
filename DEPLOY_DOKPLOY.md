# Panduan Deployment Momoji ke Dokploy

Panduan ini menggunakan Dockerfile di root repository dan MySQL 8.0 yang dikelola Dokploy. Jangan mengunggah file `.env` atau kredensial database ke Git.

## 1. Persiapan repository

1. Pastikan `Dockerfile`, `docker/`, `public/.htaccess`, `database/momoji.sql`, dan perubahan aplikasi sudah ada di branch yang akan di-deploy.
2. Salin `.env.example` hanya untuk pengujian Docker lokal; jangan gunakan file itu sebagai konfigurasi production.
3. Commit dan push kode ke GitHub/GitLab. File `.env` dan isi `storage/uploads/` dikecualikan dari Git dan Docker build context.

## 2. Buat dan isi database di Dokploy

1. Buat project `Momoji` di dashboard Dokploy.
2. Tambahkan layanan **Database → MySQL**, pilih MySQL 8.0, lalu tetapkan database `momoji_db`, user aplikasi, dan password acak yang kuat.
3. Catat internal host/service name, port internal `3306`, nama database, dan user. Jangan expose port database ke internet untuk kebutuhan normal aplikasi.
4. Tunggu database siap, lalu import `database/momoji.sql` **satu kali** melalui terminal database atau koneksi administrasi yang dibatasi dan sementara. Contoh pada terminal server yang memiliki salinan repository:

   ```sh
   mysql --host="<internal-host-mysql>" --port=3306 \
     --user="<user-mysql>" --password momoji_db < database/momoji.sql
   ```

   Ganti placeholder sesuai data Dokploy; `--password` akan meminta password secara interaktif. Atau, buka terminal container database dan import file yang diunggah melalui tool administrasi Dokploy. Jangan mengulang import terhadap database berisi data: skrip inisialisasi berisi `DROP TABLE IF EXISTS`.

## 3. Buat aplikasi di Dokploy

1. Tambahkan **Application** ke project `Momoji` dan hubungkan repository serta branch production.
2. Pilih **Build Type: Dockerfile**.
3. Atur build context ke root repository dan Dockerfile ke `./Dockerfile`.
4. Container Apache mendengarkan pada port `80`. VirtualHost menggunakan `/var/www/html/public` sebagai satu-satunya document root.

## 4. Environment aplikasi

Tambahkan variabel berikut pada bagian Environment aplikasi Dokploy. Masukkan kredensial database sebagai secret environment variables; jangan gunakan tanda kutip literal.

| Variabel | Nilai |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://domain-anda.com` |
| `DB_HOST` | Internal host/service name MySQL Dokploy |
| `DB_PORT` | `3306` |
| `DB_NAME` | `momoji_db` |
| `DB_USER` | User aplikasi MySQL |
| `DB_PASS` | Password kuat user aplikasi MySQL |

`APP_URL` harus sama dengan domain HTTPS publik dan tidak menyertakan `/public` atau path folder. Gunakan koneksi internal antar-service, bukan alamat database publik.

## 5. Persistensi upload

Di bagian **Mounts / Volumes** aplikasi, buat volume persisten dan mount ke:

```text
/var/www/html/storage/uploads
```

Volume ini menyimpan gambar produk, QRIS toko, dan bukti pembayaran. Pastikan user Apache `www-data` dapat menulis ke mount tersebut. Dockerfile menyiapkan folder dengan owner dan mode yang benar untuk volume baru; jika menggunakan bind mount yang sudah ada, atur ownership folder di host/container sebelum menerima upload. Jangan mount seluruh `/var/www/html` karena dapat menutupi kode aplikasi dan konfigurasi image.

## 6. Domain, TLS, deploy, dan pemeriksaan awal

1. Tambahkan domain pada tab **Domains**, arahkan ke port container `80`, lalu aktifkan sertifikat otomatis Let's Encrypt.
2. Deploy dan periksa build/runtime logs. Pastikan aplikasi dapat terhubung ke database internal dan halaman login tampil melalui HTTPS.
3. Sebelum login pertama, batasi domain aplikasi dengan IP allowlist atau proteksi akses sementara di reverse proxy Dokploy. SQL seeder memiliki akun bootstrap admin; jangan biarkan akses publik terbuka dengan password bootstrap.
4. Login sebagai admin dan buka **Ganti Password**. Ganti password bootstrap dengan password unik minimal 12 karakter yang disimpan di password manager, lalu login ulang untuk memastikan perubahan berlaku. Hapus pembatasan sementara hanya setelah password berhasil diganti.
5. Upload QRIS resmi di **Pengaturan Toko**, kemudian periksa bahwa upload gambar dan penyimpanan persisten berfungsi.
6. Jalankan smoke test terbatas: buat pesanan uji dengan nilai rendah, periksa alur COD/QRIS sesuai kebijakan toko, status pesanan, bukti pembayaran, dan notifikasi. Hapus data transaksi uji setelah pemeriksaan.
7. Unduh backup SQL melalui halaman admin, simpan di lokasi terenkripsi/terbatas, dan uji pemulihan backup pada database terpisah sebelum mengandalkannya.

## Pengujian lokal dengan Docker Compose

Prasyarat: Docker Engine dan Docker Compose v2.

1. Salin `.env.example` menjadi `.env`, lalu tetapkan dua password lokal yang kuat dan berbeda untuk `DB_PASS` dan `MYSQL_ROOT_PASSWORD`.
2. Jalankan dari root repository:

   ```sh
   docker compose config
   docker compose up --build -d
   docker compose ps
   ```

3. Buka `http://localhost:8080`. MySQL menjalankan `database/momoji.sql` saat volume database pertama kali dibuat dan aplikasi menunggu health check database.
4. Perubahan data MySQL dan uploads disimpan pada named volumes. `docker compose down` mempertahankannya. Jangan gunakan `docker compose down -v` kecuali memang ingin menghapus seluruh data lokal.
5. Jika database lokal dihapus dan dibuat ulang, MySQL akan menjalankan ulang file inisialisasi. Jangan pernah mengarahkan konfigurasi compose lokal ke database production.
