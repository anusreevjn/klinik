<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/layout.php';

guard($conn, 'pentadbir', '../staff_login.php');

// Pastikan pengguna adalah pentadbir

// ====================================================================
// BAHAGIAN LOGIK & PROSES (PHP)
// ====================================================================

// --- PROSES UBAT ---
if (isset($_POST['tambah_ubat'])) {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $kuantiti_stok = (int)$_POST['kuantiti_stok'];
    $had_minimum_stok = (int)$_POST['had_minimum_stok'];
    $tarikh_luput = mysqli_real_escape_string($conn, $_POST['tarikh_luput']);
    $id_pentadbir = $_SESSION['user_id']; 

    $sql = "INSERT INTO inventori (id_pentadbir, nama_barang, kategori, kuantiti_stok, had_minimum_stok, tarikh_luput) 
            VALUES ('$id_pentadbir', '$nama_barang', '$kategori', '$kuantiti_stok', '$had_minimum_stok', '$tarikh_luput')";
    if (mysqli_query($conn, $sql)) header("Location: inventori.php?status=sukses_tambah");
}

if (isset($_POST['edit_ubat'])) {
    $id_inventori = (int)$_POST['id_inventori'];
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $kuantiti_stok = (int)$_POST['kuantiti_stok'];
    $had_minimum_stok = (int)$_POST['had_minimum_stok'];
    $tarikh_luput = mysqli_real_escape_string($conn, $_POST['tarikh_luput']);

    $sql = "UPDATE inventori SET nama_barang = '$nama_barang', kategori = '$kategori', 
            kuantiti_stok = '$kuantiti_stok', had_minimum_stok = '$had_minimum_stok', tarikh_luput = '$tarikh_luput' 
            WHERE id_inventori = $id_inventori";
    if (mysqli_query($conn, $sql)) header("Location: inventori.php?status=sukses_edit");
}

