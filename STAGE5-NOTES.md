# Stage 5 — Redesign Beranda: Scene Imersif, Pintu Masuk, Live Count Publik & Layar Pembuka (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 5 (spesifikasi:
`05-HOMEPAGE-REDESIGN.md`). Dibaca bersama `00-MASTER-PROJECT.md`,
`STAGE1-NOTES.md` s.d. `STAGE4-NOTES.md`, dan `README.md`. Isi lengkap setiap
file ada di repository (tidak disalin ulang di sini).

Ringkasan Stage 5:

- beranda menjadi tiga **scene layar penuh** yang berpindah satu per satu
  (roda mouse, trackpad, geser sentuh, keyboard, navigasi scene, tautan),
  dengan transisi kedalaman 3D: scene lama mundur & meredup, scene baru
  meluncur di atasnya, isi muncul berlapis;
- **navigasi**: logo lengkap di tengah (semua halaman publik & pemilih),
  tombol "Masuk Siswa/Guru" dihapus; pengguna yang login tetap punya Dasbor
  & Keluar;
- **pintu masuk** Siswa/Guru berupa portal visual besar dengan ilustrasi yang
  dapat diganti lewat konfigurasi;
- bagian "Sedang Berlangsung" dipindah ke **panel status bergaya terminal**
  yang menempel di bawah layar;
- **live count publik**: foto pasangan + persentase + suara masuk, diperbarui
  otomatis tiap 30 detik (`GET live-count`, dapat dimatikan);
- **layar pembuka** seperti aplikasi native: latar lanskap/potret terpisah,
  logo di tengah, bilah muat nyata, logo "terbang" ke navigasi;
- footer minimal rata tengah; tanpa perubahan database; 17 test baru
  (total 306).

## 1. Stage objective

| Permintaan (05 bagian) | Implementasi |
|---|---|
| 1 Navigasi | `partials/header.php`: grid 3 kolom (`1fr auto 1fr`) sehingga logo selalu tepat di tengah; tamu hanya melihat logo; login: Dasbor (kiri) & Keluar (kanan), ikon saja di layar < 380 px |
| 2 Scroll per scene | `home.js` (Deck) + `home.css`: panggung tetap (`position: fixed`), satu scene aktif, transisi 1,05 detik; tanpa JavaScript: halaman bergulir biasa dengan `scroll-snap-type: y mandatory` |
| 3 Pintu masuk | scene "Masuk": judul "Masuk sebagai" + dua portal (SISWA, GURU) dengan ilustrasi `object-fit: cover`, lapisan gelap, nomor, panah; hover: portal melebar, gambar mengikuti pointer; sentuh: tekan mengecil |
| 4 Sedang berlangsung | `home/partials/dock.php`: status (lampu + teks), prompt `pilketos:~$`, countdown jam server (odometer), kursor berkedip, jadwal, garis progres pemilihan |
| 5 Live count | scene "Perolehan suara": foto/monogram pasangan, persentase tepat di bawah foto, batang aksen, nama; kanan: "Suara masuk" + meter bersegmen + "x dari y pemilih"; bawah: "Diperbarui [tanggal] [jam]" |
| 6 Layar pembuka | `home/partials/splash.php` + `Intro` di `home.js` |
| 7 Footer | `partials/footer.php` rata tengah; di beranda dirender di dalam scene terakhir |
| 8 Arah visual | gelap sinematik, tipografi Newsreader besar, label mono JetBrains Mono, tanpa kartu/border berlebih, tanpa gradient & emoji |
| 9 Responsif | komposisi khusus desktop, laptop pendek, HP tegak, HP miring; `dvh`/`svh`, safe-area, bilah alamat |
| 10 Batasan | tidak ada perubahan database, autentikasi, voting, atau API lama; satu route publik baru yang memakai `AnalyticsService` apa adanya |

## 2. Dependencies

Tidak ada dependency Composer/JS baru. Keputusan:

