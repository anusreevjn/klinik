# CHANGELOG_HARDENING.md

Projek: Sistem Pengurusan Pesakit Klinik Pergigian Dr Arifin
Asas: klinik_dr_arifin.zip yang dihantar klien, database daripada backup klinik_pergigian_dr_arifin.sql

## Fail baharu

| Fail | Kenapa |
|---|---|
| `include/keselamatan.php` | Bootstrap keselamatan: session cookie flags, CSRF, rate limit, audit log, semakan muat naik, header respons |
| `include/helpers.php` | Guard peranan tunggal, tetapan sistem, notifikasi, nombor giliran, nombor invois dan resit |
| `include/layout.php` | Sidebar dan topbar berkongsi untuk semua peranan |
| `include/giliran_lib.php` | Panggil giliran, selesaikan giliran, tukar status temu janji |
| `include/pembayaran_lib.php` | Pengesahan pembayaran dengan lock, penolakan, simpan bukti |
| `include/laporan_data.php` | Data untuk 6 laporan |
| `include/kod_gigi.php` | 7 kod keadaan gigi klinik (0 Sound hingga 6 Unerupt) |
| `bukti.php` | Hidangkan bukti pembayaran selepas semakan peranan dan pemilikan |
| `lupa_password.php`, `reset_password.php` | Reset kata laluan melalui Gmail SMTP dengan token hashed |
| `include/mailer.php` | Klien SMTP kecil dengan STARTTLS, tanpa Composer |
| `admin/pengguna.php` | Pengurusan pengguna: edit, aktif, nyahaktif, padam, reset kata laluan |
| `admin/rawatan.php` | Pengurusan kod rawatan dan harga termasuk julat harga |
| `admin/tetapan.php` | Tetapan sistem termasuk maklumat klinik, waktu operasi, slot, bank dan SMTP |
| `admin/pembayaran.php` | Semakan transaksi dan invois |
| `kakitangan/pesakit.php` | Daftar, cari dan kemaskini pesakit |
| `kakitangan/kehadiran.php` | Check-in, tidak hadir, tanda selesai, jana nombor giliran |
| `kakitangan/appointment_manage.php` | Ganti stub 26 baris. Luluskan, tolak, tukar slot, semakan slot bertindih, notifikasi |
| `kakitangan/pengesahan.php` | Pengesahan bukti pembayaran online |
| `kakitangan/invois.php`, `kakitangan/resit.php` | Senarai invois dan resit boleh cetak |
| `doktor/pesakit.php` | Senarai pesakit doktor |
| `doktor/kad_rawatan.php` | Kad Rawatan boleh cetak ikut format kad klinik |
| `pesakit/bayar.php` | Muat naik bukti bayaran e-wallet atau online transfer |
| `pesakit/notifikasi.php` | Notifikasi dalam sistem |
| `env.example.php` | Contoh konfigurasi database |
| `.htaccess`, `storage/.htaccess`, `include/.htaccess` | Header, blok fail sensitif, blok folder bukti |
| `migrasi_klinik_dr_arifin.sql` | Semua perubahan database |

## Fail dibuang

| Fail | Kenapa |
|---|---|
| `admin/hash.php` | Alat jana hash kata laluan tanpa login. Sesiapa boleh buka |
| `pesakit/contoh.php` | Halaman contoh statik, sudah diganti resit sebenar |
| `includes/email.php` | Guna fungsi mail() dan tidak digunakan lagi |

## Keselamatan

