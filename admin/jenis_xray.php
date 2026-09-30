<?php
require_once '../config.php';
require_once '../include/layout.php';

guard($conn, 'pentadbir', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['simpan'])) {
    $id = (int)$_POST['id_jenis'];
    $nama = trim($_POST['nama_jenis']);
    $singkatan = trim($_POST['singkatan']);
    $status = $_POST['status_aktif'] === 'Aktif' ? 'Aktif' : 'Tidak Aktif';
    $susunan = (int)$_POST['susunan'];

    if ($nama === '') {
        $mesej = 'Nama jenis X-ray wajib diisi.';
        $jenis_mesej = 'error';
    } elseif ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE jenis_xray SET nama_jenis = ?, singkatan = ?, status_aktif = ?, susunan = ? WHERE id_jenis = ?");
        mysqli_stmt_bind_param($stmt, "sssii", $nama, $singkatan, $status, $susunan, $id);
        $mesej = mysqli_stmt_execute($stmt) ? 'Jenis X-ray dikemas kini.' : 'Gagal kemas kini.';
        mysqli_stmt_close($stmt);
        audit($conn, 'jenis_xray_dikemaskini', $id, $nama . '=' . $status);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO jenis_xray (nama_jenis, singkatan, status_aktif, susunan) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $singkatan, $status, $susunan);
        $mesej = mysqli_stmt_execute($stmt) ? 'Jenis X-ray baharu ditambah.' : 'Gagal tambah. Nama mungkin sudah ada.';
        mysqli_stmt_close($stmt);
        audit($conn, 'jenis_xray_ditambah', null, $nama);
    }
}

if (isset($_POST['tukar_status'])) {
    $id = (int)$_POST['tukar_status'];
    $status = $_POST['nilai_status'] === 'Aktif' ? 'Aktif' : 'Tidak Aktif';

    $stmt = mysqli_prepare($conn, "UPDATE jenis_xray SET status_aktif = ? WHERE id_jenis = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    audit($conn, 'jenis_xray_status_ditukar', $id, $status);
    $mesej = 'Status jenis X-ray ditukar kepada ' . $status . '.';
}

if (isset($_POST['padam'])) {
    $id = (int)$_POST['padam'];

    $semak = mysqli_prepare($conn, "SELECT COUNT(*) FROM xray WHERE id_jenis_xray = ?");
    mysqli_stmt_bind_param($semak, "i", $id);
    mysqli_stmt_execute($semak);
    mysqli_stmt_bind_result($semak, $bil);
    mysqli_stmt_fetch($semak);
    mysqli_stmt_close($semak);

    if ((int)$bil > 0) {
        $mesej = 'Jenis ini sudah digunakan dalam ' . (int)$bil . ' rekod X-ray. Gunakan Tidak Aktif supaya rekod lama kekal.';
        $jenis_mesej = 'warning';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM jenis_xray WHERE id_jenis = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $mesej = mysqli_stmt_execute($stmt) ? 'Jenis X-ray dipadam.' : 'Gagal padam.';
        mysqli_stmt_close($stmt);
        audit($conn, 'jenis_xray_dipadam', $id, '');
    }
}

$edit = ['id_jenis' => 0, 'nama_jenis' => '', 'singkatan' => '', 'status_aktif' => 'Aktif', 'susunan' => 0];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM jenis_xray WHERE id_jenis = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $dapat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($dapat) {
        $edit = $dapat;
    }
}

$senarai = mysqli_query($conn, "SELECT j.*, (SELECT COUNT(*) FROM xray x WHERE x.id_jenis_xray = j.id_jenis) AS bil_guna FROM jenis_xray j ORDER BY j.susunan ASC, j.nama_jenis ASC");

mula_halaman($conn, 'Jenis X-Ray', 'pentadbir', 'jenis_xray.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <p>Senarai jenis X-ray di sini yang akan muncul dalam borang doktor. Awak boleh tambah jenis baharu, tukar nama, atau tandakan Tidak Aktif kalau klinik tidak menggunakan jenis tersebut. Rekod lama tetap kekal walaupun jenis dinonaktifkan.</p>
</div>

<div class="card">
    <div class="card-title"><?= (int)$edit['id_jenis'] > 0 ? 'Kemaskini Jenis X-Ray' : 'Tambah Jenis X-Ray' ?></div>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id_jenis" value="<?= (int)$edit['id_jenis'] ?>">
        <div class="date-group">
            <div class="form-group">
                <label>Nama Jenis</label>
                <input type="text" name="nama_jenis" class="form-control" value="<?= selamat($edit['nama_jenis']) ?>" placeholder="Contoh Periapical" required>
            </div>
            <div class="form-group">
                <label>Singkatan</label>
                <input type="text" name="singkatan" class="form-control" value="<?= selamat($edit['singkatan']) ?>" placeholder="Contoh PA">
            </div>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Status</label>
                <select name="status_aktif" class="form-control">
                    <option value="Aktif" <?= $edit['status_aktif'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= $edit['status_aktif'] === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="form-group">
                <label>Susunan</label>
                <input type="number" name="susunan" class="form-control" value="<?= (int)$edit['susunan'] ?>">
            </div>
        </div>
        <div class="btn-group">
            <button class="btn" type="submit" name="simpan">Simpan</button>
            <?php if ((int)$edit['id_jenis'] > 0) { ?>
                <a class="btn btn-back" href="jenis_xray.php">Batal</a>
            <?php } ?>
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>Susunan</th><th>Nama</th><th>Singkatan</th><th>Status</th><th>Digunakan</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= (int)$row['susunan'] ?></td>
                <td><?= selamat($row['nama_jenis']) ?></td>
                <td><?= selamat($row['singkatan'] ?: '-') ?></td>
                <td><span class="badge-status <?= kelas_status($row['status_aktif']) ?>"><?= selamat($row['status_aktif']) ?></span></td>
                <td><?= (int)$row['bil_guna'] ?> rekod</td>
                <td>
                    <a class="action-btn" href="jenis_xray.php?edit=<?= (int)$row['id_jenis'] ?>">Edit</a>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="nilai_status" value="<?= $row['status_aktif'] === 'Aktif' ? 'Tidak Aktif' : 'Aktif' ?>">
                        <button class="action-btn <?= $row['status_aktif'] === 'Aktif' ? 'btn-back' : '' ?>" type="submit" name="tukar_status" value="<?= (int)$row['id_jenis'] ?>">
                            <?= $row['status_aktif'] === 'Aktif' ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Padam jenis ini?')">
                        <?= csrf_field() ?>
                        <button class="action-btn delete-btn" type="submit" name="padam" value="<?= (int)$row['id_jenis'] ?>">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php tamat_halaman();
