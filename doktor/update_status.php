<?php
require_once '../config.php';
require_once '../include/giliran_lib.php';

guard($conn, ['doktor', 'kakitangan'], '../staff_login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Tindakan ini perlu dihantar melalui borang.');
}

$id_temu_janji = isset($_POST['id_temu_janji']) ? (int)$_POST['id_temu_janji'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!tukar_status_temu_janji($conn, $id_temu_janji, $status)) {
    header('Location: dashboard.php?status=gagal');
    exit();
}

header('Location: dashboard.php?status=ok');
exit();
