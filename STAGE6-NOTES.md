# Stage 6 — Identitas Visual SMP 1 DAWE: Lereng Muria, Tipografi, Aset & Sistem Gerak (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 6. Spesifikasi:
`06-VISUAL-DIRECTION-PILKETOS.md`, `07-TYPOGRAPHY-FONT-SYSTEM-PILKETOS.md`,
`08-VISUAL-ASSET-SPECIFICATION-PILKETOS.md`,
`09-IMAGE-GENERATION-PROMPTS-PILKETOS.md`,
`10-MOTION-AND-INTERACTION-SYSTEM-PILKETOS.md`. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE5-NOTES.md`, dan
`README.md`. Isi lengkap setiap file ada di repository.

Ringkasan Stage 6:

- **font seluruh aplikasi**: Plus Jakarta Sans menggantikan Inter lewat token
  `--font-ui` (tetap tiga family: + Newsreader + JetBrains Mono); nama sekolah
  ditulis literal **"SMP 1 DAWE"** di semua teks yang terlihat (commit
  terpisah, sesuai 07 §11);
- **hero "LERENG"** mengutamakan visual: latar lereng Muria + atap sekolah
  (lanskap/potret), lapisan gelap solid, kontur punggungan, lapisan depan
  opsional, canvas "embun", lalu teks singkat "PILKETOS 2026 / SMP 1 DAWE" +
  CTA. Pita nomor pasangan, lede, dan "2026" bergaris tepi dihapus;
- **layar pembuka selalu tampil** (1,5–5,2 detik, tanpa lewati) dengan
  transisi **"kabut tersingkap"**: latar kabut (pemandangan hero yang sama)
  memudar menjadi hero jernih sementara lockup terbang ke navigasi.
  Pengecualian: muat ulang otomatis karena status pemilihan berubah;
- **lockup acara PILKETOS 2026** (tanda gugus parijoto + wordmark ter-outline)
  menggantikan logo placeholder Stage 5; **lambang resmi sekolah** dipasang
  apa adanya lewat konfigurasi;
- palet **"Pagi Muria"**, lampu status netral, warna identitas (parijoto)
  hanya di bilah muat dan **otomatis netral** bila mirip warna pasangan
  (CIEDE2000);
- sistem gerak baru (token, transisi scene 800 ms, reveal hero berurutan,
  paralaks berlapis, portal lebih tenang);
- tanpa perubahan database/route; 10 test baru (total 316).

## 1. Stage objective

| Permintaan (dokumen §) | Implementasi |
|---|---|
| 06 §3 konsep "Surat Suara dari Lereng Muria" | tempat = latar hero/pembuka/portal (placeholder SVG lereng Muria, sekolah beratap genteng, pinus, kabut); surat suara = perforasi S02, registration mark S03, titik "lubang coblos", tanda lockup |
| 06 §5 palet "Pagi Muria" + §5.1 netralitas | token `--night` s.d. `--on-night-3` di `home.css`; lampu status tanpa hijau/kuning; parijoto hanya bilah muat, dicek CIEDE2000 vs `theme_accent` semua pasangan (`Home::identityAccent()`) |
| 06 §6–§7 hierarki & hero berlapis | `home/index.php` `.hero-bg` (latar, lapisan gelap, kontur, lapisan depan) + canvas + `.hero` di sepertiga bawah-kiri |
| 06 §8 / 10 §8 portal | gambar 1:1 selasar sekolah (placeholder), label PJS 600 lebih kecil, hover 1,2/0,85, gambar 1,03→1,06 + ikut pointer ≤ 10 px, tekan 0,985 |
| 06 §9 scene SUARA | hanya palet netral + aksen pasangan (diuji browser), nama ketua Newsreader 500 / wakil PJS 400, persentase Newsreader 2,5–4 rem |
| 06 §10 / 10 §10 layar pembuka | `home/partials/splash.php` + `Intro` di `home.js` (selalu tampil, eager, menunggu aset layar pertama saja, keluar "kabut tersingkap") |
| 06 §11 / 07 §10 logo | B02/B03 `logo-light.svg`/`logo-dark.svg` = lockup baru; B01 lewat `Config\Homepage::$schoolEmblem` |
| 07 §2–§7 tipografi | Plus Jakarta Sans self-hosted; judul hero `clamp(2.5rem, 4.5vw, 4.5rem)` / HP `clamp(2rem, 10vw, 3rem)` / pendek `clamp(1.75rem, 7vh, 2.5rem)`, weight 600, tracking +0.03em, line-height 1.04; kicker mono 0.8125rem/0.18em; tidak ada tracking > 0.22em |
| 07 §3 "SMP 1 DAWE" | literal di title/meta, alt logo, footer, surat suara, panel admin, error, impor, `osis:check`, seeder, kepala file CSS/JS, README |
| 08 §2–§4 aset & kunci konfigurasi | `heroDesktop`, `heroMobile`, `heroForeground` (+ `schoolEmblem`, `identityAccent`, `identityMinDeltaE`); placeholder SVG; S01–S04 |
| 08 §8 performa | latar pembuka `fetchpriority="high"` (bukan lazy); portal & foto pasangan `fetchpriority="low"` dan tidak ditunggu; `.htaccess` WebP/AVIF 30 hari |
| 09 | prompt tidak diubah; aset placeholder mengikuti komposisi & zona aman 09 §4–§5 (A01 dibuat dari A03 dengan bingkai identik, seperti "cara A") |
| 10 §2–§9, §14 gerak | token `--motion-*`/`--ease-*`; tabel pemetaan Stage 5 → 6 diterapkan (bagian 8.5) |

## 2. Dependencies

Tidak ada dependency Composer/JS baru.

| Kebutuhan | Keputusan | Alasan |
|---|---|---|
| Font UI | Plus Jakarta Sans variable 200–800, subset latin, `woff2` 27 KB (@fontsource-variable 5.3.0), OFL | rekomendasi 07; dirancang di Indonesia; lebih kecil dari Inter (48 KB) |
| Wordmark lockup | di-outline dari Plus Jakarta Sans 700 + JetBrains Mono 500 dengan fontTools (instance variable → path SVG) | 07 §10: tidak bergantung font terpasang, bukan huruf hasil AI |
| Placeholder raster (A01–A04, A06, A08) | SVG ditulis sebagai kode (warna solid, tanpa gradient/filter) | aset AI/foto final dibuat panitia (dokumen 09); 08 §4 membolehkan SVG placeholder |
| Beda warna | CIEDE2000 di PHP (`CandidateTheme::deltaE()`), diuji dengan data Sharma et al. | 06 §5.1 menyebut ΔE2000 < 20 |
| Gerak | CSS transform/opacity + `home.js` (ES5) seperti Stage 5 | tanpa GSAP/Three.js; hanya transform & opacity |

## 3. Required previous files

`Config\Homepage`, `Home` controller, `home/index.php` + partials (Stage 5),
`home.js` (Deck, Intro, Live, Field), `home.css`, `layouts/main.php` (skrip
inline ber-nonce), `partials/header.php`, `countdown.js`,
`CandidateTheme::present()`, `asset_url()`, `asset_size()`, CSP Stage 4.

## 4. New files

```
STAGE6-NOTES.md                                   dokumen ini
public/assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2 + OFL-PlusJakartaSans.txt
public/assets/img/home/hero-desktop.svg           A03 placeholder: lereng Muria + sekolah (1920x1080)
public/assets/img/home/hero-mobile.svg            A04 placeholder potret (1080x1920)
public/assets/img/home/hero-contour.svg           S01 kontur punggungan (menjiplak A03), currentColor
public/assets/img/home/hero-contour-mobile.svg    S01 varian potret (menjiplak A04)
public/assets/img/home/hero-perforation.svg       S02 garis perforasi 480x8
public/assets/img/home/hero-registration.svg      S03 registration mark 48x48
public/assets/img/results/result-mark.svg         S04 stempel hasil akhir (halaman hasil admin)
tests/feature/VisualIdentityTest.php              9 test
```

## 5. Modified files

| File | Perubahan |
|---|---|
| `app/Config/Homepage.php` | kunci baru `heroDesktop`, `heroMobile`, `heroForeground`, `schoolEmblem`, `identityAccent`, `identityMinDeltaE`; komentar aset mengacu dokumen 08 |
| `app/Controllers/Home.php` | `identityAccent()` (netralitas parijoto vs aksen semua pasangan), data `identity` ke view |
| `app/Libraries/CandidateTheme.php` | `deltaE()` / `deltaE2000()` + konversi sRGB→CIELAB |
| `app/Views/home/index.php` | hero berlapis baru; portal `fetchpriority="low"`; komentar scene LERENG/PEMILIH/SUARA |
| `app/Views/home/partials/splash.php` | latar eager `fetchpriority="high"`, grup `brandmark` (lockup + lambang), `--parijoto` |
| `app/Views/home/partials/pair_photo.php` | foto `fetchpriority="low"` (tidak ditunggu layar pembuka) |
| `app/Views/partials/header.php` | grup `brandmark` (lambang resmi opsional + garis + lockup) |
| `app/Views/layouts/main.php` | penanda sekali pakai `osis2026.skipIntro` (baca + hapus) menggantikan `osis2026.intro`; `theme-color` `#101312`; preload Newsreader tidak di beranda; nama sekolah & font (commit 1) |
| `app/Views/layouts/admin.php`, `admin/partials/sidebar.php`, `admin/results/index.php`, `admin/election/index.php`, `voting/partials/ballot.php`, `partials/footer.php`, `errors/html/*` | "SMP 1 DAWE", font (commit 1); `admin/results`: stempel S04 |
| `app/Database/Seeds/ElectionSeeder.php`, `app/Services/Import/*Importer.php`, `app/Commands/SystemCheckCommand.php` | "SMP 1 DAWE" (commit 1) |
| `public/assets/css/app.css` | `@font-face` Plus Jakarta Sans, `--font-ui`; komponen `.brandmark` |
| `public/assets/css/home.css` | ditulis ulang: palet Pagi Muria, token gerak, hero berlapis, layar pembuka "kabut tersingkap", portal & live sesuai 07/10, lampu netral, reduced motion |
| `public/assets/css/results.css` | `.final__mark` (S04) |
| `public/assets/js/home.js` | layar pembuka selalu tampil + `skipNextIntro()`, daftar tunggu & urutan keluar baru, paralaks berlapis (`Parallax`), canvas "embun" (`Field`), durasi scene 800 ms, angka 750 ms |
| `public/assets/js/countdown.js` | `reloadForStatus()`: event `osis:status-reload` sebelum muat ulang karena status berubah |
| `public/assets/img/brand/logo-light.svg`, `logo-dark.svg` | lockup acara B02/B03 (nama file tetap) |
| `public/assets/img/home/intro-*.svg`, `entry-*.svg` | placeholder baru: hero berkabut (bingkai identik), selasar sekolah 1:1 |
| `public/.htaccess` | `AddType image/avif`, Expires WebP/AVIF 30 hari |
| `tests/feature/HomepageTest.php`, `AuthTest.php`, `AdminPanelTest.php`, `ResultsLockTest.php`, `tests/unit/CandidateThemeTest.php` | ekspektasi baru (theme-color, penanda layar pembuka, nama sekolah, CIEDE2000) |
| `README.md`, `.env.example`, `00-MASTER-PROJECT.md`, `STAGE5-NOTES.md`, `07`/`08`/`10-*.md` | disesuaikan (status implementasi, bagian 21 README, keputusan Stage 6) |

