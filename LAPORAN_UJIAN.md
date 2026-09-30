# Laporan Ujian Sistem

Sistem Pengurusan Pesakit Klinik Pergigian Dr Arifin
Tarikh ujian: 30 September 2026
Persekitaran: PHP 8.3.6, MariaDB 10.11, database dibina semula dari kosong menggunakan backup asal klien + 2 fail migrasi

## Ringkasan

| Modul | Lulus | Gagal sebenar |
|---|---|---|
| A. Autentikasi dan kawalan akses | 14 | 0 |
| B. Pesakit | 14 | 0 |
| C. Kakitangan | 13 | 0 |
| D. Doktor dan X-Ray | 18 | 0 |
| E. Pembayaran | 18 | 0 |
| F. Pelan ansuran | 9 | 0 |
| G. Pentadbir | 21 | 0 |
| H. XSS | 9 | 0 |
| I. SQL Injection | 6 | 0 |
| J. Integriti pembayaran | 4 | 0 |
| K. Kebolehgunaan | 6 | 0 |
| **Jumlah** | **132** | **0** |

Semua 45 halaman pulangkan HTTP 200 tanpa ralat PHP. Semua 98 fail PHP lulus `php -l`. Fail JavaScript lulus semakan sintaks.

## A. Autentikasi dan kawalan akses

| Ujian | Keputusan |
|---|---|
| Log masuk 4 peranan (pesakit, kakitangan, doktor, pentadbir) | Lulus |
| Kata laluan salah ditolak | Lulus |
| Email tidak wujud beri mesej yang sama dengan kata laluan salah | Lulus |
| POST tanpa token CSRF | Ditolak 403 |
| POST dengan token CSRF palsu | Ditolak 403 |
| Akses halaman dalaman tanpa log masuk | Redirect ke log masuk |
| Pesakit cuba buka halaman pentadbir | Redirect |
| Kakitangan cuba buka halaman doktor | Redirect |
| Doktor cuba buka Tetapan Sistem | Redirect |
| Akaun dinyahaktifkan semasa sesi masih hidup | Terus dihalang, redirect dengan nota nyahaktif |

## B. Pesakit

| Ujian | Keputusan |
|---|---|
| Daftar pesakit baharu | Lulus |
| No Pendaftaran Klinik dijana automatik dari 4 digit akhir IC | Lulus |
| No IC berulang ditolak | Lulus |
| Kata laluan kurang 8 aksara ditolak | Lulus |
| Email tidak sah ditolak | Lulus |
| Had pendaftaran 5 sejam per IP | Berkuat kuasa |
| Tempah temu janji | Lulus |
| Tarikh lepas ditolak | Lulus |
| Dua temu janji aktif pada hari sama ditolak | Lulus |
| Slot yang sudah penuh ditolak | Lulus |
| Pesakit lain cuba batal temu janji orang | Gagal, status kekal |
| Pemilik batal temu janji sendiri | Lulus |
| Halaman notifikasi, invois dan pelan | Buka tanpa ralat |

## C. Kakitangan

| Ujian | Keputusan |
|---|---|
| Daftar pesakit di kaunter | Lulus |
| Cari pesakit ikut nama | Lulus |
| Kemaskini maklumat pesakit | Lulus |
| Luluskan temu janji dan tetapkan doktor | Lulus, pesakit dapat notifikasi |
| Doktor sama pada slot masa sama | Ditolak dengan amaran |
| Tolak temu janji dengan sebab | Lulus, pesakit dimaklumkan |
| Tukar slot temu janji | Lulus |
| Tukar ke tarikh lepas | Ditolak |
| Check-in dan jana nombor giliran | Lulus, format A001 |
| Baris giliran dicipta dalam jadual giliran | Lulus |
| Check-in dua kali | Dihalang |

## D. Doktor dan X-Ray

