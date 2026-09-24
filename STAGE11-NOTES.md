# Stage 11 — Analitik Pill + AJAX, Detail Suara Terpisah, Deteksi Perangkat, CRUD Pemilih (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 11. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE10-NOTES.md`, dan
`README.md`.

Ringkasan Stage 11 (permintaan revisi panel admin). Tanpa perubahan schema
database; alur penyimpanan suara tetap (hanya isi `device_info` /
`browser_info` yang lebih akurat).

- **analitik**: TOC diganti *pill section header* (Keseluruhan, Jenis
  pemilih, Jenis kelamin, Kelas, Rombel, Detail suara). Setiap pil = URL
  sendiri; dengan JavaScript isinya dimuat lewat fetch & diganti di tempat
  (tanpa memuat ulang halaman), HP & desktop. Penomoran bab `01`-`05` dihapus;
- **detail suara**: baris siswa dan guru dipisah menjadi dua tabel (kolom
  sesuai jenisnya, pagination masing-masing);
- **perangkat & browser**: parser User-Agent bawaan CodeIgniter diganti
  `matomo/device-detector` + Client Hints (bagian 3);
- **CRUD siswa & guru**: tambah & ubah satu per satu langsung di panel admin
  (sebelumnya hanya impor Excel);
- **daftar siswa/guru**: kolom "Status memilih" berisi "Sudah memilih" /
  "Belum memilih"; tanggal & jam memilih pindah ke kolom baru "Waktu memilih";
- **indikator live**: "Langsung" menjadi **Live** dengan ikon siaran SVG; di
  HP seluruh indikator digabung dengan `.live__bar` di bawah layar;
- **dasbor admin**: countdown di HP bergaya terminal, rata tengah, selebar
  layar tanpa sudut; "Rekap jenjang" menjadi **Rekap kelas** (7/8/9) dan
  "Rekap kelas" menjadi **Rekap rombel** (7A, 8B, ...).

"HP" = lebar < 720 px (sama dengan Stage 8-10).

## 1. Analitik: pill section header + muat bagian lewat fetch

| Bagian | Isi |
|---|---|
| route | `GET admin/analitik` (Keseluruhan), `admin/analitik/(:segment)` untuk `jenis-pemilih`, `jenis-kelamin`, `kelas`, `rombel` (`AnalyticsController::index($pane)`, slug lain 404), `admin/analitik/suara` tetap `votes()` |
| `AnalyticsController::PANES` | slug => label pil. Halaman penuh = `admin/analytics/index` (kepala "Analitik" + indikator live + pil + `[data-pane-host]`) |
| fragmen | request ber-header `X-Analytics-Pane` dijawab `admin/analytics/pane` saja (tanpa layout, `Vary: X-Analytics-Pane`): `<div class="pane" data-pane="…" data-pane-title="…" data-pane-heading="…">` + `section.chapter-x` (judul `#pane-title` tanpa nomor, catatan) + `admin/analytics/panes/<slug>.php` |
| pil (`nav.pills[data-pane-nav]`) | tautan biasa dengan `aria-current="page"` pada bagian aktif; `position: sticky` di bawah topbar (60 px); jalur pil membulat berlatar `--paper-dim`, pil aktif blok tinta. Dengan skrip, latar pil aktif digambar `.pills__glider` yang bergeser. HP: pil digeser horizontal, pil aktif dipusatkan |
| `admin-analytics.js` | klik pil -> `fetch(url, {headers: {'X-Analytics-Pane': '1', 'X-Requested-With': 'XMLHttpRequest'}})` -> `<template>` -> ganti isi host, animasi masuk (dimatikan `prefers-reduced-motion`), `history.pushState`, `document.title`, judul topbar, menu samping (Analitik/Detail suara), pengumuman `role="status"`. `popstate` memuat bagian lagi. Di dalam bagian: tautan ke bagian analitik (reset, pagination) & `form[data-pane-form]` (GET, parameter kosong dibuang) memakai jalur yang sama; fokus kembali ke kontrol filter yang sama. "Perbarui" memuat ulang Detail suara. 401 / redirect / error / timeout 12 dtk -> pindah halaman biasa |
| tanpa JavaScript | pil = pindah halaman penuh; pil aktif tetap berlatar tinta |
| live count | `admin-live.js` tetap mencari `table[data-live-table]` di `[data-live]` setiap polling, jadi tabel bagian yang baru dimuat ikut diperbarui |
| `admin.js` | `data-autosubmit` kini didelegasikan ke `document` (`change` pada `select`) agar form yang dimuat ulang tetap langsung diterapkan; `requestSubmit` memicu `submit` yang diambil alih `admin-analytics.js` |
| breadcrumb | Beranda > Analitik untuk semua bagian (termasuk Detail suara) |

