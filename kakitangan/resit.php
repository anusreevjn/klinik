<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/penjaga_lib.php';

$id_pengguna = guard($conn, ['kakitangan', 'pentadbir', 'pesakit'], '../login.php');
$peranan = $_SESSION['role'];

$id_pembayaran = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT p.*, i.no_invois, i.no_resit, i.tarikh_jana, i.status_invois,
               ps.nama_pesakit, ps.no_ic, ps.no_telefon, ps.alamat, ps.id_pesakit,
               r.nama_rawatan, r.kod_rawatan, r.harga_rawatan, r.tarikh_rawatan,
               d.nama_doktor, k.nama_kakitangan
        FROM pembayaran p
        LEFT JOIN invois i ON i.id_pembayaran = p.id_pembayaran
        LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan
        LEFT JOIN doktor d ON d.id_doktor = r.id_doktor
        LEFT JOIN kakitangan k ON k.id_kakitangan = p.id_kakitangan
        WHERE p.id_pembayaran = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    die('Rekod pembayaran tidak dijumpai.');
}

if ($peranan === 'pesakit' && !profil_dibenarkan_untuk_akaun($conn, $id_pengguna, (int)$data['id_pesakit'])) {
    die('Anda tidak dibenarkan melihat resit ini.');
}

$preskripsi = mysqli_prepare($conn, "SELECT b.kuantiti, b.dos, b.arahan, inv.nama_barang FROM butiran_preskripsi b LEFT JOIN inventori inv ON inv.id_inventori = b.id_inventori WHERE b.id_rawatan = ?");
mysqli_stmt_bind_param($preskripsi, "i", $data['id_rawatan']);
mysqli_stmt_execute($preskripsi);
$senarai_ubat = mysqli_stmt_get_result($preskripsi);

$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
$alamat = tetapan($conn, 'alamat_klinik', '');
$telefon = tetapan($conn, 'telefon_klinik', '');
$email = tetapan($conn, 'email_klinik', '');
$logo = '../' . tetapan($conn, 'logo_klinik', 'assets/image/logo.jpg');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resit <?= selamat($data['no_resit']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body>
<div class="container">
    <div class="invoice-card card">
        <div class="invoice-details-top">
            <div class="invoice-left">
                <img src="<?= selamat($logo) ?>" alt="Logo" style="width:80px;border-radius:14px">
                <h2><?= selamat($nama_klinik) ?></h2>
                <p><?= selamat($alamat) ?><br>Tel: <?= selamat($telefon) ?><br><?= selamat($email) ?></p>
            </div>
            <div class="invoice-right">
                <div class="invoice-id">RESIT RASMI</div>
                <div class="invoice-date">No Resit: <strong><?= selamat($data['no_resit']) ?></strong></div>
                <div class="invoice-date">No Invois: <?= selamat($data['no_invois']) ?></div>
                <div class="invoice-date">Tarikh: <?= selamat(date('d/m/Y h:i A', strtotime($data['tarikh_bayaran']))) ?></div>
                <span class="badge-status <?= kelas_status($data['status_pembayaran']) ?>"><?= selamat($data['status_pembayaran']) ?></span>
            </div>
        </div>

        <hr class="pembahagi">

        <div class="invoice-details-bottom">
            <div class="invoice-left">
                <div class="info-label">Maklumat Pesakit</div>
                <p><strong><?= selamat($data['nama_pesakit']) ?></strong><br>
                    <?= selamat($data['no_ic']) ?><br>
                    <?= selamat($data['no_telefon']) ?><br>
                    <?= selamat($data['alamat']) ?></p>
            </div>
            <div class="invoice-right">
                <div class="info-label">Maklumat Rawatan</div>
                <p>Doktor: <?= selamat($data['nama_doktor'] ?: '-') ?><br>
                    Tarikh rawatan: <?= selamat($data['tarikh_rawatan'] ? date('d/m/Y', strtotime($data['tarikh_rawatan'])) : '-') ?><br>
                    Diterima oleh: <?= selamat($data['nama_kakitangan'] ?: '-') ?></p>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead><tr><th>Kod</th><th>Perkara</th><th>Harga</th></tr></thead>
                <tbody>
                    <tr>
                        <td><?= selamat($data['kod_rawatan'] ?: '-') ?></td>
                        <td><?= selamat($data['nama_rawatan']) ?></td>
                        <td><?= wang($data['harga_rawatan']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if (mysqli_num_rows($senarai_ubat) > 0) { ?>
            <div class="card-title">Preskripsi</div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Ubat</th><th>Kuantiti</th><th>Dos</th><th>Arahan</th></tr></thead>
                    <tbody>
                    <?php while ($ubat = mysqli_fetch_assoc($senarai_ubat)) { ?>
                        <tr>
                            <td><?= selamat($ubat['nama_barang'] ?: '-') ?></td>
                            <td><?= selamat($ubat['kuantiti']) ?></td>
                            <td><?= selamat($ubat['dos']) ?></td>
                            <td><?= selamat($ubat['arahan']) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <hr class="pembahagi">

        <div class="invoice-details-bottom">
            <div class="invoice-left">
                <p>Kaedah bayaran: <strong><?= selamat($data['kaedah_bayaran']) ?></strong></p>
                <p>Resit ini dijana oleh sistem dan sah tanpa tandatangan.</p>
            </div>
            <div class="invoice-right">
                <div class="info-label">Jumlah Dibayar</div>
                <div class="price"><?= wang($data['jumlah_bayaran']) ?></div>
            </div>
        </div>
    </div>

    <div class="btn-group">
        <button class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn btn-back" href="<?= $peranan === 'pesakit' ? '../pesakit/pembayaran.php' : ($peranan === 'pentadbir' ? '../admin/pembayaran.php' : 'invois.php') ?>">Kembali</a>
    </div>
</div>
<script src="../assets/js/ui.js" defer></script>
</body>
</html>
