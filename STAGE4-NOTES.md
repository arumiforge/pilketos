# Stage 4 — Final Integration, Security, Performance, Final Result & Deployment (hasil implementasi + handoff akhir)

Dokumen ini adalah kontrak aktual Stage 4 dan mengikuti format output
`04-FINAL-INTEGRATION-TESTING-DEPLOYMENT.md` bagian 20. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md`, `STAGE2-NOTES.md`,
`STAGE3-NOTES.md`, dan `README.md` (panduan pemakaian & deployment).
Isi lengkap setiap file ada di repository (tidak disalin ulang di sini).

Ringkasan Stage 4:

- audit alur lengkap, keamanan (18 area + batasan per role), integritas suara,
  analitik, impor, UX, aksesibilitas, performa, dan pengalaman kandidat;
- penjaga integritas di tingkat database (CHECK + 8 trigger);
- halaman **hasil akhir** admin dengan tema pasangan terpilih + **confetti**
  canvas sekali tampil; keadaan final di dasbor;
- hasil akhir dikunci setelah pemilihan selesai; membuka kembali wajib
  konfirmasi;
- Content-Security-Policy aktif, header keamanan tambahan, batas idle sesi
  pemilih, impor sekali pakai, pengaman web server (`.htaccess`);
- alat deployment: `php spark admin:create`, `admin:password`, `osis:check`;
- README final + deployment Laragon; 66 test baru (total 289).

> Pembaruan Stage 5 (redesign beranda, `STAGE5-NOTES.md`): beranda imersif,
> navigasi logo di tengah tanpa tombol masuk, footer rata tengah, dan route
> publik baru `GET live-count` (persentase per pasangan + partisipasi).
> Bagian yang terdampak di dokumen ini diberi tanda "Stage 5". Total test
> sekarang 306.

## 1. Final audit

### 1.1 Alur lengkap (04 bagian 1)

| Langkah | Implementasi | Bukti |
|---|---|---|
| Landing | `Home::index`, countdown jam server (`partials/countdown.php`, `countdown.js`) | `VotingTest`, sweep browser |
| Login siswa/guru | `Student\|Teacher\AuthController`, throttle, `regenerate(true)` | `AuthTest`, `RoleMatrixTest::testLoginIsSeparatedPerRole`, browser |
| Pengalaman kandidat | `voting/index.php` + `voting/partials/chapter.php`, tema `CandidateTheme` | `VotingTest::testEachCandidateHasItsOwnThemeAndLayout` |
| Visi-misi interaktif | `candidates.js` (kata menyala, misi buka-tutup, parallax) | `testVisiMisiInteractionHooksAreRendered`, browser |
| Voting 3D | `ballot.js` + `nail-webgl.js` (WebGL, dimuat malas) | browser: drag paku 3D -> kotak 02 |
| Konfirmasi | `<dialog>` + `voting/confirm.php` (tanpa JS) | browser (JS & tanpa JS) |
| Simpan server | `VoteService::castVote()` (transaction, `FOR UPDATE`, unique key) | `VoteServiceTest`, `VoteIntegrityTest` |
| Terkunci | status `LOCKED`, CTA hilang, ballot mengarah ke my-vote | `testAfterVotingBallotAndConfirmRedirectToOwnChoice` |
| Login ulang | dasbor menampilkan status terkunci | `testReloginShowsLockedOwnChoiceWithoutVotingCta` |
| Hanya pilihan sendiri | `my-vote` dari `findActiveVote()` sesi | `testOwnChoicePageShowsNoOtherCandidatesOrOtherVoters`, `RoleMatrixTest::testOwnVoteOnlyAndNoResultsForVoters` |
| Analitik admin | `AnalyticsService` (satu definisi) | `AnalyticsConsistencyTest`, `AnalyticsServiceTest` |
| Unlock bila perlu | `UnlockService` (alasan, log, audit, riwayat) | `UnlockServiceTest`, browser (unlock -> pilih ulang) |
| Pemilihan berakhir | `ElectionModel::resolveStatus()` (`now >= end_at` = FINISHED) | `testVotingAtEndTimeIsRejectedButOneSecondBeforeIsAccepted`, `FinalResultTest::testVotingEndpointRejectsAfterEndAndResultIsUnchanged` |
| Hasil akhir | `Admin\ResultController` + `FinalResult` | `FinalResultTest` (14), `FinalResultRankingTest` (6) |
| Confetti | `confetti.js` hanya di `admin/results` saat ada pemenang | `FinalResultTest`, browser (muncul, hilang, tidak ulang) |

Alur di atas dijalankan ujung ke ujung di browser pada Apache 2.4 + mod_php
8.2 (bagian 9.3), termasuk tutup pemilihan -> POST suara terlambat ditolak
`403 voting_closed` -> hasil akhir + confetti.

### 1.2 Audit per bagian 04

| Bagian | Hasil audit | Perubahan Stage 4 |
|---|---|---|
| 2 Keamanan | 18 area diperiksa; 13 temuan (bagian 2) | lihat bagian 3 |
| 3 Integritas suara | state machine sudah benar di service; database belum menolak transisi lain | CHECK + trigger (bagian 8), `VoteIntegrityTest` |
| 4 Hasil akhir | status FINISHED sejak `end_at` sudah ada; belum ada halaman hasil & keadaan final | `FinalResult`, `admin/results`, banner dasbor, kunci hasil |
| 5 Confetti | belum ada | `confetti.js` (canvas 2D, tanpa library) |
| 6 Tema kandidat | tema per pasangan dipakai halaman pemilih & pratinjau | panggung pemenang memakai aksen, pola, layout, foto/hero pasangan itu |
| 7 Performa 3D | lazy-load, loop render hanya saat bergerak, DPR maks 2, fallback 2D sudah ada | buffer/program dihapus + `WEBGL_lose_context` saat `pagehide` |
| 8 Countdown | jam server + `performance.now()`, tidak negatif, 1 tick/detik, jeda saat tab tersembunyi, hanya visual | halaman hasil terkunci memakai countdown compact yang sama |
| 9 Analitik | definisi tunggal `AnalyticsService` | uji silang dengan SQL independen (`AnalyticsConsistencyTest`) |
| 10 Impor | 11 kasus sudah ditangani Stage 3 | uji end-to-end per kasus (`ImportVerificationTest`), commit sekali pakai |
| 11 UX | state loading/disabled/konfirmasi/sukses/error/kosong ada | pesan terkunci per halaman, keadaan final, hasil seri/tanpa suara |
| 12 Aksesibilitas | axe-core: kontras `--gray-500` 3,32:1, landmark ganda, halaman tanpa `h1` | diperbaiki; 0 pelanggaran (bagian 9.3) |
| 13 Performa | agregasi MySQL + index, paginasi, polling beririama | versi asset `?v=`, cache & kompresi Apache |

Catatan per bagian:

- **Tema kandidat (6)**: tidak ada gradient di CSS (kata "gradient" hanya ada
  di komentar aturan), tidak ada emoji di `app/` maupun `public/assets/`.
  Warna hanya aksen solid `#RRGGBB` per pasangan; teks di atas aksen memakai
  `accent_ink` (kontras WCAG).
- **Analitik (9)**: `AnalyticsConsistencyTest` membuat 240 siswa acak
  (deterministik; kelas lintas jenjang termasuk penulisan Romawi dan kelompok
  "Lainnya", L/P, sebagian nonaktif) dan 30 guru (sebagian nonaktif) dengan
  suara LOCKED, riwayat UNLOCKED, suara pemilih nonaktif, dan pasangan yang
  dinonaktifkan setelah menerima suara, lalu mencocokkan setiap angka
  snapshot (keseluruhan, siswa, guru, jenis kelamin, kelas, jenjang, pasangan,
  sudah/belum) dengan query SQL independen dan memeriksa: `siswa sudah + belum = siswa aktif`, `guru sudah + belum =
  guru aktif`, `suara siswa + suara guru = total suara`. Hasil akhir memakai
  angka yang sama (`testFinalResultUsesTheSameNumbers`).
