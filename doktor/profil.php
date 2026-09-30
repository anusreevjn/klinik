<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'doktor', '../staff_login.php');

// SECURITY CHECK

$id = $_SESSION['user_id'];
$message = "";

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $nama = trim($_POST['nama_doktor']);
    $no_lesen = trim($_POST['no_lesen']);
    $tarikh_tamat_lesen = $_POST['tarikh_tamat_lesen'];
    $tarikh_mula_kerja = $_POST['tarikh_mula_kerja'];
    $tarikh_tamat_kerja = $_POST['tarikh_tamat_kerja'];
    $kepakaran = trim($_POST['kepakaran']);
    $telefon = trim($_POST['no_telefon']);
    $email = trim($_POST['email']);
    $password_baru = $_POST['password_baru'];

    // BASIC VALIDATION
    if (empty($nama) || empty($email)) {
        $message = "Sila isi maklumat wajib.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email tidak sah.";
    }
    else {

        // CHECK EMAIL DUPLICATE
        $check = $conn->prepare("SELECT id_doktor FROM doktor WHERE email = ? AND id_doktor != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $message = "Email sudah digunakan.";
        } else {

            /* =========================
               PASSWORD LOGIC
            ========================= */
            if (!empty($password_baru)) {

                if (strlen($password_baru) < 6) {
                    $message = "Password minimum 6 aksara.";
                } else {
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                }

            } else {
                // ambil password lama
                $getPass = $conn->prepare("SELECT kata_laluan FROM doktor WHERE id_doktor = ?");
                $getPass->bind_param("i", $id);
                $getPass->execute();
                $hashed_password = $getPass->get_result()->fetch_assoc()['kata_laluan'];
            }

            if (empty($message)) {

                $sql_update = "UPDATE doktor SET
                    nama_doktor = ?,
                    no_lesen = ?,
                    tarikh_tamat_lesen = ?,
                    tarikh_mula_kerja = ?,
                    tarikh_tamat_kerja = ?,
                    kepakaran = ?,
                    no_telefon = ?,
                    email = ?,
                    kata_laluan = ?
                    WHERE id_doktor = ?";

                $stmt = $conn->prepare($sql_update);

                $stmt->bind_param(
                    "sssssssssi",
                    $nama,
                    $no_lesen,
                    $tarikh_tamat_lesen,
                    $tarikh_mula_kerja,
                    $tarikh_tamat_kerja,
                    $kepakaran,
                    $telefon,
                    $email,
                    $hashed_password,
                    $id
                );

                if ($stmt->execute()) {
                    $message = "Profil berjaya dikemaskini!";
                } else {
                    $message = "Ralat semasa kemaskini profil.";
                }
            }
        }
    }
}

/* =========================
   FETCH DATA
========================= */
$sql = "SELECT * FROM doktor WHERE id_doktor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<?php mula_halaman($conn, 'Profil Saya', 'doktor', 'profil.php'); ?>

<div class="card" style="max-width:760px;">
    <h3 class="card-title">Maklumat Doktor</h3>

    <?php if ($message) { ?>
        <div class="alert <?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="nama_doktor">Nama Doktor</label>
            <input type="text" id="nama_doktor" name="nama_doktor" class="form-control" value="<?= e($user['nama_doktor']) ?>">
        </div>

        <div class="form-group">
            <label for="no_lesen">No Lesen</label>
            <input type="text" id="no_lesen" name="no_lesen" class="form-control" value="<?= e($user['no_lesen']) ?>">
        </div>

        <div class="date-group" style="display:flex;gap:14px;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_tamat_lesen">Tarikh Tamat Lesen</label>
                <input type="date" id="tarikh_tamat_lesen" name="tarikh_tamat_lesen" class="form-control" value="<?= e($user['tarikh_tamat_lesen']) ?>">
            </div>
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_mula_kerja">Tarikh Mula Kerja</label>
                <input type="date" id="tarikh_mula_kerja" name="tarikh_mula_kerja" class="form-control" value="<?= e($user['tarikh_mula_kerja']) ?>">
            </div>
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_tamat_kerja">Tarikh Tamat Kerja</label>
                <input type="date" id="tarikh_tamat_kerja" name="tarikh_tamat_kerja" class="form-control" value="<?= e($user['tarikh_tamat_kerja']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="kepakaran">Kepakaran</label>
            <input type="text" id="kepakaran" name="kepakaran" class="form-control" value="<?= e($user['kepakaran']) ?>">
        </div>

        <div class="form-group">
            <label for="no_telefon">No Telefon</label>
            <input type="text" id="no_telefon" name="no_telefon" class="form-control" value="<?= e($user['no_telefon']) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
        </div>

        <div class="form-group">
            <label for="password_baru">Kata Laluan Baharu (Pilihan)</label>
            <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Isi jika mahu tukar kata laluan">
        </div>

        <button type="submit" name="update_profile" class="btn-login">Kemaskini Profil</button>
    </form>
</div>

<?php tamat_halaman(); ?>