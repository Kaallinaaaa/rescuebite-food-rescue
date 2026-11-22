<?php
session_start();
require_once "config/database.php";
require_once "includes/auth_check.php";
require_login();

$user_id = $_SESSION["user_id"];
$role    = $_SESSION["role"];
$error   = "";
$sukses  = "";

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil data toko (khusus mitra)
$toko = null;
if ($role === "mitra") {
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $toko = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "profil") {
        $nama      = trim($_POST["nama"]);
        $no_hp     = trim($_POST["no_hp"]);
        $nama_toko = trim($_POST["nama_toko"] ?? "");
        $alamat    = trim($_POST["alamat"] ?? "");
        $jam_tutup = $_POST["jam_tutup"] ?? "";

        if ($nama === "") {
            $error = "Nama wajib diisi.";
        } elseif ($no_hp !== "" && !preg_match('/^\+?[0-9]{8,15}$/', $no_hp)) {
            $error = "Format no. HP tidak valid.";
        } elseif ($role === "mitra" && ($nama_toko === "" || $alamat === "")) {
            $error = "Nama toko dan alamat wajib diisi.";
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET nama = ?, no_hp = ? WHERE id = ?")
                ->execute([$nama, $no_hp === "" ? null : $no_hp, $user_id]);

            if ($role === "mitra") {
                $pdo->prepare(
                    "UPDATE partners SET nama_toko = ?, alamat = ?, jam_tutup = ? WHERE user_id = ?"
                )->execute([$nama_toko, $alamat, $jam_tutup === "" ? null : $jam_tutup, $user_id]);
            }
            $pdo->commit();

            $_SESSION["nama"] = $nama;
            $sukses = "Profil berhasil diperbarui.";
        }
    } elseif ($aksi === "password") {
        $lama  = $_POST["password_lama"];
        $baru  = $_POST["password_baru"];

        if (!password_verify($lama, $user["password"])) {
            $error = "Password lama salah.";
        } elseif (strlen($baru) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $user_id]);
            $sukses = "Password berhasil diganti.";
        }
    }

    // Ambil ulang data terbaru untuk ditampilkan
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($role === "mitra") {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $toko = $stmt->fetch();
    }
}

require_once "includes/header.php";
?>

<div class="auth-wrap">
    <h1>Profil Saya</h1>
    <p class="auth-sub"><?= htmlspecialchars($user["email"]) ?> · <?= htmlspecialchars($role) ?></p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p class="alert-ok"><?= htmlspecialchars($sukses) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="aksi" value="profil">

        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($user["nama"]) ?>" required>

        <label for="no_hp">No. HP</label>
        <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($user["no_hp"] ?? "") ?>">

        <?php if ($role === "mitra" && $toko): ?>
            <label for="nama_toko">Nama toko</label>
            <input type="text" id="nama_toko" name="nama_toko" value="<?= htmlspecialchars($toko["nama_toko"]) ?>" required>

            <label for="alamat">Alamat toko</label>
            <input type="text" id="alamat" name="alamat" value="<?= htmlspecialchars($toko["alamat"]) ?>" required>

            <label for="jam_tutup">Jam tutup</label>
            <input type="time" id="jam_tutup" name="jam_tutup" value="<?= htmlspecialchars($toko["jam_tutup"] ?? "") ?>">

            <p class="auth-sub" style="margin-top:12px">
                Status verifikasi:
                <span class="badge badge-<?= $toko["status_verifikasi"] ?>"><?= $toko["status_verifikasi"] ?></span>
            </p>
        <?php endif; ?>

        <button type="submit">Simpan Profil</button>
    </form>

    <h2 class="sub-judul">Ganti Password</h2>
    <form method="POST">
        <input type="hidden" name="aksi" value="password">

        <label for="password_lama">Password lama</label>
        <input type="password" id="password_lama" name="password_lama" required>

        <label for="password_baru">Password baru</label>
        <input type="password" id="password_baru" name="password_baru" placeholder="Minimal 6 karakter" required>

        <button type="submit">Ganti Password</button>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>