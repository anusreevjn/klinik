<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'pentadbir', '../staff_login.php');

// SECURITY CHECK

$id = $_SESSION['user_id'];
$message = "";

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $nama = trim($_POST['nama_pentadbir']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['no_telefon']);
    $jawatan = trim($_POST['jawatan']);

    if (empty($nama) || empty($email)) {
        $message = "Sila isi maklumat wajib.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email tidak sah.";
    }
    else {

        $check = "SELECT id_pentadbir FROM pentadbir WHERE email = ? AND id_pentadbir != ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $message = "Email sudah digunakan.";
        } else {

            $update = "UPDATE pentadbir SET
                        nama_pentadbir = ?,
                        email = ?,
                        no_telefon = ?,
                        jawatan = ?
                        WHERE id_pentadbir = ?";

            $stmt = $conn->prepare($update);
            $stmt->bind_param("ssssi", $nama, $email, $telefon, $jawatan, $id);

            if ($stmt->execute()) {
                $message = "Profil berjaya dikemaskini!";
            } else {
                $message = "Ralat semasa kemaskini.";
            }
        }
    }
}

/* =========================
   FETCH DATA
========================= */
$sql = "SELECT * FROM pentadbir WHERE id_pentadbir = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<?php mula_halaman($conn, 'Profil Saya', 'pentadbir', 'profil.php'); ?>

<a href="dashboard.php" class="btn-back" style="margin-bottom:14px;">&larr; Kembali ke Papan Utama</a>

<div class="card" style="max-width:720px;">
    <h3 class="card-title">Maklumat Pentadbir</h3>

    <?php if ($message) { ?>
        <div class="alert <?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="nama_pentadbir">Nama</label>
            <input type="text" id="nama_pentadbir" name="nama_pentadbir" class="form-control" value="<?= e($user['nama_pentadbir']) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
        </div>

        <div class="form-group">
            <label for="no_telefon">No Telefon</label>
            <input type="text" id="no_telefon" name="no_telefon" class="form-control" value="<?= e($user['no_telefon']) ?>">
        </div>

        <div class="form-group">
            <label for="jawatan">Jawatan</label>
            <input type="text" id="jawatan" name="jawatan" class="form-control" value="<?= e($user['jawatan']) ?>">
        </div>

        <button type="submit" name="update_profile" class="btn-login">Kemaskini Profil</button>
    </form>
</div>

<?php tamat_halaman(); ?>