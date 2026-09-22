# STAGE 3
## ADMIN PANEL, IMPORT STUDENTS & TEACHERS, CANDIDATE THEMING, ANALYTICS, LIVE COUNT & UNLOCK
 
Baca:
1. MASTER PROJECT
2. STAGE 1
3. STAGE 2
4. semua source file aktual dari project.
Jangan membuat project baru.
Jangan menghapus behavior voting yang sudah bekerja.
 
## OBJECTIVE
 
Bangun panel admin lengkap.
 
Admin adalah satu-satunya role pengelola.
 
Guru hanya pemilih, bukan admin.
 
## ADMIN DASHBOARD
 
Layout:
- responsive sidebar;
- topbar;
- content area;
- mobile drawer.
Visual:
- black/white/gray;
- solid;
- modern;
- editorial;
- no gradient;
- no emoji.
Tidak boleh terlihat seperti dashboard template AI generik.
 
## DASHBOARD SUMMARY
 
Tampilkan:
- status election;
- start;
- end;
- total siswa;
- total guru;
- total pemilih;
- siswa sudah memilih;
- siswa belum memilih;
- guru sudah memilih;
- guru belum memilih;
- total suara;
- suara kandidat 1/2/3;
- persentase kandidat.
## CANDIDATE MANAGEMENT
 
CRUD:
- nomor urut;
- nama ketua;
- nama wakil;
- visi;
- misi;
- foto ketua;
- foto wakil;
- theme name;
- accent solid color;
- hero/background image;
- campaign artwork;
- optional texture;
- optional layout mode.
Upload admin.
 
Validasi:
- MIME;
- extension;
- size;
- filename;
- image dimensions;
- safe storage.
Candidate theme harus dipakai oleh frontend Stage 2.
 
## STUDENT MANAGEMENT
 
Fitur:
- listing;
- search;
- filter kelas;
- filter jenis kelamin;
- filter status voting;
- detail.
Field:
- NISN;
- nama;
- jenis kelamin;
- kelas;
- nomor absen;
- kode unik;
- status memilih.
## TEACHER MANAGEMENT
 
Fitur:
- listing;
- search;
- status vote;
- detail;
- import.
Field:
- NIP;
- nama;
- kode unik;
- status vote.
Teacher tetap dipisahkan dari student.
 
## IMPORT EXCEL STUDENTS
 
Gunakan PhpSpreadsheet.
 
Template:
`student-import-template.xlsx`
 
Header:
`no, NISN, nama, jenis_kelamin, kelas, nomor_absen, kodeunik`
 
Flow:
Download template
→ Upload
→ Preview
→ Validate
→ Import
→ Result
 
Validasi:
- header;
- NISN;
- duplicate;
- jenis kelamin;
- kelas;
- nomor absen;
- kode unik;
- leading zero.
Jangan kehilangan leading zero.
 
## IMPORT EXCEL TEACHERS
 
Template:
`teacher-import-template.xlsx`
 
Header:
`no, NIP, nama, kodeunik`
 
Flow sama.
 
Validasi:
- NIP;
- duplicate;
- nama;
- kode unik;
- leading zero.
## ELECTION CONTROL
 
Admin dapat:
- start_at;
- end_at;
- election name;
- year;
- state.
Jangan mengandalkan browser clock.
 
## ANALYTICS
 
Analytics hanya admin.
 
### 1. Overall
 
Gabungkan student + teacher untuk total election:
- total voter;
- total voted;
- total not voted;
- vote per candidate;
- percent per candidate.
### 2. Voter type
 
Bandingkan berdasarkan tipe:
- siswa;
- guru.
Contoh tabel:
Type | Total | Voted | Not Voted | Candidate 1 | Candidate 2 | Candidate 3
 
### 3. Student gender
 
Gunakan `jenis_kelamin`.
 
Kelompok:
- L;
- P.
Untuk masing-masing:
- total siswa;
- voted;
- not voted;
- candidate 1;
- candidate 2;
- candidate 3;
- percentages.
### 4. Student class
 
Kolom:
- kelas;
- total;
- voted;
- not voted;
- candidate 1;
- candidate 2;
- candidate 3.
### 5. Grade
 
Extract grade 7/8/9 dari kelas secara aman.
 
Kolom:
- jenjang;
- total;
- voted;
- not voted;
- candidate 1;
- candidate 2;
- candidate 3.
### 6. Detailed votes
 
Fields:
- no;
- voter type;
- nama;
- NISN/NIP;
- kelas;
- nomor absen;
- jenis kelamin siswa;
- candidate;
- waktu;
- device/HP;
- browser;
- status.
Tambahkan:
- search;
- filter voter type;
- filter kelas;
- filter gender;
- filter candidate;
- pagination.
## CHARTS
 
Boleh:
- doughnut;
- bar;
- horizontal bar;
- stacked bar.
Gunakan satu visual language.
 
Jangan terlalu banyak chart.
 
## LIVE COUNT
 
Implementasikan endpoint AJAX khusus.
 
Update:
- summary cards;
- candidate counts;
- percentages;
- selected charts;
- class recap;
- grade recap.
Jangan reload seluruh page.
 
## UNLOCK
 
Search voter.
 
Lihat:
- voter;
- selected candidate;
- time;
- lock status.
Action:
`Unlock Hak Suara`
 
Setelah klik:
- wajib reason;
- confirmation;
- transaction;
- ubah state;
- simpan audit log.
Admin tidak boleh:
- mengubah pilihan kandidat;
- membuat vote manual.
Setelah unlock:
- voter login sendiri;
- voter memilih sendiri.
## AUDIT LOG
 
Tampilkan:
- date/time;
- admin;
- voter;
- voter type;
- election;
- reason;
- action.
## TEST STAGE 3
 
Test:
- dashboard;
- student import;
- teacher import;
- candidate upload;
- candidate theme;
- election schedule;
- analytics overall;
- analytics by gender;
- analytics by voter type;
- analytics by class;
- analytics by grade;
- live count;
- detail votes;
- unlock;
- audit.
## OUTPUT FORMAT
 
Gunakan:
1. STAGE OBJECTIVE
2. DEPENDENCIES
3. REQUIRED PREVIOUS FILES
4. DATABASE CHANGES
5. NEW FILES
6. MODIFIED FILES
7. ROUTES
8. FULL IMPLEMENTATION
9. TESTING
10. HANDOFF
Jangan pseudo-code.
 
Jangan memberi potongan file yang tidak lengkap.
 
## HANDOFF TO STAGE 4
 
Berikan daftar file final yang wajib diaudit pada Stage 4:
- auth;
- student;
- teacher;
- candidate;
- election;
- vote;
- analytics;
- import;
- unlock;
- audit;
- upload;
- routes;
- migrations;
- models;
- services;
- views;
- JS/CSS.
