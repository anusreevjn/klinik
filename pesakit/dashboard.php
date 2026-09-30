<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';
require_once '../include/penjaga_lib.php';

guard($conn, 'pesakit', '../login.php');

// Jika user bukan pesakit, paksa log keluar


$id_akaun = (int)$_SESSION['user_id'];
$id = id_profil_aktif($conn, $id_akaun);
$profil_semasa = profil_pesakit($conn, $id);

// 1. Ambil maklumat pesakit
$sql = "SELECT * FROM pesakit WHERE id_pesakit='$id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// 2. Ambil data temu janji akan datang (PENTING: Mesti ada di sini)
$sql_upcoming = "
    SELECT * FROM temu_janji
    WHERE id_pesakit='$id'
    AND CONCAT(tarikh_temu_janji, ' ', masa_temu_janji) >= NOW()
    AND status IN ('Menunggu','Disahkan','Dipanggil')
    ORDER BY tarikh_temu_janji ASC, masa_temu_janji ASC
    LIMIT 1
";
$res_upcoming = mysqli_query($conn, $sql_upcoming);
$upcoming = mysqli_fetch_assoc($res_upcoming);
?>

<?php mula_halaman($conn, 'Papan Utama', 'pesakit', 'dashboard.php'); ?>

<?= pemilih_profil($conn, $id_akaun, $id, 'dashboard.php') ?>

<div style="display:flex; gap:20px; flex-wrap:wrap;">

    <div class="card" style="flex:1; min-width:300px;">
        <h3 class="card-title">Maklumat Pesakit</h3>
        <p><b>Nama:</b> <?= e($user['nama_pesakit']) ?></p>
        <p><b>No. Kad Pengenalan:</b> <?= e($user['no_ic']) ?></p>
        <p><b>No. Telefon:</b> <?= e($user['no_telefon']) ?></p>
        <p><b>Email:</b> <?= e($user['email']) ?></p>
    </div>

    <div class="card" style="flex:1; min-width:300px;">
        <h3 class="card-title">Temu Janji Akan Datang</h3>
        <?php if($upcoming): ?>
            <p><b>Jenis:</b> <?= e($upcoming['jenis_rawatan']) ?></p>
            <p><b>Tarikh:</b> <?= e($upcoming['tarikh_temu_janji']) ?></p>
            <p><b>Masa:</b> <?= e(substr($upcoming['masa_temu_janji'], 0, 5)) ?></p>
            <p><b>Status:</b> <span class="badge-status"><?= e($upcoming['status']) ?></span></p>
        <?php else: ?>
            <p style="color:var(--c-muted)">Tiada temu janji akan datang.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Aktiviti Pantas</h3>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="appointment.php" class="btn" style="flex:1; min-width:200px;">Temu Janji</a>
        <a href="pembayaran.php" class="btn btn-outline" style="flex:1; min-width:200px;">Invois &amp; Resit</a>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Status Klinik</h3>
    <p><span class="badge-status selesai">Sistem aktif</span></p>
    <p style="color:var(--c-muted)">Klinik Pergigian Dr. Arifin</p>
</div>

<?php tamat_halaman(); ?>