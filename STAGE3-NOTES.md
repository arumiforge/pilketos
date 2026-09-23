# Stage 3 — Panel Admin, Import, Tema Kandidat, Analitik, Live Count & Unlock (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 3. Stage 4 wajib membacanya bersama
`00-MASTER-PROJECT.md`, `03-ADMIN-IMPORT-ANALYTICS.md`, `STAGE1-NOTES.md`, dan
`STAGE2-NOTES.md`. Isi lengkap setiap file ada di repository (tidak disalin
ulang di sini).

## 1. Stage objective

Panel admin lengkap untuk satu-satunya role pengelola (guru tetap hanya pemilih):

- kerangka responsif: sidebar (drawer di HP), topbar dengan status pemilihan,
  area konten; gaya "lembar rekap" editorial hitam/putih/abu-abu, angka serif
  besar, warna hanya dari aksen solid tiap pasangan; tanpa gradient/emoji;
- dasbor + **live count** AJAX (angka, batang, donat, rekap jenjang & kelas
  diperbarui tanpa reload);
- analitik: keseluruhan, jenis pemilih, jenis kelamin siswa, jenjang 7/8/9,
  kelas, dan detail suara (cari, filter, paginasi);
- kelola pasangan calon (CRUD) + unggah foto & asset tema yang langsung
  dipakai halaman kandidat Stage 2, pratinjau halaman pemilih;
- data siswa & guru (terpisah): daftar, cari, filter, detail, nonaktifkan, hapus
  aman;
- impor Excel siswa & guru dengan PhpSpreadsheet: template -> unggah ->
  pratinjau & validasi -> impor (upsert) -> hasil;
- kontrol jadwal pemilihan (nama, tahun, mulai, selesai, tutup sekarang, buka
  sekarang) berbasis jam server;
- unlock hak suara (cari -> status -> alasan -> konfirmasi) dan audit log.

## 2. Dependencies

| Kebutuhan | Keputusan | Alasan |
|---|---|---|
| Baca/tulis Excel | `phpoffice/phpspreadsheet` **^5.10** (terkunci 5.10.0) + dependensinya: `composer/pcre`, `maennchen/zipstream-php`, `markbaker/complex`, `markbaker/matrix`, `psr/simple-cache` | Diwajibkan spesifikasi; mendukung PHP 8.2+ |
| Olah gambar | GD bawaan PHP (`ext-gd`) | Encode ulang foto di server tanpa library tambahan |
| Validasi isi file | `ext-fileinfo` | MIME dibaca dari isi file, bukan dari browser |
| File .xlsx | `ext-zip` | Format .xlsx adalah arsip zip |
| Grafik | CSS + SVG sendiri (batang horizontal, satu donat, batang komposisi) | Satu bahasa visual, tanpa Chart.js (~200 KB), jalan tanpa internet |
| Live count | `fetch` + polling (tanpa WebSocket) | Cukup untuk LAN sekolah, tidak butuh proses server tambahan |

