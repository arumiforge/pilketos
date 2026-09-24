# Stage 7 — URL Bahasa Indonesia, Nama Templat Impor & Redesain Bilik Suara (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 7. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE6-NOTES.md`, dan
`README.md`.

Ringkasan Stage 7:

- **URL bahasa Indonesia santai** untuk pemilih, admin, dan dua endpoint JSON
  publik. Dasbor ada di akar role: `/siswa`, `/guru`, `/admin`;
- **URL bahasa Inggris lama dihapus** (404, tanpa pengalihan);
- **nilai internal tidak berubah**: `user_type` di sesi (`student`/`teacher`/
  `admin`), `VoterType` (`student`/`teacher`), nama tabel, namespace
  controller, nama file view, dan key query string (`?page=`, `?vote=`,
  `?status=`, `?kelas=`, `?q=`, `?show=`, `?action=`);
- **templat impor**: `templat-impor-siswa.xlsx` / `templat-impor-guru.xlsx`;
- **redesain bilik suara** (`/siswa/coblos`, `/guru/coblos`): pembuka ringkas,
  bagian baru **Sekilas paslon**, CTA bab & tombol kartu langsung ke kotak
  surat suara, surat suara ringkas di HP;
- tanpa perubahan database, keamanan, atau alur penyimpanan suara; 7 test baru
  (total 323).

## 1. Peta URL (lama -> baru)

### Pemilih (pola sama untuk `guru`)

| Lama | Baru |
|---|---|
| `GET/POST student/login` | `GET/POST siswa/masuk` |
| `GET student` (redirect), `GET student/dashboard` | `GET siswa` |
| `GET/POST student/vote` | `GET/POST siswa/coblos` |
| `GET student/vote/confirm/{id}` | `GET siswa/coblos/yakin/{id}` |
| `GET student/my-vote` | `GET siswa/pilihanku` |
| `POST student/logout` | `POST siswa/keluar` |

### Admin

| Lama | Baru |
|---|---|
| `admin/login`, `admin/logout` | `admin/masuk`, `admin/keluar` |
| `admin` (redirect), `admin/dashboard` | `admin` |
| `admin/live-count` | `admin/hitung-suara` |
| `admin/analytics`, `admin/analytics/votes` | `admin/analitik`, `admin/analitik/suara` |
| `admin/results` | `admin/hasil` |
| `admin/candidates` (+ `new`, `{id}/edit`, `{id}/preview`, `POST {id}`, `POST {id}/delete`) | `admin/paslon` (+ `tambah`, `{id}/ubah`, `{id}/intip`, `POST {id}`, `POST {id}/hapus`) |
| `admin/students` (+ `{id}`, `POST {id}/status`, `POST {id}/delete`) | `admin/siswa` (+ `{id}`, `POST {id}/status`, `POST {id}/hapus`) |
| `admin/students/import` (+ `template`, `preview/{token}`, `POST commit`, `result`) | `admin/siswa/impor` (+ `templat`, `cek/{token}`, `POST simpan`, `selesai`) |
| `admin/teachers/...` | `admin/guru/...` (pola sama) |
| `admin/election` (+ `POST open`, `POST close`) | `admin/jadwal` (+ `POST buka`, `POST tutup`) |
| `admin/unlock` (+ `student/{id}`, `teacher/{id}`) | `admin/buka-kunci` (+ `siswa/{id}`, `guru/{id}`) |
| `admin/audit` | `admin/riwayat` |

### JSON publik

| Lama | Baru |
|---|---|
| `GET election/clock` | `GET jam-server` |
| `GET live-count` | `GET hitung-suara` |

`auto route` tetap mati (`Config\Routing::$autoRoute = false`), jadi URL lama
benar-benar 404. `RoleMatrixTest` kini memetakan prefix `admin`/`siswa`/`guru`
ke filter `adminauth`/`studentauth`/`teacherauth` dan menolak route redirect.

## 2. Implementasi

| Bagian | Perubahan |
|---|---|
| `app/Config/Routes.php` | ditulis ulang; dasbor = `$routes->get('/', ...)` di dalam grup (`siswa//` dipangkas CI4 jadi `siswa`); `buka-kunci/siswa/(:num)` diteruskan ke `UnlockController::form/student/$1` (nilai internal tetap) |
| `app/Services/VoterType.php` | baru `slug()` (`siswa`/`guru`) dan `unlockPath($id)`; `path()` & `adminPath()` memakai slug. Semua tautan pemilih & data pemilih admin lewat helper ini |
| filter auth, `*\AuthController` | halaman login `siswa/masuk`, `guru/masuk`, `admin/masuk`; setelah login ke `siswa`/`guru`/`admin` |
| `partials/header.php` | Dasbor/Keluar dari `VoterType::from($user_type)->path()` (admin: `admin`) |
| view & controller admin | string path diganti; suffix `edit/preview/delete/import...` -> `ubah/intip/hapus/impor...` |
| `VoterImporter::templateFilename()` | `templat-impor-{siswa|guru}.xlsx` |
| komentar JS | `admin-live.js`, `home.js`, `countdown.js` (URL dibaca dari atribut `data-*`, tidak ada path di JS) |

## 3. Redesain bilik suara

### 3.1 Masalah sebelumnya

Diukur di HP 375 px (Playwright, data seeder, tanpa gambar unggahan):

1. surat suara baru mulai di **3.709 px** (sekitar 4,5 layar), setelah pembuka
   besar dan tiga bab pasangan yang panjang. Pemilih yang sudah mantap tetap
   harus menggulir semuanya, dan sesi pemilih habis setelah 15 menit tanpa
   request;
