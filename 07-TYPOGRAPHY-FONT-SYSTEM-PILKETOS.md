# PILKETOS — TYPOGRAPHY & FONT SYSTEM

## Catatan revisi (Stage 6, revisi 1)

| # | Sebelumnya | Sekarang | Alasan |
|---|---|---|---|
| 1 | Hero `clamp(4rem, 10vw, 9rem)` (Stage 5 malah sampai `12.5rem`) | Hero `clamp(2.5rem, 4.5vw, 4.5rem)` desktop, `clamp(2rem, 10vw, 3rem)` HP | teks hero terlalu besar dan kaku; hero kini mengutamakan visual |
| 2 | "SMP 1 Dawe" | **"SMP 1 DAWE"** di semua teks yang terlihat | permintaan pemilik proyek |
| 3 | Headline serif dengan tracking negatif | Judul hero sans semibold kapital dengan tracking positif | huruf kapital + tracking negatif + serif besar = kesan kaku |
| 4 | Rekomendasi DM Sans, alternatif Plus Jakarta Sans | Rekomendasi **Plus Jakarta Sans** (menggantikan Inter), DM Sans sebagai pembanding | karakter lebih dekat ke Indonesia; mendukung tujuan desain eksklusif |
| 5 | "Logo tetap SVG existing, identitas resmi" | Placeholder ≠ lambang resmi; aturan lockup acara | `logo-light/dark.svg` adalah placeholder Stage 5 |
| 6 | Tahun "2026" sebagai baris besar bergaris tepi | Tahun masuk ke kicker "PILKETOS 2026" | mengurangi teks di hero |

## 1. Tujuan

Tipografi harus terasa modern, hangat, dan tenang: tidak kaku, tidak korporat, tidak seperti "developer tool". Di beranda, **tipografi mendukung visual, bukan menjadi visual utama**.

Sistem Stage 5:
- Inter (UI, `--font-ui`);
- Newsreader (display, `--font-display`);
- JetBrains Mono (status/terminal, hanya di beranda).

## 2. Rekomendasi sistem

**Plus Jakarta Sans + Newsreader + JetBrains Mono**

| Family | Peran | Catatan |
|---|---|---|
| **Plus Jakarta Sans** (OFL, variable) | UI, tombol, navigasi, body, **judul hero kapital**, label portal | menggantikan Inter lewat token `--font-ui`, jadi tetap tiga family. Karakternya ramah, modern, dan dirancang di Indonesia |
| **Newsreader** (sudah ada) | judul section ("Masuk sebagai", "Perolehan suara"), persentase besar, nama kandidat | peran editorial dipertahankan, tetapi tidak lagi untuk judul hero |
| **JetBrains Mono** (sudah ada) | kicker hero, dock/status, stempel waktu, nomor 01/02, metadata kecil | hanya untuk informasi yang bersifat status/teknis |

Pembanding wajib sebelum diputuskan (screenshot, bukan specimen):
1. Plus Jakarta Sans + Newsreader (rekomendasi);
2. DM Sans + Newsreader;
3. Inter + Newsreader (paling konservatif, tanpa file baru).

Uji pada: hero, portal Siswa/Guru, live count, halaman login, surat suara, panel admin. Ukuran: 390×844, 360×760, 1366×768, 1440×900. Pilih satu sistem untuk **seluruh aplikasi** (token `--font-ui` dipakai semua halaman), bukan hanya beranda.

Aturan file:
- self-hosted `woff2` subset latin di `public/assets/fonts/`, beserta `OFL-*.txt`;
- file Inter dihapus bila diganti, jadi total family tetap tiga;
- `preload` hanya untuk font yang tampil di layar pertama.

## 3. Aturan penulisan nama sekolah

Nama sekolah **selalu ditulis kapital: `SMP 1 DAWE`**.

