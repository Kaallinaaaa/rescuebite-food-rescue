<nav class="navbar">
    <a href="/index.php" class="nav-brand">
    <img src="/assets/img/logo-full.png" alt="Rescuebite" height="36">
</a>
    <div class="nav-links">
        <?php if (!isset($_SESSION["user_id"])): ?>
            <a href="/auth/login.php">Masuk</a>
            <a href="/auth/register.php" class="btn-nav">Daftar</a>

        <?php elseif ($_SESSION["role"] === "pembeli"): ?>
            <a href="/index.php">Beranda</a>
            <a href="/pembeli/riwayat.php">Pesanan Saya</a>
            <a href="/profil.php">Profil</a>
            <a href="/auth/logout.php" class="btn-nav">Keluar</a>

        <?php elseif ($_SESSION["role"] === "mitra"): ?>
            <a href="/mitra/index.php">Dashboard</a>
            <a href="/mitra/listing.php">Kelola Listing</a>
            <a href="/profil.php">Profil</a>
            <a href="/auth/logout.php" class="btn-nav">Keluar</a>

        <?php elseif ($_SESSION["role"] === "admin"): ?>
            <a href="/admin/index.php">Dashboard</a>
            <a href="/admin/mitra.php">Mitra</a>
            <a href="/admin/kategori.php">Kategori</a>
            <a href="/profil.php">Profil</a>
            <a href="/auth/logout.php" class="btn-nav">Keluar</a>
        <?php endif; ?>
    </div>
</nav>