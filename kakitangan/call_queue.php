<?php
require_once '../config.php';
require_once '../include/giliran_lib.php';

guard($conn, ['doktor', 'kakitangan'], '../staff_login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Tindakan ini perlu dihantar melalui borang.');
}

$id_temu_janji = isset($_POST['id_temu_janji']) ? (int)$_POST['id_temu_janji'] : 0;
$berjaya = panggil_giliran($conn, $id_temu_janji);

header('Location: dashboard.php?giliran=' . ($berjaya ? 'ok' : 'gagal'));
exit();