| Fail | Perubahan |
|---|---|
| `admin/tambah_doktor.php`, `admin/tambah_kakitangan.php` | Tiada semakan login sebelum ini. Sesiapa boleh cipta akaun doktor dan kakitangan. Sekarang guard pentadbir |
| `doktor/profil.php` | Semakan login dikomen keluar dalam kod asal. Sekarang guard doktor |
| 23 halaman dalam admin, doktor, kakitangan, pesakit | Semakan inline diganti satu fungsi `guard()` yang juga semak status akaun dan timeout sesi |
| `login.php`, `staff_login.php` | Prepared statement, rate limit 3 lapis, mesej ralat generik, `session_regenerate_id`, semakan status_aktif, paksa tukar kata laluan |
| `register.php` | SQL injection dibuang, validasi IC dan email, kata laluan minimum 8, rate limit per IP, mesej tidak dedah email berdaftar |
| `change_password.php` | SQL injection dibuang, semak kata laluan lama, minimum 8 aksara, regenerate session, kosongkan flag paksa tukar |
| `admin/inventori.php` | SQL injection pada padam stok dan kategori, padam ditukar ke POST dengan CSRF |
| `pesakit/appointment.php` | SQL injection dibuang, semakan slot dan tarikh di server, had satu temu janji aktif sehari, nombor giliran tidak lagi diberi masa tempah |
| `pesakit/appointment_saya.php` | SQL injection dibuang, pembolehubah `$id_pesakit` yang tidak ditetapkan dibetulkan, include sidebar dibetulkan |
| `doktor/consultation.php` | SQL injection dibuang, semakan temu janji milik doktor itu |
| `doktor/dashboard.php` | Tindakan panggil dan selesai ditukar dari pautan GET ke borang POST |
| `doktor/call_queue.php`, `doktor/complete_queue.php`, `doktor/update_status.php` | Ditulis semula: POST sahaja, prepared statement, status disemak terhadap senarai sah |
| `kakitangan/call_queue.php`, `kakitangan/complete_queue.php`, `kakitangan/dismiss_call.php` | Sama seperti di atas. `complete_queue.php` sebelum ini dikomen keluar sepenuhnya |
| `kakitangan/dashboard.php` | SQL injection pada carian maklumat kakitangan |
| `queue_display.php` | Output ditapis, hanya nombor giliran hari ini, tiada ralat bila tiada giliran |
| `config.php` | Kelayakan database dipindah ke `env.php`, charset utf8mb4, ralat sambungan tidak dipaparkan kepada pengguna |
| 30 borang POST | Token CSRF ditambah, disemak automatik untuk setiap POST |
| `admin/tetapan.php` | App Password Gmail jadi write-only, muat naik logo disemak dengan magic bytes |
| `include/pembayaran_lib.php` | `SELECT ... FOR UPDATE` supaya satu invois tidak boleh disahkan dua kali, bukti disimpan dalam `storage/` bukan dalam folder web |

## Ciri mengikut jawapan klien

- 7 kod keadaan gigi klinik menggantikan label lama Kaviti, Mahkota, Dirawat. Kod disimpan dalam `kod_kkm` dengan validasi. Sebelum ini `kod_kkm` tersilap diisi dengan kod rawatan.
- Odontogram dewasa FDI 11 hingga 48 dan kanak-kanak 51 hingga 85.
- Kad Rawatan boleh cetak ikut format kad kertas klinik, termasuk bahagian Keizinan Rawatan.
- Harga rawatan ikut senarai yang klien hantar dalam mesej. Julat harga daripada senarai klinik disimpan sebagai `harga_maksimum` dan `catatan_harga`.
- Kaedah bayaran: Tunai, E-Wallet, Online Transfer dengan muat naik bukti dan pengesahan kakitangan.
- 6 laporan: Kewangan, Statistik Rawatan, Inventori, Prestasi Klinik Bulanan, Pesakit, Temu Janji.
- Status temu janji: Menunggu, Disahkan, Ditolak, Dipanggil, Selesai, Tidak Hadir, Dibatalkan. Status pembayaran diasingkan.
- Tetapan Sistem boleh ubah nama klinik, logo, alamat, telefon, email, waktu operasi, slot, harga rawatan dan maklumat bank.
- Design mengikut template Decare (Dentist & Dental Clinic): palet biru #2f6bff, tajuk Poppins bold, body Open Sans, label eyebrow biru huruf besar, kad putih bucu 12px, sidebar gradient biru gelap.
- `index.php` ditulis semula ikut susunan Decare: strip atas (email dan telefon), header sticky dengan butang Tempah Temu Janji, hero dua lajur dengan blob biru dan kad terapung, seksyen perkhidmatan dengan kad ikon, seksyen tentang dengan lencana tahun pengalaman, bar tempahan biru, seksyen waktu operasi, footer biru gelap. Semua maklumat klinik dan senarai rawatan diambil dari database, bukan hardcode.
- Animasi dan transition: scroll reveal fade-up berperingkat, counter nombor statistik, header mengecil bila scroll, blob morph, kad terapung, hover lift pada kad, garis biru muncul pada kad servis, ikon servis berputar, zoom imej, panah butang bergerak, shine pada butang, titik status berdenyut. Semua dimatikan automatik untuk pengguna yang set `prefers-reduced-motion`.

