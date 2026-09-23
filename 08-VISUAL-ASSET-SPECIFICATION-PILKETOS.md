# PILKETOS — VISUAL ASSET SPECIFICATION

## Catatan revisi (Stage 6, revisi 1)

| # | Sebelumnya | Sekarang | Alasan |
|---|---|---|---|
| 1 | ID aset berbeda dengan dokumen 09 (A10 `hero-grid-light` vs `scroll-cue`, `voting-tool-fallback` vs `nail-fallback.svg`) | Satu sistem ID untuk 08 dan 09: **B** brand, **A** raster AI, **S** SVG kode, **L** eksplorasi tanda acara | penomoran lama tidak konsisten |
| 2 | Tidak ada aset latar hero | **A03/A04 latar hero** + **A05 lapisan depan** (baru), dengan kunci `Config\Homepage` baru | hero kini mengutamakan visual |
| 3 | Portal desktop 1600×1200 (4:3), "ruang kosong di kiri" | Portal desktop **1600×1600 (1:1)**, mobile **1200×800 (3:2)**, subjek di tengah, sepertiga bawah tenang | di kode Stage 5 kotak portal berubah antara ±0,75:1 dan ±1,9:1; label ada di bawah-kiri |
| 4 | Logo existing "dipertahankan sebagai identitas resmi" | B01 lambang resmi (dari sekolah) + B02/B03 lockup acara menggantikan placeholder | `logo-light/dark.svg` adalah placeholder Stage 5 |
| 5 | `hero-paper.svg` sebagai objek fokus | Diturunkan menjadi tekstur opsional; fokus pindah ke latar foto | hero bukan lagi "latar gelap + satu vector" |
| 6 | `scroll-cue.svg`, `nail-fallback.svg` dibuat baru | Dihapus dari daftar: ikon `chevron-down` dan renderer 2D di `ballot.js` sudah ada | tidak perlu membuat ulang yang sudah ada |
| 7 | Folder `svg/` baru opsional | Tetap di `public/assets/img/...` | konsisten dengan `Config\Homepage` dan `asset_url()` |
| 8 | AVIF utama | **WebP utama**, AVIF opsional | `Config\Homepage` memakai satu path per kunci; `.htaccess` sudah punya Expires WebP |
| 9 | Anggaran splash < 400 KB | Anggaran lebih ketat, dan latar splash tidak lagi `lazy` | layar pembuka kini selalu tampil |

## 1. Prinsip format

| Format | Dipakai untuk |
|---|---|
| **SVG (ditulis sebagai kode)** | lockup logo, ikon, kontur, perforasi, registration mark, result mark: semua geometri yang harus tajam, ringan, dan bisa diwarnai lewat `currentColor` |
| **WebP** | semua raster: latar layar pembuka, latar hero, lapisan depan transparan (WebP mendukung alpha), portal |
| **AVIF** | opsional, tahap berikutnya. Butuh `<source type="image/avif">` di view, `AddType image/avif .avif`, dan Expires di `.htaccess`/nginx |
| **PNG** | hanya lambang resmi bila sekolah tidak punya SVG, atau file perantara sebelum dikonversi ke WebP |
| **CSS** | garis, lapisan gelap solid, bayangan, posisi |

AI image generator hanya untuk raster (A01–A09) dan eksplorasi konsep tanda acara (L01–L03). SVG tidak dibuat dengan generator. Alasannya: geometri sederhana lebih bersih bila ditulis tangan, dan hasil "vector" dari AI biasanya berupa raster atau path yang berantakan.

## 2. Matriks aset

Path relatif ke `public/`. "Kunci" = properti di `app/Config/Homepage.php` (bisa diubah lewat `.env`: `homepage.<kunci>`).

### 2.1 Brand

| ID | File | Format | Ukuran | Kunci | Sumber | Keterangan |
|---|---|---|---|---|---|---|
| B01 | `assets/img/brand/lambang-smp1dawe.svg` (atau `.png`) | SVG / PNG ≥ 1024 px transparan | — | — | **file resmi dari sekolah** | tidak diubah; tidak dimasukkan ke AI |
| B02 | `assets/img/brand/logo-light.svg` | SVG | viewBox sesuai lockup | `logoOnDark` | dibuat Stage 6 | lockup untuk latar gelap: B01 + wordmark "PILKETOS 2026"; menggantikan isi placeholder |
| B03 | `assets/img/brand/logo-dark.svg` | SVG | sama dengan B02 | `logoOnLight` | dibuat Stage 6 | lockup untuk latar terang |

