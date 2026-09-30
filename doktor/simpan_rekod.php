<?php
require '../config.php';
require_once '../include/helpers.php';
require_once '../include/kod_gigi.php';

$id_doktor_sesi = guard($conn, 'doktor', '../staff_login.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_doktor      = $_SESSION['user_id'] ?? NULL;
    $id_pesakit     = (int)($_POST['id_pesakit'] ?? 0);
    $id_temu_janji  = (int)($_POST['id_temu_janji'] ?? 0);
    $id_temu_janji  = $id_temu_janji > 0 ? $id_temu_janji : null;
    $kod_rawatan    = $_POST['kod_kkm'] ?? ''; 
    
    $nama_rawatan   = "Rawatan Pergigian Komprehensif"; 
    $harga_rawatan  = 0.00;

    $harga_minimum = null;
    $harga_maksimum = null;

    if (!empty($kod_rawatan)) {
        $stmt_cari = $conn->prepare("SELECT nama_rawatan, harga, harga_maksimum FROM kod_rawatan WHERE kod_rawatan = ?");
        $stmt_cari->bind_param("s", $kod_rawatan);
        $stmt_cari->execute();
        $res_cari = $stmt_cari->get_result();
        if ($row_cari = $res_cari->fetch_assoc()) {
            $nama_rawatan  = $row_cari['nama_rawatan'];
            $harga_rawatan = $row_cari['harga'];
            $harga_minimum = (float)$row_cari['harga'];
            $harga_maksimum = $row_cari['harga_maksimum'] !== null ? (float)$row_cari['harga_maksimum'] : null;
        }
        $stmt_cari->close();
    }

    $harga_dikenakan = isset($_POST['harga_dikenakan']) && $_POST['harga_dikenakan'] !== '' ? (float)$_POST['harga_dikenakan'] : (float)$harga_rawatan;
    $sebab_harga = isset($_POST['sebab_harga']) ? trim($_POST['sebab_harga']) : '';
    $luar_julat = 0;

    if ($harga_minimum !== null) {
        $had_atas = $harga_maksimum !== null ? $harga_maksimum : $harga_minimum;

        if ($harga_dikenakan < $harga_minimum || $harga_dikenakan > $had_atas) {
            $luar_julat = 1;

            if ($sebab_harga === '') {
                header("Location: rekod_rawatan.php?id_p=" . (int)$id_pesakit . "&ralat=harga");
                exit();
            }
        }
    }
    
    $tarikh_rawatan = date('Y-m-d');
    $diagnosis      = $_POST['diag_catatan'] ?? "";
    $prosedur       = $_POST['pros_catatan'] ?? "";
    $nota_rawatan   = "Prosedur: " . $prosedur;
    
    $jenis_gigi     = $_POST['jenis_gigi'] ?? '';
    $catatan_gigi   = "Diagnosis Sesi: " . $diagnosis;

    // AMBIL DATA BERASINGAN (Gigi 43 -> Kaviti, Gigi 45 -> Tiada)
    $no_gigi_array     = $_POST['no_gigi'] ?? [];     
    $status_gigi_array = $_POST['status_gigi'] ?? []; 

    if (empty($no_gigi_array) || empty($status_gigi_array)) {
        echo "<script>alert('Sila tandakan sekurang-kurangnya satu status gigi sebelum menyimpan.'); window.history.back();</script>";
        exit();
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();

    try {
        // 1. Masukkan satu baris data rawatan induk
        $sql_rawatan = "INSERT INTO rekod_rawatan 
            (id_pesakit, id_doktor, id_temu_janji, nama_rawatan, kod_rawatan, harga_rawatan, harga_dikenakan, sebab_harga, luar_julat, tarikh_rawatan, diagnosis, nota_rawatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_rawatan = $conn->prepare($sql_rawatan);
        $stmt_rawatan->bind_param("iiissddsisss", $id_pesakit, $id_doktor, $id_temu_janji, $nama_rawatan, $kod_rawatan, $harga_rawatan, $harga_dikenakan, $sebab_harga, $luar_julat, $tarikh_rawatan, $diagnosis, $nota_rawatan);
        $stmt_rawatan->execute();
        $id_rawatan = $conn->insert_id; 
        $stmt_rawatan->close();

        // 2. Lakukan gelung (loop) untuk simpan status unik bagi setiap gigi
        $sql_carta = "INSERT INTO rekod_carta_pergigian 
            (id_pesakit, id_rawatan, jenis_gigi, no_gigi, kod_kkm, status_gigi, catatan_gigi) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
        $stmt_carta = $conn->prepare($sql_carta);

        foreach ($no_gigi_array as $index => $no_gigi) {
            $kod_gigi = (string)($status_gigi_array[$index] ?? '');

            if (!kod_gigi_sah($kod_gigi)) {
                continue;
            }

            $no_gigi_bersih = preg_replace('/[^0-9]/', '', (string)$no_gigi);
            $keadaan = nama_keadaan_gigi($kod_gigi);

            $stmt_carta->bind_param("iisssss", $id_pesakit, $id_rawatan, $jenis_gigi, $no_gigi_bersih, $kod_gigi, $keadaan, $catatan_gigi);
            $stmt_carta->execute();
        }
        $stmt_carta->close();

        // 3. Masukkan preskripsi ubat jika ada
        if (!empty($_POST['ubat'])) {
            $sql_ubat = "INSERT INTO butiran_preskripsi (id_rawatan, id_inventori, kuantiti, dos, arahan) VALUES (?, ?, ?, ?, ?)";
            $stmt_ubat = $conn->prepare($sql_ubat);
            foreach ($_POST['ubat'] as $u) {
                $id_inventori = $u['id_inventori'] == 0 ? NULL : $u['id_inventori']; 
                $kuantiti     = $u['kuantiti'];
                $dos          = $u['dos'];
                $arahan       = $u['arahan'];

                if(!empty($kuantiti)) {
                    $stmt_ubat->bind_param("iiiss", $id_rawatan, $id_inventori, $kuantiti, $dos, $arahan);
                    $stmt_ubat->execute();
                }
            }
            $stmt_ubat->close();
        }

        // 4. Proses fail X-Ray jika ada
        if (isset($_FILES['xray']) && $_FILES['xray']['error'] == 0) {
            $nama_fail_asal = $_FILES['xray']['name'];
            $tmp_name = $_FILES['xray']['tmp_name'];
            $nama_baru = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $nama_fail_asal);
            $folder_simpan = "../assets/uploads/" . $nama_baru;

            if (move_uploaded_file($tmp_name, $folder_simpan)) {
                $tarikh_upload = date('Y-m-d H:i:s');
                $sql_xray = "INSERT INTO xray (id_pesakit, nama_fail, tarikh_upload) VALUES (?, ?, ?)";
                $stmt_xray = $conn->prepare($sql_xray);
                $stmt_xray->bind_param("iss", $id_pesakit, $nama_baru, $tarikh_upload);
                $stmt_xray->execute();
                $stmt_xray->close();
            }
        }

        audit($conn, 'rekod_rawatan_disimpan', $id_rawatan, 'pesakit=' . $id_pesakit . ';harga=' . $harga_dikenakan . ';luar_julat=' . $luar_julat);

        $conn->commit();
        mysqli_report(MYSQLI_REPORT_OFF);
        echo "<script>alert('Rekod rawatan dan status gigi berjaya disimpan.'); window.location.href = 'dashboard.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        mysqli_report(MYSQLI_REPORT_OFF);
        error_log('Simpan rekod rawatan gagal: ' . $e->getMessage());
        echo "<script>alert('Rekod tidak dapat disimpan. Sila cuba lagi atau hubungi pentadbir.'); window.history.back();</script>";
    }
    $conn->close();
} else {
    header("Location: rekod_rawatan.php");
    exit();
}
?>