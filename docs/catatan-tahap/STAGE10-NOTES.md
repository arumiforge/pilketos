# Stage 10 — Rapikan HP: Sekilas Paslon, Dasbor, Pilihan Saya, Perolehan Suara (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 10. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE9-NOTES.md`, dan
`README.md`.

Ringkasan Stage 10 (permintaan revisi tampilan). Tanpa perubahan database,
route, controller, atau alur penyimpanan suara.

- **Sekilas paslon (HP)**: kartu bergeser sendiri 01 -> terakhir -> 01 tiap
  2 detik; panah bawah halus di dasar bagian menggulir ke navigasi bab;
- **dasbor pemilih (HP)**: `.dash-head` atas `--space-5` (dulu `--space-7`);
  isi kartu hak suara (belum & sudah memilih) rata tengah; jam sisa waktu
  tidak menempel, jadi strip selebar layar tanpa sudut tepat di atas footer;
- **modal sukses (HP)**: "Pasangan 02" lalu baris baru "dicoblos 24 September
  2026, 19.53.30 WIB" tanpa titik pemisah;
- **pilihan saya**: siswa disapa "kamu" (guru tetap "Anda"); "Pasangan 0X"
  disorot; eyebrow disembunyikan & kepala rata tengah di HP; foto rata tengah
  (HP & desktop); waktu memilih dua baris; catatan jadi pesan peringatan di
  HP; tombol "Kembali ke dasbor" dihapus;
- **beranda, scene perolehan suara**: label status (`.live__tag`) dihapus;
  judul & pasangan rata tengah; "Suara masuk" tidak lagi sekolom dengan
  pasangan, tetapi menggantikan catatan (`.live__note`) sebagai baris penutup.

"HP" = lebar < 720 px (sama dengan Stage 8-9), kecuali beranda (< 768 px,
aturan lama `home.css`).

## 1. Sekilas paslon (`voting/partials/lineup.php`, `candidates.js`, `voting.css`)

| Bagian | Perubahan |
|---|---|
| `ol.lineup__list[data-lineup]` | `initLineupAutoplay()` (HP saja, `matchMedia('(max-width: 719px)')`): tiap 2 detik `scrollTo({behavior: 'smooth'})` ke kartu berikutnya; setelah kartu terakhir (atau ujung geser) kembali ke kartu 01. Posisi dihitung dari kartu terdekat, jadi geseran manual pemilih dilanjutkan dari kartu itu |
| berhenti | sentuh / pointer / roda di daftar -> jeda, lanjut setelah 5 detik diam; fokus keyboard di dalam kartu -> berhenti sampai fokus keluar; daftar < 60% terlihat (IntersectionObserver) atau tab tersembunyi -> berhenti; `prefers-reduced-motion` -> tidak pernah bergeser sendiri |
| `a.lineup__next` | chevron bawah 22 px abu-abu, bergerak naik-turun pelan (`lineup-nudge` 2,4 s), di dasar `.lineup`, `href="#navigasi-paslon"`. Hanya HP (desktop `display: none`) |
| `nav.chapter-nav#navigasi-paslon` | id baru, tujuan panah (scroll halus bawaan `html`) |
| layar pertama HP | agar panah ikut terlihat: `.booth-open > .lineup` bawah 0, daftar `margin-top` 8 px & bawah 4 px, jarak isi kartu 4 px, foto kartu 88 px (dulu 96). Chromium 375x812: `.booth-open` tetap 747 px, panah berakhir di 810 px |

## 2. Dasbor pemilih (`app.css`)

| Bagian | Perubahan (HP) |
|---|---|
| `.dash-head` | `padding-block: var(--space-5) var(--space-6)` |
| `.vote-card` | `text-align: center`; judul `justify-content: center`; teks & "Dicoblos pada ..." `text-wrap: balance` |
| `.vote-card--locked` | garis aksen pindah ke tepi atas (`inset 0 6px 0`), nomor pasangan di atas nama (kolom) |
| `.float-clock` | `position: relative` (bukan sticky), `width: 100%`, `margin: auto 0 0` (didorong ke dasar `<main>`, tepat di atas footer), `border-radius: 0`, tanpa bayangan, isi rata tengah. Desktop tetap pil melayang (sticky) |

## 3. Modal sukses (`ballot.js`, `voting.css`)

`[data-slot="success-meta"]` kini diisi tiga span (`textContent`, tanpa
`innerHTML`): `.confirm__meta-pair` "Pasangan 02", `.confirm__meta-sep`
" · ", `.confirm__meta-time` "dicoblos ...". HP: pemisah disembunyikan,
waktu `display: block`. Desktop tetap satu baris seperti dulu.

## 4. Pilihan saya (`voting/my_vote.php`, `voting.css`)

