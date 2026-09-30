<?php

require_once __DIR__ . '/keselamatan.php';

function guard($conn, $peranan_dibenarkan, $laluan_login = '../login.php')
{
    $peranan_dibenarkan = (array)$peranan_dibenarkan;

    if (sesi_tamat_tempoh()) {
        header("Location: $laluan_login?tamat=1");
        exit();
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $peranan_dibenarkan, true)) {
        header("Location: $laluan_login");
        exit();
    }

    $id = (int)$_SESSION['user_id'];

    if (!akaun_masih_aktif($conn, $_SESSION['role'], $id)) {
        $_SESSION = [];
        session_destroy();
        header("Location: $laluan_login?nyahaktif=1");
        exit();
    }

    if (!empty($_SESSION['mesti_tukar_kata_laluan']) && basename($_SERVER['PHP_SELF']) !== 'change_password.php') {
        header('Location: ../change_password.php?wajib=1');
        exit();
    }

    return $id;
}

function tetapan($conn, $kunci, $lalai = '')
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        $res = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan_sistem");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $cache[$row['kunci']] = $row['nilai'];
            }
        }
    }

    return array_key_exists($kunci, $cache) ? $cache[$kunci] : $lalai;
}

function simpan_tetapan($conn, $kunci, $nilai)
{
    $stmt = mysqli_prepare($conn, "INSERT INTO tetapan_sistem (kunci, nilai, tarikh_kemaskini) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), tarikh_kemaskini = NOW()");
    mysqli_stmt_bind_param($stmt, "ss", $kunci, $nilai);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function hantar_notifikasi($conn, $peranan, $id_pengguna, $tajuk, $mesej, $jenis = 'umum', $pautan = null)
{
    $stmt = mysqli_prepare($conn, "INSERT INTO notifikasi (peranan, id_pengguna, tajuk, mesej, jenis, pautan, status_baca, tarikh_hantar) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
    mysqli_stmt_bind_param($stmt, "sissss", $peranan, $id_pengguna, $tajuk, $mesej, $jenis, $pautan);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function kira_notifikasi_belum_baca($conn, $peranan, $id_pengguna)
{
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM notifikasi WHERE peranan = ? AND id_pengguna = ? AND status_baca = 0");
    mysqli_stmt_bind_param($stmt, "si", $peranan, $id_pengguna);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$jumlah;
}

function jana_no_giliran($conn, $tarikh)
{
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM temu_janji WHERE tarikh_temu_janji = ? AND no_giliran IS NOT NULL AND no_giliran <> ''");
    mysqli_stmt_bind_param($stmt, "s", $tarikh);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return 'A' . str_pad((int)$jumlah + 1, 3, '0', STR_PAD_LEFT);
}

function jana_no_dokumen($conn, $jenis)
{
    $prefix = $jenis === 'resit' ? tetapan($conn, 'prefix_resit', 'RES') : tetapan($conn, 'prefix_invois', 'INV');
    $medan = $jenis === 'resit' ? 'no_resit' : 'no_invois';
    $tahun = date('Y');

    $sql = "SELECT $medan FROM invois WHERE $medan LIKE ? ORDER BY $medan DESC LIMIT 1";
    $corak = $prefix . '-' . $tahun . '-%';

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $corak);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $terakhir);
    $ada = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $nombor = 1;
    if ($ada && $terakhir) {
        $bahagian = explode('-', $terakhir);
        $nombor = (int)end($bahagian) + 1;
    }

    return $prefix . '-' . $tahun . '-' . str_pad($nombor, 4, '0', STR_PAD_LEFT);
}

function wang($jumlah)
{
    return 'RM ' . number_format((float)$jumlah, 2);
}

function selamat($teks)
{
    return e((string)$teks);
}

function kelas_status($status)
{
    return strtolower(str_replace(' ', '-', (string)$status));
}
