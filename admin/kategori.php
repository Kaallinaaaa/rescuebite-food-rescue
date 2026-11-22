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

<div class="wide-wrap">
    <h1>Kelola Kategori</h1>
    <p class="auth-sub"><a href="index.php">← Kembali ke dashboard</a></p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="aksi tambah-kategori">
        <input type="hidden" name="aksi" value="tambah">
        <input type="text" name="nama" placeholder="Nama kategori baru" required>
        <button type="submit">Tambah</button>
    </form>

    <?php foreach ($kategori as $k): ?>
        <div class="card-mitra">
            <form method="POST" class="aksi" style="flex:1">
                <input type="hidden" name="aksi" value="ubah">
                <input type="hidden" name="id" value="<?= $k["id"] ?>">
                <input type="text" name="nama" value="<?= htmlspecialchars($k["nama"]) ?>" required>
                <button type="submit">Simpan</button>
            </form>
            <form method="POST" class="aksi" onsubmit="return confirm('Hapus kategori ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= $k["id"] ?>">
                <button type="submit" class="btn-no">Hapus</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once "../includes/footer.php"; ?>