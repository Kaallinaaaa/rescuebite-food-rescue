# Rescuebite

**Rescuebite** adalah aplikasi web etalase makanan surplus. Resto dan toko roti (mitra) memposting makanan layak konsumsi yang belum laku menjelang tutup, lalu pembeli (mahasiswa dan anak rantau) memesannya dengan harga miring dan mengambilnya langsung ke toko memakai kode pickup.

Proyek ini mendukung **SDG 12: Konsumsi dan Produksi yang Bertanggung Jawab**, khususnya pengurangan sampah makanan. Dibuat untuk **Responsi 2 (Proyek Akhir) Praktikum Pemrograman Web I**, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman.

## Latar Belakang dan Tujuan

**Masalah.** Resto dan toko roti sering memiliki makanan yang masih layak tetapi tidak terjual saat tutup, lalu berakhir sebagai sampah. Di sisi lain, mahasiswa dan anak rantau membutuhkan makanan dengan harga terjangkau.

**Tujuan.** Menghubungkan mitra yang memiliki makanan surplus dengan pembeli yang membutuhkan makanan murah, sehingga makanan layak tidak terbuang dan pembeli dapat berhemat.

**Manfaat.**
- Mitra: makanan sisa tetap bernilai dan terjual, bukan terbuang.
- Pembeli: makanan berkualitas dengan harga lebih murah.
- Lingkungan: berkurangnya makanan yang terbuang.

### Keterkaitan dengan SDG 12

Rescuebite berfokus pada **SDG 12: Konsumsi dan Produksi yang Bertanggung Jawab**, terutama **target 12.3**, yaitu mengurangi sampah makanan di tingkat ritel dan konsumen serta kehilangan makanan di sepanjang rantai pasok.

| Sisi | Kontribusi Rescuebite |
|---|---|
| Produsen (mitra) | Makanan sisa yang masih layak dijual melalui aplikasi, bukan dibuang saat tokonya tutup |
| Konsumen (pembeli) | Mendorong kebiasaan membeli makanan surplus sebagai pilihan konsumsi yang lebih bertanggung jawab |
| Pengukuran | **Impact Tracker** menghitung jumlah porsi yang terselamatkan dan uang yang dihemat dari setiap pesanan yang selesai |

## Alur Utama

1. Mitra mendaftar, lalu diverifikasi oleh admin.
2. Menjelang tutup, mitra memposting surplus: foto, harga asli, harga diskon, stok, dan batas jam ambil.
3. Pembeli membuka beranda, mencari atau memfilter makanan, lalu memesan.
4. Stok berkurang otomatis dan pembeli mendapat kode pickup, lalu membayar (simulasi).
5. Pembeli datang ke toko sebelum batas jam ambil dan menunjukkan kode. Mitra mengonfirmasi kode, dan pesanan berstatus selesai.
6. Pembeli dapat memberi ulasan, dan Impact Tracker bertambah.

## Daftar Fitur dan Modul

| No | Modul | Keterangan |
|----|-------|------------|
| 1 | Autentikasi dan role | Registrasi, login, logout, session, dan proteksi halaman untuk admin, mitra, dan pembeli |
| 2 | Manajemen profil | Ubah data diri, ubah password, dan data toko untuk mitra |
| 3 | Verifikasi mitra | Admin menyetujui atau menolak pendaftaran mitra |
| 4 | Kelola kategori | Admin menambah, mengubah, dan menghapus kategori makanan |
| 5 | Kelola listing surplus | Mitra menambah, mengubah, dan menghapus listing, termasuk unggah foto, harga diskon, stok, dan batas jam ambil |
| 6 | Katalog, pencarian, dan filter | Beranda menampilkan makanan, dengan pencarian, filter kategori, dan pengurutan. Listing otomatis hilang setelah batas jam ambil lewat dan berlabel SOLD OUT saat stok habis |
| 7 | Dashboard mitra | Pesanan masuk, riwayat penjualan, dan konfirmasi kode pickup |
| 8 | Pemesanan | Stok berkurang otomatis (dengan transaksi database), pembuatan kode pickup, dan status pesanan |
| 9 | Pembayaran | Pembayaran dengan beberapa metode (simulasi, tanpa uang sungguhan) |
| 10 | Review dan rating | Pembeli memberi rating dan komentar setelah pesanan selesai |
| 11 | Impact Tracker | Total porsi terselamatkan dan uang yang dihemat, per pembeli dan seluruh pengguna |
| 12 | Riwayat pesanan | Daftar pesanan pembeli beserta status, kode pickup, dan status pembayaran |

## Teknologi yang Digunakan

- **Bahasa:** PHP native (tanpa framework), HTML, CSS, JavaScript
- **Database:** MySQL / MariaDB, diakses dengan PDO (prepared statement)
- **Server lokal:** XAMPP (Apache dan MySQL)
- **Keamanan dasar:** `password_hash` / `password_verify`, `htmlspecialchars` untuk output, prepared statement, dan pembatasan akses per role

