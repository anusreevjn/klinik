<?php
require_once '../config.php';
require_once '../include/layout.php';

guard($conn, 'kakitangan');

$carian = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT i.*, p.jumlah_bayaran, p.kaedah_bayaran, p.status_pembayaran, p.id_pembayaran,
               ps.nama_pesakit, r.nama_rawatan
        FROM invois i
        LEFT JOIN pembayaran p ON p.id_pembayaran = i.id_pembayaran
        LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
        LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan";
if ($carian !== '') {
    $sql .= " WHERE ps.nama_pesakit LIKE ? OR i.no_invois LIKE ? OR i.no_resit LIKE ?";
}
$sql .= " ORDER BY i.tarikh_jana DESC LIMIT 200";

$stmt = mysqli_prepare($conn, $sql);
if ($carian !== '') {
    $corak = '%' . $carian . '%';
    mysqli_stmt_bind_param($stmt, "sss", $corak, $corak, $corak);
}
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

mula_halaman($conn, 'Invois & Resit', 'kakitangan', 'invois.php');
?>

<div class="card">
    <form method="get" class="date-group">
        <input type="text" name="q" class="form-control" placeholder="Cari nama pesakit, no invois atau no resit" value="<?= selamat($carian) ?>">
        <button class="btn" type="submit">Cari</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>Tarikh</th><th>No Invois</th><th>No Resit</th><th>Pesakit</th><th>Rawatan</th><th>Jumlah</th><th>Kaedah</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="9"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada invois dijumpai.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat(date('d/m/Y h:i A', strtotime($row['tarikh_jana']))) ?></td>
                <td><?= selamat($row['no_invois'] ?: '-') ?></td>
                <td><?= selamat($row['no_resit'] ?: '-') ?></td>
                <td><?= selamat($row['nama_pesakit'] ?: '-') ?></td>
                <td><?= selamat($row['nama_rawatan'] ?: '-') ?></td>
                <td><?= wang($row['jumlah_invois']) ?></td>
                <td><?= selamat($row['kaedah_bayaran'] ?: '-') ?></td>
                <td><span class="badge-status <?= kelas_status($row['status_invois']) ?>"><?= selamat($row['status_invois']) ?></span></td>
                <td><a class="action-btn" target="_blank" href="resit.php?id=<?= (int)$row['id_pembayaran'] ?>">Cetak</a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
