<?php

require_once __DIR__ . '/helpers.php';

function menu_peranan($peranan)
{
    $menu = [
        'pesakit' => [
            ['dashboard.php', 'Papan Utama'],
            ['profil.php', 'Profil Saya'],
            ['tanggungan.php', 'Profil Tanggungan'],
            ['appointment.php', 'Temu Janji'],
            ['appointment_saya.php', 'Sejarah Temu Janji'],
            ['sejarah_rawatan.php', 'Rekod Rawatan'],
            ['bayar.php', 'Buat Bayaran'],
            ['pembayaran.php', 'Invois & Resit'],
            ['pelan.php', 'Pelan Ansuran'],
            ['notifikasi.php', 'Notifikasi'],
            ['../logout.php', 'Log Keluar'],
        ],
        'doktor' => [
            ['dashboard.php', 'Papan Utama'],
            ['pesakit.php', 'Senarai Pesakit'],
            ['rekod_rawatan.php', 'Rekod Rawatan'],
            ['xray.php', 'X-Ray'],
            ['sejarah_rawatan.php', 'Sejarah Rawatan'],
            ['profil.php', 'Profil Saya'],
            ['../logout.php', 'Log Keluar'],
        ],
        'kakitangan' => [
            ['dashboard.php', 'Papan Utama'],
            ['pesakit.php', 'Pengurusan Pesakit'],
            ['appointment_manage.php', 'Pengurusan Temu Janji'],
            ['kehadiran.php', 'Kehadiran & Giliran'],
            ['pembayaran.php', 'Pembayaran Kaunter'],
            ['pelan.php', 'Pelan Ansuran'],
            ['pengesahan.php', 'Pengesahan Bayaran'],
            ['invois.php', 'Invois & Resit'],
            ['profil.php', 'Profil Saya'],
            ['../logout.php', 'Log Keluar'],
        ],
        'pentadbir' => [
            ['dashboard.php', 'Papan Utama'],
            ['pengguna.php', 'Pengurusan Pengguna'],
            ['rawatan.php', 'Pengurusan Rawatan'],
            ['jenis_xray.php', 'Jenis X-Ray'],
            ['inventori.php', 'Pengurusan Inventori'],
            ['pembayaran.php', 'Pengurusan Pembayaran'],
            ['laporan.php', 'Laporan'],
            ['tetapan.php', 'Tetapan Sistem'],
            ['profil.php', 'Profil Saya'],
            ['../logout.php', 'Log Keluar'],
        ],
    ];

    return isset($menu[$peranan]) ? $menu[$peranan] : [];
}

function mula_halaman($conn, $tajuk, $peranan, $aktif, $tindakan = '')
{
    $nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
    $logo = '../' . tetapan($conn, 'logo_klinik', 'assets/image/logo.jpg');
    $nama_pengguna = isset($_SESSION['nama']) ? $_SESSION['nama'] : ucfirst($peranan);

    echo '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . selamat($tajuk) . ' | ' . selamat($nama_klinik) . '</title>';
    echo '<link rel="stylesheet" href="../assets/css/style.css?v=7">
<link rel="stylesheet" href="../assets/css/theme.css?v=7">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">';
    echo '</head><body><div class="dashboard"><div class="sidebar">';
    echo '<div class="sidebar-logo"><img src="' . selamat($logo) . '" alt="Logo"><h2>' . selamat($nama_klinik) . '</h2></div>';

    foreach (menu_peranan($peranan) as $item) {
        $kelas = basename($item[0]) === $aktif ? ' class="active"' : '';
        echo '<a href="' . selamat($item[0]) . '"' . $kelas . '>' . selamat($item[1]) . '</a>';
    }

    echo '</div><div class="main"><div class="topbar">';
    echo '<div class="topbar-kiri"><h3>' . selamat($tajuk) . '</h3></div>';
    echo '<div class="header-action"><span class="badge-status">' . selamat($nama_pengguna) . '</span>';
    echo $tindakan . '</div></div>';
}

function tamat_halaman()
{
    echo '</div></div><script src="../assets/js/ui.js?v=7"></script></body></html>';
}
