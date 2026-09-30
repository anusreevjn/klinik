<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'doktor', '../staff_login.php');

// Pastikan hanya user yang login boleh akses
if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff_login.php");
    exit();
}

// 1. QUERY YANG TELAH DIPERBAIKI
// Gunakan subquery (GROUP_CONCAT) untuk tarik sekali maklumat ubat dan gigi dari table lain
$sql = "SELECT r.*, 
               p.nama_pesakit, 
               d.nama_doktor,
               
               -- Ambil maklumat gigi
               (SELECT GROUP_CONCAT(CONCAT('Gigi ', cp.no_gigi, ' (', cp.status_gigi, ')') SEPARATOR ', ')
                FROM rekod_carta_pergigian cp 
                WHERE cp.id_rawatan = r.id_rawatan) AS senarai_gigi,
                
               -- Ambil maklumat ubat (gabung kuantiti dan arahan)
               (SELECT GROUP_CONCAT(CONCAT('ID Ubat:', bp.id_inventori, ' - ', bp.kuantiti, ' unit (', bp.arahan, ')') SEPARATOR '<br>')
                FROM butiran_preskripsi bp 
                WHERE bp.id_rawatan = r.id_rawatan) AS senarai_ubat

        FROM rekod_rawatan r
        LEFT JOIN pesakit p ON r.id_pesakit = p.id_pesakit
        LEFT JOIN doktor d ON r.id_doktor = d.id_doktor
        ORDER BY r.tarikh_rawatan DESC";

$result = mysqli_query($conn, $sql);

// Setkan nama doktor untuk sidebar (Ambil dari Session)
$nama_pengguna = $_SESSION['nama_doktor'] ?? 'Doktor';
?>

<?php mula_halaman($conn, 'Sejarah Rawatan', 'doktor', 'sejarah_rawatan.php'); ?>

<style>
.search-container{display:flex;align-items:center;width:100%;max-width:600px;background:var(--c-surface);padding:10px 15px;border-radius:var(--radius);box-shadow:var(--shadow-xs);margin-bottom:20px;border:1px solid var(--c-border)}
.search-icon{color:var(--c-muted);margin-right:12px}
#searchPatient{border:none;outline:none;width:100%;font-size:15px;background:transparent;color:var(--c-text)}
</style>

<a href="dashboard.php" class="btn-back" style="margin-bottom:14px;">&larr; Kembali ke Papan Utama</a>

<div class="search-container">
    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" id="searchPatient" onkeyup="searchFunction()" placeholder="Cari nama pesakit...">
</div>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<div class="card history-card">
    <h3 class="card-title"><?= e($row['nama_pesakit']) ?></h3>
    <p><?= e($row['tarikh_rawatan']) ?> | Dr <?= e($row['nama_doktor']) ?></p>
    <p><b>RM <?= e(number_format($row['harga_rawatan'], 2)) ?></b></p>
    <button class="btn" onclick='viewDetail(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>Lihat Butiran</button>
</div>
<?php } ?>

<!-- MODAL -->
<div id="modal" class="modal">
    <div class="modal-content">
        <h3>REKOD RAWATAN PESAKIT</h3>
        <p><b>Nama:</b> <span id="nama"></span></p>
        <p><b>Doktor:</b> <span id="doktor"></span></p>
        <p><b>Tarikh:</b> <span id="tarikh"></span></p>
        <p><b>Kos:</b> RM <span id="kos"></span></p>
        <hr>
        <p><b>Rawatan:</b><br><span id="rawatan"></span></p>
        <p><b>Gigi Dirawat:</b><br><span id="gigi_dirawat" style="color:#e03131; font-weight:bold;"></span></p>
        <p><b>Diagnosis:</b><br><span id="diagnosis"></span></p>
        <p><b>Catatan / Prosedur:</b><br><span id="catatan"></span></p>
        <hr>
        <p><b>Preskripsi Ubat:</b><br><span id="preskripsi"></span></p>

        <br>
        <button class="btn" onclick="window.print()">Print</button>
        <button class="btn btn-batal" onclick="closeModal()">Tutup</button>
    </div>
</div>

<script>
function viewDetail(data){
    document.getElementById('nama').innerText = data.nama_pesakit;
    document.getElementById('doktor').innerText = data.nama_doktor;
    document.getElementById('tarikh').innerText = data.tarikh_rawatan;
    
    // Update variable name to match database
    document.getElementById('kos').innerText = data.harga_rawatan; 
    document.getElementById('rawatan').innerText = data.nama_rawatan;
    document.getElementById('diagnosis').innerText = data.diagnosis;
    document.getElementById('catatan').innerText = data.nota_rawatan;
    
    // Paparkan data dari table lain (yang ditarik guna subquery SQL)
    document.getElementById('gigi_dirawat').innerText = data.senarai_gigi ? data.senarai_gigi : 'Tiada rekod gigi spesifik';
    document.getElementById('preskripsi').innerHTML = data.senarai_ubat ? data.senarai_ubat : 'Tiada ubat dipreskripsi';

    document.getElementById('modal').style.display = "block";
}

function closeModal(){
    document.getElementById('modal').style.display = "none";
}

function searchFunction() {
    let input = document.getElementById('searchPatient');
    let filter = input.value.toLowerCase();
    let cards = document.getElementsByClassName('history-card');

    for (let i = 0; i < cards.length; i++) {
        let title = cards[i].getElementsByTagName("h3")[0];
        if (title) {
            let txtValue = title.textContent || title.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                cards[i].style.display = ""; 
            } else {
                cards[i].style.display = "none"; 
            }
        }
    }
}
</script>

<?php tamat_halaman(); ?>