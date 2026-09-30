<?php

require_once __DIR__ . '/helpers.php';

function profil_pesakit($conn, int $id_pesakit): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_pesakit = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris ?: null;
}

function senarai_profil_dibenarkan($conn, int $id_akaun): array
{
    $senarai = [];

    $sendiri = profil_pesakit($conn, $id_akaun);
    if ($sendiri) {
        $sendiri['label_profil'] = 'Diri sendiri';
        $senarai[(int)$sendiri['id_pesakit']] = $sendiri;
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_penjaga_akaun = ? AND akses_penjaga = 1 ORDER BY nama_pesakit ASC");
    mysqli_stmt_bind_param($stmt, "i", $id_akaun);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($baris = mysqli_fetch_assoc($res)) {
        $baris['label_profil'] = $baris['hubungan_penjaga'] ? $baris['hubungan_penjaga'] : 'Tanggungan';
        $senarai[(int)$baris['id_pesakit']] = $baris;
    }
    mysqli_stmt_close($stmt);

    return $senarai;
}

function id_profil_aktif($conn, int $id_akaun): int
{
    $dibenarkan = senarai_profil_dibenarkan($conn, $id_akaun);

    if (isset($_GET['profil'])) {
        $pilihan = (int)$_GET['profil'];
        if (isset($dibenarkan[$pilihan])) {
            $_SESSION['profil_aktif'] = $pilihan;
        }
    }

    if (isset($_POST['profil_aktif'])) {
        $pilihan = (int)$_POST['profil_aktif'];
        if (isset($dibenarkan[$pilihan])) {
            $_SESSION['profil_aktif'] = $pilihan;
        }
    }

    $semasa = isset($_SESSION['profil_aktif']) ? (int)$_SESSION['profil_aktif'] : $id_akaun;

    if (!isset($dibenarkan[$semasa])) {
        $semasa = $id_akaun;
        $_SESSION['profil_aktif'] = $id_akaun;
    }

    return $semasa;
}

function profil_dibenarkan_untuk_akaun($conn, int $id_akaun, int $id_profil): bool
{
    if ($id_akaun === $id_profil) {
        return true;
    }

    $stmt = mysqli_prepare($conn, "SELECT id_pesakit FROM pesakit WHERE id_pesakit = ? AND id_penjaga_akaun = ? AND akses_penjaga = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $id_profil, $id_akaun);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return (bool)$ada;
}

function pemilih_profil($conn, int $id_akaun, int $id_aktif, string $halaman): string
{
    $dibenarkan = senarai_profil_dibenarkan($conn, $id_akaun);

    if (count($dibenarkan) < 2) {
        return '';
    }

    $html = '<div class="card"><div class="card-title">Profil Aktif</div><form method="get" action="' . e($halaman) . '" class="date-group">';
    $html .= '<select name="profil" class="form-control" onchange="this.form.submit()" style="max-width:320px">';

    foreach ($dibenarkan as $id => $profil) {
        $pilih = $id === $id_aktif ? ' selected' : '';
        $html .= '<option value="' . (int)$id . '"' . $pilih . '>' . e($profil['nama_pesakit']) . ' (' . e($profil['label_profil']) . ')</option>';
    }

    $html .= '</select><button class="btn" type="submit">Tukar Profil</button></form>';
    $html .= '<p>Semua rekod dan temu janji yang anda lihat atau buat adalah untuk profil yang dipilih di atas.</p></div>';

    return $html;
}

function jana_no_pendaftaran_klinik(string $no_ic): string
{
    $bersih = preg_replace('/[^0-9]/', '', $no_ic);

    return $bersih === '' ? '' : substr($bersih, -4);
}

function id_pesakit_sistem($conn, int $id_pesakit): string
{
    $prefix = tetapan($conn, 'prefix_id_pesakit', 'P');

    return $prefix . str_pad((string)$id_pesakit, 5, '0', STR_PAD_LEFT);
}

function daftar_profil_tanggungan($conn, int $id_penjaga_akaun, array $data, ?string &$ralat = null): ?int
{
    $nama = trim($data['nama_pesakit']);
    $ic = preg_replace('/[^0-9]/', '', (string)$data['no_ic']);
    $hubungan = trim($data['hubungan_penjaga']);

    if ($nama === '' || strlen($ic) < 7) {
        $ralat = 'Nama dan No IC atau MyKid wajib diisi dengan betul.';
        return null;
    }

    $stmt = mysqli_prepare($conn, "SELECT id_pesakit FROM pesakit WHERE no_ic = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $ic);
    mysqli_stmt_execute($stmt);
    $sudah_ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($sudah_ada) {
        $ralat = 'No IC atau MyKid ini sudah berdaftar dalam sistem. Sila hubungi kaunter klinik.';
        return null;
    }

    $penjaga = profil_pesakit($conn, $id_penjaga_akaun);
    if (!$penjaga) {
        $ralat = 'Akaun penjaga tidak dijumpai.';
        return null;
    }

    $no_pendaftaran = jana_no_pendaftaran_klinik($ic);
    $empat = substr($ic, -4);
    $jantina = in_array($data['jantina'], ['Lelaki', 'Perempuan'], true) ? $data['jantina'] : 'Lelaki';
    $golongan = trim($data['golongan']) !== '' ? trim($data['golongan']) : 'Kanak-kanak';
    $tarikh_lahir = $data['tarikh_lahir'];
    $penyakit = trim($data['penyakit_kronik']);

    $sql = "INSERT INTO pesakit
            (id_penjaga_akaun, jenis_profil, akses_penjaga, nama_pesakit, no_ic, no_pendaftaran_klinik, id_cariana_ic,
             jantina, golongan, tarikh_lahir, alamat, no_telefon, email, nama_penjaga, no_ic_penjaga, hubungan_penjaga,
             telefon_penjaga, email_penjaga, kata_laluan, tarikh_daftar, penyakit, penyakit_kronik, cabut_gigi)
            VALUES (?, 'Tanggungan', 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NULL, NOW(), 'Tiada', ?, 'Tidak')";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "isssssssssssssss",
        $id_penjaga_akaun,
        $nama,
        $ic,
        $no_pendaftaran,
        $empat,
        $jantina,
        $golongan,
        $tarikh_lahir,
        $penjaga['alamat'],
        $penjaga['no_telefon'],
        $penjaga['nama_pesakit'],
        $penjaga['no_ic'],
        $hubungan,
        $penjaga['no_telefon'],
        $penjaga['email'],
        $penyakit
    );

    if (!mysqli_stmt_execute($stmt)) {
        $ralat = 'Gagal daftar profil.';
        mysqli_stmt_close($stmt);
        return null;
    }

    $id_baru = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    audit($conn, 'profil_tanggungan_didaftar', $id_baru, 'penjaga=' . $id_penjaga_akaun);

    return (int)$id_baru;
}

function cari_profil_untuk_pautan($conn, string $no_ic): ?array
{
    $ic = preg_replace('/[^0-9]/', '', $no_ic);

    $stmt = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE no_ic = ? AND kata_laluan IS NULL LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $ic);
    mysqli_stmt_execute($stmt);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $baris ?: null;
}

function cipta_permohonan_pautan($conn, array $profil, string $email, string $kata_laluan, ?string &$ralat = null): bool
{
    $id_profil = (int)$profil['id_pesakit'];
    $id_penjaga = $profil['id_penjaga_akaun'] !== null ? (int)$profil['id_penjaga_akaun'] : null;

    $stmt = mysqli_prepare($conn, "SELECT id_permohonan FROM permohonan_pautan WHERE id_pesakit_profil = ? AND status_permohonan = 'Menunggu' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_profil);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($ada) {
        $ralat = 'Permohonan untuk profil ini sedang menunggu kelulusan penjaga.';
        return false;
    }

    $hash = password_hash($kata_laluan, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO permohonan_pautan (id_pesakit_profil, id_penjaga_akaun, email_dipohon, kata_laluan_hash, no_ic_dipohon, status_permohonan, tarikh_mohon) VALUES (?, ?, ?, ?, ?, 'Menunggu', NOW())");
    mysqli_stmt_bind_param($stmt, "iisss", $id_profil, $id_penjaga, $email, $hash, $profil['no_ic']);
    $ok = mysqli_stmt_execute($stmt);
    $id_permohonan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        $ralat = 'Gagal hantar permohonan.';
        return false;
    }

    if ($id_penjaga) {
        hantar_notifikasi($conn, 'pesakit', $id_penjaga, 'Permohonan akaun sendiri', $profil['nama_pesakit'] . ' memohon untuk mempunyai akaun sendiri. Sila sahkan dalam menu Profil Tanggungan.', 'pautan', 'tanggungan.php');
    } else {
        $staf = mysqli_query($conn, "SELECT id_kakitangan FROM kakitangan WHERE status_aktif = 'Aktif'");
        while ($s = mysqli_fetch_assoc($staf)) {
            hantar_notifikasi($conn, 'kakitangan', (int)$s['id_kakitangan'], 'Permohonan akaun pesakit', $profil['nama_pesakit'] . ' memohon akaun sendiri dan tiada penjaga berdaftar. Sila semak di kaunter.', 'pautan', 'pesakit.php');
        }
    }

    audit($conn, 'permohonan_pautan_dihantar', $id_permohonan, 'profil=' . $id_profil);

    return true;
}

function luluskan_permohonan_pautan($conn, int $id_permohonan, int $id_penjaga_akaun, bool $kekalkan_akses, ?string &$mesej = null): bool
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM permohonan_pautan WHERE id_permohonan = ? AND status_permohonan = 'Menunggu' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_permohonan);
    mysqli_stmt_execute($stmt);
    $permohonan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$permohonan) {
        $mesej = 'Permohonan tidak dijumpai atau sudah diambil tindakan.';
        return false;
    }

    if ((int)$permohonan['id_penjaga_akaun'] !== $id_penjaga_akaun) {
        $mesej = 'Anda tidak dibenarkan menguruskan permohonan ini.';
        return false;
    }

    $semak = mysqli_prepare($conn, "SELECT id_pesakit FROM pesakit WHERE email = ? AND id_pesakit <> ? LIMIT 1");
    mysqli_stmt_bind_param($semak, "si", $permohonan['email_dipohon'], $permohonan['id_pesakit_profil']);
    mysqli_stmt_execute($semak);
    $email_diguna = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
    mysqli_stmt_close($semak);

    if ($email_diguna) {
        $mesej = 'Email yang dipohon sudah digunakan oleh akaun lain.';
        return false;
    }

    $akses = $kekalkan_akses ? 1 : 0;

    $stmt = mysqli_prepare($conn, "UPDATE pesakit SET email = ?, kata_laluan = ?, jenis_profil = 'Akaun', akses_penjaga = ?, mesti_tukar_kata_laluan = 0 WHERE id_pesakit = ?");
    mysqli_stmt_bind_param($stmt, "ssii", $permohonan['email_dipohon'], $permohonan['kata_laluan_hash'], $akses, $permohonan['id_pesakit_profil']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE permohonan_pautan SET status_permohonan = 'Diluluskan', kekalkan_akses_penjaga = ?, tarikh_tindakan = NOW() WHERE id_permohonan = ?");
    mysqli_stmt_bind_param($stmt, "ii", $akses, $id_permohonan);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    hantar_notifikasi($conn, 'pesakit', (int)$permohonan['id_pesakit_profil'], 'Akaun anda telah diaktifkan', 'Penjaga anda telah sahkan permohonan. Anda kini boleh log masuk dengan email ' . $permohonan['email_dipohon'] . '.', 'pautan', 'dashboard.php');
    audit($conn, 'permohonan_pautan_diluluskan', $id_permohonan, 'akses_penjaga=' . $akses);

    $mesej = 'Permohonan diluluskan. Profil tersebut kini ada akaun sendiri.';

    return true;
}

