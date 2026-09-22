# Stage 2 — Voting Siswa & Guru (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 2. Stage 3 wajib membacanya bersama
`00-MASTER-PROJECT.md`, `02-STUDENT-TEACHER-VOTING.md`, dan `STAGE1-NOTES.md`.
Isi lengkap setiap file ada di repository (tidak disalin ulang di sini).

## 1. Stage objective

Pengalaman voting lengkap untuk siswa dan guru pada election yang sama dengan
identitas pemilih tetap terpisah:

- beranda editorial dengan countdown kinetik yang digerakkan jam server;
- dasbor pemilih (identitas, status hak suara, CTA hanya bila belum memilih);
- halaman kandidat dengan art direction berbeda per pasangan (layout, warna
  aksen solid, pola/texture, asset unggahan admin);
- visi-misi interaktif (tipografi menyala mengikuti scroll, panel misi
  buka-tutup, parallax, kedalaman/tilt);
- surat suara dengan paku coblos: tekan-tahan, geser, lepas, tusuk, dampak
  pada kotak pasangan; paku 3D WebGL dengan fallback 2D (SVG + CSS);
- modal konfirmasi, penyimpanan suara dalam transaction, status LOCKED;
- login ulang setelah memilih hanya menampilkan pilihan sendiri.

## 2. Dependencies

Tidak ada dependency baru (Composer maupun JS). Keputusan:

| Kebutuhan | Keputusan | Alasan |
|---|---|---|
| Paku 3D | WebGL langsung (`nail-webgl.js`, ~14 KB) | Hanya satu objek kecil; Three.js (~600 KB) terlalu berat untuk HP siswa & Wi-Fi sekolah, dan harus bisa jalan tanpa internet |
| Animasi | `requestAnimationFrame` + CSS | GSAP tidak memberi nilai tambah untuk tween sederhana |
| Confetti | tidak dipakai | Final result & confetti adalah Stage 4 |
| Ikon | SVG inline via helper `icon()` | Konsisten, tanpa emoji, tanpa library |
| Pola kandidat | SVG tile + CSS `mask-image` | Warna solid mengikuti aksen, tanpa gradient |

Syarat server tetap seperti Stage 1 (PHP 8.2+, MySQL 8.0+/MariaDB 10.4+).

## 3. Required previous files

Semua file Stage 1 dipertahankan. Yang dipakai langsung oleh Stage 2:
`ElectionModel` (`getCurrentElection()`, `resolveStatus()`), `CandidateModel`
(`getActiveCandidates()`, `assetUrl()`), `StudentModel`/`TeacherModel`
(`findActive()`), `StudentVoteModel`/`TeacherVoteModel` (`findActiveVote()`),
filter `studentauth`/`teacherauth` (401 JSON untuk AJAX), `BaseController`,
layout `layouts/main.php`, `partials/*`, `app.css`, `app.js`
(`App.jsonHeaders()`), migration 000001–000009.

## 4. New files

