# Pemilihan Ketua & Wakil Ketua OSIS SMP 1 Dawe 2026

Aplikasi e-voting sekolah berbasis CodeIgniter 4 + MySQL untuk siswa dan guru
(identitas pemilih terpisah, satu suara aktif per pemilih, unlock hanya oleh admin
dengan audit log).

## Dokumen proyek

| File | Isi |
|---|---|
| `00-MASTER-PROJECT.md` | Spesifikasi utama (source of truth) |
| `01-FOUNDATION-DATABASE-AUTH.md` | Stage 1: fondasi, database, autentikasi |
| `STAGE1-NOTES.md` | Hasil Stage 1: instalasi, schema, route, test, handoff |
| `02-STUDENT-TEACHER-VOTING.md` | Stage 2: pengalaman voting |
| `STAGE2-NOTES.md` | Hasil Stage 2: alur voting, route, keamanan suara, test, handoff |
| `03-ADMIN-IMPORT-ANALYTICS.md` | Stage 3: panel admin, import, analytics |
| `STAGE3-NOTES.md` | Hasil Stage 3: panel admin, impor Excel, tema kandidat, analitik & live count, unlock, audit, test, handoff |
| `04-FINAL-INTEGRATION-TESTING-DEPLOYMENT.md` | Stage 4: audit akhir & deployment |

## Mulai cepat

Syarat: PHP 8.2+ (intl, mbstring, mysqli, gd, zip, fileinfo; exif disarankan),
Composer, MySQL 8.0+ / MariaDB 10.4+. Pengaturan `php.ini` untuk unggahan foto
kandidat ada di `STAGE3-NOTES.md` bagian 2.

```
composer install
copy .env.example .env        (Linux/macOS: cp .env.example .env)
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve
```

Detail instalasi Laragon, akun development, dan cara menjalankan test ada di
`STAGE1-NOTES.md`. Alur voting siswa/guru (kandidat, surat suara & paku coblos,
konfirmasi, pilihan terkunci) dijelaskan di `STAGE2-NOTES.md`. Panel admin
(dasbor & live count, analitik, pasangan calon, impor siswa/guru, jadwal,
unlock, audit log) dijelaskan di `STAGE3-NOTES.md`.

Akun contoh (data development, dibuat `DatabaseSeeder`; tidak untuk production):

| Peran | Login | Kode unik / sandi |
|---|---|---|
| Siswa | NISN `0000000001` | `05062013` |
| Guru | NIP `000000000000000004` | `01061992` |
| Admin | `/admin/login`, username `admin` | `admin123` |

Pemilih: setelah login, buka **Lihat kandidat & coblos** di dasbor.
Admin: dasbor live count di `/admin/dashboard`; template impor Excel dapat
diunduh dari menu **Siswa** / **Guru** > Impor.

## Lisensi

Framework CodeIgniter: MIT (`LICENSE`).
Font Inter dan Newsreader: SIL Open Font License (`public/assets/fonts/`).
