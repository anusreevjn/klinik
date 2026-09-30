# SECURITY.md

Sistem Pengurusan Pesakit Klinik Pergigian Dr Arifin
PHP 8 + MySQL (MariaDB), XAMPP, localhost.
Dokumen ini menerangkan kawalan keselamatan yang memang ada dalam kod hari ini.

## Authentication

- Kata laluan disimpan dengan `password_hash` (bcrypt, cost lalai PHP) dan disemak dengan `password_verify`.
- Panjang kata laluan disemak di server: minimum 8, maksimum 128 aksara.
- Log masuk pesakit di `login.php`, log masuk staf di `staff_login.php`.
- Mesej ralat log masuk sama untuk email tidak wujud dan kata laluan salah: "Email atau kata laluan tidak betul".
- `session_regenerate_id(true)` dipanggil pada setiap log masuk berjaya dan pada tukar kata laluan.
- Cookie sesi: `HttpOnly`, `SameSite=Lax`, `Secure` apabila HTTPS dikesan.
- Timeout sesi: 30 minit tidak aktif untuk staf, doktor dan pentadbir; 60 minit untuk pesakit; had mutlak 12 jam.
- Akaun dengan `status_aktif` bukan 'Aktif' ditolak pada setiap permintaan, bukan hanya pada log masuk.
- Akaun yang kata laluannya ditetapkan semula oleh pentadbir ditanda `mesti_tukar_kata_laluan = 1` dan dipaksa ke `change_password.php` sebelum boleh guna sistem.
- Tetapan semula kata laluan melalui email: token 48 aksara rawak, disimpan sebagai SHA-256 (`token_reset.token_hash`), tamat dalam 30 minit, sekali guna sahaja.

## Authorization

- Satu fungsi guard, `guard($conn, $peranan, $laluan_login)` dalam `include/helpers.php`, dipanggil di setiap halaman dalam `admin/`, `doktor/`, `kakitangan/` dan `pesakit/`.
- Peranan disimpan dalam sesi server dan disemak pada setiap permintaan. Tiada peranan diambil daripada input pengguna.
- Pesakit hanya boleh melihat rekod sendiri. Resit (`kakitangan/resit.php`) dan bukti pembayaran (`bukti.php`) menyemak `id_pesakit` pesakit yang log masuk.
- `bukti.php` memulangkan 404 untuk rekod yang bukan milik pemanggil, supaya ID tidak disahkan kepada penyerang.
- Pesakit tidak boleh mengesahkan pembayaran sendiri. Hanya kakitangan boleh, melalui `kakitangan/pengesahan.php`.
- Pesakit hanya boleh membatalkan temu janji sendiri (`UPDATE ... WHERE id_temu_janji = ? AND id_pesakit = ?`).

## CSRF dan kaedah permintaan

- Token CSRF 32 bait dijana setiap sesi, dimasukkan ke semua borang POST melalui `csrf_field()`.
- `verify_csrf()` dijalankan automatik dalam `include/keselamatan.php` untuk setiap permintaan POST. Token tidak sah menyebabkan 403.
- Semua tindakan yang mengubah data menggunakan POST. Tiada lagi pautan GET seperti `padam.php?id=5`.

## SQL dan input

- Semua pertanyaan yang menyentuh input pengguna menggunakan prepared statement.
- Output pengguna ditapis dengan `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` melalui fungsi `e()` dan `selamat()`.
- Kod keadaan gigi disemak terhadap senarai sah (0 hingga 6) sebelum disimpan.
- Status temu janji disemak terhadap senarai sah sebelum dikemas kini.
- Tarikh temu janji disemak terhadap tarikh server, bukan jam peranti.

## Rate limiting

Dikira di server dalam jadual `rate_limits`. Kunci disimpan sebagai hash, termasuk hash IP.

| Tindakan | Had |
|---|---|
| Log masuk (email + IP) | 5 setiap 15 minit |
| Log masuk (IP) | 20 setiap minit |
| Log masuk (akaun) | 25 setiap 15 minit |
| Pendaftaran pesakit | 5 setiap jam per IP |
| Permintaan reset kata laluan | 3 setiap jam per akaun, 10 setiap jam per IP |
| Tempahan temu janji | 10 setiap jam per pesakit |

Counter log masuk hanya bertambah selepas semakan kata laluan gagal di server. Tiada lock kekal, hanya soft limit 15 minit, jadi orang lain tidak boleh lock akaun anda.

## Pembayaran manual

- Jumlah bayaran diambil daripada rekod rawatan di server, bukan daripada borang pesakit.
- Pesakit hanya boleh muat naik bukti untuk rawatan sendiri yang belum dibayar.
- Rawatan yang sudah 'Menunggu Pengesahan' atau 'Selesai' tidak menerima bukti baharu.
- Hanya kakitangan boleh menukar status kepada Selesai atau Ditolak.
- Pengesahan menggunakan `SELECT ... FOR UPDATE` dengan semakan status dalam transaksi, jadi satu invois tidak boleh disahkan dua kali.
- Nombor invois dan resit dijana di server dengan format INV-YYYY-XXXX dan RES-YYYY-XXXX, dan ada unique index.
- Setiap pengesahan dan penolakan ditulis ke `audit_log`.

