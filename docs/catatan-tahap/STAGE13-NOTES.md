# Stage 13 — Hasil Akhir & PDF, Sidebar, Akun Admin, Login Layar Terbelah (hasil implementasi + handoff)

Dokumen ini adalah kontrak aktual Stage 13. Dibaca bersama
`00-MASTER-PROJECT.md`, `STAGE1-NOTES.md` s.d. `STAGE12-NOTES.md`, dan
`README.md`.

Ringkasan Stage 13 (permintaan revisi panel admin). Tanpa perubahan schema
database. Route baru: `admin/hasil/cetak`, `admin/akun`,
`admin/akun/nama-pengguna`, `admin/akun/kata-sandi`. Dependensi baru:
`dompdf/dompdf` ^3.1 (LGPL-2.1).

- **hasil akhir (layar)**: nama sekolah di judul tidak miring, stempel
  `.final__mark` dihapus, kicker & judul rata tengah (desktop, HP, cetak),
  tombol aksi di HP ikon saja, catatan kaki rata tengah selebar lembar dengan
  bahasa sederhana;
- **hasil akhir (cetak)**: tombol **Cetak PDF** membuat PDF di server
  (dompdf); cetak browser (Ctrl+P) mengikuti tata letak yang sama;
- **sidebar**: label Title Case; **Halaman Utama** (dulu "Home") dan
  **Keluar** pindah ke kelompok menu terakhir (paling bawah) bersama
  **Akun Admin**;
- **kepala halaman HP**: Beranda tanpa breadcrumb ikon, semua tombol "i"
  (`.admin-head__hint`) disembunyikan;
- **banner final dasbor**: nomor, teks, dan tombol rata tengah;
- **analitik**: "Keseluruhan" -> **Total**, "Jenis pemilih" -> **Pemilih**,
  "Jenis kelamin" -> **Jenis Kelamin**; catatan bab (`.chapter-x__note`)
  sederhana tanpa istilah teknis;
- **login admin**: layar terbelah dengan foto surat suara, judul **Panel
  Admin**, tombol lihat kata sandi;
- **akun admin**: ganti nama pengguna & kata sandi dari panel.

"HP" = lebar < 720 px (sama dengan Stage 8-12).

## 1. Hasil akhir: tampilan layar

| Bagian | Isi |
|---|---|
| `.final__school` | `font-weight: 300`, tanpa `font-style: italic` |
| `.final__mark` | elemen & CSS dihapus (juga padding kanan kicker/judul di >= 720 px) |
| `.final__kicker`, `.final__title` | `text-align: center` (kicker `max-width: none` karena `p { max-width: 62ch }` global) |
| `.final-actions` | label tombol dibungkus `.btn__label`; HP: tombol bulat 48 px ikon saja, label tersembunyi secara visual (tetap dibaca pembaca layar). Tombol cetak = tautan `admin/hasil/cetak` (`target="_blank"`, `data-print-pdf`, "(tab baru)" untuk pembaca layar) |
| `.final__foot` | `width: 100%`, rata tengah, `p { max-width: none }` (tidak lagi 90ch / 62ch) |
| catatan kaki | `FinalResult::footnote($generatedAt)`: "Dihitung pada 1 Oktober 2026 pukul 13.00 WIB. Hanya suara sah dari pemilih terdaftar yang dihitung. Setelah pemilihan ditutup, data pemilih dan pasangan calon tidak dapat diubah, sehingga hasil ini bersifat tetap." Dipakai layar, cetak browser, dan PDF |
| logo | `public/assets/img/brand/logo-smp1dawe.png` (360 px, palet 128 warna, 29 KB). `<img class="admin-head__logo">` di kepala halaman hasil, tersembunyi di layar, tampil saat dicetak |

## 2. Hasil akhir: PDF (dompdf)

