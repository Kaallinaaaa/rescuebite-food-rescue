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

// Hitungan per status untuk tab filter (hanya tampilan)
$hitung = ["menunggu" => 0, "terverifikasi" => 0, "ditolak" => 0];
foreach ($mitra as $m) {
    if (isset($hitung[$m["status_verifikasi"]])) {
        $hitung[$m["status_verifikasi"]]++;
    }
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/assets/css/admin-kelola.css">

<div class="am-halaman">
    <a href="index.php" class="am-kembali">← Kembali ke dashboard</a>

    <div class="am-kepala">
        <h1>Verifikasi mitra</h1>
        <?php if ($hitung["menunggu"] > 0): ?>
            <span class="am-jumlah"><?= $hitung["menunggu"] ?> menunggu verifikasi</span>
        <?php endif; ?>
    </div>

    <?php if (!$mitra): ?>
        <p class="am-kosong">Belum ada mitra yang mendaftar.</p>
    <?php else: ?>

        <div class="am-tab" role="group" aria-label="Filter status mitra">
            <button type="button" data-filter="semua" aria-pressed="true">Semua (<?= count($mitra) ?>)</button>
            <button type="button" data-filter="menunggu" aria-pressed="false">Menunggu (<?= $hitung["menunggu"] ?>)</button>
            <button type="button" data-filter="terverifikasi" aria-pressed="false">Terverifikasi (<?= $hitung["terverifikasi"] ?>)</button>
            <button type="button" data-filter="ditolak" aria-pressed="false">Ditolak (<?= $hitung["ditolak"] ?>)</button>
        </div>

        <div class="am-daftar">
            <?php foreach ($mitra as $m):
                $st = htmlspecialchars($m["status_verifikasi"]);
                $inisial = mb_strtoupper(mb_substr(trim($m["nama_toko"]), 0, 1)) ?: "?";
            ?>
                <article class="am-mitra am-mitra--<?= $st ?>" data-status="<?= $st ?>">
                    <div class="am-avatar" aria-hidden="true"><?= htmlspecialchars($inisial) ?></div>

                    <div class="am-mitra-isi">
                        <div class="am-judul">
                            <h2><?= htmlspecialchars($m["nama_toko"]) ?></h2>
                            <span class="am-lencana am-lencana--<?= $st ?>"><?= $st ?></span>
                        </div>
                        <p class="am-alamat"><?= htmlspecialchars($m["alamat"]) ?></p>
                        <dl class="am-pemilik">
                            <div>
                                <dt>Pemilik</dt>
                                <dd><?= htmlspecialchars($m["nama"]) ?></dd>
                            </div>
                            <div>
                                <dt>Email</dt>
                                <dd><?= htmlspecialchars($m["email"]) ?></dd>
                            </div>
                            <?php if ($m["no_hp"]): ?>
                                <div>
                                    <dt>No. HP</dt>
                                    <dd><?= htmlspecialchars($m["no_hp"]) ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <?php if ($m["status_verifikasi"] === "menunggu"): ?>
                        <form method="POST" class="am-aksi">
                            <input type="hidden" name="id" value="<?= (int) $m["id"] ?>">
                            <button name="status" value="terverifikasi" class="am-btn am-btn--hijau">Verifikasi</button>
                            <button name="status" value="ditolak" class="am-btn am-btn--merah"
                                    onclick="return confirm('Tolak mitra ini?');">Tolak</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <script>
        // Filter tab: Semua / Menunggu / Terverifikasi / Ditolak
        document.querySelectorAll(".am-tab button").forEach(function (tombol) {
            tombol.addEventListener("click", function () {
                var f = tombol.dataset.filter;
                document.querySelectorAll(".am-tab button").forEach(function (b) {
                    b.setAttribute("aria-pressed", b === tombol ? "true" : "false");
                });
                document.querySelectorAll(".am-mitra").forEach(function (kartu) {
                    kartu.hidden = !(f === "semua" || kartu.dataset.status === f);
                });
            });
        });
        </script>

    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