Label: `grade` tampil "Kelas" (baris "Kelas 7"), `class` tampil "Rombel".
Kunci data & JSON live count (`groups.grade`, `groups.class`) tidak berubah.
Halaman hasil akhir: kolom "Jenjang" menjadi "Kelas".

## 2. Detail suara: siswa & guru terpisah

| Bagian | Perubahan |
|---|---|
| query | `AnalyticsService::detailVotes()` dipanggil per jenis (`['type' => 'student'|'teacher'] + $filters`); filter "Jenis pemilih" menyembunyikan tabel lainnya |
| pagination | grup Pager `siswa` / `guru`: `?page_siswa=` / `?page_guru=` (`AdminController::page($group)`, `pagerLinks(..., $group)`), 25 baris per tabel |
| tabel siswa | No, Siswa · NISN, Kelas, Absen, JK, Pilihan, Waktu, Perangkat · browser, Status |
| tabel guru | No, Guru · NIP, Pilihan, Waktu, Perangkat · browser, Status. Filter kelas/jenis kelamin aktif -> catatan "hanya berlaku untuk siswa" |
| ringkasan | "**13** baris suara: 11 siswa · 2 guru." + jumlah baris di kepala tiap tabel |
| perangkat | `device_summary()` (app_helper): model HP di baris pertama (atau kategori · OS), di bawahnya "HP · Android 14 · Chrome 140"; ikon ponsel/tablet/monitor. Dipakai juga di riwayat suara halaman detail pemilih |

## 3. Perangkat & browser (`App\Libraries\DeviceInfo`)

Masalah parser lama (`CodeIgniter\HTTP\UserAgent` + daftar kata kunci):
Chromebook terbaca "Mac OS X" ("CrOS x86_64" memuat "os x"), Opera Mini
terbaca "Tablet / Android" versi 12, browser Vivo / Instagram terbaca "Chrome",
merek "Samsung" ditebak dari nama browser, tanpa versi OS maupun model HP.