## Struktur Folder

```
rescuebite-food-rescue/
├── admin/          Halaman admin (verifikasi mitra, kategori)
├── assets/
│   ├── css/        style.css, pembeli.css
│   ├── img/        Gambar statis
│   └── uploads/    Foto makanan yang diunggah mitra
├── auth/           Login, register, logout
├── config/         Koneksi database
├── database/       rescuebite.sql
├── includes/       Header, navbar, footer, pengecekan login dan role
├── mitra/          Dashboard mitra dan kelola listing
├── pembeli/        Pesan, bayar, riwayat, review, dampak
├── index.php       Beranda dan katalog
├── profil.php      Profil pengguna
└── README.md
```

## Panduan Instalasi dan Konfigurasi

### Prasyarat

- XAMPP (PHP 8.0 atau lebih baru, Apache, dan MySQL/MariaDB)
- Git (opsional, untuk clone)

### Langkah

1. **Ambil source code** ke folder `htdocs` XAMPP. Nama foldernya **harus persis** `rescuebite-food-rescue`, karena alamat halaman di dalam kode memakai nama itu.

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Kaallinaaaa/rescuebite-food-rescue.git
   ```

2. **Jalankan Apache dan MySQL** dari XAMPP Control Panel.

3. **Import database.** Buka `http://localhost/phpmyadmin`, pilih tab **Impor**, lalu pilih file `database/rescuebite.sql` dan klik **Kirim**. File ini otomatis membuat database `rescuebite` beserta 7 tabelnya (`users`, `partners`, `categories`, `listings`, `orders`, `payments`, `reviews`). Menjalankannya ulang akan menghapus dan membuat ulang tabel tersebut.

4. **Cek konfigurasi database** di `config/database.php`. Bawaan XAMPP sudah sesuai:

   ```php
   $host = "localhost";
   $db   = "rescuebite";
   $user = "root";
   $pass = "";   // ubah jika MySQL kamu memakai password
   ```

5. **Pastikan folder `assets/uploads/` dapat ditulis**, karena foto makanan disimpan di sana. Di Windows (XAMPP) biasanya sudah bisa.

6. **Buat akun admin.** Akun admin tidak disertakan di file SQL supaya tidak ada password yang tersimpan di repository publik. Caranya:
   1. Buka `http://localhost/rescuebite-food-rescue/auth/register.php` dan daftar sebagai pembeli dengan email dan password pilihanmu.
   2. Di phpMyAdmin, buka database `rescuebite`, tab **SQL**, lalu jalankan (ganti emailnya):

      ```sql
      UPDATE users SET role = 'admin' WHERE email = 'emailkamu@contoh.com';
      ```

   3. Logout lalu login lagi.

## Panduan Menjalankan Aplikasi

Buka `http://localhost/rescuebite-food-rescue/`. Untuk mencoba seluruh alur:

1. **Mitra:** daftar sebagai mitra dan isi data toko. Akun mitra belum bisa memposting makanan sebelum diverifikasi.
2. **Admin:** login sebagai admin, buka menu **Mitra**, lalu verifikasi mitra tersebut.
3. **Mitra:** login, buka **Kelola Listing**, lalu posting makanan (foto wajib diunggah, harga diskon harus lebih murah dari harga asli, dan batas jam ambil harus di masa depan).
4. **Pembeli:** daftar dan login sebagai pembeli, buka beranda, klik **Pesan** pada makanan, isi jumlah, lalu bayar. Kode pickup muncul di halaman pembayaran dan **Pesanan Saya**.
5. **Mitra:** di dashboard, masukkan kode pickup dari pembeli untuk menyelesaikan pesanan.
6. **Pembeli:** di **Pesanan Saya**, klik **Beri ulasan**, lalu lihat angka pada halaman **Dampakmu**.

### Catatan

- Pembayaran bersifat **simulasi**. Tidak ada uang sungguhan yang diproses.
- Stok berkurang saat pesanan dibuat, bukan saat pembayaran.
- Aplikasi memakai zona waktu `Asia/Jakarta` (diatur di `config/database.php`).

## Tim Pengembang

| Anggota | Tanggung jawab |
|---|---|
| Kalina Aulia Briliani | Fondasi, pengguna, dan admin: autentikasi, profil, dashboard admin, struktur proyek, layout, dan landing page |
| Ahmad Hafidzuddin | Sisi mitra dan katalog: kelola listing, pencarian dan filter, dashboard mitra, dan beranda |
| Silmi Nadyya Nurbaeti | Transaksi dan dampak: pemesanan, pembayaran, review dan rating, impact tracker, riwayat pesanan|

---

Dibuat untuk Praktikum Pemrograman Web I, Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman, Purbalingga, 2026.
