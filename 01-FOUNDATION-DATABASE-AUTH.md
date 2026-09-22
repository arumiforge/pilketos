# STAGE 1
## FOUNDATION, DATABASE, AUTHENTICATION & PROJECT CONTRACT
 
Baca `MASTER PROJECT` terlebih dahulu.
 
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
- PHP;
- Composer;
- MySQL.
Dependency PHP:
- CodeIgniter 4;
- PhpSpreadsheet boleh dipasang pada tahap ini atau Stage 3.
Dependency JS:
- tidak perlu memasang library visual berat pada tahap ini kecuali benar-benar diperlukan untuk shared setup.
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
- created_at
- updated_at
Constraint:
- nisn unique.
### teachers
- id
- nip
- name
- kodeunik
- status aktif bila digunakan
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
- theme_asset
- created_at
- updated_at
### elections
- id
- nama
- tahun
- start_at
- end_at
- status
- created_at
- updated_at
### votes
 
Pilih desain yang jelas untuk membedakan student vote dan teacher vote.
 
Pastikan desain mendukung:
- election_id;
- voter type/reference;
- candidate_id;
- voted_at;
- device_info;
- browser_info;
- status;
- created_at;
- updated_at.
Pastikan database dapat mencegah duplicate active vote.
 
### vote_unlock_logs
- id
- election_id
- student_id nullable
- teacher_id nullable
- admin_id
- reason
- unlocked_at
### audit_logs
Buat jika dibutuhkan sejak awal untuk aktivitas penting admin.
 
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
### Teacher
Login:
- NIP;
- kodeunik.
Student dan teacher authentication harus terpisah secara semantik.
 
Admin tidak boleh login melalui student/teacher login.
 
Student/teacher tidak boleh masuk `/admin/*`.
 
## SESSION
 
Session minimal menyimpan:
- user type;
- user id;
- authentication state.
Jangan menyimpan password atau kode unik di session jika tidak diperlukan.
 
## FILTER
 
Buat filter:
- AdminAuth;
- VoterAuth atau StudentAuth/TeacherAuth sesuai desain.
Pastikan route terproteksi.
 
## ROUTES
 
Siapkan minimal:
 
Public:
- `/`
- `/student/login`
- `/teacher/login`
Student:
- `/student/dashboard`
- `/student/logout`
Teacher:
- `/teacher/dashboard`
- `/teacher/logout`
Admin:
- `/admin/login`
- `/admin/dashboard`
- `/admin/logout`
Voting routes dapat disiapkan struktur awal untuk Stage 2.
 
## FRONTEND FOUNDATION
 
Buat:
- base layout;
- typography;
- spacing;
- buttons;
- forms;
- card;
- modal dasar;
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
 
## SEEDER
 
Sediakan:
- 1 admin development;
- 3 pasangan kandidat;
- 1 election 2026;
- beberapa siswa;
- beberapa guru.
Pastikan data seed mudah diketahui sebagai sample development.
 
## TEST STAGE 1
 
Wajib dapat diuji:
- koneksi database;
- migration;
- seeder;
- admin login;
- student login;
- teacher login;
- invalid credential;
- route protection;
- session;
- CSRF.
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
 
