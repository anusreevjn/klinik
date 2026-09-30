# Struktur Database Akhir

Sistem Pengurusan Pesakit Klinik Pergigian Dr Arifin
Database: klinik_pergigian_dr_arifin

Jumlah jadual: 28

## Senarai Jadual

| Jadual | Keterangan |
|---|---|
| `audit_log` | Jejak audit tindakan penting dalam sistem |
| `butiran_preskripsi` | Preskripsi ubat bagi setiap rawatan |
| `doktor` | Maklumat doktor dan akaun log masuk doktor |
| `giliran` | Rekod check-in dan pergerakan giliran pesakit |
| `harga_rawatan_klinik` | Salinan senarai harga rasmi klinik sebagai rujukan |
| `inventori` | Stok ubat dan bahan pergigian |
| `invois` | Nombor invois dan resit bagi setiap pembayaran |
| `jenis_xray` | Senarai jenis X-ray yang boleh diurus oleh Pentadbir |
| `kakitangan` | Maklumat kakitangan klinik |
| `kategori_inventori` | Kategori bagi item inventori |
| `kod_keadaan_gigi` | Senarai 7 kod keadaan gigi klinik (0 Sound hingga 6 Unerupt) |
| `kod_rawatan` | Senarai rawatan dengan harga minimum, maksimum dan catatan |
| `konsultasi` | Rekod konsultasi: simptom, diagnosis, cadangan doktor |
| `laporan` | Log laporan yang dijana oleh Pentadbir |
| `notifikasi` | Notifikasi dalam sistem bagi semua peranan |
| `pelan_bayaran` | Pelan ansuran seperti braces dan gigi palsu |
| `pembayaran` | Transaksi pembayaran termasuk bukti dan pengesahan |
| `penggunaan_ubat` | Rekod pengurangan stok ubat bila preskripsi dibayar |
| `pentadbir` | Maklumat pentadbir sistem |
| `permohonan_pautan` | Permohonan profil tanggungan untuk mempunyai akaun sendiri |
| `pesakit` | Maklumat pesakit, akaun penjaga dan profil tanggungan |
| `rate_limits` | Kiraan had permintaan untuk log masuk dan muat naik |
| `rekod_carta_pergigian` | Odontogram, kod keadaan gigi bagi setiap gigi |
| `rekod_rawatan` | Rekod rawatan, diagnosis dan harga dikenakan |
| `temu_janji` | Tempahan temu janji, status dan nombor giliran |
| `tetapan_sistem` | Tetapan klinik, waktu operasi, slot, bank dan SMTP |
| `token_reset` | Token tetapan semula kata laluan (disimpan sebagai hash) |
| `xray` | Rekod X-ray termasuk fail, tarikh, jenis dan catatan doktor |

## Butiran Setiap Jadual

### audit_log

Jejak audit tindakan penting dalam sistem

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_audit` | bigint(20) | Tidak | Utama | - |
| `id_pelaku` | int(11) | Ya | Tiada | - |
| `peranan_pelaku` | varchar(20) | Ya | Asing | - |
| `tindakan` | varchar(60) | Tidak | Asing | - |
| `id_sasaran` | int(11) | Ya | Tiada | - |
| `butiran` | varchar(255) | Ya | Tiada | - |
| `ip_hash` | varchar(64) | Ya | Tiada | - |
| `dicipta_pada` | datetime | Tidak | Tiada | - |

### butiran_preskripsi

Preskripsi ubat bagi setiap rawatan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_preskripsi` | int(11) | Tidak | Utama | - |
| `id_rawatan` | int(11) | Ya | Asing | - |
| `id_inventori` | int(11) | Ya | Asing | - |
| `kuantiti` | int(11) | Ya | Tiada | - |
| `dos` | varchar(50) | Ya | Tiada | - |
| `arahan` | varchar(255) | Ya | Tiada | - |

### doktor

