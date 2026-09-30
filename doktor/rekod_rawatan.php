<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/kod_gigi.php';

guard($conn, 'doktor', '../staff_login.php');

// Ambil ID pesakit dan ID temu janji dari URL
$id_pesakit = isset($_GET['id_p']) ? (int)$_GET['id_p'] : 0;
$id_temujanji = isset($_GET['id_t']) ? mysqli_real_escape_string($conn, $_GET['id_t']) : 0;

// Dapatkan maklumat pesakit
$stmt_pesakit = mysqli_prepare($conn, "SELECT * FROM pesakit WHERE id_pesakit = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_pesakit, "i", $id_pesakit);
mysqli_stmt_execute($stmt_pesakit);
$pesakit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pesakit));
mysqli_stmt_close($stmt_pesakit);

if (!$pesakit) {
    require_once '../include/layout.php';
    mula_halaman($conn, 'Rekod Rawatan', 'doktor', 'rekod_rawatan.php');
    echo '<div class="card"><div class="kosong">';
    echo '<div class="kosong-ikon"><i class="ti ti-user-search"></i></div>';
    echo '<div class="kosong-tajuk">Belum ada pesakit dipilih</div>';
    echo '<p class="kosong-teks">Pilih pesakit dari Senarai Pesakit atau Papan Utama dahulu, kemudian klik Rekod Rawatan.</p>';
    echo '<div class="btn-group"><a class="btn" href="pesakit.php">Buka Senarai Pesakit</a>';
    echo '<a class="btn btn-back" href="dashboard.php">Papan Utama</a></div>';
    echo '</div></div>';
    tamat_halaman();
    exit();
}

// Dapatkan senarai kod rawatan & ubat
$query_kod = mysqli_query($conn, "SELECT * FROM kod_rawatan ORDER BY kod_rawatan ASC");
$query_ubat = mysqli_query($conn, "SELECT * FROM inventori ORDER BY nama_barang ASC");

// Kira Umur
$tarikh_lahir = $pesakit['tarikh_lahir'] ?? '1990-01-01'; 
$dob = new DateTime($tarikh_lahir);
$now = new DateTime();
$umur = $now->diff($dob)->y;

