<?php
require_once 'config.php';
require_once 'include/helpers.php';

$error = "";

if (isset($_GET['tamat'])) {
    $error = "Sesi anda telah tamat. Sila log masuk semula.";
}

if (isset($_GET['nyahaktif'])) {
    $error = "Akaun anda tidak aktif. Sila hubungi pentadbir.";
}

if (isset($_POST['login_staff'])) {

    $email = strtolower(trim($_POST['email']));
    $password = (string)$_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    $laluan = [
        'pentadbir' => 'admin/dashboard.php',
        'doktor' => 'doktor/dashboard.php',
        'kakitangan' => 'kakitangan/dashboard.php',
    ];

    $peta = jadual_peranan($role);

    $kunci_sumber = 'login:' . hash_kunci($email . '|' . hash_ip());
    $kunci_ip = 'login_ip:' . hash_kunci(hash_ip());
    $kunci_akaun = 'login_akaun:' . hash_kunci($email);

    if (terlalu_banyak_cubaan($conn, $kunci_sumber, 5, 900)
        || terlalu_banyak_cubaan($conn, $kunci_ip, 20, 60)
        || terlalu_banyak_cubaan($conn, $kunci_akaun, 25, 900)) {
        $error = "Terlalu banyak cubaan log masuk. Sila cuba semula dalam 15 minit.";
    } elseif (!$peta || $role === 'pesakit' || $email === '' || $password === '' || strlen($password) > 128) {
        $error = "Email atau kata laluan tidak betul";
    } else {

        list($jadual, $medan_id, $medan_nama) = $peta;

        $sql = "SELECT `$medan_id` AS id, `$medan_nama` AS nama, kata_laluan, mesti_tukar_kata_laluan";
        $sql .= $role === 'pentadbir' ? ", 'Aktif' AS status_aktif" : ", status_aktif";
        $sql .= " FROM `$jadual` WHERE email = ? LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['kata_laluan']) && $user['status_aktif'] === 'Aktif') {

            kosongkan_cubaan($conn, $kunci_sumber);
            mulakan_sesi_pengguna((int)$user['id'], $role, $user['nama'], !empty($user['mesti_tukar_kata_laluan']));
            audit($conn, 'log_masuk_berjaya', (int)$user['id'], $role);

            if (!empty($user['mesti_tukar_kata_laluan'])) {
                header("Location: change_password.php?wajib=1");
                exit();
            }

            header("Location: " . $laluan[$role]);
            exit();
        }

        rekod_cubaan($conn, $kunci_sumber);
        rekod_cubaan($conn, $kunci_ip);
        rekod_cubaan($conn, $kunci_akaun);
        audit($conn, 'log_masuk_gagal', null, $role);
        $error = "Email atau kata laluan tidak betul";
    }
}

$peranan_dipilih = isset($_POST['role']) ? $_POST['role'] : '';
$email_diisi = isset($_POST['email']) ? e($_POST['email']) : '';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk Staf - Klinik Pergigian Dr Arifin</title>
    <link rel="stylesheet" href="assets/css/style.css?v=8">
    <link rel="stylesheet" href="assets/css/theme.css?v=8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">

        <div class="auth-logo"><img src="assets/image/logo.jpg" alt="Logo Klinik"></div>
        <h1>Sistem Klinik Dr. Arifin</h1>
        <p class="subtitle">Log Masuk Kakitangan / Doktor / Pentadbir</p>

        <?php if ($error !== "") { ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php } ?>

        <form method="POST"><?= csrf_field() ?>

            <div class="form-group">
                <label for="role">Peranan</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Pilih Peranan</option>
                    <option value="pentadbir" <?= $peranan_dipilih === 'pentadbir' ? 'selected' : '' ?>>Pentadbir</option>
                    <option value="kakitangan" <?= $peranan_dipilih === 'kakitangan' ? 'selected' : '' ?>>Kakitangan</option>
                    <option value="doktor" <?= $peranan_dipilih === 'doktor' ? 'selected' : '' ?>>Doktor</option>
                </select>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= $email_diisi ?>" placeholder="nama@klinik.my" required>
            </div>

            <div class="form-group">
                <label for="password">Kata Laluan</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Kata laluan anda" required>
            </div>

            <button type="submit" name="login_staff" class="btn-login">Log Masuk</button>
        </form>

        <div class="auth-links">
            <a href="lupa_password.php" class="btn btn-outline"><i class="ti ti-lock-question"></i> Lupa Kata Laluan</a>
        </div>

        <div class="auth-footer">
            <a href="index.php" class="btn btn-back" style="width:100%"><i class="ti ti-home"></i> Kembali ke Halaman Utama</a>
        </div>

    </div>
</div>
<script src="assets/js/ui.js?v=8" defer></script>
</body>
</html>
