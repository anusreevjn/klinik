<?php
require_once '../config.php';
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

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka</title>
    <meta http-equiv="refresh" content="30">
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR (ikut CSS kau) -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/image/logo.jpg" alt="Logo">
            <h2>Klinik Dr Arifin</h2>
        </div>
        <p style="font-size:13px; opacity:0.9; text-align:center; margin-bottom:10px;">
        Portal Doktor<br>
        <b><?= $user['nama_doktor'] ?? 'doktor' ?></b>
    </p>

        <a href="dashboard.php" class="active"> Dashboard</a>
        <a href="dashboard.php"> Senarai Pesakit</a>
        <a href="rekod_rawatan.php"> Rekod Rawatan</a>
        <a href="sejarah_rawatan.php"> Sejarah Rawatan</a>
        <a href="profil.php"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>
    </div>

    <!-- MAIN -->
<div class="main" style="flex: 1; padding: 20px; width: 100%; box-sizing: border-box; background-color: #f4f6f9; min-height: 100vh;">

        <div class="card" style="width: 100%; box-sizing: border-box; background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #111827;">Papan Pemuka</h3>
        </div>

        <div class="card" style="width: 100%; box-sizing: border-box; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #111827;">Senarai Pesakit Hari Ini</h3>

            <?php if (!$result || mysqli_num_rows($result) == 0): ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; color: #6c757d; border: 1px solid #e5e7eb;">
                    Tiada pesakit dalam senarai menunggu.
                </div>
            <?php else: ?>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            
                            <span style="font-size: 16px; font-weight: bold; color: #1f2937; text-transform: uppercase;">
                                <?= htmlspecialchars($row['nama_pesakit']) ?>
                            </span>
                            
                            <span style="font-size: 13px; color: #6b7280;">
                                Masa Temu Janji: <b><?= substr($row['masa_temu_janji'],0,5) ?></b>
                            </span>
                            
                            <span style="font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; width: fit-content; 
                                <?= $row['status'] == 'Dipanggil' ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #fef3c7; color: #92400e;' ?>">
                                <?= $row['status'] == 'Dipanggil' ? '🩺 Sedang Diperiksa' : '⏳ ' . $row['status'] ?>
                            </span>

                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            
                            <?php if ($row['status'] == 'Selesai'): ?>
                                <span style="font-size: 13px; font-weight: bold; color: #16a34a; background: #dcfce7; padding: 8px 16px; border-radius: 6px;">
                                    ✅ Rawatan Selesai
                                </span>
                            <?php else: ?>
                                <form method="post" action="complete_queue.php" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id_temu_janji" value="<?= (int)$row['id_temu_janji'] ?>"><button type="submit" class="btn btn-back">Selesai</button></form>

                                <?php if ($row['status'] == 'Dipanggil'): ?>
                                    <a href="rekod_rawatan.php?id_t=<?= $row['id_temu_janji'] ?>&id_p=<?= $row['id_pesakit'] ?>" target="_blank" style="text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; color: white; background-color: #0d9488; border: 1px solid #0d9488; cursor: pointer;">
                                        Buka Rekod Rawatan
                                    </a>
                                <?php else: ?>
                                    <form method="post" action="call_queue.php" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id_temu_janji" value="<?= (int)$row['id_temu_janji'] ?>"><button type="submit" class="btn">Panggil Masuk</button></form>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php } ?>

            <?php endif; ?>

        </div>

    </div>
</div>
<script src="../assets/js/ui.js" defer></script>
</body>
</html>