## Database

Semua dalam `migrasi_klinik_dr_arifin.sql`, selamat dijalankan berulang kali (`IF NOT EXISTS`, `WHERE NOT EXISTS`, `ON DUPLICATE KEY`).

- Jadual baharu: `giliran`, `pembayaran`, `invois`, `penggunaan_ubat`, `notifikasi`, `tetapan_sistem`, `token_reset`, `rate_limits`, `audit_log`, `kod_keadaan_gigi`, `harga_rawatan_klinik`
- Column baharu: `temu_janji.notifikasi_staf`, `id_kakitangan`, `catatan`; `pesakit.golongan`, `no_ic_penjaga`, `penyakit_kronik`; `pentadbir.jantina`, `no_telefon`, `jawatan`, `alamat`, `gambar_profil`; `kakitangan.id_pentadbir`, `no_ic`, `jantina`, `kelayakan_akademik`, `alamat`; `doktor.id_pentadbir`, `no_ic`, `jantina`, `alamat`; `mesti_tukar_kata_laluan` pada 4 jadual pengguna; `kod_rawatan.harga_maksimum`, `catatan_harga`; `token_reset.token_hash`
- Enum `temu_janji.status` ditambah `Ditolak`
- `kod_rawatan` diisi semula dengan 12 rawatan daripada senarai klien

## Ujian dijalankan

Dijalankan pada PHP 8.3 dengan MariaDB 10.11 dan database klien yang sebenar.

- POST tanpa token CSRF: 403
- Semua halaman admin, doktor, kakitangan dan `bukti.php` tanpa login: redirect ke login
- Pesakit B buka bukti pembayaran pesakit A: 404
- Pesakit B buka resit pesakit A: ditolak
- Pesakit cuba sahkan bayaran sendiri: ditolak
- Invois sama disahkan dua kali: cubaan kedua ditolak, hanya 1 baris invois
- Fail PHP dinamakan .png dimuat naik: ditolak oleh semakan magic bytes
- Brute force log masuk: had berkuat kuasa, mesej kekal generik
- Pesakit cuba tukar peranan sendiri: gagal, halaman admin masih ditolak
- 6 laporan, bayaran kaunter, luluskan temu janji, check-in, panggil giliran: semua lulus
- Nombor invois dan resit dijana betul: INV-2026-0001, RES-2026-0001
- Cookie sesi: HttpOnly dan SameSite=Lax
- 4 header keselamatan hadir pada semua halaman PHP
- `php -l` lulus untuk semua fail PHP
- Migrasi dijalankan dua kali tanpa ralat

## Sebelum go live