- Ditulis literal di sumber (`SMP 1 DAWE`), **bukan** lewat `text-transform: uppercase`, supaya konsisten di `<title>`, meta, `alt`, pembaca layar, salin-tempel, dan ekspor.
- Berlaku untuk: hero, `alt`/`title` logo, footer, `<title>` dan meta, halaman login, surat suara, panel admin, halaman error, teks default impor.
- Frasa lengkap: "Pemilihan Ketua & Wakil Ketua OSIS SMP 1 DAWE 2026".
- Nama pemilihan yang sudah tersimpan di database (`elections.nama`) adalah data admin dan tidak diubah otomatis. Nilai default untuk pemilihan baru ikut memakai "SMP 1 DAWE".

Titik perubahan untuk tahap implementasi (hasil `grep "SMP 1 Dawe"`):

| Area | File |
|---|---|
| Tampilan publik | `app/Views/home/index.php`, `app/Views/partials/header.php` (alt logo), `app/Views/partials/footer.php`, `app/Views/layouts/main.php` (title, meta), `app/Views/voting/partials/ballot.php` |
| Admin | `app/Views/layouts/admin.php`, `app/Views/admin/partials/sidebar.php`, `app/Views/admin/results/index.php`, `app/Views/admin/election/index.php` (default) |
| Error | `app/Views/errors/html/error_400.php`, `error_404.php`, `production.php` |
| Lain-lain | `app/Database/Seeds/ElectionSeeder.php`, `app/Services/Import/*Importer.php`, `app/Commands/SystemCheckCommand.php`, `public/assets/img/brand/logo-*.svg` (`title`, `aria-label`) |
| Test yang ikut diperbarui | `tests/feature/HomepageTest.php` (baris 102, 137), `AuthTest.php` (39), `AdminPanelTest.php` (179, 393, 636), `ResultsLockTest.php` (data uji, opsional) |

Komentar kepala file CSS/JS (`SMP 1 Dawe — …`) boleh ikut diganti agar seragam.

## 4. Huruf kapital yang tidak kaku

Teks kapital mudah terasa kaku bila besar, rapat, dan serif. Aturannya:

- kapital memakai **tracking positif**: judul `+0.02em` s.d. `+0.04em`; label kecil `+0.12em` s.d. `+0.18em`;
- line-height judul kapital `1.0–1.1` (bukan `0.88`);
- weight 600 untuk judul hero (bukan 700–800); gunakan ukuran dan ruang kosong untuk kontras;
- jangan memakai teks bergaris tepi (`-webkit-text-stroke`) untuk judul;
- jangan memakai huruf kapital untuk kalimat panjang atau body.

Kapital dipakai hanya untuk:
- nama sekolah "SMP 1 DAWE" dan "PILKETOS";
- label portal SISWA / GURU;
- kicker, label section kecil, status, nomor.

## 5. Hierarki per fungsi

| Fungsi | Font | Weight | Keterangan |
|---|---|---|---|
| Kicker hero "PILKETOS 2026" | JetBrains Mono | 500 | kapital, kecil |
| Judul hero "SMP 1 DAWE" | Plus Jakarta Sans | 600 | kapital, tracking positif |
| Baris pendukung hero (opsional) | Plus Jakarta Sans | 400 | satu baris |
| CTA | Plus Jakarta Sans | 600 | kalimat biasa ("Masuk untuk memilih") |
| Judul section | Newsreader | 400–500 | kalimat biasa |
| Label portal SISWA/GURU | Plus Jakarta Sans | 600 | kapital |
| Nama kandidat | Newsreader (ketua) / Plus Jakarta Sans (wakil) | 500 / 400 | sama untuk semua pasangan |
| Persentase | Newsreader | 400 | `font-variant-numeric: tabular-nums` |
| Body | Plus Jakarta Sans | 400 | |
| Navigasi | Plus Jakarta Sans | 500 | |
| Dock, stempel waktu, metadata | JetBrains Mono | 400–500 | |

## 6. Isi teks hero

```text
PILKETOS 2026
SMP 1 DAWE
[ Masuk untuk memilih → ]   Lihat perolehan suara
```

- `h1` untuk pembaca layar: "Pemilihan Ketua & Wakil Ketua OSIS" (tersembunyi secara visual) + "SMP 1 DAWE" + "2026".
- Paragraf lede Stage 5 ("Satu pemilih, satu suara. Kenali pasangan calon…") dihapus. Bila perlu baris pendukung, maksimal satu baris ≤ 60 karakter.

