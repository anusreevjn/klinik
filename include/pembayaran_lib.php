<?php

require_once __DIR__ . '/helpers.php';

function jelaskan_pembayaran($conn, $id_pembayaran, $id_kakitangan, &$mesej = null)
{
    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare($conn, "SELECT p.*, r.nama_rawatan, r.id_temu_janji FROM pembayaran p LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan WHERE p.id_pembayaran = ? AND p.status_pembayaran <> 'Selesai' FOR UPDATE");
        mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
        mysqli_stmt_execute($stmt);
        $bayaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$bayaran) {
            mysqli_rollback($conn);
            $mesej = 'Pembayaran ini sudah disahkan atau tidak dijumpai.';
            return false;
        }

        $u = mysqli_prepare($conn, "UPDATE pembayaran SET status_pembayaran = 'Selesai', id_kakitangan = ?, disahkan_oleh = ?, tarikh_sah = NOW(), tarikh_bayaran = COALESCE(tarikh_bayaran, NOW()) WHERE id_pembayaran = ?");
        mysqli_stmt_bind_param($u, "iii", $id_kakitangan, $id_kakitangan, $id_pembayaran);
        mysqli_stmt_execute($u);
        mysqli_stmt_close($u);

        $semak = mysqli_prepare($conn, "SELECT id_invois, no_resit FROM invois WHERE id_pembayaran = ? LIMIT 1");
        mysqli_stmt_bind_param($semak, "i", $id_pembayaran);
        mysqli_stmt_execute($semak);
        $invois_sedia = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
        mysqli_stmt_close($semak);

        if ($invois_sedia) {
            $no_resit = $invois_sedia['no_resit'];
            $v = mysqli_prepare($conn, "UPDATE invois SET status_invois = 'Berjaya', tarikh_jana = NOW() WHERE id_invois = ?");
            mysqli_stmt_bind_param($v, "i", $invois_sedia['id_invois']);
            mysqli_stmt_execute($v);
            mysqli_stmt_close($v);
        } else {
            $no_invois = jana_no_dokumen($conn, 'invois');
            $no_resit = jana_no_dokumen($conn, 'resit');
            $jumlah = $bayaran['jumlah_bayaran'];

            $i = mysqli_prepare($conn, "INSERT INTO invois (id_pembayaran, no_invois, no_resit, jumlah_invois, tarikh_jana, status_invois) VALUES (?, ?, ?, ?, NOW(), 'Berjaya')");
            mysqli_stmt_bind_param($i, "issd", $id_pembayaran, $no_invois, $no_resit, $jumlah);
            mysqli_stmt_execute($i);
            mysqli_stmt_close($i);
        }

        if (!empty($bayaran['id_rawatan'])) {
            $ubat = mysqli_prepare($conn, "SELECT b.id_preskripsi, b.id_inventori, b.kuantiti FROM butiran_preskripsi b WHERE b.id_rawatan = ?");
            mysqli_stmt_bind_param($ubat, "i", $bayaran['id_rawatan']);
            mysqli_stmt_execute($ubat);
            $senarai_ubat = mysqli_stmt_get_result($ubat);

            while ($baris = mysqli_fetch_assoc($senarai_ubat)) {
                if (empty($baris['id_inventori'])) {
                    continue;
                }

                $sudah = mysqli_prepare($conn, "SELECT id_penggunaan FROM penggunaan_ubat WHERE id_preskripsi = ? LIMIT 1");
                mysqli_stmt_bind_param($sudah, "i", $baris['id_preskripsi']);
                mysqli_stmt_execute($sudah);
                $direkod = mysqli_fetch_assoc(mysqli_stmt_get_result($sudah));
                mysqli_stmt_close($sudah);

                if ($direkod) {
                    continue;
                }

                $id_inv = (int)$baris['id_inventori'];
                $kuantiti = (int)$baris['kuantiti'];

                $tolak = mysqli_prepare($conn, "UPDATE inventori SET kuantiti_stok = GREATEST(kuantiti_stok - ?, 0) WHERE id_inventori = ?");
                mysqli_stmt_bind_param($tolak, "ii", $kuantiti, $id_inv);
                mysqli_stmt_execute($tolak);
                mysqli_stmt_close($tolak);

                $guna = mysqli_prepare($conn, "INSERT INTO penggunaan_ubat (id_preskripsi, id_inventori, kuantiti_guna, tarikh_guna) VALUES (?, ?, ?, NOW())");
                mysqli_stmt_bind_param($guna, "iii", $baris['id_preskripsi'], $id_inv, $kuantiti);
                mysqli_stmt_execute($guna);
                mysqli_stmt_close($guna);
            }
            mysqli_stmt_close($ubat);
        }

        if (!empty($bayaran['id_temu_janji'])) {
            $t = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Selesai' WHERE id_temu_janji = ?");
            mysqli_stmt_bind_param($t, "i", $bayaran['id_temu_janji']);
            mysqli_stmt_execute($t);
            mysqli_stmt_close($t);
        }

        if (!empty($bayaran['id_pesakit'])) {
            hantar_notifikasi($conn, 'pesakit', (int)$bayaran['id_pesakit'], 'Pembayaran disahkan', 'Bayaran ' . wang($bayaran['jumlah_bayaran']) . ' untuk ' . $bayaran['nama_rawatan'] . ' telah disahkan. Resit ' . $no_resit . ' boleh dimuat turun.', 'pembayaran', 'pembayaran.php');
        }

        audit($conn, 'pembayaran_disahkan', $id_pembayaran, 'jumlah=' . $bayaran['jumlah_bayaran'] . ';resit=' . $no_resit);

        mysqli_commit($conn);
        $mesej = 'Pembayaran disahkan. Resit ' . $no_resit . ' telah dijana.';

        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $mesej = 'Ralat sistem: ' . $e->getMessage();

        return false;
    }
}

