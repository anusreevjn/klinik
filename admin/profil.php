<?php
require_once '../config.php';
require_once '../include/helpers.php';

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

<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Pentadbir</title>

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
    max-width:700px;
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

.btn-back{
    display:inline-block;
    margin-bottom:15px;
    padding:8px 12px;
    background:#374151;
    color:white;
    border-radius:8px;
    text-decoration:none;
}

.btn-back:hover{
    background:#111827;
}

.success{
    background:#dcfce7;
    padding:10px;
    border-radius:8px;
    color:#166534;
    margin-bottom:10px;
}

.error{
    background:#fee2e2;
    padding:10px;
    border-radius:8px;
    color:#991b1b;
    margin-bottom:10px;
}
</style>

</head>

<body>

<div class="dashboard">

<!-- SIDEBAR -->
<div class="sidebar">

<h2>Klinik Dr Arifin</h2>

<p>
Pentadbir:<br>
<b><?= htmlspecialchars($user['nama_pentadbir']) ?></b>
</p>

<a href="dashboard.php"> Dashboard</a>
        <a href="tambah_doktor.php"> Tambah Doktor</a>
        <a href="tambah_kakitangan.php"> Tambah Kakitangan</a>
<a href="profil.php" class="active"> Profil</a>
<a href="inventori.php"> Inventori</a>
        <a href="laporan.php"> Laporan</a>
<a href="../logout.php"> Log Keluar</a>

</div>

<!-- MAIN -->
<div class="main">

<!-- BACK BUTTON -->
<a href="dashboard.php" class="btn-back">⬅ Kembali ke Dashboard</a>

<h2>Profil Pentadbir</h2>

<div class="profile-card">

<?php if ($message) { ?>
    <div class="<?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>">
        <?= $message ?>
    </div>
<?php } ?>

<form method="POST"><?= csrf_field() ?>

    <div class="profile-item">
        <label>Nama</label>
        <input type="text" name="nama_pentadbir" value="<?= htmlspecialchars($user['nama_pentadbir']) ?>">
    </div>

    <div class="profile-item">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
    </div>

    <div class="profile-item">
        <label>No Telefon</label>
        <input type="text" name="no_telefon" value="<?= htmlspecialchars($user['no_telefon']) ?>">
    </div>

    <div class="profile-item">
        <label>Jawatan</label>
        <input type="text" name="jawatan" value="<?= htmlspecialchars($user['jawatan']) ?>">
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