| Kebutuhan | Keputusan | Alasan |
|---|---|---|
| Scroll per scene | kontroler sendiri (`home.js`, ES5, tanpa library) | fullpage.js berlisensi komersial; GSAP tidak perlu untuk transisi CSS; kontrol penuh atas inersia trackpad, konten yang lebih tinggi dari layar, fokus & aksesibilitas |
| Transisi 3D | CSS `transform`/`opacity` + `perspective` | berjalan di compositor GPU, ringan di HP |
| Bidang titik hero | canvas 2D (~2.500 titik, 30 fps saat diam) | tidak perlu WebGL/Three.js |
| Font panel terminal | JetBrains Mono (OFL, variable, latin, 40 KB) self-hosted | tampilan terminal konsisten di Windows/Android/iOS; dimuat hanya di beranda |
| Logo & ilustrasi | SVG solid (tanpa gradient), teks logo di-outline dari Newsreader/Inter | tajam di semua layar; placeholder sampai aset final tersedia |

## 3. Required previous files

`ElectionModel::getCurrentElection()`, `CandidateModel::getAllOrdered()`,
`CandidateTheme::present()`, `AnalyticsService::snapshot()` (lewat
`service('analytics')`), helper `election_clock()`, `format_waktu()`,
`election_status_label()`, `asset_url()`, `icon()`, `partials/countdown.php`,
`countdown.js`, `app.js` (`App.reducedMotion()`), layout `layouts/main.php`,
filter & CSP Stage 4.

## 4. New files

```
app/Config/Homepage.php                         aset beranda, live count publik (bisa di-.env)
app/Services/PublicLiveCount.php                proyeksi sempit AnalyticsService + irama + cache
app/Views/home/partials/splash.php              layar pembuka
app/Views/home/partials/dock.php                panel status bergaya terminal
app/Views/home/partials/pair_photo.php          foto berdua / foto ketua+wakil / monogram
public/assets/css/home.css                      gaya beranda imersif (9 KB gzip)
public/assets/js/home.js                        scene, layar pembuka, live count, bidang titik (11 KB gzip)
public/assets/fonts/jetbrains-mono-latin-wght-normal.woff2  + OFL-JetBrainsMono.txt
public/assets/img/brand/logo-light.svg          logo untuk latar gelap (placeholder)
public/assets/img/brand/logo-dark.svg           logo untuk latar terang (placeholder)
public/assets/img/home/intro-desktop.svg        latar layar pembuka lanskap (placeholder)
public/assets/img/home/intro-mobile.svg         latar layar pembuka potret (placeholder)
public/assets/img/home/entry-student.svg        ilustrasi pintu Siswa (placeholder)
public/assets/img/home/entry-teacher.svg        ilustrasi pintu Guru (placeholder)
public/assets/img/patterns/ticks.svg            mask meter bersegmen
tests/feature/HomepageTest.php                  14 test
tests/unit/PublicLiveCountTest.php              3 test
05-HOMEPAGE-REDESIGN.md, STAGE5-NOTES.md
```

## 5. Modified files

| File | Perubahan |
|---|---|
| `app/Config/Routes.php` | `GET live-count` -> `Home::liveCount` (publik) |
| `app/Controllers/Home.php` | data scene (pasangan aktif, live count + tema/foto), `liveCount()`, `resetData()` renderer setelah render |
| `app/Helpers/app_helper.php` | `asset_size()` (ukuran intrinsik SVG/raster), ikon `arrow-up-right`, `arrow-up`, `chevron-down` |
| `app/Views/layouts/main.php` | mode imersif (`$immersive`): `viewport-fit=cover`, `theme-color` gelap, kelas `is-immersive`, penanda layar pembuka di skrip ber-nonce yang sama, section `overlay`, footer global tidak dirender |
| `app/Views/partials/header.php` | logo gambar di tengah, tanpa tombol masuk, Dasbor/Keluar untuk yang login |
| `app/Views/partials/footer.php` | rata tengah, minimal, varian `--scene` |
| `app/Views/partials/countdown.php` | varian `dock` menggantikan `hero` (hari hanya bila sisa >= 1 hari, garis progres) |
| `app/Views/home/index.php` | ditulis ulang: tiga scene, navigasi scene, panel status |
| `public/assets/css/app.css` | header & footer baru; gaya beranda lama (`hero-x`, `clock-band`, `countdown--hero`, `timeline`, `entry`, `teaser`) dihapus |
| `tests/feature/RoleMatrixTest.php` | `live-count` di daftar route publik |
| `tests/feature/SecurityHardeningTest.php` | `live-count` juga `no-store` + `nosniff` |
| `README.md`, `.env.example`, `00-MASTER-PROJECT.md`, `02-STUDENT-TEACHER-VOTING.md`, `STAGE2-NOTES.md`, `STAGE4-NOTES.md` | disesuaikan dengan desain baru |

