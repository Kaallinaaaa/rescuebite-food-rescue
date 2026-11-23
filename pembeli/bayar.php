<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

function ambil_order($pdo, $order_id, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT o.*, l.nama_makanan, l.batas_jam_ambil, p.nama_toko, p.alamat,
                py.status AS status_bayar, py.metode
         FROM orders o
         JOIN listings l ON o.listing_id = l.id
         JOIN partners p ON l.partner_id = p.id
         LEFT JOIN payments py ON py.order_id = o.id
         WHERE o.id = ? AND o.user_id = ?"
    );
    $stmt->execute([$order_id, $user_id]);
    return $stmt->fetch();
}

$order_id = (int) ($_GET["order_id"] ?? $_POST["order_id"] ?? 0);
$o = ambil_order($pdo, $order_id, $_SESSION["user_id"]);

// Pesanan harus milik pembeli ini dan masih aktif (menunggu pickup)
if (!$o || $o["status_pesanan"] !== "menunggu_pickup") {
    header("Location: /rescuebite-food-rescue/pembeli/riwayat.php");
    exit;
}

$metode_tersedia = ["QRIS", "Transfer Bank", "GoPay", "OVO", "DANA"];
$error = "";

// ---------- PROSES PEMBAYARAN (SIMULASI) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $o["status_bayar"] !== "lunas") {
    $metode = $_POST["metode"] ?? "";

    if (!in_array($metode, $metode_tersedia, true)) {
        $error = "Pilih metode pembayaran terlebih dahulu.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO payments (order_id, metode, status, paid_at)
                 VALUES (?, ?, 'lunas', NOW())
                 ON DUPLICATE KEY UPDATE metode = VALUES(metode), status = 'lunas', paid_at = NOW()"
            )->execute([$o["id"], $metode]);
            $o = ambil_order($pdo, $order_id, $_SESSION["user_id"]);
        } catch (PDOException $e) {
            $error = "Pembayaran gagal diproses. Coba lagi.";
        }
    }
}

$lunas = $o["status_bayar"] === "lunas";

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pembeli.css">

<div class="wide-wrap">
    <h1><?= $lunas ? "Pembayaran Berhasil" : "Pembayaran" ?></h1>
    <p class="auth-sub"><a href="/rescuebite-food-rescue/pembeli/riwayat.php">← Pesanan saya</a></p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="kartu-pesanan">
        <div class="kp-atas">
            <div>
                <strong><?= htmlspecialchars($o["nama_makanan"]) ?></strong><br>
                <small><?= htmlspecialchars($o["nama_toko"]) ?> · <?= htmlspecialchars($o["alamat"]) ?></small>
            </div>
            <div class="kp-total"><?= rupiah($o["total_harga"]) ?></div>
        </div>
        <div class="kp-info">
            <span><?= (int) $o["jumlah"] ?> porsi</span>
            <span>Ambil sebelum <strong><?= date("d M, H:i", strtotime($o["batas_jam_ambil"])) ?></strong></span>
        </div>
    </div>

    <?php if ($lunas): ?>
        <div class="sukses-box">
            <p style="margin-top:0;">Terima kasih! Pembayaran via <strong><?= htmlspecialchars($o["metode"]) ?></strong> sudah diterima.</p>
            <small>Tunjukkan kode ini ke toko saat mengambil pesanan</small>
            <div class="kode"><?= htmlspecialchars($o["kode_pickup"]) ?></div>
        </div>
        <div class="kp-aksi">
            <a href="/rescuebite-food-rescue/pembeli/riwayat.php" class="btn-aksi">Lihat pesanan saya</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="order_id" value="<?= (int) $o["id"] ?>">

            <label for="metode">Metode pembayaran</label>
            <select id="metode" name="metode" required>
                <option value="">Pilih metode</option>
                <?php foreach ($metode_tersedia as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>

            <p class="total-bayar">Total bayar: <strong class="harga"><?= rupiah($o["total_harga"]) ?></strong></p>
            <p class="catatan-sim">Pembayaran ini simulasi, tidak ada uang sungguhan yang dipotong.</p>

            <button type="submit">Bayar <?= rupiah($o["total_harga"]) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>