## 7. Skala ukuran

### Hero

| Elemen | Stage 5 (kode) | Dokumen lama | **Stage 6** |
|---|---|---|---|
| Judul "SMP 1 DAWE" desktop | `clamp(3.2rem, min(13.5vw, 21vh), 12.5rem)` (±200 px) | `clamp(4rem, 10vw, 9rem)` (±144 px) | **`clamp(2.5rem, 4.5vw, 4.5rem)`** (40–72 px) |
| Judul HP tegak | `clamp(3.2rem, 16.5vw, 5.4rem)` | `clamp(3rem, 16vw, 6rem)` | **`clamp(2rem, 10vw, 3rem)`** (32–48 px) |
| Judul HP miring / layar pendek | `clamp(2.4rem, min(10vw, 17vh), 6rem)` | — | **`clamp(1.75rem, 7vh, 2.5rem)`** |
| Tahun "2026" | baris tersendiri, outline, rata kanan | baris tersendiri | **masuk kicker** |
| Kicker | 0.75rem, tracking 0.22em | — | **0.75–0.8125rem, tracking 0.18em** |
| Baris pendukung | `clamp(1rem, 1.6vw, 1.1875rem)`, ±2–3 baris | 1–1.25rem | **1rem–1.125rem, maks 1 baris** |
| Tracking judul | `-0.045em` | normal/negatif | **`+0.02em` s.d. `+0.04em`** |
| Line-height judul | 0.88 | — | **1.0–1.05** |

### Scene lain

| Elemen | Desktop | HP |
|---|---|---|
| Judul section | 2–3.5rem | 1.75–2.5rem |
| Label portal SISWA/GURU | `clamp(2.4rem, min(16cqi, 14vh), 6rem)` | `clamp(2rem, 14cqi, 3.5rem)` |
| Persentase pasangan | 2.5–4rem | 1.75–2.5rem |
| Body | 16–18px, line-height 1.5–1.65 | 16px |
| Metadata | 11–14px | 11–13px |

Label portal diperkecil dari Stage 5 (`10rem`) agar gambar portal lebih terlihat.

## 8. Letter spacing

- Kalimat biasa dan judul section: normal atau sedikit negatif (`-0.01em`).
- Kapital: positif (lihat §4).
- Hindari tracking ekstrem (> `0.22em`) di seluruh UI.

## 9. Batasan jumlah font

Maksimal tiga family. Jangan menambahkan:
- display serif kedua;
- condensed font untuk headline;
- monospace lain untuk angka;
- font ikon (ikon tetap SVG `icon()`).

## 10. Logo dan tipografi

| Aset | Aturan tipografi |
|---|---|
| **Lambang resmi SMP 1 DAWE** | apa adanya; tulisan di dalam lambang tidak diset ulang |
| **Wordmark "PILKETOS 2026"** pada lockup acara | diset dengan Plus Jakarta Sans (atau font terpilih) 600–700, kapital, tracking positif, lalu **di-outline menjadi path** di SVG agar tidak bergantung pada font yang terpasang |
| Baris "SMP 1 DAWE" pada lockup (bila ada) | kapital, JetBrains Mono atau Plus Jakarta Sans 500, lebih kecil dari wordmark |

- Jangan memakai huruf hasil AI image generator di logo. Hasil AI hampir selalu salah eja atau bentuknya tidak konsisten.
- `logo-light.svg` / `logo-dark.svg` Stage 5 adalah **placeholder** dan akan diganti oleh lockup acara (dokumen 08, B02/B03). Nama file boleh tetap agar `Config\Homepage` tidak berubah.

## 11. Keputusan desain

1. Jalankan perbandingan screenshot (§2) dengan ukuran hero baru (§7).
2. Bila tidak ada keberatan, pakai Plus Jakarta Sans + Newsreader + JetBrains Mono.
3. Terapkan aturan kapital "SMP 1 DAWE" (§3) bersamaan dengan pergantian font, dalam satu commit terpisah dari perubahan visual lain agar mudah ditinjau.
