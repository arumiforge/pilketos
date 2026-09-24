# Stage 12 — Loader Analitik, Breadcrumb Berikon, Kepala Halaman Center, Timeline, Live Search (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 12. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE11-NOTES.md`, dan
`README.md`.

Ringkasan Stage 12 (permintaan revisi tampilan panel admin). Tanpa perubahan
schema database, route, maupun controller.

- **analitik**: garis progres hitam di bawah pil (berkedip untuk respon cepat)
  diganti kerangka "memuat" (shimmer) yang baru tampil setelah jeda singkat;
- **breadcrumb**: titik diganti ikon (sama dengan menu samping); di desktop
  pindah ke topbar menggantikan judul teks, di HP ikon saja di atas judul;
  berlaku di semua halaman admin;
- **kepala halaman**: judul rata tengah, tanpa garis bawah, di semua halaman
  admin (tombol aksi & indikator Live ikut di tengah);
- **teks**: semua `panel__link` menjadi "Selengkapnya" (dasbor & unlock),
  kolom "Komposisi" menjadi **Grafik** (dasbor, analitik, live count);
- **paslon**: tanpa garis atas daftar & garis bawah kartu (kartu berpermukaan
  lembut), tanpa eyebrow, kelengkapan asset berupa timeline;
- **unlock**: tahapan berupa timeline;
- **daftar siswa/guru**: `list-bar` lebih lega, angka hasil berupa chip;
- **live search** di semua kolom pencarian admin.

"HP" = lebar < 720 px (sama dengan Stage 8-11).

## 1. Analitik: kerangka "memuat"

| Bagian | Isi |
|---|---|
| markup | `admin/analytics/index`: `.pane-stage` berisi `[data-pane-host]` + `.pane-loader[data-pane-loader][hidden][aria-hidden]` (kepala: spinner + `[data-pane-loader-label]`, kerangka judul, 2 baris catatan, 3 kartu, 4 baris tabel) |
| `admin-analytics.js` | `load()` tidak lagi menandai `nav.is-loading`. `showLoader()` menampilkan kerangka setelah `LOADER_DELAY` 120 ms (label "Memuat Rombel…"), isi lama `is-hidden` (opacity 0). Kerangka yang sudah tampil ditahan minimal `LOADER_MIN` 320 ms sebelum `swap()` (tanpa kedip). Batal/galat/selesai = `hideLoader()` |
| CSS | `.pills.is-loading::before` + `@keyframes pane-progress` dihapus. Kerangka absolut di atas host, isinya `position: sticky` (top 140 px) sehingga tetap terlihat saat halaman sudah digulir. Shimmer `skel-shimmer`; `prefers-reduced-motion`: kerangka statis |
| pil | desktop: deretan pil rata tengah (mengikuti kepala halaman) |
| topbar | judul topbar tidak lagi diganti skrip (topbar berisi breadcrumb); atribut fragmen `data-pane-heading` dihapus |

## 2. Breadcrumb berikon

| Bagian | Isi |
|---|---|
| view halaman | pemanggilan `admin/partials/crumbs` pindah dari `.admin-head` ke section `crumbs` di akhir file (15 view) |
| layout | `layouts/admin` merender section itu **sekali** (`$crumbs`), lalu memasangnya di topbar (`<div class="admin-top__title">`, via `setVar`) dan di awal `<main>` (`<div class="admin-crumbs">`). Jangan memakai `renderSection(..., true)`: section tersimpan menumpuk antar-render (terlihat di test) |
| partial | ikon per label (Beranda `grid`, Analitik `chart`, Hasil akhir `award`, Pasangan calon `flag`, Siswa `users`, Guru `user`, Jadwal `calendar`, Unlock `unlock`, Audit `file`, Detail/Pratinjau `eye`, Tambah/Baru `plus`, Ubah `edit`, Impor Excel `upload`, Hasil `check`); elemen ke-3 trail opsional menimpa ikon. Teks di `.crumbs__label` |
| desktop (>= 720 px) | topbar: ikon 16 px + label kecil kapital, rel tipis; `.admin-crumbs` disembunyikan |
| HP (< 720 px) | topbar tanpa breadcrumb; `.admin-crumbs` rata tengah: lingkaran 34 px berikon, langkah aktif lingkaran tinta bercincin; label disembunyikan secara visual (tetap dibaca pembaca layar). Hanya satu salinan yang tampil, jadi hanya satu landmark Breadcrumb |
| cetak | `.admin-crumbs` disembunyikan (topbar sudah) |

## 3. Kepala halaman

`.admin-head` kolom rata tengah tanpa `border-bottom` (semua halaman admin).
Judul `text-wrap: balance`; keterangan (lede) di tengah, kotak membulat tanpa
garis kiri; `.admin-head__actions` & indikator Live desktop di tengah di
bawah judul (HP: Live tetap bar bawah). `results.css`: aturan
`.final-page > .admin-head` tinggal `margin-bottom`.

## 4. Teks

| Tempat | Sebelum | Sesudah |
|---|---|---|
| dasbor, Hasil sementara | Analitik lengkap | Selengkapnya (+ ": analitik" tersembunyi) |
| dasbor, Rekap rombel | Detail suara | Selengkapnya (+ ": detail suara") |
| unlock, Unlock terakhir | Audit log unlock | Selengkapnya (+ ": audit log unlock") |
| `admin/partials/recap_table`, `admin-live.js` | Komposisi | Grafik |

