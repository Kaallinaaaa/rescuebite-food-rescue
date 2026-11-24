<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

function buat_kode($pdo) {
    $karakter = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    for ($i = 0; $i < 10; $i++) {
        $kode = "RB-";
        for ($j = 0; $j < 4; $j++) {
            $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
        }
        $cek = $pdo->prepare("SELECT 1 FROM orders WHERE kode_pickup = ?");
        $cek->execute([$kode]);
        if (!$cek->fetch()) {
            return $kode;
        }
    }
    throw new RuntimeException("Gagal membuat kode pickup, coba lagi.");
}

$listing_id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);
if ($listing_id <= 0) {
    header("Location: /rescuebite-food-rescue/index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jumlah = (int) ($_POST["jumlah"] ?? 0);

    if ($jumlah < 1) {
        $error = "Jumlah minimal 1 porsi.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? FOR UPDATE");
            $stmt->execute([$listing_id]);
            $l = $stmt->fetch();

            if (!$l) {
                throw new RuntimeException("Makanan tidak ditemukan.");
            }

            $stmt = $pdo->prepare("SELECT status_verifikasi FROM partners WHERE id = ?");
            $stmt->execute([$l["partner_id"]]);
            if ($stmt->fetchColumn() !== "terverifikasi") {
                throw new RuntimeException("Toko ini belum terverifikasi.");
            }

            if ($l["status"] !== "aktif") {
                throw new RuntimeException("Makanan ini sedang tidak tersedia.");
            }
            if (strtotime($l["batas_jam_ambil"]) < time()) {
                throw new RuntimeException("Maaf, batas jam ambil makanan ini sudah lewat.");
            }

            $stok = (int) $l["stok"];
            if ($stok <= 0) {
                throw new RuntimeException("Maaf, makanan ini sudah habis.");
            }
            if ($jumlah > $stok) {
                throw new RuntimeException("Stok tinggal $stok porsi.");
            }

            $pdo->prepare("UPDATE listings SET stok = stok - ? WHERE id = ?")
                ->execute([$jumlah, $listing_id]);

            $kode  = buat_kode($pdo);
            $total = (int) $l["harga_diskon"] * $jumlah;
            $pdo->prepare(
                "INSERT INTO orders (user_id, listing_id, jumlah, total_harga, kode_pickup, status_pesanan)
                 VALUES (?, ?, ?, ?, ?, 'menunggu_pickup')"
            )->execute([$_SESSION["user_id"], $listing_id, $jumlah, $total, $kode]);
            $order_id = $pdo->lastInsertId();

            $pdo->commit();

            header("Location: /rescuebite-food-rescue/pembeli/bayar.php?order_id=" . $order_id);
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Terjadi kesalahan saat memproses pesanan. Coba lagi.";
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT l.*, c.nama AS nama_kategori, p.nama_toko, p.alamat, p.status_verifikasi
     FROM listings l
     JOIN partners p ON l.partner_id = p.id
     JOIN categories c ON l.category_id = c.id
     WHERE l.id = ?"
);
$stmt->execute([$listing_id]);
$l = $stmt->fetch();

$tersedia = $l
    && $l["status"] === "aktif"
    && $l["status_verifikasi"] === "terverifikasi"
    && (int) $l["stok"] > 0
    && strtotime($l["batas_jam_ambil"]) >= time();

// Persentase hemat untuk lencana di foto (hanya tampilan)
$persen_hemat = 0;
if ($l && (int) $l["harga_asli"] > (int) $l["harga_diskon"]) {
    $persen_hemat = (int) round((1 - $l["harga_diskon"] / $l["harga_asli"]) * 100);
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pesan.css">

<div class="ps-halaman">
    <a href="/rescuebite-food-rescue/index.php#katalog" class="ps-kembali">← Kembali ke katalog</a>
    <h1>Pesan makanan</h1>

    <?php if ($error): ?>
        <p class="ps-pesan" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!$l): ?>
        <p class="ps-pesan" role="alert">Makanan tidak ditemukan.</p>
    <?php else: ?>
        <div class="ps-grid">

            <!-- Kolom kiri: info makanan -->
            <article class="ps-makanan">
                <div class="ps-foto">
                    <img src="/rescuebite-food-rescue/assets/uploads/<?= htmlspecialchars($l["foto"]) ?>"
                         alt="<?= htmlspecialchars($l["nama_makanan"]) ?>">
                    <?php if ($persen_hemat > 0): ?>
                        <span class="ps-hemat">Hemat <?= $persen_hemat ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="ps-isi">
                    <p class="ps-asal"><?= htmlspecialchars($l["nama_kategori"]) ?> · <?= htmlspecialchars($l["nama_toko"]) ?></p>
                    <h2><?= htmlspecialchars($l["nama_makanan"]) ?></h2>
                    <p class="ps-deskripsi"><?= htmlspecialchars($l["deskripsi"] ?: "Makanan surplus layak konsumsi.") ?></p>

                    <dl class="ps-detail">
                        <div>
                            <dt>Alamat</dt>
                            <dd><?= htmlspecialchars($l["alamat"]) ?></dd>
                        </div>
                        <div>
                            <dt>Ambil sebelum</dt>
                            <dd><?= date("d M, H:i", strtotime($l["batas_jam_ambil"])) ?></dd>
                        </div>
                        <div>
                            <dt>Stok</dt>
                            <dd>
                                <?= (int) $l["stok"] ?> porsi
                                <?php if ((int) $l["stok"] > 0 && (int) $l["stok"] <= 3): ?>
                                    <span class="ps-sisa">Hampir habis</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </article>

            <!-- Kolom kanan: panel pesan -->
            <?php if ($tersedia): ?>
                <form method="POST" class="ps-panel">
                    <input type="hidden" name="id" value="<?= (int) $l["id"] ?>">

                    <?php if ((int) $l["harga_asli"] > (int) $l["harga_diskon"]): ?>
                        <p class="ps-harga-asli"><?= rupiah($l["harga_asli"]) ?></p>
                    <?php endif; ?>
                    <p class="ps-harga">
                        <strong><?= rupiah($l["harga_diskon"]) ?></strong>
                        <span>/ porsi</span>
                    </p>

                    <label for="jumlah">Jumlah porsi</label>
                    <div class="ps-stepper">
                        <button type="button" id="kurang" aria-label="Kurangi jumlah">−</button>
                        <input type="number" id="jumlah" name="jumlah" value="1" min="1"
                               max="<?= (int) $l["stok"] ?>" data-harga="<?= (int) $l["harga_diskon"] ?>" required>
                        <button type="button" id="tambah" aria-label="Tambah jumlah">+</button>
                    </div>
                    <p class="ps-maks">Maksimal <?= (int) $l["stok"] ?> porsi</p>

                    <div class="ps-total">
                        <span>Total</span>
                        <strong id="total"><?= rupiah($l["harga_diskon"]) ?></strong>
                    </div>

                    <button type="submit" class="ps-pesan-btn">Pesan sekarang</button>
                    <p class="ps-langkah">Langkah berikutnya: pilih metode pembayaran, lalu tunjukkan kode pickup ke toko.</p>
                </form>

                <script>
                (function () {
                    var input  = document.getElementById("jumlah");
                    var total  = document.getElementById("total");
                    var kurang = document.getElementById("kurang");
                    var tambah = document.getElementById("tambah");
                    var min = parseInt(input.min, 10);
                    var max = parseInt(input.max, 10);
                    var harga = parseInt(input.dataset.harga, 10);

                    function perbarui() {
                        var n = parseInt(input.value, 10) || 0;
                        total.textContent = "Rp " + (n * harga).toLocaleString("id-ID");
                        kurang.disabled = n <= min;
                        tambah.disabled = n >= max;
                    }

                    function atur(n) {
                        input.value = Math.min(max, Math.max(min, n));
                        perbarui();
                    }

                    kurang.addEventListener("click", function () { atur((parseInt(input.value, 10) || min) - 1); });
                    tambah.addEventListener("click", function () { atur((parseInt(input.value, 10) || min) + 1); });
                    input.addEventListener("input", perbarui);
                    input.addEventListener("change", function () { atur(parseInt(input.value, 10) || min); });
                    perbarui();
                })();
                </script>
            <?php else: ?>
                <div class="ps-habis">
                    <p>Maaf, makanan ini sudah habis atau melewati batas jam ambil.</p>
                    <a href="/rescuebite-food-rescue/index.php#katalog" class="ps-btn-hijau">Cari makanan lain</a>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