2. tidak ada tempat membandingkan ketiga pasangan sekaligus;
3. pembuka ("Kenali, lalu coblos." + 4 langkah + countdown) memenuhi layar
   pertama HP;
4. di HP tiap kotak surat suara setinggi 150 px + tombol 52 px, sehingga tiga
   kotak dan dok paku tidak muat satu layar;
5. petunjuk paku dua kalimat panjang.

### 3.2 Susunan baru

| Urutan | Isi |
|---|---|
| Pembuka | eyebrow "Halo, {nama} · Bilik suara siswa/guru", judul "Kenali, lalu coblos." (tetap dengan lubang coblos), 3 langkah (Kenali paslon · Coblos satu · Konfirmasi & kunci), countdown compact yang lebih pipih di HP, pesan status bila belum/tidak bisa mencoblos |
| **Sekilas paslon** (baru, `voting/partials/lineup.php`) | tiga kartu `.pair-card` (aksen tiap pasangan): nomor, foto/monogram, tema, jumlah misi, ketua & wakil, kutipan visi (3 baris), **Baca visi & misi** (`#pasangan-0X`) dan **Pilih 0X** (`#coblos-0X`, hanya saat pencoblosan dibuka). HP: carousel scroll-snap (kartu 84 %, kartu berikutnya mengintip); >= 720 px: tiga kolom |
| Navigasi lengket | tetap chip 01/02/03; tombol kanan "Coblos" (atau "Surat suara" bila ditutup) |
| Bab pasangan | tidak berubah (layout split/poster/column, paralaks, visi kata demi kata, misi buka-tutup); panggung HP 380 -> 300 px; CTA akhir bab **Pilih pasangan 0X** -> `#coblos-0X` |
| Surat suara | kotak `li#coblos-0X`; HP: satu baris ringkas (nomor, foto kecil tanpa label peran, nama) + tombol 48 px; >= 720 px: tiga kolom seperti sebelumnya; petunjuk satu kalimat; dok paku + Efek 3D tetap |

Kotak tujuan disorot (garis aksen + tombol Coblos berwarna aksen):

- tanpa `ballot.js`: CSS `:target`;
- dengan `ballot.js`: kelas `.is-picked` (`.ballot--picks` mematikan versi
  `:target`) supaya sorotan dapat dilepas saat pemilih memegang paku atau
  mencoblos kotak lain. Setelah tautan `#coblos-0X` (atau hash yang sama
  diklik ulang), tombol Coblos kotak itu difokuskan (`preventScroll`) dan
  diumumkan ke pembaca layar.

Hasil di HP 375 px: tombol **Pilih 01** sudah terlihat di layar pertama;
satu ketukan membawa ke surat suara dengan tiga kotak + dok paku dalam satu
layar. Halaman memang sedikit lebih panjang (kartu baru), tetapi surat suara
tidak lagi harus dicapai dengan menggulir.

### 3.3 Yang sengaja tidak berubah

- metafora paku (WebGL + fallback 2D), dampak tusukan, modal konfirmasi dengan
  teks wajib `KONFIRMASI PILIHAN` / `SUARA BERHASIL DISIMPAN` / "Hak suara Anda
  telah dikunci.", jeda 400 ms anti klik ganda, POST JSON, halaman konfirmasi
  tanpa JavaScript (`/siswa/coblos/yakin/{id}`);
- aturan desain: tanpa gradient, tanpa emoji, hanya token `app.css` + aksen
  pasangan; `prefers-reduced-motion` tetap dihormati;
- hook yang dipakai test Stage 2 (`chapter chapter--{layout}`, `data-parallax`,
  `data-tilt`, `data-coblos`, "Coblos Pasangan 0X", `data-nail-grip`,
  `data-fx-toggle`, `id="vote-confirm"`).

Catatan CSS: `app.css` memuat reset `ul[class], ol[class] { margin: 0;
padding: 0 }` (spesifisitas 0,1,1), jadi aturan `ol` baru ditulis
`.booth-intro .booth-steps` dan `.lineup .lineup__list`. Kartu diberi
`position: relative` agar teks `visually-hidden` (absolute) ikut terpotong
carousel; tanpa itu halaman meluap ke samping di HP.

## 4. Test

Baru:

- `tests/feature/IndonesianRoutesTest.php` (5 test): halaman masuk, dasbor
  di akar role + filter, tautan navigasi pemilih & admin, helper `VoterType`,
  23 URL Inggris lama 404;
- `VotingTest::testLineupComparesPairsAndLinksStraightToBallotBox` dan
  `testLineupHasNoPickLinksWhenVotingIsClosed`.

Semua test lama diperbarui ke path baru (tanpa melemahkan assertion);
`RoleMatrixTest` menegaskan tidak ada route redirect.

Hasil: **323 test, 2.656 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, HP 375×812 + desktop 1280×900): tidak ada
luapan horizontal; alur siswa: login -> `/siswa` -> `/siswa/coblos` ->
**Pilih 02** (kotak 02 tersorot, tombol "Coblos Pasangan 02" terfokus) ->
Coblos -> modal konfirmasi -> **KONFIRMASI PILIHAN** -> `/siswa/pilihanku`.

## 5. Catatan lanjutan (belum dikerjakan)

- key query string masih bahasa Inggris (`?page=`, `?vote=belum`, `?show=`,
  `?action=`); nilainya sudah bahasa Indonesia. Mengganti key menyentuh form
  filter, pager, dan test, jadi dipisah bila diinginkan;
- sesi pemilih berakhir setelah 15 menit tanpa request; membaca visi-misi
  tidak mengirim request. Bila perlu, tampilkan pengingat di bilik suara
  (jangan ping otomatis tanpa interaksi karena melemahkan proteksi komputer
  lab yang ditinggal).