Maklumat doktor dan akaun log masuk doktor

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_doktor` | int(11) | Tidak | Utama | - |
| `id_pentadbir` | int(11) | Ya | Tiada | - |
| `nama_doktor` | varchar(100) | Ya | Tiada | - |
| `no_ic` | varchar(14) | Ya | Tiada | - |
| `jantina` | varchar(10) | Ya | Tiada | - |
| `no_lesen` | varchar(100) | Ya | Tiada | - |
| `tarikh_tamat_lesen` | date | Ya | Tiada | - |
| `tarikh_mula_kerja` | date | Ya | Tiada | - |
| `tarikh_tamat_kerja` | date | Ya | Tiada | - |
| `kepakaran` | varchar(100) | Ya | Tiada | - |
| `no_telefon` | varchar(15) | Ya | Tiada | - |
| `alamat` | text | Ya | Tiada | - |
| `email` | varchar(100) | Ya | Tiada | - |
| `kata_laluan` | varchar(255) | Ya | Tiada | - |
| `gambar_profil` | varchar(255) | Ya | Tiada | - |
| `status_aktif` | enum('Aktif','Tidak Aktif') | Ya | Tiada | Aktif |
| `mesti_tukar_kata_laluan` | tinyint(1) | Tidak | Tiada | 0 |

### giliran

Rekod check-in dan pergerakan giliran pesakit

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_giliran` | int(11) | Tidak | Utama | - |
| `id_temu_janji` | int(11) | Tidak | Asing | - |
| `id_kakitangan` | int(11) | Ya | Asing | - |
| `no_giliran` | varchar(10) | Tidak | Tiada | - |
| `status_giliran` | enum('Menunggu','Dipanggil','Selesai','Tidak Hadir') | Tidak | Tiada | Menunggu |
| `masa_daftar_masuk` | datetime | Ya | Tiada | - |
| `masa_panggil` | datetime | Ya | Tiada | - |
| `masa_selesai` | datetime | Ya | Tiada | - |

### harga_rawatan_klinik

Salinan senarai harga rasmi klinik sebagai rujukan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_harga` | int(11) | Tidak | Utama | - |
| `perkara` | varchar(120) | Tidak | Unik | - |
| `harga_minimum` | decimal(10,2) | Ya | Tiada | - |
| `harga_maksimum` | decimal(10,2) | Ya | Tiada | - |
| `catatan` | varchar(120) | Ya | Tiada | - |

### inventori

Stok ubat dan bahan pergigian

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_inventori` | int(11) | Tidak | Utama | - |
| `id_pentadbir` | int(11) | Tidak | Asing | - |
| `nama_barang` | varchar(100) | Tidak | Tiada | - |
| `kategori` | varchar(50) | Tidak | Tiada | - |
| `kuantiti_stok` | int(11) | Tidak | Tiada | - |
| `had_minimum_stok` | int(11) | Tidak | Tiada | - |
| `tarikh_luput` | date | Tidak | Tiada | - |

### invois

Nombor invois dan resit bagi setiap pembayaran

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_invois` | int(11) | Tidak | Utama | - |
| `id_pembayaran` | int(11) | Tidak | Asing | - |
| `no_invois` | varchar(25) | Ya | Unik | - |
| `no_resit` | varchar(25) | Ya | Unik | - |
| `jumlah_invois` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `tarikh_jana` | datetime | Ya | Tiada | - |
| `status_invois` | enum('Belum Jelas','Berjaya','Dibatalkan') | Tidak | Tiada | Belum Jelas |

### jenis_xray

Senarai jenis X-ray yang boleh diurus oleh Pentadbir

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_jenis` | int(11) | Tidak | Utama | - |
| `nama_jenis` | varchar(60) | Tidak | Unik | - |
| `singkatan` | varchar(15) | Ya | Tiada | - |
| `status_aktif` | enum('Aktif','Tidak Aktif') | Tidak | Tiada | Aktif |
| `susunan` | int(11) | Tidak | Tiada | 0 |

