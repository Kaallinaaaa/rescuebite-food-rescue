<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("admin");
require_once "../includes/header.php";

$menunggu = $pdo->query(
    "SELECT COUNT(*) FROM partners WHERE status_verifikasi = 'menunggu'"
)->fetchColumn();
?>

<div class="auth-wrap">
    <h1>Dashboard Admin</h1>
    <p class="auth-sub">Halo, <?= htmlspecialchars($_SESSION["nama"]) ?>!</p>

    <div class="menu-admin">
        <a href="mitra.php" class="menu-card">
            Verifikasi Mitra
            <span><?= $menunggu ?> menunggu</span>
        </a>
        <a href="kategori.php" class="menu-card">
            Kelola Kategori
            <span>Tambah, ubah, hapus</span>
        </a>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>