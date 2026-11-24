<?php
session_start();
require_once "../includes/auth_check.php";
require_role("pembeli");
require_once "../config/database.php";

function rupiah($angka) {
    return "Rp " . number_format((int) $angka, 0, ",", ".");
}

$stmt = $pdo->prepare(
    "SELECT o.*, l.nama_makanan, l.batas_jam_ambil, p.nama_toko, p.alamat,
            py.status AS status_bayar, py.metode,
            r.id AS review_id
     FROM orders o
     JOIN listings l ON o.listing_id = l.id
     JOIN partners p ON l.partner_id = p.id
     LEFT JOIN payments py ON py.order_id = o.id
     LEFT JOIN reviews r ON r.order_id = o.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$_SESSION["user_id"]]);
$pesanan = $stmt->fetchAll();

// [teks, kunci status untuk CSS & filter]
$label_status = [
    "menunggu_pickup" => ["Menunggu pickup", "aktif"],
    "selesai"         => ["Selesai",         "selesai"],
    "dibatalkan"      => ["Dibatalkan",      "batal"],
];

// Ringkasan untuk kartu statistik & tab filter
$jml_aktif = $jml_selesai = $jml_batal = $porsi_selamat = 0;
foreach ($pesanan as $o) {
    switch ($o["status_pesanan"]) {
        case "menunggu_pickup": $jml_aktif++; break;
        case "selesai":         $jml_selesai++; $porsi_selamat += (int) $o["jumlah"]; break;
        case "dibatalkan":      $jml_batal++; break;
    }
}

require_once "../includes/header.php";
?>
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/pembeli.css">
<link rel="stylesheet" href="/rescuebite-food-rescue/assets/css/riwayat.css">

<div class="rw-halaman">

    <div class="rw-kepala-halaman">
        <div>
            <h1>Pesanan Saya</h1>
            <p>Tunjukkan kode pickup ke toko sebelum batas jam ambil.</p>
        </div>
        <a href="/rescuebite-food-rescue/pembeli/dampak.php" class="rw-dampak">Lihat dampakmu</a>
    </div>

    <?php if (!$pesanan): ?>
        <div class="rw-kosong">
            <p>Kamu belum punya pesanan.</p>
            <a href="/rescuebite-food-rescue/index.php#katalog" class="rw-btn rw-btn--oranye">Cari makanan</a>
        </div>
    <?php else: ?>

        <div class="rw-ringkasan">
            <div class="rw-stat <?= $jml_aktif ? 'rw-stat--sorot' : '' ?>">
                <b><?= $jml_aktif ?></b>
                <span>Menunggu pickup</span>
            </div>
            <div class="rw-stat">
                <b><?= $jml_selesai ?></b>
                <span>Pesanan selesai</span>
            </div>
            <div class="rw-stat">
                <b><?= $porsi_selamat ?></b>
                <span>Porsi terselamatkan</span>
            </div>
        </div>

        <div class="rw-tab" role="group" aria-label="Filter pesanan">
            <button type="button" data-filter="semua" aria-pressed="true">Semua (<?= count($pesanan) ?>)</button>
            <button type="button" data-filter="aktif" aria-pressed="false">Aktif (<?= $jml_aktif ?>)</button>
            <button type="button" data-filter="selesai" aria-pressed="false">Selesai (<?= $jml_selesai ?>)</button>
            <?php if ($jml_batal): ?>
                <button type="button" data-filter="batal" aria-pressed="false">Dibatalkan (<?= $jml_batal ?>)</button>
            <?php endif; ?>
        </div>

        <div class="rw-daftar">
        <?php foreach ($pesanan as $o):
            [$teks_status, $kunci] = $label_status[$o["status_pesanan"]] ?? [$o["status_pesanan"], "aktif"];
            $aktif = $o["status_pesanan"] === "menunggu_pickup";
            $lunas = $o["status_bayar"] === "lunas";
        ?>
            <article class="rw-tiket rw-tiket--<?= $kunci ?>" data-status="<?= $kunci ?>">
                <div class="rw-badan">

                    <div class="rw-utama">
                        <div class="rw-judul">
                            <h2><?= htmlspecialchars($o["nama_makanan"]) ?></h2>
                            <span class="rw-lencana rw-lencana--<?= $kunci ?>"><?= htmlspecialchars($teks_status) ?></span>
                        </div>
                        <p class="rw-toko">
                            <strong><?= htmlspecialchars($o["nama_toko"]) ?></strong>
                            &middot; <?= htmlspecialchars($o["alamat"]) ?>
                        </p>

                        <?php if ($aktif): ?>
                            <div class="rw-batas">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                <span>Ambil sebelum <strong><?= date("d M, H:i", strtotime($o["batas_jam_ambil"])) ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <dl class="rw-meta">
                            <div>
                                <dt>Jumlah</dt>
                                <dd><?= (int) $o["jumlah"] ?> porsi</dd>
                            </div>
                            <div>
                                <dt>Dipesan</dt>
                                <dd><?= date("d M Y, H:i", strtotime($o["created_at"])) ?></dd>
                            </div>
                            <div>
                                <dt>Pembayaran</dt>
                                <dd class="<?= $lunas ? 'rw-bayar--lunas' : 'rw-bayar--belum' ?>">
                                    <?= $o["status_bayar"] ? ucfirst($o["status_bayar"]) : "Belum dibayar" ?>
                                </dd>
                            </div>
                        </dl>

                        <?php if (($aktif && !$lunas) || $o["status_pesanan"] === "selesai"): ?>
                            <div class="rw-aksi">
                                <?php if ($aktif && !$lunas): ?>
                                    <a href="/rescuebite-food-rescue/pembeli/bayar.php?order_id=<?= (int) $o["id"] ?>" class="rw-btn rw-btn--oranye">Bayar sekarang</a>
                                <?php endif; ?>

                                <?php if ($o["status_pesanan"] === "selesai" && !$o["review_id"]): ?>
                                    <a href="/rescuebite-food-rescue/pembeli/review.php?order_id=<?= (int) $o["id"] ?>" class="rw-btn rw-btn--hijau">Beri ulasan</a>
                                <?php elseif ($o["review_id"]): ?>
                                    <span class="rw-sudah">Sudah diulas ✓</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <aside class="rw-stub">
                        <div class="rw-stub-grup">
                            <span class="rw-stub-label">Total</span>
                            <div class="rw-total"><?= rupiah($o["total_harga"]) ?></div>
                        </div>
                        <?php if ($aktif): ?>
                            <div class="rw-stub-grup">
                                <span class="rw-stub-label">Kode pickup</span>
                                <div class="rw-kode"><?= htmlspecialchars($o["kode_pickup"]) ?></div>
                            </div>
                        <?php endif; ?>
                    </aside>

                </div>
            </article>
        <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script>
// Filter tab: Semua / Aktif / Selesai / Dibatalkan
document.querySelectorAll(".rw-tab button").forEach(function (tombol) {
    tombol.addEventListener("click", function () {
        var f = tombol.dataset.filter;
        document.querySelectorAll(".rw-tab button").forEach(function (b) {
            b.setAttribute("aria-pressed", b === tombol ? "true" : "false");
        });
        document.querySelectorAll(".rw-tiket").forEach(function (t) {
            t.hidden = !(f === "semua" || t.dataset.status === f);
        });
    });
});
</script>

<?php require_once "../includes/footer.php"; ?>
