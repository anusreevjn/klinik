<?php
require_once '../config.php';
require_once '../include/helpers.php';
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

<!DOCTYPE html>
<html>
<head>
    <title>Portal Pesakit</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/image/logo.jpg" alt="Logo Klinik">
            <h2>Klinik Dr Arifin</h2>
        </div>
        <p style="font-size:13px; opacity:0.9; text-align:center; margin-bottom:10px;">
            Portal Pesakit<br>
            <b><?= $user['nama_pesakit'] ?></b>
        </p>
        <hr style="border:0; border-top:1px solid rgba(255,255,255,0.2); margin:10px 0;">
        <a href="dashboard.php" class="active"> Dashboard</a>
        <a href="appointment.php"> Temu Janji</a>
        <a href="sejarah_rawatan.php"> Sejarah</a>
        <a href="pembayaran.php"> Invois & Resit</a>
        <a href="notifikasi.php"> Notifikasi</a>
        <a href="profil.php"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>
        
    </div>

    <div class="main">
<?= pemilih_profil($conn, $id_akaun, $id, 'dashboard.php') ?>

        <div class="topbar">
            <h3>Dashboard Pesakit</h3>
            <span style="color:#0f766e; font-weight:bold;">Selamat Datang 👋</span>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div class="card" style="flex: 1; min-width: 300px;">
                <h3>Maklumat Pesakit</h3>
                <p><b>Nama:</b> <?= htmlspecialchars($user['nama_pesakit']) ?></p>
                <p><b>No. Kad Pengenalan:</b> <?= htmlspecialchars($user['no_ic']) ?></p>
                <p><b>No. Telefon:</b> <?= htmlspecialchars($user['no_telefon']) ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($user['email']) ?></p>
            </div>

            <div class="card" style="flex: 1; min-width: 300px;">
                <h3>📅 Temu Janji Akan Datang</h3>
                <?php if($upcoming): ?>
                    <p><b>Jenis:</b> <?= htmlspecialchars($upcoming['jenis_rawatan']) ?></p>
                    <p><b>Tarikh:</b> <?= htmlspecialchars($upcoming['tarikh_temu_janji']) ?></p>
                    <p><b>Masa:</b> <?= substr($upcoming['masa_temu_janji'], 0, 5) ?></p>
                    <p><b>Status:</b> <?= htmlspecialchars($upcoming['status']) ?></p>
                <?php else: ?>
                    <p>Tiada temu janji akan datang.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>Aktiviti Pantas</h3>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="appointment.php" class="btn-login" style="flex:1; text-align:center;">📅 Temu Janji</a>
                <a href="pembayaran.php" class="btn-login" style="flex:1; text-align:center;">💳 Bayaran</a>
            </div>
        </div>

        <div class="card">
            <h3>Status Klinik</h3>
            <p>🟢 Sistem aktif</p>
            <p>🦷 Klinik Pergigian Dr. Arifin</p>
        </div>
    </div>
</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>