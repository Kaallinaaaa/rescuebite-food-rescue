<?php
session_start();
require_once "../includes/auth_check.php";
require_role("mitra");
require_once "../config/database.php";

$user_id = $_SESSION["user_id"];
$error   = "";
$sukses  = "";

// Ambil data mitra berdasarkan user_id yang login
$stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->execute([$user_id]);
$mitra = $stmt->fetch();

if (!$mitra) {
    die("Data toko mitra tidak ditemukan.");
}

// Proses konfirmasi kode pickup dari pembeli
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["konfirmasi_pickup"])) {
    $kode = strtoupper(trim($_POST["kode_pickup"] ?? ""));

    if ($kode === "") {
        $error = "Kode pickup wajib diisi.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT o.*, l.nama_makanan, u.nama AS nama_pembeli
            FROM orders o
            JOIN listings l ON o.listing_id = l.id
            JOIN users u ON o.user_id = u.id
            WHERE o.kode_pickup = ? AND l.partner_id = ?"
        );
        $stmt->execute([$kode, $mitra["id"]]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = "Kode pickup tidak valid atau bukan milik toko ini.";
        } elseif ($order["status_pesanan"] === "selesai") {
            $error = "Pesanan dengan kode ini sudah dikonfirmasi selesai.";
        } elseif ($order["status_pesanan"] === "dibatalkan") {
            $error = "Pesanan ini telah dibatalkan.";
        } else {
            $pdo->prepare("UPDATE orders SET status_pesanan = 'selesai' WHERE id = ?")
                ->execute([$order["id"]]);
            $sukses = "Pickup berhasil! Pesanan {$order['nama_makanan']} ({$order['jumlah']}x) oleh {$order['nama_pembeli']} selesai.";
        }
    }
}

// Hitung statistik untuk card menu
$stmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE partner_id = ?");
$stmt->execute([$mitra["id"]]);
$jml_listing = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM orders o 
    JOIN listings l ON o.listing_id = l.id 
    WHERE l.partner_id = ? AND o.status_pesanan = 'menunggu_pickup'"
);
$stmt->execute([$mitra["id"]]);
$jml_masuk = $stmt->fetchColumn();

// Ambil daftar pesanan masuk (menunggu pickup)
$stmt = $pdo->prepare(
    "SELECT o.*, l.nama_makanan, u.nama AS nama_pembeli, u.no_hp
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.user_id = u.id
    WHERE l.partner_id = ? AND o.status_pesanan = 'menunggu_pickup'
    ORDER BY o.created_at ASC"
);
$stmt->execute([$mitra["id"]]);
$pesanan_masuk = $stmt->fetchAll();

// Ambil riwayat penjualan (selesai)
$stmt = $pdo->prepare(
    "SELECT o.*, l.nama_makanan, u.nama AS nama_pembeli
    FROM orders o
    JOIN listings l ON o.listing_id = l.id
    JOIN users u ON o.user_id = u.id
    WHERE l.partner_id = ? AND o.status_pesanan = 'selesai'
    ORDER BY o.created_at DESC LIMIT 10"
);
$stmt->execute([$mitra["id"]]);
$riwayat_penjualan = $stmt->fetchAll();

require_once "../includes/header.php";
?>

<div class="wide-wrap">
    <h1>Dashboard Mitra</h1>
    <p class="auth-sub">
        <?= htmlspecialchars($mitra["nama_toko"]) ?>
        <span class="badge badge-<?= $mitra["status_verifikasi"] ?>"><?= $mitra["status_verifikasi"] ?></span>
    </p>

    <?php if ($mitra["status_verifikasi"] !== "terverifikasi"): ?>
        <p class="alert">
            Status toko Anda masih <strong><?= $mitra["status_verifikasi"] ?></strong>. 
            Anda dapat memposting makanan surplus setelah diverifikasi oleh Admin.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p class="alert-ok"><?= htmlspecialchars($sukses) ?></p>
    <?php endif; ?>

    <div class="menu-admin" style="grid-template-columns: 1fr 1fr; margin-bottom: 24px;">
        <a href="listing.php" class="menu-card">
            Kelola Listing Surplus
            <span><?= (int) $jml_listing ?> makanan diposting</span>
        </a>
        <div class="menu-card">
            Pesanan Menunggu Pickup
            <span><?= (int) $jml_masuk ?> pesanan aktif</span>
        </div>
    </div>

    <?php if ($mitra["status_verifikasi"] === "terverifikasi"): ?>
        <form method="POST" class="aksi tambah-kategori">
            <input type="text" name="kode_pickup" placeholder="Masukkan kode pickup pembeli (misal: RB-1234)" required>
            <button type="submit" name="konfirmasi_pickup">Konfirmasi Pickup</button>
        </form>
    <?php endif; ?>

    <h2 class="sub-judul">Pesanan Masuk</h2>
    <?php if (!$pesanan_masuk): ?>
        <p class="auth-sub" style="text-align:left;">Belum ada pesanan yang menunggu diambil.</p>
    <?php else: ?>
        <?php foreach ($pesanan_masuk as $p): ?>
            <div class="card-mitra">
                <div>
                    <strong><?= htmlspecialchars($p["nama_makanan"]) ?> (<?= (int) $p["jumlah"] ?>x)</strong>
                    <span class="badge badge-menunggu">Kode: <?= htmlspecialchars($p["kode_pickup"]) ?></span><br>
                    <small>
                        Pembeli: <?= htmlspecialchars($p["nama_pembeli"]) ?>
                        <?= $p["no_hp"] ? " · " . htmlspecialchars($p["no_hp"]) : "" ?>
                        · Total: Rp <?= number_format($p["total_harga"], 0, ",", ".") ?>
                    </small>
                </div>
                <form method="POST" class="aksi">
                    <input type="hidden" name="kode_pickup" value="<?= htmlspecialchars($p["kode_pickup"]) ?>">
                    <button type="submit" name="konfirmasi_pickup" class="btn-ok">Selesai</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 class="sub-judul">Riwayat Penjualan</h2>
    <?php if (!$riwayat_penjualan): ?>
        <p class="auth-sub" style="text-align:left;">Belum ada riwayat penjualan selesai.</p>
    <?php else: ?>
        <?php foreach ($riwayat_penjualan as $r): ?>
            <div class="card-mitra">
                <div>
                    <strong><?= htmlspecialchars($r["nama_makanan"]) ?> (<?= (int) $r["jumlah"] ?>x)</strong>
                    <span class="badge badge-terverifikasi">selesai</span><br>
                    <small>
                        Pembeli: <?= htmlspecialchars($r["nama_pembeli"]) ?> · 
                        Rp <?= number_format($r["total_harga"], 0, ",", ".") ?> · 
                        <?= date("d M Y H:i", strtotime($r["created_at"])) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>