<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

$order_id = (int) ($_GET["order_id"] ?? $_POST["order_id"] ?? 0);

$stmt = $pdo->prepare(
    "SELECT o.id, l.nama_makanan, l.partner_id, p.nama_toko,
            (SELECT COUNT(*) FROM reviews r WHERE r.order_id = o.id) AS sudah_review
     FROM orders o
     JOIN listings l ON o.listing_id = l.id
     JOIN partners p ON l.partner_id = p.id
     WHERE o.id = ? AND o.user_id = ? AND o.status_pesanan = 'selesai'"
);
$stmt->execute([$order_id, $_SESSION["user_id"]]);
$order = $stmt->fetch();

if (!$order || $order["sudah_review"] > 0) {
    header("Location: /rescuebite-food-rescue/pembeli/riwayat.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating   = (int) ($_POST["rating"] ?? 0);
    $komentar = trim($_POST["komentar"] ?? "");

    if ($rating < 1 || $rating > 5) {
        $error = "Pilih rating 1 sampai 5 bintang.";
    } elseif (strlen($komentar) > 500) {
        $error = "Komentar maksimal 500 karakter.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO reviews (order_id, user_id, partner_id, rating, komentar)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([
                $order["id"],
                $_SESSION["user_id"],
                $order["partner_id"],
                $rating,
                $komentar === "" ? null : $komentar,
            ]);
        } catch (PDOException $e) {
            // order_id bersifat UNIQUE: kalau sudah pernah diulas, abaikan saja
        }
        header("Location: /rescuebite-food-rescue/pembeli/riwayat.php");
        exit;
    }
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pembeli.css">

<div class="wide-wrap">
    <h1>Beri Ulasan</h1>
    <p class="auth-sub">
        <?= htmlspecialchars($order["nama_makanan"]) ?> dari <strong><?= htmlspecialchars($order["nama_toko"]) ?></strong>
    </p>

    <?php if ($error): ?>
        <p class="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="order_id" value="<?= (int) $order["id"] ?>">

        <label>Rating</label>
        <div class="bintang">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="r<?= $i ?>" name="rating" value="<?= $i ?>" required>
                <label for="r<?= $i ?>" title="<?= $i ?> bintang">★</label>
            <?php endfor; ?>
        </div>

        <label for="komentar">Komentar (opsional)</label>
        <textarea id="komentar" name="komentar" rows="4" maxlength="500"
                  placeholder="Ceritakan pengalamanmu..."></textarea>

        <button type="submit">Kirim Ulasan</button>
        <p class="auth-foot" style="text-align:center;"><a href="/rescuebite-food-rescue/pembeli/riwayat.php">Batal</a></p>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>