function tolak_permohonan_pautan($conn, int $id_permohonan, int $id_penjaga_akaun, string $sebab): bool
{
    $stmt = mysqli_prepare($conn, "UPDATE permohonan_pautan SET status_permohonan = 'Ditolak', catatan = ?, tarikh_tindakan = NOW() WHERE id_permohonan = ? AND id_penjaga_akaun = ? AND status_permohonan = 'Menunggu'");
    mysqli_stmt_bind_param($stmt, "sii", $sebab, $id_permohonan, $id_penjaga_akaun);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($ok) {
        audit($conn, 'permohonan_pautan_ditolak', $id_permohonan, 'sebab=' . $sebab);
    }

    return $ok;
}

function tukar_akses_penjaga($conn, int $id_profil, int $id_penjaga_akaun, bool $benarkan): bool
{
    $akses = $benarkan ? 1 : 0;

    $stmt = mysqli_prepare($conn, "UPDATE pesakit SET akses_penjaga = ? WHERE id_pesakit = ? AND id_penjaga_akaun = ?");
    mysqli_stmt_bind_param($stmt, "iii", $akses, $id_profil, $id_penjaga_akaun);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_stmt_affected_rows($stmt) >= 0;
    mysqli_stmt_close($stmt);

    audit($conn, 'akses_penjaga_ditukar', $id_profil, 'akses=' . $akses);

    return $ok;
}