`composer.json` kini mensyaratkan `ext-fileinfo`, `ext-gd`, `ext-zip` (selain
intl, mbstring, mysqli dari Stage 1) dan menambah `autoload-dev`
`Tests\Support\` -> `tests/_support/`.

Disarankan: **ext-exif** (foto HP yang miring diluruskan otomatis; tanpa exif
foto tetap diterima apa adanya) dan GD dengan dukungan **WebP** (tanpa WebP,
hasil disimpan sebagai JPEG/PNG).

Pengaturan `php.ini` untuk unggahan (Laragon: Menu > PHP > php.ini):

```
upload_max_filesize = 5M     ; batas per file foto (form menampilkan batas efektif)
post_max_size       = 40M    ; formulir kandidat dapat membawa 7 gambar sekaligus
memory_limit        = 256M   ; decode foto HP beresolusi besar
```

Dengan bawaan PHP (2M/8M) aplikasi tetap aman: batas per file mengikuti
`upload_max_filesize`, dan unggahan yang melampaui `post_max_size` mendapat
pesan ukuran yang jelas (bukan pesan token CSRF).

## 3. Required previous files

Semua file Stage 1 & 2 dipertahankan; perilaku voting tidak diubah. Yang
dipakai langsung oleh Stage 3:

- `ElectionModel` (`getCurrentElection()`, `resolveStatus()`), `CandidateModel`,
  `StudentModel`, `TeacherModel`, `AdminModel`, `AuditLogModel` (diperluas);
- `App\Services\VoterType` (diperluas), `VoteService` (tidak dipanggil admin;
  pemilih memilih ulang sendiri lewat alur Stage 2);
- `App\Libraries\CandidateTheme` (pratinjau & warna di panel), `DeviceInfo`
  (isi `device_info`/`browser_info` yang ditampilkan);
- filter `adminauth` (`AdminAuthFilter`, 401 JSON untuk AJAX), CSRF global,
  `Admin\AuthController` (login/logout admin Stage 1);
- `layouts/main.php` + `voting/partials/chapter.php` (pratinjau kandidat),
  `partials/flash.php`, `app.css`, `app.js`, helper `app_helper.php`;
- `public/uploads/.htaccess` (tanpa eksekusi skrip di folder unggahan);
- migration Stage 1 (000001–000009) dan Stage 2 (`2026-02-01-000001`).

## 4. Database changes

Migration baru **`2026-03-01-000001_AddContextToAuditLogs`**:

| Kolom `audit_logs` | Tipe | Isi |
|---|---|---|
| `election_id` | INT UNSIGNED NULL, FK `elections.id` RESTRICT | pemilihan terkait (jadwal, unlock) |
| `vote_unlock_log_id` | INT UNSIGNED NULL, FK `vote_unlock_logs.id` RESTRICT | tautan ke detail unlock (pemilih, jenis pemilih, alasan) |
| `ip_address` | VARCHAR(45) NULL | IP admin (IPv4/IPv6) |

- Kolom nullable sehingga baris audit lama tetap sah; `down()` menghapus FK
  lalu kolom.
- Tidak ada perubahan tabel suara: unlock memakai kontrak Stage 1 apa adanya
  (`LOCKED` -> `UNLOCKED` + `unlocked_at`, `vote_unlock_logs.student_vote_id` /
  `teacher_vote_id`).
- Tidak ada tabel baru untuk impor: pratinjau disimpan sementara sebagai JSON
  di `writable/uploads/imports/` (lihat 8.7).

Jalankan `php spark migrate`.

## 5. New files

```
app/Controllers/Admin/AdminController.php        dasar panel: admin(), election(), render(), audit(), paginasi
app/Controllers/Admin/LiveCountController.php    GET admin/live-count (JSON, no-store, interval polling)
app/Controllers/Admin/AnalyticsController.php    analitik + detail suara
app/Controllers/Admin/CandidateController.php    CRUD pasangan + unggah asset + pratinjau
app/Controllers/Admin/VoterController.php        daftar/detail/status/hapus (dasar siswa & guru)
app/Controllers/Admin/StudentController.php      voterType() = Student
app/Controllers/Admin/TeacherController.php      voterType() = Teacher
app/Controllers/Admin/ImportController.php       template/unggah/pratinjau/impor/hasil (dasar)
app/Controllers/Admin/StudentImportController.php
app/Controllers/Admin/TeacherImportController.php
app/Controllers/Admin/ElectionController.php     jadwal: simpan, tutup sekarang, buka sekarang
app/Controllers/Admin/UnlockController.php       cari pemilih, form unlock, proses unlock
app/Controllers/Admin/AuditController.php        audit log (hanya-baca)
app/Services/AnalyticsService.php                snapshot analitik/live count + detail suara
app/Services/UnlockService.php                   unlock dalam transaction (urutan kunci = castVote)
app/Services/UnlockResult.php                    status hasil unlock + pesan UI
app/Services/VoterDirectory.php                  query daftar/detail/cari pemilih
app/Services/Import/VoterImporter.php            baca .xlsx, validasi, bandingkan DB, commit upsert
app/Services/Import/StudentImporter.php          aturan kolom siswa
app/Services/Import/TeacherImporter.php          aturan kolom guru
app/Services/Import/ImportCell.php               nilai sel Excel -> teks aman (leading zero, tanggal)
app/Services/Import/ImportStore.php              penyimpanan pratinjau sementara (token, 1 jam)
app/Services/Import/ImportConflictException.php
app/Libraries/CandidateAssets.php                validasi + encode ulang gambar kandidat
app/Libraries/CandidateAssetException.php
app/Libraries/Grade.php                          jenjang 7/8/9 dari nama kelas
app/Filters/PostSizeFilter.php                   unggahan > post_max_size -> pesan jelas (413 JSON / redirect)
app/Database/Migrations/2026-03-01-000001_AddContextToAuditLogs.php
app/Views/layouts/admin.php                      kerangka panel + dialog konfirmasi
app/Views/admin/partials/{sidebar,topbar,live_status,scoreboard,candidate_results,recap_table,pager}.php
app/Views/admin/analytics/{index,votes}.php
app/Views/admin/candidates/{index,form,preview}.php
app/Views/admin/voters/{index,show}.php          dipakai siswa & guru
app/Views/admin/import/{index,preview,result}.php
app/Views/admin/election/index.php
app/Views/admin/unlock/{index,form}.php
app/Views/admin/audit/index.php
public/assets/css/admin.css
public/assets/js/admin.js                        drawer, dialog konfirmasi, cek file, pratinjau aksen, filter
public/assets/js/admin-live.js                   polling live count
tests/_support/bootstrap.php                     bootstrap PHPUnit + simulasi is_uploaded_file()
tests/_support/UploadFixture.php                 simulasi $_FILES untuk test
tests/_support/SpreadsheetFactory.php            pembuat .xlsx untuk test
tests/database/{AnalyticsServiceTest,UnlockServiceTest,VoterImportTest,Stage3SchemaTest}.php
tests/feature/{AdminPanelTest,AdminCandidateTest,AdminImportTest}.php
tests/unit/{CandidateAssetsTest,GradeTest}.php
STAGE3-NOTES.md
```

## 6. Modified files

| File | Perubahan |
|---|---|
| `app/Config/Routes.php` | seluruh route panel admin (grup `admin`, filter `adminauth`) |
| `app/Config/Services.php` | `analytics()`, `unlock()`, `voterDirectory()`, `candidateAssets()`, `importStore()` |
| `app/Config/Filters.php` | alias `postsize` + global before (sebelum `csrf`) |
| `app/Config/Pager.php` | template `admin` |
| `app/Controllers/Admin/DashboardController.php` | ditulis ulang: dasbor + snapshot analitik |
| `app/Views/admin/dashboard.php` | ditulis ulang: status, jadwal, countdown, ringkasan, hasil, rekap |
| `app/Models/AuditLogModel.php` | konstanta aksi + label Indonesia, `log()` dengan konteks, `requestIp()` |
| `app/Models/CandidateModel.php` | validasi + pesan Indonesia (nomor unik 1–99, aksen `#RRGGBB`, layout), `getAllOrdered()`, `voteRowCount()` |
| `app/Services/VoterType.php` | `identifierColumn()/Label()`, `adminPath()`, `auditAction()`, `unlockVoteKey()` |
| `app/Libraries/CandidateTheme.php` | `textOnPaper()` (warna teks aksen yang cukup kontras, dipakai panel) |
| `app/Helpers/app_helper.php` | `angka()`, `persen()` (format Indonesia), ikon SVG panel |
| `composer.json`, `composer.lock` | PhpSpreadsheet, ext gd/zip/fileinfo, `autoload-dev` |
| `phpunit.dist.xml` | `bootstrap="tests/_support/bootstrap.php"` |