| Bagian | Isi |
|---|---|
| pustaka | `matomo/device-detector` ^6.4 (LGPL-3.0-or-later, lewat Composer) |
| `DeviceInfo::fromRequest()` | User-Agent + header `Sec-CH-UA`, `-Mobile`, `-Platform`, `-Platform-Version`, `-Model`, `-Full-Version-List`; dipakai `VotingController::submit()` |
| format | device "Kategori / OS versi / Merek Model", mis. "HP / Android 14 / Samsung Galaxy A54 5G", "HP / iOS 18 / Apple iPhone", "Tablet / iPadOS 17.5 / Apple iPad", "Komputer / Windows 11", "Komputer / ChromeOS"; browser "Chrome 140", "Samsung Internet 28", "vivo Browser 9", "Instagram (dalam aplikasi)", "curl 8" (bukan browser tetap tercatat untuk audit), bot = "Bot" / nama bot |
| versi dibekukan | tanpa Client Hints: "Android 10; K" -> "Android", macOS 10.15 -> "macOS", Windows NT 10.0 -> "Windows 10/11"; versi build ChromeOS & versi kernel Linux dibuang |
| Client Hints | `SecurityHeadersFilter` mengirim `Accept-CH: Sec-CH-UA-Platform-Version, Sec-CH-UA-Model, Sec-CH-UA-Full-Version-List` sehingga Chrome/Edge/Samsung Internet mengirim model & versi asli pada request berikutnya (saat mencoblos). **Hanya lewat HTTPS atau localhost**; lewat `http://IP-LAN` hanya User-Agent (Chrome Android cukup "HP / Android") |
| cache | `DeviceDetectorCache` menjembatani cache pustaka ke cache CodeIgniter (`writable/cache/DeviceDetector-*`, ±2 MB, tanpa kedaluwarsa, kunci memuat versi pustaka). Parse pertama ±450 ms, berikutnya ±5-70 ms. Cache gagal tidak menggagalkan suara |
| batasan | iPad dengan iPadOS 13+ mode desktop mengirim User-Agent Mac yang identik, jadi tercatat "Komputer / macOS". Nilai lama di database tidak diubah (trigger integritas suara melarang perubahan) |

## 4. CRUD siswa & guru

| Bagian | Isi |
|---|---|
| route | `GET admin/siswa/tambah`, `POST admin/siswa`, `GET admin/siswa/{id}/ubah`, `POST admin/siswa/{id}`; sama untuk `guru` (`VoterController::new/create/edit/update`) |
| validasi | `VoterEditor` + `VoterImporter::validateForm()`: aturan sama persis dengan impor Excel (NISN tepat 10 digit, NIP angka ≤ 30 digit, kelas berjenjang 7-9 dibakukan huruf besar, absen 1-999/kosong, kode unik tanggal lahir DDMMYYYY; spasi/strip/titik dibuang). Importer kini punya `checkFields()` per kolom yang dipakai impor & form |
| identitas ganda | NISN/NIP milik pemilih lain ditolak ("NISN … sudah dipakai … (kelas 7A)") |
| peringatan | nomor absen ganda dalam satu kelas, NIP bukan 18 digit: disimpan + flash `warning` (partial flash kini menampilkan `warning`) |
| status akun | hanya saat menambah (sakelar, bawaan aktif); setelahnya lewat tombol Aktifkan/Nonaktifkan di detail. Kolom lain di body diabaikan |
| audit | `STUDENT_CREATE`, `TEACHER_CREATE`, `STUDENT_UPDATE`, `TEACHER_UPDATE` ("Tambah/Ubah data siswa/guru"); ubah mencatat "nama A -> B; kelas 7A -> 7B; kode unik" (nilai kode unik tidak dicatat). Tanpa perubahan = tanpa audit |
| hasil final | saat FINISHED: tambah & ubah ditolak (`RESULTS_LOCKED_MESSAGE`), tombol Tambah/Ubah disembunyikan |
| UI | tombol "Tambah siswa/guru" di daftar, "Ubah" per baris, "Ubah data" di detail; form `admin/voters/form.php` (dua bagian: Identitas, Akun login; L/P berupa pil, saran kelas dari `datalist`) |

## 5. Daftar siswa/guru: status & waktu memilih

`Status memilih` berisi pil "Sudah memilih" (tinta + gembok) / "Belum
memilih" (garis). Kolom baru `Waktu memilih` di sebelahnya: "1 Okt 2026" +
"08.05.09 WIB" di bawahnya, atau "—" bila belum. Sel tabel pemilih sejajar
tengah secara vertikal; tautan Ubah & Detail dalam satu sel aksi.

## 6. Indikator live (`admin/partials/live_status.php`, `admin.css`, `admin-live.js`)

