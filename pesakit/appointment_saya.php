<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';
require_once '../include/penjaga_lib.php';

guard($conn, 'pesakit', '../login.php');

$id_akaun = (int)$_SESSION['user_id'];
$id_pesakit = id_profil_aktif($conn, $id_akaun);
$profil_semasa = profil_pesakit($conn, $id_pesakit);
$today = date('Y-m-d');

$stmt = mysqli_prepare($conn, "SELECT * FROM temu_janji WHERE id_pesakit = ? AND tarikh_temu_janji >= ? AND status IN ('Menunggu', 'Disahkan', 'Dipanggil') ORDER BY tarikh_temu_janji ASC, masa_temu_janji ASC LIMIT 1");
mysqli_stmt_bind_param($stmt, "is", $id_pesakit, $today);
mysqli_stmt_execute($stmt);
$upcoming = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM temu_janji WHERE id_pesakit = ? ORDER BY tarikh_temu_janji DESC, masa_temu_janji DESC LIMIT 50");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

?>

<?php mula_halaman($conn, 'Sejarah Temu Janji', 'pesakit', 'appointment_saya.php'); ?>

<?= pemilih_profil($conn, $id_akaun, $id_pesakit, 'appointment_saya.php') ?>

<div class="card">
    <h3 class="card-title">Semua Temu Janji</h3>

    <?php if(mysqli_num_rows($result) == 0){ ?>
        <p style="color:var(--c-muted)">Tiada temu janji dibuat lagi.</p>
    <?php } ?>

    <?php while($row = mysqli_fetch_assoc($result)){ ?>
        <div class="history-card" style="margin-bottom:10px;">
            <p><b>Tarikh:</b> <?= e($row['tarikh_temu_janji']) ?></p>
            <p><b>Masa:</b> <?= e(substr($row['masa_temu_janji'],0,5)) ?></p>
            <p><b>Jenis:</b> <?= e($row['jenis_rawatan']) ?></p>
            <p><b>Status:</b> <span class="badge-status"><?= e($row['status']) ?></span></p>
        </div>
    <?php } ?>
</div>

<?php tamat_halaman(); ?>