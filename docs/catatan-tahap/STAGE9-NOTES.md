# Stage 9 — Bilik Suara Padat, Login Dua Tahap, Panel Admin Rapi (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 9. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE8-NOTES.md`, dan
`README.md`.

Ringkasan Stage 9 (permintaan revisi tampilan). Tanpa perubahan database,
route, atau alur penyimpanan suara. Satu perubahan server: login pemilih
menjawab JSON bila dikirim lewat AJAX (bagian 2.3).

- **bilik suara**: panel terminal tanpa teks `jam-server · pilketos` dan
  `pilketos:~$ sisa-waktu --tutup`; angka countdown satu baris & lebih kecil di
  HP; di HP pembuka + **Sekilas paslon** berbagi layar pertama; petunjuk
  Sekilas paslon satu baris sejajar judul di desktop;
- **login siswa/guru**: eyebrow "Masuk Siswa/Guru" dan petunjuk tanggal lahir
  dihapus; dua tahap (NISN/NIP -> kode unik) dengan indikator langkah; setelah
  diterima gembok terbuka, baru pindah ke dasbor;
- **panel admin**: eyebrow login admin dihapus; brand "SMP 1 DAWE" +
  "Panel Admin"; menu tanpa nomor & judul kelompok; "Dasbor & live count" ->
  **Beranda**; "Situs pemilih" -> **Home**; footer cukup hak cipta; semua
  `eyebrow-x` -> **breadcrumb stepper**; keterangan halaman (`admin-head__lede`)
  di balik ikon "i"; strip jadwal cukup mulai & berakhir; bar "diperbarui" +
  **Perbarui** menempel di bawah layar HP; `panel__note` jadi tooltip ikon.

## 1. Bilik suara (`voting/index.php`, `voting.css`)

| Bagian | Perubahan |
|---|---|
| panel terminal | `.term__bar` (teks judul) & `.term__cmd` (baris perintah) dihapus dari markup & CSS. Tiga titik jendela (`.term__dots`) tetap, melayang di pojok kiri atas (tidak menambah tinggi). Isi: label "Ditutup dalam"/"Dibuka dalam", angka, garis progres, jadwal + kursor |
| angka countdown | `.term .countdown__units`: `flex-wrap: nowrap; white-space: nowrap`. HP `--term-size: clamp(1.5rem, 8vw, 2.25rem)` (dulu 2.25-3.75rem, bisa pecah dua baris); >= 720 px `clamp(2.5rem, 6vw, 3.75rem)`. "06 hari 23:58:06" muat satu baris di layar 320 px |
| `.booth-open` (baru) | pembungkus `.booth-intro` + `.lineup`. HP (< 720 px): kolom flex `min-height: calc(100svh - 65px)`, pembuka tidak lagi setinggi layar sendiri; `.lineup` mengisi sisa ruang. Pembuka & kartu paslon dipadatkan di HP (judul 1 baris, node timeline 32 px, jarak & huruf lebih kecil, visi kartu 2 baris). `.chapter` & `.ballot` tetap satu layar |
| `.lineup__hint` | >= 960 px: `.lineup__head` tidak membungkus, petunjuk `white-space: nowrap` (elipsis bila sangat sempit), sejajar judul |

Ukuran di Chromium 375x812: navigasi 65 px + `.booth-open` 747 px = tepat satu
layar (kartu paslon pertama terlihat utuh). Di browser HP dengan bilah alamat
(`svh` lebih kecil) bagian bawah kartu bisa sedikit terpotong; tidak ada
konten yang disembunyikan.

## 2. Login pemilih dua tahap

### 2.1 Markup (`partials/voter_login.php`, dipakai `student/login` & `teacher/login`)

- kepala kartu: gembok SVG (`.padlock`: kait, badan, lubang kunci) dalam
  lingkaran, judul rata tengah;
- `<ol class="auth-steps" data-auth-steps>`: 1 **NISN/NIP**, 2 **Kode unik**,
  3 **Terbuka**; titik bernomor di rel, langkah aktif bercincin (`aria-current`),
  selesai = centang;
- satu `<form>` (POST sama seperti dulu) dengan dua panel:
  `data-auth-panel="1"` (NISN/NIP + **Lanjut**) dan `data-auth-panel="2"`
  (baris "NISN 0000000001 · Ubah" + kode unik + **Masuk**);
- `.auth__error` (`role="alert"`) & status pembaca layar (`aria-live`).

