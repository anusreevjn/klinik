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

$ikon_rawatan = ['R001' => 'ti-dental', 'R002' => 'ti-sparkles', 'R003' => 'ti-dental', 'R004' => 'ti-dental-off', 'R005' => 'ti-mood-kid', 'R006' => 'ti-zoom-in', 'R007' => 'ti-test-pipe', 'R008' => 'ti-crown', 'R009' => 'ti-mood-smile', 'R010' => 'ti-radioactive', 'R011' => 'ti-diamond', 'R012' => 'ti-ruler-2'];

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
    <link rel="stylesheet" href="assets/css/style.css?v=11">
<link rel="stylesheet" href="assets/css/theme.css?v=11">
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

    <button type="button" class="nav-togol" aria-label="Togol menu">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

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
                $ikon = isset($ikon_rawatan[$row['kod_rawatan']]) ? $ikon_rawatan[$row['kod_rawatan']] : 'ti-dental';
            ?>
                <div class="kad-servis reveal">
                    <div class="ikon-servis"><i class="ti <?= e($ikon) ?>" aria-hidden="true"></i></div>
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
                <div class="gigi-pentas">
                    <svg class="gigi-animasi" viewBox="0 0 200 250" role="img" aria-label="Gigi comel melompat">
                        <defs>
                            <linearGradient id="gigiBadan" x1="0.3" y1="0" x2="0.7" y2="1">
                                <stop offset="0" stop-color="#ffffff"/>
                                <stop offset="0.6" stop-color="#f3f7fd"/>
                                <stop offset="1" stop-color="#dbe6f5"/>
                            </linearGradient>
                            <radialGradient id="gigiGloss" cx="0.36" cy="0.26" r="0.5">
                                <stop offset="0" stop-color="#ffffff" stop-opacity="0.95"/>
                                <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
                            </radialGradient>
                        </defs>

                        <ellipse class="gigi-bayang" cx="100" cy="230" rx="52" ry="11" fill="#2f6bff" opacity="0.18"/>

                        <g class="gigi-tubuh">
                            <path d="M100 20 C66 20 42 40 42 76 C42 104 52 120 58 138 C63 154 58 176 70 200 C77 216 90 214 93 198 C96 184 96 170 100 170 C104 170 104 184 107 198 C110 214 123 216 130 200 C142 176 137 154 142 138 C148 120 158 104 158 76 C158 40 134 20 100 20 Z" fill="url(#gigiBadan)" stroke="#cdd9ea" stroke-width="2"/>
                            <ellipse cx="74" cy="60" rx="16" ry="26" fill="url(#gigiGloss)"/>

                            <circle class="gigi-pipi" cx="66" cy="118" r="7" fill="#ff9bb3" opacity="0.65"/>
                            <circle class="gigi-pipi" cx="134" cy="118" r="7" fill="#ff9bb3" opacity="0.65"/>

                            <g class="gigi-mata">
                                <circle cx="80" cy="100" r="8.5" fill="#22315a"/>
                                <circle cx="120" cy="100" r="8.5" fill="#22315a"/>
                                <circle cx="83" cy="96.5" r="2.6" fill="#fff"/>
                                <circle cx="123" cy="96.5" r="2.6" fill="#fff"/>
                            </g>

                            <path class="gigi-senyum" d="M82 118 Q100 134 118 118" fill="none" stroke="#22315a" stroke-width="4.5" stroke-linecap="round"/>
                        </g>

                        <g class="kilau" transform="translate(158 48)">
                            <path d="M0 -13 L3.2 -3.2 L13 0 L3.2 3.2 L0 13 L-3.2 3.2 L-13 0 L-3.2 -3.2 Z" fill="#22c3e6"/>
                        </g>
                        <g class="kilau kilau-2" transform="translate(40 44)">
                            <path d="M0 -8 L2 -2 L8 0 L2 2 L0 8 L-2 2 L-8 0 L-2 -2 Z" fill="#4f8cff"/>
                        </g>
                    </svg>
                </div>
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

<script src="assets/js/ui.js?v=11"></script>
</body>
</html>
