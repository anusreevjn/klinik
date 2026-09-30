<?php
require_once '../config.php';
require_once '../include/helpers.php';
require_once '../include/penjaga_lib.php';

guard($conn, 'pesakit', '../login.php');

// Jika user bukan pesakit, paksa log keluar


$id_akaun = (int)$_SESSION['user_id'];
$id_pesakit = id_profil_aktif($conn, $id_akaun);
$profil_semasa = profil_pesakit($conn, $id_pesakit);
$message = "";


// LOGIK TEMPAHAN

if (isset($_POST['tempah_temujanji'])) {
    $tarikh = trim($_POST['tarikh']);
    $masa = trim($_POST['masa']);
    $jenis_rawatan = trim($_POST['jenis_rawatan']);

    $kunci_tempah = 'tempah:' . hash_kunci((string)$id_pesakit);

    if (terlalu_banyak_cubaan($conn, $kunci_tempah, 10, 3600)) {
        $message = "Terlalu banyak tempahan dalam masa singkat. Sila cuba semula kemudian.";
    } elseif ($masa === '' || $jenis_rawatan === '' || $tarikh === '' || strtotime($tarikh) === false) {
        $message = "Sila lengkapkan semua maklumat.";
    } elseif ($tarikh < date('Y-m-d')) {
        $message = "Tarikh temu janji tidak boleh sebelum hari ini.";
    } else {

        $had_slot = (int)tetapan($conn, 'pesakit_per_slot', '1');
        $had_slot = $had_slot > 0 ? $had_slot : 1;

        $semak = mysqli_prepare($conn, "SELECT COUNT(*) FROM temu_janji WHERE tarikh_temu_janji = ? AND masa_temu_janji = ? AND status NOT IN ('Dibatalkan', 'Ditolak')");
        mysqli_stmt_bind_param($semak, "ss", $tarikh, $masa);
        mysqli_stmt_execute($semak);
        mysqli_stmt_bind_result($semak, $bil_slot);
        mysqli_stmt_fetch($semak);
        mysqli_stmt_close($semak);

        $semak_sendiri = mysqli_prepare($conn, "SELECT COUNT(*) FROM temu_janji WHERE id_pesakit = ? AND tarikh_temu_janji = ? AND status IN ('Menunggu', 'Disahkan')");
        mysqli_stmt_bind_param($semak_sendiri, "is", $id_pesakit, $tarikh);
        mysqli_stmt_execute($semak_sendiri);
        mysqli_stmt_bind_result($semak_sendiri, $bil_sendiri);
        mysqli_stmt_fetch($semak_sendiri);
        mysqli_stmt_close($semak_sendiri);

        if ((int)$bil_slot >= $had_slot) {
            $message = "Slot sudah penuh.";
        } elseif ((int)$bil_sendiri > 0) {
            $message = "Anda sudah ada temu janji aktif pada tarikh tersebut.";
        } else {

            rekod_cubaan($conn, $kunci_tempah);

            $stmt = mysqli_prepare($conn, "INSERT INTO temu_janji (id_pesakit, id_doktor, tarikh_temu_janji, masa_temu_janji, no_giliran, status, jenis_rawatan) VALUES (?, NULL, ?, ?, NULL, 'Menunggu', ?)");
            mysqli_stmt_bind_param($stmt, "isss", $id_pesakit, $tarikh, $masa, $jenis_rawatan);

            if (mysqli_stmt_execute($stmt)) {
                $id_baru = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                audit($conn, 'temu_janji_dimohon', $id_baru, $tarikh . ' ' . $masa);

                $staf = mysqli_query($conn, "SELECT id_kakitangan FROM kakitangan WHERE status_aktif = 'Aktif'");
                while ($s = mysqli_fetch_assoc($staf)) {
                    hantar_notifikasi($conn, 'kakitangan', (int)$s['id_kakitangan'], 'Permohonan temu janji baharu', 'Ada permohonan temu janji pada ' . date('d/m/Y', strtotime($tarikh)) . ' menunggu kelulusan.', 'temujanji', 'appointment_manage.php');
                }

                header("Location: appointment.php?tempah=ok");
                exit();
            }

            mysqli_stmt_close($stmt);
            $message = "Gagal membuat temu janji. Sila cuba lagi.";
        }
    }
}

