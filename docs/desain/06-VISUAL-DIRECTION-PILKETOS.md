# PILKETOS — VISUAL DIRECTION & ART DIRECTION

## Catatan revisi (Stage 6, revisi 1)

Dokumen ini menggantikan versi sebelumnya. Perubahan utama:

| # | Sebelumnya | Sekarang | Alasan |
|---|---|---|---|
| 1 | Konsep "Editorial Civic Interface" (kertas, registration mark, perforasi) | "Surat Suara dari Lereng Muria": tempat menjadi dunia visual, surat suara menjadi bahasa interaksi | versi lama bisa dipakai sekolah mana pun; tidak ada unsur SMP 1 DAWE |
| 2 | Hierarki: headline → objek visual → CTA | Visual → CTA → judul singkat → status → dekorasi | beranda harus lebih kuat di visual daripada teks |
| 3 | Hero: "background gelap solid" + satu vector kertas | Hero: latar foto lereng Muria + lapisan aset + sedikit teks | perpaduan background dan gambar aset |
| 4 | Pita nomor urut pasangan (`hero__bands`) di hero | Dihapus | tidak perlu di scene pertama; kesan pertama beranda juga menjadi netral |
| 5 | Judul hero raksasa (Stage 5 sampai ±200 px, "2026" outline) | Judul kecil–sedang "SMP 1 DAWE", kicker "PILKETOS 2026" | teks sebelumnya terlalu besar dan kaku |
| 6 | Layar pembuka sekali per sesi tab | Layar pembuka **selalu** tampil setiap kali beranda dimuat | permintaan pemilik proyek |
| 7 | Logo placeholder disebut logo resmi | Lambang resmi sekolah + lockup acara PILKETOS 2026 | logo resmi sekolah sudah tersedia; placeholder Stage 5 bukan identitas resmi |
| 8 | Nama sekolah "SMP 1 Dawe" | **"SMP 1 DAWE"** (kapital) di semua teks yang terlihat | permintaan pemilik proyek |
| 9 | Inter dipertahankan (06) vs DM Sans (07) | Keputusan font hanya di dokumen 07 | menghilangkan konflik antar dokumen |

Dokumen terkait:
- `07-TYPOGRAPHY-FONT-SYSTEM-PILKETOS.md`: font, skala, aturan kapital;
- `08-VISUAL-ASSET-SPECIFICATION-PILKETOS.md`: daftar aset, ukuran, zona aman;
- `09-IMAGE-GENERATION-PROMPTS-PILKETOS.md`: prompt AI + spesifikasi SVG;
- `10-MOTION-AND-INTERACTION-SYSTEM-PILKETOS.md`: gerak, layar pembuka, transisi.

## 1. Tujuan

Dokumen ini menjadi dasar visual redesign beranda Pilketos setelah Stage 5. Fondasi teknis Stage 5 dipertahankan. Stage 6 fokus pada **identitas visual yang eksklusif untuk SMP 1 DAWE**: kualitas aset, tipografi, dan gerak.

Repositori acuan: `https://github.com/arumiforge/pilketos`

Kondisi Stage 5 yang dipertahankan:
- 3 scene layar penuh: Beranda → Masuk → Perolehan/Pasangan;
- logo di tengah navigasi;
- layar pembuka dengan artwork desktop/mobile terpisah;
- portal Siswa dan Guru;
- panel status (dock) di bawah layar;
- live count publik yang dapat dimatikan;
- canvas `field` interaktif di hero (dengan peran baru, lihat §7);
- WebGL paku + fallback 2D pada pengalaman voting;
- reduced-motion fallback;
- halaman bergulir biasa ketika JavaScript tidak tersedia.

Yang berubah dari Stage 5 ada di tabel catatan revisi dan di §7 (hero), §10 (layar pembuka), §11 (logo).

## 2. Konteks SMP 1 DAWE

Desain harus terasa milik sekolah ini, bukan template pemilihan OSIS.

### 2.1 Fakta yang dapat dipakai

