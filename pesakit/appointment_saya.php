<?php
require_once '../config.php';
require_once '../include/helpers.php';
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

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Temu Janji Saya</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>

<body>

<div class="dashboard">

<?php include '../include/sidebar.php'; ?>

<div class="main">
<?= pemilih_profil($conn, $id_akaun, $id_pesakit, 'appointment_saya.php') ?>


<div class="topbar">
    <h3>Temu Janji Anda</h3>
</div>

<div class="card">

<?php if(mysqli_num_rows($result) == 0){ ?>
    <p>Tiada temu janji dibuat lagi.</p>
<?php } ?>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

    <div style="
        padding:15px;
        border:1px solid #ddd;
        border-radius:12px;
        margin-bottom:10px;
        background:#fff;
    ">

        <p><b>Tarikh:</b> <?= $row['tarikh_temu_janji'] ?></p>
        <p><b>Masa:</b> <?= $row['masa_temu_janji'] ?></p>
        <p><b>Status:</b> <?= $row['status'] ?></p>

        <?php if($row['status'] == 'Menunggu'){ ?>
            <span style="color:orange;">⏳ Menunggu pengesahan doktor</span>
        <?php } elseif($row['status'] == 'Diluluskan'){ ?>
            <span style="color:green;">✅ Diluluskan</span>
        <?php } else { ?>
            <span style="color:red;">❌ Ditolak</span>
        <?php } ?>

    </div>

<?php } ?>

</div>

</div>

</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>