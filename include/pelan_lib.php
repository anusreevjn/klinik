<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/pembayaran_lib.php';

function ringkasan_pelan($conn, int $id_pelan): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT pl.*, p.nama_pesakit, p.no_pendaftaran_klinik,
            COALESCE((SELECT SUM(b.jumlah_bayaran) FROM pembayaran b WHERE b.id_pelan = pl.id_pelan AND b.status_pembayaran = 'Selesai'), 0) AS jumlah_dibayar,
            (SELECT COUNT(*) FROM pembayaran b WHERE b.id_pelan = pl.id_pelan AND b.status_pembayaran = 'Selesai') AS bil_transaksi
            FROM pelan_bayaran pl
            LEFT JOIN pesakit p ON p.id_pesakit = pl.id_pesakit
            WHERE pl.id_pelan = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_pelan);
    mysqli_stmt_execute($stmt);
    $pelan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$pelan) {
        return null;
    }

    $pelan['baki'] = max(0, (float)$pelan['jumlah_keseluruhan'] - (float)$pelan['jumlah_dibayar']);

    return $pelan;
}

function senarai_pelan($conn, ?int $id_pesakit = null, string $status = 'Semua')
{
    $sql = "SELECT pl.*, p.nama_pesakit, p.no_pendaftaran_klinik,
            COALESCE((SELECT SUM(b.jumlah_bayaran) FROM pembayaran b WHERE b.id_pelan = pl.id_pelan AND b.status_pembayaran = 'Selesai'), 0) AS jumlah_dibayar
            FROM pelan_bayaran pl
            LEFT JOIN pesakit p ON p.id_pesakit = pl.id_pesakit
            WHERE 1 = 1";

    $params = [];
    $jenis = '';

    if ($id_pesakit !== null) {
        $sql .= " AND pl.id_pesakit = ?";
        $params[] = $id_pesakit;
        $jenis .= 'i';
    }

    if ($status !== 'Semua') {
        $sql .= " AND pl.status_pelan = ?";
        $params[] = $status;
        $jenis .= 's';
    }

    $sql .= " ORDER BY pl.status_pelan ASC, pl.dicipta_pada DESC LIMIT 200";

    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $jenis, ...$params);
    }
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

