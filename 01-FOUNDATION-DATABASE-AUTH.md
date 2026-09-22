# STAGE 1
## FOUNDATION, DATABASE, AUTHENTICATION & PROJECT CONTRACT
 
Baca `00-MASTER-PROJECT.md` terlebih dahulu.
 
Hasil implementasi aktual dan handoff Stage 1: `STAGE1-NOTES.md`.
 
## OBJECTIVE
 
Bangun fondasi proyek Sistem Pemilihan Ketua OSIS SMP 1 Dawe 2026.
 
Tahap ini mencakup:
- CodeIgniter 4;
- MySQL;
- Composer;
- Laragon;
- database migration;
- models;
- seeders;
- admin authentication;
- student authentication;
- teacher authentication;
- authorization;
- session;
- CSRF;
- base frontend structure;
- desain dasar hitam-putih tanpa gradient;
- kontrak file untuk Stage 2.
Jangan membangun analytics lengkap dan jangan membangun seluruh voting experience pada tahap ini.
 
## DEPENDENCIES
 
Environment:
- Windows;
- Laragon;
- PHP 8.2 atau lebih baru (wajib untuk CodeIgniter 4.7; tambahkan versi PHP baru di Laragon bila bawaan masih 8.1);
- ekstensi PHP: intl, mbstring, mysqli;
- Composer;
- MySQL 8.0+ atau MariaDB 10.4+ (butuh generated column dan CHECK constraint).
Dependency PHP:
- CodeIgniter 4 (folder `system/` sudah ada di repository);
- PHPUnit (require-dev, untuk test otomatis);
- PhpSpreadsheet dipasang pada Stage 3.
Dependency JS:
- tidak perlu memasang library visual berat pada tahap ini kecuali benar-benar diperlukan untuk shared setup.
Font:
- Inter dan Newsreader di-host lokal (`public/assets/fonts`, lisensi OFL) agar halaman tidak bergantung CDN pada hari pemilihan.
## REQUIRED PREVIOUS FILES
 
Untuk Stage 1, tidak ada file aplikasi sebelumnya.
 
Gunakan spesifikasi MASTER sebagai source of truth.
 
## DATABASE DESIGN
 
Implementasikan migration minimal untuk:
 
### admins
- id
- name
- username/email
- password_hash
- created_at
- updated_at
### students
- id
- nisn
- name
- jenis_kelamin
- kelas
- nomor_absen nullable
- kodeunik
- status_aktif (default 1)
- created_at
- updated_at
Constraint:
- nisn unique.
### teachers
- id
- nip
- name
- kodeunik
- status_aktif (default 1)
- created_at
- updated_at
Constraint:
- nip unique.
### candidates
- id
- nomor_urut
- nama_ketua
- nama_wakil
- foto_ketua
- foto_wakil
- visi
- misi
- theme_name
- theme_background
- theme_accent
- theme_asset (JSON: hero, texture, artwork, poster)
- status_aktif
- created_at
- updated_at
Constraint:
- nomor_urut unique.
### elections
- id
- nama
- tahun
- start_at
- end_at
- status
- created_at
- updated_at
Constraint:
- CHECK `end_at > start_at`.
Status adalah cermin jadwal. Sumber kebenaran tetap `start_at`/`end_at` terhadap waktu server (`ElectionModel::resolveStatus()`), lalu disinkronkan ke kolom `status`.
### votes
 
Keputusan desain: **dua tabel terpisah** `student_votes` dan `teacher_votes` agar foreign key ke `students`/`teachers` tetap valid di database dan vote siswa tidak mungkin tertukar dengan vote guru.
 
Kolom:
- election_id;
- student_id / teacher_id;
- candidate_id;
- status: `LOCKED` (suara aktif) atau `UNLOCKED` (dibuka admin, disimpan sebagai riwayat);
- active_lock: generated column, bernilai 1 bila LOCKED dan NULL bila UNLOCKED;
- voted_at;
- unlocked_at nullable;
- device_info;
- browser_info;
- created_at;
- updated_at.
Constraint:
- unique `(election_id, student_id, active_lock)` / `(election_id, teacher_id, active_lock)`: database menolak suara aktif kedua, termasuk pada race condition;
- baris vote tidak pernah dihapus. Unlock mengubah status menjadi `UNLOCKED`, re-vote membuat baris `LOCKED` baru;
- hasil dan analytics hanya menghitung `status = 'LOCKED'`.
### vote_unlock_logs
- id
- election_id
- student_id nullable
- teacher_id nullable
- student_vote_id nullable (baris vote yang di-unlock)
- teacher_vote_id nullable (baris vote yang di-unlock)
- admin_id
- reason
- unlocked_at
Constraint:
- CHECK: tepat satu pasangan `(student_id, student_vote_id)` atau `(teacher_id, teacher_vote_id)` yang terisi.
### audit_logs
- id
- admin_id
- action
- description
- created_at
### Kebijakan foreign key
 
Semua foreign key memakai `ON UPDATE RESTRICT ON DELETE RESTRICT`:
- siswa/guru/kandidat/election/admin yang sudah punya suara atau log tidak dapat dihapus (gunakan `status_aktif = 0`);
- MySQL 8 menolak CHECK constraint pada kolom yang FK-nya memakai CASCADE/SET NULL (error 3823).
Perhatian: urutan argumen CodeIgniter adalah `addForeignKey(field, table, tableField, onUpdate, onDelete)`.
 
