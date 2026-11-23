<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("mitra");

$user_id = $_SESSION["user_id"];
$error   = "";
$sukses  = "";

// Ambil data mitra dan cek verifikasi
$stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
$stmt->execute([$user_id]);
$mitra = $stmt->fetch();

if (!$mitra || $mitra["status_verifikasi"] !== "terverifikasi") {
    header("Location: index.php");
    exit;
}

// Ambil daftar kategori dari tabel categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

// Cek apakah sedang mode edit (?edit=ID)
$edit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND partner_id = ?");
    $stmt->execute([(int) $_GET["edit"], $mitra["id"]]);
    $edit = $stmt->fetch();
}

// Proses form (Tambah, Ubah, Hapus)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "hapus") {
        $id = (int) ($_POST["id"] ?? 0);
        $stmt = $pdo->prepare("SELECT foto FROM listings WHERE id = ? AND partner_id = ?");
        $stmt->execute([$id, $mitra["id"]]);
        $row = $stmt->fetch();

        if ($row) {
            $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
            $file_path = __DIR__ . "/../assets/uploads/" . $row["foto"];
            if ($row["foto"] && file_exists($file_path)) {
                unlink($file_path);
            }
            $sukses = "Listing berhasil dihapus.";
        }
    } elseif ($aksi === "tambah" || $aksi === "ubah") {
        $nama_makanan    = trim($_POST["nama_makanan"] ?? "");
        $category_id     = (int) ($_POST["category_id"] ?? 0);
        $deskripsi       = trim($_POST["deskripsi"] ?? "");
        $harga_asli      = (int) ($_POST["harga_asli"] ?? 0);
        $harga_diskon    = (int) ($_POST["harga_diskon"] ?? 0);
        $stok            = (int) ($_POST["stok"] ?? 0);
        $batas_jam_ambil = $_POST["batas_jam_ambil"] ?? "";

        if ($nama_makanan === "" || $category_id <= 0 || $batas_jam_ambil === "") {
            $error = "Nama makanan, kategori, dan batas jam ambil wajib diisi.";
        } elseif ($harga_asli <= 0 || $harga_diskon <= 0 || $harga_diskon >= $harga_asli) {
            $error = "Harga diskon harus lebih murah dari harga asli.";
        } elseif ($stok < 0) {
            $error = "Stok tidak boleh kurang dari 0.";
        } else {
            // Proses upload foto jika ada file dipilih
            $nama_foto = null;
            if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE) {
                $allowed_ext = ["jpg", "jpeg", "png", "webp"];
                $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));

                if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
                    $error = "Gagal mengunggah foto.";
                } elseif (!in_array($ext, $allowed_ext)) {
                    $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
                } elseif ($_FILES["foto"]["size"] > 3 * 1024 * 1024) {
                    $error = "Ukuran foto maksimal 3MB.";
                } else {
                    $nama_foto = "surplus_" . time() . "_" . rand(100, 999) . "." . $ext;
                    $dest = __DIR__ . "/../assets/uploads/" . $nama_foto;
                    if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $dest)) {
                        $error = "Gagal menyimpan foto ke folder uploads.";
                    }
                }
            }

            if ($error === "") {
                if ($aksi === "tambah") {
                    if (!$nama_foto) {
                        $error = "Foto makanan wajib diunggah.";
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO listings (partner_id, category_id, nama_makanan, deskripsi, foto, harga_asli, harga_diskon, stok, batas_jam_ambil)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->execute([
                            $mitra["id"], $category_id, $nama_makanan, $deskripsi,
                            $nama_foto, $harga_asli, $harga_diskon, $stok, $batas_jam_ambil
                        ]);
                        $sukses = "Makanan surplus berhasil diposting.";
                    }
                } elseif ($aksi === "ubah") {
                    $id = (int) ($_POST["id"] ?? 0);
                    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND partner_id = ?");
                    $stmt->execute([$id, $mitra["id"]]);
                    $lama = $stmt->fetch();

                    if ($lama) {
                        $foto_final = $nama_foto ?? $lama["foto"];
                        $stmt = $pdo->prepare(
                            "UPDATE listings 
                            SET category_id = ?, nama_makanan = ?, deskripsi = ?, foto = ?,
                                harga_asli = ?, harga_diskon = ?, stok = ?, batas_jam_ambil = ?
                            WHERE id = ? AND partner_id = ?"
                        );
                        $stmt->execute([
                            $category_id, $nama_makanan, $deskripsi, $foto_final,
                            $harga_asli, $harga_diskon, $stok, $batas_jam_ambil,
                            $id, $mitra["id"]
                        ]);

                        if ($nama_foto && $lama["foto"] && file_exists(__DIR__ . "/../assets/uploads/" . $lama["foto"])) {
                            unlink(__DIR__ . "/../assets/uploads/" . $lama["foto"]);
                        }
                        $edit   = null;
                        $sukses = "Listing berhasil diperbarui.";
                    }
                }
            }
        }
    }
}

// Ambil semua listing milik mitra
$stmt = $pdo->prepare(
    "SELECT l.*, c.nama AS nama_kategori 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.partner_id = ? 
    ORDER BY l.created_at DESC"
);
$stmt->execute([$mitra["id"]]);
$listings = $stmt->fetchAll();

