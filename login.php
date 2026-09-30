<?php
require_once 'config.php';
require_once 'include/helpers.php';

$error = "";

if (isset($_GET['tamat'])) {
    $error = "Sesi anda telah tamat. Sila log masuk semula.";
}

if (isset($_GET['nyahaktif'])) {
    $error = "Akaun anda tidak aktif. Sila hubungi klinik.";
}

if (isset($_POST['login'])) {

    $email = strtolower(trim($_POST['email']));
    $password = (string)$_POST['password'];

    $kunci_sumber = 'login:' . hash_kunci($email . '|' . hash_ip());
    $kunci_ip = 'login_ip:' . hash_kunci(hash_ip());
    $kunci_akaun = 'login_akaun:' . hash_kunci($email);

    if (terlalu_banyak_cubaan($conn, $kunci_sumber, 5, 900)
        || terlalu_banyak_cubaan($conn, $kunci_ip, 20, 60)
        || terlalu_banyak_cubaan($conn, $kunci_akaun, 25, 900)) {
        $error = "Terlalu banyak cubaan log masuk. Sila cuba semula dalam 15 minit.";
    } elseif ($email === '' || $password === '' || strlen($password) > 128) {
        $error = "Email atau kata laluan tidak betul";
    } else {

        $stmt = mysqli_prepare($conn, "SELECT id_pesakit, nama_pesakit, kata_laluan, mesti_tukar_kata_laluan FROM pesakit WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($row && password_verify($password, $row['kata_laluan'])) {

            kosongkan_cubaan($conn, $kunci_sumber);
            mulakan_sesi_pengguna((int)$row['id_pesakit'], 'pesakit', $row['nama_pesakit'], !empty($row['mesti_tukar_kata_laluan']));
            audit($conn, 'log_masuk_berjaya', (int)$row['id_pesakit'], 'pesakit');

            if (!empty($row['mesti_tukar_kata_laluan'])) {
                header("Location: change_password.php?wajib=1");
                exit();
            }

            header("Location: pesakit/dashboard.php");
            exit();
        }

        rekod_cubaan($conn, $kunci_sumber);
        rekod_cubaan($conn, $kunci_ip);
        rekod_cubaan($conn, $kunci_akaun);
        audit($conn, 'log_masuk_gagal', null, 'pesakit');
        $error = "Email atau kata laluan tidak betul";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Log Masuk Pesakit</title>

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="auth-body">

<div class="auth-container">

<div class="auth-card">

<h1>Klinik Pergigian Dr. Arifin</h1>
<p class="subtitle">Log Masuk Pesakit</p>

<?php if($error != ""){ ?>
<div class="alert error"><?= e($error) ?></div>
<?php } ?>

<form method="POST"><?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="nama@gmail.com" required>
        </div>

        <div class="form-group">
            <label for="password">Kata Laluan</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Kata laluan anda" required>
        </div>

<button type="submit" name="login" class="btn-login">
Log Masuk
</button>

</form>

<div class="auth-footer">
    <p><a href="lupa_password.php">Lupa Kata Laluan?</a></p>
    <p style="margin-top:12px;">Belum ada akaun? <a href="register.php">Daftar Akaun Baru</a></p>
    <p style="margin-top:12px;"><a href="index.php">&larr; Kembali ke Halaman Utama</a></p>
</div>

</div>

</div>

<script src="assets/js/ui.js" defer></script>
</body>
</html>