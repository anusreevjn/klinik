<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_pentadbir = guard($conn, 'pentadbir', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

$medan = [
    'klinik' => [
        'nama_klinik' => 'Nama Klinik',
        'alamat_klinik' => 'Alamat',
        'telefon_klinik' => 'No Telefon',
        'whatsapp_klinik' => 'No WhatsApp',
        'email_klinik' => 'Email Klinik',
        'facebook_klinik' => 'Facebook',
    ],
    'operasi' => [
        'waktu_ahad_khamis' => 'Waktu Ahad hingga Khamis',
        'waktu_jumaat' => 'Waktu Jumaat',
        'waktu_sabtu' => 'Waktu Sabtu',
        'hari_tutup' => 'Hari Tutup',
    ],
    'temujanji' => [
        'tempoh_slot_minit' => 'Tempoh Satu Slot (minit)',
        'pesakit_per_slot' => 'Pesakit Setiap Slot',
    ],
    'pembayaran' => [
        'prefix_invois' => 'Prefix Invois',
        'prefix_resit' => 'Prefix Resit',
        'kaedah_bayaran' => 'Kaedah Bayaran Diterima',
    ],
    'email' => [
        'smtp_host' => 'SMTP Host',
        'smtp_port' => 'SMTP Port',
        'smtp_email' => 'Email Penghantar',
        'smtp_app_password' => 'App Password Gmail',
        'smtp_nama_pengirim' => 'Nama Penghantar',
    ],
    'pembayaran_bank' => [
        'nama_bank' => 'Nama Bank',
        'no_akaun_bank' => 'No Akaun Bank',
        'nama_pemegang_akaun' => 'Nama Pemegang Akaun',
    ],
];

$medan_rahsia = ['smtp_app_password'];

if (isset($_POST['simpan_tetapan'])) {
    foreach ($medan as $kumpulan) {
        foreach ($kumpulan as $kunci => $label) {
            if (!isset($_POST[$kunci])) {
                continue;
            }

            $nilai = trim($_POST[$kunci]);

            if (in_array($kunci, $medan_rahsia, true)) {
                if ($nilai === '') {
                    continue;
                }
                simpan_tetapan($conn, $kunci, $nilai);
                audit($conn, 'tetapan_rahsia_dikemaskini', null, $kunci);
                continue;
            }

            simpan_tetapan($conn, $kunci, $nilai);
        }
    }

    audit($conn, 'tetapan_sistem_dikemaskini', null, 'kumpulan=semua');

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $ralat_logo = null;
        $sambungan = fail_imej_atau_pdf_sah($_FILES['logo'], 2 * 1024 * 1024, $ralat_logo);

        if ($sambungan === null || $sambungan === 'pdf') {
            $mesej = $sambungan === 'pdf' ? 'Logo perlu dalam format imej, bukan pdf.' : $ralat_logo;
            $jenis_mesej = 'warning';
        } else {
            $folder = __DIR__ . '/../assets/image';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $nama_fail = 'logo.' . $sambungan;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $folder . '/' . $nama_fail)) {
                simpan_tetapan($conn, 'logo_klinik', 'assets/image/' . $nama_fail);
                audit($conn, 'logo_klinik_dikemaskini', null, $nama_fail);
            }
        }
    }

    if ($mesej === '') {
        $mesej = 'Tetapan sistem berjaya disimpan.';
    }

    header('Location: tetapan.php?ok=1');
    exit();
}

if (isset($_GET['ok'])) {
    $mesej = 'Tetapan sistem berjaya disimpan.';
}

mula_halaman($conn, 'Tetapan Sistem', 'pentadbir', 'tetapan.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <?php foreach ($medan as $nama_kumpulan => $kumpulan) { ?>
        <div class="card">
            <div class="card-title"><?= selamat(ucfirst($nama_kumpulan)) ?></div>
            <?php foreach ($kumpulan as $kunci => $label) { ?>
                <div class="form-group">
                    <label><?= selamat($label) ?></label>
                    <?php if ($kunci === 'alamat_klinik') { ?>
                        <textarea name="<?= selamat($kunci) ?>" class="form-control"><?= selamat(tetapan($conn, $kunci)) ?></textarea>
                    <?php } elseif (in_array($kunci, $medan_rahsia, true)) { ?>
                        <input type="password" name="<?= selamat($kunci) ?>" class="form-control" value="" autocomplete="new-password" placeholder="<?= tetapan($conn, $kunci) === '' ? 'Belum ditetapkan' : 'Tersimpan. Isi hanya jika mahu tukar' ?>">
                    <?php } else { ?>
                        <input type="text" name="<?= selamat($kunci) ?>" class="form-control" value="<?= selamat(tetapan($conn, $kunci)) ?>">
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($nama_kumpulan === 'klinik') { ?>
                <div class="form-group">
                    <label>Logo Klinik</label>
                    <label class="upload-kotak" for="logo_input">
                        <i class="ti ti-cloud-upload" aria-hidden="true"></i>
                        <span class="upload-teks">Klik untuk muat naik logo</span>
                        <span class="upload-nama" id="logo_nama">PNG, JPG atau WEBP (maksimum 2MB)</span>
                        <input type="file" id="logo_input" name="logo" accept="image/png, image/jpeg, image/webp" hidden>
                    </label>
                    <p class="upload-semasa">Logo semasa: <?= selamat(tetapan($conn, 'logo_klinik', 'tiada')) ?></p>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="card">
        <div class="btn-group">
            <button class="btn" type="submit" name="simpan_tetapan">Simpan Semua Tetapan</button>
            <a class="btn btn-back" href="dashboard.php">Kembali</a>
        </div>
    </div>
</form>

<?php tamat_halaman();