Tanpa JavaScript (atau `auth.js` gagal dimuat) kelas `.is-stepped` tidak
pernah terpasang: indikator & tombol Lanjut tersembunyi, kedua isian tampil,
form terkirim biasa (redirect + flash seperti sebelumnya).

### 2.2 Perilaku (`assets/js/auth.js`, `assets/css/auth.css`)

- **Lanjut** / Enter di tahap 1: NISN/NIP hanya diperiksa bentuknya di
  browser (wajib diisi; NISN angka; panjang maks). **Tidak ada** permintaan
  ke server di tahap 1, jadi tidak ada celah menebak NISN/NIP terdaftar.
  Panel bergeser masuk, fokus ke kode unik;
- **Ubah**: kembali ke tahap 1 (kode unik dikosongkan);
- **Masuk** / Enter di tahap 2: `fetch` POST form (token CSRF ikut dari
  `csrf_field`) dengan `X-Requested-With: XMLHttpRequest`. Selama menunggu
  lubang kunci berdenyut;
  - `ok` -> `.is-unlocked`: lingkaran jadi tinta, kunci berputar, kait terangkat
    lalu berayun di engselnya, cincin memancar, langkah 3 selesai; setelah
    1,35 s pindah ke `redirect` (hanya bila satu origin). Reduced motion: 0,2 s;
  - gagal -> gembok bergetar, pesan server tampil; `step = 1` kembali ke tahap
    1, selain itu kode unik dikosongkan & difokuskan;
  - jawaban bukan JSON (mis. token CSRF kedaluwarsa) -> form dikirim ulang
    secara biasa agar server yang menangani;
  - gagal jaringan -> pesan "Koneksi terputus...".
- listener `submit` di form memanggil `stopPropagation()`: pencegah submit
  ganda `app.js` tidak ikut mengubah tombol (status tombol diatur `auth.js`);
- setelah kiriman biasa ditolak (NISN/NIP terisi ulang dari flash) form
  langsung di tahap 2; kembali lewat bfcache mengosongkan kode unik;
- tidak ada NISN/NIP/kode unik di localStorage/sessionStorage.

### 2.3 Server (`BaseController`)

| Method | Perubahan |
|---|---|
| `failLogin($field, $value, $message, $status = 422)` | bila `wantsLoginJson()`: JSON `{ok: false, step, messages}` (`step` 1 bila pesan validasi milik field identitas, selain itu 2). Pesan sama persis dengan flash: NISN tak terdaftar dan kode salah menghasilkan jawaban identik. Throttle memakai status 429 |
| `loginSucceeded($path)` (baru) | JSON `{ok: true, redirect: site_url($path)}` atau redirect biasa |
| `wantsLoginJson()` (baru) | `isAJAX()` atau `Accept: application/json` |

Validasi, normalisasi kode unik, throttle per akun & per IP, regenerasi sesi,
dan pesan tidak berubah. Login admin tetap satu tahap (hanya eyebrow
"Masuk Admin" yang dihapus).

## 3. Panel admin

### 3.1 Kerangka (`layouts/admin.php`, `admin/partials/sidebar.php`)

| Bagian | Perubahan |
|---|---|
| brand | `<a class="admin-side__mark">SMP 1 DAWE</a>` + `<p class="admin-side__school">Panel Admin</p>` (dulu "OSIS 2026" + "SMP 1 DAWE · Panel Admin") |
| menu | tanpa `.admin-side__no` (penomoran) & `.admin-side__group` (judul Pemilihan/Data/Kontrol); tiga `<ul>` dipisah garis tipis. "Dasbor & live count" -> **Beranda** (judul halaman & topbar juga "Beranda") |
| tautan situs | "Situs pemilih" -> **Home** (tetap tab baru) |
| footer | `© {tahun} SMP 1 DAWE` saja, rata tengah |

### 3.2 Kepala halaman (15 halaman admin)

- `admin/partials/crumbs.php`: `<nav class="crumbs" aria-label="Breadcrumb">`,
  **Beranda** selalu langkah pertama, langkah terakhir `aria-current="page"`.
  Tampilan stepper: titik kecil di rel tipis, huruf kapital kecil renggang,
  titik halaman ini bercincin tinta. Di HP rel digeser horizontal bila
  panjang. Jejak: Beranda / Beranda > Analitik > Detail suara / Beranda >
  Siswa > Impor Excel > Pratinjau, dst. Kelas `.eyebrow-x` dihapus;