if (isset($_POST['padam_id'])) {
    $id_padam = (int)$_POST['padam_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM inventori WHERE id_inventori = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_padam);
    if (mysqli_stmt_execute($stmt)) {
        audit($conn, 'inventori_dipadam', $id_padam, '');
        mysqli_stmt_close($stmt);
        header("Location: inventori.php?status=sukses_padam");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// --- PROSES KATEGORI ---
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    if ($nama_kategori !== '' && mb_strlen($nama_kategori) <= 50) {
        $stmt = mysqli_prepare($conn, "INSERT INTO kategori_inventori (nama_kategori) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nama_kategori);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: inventori.php?status=sukses_kategori&tab=kategori");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['padam_kategori'])) {
    $id_kat = (int)$_POST['padam_kategori'];
    $stmt = mysqli_prepare($conn, "DELETE FROM kategori_inventori WHERE id_kategori = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_kat);
    if (mysqli_stmt_execute($stmt)) {
        audit($conn, 'kategori_inventori_dipadam', $id_kat, '');
        mysqli_stmt_close($stmt);
        header("Location: inventori.php?status=padam_kategori&tab=kategori");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// ====================================================================
// DAPATKAN DATA UNTUK DIPAPARKAN
// ====================================================================
$result_inventori = mysqli_query($conn, "SELECT * FROM inventori ORDER BY nama_barang ASC");
$result_kategori = mysqli_query($conn, "SELECT * FROM kategori_inventori ORDER BY nama_kategori ASC");

// Simpan kategori dalam array untuk digunakan dalam Dropdown (<select>)
$senarai_kategori = [];
while($kat = mysqli_fetch_assoc($result_kategori)) {
    $senarai_kategori[] = $kat;
}
?>

<?php mula_halaman($conn, 'Pengurusan Inventori', 'pentadbir', 'inventori.php'); ?>

<style>
    .header-action{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
    .tab-menu{display:flex;gap:20px;border-bottom:2px solid var(--c-border);margin-bottom:20px}
    .tab-link{padding:10px 0;cursor:pointer;color:var(--c-muted);font-weight:600;border-bottom:3px solid transparent}
    .tab-link:hover{color:var(--c-primary)}
    .tab-link.active{color:var(--c-primary);border-bottom:3px solid var(--c-primary)}
    .tab-content{display:none}
    .tab-content.active{display:block}
    .stok-rendah{color:var(--c-danger,#dc2626);font-weight:700}
    .edit-btn{background:var(--c-primary-lt);color:var(--c-primary)}
</style>

        <div class="table-container">
            <?php if (isset($_GET['status'])): ?>
                <div class="alert success">Berjaya mengemaskini sistem pangkalan data!</div>
            <?php endif; ?>

            <div class="tab-menu">
                <div class="tab-link active" onclick="bukaTab(event, 'tab-ubat')">Daftar Ubat</div>
                <div class="tab-link" onclick="bukaTab(event, 'tab-kategori')">Kategori Ubat</div>
            </div>

            <div id="tab-ubat" class="tab-content active">
                <div class="header-action">
                    <div><h3>Senarai Ubat Terdaftar</h3></div>
                    <button class="btn-primary" onclick="bukaModal('modalTambahUbat')">+ Tambah Ubat</button>
                </div>
                <table>
                    <tr>
                        <th>NAMA BARANG</th>
                        <th>KATEGORI</th>
                        <th>KUANTITI STOK</th>
                        <th>HAD MINIMUM</th>
                        <th>TARIKH LUPUT</th>
                        <th>AKSI</th>
                    </tr>
                    <?php 
                    if (mysqli_num_rows($result_inventori) > 0) {
                        while($row = mysqli_fetch_assoc($result_inventori)) { 
                            $stok_class = ($row['kuantiti_stok'] <= $row['had_minimum_stok']) ? 'stok-rendah' : '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($row['kategori']) ?></td>
                        <td class="<?= $stok_class ?>"><?= $row['kuantiti_stok'] ?></td>
                        <td><?= $row['had_minimum_stok'] ?></td>
                        <td><?= $row['tarikh_luput'] ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="bukaModalEditUbat('<?= $row['id_inventori'] ?>', '<?= addslashes($row['nama_barang']) ?>', '<?= addslashes($row['kategori']) ?>', '<?= $row['kuantiti_stok'] ?>', '<?= $row['had_minimum_stok'] ?>', '<?= $row['tarikh_luput'] ?>')">Edit</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('Pasti mahu padam?');"><?= csrf_field() ?><button type="submit" name="padam_id" value="<?= (int)$row['id_inventori'] ?>" class="action-btn delete-btn">Padam</button></form>
                        </td>
                    </tr>
                    <?php } } else { echo "<tr><td colspan='6' style='text-align:center;'>Tiada rekod</td></tr>"; } ?>
                </table>
            </div>

            <div id="tab-kategori" class="tab-content">
                <div class="header-action">
                    <div><h3>Senarai Kategori Ubat</h3></div>
                    <button class="btn-primary" onclick="bukaModal('modalTambahKategori')">+ Tambah Kategori</button>
                </div>
                <table style="width: 50%;"> <tr>
                        <th>NAMA KATEGORI</th>
                        <th>AKSI</th>
                    </tr>
                    <?php 
                    if (count($senarai_kategori) > 0) {
                        foreach($senarai_kategori as $kat) { 
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('Pasti mahu padam kategori ini?');"><?= csrf_field() ?><button type="submit" name="padam_kategori" value="<?= (int)$kat['id_kategori'] ?>" class="action-btn delete-btn">Padam</button></form>
                        </td>
                    </tr>
                    <?php } } else { echo "<tr><td colspan='2' style='text-align:center;'>Tiada kategori</td></tr>"; } ?>
                </table>
            </div>

        </div>

<div id="modalTambahUbat" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('modalTambahUbat')">&times;</span>
        <h3>Tambah Ubat Baru</h3>
        <form action="inventori.php" method="POST"><?= csrf_field() ?>
            <input type="hidden" name="tambah_ubat" value="1">
            <div class="form-group"><label>Nama Ubat/Barang</label><input type="text" name="nama_barang" required></div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach($senarai_kategori as $k): ?>
                        <option value="<?= $k['nama_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap: 15px;">
                <div class="form-group" style="flex: 1;"><label>Stok</label><input type="number" name="kuantiti_stok" required></div>
                <div class="form-group" style="flex: 1;"><label>Had Minimum</label><input type="number" name="had_minimum_stok" required></div>
            </div>
            <div class="form-group"><label>Tarikh Luput</label><input type="date" name="tarikh_luput" required></div>
            <div class="form-group" style="text-align: right;"><button type="submit" class="btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div id="modalEditUbat" class="modal">
    <div class="modal-content">
        <span class="close" onclick="tutupModal('modalEditUbat')">&times;</span>
        <h3>Kemaskini Ubat</h3>
        <form action="inventori.php" method="POST"><?= csrf_field() ?>
            <input type="hidden" name="edit_ubat" value="1">
            <input type="hidden" name="id_inventori" id="edit_id">
            <div class="form-group"><label>Nama Ubat</label><input type="text" name="nama_barang" id="edit_nama" required></div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" id="edit_kategori" required>
                    <?php foreach($senarai_kategori as $k): ?>
                        <option value="<?= $k['nama_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap: 15px;">
                <div class="form-group" style="flex: 1;"><label>Stok</label><input type="number" name="kuantiti_stok" id="edit_stok" required></div>
                <div class="form-group" style="flex: 1;"><label>Had Min</label><input type="number" name="had_minimum_stok" id="edit_had" required></div>
            </div>
            <div class="form-group"><label>Tarikh Luput</label><input type="date" name="tarikh_luput" id="edit_luput" required></div>
            <div class="form-group" style="text-align: right;"><button type="submit" class="btn-primary">Kemaskini</button></div>
        </form>
    </div>
</div>

<div id="modalTambahKategori" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="tutupModal('modalTambahKategori')">&times;</span>
        <h3>Tambah Kategori Baru</h3>
        <form action="inventori.php" method="POST"><?= csrf_field() ?>
            <input type="hidden" name="tambah_kategori" value="1">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" required placeholder="Cth: Suplemen">
            </div>
            <div class="form-group" style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<script>
    // FUNGSI TABS (Tukar paparan)
    function bukaTab(evt, tabName) {
        var i, tabcontent, tablinks;
        // Sorokkan semua kandungan tab
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        // Buang class 'active' pada semua butang tab
        tablinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        // Tunjuk tab yang dipilih
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");
    }

    // Kekalkan Tab Kategori jika URL mempunyai parameter ?tab=kategori
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab') === 'kategori') {
        document.querySelector('.tab-link:nth-child(2)').click();
    }

    // FUNGSI MODALS
    function bukaModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function tutupModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function bukaModalEditUbat(id, nama, kategori, stok, had, luput) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_kategori').value = kategori;
        document.getElementById('edit_stok').value = stok;
        document.getElementById('edit_had').value = had;
        document.getElementById('edit_luput').value = luput;
        bukaModal('modalEditUbat');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

<?php tamat_halaman(); ?>