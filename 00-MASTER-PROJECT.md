# MASTER PROJECT
## Sistem Pemilihan Ketua OSIS SMP 1 Dawe Tahun 2026
 
## 1. KONTEKS UTAMA
 
Bangun aplikasi web e-voting untuk Pemilihan Ketua OSIS dan Wakil Ketua OSIS SMP 1 Dawe Tahun 2026.
 
Sistem memiliki dua role aplikasi:
- Siswa
- Admin
Guru memiliki hak suara seperti siswa, tetapi data guru, autentikasi guru, dan identitas pemilih guru harus dikelola sebagai entitas terpisah dari siswa. Guru tidak menjadi role admin dan tidak memiliki akses panel admin.
 
Terdapat 3 pasangan calon Ketua OSIS dan Wakil Ketua OSIS.
 
Setiap pemilih, baik siswa maupun guru, memiliki tepat 1 hak suara aktif pada election yang sama:
- siswa: 1 suara;
- guru: 1 suara.
Siswa dan guru login menggunakan identitas masing-masing:
- siswa: NISN + kode unik;
- guru: NIP + kode unik.
Kode unik menggunakan format tanggal lahir sebagai string, contoh `01032013` atau `01032006`, sehingga leading zero harus dipertahankan.
 
Setelah pemilih memberikan suara:
- vote aktif dikunci;
- pemilih tetap dapat login lagi;
- pemilih tidak dapat melakukan voting lagi;
- pemilih hanya dapat melihat pilihan yang sebelumnya sudah diberikan;
- siswa/guru tidak boleh melihat hasil suara pemilih lain atau analytics admin.
Admin dapat melakukan unlock/reset hak pilih bila pemilih benar-benar salah memilih. Admin tidak boleh memilihkan pasangan untuk pemilih. Admin hanya membuka kembali hak pilih, lalu pemilih harus login dan melakukan voting sendiri.
 
Setiap tindakan unlock wajib dicatat di audit log.
 
## 2. TUJUAN
 
Alur utama:
 
Admin menyiapkan data → import data siswa dan guru → mengatur 3 pasangan calon → upload foto kandidat dan tema visual kandidat → menentukan jadwal → membuka pemilihan → siswa/guru login → melihat kandidat dan visi-misi interaktif → memilih → visual voting interaktif → vote tersimpan dan terkunci → pemilih dapat login kembali hanya untuk melihat pilihannya → admin memonitor live count dan analytics → election berakhir → hasil final tersedia → confetti celebration ditampilkan pada halaman hasil final.
 
## 3. TEKNOLOGI
 
Backend:
- PHP
- CodeIgniter 4
- MySQL
- Composer
Development:
- Windows
- Laragon
- PHP + Composer ready
Frontend:
- HTML
- CSS
- JavaScript
Library JS/PHP boleh digunakan.
 
Library yang boleh dipilih sesuai kebutuhan:
- Chart.js atau Apache ECharts
- Three.js
- WebGL
- GSAP
- Lottie
- PhpSpreadsheet
- library CodeIgniter 4 yang relevan
Gunakan dependency hanya jika memberikan nilai nyata. Jangan menambah library sekadar dekorasi.
 
Prioritaskan CodeIgniter 4 + HTML + CSS + JavaScript.
 
## 4. ATURAN DESAIN VISUAL
 
Identitas visual:
- modern;
- editorial;
- premium;
- eksperimental tetapi tetap usable;
- clean;
- strong typography;
- responsive;
- mobile first.
Inspirasi struktur:
- Vercel / modern developer products;
- modern editorial interfaces;
- premium interactive campaign microsite.
Tetapi JANGAN membuat tampilan sebagai clone Vercel.
 
### Warna
 
Base interface:
- hitam;
- putih;
- abu-abu;
- neutral shades.
Jangan menggunakan:
- gradient warna generik;
- glassmorphism berlebihan;
- neon gradient;
- kombinasi warna yang terasa seperti template AI.
Tidak boleh menggunakan background gradient generik untuk menciptakan kesan "modern".
 
Setiap pasangan calon justru memiliki identitas visual sendiri.
 
### Aturan penting
 
Jangan menggunakan emoji apa pun di UI, copywriting, toast, tombol, empty state, atau dashboard.
 
Gunakan icon library jika membutuhkan ikon, atau SVG yang dibuat secara konsisten.
 
## 5. HERO BERANDA
 
Beranda harus mempunyai hero utama yang kuat.
 