```
app/Services/VoterType.php              enum Student/Teacher -> tabel identitas, tabel suara, kolom FK, key sesi
app/Services/VoteService.php            castVote() (transaction + lock), ballotState()
app/Services/VoteResult.php             status hasil + kode HTTP + pesan UI
app/Libraries/CandidateTheme.php        baris candidates -> data tampilan bertema (aksen, kontras, layout, URL asset)
app/Libraries/DeviceInfo.php            device_info / browser_info dari User-Agent (server-side)
app/Controllers/VotingController.php    alur voting bersama (index, confirm, submit, myVote)
app/Controllers/Student/VoteController.php   voterType() = Student
app/Controllers/Teacher/VoteController.php   voterType() = Teacher
app/Controllers/ElectionController.php  GET election/clock (jam server untuk countdown)
app/Database/Migrations/2026-02-01-000001_AddThemeLayoutToCandidates.php
app/Views/partials/countdown.php        countdown hero / compact
app/Views/partials/vote_status.php      kartu status hak suara di dasbor
app/Views/voting/index.php              kandidat + surat suara
app/Views/voting/confirm.php            konfirmasi tanpa JavaScript
app/Views/voting/my_vote.php            pilihan saya
app/Views/voting/partials/chapter.php   bab pasangan (panggung visual + visi-misi)
app/Views/voting/partials/portraits.php foto / monogram ketua & wakil
app/Views/voting/partials/ballot.php    surat suara + dok paku
app/Views/voting/partials/confirm_dialog.php  modal konfirmasi (JS)
app/Views/voting/partials/nail_svg.php  paku 2D (fallback & ikon dok)
public/assets/css/voting.css
public/assets/js/countdown.js
public/assets/js/candidates.js
public/assets/js/ballot.js
public/assets/js/nail-webgl.js
public/assets/img/patterns/{grid,hatch,dots}.svg
tests/feature/VotingTest.php
tests/database/VoteServiceTest.php
tests/database/Stage2SchemaTest.php
tests/unit/DeviceInfoTest.php
tests/unit/CandidateThemeTest.php
STAGE2-NOTES.md
```

## 5. Modified files (Stage 1)

| File | Perubahan |
|---|---|
| `app/Config/Routes.php` | route voting siswa/guru + `election/clock` |
| `app/Config/Services.php` | `service('voting')` -> `VoteService` |
| `app/Config/UserAgents.php` | deteksi Samsung Internet, Mi Browser, UC, Yandex, Chrome/Firefox iOS |
| `app/Controllers/Home.php` | kirim teaser kandidat ke beranda |
| `app/Controllers/{Student,Teacher}/DashboardController.php` | status hak suara dari `ballotState()` |
| `app/Models/CandidateModel.php` | `theme_layout` di allowedFields + validasi |
| `app/Database/Seeds/CandidateSeeder.php` | layout contoh split/poster/column |
| `app/Helpers/app_helper.php` | `election_clock()`, `icon()` |
| `app/Views/layouts/main.php` | kelas `no-js`/`js`, `$bodyClass` |
| `app/Views/partials/header.php` | tombol Keluar (POST) untuk semua role yang login |
| `app/Views/home/index.php` | hero editorial, countdown, pintu masuk, teaser |
| `app/Views/{student,teacher}/dashboard.php` | identitas + kartu status suara + jadwal |
| `public/assets/css/app.css` | token tambahan, hero, countdown, dasbor, ikon, notice |
| `public/assets/js/app.js` | `App.postJson()`, `App.reducedMotion()`, `App.getPref()/setPref()`; modal `data-modal-static` |

## 6. Database changes

Migration baru `2026-02-01-000001_AddThemeLayoutToCandidates`:

- `candidates.theme_layout VARCHAR(20) NULL` (setelah `theme_asset`);
- `CHECK (theme_layout IS NULL OR theme_layout IN ('split','poster','column'))`;
- `NULL` = otomatis dari nomor urut (01 split, 02 poster, 03 column), jadi
  database lama tidak perlu diisi ulang.

Jalankan `php spark migrate`. Tidak ada perubahan pada tabel suara: Stage 2
memakai kontrak Stage 1 apa adanya (`status = 'LOCKED'`, `active_lock`
generated, unique `uq_*_votes_active`).

Kunci JSON `theme_asset` yang dibaca frontend: `hero`, `texture`, `artwork`,
`poster` (nama file di `public/uploads/candidates/`).

## 7. Routes

| Method | URI | Handler | Filter |
|---|---|---|---|
| GET | `election/clock` | `ElectionController::clock` (JSON, `no-store`) | — |
| GET | `student/vote` | `Student\VoteController::index` | studentauth |
| POST | `student/vote` | `Student\VoteController::submit` | studentauth + CSRF |
| GET | `student/vote/confirm/(:num)` | `Student\VoteController::confirm/$1` | studentauth |
| GET | `student/my-vote` | `Student\VoteController::myVote` | studentauth |
| GET | `teacher/vote` | `Teacher\VoteController::index` | teacherauth |
| POST | `teacher/vote` | `Teacher\VoteController::submit` | teacherauth + CSRF |
| GET | `teacher/vote/confirm/(:num)` | `Teacher\VoteController::confirm/$1` | teacherauth |
| GET | `teacher/my-vote` | `Teacher\VoteController::myVote` | teacherauth |

