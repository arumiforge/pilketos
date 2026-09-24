# Stage 8 — Rapikan Dasbor Pemilih & Bilik Suara (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 8. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE7-NOTES.md`, dan
`README.md`.

Ringkasan Stage 8 (permintaan revisi tampilan, tanpa perubahan database,
route, keamanan, atau alur penyimpanan suara):

- **navigasi HP** (< 720 px): tombol Dasbor & Keluar cukup ikon;
- **dasbor pemilih**: eyebrow "Dasbor Siswa/Guru" dihapus, nama rata tengah,
  NISN / kelas / nomor absen (guru: NIP) satu baris miring dipisah garis
  miring tanpa label terlihat; countdown pindah ke **jam melayang** di bawah
  layar;
- **pembuka bilik suara**: sapaan "Halo, {nama} · Bilik suara siswa" dihapus,
  judul rata tengah, langkah memilih menjadi **journey timeline** vertikal,
  countdown dalam **panel terminal** lebar penuh; di HP tiap bagian setinggi
  satu layar;
- **paku**: kertas bolong lebih besar (sobekan + serpihan), modal konfirmasi
  baru muncul **3 detik** setelah tusukan; **efek 3D selalu nyala**, tombol
  "Efek 3D" dihapus;
- **desktop**: bagian bilik suara lebih lebar (1360 px); eyebrow bab tidak
  lagi menulis "Pasangan 0X" dua kali; jarak label peran & nama calon
  diperlebar; modal konfirmasi rata tengah, peringatan gembok dirapikan, latar
  belakang diburamkan.

## 1. Dasbor pemilih

| Bagian | Perubahan |
|---|---|
| `partials/header.php`, `app.css` | label `.site-nav__label` disembunyikan secara visual di < 720 px (sebelumnya hanya < 380 px); tombol jadi lingkaran 44 px. Teks "Dasbor Siswa"/"Keluar" tetap ada untuk pembaca layar |
| `student/dashboard.php`, `teacher/dashboard.php` | tanpa `<p class="eyebrow">Dasbor ...</p>`; `<dl class="dash-meta">` tetap, `dt` diberi `visually-hidden`; pemisah `/` dari CSS `div + div::before` (`content: '/' / ''`, tidak dibacakan) |
| `partials/float_clock.php` (baru) | `<aside class="float-clock">` berisi lampu status + countdown varian `terminal`. Hanya saat `UPCOMING`/`ONGOING` |
| `app.css` `.float-clock` | `position: sticky; bottom: 16px`: melayang di dasar layar selama digulir, lalu berhenti di atas footer (tidak pernah menutupinya). `main:has(> .float-clock)` jadi kolom flex + `margin-top: auto` agar di halaman pendek pil tetap turun ke dasar layar (browser tanpa `:has()`: pil tepat di bawah konten) |

Countdown di kolom "Jadwal pemilihan" dihapus (satu countdown per halaman);
status, mulai, dan selesai tetap di sana.

## 2. Countdown varian `terminal`

`partials/countdown.php` menerima `variant = 'terminal'` (selain `compact` &
`dock`). Perilakunya sama dengan `dock`: format `03:12:45`, "hari" hanya bila
sisa >= 1 hari, garis progres mulai -> selesai. Gaya dasar di `app.css`
(font JetBrains Mono, `@font-face` kini juga di `app.css`, ukuran angka lewat
`--term-size`); bingkai diatur pemakainya:

- `.float-clock` (dasbor): pil gelap, garis progres di tepi bawah;
- `.term` (bilik suara, `voting.css`): jendela terminal lebar penuh dengan
  bilah judul `jam-server · pilketos`, baris perintah
  `pilketos:~$ sisa-waktu --tutup|--buka`, label "Ditutup dalam", angka besar
  rata tengah, garis progres, jadwal + kursor berkedip.

`countdown.js` tidak berubah (sudah membaca `[data-unit]`, label, dan
`[data-timeline-fill]` dari varian mana pun). Varian `compact` tetap dipakai
panel admin.

## 3. Bilik suara

### 3.1 Pembuka (`voting/index.php`)

| Urutan | Isi |
|---|---|
| Judul | "Kenali, lalu coblos." rata tengah (tetap dengan lubang coblos kecil) |
| Journey timeline | `<ol class="journey" data-journey>`: 1 **Kenali paslon** (`#sekilas`), 2 **Coblos satu** (`#surat-suara`), 3 **Konfirmasi & kunci** (bukan tautan). Node bernomor di rel vertikal, deskripsi singkat per langkah |
| Status | panel terminal (saat dibuka/akan dibuka) + pesan `.notice` bila belum/tidak bisa mencoblos |

Interaksi timeline:

- langkah & rel muncul bergiliran (animasi CSS saja; tanpa JS tetap tampil
  penuh, `prefers-reduced-motion` langsung ke keadaan akhir);
- langkah aktif (`.is-current`, `aria-current="step"`) berupa node tinta
  dengan cincin berdenyut; langkah selesai (`.is-done`) berganti centang dan
  rel di bawahnya terisi;
- `candidates.js` (`initJourney`): surat suara tercapai -> langkah 2 aktif;
  event `osis:journey` dari `ballot.js`: kertas tercoblos -> langkah 3,
  konfirmasi dibatalkan -> kembali ke langkah 2;