## 6. Database changes

Tidak ada. Tidak ada migration baru; angka publik dihitung dari query
`AnalyticsService` yang sudah ada.

## 7. Routes

| Method | URI | Handler | Filter |
|---|---|---|---|
| GET | `live-count` | `Home::liveCount` (JSON, `no-store`) | — (publik) |

Respons (contoh):

```
{
  "status": "ONGOING",                 UPCOMING | ONGOING | FINISHED | null
  "label": "Sedang Berlangsung",
  "candidates": [{"id": 1, "label": "01", "percent": 42.5}, ...],   urut nomor
  "turnout": {"voted": 412, "total": 525, "percent": 78.48},
  "updated_at": "2026-10-01T09:47:15+07:00",
  "updated_label": "1 Oktober 2026, 09.47.15 WIB",
  "poll": {"interval": 30}             0 = berhenti (FINISHED / tanpa jadwal)
}
```

- `percent` = persen suara sah pasangan (sama dengan dasbor admin);
  `turnout.percent` = partisipasi (sudah memilih / pemilih aktif).
- Tidak ada jumlah suara per pasangan, pemisahan siswa/guru, rekap
  kelas/jenjang/jenis kelamin, nama, NISN/NIP (diuji
  `testLiveCountJsonIsANarrowProjectionOfTheAnalytics`).
- `Config\Homepage::$publicLiveCount = false` -> 404 dan beranda hanya
  menampilkan pasangan calon tanpa angka.

Route lama tidak berubah. `RoleMatrixTest` mendaftarkan `live-count` sebagai
publik yang disengaja.

## 8. Full implementation (ringkasan)

### 8.1 Navigasi & footer

- Logo `<img>` dari `Config\Homepage` (`logoOnDark` untuk beranda gelap,
  `logoOnLight` untuk halaman terang); `width`/`height` dibaca otomatis dari
  `viewBox` SVG atau header raster (`asset_size()`), jadi logo pengganti
  dengan rasio lain tidak menggeser layout.
- Tanpa tombol masuk. Login siswa/guru lewat portal di beranda (atau langsung
  `/student/login`, `/teacher/login`); login admin tetap `/admin/login`.
- Beranda: navigasi melayang transparan di atas scene (`site-nav--immersive`),
  `padding-top: env(safe-area-inset-top)`.
- Footer: satu baris rata tengah "© 2026 SMP 1 Dawe · Pemilihan Ketua & Wakil
  Ketua OSIS". Di beranda berada di scene terakhir bersama "Kembali ke awal".

### 8.2 Layar pembuka

- `<picture>`: `(orientation: portrait)` -> `introMobile`, selain itu
  `introDesktop`; lapisan gelap `rgba(8, 8, 11, .58)`; logo di tengah; bilah
  muat + persentase mono.
- Bilah mengikuti asset yang benar-benar dimuat: logo, latar, gambar scene
  (`[data-preload]`: ilustrasi portal, foto pasangan), font, `window.load`.
  Tampil minimal 1,5 detik, paling lama 5,2 detik.
- Keluar (~1 detik): latar & bilah memudar, logo pembuka bergerak dan mengecil
  tepat ke posisi logo navigasi (shared element), hero muncul berlapis.
- Sekali per sesi tab: `sessionStorage["osis2026.intro"] = "1"` (penanda
  non-sensitif). Skrip inline ber-nonce di layout (satu-satunya skrip inline,
  seperti Stage 4) menambah kelas `intro-seen` sebelum render pertama,
  sehingga kunjungan berikutnya tidak berkedip. `loading="lazy"`: latar tidak
  diunduh saat layar pembuka dilewati.
