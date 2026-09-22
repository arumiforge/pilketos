# Stage 1 — Foundation, Database & Auth (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 1. Stage 2 wajib membacanya bersama
`00-MASTER-PROJECT.md` dan `01-FOUNDATION-DATABASE-AUTH.md`.

## 1. Stage objective

Fondasi aplikasi: schema MySQL, model, seeder development, login admin/siswa/guru
yang terpisah, proteksi route per role, session minimal, CSRF, throttling login,
dan design foundation hitam-putih (tanpa gradient, tanpa emoji).

## 2. Perbaikan hasil audit Stage 1

| # | Masalah pada versi awal | Dampak | Perbaikan |
|---|---|---|---|
| 1 | CHECK constraint `vote_unlock_logs` dipasang pada kolom FK `ON DELETE SET NULL` | `php spark migrate` **gagal** di MySQL 8 (error 3823) dan MariaDB | Semua FK `RESTRICT`, CHECK aman |
| 2 | Argumen `addForeignKey()` tertukar (onUpdate/onDelete) | Menghapus kandidat/siswa/admin ikut **menghapus suara dan audit log** (CASCADE) | `ON UPDATE RESTRICT ON DELETE RESTRICT` di semua FK |
| 3 | Unlock menghapus baris vote | Riwayat & device/browser vote lama hilang, kolom `status` tidak bermakna | Vote `LOCKED`/`UNLOCKED`, baris tidak dihapus, unique key via generated column `active_lock` |
| 4 | `strictOn = false` | CodeIgniter mematikan strict mode: data invalid (mis. jenis kelamin `X`) dipotong diam-diam | `strictOn = true` |
| 5 | `appTimezone = UTC` tetapi UI menulis "WIB" | Jadwal tampil & dihitung selisih 7 jam | `Asia/Jakarta` |
| 6 | Status election `FINISHED` hanya bila `now > end_at` | Voting masih terbuka tepat pada `end_at` | `now >= end_at` = FINISHED, status tersinkron ke DB |
| 7 | Throttle login per IP (8/menit) dan menghitung login berhasil | Satu sekolah (1 IP Wi-Fi) saling mengunci; kode unik (tanggal lahir) bisa ditebak per akun | Throttle per akun (5 gagal) + per IP longgar (30 gagal/menit), hanya kegagalan |
| 8 | Key throttle memuat IP mentah | Login dari `::1` (IPv6 localhost Laragon) **crash** (karakter `:` dilarang di key cache) | Key di-hash |
| 9 | `withInput()` di form login | Kode unik/password tersalin ke file session | Hanya identifier yang di-flash |
| 10 | Sesi menyimpan nama/kelas; filter hanya cek `user_type` | Data basi; akun dinonaktifkan tetap punya akses | Sesi hanya tipe+id; filter cek akun aktif di DB; profil dibaca dari DB |
| 11 | Login role lain tidak membersihkan sesi | Sisa `admin_id` tertinggal saat login sebagai siswa | Semua key auth dibersihkan + `regenerate(true)` |
| 12 | `indexPage = 'index.php'` | Redirect ke `/index.php/...` sementara link ke URL bersih | `indexPage = ''` |
| 13 | CSRF `regenerate = true` mode cookie | Tab ganda & AJAX voting gagal CSRF | Mode `session`, token per sesi, `csrf_meta()` di layout |
| 14 | Google Fonts via `@import` | Render terblokir bila internet sekolah lambat | Font di-host lokal (OFL) |
| 15 | Siswa tanpa `status_aktif` | Stage 4 mensyaratkan "total active students" | Kolom `students.status_aktif` |
| 16 | Tidak ada test otomatis Stage 1 | Checklist tidak terbukti | 47 test PHPUnit (lihat bagian 10) |
| 17 | Petunjuk instalasi menyuruh `create-project appstarter` + timpa folder | Tidak sesuai repo (framework `system/` sudah ada) | Petunjuk di bagian 3 |

Karena migration lama tidak bisa dijalankan sampai selesai, file migration Stage 1
diperbaiki langsung (belum ada stage lain yang bergantung). Database lama hasil
percobaan sebelumnya harus dikosongkan dulu (lihat bagian 3).

## 3. Dependencies & instalasi (Laragon)

Syarat: PHP **8.2+** (CodeIgniter 4.7) dengan ekstensi intl, mbstring, mysqli;
Composer; MySQL 8.0+ atau MariaDB 10.4+; Apache dengan mod_rewrite.

