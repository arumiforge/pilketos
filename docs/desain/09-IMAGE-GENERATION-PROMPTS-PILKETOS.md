# PILKETOS — IMAGE GENERATION PROMPTS & SVG SPECIFICATION

## Catatan revisi (Stage 6, revisi 1)

| # | Sebelumnya | Sekarang | Alasan |
|---|---|---|---|
| 1 | Prompt generik ("Central Java school environment", "ballot on a desk") | Blok **PLACE** lereng Muria (kabut, kopi, parijoto, sekolah beratap genteng) di setiap prompt | aset harus eksklusif untuk SMP 1 DAWE |
| 2 | Tiap prompt berdiri sendiri | Tiga blok bersama (PLACE, STYLE, NEGATIVE) + alur kerja "master dulu" | konsistensi antar gambar |
| 3 | Siswa/guru foto realistis dekat | Gaya **hibrida**: lingkungan realistis, orang kecil/dari belakang/siluet | menghindari wajah AI yang terasa palsu |
| 4 | Seragam generik | Seragam putih–biru tua, siswi berjilbab, guru berbatik/seragam dinas | sesuai konteks sekolah negeri di Kudus dan nilai "Religius" dalam PRIMA |
| 5 | "Jangan pernah membuat logo dengan AI" | Lambang resmi: tidak pernah. Tanda acara: **boleh dieksplorasi** (L01–L03), lalu wajib digambar ulang sebagai SVG | pemilik proyek membuka opsi AI untuk logo bila hasilnya lebih baik |
| 6 | Prompt untuk SVG (registration, perforation, scroll cue, nail) | SVG ditulis sebagai kode (S01–S04); scroll cue dan paku tidak dibuat karena sudah ada | generator AI tidak menghasilkan SVG bersih |
| 7 | Portal 4:3, ruang kosong di kiri | Portal 1:1 dan 3:2, subjek di tengah, sepertiga bawah tenang | mengikuti tata letak portal nyata (label di bawah-kiri) |
| 8 | Splash: meja + kertas suara | Splash: **pemandangan hero berkabut pekat** (dibuat dari A03) | transisi "kabut tersingkap" |
| 9 | Hero tidak punya aset raster | A03/A04 latar hero + A05 lapisan depan | hero mengutamakan visual |

ID aset sama dengan dokumen 08: **B** brand, **A** raster AI, **S** SVG kode, **L** eksplorasi tanda acara.

## 1. Aturan umum

AI image generator **boleh** dipakai untuk:
- latar layar pembuka, latar hero, lapisan depan, portal (A01–A09);
- eksplorasi konsep tanda acara (L01–L03), bukan hasil akhir.

AI image generator **tidak boleh** dipakai untuk:
- lambang resmi SMP 1 DAWE (B01): tidak dibuat ulang, tidak diubah, **tidak diunggah ke generator**;
- teks apa pun yang terbaca (nama sekolah, "PILKETOS", angka, slogan);
- foto atau wajah kandidat;
- UI, tombol, chart, navigasi, screenshot website;
- wajah siswa/guru dari dekat.

Semua teks, logo, angka, nama kandidat, CTA, dan status tetap HTML/SVG asli dari aplikasi.

**Jangan menulis nama sekolah di prompt.** Generator cenderung menaruh papan nama atau tulisan acak bila nama sekolah disebut. Tempat dijelaskan lewat bentang alam, tumbuhan, dan arsitektur saja.

## 2. Alur kerja produksi

1. **Buat A03 (hero desktop) lebih dulu.** Hasilkan 8–16 variasi, pilih satu yang paling "Dawe", lalu jadikan A03 sebagai **master** gaya dan warna.
2. **Turunkan aset lain dari master:**
   - A01 (layar pembuka lanskap) = A03 + kabut pekat, **dengan bingkai yang sama persis** (§4.3);
   - A04 (hero HP) = komposisi potret baru, dengan A03 sebagai referensi gaya;
   - A02 = A04 + kabut pekat;
   - A06–A09 (portal) = dengan A03 sebagai referensi gaya.
3. **Samakan color grade** semua raster dengan satu preset (Lightroom/Snapseed/Photopea): saturasi rendah, bayangan hangat netral, highlight off-white.
4. **Bersihkan:** hapus artefak mirip tulisan, periksa tangan dan jumlah orang, hapus logo pada tas/seragam.
5. **Cek crop** dengan zona aman dokumen 08 §5 (tempel kotak zona di atas gambar).
6. **Ekspor WebP** kualitas 75–80 (squoosh.app atau `cwebp -q 78`), sesuai anggaran di dokumen 08 §8.1.
7. **Catat** di log produksi (§10).