// Default batas jam ambil berdasarkan jam_tutup mitra di tabel partners
$default_waktu = date("Y-m-d") . "T" . ($mitra["jam_tutup"] ? substr($mitra["jam_tutup"], 0, 5) : "21:00");

require_once "../includes/header.php";
?>

<div class="wide-wrap">
    <h1>Kelola Listing Surplus</h1>
    <p class="auth-sub"><a href="index.php">← Kembali ke dashboard</a></p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p class="alert-ok"><?= htmlspecialchars($sukses) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="kartu-info" style="text-align:left; margin-bottom:24px;">
        <h3 style="margin-top:0;"><?= $edit ? "Ubah Listing Surplus" : "Posting Makanan Surplus" ?></h3>
        <input type="hidden" name="aksi" value="<?= $edit ? "ubah" : "tambah" ?>">
        <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit["id"] ?>">
        <?php endif; ?>

        <label for="nama_makanan">Nama makanan</label>
        <input type="text" id="nama_makanan" name="nama_makanan" 
            value="<?= htmlspecialchars($edit["nama_makanan"] ?? "") ?>" 
            placeholder="Contoh: Paket Roti Manis" required>

        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c["id"] ?>" <?= (($edit["category_id"] ?? "") == $c["id"]) ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nama"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="deskripsi">Deskripsi (opsional)</label>
        <input type="text" id="deskripsi" name="deskripsi" 
            value="<?= htmlspecialchars($edit["deskripsi"] ?? "") ?>" 
            placeholder="Keterangan singkat makanan">

        <label for="harga_asli">Harga asli (Rp)</label>
        <input type="number" id="harga_asli" name="harga_asli" min="1000" 
            value="<?= htmlspecialchars($edit["harga_asli"] ?? "") ?>" placeholder="20000" required>

        <label for="harga_diskon">Harga diskon (Rp)</label>
        <input type="number" id="harga_diskon" name="harga_diskon" min="500" 
            value="<?= htmlspecialchars($edit["harga_diskon"] ?? "") ?>" placeholder="8000" required>

        <label for="stok">Stok (porsi)</label>
        <input type="number" id="stok" name="stok" min="0" 
            value="<?= htmlspecialchars($edit["stok"] ?? "5") ?>" required>

        <label for="batas_jam_ambil">Batas jam ambil</label>
        <input type="datetime-local" id="batas_jam_ambil" name="batas_jam_ambil" 
            value="<?= $edit ? date("Y-m-d\TH:i", strtotime($edit["batas_jam_ambil"])) : $default_waktu ?>" required>

        <label for="foto">Foto makanan <?= $edit ? "(kosongkan jika tidak diubah)" : "" ?></label>
        <input type="file" id="foto" name="foto" accept="image/*" <?= $edit ? "" : "required" ?>>

        <button type="submit"><?= $edit ? "Simpan Perubahan" : "Posting Makanan" ?></button>
        <?php if ($edit): ?>
            <p class="auth-foot" style="text-align:center;"><a href="listing.php">Batal ubah</a></p>
        <?php endif; ?>
    </form>

    <h2 class="sub-judul">Daftar Makanan Surplus Toko</h2>
    <?php if (!$listings): ?>
        <p class="auth-sub" style="text-align:left;">Belum ada makanan yang diposting.</p>
    <?php else: ?>
        <?php 
        $now = date("Y-m-d H:i:s");
        foreach ($listings as $item): 
            $expired  = $item["batas_jam_ambil"] <= $now;
            $sold_out = (int) $item["stok"] <= 0;
        ?>
            <div class="card-mitra">
                <div style="display:flex; gap:12px; align-items:center;">
                    <img src="/rescuebite-food-rescue/assets/uploads/<?= htmlspecialchars($item["foto"]) ?>" 
                            alt="<?= htmlspecialchars($item["nama_makanan"]) ?>" 
                            style="width:68px; height:68px; object-fit:cover; border-radius:12px;">
                    <div>
                        <strong><?= htmlspecialchars($item["nama_makanan"]) ?></strong>
                        <?php if ($item["status"] === "diblokir"): ?>
                            <span class="badge badge-ditolak">diblokir</span>
                        <?php elseif ($expired): ?>
                            <span class="badge badge-ditolak">lewat jam ambil</span>
                        <?php elseif ($sold_out): ?>
                            <span class="badge badge-menunggu">sold out</span>
                        <?php else: ?>
                            <span class="badge badge-terverifikasi">aktif</span>
                        <?php endif; ?>
                        <br>
                        <small>
                            <?= htmlspecialchars($item["nama_kategori"]) ?> · 
                            <s>Rp <?= number_format($item["harga_asli"], 0, ",", ".") ?></s>
                            <strong>Rp <?= number_format($item["harga_diskon"], 0, ",", ".") ?></strong> · 
                            Stok: <?= (int) $item["stok"] ?><br>
                            Batas ambil: <?= date("d M Y H:i", strtotime($item["batas_jam_ambil"])) ?>
                        </small>
                    </div>
                </div>

                <div class="aksi">
                    <a href="listing.php?edit=<?= $item["id"] ?>" class="btn-outline" style="padding:8px 14px; font-size:0.85rem;">Ubah</a>
                    <form method="POST" onsubmit="return confirm('Hapus listing ini?')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $item["id"] ?>">
                        <button type="submit" class="btn-no">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>