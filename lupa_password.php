<?php
require_once 'config.php';
require_once 'include/helpers.php';
require_once 'include/mailer.php';

$mesej = '';
$jenis_mesej = 'success';

$jadual_peranan = [
    'pesakit' => ['pesakit', 'nama_pesakit'],
    'doktor' => ['doktor', 'nama_doktor'],
    'kakitangan' => ['kakitangan', 'nama_kakitangan'],
    'pentadbir' => ['pentadbir', 'nama_pentadbir'],
];

if (isset($_POST['hantar_pautan'])) {
    $email = strtolower(trim($_POST['email']));
    $peranan = isset($jadual_peranan[$_POST['peranan']]) ? $_POST['peranan'] : 'pesakit';
    list($jadual, $medan_nama) = $jadual_peranan[$peranan];

    $kunci_akaun = 'reset_akaun:' . hash_kunci($peranan . '|' . $email);
    $kunci_ip = 'reset_ip:' . hash_kunci(hash_ip());

    if (terlalu_banyak_cubaan($conn, $kunci_akaun, 3, 3600) || terlalu_banyak_cubaan($conn, $kunci_ip, 10, 3600)) {
        tolak_permintaan_berlebihan('Terlalu banyak permintaan tetapan semula. Sila cuba semula kemudian.');
    }

    rekod_cubaan($conn, $kunci_akaun);
    rekod_cubaan($conn, $kunci_ip);

    $stmt = mysqli_prepare($conn, "SELECT $medan_nama AS nama FROM $jadual WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $pengguna = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$pengguna) {
        $mesej = 'Jika email ini berdaftar, pautan tetapan semula telah dihantar.';
    } else {
        $token = bin2hex(random_bytes(24));
        $token_hash = hash('sha256', $token);
        $tamat = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = mysqli_prepare($conn, "INSERT INTO token_reset (peranan, email, token, token_hash, tarikh_tamat, status_guna) VALUES (?, ?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "sssss", $peranan, $email, $token_hash, $token_hash, $tamat);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        audit($conn, 'pautan_reset_dihantar', null, $peranan);

        $asas = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $pautan = $asas . '/reset_password.php?token=' . $token;
        $nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');

        $kandungan = '<p>Salam ' . selamat($pengguna['nama']) . ',</p>'
            . '<p>Anda telah meminta tetapan semula kata laluan untuk sistem ' . selamat($nama_klinik) . '.</p>'
            . '<p><a href="' . e($pautan) . '">Klik di sini untuk tetapkan kata laluan baharu</a></p>'
            . '<p>Pautan ini sah selama 30 minit dan hanya boleh digunakan sekali. Abaikan email ini jika bukan anda yang meminta.</p>';

        $ralat = null;
        if (!hantar_email_smtp($conn, $email, 'Tetapan Semula Kata Laluan', $kandungan, $ralat)) {
            error_log('Hantar email reset gagal: ' . $ralat);
        }

        $mesej = 'Jika email ini berdaftar, pautan tetapan semula telah dihantar.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Laluan</title>
    <link rel="stylesheet" href="assets/css/style.css?v=11">
<link rel="stylesheet" href="assets/css/theme.css?v=11">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <div class="auth-card">
        <h1>Lupa Kata Laluan</h1>
        <p class="subtitle">Masukkan email berdaftar anda</p>

        <?php if ($mesej !== '') { ?>
            <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
        <?php } ?>

        <form method="post"><?= csrf_field() ?>
            <div class="form-group">
                <label>Peranan</label>
                <select name="peranan" class="form-control">
                    <option value="pesakit">Pesakit</option>
                    <option value="doktor">Doktor</option>
                    <option value="kakitangan">Kakitangan Klinik</option>
                    <option value="pentadbir">Pentadbir</option>
                </select>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn-login" type="submit" name="hantar_pautan">Hantar Pautan</button>
        </form>

        <div class="auth-footer"><a href="login.php" class="btn btn-back" style="width:100%"><i class="ti ti-arrow-back-up"></i> Kembali ke Log Masuk</a></div>
    </div>
</div>
<script src="assets/js/ui.js?v=11" defer></script>
</body>
</html>
