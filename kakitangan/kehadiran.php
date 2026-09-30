<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_kakitangan = guard($conn, 'kakitangan');

$tarikh = isset($_REQUEST['tarikh']) && $_REQUEST['tarikh'] !== '' ? $_REQUEST['tarikh'] : date('Y-m-d');
$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['check_in'])) {
    $id_tj = (int)$_POST['check_in'];

    $stmt = mysqli_prepare($conn, "SELECT t.*, p.nama_pesakit FROM temu_janji t LEFT JOIN pesakit p ON p.id_pesakit = t.id_pesakit WHERE t.id_temu_janji = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_tj);
    mysqli_stmt_execute($stmt);
    $tj = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$tj) {
        $mesej = 'Temu janji tidak dijumpai.';
        $jenis_mesej = 'error';
    } elseif ($tj['no_giliran']) {
        $mesej = 'Pesakit ini sudah check-in dengan nombor giliran ' . $tj['no_giliran'] . '.';
        $jenis_mesej = 'warning';
    } else {
        $no_giliran = jana_no_giliran($conn, $tj['tarikh_temu_janji']);

        mysqli_begin_transaction($conn);
        try {
            $u = mysqli_prepare($conn, "UPDATE temu_janji SET no_giliran = ?, status = 'Disahkan', id_kakitangan = ? WHERE id_temu_janji = ?");
            mysqli_stmt_bind_param($u, "sii", $no_giliran, $id_kakitangan, $id_tj);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);

            $g = mysqli_prepare($conn, "INSERT INTO giliran (id_temu_janji, id_kakitangan, no_giliran, status_giliran, masa_daftar_masuk) VALUES (?, ?, ?, 'Menunggu', NOW())");
            mysqli_stmt_bind_param($g, "iis", $id_tj, $id_kakitangan, $no_giliran);
            mysqli_stmt_execute($g);
            mysqli_stmt_close($g);

            audit($conn, 'pesakit_check_in', $id_tj, 'no_giliran=' . $no_giliran);
            hantar_notifikasi($conn, 'pesakit', (int)$tj['id_pesakit'], 'Check-in berjaya', 'Nombor giliran anda ialah ' . $no_giliran . '. Sila tunggu panggilan.', 'giliran', 'dashboard.php');

            mysqli_commit($conn);
            $mesej = 'Check-in berjaya. Nombor giliran ' . $no_giliran . ' diberikan kepada ' . $tj['nama_pesakit'] . '.';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $mesej = 'Ralat semasa check-in: ' . $e->getMessage();
            $jenis_mesej = 'error';
        }
    }
}