| Hal | Isi | Sumber |
|---|---|---|
| Nama | SMP 1 DAWE (SMP NEGERI 1 DAWE), Kudus | situs sekolah |
| Alamat | Jl. Colo Km 11, Desa Lau, Kec. Dawe, Kab. Kudus, Jawa Tengah | Data Sekolah Kemdikbud |
| Berdiri | 1979 | profil sekolah |
| Nilai sekolah | **PRIMA: Pintar, Religius, Inovatif, Maju, Asri** | smp1dawekudus.sch.id |
| Bentang alam | lereng selatan Gunung Muria; jalan Kudus–Colo naik ke dataran tinggi yang sejuk dan berkabut di pagi hari | geografi Kec. Dawe |
| Tumbuhan khas | **parijoto** (*Medinilla*, buah kecil bergerombol berwarna ungu-merah; sumber daya genetik lokal Kudus, dibudidayakan di Colo/Dawe); **kopi Muria** (kebun di Colo sejak 1799) | Mongabay, Pemprov Jateng |
| Kerajinan | batik Colo | profil Desa Wisata Colo |

### 2.2 Cara menerjemahkan PRIMA ke visual

| Nilai | Terjemahan visual |
|---|---|
| Pintar | tipografi rapi, informasi jelas, tidak berlebihan |
| Religius | seragam siswa/guru yang otentik (siswi berjilbab), sikap tenang dan santun; **bukan** simbol atau landmark religius sebagai dekorasi |
| Inovatif | pengalaman seperti aplikasi: scene, surat suara digital, live count |
| Maju | gerak halus dan presisi, performa baik di HP |
| Asri | lanskap hijau berkabut, tanaman lokal, cahaya pagi, material alami |

### 2.3 Yang harus dikonfirmasi panitia sebelum produksi aset

- [ ] warna lambang resmi SMP 1 DAWE (untuk menurunkan palet, §5);
- [ ] file lambang resmi dalam kualitas terbaik (SVG, atau PNG ≥ 1024 px transparan);
- [ ] seragam yang dipakai pada hari pemilihan (putih–biru tua, batik, atau pramuka);
- [ ] foto asli gedung/halaman sekolah bila ada (acuan bentuk atap, koridor, pohon). Tanpa foto, prompt memakai deskripsi umum sekolah negeri Jawa Tengah;
- [ ] izin pemakaian lambang sekolah pada aplikasi pemilihan.

## 3. Konsep utama

### Surat Suara dari Lereng Muria

Pilketos bukan dasbor, bukan game, dan bukan SaaS futuristik. Dua lapisan membentuk identitasnya:

1. **Tempat (dunia visual):** pagi berkabut di lereng Muria, sekolah yang asri, parijoto dan daun kopi, udara sejuk. Tempat inilah yang membuat desain tidak bisa dipindah ke sekolah lain.
2. **Surat suara (bahasa interaksi):** perforasi = lubang coblos, garis sobek, nomor cetak, kertas matte. Unsur ini tetap ada, tetapi sebagai detail dan interaksi, bukan fokus utama.

Narasi visual:

`LERENG → PEMILIH → SUARA`

- Scene 01 (LERENG): "ini pemilihan di sekolah kita". Tempat dan suasana pagi hari pemilihan.
- Scene 02 (PEMILIH): "siapa yang masuk dan memilih", yaitu siswa dan guru di dunia yang sama.
- Scene 03 (SUARA): "partisipasi dan hasil". Foto pasangan dan angka.

Transisi khas: **kabut tersingkap**. Layar pembuka menampilkan pemandangan yang sama dengan hero, tetapi berkabut pekat. Saat layar pembuka selesai, kabut memudar dan hero yang jernih muncul (detail di dokumen 10).

## 4. Material

