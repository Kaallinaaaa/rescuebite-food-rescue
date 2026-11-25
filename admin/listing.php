<?php
session_start();
require_once "../config/database.php";
require_once "../includes/auth_check.php";
require_role("admin");

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

// Blokir / buka blokir listing
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id   = (int) ($_POST["id"] ?? 0);
    $aksi = $_POST["aksi"] ?? "";

    if ($id > 0 && $aksi === "blokir") {
        $pdo->prepare("UPDATE listings SET status = 'diblokir' WHERE id = ?")->execute([$id]);
    } elseif ($id > 0 && $aksi === "buka") {
        $pdo->prepare("UPDATE listings SET status = 'aktif' WHERE id = ? AND status = 'diblokir'")->execute([$id]);
    }
    header("Location: listing.php");
    exit;
}

$listing = $pdo->query(
    "SELECT l.*, p.nama_toko, c.nama AS nama_kategori
     FROM listings l
     JOIN partners p ON l.partner_id = p.id
     JOIN categories c ON l.category_id = c.id
     ORDER BY (l.status = 'diblokir') DESC, l.id DESC"
)->fetchAll();

// Hitungan untuk tab filter (hanya tampilan)
$jml_diblokir = 0;
foreach ($listing as $l) {
    if ($l["status"] === "diblokir") $jml_diblokir++;
}
$jml_lainnya = count($listing) - $jml_diblokir;

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/assets/css/admin-kelola.css">

<div class="am-halaman">
    <a href="index.php" class="am-kembali">← Kembali ke dashboard</a>

    <div class="am-kepala">
        <h1>Blokir listing</h1>
        <?php if ($jml_diblokir > 0): ?>
            <span class="am-jumlah"><?= $jml_diblokir ?> diblokir</span>
        <?php endif; ?>
    </div>

    <?php if (!$listing): ?>
        <p class="am-kosong">Belum ada listing yang diposting mitra.</p>
    <?php else: ?>

        <div class="am-tab" role="group" aria-label="Filter listing">
            <button type="button" data-filter="semua" aria-pressed="true">Semua (<?= count($listing) ?>)</button>
            <button type="button" data-filter="normal" aria-pressed="false">Tidak diblokir (<?= $jml_lainnya ?>)</button>
            <button type="button" data-filter="diblokir" aria-pressed="false">Diblokir (<?= $jml_diblokir ?>)</button>
        </div>

        <div class="am-daftar">
            <?php foreach ($listing as $l):
                $diblokir = $l["status"] === "diblokir";
            ?>
                <article class="am-listing <?= $diblokir ? 'am-listing--diblokir' : '' ?>"
                         data-status="<?= $diblokir ? 'diblokir' : 'normal' ?>">

                    <img class="am-foto"
                         src="/assets/uploads/<?= htmlspecialchars($l["foto"]) ?>"
                         alt="<?= htmlspecialchars($l["nama_makanan"]) ?>">

                    <div class="am-listing-isi">
                        <div class="am-judul">
                            <h2><?= htmlspecialchars($l["nama_makanan"]) ?></h2>
                            <span class="am-lencana am-lencana--<?= htmlspecialchars($l["status"]) ?>">
                                <?= htmlspecialchars($l["status"]) ?>
                            </span>
                        </div>
                        <p class="am-alamat">
                            <?= htmlspecialchars($l["nama_toko"]) ?> · <?= htmlspecialchars($l["nama_kategori"]) ?>
                        </p>
                        <dl class="am-pemilik">
                            <div>
                                <dt>Harga</dt>
                                <dd><?= rupiah($l["harga_diskon"]) ?></dd>
                            </div>
                            <div>
                                <dt>Stok</dt>
                                <dd><?= (int) $l["stok"] ?> porsi</dd>
                            </div>
                            <div>
                                <dt>Batas ambil</dt>
                                <dd><?= date("d M, H:i", strtotime($l["batas_jam_ambil"])) ?></dd>
                            </div>
                        </dl>
                    </div>

                    <form method="POST" class="am-aksi"
                          <?php if (!$diblokir): ?>onsubmit="return confirm('Blokir listing ini? Pembeli tidak akan bisa memesannya.');"<?php endif; ?>>
                        <input type="hidden" name="id" value="<?= (int) $l["id"] ?>">
                        <?php if ($diblokir): ?>
                            <button type="submit" name="aksi" value="buka" class="am-btn am-btn--hijau">Buka blokir</button>
                        <?php else: ?>
                            <button type="submit" name="aksi" value="blokir" class="am-btn am-btn--merah">Blokir</button>
                        <?php endif; ?>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>

        <script>
        // Filter tab: Semua / Tidak diblokir / Diblokir
        document.querySelectorAll(".am-tab button").forEach(function (tombol) {
            tombol.addEventListener("click", function () {
                var f = tombol.dataset.filter;
                document.querySelectorAll(".am-tab button").forEach(function (b) {
                    b.setAttribute("aria-pressed", b === tombol ? "true" : "false");
                });
                document.querySelectorAll(".am-listing").forEach(function (kartu) {
                    kartu.hidden = !(f === "semua" || kartu.dataset.status === f);
                });
            });
        });
        </script>

    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
