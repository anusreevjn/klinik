<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/pembayaran_lib.php';

$id_kakitangan = guard($conn, 'kakitangan');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['sahkan'])) {
    $id_pembayaran = (int)$_POST['id_pembayaran'];
    $keterangan = null;
    if (jelaskan_pembayaran($conn, $id_pembayaran, $id_kakitangan, $keterangan)) {
        $mesej = $keterangan;
    } else {
        $mesej = $keterangan;
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['tolak'])) {
    $id_pembayaran = (int)$_POST['id_pembayaran'];
    $sebab = trim($_POST['sebab']) !== '' ? trim($_POST['sebab']) : 'Bukti tidak jelas';
    if (tolak_pembayaran($conn, $id_pembayaran, $id_kakitangan, $sebab)) {
        $mesej = 'Bukti pembayaran ditolak dan pesakit telah dimaklumkan.';
        $jenis_mesej = 'warning';
    } else {
        $mesej = 'Gagal tolak bukti pembayaran.';
        $jenis_mesej = 'error';
    }
}

$sql = "SELECT p.*, ps.nama_pesakit, ps.no_telefon, r.nama_rawatan
        FROM pembayaran p
        LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan
        WHERE p.status_pembayaran = 'Menunggu Pengesahan'
        ORDER BY p.tarikh_bayaran ASC";
$menunggu = mysqli_query($conn, $sql);

$sejarah = mysqli_query($conn, "SELECT p.*, ps.nama_pesakit, r.nama_rawatan
        FROM pembayaran p
        LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan
        WHERE p.status_pembayaran IN ('Selesai', 'Ditolak') AND p.bukti_pembayaran IS NOT NULL
        ORDER BY p.tarikh_sah DESC LIMIT 20");

mula_halaman($conn, 'Pengesahan Bayaran Online', 'kakitangan', 'pengesahan.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="dashboard-card">
    <p>Menunggu Pengesahan</p>
    <h2><?= mysqli_num_rows($menunggu) ?></h2>
</div>

<?php if (mysqli_num_rows($menunggu) === 0) { ?>
    <div class="card">Tiada bukti pembayaran menunggu semakan.</div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($menunggu)) { ?>
    <div class="card">
        <div class="card-title"><?= selamat($row['nama_pesakit'] ?: 'Pesakit') ?> <span class="badge-status menunggu-pengesahan">Menunggu Pengesahan</span></div>
        <div class="profile-item"><span class="info-label">Rawatan</span><span><?= selamat($row['nama_rawatan'] ?: '-') ?></span></div>
        <div class="profile-item"><span class="info-label">Jumlah</span><span><?= wang($row['jumlah_bayaran']) ?></span></div>
        <div class="profile-item"><span class="info-label">Kaedah</span><span><?= selamat($row['kaedah_bayaran']) ?></span></div>
        <div class="profile-item"><span class="info-label">No Rujukan</span><span><?= selamat($row['rujukan_bayaran'] ?: '-') ?></span></div>
        <div class="profile-item"><span class="info-label">Telefon</span><span><?= selamat($row['no_telefon'] ?: '-') ?></span></div>
        <div class="profile-item"><span class="info-label">Dihantar</span><span><?= selamat(date('d/m/Y h:i A', strtotime($row['tarikh_bayaran']))) ?></span></div>

        <?php if ($row['bukti_pembayaran']) {
            $ext = strtolower(pathinfo($row['bukti_pembayaran'], PATHINFO_EXTENSION));
        ?>
            <p><a class="btn btn-outline" target="_blank" href="../<?= selamat($row['bukti_pembayaran']) ?>">Buka Bukti Pembayaran</a></p>
            <?php if ($ext !== 'pdf') { ?>
                <img src="../<?= selamat($row['bukti_pembayaran']) ?>" alt="Bukti pembayaran" style="max-width:320px;border-radius:14px">
            <?php } ?>
        <?php } ?>

        <form method="post" class="date-group" style="margin-top:16px"><?= csrf_field() ?>
            <input type="hidden" name="id_pembayaran" value="<?= (int)$row['id_pembayaran'] ?>">
            <input type="text" name="sebab" class="form-control" placeholder="Sebab jika ditolak">
            <button class="btn" type="submit" name="sahkan">Sahkan Bayaran</button>
            <button class="btn btn-batal" type="submit" name="tolak" onclick="return confirm('Tolak bukti pembayaran ini?')">Tolak</button>
        </form>
    </div>
<?php } ?>

<div class="card">
    <div class="card-title">Sejarah Pengesahan Terkini</div>
    <div class="table-container">
        <table>
            <thead><tr><th>Tarikh Sah</th><th>Pesakit</th><th>Rawatan</th><th>Jumlah</th><th>Kaedah</th><th>Status</th><th>Catatan</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($sejarah)) { ?>
                <tr>
                    <td><?= selamat($row['tarikh_sah'] ? date('d/m/Y h:i A', strtotime($row['tarikh_sah'])) : '-') ?></td>
                    <td><?= selamat($row['nama_pesakit'] ?: '-') ?></td>
                    <td><?= selamat($row['nama_rawatan'] ?: '-') ?></td>
                    <td><?= wang($row['jumlah_bayaran']) ?></td>
                    <td><?= selamat($row['kaedah_bayaran']) ?></td>
                    <td><span class="badge-status <?= kelas_status($row['status_pembayaran']) ?>"><?= selamat($row['status_pembayaran']) ?></span></td>
                    <td><?= selamat($row['catatan_bayaran'] ?: '-') ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php tamat_halaman();