File Inter (`inter-latin-wght-normal.woff2`, `OFL-Inter.txt`) dihapus.

## 6. Database changes

Tidak ada. Tidak ada migration baru. Nilai bawaan nama pemilihan baru (form
jadwal & seeder) memakai "SMP 1 DAWE"; nama pemilihan yang sudah tersimpan
tidak diubah otomatis (07 §3).

## 7. Routes

Tidak ada route baru atau perubahan route. `GET live-count` tetap seperti
Stage 5.

## 8. Full implementation (ringkasan)

### 8.1 Tipografi (commit 1/2)

- `--font-ui: 'Plus Jakarta Sans', …` di `app.css` untuk semua halaman
  (publik, pemilih, admin). Preload hanya font layar pertama: beranda
  (Plus Jakarta Sans + JetBrains Mono), halaman lain (Plus Jakarta Sans +
  Newsreader).
- Nama sekolah literal "SMP 1 DAWE" (tidak lewat `text-transform`), termasuk
  `<title>` bawaan "Pemilihan Ketua & Wakil Ketua OSIS SMP 1 DAWE 2026".
- Skala sesuai 07 §7 (bagian 1). Kapital memakai tracking positif; tidak ada
  `-webkit-text-stroke`; tidak ada letter-spacing > 0.22em di `home.css`
  (diuji).