Tips umum per tool:
- atur rasio di tool (mis. `--ar 16:9`, `--ar 9:16`, `--ar 1:1`, `--ar 3:2`, atau opsi *aspect ratio*), lalu hasilkan lebih besar dari target dan perkecil;
- untuk konsistensi gaya pakai fitur *style reference* / *image prompt* dengan A03;
- untuk A01/A02 pakai *image-to-image* (kekuatan ±0,35–0,55) atau edit manual (§4.3);
- simpan *seed* bila tool menyediakannya.

## 3. Blok bersama

Tempel blok berikut (bahasa Inggris) di setiap prompt A01–A09 sesuai petunjuk.

### 3.1 PLACE BLOCK

```text
PLACE: the cool southern slopes of Mount Muria in Central Java, Indonesia.
Early highland morning, soft mist drifting between layered forested ridges.
Local vegetation: coffee shrubs with glossy dark-green leaves, parijoto shrubs
(Medinilla speciosa) with hanging clusters of small glossy purple-red berries,
tall tropical trees, clean green school grounds with trimmed grass.
Architecture: a simple single-storey Indonesian public junior high school with
terracotta clay-tile roofs, light painted walls and open-air corridors with
square columns.
```

Bila panitia punya foto asli gedung, ganti kalimat *Architecture* dengan deskripsi dari foto (bentuk atap, warna dinding, pohon di halaman).

### 3.2 STYLE BLOCK (hibrida)

```text
STYLE: photorealistic documentary photography, full-frame camera, 35mm lens,
natural soft morning light from the east, gentle atmospheric haze, muted
low-saturation color grade (mist grey, moss and coffee-leaf green,
volcanic-soil brown, warm off-white highlights), subtle fine film grain,
calm, quiet and premium, believable and local, not a travel postcard.
People, if present, are small in the frame, seen from a distance, from behind
or three-quarter back view, or in soft silhouette; no faces in close-up.
```

### 3.3 NEGATIVE BLOCK

```text
NEGATIVE: readable text, letters, numbers, signage, school name board,
banners, logos, watermark, flags as focal point, political or party symbols,
campaign posters, ballot boxes, cigarettes, tobacco, religious buildings or
tombs as focal point, close-up faces, identifiable people, distorted hands,
extra limbs, duplicated people, plastic skin, neon, glow, purple gradient,
futuristic HUD, UI elements, glassmorphism, fantasy architecture,
oversaturated tropical postcard look, heavy HDR, heavy bokeh, oversharpening,
lens flare
```

Bila tool tidak punya kolom *negative prompt*, tulis sebagai kalimat: "Avoid: …".

## 4. Hero & layar pembuka

### 4.1 A03: HERO DESKTOP (master)

File: `hero-desktop.webp` · Target: 1920×1080 (16:9) · Kunci: `heroDesktop`

```text
[PLACE BLOCK]
[STYLE BLOCK]

SUBJECT: election morning at a hillside school. View from the edge of the
school grounds looking toward Mount Muria.
- Foreground: dew on short grass and the edge of a paved schoolyard path,
  slightly out of focus.
- Midground: the long low terracotta roofline of the school and a row of
  trees, catching the first sunlight.
- Background: three or four layered mountain ridges fading into mist; the
  highest ridge slightly right of center.

COMPOSITION (16:9 landscape):
- the lower-left area (from 6% to 50% of the width, from 55% to 90% of the
  height) is calm, darker and low in detail;
- the main visual interest sits in the right half and the upper half;
- the top 10% is calm mist or sky; the bottom 8% is calm and dark;
- the right edge has no important detail;
- keep all important elements inside the central 76% of the frame.

MOOD: fresh, quiet anticipation, hopeful, local, dignified.

[NEGATIVE BLOCK]
```

### 4.2 A04: HERO HP

File: `hero-mobile.webp` · Target: 1080×1920 (9:16) · Kunci: `heroMobile`

```text
[PLACE BLOCK]
[STYLE BLOCK]

SUBJECT: the same place and the same morning as the reference image,
recomposed vertically (use the reference only for style, light and color).

COMPOSITION (9:16 portrait):
- upper half: layered ridges and drifting mist, the highest ridge near the
  center;
- around 50–60% of the height: the terracotta school roofline and treetops;
- lower 40%: calm, darker grass and path in soft shadow, low detail;
- the top 10% is calm;
- keep important elements away from the left and right 10%.

[NEGATIVE BLOCK]
```

### 4.3 A01: LAYAR PEMBUKA LANSKAP (dari A03)

