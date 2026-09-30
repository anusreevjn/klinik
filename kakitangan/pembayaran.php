<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/pembayaran_lib.php';

$id_kakitangan = guard($conn, 'kakitangan');

$mesej = '';
$jenis_mesej = 'success';
$id_pembayaran_baru = 0;

if (isset($_POST['sahkan_bayaran'])) {
    $id_rawatan = (int)$_POST['id_rawatan'];
    $kaedah = trim($_POST['kaedah_bayaran']);
    $jumlah = (float)$_POST['jumlah_bayaran'];

    $catatan_beza = trim(isset($_POST['catatan_beza']) ? $_POST['catatan_beza'] : '');

    $stmt = mysqli_prepare($conn, "SELECT r.*, p.nama_pesakit FROM rekod_rawatan r LEFT JOIN pesakit p ON p.id_pesakit = r.id_pesakit WHERE r.id_rawatan = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id_rawatan);
    mysqli_stmt_execute($stmt);
    $rawatan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $semak = mysqli_prepare($conn, "SELECT id_pembayaran FROM pembayaran WHERE id_rawatan = ? AND status_pembayaran = 'Selesai' LIMIT 1");
    mysqli_stmt_bind_param($semak, "i", $id_rawatan);
    mysqli_stmt_execute($semak);
    $sudah_bayar = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
    mysqli_stmt_close($semak);

    if (!$rawatan) {
        $mesej = 'Rekod rawatan tidak dijumpai.';
        $jenis_mesej = 'error';
    } elseif ($sudah_bayar) {
        $mesej = 'Rawatan ini sudah dibayar. Resit boleh dicetak dari senarai Invois.';
        $jenis_mesej = 'warning';
    } else {
        $harga_sepatutnya = $rawatan['harga_dikenakan'] !== null ? (float)$rawatan['harga_dikenakan'] : (float)$rawatan['harga_rawatan'];
        $beza = abs($jumlah - $harga_sepatutnya);

        if ($jumlah <= 0) {
            $mesej = 'Jumlah bayaran tidak sah.';
            $jenis_mesej = 'error';
            $rawatan = null;
        } elseif ($beza > 0.009 && $catatan_beza === '') {
            $mesej = 'Jumlah yang dimasukkan (' . wang($jumlah) . ') berbeza dengan harga rawatan (' . wang($harga_sepatutnya) . '). Sila isi sebab perbezaan sebelum sahkan.';
            $jenis_mesej = 'warning';
            $rawatan = null;
        }
    }

    if ($rawatan && !$sudah_bayar) {
        $id_pesakit = (int)$rawatan['id_pesakit'];

        $b = mysqli_prepare($conn, "INSERT INTO pembayaran (id_rawatan, id_kakitangan, id_pesakit, jumlah_bayaran, kaedah_bayaran, tarikh_bayaran, status_pembayaran) VALUES (?, ?, ?, ?, ?, NOW(), 'Belum Bayar')");
        mysqli_stmt_bind_param($b, "iiids", $id_rawatan, $id_kakitangan, $id_pesakit, $jumlah, $kaedah);
        mysqli_stmt_execute($b);
        $id_pembayaran = mysqli_insert_id($conn);
        mysqli_stmt_close($b);

        if ($beza > 0.009) {
            $nota = mysqli_prepare($conn, "UPDATE pembayaran SET catatan_bayaran = ? WHERE id_pembayaran = ?");
            $teks_nota = 'Beza dengan harga rawatan ' . wang($harga_sepatutnya) . '. Sebab: ' . $catatan_beza;
            mysqli_stmt_bind_param($nota, "si", $teks_nota, $id_pembayaran);
            mysqli_stmt_execute($nota);
            mysqli_stmt_close($nota);
            audit($conn, 'bayaran_beza_harga', $id_pembayaran, 'rawatan=' . $harga_sepatutnya . ';dibayar=' . $jumlah);
        }

        $keterangan = null;
        if (jelaskan_pembayaran($conn, $id_pembayaran, $id_kakitangan, $keterangan)) {
            $id_pembayaran_baru = $id_pembayaran;
            $mesej = $keterangan;
        } else {
            $mesej = $keterangan;
            $jenis_mesej = 'error';
        }
    }
}

$sql = "SELECT r.id_rawatan, r.nama_rawatan, r.harga_rawatan, r.tarikh_rawatan, r.id_temu_janji,
               k.harga AS harga_minimum_kod, k.harga_maksimum AS harga_maksimum_kod, k.catatan_harga,
               p.nama_pesakit, p.no_ic, d.nama_doktor,
               (SELECT COUNT(*) FROM pembayaran b WHERE b.id_rawatan = r.id_rawatan AND b.status_pembayaran = 'Selesai') AS sudah_bayar
        FROM rekod_rawatan r
        LEFT JOIN pesakit p ON p.id_pesakit = r.id_pesakit
        LEFT JOIN doktor d ON d.id_doktor = r.id_doktor
        LEFT JOIN kod_rawatan k ON k.kod_rawatan = r.kod_rawatan
        ORDER BY r.tarikh_rawatan DESC, r.id_rawatan DESC
        LIMIT 100";
$senarai = mysqli_query($conn, $sql);

$kaedah_tersedia = array_map('trim', explode(',', tetapan($conn, 'kaedah_bayaran', 'Tunai')));

mula_halaman($conn, 'Pembayaran', 'kakitangan', 'pembayaran.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>">
        <?= selamat($mesej) ?>
        <?php if ($id_pembayaran_baru > 0) { ?>
            <a class="btn" target="_blank" href="resit.php?id=<?= (int)$id_pembayaran_baru ?>">Cetak Resit</a>
        <?php } ?>
    </div>
<?php } ?>

<div class="table-container">
    <table>
        <thead><tr><th>Tarikh</th><th>Pesakit</th><th>Rawatan</th><th>Doktor</th><th>Harga</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat($row['tarikh_rawatan'] ? date('d/m/Y', strtotime($row['tarikh_rawatan'])) : '-') ?></td>
                <td><?= selamat($row['nama_pesakit'] ?: '-') ?></td>
                <td><?= selamat($row['nama_rawatan']) ?></td>
                <td><?= selamat($row['nama_doktor'] ?: '-') ?></td>
                <td>
                    <?= wang($row['harga_rawatan']) ?>
                    <?php if ($row['harga_maksimum_kod']) { ?>
                        <br><span class="invoice-date">Julat klinik: <?= wang($row['harga_minimum_kod']) ?> hingga <?= wang($row['harga_maksimum_kod']) ?></span>
                    <?php } ?>
                    <?php if ($row['catatan_harga']) { ?>
                        <br><span class="invoice-date"><?= selamat($row['catatan_harga']) ?></span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ((int)$row['sudah_bayar'] > 0) { ?>
                        <span class="badge-status selesai">Telah Dibayar</span>
                    <?php } else { ?>
                        <span class="badge-status menunggu">Belum Bayar</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ((int)$row['sudah_bayar'] > 0) { ?>
                        <a class="action-btn" href="invois.php?q=<?= urlencode($row['nama_pesakit']) ?>">Lihat Invois</a>
                    <?php } else { ?>
                        <form method="post" class="date-group"><?= csrf_field() ?>
                            <input type="hidden" name="id_rawatan" value="<?= (int)$row['id_rawatan'] ?>">
                            <input type="number" step="0.01" min="0" name="jumlah_bayaran" class="form-control" value="<?= selamat($row['harga_rawatan']) ?>" style="max-width:120px">
                            <input type="text" name="catatan_beza" class="form-control" placeholder="Sebab jika jumlah berbeza" style="max-width:190px">
                            <select name="kaedah_bayaran" class="form-control" style="max-width:130px">
                                <?php foreach ($kaedah_tersedia as $kaedah) { ?>
                                    <option value="<?= selamat($kaedah) ?>"><?= selamat($kaedah) ?></option>
                                <?php } ?>
                            </select>
                            <button class="btn" type="submit" name="sahkan_bayaran">Sahkan</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php tamat_halaman();
