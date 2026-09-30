<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

const TEMPOH_IDLE_PESAKIT = 3600;
const TEMPOH_IDLE_STAF = 1800;
const TEMPOH_MUTLAK = 43200;

function e(?string $nilai): string
{
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_token(): string
{
    return $_SESSION['csrf'];
}

function post_terlalu_besar(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }

    $panjang = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;

    return $panjang > 0 && empty($_POST) && empty($_FILES);
}

function had_saiz_muat_naik(): string
{
    $post = ini_get('post_max_size');
    $fail = ini_get('upload_max_filesize');

    return $fail . ' bagi satu fail dan ' . $post . ' bagi satu borang';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (post_terlalu_besar()) {
        http_response_code(413);
        exit('Fail yang dimuat naik terlalu besar. Had semasa server ialah ' . had_saiz_muat_naik() . '. Sila kecilkan fail dan cuba lagi.');
    }

    $dihantar = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!hash_equals($_SESSION['csrf'], $dihantar)) {
        http_response_code(403);
        exit('Permintaan tidak sah. Sila muat semula halaman dan cuba lagi.');
    }
}

function hash_ip(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'tiada';

    return substr(hash('sha256', 'ip|' . $ip), 0, 32);
}

function hash_kunci(string $bahagian): string
{
    return substr(hash('sha256', 'kunci|' . strtolower($bahagian)), 0, 32);
}

function terlalu_banyak_cubaan($conn, string $kunci, int $maksimum, int $tempoh_saat): bool
{
    $stmt = mysqli_prepare($conn, "DELETE FROM rate_limits WHERE dicipta_pada < (NOW() - INTERVAL ? SECOND)");
    mysqli_stmt_bind_param($stmt, "i", $tempoh_saat);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM rate_limits WHERE rl_kunci = ? AND dicipta_pada >= (NOW() - INTERVAL ? SECOND)");
    mysqli_stmt_bind_param($stmt, "si", $kunci, $tempoh_saat);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$jumlah >= $maksimum;
}

function rekod_cubaan($conn, string $kunci): void
{
    $stmt = mysqli_prepare($conn, "INSERT INTO rate_limits (rl_kunci, dicipta_pada) VALUES (?, NOW())");
    mysqli_stmt_bind_param($stmt, "s", $kunci);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function kosongkan_cubaan($conn, string $kunci): void
{
    $stmt = mysqli_prepare($conn, "DELETE FROM rate_limits WHERE rl_kunci = ?");
    mysqli_stmt_bind_param($stmt, "s", $kunci);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function had_dilanggar($conn, string $nama_had, string $bahagian_kunci, int $maksimum, int $tempoh_saat): bool
{
    return terlalu_banyak_cubaan($conn, $nama_had . ':' . hash_kunci($bahagian_kunci), $maksimum, $tempoh_saat);
}

function catat_had($conn, string $nama_had, string $bahagian_kunci): void
{
    rekod_cubaan($conn, $nama_had . ':' . hash_kunci($bahagian_kunci));
}

function tolak_permintaan_berlebihan(string $mesej = 'Terlalu banyak permintaan. Sila cuba sebentar lagi.'): void
{
    http_response_code(429);
    exit($mesej);
}

function audit($conn, string $tindakan, ?int $id_sasaran = null, string $butiran = ''): void
{
    $peranan = isset($_SESSION['role']) ? $_SESSION['role'] : 'tiada';
    $id_pelaku = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ip = hash_ip();

    $stmt = mysqli_prepare($conn, "INSERT INTO audit_log (id_pelaku, peranan_pelaku, tindakan, id_sasaran, butiran, ip_hash, dicipta_pada) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "ississ", $id_pelaku, $peranan, $tindakan, $id_sasaran, $butiran, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function sesi_tamat_tempoh(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $sekarang = time();
    $peranan = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $had_idle = $peranan === 'pesakit' ? TEMPOH_IDLE_PESAKIT : TEMPOH_IDLE_STAF;
    $aktiviti = isset($_SESSION['aktiviti_akhir']) ? (int)$_SESSION['aktiviti_akhir'] : $sekarang;
    $mula = isset($_SESSION['sesi_mula']) ? (int)$_SESSION['sesi_mula'] : $sekarang;

    if ($sekarang - $aktiviti > $had_idle || $sekarang - $mula > TEMPOH_MUTLAK) {
        $_SESSION = [];
        session_destroy();

        return true;
    }

    $_SESSION['aktiviti_akhir'] = $sekarang;

    return false;
}

function mulakan_sesi_pengguna(int $id, string $peranan, string $nama, bool $mesti_tukar_kata_laluan = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['role'] = $peranan;
    $_SESSION['nama'] = $nama;
    $_SESSION['sesi_mula'] = time();
    $_SESSION['aktiviti_akhir'] = time();
    $_SESSION['mesti_tukar_kata_laluan'] = $mesti_tukar_kata_laluan;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function jadual_peranan(string $peranan): ?array
{
    $peta = [
        'pesakit' => ['pesakit', 'id_pesakit', 'nama_pesakit'],
        'doktor' => ['doktor', 'id_doktor', 'nama_doktor'],
        'kakitangan' => ['kakitangan', 'id_kakitangan', 'nama_kakitangan'],
        'pentadbir' => ['pentadbir', 'id_pentadbir', 'nama_pentadbir'],
    ];

    return isset($peta[$peranan]) ? $peta[$peranan] : null;
}

function akaun_masih_aktif($conn, string $peranan, int $id): bool
{
    $peta = jadual_peranan($peranan);
    if (!$peta) {
        return false;
    }

    list($jadual, $medan_id) = $peta;

    $lajur = mysqli_query($conn, "SHOW COLUMNS FROM `$jadual` LIKE 'status_aktif'");
    $ada_status = $lajur && mysqli_num_rows($lajur) > 0;

    $sql = $ada_status
        ? "SELECT status_aktif FROM `$jadual` WHERE `$medan_id` = ? LIMIT 1"
        : "SELECT 'Aktif' AS status_aktif FROM `$jadual` WHERE `$medan_id` = ? LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris && $baris['status_aktif'] === 'Aktif';
}

function fail_imej_atau_pdf_sah(array $fail, int $maksimum_bait, ?string &$ralat = null): ?string
{
    if ($fail['error'] !== UPLOAD_ERR_OK) {
        $ralat = 'Muat naik gagal. Sila cuba lagi.';
        return null;
    }

    if ($fail['size'] <= 0 || $fail['size'] > $maksimum_bait) {
        $ralat = 'Saiz fail tidak dibenarkan. Maksimum ' . round($maksimum_bait / 1048576, 1) . 'MB.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $jenis = $finfo->file($fail['tmp_name']);

    $dibenarkan = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    if (!isset($dibenarkan[$jenis])) {
        $ralat = 'Jenis fail tidak disokong. Gunakan jpg, png, webp atau pdf sahaja.';
        return null;
    }

    if ($jenis !== 'application/pdf' && @getimagesize($fail['tmp_name']) === false) {
        $ralat = 'Fail imej rosak atau bukan imej sebenar.';
        return null;
    }

    return $dibenarkan[$jenis];
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
}

verify_csrf();