Konten:
- Pemilihan Ketua & Wakil Ketua OSIS
- SMP 1 Dawe
- Tahun 2026
- status pemilihan;
- waktu mulai;
- waktu selesai;
- countdown modern;
- tombol masuk siswa;
- tombol masuk guru;
- kandidat teaser bila relevan.
Countdown harus terasa seperti bagian dari art direction, bukan sekadar angka kecil.
 
Gunakan:
- segmented digits;
- flip/odometer style;
- kinetic typography;
- SVG animation;
- canvas;
- WebGL bila membantu.
Jangan menggunakan gradient hanya untuk membuat countdown terlihat modern.
 
## 6. TIGA PASANGAN CALON
 
Ada 3 pasangan.
 
Data setiap pasangan minimal:
- nomor urut;
- nama ketua;
- nama wakil;
- foto ketua;
- foto wakil;
- visi;
- misi;
- tema visual;
- background/artwork;
- accent color non-gradient;
- optional texture;
- optional campaign artwork;
- status aktif.
Tema pasangan harus dapat dibuat admin melalui upload.
 
Admin dapat mengunggah asset seperti:
- foto ketua;
- foto wakil;
- hero image;
- background;
- campaign artwork;
- texture;
- poster.
Frontend menggunakan asset tersebut untuk membangun identitas visual masing-masing pasangan.
 
Jangan memaksakan semua pasangan memakai layout visual yang identik. Gunakan shared component system, tetapi beri ruang untuk art direction per pasangan.
 
## 7. VISI MISI INTERAKTIF
 
Visi dan misi tidak hanya ditampilkan sebagai text card biasa.
 
Buat pengalaman interaktif:
- expandable editorial panel;
- horizontal/vertical reveal;
- scroll-linked animation;
- card transition;
- typography transition;
- interactive highlights;
- subtle depth/parallax;
- optional 3D scene.
Interaksi harus tetap dapat dipakai di HP dan tidak mengganggu kemampuan memilih.
 
Prioritaskan performance.
 
## 8. VOTING EXPERIENCE INTERAKTIF
 
Konsep utama visual voting:
 
Pemilih memilih pasangan dengan pengalaman seperti mengambil paku/stempel visual lalu menusukkannya ke papan suara pasangan yang dipilih.
 
Ini adalah metafora UI, bukan objek fisik nyata.
 
Gunakan teknologi yang sesuai:
- Three.js;
- WebGL;
- CSS 3D;
- Canvas;
- GSAP.
Contoh alur:
1. pemilih menyentuh/menahan tombol pilih;
2. objek 3D voting tool/paku visual muncul;
3. objek mengikuti pointer/touch;
4. pemilih mengarahkan ke kandidat;
5. objek melakukan gerakan tusuk/stamp;
6. kandidat menerima impact animation;
7. visual confirmation;
8. modal konfirmasi;
9. setelah konfirmasi server menyimpan vote;
10. vote menjadi LOCKED.
Fallback wajib:
Jika WebGL tidak tersedia atau perangkat terlalu lemah:
- gunakan CSS 3D atau 2D animation;
- jangan membuat voting gagal hanya karena efek visual.
Efek tidak boleh mengubah mekanisme keamanan.
 
## 9. KONFIRMASI VOTING
 
Sebelum final submit:
 
Tampilkan:
- nomor kandidat;
- nama ketua;
- nama wakil;
- foto;
- pernyataan bahwa pilihan setelah dikonfirmasi akan dikunci.
Tombol utama:
`KONFIRMASI PILIHAN`
 
Setelah server berhasil:
`SUARA BERHASIL DISIMPAN`
 
Kemudian:
`Hak suara Anda telah dikunci.`
 
## 10. LOGIN ULANG SETELAH MEMILIH
 
Siswa atau guru boleh login lagi setelah memilih.
 
Jika sudah memilih:
- jangan tampilkan voting form;
- jangan tampilkan tombol memilih;
- jangan memungkinkan submit vote baru;
- tampilkan hasil pilihannya sendiri.
Contoh:
- Anda memilih Pasangan 02;
- Nama Ketua;
- Nama Wakil;
- waktu memilih;
- status: suara terkunci.
Tidak tampilkan:
- suara kandidat lain;
- jumlah suara;
- peringkat kandidat;
- analytics admin.
Unlock hanya dapat dilakukan admin.
 
## 11. DATA SISWA
 
Field minimal:
 