- **Performa (13)**: `EXPLAIN` pada data development: agregat siswa membaca
  tabel `students` sekali (satu baris per siswa aktif) dan menggabungkan suara
  lewat index `student_id` + rowid filter `idx_student_votes_count`; cek suara
  aktif memakai index (ref). Untuk ratusan-ribuan pemilih sekolah tidak perlu
  index tambahan. Ukuran asset (gzip): CSS 3,3–8,5 KB per file, JS 2,2–8,0 KB
  per file, font 48 KB + 58 KB (sekali, cache 1 tahun).

## 2. Security findings

### 2.1 Temuan dan status

| # | Area | Temuan | Dampak | Tingkat | Status |
|---|---|---|---|---|---|
| 1 | Integritas suara | Suara dapat diubah/dihapus dan audit log dapat dihapus lewat query manual (HeidiSQL/phpMyAdmin) atau bug; hanya aplikasi yang menjaga | hasil dapat dimanipulasi tanpa jejak | Tinggi | Diperbaiki: CHECK + trigger (3.1) |
| 2 | Hasil akhir | Setelah FINISHED admin masih dapat menonaktifkan/menghapus pemilih, impor, menambah/menghapus pasangan, mengubah nomor urut/status aktif | angka hasil & partisipasi bergeser setelah diumumkan | Tinggi | Diperbaiki: kunci hasil (3.2) |
| 3 | Jadwal | Pemilihan selesai dapat dibuka kembali cukup dengan mengubah jadwal | hasil akhir ditarik tanpa disadari | Sedang | Diperbaiki: konfirmasi eksplisit + audit (3.3) |
| 4 | Replay POST impor | Dua POST commit dengan token pratinjau yang sama dapat berjalan bersamaan | impor & audit ganda (data tidak duplikat karena upsert) | Rendah | Diperbaiki: klaim atomik (3.4) |
| 5 | XSS (lapis kedua) | `CSPEnabled = false` | XSS yang lolos escape tidak punya penghalang kedua | Sedang | Diperbaiki: CSP ketat + nonce (3.5) |
| 6 | Kebocoran info | `X-Powered-By` (versi PHP) terkirim; tanpa Permissions-Policy/COOP/CORP | informasi versi, izin fitur browser | Rendah | Diperbaiki (3.5) |
| 7 | Session handling | Sesi pemilih 2 jam tanpa batas idle | komputer lab/HP pinjaman yang ditinggal dapat dipakai orang lain | Sedang | Diperbaiki: idle 15 menit (3.6) |
| 8 | Kebocoran info (server) | Bila DocumentRoot menunjuk folder proyek/induknya (mis. `http://localhost/smp1dawe-osis-2026/`), `.env` & isi proyek dapat diunduh | kredensial database bocor | Tinggi (bila salah konfigurasi) | Diperbaiki: `.htaccess` root (3.7), diuji di Apache |
| 9 | File upload | Folder unggahan memakai daftar larangan ekstensi skrip (bukan daftar izin) | file lain (svg/html) yang terlanjur ada di folder tersaji; nama ganda (`x.php.webp`) bergantung konfigurasi handler PHP | Sedang | Diperbaiki: daftar izin + engine off + RemoveHandler (3.7), diuji di Apache |
| 10 | Auth admin | Tidak ada cara resmi membuat admin production; akun `admin/admin123` seeder bisa terbawa | admin bersandi lemah di hari H | Tinggi | Diperbaiki: `admin:create`, `admin:password`, `osis:check` (3.8) |
| 11 | UX / kebocoran info | Halaman error bawaan CodeIgniter berbahasa Inggris dan bergaya lain; `production.php` generik (detail teknis memang sudah disembunyikan di production) | pemilih bingung saat alamat salah/terjadi error | Rendah | Diperbaiki: halaman error Indonesia, detail teknis hanya di luar production (3.9) |
| 12 | Deployment | Migration integritas yang gagal di tengah (MySQL 8, user non-SUPER, error 1419) tidak dapat diulang | database setengah termigrasi | Sedang | Diperbaiki: migration idempoten + pesan perbaikan (3.1) |
| 13 | Kebocoran info | `robots.txt` mengizinkan semua | halaman login/panel dapat diindeks | Rendah | Diperbaiki: `Disallow` area aplikasi |

### 2.2 Area yang diperiksa dan sudah aman sejak Stage 1–3

| Area | Mekanisme | Bukti test |
|---|---|---|
| CSRF | filter global, mode session, token di semua form & header AJAX; production: redirect kembali | `testPostWithoutCsrfTokenIsRejected`, `testVoteRequiresCsrfToken`, `testAdminPostRequiresCsrfToken`, Apache (403 dev / 303 production) |
| Session fixation | `regenerate(true)` + hapus semua key auth saat login | `testLoginAsAnotherRoleClearsPreviousIdentity`, `SecurityHardeningTest::testLoginRecordsActivityAndLogoutClearsIdentity` |
| Session handling | sesi hanya tipe+id+waktu aktivitas; akun dicek aktif setiap request; logout POST | `testSessionDoesNotStoreSecrets`, `testDeactivatedStudentLosesAccessImmediately`, `testLogoutRequiresPost` |
| Auth & authorization | filter per role, 401 JSON untuk AJAX | `RoleMatrixTest` (semua route diperiksa otomatis) |
| XSS | `esc()` di view, `textContent` di JS, CSP | `testCandidateTextIsEscaped`, `testVoterNamesAreEscapedInAdminPages`, `testHtmlPagesCarryStrictCspWithMatchingNonce` |
| SQL injection | Query Builder / binding; SQL mentah (join analitik, migration, pemeriksaan sistem) hanya berisi nilai int, literal tetap, atau parameter binding | review kode |
| Upload gambar | cek status unggah, ukuran, ekstensi, MIME isi, dimensi, encode ulang GD, nama acak | `CandidateAssetsTest`, `AdminCandidateTest` |
| Upload Excel | hanya `.xlsx`, 5 MB, 3.000 baris, cek zip (60 MB, 200 entri), sheet pertama | `VoterImportTest`, `ImportVerificationTest` |
| IDOR | pemilih: tanpa id di URL, identitas dari sesi; admin: id dicek 404, jenis pemilih dari route, `vote_id` harus sama dengan suara aktif | `testStudentSessionIdCannotBeUsedAsTeacherId`, `testUnlockOfStaleVoteIsRejected` |
| Suara ganda | `FOR UPDATE` baris pemilih + unique key `uq_*_votes_active` | `testParallelRequestsFromOneStudentCreateExactlyOneVote` (6 proses), `VoteIntegrityTest::testDatabaseStillAllowsOnlyOneActiveVote` |
| Replay POST suara | POST kedua = 409 `already_voted`; throttle 10/menit | `testSecondVoteIsRejectedAndFirstChoiceKept`, `testSubmitIsThrottledPerVoter` |
| Race condition | urutan kunci sama (`castVote` & unlock), deadlock/lock wait = `retry` | `testParallelVotesFromDifferentVotersAllSucceedWithoutDeadlock` (12 proses), unlock paralel vs pilih ulang |
| Akses API langsung | `election/clock` tanpa data pemilih; `admin/live-count` hanya admin (401 JSON); Stage 5: `live-count` publik hanya persentase per pasangan + partisipasi | `testLiveCountRejectsNonAdminAjaxWith401Json`, `RoleMatrixTest`, `HomepageTest::testLiveCountJsonIsANarrowProjectionOfTheAnalytics` |
| Mass assignment | input dipetakan eksplisit, `allowedFields`; field asing diabaikan | `testMassAssignmentFieldsAreIgnored`, `testInvalidCandidateInputIsRejected` |
| Brute force login | 5 gagal per akun (lalu 1/menit), 30 gagal per IP per menit, key di-hash | `testLoginIsThrottledPerAccountAfterRepeatedFailures` |
| localStorage | hanya preferensi efek `osis2026.fx`; confetti memakai sessionStorage penanda `osis2026.confetti.<election>-<end>` | review kode |
| Data sensitif | kode unik tersamar di panel, tidak di sesi; tidak di-flash; pratinjau impor di `writable/` | `testSessionDoesNotStoreSecrets` |

