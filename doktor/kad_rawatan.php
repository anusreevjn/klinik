<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/kod_gigi.php';
require_once '../include/penjaga_lib.php';

$id_pengguna = guard($conn, ['doktor', 'kakitangan', 'pentadbir'], '../staff_login.php');

$id_pesakit = isset($_GET['id_p']) ? (int)$_GET['id_p'] : 0;

$stmt = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_pesakit = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$pesakit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pesakit) {
    http_response_code(404);
    exit('Pesakit tidak dijumpai.');
}

$stmt = mysqli_prepare($conn, "SELECT c.no_gigi, c.kod_kkm, c.status_gigi, c.id_rawatan, r.tarikh_rawatan
        FROM rekod_carta_pergigian c
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = c.id_rawatan
        WHERE c.id_pesakit = ?
        ORDER BY c.id_carta ASC");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$carta = mysqli_stmt_get_result($stmt);

$keadaan_gigi = [];
while ($baris = mysqli_fetch_assoc($carta)) {
    $keadaan_gigi[$baris['no_gigi']] = $baris['kod_kkm'];
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT nama_rawatan, tarikh_rawatan, diagnosis FROM rekod_rawatan WHERE id_pesakit = ? ORDER BY tarikh_rawatan DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$sejarah = mysqli_stmt_get_result($stmt);

audit($conn, 'kad_rawatan_dilihat', $id_pesakit, $_SESSION['role']);

$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
$alamat = tetapan($conn, 'alamat_klinik', '');
$telefon = tetapan($conn, 'telefon_klinik', '');

function baris_gigi(array $nombor, array $keadaan): string
{
    $html = '';

    foreach ($nombor as $no) {
        $kod = isset($keadaan[(string)$no]) ? $keadaan[(string)$no] : null;
        $warna = $kod !== null ? warna_keadaan_gigi($kod) : '#ffffff';
        $warna_teks = $kod !== null ? '#ffffff' : '#334155';
        $html .= '<div class="sel-gigi" style="background:' . $warna . '; color:' . $warna_teks . '">';
        $html .= '<span class="nombor-gigi">' . (int)$no . '</span>';
        $html .= '<span class="kod-gigi">' . ($kod !== null ? e($kod) : '&nbsp;') . '</span>';
        $html .= '</div>';
    }

    return $html;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kad Rawatan <?= e($pesakit['nama_pesakit']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
<link rel="stylesheet" href="../assets/css/theme.css?v=7">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    <style>
        .kad { border: 1px solid #94a3b8; padding: 22px; background: #fff; }
        .kad-tajuk { text-align: center; font-weight: 800; letter-spacing: .04em; }
        .maklumat-kad { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 6px 26px; margin: 18px 0; font-size: 14px; }
        .maklumat-kad span.label { font-weight: 700; display: inline-block; min-width: 130px; }
        .carta-gigi { margin: 14px 0; }
        .baris-gigi { display: flex; gap: 3px; justify-content: center; flex-wrap: wrap; margin-bottom: 4px; }
        .sel-gigi { width: 38px; border: 1px solid #94a3b8; border-radius: 4px; text-align: center; font-size: 11px; padding: 3px 0; }
        .nombor-gigi { display: block; font-weight: 700; }
        .kod-gigi { display: block; font-size: 13px; font-weight: 800; }
        .label-rahang { text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin: 8px 0 4px; }
        .petunjuk { display: flex; gap: 10px; flex-wrap: wrap; font-size: 12px; margin-top: 12px; }
        .petunjuk span { padding: 4px 10px; border-radius: 999px; color: #fff; font-weight: 700; }
        .keizinan { margin-top: 18px; font-size: 12px; line-height: 2.1; }
    </style>
</head>
<body>
<div class="container">
    <div class="card kad">
        <div class="kad-tajuk">
            <div>KAD RAWATAN</div>
            <div><?= e(strtoupper($nama_klinik)) ?></div>
            <div style="font-weight:500; font-size:12px"><?= e($alamat) ?> | Tel: <?= e($telefon) ?></div>
        </div>

        <div class="maklumat-kad">
            <div><span class="label">Nama</span><?= e($pesakit['nama_pesakit']) ?></div>
            <div><span class="label">No Pendaftaran Klinik</span><?= e($pesakit['no_pendaftaran_klinik'] ?: jana_no_pendaftaran_klinik($pesakit['no_ic'])) ?></div>
            <div><span class="label">ID Pesakit Sistem</span><?= e(id_pesakit_sistem($conn, (int)$pesakit['id_pesakit'])) ?></div>
            <div><span class="label">No K/P</span><?= e($pesakit['no_ic']) ?></div>
            <div><span class="label">No Tel</span><?= e($pesakit['no_telefon']) ?></div>
            <div><span class="label">Alamat</span><?= e($pesakit['alamat']) ?></div>
            <div><span class="label">Tarikh Cetak</span><?= date('d/m/Y') ?></div>
            <div><span class="label">Riwayat Perubatan</span><?= e($pesakit['penyakit_kronik'] ?: ($pesakit['penyakit'] ?: 'Tiada')) ?></div>
            <div><span class="label">Golongan</span><?= e($pesakit['golongan'] ?: '-') ?></div>
            <div><span class="label">Penjaga</span><?= e($pesakit['nama_penjaga'] ?: '-') ?><?= $pesakit['hubungan_penjaga'] ? ' (' . e($pesakit['hubungan_penjaga']) . ')' : '' ?></div>
        </div>

        <div class="carta-gigi">
            <div class="label-rahang">Carta Gigi: Rahang Atas</div>
            <div class="baris-gigi"><?= baris_gigi(array_merge(range(18, 11), range(21, 28)), $keadaan_gigi) ?></div>
            <div class="baris-gigi"><?= baris_gigi(array_merge(range(55, 51), range(61, 65)), $keadaan_gigi) ?></div>

            <div class="label-rahang">Carta Gigi: Rahang Bawah</div>
            <div class="baris-gigi"><?= baris_gigi(array_merge(range(85, 81), range(71, 75)), $keadaan_gigi) ?></div>
            <div class="baris-gigi"><?= baris_gigi(array_merge(range(48, 41), range(31, 38)), $keadaan_gigi) ?></div>

            <div class="petunjuk petunjuk-gigi">
                <?php foreach (kod_keadaan_gigi() as $kod => $maklumat) { ?>
                    <span style="background:<?= $maklumat['warna'] ?>"><?= e($kod) ?> <?= e($maklumat['nama']) ?></span>
                <?php } ?>
            </div>
        </div>

        <hr class="pembahagi">

        <div class="card-title">Sejarah Rawatan Terkini</div>
        <div class="table-container">
            <table>
                <thead><tr><th>Tarikh</th><th>Rawatan</th><th>Diagnosis</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($sejarah) === 0) { ?>
                    <tr><td colspan="3"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada rekod rawatan.</div></div></td></tr>
                <?php } ?>
                <?php while ($row = mysqli_fetch_assoc($sejarah)) { ?>
                    <tr>
                        <td><?= e($row['tarikh_rawatan'] ? date('d/m/Y', strtotime($row['tarikh_rawatan'])) : '-') ?></td>
                        <td><?= e($row['nama_rawatan']) ?></td>
                        <td><?= e($row['diagnosis']) ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="keizinan">
            <strong>KEIZINAN RAWATAN</strong><br>
            Saya ............................................................ No K/P ............................................
            bagi pihak saya / jagaan saya ............................................................ dengan ini memberi keizinan
            untuk menjalani rawatan yang keadaannya dan tujuannya telah diterangkan kepada saya oleh
            Dr. ............................................................<br>
            Tandatangan (pesakit, ibu bapa atau penjaga) ............................................ Tarikh ......................
        </div>
    </div>

    <div class="btn-group">
        <button class="btn" onclick="window.print()">Cetak Kad Rawatan</button>
        <a class="btn btn-back" href="<?= $_SESSION['role'] === 'doktor' ? '../doktor/pesakit.php' : ($_SESSION['role'] === 'kakitangan' ? '../kakitangan/pesakit.php' : '../admin/pengguna.php?tab=pesakit') ?>">Kembali</a>
    </div>
</div>
<script src="../assets/js/ui.js?v=7" defer></script>
</body>
</html>
