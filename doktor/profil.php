<?php
require_once '../config.php';
require_once '../include/helpers.php';

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

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Doktor</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">

<style>
.dashboard{display:flex;}
.main{margin-left:260px;padding:20px;width:100%;}

.profile-card{
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    max-width:750px;
}

.profile-item{margin-bottom:12px;}
.profile-item label{font-weight:bold;display:block;margin-bottom:5px;}

.profile-item input{
    width:100%;
    padding:10px;
    border:1px solid #ddd;
    border-radius:8px;
}

.btn-save{
    background:#0f766e;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
}

.btn-save:hover{background:#115e59;}

.success{background:#dcfce7;padding:10px;border-radius:8px;color:#166534;margin-bottom:10px;}
.error{background:#fee2e2;padding:10px;border-radius:8px;color:#991b1b;margin-bottom:10px;}
</style>
</head>

<body>

<div class="dashboard">

<!-- SIDEBAR -->
<div class="sidebar">

<div class="sidebar-logo">
<img src="../assets/image/logo.jpg">
<h2>Klinik Dr Arifin</h2>
</div>

<p>
Doktor:<br>
<b><?= htmlspecialchars($user['nama_doktor']) ?></b>
</p>

        <a href="dashboard.php">Dashboard</a>
        <a href="dashboard.php"> Senarai Pesakit</a>
        <a href="dashboard.php"> Jadual Temu Janji</a>
        <a href="rekod_rawatan.php"> Rekod Rawatan</a>
        <a href="sejarah_rawatan.php"> Sejarah Rawatan</a>
        <a href="profil.php" class="active"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>

</div>

<!-- MAIN -->
<div class="main">

<h2>Profil Doktor</h2>

<div class="profile-card">

<?php if ($message) { ?>
    <div class="<?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>">
        <?= $message ?>
    </div>
<?php } ?>

<form method="POST"><?= csrf_field() ?>

<div class="profile-item">
    <label>Nama Doktor</label>
    <input type="text" name="nama_doktor" value="<?= htmlspecialchars($user['nama_doktor']) ?>">
</div>

<div class="profile-item">
    <label>No Lesen</label>
    <input type="text" name="no_lesen" value="<?= htmlspecialchars($user['no_lesen']) ?>">
</div>

<div class="profile-item">
    <label>Tarikh Tamat Lesen</label>
    <input type="date" name="tarikh_tamat_lesen" value="<?= $user['tarikh_tamat_lesen'] ?>">
</div>

<div class="profile-item">
    <label>Tarikh Mula Kerja</label>
    <input type="date" name="tarikh_mula_kerja" value="<?= $user['tarikh_mula_kerja'] ?>">
</div>

<div class="profile-item">
    <label>Tarikh Tamat Kerja</label>
    <input type="date" name="tarikh_tamat_kerja" value="<?= $user['tarikh_tamat_kerja'] ?>">
</div>

<div class="profile-item">
    <label>Kepakaran</label>
    <input type="text" name="kepakaran" value="<?= htmlspecialchars($user['kepakaran']) ?>">
</div>

<div class="profile-item">
    <label>No Telefon</label>
    <input type="text" name="no_telefon" value="<?= htmlspecialchars($user['no_telefon']) ?>">
</div>

<div class="profile-item">
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
</div>

<div class="profile-item">
    <label>Password Baru (optional)</label>
    <input type="password" name="password_baru" placeholder="Isi jika mahu tukar password">
</div>

<button type="submit" name="update_profile" class="btn-save">
    Kemaskini Profil
</button>

</form>

</div>

</div>
</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>