### 2.3 Batasan per role (04 bagian 2)

| Pastikan | Hasil | Test |
|---|---|---|
| Siswa tidak masuk route guru / admin | redirect ke login role itu; AJAX 401 | `RoleMatrixTest::testStudentCannotEnterTeacherOrAdminAreas` |
| Siswa tidak membaca analitik / suara orang lain | admin-only; my-vote hanya pilihan sendiri | `testOwnVoteOnlyAndNoResultsForVoters`, `testVotersCannotUseAdminActions` |
| Siswa tidak memilih dua kali | 409 + unique key | `testVoteCapabilitiesPerRole` |
| Guru tidak masuk route admin / analitik / memilih dua kali | sama | `testTeacherCannotEnterStudentOrAdminAreas`, `testVoteCapabilitiesPerRole` |
| Admin tidak memilih atas nama pemilih | tidak ada route admin yang menulis suara; sesi admin tidak dapat membuka route voting | `testNoAdminRouteWritesVotes`, `testAdminCannotCastVoteForVoters` |

`RoleMatrixTest::testEveryRouteIsPublicByDesignOrProtectedByItsRoleFilter`
membaca **semua** route terdaftar: setiap route harus ada di daftar publik
yang disengaja atau dilindungi filter role sesuai prefiks URI, sehingga route
baru tanpa filter langsung membuat test gagal.

### 2.4 Role permission matrix (04 bagian 18)

| Capability | Student | Teacher | Admin | Bukti |
|---|---:|---:|---:|---|
| Login Student | Yes | No | No | `testLoginIsSeparatedPerRole` |
| Login Teacher | No | Yes | No | `testLoginIsSeparatedPerRole` |
| Admin Login | No | No | Yes | `testLoginIsSeparatedPerRole`, `testAdminCannotLoginThroughStudentLogin` |
| View Candidates | Yes | Yes | Yes (panel + pratinjau) | `testViewCandidatesForAllRoles` |
| Vote | Yes | Yes | No | `testVoteCapabilitiesPerRole`, `testNoAdminRouteWritesVotes` |
| Re-vote while locked | No | No | No | `testVoteCapabilitiesPerRole` |
| View Own Vote | Yes | Yes | Not applicable | `testOwnVoteOnlyAndNoResultsForVoters` |
| View Analytics | No | No | Yes | `testVotersCannotUseAdminActions` |
| Import Students | No | No | Yes (dikunci setelah selesai) | `testVotersCannotUseAdminActions`, `ResultsLockTest` |
| Import Teachers | No | No | Yes (dikunci setelah selesai) | idem |
| Manage Candidates | No | No | Yes (susunan dikunci setelah selesai) | idem |
| Election Control | No | No | Yes | idem |
| Unlock Vote | No | No | Yes (hanya saat berlangsung) | idem, `testUnlockIsRefusedAfterElectionFinished` |
| Audit Log | No | No | Yes (hanya-baca) | idem |
| Final Result | No | No | Yes (hanya saat FINISHED) | `FinalResultTest::testResultsPageIsAdminOnly` |

## 3. Fixes

### 3.1 Penjaga integritas database

Migration `2026-04-01-000001_AddVoteIntegrityGuards` (detail bagian 8):
CHECK `chk_{student,teacher}_votes_state` dan 8 trigger `BEFORE UPDATE/DELETE`
(`SIGNAL SQLSTATE '45000'`, kode 1644). Satu-satunya perubahan sah pada tabel
suara: `LOCKED -> UNLOCKED` (+ `unlocked_at`, `updated_at`), persis yang
dilakukan `UnlockService`. `up()` aman diulang (CHECK hanya ditambah bila
belum ada, trigger `DROP IF EXISTS` + `CREATE`) dan menerjemahkan error MySQL
1419 menjadi langkah perbaikan. Baris lama yang tidak konsisten membuat
migration berhenti dengan daftar id (tidak diperbaiki diam-diam).

### 3.2 Hasil akhir dan kunci hasil

- `FinalResult::build()` (service murni): hanya tersedia bila status
  FINISHED; keadaan `unavailable` / `no_votes` / `tie` / `winner`; peringkat
  kompetisi (1, 2, 2, 4), urut suara lalu nomor urut; `margin` = selisih
  pemenang dengan peringkat 2; `celebrate` hanya untuk `winner`. Angka dari
  `AnalyticsService::snapshot()`.
- `GET admin/results` (`ResultController`): sebelum selesai hanya keadaan
  terkunci (status, waktu selesai, countdown compact) tanpa angka.
- Dasbor: banner keadaan final (tanpa confetti) + tautan hasil akhir;
  `admin-live.js` memuat ulang dasbor sekali saat live count melaporkan
  perubahan ONGOING/UPCOMING -> FINISHED.
- `AdminController::resultsLocked()` (status FINISHED): menolak ubah status &
  hapus pemilih, unggah & commit impor, tambah & hapus pasangan; ubah pasangan
  mempertahankan nomor urut & status aktif. View menampilkan pemberitahuan
  "dikunci" dan menyembunyikan aksi. Pesan: `RESULTS_LOCKED_MESSAGE`.

### 3.3 Membuka kembali pemilihan

`ElectionController::update()`: bila status sebelum = FINISHED dan jadwal baru
membuat status bukan FINISHED, wajib `confirm_reopen = 1` (checkbox di form,
dialog konfirmasi tipe bahaya); tanpa itu jadwal tidak disimpan. Perubahan
tercatat di audit (`SCHEDULE_UPDATE`, status lama -> baru). Mengedit nama/tahun
tanpa membuka kembali tidak butuh konfirmasi.

### 3.4 Impor sekali pakai

`ImportStore::claim()` mengambil pratinjau dengan `rename()` atomik ke
`*.json.claimed`; hanya satu request yang berhasil. `release()` mengembalikan
pratinjau bila impor ditolak (belum dicentang, tidak ada baris, konflik,
error), `delete()` setelah sukses; `purge()` ikut membersihkan klaim basi.

### 3.5 Content-Security-Policy dan header

- `App::$CSPEnabled = true`; `Config\ContentSecurityPolicy`:
  `default-src 'self'`, `script-src 'self' 'nonce-…'`, `script-src-attr 'none'`,
  `style-src 'self' 'unsafe-inline'`, `style-src-attr 'unsafe-inline'`
  (variabel tema `style="--accent: …"`), `img-src 'self' data: blob:`,
  `connect-src/font-src/form-action/manifest-src/base-uri 'self'`,
  `frame-ancestors 'self'`, `frame-src 'none'`, `object-src 'none'`.
  Skrip inline hanya penanda `js` di `layouts/main.php` dan
  `layouts/admin.php`, keduanya memakai `csp_script_nonce()`; tidak ada
  handler `on…=` inline di view aplikasi (pengecualian: halaman debug bawaan
  `error_exception.php`, hanya development, dan tidak menerima header CSP).
- Filter `appheaders` (`SecurityHeadersFilter`, global after):
  `Permissions-Policy` (kamera, mikrofon, lokasi, pembayaran, USB mati;
  layar penuh hanya situs ini), COOP & CORP `same-origin`, `Cache-Control:
  no-store` bila belum diatur, `X-Powered-By` dihapus. Header bawaan
  `secureheaders` tetap (X-Frame-Options SAMEORIGIN, nosniff, Referrer-Policy).
- Tombol Back setelah keluar menampilkan halaman login, bukan pilihan
  pemilih sebelumnya (diuji di browser).

### 3.6 Batas idle sesi pemilih

`AuthFilter` menyimpan `auth_seen_at` (jam server) di sesi setiap request
yang lolos; siswa & guru (`VOTER_IDLE_SECONDS = 900`) yang tidak aktif lebih
dari 15 menit dikeluarkan dengan pesan "Sesi berakhir karena tidak ada
aktivitas" (redirect atau 401 JSON). Admin tetap memakai batas sesi 2 jam.

### 3.7 Web server

- `.htaccess` di root proyek (baru): tolak dotfile, teruskan semua request ke
  `public/`, tolak semua bila mod_rewrite tidak ada.