| Bagian | Isi |
|---|---|
| route | `GET admin/hasil/cetak` -> `ResultController::pdf()`. Belum selesai -> redirect `admin/hasil` + flash "PDF hasil akhir tersedia setelah pemilihan selesai." |
| respons | `application/pdf`, `Content-Disposition: inline; filename="hasil-akhir-pilketos-2026.pdf"`, `Cache-Control: private, no-store, max-age=0`, `nosniff` |
| `App\Libraries\ResultPdf` | opsi dompdf: remote/PHP/JavaScript mati, chroot = `app/Fonts` + `writable/cache/dompdf`, subset font aktif, A4 tegak, DPI 96. Font didaftarkan lewat `FontMetrics::registerFont()`; metrik disimpan di `writable/cache/dompdf` (hilang karena `cache:clear` = dibuat ulang otomatis). Nomor halaman "Halaman X dari Y" digambar `page_text()` di setiap halaman |
| gambar | `ResultPdf::imageData()`: logo & foto pasangan terpilih dibaca GD, dipotong tengah (foto 4:5, hero 4:3; pengganti `object-fit: cover` yang tidak didukung dompdf), diperkecil (sisi terpanjang 640 px / hero 900 px / logo 360 px), disisipkan sebagai data URI JPEG/PNG. WebP tetap bisa (diubah GD); file tidak terbaca = inisial |
| font | `app/Fonts/`: Newsreader Regular/SemiBold, Plus Jakarta Sans Regular/Bold/Italic (TTF statis dari woff2 variabel `public/assets/fonts`, OFL, lisensi disalin). Italic = kemiringan 11° (Plus Jakarta Sans tidak punya italic asli) |
| `admin/results/pdf` | CSS 2.1 berbasis tabel (dompdf tanpa flex/grid). Halaman 1: logo (pengganti judul halaman), kicker & judul rata tengah, stempel Ditutup/Suara sah/Partisipasi, pasangan terpilih (nomor, foto/inisial, nama, perolehan), perolehan suara, partisipasi (judul bagian tidak terpisah dari isinya: `.keep`). Halaman 2 (`.recap-page { page-break-before: always }`): rekap Pemilih, Kelas, Jenis Kelamin Siswa. Setiap halaman: `.foot` fixed (miring, rata tengah, selebar kertas) + nomor halaman |
| seri | catatan "perolehan suara tertinggi sama" tidak dicetak (layar tetap menampilkannya); tanpa suara sah tetap ada catatan singkat |
| render view | `view(..., ['debug' => false])`: komentar DEBUG-VIEW (development) tidak masuk PDF |

## 3. Hasil akhir: cetak lewat browser (Ctrl+P)

`results.css` `@page { margin: 14mm 14mm 22mm }` + `@media print`:

- disembunyikan: `.final-actions`, confetti, `.final-note--tie`,
  `.final__foot` (elemen), `.final-page .admin-head__title`; `admin.css`
  juga menyembunyikan `.admin-foot`;
- `.admin-head__logo` tampil 20 mm di tengah;
- pasangan terpilih satu baris ringkas (nomor 64 pt, foto 26 mm, nama),
  semua susunan tema (split/poster/column) sama saat dicetak;
- `.final__recap { break-before: page }` (rekap mulai halaman 2),
  `.final__section { break-after: avoid }`, partisipasi tidak terbelah;
- catatan kaki + "Halaman X dari Y" = `@page { @bottom-center { ... } }`
  di `<style nonce>` halaman hasil (teks di-escape `esc(..., 'css')`), miring
  dan rata tengah. Margin box didukung Chrome/Edge 131+; browser lain tanpa
  catatan kaki saat Ctrl+P (PDF tetap lengkap). `position: fixed` tidak
  dipakai: Chrome meletakkannya di atas halaman 2 dst. menimpa isi.

## 4. Sidebar

| Bagian | Isi |
|---|---|
| label | Beranda, Analitik, Detail Suara, Hasil Akhir, Pasangan Calon, Siswa, Guru, Jadwal Pemilihan, Unlock Hak Suara, Audit Log |
| kelompok terakhir | `ul.admin-side__list.admin-side__list--end`: Akun Admin (`admin/akun`, ikon `key`), Halaman Utama (`/`, tab baru, ikon `home`), Keluar (form POST `admin/keluar` + CSRF, `button.admin-side__link--button`, ikon `logout`) |
| tata letak | `.admin-side__nav` flex kolom; `.admin-side__list + .admin-side__list--end { margin-top: auto }` mendorong kelompok ini ke bawah bila ruang cukup. Kaki sidebar tinggal "Masuk sebagai" |
| dihapus | `.admin-side__actions`, `.admin-side__site`, `.admin-side__logout` |
| ikon baru | `home`, `key`, `eye-off` (`icon()`) |