- Pengaman: tanpa JavaScript layar pembuka tidak tampil; bila `home.js` gagal
  dimuat, layar pembuka dan isi scene muncul sendiri lewat animasi CSS setelah
  8 detik.

### 8.3 Scene layar penuh (`Deck` di `home.js`)

Markup: `section.scene[data-scene][id][aria-labelledby]` berisi
`.scene__inner[data-scene-scroll]`. `home.js` menambah `html.has-scenes`:
panggung `position: fixed; inset: 0; overflow: clip`, halaman tidak
menggulir (bilah alamat HP stabil), scene ditumpuk.

| Input | Perilaku |
|---|---|
| Roda mouse | satu takik (>= 40 px) = satu scene; putaran beruntun dalam satu gestur tetap satu scene |
| Trackpad | ekor inersia tidak memicu scene berikutnya; gestur baru terdeteksi dari jeda 200 ms atau delta yang membesar lagi |
| Sentuh | geser > min(90 px, 12% tinggi) atau cepat (> 0,45 px/ms); scene aktif ikut jari dengan hambatan lalu kembali bila tidak cukup; tarik-untuk-muat-ulang & pantulan dicegah |
| Keyboard | panah, PageUp/PageDown, spasi/Shift+spasi (bukan saat fokus di tombol/tautan), Home/End |
| Navigasi scene | `nav.pager` (kanan): tautan `#beranda`, `#masuk`, `#perolehan`, `aria-current` mengikuti |
| Tautan & CTA | "Masuk untuk memilih", "Lihat perolehan suara", "Gulir/Geser", "Kembali ke awal" |
| Fokus Tab | scene nonaktif tetap di pohon aksesibilitas; Tab ke elemen scene lain memindahkan scene |
| Alamat | `#id` scene ditulis dengan `replaceState`; muat ulang/tautan langsung membuka scene yang sama |

- Konten lebih tinggi dari layar (HP miring, zoom besar, laptop pendek):
  gestur yang dimulai saat isi masih bisa digulir menggulir isi scene dulu;
  scene berpindah hanya di batas atas/bawah dengan gestur baru.
- Scene pertama/terakhir: dorongan kecil (tanda tidak ada scene lagi).
- Pengumuman pembaca layar (`aria-live`) "Bagian 2 dari 3: Masuk sebagai"
  bila fokus tidak dipindahkan; bila dipicu tautan/keyboard, fokus pindah ke
  scene (judulnya dibacakan).
- Pesan flash (mis. token form kedaluwarsa) melayang di atas scene.

### 8.4 Transisi & efek

- Maju: scene lama `translate3d(0,-12%,-320px) rotateX(10deg) scale(.9)`,
  redup (lapisan hitam .72, opacity .35); scene baru meluncur dari bawah di
  atasnya. Mundur: kebalikannya. Isi (`.scene__inner`) tertinggal dari bidang
  scene (paralaks berlapis), lalu elemen `[data-reveal]` naik & memudar
  bertahap (85 ms per elemen). Judul hero naik dari "jendela" baris.
- Pointer (mouse): paralaks halus judul & pita pasangan; gambar portal
  mengikuti pointer.
- Hero: bidang titik canvas ("surat suara berlubang"): titik di sekitar
  pointer terdorong seperti kertas ditekan paku; klik/ketuk menimbulkan riak.
  Hanya berjalan saat hero aktif & tab terlihat, 30 fps saat diam, DPR maks 2.
- `prefers-reduced-motion`: tanpa paralaks/3D/bidang bergerak; scene berganti
  seketika; layar pembuka singkat tanpa animasi logo.

### 8.5 Pintu masuk Siswa / Guru

- Hanya konsep "WHO": judul kecil "Masuk sebagai", kata besar SISWA / GURU
  (ukuran mengikuti lebar portal sendiri, container query), nomor 01/02,
  tombol panah. Teks "NISN & kode unik" tidak lagi di beranda (tetap di
  halaman login).