Route Stage 1 tidak berubah. Tidak ada id pemilih/suara di URL (anti IDOR).

`POST <role>/vote` menerima `candidate_id` (JSON dari `ballot.js` atau form
POST dari halaman konfirmasi). Respons JSON:

| HTTP | `status` | Arti |
|---|---|---|
| 200 | `ok` | tersimpan; `title` "SUARA BERHASIL DISIMPAN", `detail` "Hak suara Anda telah dikunci.", `vote`, `redirect` |
| 409 | `already_voted` | sudah punya suara LOCKED (termasuk duplicate key); `redirect` ke my-vote |
| 403 | `voting_closed` / `no_election` / `voter_inactive` | + `election_status` |
| 422 | `invalid_candidate` | bukan angka / tidak ada / nonaktif |
| 429 | `throttled` | > 10 submit per menit per pemilih (`Retry-After`) |
| 503 | `retry` | lock wait timeout / deadlock; suara belum tersimpan, aman dikirim ulang |
| 401 | `unauthenticated` | dari filter Stage 1, + `redirect` login |

Tanpa header JSON, hasil yang sama dikembalikan sebagai redirect + flash.

## 8. Full implementation (ringkasan alur)

### 8.1 Menyimpan suara (`VoteService::castVote`)

```
transBegin (transException aktif)
  SELECT election terbaru ... LOCK IN SHARE MODE      -> NO_ELECTION
  resolveStatus(election, waktu server) != ONGOING    -> VOTING_CLOSED
  SELECT pemilih aktif ... FOR UPDATE                 -> VOTER_INACTIVE
  SELECT kandidat aktif ... LOCK IN SHARE MODE        -> INVALID_CANDIDATE
  SELECT suara LOCKED pemilih (consistent read)       -> ALREADY_VOTED
  INSERT <role>_votes (status LOCKED, voted_at = Time::now(), device/browser dari server)
transCommit
DatabaseException: rollback + resetTransStatus;
  1062/1586 pada uq_<table>_active -> ALREADY_VOTED; 1205/1213 -> RETRY; lainnya dilempar.
```

- Kunci `FOR UPDATE` pada baris pemilih membuat request paralel milik pemilih
  yang sama (klik ganda, tab ganda, request manual) berjalan bergantian; unique
  key tetap penjaga terakhir.
- Tabel suara sengaja tidak dibaca dengan locking read (gap lock pada index
  unik bisa membuat dua pemilih berbeda saling deadlock).
- `syncStatus()` (UPDATE elections) tidak dipanggil di dalam transaction.
- Identitas pemilih hanya `session('<role>_id')`; `VoterType` ditetapkan
  controller, bukan input. Field lain di body (mis. `student_id`) diabaikan.

### 8.2 Frontend

- `countdown.js`: odometer bersegmen per digit + garis waktu. Menghitung dari
  `data-now` (jam server) + `performance.now()`, bukan jam perangkat. Tick 1x
  per detik, berhenti saat tab tersembunyi, sinkron ulang ke `election/clock`
  saat tab aktif lagi (> 30 detik) atau hitungan habis; reload hanya bila
  status server benar-benar berubah. Tidak pernah negatif.
- `candidates.js`: kata visi menyala mengikuti scroll, panel misi buka-tutup
  (`aria-expanded`), item misi muncul bertahap, parallax dibatasi ±56px hanya
  untuk bab yang terlihat (satu rAF), tilt hanya untuk mouse, penanda bab
  aktif di navigasi. Tanpa JS/IntersectionObserver semua teks tampil penuh.
