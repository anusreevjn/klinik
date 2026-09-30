<?php

function jenis_laporan_tersedia()
{
    return [
        'Laporan Kewangan' => 'Kutipan, kaedah bayaran dan jumlah terkumpul',
        'Statistik Rawatan' => 'Bilangan dan hasil setiap jenis rawatan',
        'Laporan Inventori' => 'Baki stok, stok rendah dan tarikh luput',
        'Laporan Prestasi Klinik Bulanan' => 'Ringkasan prestasi klinik mengikut bulan',
        'Laporan Pesakit' => 'Pendaftaran pesakit dan kekerapan kunjungan',
        'Laporan Temu Janji' => 'Status temu janji dan kadar kehadiran',
    ];
}

function data_laporan($conn, $jenis, $dari, $hingga)
{
    $hasil = ['tajuk' => $jenis, 'kepala' => [], 'baris' => [], 'ringkasan' => []];

    if ($jenis === 'Laporan Kewangan') {
        $hasil['kepala'] = ['Tarikh', 'No Resit', 'Pesakit', 'Rawatan', 'Kaedah', 'Jumlah', 'Status'];
        $sql = "SELECT p.tarikh_bayaran, i.no_resit, ps.nama_pesakit, r.nama_rawatan, p.kaedah_bayaran, p.jumlah_bayaran, p.status_pembayaran
                FROM pembayaran p
                LEFT JOIN invois i ON i.id_pembayaran = p.id_pembayaran
                LEFT JOIN pesakit ps ON ps.id_pesakit = p.id_pesakit
                LEFT JOIN rekod_rawatan r ON r.id_rawatan = p.id_rawatan
                WHERE DATE(p.tarikh_bayaran) BETWEEN ? AND ?
                ORDER BY p.tarikh_bayaran DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $jumlah = 0;
        while ($row = mysqli_fetch_assoc($res)) {
            $jumlah += (float)$row['jumlah_bayaran'];
            $hasil['baris'][] = [
                date('d/m/Y H:i', strtotime($row['tarikh_bayaran'])),
                $row['no_resit'] ?: '-',
                $row['nama_pesakit'] ?: '-',
                $row['nama_rawatan'] ?: '-',
                $row['kaedah_bayaran'],
                wang($row['jumlah_bayaran']),
                $row['status_pembayaran'],
            ];
        }
        $hasil['ringkasan'] = ['Jumlah Transaksi' => count($hasil['baris']), 'Jumlah Kutipan' => wang($jumlah)];
        return $hasil;
    }

    if ($jenis === 'Statistik Rawatan') {
        $hasil['kepala'] = ['Rawatan', 'Bilangan', 'Jumlah Hasil', 'Purata Harga'];
        $sql = "SELECT nama_rawatan, COUNT(*) AS bil, SUM(harga_rawatan) AS hasil, AVG(harga_rawatan) AS purata
                FROM rekod_rawatan
                WHERE tarikh_rawatan BETWEEN ? AND ?
                GROUP BY nama_rawatan ORDER BY bil DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $bil = 0;
        $hasil_total = 0;
        while ($row = mysqli_fetch_assoc($res)) {
            $bil += (int)$row['bil'];
            $hasil_total += (float)$row['hasil'];
            $hasil['baris'][] = [$row['nama_rawatan'], $row['bil'], wang($row['hasil']), wang($row['purata'])];
        }
        $hasil['ringkasan'] = ['Jumlah Rawatan' => $bil, 'Jumlah Hasil' => wang($hasil_total)];
        return $hasil;
    }

    if ($jenis === 'Laporan Inventori') {
        $hasil['kepala'] = ['Barang', 'Kategori', 'Baki Stok', 'Had Minimum', 'Tarikh Luput', 'Status'];
        $res = mysqli_query($conn, "SELECT nama_barang, kategori, kuantiti_stok, had_minimum_stok, tarikh_luput FROM inventori ORDER BY kuantiti_stok ASC");
        $rendah = 0;
        while ($row = mysqli_fetch_assoc($res)) {
            $status = (int)$row['kuantiti_stok'] <= (int)$row['had_minimum_stok'] ? 'Stok Rendah' : 'Mencukupi';
            if ($status === 'Stok Rendah') {
                $rendah++;
            }
            $hasil['baris'][] = [
                $row['nama_barang'],
                $row['kategori'],
                $row['kuantiti_stok'],
                $row['had_minimum_stok'],
                $row['tarikh_luput'] ? date('d/m/Y', strtotime($row['tarikh_luput'])) : '-',
                $status,
            ];
        }
        $hasil['ringkasan'] = ['Jumlah Item' => count($hasil['baris']), 'Item Stok Rendah' => $rendah];
        return $hasil;
    }

    if ($jenis === 'Laporan Prestasi Klinik Bulanan') {
        $hasil['kepala'] = ['Bulan', 'Temu Janji', 'Hadir', 'Tidak Hadir', 'Rawatan', 'Kutipan'];
        $sql = "SELECT DATE_FORMAT(t.tarikh_temu_janji, '%Y-%m') AS bulan,
                       COUNT(*) AS jumlah,
                       SUM(CASE WHEN t.status = 'Selesai' THEN 1 ELSE 0 END) AS hadir,
                       SUM(CASE WHEN t.status = 'Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir
                FROM temu_janji t
                WHERE t.tarikh_temu_janji BETWEEN ? AND ?
                GROUP BY bulan ORDER BY bulan DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($res)) {
            $bulan = $row['bulan'];

            $q1 = mysqli_prepare($conn, "SELECT COUNT(*) FROM rekod_rawatan WHERE DATE_FORMAT(tarikh_rawatan, '%Y-%m') = ?");
            mysqli_stmt_bind_param($q1, "s", $bulan);
            mysqli_stmt_execute($q1);
            mysqli_stmt_bind_result($q1, $bil_rawatan);
            mysqli_stmt_fetch($q1);
            mysqli_stmt_close($q1);

            $q2 = mysqli_prepare($conn, "SELECT COALESCE(SUM(jumlah_bayaran), 0) FROM pembayaran WHERE DATE_FORMAT(tarikh_bayaran, '%Y-%m') = ? AND status_pembayaran = 'Selesai'");
            mysqli_stmt_bind_param($q2, "s", $bulan);
            mysqli_stmt_execute($q2);
            mysqli_stmt_bind_result($q2, $kutipan);
            mysqli_stmt_fetch($q2);
            mysqli_stmt_close($q2);

            $hasil['baris'][] = [$bulan, $row['jumlah'], $row['hadir'], $row['tidak_hadir'], (int)$bil_rawatan, wang($kutipan)];
        }

        $hasil['ringkasan'] = ['Bilangan Bulan' => count($hasil['baris'])];
        return $hasil;
    }

    if ($jenis === 'Laporan Pesakit') {
        $hasil['kepala'] = ['Nama', 'No IC', 'Telefon', 'Tarikh Daftar', 'Jumlah Temu Janji', 'Jumlah Rawatan'];
        $sql = "SELECT p.nama_pesakit, p.no_ic, p.no_telefon, p.tarikh_daftar,
                       (SELECT COUNT(*) FROM temu_janji t WHERE t.id_pesakit = p.id_pesakit) AS bil_tj,
                       (SELECT COUNT(*) FROM rekod_rawatan r WHERE r.id_pesakit = p.id_pesakit) AS bil_rawatan
                FROM pesakit p
                WHERE DATE(p.tarikh_daftar) BETWEEN ? AND ?
                ORDER BY p.tarikh_daftar DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $hasil['baris'][] = [
                $row['nama_pesakit'],
                $row['no_ic'],
                $row['no_telefon'],
                $row['tarikh_daftar'] ? date('d/m/Y', strtotime($row['tarikh_daftar'])) : '-',
                $row['bil_tj'],
                $row['bil_rawatan'],
            ];
        }
        $hasil['ringkasan'] = ['Pesakit Baharu' => count($hasil['baris'])];
        return $hasil;
    }

    if ($jenis === 'Laporan Temu Janji') {
        $hasil['kepala'] = ['Tarikh', 'Masa', 'Pesakit', 'Doktor', 'Jenis Rawatan', 'No Giliran', 'Status'];
        $sql = "SELECT t.tarikh_temu_janji, t.masa_temu_janji, t.jenis_rawatan, t.no_giliran, t.status,
                       ps.nama_pesakit, d.nama_doktor
                FROM temu_janji t
                LEFT JOIN pesakit ps ON ps.id_pesakit = t.id_pesakit
                LEFT JOIN doktor d ON d.id_doktor = t.id_doktor
                WHERE t.tarikh_temu_janji BETWEEN ? AND ?
                ORDER BY t.tarikh_temu_janji DESC, t.masa_temu_janji ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $dari, $hingga);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $kira = ['Selesai' => 0, 'Tidak Hadir' => 0, 'Dibatalkan' => 0];
        while ($row = mysqli_fetch_assoc($res)) {
            if (isset($kira[$row['status']])) {
                $kira[$row['status']]++;
            }
            $hasil['baris'][] = [
                date('d/m/Y', strtotime($row['tarikh_temu_janji'])),
                $row['masa_temu_janji'] ? date('h:i A', strtotime($row['masa_temu_janji'])) : '-',
                $row['nama_pesakit'] ?: '-',
                $row['nama_doktor'] ?: '-',
                $row['jenis_rawatan'],
                $row['no_giliran'] ?: '-',
                $row['status'],
            ];
        }
        $jumlah = count($hasil['baris']);
        $kadar = $jumlah > 0 ? round(($kira['Selesai'] / $jumlah) * 100, 1) . '%' : '0%';
        $hasil['ringkasan'] = [
            'Jumlah Temu Janji' => $jumlah,
            'Selesai' => $kira['Selesai'],
            'Tidak Hadir' => $kira['Tidak Hadir'],
            'Dibatalkan' => $kira['Dibatalkan'],
            'Kadar Kehadiran' => $kadar,
        ];
        return $hasil;
    }

    return $hasil;
}