- id
- NISN
- nama
- jenis_kelamin
- kelas
- nomor_absen
- kodeunik
- created_at
- updated_at
Jenis kelamin minimal mendukung:
- L
- P
Jangan mengandalkan nama untuk menentukan jenis kelamin.
 
Import Excel siswa minimal:
 
| no | NISN | nama | jenis_kelamin | kelas | nomor_absen | kodeunik |
|---|---|---|---|---|---:|---|
 
Simpan NISN dan kodeunik sebagai string.
 
## 12. DATA GURU
 
Guru adalah entitas terpisah dari siswa.
 
Field minimal:
- id
- NIP
- nama
- kodeunik
- created_at
- updated_at
Opsional:
- status aktif.
Format import guru:
 
| no | NIP | nama | kodeunik |
|---|---|---|---|
 
Guru login menggunakan:
- NIP
- kode unik.
Guru memiliki hak suara yang setara secara teknis, tetapi dipisahkan dalam identitas pemilih.
 
Jangan menggabungkan tabel identitas siswa dan guru menjadi satu tabel hanya demi kemudahan query apabila hal tersebut mengorbankan keterbacaan data.
 
## 13. MODEL VOTING
 
Gunakan model data yang jelas untuk membedakan:
- vote siswa;
- vote guru.
Tetap gunakan election yang sama.
 
Desain boleh:
A. dua tabel vote terpisah;
atau
B. satu tabel votes dengan polymorphic voter/reference type.
 
Pilih desain yang paling mudah menjaga:
- satu suara aktif per pemilih;
- audit;
- analytics;
- foreign key;
- integrity.
Tidak boleh ada cara bagi siswa untuk menggunakan vote guru atau sebaliknya.
 
## 14. ADMIN ANALYTICS
 
Analytics hanya tersedia pada panel admin.
 
### Keseluruhan
 
Gabungkan siswa + guru dalam ringkasan pemilih bila konteksnya memang total partisipasi election.
 
Tampilkan:
- total siswa;
- total guru;
- total seluruh pemilih;
- siswa sudah memilih;
- siswa belum memilih;
- guru sudah memilih;
- guru belum memilih;
- seluruh suara;
- jumlah per kandidat;
- persentase per kandidat.
### Rekap berdasarkan jenis pemilih
 
- siswa;
- guru.
### Rekap jenis kelamin siswa
 
Gunakan:
- Laki-laki;
- Perempuan.
Tampilkan:
- jumlah pemilih;
- jumlah belum memilih;
- per kandidat;
- persentase kandidat dalam kelompok.
Guru tidak dipaksa memiliki field jenis kelamin bila tidak tersedia pada data guru. Bila admin nanti ingin menambahkan, desain harus mudah diperluas.
 
### Rekap kelas
 
Untuk siswa:
- kelas;
- total siswa;
- sudah memilih;
- belum memilih;
- kandidat 1;
- kandidat 2;
- kandidat 3.
### Rekap jenjang
 
- 7
- 8
- 9
Tampilkan:
- total pemilih;
- sudah memilih;
- belum memilih;
- kandidat 1;
- kandidat 2;
- kandidat 3;
- persentase.
### Detail vote
 
Field minimal:
- nama;
- tipe pemilih;
- NISN/NIP;
- kelas bila siswa;
- nomor absen bila tersedia;
- jenis kelamin bila siswa;
- pilihan;
- waktu memilih;
- device/HP;
- browser;
- status vote.
### Chart
 
Gunakan chart modern:
- donut;
- bar;
- horizontal bar;
- stacked bar;
- progress visualization.
Jangan memenuhi dashboard dengan terlalu banyak chart.
 
## 15. LIVE COUNT
 
Admin membutuhkan live count.
 
Boleh menggunakan AJAX polling.
 
Data live:
- total suara;
- total siswa sudah memilih;
- total guru sudah memilih;
- total keseluruhan sudah memilih;
- suara per kandidat;
- persentase kandidat;
- rekap kelas;
- rekap jenjang.
Polling interval dapat disesuaikan.
 
Hentikan/kurangi polling ketika:
- election selesai;
- browser tab tidak aktif;
- admin logout.
## 16. ELECTION SCHEDULE
 
Admin dapat menentukan:
- start_at;
- end_at.
State:
- UPCOMING;
- ONGOING;
- FINISHED.
Backend/server time adalah sumber kebenaran.
 
Countdown di frontend hanya visualisasi.
 
Jangan membiarkan manipulasi waktu browser membuka voting lebih awal atau memperpanjang voting.
 