Bila B01 hanya tersedia sebagai raster:
1. minta file asli dari tata usaha (hasil desain, bukan foto/screenshot);
2. bila tetap raster, **tracing manual** menjadi SVG dengan warna persis, lalu cocokkan berdampingan dengan aslinya. Jangan memakai AI;
3. bila tracing tidak memungkinkan, sematkan PNG 2× di dalam lockup SVG (`<image>`), dengan ukuran tampil tinggi 24–40 px di navigasi.

### 2.2 Raster AI (gaya hibrida)

| ID | File | Target | Rasio | Kunci | Keterangan |
|---|---|---|---|---|---|
| A01 | `assets/img/home/intro-desktop.webp` | 1920×1080 | 16:9 | `introDesktop` | layar pembuka lanskap: **pemandangan A03 berkabut pekat** |
| A02 | `assets/img/home/intro-mobile.webp` | 1080×1920 | 9:16 | `introMobile` | layar pembuka potret: pemandangan A04 berkabut pekat |
| A03 | `assets/img/home/hero-desktop.webp` | 1920×1080 (maks 2560×1440 bila ≤ anggaran) | 16:9 | `heroDesktop` **(baru)** | **master**: lanskap pagi lereng Muria + atap sekolah |
| A04 | `assets/img/home/hero-mobile.webp` | 1080×1920 | 9:16 | `heroMobile` **(baru)** | versi potret A03 (komposisi sendiri, bukan crop) |
| A05 | `assets/img/home/hero-foreground.webp` | 1400×1400, alpha | 1:1 | `heroForeground` **(baru, nullable)** | ranting parijoto + daun kopi, latar transparan; opsional |
| A06 | `assets/img/home/entry-student.webp` | 1600×1600 | 1:1 | `entryStudent` | portal Siswa (desktop, tablet, HP miring) |
| A07 | `assets/img/home/entry-student-mobile.webp` | 1200×800 | 3:2 | `entryStudentMobile` | portal Siswa di HP tegak |
| A08 | `assets/img/home/entry-teacher.webp` | 1600×1600 | 1:1 | `entryTeacher` | portal Guru |
| A09 | `assets/img/home/entry-teacher-mobile.webp` | 1200×800 | 3:2 | `entryTeacherMobile` | portal Guru di HP tegak |

### 2.3 SVG kode

| ID | File | viewBox | Keterangan |
|---|---|---|---|
| S01 | `assets/img/home/hero-contour.svg` | `0 0 1920 1080` (+ varian `0 0 1080 1920` bila perlu) | garis kontur bergaya punggungan Muria; stroke `currentColor` 1–1.5 px, `vector-effect: non-scaling-stroke`, tanpa fill |
| S02 | `assets/img/home/hero-perforation.svg` | `0 0 480 8` (pola berulang) | garis sobek/perforasi: lingkaran kecil berulang dengan sedikit ketidakteraturan, fill `currentColor` |
| S03 | `assets/img/home/hero-registration.svg` | `0 0 48 48` | registration mark kecil (opsional, untuk sudut blok teks) |
| S04 | `assets/img/results/result-mark.svg` | `0 0 240 240` | stempel geometris hasil akhir (opsional, halaman hasil admin) |

Aturan SVG: tanpa gradient, tanpa filter blur, tanpa teks, ≤ 20 KB, `aria-hidden` saat dipakai dekoratif, dan warna dari CSS (`currentColor`).

### 2.4 Dihapus dari daftar lama

| Lama | Keputusan |
|---|---|
| `hero-paper.svg` | opsional: tekstur kertas tipis (opacity ≤ 0,06) bila dirasa perlu; bukan fokus |
| `hero-grid-light` | tidak dibuat; pola `grid.svg` sudah ada |
| `scroll-cue.svg` | tidak dibuat; `icon('chevron-down')` sudah ada |
| `nail-fallback.svg` / `voting-tool-fallback` | tidak dibuat; renderer 2D (SVG + CSS transform) sudah ada di `ballot.js` |

## 3. Aset existing yang dipertahankan

- `public/assets/img/patterns/dots.svg`, `grid.svg`, `hatch.svg`, `ticks.svg`
- `public/assets/js/home.js`, `nail-webgl.js`, `confetti.js`, `ballot.js`
- fallback 2D paku di `ballot.js`

Placeholder Stage 5 yang **diganti**: `brand/logo-light.svg`, `brand/logo-dark.svg` (isi diganti, nama tetap), `home/intro-desktop.svg`, `home/intro-mobile.svg`, `home/entry-student.svg`, `home/entry-teacher.svg` (diganti WebP; ubah path di `Config\Homepage`).

## 4. Kunci `Config\Homepage` baru (untuk tahap implementasi)