### 8.2 Hero LERENG (scene 01)

Lapisan (belakang → depan):

| # | Elemen | Keterangan |
|---|---|---|
| 1 | `picture.hero-bg__photo > img.hero-bg__img` | `(orientation: portrait)` → `heroMobile`, selain itu `heroDesktop`; `object-fit: cover`; `fetchpriority="high"` |
| 2 | `.hero-bg__shade` | `rgba(16,19,18,.22)` solid |
| 3 | `.hero-bg__contour` | mask `hero-contour(-mobile).svg`, warna Kabut, opacity 0,09; sebingkai dengan latar |
| 4 | `.hero-bg__fore` | hanya bila `heroForeground` diisi; pojok kanan bawah, keluar bingkai; disembunyikan di HP tegak |
| 5 | `canvas.field` | titik "embun / lubang coblos" hanya di sekitar pointer & saat diketuk |
| 6 | `.hero` | kicker "PILKETOS 2026" (+ S03), `h1`, baris pendukung, perforasi S02, CTA |

- `h1` untuk pembaca layar: "Pemilihan Ketua & Wakil Ketua OSIS SMP 1 DAWE
  2026" (bagian tersembunyi secara visual); kicker `aria-hidden` (duplikat).
- Baris pendukung opsional "Satu pemilih, satu suara." (< 60 karakter);
  disembunyikan di HP miring/layar pendek.
