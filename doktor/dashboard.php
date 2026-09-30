<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/giliran_lib.php';

guard($conn, 'doktor', '../staff_login.php');

// SECURITY CHECK

// UPDATE STATUS CALL

// UPDATE STATUS DONE

// QUERY UNTUK DEBUGGING
$sql = "SELECT t.*, p.nama_pesakit, p.id_pesakit
        FROM temu_janji t
        JOIN pesakit p ON t.id_pesakit = p.id_pesakit
        WHERE t.tarikh_temu_janji = CURDATE()
        AND t.status IN ('Menunggu', 'Disahkan', 'Dipanggil') 
        ORDER BY t.masa_temu_janji ASC";

$result = mysqli_query($conn, $sql);

// Tambah ini untuk tengok kalau ada ralat SQL
if (!$result) {
    die("Ralat Database: " . mysqli_error($conn));
}
?>

<?php mula_halaman($conn, 'Papan Utama', 'doktor', 'dashboard.php'); ?>

<div class="card">
    <h3 class="card-title">Senarai Pesakit Hari Ini</h3>

    <?php if (!$result || mysqli_num_rows($result) == 0): ?>
        <p style="color:var(--c-muted)">Tiada pesakit dalam senarai menunggu.</p>
    <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="history-card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <span style="font-weight:700;text-transform:uppercase;"><?= e($row['nama_pesakit']) ?></span>
                    <span style="font-size:13px;color:var(--c-muted);">Masa Temu Janji: <b><?= e(substr($row['masa_temu_janji'],0,5)) ?></b></span>
                    <span class="badge-status <?= e(kelas_status($row['status'])) ?>" style="width:fit-content;"><?= e($row['status']) ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <?php if ($row['status'] == 'Selesai'): ?>
                        <span class="badge-status selesai">Rawatan Selesai</span>
                    <?php else: ?>
                        <form method="post" action="complete_queue.php" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id_temu_janji" value="<?= (int)$row['id_temu_janji'] ?>"><button type="submit" class="btn btn-back">Selesai</button></form>
                        <?php if ($row['status'] == 'Dipanggil'): ?>
                            <a href="rekod_rawatan.php?id_t=<?= (int)$row['id_temu_janji'] ?>&id_p=<?= (int)$row['id_pesakit'] ?>" target="_blank" class="btn">Buka Rekod Rawatan</a>
                        <?php else: ?>
                            <form method="post" action="call_queue.php" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id_temu_janji" value="<?= (int)$row['id_temu_janji'] ?>"><button type="submit" class="btn">Panggil Masuk</button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
    <?php endif; ?>
</div>

<script>setTimeout(function(){ location.reload(); }, 30000);</script>

<?php tamat_halaman(); ?>