- `public/uploads/.htaccess`: tolak semua kecuali nama gambar aman
  `^[a-z0-9][a-z0-9._-]*\.(jpe?g|png|webp)$`, `php_flag engine off`,
  `RemoveHandler/RemoveType` ekstensi PHP, header `nosniff`, CSP `sandbox`,
  CORP.
- `public/.htaccess`: kompresi (mod_deflate), cache asset (CSS/JS 30 hari,
  font 1 tahun, gambar 7 hari), `X-Powered-By` dihapus. Helper `asset_url()`
  menambah `?v=<filemtime>` sehingga cache panjang aman setelah update.
- `public/robots.txt`: `Disallow` `/admin`, `/student`, `/teacher`,
  `/election`, `/uploads`.

### 3.8 Alat deployment

- `php spark admin:create --username <u> --name "<nama>"`: username 3–50
  karakter (`a-z 0-9 . _ -`, unik), kata sandi acak 16 karakter tanpa karakter
  mirip (0/O/1/l/I), hanya hash yang disimpan, sandi ditampilkan sekali.
- `php spark admin:password <u>`: sandi acak baru.
- `php spark osis:check` (`SystemCheck`): OK / PERINGATAN / GAGAL untuk PHP &
  ekstensi, WebP, exif, `CI_ENVIRONMENT`, `app.baseURL`, zona waktu, CSP,
  cookie secure (HTTPS), `php.ini`, folder tulis, koneksi & versi database,
  migration, 8 trigger, akun admin, `admin/admin123` (GAGAL di production),
  jadwal, 3 pasangan, pemilih aktif, data contoh NISN `00000000xx` (GAGAL di
  production). Exit code 1 bila ada GAGAL.

### 3.9 Halaman error

`errors/html/error_404.php`, `error_400.php`, `production.php`: bahasa
Indonesia, gaya mandiri (`<style>` dengan nonce bila tersedia), tautan ke
beranda; pesan teknis hanya di luar production.

### 3.10 Aksesibilitas, UX, performa

- Kontras: `--gray-500` `#8D897D` (3,32:1) -> `#6E6A5F` (5,1:1, lulus AA teks
  kecil).
- Judul login `h2` -> `h1`; pratinjau kandidat punya `h1` tersembunyi;
  landmark Visi/Misi per pasangan dan tabel riwayat suara diberi label unik.
- Beranda saat FINISHED: "Pencoblosan sudah ditutup. Hasil resmi diumumkan
  oleh panitia pemilihan OSIS." (hasil tidak dibuka ke publik). Stage 5:
  kalimat ini tetap tampil, kini bersama "Perolehan akhir" (persentase)
  bila live count publik aktif; pemenang & confetti tetap hanya di admin.
- Paku WebGL: `destroy()` menghapus buffer & program dan memanggil
  `WEBGL_lose_context`; `ballot.js` memanggilnya saat `pagehide`.
- Panel: tombol **Layar penuh** (fallback webkit, `aria-pressed`) dan
  **Cetak** di halaman hasil akhir; gaya cetak & layar penuh khusus.

### 3.11 Confetti (04 bagian 5)

`public/assets/js/confetti.js` (ES5, tanpa library, ~7,8 KB):

- hanya dimuat oleh `admin/results/index.php` bila `celebrate` (tepat satu
  pemenang, pemilihan FINISHED, ada suara sah); canvas `aria-hidden`,
  `position: fixed`, `pointer-events: none` (klik tetap sampai ke halaman);
- satu kali per sesi browser per pemilihan (sessionStorage
  `osis2026.confetti.<id>-<waktu selesai>`); bila storage diblokir, tetap sekali
  per muat halaman;
- dilewati bila `prefers-reduced-motion: reduce` atau canvas 2D tidak
  tersedia; halaman yang dibuka di tab latar menunggu sampai tab terlihat;
  berhenti & dihapus bila tab disembunyikan saat berjalan;
- tiga semburan (45 %, 25 %, 30 % partikel) dalam ~5,2 detik + fade 0,9 detik,
  jumlah partikel mengikuti luas layar (60–200, lebih sedikit di perangkat
  lemah); warna dari aksen pasangan terpilih + netral (tervalidasi `#RRGGBB`);
  bentuk potongan kertas, tanpa emoji;
- tidak pernah dimuat di dasbor, analitik, maupun halaman lain.

## 4. New files

```
.htaccess                                             pengaman bila DocumentRoot salah (root proyek)
app/Commands/AdminCreate.php                          php spark admin:create
app/Commands/AdminPassword.php                        php spark admin:password
app/Commands/SystemCheckCommand.php                   php spark osis:check
app/Controllers/Admin/ResultController.php            GET admin/results
app/Database/Migrations/2026-04-01-000001_AddVoteIntegrityGuards.php   CHECK + 8 trigger
app/Filters/SecurityHeadersFilter.php                 alias appheaders
app/Libraries/AdminAccount.php                        buat admin / ganti sandi (acak, hash)
app/Libraries/SystemCheck.php                         daftar periksa kesiapan
app/Services/FinalResult.php                          keadaan & peringkat hasil akhir
app/Views/admin/partials/final_banner.php             keadaan final di dasbor
app/Views/admin/results/index.php                     halaman hasil akhir
public/assets/css/results.css                         gaya hasil akhir, layar penuh, cetak
public/assets/js/confetti.js                          confetti canvas
tests/database/AnalyticsConsistencyTest.php           2 test
tests/database/DeploymentToolsTest.php                6 test
tests/database/VoteIntegrityTest.php                  8 test
tests/feature/FinalResultTest.php                     14 test
tests/feature/ImportVerificationTest.php              6 test
tests/feature/ResultsLockTest.php                     6 test
tests/feature/RoleMatrixTest.php                      9 test
tests/feature/SecurityHardeningTest.php               9 test
tests/unit/FinalResultRankingTest.php                 6 test
STAGE4-NOTES.md
```

## 5. Modified files

| File | Perubahan |
|---|---|
| `app/Config/App.php` | `CSPEnabled = true` |
| `app/Config/ContentSecurityPolicy.php` | kebijakan ketat (3.5) |
| `app/Config/Filters.php` | alias `appheaders` + global after |
| `app/Config/Routes.php` | `GET admin/results` |
| `app/Controllers/BaseController.php` | `AUTH_SEEN_KEY` di sesi login & daftar key auth |
| `app/Filters/AuthFilter.php`, `StudentAuthFilter.php`, `TeacherAuthFilter.php` | batas idle 15 menit pemilih |
| `app/Controllers/Admin/AdminController.php` | `resultsLocked()`, `RESULTS_LOCKED_MESSAGE`, `resultsLocked` ke semua view |
| `app/Controllers/Admin/DashboardController.php` | `final` = `FinalResult::build()` |
| `app/Controllers/Admin/ElectionController.php` | konfirmasi membuka kembali pemilihan FINISHED |
| `app/Controllers/Admin/ImportController.php` | kunci setelah selesai; commit dengan `claim()`/`release()`/`delete()` |
| `app/Controllers/Admin/VoterController.php` | kunci status & hapus setelah selesai |
| `app/Controllers/Admin/CandidateController.php` | kunci tambah/hapus; nomor urut & status aktif dipertahankan setelah selesai |
| `app/Services/Import/ImportStore.php` | `claim()`, `release()`, `delete()`/`purge()` ikut file klaim |
| `app/Helpers/app_helper.php` | `asset_url()`; ikon `award`, `expand`, `printer` |
| `app/Views/layouts/main.php`, `layouts/admin.php` | nonce skrip inline, `asset_url()` |
| `app/Views/admin/dashboard.php` | banner final |
| `app/Views/admin/partials/sidebar.php` | menu Hasil akhir |
| `app/Views/admin/election/index.php` | konfirmasi & checkbox buka kembali, tautan hasil akhir |
| `app/Views/admin/import/index.php`, `candidates/{index,form}.php`, `voters/show.php` | pemberitahuan & aksi terkunci |
| `app/Views/admin/candidates/preview.php` | `h1` tersembunyi |
| `app/Views/{student,teacher,admin}/login.php` | judul `h1` |
| `app/Views/home/index.php` | catatan saat FINISHED |
| `app/Views/voting/partials/chapter.php` | label Visi/Misi unik per pasangan |
| `app/Views/voting/*`, `student/dashboard.php`, `teacher/dashboard.php`, `admin/analytics/index.php` | `asset_url()` |
| `app/Views/errors/html/{error_400,error_404,production}.php` | halaman error Indonesia (3.9) |
| `public/.htaccess`, `public/uploads/.htaccess`, `public/robots.txt` | 3.7 |
| `public/assets/css/app.css` | kontras `--gray-500`, `.card__title`, `.clock-band__note` |
| `public/assets/css/admin.css` | banner keadaan final di dasbor (responsif) |
| `public/assets/js/admin.js` | tombol Cetak & Layar penuh |
| `public/assets/js/admin-live.js` | muat ulang sekali saat menjadi FINISHED |
| `public/assets/js/nail-webgl.js`, `ballot.js` | pembersihan WebGL saat `pagehide` |
| `tests/feature/AdminPanelTest.php` | data uji mengikuti CHECK (`unlocked_at`), FK check dipulihkan |
| `tests/feature/VotingTest.php` | URL WebGL memakai `asset_url()` |
| `README.md`, `.env.example` | dokumentasi final (bagian 11) |
| `00-MASTER-PROJECT.md`, `04-FINAL-INTEGRATION-TESTING-DEPLOYMENT.md` | catatan keputusan Stage 4 / rujukan dokumen ini |

