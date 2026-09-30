<?php
require_once '../config.php';
require_once '../include/helpers.php';

guard($conn, 'kakitangan', '../staff_login.php');

// Cari jika ada temu janji hari ini yang berstatus 'Dipanggil' dan staf belum ambil tindakan (notifikasi_staf = 0)
$sql = "SELECT t.id_temu_janji, p.nama_pesakit, t.no_giliran 
        FROM temu_janji t 
        JOIN pesakit p ON t.id_pesakit = p.id_pesakit 
        WHERE t.tarikh_temu_janji = CURDATE() 
        AND t.status = 'Dipanggil' 
        AND t.notifikasi_staf = 0 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'ada_panggilan' => true,
        'id_temu_janji' => $row['id_temu_janji'],
        'nama_pesakit' => htmlspecialchars($row['nama_pesakit']),
        'no_giliran' => $row['no_giliran']
    ]);
} else {
    echo json_encode(['ada_panggilan' => false]);
}
?>