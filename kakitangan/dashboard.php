<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'kakitangan', '../staff_login.php');

// SECURITY CHECK (Buka komen bila dah sedia)

$id_user = (int)$_SESSION['user_id'];
$stmt_user = mysqli_prepare($conn, "SELECT * FROM kakitangan WHERE id_kakitangan = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_user, "i", $id_user);
mysqli_stmt_execute($stmt_user);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
mysqli_stmt_close($stmt_user);

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT temu_janji.*, pesakit.nama_pesakit 
        FROM temu_janji 
        LEFT JOIN pesakit ON temu_janji.id_pesakit = pesakit.id_pesakit 
        WHERE temu_janji.tarikh_temu_janji >= CURDATE()";

if (!empty($search)) {
    $sql .= " AND pesakit.nama_pesakit LIKE '%$search%'";
}

$sql .= " ORDER BY temu_janji.tarikh_temu_janji ASC, temu_janji.masa_temu_janji ASC";
$result = mysqli_query($conn, $sql);
?>
<?php mula_halaman($conn, 'Papan Utama', 'kakitangan', 'dashboard.php'); ?>

<style>
    .notif-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999}
    .notif-content{background:var(--c-surface);width:min(450px,92vw);margin:15vh auto;padding:30px;border-radius:12px;text-align:center;box-shadow:var(--shadow-lg);border-top:8px solid var(--c-primary)}
</style>

<div class="card">
    <h3 class="card-title">Senarai Temu Janji</h3>

    <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama pesakit..." value="<?= e($search) ?>" style="max-width:280px;">
        <button type="submit" class="btn">Cari</button>
    </form>

    <?php if (!$result || mysqli_num_rows($result) == 0): ?>
        <p style="color:var(--c-muted)">Tiada rekod temu janji ditemui.</p>
    <?php else: ?>
        <?php
        $current_date = '';
        while($row = mysqli_fetch_assoc($result)) {
            if($row['tarikh_temu_janji'] !== $current_date) {
                $current_date = $row['tarikh_temu_janji'];
                echo "<h4 style='margin:18px 0 8px;border-bottom:1px solid var(--c-border);padding-bottom:5px;'>Tarikh: " . e(date("d-m-Y", strtotime($current_date))) . "</h4>";
            }
        ?>
            <div class="history-card" style="margin-bottom:10px;">
                <h3 style="margin-top:0;"><?= e($row['nama_pesakit'] ?? 'Pesakit tidak diketahui') ?></h3>
                <p style="margin:5px 0;"><b>Masa:</b> <?= e(substr($row['masa_temu_janji'],0,5)) ?> | <b>No Giliran:</b> <?= e($row['no_giliran']) ?></p>
                <p style="margin:5px 0;"><b>Jenis:</b> <?= e($row['jenis_rawatan']) ?></p>
                <p style="margin:5px 0 12px;"><b>Status:</b> <span class="badge-status <?= e(kelas_status($row['status'])) ?>"><?= e($row['status']) ?></span></p>

                <div style="border-top:1px solid var(--c-border);padding-top:12px;">
                    <?php if(in_array($row['status'], ['Menunggu', 'Disahkan'])): ?>
                        <span style="color:var(--c-warning-fg,#92400e);font-weight:600;">Menunggu Panggilan Doktor</span>
                    <?php elseif($row['status'] == 'Dipanggil'): ?>
                        <span style="color:var(--c-primary);font-weight:600;">Sedang Dirawat di Bilik Doktor</span>
                    <?php elseif($row['status'] == 'Selesai'): ?>
                        <a href="pembayaran.php?id_t=<?= (int)$row['id_temu_janji'] ?>" class="btn">Bayar &amp; Ambil Ubat</a>
                    <?php elseif($row['status'] == 'Telah Dibayar'): ?>
                        <span class="badge-status selesai">Rawatan &amp; Pembayaran Selesai</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
    <?php endif; ?>
</div>

<div id="panggilanModal" class="notif-modal">
    <div class="notif-content">
        <span style="font-size: 50px;">🔔</span>
        <h2 style="color: #1f2937; margin-top: 10px;">Panggilan Doktor!</h2>
        <p style="font-size: 16px; color: #4b5563; margin: 15px 0;">
            Sila panggil No. Giliran <b id="notifNo" style="color:#4f46e5; font-size:18px;"></b>:<br>
            <span id="notifNama" style="font-weight: bold; font-size: 20px; color: #111827; text-transform: uppercase;"></span>
        </p>
        <p style="font-size: 13px; color: var(--c-muted);">Doktor sedang menunggu pesakit ini di dalam bilik.</p>
        <button class="btn-login" id="btnSelesaiPanggil" style="margin-top:15px;">Selesai Panggil Pesakit</button>
    </div>
</div>

<audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-120.wav" preload="auto"></audio>

<script>
let activeCallId = null;

// Fungsi semak panggilan doktor setiap 5 saat (AJAX Polling)
async function semakPanggilanDoktor() {
    try {
        const response = await fetch('check_call.php');
        const data = await response.json();

        if (data.ada_panggilan) {
            activeCallId = data.id_temu_janji;
            document.getElementById('notifNo').innerText = data.no_giliran;
            document.getElementById('notifNama').innerText = data.nama_pesakit;
            
            // Papar modal & bunyikan alert
            document.getElementById('panggilanModal').style.display = "block";
            document.getElementById('notifSound').play().catch(e => console.log("Audio play blocked"));
        }
    } catch (error) {
        console.error("Gagal menyemak panggilan:", error);
    }
}

// Tindakan apabila staf menekan butang "Selesai Panggil Pesakit"
document.getElementById('btnSelesaiPanggil').addEventListener('click', async function() {
    if (!activeCallId) return;

    const formData = new FormData();
    formData.append('id_temu_janji', activeCallId);

    try {
        const response = await fetch('dismiss_call.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.status === 'success') {
            // Tutup modal & refresh halaman untuk kemas kini senarai paparan terkini
            document.getElementById('panggilanModal').style.display = "none";
            window.location.reload();
        }
    } catch (error) {
        console.error("Gagal mengemaskini status notifikasi:", error);
    }
});

// Set pemasa semakan automatik setiap 5000ms (5 saat)
setInterval(semakPanggilanDoktor, 5000);
</script>

<?php tamat_halaman(); ?>