## 7. Routes

Semua route di bawah memakai filter global `postsize`, `csrf`, `invalidchars`
dan filter `adminauth` (tanpa sesi admin: redirect ke `admin/login`; AJAX: 401
JSON). Semua perubahan data memakai POST + token CSRF.

| Method | URI | Handler |
|---|---|---|
| GET | `admin/dashboard` | `Admin\DashboardController::index` |
| GET | `admin/live-count` | `Admin\LiveCountController::index` (JSON, `no-store`) |
| GET | `admin/analytics` | `Admin\AnalyticsController::index` |
| GET | `admin/analytics/votes` | `Admin\AnalyticsController::votes` (`q`, `type`, `kelas`, `gender`, `candidate`, `status`, `page`) |
| GET | `admin/candidates` | `Admin\CandidateController::index` |
| GET | `admin/candidates/new` | `::new` |
| POST | `admin/candidates` | `::create` (multipart) |
| GET | `admin/candidates/(:num)/edit` | `::edit/$1` |
| POST | `admin/candidates/(:num)` | `::update/$1` (multipart) |
| POST | `admin/candidates/(:num)/delete` | `::delete/$1` |
| GET | `admin/candidates/(:num)/preview` | `::preview/$1` |
| GET | `admin/students`, `admin/teachers` | `Admin\{Student,Teacher}Controller::index` (`q`, `kelas`, `jk`, `vote`, `status`, `page`) |
| GET | `admin/students/(:num)`, `admin/teachers/(:num)` | `::show/$1` |
| POST | `admin/students/(:num)/status`, `admin/teachers/(:num)/status` | `::status/$1` (`status_aktif` 0/1) |
| POST | `admin/students/(:num)/delete`, `admin/teachers/(:num)/delete` | `::delete/$1` |
| GET | `admin/{students,teachers}/import` | `Admin\{Student,Teacher}ImportController::index` |
| GET | `admin/{students,teachers}/import/template` | `::template` (unduh .xlsx) |
| POST | `admin/{students,teachers}/import` | `::upload` (multipart, field `file`) |
| GET | `admin/{students,teachers}/import/preview/(:segment)` | `::preview/$1` (`show`, `page`) |
| POST | `admin/{students,teachers}/import/commit` | `::commit` (`token`, `confirm_skip`) |
| GET | `admin/{students,teachers}/import/result` | `::result` |
| GET | `admin/election` | `Admin\ElectionController::index` |
| POST | `admin/election` | `::save` (`nama`, `tahun`, `start_at`, `end_at`) |
| POST | `admin/election/close` | `::close` (selesai = jam server sekarang) |
| POST | `admin/election/open` | `::open` (mulai = jam server sekarang) |
| GET | `admin/unlock` | `Admin\UnlockController::index` (`q`) |
| GET | `admin/unlock/student/(:num)`, `admin/unlock/teacher/(:num)` | `::form/{student,teacher}/$1` |
| POST | `admin/unlock/student/(:num)`, `admin/unlock/teacher/(:num)` | `::unlock/{student,teacher}/$1` (`vote_id`, `reason`, `confirm`) |
| GET | `admin/audit` | `Admin\AuditController::index` (`q`, `action`, `page`) |

Route Stage 1 & 2 tidak berubah (`admin/login`, `admin/logout`, `admin` ->
`admin/dashboard`, route voting). Jenis pemilih pada unlock ditentukan route
(bukan input); field lain di body seperti `candidate_id` diabaikan.

`GET admin/live-count` (200):

```
{
  "election":   {"id", "nama", "tahun", "status", "label", "start_at", "end_at"} | null,
  "candidates": [{"id", "number", "label", "ketua", "wakil", "accent", "accent_ink", "active",
                  "votes", "student_votes", "teacher_votes", "percent"}],
  "summary":    {"students"|"teachers"|"all": {"total", "voted", "not_voted", "participation",
                                               "votes": {"<id>": n}, "shares": {"<id>": persen}}},
  "groups":     {"type"|"gender"|"grade"|"class": [{"key", "label", "total", "voted", "not_voted",
                                                    "participation", "votes": {...}, "shares": {...}}]},
  "generated_at", "generated_label", "server_time",
  "poll":       {"interval": 10 | 60 | 0}
}
```

## 8. Full implementation (ringkasan)

### 8.1 Kerangka panel

- `layouts/admin.php`: `noindex`, sidebar bernomor 01–09 (Pemilihan / Data /
  Kontrol), topbar (tombol Menu di HP, judul, lencana status pemilihan yang
  ikut diperbarui live), flash, konten, jam server di footer.
- HP (< 1024 px, JavaScript aktif): sidebar menjadi drawer; konten di belakang
  `inert`, Escape/scrim menutup, fokus kembali ke tombol Menu. Tanpa JavaScript
  menu tampil biasa di atas konten.
- `<dialog>` konfirmasi untuk semua `form[data-confirm]` (tindakan berisiko:
  fokus awal di "Batal"); tanpa `<dialog>` jatuh ke `window.confirm`.
- Tabel lebar bergulir di dalam wadahnya sendiri (halaman tidak pernah
  bergeser horizontal di 360 px). Status selalu tertulis, tidak hanya warna.
  Animasi kecil dimatikan oleh `prefers-reduced-motion`.

### 8.2 Analitik (`AnalyticsService`)

Definisi tunggal untuk dasbor, live count, analitik, daftar pemilih:

- pemilih aktif = `status_aktif = 1`; suara sah = baris `*_votes` berstatus
  `LOCKED` pada election berjalan milik pemilih aktif (riwayat `UNLOCKED`
  tidak dihitung);
