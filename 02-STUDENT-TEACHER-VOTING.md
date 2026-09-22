# STAGE 2
## STUDENT & TEACHER VOTING EXPERIENCE, INTERACTIVE CANDIDATES & LOCKED CHOICE
 
Baca:
1. `MASTER PROJECT`
2. `01-foundation-database-auth.md`
3. seluruh file aplikasi yang benar-benar dihasilkan Stage 1.
Jangan membuat project baru.
 
## OBJECTIVE
 
Bangun pengalaman voting lengkap untuk:
- siswa;
- guru.
Keduanya menggunakan election yang sama tetapi identitas pemilih tetap terpisah.
 
Fokus utama:
- mobile first;
- candidate presentation;
- interactive visi-misi;
- candidate-specific visual theme;
- modern countdown;
- interactive voting animation;
- vote confirmation;
- server-side transaction;
- lock;
- login ulang setelah vote hanya untuk melihat pilihannya.
## DEPENDENCIES
 
Gunakan file Stage 1.
 
Tambahkan library jika diperlukan:
- Three.js;
- GSAP;
- WebGL helper;
- canvas-confetti hanya bila tahap 2 membutuhkan preview/utility, tetapi final confetti terutama Stage 4.
Boleh menggunakan CSS 3D jika performa lebih baik.
 
## PUBLIC HERO
 
Bangun landing page:
- title;
- school;
- year;
- voting status;
- start time;
- end time;
- countdown;
- student login;
- teacher login.
Countdown:
- premium;
- kinetic;
- readable;
- mobile friendly;
- server-schedule driven.
Jangan gunakan gradient.
Jangan gunakan emoji.
 
## STUDENT LOGIN
 
Input:
- NISN;
- kodeunik.
Jika valid:
- session dibuat;
- siswa masuk dashboard.
## TEACHER LOGIN
 
Input:
- NIP;
- kodeunik.
Jika valid:
- teacher session dibuat;
- guru masuk teacher dashboard.
Jangan campurkan credential lookup siswa dan guru.
 
## VOTER DASHBOARD
 
Siswa melihat:
- nama;
- kelas;
- nomor absen;
- status vote.
Guru melihat:
- nama;
- status vote.
Sebelum memilih:
- CTA menuju election.
Setelah memilih:
- jangan tampilkan CTA vote.
## CANDIDATE EXPERIENCE
 
Tampilkan ketiga pasangan.
 
Setiap pasangan memiliki:
- nomor urut;
- foto ketua;
- foto wakil;
- nama;
- visi;
- misi;
- uploaded campaign background/art;
- accent color;
- theme asset.
Buat setiap pasangan punya art direction berbeda berdasarkan asset yang diupload admin.
 
Jangan membuat 3 card generik dengan warna berbeda saja.
 
## INTERACTIVE VISI MISI
 
Buat interaksi:
- reveal;
- expand;
- scroll animation;
- depth;
- parallax;
- animated typography.
Tetap sederhana pada perangkat lemah.
 
Gunakan `prefers-reduced-motion`.
 
## INTERACTIVE VOTING
 
Konsep:
pengguna seperti mengambil objek "paku/stempel" visual lalu menusukkannya ke area pasangan.
 
Implementasi:
- pointer/touch interaction;
- 3D object;
- visual depth;
- impact;
- candidate reaction.
Teknologi:
- Three.js/WebGL;
- CSS 3D;
- Canvas;
- GSAP.
Fallback wajib jika:
- WebGL tidak tersedia;
- performance lemah;
- browser tertentu bermasalah.
Fallback harus tetap memberikan pengalaman interaktif dengan CSS/2D.
 
Jangan menjadikan efek sebagai bagian dari security.
 
## CONFIRMATION
 
Sebelum final submit:
- tampilkan pasangan;
- foto;
- nomor;
- nama;
- warning lock.
Setelah confirm:
- request ke backend;
- backend revalidate;
- transaction;
- duplicate-vote check;
- insert vote;
- commit.
Simpan:
- voter identity;
- election;
- candidate;
- server timestamp;
- device info;
- browser info;
- lock status.
## DUPLICATE PROTECTION
 
Wajib aman terhadap:
- double click;
- duplicate POST;
- refresh;
- duplicate tabs;
- request manual;
- race condition.
Gunakan:
- DB constraint;
- transaction;
- server-side validation.
## LOGIN ULANG
 
Setelah voting:
- siswa boleh login lagi;
- guru boleh login lagi.
Tetapi mereka hanya dapat:
- melihat identitas sendiri;
- melihat pasangan yang dipilih;
- melihat waktu memilih;
- melihat status locked.
Tidak boleh:
- voting;
- mengganti;
- unlock;
- melihat analytics;
- melihat hasil suara seluruh election.
## ELECTION STATES
 
Backend:
- UPCOMING;
- ONGOING;
- FINISHED.
Jika UPCOMING:
- voting tidak tersedia.
Jika ONGOING:
- voting tersedia.
Jika FINISHED:
- voting tidak tersedia.
Countdown hanya visual.
 
## TEST STAGE 2
 
Test:
1. student login;
2. teacher login;
3. candidate rendering;
4. candidate-specific theme;
5. visi-misi interaction;
6. WebGL 3D voting animation;
7. fallback animation;
8. confirm;
9. transaction;
10. duplicate prevention;
11. lock;
12. re-login;
13. own-choice-only view;
14. schedule enforcement.
## OUTPUT FORMAT
 
Gunakan:
- STAGE OBJECTIVE
- DEPENDENCIES
- REQUIRED PREVIOUS FILES
- NEW FILES
- MODIFIED FILES
- DATABASE CHANGES
- ROUTES
- FULL IMPLEMENTATION
- TESTING
- EDGE CASES
- HANDOFF
Jika mengubah file Stage 1, tampilkan isi file lengkap.
 
## HANDOFF TO STAGE 3
 
Berikan daftar file yang wajib digunakan Stage 3:
- student voting controller/service;
- teacher voting controller/service;
- candidate model;
- election model/service;
- vote model;
- auth filters;
- vote views;
- theme asset handling;
- frontend JS untuk voting;
- frontend CSS;
- routes;
- migration baru bila ada.