1. Clone repository ke `C:\laragon\www\smp1dawe-osis-2026`.
2. `composer install`
3. Salin `.env.example` menjadi `.env`, sesuaikan `app.baseURL` dan kredensial database.
4. Buat database kosong `smp1dawe_osis_2026` (dan `smp1dawe_osis_2026_test` untuk test),
   collation `utf8mb4_unicode_ci`.
   Bila sebelumnya pernah menjalankan migration versi lama: drop lalu buat ulang database.
5. Jalankan:
   ```
   php spark migrate
   php spark db:seed DatabaseSeeder
   ```
6. Laragon: arahkan document root virtual host ke folder `public/`
   (`http://smp1dawe-osis-2026.test/`), atau jalankan `php spark serve`
   lalu buka `http://localhost:8080/` (set `app.baseURL` sesuai).

## 4. Database schema

| Tabel | Kolom penting | Constraint |
|---|---|---|
| `admins` | name, username, password_hash | username unique |
| `students` | nisn, name, jenis_kelamin (L/P), kelas, nomor_absen, kodeunik, status_aktif | nisn unique |
| `teachers` | nip, name, kodeunik, status_aktif | nip unique |
| `candidates` | nomor_urut, nama_ketua, nama_wakil, foto_ketua, foto_wakil, visi, misi, theme_name, theme_background, theme_accent, theme_asset (JSON), status_aktif | nomor_urut unique |
| `elections` | nama, tahun, start_at, end_at, status | CHECK end_at > start_at |
| `student_votes` | election_id, student_id, candidate_id, status (LOCKED/UNLOCKED), active_lock (generated), voted_at, unlocked_at, device_info, browser_info | unique (election_id, student_id, active_lock) |
| `teacher_votes` | election_id, teacher_id, candidate_id, status, active_lock, voted_at, unlocked_at, device_info, browser_info | unique (election_id, teacher_id, active_lock) |
| `vote_unlock_logs` | election_id, student_id, teacher_id, student_vote_id, teacher_vote_id, admin_id, reason, unlocked_at | CHECK tepat satu jenis pemilih |
| `audit_logs` | admin_id, action, description, created_at | — |

Semua FK: `ON UPDATE RESTRICT ON DELETE RESTRICT`. NISN, NIP, dan kode unik adalah
`VARCHAR` (leading zero aman).

State hak suara (per pemilih per election):

```
NO_ACTIVE_VOTE --vote--> LOCKED --admin unlock--> NO_ACTIVE_VOTE (baris lama = UNLOCKED)
                                                    --vote lagi--> LOCKED (baris baru)
```

## 5. File tree (file aplikasi Stage 1)

```
.env.example
composer.json / composer.lock
app/Config/App.php            (timezone, indexPage, locale)
app/Config/Autoload.php       (helper url, form, app)
app/Config/Database.php       (strictOn, grup tests MySQL)
app/Config/Filters.php        (alias adminauth/studentauth/teacherauth, CSRF global)
app/Config/Routes.php
app/Config/Security.php       (CSRF session)
app/Controllers/BaseController.php
app/Controllers/Home.php
app/Controllers/Admin/{AuthController,DashboardController}.php
app/Controllers/Student/{AuthController,DashboardController}.php
app/Controllers/Teacher/{AuthController,DashboardController}.php
app/Database/Migrations/2026-01-01-000001..000009_*.php
app/Database/Seeds/{Database,Admin,Election,Candidate,Student,Teacher}Seeder.php
app/Filters/{AuthFilter,AdminAuthFilter,StudentAuthFilter,TeacherAuthFilter}.php
app/Helpers/app_helper.php
app/Language/id/Security.php
app/Models/{Admin,Student,Teacher,Candidate,Election,StudentVote,TeacherVote,VoteUnlockLog,AuditLog}Model.php
app/Views/layouts/main.php
app/Views/partials/{header,footer,flash,election_status}.php
app/Views/home/index.php
app/Views/{admin,student,teacher}/{login,dashboard}.php
public/assets/css/app.css
public/assets/js/app.js
public/assets/fonts/*.woff2 (+ lisensi OFL)
public/uploads/.htaccess
public/uploads/candidates/
tests/unit/{HealthTest,ElectionStatusTest}.php
tests/feature/AuthTest.php
tests/database/SchemaIntegrityTest.php
```

## 6. Routes

