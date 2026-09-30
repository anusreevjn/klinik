<?php
require_once '../config.php';
require_once '../include/layout.php';
require_once '../include/pelan_lib.php';

$id_kakitangan = guard($conn, 'kakitangan', '../staff_login.php');

$mesej = '';
$jenis_mesej = 'success';

if (isset($_POST['cipta_pelan'])) {
    $ralat = null;
    $id_baru = cipta_pelan($conn, [
        'id_pesakit' => $_POST['id_pesakit'],
        'id_kod_rawatan' => $_POST['id_kod_rawatan'],
        'nama_pelan' => $_POST['nama_pelan'],
        'jumlah_keseluruhan' => $_POST['jumlah_keseluruhan'],
        'deposit' => $_POST['deposit'],
        'ansuran_dijangka' => $_POST['ansuran_dijangka'],
        'catatan' => $_POST['catatan'],
        'tarikh_mula' => $_POST['tarikh_mula'],
    ], $id_kakitangan, $ralat);

    if ($id_baru) {
        $mesej = 'Pelan ansuran berjaya dibuka.';
    } else {
        $mesej = $ralat;
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['rekod_bayaran'])) {
    $id_pelan = (int)$_POST['rekod_bayaran'];
    $jumlah = (float)$_POST['jumlah_bayaran'];
    $kaedah = trim($_POST['kaedah_bayaran']);
    $jenis = $_POST['jenis_bayaran'];
    $keterangan = null;

    if (rekod_bayaran_pelan($conn, $id_pelan, $jumlah, $kaedah, $jenis, $id_kakitangan, $keterangan)) {
        $mesej = $keterangan;
    } else {
        $mesej = $keterangan;
        $jenis_mesej = 'error';
    }
}

if (isset($_POST['batal_pelan'])) {
    $id_pelan = (int)$_POST['batal_pelan'];
    $sebab = trim($_POST['sebab']) !== '' ? trim($_POST['sebab']) : 'Dibatalkan oleh kakitangan';

    if (batalkan_pelan($conn, $id_pelan, $sebab)) {
        $mesej = 'Pelan dibatalkan.';
        $jenis_mesej = 'warning';
    } else {
        $mesej = 'Gagal batalkan pelan.';
        $jenis_mesej = 'error';
    }
}

$status_papar = isset($_GET['status']) ? $_GET['status'] : 'Aktif';
if (!in_array($status_papar, ['Semua', 'Aktif', 'Selesai', 'Dibatalkan'], true)) {
    $status_papar = 'Aktif';
}

$senarai = senarai_pelan($conn, null, $status_papar);
$pesakit_pilihan = mysqli_query($conn, "SELECT id_pesakit, nama_pesakit, no_pendaftaran_klinik FROM pesakit ORDER BY nama_pesakit ASC LIMIT 300");
$rawatan_pilihan = mysqli_query($conn, "SELECT id_kod, kod_rawatan, nama_rawatan, harga, harga_maksimum FROM kod_rawatan ORDER BY kod_rawatan ASC");
$kaedah_tersedia = array_map('trim', explode(',', tetapan($conn, 'kaedah_bayaran', 'Tunai')));

mula_halaman($conn, 'Pelan Ansuran', 'kakitangan', 'pelan.php');
?>

<?php if ($mesej !== '') { ?>
    <div class="alert <?= selamat($jenis_mesej) ?>"><?= selamat($mesej) ?></div>
<?php } ?>