## 5. Kepala halaman & banner final

| Bagian | Isi |
|---|---|
| breadcrumb Beranda | `admin/partials/crumbs`: trail hanya Beranda -> `nav.crumbs.crumbs--root`; HP: `.admin-crumbs .crumbs--root { display: none }` (margin pindah ke `.admin-crumbs .crumbs`). Topbar desktop tetap menampilkan "Beranda" |
| tombol "i" | HP: `.js .admin-head__hint { display: none }`, keterangan yang terbuka ikut tersembunyi |
| `.final-banner` | satu kolom `justify-items: center`, `text-align: center` di semua lebar; pemenang = garis atas 10 px warna aksen (dulu garis kiri); eyebrow `justify-content: center` |

## 6. Analitik

| Tempat | Sebelum | Sesudah |
|---|---|---|
| pil (`AnalyticsController::PANES`, slug URL tetap) | Keseluruhan, Jenis pemilih, Jenis kelamin, Detail suara | Total, Pemilih, Jenis Kelamin, Detail Suara |
| judul bab | Jenis kelamin siswa, Rekap kelas, Rekap rombel | Jenis Kelamin Siswa, Rekap Kelas, Rekap Rombel |
| kolom tabel rekap | Jenis / Jenis kelamin | Pemilih / Jenis Kelamin |
| filter detail suara | Jenis pemilih, Jenis kelamin | Pemilih, Jenis Kelamin |
| rekap hasil akhir | Jenis pemilih, Jenis kelamin siswa | Pemilih, Jenis Kelamin Siswa |

Catatan bab: Total "Gabungan suara siswa dan guru."; Pemilih "Perbandingan
suara siswa dan guru."; Jenis Kelamin "Suara siswa menurut jenis kelamin.
Guru tidak termasuk."; Kelas "Suara siswa menurut kelas 7, 8, dan 9.";
Rombel "Pilih nama rombel untuk melihat siswa yang sudah dan belum
memilih."; Detail Suara "Daftar suara yang masuk beserta waktu dan
perangkatnya. Jumlah suara sah sama dengan di Beranda (N)."

## 7. Login admin

| Bagian | Isi |
|---|---|
| layout | `layouts/split` (kepala dokumen sama dengan `layouts/main`, tanpa navigasi situs & footer) |
| `admin/login` | `.split-login`: `figure.split-login__visual` (`<picture>` WebP/JPEG 960 & 1600 px dari foto surat suara, keterangan di pita tinta solid) + `main.split-login__panel` (logo kecil + "SMP 1 DAWE", `h1` **Panel Admin**, pesan galat/sukses, form POST `admin/masuk`, tautan "Halaman Utama") |
| CSS (`auth.css`) | >= 900 px: dua kolom `minmax(0, 1.25fr) minmax(420px, 1fr)`, foto menempel setinggi layar. < 900 px: foto pita 200-300 px di atas, formulir di bawah |
| lihat kata sandi | `partials/password_field` (isian + `button.password-field__toggle[data-password-toggle][hidden]`, ikon `eye`/`eye-off`). `app.js initPasswordToggles()`: tombol tampil, klik = `type` text/password + `aria-pressed` + label "Tampilkan/Sembunyikan kata sandi"; saat form dikirim kembali tersembunyi. Tanpa JavaScript: isian kata sandi biasa |
| judul tab | "Panel Admin — Pemilihan OSIS SMP 1 DAWE" |

Catatan: data `view()` CodeIgniter tersimpan antarpanggilan, jadi pemanggil
`partials/password_field` selalu mengirim `error`, `hint`, `attrs` (lihat
`admin/account/index`).

## 8. Akun Admin