if (isset($_GET['tempah'])) {
    $message = "Temu janji berjaya dimohon. Sila tunggu kelulusan kakitangan klinik.";
}

// LOGIK PEMBATALAN TEMU JANJI (BARU)
if (isset($_POST['batal_temujanji'])) {
    $id_temujanji = (int)$_POST['id_temujanji'];

    $stmt = mysqli_prepare($conn, "UPDATE temu_janji SET status = 'Dibatalkan' WHERE id_temu_janji = ? AND id_pesakit = ? AND status IN ('Menunggu', 'Disahkan')");
    mysqli_stmt_bind_param($stmt, "ii", $id_temujanji, $id_pesakit);
    mysqli_stmt_execute($stmt);
    $terjejas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($terjejas > 0) {
        audit($conn, 'temu_janji_dibatalkan_pesakit', $id_temujanji, '');
        header("Location: appointment.php?batal=ok");
        exit();
    }

    $message = "Temu janji tidak dapat dibatalkan.";
}

if (isset($_GET['batal'])) {
    $message = "Temu janji telah dibatalkan.";
}

$stmt = mysqli_prepare($conn, "SELECT * FROM temu_janji WHERE id_pesakit = ? AND status IN ('Menunggu', 'Disahkan', 'Dipanggil') AND tarikh_temu_janji >= CURDATE() ORDER BY tarikh_temu_janji ASC, masa_temu_janji ASC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$upcoming = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM temu_janji WHERE id_pesakit = ? ORDER BY tarikh_temu_janji DESC, masa_temu_janji DESC LIMIT 50");
mysqli_stmt_bind_param($stmt, "i", $id_pesakit);
mysqli_stmt_execute($stmt);
$result_all = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temu Janji</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.0/dist/tabler-icons.min.css">

    <style>
        .time-btn{
            padding:10px;
            border:1px solid #0f766e;
            border-radius:10px;
            cursor:pointer;
            text-align:center;
        }
        .time-btn:hover{
            background:#0f766e;
            color:white;
        }
        .card-custom{
            background:white;
            padding:20px;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            margin-bottom:20px;
        }
        .history-card{
            background:#e2f7f3;
            border-left:5px solid #0f766e;
            padding:15px;
            margin-bottom:10px;
            border-radius:5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-batal {
            background: #dc2626;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-batal:hover {
            background: #b91c1c;
        }
    </style>
</head>

<body>

<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/image/logo.jpg" alt="Logo">
            <h2>Klinik Dr Arifin</h2>
        </div>
        <a href="dashboard.php"> Dashboard</a>
        <a href="appointment.php"> Temu Janji</a>
        <a href="sejarah_rawatan.php"> Sejarah</a>
        <a href="pembayaran.php"> Pembayaran</a>
        <a href="profil.php"> Profil</a>
        <a href="../logout.php"> Log Keluar</a>
    </div>

    <div class="main">
<?= pemilih_profil($conn, $id_akaun, $id_pesakit, 'appointment.php') ?>

        <div class="topbar"><h3>Temu Janji</h3></div>

        <?php if(!empty($message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; gap:25px; flex-wrap:wrap;">

            <!-- FORM -->
            <div class="card card-custom" style="flex:2; min-width:350px;">
                <form method="POST"><?= csrf_field() ?>

                    <label>Tarikh</label>
                    <input type="date" name="tarikh" id="tarikh"
                        min="<?= date('Y-m-d') ?>"
                        required
                        style="margin-bottom:15px; width:100%;">

                    <label>Masa Slot</label>
                    <div id="time-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px;">
                        <p>Pilih tarikh dahulu</p>
                    </div>

                    <input type="hidden" name="masa" id="selected-time" required>

                    <label style="margin-top:15px; display:block;">Jenis Rawatan</label>
                    <select name="jenis_rawatan" required style="width:100%; padding:10px;">
                        <option value="">Pilih</option>
                        <option value="Pemeriksaan">Pemeriksaan</option>
                        <option value="Tampalan">Tampalan</option>
                        <option value="Cabutan">Cabutan</option>
                    </select>

                    <button type="submit" name="tempah_temujanji"
                        style="width:100%; padding:10px; margin-top:15px; background:#0f766e; color:white; border:none; cursor:pointer;">
                        Hantar Temu Janji
                    </button>
                </form>
            </div>

            <!-- UPCOMING -->
            <?php if($upcoming): ?>
            <div class="card card-custom" style="flex:1; min-width:300px; background:#e0f2f1; border-left:5px solid #0f766e; display:flex; flex-direction:column; justify-content:between;">
                <div>
                    <h4> Temu Janji Akan Datang</h4>
                    <p><b>Jenis:</b> <?= $upcoming['jenis_rawatan'] ?></p>
                    <p><b>Tarikh:</b> <?= $upcoming['tarikh_temu_janji'] ?></p>
                    <p><b>Masa:</b> <?= substr($upcoming['masa_temu_janji'],0,5) ?></p>
                    <p><b>Status:</b> <?= $upcoming['status'] ?></p>
                </div>
                
                <!-- Butang batal untuk temu janji akan datang -->
                <?php if(in_array($upcoming['status'], ['Menunggu', 'Disahkan'])): ?>
                <form method="POST" onsubmit="return confirm('Adakah anda pasti mahu membatalkan temu janji ini?');" style="margin-top:15px;"><?= csrf_field() ?>
                    <input type="hidden" name="id_temujanji" value="<?= $upcoming['id_temu_janji'] ?>">
                    <button type="submit" name="batal_temujanji" class="btn-batal" style="width:100%;">Batal Temu Janji</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- ALL -->
        <div class="card card-custom">
            <h3>Semua Temu Janji</h3>

            <?php while($row = mysqli_fetch_assoc($result_all)) { ?>
                <div class="history-card" style="<?= $row['status'] == 'Dibatalkan' ? 'background:#fee2e2; border-left:5px solid #ef4444;' : '' ?>">
                    <div>
                        <b><?= $row['jenis_rawatan'] ?></b><br>
                        Tarikh: <?= $row['tarikh_temu_janji'] ?> |
                        Masa: <?= substr($row['masa_temu_janji'],0,5) ?><br>
                        Status: <span style="font-weight:bold; color: <?= $row['status'] == 'Dibatalkan' ? '#dc2626' : '#0f766e' ?>"><?= $row['status'] ?></span>
                    </div>

                    <!-- Butang batal dalam senarai semua jika status masih aktif -->
                    <?php if(in_array($row['status'], ['Menunggu', 'Disahkan'])): ?>
                    <form method="POST" onsubmit="return confirm('Adakah anda pasti mahu membatalkan temu janji ini?');"><?= csrf_field() ?>
                        <input type="hidden" name="id_temujanji" value="<?= $row['id_temu_janji'] ?>">
                        <button type="submit" name="batal_temujanji" class="btn-batal">Batal</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php } ?>
        </div>

    </div>
</div>

<script>
document.getElementById('tarikh').addEventListener('change', async function(){
    const tarikh = this.value;
    const grid = document.getElementById('time-grid');
    const selected = document.getElementById('selected-time');

    grid.innerHTML = "Loading...";

    const res = await fetch('check_slot.php?tarikh=' + tarikh);
    const booked = await res.json();

    grid.innerHTML = "";

    const slots = [
        '09:00:00','09:30:00','10:00:00','10:30:00','11:00:00','11:30:00',
        '14:00:00','14:30:00','15:00:00','15:30:00','16:00:00','16:30:00'
    ];

    slots.forEach(s => {
        let btn = document.createElement('div');
        btn.className = 'time-btn';
        btn.innerText = s.substring(0,5);

        if(booked.includes(s)){
            btn.innerText += " (Penuh)";
            btn.style.opacity = "0.4";
            btn.style.pointerEvents = "none";
        } else {
            btn.onclick = () => {
                selected.value = s;
                document.querySelectorAll('.time-btn').forEach(b=>{
                    b.style.background="#fff";
                    b.style.color="#000";
                });
                btn.style.background="#0f766e";
                btn.style.color="white";
            }
        }

        grid.appendChild(btn);
    });
});
</script>

<script src="../assets/js/ui.js" defer></script>
</body>
</html>