- CTA utama "Masuk untuk memilih", sekunder berupa tautan bergaris bawah
  tipis (tidak bersaing dengan CTA).
- Petunjuk "Gulir" dihapus dari hero (07 §6: teks hanya kicker, judul, CTA);
  navigasi scene & CTA tetap tersedia.
- Tanpa JavaScript: semua lapisan terlihat langsung (keadaan awal reveal
  hanya berlaku dengan kelas `.js`).

### 8.3 Lockup & lambang resmi

- Lockup (B02/B03, 349×64): tanda acara konsep L02 "gugus parijoto" (enam
  buah bertangkai, buah paling bawah = lingkaran kosong/lubang coblos) +
  "PILKETOS 2026" (PJS 700, +0.04em, di-outline) + "SMP 1 DAWE" (JetBrains
  Mono 500, +0.2em). Satu warna (Kabut / ink): tidak ada warna identitas di
  navigasi, jadi scene SUARA tetap netral.
- Lambang resmi (B01) **tidak** digambar ulang. Panitia menaruh file resmi di
  `public/assets/img/brand/` lalu mengisi `homepage.schoolEmblem`; navigasi &
  layar pembuka menampilkan `[lambang] | [lockup]` (grup `.brandmark`, ukuran
  relatif `--brand-h` sehingga proporsi sama di navigasi dan layar pembuka).
- Konsep tanda acara boleh diganti hasil eksplorasi L01–L03 (09 §7): gambar
  ulang sebagai SVG satu warna, lalu ganti isi `logo-*.svg` (nama tetap).

### 8.4 Layar pembuka (10 §10)

