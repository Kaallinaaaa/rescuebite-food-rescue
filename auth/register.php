<?php
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama     = trim($_POST["nama"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $role     = $_POST["role"];
    $no_hp    = trim($_POST["no_hp"]);

    if ($nama === "" || $email === "" || $password === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (!in_array($role, ["pembeli", "mitra"])) {
        $error = "Peran tidak valid.";
    } elseif ($no_hp !== "" && !preg_match('/^\+?[0-9]{8,15}$/', $no_hp)) {
        $error = "Format no. HP tidak valid.";
    } else {
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);

        if ($cek->fetch()) {
            $error = "Email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (nama, email, password, role, no_hp) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$nama, $email, $hash, $role, $no_hp === "" ? null : $no_hp]);
            header("Location: login.php");
            exit;
        }
    }
}

require_once "../includes/header.php";
?>

<div class="auth-wrap">
    <img src="/rescuebite-food-rescue/assets/img/logo.png" alt="Rescuebite" class="auth-logo">
    <h1>Buat Akunmu</h1>
    <p class="auth-sub">Daftar untuk mulai menyelamatkan makanan dan hemat uang makan.</p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" placeholder="Nama kamu" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="contoh@gmail.com" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>

        <label for="no_hp">No. HP (opsional)</label>
        <input type="tel" id="no_hp" name="no_hp" placeholder="081234567890">

        <label for="role">Daftar sebagai</label>
        <select id="role" name="role">
            <option value="pembeli">Pembeli</option>
            <option value="mitra">Mitra (resto/toko)</option>
        </select>

        <button type="submit">Buat Akun</button>
    </form>

    <p class="auth-foot">Sudah punya akun? <a href="login.php">Masuk</a></p>
</div>

<?php require_once "../includes/footer.php"; ?>