### kakitangan

Maklumat kakitangan klinik

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_kakitangan` | int(11) | Tidak | Utama | - |
| `id_pentadbir` | int(11) | Ya | Tiada | - |
| `nama_kakitangan` | varchar(100) | Ya | Tiada | - |
| `no_ic` | varchar(14) | Ya | Tiada | - |
| `jantina` | varchar(10) | Ya | Tiada | - |
| `jawatan` | varchar(50) | Ya | Tiada | - |
| `kelayakan_akademik` | varchar(100) | Ya | Tiada | - |
| `tarikh_mula_kerja` | date | Ya | Tiada | - |
| `tarikh_tamat_kerja` | date | Ya | Tiada | - |
| `no_telefon` | varchar(15) | Ya | Tiada | - |
| `alamat` | text | Ya | Tiada | - |
| `email` | varchar(100) | Ya | Tiada | - |
| `kata_laluan` | varchar(255) | Ya | Tiada | - |
| `gambar_profil` | varchar(255) | Ya | Tiada | - |
| `status_aktif` | enum('Aktif','Tidak Aktif') | Ya | Tiada | Aktif |
| `mesti_tukar_kata_laluan` | tinyint(1) | Tidak | Tiada | 0 |

### kategori_inventori

Kategori bagi item inventori

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_kategori` | int(11) | Tidak | Utama | - |
| `nama_kategori` | varchar(100) | Tidak | Tiada | - |

### kod_keadaan_gigi

Senarai 7 kod keadaan gigi klinik (0 Sound hingga 6 Unerupt)

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `kod` | varchar(5) | Tidak | Utama | - |
| `nama` | varchar(40) | Tidak | Tiada | - |
| `label_bm` | varchar(40) | Tidak | Tiada | - |

### kod_rawatan

Senarai rawatan dengan harga minimum, maksimum dan catatan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_kod` | int(11) | Tidak | Utama | - |
| `kod_rawatan` | varchar(20) | Tidak | Tiada | - |
| `nama_rawatan` | varchar(100) | Tidak | Tiada | - |
| `harga` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `harga_maksimum` | decimal(10,2) | Ya | Tiada | - |
| `catatan_harga` | varchar(120) | Ya | Tiada | - |

### konsultasi

Rekod konsultasi: simptom, diagnosis, cadangan doktor

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_konsultasi` | int(11) | Tidak | Utama | - |
| `id_temu_janji` | int(11) | Ya | Asing | - |
| `simptom` | text | Ya | Tiada | - |
| `diagnosis` | text | Ya | Tiada | - |
| `cadangan_doktor` | text | Ya | Tiada | - |

### laporan

Log laporan yang dijana oleh Pentadbir

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_laporan` | int(11) | Tidak | Utama | - |
| `id_pentadbir` | int(11) | Tidak | Tiada | - |
| `id_inventori` | int(11) | Ya | Tiada | - |
| `id_temu_janji` | int(11) | Ya | Tiada | - |
| `jenis_laporan` | varchar(100) | Tidak | Tiada | - |
| `bulan_rujukan` | varchar(10) | Ya | Tiada | - |
| `tarikh_dijana` | datetime | Tidak | Tiada | - |

### notifikasi

Notifikasi dalam sistem bagi semua peranan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_notifikasi` | int(11) | Tidak | Utama | - |
| `peranan` | enum('pesakit','doktor','kakitangan','pentadbir') | Tidak | Asing | - |
| `id_pengguna` | int(11) | Tidak | Tiada | - |
| `tajuk` | varchar(120) | Tidak | Tiada | - |
| `mesej` | text | Ya | Tiada | - |
| `jenis` | varchar(30) | Ya | Tiada | umum |
| `pautan` | varchar(255) | Ya | Tiada | - |
| `status_baca` | tinyint(1) | Tidak | Tiada | 0 |
| `tarikh_hantar` | datetime | Ya | Tiada | - |