Material tempat:
- kabut pagi dan punggungan gunung berlapis;
- daun kopi yang mengkilap, gugus buah parijoto;
- rumput berembun, tanah vulkanik gelap;
- bangunan sekolah sederhana: genteng tanah liat, dinding bercat, koridor terbuka (sesuaikan dengan foto asli);
- kain seragam sekolah secara natural;
- motif batik Colo hanya sebagai tekstur sangat halus (opsional, opacity rendah, tidak ramai).

Material surat suara:
- kertas matte, serat halus;
- perforasi/garis sobek;
- nomor cetak;
- registration mark hanya sebagai detail kecil.

Jangan memakai:
- chrome sci-fi, hologram, kaca neon, metalik cyberpunk, gel UI, 3D glossy.

## 5. Warna: palet "Pagi Muria"

Palet dasar diambil dari suasana pagi di lereng Muria, dengan **kroma rendah** agar tidak bersaing dengan warna pasangan calon. Nilai di bawah adalah usulan awal. Bila warna lambang resmi sudah dikonfirmasi, sesuaikan nilainya lalu validasi ulang kontras (WCAG AA).

| Token usulan | Nama | Hex | Kontras | Pemakaian |
|---|---|---|---|---|
| `--night` | Malam Lereng | `#101312` | — | latar gelap utama (menggantikan `#0F0E13`) |
| `--night-2` | Lereng 2 | `#171B19` | — | permukaan dock, panel |
| `--night-3` | Lereng 3 | `#1F2421` | — | latar portal sebelum gambar dimuat |
| `--on-night` | Kabut | `#F2F1EC` | 16,5:1 di atas `--night` | teks utama |
| `--on-night-2` | Kabut 2 | `#B7B8B0` | 9,3:1 | teks sekunder |
| `--on-night-3` | Kabut 3 | `#8C8E86` | 5,6:1 | teks kecil sekunder |
| `--earth` | Tanah | `#2A2521` | — | bayangan hangat, lapisan layar pembuka |
| `--parijoto` | Parijoto | `#A8628F` | 4,3:1 (elemen non-teks) | aksen identitas: bilah muat layar pembuka, detail lockup acara |
| `--parijoto-ink` | Parijoto tua | `#5E2B4F` | 9,6:1 di atas `#F2F1EC` | aksen identitas di halaman terang |

Aturan:
- warna identitas berupa **solid**, tanpa gradient sebagai identitas;
- **parijoto dipakai sangat hemat**: maksimal satu elemen per layar, tidak pernah di scene 03;
- foto lanskap di-grade ke arah kabut (saturasi rendah) agar hijau alam tidak menjadi warna dominan.

### 5.1 Netralitas terhadap pasangan calon

Warna aksen pasangan berasal dari tema kandidat (`theme_accent`, diisi admin). Karena itu:
- scene 03 hanya memakai palet netral + warna aksen pasangan. Warna identitas sekolah tidak tampil di sana;
- sebelum pemilihan, bandingkan warna parijoto dan warna lambang sekolah dengan `theme_accent` setiap pasangan. Bila ada yang mirip (misalnya ΔE2000 < 20, atau sama-sama "ungu"/"hijau tua" di mata awam), ganti aksen identitas di beranda dengan Kabut (`#F2F1EC`). Contoh: data seed memakai `#2F5D50` (hijau tua), sehingga elemen UI hijau jenuh harus dihindari;
- hero dan layar pembuka tidak menampilkan warna, nomor, atau foto pasangan mana pun.

## 6. Hierarki visual

1. **Visual:** latar + lapisan aset (lanskap, parijoto, kontur);
2. **CTA utama:** "Masuk untuk memilih";
3. **Judul singkat:** "SMP 1 DAWE" + kicker "PILKETOS 2026";
4. **Status:** dock (jadwal, hitung mundur);
5. **Dekorasi:** perforasi, kontur, canvas `field`.

Teks tidak boleh lebih dominan daripada visual di scene 01. Dekorasi tidak boleh lebih menarik daripada CTA.

## 7. Scene 01: LERENG (hero)

### 7.1 Susunan lapisan (belakang → depan)