File: `intro-desktop.webp` · Target: 1920×1080 · Kunci: `introDesktop`

A01 **wajib** memakai bingkai yang sama persis dengan A03, supaya saat layar pembuka memudar, kabut terlihat "tersingkap" di tempat, tidak bergeser.

**Cara A (paling presisi, disarankan): edit manual**
1. Buka A03 di editor foto (Photopea/Photoshop/GIMP).
2. Tambahkan lapisan kabut: isi abu-abu hangat (`#C9CAC4`) dengan opacity 55–70%, lalu hapus sebagian dengan kuas lembut agar atap sekolah dan pohon terdekat tampak samar.
3. Turunkan kontras dan saturasi, lalu gelapkan keseluruhan ±15% agar logo putih terbaca.
4. Pastikan area 30–70% lebar × 35–65% tinggi rata dan tenang.

**Cara B: image-to-image dengan A03 sebagai input**

```text
Use the input image as the exact composition: same viewpoint, same framing,
same horizon line.
Change only the atmosphere: a dense pre-dawn fog fills the valley; the
mountain ridges almost disappear; only faint soft silhouettes of the school
roofline and the nearest trees remain; very low contrast, soft even light,
slightly darker overall.
The central area (30–70% of the width, 35–65% of the height) is smooth, even
and calm for a white logo.

[STYLE BLOCK]
[NEGATIVE BLOCK]
```

Kekuatan ±0,35–0,55. Bandingkan dengan A03 dalam dua lapisan (toggle); garis atap tidak boleh bergeser.

### 4.4 A02: LAYAR PEMBUKA POTRET (dari A04)

File: `intro-mobile.webp` · Target: 1080×1920 · Kunci: `introMobile`

Sama seperti §4.3, dengan A04 sebagai sumber. Area tenang untuk logo: 20–80% lebar × 38–62% tinggi.

### 4.5 A05: LAPISAN DEPAN (opsional)

File: `hero-foreground.webp` (alpha) · Target: 1400×1400 · Kunci: `heroForeground`

```text
Photorealistic close-up of a single parijoto branch (Medinilla speciosa) with
two hanging clusters of small glossy purple-red berries and a few glossy
dark-green coffee leaves, entering the frame from the lower right corner,
isolated on a plain flat mid-grey background, soft diffused morning light
matching a misty highland morning, crisp clean edges, no shadow on the
background, no text.

[NEGATIVE BLOCK]
```

Lalu hapus latar (fitur *remove background* di editor, atau `rembg`), rapikan tepi agar tanpa halo, dan ekspor WebP dengan alpha (≤ 150 KB). Gelapkan sedikit agar serasi dengan A03, karena objek ini tampil di sudut yang lebih gelap.

## 5. Portal Siswa & Guru

Label SISWA/GURU (HTML) ada di **bawah-kiri**, nomor 01/02 di **atas-kiri**, tombol panah di **bawah-kanan**. Subjek harus di tengah; sepertiga bawah tenang.

### 5.1 A06: PORTAL SISWA (desktop)

File: `entry-student.webp` · Target: 1600×1600 (1:1) · Kunci: `entryStudent`

```text
[PLACE BLOCK]
[STYLE BLOCK]

SUBJECT: an open-air corridor of the same school in the morning; square
columns casting soft diagonal shadows; beyond the corridor opening, misty
green trees and a hint of the mountain ridge.
Two or three junior high school students, 13 to 15 years old, walk away from
the camera toward the bright end of the corridor, seen from behind or
three-quarter back view, faces not visible:
- girls wear white hijab, white long-sleeve school shirts and long navy-blue
  skirts;
- boys wear white short-sleeve school shirts and navy-blue trousers;
- one carries a plain backpack without any logo.

COMPOSITION (1:1 square):
- the students stand in the center area (25–75% of the width and the
  height), at mid distance;
- the bottom 35% is calm: floor tiles and soft shadow, low detail;
- the top-left corner is calm;
- only the corridor environment touches the outer 12% of the frame.

MOOD: calm, attentive, a sense of purpose and belonging.

[NEGATIVE BLOCK]
```

### 5.2 A07: PORTAL SISWA (HP tegak)

File: `entry-student-mobile.webp` · Target: 1200×800 (3:2) · Kunci: `entryStudentMobile`

```text
[PLACE BLOCK]
[STYLE BLOCK]

SUBJECT: the same corridor and the same students as the reference image,
recomposed for a wider frame; the corridor recedes toward the center.

COMPOSITION (3:2 landscape):
- the students are small, in the center (25–75% of the width, 15–70% of the
  height);
- the lower 35% is calm floor and shadow, especially on the left 60%;
- the right-bottom corner is calm;
- nothing important in the outer 10%.

[NEGATIVE BLOCK]
```

