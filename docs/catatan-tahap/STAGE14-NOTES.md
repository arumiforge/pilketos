# Stage 14 — Gambar Layar Pembuka, Halaman Impor Ramah, Tab Pratinjau Tanpa Muat Ulang (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 14. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE13-NOTES.md`, dan
`README.md`.

Ringkasan Stage 14 (permintaan revisi). Tanpa perubahan schema database,
route, atau dependensi.

- **layar pembuka beranda**: latar kabut tidak lagi muncul telat;
- **pasangan calon (admin)**: tombol aksi kartu rata tengah di HP;
- **impor siswa**: contoh nama **Sasuke Uchiha**, bahasa ramah untuk anak SMP;
- **impor guru**: contoh NIP `199305012020121004`, nama **Fajar Afif
  Dewantoro, S.Pd.**, kode unik `01012000`; bahasa ramah tanpa istilah teknis;
- **tombol unduh template**: selebar panel di HP, teks rata tengah;
- **tab "Saring baris pratinjau"**: rata tengah, bergulir mendatar di HP, dan
  berpindah saringan/halaman tanpa muat ulang halaman (desktop & HP).

"HP" = lebar < 720 px (sama dengan Stage 8-13).

## 1. Layar pembuka: gambar tidak telat

Penyebab: `.splash__img` berawal `opacity: 0` dan baru terlihat setelah
`home.js` (defer) berjalan, gambar selesai dimuat, lalu transisi 400 ms. Di
jaringan lambat latar kabut baru tampil di akhir layar pembuka, padahal
10-MOTION §10.3 meminta "0 ms latar kabut tampil".

| Bagian | Perubahan |
|---|---|
| `home/index.php` (section `head`) | `<link rel="preload" as="image" fetchpriority="high">` untuk latar pembuka (`media` potret/lanskap sama dengan `<source>` di `<picture>`), lockup, dan lambang sekolah bila dipasang. URL = `asset_url()` yang sama dengan `<img>` (`?v=`) agar unduhan dipakai ulang. Latar hero tidak di-preload: tertutup layar pembuka dan sudah `fetchpriority="high"` |
| `home/partials/splash.php` | `decoding="async"` dilepas dari latar & lockup pembuka (terlukis bersama frame pertama) |
| `home.css` `.splash__img` | tanpa `opacity: 0` / `.is-loaded`: terlihat begitu terlukis, tidak menunggu skrip |
| `home.js` `whenImage` | selesai setelah `load` **dan** `img.decode()` (gagal tetap selesai), jadi bilah muat baru penuh ketika latar hero benar-benar siap dilukis |
| `home.js` `warmImages` | begitu semua aset yang ditunggu selesai (atau tanpa layar pembuka), gambar portal & foto pasangan diurai lebih dulu, tanpa menahan layar pembuka (§10.4 tetap) |

Uji Chromium (jaringan diperlambat 400 KB/s + 150 ms, server `spark serve`):
latar kabut terlihat penuh pada ±1,24 s (1440 px) / ±1,10 s (375 px), dulu
±1,78 s / ±1,70 s (setelah durasi minimal 1,5 s layar pembuka).

## 2. Kartu pasangan calon (admin)

`admin.css`, `@media (max-width: 719px)`: `.cand-card__actions`
`justify-content: center; align-items: center`; keterangan kunci
(`.cand-card__lock`) selebar baris dan rata tengah. Desktop tidak berubah
(kolom kanan di >= 960 px).

## 3. Halaman impor siswa & guru

