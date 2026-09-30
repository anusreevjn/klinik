<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'pentadbir', '../staff_login.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pentadbir') {
    header("Location: ../staff_login.php");
    exit();
}

$message = "";

/* SUCCESS MESSAGE*/
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}

/* INSERT DOKTOR (STRICT)*/
if (isset($_POST['tambah'])) {

    $nama = trim($_POST['nama']);
    $lesen = trim($_POST['lesen']);
    $tarikh_lesen = $_POST['tarikh_lesen'];
    $tarikh_mula = $_POST['tarikh_mula'];
    $tarikh_tamat = $_POST['tarikh_tamat'];
    $kepakaran = trim($_POST['kepakaran']);
    $telefon = trim($_POST['telefon']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    /* VALIDATION STRICt*/

    if (
        empty($nama) || empty($lesen) || empty($tarikh_lesen) ||
        empty($tarikh_mula) || empty($kepakaran) ||
        empty($telefon) || empty($email) || empty($password_raw)
    ) {
        $message = "Sila lengkapkan semua maklumat wajib.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email tidak sah.";
    }
    elseif (strlen($nama) < 3) {
        $message = "Nama terlalu pendek.";
    }
    elseif (!preg_match("/^[0-9]{10,15}$/", $telefon)) {
        $message = "No telefon tidak sah (10-15 digit sahaja).";
    }
    elseif (strlen($password_raw) < 6) {
        $message = "Password minimum 6 aksara.";
    }
    elseif (!empty($tarikh_tamat) && $tarikh_mula > $tarikh_tamat) {
        $message = "Tarikh kerja tidak sah.";
    }
    else {

        /* =========================
           CHECK DUPLICATE EMAIL
        ========================= */
        $check = $conn->prepare("SELECT id_doktor FROM doktor WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result_check = $check->get_result();

        if ($result_check->num_rows > 0) {

            $message = "Email sudah wujud!";

        } else {

            /*  INSERT (PREPARED STATEMENT) */

            $password = password_hash($password_raw, PASSWORD_DEFAULT);

            $sql = "INSERT INTO doktor 
            (nama_doktor, no_lesen, tarikh_tamat_lesen,
            tarikh_mula_kerja, tarikh_tamat_kerja,
            kepakaran, no_telefon, email,
            kata_laluan, gambar_profil, status_aktif)

            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'default.png', 'Aktif')";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sssssssss",
                $nama,
                $lesen,
                $tarikh_lesen,
                $tarikh_mula,
                $tarikh_tamat,
                $kepakaran,
                $telefon,
                $email,
                $password
            );

            if ($stmt->execute()) {

                $_SESSION['success'] = "Doktor berjaya ditambah.";
                header("Location: tambah_doktor.php");
                exit();

            } else {
                $message = "Ralat sistem: " . $stmt->error;
            }
        }
    }
}
?>

<?php mula_halaman($conn, 'Tambah Doktor', 'pentadbir', 'pengguna.php'); ?>

<div class="card" style="max-width:720px;">
    <h3 class="card-title">Tambah Doktor</h3>

    <?php if ($message != "") { ?>
        <div class="alert <?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama Doktor</label>
            <input type="text" id="nama" name="nama" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="lesen">No Lesen</label>
            <input type="text" id="lesen" name="lesen" class="form-control" required>
        </div>

        <div class="date-group" style="display:flex;gap:14px;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_lesen">Tarikh Tamat Lesen</label>
                <input type="date" id="tarikh_lesen" name="tarikh_lesen" class="form-control">
            </div>
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_mula">Tarikh Mula Kerja</label>
                <input type="date" id="tarikh_mula" name="tarikh_mula" class="form-control">
            </div>
            <div class="form-group" style="flex:1;min-width:170px;">
                <label for="tarikh_tamat">Tarikh Tamat Kerja</label>
                <input type="date" id="tarikh_tamat" name="tarikh_tamat" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="kepakaran">Kepakaran</label>
            <input type="text" id="kepakaran" name="kepakaran" class="form-control">
        </div>

        <div class="form-group">
            <label for="telefon">No Telefon</label>
            <input type="text" id="telefon" name="telefon" class="form-control" placeholder="Contoh: 0123456789">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control">
        </div>

        <div class="form-group">
            <label for="password">Kata Laluan (min 6)</label>
            <input type="password" id="password" name="password" class="form-control">
        </div>

        <button type="submit" name="tambah" class="btn-login">Tambah Doktor</button>
    </form>
</div>

<?php tamat_halaman(); ?>