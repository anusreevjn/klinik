<?php
require_once 'config.php';
require_once 'include/helpers.php';

$error = "";
$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');

$nilai = [
    'nama' => '',
    'no_ic' => '',
    'jantina' => '',
    'tarikh_lahir' => '',
    'no_telefon' => '',
    'email' => '',
    'alamat' => '',
];

if (isset($_POST['daftar'])) {

    $nilai['nama'] = trim($_POST['nama'] ?? '');
    $nilai['no_ic'] = preg_replace('/\D/', '', $_POST['no_ic'] ?? '');
    $nilai['jantina'] = $_POST['jantina'] ?? '';
    $nilai['tarikh_lahir'] = trim($_POST['tarikh_lahir'] ?? '');
    $nilai['no_telefon'] = trim($_POST['no_telefon'] ?? '');
    $nilai['email'] = strtolower(trim($_POST['email'] ?? ''));
    $nilai['alamat'] = trim($_POST['alamat'] ?? '');
    $kata_laluan = (string)($_POST['kata_laluan'] ?? '');
    $sah_kata_laluan = (string)($_POST['sah_kata_laluan'] ?? '');

    $kunci_ip = 'daftar_ip:' . hash_kunci(hash_ip());

    if (terlalu_banyak_cubaan($conn, $kunci_ip, 10, 3600)) {
        $error = "Terlalu banyak percubaan pendaftaran. Sila cuba semula kemudian.";
    } elseif ($nilai['nama'] === '' || $nilai['no_ic'] === '' || $nilai['email'] === '' || $kata_laluan === '') {
        $error = "Sila lengkapkan semua ruangan bertanda wajib.";
    } elseif (strlen($nilai['no_ic']) !== 12) {
        $error = "No. Kad Pengenalan mesti mengandungi 12 digit.";
    } elseif (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak sah.";
    } elseif ($nilai['jantina'] !== '' && !in_array($nilai['jantina'], ['Lelaki', 'Perempuan'], true)) {
        $error = "Sila pilih jantina yang sah.";
    } elseif (strlen($kata_laluan) < 8 || strlen($kata_laluan) > 128) {
        $error = "Kata laluan mesti antara 8 hingga 128 aksara.";
    } elseif ($kata_laluan !== $sah_kata_laluan) {
        $error = "Kata laluan dan pengesahan kata laluan tidak sepadan.";
    } else {

        rekod_cubaan($conn, $kunci_ip);

        $stmt = mysqli_prepare($conn, "SELECT id_pesakit FROM pesakit WHERE email = ? OR no_ic = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $nilai['email'], $nilai['no_ic']);
        mysqli_stmt_execute($stmt);
        $wujud = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($wujud) {
            $error = "Email atau No. Kad Pengenalan ini telah didaftarkan. Sila log masuk atau guna Lupa Kata Laluan.";
        } else {

            $last4 = substr($nilai['no_ic'], -4);
            $tarikh_lahir = $nilai['tarikh_lahir'] !== '' ? $nilai['tarikh_lahir'] : null;
            $jantina = $nilai['jantina'] !== '' ? $nilai['jantina'] : null;
            $hash = password_hash($kata_laluan, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO pesakit
                (jenis_profil, akses_penjaga, nama_pesakit, no_ic, no_pendaftaran_klinik, id_cariana_ic, jantina, tarikh_lahir, alamat, no_telefon, email, kata_laluan, tarikh_daftar)
                VALUES ('Akaun', 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssss",
                $nilai['nama'],
                $nilai['no_ic'],
                $last4,
                $last4,
                $jantina,
                $tarikh_lahir,
                $nilai['alamat'],
                $nilai['no_telefon'],
                $nilai['email'],
                $hash
            );

            if (mysqli_stmt_execute($stmt)) {
                $id_baru = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                mulakan_sesi_pengguna($id_baru, 'pesakit', $nilai['nama'], false);
                audit($conn, 'daftar_pesakit', $id_baru, 'pesakit');

                header("Location: pesakit/dashboard.php");
                exit();
            }

            mysqli_stmt_close($stmt);
            $error = "Pendaftaran gagal. Sila cuba lagi atau hubungi klinik.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akaun Pesakit - <?= e($nama_klinik) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=8">
    <link rel="stylesheet" href="assets/css/theme.css?v=8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">
<div class="auth-container" style="max-width:560px;">
    <div class="auth-card">

        <h1>Daftar Akaun Pesakit</h1>
        <p class="subtitle">Cipta akaun untuk menempah temu janji dan melihat rekod rawatan anda</p>

        <?php if ($error !== "") { ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php } ?>

        <form method="POST"><?= csrf_field() ?>

            <div class="form-group">
                <label for="nama">Nama Penuh <span style="color:var(--bad)">*</span></label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?= e($nilai['nama']) ?>" required>
            </div>

            <div class="form-group">
                <label for="no_ic">No. Kad Pengenalan <span style="color:var(--bad)">*</span></label>
                <input type="text" id="no_ic" name="no_ic" class="form-control" value="<?= e($nilai['no_ic']) ?>" inputmode="numeric" maxlength="14" placeholder="Contoh: 010304036061" required>
            </div>

            <div class="date-group" style="display:flex;gap:14px;flex-wrap:wrap;">
                <div class="form-group" style="flex:1;min-width:180px;">
                    <label for="jantina">Jantina</label>
                    <select id="jantina" name="jantina" class="form-control">
                        <option value="">Pilih</option>
                        <option value="Lelaki" <?= $nilai['jantina'] === 'Lelaki' ? 'selected' : '' ?>>Lelaki</option>
                        <option value="Perempuan" <?= $nilai['jantina'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:180px;">
                    <label for="tarikh_lahir">Tarikh Lahir</label>
                    <input type="date" id="tarikh_lahir" name="tarikh_lahir" class="form-control" value="<?= e($nilai['tarikh_lahir']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="no_telefon">No. Telefon</label>
                <input type="text" id="no_telefon" name="no_telefon" class="form-control" value="<?= e($nilai['no_telefon']) ?>" placeholder="Contoh: 0134567890">
            </div>

            <div class="form-group">
                <label for="email">Email <span style="color:var(--bad)">*</span></label>
                <input type="email" id="email" name="email" class="form-control" value="<?= e($nilai['email']) ?>" placeholder="nama@gmail.com" required>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" class="form-control" rows="2"><?= e($nilai['alamat']) ?></textarea>
            </div>

            <div class="date-group" style="display:flex;gap:14px;flex-wrap:wrap;">
                <div class="form-group" style="flex:1;min-width:180px;">
                    <label for="kata_laluan">Kata Laluan <span style="color:var(--bad)">*</span></label>
                    <input type="password" id="kata_laluan" name="kata_laluan" class="form-control" placeholder="Minimum 8 aksara" required>
                </div>
                <div class="form-group" style="flex:1;min-width:180px;">
                    <label for="sah_kata_laluan">Sahkan Kata Laluan <span style="color:var(--bad)">*</span></label>
                    <input type="password" id="sah_kata_laluan" name="sah_kata_laluan" class="form-control" placeholder="Taip semula kata laluan" required>
                </div>
            </div>

            <button type="submit" name="daftar" class="btn-login">Daftar Akaun</button>
        </form>

        <div class="auth-footer">
            <p>Sudah ada akaun? <a href="login.php">Log Masuk</a></p>
            <a href="index.php" class="btn btn-back" style="width:100%;margin-top:4px"><i class="ti ti-home"></i> Kembali ke Halaman Utama</a>
        </div>

    </div>
</div>
<script src="assets/js/ui.js?v=8" defer></script>
</body>
</html>