function cipta_pelan($conn, array $data, int $id_kakitangan, ?string &$ralat = null): ?int
{
    $id_pesakit = (int)$data['id_pesakit'];
    $nama = trim($data['nama_pelan']);
    $jumlah = (float)$data['jumlah_keseluruhan'];
    $deposit = (float)$data['deposit'];
    $ansuran = (float)$data['ansuran_dijangka'];
    $catatan = trim($data['catatan']);
    $tarikh = $data['tarikh_mula'] !== '' ? $data['tarikh_mula'] : date('Y-m-d');
    $id_kod = $data['id_kod_rawatan'] !== '' ? (int)$data['id_kod_rawatan'] : null;

    if ($id_pesakit <= 0 || $nama === '' || $jumlah <= 0) {
        $ralat = 'Sila pilih pesakit, isi nama pelan dan jumlah keseluruhan.';
        return null;
    }

    if ($deposit > $jumlah) {
        $ralat = 'Deposit tidak boleh melebihi jumlah keseluruhan.';
        return null;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO pelan_bayaran (id_pesakit, id_kod_rawatan, nama_pelan, jumlah_keseluruhan, deposit, ansuran_dijangka, status_pelan, catatan, tarikh_mula, dicipta_oleh, dicipta_pada) VALUES (?, ?, ?, ?, ?, ?, 'Aktif', ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "iisdddssi", $id_pesakit, $id_kod, $nama, $jumlah, $deposit, $ansuran, $catatan, $tarikh, $id_kakitangan);

    if (!mysqli_stmt_execute($stmt)) {
        $ralat = 'Gagal cipta pelan.';
        mysqli_stmt_close($stmt);
        return null;
    }

    $id_pelan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    audit($conn, 'pelan_bayaran_dicipta', $id_pelan, 'pesakit=' . $id_pesakit . ';jumlah=' . $jumlah);
    hantar_notifikasi($conn, 'pesakit', $id_pesakit, 'Pelan ansuran dibuka', 'Pelan ' . $nama . ' sebanyak ' . wang($jumlah) . ' telah dibuka. Anda boleh lihat baki dan sejarah bayaran dalam menu Pelan Ansuran.', 'pelan', 'pelan.php');

    return (int)$id_pelan;
}

function rekod_bayaran_pelan($conn, int $id_pelan, float $jumlah, string $kaedah, string $jenis_bayaran, int $id_kakitangan, ?string &$mesej = null): bool
{
    $pelan = ringkasan_pelan($conn, $id_pelan);

    if (!$pelan) {
        $mesej = 'Pelan tidak dijumpai.';
        return false;
    }

    if ($pelan['status_pelan'] !== 'Aktif') {
        $mesej = 'Pelan ini sudah ' . strtolower($pelan['status_pelan']) . '.';
        return false;
    }

    if ($jumlah <= 0) {
        $mesej = 'Jumlah bayaran tidak sah.';
        return false;
    }

    if ($jumlah > $pelan['baki'] + 0.001) {
        $mesej = 'Jumlah melebihi baki. Baki semasa ialah ' . wang($pelan['baki']) . '.';
        return false;
    }

    if (!in_array($jenis_bayaran, ['Deposit', 'Ansuran'], true)) {
        $jenis_bayaran = 'Ansuran';
    }

    $id_pesakit = (int)$pelan['id_pesakit'];

    $stmt = mysqli_prepare($conn, "INSERT INTO pembayaran (id_rawatan, id_pelan, jenis_bayaran, id_kakitangan, id_pesakit, jumlah_bayaran, kaedah_bayaran, tarikh_bayaran, status_pembayaran) VALUES (NULL, ?, ?, ?, ?, ?, ?, NOW(), 'Belum Bayar')");
    mysqli_stmt_bind_param($stmt, "isiids", $id_pelan, $jenis_bayaran, $id_kakitangan, $id_pesakit, $jumlah, $kaedah);

    if (!mysqli_stmt_execute($stmt)) {
        $mesej = 'Gagal rekod bayaran.';
        mysqli_stmt_close($stmt);
        return false;
    }

    $id_pembayaran = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $keterangan = null;
    if (!jelaskan_pembayaran($conn, $id_pembayaran, $id_kakitangan, $keterangan)) {
        $mesej = $keterangan;
        return false;
    }

    $selepas = ringkasan_pelan($conn, $id_pelan);

    if ($selepas && $selepas['baki'] <= 0.001) {
        $tutup = mysqli_prepare($conn, "UPDATE pelan_bayaran SET status_pelan = 'Selesai' WHERE id_pelan = ?");
        mysqli_stmt_bind_param($tutup, "i", $id_pelan);
        mysqli_stmt_execute($tutup);
        mysqli_stmt_close($tutup);

        hantar_notifikasi($conn, 'pesakit', $id_pesakit, 'Pelan ansuran selesai', 'Tahniah, pelan ' . $pelan['nama_pelan'] . ' telah selesai dibayar sepenuhnya.', 'pelan', 'pelan.php');
    }

    audit($conn, 'bayaran_pelan_direkod', $id_pelan, 'jumlah=' . $jumlah . ';jenis=' . $jenis_bayaran);

    $baki_baharu = $selepas ? $selepas['baki'] : 0;
    $mesej = 'Bayaran ' . wang($jumlah) . ' direkodkan. Baki sekarang ' . wang($baki_baharu) . '.';

    return true;
}

function batalkan_pelan($conn, int $id_pelan, string $sebab): bool
{
    $stmt = mysqli_prepare($conn, "UPDATE pelan_bayaran SET status_pelan = 'Dibatalkan', catatan = ? WHERE id_pelan = ? AND status_pelan = 'Aktif'");
    mysqli_stmt_bind_param($stmt, "si", $sebab, $id_pelan);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($ok) {
        audit($conn, 'pelan_bayaran_dibatalkan', $id_pelan, 'sebab=' . $sebab);
    }

    return $ok;
}

function transaksi_pelan($conn, int $id_pelan)
{
    $stmt = mysqli_prepare($conn, "SELECT b.*, i.no_resit, i.no_invois, k.nama_kakitangan
            FROM pembayaran b
            LEFT JOIN invois i ON i.id_pembayaran = b.id_pembayaran
            LEFT JOIN kakitangan k ON k.id_kakitangan = b.id_kakitangan
            WHERE b.id_pelan = ?
            ORDER BY b.tarikh_bayaran DESC");
    mysqli_stmt_bind_param($stmt, "i", $id_pelan);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}
