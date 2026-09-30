<?php

require_once __DIR__ . '/include/keselamatan.php';

$fail_env = __DIR__ . '/env.php';
$env = file_exists($fail_env) ? require $fail_env : [];

$host = isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost';
$user = isset($env['DB_USER']) ? $env['DB_USER'] : 'root';
$password = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$database = isset($env['DB_NAME']) ? $env['DB_NAME'] : 'klinik_pergigian_dr_arifin';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($host, $user, $password, $database);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Sambungan database gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Sistem tidak dapat menyambung ke database. Sila hubungi pentadbir.');
}

mysqli_report(MYSQLI_REPORT_OFF);