Koneksi database wajib `strictOn = true`. Bila false, CodeIgniter menghapus `STRICT_TRANS_TABLES` sehingga data tidak valid dipotong diam-diam.
 
## AUTHENTICATION
 
### Admin
Login:
- username/email;
- password.
Password wajib hash.
 
### Student
Login:
- NISN;
- kodeunik.
Hanya siswa `status_aktif = 1`.
 
### Teacher
Login:
- NIP;
- kodeunik.
Hanya guru `status_aktif = 1`.
 
Kode unik divalidasi sebagai 8 digit (DDMMYYYY). Input `01-03-2013` atau `01/03/2013` dinormalisasi menjadi `01032013`.
 
Student dan teacher authentication harus terpisah secara semantik.
 
Admin tidak boleh login melalui student/teacher login.
 
Student/teacher tidak boleh masuk `/admin/*`.
 
### Login throttling
- dihitung dari login **gagal**;
- per akun (NISN/NIP/username): 5 kegagalan, lalu 1 percobaan per menit;
- per IP: 30 kegagalan per menit, sengaja longgar karena satu sekolah bisa berbagi satu IP;
- key cache di-hash (IPv6 seperti `::1` mengandung karakter terlarang untuk key cache).
## SESSION
 
Session hanya menyimpan:
- `user_type` (`admin` / `student` / `teacher`);
- `admin_id` / `student_id` / `teacher_id`;
- `isLoggedIn`.
Data profil (nama, kelas, dll) selalu dibaca ulang dari database.
 
Jangan menyimpan password atau kode unik di session. Karena itu form login tidak memakai `withInput()` (fungsi itu menyalin seluruh POST ke session); hanya identifier yang di-flash untuk isian ulang.
 
Setiap login: identitas role lain dihapus dan session id di-regenerate.
 
## FILTER
 
Buat filter:
- AdminAuth;
- StudentAuth;
- TeacherAuth.
Filter memeriksa `user_type` di session **dan** memastikan akun masih ada/aktif di database. Request AJAX/JSON yang ditolak menerima `401` JSON, bukan redirect HTML.
 
Pastikan route terproteksi.
 
## CSRF
 
- mode `session`, token tetap per sesi (`regenerate = false`) agar tab ganda dan request AJAX tidak gagal;
- nama field `csrf_token`, header `X-CSRF-TOKEN`;
- layout memuat `csrf_meta()`; JS membaca token lewat `App.jsonHeaders()`.
## ROUTES
 
Siapkan minimal:
 
Public:
- `/`
- `/student/login`
- `/teacher/login`
Student:
- `/student/dashboard`
- `/student/logout` (POST)
Teacher:
- `/teacher/dashboard`
- `/teacher/logout` (POST)
Admin:
- `/admin/login`
- `/admin/dashboard`
- `/admin/logout` (POST)
Voting routes dapat disiapkan struktur awal untuk Stage 2.
 
## FRONTEND FOUNDATION
 
Buat:
- base layout;
- typography;
- spacing;
- buttons;
- forms;
- card;
- modal dasar (elemen `<dialog>` native);
- responsive breakpoint;
- navigation foundation.
Tema:
- hitam;
- putih;
- grayscale;
- solid colors.
Tidak boleh menggunakan gradient.
 
Tidak boleh menggunakan emoji.
 
Boleh menggunakan SVG/icon library.
 
## KONFIGURASI WAKTU & URL
 
- `appTimezone = Asia/Jakarta`; seluruh waktu ditulis dari PHP (`Time::now()`), bukan `NOW()` MySQL;
- `indexPage = ''` (URL bersih, butuh mod_rewrite Apache Laragon atau `php spark serve`);
- locale `id` untuk format tanggal Indonesia.
## SEEDER
 
Sediakan:
- 1 admin development;
- 3 pasangan kandidat;
- 1 election 2026;
- beberapa siswa;
- beberapa guru.
Pastikan data seed mudah diketahui sebagai sample development (NISN `00000000xx`, NIP `0000...0x`). Seeder ditolak bila `CI_ENVIRONMENT = production`.
 
## TEST STAGE 1
 
Wajib dapat diuji (otomatis dengan PHPUnit):
- koneksi database;
- migration;
- seeder;
- admin login;
- student login;
- teacher login;
- invalid credential;
- route protection;
- session;
- CSRF;
- satu suara aktif per pemilih (constraint database).
## OUTPUT FORMAT
 
Berikan:
 
1. STAGE OBJECTIVE
2. DEPENDENCIES
3. DATABASE SCHEMA
4. FILE TREE
5. NEW FILES
6. FULL FILE CONTENT
7. ROUTES
8. CONFIGURATION
9. SEED DATA
10. TEST CHECKLIST
11. HANDOFF
Jangan memberi pseudo-code.
 
Jangan menggunakan placeholder implementasi.
 
## HANDOFF TO STAGE 2
 
Akhiri dengan daftar lengkap file yang wajib dipertahankan dan dibaca oleh Stage 2.
 
Kelompokkan:
- Config;
- migrations;
- models;
- filters;
- controllers;
- views;
- CSS;
- JS;
- routes;
- seeders.
