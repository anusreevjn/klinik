<?php
require_once 'config.php';
require_once 'include/helpers.php';

$mesej = '';
$jenis_mesej = 'error';
$token = isset($_GET['token']) ? (string)$_GET['token'] : (string)($_POST['token'] ?? '');
$token_hash = $token !== '' ? hash('sha256', $token) : '';
$rekod = null;
$borang_boleh_papar = false;

function cari_token_sah($conn, string $token_hash)
{
    if ($token_hash === '') {
        return null;
    }

    $stmt = mysqli_prepare($conn, "SELECT id_token, peranan, email FROM token_reset WHERE token_hash = ? AND status_guna = 0 AND tarikh_tamat > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token_hash);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris ?: null;
}

$rekod = cari_token_sah($conn, $token_hash);

if (!$rekod) {
    $mesej = 'Pautan tetapan semula tidak sah atau telah tamat tempoh. Sila mohon pautan baharu.';
} else {
    $borang_boleh_papar = true;

    if (isset($_POST['tetap_semula'])) {

        $baharu = (string)($_POST['kata_laluan'] ?? '');
        $sah = (string)($_POST['sah_kata_laluan'] ?? '');
        $peta = jadual_peranan($rekod['peranan']);

        if (!$peta) {
            $mesej = 'Peranan tidak sah.';
            $borang_boleh_papar = false;
        } elseif (strlen($baharu) < 8 || strlen($baharu) > 128) {
            $mesej = 'Kata laluan mesti antara 8 hingga 128 aksara.';
        } elseif ($baharu !== $sah) {
            $mesej = 'Kata laluan dan pengesahan tidak sepadan.';
        } else {

            list($jadual) = $peta;
            $hash = password_hash($baharu, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE `$jadual` SET kata_laluan = ?, mesti_tukar_kata_laluan = 0 WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $hash, $rekod['email']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE token_reset SET status_guna = 1 WHERE id_token = ?");
            mysqli_stmt_bind_param($stmt, "i", $rekod['id_token']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            audit($conn, 'kata_laluan_ditetap_semula', null, $rekod['peranan']);

            $mesej = 'Kata laluan berjaya ditetapkan semula. Sila log masuk dengan kata laluan baharu.';
            $jenis_mesej = 'success';
            $borang_boleh_papar = false;
        }
    }
}

$laluan_login = ($rekod && $rekod['peranan'] === 'pesakit') ? 'login.php' : 'staff_login.php';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetapan Semula Kata Laluan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">

        <h1>Tetapan Semula Kata Laluan</h1>
        <p class="subtitle">Masukkan kata laluan baharu anda</p>

        <?php if ($mesej !== '') { ?>
            <div class="alert <?= e($jenis_mesej) ?>"><?= e($mesej) ?></div>
        <?php } ?>

        <?php if ($borang_boleh_papar) { ?>
            <form method="POST"><?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group">
                    <label for="kata_laluan">Kata Laluan Baharu</label>
                    <input type="password" id="kata_laluan" name="kata_laluan" class="form-control" placeholder="Minimum 8 aksara" required>
                </div>

                <div class="form-group">
                    <label for="sah_kata_laluan">Sahkan Kata Laluan</label>
                    <input type="password" id="sah_kata_laluan" name="sah_kata_laluan" class="form-control" placeholder="Taip semula kata laluan" required>
                </div>

                <button type="submit" name="tetap_semula" class="btn-login">Simpan Kata Laluan</button>
            </form>
        <?php } ?>

        <div class="auth-footer">
            <a href="<?= e($laluan_login) ?>">Kembali ke Log Masuk</a>
        </div>

    </div>
</div>
<script src="assets/js/ui.js" defer></script>
</body>
</html>