| Situasi | Layar pembuka |
|---|---|
| Tab baru, muat ulang (F5/tarik), URL diketik, tautan luar, bookmark | tampil |
| Tautan langsung `/#masuk`, `/#perolehan` | tampil, lalu mendarat di scene itu |
| Kembali (Back) dan halaman dimuat ulang browser | tampil |
| Kembali (Back) dari bfcache | tidak (JavaScript tidak dijalankan ulang; perilaku browser) |
| Muat ulang otomatis karena status pemilihan berubah (live count **atau** hitung mundur) | tidak |
| Pindah scene di dalam beranda | tidak |
| Tanpa JavaScript | tidak |
| Gerak dikurangi | tampil, durasi sama, statis |

- Penanda sekali pakai: `Live.reload()` dan event `osis:status-reload`
  (dikirim `countdown.js` sebelum `location.reload()`) menulis
  `sessionStorage["osis2026.skipIntro"] = "1"`; skrip inline ber-nonce di
  layout membaca, **menghapus**, lalu menambah `html.intro-seen`. Diblokir →
  layar pembuka tampil (diterima). `countdown.js` dipakai juga di dasbor, tetapi
  event hanya didengar `home.js`, jadi penanda tidak pernah ditulis di luar
  beranda.
- Durasi: minimal 1500 ms, maksimal 5200 ms (`Intro.MIN/MAX`); bilah dipacu
  waktu dan dibatasi aset nyata. Ditunggu: lockup (pembuka + navigasi), latar
  pembuka, latar hero yang dipilih `<picture>`, font layar pertama
  (`document.fonts.load` PJS 600 & JetBrains Mono 500). Tidak ditunggu:
  portal & foto pasangan (`fetchpriority="low"`, dulu `[data-preload]`).
- Urutan keluar "kabut tersingkap": t0 bilah & angka pudar 200 ms, latar kabut
  pudar 700 ms di atas hero yang sudah dirender, lockup terbang ke navigasi
  800 ms (shared element, hanya satu logo terlihat); t0+260 ms reveal hero
  dimulai. Latar kabut dan latar hero sama-sama diam di skala 1,04 lalu
  mengendap bersama (1200 ms, `ease-editorial`, mulai t0+260) sehingga
  pemandangan tidak bergeser saat kabut memudar.
- Warna bilah: `--parijoto` dari server bila netral, selain itu Kabut.
- Gerak dikurangi: lockup langsung terlihat, bilah terisi linear, keluar =
  pudar 200 ms tanpa terbang. Pengaman CSS bila `home.js` gagal: 6 detik.

### 8.5 Gerak (10 §2–§9)

| Hal | Stage 5 | Stage 6 |
|---|---|---|
| Transisi scene | 1,05 dtk | 800 ms (`--motion-scene`, `ease-in-out`) |
| Scene keluar | `translate3d(0,-12%,-320px) rotateX(10deg) scale(.9)`, redup .72 + opacity .35 | `translate3d(0,-6%,-120px) rotateX(3deg) scale(.94)`, redup .5; **tanpa rotateX** di layar sentuh |
| Stagger reveal | 85 ms | 70 ms (durasi 500 ms) |
| Judul hero naik | 1,3 dtk | 650 ms, mulai 440 ms |
| Reveal hero | kicker → judul → tahun → lede → CTA | latar 0 → lapisan depan 120 → kontur 240 → kicker 360 → judul 440 → pendukung 500 → CTA + perforasi 560 ms |
| Paralaks | judul ±10 px, pita ±18 px | latar 6, kontur 8, lapisan depan 14, teks 3 px; lerp 0,08; hanya hero aktif & pointer halus; latar diam di skala 1,02 |
| Portal | `scale(1.1)` + ±26 px, grow 1.32/0.78 0,9 dtk | 1,03 → 1,06 + ≤ 10 px, grow 1.2/0.85 680 ms, label 6 px, panah 4 px |
| Angka live | 1,2 dtk | 750 ms |
| Canvas | bidang titik penuh + gelombang diam 30 fps | titik hanya dalam radius 140 px pointer & riak ketukan, opacity ≤ 0,35, diredam 70% di area fokus gambar & blok teks; berhenti bila diam |
| Failsafe layar pembuka | 8 dtk | 6 dtk |