## 6. Full file content

Isi lengkap semua file baru dan yang diubah ada di repository pada commit
Stage 4 (branch `claude/tahap-4-implementasi-h60741`); daftar di bagian 4–5.
Tidak ada placeholder: setiap file lengkap dan dijalankan oleh test (bagian 9).

## 7. Routes

Route baru Stage 4: `GET admin/results` -> `Admin\ResultController::index`
(filter `adminauth`). Tidak ada route yang dihapus atau diganti nama.

Filter global semua route aplikasi: before `postsize`, `csrf`, `invalidchars`;
after `secureheaders`, `appheaders` (+ CSP). Semua POST wajib token CSRF.

### 7.1 Route matrix (04 bagian 17)

**Public** (tanpa login):

| Method | URI | Handler |
|---|---|---|
| GET | `/` | `Home::index` |
| GET | `election/clock` | `ElectionController::clock` (JSON jam server, no-store) |
| GET | `live-count` | `Home::liveCount` (Stage 5: JSON live count publik, no-store) |
| GET, POST | `student/login` | `Student\AuthController::loginForm / attemptLogin` |
| GET, POST | `teacher/login` | `Teacher\AuthController::loginForm / attemptLogin` |
| GET, POST | `admin/login` | `Admin\AuthController::loginForm / attemptLogin` |
| semua | `student`, `teacher`, `admin` | redirect ke dasbor role (filter role di tujuan) |

**Student** (`studentauth`, idle 15 menit):

| Method | URI | Handler |
|---|---|---|
| GET | `student/dashboard` | `Student\DashboardController::index` |
| GET | `student/vote` | `Student\VoteController::index` |
| GET | `student/vote/confirm/(:num)` | `::confirm/$1` |
| POST | `student/vote` | `::submit` (JSON atau form) |
| GET | `student/my-vote` | `::myVote` |
| POST | `student/logout` | `Student\AuthController::logout` |

**Teacher** (`teacherauth`, idle 15 menit): sama dengan siswa di prefiks
`teacher/` (`Teacher\DashboardController`, `Teacher\VoteController`,
`Teacher\AuthController::logout`).

**Admin** (`adminauth`):

| Method | URI | Handler |
|---|---|---|
| GET | `admin/dashboard` | `Admin\DashboardController::index` |
| GET | `admin/analytics`, `admin/analytics/votes` | `Admin\AnalyticsController::index / votes` |
| GET | `admin/results` | `Admin\ResultController::index` (Stage 4) |
| GET | `admin/candidates`, `admin/candidates/new`, `admin/candidates/(:num)/edit`, `admin/candidates/(:num)/preview` | `Admin\CandidateController` |
| POST | `admin/candidates`, `admin/candidates/(:num)`, `admin/candidates/(:num)/delete` | `::create / update / delete` |
| GET | `admin/students`, `admin/students/(:num)`, `admin/teachers`, `admin/teachers/(:num)` | `Admin\{Student,Teacher}Controller::index / show` |
| POST | `admin/{students,teachers}/(:num)/status`, `.../delete` | `::status / delete` |
| GET | `admin/{students,teachers}/import`, `.../import/template`, `.../import/preview/(:segment)`, `.../import/result` | `Admin\{Student,Teacher}ImportController` |
| POST | `admin/{students,teachers}/import`, `.../import/commit` | `::upload / commit` |
| GET, POST | `admin/election` | `Admin\ElectionController::index / save` |
| POST | `admin/election/close`, `admin/election/open` | `::close / open` |
| GET | `admin/unlock`, `admin/unlock/{student,teacher}/(:num)` | `Admin\UnlockController::index / form` |
| POST | `admin/unlock/{student,teacher}/(:num)` | `::unlock` |
| GET | `admin/audit` | `Admin\AuditController::index` |
| POST | `admin/logout` | `Admin\AuthController::logout` |

**API / AJAX**:

| Method | URI | Akses | Respons |
|---|---|---|---|
| GET | `election/clock` | publik | `{status, now, start, end, label}` (epoch ms jam server), tanpa data pemilih |
| GET | `live-count` | publik (Stage 5) | `{status, label, candidates[{id, label, percent}], turnout{voted, total, percent}, updated_at, updated_label, poll}`, `no-store`; 404 bila `homepage.publicLiveCount = false` |
| POST | `student/vote`, `teacher/vote` | pemilih role itu | 200 `ok` / 409 `already_voted` / 403 `voting_closed`, `no_election`, `voter_inactive` / 422 `invalid_candidate` / 429 `throttled` / 503 `retry` / 401 `unauthenticated` |
| GET | `admin/live-count` | admin | snapshot JSON (STAGE3-NOTES bagian 7), `poll.interval` 10/60/0, `no-store`; non-admin AJAX = 401 JSON |

Tanpa sesi yang sesuai: halaman = redirect ke login role; AJAX/JSON = 401
`{status: "unauthenticated", message, redirect}`.

## 8. Migrations

Urutan lengkap (namespace `App`):

```
2026-01-01-000001 CreateAdminsTable            2026-01-01-000006 CreateStudentVotesTable
2026-01-01-000002 CreateStudentsTable          2026-01-01-000007 CreateTeacherVotesTable
2026-01-01-000003 CreateTeachersTable          2026-01-01-000008 CreateVoteUnlockLogsTable
2026-01-01-000004 CreateCandidatesTable        2026-01-01-000009 CreateAuditLogsTable
2026-01-01-000005 CreateElectionsTable         2026-02-01-000001 AddThemeLayoutToCandidates
2026-03-01-000001 AddContextToAuditLogs        2026-04-01-000001 AddVoteIntegrityGuards   (Stage 4)
```

`2026-04-01-000001_AddVoteIntegrityGuards`:

| Objek | Tabel | Aturan |
|---|---|---|
| CHECK `chk_student_votes_state`, `chk_teacher_votes_state` | `*_votes` | `(status='LOCKED' AND unlocked_at IS NULL) OR (status='UNLOCKED' AND unlocked_at IS NOT NULL)` |
| `trg_{student,teacher}_votes_guard_update` | `*_votes` | tolak bila baris lama UNLOCKED; tolak perubahan `id`, `election_id`, pemilih, `candidate_id`, `voted_at`, `device_info`, `browser_info`, `created_at` |
| `trg_{student,teacher}_votes_guard_delete` | `*_votes` | tolak DELETE |
| `trg_vote_unlock_logs_guard_{update,delete}` | `vote_unlock_logs` | append-only |
| `trg_audit_logs_guard_{update,delete}` | `audit_logs` | append-only |

- Sebelum memasang: memeriksa baris yang melanggar aturan state; bila ada,
  berhenti dengan daftar id.