- sudah memilih = pemilih aktif dengan suara sah; belum = aktif - sudah;
- jadi `sudah + belum = total aktif` (siswa, guru, gabungan) dan
  `suara siswa + suara guru = total suara`.

Siswa dihitung dengan **satu** query agregasi MySQL
(`GROUP BY kelas, jenis_kelamin, candidate_id` dengan LEFT JOIN suara LOCKED);
rekap kelas, jenis kelamin, jenjang, dan total siswa dijumlahkan dari baris
agregat yang sama sehingga tidak mungkin saling bertentangan. Guru satu query.
PHP hanya menjumlahkan beberapa ratus baris agregat; data mentah tidak pernah
dikirim ke browser. Persentase pasangan = suara / total suara sah kelompok.

- Jenjang (`Grade::fromKelas`): angka Arab (`7A`, `07-C`, `Kelas 9D`) atau
  Romawi (`VII A`, `VIII-B`, `IX-C`) di awal nama kelas; nilai lain (`10A`,
  `70`, `X IPA`) masuk kelompok "Lainnya" yang hanya tampil bila berisi.
- Kelas dinormalkan (huruf besar, spasi tunggal) dan diurutkan per jenjang.
- Pasangan nonaktif tetap ditampilkan bila sudah memiliki suara (label
  "nonaktif") agar jumlah per pasangan tetap sama dengan total.
- Guru tidak memiliki jenis kelamin; rekap jenis kelamin khusus siswa.

Grafik: batang horizontal per pasangan + satu donat (dasbor/analitik) dan
batang komposisi per baris rekap. Satu bahasa visual, warna = aksen pasangan.

### 8.3 Live count

- `admin-live.js` memanggil `GET admin/live-count` dan memperbarui angka
  (`data-live-value`), batang, donat, lencana status, dan tabel rekap
  (jenis pemilih, jenis kelamin, jenjang, kelas) lewat `textContent`, tanpa
  reload. Bila susunan pasangan berubah, daftar hasil dibangun ulang.
- Irama dari server: 10 detik saat ONGOING, 60 detik saat UPCOMING, berhenti
  saat FINISHED/tanpa jadwal. Dijeda saat tab tidak aktif dan langsung
  diperbarui saat aktif kembali; gagal jaringan = jeda bertambah (maks 60 dtk,
  timeout 8 dtk); sesi habis (401) = berhenti dan ke halaman login; tombol
  "Perbarui" memaksa ambil data. Pembaca layar mendapat ringkasan perubahan
  melalui `aria-live`.

### 8.4 Detail suara

- Satu query `UNION ALL` (siswa + guru) yang dibungkus subquery sehingga
  cari/filter/urut/paginasi dikerjakan MySQL; 25 baris per halaman, terbaru
  lebih dulu.
- Bawaan hanya suara sah (jumlahnya sama dengan total di dasbor). Filter
  status: sah, riwayat dibuka admin, semua baris (suara pemilih nonaktif diberi
  label "tidak dihitung").
- Filter: cari (nama/NISN/NIP), jenis pemilih, kelas, jenis kelamin, pasangan.
  Filter kelas/jenis kelamin otomatis hanya siswa (dijelaskan di halaman).
- Kolom: no, pemilih & jenis, NISN/NIP, kelas, absen, JK, pilihan, waktu,
  perangkat & browser, status.

### 8.5 Pasangan calon & unggahan (`CandidateController`, `CandidateAssets`)

- Form: nomor urut (1–99, unik), nama ketua/wakil, visi, misi, nama tema,
  warna aksen (`#RRGGBB`, pratinjau kontras langsung), layout (otomatis /
  split / poster / column), status aktif, dan 7 slot gambar: foto ketua, foto
  wakil, hero (foto berdua), latar panggung (`theme_background`), artwork,
  tekstur, poster (`theme_asset` JSON `hero/texture/artwork/poster`).
- Validasi berlapis per file: status unggah PHP + `is_uploaded_file()`,
  ukuran (5 MB, tekstur 2 MB, dibatasi `upload_max_filesize`), ekstensi asli
  `jpg/jpeg/png/webp`, MIME dari isi file (finfo) harus sama dengan ekstensi,
  `getimagesize()` (dimensi minimum per slot, maks 8000 px/sisi dan 40 MP),
  lalu gambar di-decode GD dan **di-encode ulang** (WebP q82; tanpa WebP:
  JPEG/PNG): metadata EXIF/GPS terbuang, sisipan polyglot hilang, orientasi
  foto HP diluruskan, sisi terpanjang diperkecil (foto 1000 px, latar 2000 px).
- Nama file selalu dibuat server: `c<nomor>-<slot>-<16 hex acak>.<ext>`;
  nama asli tidak dipakai. Disimpan di `public/uploads/candidates/`; kolom
  hanya berisi nama file.
- Teks divalidasi dulu, lalu semua file diproses; bila ada yang gagal, file
  baru dihapus dan database tidak berubah. File lama dihapus setelah simpan
  berhasil (ganti/hapus asset). `CandidateAssets::delete()` hanya menghapus
  file gambar di dalam folder unggahan (anti path traversal).
- Pasangan yang pernah menerima suara (LOCKED atau riwayat) tidak dapat
  dihapus: admin diarahkan menonaktifkan (`status_aktif = 0`). Pasangan tanpa
  suara dapat dihapus beserta file-nya.
- Pratinjau memakai `voting/partials/chapter.php` Stage 2 (tanpa tombol
  coblos), jadi yang dilihat admin sama dengan halaman pemilih.
- Setiap tambah/ubah/hapus dicatat di audit (field yang berubah + file).

### 8.6 Data siswa & guru (`VoterController`, `VoterDirectory`)

