<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/pelan_lib.php';
require_once '../include/penjaga_lib.php';

$id_akaun = guard($conn, 'pesakit');
$id_pesakit = id_profil_aktif($conn, $id_akaun);

$senarai = senarai_pelan($conn, $id_pesakit, 'Semua');

mula_halaman($conn, 'Pelan Ansuran', 'pesakit', 'pelan.php');
echo pemilih_profil($conn, $id_akaun, $id_pesakit, 'pelan.php');
?>

<div class="card">
    <p>Di sini anda boleh lihat pelan bayaran berperingkat seperti braces atau gigi palsu, termasuk jumlah keseluruhan, jumlah yang telah dibayar, baki dan sejarah setiap bayaran.</p>
</div>

<?php if (mysqli_num_rows($senarai) === 0) { ?>
    <div class="card">Tiada pelan ansuran untuk profil ini.</div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($senarai)) {
    $baki = max(0, (float)$row['jumlah_keseluruhan'] - (float)$row['jumlah_dibayar']);
    $peratus = (float)$row['jumlah_keseluruhan'] > 0 ? round(((float)$row['jumlah_dibayar'] / (float)$row['jumlah_keseluruhan']) * 100) : 0;
    $transaksi = transaksi_pelan($conn, (int)$row['id_pelan']);
?>
    <div class="card">
        <div class="card-title">
            <?= selamat($row['nama_pelan']) ?>
            <span class="badge-status <?= kelas_status($row['status_pelan']) ?>"><?= selamat($row['status_pelan']) ?></span>
        </div>

        <div class="profile-item"><span class="info-label">Jumlah Keseluruhan</span><span><?= wang($row['jumlah_keseluruhan']) ?></span></div>
        <div class="profile-item"><span class="info-label">Telah Dibayar</span><span><strong><?= wang($row['jumlah_dibayar']) ?></strong> (<?= $peratus ?>%)</span></div>
        <div class="profile-item"><span class="info-label">Baki</span><span><strong><?= wang($baki) ?></strong></span></div>
        <div class="profile-item"><span class="info-label">Ansuran Dijangka</span><span><?= wang($row['ansuran_dijangka']) ?></span></div>
        <?php if ($row['catatan']) { ?>
            <div class="profile-item"><span class="info-label">Catatan</span><span><?= selamat($row['catatan']) ?></span></div>
        <?php } ?>

        <div class="table-container">
            <table>
                <thead><tr><th>Tarikh</th><th>Jenis</th><th>Jumlah</th><th>Kaedah</th><th>No Resit</th><th>Resit</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($transaksi) === 0) { ?>
                    <tr><td colspan="6"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Belum ada bayaran direkodkan.</div></div></td></tr>
                <?php } ?>
                <?php while ($t = mysqli_fetch_assoc($transaksi)) { ?>
                    <tr>
                        <td><?= selamat($t['tarikh_bayaran'] ? date('d/m/Y', strtotime($t['tarikh_bayaran'])) : '-') ?></td>
                        <td><?= selamat($t['jenis_bayaran']) ?></td>
                        <td><?= wang($t['jumlah_bayaran']) ?></td>
                        <td><?= selamat($t['kaedah_bayaran']) ?></td>
                        <td><?= selamat($t['no_resit'] ?: '-') ?></td>
                        <td>
                            <?php if ($t['no_resit']) { ?>
                                <a class="action-btn" target="_blank" href="../kakitangan/resit.php?id=<?= (int)$t['id_pembayaran'] ?>">Lihat</a>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php tamat_halaman();