- Pelanggaran: trigger = error 1644 (SQLSTATE 45000, pesan Indonesia), CHECK =
  3819 (MySQL) / 4025 (MariaDB).
- `down()`: hapus 8 trigger (`IF EXISTS`) lalu CHECK (bila ada).
- Hak akses: `TRIGGER`; MySQL 8 + binary log + user non-SUPER butuh
  `log_bin_trust_function_creators = 1` (diuji: tanpa itu error 1419 dengan
  pesan perbaikan; setelah diset, `php spark migrate` diulang dan selesai).

Jalankan `php spark migrate`. Tidak ada kolom/tabel baru.

## 9. Test results

### 9.1 Test otomatis

`composer test` (`vendor/bin/phpunit --no-coverage`), dijalankan di container
Linux pada kode final:

| PHP | Database | Hasil |
|---|---|---|
| 8.4.19 | MariaDB 10.11.14 | **289 test, 2.122 assertion, OK** |
| 8.4.19 | MySQL 8.0.46 | **289 test, 2.122 assertion, OK** |
| 8.2.33 | MariaDB 10.11.14 | **289 test, 2.122 assertion, OK** |
| 8.2.33 | MySQL 8.0.46 | **289 test, 2.122 assertion, OK** |

Stage 1–3: 223 test; Stage 4: 66 test. Test paralel (`pcntl_fork`) ikut
berjalan di keempat kombinasi (di Windows otomatis di-skip).

Stage 4 per bagian 04:

| Bagian 04 | Test |
|---|---|
| 2 keamanan & role | `RoleMatrixTest` (9), `SecurityHardeningTest` (CSP + nonce, header & no-store di semua area, aktivitas sesi, idle pemilih, admin tanpa idle pendek, replay commit impor, klaim atomik, aturan `.htaccess`, halaman error production) |
| 3 integritas | `VoteIntegrityTest` (state machine lewat service, DELETE ditolak, isi suara tidak dapat diubah, UNLOCKED final + CHECK, satu suara aktif, log append-only, migration dapat diulang setelah gagal di tengah, kerja admin normal tidak terhalang) |
| 4 hasil akhir | `FinalResultTest` (admin-only; terkunci saat berlangsung sampai 1 detik sebelum selesai, sebelum mulai, tanpa jadwal; tepat `end_at`; seri; tanpa suara; hanya LOCKED pemilih aktif; pasangan nonaktif bersuara tetap ada; dasbor final tanpa confetti; POST suara setelah selesai ditolak & hasil tetap; angka = live count & polling berhenti; menu), `FinalResultRankingTest`, `ResultsLockTest` (6) |
| 5 confetti | `FinalResultTest::testFinalResultAtEndTimeShowsWinnerRankingTotalsAndConfetti`, `testTieHasNoWinnerAndNoConfetti`, `testNoValidVotesHasNoWinnerAndNoConfetti`, `testDashboardShowsFinalStateWithoutConfetti` |
| 6 tema | `FinalResultTest::testWinnerStageUsesTheCandidateSpecificTheme` (+ test tema Stage 2–3) |
| 9 analitik | `AnalyticsConsistencyTest` (2) |
| 10 impor | `ImportVerificationTest`: Excel kosong & header salah; NISN ganda & NIP ganda; jenis kelamin salah, nama kosong, kelas kosong, kode salah; leading zero sampai login; file besar (baris & byte); impor berulang tanpa duplikat |
| 14 deployment | `DeploymentToolsTest` (sandi acak, hash, validasi, reset, perintah spark, `osis:check` + trigger hilang = GAGAL) |

### 9.2 Checklist end-to-end (04 bagian 19)

| Item | Bukti otomatis | Bukti manual (bagian 9.3) |
|---|---|---|
| clean installation | setiap kelas test database migrate dari nol | `migrate:refresh` + seed di PHP 8.2; database MySQL 8 kosong dengan user non-root |
| migrations | `SchemaIntegrityTest`, `Stage2SchemaTest`, `Stage3SchemaTest`, `VoteIntegrityTest` | 12 migration, `down()` semua lewat `migrate:refresh` |
| seed | `SchemaIntegrityTest::testSeederCreatesDevelopmentData` | seeder menolak di production |
| student login | `AuthTest`, `RoleMatrixTest` | Apache |
| teacher login | `AuthTest`, `RoleMatrixTest` | Apache |
| admin login | `AuthTest`, `DeploymentToolsTest` | admin dari `admin:create` |
| upcoming | `testVotingBeforeStartIsRejected`, `testUpcomingBallotShowsCandidatesWithoutVotingControls`, `testResultsStayLockedBeforeStartAndWithoutElection` | — |
| ongoing | `VotingTest`, `testResultsStayLockedWhileOngoingEvenOneSecondBeforeEnd` | sweep ONGOING |
| finished | `testVotingAtEndTimeIsRejectedButOneSecondBeforeIsAccepted`, `testFinishedElectionHidesVotingAndShowsNotVoted`, `testVotingEndpointRejectsAfterEndAndResultIsUnchanged` | tutup sekarang -> 403 `voting_closed`; sweep FINISHED |
| student vote | `testJsonVoteIsStoredLockedWithServerSideMetadata` | 3D, 2D HP, keyboard, tanpa JS |
| teacher vote | `testTeacherLoginLeadsToTeacherDashboardWithVoteCta`, `VoteServiceTest` | tombol Coblos guru |
| duplicate prevention | `testSecondVoteIsRejectedAndFirstChoiceKept`, test paralel, `testDatabaseStillAllowsOnlyOneActiveVote` | — |
| re-login | `testReloginShowsLockedOwnChoiceWithoutVotingCta` | Back setelah keluar = halaman login |
| own vote only | `testOwnChoicePageShowsNoOtherCandidatesOrOtherVoters`, `testOwnVoteOnlyAndNoResultsForVoters` | — |
| unlock | `UnlockServiceTest`, `AdminPanelTest` unlock | unlock dengan alasan + dialog |
| re-vote | `testUnlockedHistoryAllowsVotingAgain`, `testStateMachineThroughServicesKeepsAuditableHistory` | pilih ulang: riwayat `UNLOCKED` + `LOCKED` baru |
| analytics / gender / class / grade | `AnalyticsServiceTest`, `AnalyticsConsistencyTest` | — |
| teacher analytics / combined | `testVoterTypeBreakdown`, `AnalyticsConsistencyTest` | — |
| live count | `testLiveCountReturnsConsistentJson`, `testLiveCountPollingFollowsElectionStatus`, `testFinalNumbersEqualLiveCountAndPollingStops` | dasbor 6 suara |
| final result | `FinalResultTest`, `FinalResultRankingTest` | pemenang, peringkat, selisih |
| confetti | 4 test di 9.1 | tersembunyi -> tampil -> dihapus; tidak ulang saat reload; tidak di dasbor/analitik; tidak saat reduced motion |
| mobile | — | 30 halaman di 360x760; voting 2D sentuh |
| desktop | — | 30 halaman di 1280x860 |
| reduced motion | — | voting keyboard mode 2D; confetti dilewati |
| WebGL fallback | `testBallotLoadsInteractiveScriptsWithLazyWebglAndFallbacks` | Chromium `--disable-webgl`: label "Tidak didukung", voting 2D berhasil |

### 9.3 Uji manual (dijalankan, bukan bagian `composer test`)

Chromium (Playwright) dengan CSP aktif, WebGL via SwiftShader:

- **`php spark serve` (PHP 8.4)** selama pengembangan: seluruh alur voting,
  hasil akhir & confetti, 30 halaman x 2 lebar x ONGOING/FINISHED, axe-core
  (WCAG 2.1 A/AA + best-practice). Masalah yang ditemukan dan diperbaiki:
  kontras `--gray-500`, landmark Visi/Misi & tabel riwayat tanpa label unik,
  pratinjau tanpa `h1`, panggung pemenang terdorong ke bawah lipatan di HP
  (masthead diringkas).