| Ujian | Keputusan |
|---|---|
| Cari pesakit | Lulus |
| Simpan rekod rawatan dengan odontogram | Lulus |
| Kod gigi disimpan dalam kod_kkm | Lulus |
| Harga dalam julat klinik | Diterima |
| Harga luar julat tanpa sebab | Dihalang, diminta sebab |
| Harga luar julat dengan sebab | Diterima dan ditanda luar_julat |
| Kad Rawatan papar dua nombor pendaftaran dan petunjuk kod | Lulus |
| Muat naik X-ray (png sah) | Lulus |
| Skrip PHP dinamakan .png | Ditolak oleh semakan magic bytes |
| Fail 9MB melebihi had server | Ditolak dengan mesej saiz yang jelas (413) |
| Fail rawak 1.5MB dinamakan .png | Ditolak, bukan imej sebenar |
| Doktor buka fail X-ray | 200 |
| Pesakit lain buka fail X-ray | 404 |
| Tanpa log masuk buka fail X-ray | Redirect |
| Fail disimpan dalam storage, bukan folder web | Lulus |

## E. Pembayaran

| Ujian | Keputusan |
|---|---|
| Bayaran kaunter dan jana resit | Lulus |
| Nombor invois format INV-YYYY-XXXX | Lulus |
| Bayaran berganda pada rawatan sama | Dihalang |
| Hanya satu invois wujud selepas cubaan berganda | Lulus |
| Resit dibuka oleh pesakit lain | Ditolak |
| Pesakit muat naik bukti bayaran | Lulus, status Menunggu Pengesahan |
| Hantar bukti dua kali | Dihalang |
| Pesakit cuba sahkan bayaran sendiri | Dihalang, status tidak berubah |
| Kakitangan sahkan bukti | Lulus |
| Sahkan dua kali | Dihalang |
| Bukti dibuka pesakit lain | 404 |
| Jumlah berbeza dengan harga rawatan tanpa sebab | Dihalang (ciri baharu) |
| Jumlah berbeza dengan sebab | Diterima dan sebab direkod |

## F. Pelan ansuran

| Ujian | Keputusan |
|---|---|
| Buka pelan braces RM3500, deposit RM500, ansuran RM150 | Lulus |
| Rekod deposit, baki jadi RM3000 | Lulus |
| Rekod ansuran, baki jadi RM2850 | Lulus |
| Bayaran melebihi baki | Ditolak |
| Jumlah negatif | Ditolak |
| Bayaran akhir menutup pelan | Status jadi Selesai, pesakit dapat notifikasi |
| Setiap bayaran ada invois dan resit sendiri | Lulus, 3 resit untuk 3 bayaran |
| Bayaran pada pelan yang sudah selesai | Ditolak |

## G. Pentadbir

| Ujian | Keputusan |
|---|---|
| Tambah rawatan dengan julat harga | Lulus |
| Padam rawatan | Lulus |
| Tambah jenis X-ray | Lulus |
| Nama jenis berulang | Ditolak |
| Nonaktifkan jenis X-ray | Lulus, hilang dari borang doktor |
| Padam jenis X-ray yang sudah digunakan | Dihalang, disuruh guna Tidak Aktif |
| Reset kata laluan pengguna | Lulus, flag mesti tukar kata laluan dipasang |
| Nyahaktif dan aktifkan semula pengguna | Lulus |
| Padam pengguna yang ada rekod | Dihalang |
| 6 laporan dijana | Semua tanpa ralat |
| Simpan App Password | Tersimpan tetapi tidak dipapar semula |
| Simpan borang dengan medan rahsia kosong | Nilai lama kekal, tidak terpadam |

## H. XSS

Input ujian: `<script>alert(1)</script>` dan `<img src=x onerror=alert(3)>`