- Daftar (25 per halaman, urut kelas/absen/nama): cari nama/identitas, filter
  kelas & jenis kelamin (siswa), status memilih (sudah/belum), status akun
  (aktif/nonaktif/semua). "Belum memilih + aktif" sama dengan angka dasbor.
- Detail: identitas, kode unik tersamar dengan tombol "Tampilkan", status hak
  suara (pilihan, waktu, perangkat, browser), riwayat suara & unlock, tautan
  unlock.
- Nonaktifkan/aktifkan akun (audit): pemilih nonaktif tidak dapat login dan
  tidak dihitung. Hapus hanya untuk data tanpa riwayat suara/unlock (mis. salah
  impor); selain itu ditolak dengan saran menonaktifkan.

### 8.7 Impor Excel (`VoterImporter`, `StudentImporter`, `TeacherImporter`)

Template dibuat server: `student-import-template.xlsx`
(`no, NISN, nama, jenis_kelamin, kelas, nomor_absen, kodeunik`) dan
`teacher-import-template.xlsx` (`no, NIP, nama, kodeunik`), kolom identitas &
kode unik berformat Teks, daftar pilihan L/P, sheet "Petunjuk".

Alur: unduh template -> unggah -> pratinjau & validasi -> impor -> hasil.

- File: hanya `.xlsx`, maks 5 MB, maks 3.000 baris data, isi zip diperiksa
  (maks 60 MB setelah diekstrak, 200 entri) sebelum dibaca; hanya sheet
  pertama, header dicari di 5 baris teratas (tidak peka huruf besar/spasi).
- Siswa: NISN tepat 10 digit; jenis kelamin L/P (juga "Laki-laki",
  "Perempuan"; tidak ditebak dari nama); kelas wajib berjenjang 7/8/9 dan
  dibakukan; nomor absen kosong atau 1–999 (absen ganda dalam satu kelas =
  peringatan); kode unik tanggal lahir DDMMYYYY yang valid.
- Guru: NIP hanya angka (spasi dibuang), maks 30 digit; 18 digit (PNS) normal,
  panjang lain (mis. NUPTK) diterima dengan peringatan; NIP yang tersimpan
  sebagai angka > 15 digit ditolak karena Excel sudah membulatkannya.
- **Leading zero**: sel teks dipakai apa adanya; sel angka dikembalikan ke
  panjang yang benar (`12345679` -> `0012345679`) dan tanggal Excel dibaca
  ulang sebagai DDMMYYYY, selalu dengan peringatan di pratinjau.
- Identitas ganda di dalam file: semua baris kembarnya ditolak.
- Dibandingkan dengan database: baru / diperbarui (kolom yang berubah
  ditampilkan) / tidak berubah / bermasalah. Identitas yang sudah ada
  **diperbarui (upsert)**, tidak diduplikasi; `status_aktif` pemilih lama tidak
  diubah dan pemilih yang tidak ada di file tidak dihapus.
- Pratinjau (100 baris per halaman, tab per status) disimpan sebagai JSON di
  `writable/uploads/imports/` (di luar `public/`): token acak 128-bit, terikat
  admin & jenis pemilih, berlaku 1 jam, **sekali pakai**. File Excel asli tidak
  disimpan.
- Impor: bila ada baris bermasalah, admin wajib mencentang "baris bermasalah
  tidak diimpor". Commit dalam satu transaction (`insertBatch`/`updateBatch`
  per 200 baris) dan diputuskan ulang terhadap isi database saat itu;
  konflik (duplicate key/lock) membatalkan seluruh impor dengan pesan jelas.
- Hasil: jumlah ditambahkan / diperbarui / tidak berubah / dilewati; audit
  `IMPORT_STUDENT`/`IMPORT_TEACHER` berisi nama file dan jumlah yang sama.

### 8.8 Jadwal pemilihan (`ElectionController`)

- Form nama, tahun, mulai, selesai (`datetime-local` dibaca sebagai WIB,
  zona aplikasi). Validasi: selesai harus setelah mulai. Bila belum ada
  pemilihan, form membuat baris baru (`ELECTION_CREATE`).
- Status selalu `ElectionModel::resolveStatus()` terhadap jam server; tidak ada
  pengubah status manual. "Tutup pemilihan sekarang" = `end_at` jam server
  (saat berlangsung); "Buka sekarang" = `start_at` jam server (sebelum mulai).
  Memperpanjang = mengubah `end_at`.
- Perubahan saat berlangsung meminta konfirmasi. Audit `SCHEDULE_UPDATE`
  mencatat nilai lama -> baru dan status (atau "Status tetap").
- `VoteService` Stage 2 memegang shared lock pada baris election, sehingga
  penutupan menunggu suara yang sedang diproses; suara setelahnya ditolak.

### 8.9 Unlock (`UnlockController`, `UnlockService`)

Alur: cari (nama/NISN/NIP, siswa & guru; identitas yang sama persis di urutan
teratas) -> status suara (pilihan, waktu, perangkat, status kunci) -> alasan
(10–500 karakter) + centang pernyataan -> dialog konfirmasi -> proses.

```
transBegin
  SELECT election terbaru ... LOCK IN SHARE MODE        (harus ONGOING)
  SELECT pemilih ... FOR UPDATE                         (urutan kunci = castVote)
  SELECT suara LOCKED pemilih; id harus sama dengan vote_id form (anti basi/IDOR)
  UPDATE *_votes SET status='UNLOCKED', unlocked_at WHERE id=? AND status='LOCKED'
  INSERT vote_unlock_logs (student_vote_id/teacher_vote_id, admin, alasan, waktu)
  INSERT audit_logs (UNLOCK_VOTE, election_id, vote_unlock_log_id, ip)
transCommit
1205/1213 -> "coba lagi" (tidak ada yang berubah)
```