- **Apache 2.4.58 + mod_php 8.2.33 + MariaDB** (mirip Laragon), kode final:
  - voting: 3D WebGL drag -> 02; 2D HP 360 px drag -> 01 (tanpa overflow);
    reduced motion + keyboard -> 03; tanpa JavaScript -> halaman konfirmasi ->
    form POST; guru dengan tombol Coblos; `--disable-webgl` -> "Tidak
    didukung" -> 2D; semua "SUARA BERHASIL DISIMPAN";
  - admin (akun dari `admin:create`): dasbor 6 suara; unggah foto ketua 1200x1500
    JPEG -> WebP 800x1000 nama acak, tersaji lewat aturan uploads, file lama
    terhapus saat diganti; impor `.xlsx` 2 siswa -> "Ditambahkan 2"; unlock
    dengan alasan -> siswa memilih ulang sendiri (riwayat `UNLOCKED` + `LOCKED`
    baru, log unlock atas nama admin); tutup sekarang -> POST suara 403
    `voting_closed`; hasil akhir: pemenang 02 (3 suara, 50,0 %, unggul 1),
    confetti tersembunyi -> tampil -> dihapus, tidak ulang setelah reload;
    banner final dasbor; tanpa confetti di dasbor/analitik; impor terkunci;
    audit mencatat semua tindakan;
  - sweep 30 halaman x 360/1280 pada FINISHED dan ONGOING: 0 overflow, 0 error
    konsol/pelanggaran CSP, 0 pelanggaran axe-core;
  - 50 pemeriksaan HTTP: URL bersih, header (CSP ber-nonce, X-Frame-Options,
    nosniff, Permissions-Policy, COOP, no-store, tanpa X-Powered-By), cookie
    sesi HttpOnly + SameSite=Lax, `?v=` asset, gzip, cache CSS 30 hari & font
    1 tahun, POST tanpa CSRF 403; uploads: WebP 200 (nosniff, CSP sandbox),
    `.php`/`.svg`/tanpa ekstensi/`.htaccess`/`.gitkeep`/daftar folder 403;
    DocumentRoot salah (folder induk proyek): `.env` 403 tanpa isi,
    `.git/config`/`env`/`composer.json`/`spark`/`vendor` tidak terbuka,
    `app/` & `writable/` 403, aplikasi tetap jalan lewat `public/`;
  - tanpa mod_rewrite: semua isi proyek 403;
  - `AddHandler application/x-httpd-php .php` sengaja diaktifkan: `a.php.webp`
    di uploads tersaji sebagai teks (tidak dieksekusi); kontrol tanpa
    `uploads/.htaccess`: dieksekusi (aturan ini yang melindungi);
  - instalasi di subfolder (`app.baseURL` = `http://<host>/<folder>/`): asset,
    form, login, redirect benar;
  - production (`CI_ENVIRONMENT = production`): tanpa Debug Toolbar, CSP tanpa
    nonce gaya, 404 bahasa Indonesia tanpa detail teknis, gagal CSRF = 303
    kembali ke form; `osis:check` GAGAL (exit 1) karena `admin/admin123` &
    data contoh; `db:seed DatabaseSeeder` ditolak.
- **Backup & pemulihan** (README bagian 17): `mysqldump --single-transaction
  --routines --triggers` -> pulihkan ke database baru: 8 trigger, 2 CHECK,
  jumlah baris suara & audit sama; DELETE suara pada database hasil pulihan
  tetap ditolak (1644).
- **MySQL 8.0.46, user non-SUPER, binary log aktif**: `php spark migrate`
  berhenti di migration Stage 4 dengan pesan error 1419 + perbaikan; setelah
  `log_bin_trust_function_creators = 1`, `php spark migrate` diulang dan
  selesai (8 trigger, 2 CHECK), `osis:check` OK.

### 9.4 Belum diverifikasi

- Windows + Laragon secara langsung (menu Laragon, virtual host otomatis
  `.test`, Document Root, Windows Firewall); uji Apache dilakukan di Linux
  dengan konfigurasi serupa.
- HP Android kelas bawah dengan GPU fisik dan Safari iOS (uji memakai
  Chromium + SwiftShader, viewport HP diemulasikan).
- Pembaca layar sungguhan (NVDA/TalkBack); aksesibilitas diperiksa dengan
  axe-core dan alur keyboard.
- Beban ratusan pemilih serentak di Wi-Fi sekolah (hanya uji paralel 12
  proses).
- File Excel sekolah asli dan server GD tanpa WebP.
- MySQL 8.0.16–8.0.18 (CHECK didukung; `DROP CONSTRAINT` di `down()` baru ada
  sejak 8.0.19).

## 10. Deployment

Panduan lengkap Laragon (`C:\laragon\www\smp1dawe-osis-2026`): **README.md
bagian 7**. Ringkasan:

1. Laragon dengan PHP 8.2+, ekstensi intl/mbstring/mysqli/gd/zip/fileinfo/exif,
   `php.ini` upload 5M / post 40M / memory 256M / `expose_php Off`, jam Windows
   benar.
2. `composer install --no-dev --optimize-autoloader`, `.env` dari
   `.env.example` (`CI_ENVIRONMENT = production`, `app.baseURL` = alamat yang
   dibuka semua perangkat).
3. Database `utf8mb4_unicode_ci`, `php spark migrate`.
4. Admin: `php spark admin:create ...` (seeder hanya development).
5. `writable/` dan `public/uploads/candidates/` dapat ditulis.
6. DocumentRoot = `public/` (virtual host Laragon atau Document Root), atau
   subfolder lewat `.htaccess` root; akses HP lewat IP server.
7. `php spark osis:check` tanpa GAGAL; uji coba di database terpisah; backup
   (`mysqldump --single-transaction --routines --triggers`).

## 11. README

`README.md` ditulis ulang: ringkasan, arsitektur (diagram lapisan, prinsip,
state machine, struktur folder), kebutuhan, instalasi, `.env`, database &
penjaga integritas, deployment Laragon (server, aplikasi, virtual host & akses
HP, lapisan pengaman web server, persiapan hari H), admin, siswa, guru, kelola
pasangan calon, impor Excel, voting, unlock, analitik & live count, hasil
akhir & confetti, backup & pemulihan, troubleshooting (16 gejala), test, daftar
dokumen. `.env.example` diberi panduan `app.baseURL` (laptop/IP/serve),
`app.CSPEnabled`, `cookie.secure`.

## 12. Final project tree

Tanpa `vendor/` (Composer), `system/` (framework CodeIgniter 4.7.4), isi
`writable/`, dan file bawaan CodeIgniter yang tidak diubah di `app/Config/`.