1. Jalankan `migrasi_klinik_dr_arifin.sql` dalam phpMyAdmin selepas import backup asal.
2. Salin `env.example.php` ke `env.php`, tukar kepada pengguna database khas dengan kata laluan. Jangan guna root.
3. Padam atau tukar kata laluan 4 akaun testing (admin.demo, doktor.afiq, staf.aina, pesakit.ahmad).
4. Tukar App Password Gmail yang pernah dihantar dalam chat kepada yang baharu, masukkan melalui Tetapan Sistem.
5. Pastikan folder `storage/bukti` boleh ditulis oleh PHP tetapi tidak boleh dibuka melalui browser.
6. Jika deploy ke hosting: pasang HTTPS, tambah HSTS dan Content-Security-Policy, matikan display_errors, dan pindahkan `env.php` keluar dari folder public jika hosting benarkan.

## Fasa 2: Pilihan A (28 September 2026)

Semua dalam `migrasi_v2_pilihan_a.sql`, selamat dijalankan berulang kali.

### Akaun penjaga dan profil tanggungan
- `include/penjaga_lib.php`: senarai profil dibenarkan, profil aktif, pemilih profil, semakan kebenaran
- `pesakit/tanggungan.php`: penjaga daftar profil anak, urus akses, sahkan atau tolak permohonan akaun
- Satu akaun boleh pegang beberapa profil. Temu janji, rawatan, invois dan X-ray masuk ke profil yang dipilih, bukan profil penjaga
- Pemilih profil pada Papan Utama, Temu Janji, Sejarah Temu Janji, Rekod Rawatan, Invois, Buat Bayaran dan Pelan Ansuran
- Penjaga boleh tutup dan buka semula akses dia ke setiap profil pada bila-bila masa

### Account linking
- `permohonan_pautan`: bila anak yang sudah besar daftar guna No IC yang sama, sistem tidak cipta profil baharu. Sistem hantar permohonan kepada penjaga
- Penjaga sahkan, kemudian email dan kata laluan dipindahkan ke profil yang sedia ada. Semua rekod lama kekal
- Penjaga pilih sama nak kekalkan akses dia atau tidak selepas anak ada akaun sendiri
- Kata laluan dalam permohonan disimpan sebagai hash, bukan teks biasa

### X-Ray
- `doktor/xray.php`: upload gambar atau PDF X-ray dengan tarikh pemeriksaan, jenis, no gigi dan catatan doktor
- Fail disimpan dalam `storage/xray/` di luar capaian web, disemak dengan magic bytes, maksimum 8MB
- `xray_fail.php`: hidangkan fail selepas semakan peranan dan pemilikan profil. Pesakit lain dapat 404
- `admin/jenis_xray.php`: Pentadbir tambah, edit, aktifkan atau nonaktifkan jenis X-ray tanpa ubah coding
- Jenis awal: Periapical, Bitewing, Panoramic, Cephalometric aktif, CBCT tidak aktif
- Jenis yang sudah digunakan tidak boleh dipadam, hanya boleh dinonaktifkan supaya rekod lama kekal

### Pelan ansuran braces dan gigi palsu
- `pelan_bayaran` dan `include/pelan_lib.php`
- `kakitangan/pelan.php`: buka pelan dengan jumlah keseluruhan, deposit dan ansuran dijangka, kemudian rekod setiap bayaran
- Sistem kira jumlah dibayar, baki dan peratus sendiri. Bayaran melebihi baki ditolak
- Pelan bertukar Selesai automatik bila baki habis, dan pesakit dapat notifikasi
- Setiap bayaran ansuran jana invois dan resit sendiri melalui fungsi pembayaran yang sama
- `pesakit/pelan.php`: pesakit lihat jumlah, baki dan sejarah setiap bayaran

### Harga minimum, maksimum dan harga sebenar
- `rekod_rawatan` tambah `harga_dikenakan`, `sebab_harga` dan `luar_julat`
- Borang doktor terima harga sebenar. Kalau di luar julat klinik, sistem halang simpan sampai sebab diisi
- Harga sebenar dan sebab direkod dalam audit log

### Dua nombor pendaftaran
- `pesakit.no_pendaftaran_klinik` diisi dengan 4 digit terakhir IC atau MyKid, dan bukan primary key
- ID Pesakit Sistem dijana dari id_pesakit dengan prefix boleh tukar dalam Tetapan Sistem, contoh P00006
- Kad Rawatan papar kedua-dua nombor dan maklumat penjaga