### 8.6 Netralitas warna

- Hero dan layar pembuka tanpa warna, nomor, nama, atau foto pasangan (diuji).
- Parijoto (`#A8628F`) hanya di bilah muat; `Home::identityAccent()`
  membandingkan dengan `theme_accent` **semua** pasangan (aktif & nonaktif);
  CIEDE2000 < `identityMinDeltaE` (20) → null (Kabut). Data seed: 27,7 /
  36,9 / 32,9 (terracotta / hijau tua / biru tua) → parijoto dipakai.
- Scene SUARA: diuji di browser tidak ada warna parijoto; lampu status
  netral (titik penuh berdenyut = berlangsung, cincin = belum dibuka, redup =
  selesai/tanpa jadwal) sehingga tidak ada hijau yang mirip aksen pasangan
  dan status tidak bergantung pada warna.
- Warna atap sekolah pada placeholder hero dijaga CIEDE2000 > 20 terhadap
  aksen terracotta seed.

### 8.7 Aset placeholder

| ID | File | Ukuran (gzip) | Catatan |
|---|---|---|---|
| A03 / A04 | `hero-desktop.svg` / `hero-mobile.svg` | 24 / 18 KB | lereng Muria berlapis, kabut lembah, sekolah beratap genteng, pinus, embun; komposisi & zona aman 08 §5.1–5.2 |
| A01 / A02 | `intro-desktop.svg` / `intro-mobile.svg` | 32 / 26 KB | geometri A03/A04 yang sama + kabut pekat (bingkai identik, diuji) |
| A06 / A08 | `entry-student.svg` / `entry-teacher.svg` | 2,5 / 2,3 KB | selasar sekolah perspektif satu titik; siswa (jilbab putih, rok/celana biru tua) & guru (jilbab, blus batik halus) dari belakang, di zona tengah |
| B02 / B03 | `logo-light.svg` / `logo-dark.svg` | 3,4 KB | lockup (bagian 8.3) |
| S01–S04 | kontur, perforasi, registration, result mark | 0,2–4,5 KB | `currentColor`, `aria-hidden`, ≤ 20 KB |

Layar pertama beranda di HP (latar pembuka + latar hero + lockup + 2 font):
±115 KB terkompresi, jauh di bawah anggaran ±900 KB (08 §8.1).

A05 (lapisan depan), A07/A09 (portal HP 3:2), dan B01 (lambang resmi) belum
ada filenya: kunci konfigurasi `null` sampai panitia menyediakan.

## 9. Testing

### 9.1 Test otomatis

`composer test` pada kode final:

| PHP | Database | Hasil |
|---|---|---|
| 8.4.19 | MariaDB 10.11.14 | **316 test, 2.525 assertion, OK** |

Stage 1–5: 306 test; Stage 6: 10 test baru. PHP 8.2 dan MySQL 8 tidak diuji
ulang di tahap ini (tidak ada perubahan schema, query, maupun sintaks PHP di
luar yang sudah dipakai Stage 1–5).

| Dokumen | Test |
|---|---|
| 07 font & nama sekolah | `VisualIdentityTest::testUiFontIsPlusJakartaSansAcrossTheApp`, `testSchoolNameIsWrittenInCapitalsOnEveryPage` |
| 06 §7 hero | `testHeroIsLayeredVisualFirstWithShortText`, `testHeroImagesAreReplaceableThroughConfig` |
| 06 §10 / 10 §10 layar pembuka | `testSplashIsTheFoggyHeroAndAlwaysShows`, `HomepageTest::testSplashScreenHasSeparateLandscapeAndPortraitBackgrounds` |
| 06 §5.1 netralitas | `testIdentityAccentStaysNeutralAgainstCandidateColours`, `CandidateThemeTest::testDeltaE2000MatchesTheReferenceData` |
| 06 §11 logo | `testLockupAndOptionalOfficialEmblemInTheNavigation` |
| 08 §1–§2 aturan SVG | `testBrandAndCodeSvgsFollowTheAssetRules` |
| 06 §5, 07 §7–§8 gaya | `testHomepageStylesUsePagiMuriaAndCalmerType` |

