# STAGE 5
## HOMEPAGE UI/UX REDESIGN: IMMERSIVE SCENES, ENTRY PORTALS, PUBLIC LIVE COUNT & LAUNCH SCREEN

Baca:
1. `00-MASTER-PROJECT.md`
2. `STAGE1-NOTES.md` s.d. `STAGE4-NOTES.md`
3. seluruh file aplikasi hasil Stage 1–4.
Jangan membuat project baru.

Hasil implementasi aktual dan handoff Stage 5: `STAGE5-NOTES.md`.

## OBJECTIVE

Desain ulang beranda agar terasa modern, imersif, premium, dan mendekati
pengalaman aplikasi native di desktop maupun HP.

Referensi visual: https://antigravity.google/ hanya sebagai inspirasi kualitas,
komposisi, kedalaman, gerak, tipografi, dan interaksi imersif. Jangan menyalin
desainnya.

Fokus: hierarki visual + tipografi + ruang kosong + gerak + kedalaman +
transisi imersif.

## 1. SITE NAVIGATION

- Hapus tombol "Masuk Guru" dan "Masuk Siswa".
- Ganti wordmark teks dengan gambar logo lengkap.
- Logo di tengah, desktop maupun HP; responsif dan berskala tepat.
- Navigasi minimal, bersih, ringan; bukan gaya dasbor SaaS.

## 2. FULL-PAGE SECTION SCROLLING

Beranda bergulir satu bagian per layar (full-screen vertical snap):
- setiap bagian kira-kira setinggi satu layar dan terasa sebagai "scene";
- gulir berpindah langsung ke bagian berikut/sebelumnya, bukan gulir halaman
  biasa; tidak berhenti di tengah dua bagian;
- transisi halus dan disengaja;
- roda mouse & trackpad (desktop), geser sentuh (HP), keyboard bila relevan;
- efek halus: paralaks, skala, pudar, kedalaman, perspektif, gerak 3D
  ringan, gerak berlapis; sinematik tetapi ringan dan cepat;
- bagian berikutnya tidak tampil sebagai lanjutan halaman panjang;
- sediakan tombol/CTA navigasi untuk berpindah bagian dengan sengaja.

## 3. STUDENT AND TEACHER ENTRY

- Hapus teks penjelasan "cara masuk"; sisakan konsep "WHO" saja.
- Dua pintu utama: SISWA dan GURU.
- Masing-masing punya latar/ilustrasi besar (ilustrasi final menyusul) dan
  terasa seperti portal visual interaktif, bukan kartu dasbor.
- Dukung: ilustrasi latar penuh, lapisan gelap agar terbaca, hierarki kuat,
  kedalaman & animasi halus, hover di desktop, ramah sentuh di HP, tipografi
  kuat, komposisi responsif.
- Struktur siap untuk mengganti ilustrasi tanpa perubahan layout besar.

## 4. INFORMASI "SEDANG BERLANGSUNG"

- Keluarkan bagian "Sedang Berlangsung" dari alur bagian beranda.
- Pindahkan ke panel bawah yang menempel (sticky), selalu terlihat di beranda:
  lebar penuh, modern, estetis, terinspirasi terminal/konsol perintah,
  digital, animasi halus, tidak mengganggu, terbaca di desktop & HP.
- Bukan gaya alert/notifikasi/widget dasbor generik.
- Di HP tidak boleh menutupi tombol/konten penting.

## 5. LIVE VOTE COUNT SECTION

- Bagian khusus perolehan suara; fokus visual = foto pasangan calon.
- Per pasangan: foto menonjol, persentase suara tepat di bawah foto, sangat
  terlihat, animasi halus saat nilai berubah.
- Di bawah bagian: "Diperbarui [tanggal] [jam]".
- Data diperbarui otomatis setiap 30 detik.
- Kiri: informasi pasangan & persentase; kanan: suara yang sudah masuk,
  mis. `Suara masuk — 78,4%`.
- Fleksibel untuk jumlah pasangan berapa pun.
- HP: tata ulang (bertumpuk/posisi adaptif), bukan desktop yang diperkecil.

## 6. HOMEPAGE LOADING SCREEN

Saat beranda pertama dibuka, tampilkan layar pembuka layar penuh seperti
launch screen aplikasi native:
- logo di tengah (fokus utama), bilah muat di bawah logo;
- latar khusus: aset terpisah untuk desktop dan HP (dipilih otomatis),
  lapisan gelap di atas latar;
- animasi muat halus dan premium;
- setelah selesai, transisi halus ke beranda (tidak muncul tiba-tiba).

## 7. FOOTER

Minimal, rata tengah, konsisten dengan beranda; tanpa kolom/tautan berlebih.

## 8. OVERALL VISUAL DIRECTION

Modern, premium, imersif, interaktif, seperti aplikasi, visual, bersih,
sinematik, rapi, responsif. Hindari: dasbor admin generik, template SaaS,
kartu berlebihan, border berlebihan, teks berlebihan, website sekolah
konvensional, tata letak korporat.

Aturan `00-MASTER-PROJECT.md` tetap berlaku: tanpa emoji, tanpa gradient
generik, tanpa glassmorphism berlebihan, warna netral + aksen solid pasangan.

## 9. DESKTOP AND MOBILE RESPONSIVENESS

Desktop: laptop, monitor, roda mouse, trackpad, keyboard.
HP: potret, lanskap, geser sentuh, tinggi viewport dinamis, bilah alamat
browser, safe-area bila ada.
Desain khusus untuk HP (bukan desktop yang diperkecil) dengan identitas
visual dan konsep interaksi yang sama.

## 10. IMPLEMENTATION CONSTRAINTS

- Pertahankan struktur & fungsi proyek; fokus UI/UX, interaksi, animasi,
  responsif.
- Jangan mengubah logika bisnis, alur autentikasi, perilaku API, struktur
  database, atau fungsi inti kecuali diperlukan beranda baru.
- Analisis dulu struktur & beranda lama, pakai ulang komponen/utilitas/pola
  yang ada, hindari penulisan ulang yang tidak perlu, jaga konsistensi
  arsitektur, periksa regresi setelah implementasi.
- Perbarui dokumen yang ada sesuai desain terbaru.

## OUTPUT FORMAT

Ikuti format dokumen tahap sebelumnya: tujuan, dependency, file baru, file
diubah, perubahan database, route, ringkasan implementasi, test, edge case,
batasan, handoff.
