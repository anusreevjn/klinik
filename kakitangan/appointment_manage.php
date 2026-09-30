<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/giliran_lib.php';

$id_kakitangan = guard($conn, 'kakitangan', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

function slot_sudah_diambil($conn, string $tarikh, string $masa, ?int $id_doktor, int $kecuali_id): bool
{
    $had = (int)tetapan($conn, 'pesakit_per_slot', '1');
    $had = $had > 0 ? $had : 1;

    if ($id_doktor) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM temu_janji WHERE tarikh_temu_janji = ? AND masa_temu_janji = ? AND id_doktor = ? AND id_temu_janji <> ? AND status NOT IN ('Dibatalkan', 'Ditolak')");
        mysqli_stmt_bind_param($stmt, "ssii", $tarikh, $masa, $id_doktor, $kecuali_id);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM temu_janji WHERE tarikh_temu_janji = ? AND masa_temu_janji = ? AND id_doktor IS NULL AND id_temu_janji <> ? AND status NOT IN ('Dibatalkan', 'Ditolak')");
        mysqli_stmt_bind_param($stmt, "ssi", $tarikh, $masa, $kecuali_id);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$jumlah >= $had;
}

function temu_janji_penuh($conn, int $id): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT t.*, p.nama_pesakit, p.no_telefon FROM temu_janji t LEFT JOIN pesakit p ON p.id_pesakit = t.id_pesakit WHERE t.id_temu_janji = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris ?: null;
}