| # | Lapisan | Aset | Keterangan |
|---|---|---|---|
| 1 | Latar | A03 (desktop) / A04 (HP) | foto lanskap pagi lereng Muria + atap sekolah; layar penuh, `object-fit: cover` |
| 2 | Lapisan gelap | CSS solid | `rgba` solid rendah. Gambar sendiri sudah lebih gelap di area teks, jadi tidak perlu gradient CSS |
| 3 | Kontur | S01 `hero-contour.svg` | garis kontur bergaya di area langit/punggungan, opacity 0,08–0,14 |
| 4 | Lapisan depan | A05 `hero-foreground.webp` (opsional) | ranting parijoto + daun kopi di pojok kanan bawah, sebagian keluar bingkai, paralaks lebih kuat |
| 5 | Canvas `field` | ada (Stage 5) | peran baru: "embun/lubang coblos" yang hanya terlihat di sekitar pointer atau saat diketuk; opacity rendah; tidak menutupi fokus gambar |
| 6 | Teks + CTA | HTML | sedikit, di sepertiga bawah-kiri (desktop) atau bawah (HP) |
| 7 | Garis sobek | S02 `hero-perforation.svg` | satu garis perforasi tipis di atas CTA, penghubung ke konsep surat suara |

### 7.2 Isi teks (hanya ini)

```text
PILKETOS 2026                       ← kicker, mono kecil
SMP 1 DAWE                          ← judul, kapital, ukuran kecil–sedang
[ Masuk untuk memilih → ]  Lihat perolehan suara
```

- Judul `h1` tetap lengkap untuk pembaca layar: "Pemilihan Ketua & Wakil Ketua OSIS" (tersembunyi secara visual) + "SMP 1 DAWE" + "2026".
- **Dihapus dari Stage 5:** paragraf lede, tahun "2026" bergaris tepi, pita nomor urut pasangan (`hero__bands`).
- Opsional: satu baris pendukung ≤ 60 karakter (mis. "Satu pemilih, satu suara."). Bila dipakai, jangan lebih dari satu baris di desktop.
- Ukuran judul: lihat dokumen 07 (desktop maks ±72 px, HP maks ±48 px).

### 7.3 Komposisi

Desktop:
- blok teks di sepertiga bawah-kiri, rata kiri;
- fokus visual (punggungan tertinggi, atap sekolah) di kanan-tengah dan setengah atas;
- lapisan depan parijoto di pojok kanan bawah, tidak menutupi dock;
- area kanan ±6% bebas detail penting (navigasi scene).

HP tegak:
- punggungan dan kabut di setengah atas;
- blok teks + CTA selebar layar di bawah, tepat di atas dock;
- lapisan depan boleh dihilangkan bila membuat sesak.

HP miring / layar pendek:
- hanya kicker, judul, dan CTA; baris pendukung disembunyikan.

Zona aman per aset: dokumen 08 §5.

### 7.4 Gerak (ringkas)

Latar "mengendap" (skala 1,04 → 1), lapisan depan bergeser sedikit, kontur muncul, lalu teks. Paralaks pointer berlapis hanya di desktop. Detail di dokumen 10 §4–§6.

## 8. Scene 02: PEMILIH

Dua portal bukan kartu CRUD. Keduanya memakai dua gambar besar dari **dunia foto yang sama** dengan hero: pagi, kabut tipis, palet sama, sekolah yang sama.

Gaya **hibrida**: lingkungan realistis, **orang kecil/dari jauh/dari belakang/siluet**, tanpa wajah AI dari dekat.

SISWA:
- koridor terbuka atau halaman sekolah pagi hari;
- 2–3 siswa SMP berjalan menjauh atau tampak tiga-perempat dari belakang;
- seragam otentik: kemeja putih + bawahan biru tua; siswi berjilbab putih dengan rok panjang (sesuaikan dengan seragam hari pemilihan);
- tidak mempromosikan kandidat, tanpa poster/teks.