- Tautan `aria-label="Masuk sebagai Siswa"` / `"… Guru"` ke `student/login`,
  `teacher/login` (pengguna yang sudah login diarahkan server ke dasbornya).
- Desktop: berdampingan, portal yang di-hover melebar & terang, yang lain
  meredup. HP tegak: bertumpuk setengah layar. HP miring: berdampingan.

### 8.6 Live count publik

- Data: `PublicLiveCount::build()` = `AnalyticsService::snapshot()` diproyeksi
  sempit (satu definisi angka: suara sah = `LOCKED` milik pemilih aktif).
  Pasangan yang ditampilkan = pasangan yang dihitung (aktif + nonaktif yang
  sudah punya suara sah) urut nomor, sehingga persentase berjumlah 100%.
  Tidak ada penanda "unggul" atau urutan peringkat.
- Foto: `hero` (foto berdua) bila ada; selain itu foto ketua & wakil
  berdampingan; foto yang belum diunggah = monogram inisial di atas warna
  aksen pasangan. Crop `object-position: 50% 30%` (wajah).
- Angka awal dirender server (tanpa JavaScript tetap benar); `Live` di
  `home.js` meminta `live-count` sesuai `poll.interval` (30 detik saat
  UPCOMING/ONGOING, 0 saat FINISHED/tanpa jadwal), dijeda saat tab tidak
  aktif dan langsung diperbarui saat aktif lagi; gagal jaringan = jeda
  bertambah (maks 120 detik); 404 = berhenti.
- Nilai berubah: angka bergulir 1,2 detik, batang/ meter bergeser, batang
  aksen berdenyut sekali; "Diperbarui …" ikut berganti.
- Status berubah (dibuka/ditutup/jadwal diubah admin) atau susunan pasangan
  berubah: halaman dimuat ulang agar server merender keadaan baru (scene tetap
  lewat `#perolehan`).
- Cache 5 detik (`liveCacheSeconds`) dengan kunci `home_live_<id>_<status>`:
  ratusan layar yang terbuka bersamaan tidak masing-masing menghitung ulang,
  dan perubahan status tidak pernah terbaca basi.
- Teks FINISHED: "Perolehan akhir" + "Pencoblosan sudah ditutup. Hasil resmi
  diumumkan oleh panitia pemilihan OSIS." Halaman hasil akhir (pemenang,
  confetti) tetap hanya di panel admin.

### 8.7 Panel status bergaya terminal

- Menempel di bawah, lebar penuh, `padding-bottom: env(safe-area-inset-bottom)`,
  latar gelap solid (tanpa blur/glass), font mono.
- Isi: lampu + status (teks, bukan warna saja), `pilketos:~$`, "Ditutup/
  Dibuka dalam" + odometer `03:12:45` (hari hanya bila >= 1 hari), kursor
  berkedip, jadwal ("1 Okt 2026 · 07.00–12.00 WIB"), garis progres 2 px di
  tepi atas. Countdown tetap `countdown.js` (jam server + `performance.now()`,
  muat ulang saat habis).
- HP tegak: dua baris (status + hitung mundur; jadwal). HP miring: satu baris
  ringkas. Tinggi panel diukur (`ResizeObserver`) menjadi ruang bawah setiap
  scene, jadi tombol & konten tidak pernah tertutup.

### 8.8 Responsif

| Layar | Komposisi |
|---|---|
| Desktop (>= 901 px) | hero rata kiri, "2026" bergaris tepi di kanan; portal berdampingan; live: pasangan kiri + "Suara masuk" kanan |
| <= 900 px | "Suara masuk" di bawah pasangan; prompt terminal disembunyikan |
| HP tegak (<= 767 px) | judul 16,5vw, CTA selebar layar, portal bertumpuk, live 3 kolom foto (4+ pasangan: 2 kolom), panel dua baris, navigasi scene berupa garis vertikal |
| Pendek & lanskap (tinggi <= 540 px) | navigasi 48 px, panel 40 px, lede/pita/petunjuk hero disembunyikan, portal berdampingan, live dua kolom (isi digulir di dalam scene bila perlu) |
| Layar lebar (>= 1400 × 820) | navigasi & logo lebih besar |

