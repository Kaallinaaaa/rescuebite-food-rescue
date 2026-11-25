<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("admin");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $nama = trim($_POST["nama"] ?? "");
    $id   = (int) ($_POST["id"] ?? 0);

    if ($aksi === "tambah") {
        if ($nama === "") {
            $error = "Nama kategori wajib diisi.";
        } else {
            $pdo->prepare("INSERT INTO categories (nama) VALUES (?)")->execute([$nama]);
        }
    } elseif ($aksi === "ubah") {
        if ($nama === "") {
            $error = "Nama kategori wajib diisi.";
        } else {
            $pdo->prepare("UPDATE categories SET nama = ? WHERE id = ?")->execute([$nama, $id]);
        }
    } elseif ($aksi === "hapus") {
        try {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        } catch (PDOException $e) {
            $error = "Kategori tidak bisa dihapus karena sedang dipakai.";
        }
    }

    if ($error === "") {
        header("Location: kategori.php");
        exit;
    }
}

$kategori = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/assets/css/admin-kelola.css">

<div class="am-halaman">
    <a href="index.php" class="am-kembali">← Kembali ke dashboard</a>

    <div class="am-kepala">
        <h1>Kelola kategori</h1>
        <span class="am-jumlah"><?= count($kategori) ?> kategori</span>
    </div>

    <?php if ($error): ?>
        <p class="am-pesan" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Tambah kategori -->
    <form method="POST" class="am-tambah">
        <input type="hidden" name="aksi" value="tambah">
        <input type="text" name="nama" class="am-input" placeholder="Nama kategori baru" aria-label="Nama kategori baru" required>
        <button type="submit" class="am-btn am-btn--oranye">Tambah</button>
    </form>

    <!-- Daftar kategori -->
    <?php if (!$kategori): ?>
        <p class="am-kosong">Belum ada kategori. Tambahkan yang pertama di atas.</p>
    <?php endif; ?>

    <?php foreach ($kategori as $k): ?>
        <div class="am-baris">
            <form method="POST" class="am-ubah">
                <input type="hidden" name="aksi" value="ubah">
                <input type="hidden" name="id" value="<?= (int) $k["id"] ?>">
                <input type="text" name="nama" class="am-input" value="<?= htmlspecialchars($k["nama"]) ?>"
                       aria-label="Nama kategori" required>
                <button type="submit" class="am-btn am-btn--hijau">Simpan</button>
            </form>
            <form method="POST" class="am-hapus" onsubmit="return confirm('Hapus kategori ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $k["id"] ?>">
                <button type="submit" class="am-btn am-btn--merah">Hapus</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