### 9.2 Uji browser (dijalankan, bukan bagian `composer test`)

Chromium (Playwright 1.56.1) terhadap `php spark serve`, CSP aktif, data
development. **39 pemeriksaan otomatis, semua lolos**:

- Layar pembuka tampil pada pemuatan pertama; `is-leaving` pada ±2,1 detik
  (≥ 1,5 detik), dihapus ±3,0 detik; lockup terbang (`translate3d + scale`);
  reveal hero dimulai sebelum layar pembuka hilang; latar kabut & latar hero
  ber-skala bersamaan; hanya satu logo terlihat.
- Muat ulang (F5) → tampil lagi; event `osis:status-reload` → penanda ditulis,
  muat ulang berikutnya tanpa layar pembuka dan penanda terhapus; pemuatan
  setelahnya tampil lagi; `/#perolehan` dari halaman lain → tampil lalu
  mendarat di scene perolehan.
- Hero 1440 px: judul 64,8 px (4,5vw; maks 72 px), weight 600, tracking
  positif; latar diam di skala 1,02; kontur opacity 0,09; paralaks latar
  ≤ 6 px; canvas hanya 665 piksel menyala (di sekitar pointer).
- Scene mundur memakai `rotateX(3deg)` di desktop, tanpa miring di HP
  sentuh; label portal PJS & judul section Newsreader; scene SUARA tanpa
  warna parijoto; lampu status Kabut.
- HP 390×844 sentuh: judul 39 px; `hero-mobile` & kontur potret dipilih; CTA
  di atas panel status; tanpa overflow horizontal; latar tanpa skala paralaks.
- Gerak dikurangi: layar pembuka tetap ±1,7 detik, tanpa logo terbang;
  canvas mati, latar tanpa skala, teks langsung terlihat.
- Tanpa JavaScript: tanpa layar pembuka, hero lengkap (kontur terlihat,
  latar diam).
- Tanpa error konsol/pelanggaran CSP.
- **axe-core 4.10** (WCAG 2.1 A/AA + best-practice): beranda & login siswa
  pada 1280 px dan 390 px: **0 pelanggaran**.
- Halaman hasil akhir admin (status FINISHED): stempel S04 di kanan
  masthead, judul tidak tertimpa.

### 9.3 Perbandingan font (07 §2)

Screenshot (bukan specimen) beranda (hero, masuk, perolehan) dan login siswa
pada 1366×768 dan 390×844 untuk tiga sistem: Plus Jakarta Sans, DM Sans, dan
Inter (masing-masing + Newsreader + JetBrains Mono). Hasil pengamatan:

- **Plus Jakarta Sans**: bentuk huruf paling hangat dan terbuka; "SMP 1 DAWE"
  kapital + tracking positif terasa tenang, tidak kaku; angka & tombol tetap
  jelas di HP.
- DM Sans: lebih geometris dan sempit; judul terlihat lebih kecil pada ukuran
  yang sama; cocok, tetapi karakter lebih generik.
- Inter: paling netral/"developer tool", sesuai yang ingin dihindari 07 §1.

Keputusan: Plus Jakarta Sans (tidak ada keberatan). Mengganti sistem cukup
lewat `@font-face` + token `--font-ui` di `app.css`, preload di dua layout,
dan outline wordmark lockup.

### 9.4 Belum diverifikasi

- Safari iOS, Firefox, HP Android fisik, trackpad fisik, pembaca layar
  sungguhan (sama seperti Stage 5).
- PHP 8.2 & MySQL 8 pada kode Stage 6 (tidak ada perubahan yang bergantung
  versi, tetapi belum dijalankan ulang).
- Aset final dari sekolah (lambang resmi, foto/AI hero, portal) dan
  pengukuran Lighthouse di Wi-Fi sekolah.

## 10. Edge cases

