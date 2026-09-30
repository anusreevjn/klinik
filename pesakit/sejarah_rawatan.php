<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';
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

<?php mula_halaman($conn, 'Rekod Rawatan', 'pesakit', 'sejarah_rawatan.php'); ?>

<?= pemilih_profil($conn, $id_akaun, $id, 'sejarah_rawatan.php') ?>

<?php if ($result->num_rows === 0) { ?>
    <div class="card"><p style="color:var(--c-muted)">Tiada rekod rawatan lagi.</p></div>
<?php } ?>
<?php while($row = $result->fetch_assoc()) { ?>
    <div class="card">
        <h3 class="card-title"><?= e($row['nama_rawatan']) ?></h3>
        <p>Doktor: <?= e($row['nama_doktor']) ?></p>
        <p>Tarikh: <?= e(date("d-m-Y", strtotime($row['tarikh_rawatan']))) ?></p>
        <button class="btn" onclick='viewDetail(<?= json_encode($row, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>Lihat Butiran</button>
    </div>
<?php } ?>

<div id="modal" class="modal">
    <div class="modal-content">
        <h3>Rekod Rawatan</h3>
        <p><b>Doktor:</b> <span id="modal-doktor"></span></p>
        <p><b>Tarikh:</b> <span id="modal-tarikh"></span></p>
        <p><b>Rawatan:</b> <span id="modal-rawatan"></span></p>
        <p><b>Diagnosis:</b> <span id="modal-diagnosis"></span></p>
        <p><b>Preskripsi:</b><br><span id="modal-preskripsi"></span></p>
        <button class="btn btn-outline" onclick="closeModal()">Tutup</button>
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

<?php tamat_halaman(); ?>