if (isset($_POST['luluskan'])) {
    $id = (int)$_POST['luluskan'];
    $id_doktor = isset($_POST['id_doktor']) && $_POST['id_doktor'] !== '' ? (int)$_POST['id_doktor'] : null;
    $temu_janji = temu_janji_penuh($conn, $id);

    if (!$temu_janji || !in_array($temu_janji['status'], ['Menunggu', 'Ditolak'], true)) {
        $mesej = 'Temu janji ini tidak boleh diluluskan lagi.';
        $jenis_mesej = 'warning';
    } elseif ($id_doktor && slot_sudah_diambil($conn, $temu_janji['tarikh_temu_janji'], $temu_janji['masa_temu_janji'], $id_doktor, $id)) {
        $mesej = 'Doktor tersebut sudah ada pesakit pada slot masa itu.';
        $jenis_mesej = 'warning';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Disahkan', id_doktor = ?, id_kakitangan = ? WHERE id_temu_janji = ?");
        mysqli_stmt_bind_param($stmt, "iii", $id_doktor, $id_kakitangan, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        hantar_notifikasi($conn, 'pesakit', (int)$temu_janji['id_pesakit'], 'Temu janji diluluskan', 'Temu janji anda pada ' . date('d/m/Y', strtotime($temu_janji['tarikh_temu_janji'])) . ' jam ' . date('h:i A', strtotime($temu_janji['masa_temu_janji'])) . ' telah diluluskan.', 'temujanji', 'appointment_saya.php');
        audit($conn, 'temu_janji_diluluskan', $id, 'doktor=' . (string)$id_doktor);

        $mesej = 'Temu janji diluluskan dan pesakit telah dimaklumkan.';
    }
}

if (isset($_POST['tolak'])) {
    $id = (int)$_POST['tolak'];
    $sebab = trim($_POST['sebab']) !== '' ? trim($_POST['sebab']) : 'Slot tidak tersedia';
    $temu_janji = temu_janji_penuh($conn, $id);

    if (!$temu_janji || in_array($temu_janji['status'], ['Selesai', 'Dibatalkan'], true)) {
        $mesej = 'Temu janji ini tidak boleh ditolak.';
        $jenis_mesej = 'warning';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Ditolak', id_kakitangan = ?, catatan = ? WHERE id_temu_janji = ?");
        mysqli_stmt_bind_param($stmt, "isi", $id_kakitangan, $sebab, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        hantar_notifikasi($conn, 'pesakit', (int)$temu_janji['id_pesakit'], 'Temu janji ditolak', 'Temu janji anda pada ' . date('d/m/Y', strtotime($temu_janji['tarikh_temu_janji'])) . ' tidak dapat diterima. Sebab: ' . $sebab . '. Sila pilih slot lain.', 'temujanji', 'appointment.php');
        audit($conn, 'temu_janji_ditolak', $id, 'sebab=' . $sebab);

        $mesej = 'Temu janji ditolak dan pesakit telah dimaklumkan.';
        $jenis_mesej = 'warning';
    }
}

if (isset($_POST['tukar_slot'])) {
    $id = (int)$_POST['tukar_slot'];
    $tarikh_baru = $_POST['tarikh_baru'];
    $masa_baru = $_POST['masa_baru'];
    $temu_janji = temu_janji_penuh($conn, $id);

    if (!$temu_janji) {
        $mesej = 'Temu janji tidak dijumpai.';
        $jenis_mesej = 'error';
    } elseif ($tarikh_baru === '' || $masa_baru === '' || strtotime($tarikh_baru) === false) {
        $mesej = 'Tarikh atau masa baharu tidak sah.';
        $jenis_mesej = 'error';
    } elseif ($tarikh_baru < date('Y-m-d')) {
        $mesej = 'Tarikh baharu tidak boleh sebelum hari ini.';
        $jenis_mesej = 'error';
    } elseif (slot_sudah_diambil($conn, $tarikh_baru, $masa_baru, $temu_janji['id_doktor'] ? (int)$temu_janji['id_doktor'] : null, $id)) {
        $mesej = 'Slot baharu itu sudah penuh.';
        $jenis_mesej = 'warning';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET tarikh_temu_janji = ?, masa_temu_janji = ?, id_kakitangan = ? WHERE id_temu_janji = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $tarikh_baru, $masa_baru, $id_kakitangan, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        hantar_notifikasi($conn, 'pesakit', (int)$temu_janji['id_pesakit'], 'Slot temu janji ditukar', 'Temu janji anda telah ditukar ke ' . date('d/m/Y', strtotime($tarikh_baru)) . ' jam ' . date('h:i A', strtotime($masa_baru)) . '.', 'temujanji', 'appointment_saya.php');
        audit($conn, 'slot_temu_janji_ditukar', $id, $tarikh_baru . ' ' . $masa_baru);

        $mesej = 'Slot temu janji berjaya ditukar.';
    }
}

$status_papar = isset($_GET['status']) ? $_GET['status'] : 'Menunggu';
$senarai_status = ['Semua', 'Menunggu', 'Disahkan', 'Ditolak', 'Selesai', 'Tidak Hadir', 'Dibatalkan'];
if (!in_array($status_papar, $senarai_status, true)) {
    $status_papar = 'Menunggu';
}

$sql = "SELECT t.*, p.nama_pesakit, p.no_telefon, d.nama_doktor
        FROM temu_janji t
        LEFT JOIN pesakit p ON p.id_pesakit = t.id_pesakit
        LEFT JOIN doktor d ON d.id_doktor = t.id_doktor";

if ($status_papar !== 'Semua') {
    $sql .= " WHERE t.status = ?";
}
$sql .= " ORDER BY t.tarikh_temu_janji DESC, t.masa_temu_janji ASC LIMIT 200";

$stmt = mysqli_prepare($conn, $sql);
if ($status_papar !== 'Semua') {
    mysqli_stmt_bind_param($stmt, "s", $status_papar);
}
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$doktor_aktif = mysqli_query($conn, "SELECT id_doktor, nama_doktor FROM doktor WHERE status_aktif = 'Aktif' ORDER BY nama_doktor ASC");
$senarai_doktor = [];
while ($d = mysqli_fetch_assoc($doktor_aktif)) {
    $senarai_doktor[] = $d;
}

$kira = mysqli_query($conn, "SELECT
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) AS menunggu,
        SUM(CASE WHEN status = 'Disahkan' AND tarikh_temu_janji = CURDATE() THEN 1 ELSE 0 END) AS hari_ini,
        SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) AS ditolak
    FROM temu_janji");
$ringkasan = mysqli_fetch_assoc($kira);

mula_halaman($conn, 'Pengurusan Temu Janji', 'kakitangan', 'appointment_manage.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="dashboard-card"><p>Menunggu Kelulusan</p><h2><?= (int)$ringkasan['menunggu'] ?></h2></div>
<div class="dashboard-card"><p>Disahkan Hari Ini</p><h2><?= (int)$ringkasan['hari_ini'] ?></h2></div>
<div class="dashboard-card"><p>Pernah Ditolak</p><h2><?= (int)$ringkasan['ditolak'] ?></h2></div>

<div class="card">
    <div class="btn-group">
        <?php foreach ($senarai_status as $status) { ?>
            <a class="tab-link <?= $status === $status_papar ? 'active' : '' ?>" href="appointment_manage.php?status=<?= urlencode($status) ?>"><?= selamat($status) ?></a>
        <?php } ?>
    </div>
</div>

<?php if (mysqli_num_rows($senarai) === 0) { ?>
    <div class="card">Tiada temu janji dalam status ini.</div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
    <div class="card">
        <div class="card-title">
            <?= selamat($row['nama_pesakit'] ?: 'Pesakit') ?>
            <span class="badge-status <?= kelas_status($row['status']) ?>"><?= selamat($row['status']) ?></span>
        </div>

        <div class="profile-item"><span class="info-label">Tarikh</span><span><?= selamat(date('d/m/Y', strtotime($row['tarikh_temu_janji']))) ?></span></div>
        <div class="profile-item"><span class="info-label">Masa</span><span><?= selamat($row['masa_temu_janji'] ? date('h:i A', strtotime($row['masa_temu_janji'])) : '-') ?></span></div>
        <div class="profile-item"><span class="info-label">Rawatan</span><span><?= selamat($row['jenis_rawatan']) ?></span></div>
        <div class="profile-item"><span class="info-label">Doktor</span><span><?= selamat($row['nama_doktor'] ?: 'Belum ditetapkan') ?></span></div>
        <div class="profile-item"><span class="info-label">Telefon</span><span><?= selamat($row['no_telefon'] ?: '-') ?></span></div>
        <?php if ($row['catatan']) { ?>
            <div class="profile-item"><span class="info-label">Catatan</span><span><?= selamat($row['catatan']) ?></span></div>
        <?php } ?>

        <?php if (!in_array($row['status'], ['Selesai', 'Dibatalkan'], true)) { ?>
            <hr class="pembahagi">

            <?php if (in_array($row['status'], ['Menunggu', 'Ditolak'], true)) { ?>
                <form method="post" class="date-group">
                    <?= csrf_field() ?>
                    <select name="id_doktor" class="form-control" style="max-width:220px">
                        <option value="">Pilih doktor</option>
                        <?php foreach ($senarai_doktor as $doktor) { ?>
                            <option value="<?= (int)$doktor['id_doktor'] ?>" <?= (int)$doktor['id_doktor'] === (int)$row['id_doktor'] ? 'selected' : '' ?>><?= selamat($doktor['nama_doktor']) ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn" type="submit" name="luluskan" value="<?= (int)$row['id_temu_janji'] ?>">Luluskan</button>
                </form>
            <?php } ?>

            <form method="post" class="date-group">
                <?= csrf_field() ?>
                <input type="text" name="sebab" class="form-control" placeholder="Sebab tolak" style="max-width:240px">
                <button class="btn btn-batal" type="submit" name="tolak" value="<?= (int)$row['id_temu_janji'] ?>" onclick="return confirm('Tolak temu janji ini?')">Tolak</button>
            </form>

            <form method="post" class="date-group">
                <?= csrf_field() ?>
                <input type="date" name="tarikh_baru" class="form-control" value="<?= selamat($row['tarikh_temu_janji']) ?>" style="max-width:180px">
                <input type="time" name="masa_baru" class="form-control" value="<?= selamat($row['masa_temu_janji']) ?>" style="max-width:150px">
                <button class="btn btn-outline" type="submit" name="tukar_slot" value="<?= (int)$row['id_temu_janji'] ?>">Tukar Slot</button>
            </form>
        <?php } ?>
    </div>
<?php } ?>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