### Pembetulan pepijat
- `doktor/simpan_rekod.php`: `id_temu_janji` 0 menyebabkan foreign key gagal dan rekod rawatan tidak tersimpan walaupun sistem kata berjaya. Sekarang 0 ditukar NULL, ralat database dibangkitkan, transaksi rollback dan mesej jujur dipaparkan
- `rekod_carta_pergigian` sebelum ini boleh tersimpan dengan `id_rawatan` 0 bila induk gagal. Tidak lagi berlaku
- `.reveal` dalam CSS sebelum ini sembunyikan kandungan tanpa JavaScript. Sekarang JavaScript yang sembunyikan, jadi tanpa JS kandungan tetap nampak
- Statistik di laman utama sebelum ini papar 0 tanpa JavaScript. Sekarang nombor sebenar dalam HTML

## Fasa 3: Naik taraf UI dan UX (30 September 2026)

Pendekatan: tiada markup 45 halaman ditulis semula. Satu fail token shim `assets/css/theme.css` dimuatkan selepas `style.css`, jadi semua class lama terus dapat rupa baharu.

### Kenapa bukan pasang Bootstrap atau Tabler terus
Class sistem ni (`.card`, `.btn`, `.form-control`, `.alert`, `.badge`, `.modal`) sama nama dengan class Bootstrap. Kalau muat Bootstrap penuh, reset Bootstrap akan ubah box-sizing, margin heading, label, table dan link pada semua halaman sekali gus, dan `.modal` lama akan terus hilang sebab Bootstrap jangka struktur `.modal-dialog > .modal-content`. Jadi kami ambil design token Tabler sahaja (warna, radius, shadow, saiz font) dan pakai pada selector sedia ada.

### Apa yang berubah
- Palet baharu: biru #066fd1, latar #f6f8fb, kad putih dengan border halus dan shadow lembut
- Font Inter untuk semua teks, saiz asas 14px
- Sidebar tukar dari gradient biru gelap ke putih bersih dengan item aktif bertanda biru muda, dan ikon Tabler dimasukkan automatik ikut nama menu
- Topbar jadi sticky dengan latar blur
- Jadual: header kelabu muda dengan teks kecil huruf besar, baris hover biru muda, header sticky, lajur tindakan tidak putus baris
- Butang: lebih kecil dan bersih, ada focus ring untuk papan kekunci
- Badge status guna warna latar lembut dengan teks gelap supaya lulus kontras WCAG AA (hijau #166534 atas #dcfce7, kuning #92400e atas #fef3c7, merah #991b1b atas #fee2e2, biru #1e40af atas #dbeafe)
- Alert guna warna sama family dengan badge
- Empty state: baris "Tiada rekod" tukar jadi blok dengan ikon dan tajuk, 11 halaman
- Odontogram: petak gigi ada border kemas, hover ring biru, dan petunjuk kod jadi chip berwarna
- Kad Rawatan: susunan label dan nilai lebih kemas untuk cetakan
- Mobile: bawah 991px sidebar jadi off-canvas dengan butang hamburger yang disuntik automatik ke topbar, plus backdrop gelap
- Print: sidebar, topbar, butang dan alert disembunyikan, kad tidak putus antara muka surat, saiz A4 dengan margin 12mm, dan warna dikekalkan untuk resit dan Kad Rawatan
- prefers-reduced-motion dihormati, semua animasi dimatikan untuk pengguna yang set tu

### Nota teknikal
- Semua selector ditulis penuh tanpa `:is()` atau `:where()` supaya jalan pada browser lama juga
- Ikon guna Tabler Icons webfont dari jsDelivr, versi dipin pada 3.34.0
- Tiada build step, tiada npm, tiada Sass. Fail CSS biasa sahaja
