<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("admin");
require_once "../includes/header.php";

$menunggu = $pdo->query(
    "SELECT COUNT(*) FROM partners WHERE status_verifikasi = 'menunggu'"
)->fetchColumn();

// Tambahan hanya untuk tampilan: total mitra & jumlah kategori
$total_mitra   = $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_listing  = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/admin-dashboard.css">

<div class="ad-halaman">

    <div class="ad-kepala">
        <div>
            <h1>Dashboard Admin</h1>
            <p class="ad-sapa">Halo, <strong><?= htmlspecialchars($_SESSION["nama"]) ?></strong>!</p>
        </div>
        <span class="ad-total"><?= (int) $total_mitra ?> mitra terdaftar</span>
    </div>

    <div class="ad-grid">

        <a href="mitra.php" class="ad-kartu <?= $menunggu ? 'ad-kartu--sorot' : '' ?>">
            <div>
                <span class="ad-angka"><?= (int) $menunggu ?></span>
                <span class="ad-satuan">mitra menunggu verifikasi</span>
            </div>
            <div class="ad-bawah">
                <div>
                    <h2>Verifikasi mitra</h2>
                    <p><?= $menunggu ? "Ada pengajuan yang perlu kamu tinjau." : "Semua pengajuan sudah ditinjau." ?></p>
                </div>
                <span class="ad-panah" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </span>
            </div>
        </a>

        <a href="kategori.php" class="ad-kartu">
            <div>
                <span class="ad-angka"><?= (int) $total_kategori ?></span>
                <span class="ad-satuan">kategori makanan</span>
            </div>
            <div class="ad-bawah">
                <div>
                    <h2>Kelola kategori</h2>
                    <p>Tambah, ubah, hapus.</p>
                </div>
                <span class="ad-panah" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </span>
            </div>
        </a>

        <a href="listing.php" class="ad-kartu">
            <div>
                <span class="ad-angka"><?= (int) $total_listing ?></span>
                <span class="ad-satuan">listing diposting mitra</span>
            </div>
            <div class="ad-bawah">
                <div>
                    <h2>Blokir listing</h2>
                    <p>Sembunyikan listing yang bermasalah.</p>
                </div>
                <span class="ad-panah" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </span>
            </div>
        </a>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