### 5.3 A08: PORTAL GURU (desktop)

File: `entry-teacher.webp` · Target: 1600×1600 (1:1) · Kunci: `entryTeacher`

Pilih satu varian guru, sesuaikan dengan panitia.

```text
[PLACE BLOCK]
[STYLE BLOCK]

SUBJECT: the doorway of a classroom along the same open-air corridor, soft
morning light, the same columns and misty trees as the student image.
One Indonesian junior high school teacher stands at mid distance near the
doorway, seen from the side or three-quarter back, holding a plain folder and
looking along the corridor. Variant A: a woman wearing a hijab and a modest
batik blouse. Variant B: a man wearing a long-sleeve batik shirt. The face is
not visible or very small and soft.

COMPOSITION (1:1 square):
- the teacher stands in the center area (25–75% of the width and the height);
- the bottom 35% is calm floor and shadow;
- the top-left corner is calm;
- only the environment touches the outer 12%.

MOOD: approachable, calm, responsible; not corporate, not a stock photo.

[NEGATIVE BLOCK]
```

### 5.4 A09: PORTAL GURU (HP tegak)

File: `entry-teacher-mobile.webp` · Target: 1200×800 (3:2) · Kunci: `entryTeacherMobile`

Sama dengan §5.2, dengan subjek guru dari §5.3.

## 6. Pemeriksaan per gambar

- [ ] tidak ada tulisan, angka, papan nama, atau logo (termasuk di tas/seragam);
- [ ] tidak ada wajah dari dekat; orang tidak dapat dikenali;
- [ ] seragam sesuai (putih–biru tua, jilbab putih, batik/seragam dinas);
- [ ] jumlah tangan/kaki wajar, tidak ada orang ganda;
- [ ] tidak ada rokok, bendera, simbol partai, atau landmark religius sebagai fokus;
- [ ] zona aman dokumen 08 §5 terpenuhi;
- [ ] warna selaras dengan A03 (satu preset grade);
- [ ] ukuran file sesuai anggaran.

## 7. Tanda acara (L01–L03): eksplorasi dengan AI

### 7.1 Aturan

- Yang dieksplorasi hanya **tanda acara** Pilketos (simbol kecil), **bukan lambang resmi sekolah**.
- Prompt tanpa teks. Wordmark "PILKETOS 2026" diset dengan font asli (dokumen 07 §10).
- Hasil AI hanya **referensi ide**. Setelah konsep dipilih:
  1. gambar ulang sebagai SVG geometris di grid (lingkaran/garis dengan ukuran bulat), 1–2 warna, fill `currentColor`;
  2. uji di ukuran 24 px, 40 px, dan 160 px, di latar gelap dan terang;
  3. cek kemiripan dengan pencarian gambar terbalik (Google Lens). Hindari kemiripan dengan lambang OSIS nasional, lambang sekolah lain, atau logo komersial;
  4. susun lockup B02 (latar gelap) dan B03 (latar terang): `[B01 lambang resmi] | [tanda acara] PILKETOS 2026` (+ "SMP 1 DAWE" kecil bila perlu).
- Bila tidak ada konsep yang lebih baik dari lockup polos (lambang resmi + wordmark), **pakai lockup polos**.

### 7.2 Prompt

Negatif untuk semua L: `text, letters, numbers, gradient, 3D, glossy, shadow, mockup, photo, multiple logos, shield, star, torch, trophy, crown, checkmark`

**L01: Coblos di punggungan**

```text
Flat minimal vector logo mark, one solid color on a white background, no text.
Concept: a small round punched hole, like a ballot perforation, resting on a
simple line of two overlapping mountain ridges. Geometric, balanced, thick even
strokes, readable at 24 pixels, no gradient, no shading, no 3D, no letters.
```

**L02: Gugus parijoto sebagai suara**

```text
Flat minimal vector logo mark, one solid color on a white background, no text.
Concept: a hanging cluster of seven small circles shaped like a parijoto
berry cluster; one circle is an empty outline, like a punched hole, suggesting
one vote among many. Simple geometry, even spacing, readable at 24 pixels,
no gradient, no letters.
```

**L03: Lembar suara dan kabut**

```text
Flat minimal vector logo mark, maximum two solid colors on a white background,
no text. Concept: a square ballot sheet with a perforated top edge; inside it,
three soft horizontal lines like mist layers over a mountain, and one small
punched circle. Geometric, simple, readable at small size, no gradient,
no letters.
```