GURU:
- ambang pintu kelas atau selasar dengan arsitektur yang sama;
- satu guru dari jarak menengah, tampak samping/belakang;
- batik atau seragam dinas (sesuai hari pemilihan);
- tidak terasa seperti foto stok korporat.

Komposisi mengikuti tata letak portal Stage 5: label SISWA/GURU di **bawah-kiri**, nomor di **atas-kiri**, tombol panah di **bawah-kanan**. Subjek berada di tengah, sepertiga bawah tetap tenang (dokumen 08 §5.3).

Interaksi:
- desktop: portal di-hover melebar, gambar bergeser sedikit (8–12 px);
- HP: umpan balik tekan sederhana;
- tanpa efek neon.

## 9. Scene 03: SUARA

Fokus utama: foto pasangan dan angka. Tidak berubah dari Stage 5, kecuali:
- palet netral (§5) + warna aksen pasangan; tidak ada warna identitas sekolah;
- tidak ada penanda "unggul", tidak ada urutan peringkat;
- semua pasangan disajikan setara (ukuran foto, crop, tipografi);
- foto kandidat asli (bukan AI), mengikuti brief di dokumen 09 §9.

Komposisi:
- potret pasangan;
- nomor pasangan;
- nama;
- persentase besar (angka tabular);
- batang aksen solid;
- meter partisipasi sebagai data kedua.

Jangan membuat dashboard chart.

## 10. Layar pembuka (loading)

Layar pembuka adalah pintu masuk ke dunia visual Pilketos dan **selalu tampil setiap kali beranda dimuat penuh**.

| Hal | Keputusan |
|---|---|
| Kapan tampil | setiap pemuatan penuh beranda: tab baru, muat ulang, URL diketik, tautan langsung (`/#perolehan`), kembali dari halaman lain bila browser memuat ulang halaman |
| Durasi | sama setiap kali: minimal 1,5 detik, maksimal 5,2 detik (mengikuti aset yang benar-benar dimuat) |
| Lewati | tidak ada tombol lewati |
| Pengecualian | muat ulang otomatis oleh live count (status pemilihan berubah) tidak menampilkan layar pembuka |
| Tanpa JavaScript | tidak tampil (sama dengan Stage 5) |
| Gerak dikurangi | tetap tampil dengan durasi yang sama, statis, tanpa logo terbang |

Visual:
- latar A01 (lanskap) / A02 (potret): **pemandangan yang sama dengan hero, berkabut pekat**;
- lockup acara (B02) di tengah sebagai fokus;
- bilah muat tipis berwarna parijoto + angka mono di bawah logo;
- tanpa teks lain dan tanpa tulisan hasil AI.

Keluar: kabut memudar menjadi hero yang jernih sambil logo terbang ke navigasi (shared element, sudah ada di Stage 5). Detail teknis dan titik perubahan kode: dokumen 10 §10.

## 11. Logo dan identitas

Ada tiga hal yang berbeda:

| Aset | Status | Aturan |
|---|---|---|
| **Lambang resmi SMP 1 DAWE** (B01) | sudah dimiliki panitia | dipakai apa adanya: tidak digambar ulang oleh AI, tidak diubah warna/bentuknya, tidak dimasukkan ke generator AI |
| **Lockup acara** (B02 terang, B03 gelap) | dibuat Stage 6 | lambang resmi + wordmark "PILKETOS 2026" + (opsional) tanda acara; menggantikan isi placeholder `logo-light.svg` / `logo-dark.svg` |
| **Tanda acara** (opsional) | eksplorasi | konsep boleh dieksplorasi dengan AI (dokumen 09 §7), tetapi hasil akhir **wajib digambar ulang sebagai SVG** |

- Wordmark diset dengan font sistem (dokumen 07), bukan huruf hasil AI.
- Lockup harus terbaca di navigasi (tinggi 24–40 px) dan di layar pembuka.
- Nama sekolah pada lockup dan `alt`/`title`: "SMP 1 DAWE".

## 12. Prinsip gerak (ringkas)