- `ballot.js`: state machine `idle -> holding/flying -> stabbing -> confirming
  -> submitting -> done`. Paku mengikuti pointer (ujung 72px di atas jari pada
  layar sentuh), auto-scroll di tepi layar, deteksi kotak via
  `elementFromPoint`, animasi tusuk, lubang coblos + cincin dampak + getar
  ringan, lalu modal. Satu POST per konfirmasi (tombol dikunci + jeda 400 ms
  anti Enter tertahan). Loop render hanya berjalan saat paku bergerak.
- Mode paku: `3d` (WebGL, dimuat malas saat idle) atau `2d` (SVG + CSS).
  Otomatis `2d` bila WebGL tidak ada, `failIfMajorPerformanceCaveat`,
  perangkat lemah (`deviceMemory`/`hardwareConcurrency`/`saveData`),
  `prefers-reduced-motion`, context lost, atau frame lambat (>= 10 frame
  > 45 ms dari 45 frame pertama). Tombol "Efek 3D" menyimpan preferensi di
  localStorage (`osis2026.fx`, non-sensitif); `?fx=3d|2d` untuk uji.
- Fallback berlapis: tombol "Coblos Pasangan 0X" (keyboard/pembaca layar,
  tanpa gestur) -> tanpa `<dialog>`/JS: tautan ke halaman konfirmasi server
  (`vote/confirm/{id}`, form POST biasa). Efek visual tidak ikut menentukan
  keamanan.

### 8.3 Tema kandidat

`CandidateTheme::present()` menghasilkan `accent` (hanya `#RRGGBB`, selain itu
ink), `accent_ink` (putih/ink sesuai kontras WCAG), `accent_text` (aksen bila
kontras >= 3:1 terhadap kertas, selain itu ink), `layout`, `pattern`, URL
asset lewat `CandidateModel::assetUrl()`, misi per baris tanpa penomoran manual,
inisial monogram, dan string `style` (custom property CSS) yang aman.

## 9. Testing

Jalankan `composer test` (atau `vendor/bin/phpunit --no-coverage`).

Hasil (PHP 8.4.19): **107 test, 428 assertion, lulus** di **MariaDB 10.11.14**
dan **MySQL 8.0.46** (Stage 1: 47 test, Stage 2: 60 test).

| Checklist Stage 2 | Bukti |
|---|---|
| 1. student login | `VotingTest::testStudentLoginLeadsToDashboardWithIdentityAndVoteCta` |
| 2. teacher login | `testTeacherLoginLeadsToTeacherDashboardWithVoteCta` |
| 3. candidate rendering | `testBallotPageRendersAllActiveCandidatesWithVisiMisi`, `testCandidateTextIsEscaped` |
| 4. candidate-specific theme | `testEachCandidateHasItsOwnThemeAndLayout`, `testUploadedThemeAssetsAreUsedByTheCandidatePage`, `CandidateThemeTest`, `Stage2SchemaTest` |
| 5. visi-misi interaction | `testVisiMisiInteractionHooksAreRendered` + uji browser (di bawah) |
| 6. WebGL 3D voting animation | `testBallotLoadsInteractiveScriptsWithLazyWebglAndFallbacks` + uji browser |
| 7. fallback animation | uji browser mode 2D, reduced motion, dan tanpa JavaScript |
| 8. confirm | `testConfirmPageShowsPairPhotoNumberNamesAndLockWarning`, `testConfirmPageRejectsUnknownOrInactiveCandidate` |
| 9. transaction | `testJsonVoteIsStoredLockedWithServerSideMetadata`, `VoteServiceTest::testRejectionRollsBackAndKeepsConnectionUsable`, `testLockWaitTimeoutIsReportedAsRetry` |
| 10. duplicate prevention | `testSecondVoteIsRejectedAndFirstChoiceKept`, `testDuplicateFormPostRedirectsToOwnChoice`, `testSubmitIsThrottledPerVoter`, `VoteServiceTest::testUniqueKeyConflictIsReportedAsAlreadyVoted`, `testParallelRequestsFromOneStudentCreateExactlyOneVote` (6 proses paralel -> tepat 1 suara), `testParallelVotesFromDifferentVotersAllSucceedWithoutDeadlock` (12 proses) |
| 11. lock | `testAfterVotingBallotAndConfirmRedirectToOwnChoice` |
| 12. re-login | `testReloginShowsLockedOwnChoiceWithoutVotingCta` |
| 13. own-choice-only view | `testOwnChoicePageShowsNoOtherCandidatesOrOtherVoters`, `testOwnChoicePageWithoutVoteRedirectsToDashboard` |
| 14. schedule enforcement | `testVotingBeforeStartIsRejected`, `testVotingAtEndTimeIsRejectedButOneSecondBeforeIsAccepted`, `testUpcomingBallotShowsCandidatesWithoutVotingControls`, `testFinishedElectionHidesVotingAndShowsNotVoted`, `testElectionClockEndpointReturnsServerTime` |
| Keamanan tambahan | `testStudentCannotUseTeacherVotingAndViceVersa`, `testGuestAndDeactivatedVoterCannotVote`, `testInvalidCandidateInputIsRejected`, `testVoteRequiresCsrfToken`, `testUnlockedHistoryAllowsVotingAgain`, `DeviceInfoTest` |

