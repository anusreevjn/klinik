<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/laporan_data.php';

$id_pentadbir = guard($conn, 'pentadbir', '../staff_login.php');

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'Laporan Kewangan';
$senarai_jenis = jenis_laporan_tersedia();
if (!array_key_exists($jenis, $senarai_jenis)) {
    $jenis = 'Laporan Kewangan';
}

$dari = isset($_GET['dari']) && $_GET['dari'] !== '' ? $_GET['dari'] : date('Y-m-01');
$hingga = isset($_GET['hingga']) && $_GET['hingga'] !== '' ? $_GET['hingga'] : date('Y-m-d');
$mesej = '';

if (isset($_POST['jana'])) {
    $jenis_simpan = $_POST['jenis_laporan'];
    $bulan = date('Y-m', strtotime($_POST['dari']));
    $stmt = mysqli_prepare($conn, "INSERT INTO laporan (id_pentadbir, id_inventori, id_temu_janji, jenis_laporan, bulan_rujukan, tarikh_dijana) VALUES (?, NULL, NULL, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iss", $id_pentadbir, $jenis_simpan, $bulan);
    $mesej = mysqli_stmt_execute($stmt) ? 'Laporan direkodkan dalam log.' : 'Gagal rekod laporan.';
    mysqli_stmt_close($stmt);
    $jenis = $jenis_simpan;
    $dari = $_POST['dari'];
    $hingga = $_POST['hingga'];
}

$data = data_laporan($conn, $jenis, $dari, $hingga);
$log = mysqli_query($conn, "SELECT l.*, p.nama_pentadbir FROM laporan l LEFT JOIN pentadbir p ON p.id_pentadbir = l.id_pentadbir ORDER BY l.tarikh_dijana DESC LIMIT 15");

$cetak = 'cetak_laporan.php?jenis=' . urlencode($jenis) . '&dari=' . urlencode($dari) . '&hingga=' . urlencode($hingga);
mula_halaman($conn, 'Laporan', 'pentadbir', 'laporan.php', '<a class="btn btn-pdf" target="_blank" href="' . selamat($cetak) . '">Cetak / Simpan PDF</a>');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert success"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <form method="post"><?= csrf_field() ?>
        <div class="form-group">
            <label>Jenis Laporan</label>
            <select name="jenis_laporan" class="form-control" onchange="this.form.submit()">
                <?php foreach ($senarai_jenis as $nama => $huraian) { ?>
                    <option value="<?= selamat($nama) ?>" <?= $nama === $jenis ? 'selected' : '' ?>><?= selamat($nama) ?></option>
                <?php } ?>
            </select>
            <p><?= selamat($senarai_jenis[$jenis]) ?></p>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Dari</label>
                <input type="date" name="dari" class="form-control" value="<?= selamat($dari) ?>">
            </div>
            <div class="form-group">
                <label>Hingga</label>
                <input type="date" name="hingga" class="form-control" value="<?= selamat($hingga) ?>">
            </div>
        </div>
        <button class="btn" type="submit" name="jana">Jana Laporan</button>
    </form>
</div>

<?php foreach ($data['ringkasan'] as $label => $nilai) { ?>
    <div class="dashboard-card">
        <p><?= selamat($label) ?></p>
        <h2><?= selamat($nilai) ?></h2>
    </div>
<?php } ?>

<div class="table-container">
    <table>
        <thead>
            <tr><?php foreach ($data['kepala'] as $kepala) { ?><th><?= selamat($kepala) ?></th><?php } ?></tr>
        </thead>
        <tbody>
        <?php if (count($data['baris']) === 0) { ?>
            <tr><td colspan="<?= max(1, count($data['kepala'])) ?>">Tiada data untuk tempoh ini.</td></tr>
        <?php } ?>
        <?php foreach ($data['baris'] as $baris) { ?>
            <tr><?php foreach ($baris as $sel) { ?><td><?= selamat($sel) ?></td><?php } ?></tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-title">Log Laporan Terdahulu</div>
    <div class="table-container">
        <table>
            <thead><tr><th>Jenis</th><th>Bulan Rujukan</th><th>Dijana Oleh</th><th>Tarikh</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($log)) { ?>
                <tr>
                    <td><?= selamat($row['jenis_laporan']) ?></td>
                    <td><?= selamat($row['bulan_rujukan']) ?></td>
                    <td><?= selamat($row['nama_pentadbir']) ?></td>
                    <td><?= selamat(date('d/m/Y H:i', strtotime($row['tarikh_dijana']))) ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php tamat_halaman();
