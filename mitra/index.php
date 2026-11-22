<?php
session_start();
require_once "../includes/auth_check.php";
require_role("mitra");
require_once "../includes/header.php";
?>

<div class="auth-wrap">
    <h1>Dashboard Mitra</h1>
    <p class="auth-sub">Halo, <?= htmlspecialchars($_SESSION["nama"]) ?>!</p>
</div>

<?php require_once "../includes/footer.php"; ?>