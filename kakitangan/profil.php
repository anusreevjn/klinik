<?php
require_once '../config.php';
require_once '../include/helpers.php';

guard($conn, 'kakitangan', '../staff_login.php');

// SECURITY CHECK

$id = $_SESSION['user_id'];
$message = "";

/* =========================
   UPDATE PROFILE
========================= */
if (isset($_POST['update_profile'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $jawatan = trim($_POST['jawatan']);
    $password_baru = $_POST['password_baru'];

    /* =========================
       VALIDATION
    ========================= */
    if (empty($nama) || empty($email)) {
        $message = "Sila lengkapkan maklumat wajib.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak sah.";
    }
    elseif (strlen($nama) > 100) {
        $message = "Nama terlalu panjang.";
    }
    elseif (!empty($telefon) && !preg_match("/^[0-9]{10,15}$/", $telefon)) {
        $message = "No telefon tidak sah.";
    }
    elseif (!empty($password_baru) && strlen($password_baru) < 6) {
        $message = "Password minimum 6 aksara.";
    }
    else {

        /* =========================
           CHECK EMAIL DUPLICATE
        ========================= */
        $check_email = $conn->prepare("
            SELECT id_kakitangan 
            FROM kakitangan 
            WHERE email = ? AND id_kakitangan != ?
        ");

        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        $res = $check_email->get_result();

        if ($res->num_rows > 0) {

            $message = "Email sudah digunakan.";

        } else {

            /* =========================
               PASSWORD LOGIC
            ========================= */
            if (!empty($password_baru)) {

                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

            } else {

                $getPass = $conn->prepare("SELECT kata_laluan FROM kakitangan WHERE id_kakitangan = ?");
                $getPass->bind_param("i", $id);
                $getPass->execute();
                $hashed_password = $getPass->get_result()->fetch_assoc()['kata_laluan'];
            }

            /* =========================
               UPDATE DATA
            ========================= */
            $update_sql = "UPDATE kakitangan SET
                nama_kakitangan = ?,
                email = ?,
                no_telefon = ?,
                jawatan = ?,
                kata_laluan = ?
                WHERE id_kakitangan = ?";

            $stmt_update = $conn->prepare($update_sql);

            $stmt_update->bind_param(
                "sssssi",
                $nama,
                $email,
                $telefon,
                $jawatan,
                $hashed_password,
                $id
            );

            if ($stmt_update->execute()) {
                $message = "Profil berjaya dikemaskini!";
            } else {
                $message = "Ralat semasa kemaskini profil.";
            }
        }
    }
}

/* =========================
   FETCH DATA
========================= */
$sql = "SELECT * FROM kakitangan WHERE id_kakitangan = ?";
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
<title>Profil Kakitangan</title>
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

.profile-item{margin-bottom:15px;}

.profile-item label{
    font-weight:bold;
    display:block;
    margin-bottom:5px;
}

.profile-item input{
    width:100%;
    padding:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
}

.btn-save{
    background:#0f766e;
    color:white;
    border:none;
    padding:12px 18px;
    border-radius:8px;
    cursor:pointer;
}

.btn-save:hover{background:#115e59;}

.success-message{
    background:#dcfce7;
    color:#166534;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
}

.error-message{
    background:#fee2e2;
    color:#991b1b;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
}

@media(max-width:768px){
    .main{margin-left:0;}
}
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
Kakitangan:<br>
<b><?= htmlspecialchars($user['nama_kakitangan']) ?></b>
</p>

<a href="dashboard.php">🏠 Dashboard</a>
<a href="kehadiran.php">📋 Sejarah</a>
<a href="profil.php">👤 Profil</a>
<a href="../logout.php">🚪 Log Keluar</a>

</div>

<!-- MAIN -->
<div class="main">

<h2>Profil Kakitangan</h2>

<div class="profile-card">

<?php if (!empty($message)) { ?>
    <div class="<?= strpos($message,'berjaya')!==false ? 'success-message' : 'error-message' ?>">
        <?= $message ?>
    </div>
<?php } ?>

<form method="POST"><?= csrf_field() ?>

<div class="profile-item">
    <label>Nama</label>
    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama_kakitangan']) ?>" required>
</div>

<div class="profile-item">
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
</div>

<div class="profile-item">
    <label>No Telefon</label>
    <input type="text" name="telefon" value="<?= htmlspecialchars($user['no_telefon']) ?>">
</div>

<div class="profile-item">
    <label>Jawatan</label>
    <input type="text" name="jawatan" value="<?= htmlspecialchars($user['jawatan']) ?>">
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