- desktop (>= 960 px): timeline & terminal berdampingan di bawah judul.

### 3.2 Satu layar per bagian (HP < 720 px)

`.booth-intro` `min-height: calc(100svh - 65px)` (dikurangi tinggi navigasi
situs), `.lineup`, `.chapter`, `.ballot` `min-height: 100svh`, isi dirata
tengahkan secara vertikal (flex). Bab yang lebih panjang dari satu layar tetap
memanjang; tidak ada scroll-snap (bab panjang tidak boleh "tersangkut").

### 3.3 Lebar desktop

`.booth-intro, .lineup, .chapter-nav, .chapter, .ballot { --container-w: 1360px; }`
(bawaan 1080 px). `.container` membaca variabel dari bagian induknya, jadi
halaman lain (`/siswa/pilihanku`, konfirmasi tanpa JS, navigasi situs) tidak
berubah.

### 3.4 Bab & Sekilas paslon

- `CandidateTheme::present()` menambah `theme_distinct` (false bila nama tema
  kosong/sama dengan "Pasangan 0X", tanpa beda huruf besar-kecil). Eyebrow
  bab dan kartu Sekilas paslon hanya menulis tema bila `theme_distinct`, jadi
  tidak ada lagi "PASANGAN 01   PASANGAN 01";
- `.chapter__role`: `margin-bottom: 12px`, huruf lebih kecil & renggang;
  jarak antarcalon 24 px.

## 4. Paku, kertas bolong, konfirmasi

| Bagian | Perubahan |
|---|---|
| mode | `decideMode()`: selalu `3d` bila `WebGLRenderingContext` ada; `NailWebGL.create(canvas, true)` (renderer software diizinkan). Paku 2D hanya cadangan: WebGL tidak ada/gagal dibuat, context lost, atau pemantau frame (>= 10 frame lambat dari 45 frame pertama) |
| tombol | `.fx-toggle` / `data-fx-toggle` dihapus dari markup, CSS, JS; preferensi lama `osis2026.fx` di localStorage dihapus saat bilik dibuka |
| lubang | `.hole` 72 px (HP) / 92 px (>= 720 px), dulu 30 px. SVG: cekungan, bibir sobek terangkat, lubang tembus gelap, 5 kelopak sobekan, kilap tepi kiri atas (arah cahaya tetap, tidak diputar acak); muncul dengan sedikit overshoot |
| serpihan | 9 `.hole-bit` terlempar lalu jatuh (0,9 s, dihapus setelah animasi); tidak dibuat saat reduced motion |
| jeda | `confirmLater()`: fase `punched` selama `CONFIRM_DELAY = 3000` ms (kotak lain meredup, paku & tombol Coblos terkunci), pembaca layar diberi tahu "Kotak pasangan 0X tercoblos. Konfirmasi muncul sebentar lagi.", lalu `openConfirm()` |
| modal | `.confirm` rata tengah; foto pasangan di tengah; peringatan = ikon gembok dalam lingkaran di atas teks; tombol ditumpuk selebar modal; `::backdrop` `rgba(21,20,26,.5)` + `backdrop-filter: blur(10px)`. Halaman konfirmasi tanpa JS memakai gaya yang sama |

Teks wajib, anti klik ganda 400 ms, POST JSON, dan jalur tanpa JavaScript
tidak berubah.

## 5. Test

Baru: `tests/feature/BoothRefinementTest.php` (8 test): navigasi ikon di HP
(aturan CSS + label tetap ada), kepala dasbor siswa & guru, jam melayang
(berlangsung, belum dibuka, selesai; satu countdown per halaman), pembuka
bilik suara (tanpa sapaan, timeline, terminal, satu layar di HP, lebar
desktop), bilik ditutup, paku 3D tanpa tombol + jeda 3 detik + ukuran lubang,
eyebrow bab tanpa teks kembar, modal rata tengah + latar buram.

Diperbarui: `VotingTest` (sapaan "Halo" & `data-fx-toggle` kini tidak
boleh ada), `CandidateThemeTest` (`theme_distinct`).

Hasil: **331 test, 2.741 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, HP 375×812 + desktop 1440×900, data
seeder): tidak ada luapan horizontal; tanpa error konsol; alur tombol
**Coblos Pasangan 02** -> lubang terlihat, modal belum terbuka pada 1,3 s ->
modal terbuka ±3,8 s setelah klik (terbang paku ±0,7 s + jeda 3 s) dengan
latar buram; alur seret paku (kanvas WebGL tampil, paku 2D tersembunyi) ->
lepas di kotak 01 -> modal setelah 3 s -> **Batal** -> lubang hilang,
timeline kembali ke langkah 2; timeline: langkah 1 selesai & langkah 2 aktif
setelah surat suara tercapai, langkah 3 aktif saat konfirmasi.

## 6. Catatan lanjutan

- jam melayang hanya di dasbor siswa/guru; bilik suara memakai panel
  terminal di pembuka (tidak ada countdown ganda);
- `icon('layers')` di helper tidak dipakai lagi (dibiarkan, tidak berbahaya);
- `:has()` untuk menurunkan jam melayang di halaman pendek: browser lama
  cukup menaruhnya tepat di bawah konten.
