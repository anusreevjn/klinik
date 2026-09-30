<?php
require_once '../config.php';
require_once '../include/helpers.php';

guard($conn, 'pesakit', '../login.php');

header('Content-Type: application/json');

$tarikh = $_GET['tarikh'] ?? '';

if (!$tarikh) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT masa_temu_janji 
        FROM temu_janji 
        WHERE tarikh_temu_janji='$tarikh' 
        AND status!='Dibatalkan'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit();
}

$booked = [];

while ($row = mysqli_fetch_assoc($result)) {
    $booked[] = $row['masa_temu_janji'];
}

echo json_encode($booked);
?>