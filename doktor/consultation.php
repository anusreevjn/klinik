<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

$id_doktor_sesi = guard($conn, 'doktor', '../staff_login.php');

$id_temu_janji = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";

$stmt = mysqli_prepare($conn, "SELECT t.id_temu_janji, t.tarikh_temu_janji, t.masa_temu_janji, t.jenis_rawatan, p.nama_pesakit FROM temu_janji t LEFT JOIN pesakit p ON p.id_pesakit = t.id_pesakit WHERE t.id_temu_janji = ? AND t.id_doktor = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $id_temu_janji, $id_doktor_sesi);
mysqli_stmt_execute($stmt);
$temu_janji = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (isset($_POST['submit'])) {

    if (!$temu_janji) {
        $message = "Temu janji tidak dijumpai atau bukan pesakit anda.";
    } else {

        $simptom = trim($_POST['simptom']);
        $diagnosis = trim($_POST['diagnosis']);
        $cadangan = trim($_POST['cadangan']);

        $stmt = mysqli_prepare($conn, "INSERT INTO konsultasi (id_temu_janji, simptom, diagnosis, cadangan_doktor) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $id_temu_janji, $simptom, $diagnosis, $cadangan);

        if (mysqli_stmt_execute($stmt)) {
            audit($conn, 'konsultasi_disimpan', $id_temu_janji, '');
            $message = "Konsultasi berjaya disimpan";
        } else {
            $message = "Gagal simpan konsultasi.";
        }
        mysqli_stmt_close($stmt);
    }
}

?>

<?php mula_halaman($conn, 'Konsultasi Pesakit', 'doktor', 'consultation.php'); ?>

<div class="card" style="max-width:720px;">
    <h3 class="card-title">Konsultasi Pesakit</h3>

    <?php if ($message !== '') { ?>
        <div class="alert <?= strpos($message,'berjaya') !== false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>
    <?php if ($temu_janji) { ?>
        <div class="profile-item"><span class="info-label">Pesakit</span><span><?= e($temu_janji['nama_pesakit']) ?> | <?= e($temu_janji['jenis_rawatan']) ?></span></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="simptom">Simptom</label>
            <textarea id="simptom" name="simptom" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label for="diagnosis">Diagnosis</label>
            <textarea id="diagnosis" name="diagnosis" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label for="cadangan">Cadangan Doktor</label>
            <textarea id="cadangan" name="cadangan" class="form-control"></textarea>
        </div>

        <button type="submit" name="submit" class="btn-login">Simpan Konsultasi</button>
    </form>
</div>

<?php tamat_halaman(); ?>