## 8. SVG kode (S01–S04)

Ditulis tangan (atau oleh asisten kode), bukan dari generator gambar. Semua: tanpa gradient, tanpa filter, tanpa teks, warna `currentColor`, ≤ 20 KB.

### S01: `hero-contour.svg`

- viewBox `0 0 1920 1080` (sama dengan A03), `preserveAspectRatio="xMidYMid slice"` agar sejajar dengan latar `object-fit: cover`.
- **Garis pertama = siluet punggungan tertinggi di A03**, dijiplak manual (path halus, 20–40 titik). Tambahkan 6–9 garis offset di atasnya dengan jarak membesar (12, 16, 22, 30 px, …), sehingga kontur benar-benar mengikuti gunung di gambar SMP 1 DAWE.
- `fill="none"`, `stroke="currentColor"`, `stroke-width="1"`, `vector-effect="non-scaling-stroke"`.
- Opacity diatur di CSS (0,08–0,14). Varian potret `0 0 1080 1920` dibuat dari A04 bila dipakai di HP.

### S02: `hero-perforation.svg`

- viewBox `0 0 480 8`, dipakai sebagai `mask`/`background` berulang secara horizontal.
- Lingkaran `r` 2,2 dengan jarak 10, pusat di `y = 4`. Variasikan jari-jari ±0,2 dan posisi ±0,3 pada beberapa lingkaran (pola tetap, bukan acak saat runtime) agar terasa cetak, bukan digital.

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 8" aria-hidden="true">
  <g fill="currentColor">
    <circle cx="5" cy="4" r="2.2"/><circle cx="15" cy="4.2" r="2.1"/>
    <circle cx="25" cy="4" r="2.3"/><!-- … s.d. cx="475" … -->
  </g>
</svg>
```

### S03: `hero-registration.svg` (opsional)

- viewBox `0 0 48 48`: lingkaran `r` 10 + garis silang 48 px, stroke 1, `currentColor`. Dipakai paling banyak dua kali (sudut blok teks hero).

### S04: `result-mark.svg` (opsional, halaman hasil admin)

- viewBox `0 0 240 240`: dua cincin konsentris, cincin perforasi (lingkaran kecil di keliling), dan satu titik "coblos" di tengah. Satu atau dua warna. Tanpa piala, mahkota, atau konfeti di dalam aset.

## 9. Brief foto kandidat (bukan AI)

Foto kandidat tidak dibuat dengan AI dan tidak diubah wajahnya.

```text
Portrait fotografi resmi kandidat Ketua/Wakil Ketua OSIS SMP 1 DAWE.

- latar polos abu-abu netral yang sama untuk semua kandidat (bukan warna tema);
- pencahayaan lembut dari depan 45 derajat, satu setup untuk semua kandidat;
- kamera setinggi mata, lensa 50–85 mm (setara), jarak sama;
- framing kepala hingga dada, ruang di atas kepala ±10% agar aman di-crop;
- rasio 3:4, minimal 1200×1600;
- ekspresi natural dan tenang;
- seragam sekolah rapi; jilbab rapi bagi siswi;
- tanpa atribut kampanye, slogan, atau poster;
- untuk foto berdua (hero pasangan): posisi dan jarak sama untuk semua pasangan.
```

Semua kandidat difoto pada sesi yang sama dengan pengaturan yang sama, supaya tampilan setara di scene SUARA.

## 10. Log produksi

Isi satu baris per aset final, supaya bisa diulang atau diganti nanti.

| ID | Tool & versi | Tanggal | Prompt (ringkas) / blok | Referensi | Seed | Edit manual | File final | Ukuran |
|---|---|---|---|---|---|---|---|---|
| A03 | | | PLACE+STYLE+NEG, §4.1 | — | | grade preset "Pagi Muria" | `hero-desktop.webp` | |
| A01 | | | §4.3 cara A | A03 | — | lapisan kabut | `intro-desktop.webp` | |
| … | | | | | | | | |

## 11. Checklist sebelum generate

- [ ] komposisi menjelaskan posisi objek dan zona tenang
- [ ] zona aman dokumen 08 §5 disebut dalam persen
- [ ] rasio target diatur di tool
- [ ] PLACE, STYLE, dan NEGATIVE ditempel
- [ ] nama sekolah **tidak** ditulis di prompt
- [ ] tidak meminta teks, logo, atau wajah dari dekat
- [ ] A03 master sudah dipilih sebelum aset turunan dibuat
- [ ] hasil bisa bekerja dengan overlay HTML (teks, tombol, dock)