| Bagian | Perubahan |
|---|---|
| teks | ONGOING "Langsung" -> **"Live"** (juga `STATE_TEXT` di `admin-live.js`), warna hijau saat berlangsung |
| ikon | `icon('live')`: titik + dua pasang gelombang (`icon__wave--in/--out`) berdenyut bergantian saat berlangsung & polling aktif; status lain gelombang redup; `prefers-reduced-motion` tanpa animasi. `.live__dot` dihapus |
| markup | ikon & status masuk `.live__bar`: `.live__signal`, `.live__text` (status + waktu), tombol Perbarui. Desktop `display: contents` (tetap sebaris) |
| HP | `.live { display: contents; }`, bar bawah: ikon, status tebal di atas "diperbarui …", tombol Perbarui di kanan |

## 7. Dasbor admin

| Bagian | Perubahan |
|---|---|
| countdown | dibungkus `.admin-clock` (+ tiga titik jendela). Partial countdown menerima `trimDays` (hari hilang bila sisa < 1 hari) & `withProgress` (garis progres). Desktop: kotak compact seperti biasa + garis progres tipis. HP: panel terminal latar tinta, huruf mono, label "DITUTUP DALAM" renggang, `06 hari 23:56:58` rata tengah, selebar layar (margin negatif `--admin-pad`), tanpa sudut, garis progres di tepi bawah |
| rekap | "Rekap kelas" (grade, kolom "Kelas", catatan "kelas 7, 8, 9 dibaca dari awal nama rombel") lalu "Rekap rombel" (class, kolom "Rombel", tautan ke daftar siswa) |

## 8. Test

Baru: `tests/feature/StageElevenRefinementTest.php` (13 test): pil & tanpa
nomor bab, fragmen bagian (tanpa layout, `Vary`), detail suara dua tabel +
filter jenis/kelas, kontrak skrip (header, pushState, popstate, delegasi
autosubmit), indikator Live + ikon, dasbor (rekap kelas/rombel, countdown
terminal tanpa "hari" < 1 hari), daftar pemilih (status & kolom waktu),
tambah siswa (normalisasi, audit tanpa kode unik), error per kolom & NISN
ganda, ubah siswa (audit perubahan, status tidak ikut, tanpa perubahan tanpa
audit), tambah/ubah guru (peringatan NIP), kunci hasil final, `Accept-CH`.

Diperbarui: `DeviceInfoTest` (23 test: device-detector, Chromebook, Opera
Mini, vivo, Instagram, versi dibekukan, Client Hints Windows 11 & Galaxy A54,
jembatan cache), `VotingTest` (device tersimpan "HP / Android 13 / Samsung
Galaxy A14"), `AdminPanelTest` (bagian analitik per URL, perangkat dua baris,
pagination `page_siswa`), `StageNineRefinementTest` (breadcrumb detail suara,
bar live baru, catatan rekap kelas).

Hasil: **372 test, 3.268 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, data seeder + suara uji):

- analitik 1440 & 375: klik pil Rombel -> Kelas -> Jenis kelamin -> Detail
  suara tanpa memuat ulang (penanda `window` tetap), URL, judul tab, menu
  samping, glider ikut; filter "Guru" & pencarian lewat fetch; Back 3x kembali
  ke bagian sebelumnya; buka `admin/analitik/rombel` langsung = halaman penuh;
  "Perbarui" memuat ulang Detail suara; tanpa JavaScript pil = pindah halaman;
- HP: pil menempel di bawah topbar saat digulir, pil aktif dipusatkan; bar
  live bawah berisi ikon + "Live" + waktu + Perbarui; countdown dasbor panel
  terminal 375 px tanpa sudut;
- mencoblos lewat localhost: Client Hints terbaca (Windows 11, Galaxy A54 5G);
- tambah siswa tidak valid -> error per kolom, valid -> detail + peringatan
  absen ganda, ubah -> audit "nama … -> …; kelas 7A -> 7B", NISN ganda ditolak;
- tanpa luapan horizontal di 1440 & 375, tanpa error JavaScript.