Test paralel memakai `pcntl_fork` dan otomatis **skipped** di Windows/Laragon
(ekstensi pcntl tidak ada); jalankan di Linux/WSL untuk bukti race condition.
Diulang 6x berturut-turut di MariaDB tanpa gagal.

Uji browser (Chromium via Playwright, dijalankan manual, tidak termasuk
`composer test`):

- 360x760 (sentuh) dan 1280x860: tanpa overflow horizontal, tanpa error konsol;
- drag paku 3D WebGL -> kotak 02 -> modal -> konfirmasi -> sukses -> my-vote;
- drag sentuh paku 2D di HP -> konfirmasi -> my-vote;
- tombol Coblos (animasi terbang) -> batal: lubang hilang, fokus kembali ke tombol;
- tiga klik cepat pada KONFIRMASI PILIHAN -> tepat 1 POST;
- Escape pada modal sukses -> my-vote; buka lagi `vote` -> diarahkan ke my-vote;
- `prefers-reduced-motion`: mode 2D, tanpa animasi kata, alur keyboard penuh;
- JavaScript dimatikan: Coblos -> halaman konfirmasi -> form POST -> my-vote;
- beranda status UPCOMING / ONGOING / FINISHED.

Bug yang ditemukan dan diperbaiki lewat uji browser: presisi uniform shader
berbeda (program WebGL gagal link) dan ukuran buffer kanvas default 300x150.

Belum diverifikasi: GPU fisik HP Android kelas bawah (uji memakai SwiftShader),
Safari iOS, dan runtime PHP 8.2 / Laragon Windows secara langsung.

## 10. Edge cases

