<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("admin");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id     = (int) $_POST["id"];
    $status = $_POST["status"];

    if (in_array($status, ["terverifikasi", "ditolak"])) {
        $stmt = $pdo->prepare("UPDATE partners SET status_verifikasi = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
    header("Location: mitra.php");
    exit;
}

$mitra = $pdo->query(
    "SELECT p.*, u.nama, u.email, u.no_hp
     FROM partners p
     JOIN users u ON p.user_id = u.id
     ORDER BY FIELD(p.status_verifikasi, 'menunggu', 'terverifikasi', 'ditolak'), p.id DESC"
)->fetchAll();

require_once "../includes/header.php";
?>

<div class="wide-wrap">
    <h1>Verifikasi Mitra</h1>
    <p class="auth-sub"><a href="index.php">← Kembali ke dashboard</a></p>

    <?php if (!$mitra): ?>
        <p>Belum ada mitra yang mendaftar.</p>
    <?php endif; ?>

    <?php foreach ($mitra as $m): ?>
        <div class="card-mitra">
            <div>
                <strong><?= htmlspecialchars($m["nama_toko"]) ?></strong>
                <span class="badge badge-<?= $m["status_verifikasi"] ?>"><?= $m["status_verifikasi"] ?></span><br>
                <small>
                    <?= htmlspecialchars($m["alamat"]) ?><br>
                    Pemilik: <?= htmlspecialchars($m["nama"]) ?> · <?= htmlspecialchars($m["email"]) ?>
                    <?= $m["no_hp"] ? " · " . htmlspecialchars($m["no_hp"]) : "" ?>
                </small>
            </div>

            <?php if ($m["status_verifikasi"] === "menunggu"): ?>
            <form method="POST" class="aksi">
                <input type="hidden" name="id" value="<?= $m["id"] ?>">
                <button name="status" value="terverifikasi" class="btn-ok">Verifikasi</button>
                <button name="status" value="ditolak" class="btn-no">Tolak</button>
            </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once "../includes/footer.php"; ?>