if (isset($_POST['tidak_hadir'])) {
    $id_tj = (int)$_POST['tidak_hadir'];

    $u = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Tidak Hadir', id_kakitangan = ? WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($u, "ii", $id_kakitangan, $id_tj);
    mysqli_stmt_execute($u);
    mysqli_stmt_close($u);

    $g = mysqli_prepare($conn, "UPDATE giliran SET status_giliran = 'Tidak Hadir' WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($g, "i", $id_tj);
    mysqli_stmt_execute($g);
    mysqli_stmt_close($g);

    $mesej = 'Pesakit ditanda sebagai tidak hadir.';
    $jenis_mesej = 'warning';
}

if (isset($_POST['selesai'])) {
    $id_tj = (int)$_POST['selesai'];

    $u = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Selesai' WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($u, "i", $id_tj);
    mysqli_stmt_execute($u);
    mysqli_stmt_close($u);

    $g = mysqli_prepare($conn, "UPDATE giliran SET status_giliran = 'Selesai', masa_selesai = NOW() WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($g, "i", $id_tj);
    mysqli_stmt_execute($g);
    mysqli_stmt_close($g);

    $mesej = 'Giliran ditanda selesai.';
}

$sql = "SELECT t.*, p.nama_pesakit, p.no_telefon, d.nama_doktor, g.status_giliran, g.masa_daftar_masuk
        FROM temu_janji t
        LEFT JOIN pesakit p ON p.id_pesakit = t.id_pesakit
        LEFT JOIN doktor d ON d.id_doktor = t.id_doktor
        LEFT JOIN giliran g ON g.id_temu_janji = t.id_temu_janji
        WHERE t.tarikh_temu_janji = ?
        ORDER BY t.masa_temu_janji ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $tarikh);
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$kira = mysqli_prepare($conn, "SELECT
        COUNT(*) AS jumlah,
        SUM(CASE WHEN no_giliran IS NOT NULL AND no_giliran <> '' THEN 1 ELSE 0 END) AS check_in,
        SUM(CASE WHEN status = 'Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) AS selesai
    FROM temu_janji WHERE tarikh_temu_janji = ?");
mysqli_stmt_bind_param($kira, "s", $tarikh);
mysqli_stmt_execute($kira);
$ringkasan = mysqli_fetch_assoc(mysqli_stmt_get_result($kira));

mula_halaman($conn, 'Kehadiran & Giliran', 'kakitangan', 'kehadiran.php', '<a class="btn btn-outline" target="_blank" href="../queue_display.php">Papan Giliran</a>');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <form method="get" class="date-group">
        <div class="form-group">
            <label>Tarikh</label>
            <input type="date" name="tarikh" class="form-control" value="<?= selamat($tarikh) ?>">
        </div>
        <div class="form-group"><label>&nbsp;</label><button class="btn" type="submit">Papar</button></div>
    </form>
</div>

<div class="dashboard-card"><p>Temu Janji Hari Ini</p><h2><?= (int)$ringkasan['jumlah'] ?></h2></div>
<div class="dashboard-card"><p>Sudah Check-in</p><h2><?= (int)$ringkasan['check_in'] ?></h2></div>
<div class="dashboard-card"><p>Selesai</p><h2><?= (int)$ringkasan['selesai'] ?></h2></div>
<div class="dashboard-card"><p>Tidak Hadir</p><h2><?= (int)$ringkasan['tidak_hadir'] ?></h2></div>

<div class="table-container">
    <table>
        <thead><tr><th>Masa</th><th>Pesakit</th><th>Telefon</th><th>Doktor</th><th>Rawatan</th><th>Giliran</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="8"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada temu janji pada tarikh ini.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat($row['masa_temu_janji'] ? date('h:i A', strtotime($row['masa_temu_janji'])) : '-') ?></td>
                <td><?= selamat($row['nama_pesakit']) ?></td>
                <td><?= selamat($row['no_telefon']) ?></td>
                <td><?= selamat($row['nama_doktor'] ?: '-') ?></td>
                <td><?= selamat($row['jenis_rawatan']) ?></td>
                <td><strong><?= selamat($row['no_giliran'] ?: '-') ?></strong></td>
                <td><span class="badge-status <?= kelas_status($row['status']) ?>"><?= selamat($row['status']) ?></span></td>
                <td>
                    <?php if (!$row['no_giliran'] && $row['status'] !== 'Dibatalkan' && $row['status'] !== 'Tidak Hadir') { ?>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tarikh" value="<?= selamat($tarikh) ?>">
                            <button class="action-btn" type="submit" name="check_in" value="<?= (int)$row['id_temu_janji'] ?>">Check-in</button>
                        </form>
                        <form method="post" style="display:inline" onsubmit="return confirm('Tanda pesakit tidak hadir?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tarikh" value="<?= selamat($tarikh) ?>">
                            <button class="action-btn btn-back" type="submit" name="tidak_hadir" value="<?= (int)$row['id_temu_janji'] ?>">Tidak Hadir</button>
                        </form>
                    <?php } elseif ($row['status'] !== 'Selesai' && $row['status'] !== 'Tidak Hadir') { ?>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tarikh" value="<?= selamat($tarikh) ?>">
                            <button class="action-btn" type="submit" name="selesai" value="<?= (int)$row['id_temu_janji'] ?>">Tanda Selesai</button>
                        </form>
                    <?php } else { ?>
                        -
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