| Method | URI | Handler | Filter |
|---|---|---|---|
| GET | `/` | `Home::index` | — |
| GET/POST | `/student/login` | `Student\AuthController::loginForm/attemptLogin` | — |
| GET/POST | `/teacher/login` | `Teacher\AuthController::loginForm/attemptLogin` | — |
| GET/POST | `/admin/login` | `Admin\AuthController::loginForm/attemptLogin` | — |
| GET | `/student`, `/teacher`, `/admin` | redirect ke dasbor | — |
| GET | `/student/dashboard` | `Student\DashboardController::index` | studentauth |
| POST | `/student/logout` | `Student\AuthController::logout` | studentauth |
| GET | `/teacher/dashboard` | `Teacher\DashboardController::index` | teacherauth |
| POST | `/teacher/logout` | `Teacher\AuthController::logout` | teacherauth |
| GET | `/admin/dashboard` | `Admin\DashboardController::index` | adminauth |
| POST | `/admin/logout` | `Admin\AuthController::logout` | adminauth |

CSRF global untuk semua POST. Route voting (Stage 2) masuk ke grup `studentauth`/
`teacherauth`; route admin (Stage 3) masuk ke grup `adminauth`.

## 7. Configuration

- `app.baseURL` dari `.env`; `indexPage = ''`; `appTimezone = Asia/Jakarta`; locale `id`.
- Database: `charset utf8mb4`, `DBCollat utf8mb4_unicode_ci`, `strictOn = true`. Kredensial hanya di `.env`.
- Session: FileHandler, cookie `osis_session`, 2 jam. Isi: `user_type`, `<role>_id`, `isLoggedIn`.
- CSRF: mode session, field `csrf_token`, header `X-CSRF-TOKEN`, token per sesi.
- Throttle login: per akun 5 gagal lalu 1/menit; per IP 30 gagal/menit.
- Waktu selalu dari PHP (`CodeIgniter\I18n\Time::now()`), jangan `NOW()` MySQL.

## 8. Seed data (development saja)

Seeder menolak berjalan bila `CI_ENVIRONMENT = production`.

- Admin: `admin` / `admin123`
- Election 2026: mulai kemarin, selesai 7 hari lagi (status ONGOING)
- 3 pasangan calon (tanpa foto/asset; diunggah admin di Stage 3)
- Siswa: NISN `0000000001` s/d `0000000012`, kode unik lihat `StudentSeeder.php`
  (contoh: `0000000001` / `05062013`, `0000000003` / `01032013`); `0000000012` non-aktif.
- Guru: NIP `000000000000000001` s/d `...005`, kode unik lihat `TeacherSeeder.php`
  (contoh: `000000000000000004` / `01061992`); `...005` non-aktif.

## 9. Frontend foundation

- Token warna netral, `--accent` default ink (ditimpa per kandidat di Stage 2/3).
- Tipografi Newsreader (display) + Inter (UI), self-hosted.
- Komponen: container, stack, cluster, button (default/outline/danger/sm/block, disabled),
  field (input/select/textarea, hint, error), alert, card, badge status (teks + warna),
  modal `<dialog>`, kv-list, skip link, focus ring, `prefers-reduced-motion`.
- `app.js`: `App.jsonHeaders()` / `App.csrfHeader()` untuk AJAX, modal
  `data-modal-open`/`data-modal-close`, pencegah submit ganda, flash sukses auto-hide.
- Tidak ada data siswa/guru di localStorage.

## 10. Test checklist

Jalankan: `composer test` (atau `vendor\bin\phpunit --no-coverage`).
Butuh database `smp1dawe_osis_2026_test` dan `database.tests.*` di `.env`.

Hasil (dijalankan pada PHP 8.4.19): **47 test, 109 assertion, lulus** di
MariaDB 10.11.14 dan MySQL 8.0.46.

