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

// Huruf awal untuk avatar (hanya tampilan)
$inisial = mb_strtoupper(mb_substr(trim($user["nama"]), 0, 1)) ?: "?";

require_once "includes/header.php";
?>
<link rel="stylesheet" href="/assets/css/profil.css">

<div class="pf-halaman">

    <!-- Kepala profil -->
    <section class="pf-kepala">
        <div class="pf-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></div>
        <div class="pf-kepala-teks">
            <h1><?= htmlspecialchars($user["nama"]) ?></h1>
            <p class="pf-email"><?= htmlspecialchars($user["email"]) ?></p>
            <div class="pf-label-baris">
                <span class="pf-label"><?= htmlspecialchars($role) ?></span>
                <?php if ($role === "mitra" && $toko): ?>
                    <span class="pf-label pf-label--<?= htmlspecialchars($toko["status_verifikasi"]) ?>">
                        <?= htmlspecialchars($toko["status_verifikasi"]) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($error): ?>
        <p class="pf-pesan pf-pesan--salah" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sukses): ?>
        <p class="pf-pesan pf-pesan--ok" role="status"><?= htmlspecialchars($sukses) ?></p>
    <?php endif; ?>

    <div class="pf-grid">

        <!-- Data diri -->
        <section class="pf-kartu">
            <h2>Data diri</h2>
            <p class="pf-ket">Perbarui nama dan nomor HP yang dipakai untuk pesanan.</p>

            <form method="POST">
                <input type="hidden" name="aksi" value="profil">

                <div class="pf-field">
                    <label for="nama">Nama lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($user["nama"]) ?>" required>
                </div>

                <div class="pf-field">
                    <label for="no_hp">No. HP</label>
                    <input type="tel" id="no_hp" name="no_hp" inputmode="tel" value="<?= htmlspecialchars($user["no_hp"] ?? "") ?>">
                    <span class="pf-bantu">8–15 angka, boleh diawali +. Contoh: 081234567890</span>
                </div>

                <?php if ($role === "mitra" && $toko): ?>
                    <div class="pf-toko">
                        <h3>Data toko</h3>

                        <div class="pf-field">
                            <label for="nama_toko">Nama toko</label>
                            <input type="text" id="nama_toko" name="nama_toko" value="<?= htmlspecialchars($toko["nama_toko"]) ?>" required>
                        </div>

                        <div class="pf-field">
                            <label for="alamat">Alamat toko</label>
                            <input type="text" id="alamat" name="alamat" value="<?= htmlspecialchars($toko["alamat"]) ?>" required>
                        </div>

                        <div class="pf-field">
                            <label for="jam_tutup">Jam tutup</label>
                            <input type="time" id="jam_tutup" name="jam_tutup" value="<?= htmlspecialchars($toko["jam_tutup"] ?? "") ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="pf-btn pf-btn--hijau">Simpan profil</button>
            </form>
        </section>

        <!-- Ganti password -->
        <section class="pf-kartu">
            <h2>Ganti password</h2>
            <p class="pf-ket">Gunakan password baru minimal 6 karakter.</p>

            <form method="POST">
                <input type="hidden" name="aksi" value="password">

                <div class="pf-field pf-sandi">
                    <label for="password_lama">Password lama</label>
                    <input type="password" id="password_lama" name="password_lama" autocomplete="current-password" required>
                    <button type="button" class="pf-lihat" data-target="password_lama" aria-label="Tampilkan password lama">Lihat</button>
                </div>

                <div class="pf-field pf-sandi">
                    <label for="password_baru">Password baru</label>
                    <input type="password" id="password_baru" name="password_baru" placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                    <button type="button" class="pf-lihat" data-target="password_baru" aria-label="Tampilkan password baru">Lihat</button>
                </div>

                <button type="submit" class="pf-btn pf-btn--oranye">Ganti password</button>
            </form>
        </section>

    </div>
</div>

<script>
// Tombol Lihat / Sembunyikan password
document.querySelectorAll(".pf-lihat").forEach(function (tombol) {
    tombol.addEventListener("click", function () {
        var input = document.getElementById(tombol.dataset.target);
        var tampil = input.type === "password";
        input.type = tampil ? "text" : "password";
        tombol.textContent = tampil ? "Sembunyi" : "Lihat";
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
