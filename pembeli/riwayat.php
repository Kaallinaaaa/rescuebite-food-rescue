<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

$stmt = $pdo->prepare(
    "SELECT o.*, l.nama_makanan, l.batas_jam_ambil, p.nama_toko, p.alamat,
            py.status AS status_bayar, py.metode,
            r.id AS review_id
     FROM orders o
     JOIN listings l ON o.listing_id = l.id
     JOIN partners p ON l.partner_id = p.id
     LEFT JOIN payments py ON py.order_id = o.id
     LEFT JOIN reviews r ON r.order_id = o.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$_SESSION["user_id"]]);
$pesanan = $stmt->fetchAll();

$label_status = [
    "menunggu_pickup" => ["Menunggu pickup", "badge-menunggu"],
    "selesai"         => ["Selesai",         "badge-terverifikasi"],
    "dibatalkan"      => ["Dibatalkan",      "badge-ditolak"],
];

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pembeli.css">

<div class="wide-wrap">
    <h1>Pesanan Saya</h1>
    <p class="auth-sub">
        Tunjukkan kode pickup ke toko sebelum batas jam ambil.
        <a href="/rescuebite-food-rescue/pembeli/dampak.php">Lihat dampakmu →</a>
    </p>

    <?php if (!$pesanan): ?>
        <div class="kosong">
            <p>Kamu belum punya pesanan.</p>
            <a href="/rescuebite-food-rescue/index.php#katalog" class="btn-aksi">Cari makanan</a>
        </div>
    <?php endif; ?>

    <?php foreach ($pesanan as $o):
        [$teks_status, $kelas_status] = $label_status[$o["status_pesanan"]] ?? [$o["status_pesanan"], "badge-menunggu"];
        $aktif = $o["status_pesanan"] === "menunggu_pickup";
        $lunas = $o["status_bayar"] === "lunas";
    ?>
        <div class="kartu-pesanan">
            <div class="kp-atas">
                <div>
                    <strong><?= htmlspecialchars($o["nama_makanan"]) ?></strong>
                    <span class="badge <?= $kelas_status ?>"><?= $teks_status ?></span><br>
                    <small><?= htmlspecialchars($o["nama_toko"]) ?> · <?= htmlspecialchars($o["alamat"]) ?></small>
                </div>
                <div class="kp-total"><?= rupiah($o["total_harga"]) ?></div>
            </div>

            <div class="kp-info">
                <span><?= (int) $o["jumlah"] ?> porsi</span>
                <span>Dipesan <?= date("d M Y, H:i", strtotime($o["created_at"])) ?></span>
                <?php if ($aktif): ?>
                    <span>Ambil sebelum <strong><?= date("d M, H:i", strtotime($o["batas_jam_ambil"])) ?></strong></span>
                <?php endif; ?>
                <span>Bayar: <strong><?= $o["status_bayar"] ? ucfirst($o["status_bayar"]) : "Belum dibayar" ?></strong></span>
            </div>

            <?php if ($aktif): ?>
                <div class="kode-box">
                    <small>Kode pickup</small>
                    <div class="kode"><?= htmlspecialchars($o["kode_pickup"]) ?></div>
                </div>
            <?php endif; ?>

            <div class="kp-aksi">
                <?php if ($aktif && !$lunas): ?>
                    <a href="/rescuebite-food-rescue/pembeli/bayar.php?order_id=<?= (int) $o["id"] ?>" class="btn-aksi">Bayar sekarang</a>
                <?php endif; ?>

                <?php if ($o["status_pesanan"] === "selesai" && !$o["review_id"]): ?>
                    <a href="/rescuebite-food-rescue/pembeli/review.php?order_id=<?= (int) $o["id"] ?>" class="btn-aksi">Beri ulasan</a>
                <?php elseif ($o["review_id"]): ?>
                    <span class="sudah">Sudah diulas ✓</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once "../includes/footer.php"; ?>