## 5. Pasangan calon

| Bagian | Isi |
|---|---|
| daftar | `ol.cand-list` grid ber-gap tanpa garis atas; `.cand-card` berlatar `--paper-dim`, sudut `--radius-lg`, tanpa garis bawah (HP < 520 px: kolom nomor 64 px) |
| eyebrow | dihapus. "Pasangan 01" tetap dibacakan (teks tersembunyi di `h2`); tema pindah ke meta ("Tema"); pil Nonaktif ke meta ("Status", hanya bila nonaktif) |
| asset | `ol.asset-dots` timeline 7 simpul: terisi = simpul tinta + centang, kosong = simpul putus-putus; rel tinta di antara dua simpul terisi. Vertikal secara bawaan, horizontal bila isi kartu >= 560 px (`@container`, `.cand-card__body { container-type: inline-size }`) |

## 6. Timeline tahapan unlock

Markup `<li class="steps__item …"><span class="steps__node">01</span><span
class="steps__label">…</span></li>`; langkah selesai = simpul tinta berikon
centang (+ "(selesai)" untuk pembaca layar), langkah aktif = simpul tinta
bercincin dengan satu `aria-current="step"`, berikutnya = simpul bergaris.
Rel di antara simpul (tinta setelah langkah selesai / antar-langkah aktif).
Label di bawah simpul, rata tengah, juga di HP (4 kolom).

## 7. list-bar & jumlah hasil

`.list-bar` margin `--space-5` atas & bawah (jarak ke tabel), jumlah hasil di
dalamnya tanpa margin sendiri. `.result-count strong` = chip tinta membulat
(juga di audit, unlock, detail suara).

## 8. Live search

| Bagian | Isi |
|---|---|
| form | `form[data-live-search]` di daftar siswa/guru, audit log, unlock, detail suara. Input dibungkus `.search-field` + ikon `search` di kiri (menjadi spinner selama mencari) |
| wilayah | `[data-live-region="results"]` (jumlah + tabel/kosong + pager; di unlock wadah selalu dirender walau kueri kosong), `[data-live-region="actions"]` (`.filters__actions`, agar tombol Reset ikut muncul/hilang) |
| `admin.js initLiveSearch()` | delegasi `input` di `document`: jeda 300 ms, kueri kosong atau >= 2 huruf; URL = atribut `action` + parameter tidak kosong (bukan `form.action`: form audit punya kolom bernama `action`); dilewati bila sama dengan URL halaman. `fetch` GET (`X-Requested-With`; + `X-Analytics-Pane` untuk `data-pane-form`), request lama dibatalkan, timeout 12 dtk. Balasan diurai `DOMParser`, setiap wilayah diganti pasangannya, `history.replaceState`, jumlah hasil diumumkan lewat satu `role="status"`. Form sudah tidak di dokumen (bagian analitik lain dimuat) = hasil dibuang. Wilayah tidak ditemukan / error / 401 / redirect / timeout = pindah halaman biasa |
| submit | form bukan `data-pane-form`: `submit` (Enter & dropdown lewat autosubmit) diambil alih di fase capture + `stopImmediatePropagation` (penjaga submit ganda `app.js` tidak mengunci form), jadi filter juga tanpa muat ulang. Detail suara: dropdown tetap lewat `admin-analytics.js` (pushState bagian) |
| kode unik | `initSecrets(root)` idempoten (`data-secret-ready`) dan dipanggil untuk wilayah baru |
| tanpa JavaScript | form GET biasa seperti sebelumnya |

## 9. Test

Baru: `tests/feature/StageTwelveRefinementTest.php` (9 test): breadcrumb di
topbar + salinan HP + ikon + tidak di kepala halaman; kepala halaman center
tanpa garis; kerangka analitik & kontrak skrip (tanpa `pane-progress`,
`topTitle`); "Selengkapnya" & "Grafik" (dasbor, analitik, unlock,
`admin-live.js`); kartu paslon tanpa eyebrow + timeline asset + Tema/Status di
meta; timeline unlock (satu `aria-current="step"`); list-bar; atribut live
search + wilayah di 5 halaman (termasuk fragmen detail suara & unlock tanpa
kueri); kontrak `initLiveSearch`.

Diperbarui: `StageNineRefinementTest::testAdminPagesUseBreadcrumbStepperAndHintedLede`
(dua salinan breadcrumb, markup ikon + `.crumbs__label`).

Hasil: **383 test, 3.422 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium, data seeder + 8 suara uji):

- analitik 1440: respon bagian ditahan 900 ms -> kerangka "Memuat Rombel…"
  tampil, pil tanpa garis hitam, lalu isi masuk tanpa memuat ulang halaman;
  respon cepat (20 ms) -> kerangka tidak pernah tampil;
- detail suara: ketik "bunga" -> 1 baris, URL `?q=bunga…`, fokus & isi input
  tetap, tombol Reset muncul; hapus kueri -> 8 baris;
- siswa: ketik "an" -> 7 siswa, pilih rombel 7A -> 1 siswa (tanpa memuat
  ulang), "Tampilkan kode unik" tetap berfungsi di tabel baru, Enter -> tetap
  tanpa memuat ulang & form tidak terkunci; audit log & unlock sama;
- 1440 & 375: breadcrumb topbar / ikon HP, kepala halaman center, timeline
  asset (horizontal / vertikal) dan tahapan unlock, tanpa luapan horizontal,
  tanpa error JavaScript.
