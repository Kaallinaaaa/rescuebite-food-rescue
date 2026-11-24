<?php
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nama"]    = $user["nama"];
        $_SESSION["role"]    = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: ../admin/index.php");
        } elseif ($user["role"] === "mitra") {
            header("Location: ../mitra/index.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $error = "Email atau password salah.";
    }
}

require_once "../includes/header.php";
?>

<div class="auth-wrap">
    <img src="/rescuebite-food-rescue/assets/img/logo-putih.png" alt="Rescuebite" class="auth-logo">
    <h1>Selamat Datang</h1>
    <p class="auth-sub">Masuk untuk mulai menyelamatkan makanan dan hemat uang makan.</p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="contoh@gmail.com" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Password kamu" required>

        <button type="submit">Masuk</button>
    </form>

    <p class="auth-foot">Belum punya akun? <a href="register.php">Daftar</a></p>
</div>

<?php require_once "../includes/footer.php"; ?>