`min-height: 100svh` (tanpa JavaScript) dan panggung `position: fixed;
inset: 0` (mode scene) mengikuti bilah alamat HP; `viewport-fit=cover` +
`env(safe-area-inset-*)` untuk notch.

### 8.9 Mengganti gambar & pengaturan (`Config\Homepage`)

Semua dapat diubah di `app/Config/Homepage.php` atau `.env` (`homepage.<kunci>`).
Path relatif ke `public/`, simpan di `public/assets/img/...` (bukan
`public/uploads/`, yang hanya menyajikan foto kandidat).

| Kunci | Bawaan | Tampil di | Saran aset |
|---|---|---|---|
| `logoOnDark` | `assets/img/brand/logo-light.svg` | navigasi beranda, layar pembuka | logo terang, latar transparan (SVG/PNG/WebP); tampil tinggi 24–40 px dan lebar ±440 px |
| `logoOnLight` | `assets/img/brand/logo-dark.svg` | navigasi login, dasbor, voting | logo gelap |
| `introDesktop` | `assets/img/home/intro-desktop.svg` | layar pembuka, layar lanskap | 1920×1080 atau lebih, WebP/JPG < 300 KB; area tengah tertutup logo |
| `introMobile` | `assets/img/home/intro-mobile.svg` | layar pembuka, layar potret | 1080×1920 |
| `entryStudent`, `entryTeacher` | `assets/img/home/entry-*.svg` | portal Siswa/Guru | >= 1200×1500, subjek di tengah (dipotong cover; desktop kotak potret ±3:4, HP tegak kotak lebar ±2:1) |
| `entryStudentMobile`, `entryTeacherMobile` | `null` | portal di HP tegak | opsional, ±1200×600 |
| `publicLiveCount` | `true` | scene perolehan suara + `GET live-count` | `false` = hanya pasangan calon, tanpa angka |
| `livePollSeconds` | `30` | irama pembaruan | minimal 10; 0 = tanpa pembaruan otomatis |
| `liveCacheSeconds` | `5` | cache angka publik | 0 = tanpa cache |

Foto pasangan di scene perolehan suara berasal dari unggahan admin (Pasangan
calon: foto ketua, foto wakil, hero/foto berdua), tanpa pengaturan tambahan.

## 9. Testing

### 9.1 Test otomatis

`composer test` pada kode final:

| PHP | Database | Hasil |
|---|---|---|
| 8.4.19 | MariaDB 10.11.14 | **306 test, 2.303 assertion, OK** |
| 8.2.33 | MariaDB 10.11.14 | **306 test, 2.303 assertion, OK** |

Stage 1–4: 289 test; Stage 5: 17 test baru. MySQL 8 tidak diuji ulang karena
tidak ada perubahan schema maupun query (angka memakai `AnalyticsService`
yang sudah diuji di MySQL 8.0.46 pada Stage 4).

| Checklist 05 | Test |
|---|---|
| 1 navigasi | `HomepageTest::testNavigationShowsTheCentredLogoWithoutLoginButtons`, `testSignedInVoterKeepsDashboardAndLogoutAroundTheLogo`, `PublicLiveCountTest::testAssetSizeReadsSvgViewBoxAndRasterImages` |
| 2 scene | `testHomepageIsThreeFullScreenScenesWithSceneNavigation` (struktur; perilaku gerak: 9.2) |
| 3 pintu masuk | `testEntryPortalsKeepOnlyTheWhoWithReplaceableIllustrations` |
| 4 panel status | `testStatusDockReplacesTheOngoingSection` |
| 5 live count | `testLiveCountJsonIsANarrowProjectionOfTheAnalytics`, `testHomepageRendersTheSameNumbersAsTheEndpoint`, `testLivePairsFollowTheCandidatesThatAreCounted`, `testPollingFollowsTheElectionStatus`, `testLiveCountIsCachedBrieflyPerElectionStatus`, `testPublicLiveCountCanBeTurnedOffForTheCandidatesTeaser`, `testLiveCountEndpointIsGoneWhenTurnedOff`, `PublicLiveCountTest` |
| 6 layar pembuka | `testSplashScreenHasSeparateLandscapeAndPortraitBackgrounds` |
| 7 footer | `testFooterIsMinimalAndCentredOnEveryPage` |
| keamanan | `RoleMatrixTest` (route publik), `SecurityHardeningTest` (CSP + nonce di beranda, `no-store` & `nosniff` di `live-count`) |