### pelan_bayaran

Pelan ansuran seperti braces dan gigi palsu

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_pelan` | int(11) | Tidak | Utama | - |
| `id_pesakit` | int(11) | Tidak | Asing | - |
| `id_kod_rawatan` | int(11) | Ya | Tiada | - |
| `nama_pelan` | varchar(120) | Tidak | Tiada | - |
| `jumlah_keseluruhan` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `deposit` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `ansuran_dijangka` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `status_pelan` | enum('Aktif','Selesai','Dibatalkan') | Tidak | Tiada | Aktif |
| `catatan` | varchar(255) | Ya | Tiada | - |
| `tarikh_mula` | date | Ya | Tiada | - |
| `dicipta_oleh` | int(11) | Ya | Tiada | - |
| `dicipta_pada` | datetime | Tidak | Tiada | - |

### pembayaran

Transaksi pembayaran termasuk bukti dan pengesahan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_pembayaran` | int(11) | Tidak | Utama | - |
| `id_rawatan` | int(11) | Ya | Asing | - |
| `id_pelan` | int(11) | Ya | Asing | - |
| `jenis_bayaran` | enum('Rawatan','Deposit','Ansuran') | Tidak | Tiada | Rawatan |
| `id_kakitangan` | int(11) | Ya | Asing | - |
| `id_pesakit` | int(11) | Ya | Asing | - |
| `jumlah_bayaran` | decimal(10,2) | Tidak | Tiada | 0.00 |
| `kaedah_bayaran` | varchar(30) | Tidak | Tiada | Tunai |
| `tarikh_bayaran` | datetime | Ya | Tiada | - |
| `status_pembayaran` | enum('Belum Bayar','Menunggu Pengesahan','Selesai','Ditolak','Dibatalkan','Bayaran Balik') | Tidak | Tiada | Belum Bayar |
| `rujukan_bayaran` | varchar(60) | Ya | Tiada | - |
| `bukti_pembayaran` | varchar(255) | Ya | Tiada | - |
| `disahkan_oleh` | int(11) | Ya | Tiada | - |
| `tarikh_sah` | datetime | Ya | Tiada | - |
| `catatan_bayaran` | varchar(255) | Ya | Tiada | - |

### penggunaan_ubat

Rekod pengurangan stok ubat bila preskripsi dibayar

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_penggunaan` | int(11) | Tidak | Utama | - |
| `id_preskripsi` | int(11) | Ya | Asing | - |
| `id_inventori` | int(11) | Ya | Asing | - |
| `kuantiti_guna` | int(11) | Tidak | Tiada | 0 |
| `tarikh_guna` | datetime | Ya | Tiada | - |

### pentadbir

Maklumat pentadbir sistem

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_pentadbir` | int(11) | Tidak | Utama | - |
| `nama_pentadbir` | varchar(100) | Ya | Tiada | - |
| `jantina` | varchar(10) | Ya | Tiada | - |
| `no_telefon` | varchar(15) | Ya | Tiada | - |
| `email` | varchar(100) | Ya | Tiada | - |
| `jawatan` | varchar(50) | Ya | Tiada | - |
| `alamat` | text | Ya | Tiada | - |
| `gambar_profil` | varchar(255) | Ya | Tiada | default.png |
| `kata_laluan` | varchar(255) | Ya | Tiada | - |
| `tarikh_daftar` | date | Ya | Tiada | - |
| `mesti_tukar_kata_laluan` | tinyint(1) | Tidak | Tiada | 0 |

### permohonan_pautan