- Admin tidak pernah memilih atau mengubah `candidate_id`; baris lama tidak
  dihapus. Pemilih login lalu memilih sendiri (baris LOCKED baru lewat
  `VoteService`).
- Unlock hanya saat pemilihan berlangsung (setelah selesai, unlock akan
  mengurangi hasil akhir tanpa kesempatan memilih ulang).
- Keterangan audit tidak menyebut pasangan yang dipilih.

### 8.10 Audit log (`AuditController`)

Kolom: waktu, admin (+ username & IP), tindakan, pemilih & jenis pemilih
(dari `vote_unlock_logs` untuk unlock), pemilihan, alasan, keterangan. Filter
tindakan + cari (keterangan, alasan, pemilih, admin), 30 per halaman, terbaru
di atas. Hanya-baca: panel tidak menyediakan ubah/hapus audit.

Tindakan yang dicatat: `UNLOCK_VOTE`, `SCHEDULE_UPDATE`, `ELECTION_CREATE`,
`CANDIDATE_CREATE/UPDATE/DELETE`, `IMPORT_STUDENT/TEACHER`,
`STUDENT_STATUS/TEACHER_STATUS`, `STUDENT_DELETE/TEACHER_DELETE`.

### 8.11 Keamanan yang ditambahkan

- Semua route admin di balik `adminauth`; semua POST ber-CSRF; id di URL
  selalu dicek keberadaannya (404) dan jenis pemilih ditentukan route.
- Output di-escape (`esc()`), data live dimasukkan lewat `textContent`.
- `admin/live-count` `Cache-Control: no-store`; halaman admin `noindex`.
- Tidak ada data siswa/guru di localStorage; pratinjau impor hanya di server.
- Unggahan: lihat 8.5; `public/uploads/.htaccess` menolak eksekusi skrip.
- `PostSizeFilter` (sebelum CSRF): body > `post_max_size` -> 413 JSON untuk
  AJAX atau kembali ke form dengan pesan ukuran.

## 9. Testing

Jalankan `composer test` (atau `vendor/bin/phpunit --no-coverage`).

Hasil (PHP 8.4.19): **223 test, 1.271 assertion, lulus** di **MariaDB
10.11.14** dan **MySQL 8.0.46** (sql_mode bawaan MySQL 8 termasuk
`ONLY_FULL_GROUP_BY` dan `STRICT_TRANS_TABLES`). Stage 1–2: 107 test,
Stage 3: 116 test.

| Checklist Stage 3 | Bukti |
|---|---|
| dashboard | `AdminPanelTest::testDashboardShowsSummaryCandidatesAndRecaps`, `testDashboardWithoutElectionPromptsForSchedule`, `testAdminRoutesRequireAdminSession`, `testAdminPostRequiresCsrfToken` |
| student import | `VoterImportTest` (template, leading zero, baris salah + alasan, sinonim JK, NISN ganda, absen ganda, header, file kosong/bukan Excel, batas baris, upsert tanpa duplikat, keputusan ulang saat commit, login dengan NISN ber-nol depan, token pratinjau), `AdminImportTest::testStudentImportFlowPreviewCommitResultAndAudit`, `testTemplateDownloadIsTheStudentTemplate`, `testInvalidFilesAreRejectedBeforePreview`, `testPreviewTokenIsBoundToTheUploadingAdmin`, `testImportPageExplainsTemplateAndLimits` |
| teacher import | `VoterImportTest::testTeacherTemplateHeaders`, `testTeacherNipRules`, `AdminImportTest::testTeacherImportFlow` |
| candidate upload | `CandidateAssetsTest` (encode ulang + nama acak + downscale, EXIF, polyglot, file menyamar, dimensi/ukuran, error unggah HTTP, hapus aman), `AdminCandidateTest::testCreateCandidateWithUploadsStoresRandomNamesAndAudits`, `testReplacingAndRemovingAssetsDeletesOldFiles`, `testInvalidFieldsAndFilesAreRejectedWithoutSavingAnything`, `testMassAssignmentFieldsAreIgnored`, `testCandidateWithVotesCannotBeDeletedButCanBeDeactivated`, `testCandidateWithoutVotesCanBeDeletedWithItsFiles` |
| candidate theme | `AdminCandidateTest::testUploadedThemeIsUsedByVoterCandidatePage`, `testPreviewRendersVoterChapterForAdmin`, `testCandidateListShowsThemeAndAssetCompleteness`, `testEditFormShowsCurrentValuesAndUploadLimits` |
| election schedule | `testScheduleUpdateUsesServerTimeZoneAndIsAudited`, `testExtendingOngoingElectionKeepsStatusAndSaysSoInAudit`, `testScheduleValidationRejectsEndBeforeStart`, `testCloseNowEndsVotingAtServerTime`, `testOpenNowStartsUpcomingElection`, `testElectionCanBeCreatedWhenNoneExists` |
| analytics overall | `AnalyticsServiceTest::testOverallCountsOnlyLockedVotesOfActiveVoters`, `testBreakdownsAreConsistentWithTotals`, `testNoElectionMeansEveryoneHasNotVoted`, `testInactiveCandidateIsHiddenOnlyWhenItHasNoVotes`, `AdminPanelTest::testAnalyticsPageShowsAllBreakdowns` |
| analytics by gender | `AnalyticsServiceTest::testStudentGenderBreakdown` |
| analytics by voter type | `AnalyticsServiceTest::testVoterTypeBreakdown` |
| analytics by class | `AnalyticsServiceTest::testClassBreakdownIsSortedByGradeAndName`, `testClassesAreNormalizedAndSorted`, `AdminPanelTest::testStudentListFiltersMatchDashboardNumbers` |
| analytics by grade | `AnalyticsServiceTest::testGradeBreakdownIncludesRomanNumeralsAndOtherBucket`, `GradeTest` (21 contoh nama kelas) |
| live count | `testLiveCountReturnsConsistentJson`, `testLiveCountPollingFollowsElectionStatus`, `testLiveCountRejectsNonAdminAjaxWith401Json`, `AnalyticsServiceTest::testJsonShapeKeepsCandidateMapsAsObjects` + uji browser |
| detail votes | `AnalyticsServiceTest::testDetailVotesDefaultListsExactlyTheCountedVotes`, `testDetailVotesFiltersAndPagination`, `AdminPanelTest::testDetailVotesListsCountedVotesWithDeviceAndBrowser`, `testDetailVotesPaginates`, `testVoterNamesAreEscapedInAdminPages` |
| unlock | `UnlockServiceTest` (riwayat + log + audit, pilih ulang sendiri, alasan wajib, vote_id basi/asing, hanya saat ONGOING, lock wait -> RETRY, **unlock paralel vs pilih ulang** -> tepat satu LOCKED), `AdminPanelTest::testUnlockSearchFindsStudentsAndTeachersWithVoteStatus`, `testUnlockFlowRequiresReasonAndConfirmation`, `testUnlockKeepsHistoryLogsAuditAndLetsVoterVoteAgain`, `testUnlockOfStaleVoteIsRejected`, `testUnlockIsRefusedAfterElectionFinished`, `testAdminCannotCastVoteForVoters` |
| audit | `AdminPanelTest::testAuditLogListsAdministrativeActionsNewestFirst`, `Stage3SchemaTest` (kolom konteks, FK, baris lama), `testDeactivatingVoterIsAuditedAndBlocksLogin`, `testVoterWithHistoryCannotBeDeletedButCleanRecordCan` |
| tambahan | `testOversizedPostGetsClearMessageInsteadOfCsrfError`, `testTeacherListIsSeparateFromStudents`, `testVoterDetailShowsVoteHistoryAndUnlockAction` |