- `admin/partials/head_hint.php`: tombol ikon "i" (`data-lede-toggle`,
  `aria-expanded`, `aria-controls="admin-head-lede"`) menempel di ujung baris
  terakhir judul (`h1` inline). Keterangan `#admin-head-lede[data-lede]`
  tersembunyi (`.js`) sampai tombol ditekan, lalu tampil sebagai catatan
  bergaris kiri. Tanpa JS: tombol tidak tampil, keterangan terlihat. Cetak:
  keterangan selalu tercetak. Halaman tanpa keterangan (form pasangan calon)
  tanpa tombol.

### 3.3 Beranda (dasbor admin)

| Bagian | Perubahan |
|---|---|
| strip jadwal | "Status" dihapus (status tetap di topbar); cukup **Mulai** & **Berakhir** (dua kolom, juga di HP); countdown "Ditutup dalam" di bawahnya (>= 1100 px di sampingnya) |
| live status | `diperbarui HH.mm.ss WIB` + **Perbarui** dibungkus `.live__bar`: desktop `display: contents` (tampilan sama), HP (< 720 px) `position: fixed` di bawah layar + safe-area; `.admin-main` diberi ruang bawah 64 px. Dipakai juga di Analitik |
| catatan panel | `admin/partials/note.php`: ikon "i" di samping judul panel, teks = tooltip (`role="tooltip"`, `aria-describedby`) muncul saat hover / fokus / diketuk (CSS `:focus-within`, tanpa JS), Escape menutup (`admin.js`). HP: tooltip ditambatkan ke kepala panel agar tidak meluap. Dipakai di Rekap jenjang & Riwayat suara pemilih |

## 4. Test

Baru: `tests/feature/StageNineRefinementTest.php` (9 test): terminal tanpa
teks judul/perintah + angka satu baris, `.booth-open` + petunjuk satu baris,
markup login dua tahap siswa & guru (tanpa eyebrow/petunjuk tanggal lahir),
jawaban JSON login berhasil (siswa & guru), gagal (tahap 2, jawaban identik
untuk NISN tak terdaftar), NISN tidak valid (tahap 1), throttle 429, login
form biasa tetap redirect + login admin tanpa eyebrow, kerangka admin (brand,
menu, Beranda, Home, footer), breadcrumb + tombol keterangan di 12 halaman,
strip jadwal + `.live__bar` + tooltip catatan.

Diperbarui: `BoothRefinementTest` (baris perintah terminal kini tidak boleh
ada; aturan satu layar HP membaca `.booth-open`).

Hasil: **340 test, 2.966 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, data seeder, sisa waktu 6 hari):

- HP 375x812: `.booth-open` 747 px (pas satu layar di bawah navigasi),
  countdown `06 hari 23:58:06` satu baris (250 px); 320x640: satu baris
  (210 px), tanpa luapan horizontal; desktop 1440x900: petunjuk Sekilas
  paslon satu baris sejajar judul;
- login siswa: Lanjut kosong -> gembok bergetar + pesan; Enter -> tahap 2;
  kode salah -> pesan, kode dikosongkan; kode benar -> gembok terbuka, langkah
  3 selesai, lalu `/siswa`. Guru di 320 px: Lanjut -> Ubah -> Enter -> Enter
  -> `/guru`. Tanpa JavaScript: kedua isian tampil, login guru berhasil;
- admin HP & desktop: breadcrumb, ikon keterangan menempel di ujung judul
  dua baris, keterangan terbuka saat ditekan, tooltip Rekap jenjang saat
  hover, bar "diperbarui" menempel di bawah layar HP, drawer menu tanpa
  nomor/judul kelompok; 12 halaman admin di HP tanpa luapan horizontal;
  tanpa JavaScript keterangan tampil dan tombol tersembunyi;
- tanpa error JavaScript. Satu-satunya pesan konsol adalah respon 422 yang
  memang diharapkan dari percobaan kode salah.

## 5. Catatan lanjutan

- `:has()` dipakai untuk ruang bawah `.admin-main` saat bar live count
  menempel di HP; browser lama tanpa `:has()` bisa menutupi baris hak cipta
  footer (konten utama tetap terbaca);
- pil "Akun nonaktif" di kepala detail pemilih/unlock ikut berada di
  keterangan tersembunyi; status akun tetap tampil di panel Identitas
  (detail pemilih);
- `<title>` halaman login tetap "Masuk Siswa/Guru" (hanya teks di tab
  browser).
