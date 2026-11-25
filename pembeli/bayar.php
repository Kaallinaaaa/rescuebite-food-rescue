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

if (!$o || $o["status_pesanan"] !== "menunggu_pickup") {
    header("Location: /pembeli/riwayat.php");
    exit;
}

$metode_tersedia = ["QRIS", "Transfer Bank", "GoPay", "OVO", "DANA"];

// Keterangan singkat di tiap kartu metode (hanya tampilan)
$ket_metode = [
    "QRIS"          => "Scan dari aplikasi e-wallet atau m-banking apa pun",
    "Transfer Bank" => "Transfer dari rekening bank",
    "GoPay"         => "Dompet digital",
    "OVO"           => "Dompet digital",
    "DANA"          => "Dompet digital",
];

$error = "";

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
<link rel="stylesheet" href="/assets/css/bayar.css">

<div class="by-halaman">
    <a href="/pembeli/riwayat.php" class="by-kembali">← Pesanan saya</a>
    <h1><?= $lunas ? "Pembayaran berhasil" : "Pembayaran" ?></h1>

    <?php if ($error): ?>
        <p class="by-pesan" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Ringkasan pesanan (gaya struk) -->
    <section class="by-struk">
        <h2><?= htmlspecialchars($o["nama_makanan"]) ?></h2>
        <p class="by-toko"><?= htmlspecialchars($o["nama_toko"]) ?> · <?= htmlspecialchars($o["alamat"]) ?></p>

        <dl class="by-rincian">
            <div>
                <dt>Jumlah</dt>
                <dd><?= (int) $o["jumlah"] ?> porsi</dd>
            </div>
            <div>
                <dt>Ambil sebelum</dt>
                <dd><?= date("d M, H:i", strtotime($o["batas_jam_ambil"])) ?></dd>
            </div>
        </dl>

        <div class="by-total">
            <span>Total</span>
            <strong><?= rupiah($o["total_harga"]) ?></strong>
        </div>
    </section>

    <?php if ($lunas): ?>

        <section class="by-berhasil">
            <span class="by-centang" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </span>
            <p>Terima kasih! Pembayaran via <strong><?= htmlspecialchars($o["metode"]) ?></strong> sudah diterima.</p>
            <small>Tunjukkan kode ini ke toko saat mengambil pesanan</small>
            <div class="by-kode"><?= htmlspecialchars($o["kode_pickup"]) ?></div>
            <div class="by-batas">Ambil sebelum <strong><?= date("d M, H:i", strtotime($o["batas_jam_ambil"])) ?></strong></div>
        </section>

        <a href="/pembeli/riwayat.php" class="by-lihat">Lihat pesanan saya</a>

    <?php else: ?>

        <form method="POST" class="by-form">
            <input type="hidden" name="order_id" value="<?= (int) $o["id"] ?>">

            <fieldset>
                <legend>Pilih metode pembayaran</legend>
                <div class="by-metode-daftar">
                    <?php foreach ($metode_tersedia as $m): ?>
                        <label class="by-metode">
                            <input type="radio" name="metode" value="<?= htmlspecialchars($m) ?>" required>
                            <span class="by-metode-isi">
                                <span class="by-radio" aria-hidden="true"></span>
                                <span class="by-metode-teks">
                                    <b><?= htmlspecialchars($m) ?></b>
                                    <small><?= htmlspecialchars($ket_metode[$m] ?? "") ?></small>
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <p class="by-catatan">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                <span>Pembayaran ini simulasi, tidak ada uang sungguhan yang dipotong.</span>
            </p>

            <button type="submit" class="by-bayar">Bayar <?= rupiah($o["total_harga"]) ?></button>
        </form>

    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