Catatan test:

- Unggahan disimulasikan tanpa server web: `tests/_support/bootstrap.php`
  mendefinisikan `CodeIgniter\HTTP\Files\is_uploaded_file()` yang hanya
  menerima file yang didaftarkan `UploadFixture`; file ditulis ke folder
  sementara (service `candidateAssets`/`importStore` di-mock), bukan ke
  `public/uploads`.
- Test paralel memakai `pcntl_fork` dan otomatis **skipped** di
  Windows/Laragon; test orientasi EXIF skipped bila ext-exif tidak aktif; hasil
  gambar mengikuti dukungan WebP server (`CandidateAssets::outputExtension()`).

Uji browser (Chromium via Playwright terhadap `php spark serve` + database
development; dijalankan manual, tidak termasuk `composer test`):

- 14 halaman admin di 1280x860 dan 360x760: tanpa error konsol dan tanpa
  overflow horizontal halaman;
- siswa login & mencoblos lewat alur Stage 2 (context browser lain) ->
  dasbor admin yang sedang terbuka berubah 7 -> 8 suara dan pasangan 02
  2 -> 3 **tanpa reload** (satu request polling);
- unlock: dialog konfirmasi (fokus di "Batal") -> baris UNLOCKED + log unlock
  + audit -> siswa memilih ulang sendiri (riwayat `02:UNLOCKED, 03:LOCKED`);
- impor siswa: unggah -> pratinjau (baru, diperbarui, peringatan leading zero,
  bermasalah) -> centang -> impor -> hasil, data tersimpan;
- ubah jadwal saat berlangsung (dialog konfirmasi) -> tersimpan + audit;
- audit log menampilkan unlock, impor, jadwal;
- drawer HP: buka, konten `inert`, Escape menutup, fokus kembali ke Menu.

Masalah yang ditemukan dan diperbaiki lewat uji browser: teks
`visually-hidden` di dalam tabel melebarkan halaman HP; reset
`ol[class]`/`ul[class]` app.css menghapus margin daftar admin; input
`datetime-local` terpotong; tabel detail suara & audit terlalu lebar di 1280 px;
sel kosong papan angka tampil abu-abu; judul topbar terpotong di HP; pesan flash
unlock berulang.

Belum diverifikasi: runtime PHP 8.2 dan Laragon/Windows secara langsung
(dependency dikunci dengan platform PHP 8.2), Safari iOS dan HP Android fisik,
file Excel sekolah yang sebenarnya (dibuat dari aplikasi selain Excel/LibreOffice),
server dengan GD tanpa WebP.

## 10. Edge cases

