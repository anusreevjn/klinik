<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

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

<?php mula_halaman($conn, 'Profil Saya', 'kakitangan', 'profil.php'); ?>

<div class="card" style="max-width:720px;">
    <h3 class="card-title">Maklumat Kakitangan</h3>

    <?php if (!empty($message)) { ?>
        <div class="alert <?= strpos($message,'berjaya')!==false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" class="form-control" value="<?= e($user['nama_kakitangan']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="telefon">No Telefon</label>
            <input type="text" id="telefon" name="telefon" class="form-control" value="<?= e($user['no_telefon']) ?>">
        </div>

        <div class="form-group">
            <label for="jawatan">Jawatan</label>
            <input type="text" id="jawatan" name="jawatan" class="form-control" value="<?= e($user['jawatan']) ?>">
        </div>

        <div class="form-group">
            <label for="password_baru">Kata Laluan Baharu (Pilihan)</label>
            <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Isi jika mahu tukar kata laluan">
        </div>

        <button type="submit" name="update_profile" class="btn-login">Kemaskini Profil</button>
    </form>
</div>

<?php tamat_halaman(); ?>