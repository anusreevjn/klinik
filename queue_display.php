<?php
require_once 'config.php';
require_once 'include/helpers.php';

$mesej = '';
$jenis_mesej = 'error';
$sah = false;
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

$jadual_peranan = [
    'pesakit' => 'pesakit',
    'doktor' => 'doktor',
    'kakitangan' => 'kakitangan',
    'pentadbir' => 'pentadbir',
];

$rekod = null;
if ($token !== '') {
    $token_hash = hash('sha256', $token);
    $stmt = mysqli_prepare($conn, "SELECT * FROM token_reset WHERE token_hash = ? AND status_guna = 0 AND tarikh_tamat > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token_hash);
    mysqli_stmt_execute($stmt);
    $rekod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

if ($rekod) {
    $sah = true;
} else {
    $mesej = 'Pautan tidak sah atau sudah tamat tempoh. Sila minta pautan baharu.';
}

if ($sah && isset($_POST['tetapkan'])) {
    $baru = $_POST['kata_laluan'];
    $ulang = $_POST['kata_laluan_ulang'];

    if (strlen($baru) < 8 || strlen($baru) > 128) {
        $mesej = 'Kata laluan mesti antara 8 hingga 128 aksara.';
    } elseif ($baru !== $ulang) {
        $mesej = 'Kata laluan tidak sama.';
    } else {
        $jadual = $jadual_peranan[$rekod['peranan']];
        $hash = password_hash($baru, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "UPDATE `$jadual` SET kata_laluan = ?, mesti_tukar_kata_laluan = 0 WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $hash, $rekod['email']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE token_reset SET status_guna = 1 WHERE id_token = ?");
        mysqli_stmt_bind_param($stmt, "i", $rekod['id_token']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        audit($conn, 'kata_laluan_reset_melalui_email', null, $rekod['peranan']);
        $mesej = 'Kata laluan berjaya ditetapkan semula. Sila log masuk semula.';
        $jenis_mesej = 'success';
        $sah = false;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetapkan Kata Laluan</title>
    <link rel="stylesheet" href="assets/css/style.css?v=8">
<link rel="stylesheet" href="assets/css/theme.css?v=8">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">
        <h1>Kata Laluan Baharu</h1>

        <?php if ($mesej !== '') { ?>
            <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
        <?php } ?>

        <?php if ($sah) { ?>
            <form method="post"><?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= selamat($token) ?>">
                <div class="form-group">
                    <label>Kata Laluan Baharu</label>
                    <input type="password" name="kata_laluan" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ulang Kata Laluan</label>
                    <input type="password" name="kata_laluan_ulang" class="form-control" required>
                </div>
                <button class="btn-login" type="submit" name="tetapkan">Tetapkan Kata Laluan</button>
            </form>
        <?php } else { ?>
            <div class="btn-group">
                <a class="btn" href="login.php">Log Masuk Pesakit</a>
                <a class="btn btn-outline" href="staff_login.php">Log Masuk Staf</a>
            </div>
        <?php } ?>
    </div>
</div>
<script src="assets/js/ui.js?v=8" defer></script>
</body>
</html>