| Bagian | Perubahan |
|---|---|
| sapaan | `$you` = "kamu" (siswa) / "Anda" (guru): judul "Kamu memilih", catatan "... lalu kamu mencoblos sendiri" |
| judul | `.receipt__lead` ("Kamu memilih", setengah ukuran, abu-abu) + `.receipt__pair` "Pasangan 0X" blok aksen penuh (`--accent` / `--accent-ink`), tanda paling kuat di halaman |
| HP | `.receipt__eyebrow` disembunyikan; `.receipt__head` (judul, lock-badge) rata tengah |
| foto | `.receipt__portraits .portraits` `margin-inline: auto` (HP & desktop) |
| waktu memilih | `<time>` berisi `.receipt__date` "24 September 2026" dan `.receipt__time` "19.53.30 WIB", masing-masing satu baris |
| catatan | `<strong class="receipt__note-title">Pilihan tidak dapat diubah.</strong>` + teks. HP: kotak peringatan (latar `--paper-dim`, garis `--line-strong`, sudut 6 px), ikon `alert` dalam lingkaran tinta, judul tebal, teks redup. Desktop: teks seperti dulu, kalimat pertama tebal |
| tombol | hanya **Selesai & keluar** (HP selebar kolom). Dasbor tetap bisa dibuka dari navigasi atas |

## 5. Beranda: scene perolehan suara (`home/index.php`, `home.css`)

| Bagian | Perubahan |
|---|---|
| `.live__tag` | dihapus dari markup & CSS (juga `$liveTag`, `.live__led`). Status tetap di panel dock ("Sedang Berlangsung", "Sudah Selesai", ...) |
| `.live__head` | judul rata tengah |
| `.live__grid` | dihapus. `.live__pairs` langsung di `.live`: kolom `minmax(0, var(--pair-w))` dengan `justify-content: center`; isi kartu (persentase, nama) rata tengah |
| ukuran foto | `--photo-h: clamp(140px, calc(100svh - 590px), 440px)`, `--pair-w: max(200px, var(--photo-h) * 0.8)` (foto 4:5 selama muat). HP miring: `--photo-h: 34vh` |
| `.live__note` (live count) | diganti `.turnout` di dalam `.live__foot`: kiri persentase besar; kanan "SUARA MASUK", "2 dari 15 pemilih telah memberikan suara", "Diperbarui [tanggal jam]"; meter bersegmen di bawah; lebar maks 560 px, rata tengah, garis tipis di atas. HP: "Diperbarui" turun ke bawah meter |
| teks yang hilang | catatan per status ("Persentase dari suara sah...", "Penghitungan dimulai...", "Pencoblosan sudah ditutup. Hasil resmi...") dan "Diperbarui otomatis tiap N detik." |
| teaser (live count publik mati) | tetap: catatan "Visi, misi, dan surat suara tersedia setelah masuk." + tombol, kini rata tengah |

`home.js` tidak berubah: hook `data-live-turnout`, `data-live-meter`,
`data-live-voted`, `data-live-total`, `data-live-updated` tetap ada
(`data-live-tag` memang tidak pernah dibaca skrip).

Tinggi scene (Chromium, data seeder): tanpa luapan di 1920x1080, 1536x864,
1440x900, 1366x768, 1280x800, 1280x720, 1024x768, 768x1024, 390x844,
375x667 (dulu 1440x900 meluap 7 px, 1536x864 3 px, 375x667 97 px). HP miring
(844x390, 667x375) tetap perlu digulir di dalam scene seperti sebelumnya
(187/218 px, dulu 168/187 px).

## 6. Test

Baru: `tests/feature/StageTenRefinementTest.php` (8 test): hook autoplay +
panah + id navigasi bab, kontrak JS (2 detik, HP saja, kembali ke 01,
reduced motion), span meta modal sukses, CSS dasbor HP (kepala, kartu rata
tengah, jam di atas footer + urutan markup), pilihan saya siswa ("kamu",
blok pasangan, tanggal/jam dua baris, catatan, tanpa "Kembali ke dasbor"),
guru tetap "Anda", CSS pilihan saya, scene perolehan suara (tanpa tag/grid/
catatan, "Suara masuk" di baris penutup berisi waktu pembaruan) dan teaser.

Diperbarui: `VotingTest` ("Kamu memilih"), `HomepageTest` (teks turnout baru;
status UPCOMING/FINISHED dibaca dari `data-live-status` dan dock, bukan
catatan/tag).

Hasil: **348 test, 3.030 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, data seeder):

- HP 375x812: Sekilas paslon bergeser 0 -> 300 -> 545 (kartu terakhir) -> 0
  tiap 2 detik; setelah disentuh diam 4 detik lalu lanjut; reduced motion &
  desktop 1280x800 tidak bergeser; panah terlihat di layar pertama, ditekan
  -> `#navigasi-paslon` di atas layar;
- dasbor 375: kepala atas 24 px, kartu rata tengah, jam selebar layar
  (375 px, sudut 0) berakhir tepat di garis footer; desktop tetap sticky;
- coblos 02 lewat tombol -> modal sukses "Pasangan 02" / "dicoblos ..." dua
  baris; pilihan saya 375 & 1440 tanpa luapan horizontal;
- tanpa error JavaScript.
