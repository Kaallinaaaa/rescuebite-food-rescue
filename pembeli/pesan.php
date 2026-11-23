<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

// Buat kode pickup unik, contoh: RB-7K2M (tanpa huruf/angka yang mirip: I, O, 0, 1)
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

// Link dari beranda memakai ?id=, form di bawah mengirim id lewat input hidden
$listing_id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);
if ($listing_id <= 0) {
    header("Location: /rescuebite-food-rescue/index.php");
    exit;
}

$error = "";

// ---------- PROSES PEMESANAN ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jumlah = (int) ($_POST["jumlah"] ?? 0);

    if ($jumlah < 1) {
        $error = "Jumlah minimal 1 porsi.";
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris listing: pemesan lain menunggu sampai transaksi ini selesai,
            // jadi stok terakhir tidak bisa terjual dua kali.
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

            // Kurangi stok (kalau jadi 0, beranda otomatis menandainya SOLD OUT)
            $pdo->prepare("UPDATE listings SET stok = stok - ? WHERE id = ?")
                ->execute([$jumlah, $listing_id]);

            // Simpan pesanan
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

// ---------- DATA UNTUK TAMPILAN ----------
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

require_once "../includes/header.php";
?>

<div class="wide-wrap">
    <h1>Pesan Makanan</h1>
    <p class="auth-sub"><a href="/rescuebite-food-rescue/index.php#katalog">← Kembali ke katalog</a></p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!$l): ?>
        <p class="alert">Makanan tidak ditemukan.</p>
    <?php else: ?>
        <div class="kartu-makanan" style="margin-bottom:16px;">
            <img src="/rescuebite-food-rescue/assets/uploads/<?= htmlspecialchars($l["foto"]) ?>"
                 alt="<?= htmlspecialchars($l["nama_makanan"]) ?>">
            <div class="isi">
                <small style="color:var(--hijau-tua); font-weight:600;">
                    <?= htmlspecialchars($l["nama_kategori"]) ?> · <?= htmlspecialchars($l["nama_toko"]) ?>
                </small>
                <h3><?= htmlspecialchars($l["nama_makanan"]) ?></h3>
                <p><?= htmlspecialchars($l["deskripsi"] ?: "Makanan surplus layak konsumsi.") ?></p>
                <p style="font-size:0.8rem;">
                    Alamat: <?= htmlspecialchars($l["alamat"]) ?><br>
                    Ambil sebelum: <strong><?= date("d M H:i", strtotime($l["batas_jam_ambil"])) ?></strong> ·
                    Stok: <strong><?= (int) $l["stok"] ?></strong>
                </p>
                <span class="harga">
                    <s><?= rupiah($l["harga_asli"]) ?></s> <?= rupiah($l["harga_diskon"]) ?> / porsi
                </span>
            </div>
        </div>

        <?php if ($tersedia): ?>
            <form method="POST">
                <input type="hidden" name="id" value="<?= (int) $l["id"] ?>">

                <label for="jumlah">Jumlah porsi</label>
                <input type="number" id="jumlah" name="jumlah" value="1" min="1"
                       max="<?= (int) $l["stok"] ?>" data-harga="<?= (int) $l["harga_diskon"] ?>" required>

                <p style="margin:14px 0 0;">Total: <strong class="harga" id="total"><?= rupiah($l["harga_diskon"]) ?></strong></p>

                <button type="submit">Pesan Sekarang</button>
            </form>

            <script>
            const jumlah = document.getElementById("jumlah");
            const total  = document.getElementById("total");
            jumlah.addEventListener("input", function () {
                const n = parseInt(jumlah.value) || 0;
                total.textContent = "Rp " + (n * parseInt(jumlah.dataset.harga)).toLocaleString("id-ID");
            });
            </script>
        <?php else: ?>
            <p class="alert">Maaf, makanan ini sudah habis atau melewati batas jam ambil.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>