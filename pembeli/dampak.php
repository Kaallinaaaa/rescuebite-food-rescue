<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

// Hanya pesanan yang sudah selesai (makanan benar-benar diambil) yang dihitung
$sql = "SELECT COUNT(*) AS jml_pesanan,
               COALESCE(SUM(o.jumlah), 0) AS porsi,
               COALESCE(SUM((l.harga_asli - l.harga_diskon) * o.jumlah), 0) AS hemat
        FROM orders o
        JOIN listings l ON o.listing_id = l.id
        WHERE o.status_pesanan = 'selesai'";

// Dampak pribadi
$stmt = $pdo->prepare($sql . " AND o.user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$saya = $stmt->fetch();

// Dampak seluruh pengguna
$semua = $pdo->query($sql)->fetch();

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pembeli.css">

<div class="wide-wrap">
    <h1>Dampakmu</h1>
    <p class="auth-sub">
        Setiap pesanan yang kamu ambil berarti makanan layak yang tidak berakhir di tempat sampah.
        <a href="/rescuebite-food-rescue/pembeli/riwayat.php">← Pesanan saya</a>
    </p>

    <div class="dampak-grid">
        <div class="dampak-kartu">
            <strong><?= (int) $saya["porsi"] ?></strong>
            <span>Porsi terselamatkan</span>
        </div>
        <div class="dampak-kartu">
            <strong><?= rupiah($saya["hemat"]) ?></strong>
            <span>Uang yang kamu hemat</span>
        </div>
        <div class="dampak-kartu">
            <strong><?= (int) $saya["jml_pesanan"] ?></strong>
            <span>Pesanan selesai</span>
        </div>
    </div>

    <?php if ((int) $saya["jml_pesanan"] === 0): ?>
        <div class="kosong">
            <p>Belum ada pesanan yang selesai. Dampakmu muncul setelah kamu mengambil pesanan pertama.</p>
            <a href="/rescuebite-food-rescue/index.php#katalog" class="btn-aksi">Cari makanan</a>
        </div>
    <?php endif; ?>

    <h2 class="judul-komunitas">Dampak seluruh pengguna Rescuebite</h2>
    <div class="dampak-komunitas">
        <div><strong><?= (int) $semua["porsi"] ?></strong> porsi terselamatkan</div>
        <div><strong><?= rupiah($semua["hemat"]) ?></strong> total penghematan</div>
        <div><strong><?= (int) $semua["jml_pesanan"] ?></strong> pesanan selesai</div>
    </div>

    <p class="catatan-sdg">
        Mendukung SDG 2 (Tanpa Kelaparan) dan SDG 12 (Konsumsi dan Produksi yang Bertanggung Jawab).
    </p>
</div>

<?php require_once "../includes/footer.php"; ?>