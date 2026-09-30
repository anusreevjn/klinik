<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_doktor = guard($conn, 'doktor', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

$id_pesakit = isset($_REQUEST['id_p']) ? (int)$_REQUEST['id_p'] : 0;

if (isset($_POST['simpan_xray'])) {
    $id_pesakit = (int)$_POST['id_p'];
    $id_jenis = $_POST['id_jenis_xray'] !== '' ? (int)$_POST['id_jenis_xray'] : null;
    $tarikh = $_POST['tarikh_pemeriksaan'];
    $catatan = trim($_POST['catatan_doktor']);
    $no_gigi = preg_replace('/[^0-9, ]/', '', (string)$_POST['no_gigi']);

    $kunci = 'xray_upload:' . hash_kunci((string)$id_doktor);

    if (terlalu_banyak_cubaan($conn, $kunci, 40, 3600)) {
        $mesej = 'Terlalu banyak muat naik dalam masa singkat. Sila cuba sebentar lagi.';
        $jenis_mesej = 'warning';
    } elseif ($id_pesakit <= 0 || $tarikh === '' || strtotime($tarikh) === false) {
        $mesej = 'Sila pilih pesakit dan tarikh pemeriksaan yang sah.';
        $jenis_mesej = 'error';
    } else {
        $ralat = null;
        $sambungan = fail_imej_atau_pdf_sah(isset($_FILES['fail_xray']) ? $_FILES['fail_xray'] : [], 8 * 1024 * 1024, $ralat);

        if (!$sambungan) {
            $mesej = $ralat;
            $jenis_mesej = 'error';
        } else {
            $folder = __DIR__ . '/../storage/xray';
            if (!is_dir($folder)) {
                mkdir($folder, 0750, true);
            }

            $nama_fail = 'xray_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $sambungan;

            if (!move_uploaded_file($_FILES['fail_xray']['tmp_name'], $folder . '/' . $nama_fail)) {
                $mesej = 'Gagal muat naik fail X-ray.';
                $jenis_mesej = 'error';
            } else {
                chmod($folder . '/' . $nama_fail, 0640);
                $laluan = 'storage/xray/' . $nama_fail;

                rekod_cubaan($conn, $kunci);

                $stmt = mysqli_prepare($conn, "INSERT INTO xray (id_pesakit, id_doktor, id_jenis_xray, fail_xray, tarikh_pemeriksaan, catatan_doktor, no_gigi, dicipta_pada) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, "iiissss", $id_pesakit, $id_doktor, $id_jenis, $laluan, $tarikh, $catatan, $no_gigi);

                if (mysqli_stmt_execute($stmt)) {
                    $id_xray = mysqli_insert_id($conn);
                    audit($conn, 'xray_dimuat_naik', $id_xray, 'pesakit=' . $id_pesakit);
                    hantar_notifikasi($conn, 'pesakit', $id_pesakit, 'X-Ray baharu direkodkan', 'Doktor telah merekodkan X-Ray bertarikh ' . date('d/m/Y', strtotime($tarikh)) . ' dalam rekod rawatan anda.', 'xray', 'sejarah_rawatan.php');
                    $mesej = 'X-Ray berjaya dimuat naik dan disimpan.';
                } else {
                    $mesej = 'Gagal simpan rekod X-ray.';
                    $jenis_mesej = 'error';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if (isset($_POST['padam_xray'])) {
    $id_xray = (int)$_POST['padam_xray'];

    $stmt = mysqli_prepare($conn, "SELECT fail_xray, id_pesakit FROM xray WHERE id_xray = ? AND id_doktor = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $id_xray, $id_doktor);
    mysqli_stmt_execute($stmt);
    $rekod = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$rekod) {
        $mesej = 'Rekod X-ray tidak dijumpai atau bukan milik anda.';
        $jenis_mesej = 'error';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM xray WHERE id_xray = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_xray);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $fail = __DIR__ . '/../' . $rekod['fail_xray'];
        if (is_file($fail)) {
            unlink($fail);
        }

        audit($conn, 'xray_dipadam', $id_xray, 'pesakit=' . $rekod['id_pesakit']);
        $id_pesakit = (int)$rekod['id_pesakit'];
        $mesej = 'Rekod X-ray telah dipadam.';
        $jenis_mesej = 'warning';
    }
}

$jenis_tersedia = mysqli_query($conn, "SELECT id_jenis, nama_jenis, singkatan FROM jenis_xray WHERE status_aktif = 'Aktif' ORDER BY susunan ASC, nama_jenis ASC");

$carian = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql_pesakit = "SELECT id_pesakit, nama_pesakit, no_ic, no_pendaftaran_klinik FROM pesakit";
if ($carian !== '') {
    $sql_pesakit .= " WHERE nama_pesakit LIKE ? OR no_ic LIKE ? OR no_pendaftaran_klinik LIKE ?";
}
$sql_pesakit .= " ORDER BY nama_pesakit ASC LIMIT 100";

$stmt = mysqli_prepare($conn, $sql_pesakit);
if ($carian !== '') {
    $corak = '%' . $carian . '%';
    mysqli_stmt_bind_param($stmt, "sss", $corak, $corak, $corak);
}
mysqli_stmt_execute($stmt);
$senarai_pesakit = mysqli_stmt_get_result($stmt);

$pesakit_dipilih = null;
$senarai_xray = null;

if ($id_pesakit > 0) {
    $s = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_pesakit = ? LIMIT 1");
    mysqli_stmt_bind_param($s, "i", $id_pesakit);
    mysqli_stmt_execute($s);
    $pesakit_dipilih = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);

    $s = mysqli_prepare($conn, "SELECT x.*, j.nama_jenis, j.singkatan, d.nama_doktor
            FROM xray x
            LEFT JOIN jenis_xray j ON j.id_jenis = x.id_jenis_xray
            LEFT JOIN doktor d ON d.id_doktor = x.id_doktor
            WHERE x.id_pesakit = ?
            ORDER BY x.tarikh_pemeriksaan DESC, x.id_xray DESC");
    mysqli_stmt_bind_param($s, "i", $id_pesakit);
    mysqli_stmt_execute($s);
    $senarai_xray = mysqli_stmt_get_result($s);
    mysqli_stmt_close($s);
}

mula_halaman($conn, 'X-Ray Pesakit', 'doktor', 'xray.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <form method="get" class="date-group">
        <input type="text" name="q" class="form-control" placeholder="Cari nama, No IC atau No Pendaftaran" value="<?= selamat($carian) ?>">
        <button class="btn" type="submit">Cari</button>
    </form>

    <div class="table-container">
        <table>
            <thead><tr><th>Nama</th><th>No Pendaftaran</th><th>No IC</th><th>Tindakan</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($senarai_pesakit)) { ?>
                <tr>
                    <td><?= selamat($row['nama_pesakit']) ?></td>
                    <td><?= selamat($row['no_pendaftaran_klinik'] ?: '-') ?></td>
                    <td><?= selamat($row['no_ic']) ?></td>
                    <td><a class="action-btn" href="xray.php?id_p=<?= (int)$row['id_pesakit'] ?>">Pilih</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pesakit_dipilih) { ?>
    <div class="card">
        <div class="card-title">Muat Naik X-Ray untuk <?= selamat($pesakit_dipilih['nama_pesakit']) ?></div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_p" value="<?= (int)$pesakit_dipilih['id_pesakit'] ?>">

            <div class="date-group">
                <div class="form-group">
                    <label>Jenis X-Ray</label>
                    <select name="id_jenis_xray" class="form-control">
                        <option value="">Tidak dinyatakan</option>
                        <?php while ($j = mysqli_fetch_assoc($jenis_tersedia)) { ?>
                            <option value="<?= (int)$j['id_jenis'] ?>"><?= selamat($j['nama_jenis']) ?><?= $j['singkatan'] ? ' (' . selamat($j['singkatan']) . ')' : '' ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tarikh Pemeriksaan</label>
                    <input type="date" name="tarikh_pemeriksaan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>No Gigi Berkaitan</label>
                <input type="text" name="no_gigi" class="form-control" placeholder="Contoh 16, 17">
            </div>

            <div class="form-group">
                <label>Catatan atau Keputusan Doktor</label>
                <textarea name="catatan_doktor" class="form-control" placeholder="Contoh: karies pada gigi 16, disyorkan tampalan"></textarea>
            </div>

            <div class="form-group">
                <label>Fail X-Ray (jpg, png, webp atau pdf, maksimum 8MB)</label>
                <input type="file" name="fail_xray" class="form-control" accept="image/*,application/pdf" required>
            </div>

            <button class="btn" type="submit" name="simpan_xray">Simpan X-Ray</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Rekod X-Ray</div>
        <?php if (!$senarai_xray || mysqli_num_rows($senarai_xray) === 0) { ?>
            <p>Tiada rekod X-ray untuk pesakit ini.</p>
        <?php } ?>

        <?php while ($senarai_xray && ($row = mysqli_fetch_assoc($senarai_xray))) {
            $ext = strtolower(pathinfo($row['fail_xray'], PATHINFO_EXTENSION));
        ?>
            <hr class="pembahagi">
            <div class="profile-item"><span class="info-label">Tarikh</span><span><?= selamat($row['tarikh_pemeriksaan'] ? date('d/m/Y', strtotime($row['tarikh_pemeriksaan'])) : '-') ?></span></div>
            <div class="profile-item"><span class="info-label">Jenis</span><span><?= selamat($row['nama_jenis'] ?: 'Tidak dinyatakan') ?></span></div>
            <div class="profile-item"><span class="info-label">No Gigi</span><span><?= selamat($row['no_gigi'] ?: '-') ?></span></div>
            <div class="profile-item"><span class="info-label">Doktor</span><span><?= selamat($row['nama_doktor'] ?: '-') ?></span></div>
            <div class="profile-item"><span class="info-label">Catatan</span><span><?= selamat($row['catatan_doktor'] ?: '-') ?></span></div>

            <p>
                <a class="btn btn-outline" target="_blank" href="../xray_fail.php?id=<?= (int)$row['id_xray'] ?>">Buka Fail X-Ray</a>
            </p>

            <?php if ($ext !== 'pdf') { ?>
                <img src="../xray_fail.php?id=<?= (int)$row['id_xray'] ?>" alt="X-Ray" style="max-width:420px;border-radius:12px;background:#000">
            <?php } ?>

            <form method="post" style="margin-top:12px" onsubmit="return confirm('Padam rekod X-ray ini?')">
                <?= csrf_field() ?>
                <button class="btn btn-batal" type="submit" name="padam_xray" value="<?= (int)$row['id_xray'] ?>">Padam Rekod</button>
            </form>
        <?php } ?>
    </div>
<?php } ?>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
