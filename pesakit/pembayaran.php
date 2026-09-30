<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';
require_once '../include/penjaga_lib.php';

guard($conn, 'pesakit', '../login.php');

// SECURITY CHECK: Pastikan pesakit login

$id_akaun = (int)$_SESSION['user_id'];
$id_pesakit = id_profil_aktif($conn, $id_akaun);
$profil_semasa = profil_pesakit($conn, $id_pesakit);

// QUERY: Ambil semua rekod pembayaran pesakit 
$sql = "SELECT p.*, r.nama_rawatan, r.harga_rawatan, i.no_resit, i.tarikh_jana
        FROM pembayaran p
        JOIN rekod_rawatan r ON p.id_rawatan = r.id_rawatan
        JOIN invois i ON p.id_pembayaran = i.id_pembayaran
        WHERE p.id_pesakit = ?
        ORDER BY i.tarikh_jana DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pesakit);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php mula_halaman($conn, 'Invois & Resit', 'pesakit', 'pembayaran.php'); ?>

<?= pemilih_profil($conn, $id_akaun, $id_pesakit, 'pembayaran.php') ?>

<div class="table-container">
    <h3 class="card-title">Rekod Pembayaran</h3>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Tarikh</th>
                    <th>Rawatan</th>
                    <th>Kaedah</th>
                    <th>Jumlah (RM)</th>
                    <th>No Resit</th>
                    <th>Status</th>
                    <th>Resit</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= e(date("d/m/Y", strtotime($row['tarikh_jana']))) ?></td>
                        <td><?= e($row['nama_rawatan']) ?></td>
                        <td><?= e($row['kaedah_bayaran']) ?></td>
                        <td><?= e(number_format($row['jumlah_bayaran'], 2)) ?></td>
                        <td><?= e($row['no_resit']) ?></td>
                        <td><span class="badge-status <?= e(kelas_status($row['status_pembayaran'])) ?>"><?= e($row['status_pembayaran']) ?></span></td>
                        <td><a class="action-btn" target="_blank" href="../kakitangan/resit.php?id=<?= (int)$row['id_pembayaran'] ?>">Cetak</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:var(--c-muted)">Tiada rekod pembayaran dijumpai.</p>
    <?php endif; ?>
</div>

<?php tamat_halaman(); ?>