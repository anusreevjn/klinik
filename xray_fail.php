<?php
require_once 'config.php';
require_once 'include/helpers.php';
require_once 'include/penjaga_lib.php';

$id_pengguna = guard($conn, ['pesakit', 'doktor', 'kakitangan', 'pentadbir'], 'login.php');
$peranan = $_SESSION['role'];

$id_xray = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT id_xray, id_pesakit, fail_xray FROM xray WHERE id_xray = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_xray);
mysqli_stmt_execute($stmt);
$rekod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$rekod || empty($rekod['fail_xray'])) {
    http_response_code(404);
    exit('Fail X-ray tidak dijumpai.');
}

if ($peranan === 'pesakit' && !profil_dibenarkan_untuk_akaun($conn, $id_pengguna, (int)$rekod['id_pesakit'])) {
    http_response_code(404);
    exit('Fail X-ray tidak dijumpai.');
}

$nama_fail = basename($rekod['fail_xray']);
$laluan = __DIR__ . '/storage/xray/' . $nama_fail;

if (!is_file($laluan)) {
    http_response_code(404);
    exit('Fail tidak dijumpai.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$jenis = $finfo->file($laluan);

if (!in_array($jenis, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
    http_response_code(415);
    exit('Jenis fail tidak disokong.');
}

audit($conn, 'xray_dilihat', $id_xray, $peranan);

header('Content-Type: ' . $jenis);
header('Content-Length: ' . filesize($laluan));
header('Content-Disposition: inline; filename="xray-' . $id_xray . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($laluan);
