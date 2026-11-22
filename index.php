<?php
session_start();
require_once "config/database.php";

$jml_mitra = $pdo->query(
    "SELECT COUNT(*) FROM partners WHERE status_verifikasi = 'terverifikasi'"
)->fetchColumn();
$jml_pembeli = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'pembeli'"
)->fetchColumn();

$body_class = "landing";
require_once "includes/header.php";
?>

<section class="hero">
    <h1>Makan hemat, bumi selamat.</h1>
    <p class="hero-sub">
        Rescuebite menyelamatkan makanan layak dari resto dan toko roti,
        lalu menjualnya dengan harga miring untuk mahasiswa dan anak rantau.
    </p>
    <?php if (isset($_SESSION["user_id"])): ?>
        <p>Halo, <strong><?= htmlspecialchars($_SESSION["nama"]) ?></strong>! Katalog makanan segera hadir.</p>
    <?php else: ?>
        <div class="hero-aksi">
            <a href="auth/register.php" class="btn-solid">Mulai Sekarang</a>
            <a href="auth/login.php" class="btn-outline">Masuk</a>
        </div>
    <?php endif; ?>
    <img src="assets/img/hero.jpg" alt="Makanan surplus Rescuebite" class="hero-img">
</section>

<section class="stat-bar">
    <p class="stat-teks">Selamatkan makanan, hemat uang makan, dan kurangi sampah makanan bersama-sama.</p>
    <div class="stat-item"><strong><?= (int) $jml_mitra ?></strong><span>Mitra terverifikasi</span></div>
    <div class="stat-item"><strong><?= (int) $jml_pembeli ?></strong><span>Pembeli terdaftar</span></div>
    <div class="stat-item"><strong>0</strong><span>Porsi terselamatkan</span></div>
</section>

<section class="blok">
    <h2 class="judul-blok">Cara Kerja</h2>
    <div class="grid-3">
        <div class="kartu-info">
            <div class="angka">1</div>
            <h3>Mitra posting surplus</h3>
            <p>Menjelang tutup, resto dan toko roti memasang makanan yang belum laku dengan harga diskon.</p>
        </div>
        <div class="kartu-info">
            <div class="angka">2</div>
            <h3>Kamu pesan</h3>
            <p>Cari makanan di sekitarmu, pesan, lalu dapatkan kode pickup.</p>
        </div>
        <div class="kartu-info">
            <div class="angka">3</div>
            <h3>Ambil dan nikmati</h3>
            <p>Datang ke toko sebelum batas jam ambil, tunjukkan kode, dan makan hemat.</p>
        </div>
    </div>
</section>

<section class="blok">
    <h2 class="judul-blok">Contoh Makanan yang Diselamatkan</h2>
    <div class="grid-3">
        <div class="kartu-makanan">
            <img src="assets/img/roti.jpg" alt="Roti">
            <div class="isi">
                <h3>Roti &amp; Kue</h3>
                <p>Roti hari ini yang belum terjual, masih segar dan layak.</p>
                <span class="harga"><s>Rp 20.000</s> Rp 8.000</span>
            </div>
        </div>
        <div class="kartu-makanan">
            <img src="assets/img/makanan.jpg" alt="Makanan berat">
            <div class="isi">
                <h3>Makanan Berat</h3>
                <p>Nasi dan lauk sisa jualan, pas untuk makan malam hemat.</p>
                <span class="harga"><s>Rp 25.000</s> Rp 12.000</span>
            </div>
        </div>
        <div class="kartu-makanan">
            <img src="assets/img/camilan.jpg" alt="Camilan">
            <div class="isi">
                <h3>Camilan &amp; Minuman</h3>
                <p>Camilan dan minuman menjelang tutup dengan harga miring.</p>
                <span class="harga"><s>Rp 15.000</s> Rp 6.000</span>
            </div>
        </div>
    </div>
    <p class="catatan-sdg">Contoh tampilan. Katalog asli muncul setelah mitra memposting surplus.</p>
</section>

<section class="cta" style="background-image: linear-gradient(rgba(47,138,74,.85), rgba(47,138,74,.85)), url('assets/img/mahasiswa.jpg');">
    <h2>Yuk, mulai selamatkan makanan</h2>
    <p>Mendukung SDG 12: Konsumsi dan Produksi yang Bertanggung Jawab.</p>
    <?php if (!isset($_SESSION["user_id"])): ?>
        <a href="auth/register.php" class="btn-cta">Daftar Gratis</a>
    <?php endif; ?>
</section>

<footer class="footer-landing">
    <img src="assets/img/logo-putih.png" alt="Rescuebite" height="40">
    <p>Makan hemat, bumi selamat.</p>
    <small>&copy; 2026 Rescuebite · Proyek Praktikum Pemrograman Web I</small>
</footer>

<?php require_once "includes/footer.php"; ?>