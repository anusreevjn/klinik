<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_doktor = guard($conn, 'doktor', '../staff_login.php');

$carian = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT p.*,
               (SELECT COUNT(*) FROM rekod_rawatan r WHERE r.id_pesakit = p.id_pesakit) AS bil_rawatan,
               (SELECT MAX(r.tarikh_rawatan) FROM rekod_rawatan r WHERE r.id_pesakit = p.id_pesakit) AS rawatan_akhir,
               (SELECT COUNT(*) FROM temu_janji t WHERE t.id_pesakit = p.id_pesakit AND t.tarikh_temu_janji = CURDATE()) AS tj_hari_ini
        FROM pesakit p";
if ($carian !== '') {
    $sql .= " WHERE p.nama_pesakit LIKE ? OR p.no_ic LIKE ?";
}
$sql .= " ORDER BY p.nama_pesakit ASC LIMIT 200";

$stmt = mysqli_prepare($conn, $sql);
if ($carian !== '') {
    $corak = '%' . $carian . '%';
    mysqli_stmt_bind_param($stmt, "ss", $corak, $corak);
}
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

mula_halaman($conn, 'Senarai Pesakit', 'doktor', 'pesakit.php');
?>

<div class="card">
    <form method="get" class="date-group">
        <input type="text" name="q" class="form-control" placeholder="Cari nama atau no IC pesakit" value="<?= selamat($carian) ?>">
        <button class="btn" type="submit">Cari</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>Nama</th><th>No IC</th><th>Umur</th><th>Penyakit Kronik</th><th>Rawatan</th><th>Rawatan Akhir</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="7"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada pesakit dijumpai.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) {
            $umur = $row['tarikh_lahir'] ? (int)((time() - strtotime($row['tarikh_lahir'])) / 31556952) : '-';
        ?>
            <tr>
                <td><?= selamat($row['nama_pesakit']) ?><?php if ((int)$row['tj_hari_ini'] > 0) { ?> <span class="badge-status dipanggil">Hari Ini</span><?php } ?></td>
                <td><?= selamat($row['no_ic']) ?></td>
                <td><?= selamat($umur) ?></td>
                <td><?= selamat($row['penyakit_kronik'] ?: ($row['penyakit'] ?: 'Tiada')) ?></td>
                <td><?= (int)$row['bil_rawatan'] ?></td>
                <td><?= selamat($row['rawatan_akhir'] ? date('d/m/Y', strtotime($row['rawatan_akhir'])) : '-') ?></td>
                <td>
                    <a class="action-btn" href="rekod_rawatan.php?id_p=<?= (int)$row['id_pesakit'] ?>">Rekod Rawatan</a>
                    <a class="action-btn btn-outline" href="sejarah_rawatan.php?id_p=<?= (int)$row['id_pesakit'] ?>">Sejarah</a>
                    <a class="action-btn btn-outline" target="_blank" href="../doktor/kad_rawatan.php?id_p=<?= (int)$row['id_pesakit'] ?>">Kad Rawatan</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
