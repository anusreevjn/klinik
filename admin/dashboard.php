<?php
require_once '../config.php';
require_once '../include/helpers.php';

guard($conn, 'pentadbir', '../staff_login.php');


   //TOTAL PESAKIT
$sql_pesakit = "SELECT COUNT(*) AS total_pesakit FROM pesakit";
$result_pesakit = mysqli_query($conn, $sql_pesakit);
$data_pesakit = mysqli_fetch_assoc($result_pesakit);

//TOTAL DOKTOR

$sql_doktor = "SELECT COUNT(*) AS total_doktor FROM doktor";
$result_doktor = mysqli_query($conn, $sql_doktor);
$data_doktor = mysqli_fetch_assoc($result_doktor);


   //TOTAL KAKITANGAN

$sql_staff = "SELECT COUNT(*) AS total_staff FROM kakitangan";
$result_staff = mysqli_query($conn, $sql_staff);
$data_staff = mysqli_fetch_assoc($result_staff);

// TOTAL APPOINTMENT

$sql_appointment = "SELECT COUNT(*) AS total_appointment FROM temu_janji";
$result_appointment = mysqli_query($conn, $sql_appointment);
$data_appointment = mysqli_fetch_assoc($result_appointment);


   //APPOINTMENT TERKINI

$sql_latest = "SELECT * FROM temu_janji
ORDER BY tarikh_temu_janji DESC, masa_temu_janji DESC
LIMIT 5";

$result_latest = mysqli_query($conn, $sql_latest);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">

    <style>

    .dashboard-card{
        background: white;
        padding: 20px;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        flex: 1;
        min-width: 220px;
    }

    .dashboard-card h2{
        margin: 0;
        font-size: 32px;
        color: #0f766e;
    }

    .dashboard-card p{
        margin-top: 10px;
        color: #555;
        font-weight: 600;
    }

    .table-container{
        margin-top: 25px;
        background: white;
        padding: 20px;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    table{
        width: 100%;
        border-collapse: collapse;
    }

    table th{
        background: #0f766e;
        color: white;
        padding: 12px;
        text-align: left;
    }

    table td{
        padding: 12px;
        border-bottom: 1px solid #ddd;
    }

    .status-menunggu{
        color: orange;
        font-weight: bold;
    }

    .status-disahkan{
        color: green;
        font-weight: bold;
    }

    .status-ditolak{
        color: red;
        font-weight: bold;
    }

    </style>
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="../assets/image/logo.jpg" alt="Logo">
            <h2>Klinik Dr Arifin</h2>
        </div>

        <a href="dashboard.php" class="active"> Dashboard</a>
        <a href="tambah_doktor.php"> Tambah Doktor</a>
        <a href="tambah_kakitangan.php"> Tambah Kakitangan</a>
        <a href="inventori.php"> Inventori</a>
        <a href="laporan.php"> Laporan</a>
        <a href="../staff_login.php"> Log Keluar</a>

    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="topbar">
            <h2>Papan Pemuka Pentadbir</h2>
        </div>

        <!-- CARD -->
        <div style="display:flex; gap:20px; flex-wrap:wrap;">

            <div class="dashboard-card">
                <h2><?= $data_pesakit['total_pesakit'] ?></h2>
                <p>Jumlah Pesakit</p>
            </div>

            <div class="dashboard-card">
                <h2><?= $data_doktor['total_doktor'] ?></h2>
                <p>Jumlah Doktor</p>
            </div>

            <div class="dashboard-card">
                <h2><?= $data_staff['total_staff'] ?></h2>
                <p>Jumlah Kakitangan</p>
            </div>

            <div class="dashboard-card">
                <h2><?= $data_appointment['total_appointment'] ?></h2>
                <p>Jumlah Temu Janji</p>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-container">
            <h3>Temu Janji Terkini</h3>
            <table>

                <tr>
                    <th>ID</th>
                    <th>Tarikh</th>
                    <th>Masa</th>
                    <th>Jenis Rawatan</th>
                    <th>Status</th>
                </tr>

                <?php while($row = mysqli_fetch_assoc($result_latest)) { ?>

                <tr>
                    <td><?= $row['id_temu_janji'] ?></td>
                    <td><?= $row['tarikh_temu_janji'] ?></td>
                    <td><?= substr($row['masa_temu_janji'],0,5) ?></td>
                    <td><?= $row['jenis_rawatan'] ?></td>
                    <td>

                        <?php if($row['status'] == 'Menunggu'){ ?>
                            <span class="status-menunggu">Menunggu</span>

                        <?php } elseif($row['status'] == 'Disahkan'){ ?>
                            <span class="status-disahkan">Disahkan</span>

                        <?php } else { ?>
                            <span class="status-ditolak"><?= $row['status'] ?></span>
                        <?php } ?>

                    </td>

                </tr>

                <?php } ?>

            </table>

        </div>

    </div>

</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>