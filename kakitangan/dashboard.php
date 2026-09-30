<?php
require_once '../config.php';
require_once '../include/helpers.php';

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
<!DOCTYPE html>
<html lang="ms">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kakitangan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    <style>
        .dashboard { display: flex; min-height: 100vh; }
        .main { flex: 1; padding: 20px; background: #f4f7f6; }
        .card { background: white; padding: 15px; margin-bottom: 10px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .btn { display: inline-block; padding: 6px 10px; margin-right: 5px; background: #0f766e; color: white; border-radius: 6px; text-decoration: none; font-size: 14px;}
        .btn:hover { background: #0c5e56; }
        .btn-bayar { background: #eab308; color: black; font-weight:bold; }
        .btn-bayar:hover { background: #ca8a04; color: white;}

        .search-box { margin-bottom: 20px; }
        .status { font-weight: bold; }
        
        .menunggu { color: #f59e0b; } 
        .disahkan { color: #3b82f6; } 
        .dipanggil { color: #a855f7; } 
        .selesai { color: #10b981; } 
        .telah { color: #16a34a; } 

        /* CSS BARU UNTUK MODAL NOTIFIKASI */
        .notif-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; }
        .notif-content { background: white; width: 450px; margin: 15vh auto; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2); border-top: 8px solid #4f46e5; }
        .btn-notif-action { background: #4f46e5; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 15px; width: 100%; }
        .btn-notif-action:hover { background: #4338ca; }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/image/logo.jpg" alt="Logo">
            <h2>Klinik Dr Arifin</h2>
        </div>
        <p style="font-size:13px; opacity:0.9; text-align:center; margin-bottom:10px;">
            Portal Kakitangan<br>
            <b><?= $user['nama_kakitangan'] ?? 'Staff' ?></b>
        </p>
        <a href="dashboard.php" class="active"> Papan Pemuka</a>
        <a href="pembayaran.php"> Pembayaran</a>
        <a href="pembayaran.php"> Invois</a>
        <a href="profil.php"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>
    </div>

    <div class="main">
        <h2>Senarai Temu Janji</h2>
        
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Cari nama pesakit..." value="<?= htmlspecialchars($search) ?>" style="padding:8px; width:250px; border-radius:6px; border:1px solid #ccc;">
                <button type="submit" class="btn">Cari</button>
            </form>
        </div>

        <?php if (!$result || mysqli_num_rows($result) == 0): ?>
            <div style="background:white; padding:20px; text-align:center; border-radius:8px; color:#666;">
                Tiada rekod temu janji ditemui.
            </div>
        <?php else: ?>
            <?php 
            $current_date = '';
            while($row = mysqli_fetch_assoc($result)) { 
                if($row['tarikh_temu_janji'] !== $current_date) {
                    $current_date = $row['tarikh_temu_janji'];
                    echo "<h3 style='margin-top:20px; color:#333; border-bottom:2px solid #ddd; padding-bottom:5px;'>Tarikh: " . date("d-m-Y", strtotime($current_date)) . "</h3>";
                }
                
                $status_class = strtolower($row['status']);
                if($row['status'] == 'Telah Dibayar') $status_class = 'telah';
            ?>
                <div class="card">
                    <h3 style="margin-top:0; color:#1f2937;"><?= htmlspecialchars($row['nama_pesakit'] ?? 'Pesakit tidak diketahui') ?></h3>
                    <p style="margin:5px 0; color:#4b5563;"><b>Masa:</b> <?= substr($row['masa_temu_janji'],0,5) ?> | <b>No Giliran:</b> <?= $row['no_giliran'] ?></p>
                    <p style="margin:5px 0; color:#4b5563;"><b>Jenis:</b> <?= $row['jenis_rawatan'] ?></p>
                    <p style="margin:5px 0; margin-bottom:15px;"><b>Status:</b> 
                        <span class="status <?= $status_class ?>"><?= $row['status'] ?></span>
                    </p>

                    <div style="border-top: 1px solid #eee; padding-top: 12px;">
                        <?php if(in_array($row['status'], ['Menunggu', 'Disahkan'])): ?>
                            <span style="color: #f59e0b; font-weight: bold; font-size:14px;">⏳ Menunggu Panggilan Doktor</span>
                            
                        <?php elseif($row['status'] == 'Dipanggil'): ?>
                            <span style="color: #6b21a8; font-weight: bold; font-size:14px;">👨‍⚕️ Sedang Dirawat di Bilik Doktor</span>
                            
                        <?php elseif($row['status'] == 'Selesai'): ?>
                            <a href="pembayaran.php?id_t=<?= $row['id_temu_janji'] ?>" class="btn btn-bayar">💰 Bayar & Ambil Ubat</a>
                            
                        <?php elseif($row['status'] == 'Telah Dibayar'): ?>
                            <span style="color: #16a34a; font-weight: bold; font-size:14px;">✅ Rawatan & Pembayaran Selesai</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
        <?php endif; ?>
    </div>
</div>

<div id="panggilanModal" class="notif-modal">
    <div class="notif-content">
        <span style="font-size: 50px;">🔔</span>
        <h2 style="color: #1f2937; margin-top: 10px;">Panggilan Doktor!</h2>
        <p style="font-size: 16px; color: #4b5563; margin: 15px 0;">
            Sila panggil No. Giliran <b id="notifNo" style="color:#4f46e5; font-size:18px;"></b>:<br>
            <span id="notifNama" style="font-weight: bold; font-size: 20px; color: #111827; text-transform: uppercase;"></span>
        </p>
        <p style="font-size: 13px; color: #9ca3af;">Doktor sedang menunggu pesakit ini di dalam bilik.</p>
        <button class="btn-notif-action" id="btnSelesaiPanggil"> Selesai Panggil Pesakit</button>
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

<script src="../assets/js/ui.js" defer></script>
</body>
</html>