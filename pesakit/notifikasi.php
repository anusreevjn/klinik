<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_pesakit = guard($conn, 'pesakit');

if (isset($_POST['baca'])) {
    $id = (int)$_POST['baca'];
    $stmt = mysqli_prepare($conn, "UPDATE notifikasi SET status_baca = 1 WHERE id_notifikasi = ? AND peranan = 'pesakit' AND id_pengguna = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_pesakit);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if (isset($_POST['baca_semua'])) {
    $stmt = mysqli_prepare($conn, "UPDATE notifikasi SET status_baca = 1 WHERE peranan = 'pesakit' AND id_pengguna = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($conn, "SELECT * FROM notifikasi WHERE peranan = 'pesakit' AND id_pengguna = ? ORDER BY tarikh_hantar DESC LIMIT 100");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$belum_baca = kira_notifikasi_belum_baca($conn, 'pesakit', $id_pesakit);

mula_halaman($conn, 'Notifikasi', 'pesakit', 'notifikasi.php', '<form method="post" style="display:inline">' . csrf_field() . '<button class="btn btn-outline" type="submit" name="baca_semua" value="1">Tanda Semua Dibaca</button></form>');
?>

<div class="dashboard-card">
    <p>Notifikasi Belum Dibaca</p>
    <h2><?= (int)$belum_baca ?></h2>
</div>

<?php if (mysqli_num_rows($senarai) === 0) { ?>
    <div class="card">Tiada notifikasi buat masa ini.</div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
    <div class="card history-card">
        <div class="card-title">
            <?= selamat($row['tajuk']) ?>
            <?php if ((int)$row['status_baca'] === 0) { ?>
                <span class="badge-status menunggu">Baharu</span>
            <?php } ?>
        </div>
        <p><?= selamat($row['mesej']) ?></p>
        <p class="invoice-date"><?= selamat(date('d/m/Y h:i A', strtotime($row['tarikh_hantar']))) ?></p>
        <div class="btn-group">
            <?php if ($row['pautan']) { ?>
                <a class="btn btn-outline" href="<?= selamat($row['pautan']) ?>">Lihat</a>
            <?php } ?>
            <?php if ((int)$row['status_baca'] === 0) { ?>
                <form method="post" style="display:inline"><?= csrf_field() ?><button class="btn" type="submit" name="baca" value="<?= (int)$row['id_notifikasi'] ?>">Tanda Dibaca</button></form>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
