# PILKETOS — MOTION & INTERACTION SYSTEM

## Catatan revisi (Stage 6, revisi 1)

| # | Sebelumnya | Sekarang | Alasan |
|---|---|---|---|
| 1 | Layar pembuka sekali per sesi tab (Stage 5, `sessionStorage`) | **Selalu tampil** setiap kali beranda dimuat penuh, durasi sama, tanpa tombol lewati. Pengecualian: muat ulang otomatis live count | permintaan pemilik proyek |
| 2 | Keluar layar pembuka: latar & bilah memudar | **"Kabut tersingkap"**: latar kabut (A01/A02) memudar menjadi hero jernih (A03/A04) dengan bingkai yang sama | transisi khas yang hanya masuk akal di lereng Muria |
| 3 | Reveal hero: kicker → judul → **year** → **supporting text** → CTA → dekorasi | Reveal hero: **latar → lapisan depan → kontur** → kicker → judul → CTA | hero mengutamakan visual; tahun, lede, dan pita pasangan dihapus |
| 4 | Paralaks pita pasangan (`hero__bands`) | Dihapus; diganti paralaks berlapis latar/lapisan depan | pita dihapus dari hero |
| 5 | Nilai Stage 5 di kode lebih besar dari token (scene 1,05 dtk, `rotateX(10deg)`, portal ±26 px, angka 1,2 dtk) | Tabel "Stage 5 → Stage 6" per nilai (§3) | token sudah ada di dokumen lama, tetapi belum dipetakan ke kode |
| 6 | Reduced motion: "no long splash animation" | Layar pembuka tetap tampil dengan durasi sama, statis | konsisten dengan "selalu tampil, durasi sama" |

## 1. Prinsip

**Gerak menjelaskan hierarki, jarak, dan keadaan, bukan hiasan.**

Karakter gerak Pilketos SMP 1 DAWE:
- tenang, disengaja, presisi;
- taktil (kertas, kabut, embun);
- sedikit sinematik;
- tidak pernah memantul (no bounce) atau main-main.

Kata kunci internal: **kabut yang tersingkap, kertas yang bergerak, percaya diri yang tenang.**

## 2. Token

```text
--motion-fast:        180ms
--motion-ui:          260ms
--motion-reveal:      380ms
--motion-scene:       750ms
--motion-scene-max:   850ms
--motion-splash-min: 1500ms   (Intro.MIN)
--motion-splash-max: 5200ms   (Intro.MAX)
```

Easing:

```text
--ease-out:       cubic-bezier(0.16, 1, 0.3, 1)     (= --out Stage 5)
--ease-editorial: cubic-bezier(0.22, 1, 0.36, 1)
--ease-in-out:    cubic-bezier(0.65, 0, 0.35, 1)
```

Pakai token; jangan membuat durasi baru per elemen.

## 3. Pemetaan Stage 5 → Stage 6

| Hal | Stage 5 (kode) | Stage 6 |
|---|---|---|
| Transisi scene | 1,05 dtk | 750–850 ms |
| Scene keluar (maju) | `translate3d(0,-12%,-320px) rotateX(10deg) scale(.9)`, redup .72 | `translate3d(0,-6%,-120px) rotateX(3deg) scale(.94)` di desktop; **tanpa rotateX** di layar sentuh; redup ±.5 |
| Stagger `[data-reveal]` | 85 ms | 60–80 ms |
| Judul hero naik | 1,3 dtk | 600–700 ms |
| Paralaks judul hero | ±10 px / ±8 px | 0–4 px |
| Paralaks pita pasangan | ±18 px | **dihapus** (pita dihapus) |
| Gambar portal | `scale(1.1)` + ±26 px | `scale(1.06)` + 8–12 px |
| Portal melebar saat hover | `flex-grow` 1.32 / 0.78, 0,9 dtk | 1.2 / 0.85, 600–750 ms |
| Angka live bergulir | 1,2 dtk | 600–900 ms |
| Keluar layar pembuka | ±1 dtk | latar 500–800 ms, logo 650–900 ms |
| Failsafe CSS layar pembuka (`home.js` gagal) | 8 dtk | 6 dtk (tetap di atas `Intro.MAX`) |

## 4. Reveal hero (scene 01)

Urutan setelah layar pembuka mulai keluar, atau setelah masuk kembali ke scene 01:

```text
0ms      latar A03/A04 mengendap: scale 1.04 → 1.00, 1200ms, ease-editorial
120ms    lapisan depan A05 bergeser masuk 12–16px + opacity, 700ms
240ms    kontur S01 muncul: opacity 0 → target, 600ms
360ms    kicker "PILKETOS 2026"
440ms    judul "SMP 1 DAWE" naik dari jendela baris, 600–700ms
560ms    CTA + garis perforasi S02
```

- Tidak ada lagi langkah "year", "supporting text", atau pita pasangan.
- Bila baris pendukung opsional dipakai, masukkan di 500 ms.
- Tanpa bounce, tanpa overshoot.

## 5. Paralaks berlapis hero