Gerak menjelaskan perpindahan ruang dan keadaan, bukan hiasan. Rinciannya di dokumen 10:
- transisi scene 750–850 ms, lebih tenang dari Stage 5;
- isi 250–500 ms, stagger 50–80 ms;
- ambient sangat kecil, hanya di hero;
- reduced motion: tanpa paralaks/tilt/drift, scene berganti langsung, layar pembuka statis.

## 13. Yang harus dihindari

Umum:
- landing page AI generik, dasbor SaaS gelap, gradient ungu/biru neon, kartu kaca;
- terlalu banyak pill membulat, partikel melayang, objek 3D di setiap scene;
- foto stok korporat, teks UI futuristik palsu;
- terlalu banyak font family;
- latar animasi yang terus menarik mata.

Khusus konteks lokal:
- **kretek/rokok/tembakau** dalam bentuk apa pun (sekolah SMP);
- landmark religius (Menara Kudus, makam Sunan Muria, masjid) sebagai dekorasi atau fokus;
- klise "Indonesia" yang ramai (wayang, batik penuh, gunungan) sebagai hiasan;
- kotak suara kardus clipart, ikon paku clipart, jempol/centang generik;
- simbol partai politik, bendera sebagai fokus;
- wajah siswa hasil AI dari dekat, atau foto siswa asli tanpa izin;
- menampilkan warna/nomor/foto pasangan di hero atau layar pembuka.

## 14. Keputusan lintas dokumen

| Topik | Keputusan | Dokumen |
|---|---|---|
| Font | Plus Jakarta Sans + Newsreader + JetBrains Mono (divalidasi dengan screenshot) | 07 |
| Nama sekolah | "SMP 1 DAWE" kapital, ditulis literal | 07 §3 |
| Ukuran hero | desktop `clamp(2.5rem, 4.5vw, 4.5rem)`, HP `clamp(2rem, 10vw, 3rem)` | 07 §7 |
| ID aset | B = brand, A = raster AI, S = SVG kode, L = eksplorasi tanda acara | 08 §2, 09 |
| Folder aset | tetap `public/assets/img/...` (tanpa folder `svg/` baru) | 08 §7 |
| Format raster | WebP utama; AVIF opsional | 08 §1 |
| Layar pembuka | selalu tampil, durasi sama, tanpa lewati; kecuali muat ulang otomatis live count | 10 §10 |
| Pita pasangan hero | dihapus | 06 §7, 10 §4 |

## 15. Prinsip final

Pilketos SMP 1 DAWE harus terlihat modern karena **art direction, tempat, komposisi, material, tipografi, dan gerak**, bukan karena efek sebanyak mungkin.

Target rasa:

**asri + tenang + taktil + lokal + kontemporer + muda tanpa kekanak-kanakan.**

Uji sederhana: bila logo dan teks ditutup, orang yang mengenal Dawe tetap merasa "ini di lereng Muria", dan desainnya tidak bisa dipakai sekolah lain tanpa diubah total.

## Sumber konteks lokal

- Situs SMP 1 DAWE KUDUS (PRIMA): https://smp1dawekudus.sch.id/
- Data Sekolah Kemdikbud: https://sekolah.data.kemdikbud.go.id/index.php/chome/profil/b0ddfe19-bd99-e111-a711-1f63100944c6
- Profil sekolah: https://data-sekolah.zekolah.id/sekolah/smp-negeri-1-dawe-79396
- Parijoto lereng Muria (Mongabay): https://mongabay.co.id/short-article/2026/06/parijoto-buah-ungu-lereng-muria-yang-kini-jadi-sumber-penghidupan-dan-simbol-pelestarian/
- Kopi Muria (Pemprov Jateng): https://jatengprov.go.id/beritadaerah/kopi-muria-dari-rasa-hingga-potensi-wisata/
- Desa Wisata Colo (babad.id): https://www.babad.id/2026/06/profil-desa-wisata-colo-harmoni-religi-budaya-dan-pesona-alam-di-puncak-muria.html