<div class="card">
    <div class="card-title">Buka Pelan Ansuran Baharu</div>
    <p>Guna pelan ni untuk rawatan yang dibayar berperingkat seperti braces atau gigi palsu. Sistem akan kira jumlah dibayar, baki dan status sendiri.</p>

    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Pesakit</label>
            <select name="id_pesakit" class="form-control" required>
                <option value="">Pilih pesakit</option>
                <?php while ($p = mysqli_fetch_assoc($pesakit_pilihan)) { ?>
                    <option value="<?= (int)$p['id_pesakit'] ?>"><?= selamat($p['nama_pesakit']) ?><?= $p['no_pendaftaran_klinik'] ? ' (' . selamat($p['no_pendaftaran_klinik']) . ')' : '' ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="date-group">
            <div class="form-group">
                <label>Jenis Rawatan</label>
                <select name="id_kod_rawatan" class="form-control">
                    <option value="">Tidak dinyatakan</option>
                    <?php while ($r = mysqli_fetch_assoc($rawatan_pilihan)) { ?>
                        <option value="<?= (int)$r['id_kod'] ?>"><?= selamat($r['nama_rawatan']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nama Pelan</label>
                <input type="text" name="nama_pelan" class="form-control" placeholder="Contoh Braces Penuh 2026" required>
            </div>
        </div>

        <div class="date-group">
            <div class="form-group">
                <label>Jumlah Keseluruhan (RM)</label>
                <input type="number" step="0.01" min="0" name="jumlah_keseluruhan" class="form-control" placeholder="3500.00" required>
            </div>
            <div class="form-group">
                <label>Deposit (RM)</label>
                <input type="number" step="0.01" min="0" name="deposit" class="form-control" placeholder="500.00" value="0">
            </div>
            <div class="form-group">
                <label>Ansuran Dijangka (RM)</label>
                <input type="number" step="0.01" min="0" name="ansuran_dijangka" class="form-control" placeholder="150.00" value="0">
            </div>
        </div>

        <div class="date-group">
            <div class="form-group">
                <label>Tarikh Mula</label>
                <input type="date" name="tarikh_mula" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <input type="text" name="catatan" class="form-control" placeholder="Contoh bayaran setiap 6 hingga 8 minggu">
            </div>
        </div>

        <button class="btn" type="submit" name="cipta_pelan">Buka Pelan</button>
    </form>
</div>

<div class="card">
    <div class="btn-group">
        <?php foreach (['Aktif', 'Selesai', 'Dibatalkan', 'Semua'] as $status) { ?>
            <a class="tab-link <?= $status === $status_papar ? 'active' : '' ?>" href="pelan.php?status=<?= urlencode($status) ?>"><?= selamat($status) ?></a>
        <?php } ?>
    </div>
</div>

<?php if (mysqli_num_rows($senarai) === 0) { ?>
    <div class="card">Tiada pelan dalam status ini.</div>
<?php } ?>

<?php while ($row = mysqli_fetch_assoc($senarai)) {
    $baki = max(0, (float)$row['jumlah_keseluruhan'] - (float)$row['jumlah_dibayar']);
    $peratus = (float)$row['jumlah_keseluruhan'] > 0 ? round(((float)$row['jumlah_dibayar'] / (float)$row['jumlah_keseluruhan']) * 100) : 0;
    $transaksi = transaksi_pelan($conn, (int)$row['id_pelan']);
?>
    <div class="card">
        <div class="card-title">
            <?= selamat($row['nama_pelan']) ?>
            <span class="badge-status <?= kelas_status($row['status_pelan']) ?>"><?= selamat($row['status_pelan']) ?></span>
        </div>

        <div class="profile-item"><span class="info-label">Pesakit</span><span><?= selamat($row['nama_pesakit']) ?> (<?= selamat($row['no_pendaftaran_klinik'] ?: '-') ?>)</span></div>
        <div class="profile-item"><span class="info-label">Jumlah Keseluruhan</span><span><?= wang($row['jumlah_keseluruhan']) ?></span></div>
        <div class="profile-item"><span class="info-label">Deposit Ditetapkan</span><span><?= wang($row['deposit']) ?></span></div>
        <div class="profile-item"><span class="info-label">Ansuran Dijangka</span><span><?= wang($row['ansuran_dijangka']) ?></span></div>
        <div class="profile-item"><span class="info-label">Telah Dibayar</span><span><strong><?= wang($row['jumlah_dibayar']) ?></strong> (<?= $peratus ?>%)</span></div>
        <div class="profile-item"><span class="info-label">Baki</span><span><strong><?= wang($baki) ?></strong></span></div>
        <?php if ($row['catatan']) { ?>
            <div class="profile-item"><span class="info-label">Catatan</span><span><?= selamat($row['catatan']) ?></span></div>
        <?php } ?>

        <?php if ($row['status_pelan'] === 'Aktif') { ?>
            <hr class="pembahagi">
            <form method="post" class="date-group">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Jumlah (RM)</label>
                    <input type="number" step="0.01" min="0" name="jumlah_bayaran" class="form-control" value="<?= selamat($row['ansuran_dijangka'] > 0 ? $row['ansuran_dijangka'] : '') ?>" style="max-width:140px" required>
                </div>
                <div class="form-group">
                    <label>Jenis</label>
                    <select name="jenis_bayaran" class="form-control" style="max-width:140px">
                        <option value="Ansuran">Ansuran</option>
                        <option value="Deposit">Deposit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kaedah</label>
                    <select name="kaedah_bayaran" class="form-control" style="max-width:150px">
                        <?php foreach ($kaedah_tersedia as $kaedah) { ?>
                            <option value="<?= selamat($kaedah) ?>"><?= selamat($kaedah) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit" name="rekod_bayaran" value="<?= (int)$row['id_pelan'] ?>">Rekod Bayaran</button>
                </div>
            </form>

            <form method="post" class="date-group" onsubmit="return confirm('Batalkan pelan ini?')">
                <?= csrf_field() ?>
                <input type="text" name="sebab" class="form-control" placeholder="Sebab batal" style="max-width:240px">
                <button class="btn btn-batal" type="submit" name="batal_pelan" value="<?= (int)$row['id_pelan'] ?>">Batalkan Pelan</button>
            </form>
        <?php } ?>

        <div class="table-container">
            <table>
                <thead><tr><th>Tarikh</th><th>Jenis</th><th>Jumlah</th><th>Kaedah</th><th>No Resit</th><th>Direkod Oleh</th><th>Resit</th></tr></thead>
                <tbody>
                <?php if (mysqli_num_rows($transaksi) === 0) { ?>
                    <tr><td colspan="7"><div class="kosong"><div class="kosong-ikon"><i class="ti ti-inbox"></i></div><div class="kosong-tajuk">Belum ada bayaran.</div></div></td></tr>
                <?php } ?>
                <?php while ($t = mysqli_fetch_assoc($transaksi)) { ?>
                    <tr>
                        <td><?= selamat($t['tarikh_bayaran'] ? date('d/m/Y h:i A', strtotime($t['tarikh_bayaran'])) : '-') ?></td>
                        <td><?= selamat($t['jenis_bayaran']) ?></td>
                        <td><?= wang($t['jumlah_bayaran']) ?></td>
                        <td><?= selamat($t['kaedah_bayaran']) ?></td>
                        <td><?= selamat($t['no_resit'] ?: '-') ?></td>
                        <td><?= selamat($t['nama_kakitangan'] ?: '-') ?></td>
                        <td>
                            <?php if ($t['no_resit']) { ?>
                                <a class="action-btn" target="_blank" href="resit.php?id=<?= (int)$t['id_pembayaran'] ?>">Cetak</a>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>

<?php tamat_halaman();