### 9.2 Uji browser (dijalankan manual, bukan bagian `composer test`)

Chromium (Playwright 1.56) terhadap `php spark serve`, CSP aktif, data
development + suara contoh:

- **Interaksi** (30 pemeriksaan otomatis, semua lolos): satu takik roda = satu
  scene; 12 delta beruntun = satu scene; inersia trackpad 57 delta menurun =
  satu scene; panah/PageDown/Home/End; navigasi scene & `aria-current`; CTA
  hero memindahkan fokus ke scene; Tab masuk scene berikutnya memindahkan
  scene; tidak ada scene setelah yang terakhir; `#perolehan` langsung; muat
  ulang mempertahankan scene tanpa layar pembuka; dokumen tidak menggulir;
  geser sentuh naik/turun, geser kecil kembali ke tempat, geser di scene
  pertama tetap; HP miring 844×390: geser menggulir isi scene dulu, di batas
  atas pindah scene; tanpa error konsol/pelanggaran CSP.
- **Live count**: suara baru di database -> dalam satu irama angka bergulir
  (33,3/44,4/22,2 -> 30,0/40,0/30,0), partisipasi 60,0 -> 66,7%, "Diperbarui"
  berganti, tanpa muat ulang. Waktu selesai tercapai -> satu kali muat ulang
  ke keadaan FINISHED di scene yang sama, polling berhenti.
- **Tampilan**: 1440×900, 1366×768, 1280×800, 390×844 (sentuh), 844×390
  (miring), 360×760: tanpa overflow horizontal; scene muat satu layar kecuali
  HP miring (digulir di dalam scene); tiga mode foto (foto berdua, foto
  ketua+wakil, monogram); hover portal; urutan keluar layar pembuka (logo
  mendarat di navigasi, x tengah = 640 dari 1280).
- **Gerak dikurangi**: layar pembuka ±0,25 detik, scene berganti seketika,
  isi langsung terlihat. **Tanpa JavaScript**: tanpa layar pembuka, tiga
  bagian bergulir dengan scroll-snap, semua isi terlihat.
- **Halaman lain**: login (tamu: hanya logo di tengah), dasbor & surat suara
  (Dasbor/Keluar, logo tetap di tengah pada 360 px dan 1280 px), footer rata
  tengah.
- **axe-core 4.13** (WCAG 2.1 A/AA + best-practice): beranda (tiga scene),
  login, dasbor pada 1280 px dan 390 px: **0 pelanggaran** (dua temuan awal
  diperbaiki: nama tautan navigasi scene di HP, kontras inisial monogram
  kecil).

### 9.3 Belum diverifikasi

- Safari iOS, Firefox, dan HP Android fisik (uji memakai Chromium; fitur yang
  dipakai: container query, `overflow: clip`, `svh`, `env()`, ResizeObserver
  tersedia di browser modern tersebut).
- Trackpad Windows/macOS fisik (inersia diuji dengan simulasi delta).
- Pembaca layar sungguhan (NVDA/TalkBack/VoiceOver).
- Aset final sekolah (logo, ilustrasi, latar) dan beban ratusan layar beranda
  serentak di Wi-Fi sekolah.

## 10. Edge cases

