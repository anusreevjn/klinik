<?php
require_once 'config.php';
require_once 'include/helpers.php';

$nama_klinik = tetapan($conn, 'nama_klinik', 'Klinik Pergigian Dr Arifin');
$alamat = tetapan($conn, 'alamat_klinik', '');
$telefon = tetapan($conn, 'telefon_klinik', '');
$whatsapp = tetapan($conn, 'whatsapp_klinik', '');
$email_klinik = tetapan($conn, 'email_klinik', '');
$facebook = tetapan($conn, 'facebook_klinik', '');
$logo = tetapan($conn, 'logo_klinik', 'assets/image/logo.jpg');

$waktu = [
    'Ahad hingga Khamis' => tetapan($conn, 'waktu_ahad_khamis', '-'),
    'Jumaat' => tetapan($conn, 'waktu_jumaat', '-'),
    'Sabtu' => tetapan($conn, 'waktu_sabtu', '-'),
];

$ikon_rawatan = ['R001' => '🦷', 'R002' => '✨', 'R003' => '🪥', 'R004' => '🩹', 'R005' => '🧒', 'R006' => '🔍', 'R007' => '🧪', 'R008' => '👑', 'R009' => '😁', 'R010' => '🩻', 'R011' => '💎', 'R012' => '📐'];

$servis = mysqli_query($conn, "SELECT kod_rawatan, nama_rawatan, harga, harga_maksimum FROM kod_rawatan ORDER BY kod_rawatan ASC LIMIT 6");