## 17. FINAL RESULT
 
Ketika waktu voting telah berakhir:
- voting ditolak;
- analytics final dihitung;
- status election menjadi FINISHED ketika logika schedule menyatakannya selesai.
Tampilkan halaman hasil final yang lebih ceremonial tetapi tetap elegan.
 
Jika hasil final sudah tersedia, tampilkan:
- 3 pasangan;
- total suara;
- persentase;
- data rekap yang sesuai;
- pemenang sesuai hasil database.
### Confetti
 
Setelah election selesai dan hasil final sudah ada:
- tampilkan efek confetti satu kali saat memasuki halaman final result;
- gunakan canvas/confetti library atau implementasi ringan;
- jangan menggunakan emoji;
- jangan membuat confetti terus-menerus sehingga mengganggu.
## 18. ADMIN UNLOCK
 
Flow:
Admin → cari pemilih → lihat status vote → unlock → masukkan alasan → konfirmasi.
 
Unlock tidak boleh:
- memilih kandidat;
- mengedit candidate_id vote secara manual.
Unlock harus:
- mencatat admin;
- mencatat pemilih;
- election;
- alasan;
- timestamp.
Riwayat vote tetap dapat diaudit.
 
## 19. AUDIT
 
Sediakan audit log untuk:
- unlock;
- perubahan schedule;
- perubahan candidate;
- import siswa;
- import guru;
- tindakan administratif penting lainnya.
Gunakan audit log secara proporsional, bukan logging segala hal yang tidak dibutuhkan.
 
## 20. EXCEL IMPORT
 
Sediakan dua template:
- student-import-template.xlsx
- teacher-import-template.xlsx
Student:
`no, NISN, nama, jenis_kelamin, kelas, nomor_absen, kodeunik`
 
Teacher:
`no, NIP, nama, kodeunik`
 
Sediakan:
- download template;
- upload;
- preview;
- validation;
- import;
- result.
Kode unik harus dibaca sebagai string agar leading zero tidak hilang.
 
## 21. MOBILE-FIRST
 
Siswa:
- desain terlebih dahulu untuk HP;
- touch target besar;
- candidate card mudah disentuh;
- scroll tidak berat;
- gambar teroptimasi;
- WebGL punya fallback;
- animation respect `prefers-reduced-motion`.
Admin:
- full responsive;
- laptop-friendly;
- desktop-friendly;
- HP-friendly;
- tabel punya horizontal overflow terkontrol atau alternate compact view.
## 22. AKSESIBILITAS
 
Tambahkan:
- keyboard navigation;
- focus state;
- alt text;
- sufficient contrast;
- reduced motion;
- readable typography.
Jangan bergantung pada warna saja untuk menyampaikan status.
 
## 23. SECURITY
 
Wajib:
- CSRF;
- server-side validation;
- session authentication;
- role authorization;
- password hashing admin;
- query builder/prepared query;
- XSS escaping;
- upload validation;
- MIME/type/size validation;
- filename sanitization;
- duplicate-vote prevention;
- transaction;
- database constraints;
- race-condition protection;
- IDOR protection;
- rate limiting/login throttling bila relevan.
Jangan menyimpan credential database di source code.
 
Jangan menyimpan data siswa/guru di localStorage.
 
LocalStorage hanya boleh menyimpan data non-sensitif yang benar-benar diperlukan.
 
## 24. FINAL PROJECT QUALITY BAR
 
Aplikasi akhir harus terasa seperti:
- aplikasi election sekolah modern;
- bukan dashboard CRUD generik;
- bukan template AI;
- bukan demo;
- siap digunakan.
Jangan menggunakan:
- emoji;
- gradient UI generik;
- excessive glassmorphism;
- template card berulang;
- stock-style decorative clutter.
Gunakan:
- art direction;
- typography;
- motion;
- 3D/WebGL secukupnya;
- candidate-specific visual assets;
- interactive storytelling;
- clear UX.
## 25. MASTER CONTRACT
 
Setiap tahap wajib:
- menggunakan file tahap sebelumnya;
- tidak membuat ulang project;
- menjaga nama tabel/field/route/file tetap konsisten;
- jika schema berubah, buat migration baru;
- jika file diubah, tampilkan isi file lengkap;
- tidak menggunakan placeholder seperti "lanjutkan sendiri";
- menjelaskan dependency;
- menjelaskan file input;
- menjelaskan file output;
- memberikan handoff ke tahap berikutnya.
