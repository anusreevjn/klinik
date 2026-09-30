<?php
require_once __DIR__ . '/layout.php';

$peranan_semasa = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$halaman_semasa = basename($_SERVER['PHP_SELF']);
$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
$logo_sidebar = '../' . tetapan($conn, 'logo_klinik', 'assets/image/logo.jpg');
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= selamat($logo_sidebar) ?>" alt="Logo Klinik">
        <h2><?= selamat($nama_klinik) ?></h2>
    </div>
    <?php foreach (menu_peranan($peranan_semasa) as $item) { ?>
        <a href="<?= selamat($item[0]) ?>"<?= basename($item[0]) === $halaman_semasa ? ' class="active"' : '' ?>><?= selamat($item[1]) ?></a>
    <?php } ?>
</div>
