# STAGE 4
## FINAL INTEGRATION, SECURITY, PERFORMANCE, POLISH, FINAL RESULT & DEPLOYMENT
 
Baca:
1. MASTER PROJECT
2. STAGE 1
3. STAGE 2
4. STAGE 3
5. seluruh source file aktual.
Jangan membuat project baru.
 
## OBJECTIVE
 
Tahap terakhir adalah audit dan penyelesaian produk agar dapat dipakai sebagai sistem e-voting sekolah.
 
## 1. FULL FLOW REVIEW
 
Pastikan alur:
 
Landing
→ student/teacher login
→ candidate experience
→ interactive visi-misi
→ interactive 3D voting
→ confirmation
→ server save
→ locked
→ re-login
→ own vote only
→ admin analytics
→ admin unlock when necessary
→ election end
→ final results
→ confetti.
 
## 2. SECURITY AUDIT
 
Review dan perbaiki:
- CSRF;
- session fixation;
- session handling;
- auth;
- authorization;
- XSS;
- SQL injection;
- file upload;
- Excel upload;
- IDOR;
- duplicate vote;
- replay POST;
- race conditions;
- direct API endpoint access;
- mass assignment;
- brute force login;
- insecure localStorage;
- sensitive information leakage.
Pastikan student tidak dapat:
- masuk route teacher;
- masuk route admin;
- membaca analytics;
- membaca vote orang lain;
- vote dua kali.
Pastikan teacher tidak dapat:
- masuk route admin;
- membaca analytics;
- vote dua kali.
Admin tidak dapat:
- memilih atas nama voter.
## 3. VOTE INTEGRITY
 
Pastikan state transitions valid:
 
Before:
`NO_ACTIVE_VOTE`
 
After:
`LOCKED`
 
Unlock:
`NO_ACTIVE_VOTE` untuk hak pilih aktif, tetapi riwayat tetap dapat diaudit.
 
Re-vote:
`LOCKED` dengan vote baru.
 
Jangan menghapus audit secara sembarangan.
 
Gunakan transaction dan DB constraint.
 
## 4. FINAL RESULT LOGIC
 
Ketika current server time >= end_at:
- election dianggap selesai;
- voting endpoint menolak;
- dashboard admin menampilkan final state;
- final analytics dihitung dari data yang valid.
Final result hanya aktif jika election benar-benar selesai.
 
## 5. CONFETTI
 
Tambahkan effect confetti hanya pada final result screen ketika:
- election FINISHED;
- final result data available.
Ketentuan:
- no emoji;
- canvas-based/confetti library;
- one-time trigger per page/session;
- respect reduced motion;
- jangan mengganggu interaksi.
Confetti tidak boleh aktif pada dashboard analytics biasa.
 
## 6. FINAL CANDIDATE EXPERIENCE
 
Pastikan candidate-specific theme benar-benar digunakan.
 
Setiap pasangan boleh memiliki:
- background berbeda;
- visual artwork;
- image composition;
- solid accent;
- motion;
- texture.
Namun tetap menjaga:
- readability;
- accessibility;
- mobile performance.
Jangan menggunakan gradient generik sebagai solusi mudah.
 
## 7. 3D VOTING PERFORMANCE
 
Audit Three.js/WebGL/CSS 3D experience.
 
Optimasi:
- lazy-load 3D;
- compress/resize image;
- limit render loop;
- dispose geometry/material;
- disable unnecessary postprocessing;
- use reduced motion;
- fallback CSS/2D.
Pastikan low-end Android tetap bisa voting.
 
Jika visual 3D gagal:
- voting tetap dapat dilakukan melalui fallback.
## 8. COUNTDOWN
 
Countdown:
- berasal dari server time;
- tidak dapat memodifikasi election;
- berhenti pada final state;
- tidak menyebabkan negative countdown;
- tidak membuat polling atau rendering berlebihan.
## 9. ANALYTICS VERIFICATION
 
Cross-check:
- overall;
- student;
- teacher;
- gender;
- class;
- grade;
- candidate;
- voted/not voted.
Semua angka harus berasal dari query yang konsisten.
 
Pastikan:
`student voted + student not voted = total active students`
 
dan:
`teacher voted + teacher not voted = total active teachers`
 
dan:
`active student votes + active teacher votes = total election votes`
 
kecuali ada status bisnis khusus yang secara eksplisit didokumentasikan.
 
## 10. IMPORT VERIFICATION
 
