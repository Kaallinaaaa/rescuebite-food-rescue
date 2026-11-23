<nav class="navbar">
    <a href="/rescuebite-food-rescue/index.php" class="nav-brand">
    <img src="/rescuebite-food-rescue/assets/img/logo-full.png" alt="Rescuebite" height="36">
</a>
    <div class="nav-links">
        <?php if (!isset($_SESSION["user_id"])): ?>
            <a href="/rescuebite-food-rescue/auth/login.php">Masuk</a>
            <a href="/rescuebite-food-rescue/auth/register.php" class="btn-nav">Daftar</a>

        <?php elseif ($_SESSION["role"] === "pembeli"): ?>
            <a href="/rescuebite-food-rescue/index.php">Beranda</a>
            <a href="/rescuebite-food-rescue/pembeli/riwayat.php">Pesanan Saya</a>
            <a href="/rescuebite-food-rescue/profil.php">Profil</a>
            <a href="/rescuebite-food-rescue/auth/logout.php" class="btn-nav">Keluar</a>

        <?php elseif ($_SESSION["role"] === "mitra"): ?>
            <a href="/rescuebite-food-rescue/mitra/index.php">Dashboard</a>
            <a href="/rescuebite-food-rescue/mitra/listing.php">Kelola Listing</a>
            <a href="/rescuebite-food-rescue/profil.php">Profil</a>
            <a href="/rescuebite-food-rescue/auth/logout.php" class="btn-nav">Keluar</a>

        <?php elseif ($_SESSION["role"] === "admin"): ?>
            <a href="/rescuebite-food-rescue/admin/index.php">Dashboard</a>
            <a href="/rescuebite-food-rescue/admin/mitra.php">Mitra</a>
            <a href="/rescuebite-food-rescue/admin/kategori.php">Kategori</a>
            <a href="/rescuebite-food-rescue/profil.php">Profil</a>
            <a href="/rescuebite-food-rescue/auth/logout.php" class="btn-nav">Keluar</a>
        <?php endif; ?>
    </div>
</nav>