| Bagian | Isi |
|---|---|
| route | `GET admin/akun`, `POST admin/akun/nama-pengguna`, `POST admin/akun/kata-sandi` (grup `adminauth`, CSRF global) |
| `AccountController` | keduanya meminta kata sandi saat ini (throttle `admin_account` per admin + per IP, sama dengan login: 5 salah lalu 1 per menit). Tanpa `withInput()`: kata sandi tidak pernah masuk sesi; yang diisi ulang hanya nama pengguna. Galat per isian lewat flash `errors` + `account_form` (username/password) |
| nama pengguna | huruf kecil otomatis, `AdminAccount::USERNAME_PATTERN` (3-50: huruf kecil, angka, titik, strip, garis bawah), tidak sama dengan yang sekarang, unik |
| kata sandi | minimal 8 karakter, maksimal 72 byte (batas bcrypt), tidak hanya spasi, tidak sama dengan nama pengguna, berbeda dari kata sandi saat ini, konfirmasi sama |
| sesi | `AdminModel::SESSION_STAMP_KEY` (`admin_stamp`) = `stampFor(password_hash)` (sha256, 32 hex). Dipasang saat login dan setelah ganti kata sandi (+ `session()->regenerate(true)`). `AdminAuthFilter::accountIsValid()` menolak sesi yang capnya tidak cocok -> perangkat lain keluar. Sesi tanpa cap (sebelum Stage 13) tetap berlaku sampai habis. `admin_stamp` ikut `BaseController::AUTH_SESSION_KEYS` |
| audit | `ADMIN_USERNAME` "Ganti nama pengguna admin" ("Nama pengguna admin diganti dari @lama menjadi @baru."), `ADMIN_PASSWORD` "Ganti kata sandi admin" ("Kata sandi admin diganti.") |
| view | `admin/account/index`: identitas (nama + @nama pengguna), dua panel (`detail-grid`), breadcrumb "Akun Admin" (ikon `key`), menu aktif `account` |

## 9. Test

Baru: `tests/feature/StageThirteenRefinementTest.php` (20 test): kepala
hasil akhir (tanpa mark, tidak miring, center, logo), catatan kaki bahasa
sederhana, tombol ikon HP, aturan cetak browser + margin box `@page`,
PDF (200, header, `%PDF-`, font brand tertanam, 2 halaman), PDF sebelum
selesai ditolak, template PDF (rekap halaman 2, footer fixed miring, seri
tanpa catatan), sidebar Title Case + kelompok terakhir, breadcrumb Beranda &
tombol "i" di HP, banner final center, istilah & catatan analitik, login
layar terbelah + lihat kata sandi, galat login + cap sesi, halaman akun,
ganti nama pengguna (+ validasi), ganti kata sandi (sesi lain keluar, sesi
lama tanpa cap tetap berlaku) + validasi, throttle kata sandi saat ini, akses
khusus admin.

Diperbarui: `AdminPanelTest::testAnalyticsPageShowsAllBreakdowns`,
`StageElevenRefinementTest` (judul Rekap Rombel, pil Detail Suara),
`StageNineRefinementTest` (judul login Panel Admin, Halaman Utama di
kelompok terakhir, `crumbs--root` di Beranda).

Hasil: **403 test, 3.725 assertion, lulus** (PHP 8.4.19, MariaDB 10.11.14).

Uji browser (Playwright/Chromium 141, data seeder + 11 suara uji, pemilihan
selesai):

- 1440 & 375: hasil akhir (kicker/judul center, tidak miring, tanpa mark,
  tombol ikon bulat di HP, catatan kaki selebar lembar), dasbor (banner
  center, HP tanpa breadcrumb ikon & tombol "i"), drawer sidebar (Akun Admin,
  Halaman Utama, Keluar di bawah), akun admin, login layar terbelah, tombol
  lihat kata sandi mengubah `type` ke `text`; tanpa error JavaScript;
- PDF dompdf: 2 halaman, logo di atas, rekap di halaman 2, catatan kaki miring
  rata tengah + "Halaman X dari Y" di tiap halaman, font Newsreader & Plus
  Jakarta Sans tertanam;
- Ctrl+P (emulasi cetak Chromium): 2 halaman, logo pengganti judul, rekap di
  halaman 2, catatan kaki & nomor halaman di margin bawah tiap halaman.