Test:
- empty Excel;
- invalid header;
- duplicate NISN;
- duplicate NIP;
- invalid gender;
- empty name;
- missing class;
- invalid code;
- leading zero;
- large file;
- repeated import.
Jangan membuat import menghasilkan duplicate records tanpa penanganan yang jelas.
 
## 11. UX
 
Periksa:
- loading;
- disabled state;
- confirmation;
- success;
- error;
- empty;
- no result;
- table;
- mobile;
- admin desktop.
No emoji anywhere.
 
No generic gradient.
 
## 12. ACCESSIBILITY
 
Implement:
- keyboard;
- focus;
- reduced motion;
- semantic HTML;
- alt;
- contrast;
- screen-reader friendly status.
## 13. PERFORMANCE
 
Review:
- DB indexes;
- aggregation queries;
- pagination;
- asset loading;
- image optimization;
- live polling;
- caching only if necessary.
Analytics tidak boleh mengambil seluruh dataset lalu menghitung semuanya di browser jika dapat dihitung di MySQL.
 
## 14. LARAGON DEPLOYMENT
 
Dokumentasikan:
 
`C:\laragon\www\smp1dawe-osis-2026`
 
Setup:
- Apache;
- MySQL;
- `.env`;
- database;
- composer;
- migration;
- seeder;
- writable;
- upload directory;
- public folder;
- virtual host.
## 15. FINAL DOCUMENTATION
 
Buat:
`README.md`
 
Isi:
- overview;
- architecture;
- requirements;
- install;
- env;
- database;
- admin;
- student;
- teacher;
- candidate management;
- import;
- voting;
- unlock;
- analytics;
- final result;
- backup;
- troubleshooting.
## 16. FINAL FILE TREE
 
Tampilkan final project tree.
 
## 17. FINAL ROUTE MATRIX
 
Tampilkan:
- public;
- student;
- teacher;
- admin;
- API/AJAX.
## 18. ROLE PERMISSION MATRIX
 
Pastikan:
 
| Capability | Student | Teacher | Admin |
|---|---:|---:|---:|
| Login Student | Yes | No | No |
| Login Teacher | No | Yes | No |
| Admin Login | No | No | Yes |
| View Candidates | Yes | Yes | Yes |
| Vote | Yes | Yes | No |
| Re-vote while locked | No | No | No |
| View Own Vote | Yes | Yes | No/Not Applicable |
| View Analytics | No | No | Yes |
| Import Students | No | No | Yes |
| Import Teachers | No | No | Yes |
| Manage Candidates | No | No | Yes |
| Election Control | No | No | Yes |
| Unlock Vote | No | No | Yes |
| Audit Log | No | No | Yes |
 
## 19. FINAL TEST SUITE
 
Buat checklist end-to-end:
- clean installation;
- migrations;
- seed;
- student login;
- teacher login;
- admin login;
- upcoming;
- ongoing;
- finished;
- student vote;
- teacher vote;
- duplicate prevention;
- re-login;
- own vote only;
- unlock;
- re-vote;
- analytics;
- gender;
- class;
- grade;
- teacher analytics;
- combined analytics;
- live count;
- final result;
- confetti;
- mobile;
- desktop;
- reduced motion;
- WebGL fallback.
## 20. OUTPUT FORMAT
 
Berikan:
1. FINAL AUDIT
2. SECURITY FINDINGS
3. FIXES
4. NEW FILES
5. MODIFIED FILES
6. FULL FILE CONTENT
7. ROUTES
8. MIGRATIONS
9. TEST RESULTS
10. DEPLOYMENT
11. README
12. FINAL PROJECT TREE
13. KNOWN LIMITATIONS
Jangan mengklaim test berhasil bila belum dijalankan.
 
Jika ada bagian yang belum bisa diverifikasi, nyatakan secara eksplisit.
 
## FINAL QUALITY BAR
 
Produk akhir harus:
- kreatif;
- unik;
- premium;
- tidak generik;
- tidak terasa dibuat oleh template AI;
- tidak menggunakan emoji;
- tidak menggunakan gradient generik;
- memiliki interaksi kandidat yang kuat;
- memiliki visi-misi interaktif;
- memiliki countdown modern;
- memiliki pengalaman voting 3D/WebGL dengan fallback;
- memiliki candidate-specific visual identity;
- memiliki confetti pada final result;
- aman;
- terintegrasi;
- maintainable;
- dapat dijalankan di Laragon.
