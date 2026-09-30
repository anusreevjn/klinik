<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_kakitangan = guard($conn, 'kakitangan');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['daftar_pesakit'])) {
    $nama = trim($_POST['nama_pesakit']);
    $ic = trim($_POST['no_ic']);
    $jantina = $_POST['jantina'];
    $tarikh_lahir = $_POST['tarikh_lahir'];
    $telefon = trim($_POST['no_telefon']);
    $email = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $golongan = $_POST['golongan'];
    $penyakit = trim($_POST['penyakit_kronik']);
    $nama_penjaga = trim($_POST['nama_penjaga']);
    $telefon_penjaga = trim($_POST['telefon_penjaga']);
    $empat_akhir = substr($ic, -4);
    $kata_laluan = password_hash(substr($ic, 0, 6), PASSWORD_DEFAULT);

    $semak = mysqli_prepare($conn, "SELECT id_pesakit FROM pesakit WHERE no_ic = ? OR email = ? LIMIT 1");
    mysqli_stmt_bind_param($semak, "ss", $ic, $email);
    mysqli_stmt_execute($semak);
    $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
    mysqli_stmt_close($semak);

    if ($ada) {
        $mesej = 'Pesakit dengan no IC atau email ini sudah berdaftar.';
        $jenis_mesej = 'warning';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO pesakit (nama_pesakit, no_ic, id_cariana_ic, jantina, golongan, tarikh_lahir, alamat, no_telefon, email, nama_penjaga, telefon_penjaga, kata_laluan, tarikh_daftar, penyakit_kronik, cabut_gigi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Tidak')");
        mysqli_stmt_bind_param($stmt, "sssssssssssss", $nama, $ic, $empat_akhir, $jantina, $golongan, $tarikh_lahir, $alamat, $telefon, $email, $nama_penjaga, $telefon_penjaga, $kata_laluan, $penyakit);

        if (mysqli_stmt_execute($stmt)) {
            $mesej = 'Pesakit berjaya didaftarkan. Kata laluan sementara ialah 6 angka pertama no IC.';
        } else {
            $mesej = 'Gagal daftar pesakit: ' . mysqli_error($conn);
            $jenis_mesej = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['kemaskini_pesakit'])) {
    $id = (int)$_POST['id_pesakit'];
    $nama = trim($_POST['nama_pesakit']);
    $telefon = trim($_POST['no_telefon']);
    $email = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $penyakit = trim($_POST['penyakit_kronik']);

    $stmt = mysqli_prepare($conn, "UPDATE pesakit SET nama_pesakit = ?, no_telefon = ?, email = ?, alamat = ?, penyakit_kronik = ? WHERE id_pesakit = ?");
    mysqli_stmt_bind_param($stmt, "sssssi", $nama, $telefon, $email, $alamat, $penyakit, $id);
    $mesej = mysqli_stmt_execute($stmt) ? 'Maklumat pesakit dikemas kini.' : 'Gagal kemas kini maklumat.';
    mysqli_stmt_close($stmt);
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_pesakit = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$carian = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT p.*, (SELECT COUNT(*) FROM temu_janji t WHERE t.id_pesakit = p.id_pesakit) AS bil_tj FROM pesakit p";
if ($carian !== '') {
    $sql .= " WHERE p.nama_pesakit LIKE ? OR p.no_ic LIKE ? OR p.no_telefon LIKE ?";
}
$sql .= " ORDER BY p.id_pesakit DESC LIMIT 100";

$stmt = mysqli_prepare($conn, $sql);
if ($carian !== '') {
    $corak = '%' . $carian . '%';
    mysqli_stmt_bind_param($stmt, "sss", $corak, $corak, $corak);
}
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

mula_halaman($conn, 'Pengurusan Pesakit', 'kakitangan', 'pesakit.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <form method="get" class="date-group">
        <input type="text" name="q" class="form-control" placeholder="Cari nama, no IC atau telefon" value="<?= selamat($carian) ?>">
        <button class="btn" type="submit">Cari</button>
    </form>
</div>

<?php if ($edit) { ?>
<div class="card">
    <div class="card-title">Kemaskini Pesakit</div>
    <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="id_pesakit" value="<?= (int)$edit['id_pesakit'] ?>">
        <div class="form-group"><label>Nama</label><input type="text" name="nama_pesakit" class="form-control" value="<?= selamat($edit['nama_pesakit']) ?>" required></div>
        <div class="form-group"><label>No Telefon</label><input type="text" name="no_telefon" class="form-control" value="<?= selamat($edit['no_telefon']) ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= selamat($edit['email']) ?>"></div>
        <div class="form-group"><label>Alamat</label><textarea name="alamat" class="form-control"><?= selamat($edit['alamat']) ?></textarea></div>
        <div class="form-group"><label>Penyakit Kronik</label><input type="text" name="penyakit_kronik" class="form-control" value="<?= selamat($edit['penyakit_kronik']) ?>"></div>
        <div class="btn-group">
            <button class="btn" type="submit" name="kemaskini_pesakit">Simpan</button>
            <a class="btn btn-back" href="pesakit.php">Batal</a>
        </div>
    </form>
</div>
<?php } else { ?>
<div class="card">
    <div class="card-title">Daftar Pesakit Baharu</div>
    <form method="post"><?= csrf_field() ?>
        <div class="form-group"><label>Nama Penuh</label><input type="text" name="nama_pesakit" class="form-control" required></div>
        <div class="date-group">
            <div class="form-group"><label>No IC</label><input type="text" name="no_ic" class="form-control" required></div>
            <div class="form-group"><label>Tarikh Lahir</label><input type="date" name="tarikh_lahir" class="form-control" required></div>
        </div>
        <div class="date-group">
            <div class="form-group">
                <label>Jantina</label>
                <select name="jantina" class="form-control"><option>Lelaki</option><option>Perempuan</option></select>
            </div>
            <div class="form-group">
                <label>Golongan</label>
                <select name="golongan" class="form-control"><option>Dewasa</option><option>Kanak-kanak</option><option>Warga Emas</option></select>
            </div>
        </div>
        <div class="date-group">
            <div class="form-group"><label>No Telefon</label><input type="text" name="no_telefon" class="form-control" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Alamat</label><textarea name="alamat" class="form-control"></textarea></div>
        <div class="form-group"><label>Penyakit Kronik</label><input type="text" name="penyakit_kronik" class="form-control" placeholder="Tiada"></div>
        <div class="date-group">
            <div class="form-group"><label>Nama Penjaga</label><input type="text" name="nama_penjaga" class="form-control"></div>
            <div class="form-group"><label>Telefon Penjaga</label><input type="text" name="telefon_penjaga" class="form-control"></div>
        </div>
        <button class="btn" type="submit" name="daftar_pesakit">Daftar Pesakit</button>
    </form>
</div>
<?php } ?>

<div class="table-container">
    <table>
        <thead><tr><th>ID</th><th>Nama</th><th>No IC</th><th>Telefon</th><th>Golongan</th><th>Temu Janji</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="7"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada pesakit dijumpai.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) { ?>
            <tr>
                <td><?= (int)$row['id_pesakit'] ?></td>
                <td><?= selamat($row['nama_pesakit']) ?></td>
                <td><?= selamat($row['no_ic']) ?></td>
                <td><?= selamat($row['no_telefon']) ?></td>
                <td><?= selamat($row['golongan'] ?: '-') ?></td>
                <td><?= (int)$row['bil_tj'] ?></td>
                <td><a class="action-btn" href="pesakit.php?edit=<?= (int)$row['id_pesakit'] ?>">Edit</a>
                    <a class="action-btn btn-outline" target="_blank" href="../doktor/kad_rawatan.php?id_p=<?= (int)$row['id_pesakit'] ?>">Kad Rawatan</a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
