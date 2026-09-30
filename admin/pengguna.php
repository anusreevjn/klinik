<?php
require_once '../config.php';
require_once '../include/layout.php';

$id_pentadbir = guard($conn, 'pentadbir', '../staff_login.php');

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'doktor';
if (!in_array($tab, ['doktor', 'kakitangan', 'pesakit'], true)) {
    $tab = 'doktor';
}

$mesej = '';
$jenis_mesej = 'success';

$peta = [
    'doktor' => ['jadual' => 'doktor', 'id' => 'id_doktor', 'nama' => 'nama_doktor'],
    'kakitangan' => ['jadual' => 'kakitangan', 'id' => 'id_kakitangan', 'nama' => 'nama_kakitangan'],
    'pesakit' => ['jadual' => 'pesakit', 'id' => 'id_pesakit', 'nama' => 'nama_pesakit'],
];

$semasa = $peta[$tab];

if (isset($_POST['simpan_edit'])) {
    $id = (int)$_POST['id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['no_telefon']);

    if ($tab === 'pesakit') {
        $stmt = mysqli_prepare($conn, "UPDATE pesakit SET nama_pesakit = ?, email = ?, no_telefon = ?, alamat = ? WHERE id_pesakit = ?");
        $alamat = trim($_POST['alamat']);
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $telefon, $alamat, $id);
    } elseif ($tab === 'doktor') {
        $stmt = mysqli_prepare($conn, "UPDATE doktor SET nama_doktor = ?, email = ?, no_telefon = ?, kepakaran = ?, no_lesen = ? WHERE id_doktor = ?");
        $kepakaran = trim($_POST['kepakaran']);
        $lesen = trim($_POST['no_lesen']);
        mysqli_stmt_bind_param($stmt, "sssssi", $nama, $email, $telefon, $kepakaran, $lesen, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE kakitangan SET nama_kakitangan = ?, email = ?, no_telefon = ?, jawatan = ? WHERE id_kakitangan = ?");
        $jawatan = trim($_POST['jawatan']);
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $telefon, $jawatan, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $mesej = 'Maklumat pengguna berjaya dikemas kini.';
    } else {
        $mesej = 'Gagal kemas kini: ' . mysqli_error($conn);
        $jenis_mesej = 'error';
    }
    mysqli_stmt_close($stmt);
}

if (isset($_POST['tukar_kata_laluan'])) {
    $id = (int)$_POST['id'];
    $baru = $_POST['kata_laluan_baru'];

    if (strlen($baru) < 6) {
        $mesej = 'Kata laluan mesti sekurang-kurangnya 6 aksara.';
        $jenis_mesej = 'error';
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $sql = "UPDATE {$semasa['jadual']} SET kata_laluan = ? WHERE {$semasa['id']} = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hash, $id);
        $mesej = mysqli_stmt_execute($stmt) ? 'Kata laluan berjaya ditetapkan semula. Pengguna perlu tukar kata laluan pada log masuk seterusnya.' : 'Gagal tetapkan kata laluan.';
        mysqli_stmt_close($stmt);

        $paksa = mysqli_prepare($conn, "UPDATE {$semasa['jadual']} SET mesti_tukar_kata_laluan = 1 WHERE {$semasa['id']} = ?");
        mysqli_stmt_bind_param($paksa, "i", $id);
        mysqli_stmt_execute($paksa);
        mysqli_stmt_close($paksa);

        audit($conn, 'kata_laluan_ditetapkan_semula', $id, $tab);
    }
}

if (isset($_POST['tukar_status']) && isset($_POST['id']) && $tab !== 'pesakit') {
    $id = (int)$_POST['id'];
    $status = $_POST['tukar_status'] === 'aktif' ? 'Aktif' : 'Tidak Aktif';
    $sql = "UPDATE {$semasa['jadual']} SET status_aktif = ? WHERE {$semasa['id']} = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    audit($conn, 'status_pengguna_ditukar', $id, $tab . '=' . $status);
    $mesej = "Status pengguna ditukar kepada $status.";
}

if (isset($_POST['padam']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $boleh_padam = true;

    if ($tab === 'pesakit') {
        $semak = mysqli_prepare($conn, "SELECT (SELECT COUNT(*) FROM temu_janji WHERE id_pesakit = ?) + (SELECT COUNT(*) FROM rekod_rawatan WHERE id_pesakit = ?) AS jumlah");
        mysqli_stmt_bind_param($semak, "ii", $id, $id);
        mysqli_stmt_execute($semak);
        $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
        mysqli_stmt_close($semak);
        $boleh_padam = (int)$baris['jumlah'] === 0;
    } elseif ($tab === 'doktor') {
        $semak = mysqli_prepare($conn, "SELECT (SELECT COUNT(*) FROM temu_janji WHERE id_doktor = ?) + (SELECT COUNT(*) FROM rekod_rawatan WHERE id_doktor = ?) AS jumlah");
        mysqli_stmt_bind_param($semak, "ii", $id, $id);
        mysqli_stmt_execute($semak);
        $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($semak));
        mysqli_stmt_close($semak);
        $boleh_padam = (int)$baris['jumlah'] === 0;
    }

    if ($boleh_padam) {
        $sql = "DELETE FROM {$semasa['jadual']} WHERE {$semasa['id']} = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        $mesej = mysqli_stmt_execute($stmt) ? 'Pengguna berjaya dipadam.' : 'Gagal padam pengguna.';
        mysqli_stmt_close($stmt);
        audit($conn, 'pengguna_dipadam', $id, $tab);
    } else {
        $mesej = 'Pengguna ini ada rekod temu janji atau rawatan. Gunakan Nyahaktif supaya rekod lama kekal.';
        $jenis_mesej = 'warning';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $sql = "SELECT * FROM {$semasa['jadual']} WHERE {$semasa['id']} = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$carian = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql_senarai = "SELECT * FROM {$semasa['jadual']}";
if ($carian !== '') {
    $sql_senarai .= " WHERE {$semasa['nama']} LIKE ? OR email LIKE ?";
}
$sql_senarai .= " ORDER BY {$semasa['id']} DESC";

$stmt = mysqli_prepare($conn, $sql_senarai);
if ($carian !== '') {
    $corak = '%' . $carian . '%';
    mysqli_stmt_bind_param($stmt, "ss", $corak, $corak);
}
mysqli_stmt_execute($stmt);
$senarai = mysqli_stmt_get_result($stmt);

$tindakan = '<a class="btn" href="tambah_doktor.php">Tambah Doktor</a><a class="btn btn-outline" href="tambah_kakitangan.php">Tambah Kakitangan</a>';
mula_halaman($conn, 'Pengurusan Pengguna', 'pentadbir', 'pengguna.php', $tindakan);
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <div class="btn-group">
        <a class="tab-link <?= $tab === 'doktor' ? 'active' : '' ?>" href="pengguna.php?tab=doktor">Doktor</a>
        <a class="tab-link <?= $tab === 'kakitangan' ? 'active' : '' ?>" href="pengguna.php?tab=kakitangan">Kakitangan</a>
        <a class="tab-link <?= $tab === 'pesakit' ? 'active' : '' ?>" href="pengguna.php?tab=pesakit">Pesakit</a>
    </div>

    <form method="get" class="date-group" style="margin-top:16px">
        <input type="hidden" name="tab" value="<?= selamat($tab) ?>">
        <input type="text" name="q" class="form-control" placeholder="Cari nama atau email" value="<?= selamat($carian) ?>">
        <button class="btn" type="submit">Cari</button>
    </form>
</div>

<?php if ($edit) { ?>
<div class="card">
    <div class="card-title">Kemaskini <?= selamat(ucfirst($tab)) ?></div>
    <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$edit[$semasa['id']] ?>">
        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control" value="<?= selamat($edit[$semasa['nama']]) ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= selamat($edit['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>No Telefon</label>
            <input type="text" name="no_telefon" class="form-control" value="<?= selamat(isset($edit['no_telefon']) ? $edit['no_telefon'] : '') ?>">
        </div>

        <?php if ($tab === 'doktor') { ?>
            <div class="form-group">
                <label>Kepakaran</label>
                <input type="text" name="kepakaran" class="form-control" value="<?= selamat($edit['kepakaran']) ?>">
            </div>
            <div class="form-group">
                <label>No Lesen</label>
                <input type="text" name="no_lesen" class="form-control" value="<?= selamat($edit['no_lesen']) ?>">
            </div>
        <?php } elseif ($tab === 'kakitangan') { ?>
            <div class="form-group">
                <label>Jawatan</label>
                <input type="text" name="jawatan" class="form-control" value="<?= selamat($edit['jawatan']) ?>">
            </div>
        <?php } else { ?>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"><?= selamat($edit['alamat']) ?></textarea>
            </div>
        <?php } ?>

        <div class="btn-group">
            <button class="btn" type="submit" name="simpan_edit">Simpan</button>
            <a class="btn btn-back" href="pengguna.php?tab=<?= selamat($tab) ?>">Batal</a>
        </div>
    </form>

    <hr class="pembahagi">

    <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$edit[$semasa['id']] ?>">
        <div class="form-group">
            <label>Tetapkan Kata Laluan Baru</label>
            <input type="text" name="kata_laluan_baru" class="form-control" placeholder="Minimum 6 aksara" required>
        </div>
        <button class="btn btn-outline" type="submit" name="tukar_kata_laluan">Tetapkan Semula Kata Laluan</button>
    </form>
</div>
<?php } ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Telefon</th>
                <th><?= $tab === 'doktor' ? 'Kepakaran' : ($tab === 'kakitangan' ? 'Jawatan' : 'No IC') ?></th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($senarai) === 0) { ?>
            <tr><td colspan="7"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Tiada rekod dijumpai.</div></div></td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($senarai)) {
            $id = (int)$row[$semasa['id']];
            $status = isset($row['status_aktif']) ? $row['status_aktif'] : 'Aktif';
            if ($tab === 'doktor') {
                $lajur = $row['kepakaran'];
            } elseif ($tab === 'kakitangan') {
                $lajur = $row['jawatan'];
            } else {
                $lajur = $row['no_ic'];
            }
        ?>
            <tr>
                <td><?= $id ?></td>
                <td><?= selamat($row[$semasa['nama']]) ?></td>
                <td><?= selamat($row['email']) ?></td>
                <td><?= selamat(isset($row['no_telefon']) ? $row['no_telefon'] : '-') ?></td>
                <td><?= selamat($lajur) ?></td>
                <td><span class="badge-status <?= kelas_status($status) ?>"><?= selamat($status) ?></span></td>
                <td>
                    <a class="action-btn" href="pengguna.php?tab=<?= selamat($tab) ?>&edit=<?= $id ?>">Edit</a>
                    <?php if ($tab !== 'pesakit') { ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Tukar status pengguna ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <?php if ($status === 'Aktif') { ?>
                                <button class="action-btn btn-back" type="submit" name="tukar_status" value="tidak">Nyahaktif</button>
                            <?php } else { ?>
                                <button class="action-btn" type="submit" name="tukar_status" value="aktif">Aktifkan</button>
                            <?php } ?>
                        </form>
                    <?php } ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Padam pengguna ini secara kekal?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="action-btn delete-btn" type="submit" name="padam" value="1">Padam</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php
mysqli_stmt_close($stmt);
tamat_halaman();
