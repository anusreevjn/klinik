<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/penjaga_lib.php';

$id_akaun = guard($conn, 'pesakit');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['daftar_tanggungan'])) {
    $ralat = null;
    $id_baru = daftar_profil_tanggungan($conn, $id_akaun, [
        'nama_pesakit' => $_POST['nama_pesakit'],
        'no_ic' => $_POST['no_ic'],
        'jantina' => $_POST['jantina'],
        'golongan' => $_POST['golongan'],
        'tarikh_lahir' => $_POST['tarikh_lahir'],
        'hubungan_penjaga' => $_POST['hubungan_penjaga'],
        'penyakit_kronik' => $_POST['penyakit_kronik'],
    ], $ralat);

    if ($id_baru) {
        $mesej = 'Profil berjaya didaftarkan. No Pendaftaran Klinik dan ID sistem sudah dijana.';
    } else {
        $mesej = $ralat;
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['luluskan_pautan'])) {
    $id_permohonan = (int)$_POST['luluskan_pautan'];
    $kekalkan = isset($_POST['kekalkan_akses']);
    $keterangan = null;

    if (luluskan_permohonan_pautan($conn, $id_permohonan, $id_akaun, $kekalkan, $keterangan)) {
        $mesej = $keterangan;
    } else {
        $mesej = $keterangan;
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['tolak_pautan'])) {
    $id_permohonan = (int)$_POST['tolak_pautan'];
    $sebab = trim($_POST['sebab']) !== '' ? trim($_POST['sebab']) : 'Tidak diluluskan oleh penjaga';

    if (tolak_permohonan_pautan($conn, $id_permohonan, $id_akaun, $sebab)) {
        $mesej = 'Permohonan ditolak.';
        $jenis_mesej = 'warning';
    } else {
        $mesej = 'Gagal tolak permohonan.';
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['tukar_akses'])) {
    $id_profil = (int)$_POST['tukar_akses'];
    $benarkan = $_POST['nilai_akses'] === '1';

    if (tukar_akses_penjaga($conn, $id_profil, $id_akaun, $benarkan)) {
        $mesej = $benarkan ? 'Akses anda ke profil tersebut dibuka semula.' : 'Akses anda ke profil tersebut telah ditutup.';
        $jenis_mesej = $benarkan ? 'success' : 'warning';
    }
}

