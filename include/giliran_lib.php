<?php

require_once __DIR__ . '/helpers.php';

function status_giliran_dibenarkan(): array
{
    return ['Menunggu', 'Disahkan', 'Ditolak', 'Dipanggil', 'Selesai', 'Tidak Hadir', 'Dibatalkan'];
}

function temu_janji_untuk_tindakan($conn, int $id_temu_janji): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT id_temu_janji, id_pesakit, id_doktor, status, no_giliran, tarikh_temu_janji FROM temu_janji WHERE id_temu_janji = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris ?: null;
}

function panggil_giliran($conn, int $id_temu_janji): bool
{
    $temu_janji = temu_janji_untuk_tindakan($conn, $id_temu_janji);

    if (!$temu_janji || !in_array($temu_janji['status'], ['Menunggu', 'Disahkan'], true)) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Dipanggil', notifikasi_staf = 0 WHERE id_temu_janji = ? AND status IN ('Menunggu', 'Disahkan')");
    mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
    mysqli_stmt_execute($stmt);
    $berjaya = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if (!$berjaya) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "UPDATE giliran SET status_giliran = 'Dipanggil', masa_panggil = NOW() WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    hantar_notifikasi($conn, 'pesakit', (int)$temu_janji['id_pesakit'], 'Giliran anda dipanggil', 'Nombor giliran ' . ($temu_janji['no_giliran'] ?: '-') . ' sedang dipanggil. Sila ke bilik rawatan.', 'giliran', 'dashboard.php');
    audit($conn, 'giliran_dipanggil', $id_temu_janji, 'no_giliran=' . $temu_janji['no_giliran']);

    return true;
}

function selesaikan_giliran($conn, int $id_temu_janji): bool
{
    $temu_janji = temu_janji_untuk_tindakan($conn, $id_temu_janji);

    if (!$temu_janji || in_array($temu_janji['status'], ['Selesai', 'Dibatalkan', 'Tidak Hadir'], true)) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Selesai' WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE giliran SET status_giliran = 'Selesai', masa_selesai = NOW() WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_temu_janji);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    audit($conn, 'giliran_selesai', $id_temu_janji, '');

    return true;
}

function tukar_status_temu_janji($conn, int $id_temu_janji, string $status): bool
{
    if (!in_array($status, status_giliran_dibenarkan(), true)) {
        return false;
    }

    $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = ? WHERE id_temu_janji = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id_temu_janji);
    mysqli_stmt_execute($stmt);
    $berjaya = mysqli_stmt_affected_rows($stmt) >= 0;
    mysqli_stmt_close($stmt);

    audit($conn, 'status_temu_janji_ditukar', $id_temu_janji, 'status=' . $status);

    return $berjaya;
}