Hanya untuk pointer halus (`(hover: hover) and (pointer: fine)`).

| Lapisan | Pergeseran maksimum |
|---|---|
| Latar A03 | 6 px |
| Kontur S01 | 8 px |
| Lapisan depan A05 | 14 px |
| Teks + CTA | 0–4 px |

- Smoothing tinggi (lerp ±0,08 per frame); berhenti saat hero tidak aktif atau tab tersembunyi.
- Latar sedikit diperbesar (`scale(1.02)`) agar tepi tidak terlihat saat bergeser.
- HP/tablet: tanpa paralaks; tidak memakai sensor orientasi perangkat.

## 6. Canvas `field` (ambient hero)

Canvas dipertahankan dengan peran baru: **embun / lubang coblos**.

- Titik hanya terlihat di sekitar pointer (radius ±140 px) dan saat diketuk (riak), bukan bidang titik penuh di seluruh hero.
- Opacity maksimum ±0,35; warna Kabut (`--on-night`).
- Tidak aktif di atas area fokus gambar (atau diredam di sana) dan tidak menutupi teks.
- Berjalan hanya saat hero aktif dan tab terlihat; 30 fps saat diam; DPR maks 2 (seperti Stage 5).
- Bukan particle system besar, star field, atau latar yang bergerak terus.

## 7. Transisi scene

Urutan maju:
1. scene aktif bergerak menjauh (lihat §3) dan meredup;
2. scene berikutnya naik ke posisi utama;
3. isi scene masuk setelah bidang scene mulai stabil (stagger 60–80 ms).

Mundur: kebalikannya. Rotasi 3D besar tidak dipakai; di layar sentuh `rotateX` = 0.

## 8. Portal (scene 02)

Desktop:
- portal aktif melebar (lihat §3), gambar `scale(1.03–1.06)`;
- label bergeser 4–8 px, panah bergeser 4 px;
- portal lain sedikit lebih gelap/redup; tanpa blur berat;
- gambar mengikuti pointer maksimum 8–12 px; wajah/subjek tidak boleh keluar crop (subjek ada di zona tengah, dokumen 08 §5.3).

HP:
- tanpa hover; tekan = `scale(0.985)`, 160–220 ms.

## 9. Live count (scene 03)

Saat angka berubah:
- angka bergulir 600–900 ms; perubahan kecil = gerak kecil;
- batang aksen menyesuaikan; foto tetap diam; layout tidak bergeser;
- tanpa kilatan putih;
- beberapa perubahan sekaligus tetap satu transisi.

## 10. Layar pembuka (loading)

### 10.1 Aturan tampil

| Situasi | Layar pembuka |
|---|---|
| Tab baru membuka beranda | **tampil** |
| Muat ulang (F5, tarik-untuk-muat-ulang) | **tampil** |
| URL diketik / tautan dari luar / bookmark | **tampil** |
| Tautan langsung ke scene (`/#masuk`, `/#perolehan`) | **tampil**, lalu mendarat di scene tersebut |
| Kembali (Back) dari login/halaman lain, halaman dimuat ulang oleh browser | **tampil** |
| Kembali (Back) dan browser memulihkan halaman dari *bfcache* | tidak tampil (JavaScript tidak dijalankan ulang; halaman kembali seperti saat ditinggalkan). Ini perilaku browser dan diterima |
| **Muat ulang otomatis oleh live count** (status pemilihan berubah) | **tidak tampil** (satu-satunya pengecualian) |
| Pindah scene di dalam beranda (gulir, geser, `#hash`) | tidak tampil (bukan pemuatan halaman) |
| JavaScript mati | tidak tampil (sama dengan Stage 5) |
| `prefers-reduced-motion: reduce` | **tampil**, durasi sama, statis (§10.3) |

### 10.2 Durasi

- **Sama setiap kali:** minimal 1500 ms, maksimal 5200 ms. Bilah muat mengikuti aset yang benar-benar dimuat, dipacu oleh waktu (mekanisme `Intro` Stage 5 dipertahankan).
- Tanpa tombol atau gestur lewati.
- Selama layar pembuka, scene terkunci (`deck.locked`), seperti Stage 5.

### 10.3 Urutan gerak

Masuk:

```text
0ms       latar kabut A01/A02 + lapisan gelap tampil
100ms     lockup B02 muncul (opacity + naik 8px, 900ms)
300ms     bilah muat (parijoto) + angka mono mulai terisi
```

Keluar (**kabut tersingkap**):

```text
t0        bilah & angka memudar (200ms)
t0        hero sudah dirender di bawah layar pembuka (latar A03/A04 terlihat samar)
t0        latar kabut A01/A02 memudar 500–800ms → hero jernih terlihat di tempat yang sama
t0        lockup terbang ke logo navigasi 650–900ms (shared element Stage 5)
t0+260ms  reveal hero dimulai (§4), sebelum layar pembuka benar-benar hilang
```