$stmt = mysqli_prepare($conn, "SELECT p.*,
        (SELECT COUNT(*) FROM temu_janji t WHERE t.id_pesakit = p.id_pesakit) AS bil_tj,
        (SELECT COUNT(*) FROM rekod_rawatan r WHERE r.id_pesakit = p.id_pesakit) AS bil_rawatan
        FROM pesakit p WHERE p.id_penjaga_akaun = ? ORDER BY p.nama_pesakit ASC");
mysqli_stmt_bind_param($stmt, "i", $id_akaun);
mysqli_stmt_execute($stmt);
$tanggungan = mysqli_stmt_get_result($stmt);

$stmt2 = mysqli_prepare($conn, "SELECT pp.*, p.nama_pesakit, p.no_ic FROM permohonan_pautan pp
        LEFT JOIN pesakit p ON p.id_pesakit = pp.id_pesakit_profil
        WHERE pp.id_penjaga_akaun = ? ORDER BY pp.tarikh_mohon DESC LIMIT 20");
mysqli_stmt_bind_param($stmt2, "i", $id_akaun);
mysqli_stmt_execute($stmt2);
$permohonan = mysqli_stmt_get_result($stmt2);

mula_halaman($conn, 'Profil Tanggungan', 'pesakit', 'tanggungan.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <div class="card-title">Apa itu Profil Tanggungan</div>
    <p>Anda boleh daftarkan anak atau ibu bapa di bawah akaun anda. Setiap profil ada No Pendaftaran Klinik dan rekod rawatan sendiri. Temu janji yang anda buat untuk mereka akan masuk ke profil mereka, bukan profil anda.</p>
</div>

<div class="card">
    <div class="card-title">Daftar Profil Baharu</div>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Nama Penuh</label>
            <input type="text" name="nama_pesakit" class="form-control" required>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>No IC atau MyKid</label>
                <input type="text" name="no_ic" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Tarikh Lahir</label>
                <input type="date" name="tarikh_lahir" class="form-control" required>
            </div>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Jantina</label>
                <select name="jantina" class="form-control"><option>Lelaki</option><option>Perempuan</option></select>
            </div>
            <div class="form-group">
                <label>Golongan</label>
                <select name="golongan" class="form-control"><option>Kanak-kanak</option><option>Dewasa</option><option>Warga Emas</option></select>
            </div>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Hubungan Dengan Anda</label>
                <select name="hubungan_penjaga" class="form-control">
                    <option>Anak</option>
                    <option>Ibu</option>
                    <option>Bapa</option>
                    <option>Adik Beradik</option>
                    <option>Lain-lain</option>
                </select>
            </div>
            <div class="form-group">
                <label>Penyakit Kronik</label>
                <input type="text" name="penyakit_kronik" class="form-control" placeholder="Tiada">
            </div>
        </div>
        <button class="btn" type="submit" name="daftar_tanggungan">Daftar Profil</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>Nama</th><th>No Pendaftaran</th><th>ID Sistem</th><th>Hubungan</th><th>Temu Janji</th><th>Rawatan</th><th>Akaun</th><th>Akses Anda</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($tanggungan) === 0) { ?>
            <tr><td colspan="8"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Belum ada profil tanggungan.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($tanggungan)) { ?>
            <tr>
                <td><?= selamat($row['nama_pesakit']) ?></td>
                <td><?= selamat($row['no_pendaftaran_klinik'] ?: '-') ?></td>
                <td><?= selamat(id_pesakit_sistem($conn, (int)$row['id_pesakit'])) ?></td>
                <td><?= selamat($row['hubungan_penjaga'] ?: '-') ?></td>
                <td><?= (int)$row['bil_tj'] ?></td>
                <td><?= (int)$row['bil_rawatan'] ?></td>
                <td>
                    <?php if ($row['kata_laluan']) { ?>
                        <span class="badge-status selesai">Ada akaun sendiri</span>
                    <?php } else { ?>
                        <span class="badge-status menunggu">Tiada akaun</span>
                    <?php } ?>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="nilai_akses" value="<?= (int)$row['akses_penjaga'] === 1 ? '0' : '1' ?>">
                        <button class="action-btn <?= (int)$row['akses_penjaga'] === 1 ? 'btn-back' : '' ?>" type="submit" name="tukar_akses" value="<?= (int)$row['id_pesakit'] ?>">
                            <?= (int)$row['akses_penjaga'] === 1 ? 'Tutup Akses' : 'Buka Akses' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-title">Permohonan Akaun Sendiri</div>
    <p>Bila anak anda sudah besar dan mahu akaun sendiri, dia boleh daftar guna No IC yang sama. Permohonan akan masuk di sini untuk anda sahkan.</p>

    <?php if (mysqli_num_rows($permohonan) === 0) { ?>
        <p>Tiada permohonan buat masa ini.</p>
    <?php } ?>

    <?php while ($row = mysqli_fetch_assoc($permohonan)) { ?>
        <hr class="pembahagi">
        <div class="profile-item"><span class="info-label">Nama</span><span><?= selamat($row['nama_pesakit']) ?></span></div>
        <div class="profile-item"><span class="info-label">Email Dipohon</span><span><?= selamat($row['email_dipohon']) ?></span></div>
        <div class="profile-item"><span class="info-label">Tarikh</span><span><?= selamat(date('d/m/Y h:i A', strtotime($row['tarikh_mohon']))) ?></span></div>
        <div class="profile-item"><span class="info-label">Status</span><span class="badge-status <?= kelas_status($row['status_permohonan']) ?>"><?= selamat($row['status_permohonan']) ?></span></div>

        <?php if ($row['status_permohonan'] === 'Menunggu') { ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="kekalkan_akses" checked>
                        Kekalkan akses saya ke profil ini selepas dia ada akaun sendiri
                    </label>
                </div>
                <div class="date-group">
                    <input type="text" name="sebab" class="form-control" placeholder="Sebab jika ditolak" style="max-width:260px">
                    <button class="btn" type="submit" name="luluskan_pautan" value="<?= (int)$row['id_permohonan'] ?>">Sahkan</button>
                    <button class="btn btn-batal" type="submit" name="tolak_pautan" value="<?= (int)$row['id_permohonan'] ?>" onclick="return confirm('Tolak permohonan ini?')">Tolak</button>
                </div>
            </form>
        <?php } ?>
    <?php } ?>
</div>

<?php
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt2);
tamat_halaman();
