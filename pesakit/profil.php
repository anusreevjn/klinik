<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'pesakit', '../login.php');

// SECURITY CHECK

$id = $_SESSION['user_id'];
$message = "";

/* UPDATE PROFILE*/
if (isset($_POST['update_profile'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $alamat = trim($_POST['alamat']);
    $jantina = trim($_POST['jantina']);
    $tarikh_lahir = trim($_POST['tarikh_lahir']);
    $password_baru = $_POST['password_baru'] ?? '';

    /* VALIDATION */
    if (empty($nama) || empty($email)) {
        $message = "Sila lengkapkan maklumat wajib.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak sah.";
    }
    elseif (strlen($nama) > 100) {
        $message = "Nama terlalu panjang.";
    }
    elseif (!empty($telefon) && strlen($telefon) > 15) {
        $message = "No telefon terlalu panjang.";
    }
    elseif (!empty($password_baru) && strlen($password_baru) < 6) {
        $message = "Password minimum 6 aksara.";
    }
    else {

        /* CHECK EMAIL DUPLICATE*/
        $check_email = $conn->prepare("
            SELECT id_pesakit 
            FROM pesakit 
            WHERE email = ? AND id_pesakit != ?
        ");

        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        $res = $check_email->get_result();

        if ($res->num_rows > 0) {
            $message = "Email sudah digunakan.";
        } else {

            /*  PASSWORD LOGIC*/
            if (!empty($password_baru)) {

                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

            } else {

                $getPass = $conn->prepare("SELECT kata_laluan FROM pesakit WHERE id_pesakit = ?");
                $getPass->bind_param("i", $id);
                $getPass->execute();
                $hashed_password = $getPass->get_result()->fetch_assoc()['kata_laluan'];
            }

            /*  UPDATE DATABASE*/
            $update_sql = "UPDATE pesakit SET
                nama_pesakit = ?,
                email = ?,
                no_telefon = ?,
                alamat = ?,
                jantina = ?,
                tarikh_lahir = ?,
                kata_laluan = ?
                WHERE id_pesakit = ?";

            $stmt_update = $conn->prepare($update_sql);

            $stmt_update->bind_param(
                "sssssssi",
                $nama,
                $email,
                $telefon,
                $alamat,
                $jantina,
                $tarikh_lahir,
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

/* FETCH DATA*/
$sql = "SELECT * FROM pesakit WHERE id_pesakit = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<?php mula_halaman($conn, 'Profil Saya', 'pesakit', 'profil.php'); ?>

<div class="card" style="max-width:720px;">
    <h3 class="card-title">Maklumat Profil</h3>

    <?php if (!empty($message)) { ?>
        <div class="alert <?= strpos($message, 'berjaya') !== false ? 'success' : 'error' ?>"><?= e($message) ?></div>
    <?php } ?>

    <form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" class="form-control" value="<?= e($user['nama_pesakit']) ?>" required>
        </div>

        <div class="form-group">
            <label for="no_ic">No. Kad Pengenalan</label>
            <input type="text" id="no_ic" class="form-control" value="<?= e($user['no_ic']) ?>" readonly>
        </div>

        <div class="date-group" style="display:flex;gap:14px;flex-wrap:wrap;">
            <div class="form-group" style="flex:1;min-width:180px;">
                <label for="jantina">Jantina</label>
                <select id="jantina" name="jantina" class="form-control">
                    <option value="">-- Pilih --</option>
                    <option value="Lelaki" <?= $user['jantina'] === 'Lelaki' ? 'selected' : '' ?>>Lelaki</option>
                    <option value="Perempuan" <?= $user['jantina'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;min-width:180px;">
                <label for="tarikh_lahir">Tarikh Lahir</label>
                <input type="date" id="tarikh_lahir" name="tarikh_lahir" class="form-control" value="<?= e($user['tarikh_lahir']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Alamat Emel</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="telefon">No. Telefon</label>
            <input type="text" id="telefon" name="telefon" class="form-control" value="<?= e($user['no_telefon']) ?>">
        </div>

        <div class="form-group">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" class="form-control"><?= e($user['alamat']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="password_baru">Kata Laluan Baharu (Pilihan)</label>
            <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Isi jika mahu tukar kata laluan">
        </div>

        <button type="submit" name="update_profile" class="btn-login">Kemaskini Profil</button>
    </form>
</div>

<?php tamat_halaman(); ?>