Permohonan profil tanggungan untuk mempunyai akaun sendiri

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_permohonan` | int(11) | Tidak | Utama | - |
| `id_pesakit_profil` | int(11) | Tidak | Asing | - |
| `id_penjaga_akaun` | int(11) | Ya | Asing | - |
| `email_dipohon` | varchar(100) | Tidak | Tiada | - |
| `kata_laluan_hash` | varchar(255) | Tidak | Tiada | - |
| `no_ic_dipohon` | varchar(14) | Tidak | Tiada | - |
| `status_permohonan` | enum('Menunggu','Diluluskan','Ditolak','Dibatalkan') | Tidak | Tiada | Menunggu |
| `kekalkan_akses_penjaga` | tinyint(1) | Tidak | Tiada | 1 |
| `catatan` | varchar(255) | Ya | Tiada | - |
| `tarikh_mohon` | datetime | Tidak | Tiada | - |
| `tarikh_tindakan` | datetime | Ya | Tiada | - |

### pesakit

Maklumat pesakit, akaun penjaga dan profil tanggungan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_pesakit` | int(11) | Tidak | Utama | - |
| `id_penjaga_akaun` | int(11) | Ya | Asing | - |
| `jenis_profil` | enum('Akaun','Tanggungan') | Tidak | Tiada | Akaun |
| `akses_penjaga` | tinyint(1) | Tidak | Tiada | 1 |
| `nama_pesakit` | varchar(100) | Tidak | Tiada | - |
| `no_ic` | varchar(20) | Ya | Unik | - |
| `no_pendaftaran_klinik` | varchar(10) | Ya | Asing | - |
| `id_cariana_ic` | varchar(4) | Ya | Tiada | - |
| `jantina` | enum('Lelaki','Perempuan') | Ya | Tiada | - |
| `golongan` | varchar(20) | Ya | Tiada | - |
| `tarikh_lahir` | date | Ya | Tiada | - |
| `alamat` | text | Ya | Tiada | - |
| `no_telefon` | varchar(15) | Ya | Tiada | - |
| `email` | varchar(100) | Ya | Unik | - |
| `nama_penjaga` | varchar(100) | Ya | Tiada | - |
| `no_ic_penjaga` | varchar(14) | Ya | Tiada | - |
| `hubungan_penjaga` | varchar(40) | Ya | Tiada | - |
| `telefon_penjaga` | varchar(15) | Ya | Tiada | - |
| `email_penjaga` | varchar(100) | Ya | Tiada | - |
| `gambar_profil` | varchar(255) | Ya | Tiada | - |
| `kata_laluan` | varchar(255) | Ya | Tiada | - |
| `tarikh_daftar` | timestamp | Tidak | Tiada | current_timestamp() |
| `penyakit` | text | Ya | Tiada | - |
| `penyakit_kronik` | varchar(100) | Ya | Tiada | - |
| `cabut_gigi` | varchar(10) | Ya | Tiada | - |
| `mesti_tukar_kata_laluan` | tinyint(1) | Tidak | Tiada | 0 |

### rate_limits

Kiraan had permintaan untuk log masuk dan muat naik

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_had` | bigint(20) | Tidak | Utama | - |
| `rl_kunci` | varchar(255) | Tidak | Asing | - |
| `dicipta_pada` | datetime | Tidak | Tiada | - |

### rekod_carta_pergigian

Odontogram, kod keadaan gigi bagi setiap gigi

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_carta` | int(11) | Tidak | Utama | - |
| `id_pesakit` | int(11) | Tidak | Tiada | - |
| `id_rawatan` | int(11) | Tidak | Tiada | - |
| `jenis_gigi` | varchar(20) | Tidak | Tiada | - |
| `no_gigi` | varchar(10) | Tidak | Tiada | - |
| `kod_kkm` | varchar(10) | Ya | Tiada | - |
| `status_gigi` | varchar(50) | Tidak | Tiada | - |
| `catatan_gigi` | text | Ya | Tiada | - |

### rekod_rawatan