// Inisial nama untuk Avatar
$nama_parts = explode(' ', $pesakit['nama_pesakit']);
$inisial = strtoupper(substr($nama_parts[0], 0, 1) . (isset($nama_parts[1]) ? substr($nama_parts[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekod Rawatan - Klinik Pergigian Dr. Arifin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=7">
<link rel="stylesheet" href="../assets/css/theme.css?v=7">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">
    
    <style>
        .rawatan-container { display: flex; gap: 20px; align-items: flex-start; }
        .patient-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); width: 280px; flex-shrink: 0; }
        .profile-section { text-align: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .avatar { width: 60px; height: 60px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; color: #495057; font-size: 20px; }
        .info-group { margin-bottom: 12px; font-size: 14px; color: #333; }
        .info-label { font-weight: bold; color: #6c757d; margin-bottom: 3px; display:block; }
        
        .rawatan-content { flex-grow: 1; display: flex; flex-direction: column; gap: 20px; }
        .card-custom { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        
        /* CARTA GIGI */
        .rahang-label { text-align: center; font-size: 12px; font-weight: bold; color: #868e96; text-transform: uppercase; margin: 15px 0 5px; }
        .rahang { display: flex; justify-content: center; gap: 4px; margin-bottom: 10px; }
        .gigi { width: 32px; height: 32px; border: 1px solid #dee2e6; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; cursor: pointer; background: #fff; transition: all 0.2s; }
        .gigi:hover { background: #f1f3f5; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .pembahagi { width: 20px; }
        
        /* WARNA STATUS GIGI PADA CARTA */
        .gigi.status-kaviti { border: 2px solid #fa5252 !important; color: #fa5252 !important; background: #fff5f5 !important; }
        .gigi.status-mahkota { border: 2px solid #fab005 !important; color: #fab005 !important; background: #fff9db !important; }
        .gigi.status-dirawat { border: 2px solid #228be6 !important; color: #228be6 !important; background: #e7f5ff !important; }
        .gigi.status-tiada { background: #e9ecef !important; color: #adb5bd !important; border-color: #dee2e6 !important; }
        .gigi.status-sihat { border: 1px solid #dee2e6 !important; color: #2f9e44 !important; background: #ebfbee !important; }

        /* GIGI YANG SEDANG DIPILIH (AKTIF) */
        .gigi.active-gigi { border: 2px dashed #000 !important; transform: scale(1.15); box-shadow: 0 4px 8px rgba(0,0,0,0.2); z-index: 10; }

        /* FORM BATCH */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #495057; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 14px; }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .btn-header-group { display: flex; gap: 10px; }
        .btn-simpan { background: #2f9e44; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight:bold; }
        .btn-kembali { background: #e5e7eb; color: #374151; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight:bold; }
        
        /* CUSTOM RADIO BUTTONS (Rekaan Gambar Pertama) */
        .radio-tools { text-align: center; margin-top: 25px; margin-bottom: 10px; padding: 10px; }
        .radio-tools label { display: inline-flex; align-items: center; gap: 8px; margin: 0 15px; cursor: pointer; font-weight: 500; font-size: 14px; color: #000; }
        
        .radio-tools input[type="radio"] { 
            appearance: none; 
            -webkit-appearance: none; 
            width: 18px; 
            height: 18px; 
            border-radius: 50%; 
            background-color: #fff; 
            margin: 0;
            cursor: pointer; 
            position: relative; 
            outline: none;
            transition: all 0.2s ease;
        }

        /* Warna Garisan Bulatan Kosong */
        .radio-tools input[value="Sihat"] { border: 2px solid #dee2e6; }
        .radio-tools input[value="Kaviti"] { border: 2px solid #fa5252; }
        .radio-tools input[value="Mahkota"] { border: 2px solid #fab005; }
        .radio-tools input[value="Tiada"] { border: 2px solid #e9ecef; background-color: #f1f3f5; }
        .radio-tools input[value="Dirawat"] { border: 2px solid #228be6; }

        /* Isi Bulatan Apabila Dipilih (Titik Tengah) */
        .radio-tools input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .radio-tools input[value="Sihat"]:checked { border-color: #2f9e44; } /* Tunjuk sikit warna hijau bila klik siap */
        .radio-tools input[value="Sihat"]:checked::after { background: #2f9e44; }
        .radio-tools input[value="Kaviti"]:checked::after { background: #fa5252; }
        .radio-tools input[value="Mahkota"]:checked::after { background: #fab005; }
        .radio-tools input[value="Tiada"]:checked::after { background: #adb5bd; }
        .radio-tools input[value="Dirawat"]:checked::after { background: #228be6; }

    </style>
</head>

<body>

<div class="dashboard">
    <?php require_once '../include/layout.php'; include '../include/sidebar.php'; ?>

    <div class="main">
<?php if (isset($_GET['ralat']) && $_GET['ralat'] === 'harga') { ?>
    <div class="alert error">Harga yang dimasukkan berada di luar julat klinik. Sila isi sebab perubahan harga sebelum simpan.</div>
<?php } ?>

        <form action="simpan_rekod.php" method="POST" enctype="multipart/form-data" onsubmit="return sahkanForm()"><?= csrf_field() ?>
            <div class="topbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Borang Rawatan Pesakit</h3>
                <div class="btn-header-group">
                    <a href="dashboard.php" class="btn-kembali">Kembali</a>
                    <button type="submit" class="btn-simpan">Simpan Rekod</button>
                </div>
            </div>

            <div class="rawatan-container">
                <div class="patient-card">
                    <div class="profile-section">
                        <div class="avatar"><?= $inisial ?></div>
                        <h3 style="margin:5px 0;"><?= htmlspecialchars($pesakit['nama_pesakit']) ?></h3>
                        <span style="font-size:12px; color:#868e96;">ID: <?= htmlspecialchars($pesakit['id_pesakit']) ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Tarikh Lahir</span>
                        <?= date("d M Y", strtotime($pesakit['tarikh_lahir'])) ?> (<?= $umur ?> thn)
                    </div>
                    <div class="info-group">
                        <span class="info-label">Jantina</span>
                        <?= htmlspecialchars($pesakit['jantina']) ?>
                    </div>
                    <div class="info-group" style="margin-top:20px;">
                        <span class="info-label">Hubungi</span>
                        📞 <?= htmlspecialchars($pesakit['no_telefon'] ?? 'Tiada') ?><br>
                        <span style="font-size:13px; color:#6c757d;">✉️ <?= htmlspecialchars($pesakit['email'] ?? 'Tiada email') ?></span>
                    </div>
                </div>

                <div class="rawatan-content">
                    
                    <div class="card-custom">
                        <div class="card-title">
                            Carta Gigi
                            <span id="gigi-dipilih-teks" style="color:#228be6; font-size:14px; font-weight: normal;">Sila klik pada mana-mana gigi</span>
                        </div>
                        
                        <div class="rahang-label">RAHANG ATAS</div>
                        <div class="rahang">
                            <?php for($i=18; $i>=11; $i--) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                            <div class="pembahagi"></div>
                            <?php for($i=21; $i<=28; $i++) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                        </div>

                        <div class="rahang" style="margin-top: 5px; margin-bottom: 25px;">
                            <?php for($i=55; $i>=51; $i--) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                            <div class="pembahagi"></div>
                            <?php for($i=61; $i<=65; $i++) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                        </div>

                        <div class="rahang-label">RAHANG BAWAH</div>
                        <div class="rahang" style="margin-bottom: 5px;">
                            <?php for($i=85; $i>=81; $i--) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                            <div class="pembahagi"></div>
                            <?php for($i=71; $i<=75; $i++) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                        </div>

                        <div class="rahang">
                            <?php for($i=48; $i>=41; $i--) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                            <div class="pembahagi"></div>
                            <?php for($i=31; $i<=38; $i++) { echo "<div class='gigi' id='gigi-$i' onclick='klikGigiCarta($i)'>$i</div>"; } ?>
                        </div>

                        <!-- RADIO BUTTON YANG TELAH DIKEMASKINI -->
                        <div class="radio-tools petunjuk-alat">
                            <?php foreach (kod_keadaan_gigi() as $kod => $maklumat) { ?>
                                <label style="border-left:4px solid <?= $maklumat['warna'] ?>; padding-left:8px">
                                    <input type="radio" name="status_tool" value="<?= $kod ?>" onchange="pilihStatusGigi(this.value)">
                                    <?= $kod ?> <?= htmlspecialchars($maklumat['nama'], ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-title">
                            Catatan Rawatan
                            <div id="list-gigi-rawatan" style="display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end;"></div>
                        </div>
                        
                        <input type="hidden" name="id_pesakit" value="<?= htmlspecialchars($id_pesakit) ?>">
                        <input type="hidden" name="id_temu_janji" value="<?= htmlspecialchars($id_temujanji) ?>">
                        
                        <div id="hidden-inputs-gigi"></div>

                        <div class="form-group">
                            <label>Jenis Golongan Gigi:</label>
                            <select name="jenis_gigi" class="form-control" required>
                                <option value="Gigi Dewasa">Gigi Dewasa</option>
                                <option value="Gigi Kanak-kanak">Gigi Kanak-kanak</option>
                            </select>
                        </div>

                        <!-- FUNGSI TAMBAH RAWATAN (BARU) -->
                        <div class="form-group">
                            <label>Senarai Rawatan Pergigian yang Dilakukan :</label>
                            <div style="display:grid; grid-template-columns:2fr 2fr auto; gap:10px; margin-bottom:10px;">
                                <input type="text" id="rawatan_kod" class="form-control" list="senaraiKod" placeholder="Cari kod atau nama rawatan">
                                <datalist id="senaraiKod">
                                    <?php while($kod = mysqli_fetch_assoc($query_kod)) { ?>
                                        <option value="<?= htmlspecialchars($kod['kod_rawatan']); ?> - <?= htmlspecialchars($kod['nama_rawatan']); ?>"></option>
                                    <?php } ?>
                                </datalist>
                                
                                <input type="text" id="rawatan_catatan" class="form-control" placeholder="Gigi / Catatan (cth: Cabut gigi 43)">
                                <button type="button" onclick="tambahRawatan()" style="background:#2f9e44;color:white;border:none;border-radius:8px;padding:10px 15px;cursor:pointer;font-weight:bold;">+ Tambah</button>
                            </div>
                            <div id="list-rawatan"></div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label>Harga Sebenar Dikenakan (RM) :</label>
                            <input type="number" step="0.01" min="0" name="harga_dikenakan" class="form-control" placeholder="Contoh 100.00">
                            <p style="font-size:13px;color:#63718c;margin-top:6px">Kosongkan untuk guna harga minimum dalam senarai rawatan. Kalau harga di luar julat klinik, sistem akan minta sebab.</p>
                        </div>

                        <div class="form-group">
                            <label>Sebab Jika Harga Di Luar Julat :</label>
                            <input type="text" name="sebab_harga" class="form-control" placeholder="Contoh kes lebih kompleks, scaling banyak kalkulus">
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label>Diagnosis / Keadaan Masalah (Umum) :</label>
                            <textarea name="diag_catatan" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Prosedur Keseluruhan / Nota Tambahan :</label>
                            <textarea name="pros_catatan" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Preskripsi Ubat</label>
                            <div style="display:grid; grid-template-columns:2fr 1fr 1fr 2fr auto; gap:10px; margin-bottom:10px;">
                                <select id="ubat" class="form-control">
                                    <option value="">Pilih Ubat</option>
                                    <?php while($u = mysqli_fetch_assoc($query_ubat)) { ?>
                                        <option value="<?= htmlspecialchars($u['id_inventori']) ?>">
                                            <?= htmlspecialchars($u['nama_barang']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <input type="number" id="kuantiti" class="form-control" placeholder="Kuantiti" min="1">
                                <input type="text" id="dos" class="form-control" list="senaraiDos" placeholder="Dos (cth: 500mg)">
                                <datalist id="senaraiDos">
                                    <option value="500mg"><option value="250mg"><option value="1 biji"><option value="2 biji">
                                </datalist>
                                <input type="text" id="arahan" class="form-control" list="senaraiArahan" placeholder="Arahan">
                                <datalist id="senaraiArahan">
                                    <option value="3 kali sehari (Selepas makan)"><option value="2 kali sehari (Selepas makan)"><option value="Bila perlu">
                                </datalist>
                                <button type="button" onclick="tambahUbat()" style="background:#228be6;color:white;border:none;border-radius:8px;padding:10px 15px;cursor:pointer;font-weight:bold;">+ Tambah</button>
                            </div>
                            <div id="list-ubat"></div>
                        </div>

                        <div class="form-group" style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px dashed #ced4da; margin-top:20px;">
                            <label style="color:#495057;">Muat Naik X-Ray (Jika Ada)</label>
                            <input type="file" name="xray" class="form-control" accept="image/jpeg, image/png, application/pdf">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Menyimpan rekod gigi. Cth: { 43: 'Kaviti', 35: 'Mahkota' }
    let dataGigiDipilih = {}; 
    let gigiAktifSemasa = null; // Menyimpan ID gigi yang sedang diklik

    // 1. Apabila doktor klik pada petak nombor gigi (PILIH GIGI DULU)
    function klikGigiCarta(noGigi) {
        // Buang highlight dari gigi yang dipilih sebelum ini
        if (gigiAktifSemasa !== null) {
            let oldGigi = document.getElementById('gigi-' + gigiAktifSemasa);
            if (oldGigi) oldGigi.classList.remove('active-gigi');
        }

        // Set gigi baru sebagai aktif
        gigiAktifSemasa = noGigi;
        let newGigi = document.getElementById('gigi-' + noGigi);
        if (newGigi) newGigi.classList.add('active-gigi');

        // Update teks arahan
        document.getElementById('gigi-dipilih-teks').innerHTML = `Gigi Terpilih: <b style="color:black;">${noGigi}</b>. Sila pilih status di bawah.`;

        // Check radio button secara automatik jika gigi ini sudah ada status sebelum ini
        let formRadios = document.getElementsByName('status_tool');
        let statusSediaAda = dataGigiDipilih[noGigi];
        
        for (let i = 0; i < formRadios.length; i++) {
            if (statusSediaAda && formRadios[i].value === statusSediaAda) {
                formRadios[i].checked = true;
            } else {
                formRadios[i].checked = false;
            }
        }
    }

    // 2. Apabila doktor pilih status dari radio button (BUKA STATUS GIGI)
    function pilihStatusGigi(status) {
        if (gigiAktifSemasa === null) {
            alert("Sila klik pada nombor gigi di carta terlebih dahulu!");
            // Uncheck balik radio button tu
            let formRadios = document.getElementsByName('status_tool');
            for(let i=0; i<formRadios.length; i++) formRadios[i].checked = false;
            return;
        }

        // Jika klik status yang sama, kita anggap doktor nak batal (Undo)
        if (dataGigiDipilih[gigiAktifSemasa] === status) {
            delete dataGigiDipilih[gigiAktifSemasa];
            kemaskiniWarnaGigiGrafik(gigiAktifSemasa, null);
            
            // Uncheck radio button
            let formRadios = document.getElementsByName('status_tool');
            for(let i=0; i<formRadios.length; i++) formRadios[i].checked = false;
        } else {
            // Set status baru pada gigi yang aktif
            dataGigiDipilih[gigiAktifSemasa] = status;
            kemaskiniWarnaGigiGrafik(gigiAktifSemasa, status);
        }
        
        // Kemaskini Lencana & Input Array Tersembunyi
        renderSenaraiGigi();
    }

    // 3. Tukar warna spesifik untuk SATU gigi
    const warnaKod = <?= json_encode(array_map(function ($m) { return $m['warna']; }, kod_keadaan_gigi())) ?>;
    const namaKod = <?= json_encode(array_map(function ($m) { return $m['nama']; }, kod_keadaan_gigi())) ?>;

    function kemaskiniWarnaGigiGrafik(noGigi, status) {
        const gigiEl = document.getElementById('gigi-' + noGigi);
        if (!gigiEl) return;

        let isAktif = gigiEl.classList.contains('active-gigi');
        gigiEl.className = "gigi";
        if (isAktif) gigiEl.classList.add('active-gigi');

        if (status === null || status === undefined) {
            gigiEl.style.backgroundColor = '';
            gigiEl.style.color = '';
            return;
        }

        gigiEl.style.backgroundColor = warnaKod[status] || '#ced4da';
        gigiEl.style.color = '#ffffff';
    }

    // 4. Proses lencana/badge di ruang Catatan Rawatan
    function renderSenaraiGigi() {
        const listContainer = document.getElementById('list-gigi-rawatan');
        const hiddenContainer = document.getElementById('hidden-inputs-gigi');

        const senaraiKunci = Object.keys(dataGigiDipilih).sort((a, b) => a - b);

        if (senaraiKunci.length === 0) {
            listContainer.innerHTML = '';
            hiddenContainer.innerHTML = '';
            return;
        }

        let htmlSenarai = "";
        let htmlInputs = "";

        senaraiKunci.forEach((noGigi, index) => {
            const kod = dataGigiDipilih[noGigi];
            const warna = warnaKod[kod] || '#ced4da';
            const nama = namaKod[kod] || kod;

            htmlSenarai += `<span style="padding:6px 12px; border:1px solid ${warna}; color:${warna}; border-radius:20px; font-size:12px; font-weight:bold; cursor:pointer;" onclick="padamGigiDariSenarai(${noGigi})" title="Klik untuk padam">Gigi ${noGigi}: ${kod} ${nama}</span>`;

            htmlInputs += `<input type="hidden" name="no_gigi[${index}]" value="${noGigi}">`;
            htmlInputs += `<input type="hidden" name="status_gigi[${index}]" value="${kod}">`;
        });

        listContainer.innerHTML = htmlSenarai;
        hiddenContainer.innerHTML = htmlInputs;
    }

    // Fungsi memadam gigi jika doktor klik pada lencana di bahagian Catatan
    function padamGigiDariSenarai(noGigi) {
        delete dataGigiDipilih[noGigi];
        kemaskiniWarnaGigiGrafik(noGigi, null);
        
        // Jika gigi yang dipadam adalah gigi yang sedang aktif, uncheck radio
        if (noGigi === gigiAktifSemasa) {
            let formRadios = document.getElementsByName('status_tool');
            for(let i=0; i<formRadios.length; i++) formRadios[i].checked = false;
        }
        
        renderSenaraiGigi();
    }

    // Validasi sebelum tekan butang "Simpan Rekod"
    function sahkanForm() {
        if(Object.keys(dataGigiDipilih).length === 0) {
            alert("Peringatan: Sila pilih sekurang-kurangnya satu gigi pada carta sebelum menyimpan rekod.");
            return false;
        }
        return true;
    }

    // === TAMBAH MULTIPLE RAWATAN (BARU) ===
    let rawatanIndex = 0;
    function tambahRawatan() {
        let kodRawatan = document.getElementById("rawatan_kod").value.trim();
        let catatanRawatan = document.getElementById("rawatan_catatan").value.trim();

        if(kodRawatan === "") {
            alert("Sila pilih kod rawatan terlebih dahulu.");
            return;
        }

        rawatanIndex++;

        document.getElementById("list-rawatan").innerHTML += `
            <div style="background:#f8f9fa; padding:10px 15px; border-radius:8px; margin-bottom:8px; border:1px solid #dee2e6; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <b style="color:#1f2937;">${kodRawatan}</b><br>
                    <small style="color:#6c757d;">📝 Catatan: ${catatanRawatan !== "" ? catatanRawatan : 'Tiada catatan'}</small>
                </div>
                <input type="hidden" name="rawatan[${rawatanIndex}][kod]" value="${kodRawatan}">
                <input type="hidden" name="rawatan[${rawatanIndex}][catatan]" value="${catatanRawatan}">
                <button type="button" onclick="this.parentElement.remove()" style="background:#fa5252; color:white; border:none; border-radius:4px; padding:6px 12px; cursor:pointer; font-size:12px;">Padam</button>
            </div>
        `;

        // Kosongkan form rawatan selepas tambah
        document.getElementById("rawatan_kod").value = "";
        document.getElementById("rawatan_catatan").value = "";
    }

    // === TAMBAH MULTIPLE UBAT ===
    let ubatIndex = 0;
    function tambahUbat(){
        let ubat = document.getElementById("ubat");
        let kuantiti = document.getElementById("kuantiti").value.trim();
        let dos = document.getElementById("dos").value.trim();
        let arahan = document.getElementById("arahan").value.trim();

        if(ubat.value == "" || kuantiti == "" || kuantiti <= 0 || dos == "" || arahan == ""){
            alert("Sila lengkapkan maklumat preskripsi ubat.");
            return;
        }

        let namaUbat = ubat.options[ubat.selectedIndex].text;
        ubatIndex++;

        document.getElementById("list-ubat").innerHTML += `
            <div style="background:#f8f9fa; padding:10px 15px; border-radius:8px; margin-bottom:8px; border:1px solid #dee2e6; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <b style="color:#1f2937;">${namaUbat}</b> (Kuantiti: ${kuantiti})<br>
                    <small style="color:#6c757d;">💊 Dos: ${dos} | Arahan: ${arahan}</small>
                </div>
                <input type="hidden" name="ubat[${ubatIndex}][id_inventori]" value="${ubat.value}">
                <input type="hidden" name="ubat[${ubatIndex}][kuantiti]" value="${kuantiti}">
                <input type="hidden" name="ubat[${ubatIndex}][dos]" value="${dos}">
                <input type="hidden" name="ubat[${ubatIndex}][arahan]" value="${arahan}">
                <button type="button" onclick="this.parentElement.remove()" style="background:#fa5252; color:white; border:none; border-radius:4px; padding:6px 12px; cursor:pointer; font-size:12px;">Padam</button>
            </div>
        `;

        // Kosongkan form ubat selepas tambah
        document.getElementById("ubat").value = "";
        document.getElementById("kuantiti").value = "";
        document.getElementById("dos").value = "";
        document.getElementById("arahan").value = "";
    }
</script>

<script src="../assets/js/ui.js?v=7" defer></script>
</body>
</html>