<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

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

<?php mula_halaman($conn, 'Papan Utama', 'pentadbir', 'dashboard.php'); ?>

<div style="display:flex; gap:20px; flex-wrap:wrap;">
    <div class="dashboard-card" style="flex:1;min-width:200px;">
        <p>Jumlah Pesakit</p>
        <h2><?= (int)$data_pesakit['total_pesakit'] ?></h2>
    </div>
    <div class="dashboard-card" style="flex:1;min-width:200px;">
        <p>Jumlah Doktor</p>
        <h2><?= (int)$data_doktor['total_doktor'] ?></h2>
    </div>
    <div class="dashboard-card" style="flex:1;min-width:200px;">
        <p>Jumlah Kakitangan</p>
        <h2><?= (int)$data_staff['total_staff'] ?></h2>
    </div>
    <div class="dashboard-card" style="flex:1;min-width:200px;">
        <p>Jumlah Temu Janji</p>
        <h2><?= (int)$data_appointment['total_appointment'] ?></h2>
    </div>
</div>

<div class="table-container">
    <h3 class="card-title">Temu Janji Terkini</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tarikh</th>
                <th>Masa</th>
                <th>Jenis Rawatan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result_latest)) { ?>
            <tr>
                <td><?= (int)$row['id_temu_janji'] ?></td>
                <td><?= e($row['tarikh_temu_janji']) ?></td>
                <td><?= e(substr($row['masa_temu_janji'],0,5)) ?></td>
                <td><?= e($row['jenis_rawatan']) ?></td>
                <td><span class="badge-status <?= e(kelas_status($row['status'])) ?>"><?= e($row['status']) ?></span></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php tamat_halaman(); ?>