Rekod rawatan, diagnosis dan harga dikenakan

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_rawatan` | int(11) | Tidak | Utama | - |
| `id_pesakit` | int(11) | Ya | Asing | - |
| `id_doktor` | int(11) | Ya | Asing | - |
| `id_temu_janji` | int(11) | Ya | Asing | - |
| `nama_rawatan` | varchar(100) | Ya | Tiada | - |
| `kod_rawatan` | varchar(20) | Ya | Tiada | - |
| `harga_rawatan` | decimal(10,2) | Ya | Tiada | - |
| `harga_dikenakan` | decimal(10,2) | Ya | Tiada | - |
| `sebab_harga` | varchar(255) | Ya | Tiada | - |
| `luar_julat` | tinyint(1) | Tidak | Tiada | 0 |
| `tarikh_rawatan` | date | Ya | Tiada | - |
| `diagnosis` | text | Ya | Tiada | - |
| `nota_rawatan` | text | Ya | Tiada | - |
| `status_gigi` | varchar(50) | Ya | Tiada | - |

### temu_janji

Tempahan temu janji, status dan nombor giliran

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_temu_janji` | int(11) | Tidak | Utama | - |
| `id_pesakit` | int(11) | Ya | Asing | - |
| `id_doktor` | int(11) | Ya | Asing | - |
| `id_kakitangan` | int(11) | Ya | Tiada | - |
| `tarikh_temu_janji` | date | Ya | Tiada | - |
| `masa_temu_janji` | time | Ya | Tiada | - |
| `jenis_rawatan` | varchar(100) | Tidak | Tiada | - |
| `no_giliran` | varchar(10) | Ya | Tiada | - |
| `status` | enum('Menunggu','Disahkan','Ditolak','Dipanggil','Selesai','Tidak Hadir','Dibatalkan') | Tidak | Tiada | Menunggu |
| `catatan` | text | Ya | Tiada | - |
| `notifikasi_staf` | tinyint(1) | Tidak | Tiada | 1 |

### tetapan_sistem

Tetapan klinik, waktu operasi, slot, bank dan SMTP

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_tetapan` | int(11) | Tidak | Utama | - |
| `kunci` | varchar(60) | Tidak | Unik | - |
| `nilai` | text | Ya | Tiada | - |
| `kategori` | varchar(30) | Ya | Tiada | umum |
| `tarikh_kemaskini` | datetime | Ya | Tiada | - |

### token_reset

Token tetapan semula kata laluan (disimpan sebagai hash)

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_token` | int(11) | Tidak | Utama | - |
| `peranan` | enum('pesakit','doktor','kakitangan','pentadbir') | Tidak | Tiada | - |
| `email` | varchar(100) | Tidak | Asing | - |
| `token_hash` | varchar(64) | Ya | Tiada | - |
| `token` | varchar(64) | Tidak | Unik | - |
| `tarikh_tamat` | datetime | Tidak | Tiada | - |
| `status_guna` | tinyint(1) | Tidak | Tiada | 0 |

### xray

Rekod X-ray termasuk fail, tarikh, jenis dan catatan doktor

| Atribut | Jenis Data | Null | Kunci | Nilai Lalai |
|---|---|---|---|---|
| `id_xray` | int(11) | Tidak | Utama | - |
| `id_pesakit` | int(11) | Ya | Asing | - |
| `nama_fail` | varchar(255) | Ya | Tiada | - |
| `tarikh_upload` | timestamp | Tidak | Tiada | current_timestamp() |
| `id_doktor` | int(11) | Ya | Tiada | - |
| `id_jenis_xray` | int(11) | Ya | Tiada | - |
| `fail_xray` | varchar(255) | Ya | Tiada | - |
| `tarikh_pemeriksaan` | date | Ya | Tiada | - |
| `catatan_doktor` | text | Ya | Tiada | - |
| `no_gigi` | varchar(20) | Ya | Tiada | - |
| `dicipta_pada` | datetime | Ya | Tiada | - |