| Kasus | Perilaku |
|---|---|
| JavaScript mati | tanpa layar pembuka; tiga bagian setinggi layar bergulir dengan scroll-snap; angka live count dari server |
| `home.js` gagal dimuat | layar pembuka & isi muncul sendiri setelah 8 detik; halaman tetap bisa digulir |
| Konten lebih tinggi dari layar | digulir di dalam scene dulu, lalu pindah scene |
| Muat ulang / tombol Back | scene yang sama (`#id`), layar pembuka tidak diulang di tab itu |
| Tab baru | layar pembuka sekali lagi (sessionStorage per tab) |
| sessionStorage diblokir | layar pembuka tampil setiap kali membuka beranda |
| Tab tidak aktif / HP tidur | polling & animasi berhenti; saat kembali langsung diperbarui |
| Jaringan putus | coba lagi dengan jeda berlipat (paling lama 120 detik), angka terakhir tetap tampil |
| Live count dimatikan | `GET live-count` 404, scene "Pasangan calon" tanpa angka, polling berhenti |
| Pasangan dinonaktifkan setelah menerima suara | tetap tampil (total 100%); tanpa suara: tidak tampil |
| Belum ada pasangan | "Pasangan calon belum ditetapkan." |
| Tanpa jadwal | panel "Belum Dijadwalkan · Jadwal belum tersedia", polling 0 |
| Pemilihan dibuka/ditutup saat beranda terbuka | countdown memuat ulang saat habis; perubahan jadwal oleh admin terdeteksi polling (<= 30 detik) |
| Logo pengganti rasio lain | `asset_size()` membaca ukuran intrinsik; tinggi tampil tetap |
| Ilustrasi/latar pengganti | `object-fit: cover` + lapisan gelap; teks tetap terbaca |
| Pengguna sudah login membuka beranda | Dasbor & Keluar di navigasi; portal mengarah ke dasbor lewat server |
| Pesan flash di beranda | melayang di bawah navigasi |

## 11. Known limitations

1. **Live count publik adalah keputusan kebijakan.** Stage 1–4 hanya membuka
   angka ke admin; Stage 5 menampilkan persentase per pasangan dan
   partisipasi ke publik atas permintaan pemilik proyek. Angka yang tampil
   selama pencoblosan dapat memengaruhi pemilih, dan saat sangat sepi
   perubahan kecil antar-pembaruan dapat menebak pilihan seseorang yang baru
   memilih. Bila aturan sekolah melarang, set `homepage.publicLiveCount =
   false` (beranda tetap lengkap tanpa angka).
2. Aset logo, ilustrasi, dan latar adalah placeholder SVG buatan Stage 5;
   ganti dengan aset final (bagian 8.9).
3. Cari di halaman (Ctrl+F) tidak dapat menggulir ke teks di scene lain;
   gunakan navigasi scene.
4. Renderer view CodeIgniter menyimpan data antar-render dalam satu proses
   (`Config\View::$saveData`); `Home::index()` mengosongkannya setelah
   render agar mode imersif tidak terbawa ke halaman lain di test/worker
   mode. Pola ini perlu diikuti bila ada halaman imersif baru.
5. `php spark serve` melayani satu request dalam satu waktu: layar pembuka
   bisa menunggu sampai batas 5,2 detik bila banyak tab dibuka bersamaan.
   Tidak terjadi di Apache/nginx.
6. Hal yang belum diverifikasi: bagian 9.3.

## 12. Handoff / catatan pemeliharaan

- Scene baru: tambahkan `section.scene[data-scene]` dengan `id`,
  `aria-labelledby`, dan `.scene__inner[data-scene-scroll]` di
  `home/index.php`, lalu tautannya di `nav.pager`. `home.js` tidak perlu
  diubah; elemen `[data-reveal]` otomatis muncul bertahap.
- Angka publik wajib lewat `PublicLiveCount` (proyeksi `AnalyticsService`);
  jangan menambah rincian (kelas, jenis kelamin, siswa/guru, jumlah suara,
  nama) ke `GET live-count`: rincian itu admin-only.
- Route publik baru wajib masuk `RoleMatrixTest::PUBLIC_ROUTES`.
- Skrip inline baru wajib `csp_script_nonce()`; beranda tetap memakai satu
  skrip inline di layout.
- Aset baru dipanggil lewat `asset_url()`; gambar beranda lewat
  `Config\Homepage`.
- Tetap tanpa gradient, tanpa emoji, hormati `prefers-reduced-motion`.