$stat = mysqli_query($conn, "SELECT
        (SELECT COUNT(*) FROM pesakit) AS pesakit,
        (SELECT COUNT(*) FROM doktor WHERE status_aktif = 'Aktif') AS doktor,
        (SELECT COUNT(*) FROM kod_rawatan) AS rawatan");
$angka = mysqli_fetch_assoc($stat);
$tahun_beroperasi = (int)date('Y') - 1997;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($nama_klinik) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=7">
<link rel="stylesheet" href="assets/css/theme.css?v=7">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>
<body>

<div class="strip-atas">
    <div>
        <span>✉ <a href="mailto:<?= e($email_klinik) ?>"><?= e($email_klinik) ?></a></span>
        &nbsp;&nbsp;
        <span>☎ <a href="tel:<?= e($telefon) ?>"><?= e($telefon) ?></a></span>
    </div>
    <div>
        <?php if ($facebook !== '') { ?>
            <span><a href="<?= e($facebook) ?>" target="_blank" rel="noopener">Facebook</a></span>
        <?php } ?>
        <?php if ($whatsapp !== '') { ?>
            &nbsp;&nbsp;<span>WhatsApp <?= e($whatsapp) ?></span>
        <?php } ?>
    </div>
</div>

<div class="header-decare" id="header-utama">
    <div class="logo">
        <img src="<?= e($logo) ?>" alt="Logo <?= e($nama_klinik) ?>">
        <?= e($nama_klinik) ?>
    </div>

    <div class="menu-decare">
        <a href="#utama">Laman Utama</a>
        <a href="#servis">Perkhidmatan</a>
        <a href="#tentang">Tentang Kami</a>
        <a href="#hubungi">Hubungi</a>
        <a href="login.php">Log Masuk</a>
        <a class="btn-tempah" href="register.php">Tempah Temu Janji</a>
    </div>
</div>

<div class="hero-decare" id="utama">
    <div class="hero-grid">
        <div class="reveal">
            <span class="eyebrow">Selamat datang ke <?= e($nama_klinik) ?></span>
            <h1>Senyuman Sihat, <span class="biru">Hidup Lebih Yakin</span></h1>
            <p>Tempah temu janji, semak giliran, lihat rekod rawatan dan muat turun resit anda dalam satu sistem. Klinik kami di Bachok, Kelantan beroperasi sejak 1997.</p>

            <div class="hero-cta">
                <a class="btn btn-panah" href="register.php">Daftar Sebagai Pesakit <span>→</span></a>
                <a class="btn btn-outline" href="login.php">Log Masuk Pesakit</a>
            </div>

            <div class="grid-servis" style="margin-top:34px">
                <div class="kad-jam reveal">
                    <span class="eyebrow">Pesakit Berdaftar</span>
                    <h2 class="kira-nombor" data-kira="<?= (int)$angka['pesakit'] ?>"><?= (int)$angka['pesakit'] ?></h2>
                </div>
                <div class="kad-jam reveal">
                    <span class="eyebrow">Jenis Rawatan</span>
                    <h2 class="kira-nombor" data-kira="<?= (int)$angka['rawatan'] ?>"><?= (int)$angka['rawatan'] ?></h2>
                </div>
                <div class="kad-jam reveal">
                    <span class="eyebrow">Tahun Beroperasi</span>
                    <h2 class="kira-nombor" data-kira="<?= $tahun_beroperasi ?>"><?= $tahun_beroperasi ?></h2>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="blob-biru"></div>
            <div class="kotak-visual">
                <img src="<?= e($logo) ?>" alt="Logo <?= e($nama_klinik) ?>">
                <h3><?= e($nama_klinik) ?></h3>
                <p class="invoice-date">Klinik Pergigian / General Dentist</p>
                <a class="btn-tempah" href="#servis">Lihat Perkhidmatan</a>
            </div>
            <div class="kad-terapung"><span class="titik"></span> Temu janji dibuka setiap hari</div>
        </div>
    </div>
</div>

<div class="seksyen" id="servis">
    <div class="seksyen-dalam">
        <div class="seksyen-kepala reveal">
            <span class="eyebrow">Perkhidmatan Kami</span>
            <h2>Rawatan Yang Kami Sediakan</h2>
            <p>Harga di bawah adalah harga rujukan. Harga akhir bergantung pada keadaan gigi dan akan dimaklumkan oleh doktor sebelum rawatan.</p>
        </div>

        <div class="grid-servis">
            <?php while ($row = mysqli_fetch_assoc($servis)) {
                $ikon = isset($ikon_rawatan[$row['kod_rawatan']]) ? $ikon_rawatan[$row['kod_rawatan']] : '🦷';
            ?>
                <div class="kad-servis reveal">
                    <div class="ikon-servis"><?= $ikon ?></div>
                    <div>
                        <h3><?= e($row['nama_rawatan']) ?></h3>
                        <p class="harga">
                            <?= wang($row['harga']) ?><?= $row['harga_maksimum'] ? ' hingga ' . wang($row['harga_maksimum']) : '' ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="seksyen lembut" id="tentang">
    <div class="seksyen-dalam">
        <div class="grid-tentang">
            <div class="gambar-tentang reveal">
                <img src="<?= e($logo) ?>" alt="Logo <?= e($nama_klinik) ?>">
                <div class="lencana-tahun">
                    <strong class="kira-nombor" data-kira="<?= $tahun_beroperasi ?>"><?= $tahun_beroperasi ?></strong>
                    <span>Tahun Pengalaman</span>
                </div>
            </div>

            <div class="reveal">
                <span class="eyebrow">Tentang Kami</span>
                <h2>Kami Jaga Kesihatan Gigi Anda</h2>
                <p>Klinik Pergigian Dr Arifin menawarkan pelbagai perkhidmatan rawatan pergigian untuk dewasa dan kanak-kanak, daripada pemeriksaan dan scaling sehingga rawatan akar, crown dan gigi palsu.</p>

                <ul class="senarai-tanda">
                    <li>Rekod rawatan dan carta gigi digital untuk setiap pesakit</li>
                    <li>Tempahan temu janji atas talian dengan slot 30 minit</li>
                    <li>Nombor giliran automatik semasa check-in di kaunter</li>
                    <li>Invois dan resit boleh dimuat turun sendiri oleh pesakit</li>
                </ul>

                <a class="btn btn-panah" href="register.php">Mula Sekarang <span>→</span></a>
            </div>
        </div>
    </div>
</div>

<div class="seksyen">
    <div class="bar-tempahan reveal">
        <div>
            <label>Nama Anda</label>
            <input type="text" class="form-control" placeholder="Nama penuh" disabled>
        </div>
        <div>
            <label>Jenis Rawatan</label>
            <input type="text" class="form-control" placeholder="Contoh Scaling" disabled>
        </div>
        <div>
            <label>Tarikh Pilihan</label>
            <input type="text" class="form-control" placeholder="Pilih tarikh" disabled>
        </div>
        <div>
            <a class="btn btn-putih btn-panah" href="register.php">Tempah Sekarang <span>→</span></a>
        </div>
    </div>
</div>

<div class="seksyen lembut" id="hubungi">
    <div class="seksyen-dalam">
        <div class="seksyen-kepala reveal">
            <span class="eyebrow">Hubungi Kami</span>
            <h2>Waktu Operasi &amp; Lokasi</h2>
        </div>

        <div class="grid-maklumat">
            <div class="kad-jam reveal">
                <h3>Waktu Operasi</h3>
                <?php foreach ($waktu as $hari => $jam) { ?>
                    <div class="baris-jam"><span><?= e($hari) ?></span><strong><?= e($jam) ?></strong></div>
                <?php } ?>
            </div>

            <div class="kad-jam reveal">
                <h3>Maklumat Klinik</h3>
                <div class="baris-jam"><span>Alamat</span><strong style="text-align:right;max-width:60%"><?= e($alamat) ?></strong></div>
                <div class="baris-jam"><span>Telefon</span><strong><?= e($telefon) ?></strong></div>
                <div class="baris-jam"><span>WhatsApp</span><strong><?= e($whatsapp) ?></strong></div>
                <div class="baris-jam"><span>Email</span><strong><?= e($email_klinik) ?></strong></div>
            </div>

            <div class="kad-jam reveal">
                <h3>Akses Sistem</h3>
                <p>Pesakit boleh tempah temu janji dan lihat rekod rawatan. Kakitangan, doktor dan pentadbir guna log masuk berasingan.</p>
                <div class="btn-group">
                    <a class="btn" href="login.php">Log Masuk Pesakit</a>
                    <a class="btn btn-outline" href="staff_login.php">Log Masuk Staf</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer-decare">
    <div class="footer-grid">
        <div>
            <h4><?= e($nama_klinik) ?></h4>
            <p><?= e($alamat) ?></p>
            <p>Tel: <?= e($telefon) ?></p>
        </div>
        <div>
            <h4>Pautan</h4>
            <a href="#servis">Perkhidmatan</a>
            <a href="#tentang">Tentang Kami</a>
            <a href="#hubungi">Hubungi</a>
        </div>
        <div>
            <h4>Pesakit</h4>
            <a href="register.php">Daftar Akaun</a>
            <a href="login.php">Log Masuk</a>
            <a href="lupa_password.php">Lupa Kata Laluan</a>
        </div>
        <div>
            <h4>Kakitangan</h4>
            <a href="staff_login.php">Log Masuk Staf</a>
            <a href="queue_display.php">Papan Giliran</a>
        </div>
    </div>

    <div class="footer-bawah">
        <?= date('Y') ?> <?= e($nama_klinik) ?>. Semua hak terpelihara.
    </div>
</div>

<script src="assets/js/ui.js?v=7"></script>
</body>
</html>
