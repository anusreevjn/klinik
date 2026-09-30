<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/penjaga_lib.php';

guard($conn, 'pesakit', '../login.php');


$id_akaun = (int)$_SESSION['user_id'];
$id = id_profil_aktif($conn, $id_akaun);
$profil_semasa = profil_pesakit($conn, $id);

$sql = "SELECT 
            r.id_rawatan,
            r.tarikh_rawatan, 
            r.nama_rawatan,
            r.harga_rawatan,
            r.diagnosis,
            d.nama_doktor,
            (SELECT GROUP_CONCAT(
                CONCAT('• ', COALESCE(i.nama_barang, 'Ubat Luar'), ' (Dos: ', bp.dos, ' | Arahan: ', bp.arahan, ')') 
                SEPARATOR '<br>'
            ) 
            FROM butiran_preskripsi bp 
            LEFT JOIN inventori i ON bp.id_inventori = i.id_inventori 
            WHERE bp.id_rawatan = r.id_rawatan) AS senarai_ubat
        FROM rekod_rawatan r 
        LEFT JOIN doktor d ON r.id_doktor = d.id_doktor 
        WHERE r.id_pesakit = ? 
        ORDER BY r.tarikh_rawatan DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$user_sql = $conn->prepare("SELECT nama_pesakit FROM pesakit WHERE id_pesakit = ?");
$user_sql->bind_param("i", $id);
$user_sql->execute();
$user_result = $user_sql->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sejarah Rawatan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    <style>
        /* Pastikan CSS modal ini ada dalam style.css atau di sini */
        .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
        .modal-content { background:white; margin:5% auto; padding:20px; width:80%; max-width:600px; border-radius:10px; }
    </style>
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
        <a href="dashboard.php"> Dashboard</a>
        <a href="appointment.php"> Temu Janji</a>
        <a href="sejarah_rawatan.php" class="active"> Sejarah</a>
        <a href="pembayaran.php"> Pembayaran</a>
        <a href="profil.php"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>
    </div>

    <div class="main">
<?= pemilih_profil($conn, $id_akaun, $id, 'sejarah_rawatan.php') ?>

        <h2>Sejarah Rawatan Anda</h2>
        <?php while($row = $result->fetch_assoc()) { ?>
            <div class="card" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                <h3><?= htmlspecialchars($row['nama_rawatan']) ?></h3>
                <p>Doktor: <?= htmlspecialchars($row['nama_doktor']) ?></p>
                <p>Tarikh: <?= date("d-m-Y", strtotime($row['tarikh_rawatan'])) ?></p>
                
                <button class="btn" onclick='viewDetail(<?= json_encode($row, JSON_HEX_QUOT | JSON_HEX_APOS) ?>)'>
                    Lihat Butiran
                </button>
            </div>
        <?php } ?>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <h3>REKOD RAWATAN</h3>
        <p><b>Doktor:</b> <span id="modal-doktor"></span></p>
        <p><b>Tarikh:</b> <span id="modal-tarikh"></span></p>
        <p><b>Rawatan:</b> <span id="modal-rawatan"></span></p>
        <p><b>Diagnosis:</b> <span id="modal-diagnosis"></span></p>
        <p><b>Preskripsi:</b><br><span id="modal-preskripsi"></span></p>
        <button class="btn" onclick="closeModal()">Tutup</button>
    </div>
</div>

<script>
function viewDetail(data) {
    document.getElementById('modal-doktor').innerText = data.nama_doktor;
    document.getElementById('modal-tarikh').innerText = data.tarikh_rawatan;
    document.getElementById('modal-rawatan').innerText = data.nama_rawatan;
    document.getElementById('modal-diagnosis').innerText = data.diagnosis;
    document.getElementById('modal-preskripsi').innerHTML = data.senarai_ubat || 'Tiada ubat';
    
    document.getElementById('modal').style.display = "block";
}

function closeModal() {
    document.getElementById('modal').style.display = "none";
}
</script>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>