| Bagian | Isi |
|---|---|
| contoh siswa (`StudentImporter::columns()`) | nama `Sasuke Uchiha` (NISN, rombel, kode unik tetap) |
| contoh guru (`TeacherImporter::columns()`) | NIP `199305012020121004`, nama `Fajar Afif Dewantoro, S.Pd.`, kode unik `01012000`; petunjuk no. 4 di file template ikut ("contoh 01012000 untuk 1 Januari 2000") |
| keterangan kepala | "{Siswa/Guru} yang {NISN/NIP}-nya sudah ada cukup diperbarui datanya, jadi tidak dobel. ... tetap aman, tidak ikut terhapus." |
| panel 01 | teks per jenis pemilih: siswa "tinggal ketik saja", guru "cukup ketik seperti biasa"; tanpa "berformat Teks" |
| panel 02 | batas file & sheet pertama dalam kalimat biasa; tombol memuat "Sedang membaca & memeriksa..." |
| "Yang dicek sebelum data disimpan" | siswa: satu aturan per butir, bahasa anak SMP; guru: tanpa istilah teknis ("dibulatkan Excel", "terbaca sebagai angka", "DDMMYYYY" diganti penjelasan biasa). Contoh kode unik diambil dari contoh kolom template dan diubah ke tanggal (`Time::createFromFormat('!dmY')`) |
| tombol unduh template | kelas `.import-download`; HP: `display: flex; width: 100%; text-align: center` |

Pesan galat per baris di pratinjau & petunjuk lain di file template tidak
diubah.

## 4. Tab "Saring baris pratinjau"

| Bagian | Isi |
|---|---|
| `admin/import/preview.php` | `<nav class="tabs" ... data-live-nav="saring">`; tabel, "tidak ada baris", dan pager dibungkus `<div class="preview-rows" data-live-region="pratinjau" data-live-nav="halaman">` + teks tersembunyi `data-live-announce` ("Perlu diperiksa: 12 baris.") untuk pembaca layar |
| `admin.css` `.tabs` | satu baris (`flex-wrap` dihapus), `overflow-x: auto`, rata tengah lewat `margin-left/right: auto` di tab pertama & terakhir (bukan `justify-content: center`, agar tab pertama tetap bisa dicapai saat bergulir), garis bawah = `box-shadow` dalam (overflow memotong margin negatif). HP: selebar layar (margin negatif `--admin-pad`), tanpa scrollbar |
| `admin.js` `initLiveSearch` | jalur fetch live search dipecah menjadi `load(url, options)`; klik tautan di `[data-live-nav]` (klik kiri biasa, asal sama) mengganti `[data-live-region]` tanpa muat ulang, `pushState` + `popstate` (Back/Forward berpindah saringan), `aria-current` tab disalin dari balasan server, tab aktif digeser ke tengah deretan. Klik pager memindah fokus ke region baru dan menggulir ke tab bila sudah lewat. Ctrl/Shift/klik tengah tetap tab baru; tanpa JavaScript tetap tautan biasa |
| pengalihan | balasan yang dialihkan (mis. pratinjau kedaluwarsa) membuka URL aslinya, bukan URL tujuan, agar pesan flash pengalihan tidak habis terpakai oleh fetch |

Kontrak live search Stage 12 (`replaceState`, `X-Analytics-Pane`,
`.result-count`) tetap.

## 5. Test

Baru: `tests/feature/StageFourteenRefinementTest.php` (9 test): preload latar
pembuka & lockup di `<head>` (URL sama dengan `<img>`, tanpa hero), latar
pembuka tidak menunggu skrip + `img.decode()` + `warmImages`, aksi kartu
paslon rata tengah di HP, halaman impor siswa (Sasuke Uchiha, bahasa ramah) &
guru (contoh baru, tanpa istilah teknis, petunjuk template), tombol unduh
selebar layar di HP, tab & region pratinjau (fetch mengembalikan tab aktif),
CSS tab bergulir & rata tengah, kontrak skrip navigasi tanpa muat ulang.

Diperbarui: `AdminImportTest::testImportPageExplainsTemplateAndLimits`
(kalimat baru) + `testTeacherImportPageUsesPlainLanguageAndNewExample`.

Uji browser (Playwright/Chromium, data seeder + file uji 160 baris): 375 &
1440 px, klik tab & pager tanpa muat ulang (penanda `window` bertahan), URL
berganti, Back/Forward memulihkan saringan & halaman, tab aktif terlihat di
HP, tanpa gulir mendatar halaman, tanpa error JavaScript.