## Muat naik fail

- Bukti pembayaran: maksimum 5MB, jenis disemak dengan magic bytes (`finfo`) dan `getimagesize`, bukan sambungan fail.
- Hanya jpg, png, webp dan pdf diterima. Fail PHP yang dinamakan `.png` ditolak.
- Fail dinamakan semula (`bukti_YYYYmmdd_His_<rawak>.<ext>`) dan disimpan dalam `storage/bukti/`, bukan dalam folder yang boleh dibuka terus.
- `storage/.htaccess` menolak semua akses web. Fail dihidangkan melalui `bukti.php` selepas semakan peranan dan pemilikan.
- Logo klinik: maksimum 2MB, imej sahaja, disemak cara sama.

## Rahsia dan konfigurasi

- Kelayakan database dibaca daripada `env.php` (tidak disertakan dalam repo). Contoh dalam `env.example.php`.
- App Password Gmail disimpan dalam `tetapan_sistem`. Medan itu write-only dalam borang Tetapan Sistem: nilai tidak pernah dipaparkan semula, hanya placeholder.
- `.htaccess` utama menolak `.sql`, `.bak`, `.log`, `.md`, `.git`, `.env`, `env.php`, dan folder `storage/`. Directory listing dimatikan.
- `include/.htaccess` menolak akses terus ke fail include.

## Header respons

Dihantar oleh `include/keselamatan.php` pada setiap halaman PHP:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`

## Audit log

Jadual `audit_log` merekod: log masuk berjaya dan gagal, pengesahan dan penolakan pembayaran, bukti pembayaran dibuka, kad rawatan dibuka, temu janji diluluskan, ditolak dan ditukar slot, check-in pesakit, pengguna dipadam, status pengguna ditukar, kata laluan ditetapkan semula, tetapan sistem dan logo dikemas kini. Setiap baris menyimpan pelaku, peranan, sasaran, butiran, hash IP dan masa. Tiada kata laluan atau token direkod.

## Privasi

- Rekod rawatan, preskripsi dan odontogram hanya boleh dilihat oleh pesakit berkenaan, doktor, kakitangan dan pentadbir.
- Papan giliran awam (`queue_display.php`) hanya memaparkan nombor giliran, tiada nama pesakit.
- Bukti pembayaran dan resit adalah fail peribadi, dihidangkan melalui endpoint berkebenaran.

## Akaun penjaga dan profil tanggungan

- Peranan dan pemilikan profil disemak di server pada setiap permintaan, bukan dari input pengguna
- Satu akaun hanya boleh buka profil sendiri dan profil tanggungan yang `akses_penjaga = 1`
- Penjaga boleh tarik balik aksesnya sendiri, dan akses terputus serta-merta termasuk untuk fail X-ray dan resit
- Pemilih profil hanya terima id profil yang sah untuk akaun tersebut. Id lain diabaikan dan sistem kembali ke profil sendiri
- Permohonan akaun sendiri perlu kelulusan penjaga. Kata laluan dalam permohonan disimpan sebagai hash
- Email yang sudah digunakan akaun lain ditolak semasa kelulusan
- Semua tindakan pautan dan tukar akses masuk dalam audit log

## Fail X-Ray

- Maksimum 8MB, jenis disemak dengan magic bytes, hanya jpg, png, webp dan pdf
- Disimpan dalam `storage/xray/` dengan nama rawak, di luar folder yang boleh dibuka melalui browser
- Dihidangkan melalui `xray_fail.php` selepas semakan peranan dan pemilikan profil. Rekod bukan milik pemanggil pulangkan 404
- Setiap fail dibuka direkod dalam audit log
- Had 40 muat naik sejam bagi setiap doktor

## Tidak dilaksanakan (gap yang disengajakan)

- Tiada 2FA untuk akaun pentadbir.
- Tiada HTTPS, HSTS atau Content-Security-Policy kerana sistem berjalan di localhost XAMPP. Perlu ditambah jika deploy ke hosting.
- Tiada backup automatik. Backup manual melalui phpMyAdmin Export.
- Tiada error tracking luaran seperti Sentry. Ralat ditulis ke log PHP.
- Database masih guna pengguna `root` XAMPP tanpa kata laluan. Untuk hosting, buat pengguna khas dengan kata laluan dan isi `env.php`.
- Akaun testing (admin.demo, doktor.afiq, staf.aina, pesakit.ahmad) masih ada. Perlu dipadam atau ditukar kata laluan sebelum sistem digunakan dengan data pesakit sebenar.
- Tiada muat naik X-Ray dan tiada halaman konsultasi berasingan, walaupun jadual `xray` dan `konsultasi` ada dalam database.
- Kod KKM untuk odontogram menggunakan 7 kod klinik (0 hingga 6). Tiada pemetaan ke kod KKM penuh kerana senarai itu belum disahkan.
