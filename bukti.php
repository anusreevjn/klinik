<?php
require_once 'config.php';
require_once 'include/helpers.php';
require_once 'include/penjaga_lib.php';

$id_pengguna = guard($conn, ['pesakit', 'kakitangan', 'pentadbir'], 'login.php');
$peranan = $_SESSION['role'];

$id_pembayaran = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT id_pesakit, bukti_pembayaran FROM pembayaran WHERE id_pembayaran = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
mysqli_stmt_execute($stmt);
$bayaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$bayaran || empty($bayaran['bukti_pembayaran'])) {
    http_response_code(404);
    exit('Bukti pembayaran tidak dijumpai.');
}

if ($peranan === 'pesakit' && !profil_dibenarkan_untuk_akaun($conn, $id_pengguna, (int)$bayaran['id_pesakit'])) {
    http_response_code(404);
    exit('Bukti pembayaran tidak dijumpai.');
}

$nama_fail = basename($bayaran['bukti_pembayaran']);
$laluan = __DIR__ . '/storage/bukti/' . $nama_fail;

if (!is_file($laluan)) {
    http_response_code(404);
    exit('Fail tidak dijumpai.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$jenis = $finfo->file($laluan);
$dibenarkan = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

if (!in_array($jenis, $dibenarkan, true)) {
    http_response_code(415);
    exit('Jenis fail tidak disokong.');
}

audit($conn, 'bukti_pembayaran_dilihat', $id_pembayaran, $peranan);

header('Content-Type: ' . $jenis);
header('Content-Length: ' . filesize($laluan));
header('Content-Disposition: inline; filename="bukti-' . $id_pembayaran . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($laluan);
