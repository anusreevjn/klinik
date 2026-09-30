<?php
require_once '../config.php';
require_once '../include/helpers.php';

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

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Doktor</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">

<style>
.card{
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

.form-control{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border:1px solid #ddd;
    border-radius:8px;
}

.btn-login{
    background:#0f766e;
    color:white;
    padding:10px 15px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn-login:hover{
    background:#115e59;
}

.alert{
    padding:10px;
    background:#e2f7f3;
    margin-bottom:10px;
    border-radius:8px;
}
</style>

</head>

<body>

<div class="dashboard">

<div class="sidebar">

<div class="sidebar-logo">
<img src="../assets/image/logo.jpg">
<h2>Klinik Dr Arifin</h2>
</div>

<a href="dashboard.php"> Dashboard</a>
<a href="tambah_doktor.php" class="active"> Tambah Doktor</a>
<a href="tambah_kakitangan.php"> Tambah Kakitangan</a>
<a href="inventori.php"> Inventori</a>
        <a href="laporan.php"> Laporan</a>
<a href="../logout.php"> Log Keluar</a>

</div>

<div class="main">

<h3>Tambah Doktor</h3>

<?php if ($message != "") { ?>
<div class="alert">
    <?= $message ?>
</div>
<?php } ?>

<div class="card">

<form method="POST"><?= csrf_field() ?>

<input type="text" name="nama" placeholder="Nama Doktor" class="form-control">

<input type="text" name="lesen" placeholder="No Lesen" class="form-control">

<label>Tarikh Lesen</label>
<input type="date" name="tarikh_lesen" class="form-control">

<label>Tarikh Mula Kerja</label>
<input type="date" name="tarikh_mula" class="form-control">

<label>Tarikh Tamat Kerja</label>
<input type="date" name="tarikh_tamat" class="form-control">

<input type="text" name="kepakaran" placeholder="Kepakaran" class="form-control">

<input type="text" name="telefon" placeholder="No Telefon (contoh: 0123456789)" class="form-control">

<input type="email" name="email" placeholder="Email" class="form-control">

<input type="password" name="password" placeholder="Password (min 6)" class="form-control">

<button type="submit" name="tambah" class="btn-login">
Tambah Doktor
</button>

</form>

</div>

</div>
</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>