```php
/** Latar hero (scene 01) untuk layar lanskap. Disarankan 1920x1080 WebP. */
public string $heroDesktop = 'assets/img/home/hero-desktop.webp';

/** Latar hero untuk layar potret (HP & tablet tegak). 1080x1920. */
public string $heroMobile = 'assets/img/home/hero-mobile.webp';

/** Lapisan depan transparan (parijoto/daun kopi). null = tanpa lapisan depan. */
public ?string $heroForeground = null;
```

Pemilihan sumber memakai `<picture>` seperti layar pembuka: `(orientation: portrait)` → `heroMobile`, selain itu `heroDesktop`. Nilai default bisa berupa SVG placeholder sampai aset final tersedia, supaya test dan instalasi baru tidak rusak.

## 5. Zona aman (crop-safe)

Semua raster memakai `object-fit: cover`, jadi sisi gambar akan terpotong sesuai layar. Persentase di bawah dihitung dari tepi gambar sumber.

### 5.1 Hero desktop (A03) dan layar pembuka lanskap (A01)

Layar yang harus dilayani: 16:9, 16:10 (terpotong ±5% kiri-kanan), 4:3 (±12% kiri-kanan), 21:9 (±12% atas-bawah).

| Zona | Area (x × y) | Isi |
|---|---|---|
| Navigasi | seluruh lebar × 0–10% | langit/kabut tenang |
| Dock | seluruh lebar × 92–100% | tenang, gelap |
| Navigasi scene (pager) | 94–100% × seluruh tinggi | tanpa detail penting |
| Blok teks hero | 6–50% × 55–90% | tenang, lebih gelap, detail rendah |
| Fokus visual | 50–88% × 15–60% | punggungan tertinggi, atap sekolah |
| Lapisan depan (A05) | 75–100% × 65–100% | ruang untuk ranting parijoto |
| Harus selamat dari crop | 12–88% × 12–88% | semua yang penting |
| Logo layar pembuka (A01) | 30–70% × 35–65% | sangat tenang dan rata (kabut) |

A01 **harus** memakai bingkai yang sama persis dengan A03 (lihat dokumen 09 §4), agar transisi "kabut tersingkap" tidak bergeser.

### 5.2 Hero HP (A04) dan layar pembuka potret (A02)

Layar yang harus dilayani: 9:19,5 (HP modern, terpotong ±9% kiri-kanan), 9:16, 3:4 tablet (±12% atas-bawah).

| Zona | Area | Isi |
|---|---|---|
| Navigasi | 0–10% tinggi | tenang |
| Fokus | 12–50% tinggi, 10–90% lebar | punggungan, kabut |
| Transisi | 50–60% tinggi | atap sekolah/pepohonan |
| Teks + CTA | 60–90% tinggi, 10–90% lebar | tenang, lebih gelap |
| Dock | 90–100% tinggi | tenang |
| Logo layar pembuka (A02) | 20–80% lebar × 38–62% tinggi | sangat tenang |

### 5.3 Portal desktop (A06, A08): 1:1

Kotak portal di Stage 5 (`home.css`, `.entry__portals`/`.portal`) berubah rasio:
- desktop normal: ±1:1 s.d. ±1,25:1;
- saat portal lain di-hover: menyempit sampai ±0,75:1;
- saat di-hover: melebar sampai ±1,6:1;
- HP miring/layar pendek: ±1,9:1;
- gambar juga di-skala 1,1 dan bergeser mengikuti pointer.

| Zona | Area | Isi |
|---|---|---|
| Subjek | 25–75% × 25–75% | siswa/guru, detail penting |
| Nomor (01/02) | 0–25% × 0–15% | tenang |
| Label SISWA/GURU | 0–70% × 65–100% | tenang, lebih gelap (lantai, bayangan) |
| Tombol panah | 80–100% × 75–100% | tenang |
| Boleh terpotong | di luar 12–88% lebar dan 24–76% tinggi | hanya lingkungan |

### 5.4 Portal HP tegak (A07, A09): 3:2

Portal bertumpuk; rasio kotak ±1,2:1 (HP tinggi) s.d. ±2:1 (HP pendek). Ukur ulang di perangkat nyata saat implementasi.

| Zona | Area | Isi |
|---|---|---|
| Subjek | 25–75% × 15–70% | siswa/guru |
| Label | 0–60% × 65–100% | tenang |
| Tombol panah | 80–100% × 65–100% | tenang |
| Nomor | 0–20% × 0–20% | tenang |

### 5.5 Lapisan depan (A05)

