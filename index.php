<?php
session_start();
require_once "config/database.php";

$jml_mitra = $pdo->query(
    "SELECT COUNT(*) FROM partners WHERE status_verifikasi = 'terverifikasi'"
)->fetchColumn();
$jml_pembeli = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'pembeli'"
)->fetchColumn();

try {
    $jml_porsi = $pdo->query(
        "SELECT COALESCE(SUM(jumlah), 0) FROM orders WHERE status_pesanan = 'selesai'"
    )->fetchColumn();
} catch (PDOException $e) {
    $jml_porsi = 0;
}

// Ambil kategori untuk dropdown filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

// Parameter pencarian & filter
$q           = trim($_GET["q"] ?? "");
$kategori_id = (int) ($_GET["kategori"] ?? 0);
$urut        = $_GET["urut"] ?? "terbaru";

$sekarang = date("Y-m-d H:i:s");
$where  = [
    "p.status_verifikasi = 'terverifikasi'",
    "l.status = 'aktif'",
    "l.batas_jam_ambil >= ?", 
];
$params = [$sekarang];

if ($q !== "") {
    $where[]  = "(l.nama_makanan LIKE ? OR p.nama_toko LIKE ? OR p.alamat LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($kategori_id > 0) {
    $where[]  = "l.category_id = ?";
    $params[] = $kategori_id;
}

$order_sql = "l.created_at DESC";
if ($urut === "termurah") {
    $order_sql = "l.harga_diskon ASC";
} elseif ($urut === "batas_ambil") {
    $order_sql = "l.batas_jam_ambil ASC";
}

// Yang masih ada stok tampil di atas, yang stok 0 (sold out) tampil di bawah
$sql = "SELECT l.*, c.nama AS nama_kategori, p.nama_toko, p.alamat
        FROM listings l
        JOIN partners p ON l.partner_id = p.id
        JOIN categories c ON l.category_id = c.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY (l.stok > 0) DESC, $order_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

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
        <p>Halo, <strong><?= htmlspecialchars($_SESSION["nama"]) ?></strong>! Pilih makanan surplus di bawah sebelum kehabisan.</p>
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
    <div class="stat-item"><strong><?= (int) $jml_porsi ?></strong><span>Porsi terselamatkan</span></div>
</section>

<section class="blok" id="katalog">
    <h2 class="judul-blok">Katalog Makanan Surplus</h2>

    <form method="GET" action="index.php#katalog" class="filter-bar">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari makanan atau nama toko...">
        <select name="kategori">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c["id"] ?>" <?= $kategori_id === (int) $c["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nama"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="urut">
            <option value="terbaru" <?= $urut === "terbaru" ? "selected" : "" ?>>Terbaru</option>
            <option value="termurah" <?= $urut === "termurah" ? "selected" : "" ?>>Harga Termurah</option>
            <option value="batas_ambil" <?= $urut === "batas_ambil" ? "selected" : "" ?>>Segera Berakhir</option>
        </select>
        <button type="submit">Cari</button>
    </form>

    <?php if ($listings): ?>
        <div class="grid-3">
            <?php foreach ($listings as $item): 
                $sold_out = (int) $item["stok"] <= 0;
            ?>
                <div class="kartu-makanan <?= $sold_out ? 'sold-out' : '' ?>">
                    <div class="foto-wrap">
                        <img src="assets/uploads/<?= htmlspecialchars($item["foto"]) ?>" alt="<?= htmlspecialchars($item["nama_makanan"]) ?>">
                        <?php if ($sold_out): ?>
                            <span class="label-soldout">SOLD OUT</span>
                        <?php endif; ?>
                    </div>
                    <div class="isi">
                        <small style="color:var(--hijau-tua); font-weight:600;">
                            <?= htmlspecialchars($item["nama_kategori"]) ?> · <?= htmlspecialchars($item["nama_toko"]) ?>
                        </small>
                        <h3><?= htmlspecialchars($item["nama_makanan"]) ?></h3>
                        <p><?= htmlspecialchars($item["deskripsi"] ?: "Makanan surplus layak konsumsi.") ?></p>
                        <p style="font-size:0.8rem; margin-bottom:10px;">
                            Alamat: <?= htmlspecialchars($item["alamat"]) ?><br>
                            Ambil sebelum: <strong><?= date("d M H:i", strtotime($item["batas_jam_ambil"])) ?></strong> · 
                            Stok: <strong><?= (int) $item["stok"] ?></strong>
                        </p>
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                            <span class="harga">
                                <s>Rp <?= number_format($item["harga_asli"], 0, ",", ".") ?></s>
                                Rp <?= number_format($item["harga_diskon"], 0, ",", ".") ?>
                            </span>

                            <?php if ($sold_out): ?>
                                <span class="badge badge-ditolak">Habis</span>
                            <?php elseif (!isset($_SESSION["user_id"])): ?>
                                <a href="auth/login.php" class="btn-solid" style="padding:8px 16px; font-size:0.85rem;">Pesan</a>
                            <?php elseif ($_SESSION["role"] === "pembeli"): ?>
                                <a href="pembeli/pesan.php?id=<?= $item["id"] ?>" class="btn-solid" style="padding:8px 16px; font-size:0.85rem;">Pesan</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($q !== "" || $kategori_id > 0): ?>
        <p class="catatan-sdg">Tidak ada makanan surplus yang cocok dengan pencarian Anda.</p>
    <?php else: ?>
        <!-- Tampilan contoh bawaan dari Anggota A jika belum ada mitra yang posting -->
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
    <?php endif; ?>
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