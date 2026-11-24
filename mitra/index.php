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

$terverifikasi = $mitra["status_verifikasi"] === "terverifikasi";

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/mitra-dashboard.css">

<div class="mt-halaman">

    <div class="mt-kepala">
        <div>
            <h1>Dashboard Mitra</h1>
            <p class="mt-toko"><strong><?= htmlspecialchars($mitra["nama_toko"]) ?></strong></p>
        </div>
        <span class="mt-verif mt-verif--<?= htmlspecialchars($mitra["status_verifikasi"]) ?>">
            <?= htmlspecialchars($mitra["status_verifikasi"]) ?>
        </span>
    </div>

    <?php if (!$terverifikasi): ?>
        <p class="mt-pesan mt-pesan--info">
            Status toko Anda masih <strong><?= htmlspecialchars($mitra["status_verifikasi"]) ?></strong>.
            Anda dapat memposting makanan surplus setelah diverifikasi oleh Admin.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="mt-pesan mt-pesan--salah" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p class="mt-pesan mt-pesan--ok" role="status"><?= htmlspecialchars($sukses) ?></p>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="mt-stat-baris">
        <a href="listing.php" class="mt-stat">
            <div class="mt-stat-isi">
                <b><?= (int) $jml_listing ?></b>
                <span>makanan diposting</span>
                <strong>Kelola listing surplus</strong>
            </div>
            <svg class="mt-stat-panah" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </a>
        <div class="mt-stat <?= $jml_masuk ? 'mt-stat--sorot' : '' ?>">
            <div class="mt-stat-isi">
                <b><?= (int) $jml_masuk ?></b>
                <span>pesanan aktif</span>
                <strong>Menunggu pickup</strong>
            </div>
        </div>
    </div>

    <!-- Konfirmasi pickup -->
    <?php if ($terverifikasi): ?>
        <section class="mt-pickup">
            <h2>Konfirmasi pickup</h2>
            <p>Minta pembeli menunjukkan kode pickup, lalu masukkan di sini.</p>
            <form method="POST">
                <input type="text" name="kode_pickup" placeholder="Contoh: RB-1234" autocomplete="off" required aria-label="Kode pickup pembeli">
                <button type="submit" name="konfirmasi_pickup">Konfirmasi pickup</button>
            </form>
        </section>
    <?php endif; ?>

    <!-- Pesanan masuk -->
    <section class="mt-blok">
        <div class="mt-bagian">
            <h2>Pesanan masuk</h2>
            <?php if ($pesanan_masuk): ?><span><?= count($pesanan_masuk) ?> menunggu diambil</span><?php endif; ?>
        </div>

        <?php if (!$pesanan_masuk): ?>
            <p class="mt-kosong">Belum ada pesanan yang menunggu diambil.</p>
        <?php else: ?>
            <div class="mt-daftar">
                <?php foreach ($pesanan_masuk as $p): ?>
                    <article class="mt-pesanan">
                        <div>
                            <h3><?= htmlspecialchars($p["nama_makanan"]) ?> <small>(<?= (int) $p["jumlah"] ?>x)</small></h3>
                            <p class="mt-info">
                                <strong><?= htmlspecialchars($p["nama_pembeli"]) ?></strong>
                                <?= $p["no_hp"] ? " · " . htmlspecialchars($p["no_hp"]) : "" ?><br>
                                Total Rp <?= number_format($p["total_harga"], 0, ",", ".") ?>
                                · Dipesan <?= date("d M, H:i", strtotime($p["created_at"])) ?>
                            </p>
                        </div>
                        <div class="mt-kode-kotak">
                            <small>Kode pickup</small>
                            <span class="mt-kode"><?= htmlspecialchars($p["kode_pickup"]) ?></span>
                        </div>
                        <form method="POST" class="mt-aksi" onsubmit="return confirm('Tandai pesanan ini sudah diambil?');">
                            <input type="hidden" name="kode_pickup" value="<?= htmlspecialchars($p["kode_pickup"]) ?>">
                            <button type="submit" name="konfirmasi_pickup" class="mt-selesai">Selesai</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Riwayat penjualan -->
    <section class="mt-blok">
        <div class="mt-bagian">
            <h2>Riwayat penjualan</h2>
            <?php if ($riwayat_penjualan): ?><span>10 terakhir</span><?php endif; ?>
        </div>

        <?php if (!$riwayat_penjualan): ?>
            <p class="mt-kosong">Belum ada riwayat penjualan selesai.</p>
        <?php else: ?>
            <div class="mt-riwayat">
                <?php foreach ($riwayat_penjualan as $r): ?>
                    <div class="mt-baris">
                        <div class="mt-baris-judul">
                            <strong><?= htmlspecialchars($r["nama_makanan"]) ?> (<?= (int) $r["jumlah"] ?>x)</strong>
                            <span class="mt-lencana">Selesai</span>
                        </div>
                        <div class="mt-total">Rp <?= number_format($r["total_harga"], 0, ",", ".") ?></div>
                        <p class="mt-info">
                            <?= htmlspecialchars($r["nama_pembeli"]) ?>
                            · <?= date("d M Y, H:i", strtotime($r["created_at"])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once "../includes/footer.php"; ?>