- objek menempel ke tepi kanan-bawah bingkai (akan diposisikan keluar sebagian dari layar);
- tepi potongan bersih, tanpa halo putih;
- tanpa bayangan di latar (bayangan, bila perlu, dibuat di CSS).

## 6. Orang dalam gambar (gaya hibrida)

- Orang tampil **kecil, dari jauh, dari belakang, tiga-perempat belakang, atau siluet**. Tidak ada wajah AI dari dekat.
- Seragam otentik: kemeja putih + bawahan biru tua (SMP); siswi berjilbab putih dengan rok panjang; guru berbatik atau berseragam dinas sesuai hari pemilihan (konfirmasi dengan panitia).
- Gambar AI **tidak diklaim** sebagai foto siswa/guru SMP 1 DAWE. Alt tetap kosong (dekoratif), karena portal punya label teks sendiri.
- Bila memakai foto asli siswa/guru sebagai pengganti, wajib ada izin (orang tua untuk siswa).
- Foto kandidat **tidak pernah** dibuat AI (dokumen 09 §9).

## 7. Struktur folder

```text
public/assets/
├── fonts/
└── img/
    ├── brand/
    │   ├── lambang-smp1dawe.svg      B01 (dari sekolah)
    │   ├── logo-light.svg            B02 lockup, latar gelap
    │   └── logo-dark.svg             B03 lockup, latar terang
    ├── home/
    │   ├── intro-desktop.webp        A01
    │   ├── intro-mobile.webp         A02
    │   ├── hero-desktop.webp         A03
    │   ├── hero-mobile.webp          A04
    │   ├── hero-foreground.webp      A05 (opsional)
    │   ├── entry-student.webp        A06
    │   ├── entry-student-mobile.webp A07
    │   ├── entry-teacher.webp        A08
    │   ├── entry-teacher-mobile.webp A09
    │   ├── hero-contour.svg          S01
    │   ├── hero-perforation.svg      S02
    │   └── hero-registration.svg     S03 (opsional)
    ├── results/
    │   └── result-mark.svg           S04 (opsional)
    └── patterns/                     (existing)
```

Jangan menaruh aset beranda di `public/uploads/`: folder itu hanya menyajikan foto kandidat (`public/uploads/.htaccess`).

## 8. Performa

Layar pembuka kini **selalu tampil**, jadi aset layar pertama berada di jalur kritis setiap kunjungan.

### 8.1 Anggaran ukuran

| Aset | Batas |
|---|---|
| A01 layar pembuka lanskap | ≤ 250 KB |
| A02 layar pembuka potret | ≤ 200 KB |
| A03 hero desktop | ≤ 300 KB |
| A04 hero HP | ≤ 250 KB |
| A05 lapisan depan | ≤ 150 KB |
| A06/A08 portal desktop | ≤ 250 KB masing-masing |
| A07/A09 portal HP | ≤ 150 KB masing-masing |
| B02/B03 lockup | ≤ 60 KB (tergantung kerumitan lambang resmi) |
| S01–S04 | ≤ 20 KB masing-masing |
| Layar pertama total (latar pembuka + hero + lockup + font) di HP | ≤ ±900 KB |

Kabut dan langit terkompresi sangat baik. Ekspor WebP kualitas 75–80, lalu periksa area kabut; naikkan kualitas bila muncul pita warna (*banding*).

### 8.2 Prioritas pemuatan

| Urutan | Aset | Cara |
|---|---|---|
| 1 | lockup B02, latar pembuka A01/A02, font layar pertama | tanpa `loading="lazy"`; latar pembuka `fetchpriority="high"` (sebelumnya `lazy` karena bisa dilewati) |
| 2 | latar hero A03/A04 | eager; ditunggu oleh bilah muat karena langsung terlihat saat kabut tersingkap |
| 3 | A05, S01, S02 | eager, prioritas normal |
| 4 | portal A06–A09, foto pasangan | tidak lagi ditunggu bilah muat; dimuat setelah layar pertama (lihat dokumen 10 §10.4) |

### 8.3 Cache

- `asset_url()` sudah menambahkan `?v=<filemtime>`, jadi aset aman di-cache lama.
- `public/.htaccess`: Expires WebP saat ini 7 hari. Naikkan ke 30 hari, dan tambahkan AVIF bila dipakai.
- nginx (README §7.4): pastikan `webp`/`avif` masuk blok `expires 30d`.
- Kunjungan ulang mengambil aset dari cache sehingga bilah muat cepat penuh. Durasi minimum 1,5 detik tetap berlaku (dokumen 10).

Optimalkan berdasarkan pengukuran nyata (Lighthouse, Wi-Fi sekolah, HP kelas menengah), bukan angka di atas semata.