| Ujian | Keputusan |
|---|---|
| Nama pesakit mengandungi script | Disimpan mentah dalam database |
| Paparan senarai kakitangan | Di-escape jadi `&lt;script&gt;`, tidak dijalankan |
| Paparan senarai doktor | Selamat |
| Paparan senarai pentadbir | Selamat |
| Kad Rawatan | Selamat |
| Carian reflected dengan payload dalam URL | Selamat |
| Nama pelan ansuran dengan payload img onerror | Selamat |
| Halaman pembayaran pentadbir | Selamat |

## I. SQL Injection

| Ujian | Keputusan |
|---|---|
| Log masuk guna `' OR '1'='1` | Gagal, mesej biasa |
| Carian pesakit guna `' OR 1=1 --` | Tiada kebocoran rekod |
| Parameter id guna `5 OR 1=1` | Ditapis jadi integer |
| Parameter tab dengan petik tunggal | Tidak ranap |
| Bilangan jadual selepas semua cubaan | Kekal 28 |

## J. Integriti pembayaran

Sistem ni tidak guna payment gateway atau webhook, jadi ujian tertumpu pada integriti rekod.

| Ujian | Keputusan |
|---|---|
| Pesakit cuba bayar rawatan milik orang lain | Tiada rekod tercipta |
| Pengesahan berganda pada invois sama | Dihalang oleh SELECT FOR UPDATE |
| Nombor resit unik | Tiada pendua |
| Audit log merekod pengesahan bayaran | Lulus |
| Audit log merekod log masuk gagal | Lulus |
| Jumlah berbeza dari harga rawatan | Perlu sebab, direkod dalam audit |

## K. Kebolehgunaan

| Ujian | Keputusan |
|---|---|
| Meta viewport pada semua halaman | 18/18 selepas pembetulan |
| Sidebar bertukar off-canvas bawah 991px | Lulus |
| CSS cetak (A4, sembunyi menu, kad tidak putus) | Ada |
| prefers-reduced-motion dihormati | Ada |
| Focus ring untuk navigasi papan kekunci | 10 peraturan |
| Label pada borang utama | Setiap input ada label |
| Empty state bila tiada rekod | Papar ikon dan mesej, bukan baris kosong |
| Saiz halaman | 5 hingga 39 KB |
| Masa respons | Bawah 50 ms untuk semua halaman diuji |
| Kontras warna badge status | Semua pasangan lulus WCAG AA |

## Pepijat yang dijumpai dan dibaiki semasa ujian

1. **Muat naik fail besar beri mesej mengelirukan.** Fail melebihi had `post_max_size` PHP menyebabkan seluruh POST dibuang, jadi pengguna nampak "Permintaan tidak sah" (403 CSRF). Sekarang sistem kesan keadaan ni dan beri mesej saiz yang jelas dengan kod 413.
2. **Jumlah bayaran kaunter tiada semakan.** Kakitangan boleh masukkan sebarang jumlah tanpa amaran. Sekarang kalau jumlah berbeza dari harga rawatan, sistem minta sebab dahulu, dan sebab tu disimpan dalam rekod bayaran serta audit log.
3. **5 halaman tiada meta viewport**, jadi paparan telefon tidak skala betul. Sudah ditambah pada semua halaman.
4. **Halaman Rekod Rawatan tanpa pesakit** papar teks kosong tanpa layout. Sekarang jadi halaman penuh dengan mesej dan butang ke Senarai Pesakit.
5. **Query pesakit dalam Rekod Rawatan** masih guna gabungan string. Sudah tukar ke prepared statement.

## Nota

- Ujian dijalankan pada PHP dev server. Peraturan `.htaccess` (blok folder storage, header keselamatan tambahan) hanya berkuat kuasa pada Apache XAMPP, jadi perlu disahkan semula selepas dipasang.
- Had muat naik bergantung pada tetapan PHP. Pada persekitaran ujian `upload_max_filesize` ialah 2M; XAMPP lalai biasanya 40M. Sistem akan beritahu had sebenar server dalam mesej ralat.
- Akaun testing dan data sample masih dalam database. Perlu dipadam atau ditukar sebelum guna dengan data pesakit sebenar.
