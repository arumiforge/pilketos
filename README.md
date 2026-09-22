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
| `04-FINAL-INTEGRATION-TESTING-DEPLOYMENT.md` | Stage 4: audit akhir & deployment |

## Mulai cepat

Syarat: PHP 8.2+ (intl, mbstring, mysqli), Composer, MySQL 8.0+ / MariaDB 10.4+.

```
composer install
copy .env.example .env        (Linux/macOS: cp .env.example .env)
php spark migrate
php spark db:seed DatabaseSeeder
php spark serve
```

Detail instalasi Laragon, akun development, dan cara menjalankan test ada di
`STAGE1-NOTES.md`. Alur voting siswa/guru (kandidat, surat suara & paku coblos,
konfirmasi, pilihan terkunci) dijelaskan di `STAGE2-NOTES.md`.

Akun contoh untuk mencoba voting (data development):

| Peran | Login | Kode unik |
|---|---|---|
| Siswa | NISN `0000000001` | `05062013` |
| Guru | NIP `000000000000000004` | `01061992` |

Setelah login, buka **Lihat kandidat & coblos** di dasbor.

## Lisensi

Framework CodeIgniter: MIT (`LICENSE`).
Font Inter dan Newsreader: SIL Open Font License (`public/assets/fonts/`).
