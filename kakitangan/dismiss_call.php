<?php
require_once '../config.php';
require_once '../include/helpers.php';

guard($conn, 'kakitangan', '../staff_login.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed']);
    exit();
}

$id_temu_janji = isset($_POST['id_temu_janji']) ? (int)$_POST['id_temu_janji'] : 0;

if ($id_temu_janji <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'invalid']);
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE temu_janji SET notifikasi_staf = 1 WHERE id_temu_janji = ?");
mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['status' => 'success']);
