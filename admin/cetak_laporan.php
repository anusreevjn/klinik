<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/laporan_data.php';

guard($conn, 'pentadbir', '../staff_login.php');

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'Laporan Kewangan';
if (!array_key_exists($jenis, jenis_laporan_tersedia())) {
    $jenis = 'Laporan Kewangan';
}

$dari = isset($_GET['dari']) && $_GET['dari'] !== '' ? $_GET['dari'] : date('Y-m-01');
$hingga = isset($_GET['hingga']) && $_GET['hingga'] !== '' ? $_GET['hingga'] : date('Y-m-d');

$data = data_laporan($conn, $jenis, $dari, $hingga);
$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
$alamat = tetapan($conn, 'alamat_klinik', '');
$telefon = tetapan($conn, 'telefon_klinik', '');
$logo = '../' . tetapan($conn, 'logo_klinik', 'assets/image/logo.jpg');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= selamat($jenis) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=9">
<link rel="stylesheet" href="../assets/css/theme.css?v=9">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body>
<div class="container">
    <div class="card invoice-card">
        <div class="invoice-details-top">
            <div class="invoice-left">
                <img src="<?= selamat($logo) ?>" alt="Logo" style="width:70px;border-radius:12px">
                <h2><?= selamat($nama_klinik) ?></h2>
                <p><?= selamat($alamat) ?><br>Tel: <?= selamat($telefon) ?></p>
            </div>
            <div class="invoice-right">
                <div class="invoice-id"><?= selamat($jenis) ?></div>
                <div class="invoice-date">Tempoh: <?= selamat(date('d/m/Y', strtotime($dari))) ?> hingga <?= selamat(date('d/m/Y', strtotime($hingga))) ?></div>
                <div class="invoice-date">Dijana: <?= date('d/m/Y h:i A') ?></div>
            </div>
        </div>

        <hr class="pembahagi">

        <?php foreach ($data['ringkasan'] as $label => $nilai) { ?>
            <div class="profile-item"><span class="info-label"><?= selamat($label) ?></span><strong><?= selamat($nilai) ?></strong></div>
        <?php } ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr><?php foreach ($data['kepala'] as $kepala) { ?><th><?= selamat($kepala) ?></th><?php } ?></tr>
                </thead>
                <tbody>
                <?php if (count($data['baris']) === 0) { ?>
                    <tr><td colspan="<?= max(1, count($data['kepala'])) ?>">Tiada data untuk tempoh ini.</td></tr>
                <?php } ?>
                <?php foreach ($data['baris'] as $baris) { ?>
                    <tr><?php foreach ($baris as $sel) { ?><td><?= selamat($sel) ?></td><?php } ?></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="btn-group">
        <button class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn btn-back" href="laporan.php">Kembali</a>
    </div>
</div>
<script src="../assets/js/ui.js?v=9" defer></script>
</body>
</html>