function tolak_pembayaran($conn, $id_pembayaran, $id_kakitangan, $sebab)
{
    $stmt = mysqli_prepare($conn, "SELECT id_pesakit, jumlah_bayaran FROM pembayaran WHERE id_pembayaran = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
    mysqli_stmt_execute($stmt);
    $bayaran = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$bayaran) {
        return false;
    }

    $u = mysqli_prepare($conn, "UPDATE pembayaran SET status_pembayaran = 'Ditolak', disahkan_oleh = ?, tarikh_sah = NOW(), catatan_bayaran = ? WHERE id_pembayaran = ?");
    mysqli_stmt_bind_param($u, "isi", $id_kakitangan, $sebab, $id_pembayaran);
    $ok = mysqli_stmt_execute($u);
    mysqli_stmt_close($u);

    if ($ok) {
        audit($conn, 'pembayaran_ditolak', $id_pembayaran, 'sebab=' . $sebab);
    }

    if ($ok && !empty($bayaran['id_pesakit'])) {
        hantar_notifikasi($conn, 'pesakit', (int)$bayaran['id_pesakit'], 'Bukti pembayaran ditolak', 'Bukti pembayaran ' . wang($bayaran['jumlah_bayaran']) . ' tidak dapat disahkan. Sebab: ' . $sebab . '. Sila muat naik semula atau hubungi kaunter.', 'pembayaran', 'bayar.php');
    }

    return $ok;
}

function simpan_bukti_pembayaran($fail, &$ralat = null)
{
    if (!is_array($fail)) {
        $ralat = 'Sila pilih fail bukti pembayaran.';
        return null;
    }

    $sambungan = fail_imej_atau_pdf_sah($fail, 5 * 1024 * 1024, $ralat);

    if (!$sambungan) {
        return null;
    }

    $folder = __DIR__ . '/../storage/bukti';
    if (!is_dir($folder)) {
        mkdir($folder, 0750, true);
    }

    $nama_fail = 'bukti_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $sambungan;

    if (!move_uploaded_file($fail['tmp_name'], $folder . '/' . $nama_fail)) {
        $ralat = 'Gagal muat naik fail.';
        return null;
    }

    chmod($folder . '/' . $nama_fail, 0640);

    return 'storage/bukti/' . $nama_fail;
}