- Hanya satu logo yang terlihat pada satu waktu (logo navigasi disembunyikan selama `intro-active`, seperti Stage 5), jadi tidak ada crossfade dua logo.
- Agar kabut benar-benar "tersingkap", A01 harus sebingkai dengan A03 dan A02 sebingkai dengan A04 (dokumen 09 §4.3), dan keduanya memakai `object-fit`/`object-position` yang sama.

Gerak dikurangi:
- durasi minimal tetap 1500 ms;
- lockup langsung terlihat (tanpa naik), bilah muat terisi bertahap tanpa easing panjang;
- keluar = fade 200 ms, tanpa logo terbang dan tanpa animasi skala latar.

### 10.4 Yang ditunggu bilah muat

Supaya durasi tetap sama dan tidak tertahan oleh aset scene lain:
- **ditunggu:** lockup (navigasi + layar pembuka), latar layar pembuka, latar hero (A03/A04 yang terpilih oleh `<picture>`), font layar pertama, `DOMContentLoaded`;
- **tidak lagi ditunggu:** gambar portal dan foto pasangan (`[data-preload]` Stage 5). Keduanya dimuat setelah layar pertama siap, dengan prioritas normal.

### 10.5 Titik perubahan kode (untuk tahap implementasi)

| File | Perubahan |
|---|---|
| `app/Views/layouts/main.php` (baris 23) | hapus pembacaan `sessionStorage 'osis2026.intro'` → kelas `intro-seen`; ganti dengan pembacaan flag sekali-pakai `osis2026.skipIntro`: bila ada, tambah `intro-seen` **dan hapus flag** |
| `public/assets/js/home.js`: `Intro.prototype.finish` | jangan lagi menulis `INTRO_KEY` |
| `public/assets/js/home.js`: `Live.prototype.reload` | sebelum `location.reload()`, set `sessionStorage['osis2026.skipIntro'] = '1'` (dibungkus try/catch; bila diblokir, layar pembuka tampil. Itu diterima) |
| `public/assets/js/home.js`: `Intro.run` | daftar tunggu sesuai §10.4; urutan keluar sesuai §10.3 |
| `public/assets/js/home.js`: `init` | cek `intro-seen` tetap dipakai hanya untuk flag sekali-pakai |
| `app/Views/home/partials/splash.php` | latar tanpa `loading="lazy"`, tambah `fetchpriority="high"`; komentar "sekali per sesi tab" diperbarui |
| `public/assets/css/home.css` | failsafe `splash-failsafe` 8 dtk → 6 dtk; `.js.intro-seen .splash` tetap (untuk pengecualian live count) |
| `tests/feature/HomepageTest.php` | test layar pembuka: tidak ada lagi ketergantungan pada penanda sesi; layout tidak lagi memuat `osis2026.intro` |
| `STAGE5-NOTES.md` / catatan Stage 6 | baris "Muat ulang / tombol Back → layar pembuka tidak diulang" dan "Tab baru → sekali lagi" diganti dengan tabel §10.1 |

Skrip inline tetap satu dan ber-nonce (`csp_script_nonce()`), sesuai kontrak CSP Stage 4.

## 11. Scroll-driven enhancement

Boleh memakai CSS scroll-driven animation sebagai tambahan, tetapi navigasi inti tetap `home.js`.

Hanya untuk: kontur/registration mark, kedalaman latar, progres scene.

Jangan untuk: pengiriman suara, CTA penting, teks bacaan, nilai data.

## 12. View Transition enhancement

Portal → halaman login boleh memakai View Transition API bila didukung. Fallback: navigasi biasa. Jangan mengubah MPA menjadi SPA.

## 13. Sentuhan

Sentuhan selalu lebih penting daripada gerak:
- jangan menunda klik karena animasi;
- jangan mengharuskan geser yang presisi;
- jangan mengaktifkan perilaku mirip hover di layar sentuh.

## 14. Aksesibilitas

### Reduced motion

Saat `prefers-reduced-motion: reduce`:
- tanpa paralaks, tanpa tilt scene, tanpa drift/scale latar;
- tanpa angka bergulir (langsung berganti);
- canvas ambient mati;
- scene berganti langsung, isi langsung terlihat;
- layar pembuka tetap tampil dengan durasi sama, statis (§10.3).

### Keyboard & pembaca layar

- Gerak tidak mengubah fokus secara tak terduga.
- Layar pembuka `aria-hidden`; konten beranda tetap di pohon aksesibilitas.
- Transisi visual bukan satu-satunya penanda perubahan scene (`aria-live` Stage 5 dipertahankan).

## 15. Performa

Animasikan hanya `transform` dan `opacity`.

Hindari animasi terus-menerus pada: `width`, `height`, `left`, `top`, `box-shadow` kompleks, `filter: blur` besar, `stroke-dashoffset` pada path panjang.

- Latar hero dianimasikan lewat `transform` pada elemen gambar, bukan `background-position`.
- Canvas/WebGL hanya aktif saat dibutuhkan (hero aktif, tab terlihat).
- Layar pembuka selalu tampil, jadi aset kritisnya harus kecil dan ter-cache (dokumen 08 §8).
