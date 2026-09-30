<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_pentadbir = guard($conn, 'pentadbir', '../staff_login.php');

$dari = isset($_GET['dari']) && $_GET['dari'] !== '' ? $_GET['dari'] : date('Y-m-01');
$hingga = isset($_GET['hingga']) && $_GET['hingga'] !== '' ? $_GET['hingga'] : date('Y-m-d');

$sql = "SELECT p.*, i.no_invois, i.no_resit, i.status_invois, i.tarikh_jana,
               ps.nama_pesakit, k.nama_kakitangan, r.nama_rawatan, r.harga_rawatan
        FROM pembayaran p
        LEFT JOIN invois i ON i.id_pembayaran = p.id_pembayaran
        LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
        LEFT JOIN kakitangan k ON k.id_kakitangan = p.id_kakitangan
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan
        WHERE DATE(p.tarikh_bayaran) BETWEEN ? AND ?
        ORDER BY p.tarikh_bayaran DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$sql_ringkas = "SELECT COUNT(*) AS bil,
                       SUM(CASE WHEN status_pembayaran = 'Selesai' THEN jumlah_bayaran ELSE 0 END) AS terkumpul,
                       SUM(CASE WHEN status_pembayaran = 'Belum Bayar' THEN jumlah_bayaran ELSE 0 END) AS tertunggak
                FROM pembayaran WHERE DATE(tarikh_bayaran) BETWEEN ? AND ?";
$stmt2 = mysqli_prepare($conn, $sql_ringkas);
mysqli_stmt_bind_param($stmt2, "ss", $dari, $hingga);
mysqli_stmt_execute($stmt2);
$ringkasan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

mula_halaman($conn, 'Pengurusan Pembayaran', 'pentadbir', 'pembayaran.php');
?>

<div class="card">
    <form method="get" class="date-group">
        <div class="form-group">
            <label>Dari</label>
            <input type="date" name="dari" class="form-control" value="<?= selamat($dari) ?>">
        </div>
        <div class="form-group">
            <label>Hingga</label>
            <input type="date" name="hingga" class="form-control" value="<?= selamat($hingga) ?>">
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button class="btn" type="submit">Semak</button>
        </div>
    </form>
</div>

<div class="dashboard-card">
    <p>Jumlah Transaksi</p>
    <h2><?= (int)$ringkasan['bil'] ?></h2>
</div>

<div class="dashboard-card">
    <p>Kutipan Selesai</p>
    <h2><?= wang($ringkasan['terkumpul']) ?></h2>
</div>

<div class="dashboard-card">
    <p>Belum Bayar</p>
    <h2><?= wang($ringkasan['tertunggak']) ?></h2>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Tarikh</th>
                <th>No Invois</th>
                <th>No Resit</th>
                <th>Pesakit</th>
                <th>Rawatan</th>
                <th>Kakitangan</th>
                <th>Kaedah</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Resit</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="10"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada transaksi dalam tempoh ini.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat(date('d/m/Y H:i', strtotime($row['tarikh_bayaran']))) ?></td>
                <td><?= selamat($row['no_invois'] ?: '-') ?></td>
                <td><?= selamat($row['no_resit'] ?: '-') ?></td>
                <td><?= selamat($row['nama_pesakit'] ?: '-') ?></td>
                <td><?= selamat($row['nama_rawatan'] ?: '-') ?></td>
                <td><?= selamat($row['nama_kakitangan'] ?: '-') ?></td>
                <td><?= selamat($row['kaedah_bayaran']) ?></td>
                <td><?= wang($row['jumlah_bayaran']) ?></td>
                <td><span class="badge-status <?= kelas_status($row['status_pembayaran']) ?>"><?= selamat($row['status_pembayaran']) ?></span></td>
                <td>
                    <?php if ($row['no_resit']) { ?>
                        <a class="action-btn" target="_blank" href="../kakitangan/resit.php?id=<?= (int)$row['id_pembayaran'] ?>">Lihat</a>
                    <?php } else { ?>
                        -
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php tamat_halaman();
