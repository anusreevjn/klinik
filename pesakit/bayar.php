<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/pembayaran_lib.php';
require_once '../include/penjaga_lib.php';

$id_akaun = guard($conn, 'pesakit');
$id_pesakit = id_profil_aktif($conn, $id_akaun);

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['hantar_bukti'])) {
    $id_rawatan = (int)$_POST['id_rawatan'];
    $kaedah = $_POST['kaedah_bayaran'];
    $rujukan = trim($_POST['rujukan_bayaran']);

    $stmt = mysqli_prepare($conn, "SELECT id_rawatan, nama_rawatan, harga_rawatan FROM rekod_rawatan WHERE id_rawatan = ? AND id_pesakit = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $id_rawatan, $id_pesakit);
    mysqli_stmt_execute($stmt);
    $rawatan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $semak = mysqli_prepare($conn, "SELECT id_pembayaran FROM pembayaran WHERE id_rawatan = ? AND status_pembayaran IN ('Menunggu Pengesahan', 'Selesai') LIMIT 1");
    mysqli_stmt_bind_param($semak, "i", $id_rawatan);
    mysqli_stmt_execute($semak);
    $sedia_ada = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
    mysqli_stmt_close($semak);

    $ralat = null;
    $laluan_bukti = simpan_bukti_pembayaran(isset($_FILES['bukti']) ? $_FILES['bukti'] : null, $ralat);

    if (!$rawatan) {
        $mesej = 'Rekod rawatan tidak dijumpai.';
        $jenis_mesej = 'error';
    } elseif ($sedia_ada) {
        $mesej = 'Bayaran untuk rawatan ini sudah dihantar atau disahkan.';
        $jenis_mesej = 'warning';
    } elseif (!$laluan_bukti) {
        $mesej = $ralat;
        $jenis_mesej = 'error';
    } else {
        $jumlah = (float)$rawatan['harga_rawatan'];

        $stmt = mysqli_prepare($conn, "INSERT INTO pembayaran (id_rawatan, id_pesakit, jumlah_bayaran, kaedah_bayaran, rujukan_bayaran, bukti_pembayaran, tarikh_bayaran, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Menunggu Pengesahan')");
        mysqli_stmt_bind_param($stmt, "iidsss", $id_rawatan, $id_pesakit, $jumlah, $kaedah, $rujukan, $laluan_bukti);

        if (mysqli_stmt_execute($stmt)) {
            $mesej = 'Bukti pembayaran berjaya dihantar. Kakitangan klinik akan menyemak dan mengesahkan tidak lama lagi.';

            $staf = mysqli_query($conn, "SELECT id_kakitangan FROM kakitangan WHERE status_aktif = 'Aktif'");
            while ($s = mysqli_fetch_assoc($staf)) {
                hantar_notifikasi($conn, 'kakitangan', (int)$s['id_kakitangan'], 'Bukti pembayaran baharu', 'Ada bukti pembayaran ' . wang($jumlah) . ' menunggu pengesahan.', 'pembayaran', 'pengesahan.php');
            }
        } else {
            $mesej = 'Gagal hantar bukti pembayaran.';
            $jenis_mesej = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

$sql = "SELECT r.id_rawatan, r.nama_rawatan, r.harga_rawatan, r.tarikh_rawatan,
               b.status_pembayaran, b.kaedah_bayaran, b.bukti_pembayaran, b.catatan_bayaran
        FROM rekod_rawatan r
        LEFT JOIN pembayaran b ON b.id_rawatan = r.id_rawatan AND b.status_pembayaran IN ('Menunggu Pengesahan', 'Selesai')
        WHERE r.id_pesakit = ?
        ORDER BY r.tarikh_rawatan DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$kaedah_tersedia = array_map('trim', explode(',', tetapan($conn, 'kaedah_bayaran', 'Tunai')));
$kaedah_dalam_talian = array_values(array_filter($kaedah_tersedia, function ($k) {
    return strtolower($k) !== 'tunai';
}));

mula_halaman($conn, 'Buat Bayaran', 'pesakit', 'bayar.php');
echo pemilih_profil($conn, $id_akaun, $id_pesakit, 'bayar.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <div class="card-title">Maklumat Pembayaran Klinik</div>
    <div class="profile-item"><span class="info-label">Bank</span><span><?= selamat(tetapan($conn, 'nama_bank', '-')) ?></span></div>
    <div class="profile-item"><span class="info-label">No Akaun</span><span><?= selamat(tetapan($conn, 'no_akaun_bank', '-')) ?></span></div>
    <div class="profile-item"><span class="info-label">Nama Pemegang Akaun</span><span><?= selamat(tetapan($conn, 'nama_pemegang_akaun', '-')) ?></span></div>
    <?php if (tetapan($conn, 'qr_ewallet', '') !== '') { ?>
        <p>QR E-Wallet:</p>
        <img src="../<?= selamat(tetapan($conn, 'qr_ewallet')) ?>" alt="QR E-Wallet" style="max-width:220px;border-radius:14px">
    <?php } ?>
    <p>Selepas membuat pemindahan, muat naik bukti pembayaran di bawah. Kakitangan klinik akan menyemak dan status akan bertukar kepada Selesai.</p>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>Tarikh</th><th>Rawatan</th><th>Jumlah</th><th>Status</th><th>Muat Naik Bukti</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="5"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada rawatan direkodkan lagi.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat($row['tarikh_rawatan'] ? date('d/m/Y', strtotime($row['tarikh_rawatan'])) : '-') ?></td>
                <td><?= selamat($row['nama_rawatan']) ?></td>
                <td><?= wang($row['harga_rawatan']) ?></td>
                <td>
                    <?php if ($row['status_pembayaran']) { ?>
                        <span class="badge-status <?= kelas_status($row['status_pembayaran']) ?>"><?= selamat($row['status_pembayaran']) ?></span>
                    <?php } else { ?>
                        <span class="badge-status menunggu">Belum Bayar</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row['status_pembayaran'] === 'Selesai') { ?>
                        Telah disahkan
                    <?php } elseif ($row['status_pembayaran'] === 'Menunggu Pengesahan') { ?>
                        Menunggu semakan kakitangan
                    <?php } else { ?>
                        <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
                            <input type="hidden" name="id_rawatan" value="<?= (int)$row['id_rawatan'] ?>">
                            <div class="form-group">
                                <select name="kaedah_bayaran" class="form-control">
                                    <?php foreach ($kaedah_dalam_talian as $kaedah) { ?>
                                        <option value="<?= selamat($kaedah) ?>"><?= selamat($kaedah) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="rujukan_bayaran" class="form-control" placeholder="No rujukan transaksi">
                            </div>
                            <div class="form-group">
                                <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf" required>
                            </div>
                            <button class="btn" type="submit" name="hantar_bukti">Hantar Bukti</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