| Kasus | Perilaku |
|---|---|
| Klik ganda / Enter tertahan | tombol dikunci + jeda 400 ms; server tetap satu suara |
| Tab ganda / request manual paralel | `FOR UPDATE` baris pemilih + unique key -> satu LOCKED, sisanya 409 |
| Koneksi putus setelah server menyimpan | kirim ulang -> 409 -> diarahkan ke my-vote |
| Lock wait / deadlock | 503 `retry`, tidak ada suara tersimpan, tombol aktif lagi |
| Tepat pada `end_at` | FINISHED (ditolak), 1 detik sebelumnya diterima |
| Jam perangkat diubah | tidak berpengaruh (countdown pakai jam server + `performance.now()`) |
| HP tidur / tab lama tidak aktif | countdown sinkron ulang ke `election/clock` |
| Kembali (bfcache) setelah memilih | halaman voting dimuat ulang -> server mengarahkan ke my-vote |
| Kandidat dinonaktifkan saat halaman terbuka | 422, modal menyarankan pilih ulang |
| Kandidat yang dipilih kemudian dinonaktifkan | my-vote tetap menampilkan pilihan (kandidat tidak bisa dihapus: FK RESTRICT) |
| Pemilih dinonaktifkan saat halaman terbuka | 401 dari filter -> ke halaman login |
| Sesi habis saat konfirmasi | 401 JSON -> `App.postJson()` mengarahkan ke login |
| Suara di-unlock admin (baris UNLOCKED) | dasbor kembali "Belum memilih", suara baru = baris LOCKED baru |
| Foto/asset belum diunggah | monogram inisial + pola SVG sesuai layout |
| Warna aksen tidak valid / terang | jatuh ke ink / teks gelap otomatis (kontras) |
| WebGL gagal / lambat / context lost | pindah ke paku 2D tanpa memutus alur |
| Tanpa JavaScript | halaman konfirmasi server + form POST |
| localStorage diblokir | preferensi efek diabaikan (try/catch) |
| Komputer lab dipakai bergantian | tombol "Keluar" di header & "Selesai & keluar" di my-vote |

## 11. Handoff ke Stage 3

File yang wajib dipakai Stage 3:

**Voting service & controller**
- `app/Services/VoteService.php` (`castVote()`, `ballotState()`), `VoteResult.php`, `VoterType.php`
- `app/Controllers/VotingController.php`, `Student/VoteController.php`, `Teacher/VoteController.php`
- `app/Controllers/ElectionController.php`

**Model / database**
- `CandidateModel` (+ `theme_layout`), `ElectionModel`, `StudentVoteModel`, `TeacherVoteModel`
- migration `2026-02-01-000001_AddThemeLayoutToCandidates.php`

**Filter**: `StudentAuthFilter`, `TeacherAuthFilter`, `AdminAuthFilter` (tidak berubah)

**Tema & asset**
- `app/Libraries/CandidateTheme.php` (satu-satunya pintu data tema ke view)
- `public/uploads/candidates/` + `CandidateModel::assetUrl()`
- `public/assets/img/patterns/*.svg`

**View**: `app/Views/voting/*`, `partials/countdown.php`, `partials/vote_status.php`,
`home/index.php`, `{student,teacher}/dashboard.php`

**Frontend**: `public/assets/js/{app,countdown,candidates,ballot,nail-webgl}.js`,
`public/assets/css/{app,voting}.css`

**Routes**: `app/Config/Routes.php`

Aturan wajib Stage 3:
1. Form kandidat mengisi: `theme_accent` (`#RRGGBB`), `theme_layout`
   (`split|poster|column` atau kosong), `theme_background`, `foto_ketua`,
   `foto_wakil`, dan `theme_asset` JSON dengan kunci `hero`, `texture`,
   `artwork`, `poster`. Kolom hanya berisi nama file acak; frontend otomatis
   memakainya tanpa perubahan view.
2. Unlock: `UPDATE` baris LOCKED -> `UNLOCKED` + `unlocked_at` dalam transaction
   (jangan DELETE), insert `vote_unlock_logs` + `audit_logs`. Kunci baris
   pemilih `FOR UPDATE` lebih dulu (urutan kunci sama dengan `castVote()`).
   Setelah unlock, dasbor pemilih otomatis kembali menampilkan CTA.
3. Admin tidak boleh memanggil `VoteService::castVote()` untuk pemilih.
4. Analytics & detail suara hanya `status = 'LOCKED'`; `device_info` berformat
   `"HP / Android / Samsung"`, `browser_info` `"Samsung Internet 25"`.
5. Perubahan jadwal cukup mengubah `start_at`/`end_at`; halaman pemilih dan
   countdown mengikuti otomatis (server memutuskan, countdown hanya visual).
6. `election/clock` publik dan tidak boleh diberi data pemilih/suara.
7. Tetap tanpa gradient, tanpa emoji, hormati `prefers-reduced-motion`.