| Checklist | Test |
|---|---|
| Koneksi database, migration, seeder | `SchemaIntegrityTest::testSeederCreatesDevelopmentData` (seluruh test DB migrate+seed dari nol) |
| Admin login / password salah | `AuthTest::testAdminLoginWithValidCredential`, `testAdminLoginWithWrongPassword` |
| Student login, leading zero, format kode | `testStudentLoginWithValidCredential`, `testStudentLoginKeepsLeadingZeroAndAcceptsDateSeparators`, `testStudentLoginRejectsMalformedKodeunik` |
| Teacher login | `testTeacherLoginWithValidCredential` |
| Invalid credential / akun non-aktif | `testStudentLoginWithInvalidCredential`, `testInactiveStudentCannotLogin`, `testInactiveTeacherCannotLogin`, `testStudentCredentialCannotLoginAsTeacher`, `testAdminCannotLoginThroughStudentLogin` |
| Route protection | `testGuestIsRedirectedFromProtectedRoutes`, `testStudentCannotAccessTeacherOrAdminRoutes`, `testTeacherCannotAccessAdminRoutes`, `testStudentSessionIdCannotBeUsedAsTeacherId`, `testAjaxGuestReceivesJson401` |
| Session | `testSessionDoesNotStoreSecrets`, `testLoginAsAnotherRoleClearsPreviousIdentity`, `testDeactivatedStudentLosesAccessImmediately`, `testLogoutEndsSession`, `testLogoutRequiresPost` |
| CSRF | `testPostWithoutCsrfTokenIsRejected` |
| Throttle | `testLoginIsThrottledPerAccountAfterRepeatedFailures` |
| Integritas suara | `testStudentCanHaveOnlyOneActiveVote`, `testTeacherCanHaveOnlyOneActiveVote`, `testUnlockedVoteStaysAsHistoryAndAllowsNewVote`, `testUnlockLogMustReferenceExactlyOneVoterType`, `testVoterWithVoteCannotBeDeleted`, `testCandidateWithVoteCannotBeDeleted` |
| Jadwal & status | `ElectionStatusTest` (batas start/end), `testElectionStatusColumnIsSyncedWithSchedule`, `testElectionEndMustBeAfterStart` |
| Strict mode & data | `testStrictModeRejectsInvalidEnumInsteadOfTruncating`, `testIdentifiersKeepLeadingZero`, `testCandidateThemeAssetIsStoredAsJson` |

Belum diverifikasi: instalasi langsung di Windows/Laragon dan runtime PHP 8.2
(dependency sudah di-resolve untuk platform PHP 8.2). Tampilan diperiksa di
Chromium 360px dan 1280px: tanpa overflow horizontal, font lokal termuat.

## 11. Handoff ke Stage 2

File yang wajib dipertahankan dan dibaca Stage 2:

**Config**
- `app/Config/App.php`, `app/Config/Autoload.php`, `app/Config/Database.php`,
  `app/Config/Filters.php`, `app/Config/Security.php`, `.env.example`

**Migrations**
- `app/Database/Migrations/2026-01-01-000001_CreateAdminsTable.php` s/d
  `2026-01-01-000009_CreateAuditLogsTable.php` (perubahan schema berikutnya = migration baru)

**Models**
- `ElectionModel` (`getCurrentElection()`, `resolveStatus()`, konstanta status)
- `CandidateModel` (`getActiveCandidates()`, `assetUrl()`, cast `theme_asset`)
- `StudentModel` / `TeacherModel` (`findForLogin()`, `findActive()`)
- `StudentVoteModel` / `TeacherVoteModel` (`findActiveVote()`, `hasVoted()`, `STATUS_LOCKED`)
- `VoteUnlockLogModel`, `AuditLogModel` (`log()`), `AdminModel`

**Filters**
- `app/Filters/AuthFilter.php` + `AdminAuthFilter`, `StudentAuthFilter`, `TeacherAuthFilter`

**Controllers**
- `BaseController` (`startAuthSession()`, `endAuthSession()`, throttle, `failLogin()`)
- `Home`, `Student/*`, `Teacher/*`, `Admin/*`

**Views**
- `layouts/main.php` (section `head`, `content`, `scripts`)
- `partials/header.php`, `partials/footer.php`, `partials/flash.php`, `partials/election_status.php`
- `home/index.php`, `student/*`, `teacher/*`, `admin/*`

**Helpers**
- `app/Helpers/app_helper.php` (`format_waktu()`, `election_status_label()`)

**CSS / JS / assets**
- `public/assets/css/app.css`, `public/assets/js/app.js`, `public/assets/fonts/`
- `public/uploads/candidates/` (asset kandidat, dilindungi `public/uploads/.htaccess`)

**Routes**
- `app/Config/Routes.php`

**Seeders**
- `app/Database/Seeds/*`

Aturan wajib Stage 2:
1. Voting hanya bila `ElectionModel::getCurrentElection()['status'] === 'ONGOING'` (dicek ulang di server saat submit).
2. Identitas pemilih hanya dari session; `candidate_id` divalidasi aktif.
3. Insert vote `LOCKED` dalam transaction; duplicate key `uq_*_votes_active` = sudah memilih.
4. Tampilan pilihan sendiri memakai `findActiveVote()`; jangan query suara pemilih lain.
5. AJAX memakai `App.jsonHeaders()`; tangani `401` JSON (`redirect`).
6. Tidak ada gradient, tidak ada emoji, hormati `prefers-reduced-motion`.