| Kasus | Perilaku |
|---|---|
| Suara masuk saat admin membuka dasbor | angka berubah pada polling berikutnya (maks 10 dtk) tanpa reload |
| Tab dasbor ditinggal lama / HP tidur | polling berhenti; saat aktif lagi langsung diperbarui |
| Sesi admin habis saat polling | 401 JSON -> polling berhenti -> halaman login |
| Server/jaringan gagal | indikator "Koneksi terputus, mencoba lagi", dicoba lagi dengan jeda bertambah |
| Pemilih dinonaktifkan setelah memilih | suaranya tidak dihitung (tetap tampil di "Semua baris" dengan label) |
| Pasangan dinonaktifkan setelah menerima suara | tetap di hasil dengan label "nonaktif"; tidak bisa dihapus |
| Nomor urut dipakai pasangan lain | ditolak dengan pesan |
| Aksen bukan `#RRGGBB` / terlalu terang | ditolak / teks otomatis gelap (kontras) |
| File .php/.svg/.gif atau ekstensi palsu | ditolak, tidak ada file tersimpan |
| Foto HP 12 MP miring dengan GPS | diluruskan, diperkecil, metadata dibuang |
| Total unggahan > post_max_size | pesan ukuran jelas (bukan "token kedaluwarsa"); dicek juga di browser |
| NISN "12345679" (angka, nol hilang) | dipulihkan `0012345679` + peringatan |
| NIP 18 digit diketik sebagai angka | ditolak (digit terakhir sudah dibulatkan Excel) |
| Kode unik terbaca tanggal Excel | dibaca ulang DDMMYYYY + peringatan |
| NISN ganda dalam file | semua baris kembar ditolak |
| Impor ulang file yang sama | "tidak berubah", tanpa duplikat |
| Data berubah antara pratinjau dan impor | keputusan diulang saat commit; konflik membatalkan seluruh impor |
| Token pratinjau dipakai admin lain / jenis lain / setelah 1 jam / kedua kali | ditolak, kembali ke halaman unggah |
| Menutup pemilihan saat suara sedang disimpan | menunggu kunci; suara yang sudah diproses sah, sesudahnya ditolak |
| Jam perangkat admin salah | tidak berpengaruh (semua waktu dari server) |
| Unlock bersamaan dengan pemilih mencoblos ulang | dikunci bergantian; tepat satu suara LOCKED |
| Form unlock basi (suara sudah dibuka/berganti) | ditolak, tidak ada perubahan |
| Unlock setelah pemilihan selesai | ditolak |
| Hapus pemilih yang punya riwayat suara | ditolak; gunakan nonaktifkan |
| Nama berisi HTML | di-escape di semua halaman admin |
| JavaScript mati | panel tetap berfungsi: menu tampil statis, "Perbarui" memuat ulang halaman; dialog konfirmasi tidak muncul, tetapi unlock tetap wajib alasan + centang pernyataan yang divalidasi server |

## 11. Handoff ke Stage 4

File final yang wajib diaudit:

**Auth**: `app/Filters/AdminAuthFilter.php`, `StudentAuthFilter.php`,
`TeacherAuthFilter.php`, `app/Controllers/Admin/AuthController.php`,
`app/Controllers/{Student,Teacher}/AuthController.php`, `app/Config/Filters.php`

**Student**: `app/Controllers/Admin/StudentController.php`,
`app/Controllers/Admin/VoterController.php`, `app/Services/VoterDirectory.php`,
`app/Models/StudentModel.php`, `app/Views/admin/voters/*`,
`app/Controllers/Student/*`, `app/Views/student/*`

**Teacher**: `app/Controllers/Admin/TeacherController.php`,
`app/Models/TeacherModel.php`, `app/Controllers/Teacher/*`, `app/Views/teacher/*`

**Candidate**: `app/Controllers/Admin/CandidateController.php`,
`app/Models/CandidateModel.php`, `app/Libraries/CandidateTheme.php`,
`app/Views/admin/candidates/*`, `app/Views/voting/partials/*`

**Election**: `app/Controllers/Admin/ElectionController.php`,
`app/Models/ElectionModel.php`, `app/Controllers/ElectionController.php`,
`app/Views/admin/election/index.php`

**Vote**: `app/Services/VoteService.php`, `VoteResult.php`, `VoterType.php`,
`app/Controllers/VotingController.php`, `app/Models/{Student,Teacher}VoteModel.php`

**Analytics**: `app/Services/AnalyticsService.php`, `app/Libraries/Grade.php`,
`app/Controllers/Admin/{Dashboard,Analytics,LiveCount}Controller.php`,
`app/Views/admin/dashboard.php`, `app/Views/admin/analytics/*`,
`app/Views/admin/partials/{scoreboard,candidate_results,recap_table,live_status}.php`

**Import**: `app/Services/Import/*`,
`app/Controllers/Admin/{Import,StudentImport,TeacherImport}Controller.php`,
`app/Views/admin/import/*`

**Unlock**: `app/Services/UnlockService.php`, `UnlockResult.php`,
`app/Controllers/Admin/UnlockController.php`, `app/Views/admin/unlock/*`,
`app/Models/VoteUnlockLogModel.php`

**Audit**: `app/Models/AuditLogModel.php`, `app/Controllers/Admin/AuditController.php`,
`app/Views/admin/audit/index.php`

**Upload**: `app/Libraries/CandidateAssets.php`, `CandidateAssetException.php`,
`app/Filters/PostSizeFilter.php`, `public/uploads/.htaccess`,
`public/uploads/candidates/`, `writable/uploads/imports/`

**Routes**: `app/Config/Routes.php`

**Migrations**: `app/Database/Migrations/*` (Stage 1 000001–000009,
`2026-02-01-000001_AddThemeLayoutToCandidates`,
`2026-03-01-000001_AddContextToAuditLogs`), `app/Database/Seeds/*`
(development saja)

**Models**: `app/Models/*`

**Services**: `app/Services/*`, `app/Config/Services.php`

**Views**: `app/Views/layouts/{main,admin}.php`, `app/Views/admin/**`,
`app/Views/voting/**`, `app/Views/partials/*`, `app/Views/home/index.php`

**JS/CSS**: `public/assets/css/{app,voting,admin}.css`,
`public/assets/js/{app,countdown,candidates,ballot,nail-webgl,admin,admin-live}.js`

Catatan untuk Stage 4:
1. Halaman hasil akhir + confetti (MASTER 17) belum dibuat; hitung dari
   `AnalyticsService::snapshot()` agar angka sama dengan panel admin.
2. Verifikasi analitik (04 bagian 9) sudah dijamin oleh definisi tunggal di
   `AnalyticsService` dan diuji `testBreakdownsAreConsistentWithTotals`.
3. Deployment: `CI_ENVIRONMENT = production` (Debug Toolbar mati), ekstensi
   gd/zip/fileinfo (+ exif) aktif, nilai `php.ini` di bagian 2, folder
   `public/uploads/candidates` dan `writable/uploads/imports` dapat ditulis PHP.
4. Admin production dibuat terpisah (seeder hanya development).
5. Tetap tanpa gradient, tanpa emoji, hormati `prefers-reduced-motion`.
