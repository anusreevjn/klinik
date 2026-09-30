<?php
require_once 'config.php';
require_once 'include/helpers.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

$message = "";
$wajib = !empty($_SESSION['mesti_tukar_kata_laluan']);

if (isset($_POST['change'])) {

    $id = (int)$_SESSION['user_id'];
    $peranan = $_SESSION['role'];
    $peta = jadual_peranan($peranan);

    $old = (string)$_POST['old_password'];
    $new = (string)$_POST['new_password'];

    if (!$peta) {
        header('Location: login.php');
        exit();
    }

    list($table, $id_col) = $peta;

    $stmt = mysqli_prepare($conn, "SELECT kata_laluan FROM `$table` WHERE `$id_col` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (strlen($new) < 8 || strlen($new) > 128) {
        $message = "Kata laluan baharu perlu antara 8 hingga 128 aksara.";
    } elseif ($new === $old) {
        $message = "Kata laluan baharu perlu berbeza daripada kata laluan lama.";
    } elseif ($user && password_verify($old, $user['kata_laluan'])) {

        $hash = password_hash($new, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "UPDATE `$table` SET kata_laluan = ?, mesti_tukar_kata_laluan = 0 WHERE `$id_col` = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        audit($conn, 'kata_laluan_ditukar', $id, $peranan);
        session_regenerate_id(true);
        $_SESSION['mesti_tukar_kata_laluan'] = false;
        $wajib = false;

        $message = "Kata laluan berjaya ditukar.";
    } else {
        $message = "Kata laluan lama tidak betul.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tukar Password</title>
<link rel="stylesheet" href="assets/css/style.css?v=8">
<link rel="stylesheet" href="assets/css/theme.css?v=8">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body class="auth-body">

<div class="auth-container">

<div class="auth-card">

<h1>Tukar Kata Laluan</h1>
<p class="subtitle"><?= $wajib ? 'Anda perlu menukar kata laluan sebelum meneruskan' : 'Kemas kini kata laluan akaun anda' ?></p>

<?php if ($message !== "") { ?>
<div class="alert <?= strpos($message, 'berjaya') !== false ? 'success' : 'error' ?>"><?= e($message) ?></div>
<?php } ?>

<form method="POST"><?= csrf_field() ?>

<div class="form-group">
<label for="old_password">Kata Laluan Lama</label>
<input type="password" id="old_password" name="old_password" class="form-control" required>
</div>

<div class="form-group">
<label for="new_password">Kata Laluan Baharu</label>
<input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 8 aksara" required>
</div>

<button type="submit" name="change" class="btn-login">
Tukar Kata Laluan
</button>

</form>

<?php if (!$wajib) { ?>
<div class="auth-footer">
    <a href="index.php" class="btn btn-back" style="width:100%"><i class="ti ti-home"></i> Kembali</a>
</div>
<?php } ?>

</div>
</div>

<script src="assets/js/ui.js?v=8" defer></script>
</body>
</html>