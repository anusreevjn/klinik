<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_pentadbir = guard($conn, 'pentadbir', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['simpan'])) {
    $id = (int)$_POST['id_kod'];
    $kod = strtoupper(trim($_POST['kod_rawatan']));
    $nama = trim($_POST['nama_rawatan']);
    $harga = (float)$_POST['harga'];
    $harga_maksimum = $_POST['harga_maksimum'] !== '' ? (float)$_POST['harga_maksimum'] : null;
    $catatan_harga = trim($_POST['catatan_harga']);

    if ($kod === '' || $nama === '') {
        $mesej = 'Kod dan nama rawatan wajib diisi.';
        $jenis_mesej = 'error';
    } elseif ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE kod_rawatan SET kod_rawatan = ?, nama_rawatan = ?, harga = ?, harga_maksimum = ?, catatan_harga = ? WHERE id_kod = ?");
        mysqli_stmt_bind_param($stmt, "ssddsi", $kod, $nama, $harga, $harga_maksimum, $catatan_harga, $id);
        $mesej = mysqli_stmt_execute($stmt) ? 'Rawatan berjaya dikemas kini.' : 'Gagal kemas kini rawatan.';
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO kod_rawatan (kod_rawatan, nama_rawatan, harga, harga_maksimum, catatan_harga) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdds", $kod, $nama, $harga, $harga_maksimum, $catatan_harga);
        $mesej = mysqli_stmt_execute($stmt) ? 'Rawatan baharu berjaya ditambah.' : 'Gagal tambah rawatan.';
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['padam'])) {
    $id = (int)$_POST['padam'];
    $stmt = mysqli_prepare($conn, "DELETE FROM kod_rawatan WHERE id_kod = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    $mesej = mysqli_stmt_execute($stmt) ? 'Rawatan berjaya dipadam.' : 'Gagal padam rawatan.';
    mysqli_stmt_close($stmt);
    audit($conn, 'kod_rawatan_dipadam', $id, '');
}

$edit = ['id_kod' => 0, 'kod_rawatan' => '', 'nama_rawatan' => '', 'harga' => '', 'harga_maksimum' => '', 'catatan_harga' => ''];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM kod_rawatan WHERE id_kod = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $dapat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($dapat) {
        $edit = $dapat;
    }
}

$senarai = mysqli_query($conn, "SELECT * FROM kod_rawatan ORDER BY kod_rawatan ASC");
$jumlah = mysqli_query($conn, "SELECT COUNT(*) AS bil, AVG(harga) AS purata FROM kod_rawatan");
$ringkasan = mysqli_fetch_assoc($jumlah);

mula_halaman($conn, 'Pengurusan Rawatan', 'pentadbir', 'rawatan.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="dashboard-card">
    <p>Jumlah Jenis Rawatan</p>
    <h2><?= (int)$ringkasan['bil'] ?></h2>
    <p>Purata harga <?= wang($ringkasan['purata']) ?></p>
</div>

<div class="card">
    <div class="card-title"><?= (int)$edit['id_kod'] > 0 ? 'Kemaskini Rawatan' : 'Tambah Rawatan Baharu' ?></div>
    <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="id_kod" value="<?= (int)$edit['id_kod'] ?>">
        <div class="form-group">
            <label>Kod Rawatan</label>
            <input type="text" name="kod_rawatan" class="form-control" value="<?= selamat($edit['kod_rawatan']) ?>" placeholder="Contoh R016" required>
        </div>
        <div class="form-group">
            <label>Nama Rawatan</label>
            <input type="text" name="nama_rawatan" class="form-control" value="<?= selamat($edit['nama_rawatan']) ?>" required>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Harga Minimum (RM)</label>
                <input type="number" step="0.01" min="0" name="harga" class="form-control" value="<?= selamat($edit['harga']) ?>" required>
            </div>
            <div class="form-group">
                <label>Harga Maksimum (RM)</label>
                <input type="number" step="0.01" min="0" name="harga_maksimum" class="form-control" value="<?= selamat($edit['harga_maksimum']) ?>" placeholder="Kosongkan jika harga tetap">
            </div>
        </div>
        <div class="form-group">
            <label>Catatan Harga</label>
            <input type="text" name="catatan_harga" class="form-control" value="<?= selamat($edit['catatan_harga']) ?>" placeholder="Contoh: RM80 biasa, RM130 jika banyak kalkulus">
        </div>
        <div class="btn-group">
            <button class="btn" type="submit" name="simpan">Simpan</button>
            <?php if ((int)$edit['id_kod'] > 0) { ?>
                <a class="btn btn-back" href="rawatan.php">Batal</a>
            <?php } ?>
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr><th>Kod</th><th>Nama Rawatan</th><th>Harga</th><th>Catatan</th><th>Tindakan</th></tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= selamat($row['kod_rawatan']) ?></td>
                <td><?= selamat($row['nama_rawatan']) ?></td>
                <td><?= wang($row['harga']) ?><?= $row['harga_maksimum'] ? ' hingga ' . wang($row['harga_maksimum']) : '' ?></td>
                <td style="max-width:260px"><?= selamat($row['catatan_harga'] ?: '-') ?></td>
                <td>
                    <a class="action-btn" href="rawatan.php?edit=<?= (int)$row['id_kod'] ?>">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Padam rawatan ini?')"><?= csrf_field() ?><button class="action-btn delete-btn" type="submit" name="padam" value="<?= (int)$row['id_kod'] ?>">Padam</button></form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php tamat_halaman();
