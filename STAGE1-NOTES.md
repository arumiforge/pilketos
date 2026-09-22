# Stage 1 — Foundation, Database & Auth

## Cara pakai (Laragon)

1. `composer create-project codeigniter4/appstarter smp1dawe-osis-2026`
   di dalam `C:\laragon\www\`.
2. Timpa/gabungkan folder `app/` dan `public/assets/` dari paket ini ke
   dalam project hasil langkah 1 (Config/Routes.php dan Config/Filters.php
   di paket ini MENGGANTIKAN file default appstarter).
3. Salin `env` bawaan appstarter menjadi `.env`, lalu isi sesuai contoh
   pada `.env.example` di paket ini.
4. Buat database MySQL kosong: `smp1dawe_osis_2026` (lewat HeidiSQL/phpMyAdmin Laragon).
5. Jalankan:
   ```
   composer install
   php spark migrate
   php spark db:seed DatabaseSeeder
   php spark serve
   ```
6. Akses `http://localhost:8080/`.

## Akun development (dari seeder)

- Admin: username `admin`, password `admin123`
- Siswa: NISN `0081234561` s/d `0081234572`, kodeunik = tanggal lahir (lihat `StudentSeeder.php`)
- Guru: NIP lihat `TeacherSeeder.php`, kodeunik = tanggal lahir

Kredensial ini hanya untuk pengembangan lokal.

## Desain kunci Stage 1

- **Votes**: dua tabel terpisah `student_votes` dan `teacher_votes`
  (bukan satu tabel polymorphic) agar foreign key ke `students`/`teachers`
  tetap valid di level database. Baris vote hanya ada selama hak suara
  terkunci; unlock akan mengarsipkan pilihan sebelumnya ke
  `vote_unlock_logs.previous_candidate_id` lalu menghapus baris aktifnya,
  sehingga "1 suara aktif" cukup dijaga dengan unique key
  `(election_id, student_id)` / `(election_id, teacher_id)`.
- **Kredensial DB** hanya di `.env`, tidak pernah di source code.
- **Kode unik & NISN/NIP** disimpan sebagai VARCHAR, bukan angka.
- Config `Database.php` dan `Security.php` dibiarkan default framework
  (tidak perlu diubah untuk Stage 1).
