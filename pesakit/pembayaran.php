<?php
require_once '../config.php';
require_once '../include/helpers.php';
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

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekod Pembayaran Saya</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar">
        <h2>Klinik Dr Arifin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="appointment.php">Temu Janji</a>
        <a href="sejarah_rawatan.php">Sejarah</a>
        <a href="pembayaran.php" class="active">Invois &amp; Resit</a>
        <a href="notifikasi.php">Notifikasi</a>
        <a href="../logout.php">Log Keluar</a>
    </div>

    <div class="main">
<?= pemilih_profil($conn, $id_akaun, $id_pesakit, 'pembayaran.php') ?>

        <h2>Rekod Pembayaran</h2>
        <div class="table-container">
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
                                <td><?= date("d/m/Y", strtotime($row['tarikh_jana'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_rawatan']) ?></td>
                                <td><?= htmlspecialchars($row['kaedah_bayaran']) ?></td>
                                <td><?= number_format($row['jumlah_bayaran'], 2) ?></td>
                                <td><?= htmlspecialchars($row['no_resit']) ?></td>
                                <td><span class="badge-status <?= strtolower(str_replace(' ', '-', $row['status_pembayaran'])) ?>"><?= $row['status_pembayaran'] ?></span></td>
                                <td><a class="action-btn" target="_blank" href="../kakitangan/resit.php?id=<?= (int)$row['id_pembayaran'] ?>">Cetak</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Tiada rekod pembayaran dijumpai.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>