```
smp1dawe-osis-2026/
├── .env.example  .gitignore  .htaccess  composer.json  composer.lock  env  phpunit.dist.xml  preload.php  spark  LICENSE
├── 00-MASTER-PROJECT.md  01-FOUNDATION-DATABASE-AUTH.md  02-STUDENT-TEACHER-VOTING.md
├── 03-ADMIN-IMPORT-ANALYTICS.md  04-FINAL-INTEGRATION-TESTING-DEPLOYMENT.md
├── 05-HOMEPAGE-REDESIGN.md (Stage 5)
├── README.md  STAGE1-NOTES.md  STAGE2-NOTES.md  STAGE3-NOTES.md  STAGE4-NOTES.md  STAGE5-NOTES.md
├── app/
│   ├── Commands/        AdminCreate, AdminPassword, SystemCheckCommand
│   ├── Config/          App, Autoload, ContentSecurityPolicy, Database, Filters, Homepage (Stage 5),
│   │                    Pager, Routes, Security, Services, UserAgents (+ bawaan CodeIgniter)
│   ├── Controllers/
│   │   ├── BaseController, Home, ElectionController, VotingController
│   │   ├── Admin/       AdminController, AuthController, DashboardController, LiveCountController,
│   │   │                AnalyticsController, ResultController, CandidateController, VoterController,
│   │   │                StudentController, TeacherController, ImportController,
│   │   │                StudentImportController, TeacherImportController, ElectionController,
│   │   │                UnlockController, AuditController
│   │   ├── Student/     AuthController, DashboardController, VoteController
│   │   └── Teacher/     AuthController, DashboardController, VoteController
│   ├── Database/
│   │   ├── Migrations/  2026-01-01-000001..000009, 2026-02-01-000001, 2026-03-01-000001,
│   │   │                2026-04-01-000001_AddVoteIntegrityGuards
│   │   └── Seeds/       DatabaseSeeder, AdminSeeder, ElectionSeeder, CandidateSeeder,
│   │                    StudentSeeder, TeacherSeeder (development saja)
│   ├── Filters/         AuthFilter, AdminAuthFilter, StudentAuthFilter, TeacherAuthFilter,
│   │                    PostSizeFilter, SecurityHeadersFilter
│   ├── Helpers/         app_helper.php
│   ├── Language/id/     Security.php
│   ├── Libraries/       AdminAccount, CandidateAssets, CandidateAssetException, CandidateTheme,
│   │                    DeviceInfo, Grade, SystemCheck
│   ├── Models/          AdminModel, StudentModel, TeacherModel, CandidateModel, ElectionModel,
│   │                    StudentVoteModel, TeacherVoteModel, VoteUnlockLogModel, AuditLogModel
│   ├── Services/        VoteService, VoteResult, VoterType, UnlockService, UnlockResult,
│   │   │                AnalyticsService, FinalResult, VoterDirectory, PublicLiveCount (Stage 5)
│   │   └── Import/      VoterImporter, StudentImporter, TeacherImporter, ImportCell,
│   │                    ImportStore, ImportConflictException
│   └── Views/
│       ├── layouts/     main.php, admin.php
│       ├── partials/    header, footer, flash, election_status, countdown, vote_status
│       ├── home/        index.php + partials/ (splash, dock, pair_photo) (Stage 5)
│       ├── student/     login.php, dashboard.php
│       ├── teacher/     login.php, dashboard.php
│       ├── voting/      index, confirm, my_vote + partials/ (chapter, portraits, ballot,
│       │                confirm_dialog, nail_svg)
│       ├── admin/       login, dashboard, analytics/ (index, votes), results/index,
│       │                candidates/ (index, form, preview), voters/ (index, show),
│       │                import/ (index, preview, result), election/index,
│       │                unlock/ (index, form), audit/index,
│       │                partials/ (sidebar, topbar, live_status, scoreboard,
│       │                candidate_results, recap_table, pager, final_banner)
│       └── errors/html/ error_404, error_400, production (+ bawaan debug)
├── public/
│   ├── index.php  .htaccess  robots.txt  favicon.ico
│   ├── assets/
│   │   ├── css/         app.css, voting.css, admin.css, results.css, home.css (Stage 5)
│   │   ├── js/          app, countdown, candidates, ballot, nail-webgl, admin, admin-live, confetti,
│   │   │                home (Stage 5)
│   │   ├── fonts/       Inter, Newsreader, JetBrains Mono (Stage 5) (woff2 + OFL)
│   │   └── img/         patterns/ (grid, hatch, dots, ticks), brand/ (logo), home/ (layar pembuka,
│   │                    ilustrasi pintu masuk) (Stage 5)
│   └── uploads/         .htaccess, candidates/ (gambar unggahan, tidak di-commit)
├── tests/
│   ├── _support/        bootstrap.php, UploadFixture.php, SpreadsheetFactory.php
│   ├── unit/            HealthTest, ElectionStatusTest, DeviceInfoTest, CandidateThemeTest,
│   │                    CandidateAssetsTest, GradeTest, FinalResultRankingTest,
│   │                    PublicLiveCountTest (Stage 5)
│   ├── database/        SchemaIntegrityTest, Stage2SchemaTest, Stage3SchemaTest, VoteServiceTest,
│   │                    UnlockServiceTest, AnalyticsServiceTest, VoterImportTest,
│   │                    VoteIntegrityTest, AnalyticsConsistencyTest, DeploymentToolsTest
│   └── feature/         AuthTest, VotingTest, AdminPanelTest, AdminCandidateTest, AdminImportTest,
│                        FinalResultTest, RoleMatrixTest, SecurityHardeningTest, ResultsLockTest,
│                        ImportVerificationTest, HomepageTest (Stage 5)
└── writable/            cache/, logs/, session/, uploads/imports/ (pratinjau impor), debugbar/
```

## 13. Known limitations

1. **Hasil akhir hanya untuk admin.** Beranda publik saat selesai hanya
   menyatakan pencoblosan ditutup; pengumuman dilakukan panitia (layar penuh /
   cetak dari panel). Bila hasil perlu tampil publik, tambahkan halaman baru
   yang memakai `FinalResult` yang sama. Stage 5: beranda kini juga
   menampilkan persentase per pasangan & partisipasi (live count publik,
   dapat dimatikan), tanpa pemenang maupun confetti; lihat
   `STAGE5-NOTES.md` bagian 11.
2. **Confetti hanya untuk satu pemenang.** Hasil seri atau tanpa suara sah
   tidak dirayakan; aplikasi tidak memutuskan seri (aturan panitia).
3. **Hasil dikunci setelah FINISHED.** Koreksi data pemilih/pasangan setelah
   selesai hanya lewat membuka kembali pemilihan (konfirmasi + audit), yang
   juga membuka pencoblosan.
4. **Suara & log tidak dapat dihapus.** "Mengosongkan" data uji coba berarti
   database baru/backup lama; lakukan simulasi di database terpisah. Trigger
   butuh hak `TRIGGER` (dan di MySQL 8 + binary log, user non-SUPER perlu
   `log_bin_trust_function_creators = 1`).
5. **Satu pemilihan.** Aplikasi dirancang untuk Pemilihan OSIS 2026
   (`getCurrentElection()` = baris terbaru; form jadwal mengubah baris yang ada).
   Pemilihan tahun berikutnya sebaiknya memakai database baru; database 2026
   disimpan sebagai arsip.
6. **Idle 15 menit untuk pemilih** dihitung dari request terakhir yang melewati
   filter; membaca halaman kandidat lebih dari 15 menit tanpa request membuat
   pemilih harus login lagi (suara belum tersimpan tidak hilang karena belum
   dikirim).
7. **CSP**: `style-src-attr 'unsafe-inline'` tetap diperlukan untuk variabel
   tema per pasangan di atribut `style`. Di development, Debug Toolbar
   menambah nonce gaya ke `style-src` (sehingga `'unsafe-inline'` diabaikan
   untuk elemen `<style>` tanpa nonce); di production tidak. CodeIgniter juga
   mengirim header `Content-Security-Policy-Report-Only` kosong (diabaikan
   browser).
8. **Halaman error (404/500)** dirender langsung oleh exception handler
   CodeIgniter sehingga tidak melewati filter after: tanpa CSP dan header
   keamanan tambahan. Halaman ini tidak berisi data maupun tindakan.
   Klien non-browser (tanpa `Accept: text/html`) menerima body kosong di
   production (perilaku CodeIgniter).
9. **`osis:check` membaca `php.ini` CLI.** Di Laragon sama dengan Apache; di
   Linux periksa juga `php.ini` Apache (form kandidat menampilkan batas
   unggah efektif web server).
10. **Akses HP** bergantung jaringan sekolah (IP server tetap, firewall,
    alamat sama dengan `app.baseURL`); belum diuji di Windows/Laragon asli.
11. **Throttle login per IP** (30 gagal/menit) dibagi semua perangkat di
    belakang satu NAT sekolah; batas per akun (5 gagal) tetap utama.
12. Hal yang belum diverifikasi: bagian 9.4.

## 14. Catatan pemeliharaan

- Perubahan schema berikutnya = migration baru; jangan mengubah migration yang
  sudah berjalan. Bila perlu mengubah tabel suara/log, perhatikan trigger
  (UPDATE yang tidak termasuk `LOCKED -> UNLOCKED` akan ditolak).
- Route baru wajib masuk grup filter role atau daftar publik
  `RoleMatrixTest::PUBLIC_ROUTES`; bila tidak, test gagal.
- Skrip inline baru wajib `csp_script_nonce()`; lebih baik file JS terpisah.
- Asset baru dipanggil dengan `asset_url()` agar cache Apache aman.
- Angka baru di panel wajib berasal dari `AnalyticsService` (satu definisi).