| Kasus | Perilaku |
|---|---|
| Lambang resmi belum ada (`schoolEmblem = null`) | lockup acara saja |
| Lambang resmi berupa PNG/SVG berasio lain | `asset_size()` membaca ukuran; tinggi = tinggi lockup |
| Pasangan memakai warna mirip parijoto (mis. ungu) | bilah muat otomatis Kabut; tanpa konfigurasi |
| `identityAccent` diisi nilai bukan `#RRGGBB` / kosong | diabaikan (netral); aman dari injeksi atribut style |
| `heroForeground` diisi | lapisan depan tampil di desktop & HP miring, tidak di HP tegak |
| Latar hero diganti foto asli | kontur bawaan tidak lagi menjiplak punggungan; gambar ulang S01 (09 §8) atau biarkan sebagai tekstur halus (opacity 0,09) |
| Latar pembuka tidak sebingkai dengan hero | transisi tetap berjalan, tetapi pemandangan tampak "bergeser"; buat A01 dari A03 (09 §4.3) |
| Status berubah saat beranda terbuka | live count / hitung mundur memuat ulang tanpa layar pembuka |
| sessionStorage diblokir | layar pembuka juga tampil setelah muat ulang otomatis |
| `home.js` gagal dimuat | layar pembuka & isi muncul sendiri setelah 6 detik |
| Gerak dikurangi | layar pembuka statis dengan durasi sama; tanpa paralaks, canvas, skala latar, tilt |
| Tanpa JavaScript | tanpa layar pembuka; hero lengkap; scene bergulir dengan scroll-snap |

## 11. Known limitations

1. Aset hero, layar pembuka, dan portal masih **placeholder SVG** buatan kode.
   Arah, komposisi, dan palet sudah sesuai dokumen, tetapi rasa "ini di lereng
   Muria" baru utuh dengan foto/aset final (09).
2. Lambang resmi SMP 1 DAWE belum tersedia di repository; dipasang panitia
   lewat `homepage.schoolEmblem`. Checklist konfirmasi 06 §2.3 (warna lambang,
   seragam hari H, foto gedung, izin lambang) masih terbuka.
3. Tanda acara (gugus parijoto) adalah konsep L02 yang digambar langsung
   sebagai SVG; eksplorasi L01–L03 dengan AI (09 §7) belum dilakukan.
4. Layar pembuka yang selalu tampil menambah 1,5–5,2 detik setiap membuka
   beranda (permintaan pemilik proyek). Halaman login/dasbor tidak terdampak.
5. Muat ulang oleh hitung mundur juga melewati layar pembuka (perluasan kecil
   dari 10 §10.1, karena penyebabnya sama: status pemilihan berubah).
6. Warna parijoto di halaman terang (`--parijoto-ink`) belum dipakai; tidak ada
   elemen identitas di halaman terang saat ini.
7. Hal yang belum diverifikasi: bagian 9.4.

## 12. Handoff / catatan pemeliharaan

- Mengganti aset: taruh file di `public/assets/img/...`, isi kunci
  `homepage.*` (README bagian 21). A01 harus sebingkai dengan A03, A02 dengan
  A04. Kunci baru wajib punya nilai bawaan yang filenya ada (test & instalasi
  baru).
- Warna identitas hanya lewat `--parijoto` (satu pemakaian di `home.css`,
  diuji); jangan dipakai di scene SUARA atau di navigasi.
- Hero dan layar pembuka tidak boleh memuat data pasangan (diuji).
- Gerak baru: pakai token `--motion-*` / `--ease-*`, animasikan hanya
  `transform`/`opacity`, sediakan keadaan tanpa JS (`.js` untuk keadaan awal
  reveal) dan reduced motion.
- Muat ulang otomatis baru di beranda: panggil `skipNextIntro()` (atau kirim
  `osis:status-reload`) sebelum `location.reload()`.
- Nama sekolah selalu literal "SMP 1 DAWE" (diuji di beranda, login, dasbor).
- Tetap tanpa gradient, tanpa emoji, tanpa blur; skrip inline tetap satu dan
  ber-nonce.
