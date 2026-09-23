<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Beranda imersif (redesign beranda, STAGE5-NOTES.md).
 *
 * Semua gambar beranda dapat diganti panitia TANPA mengubah layout: taruh
 * file baru di public/assets/img/..., lalu ubah path di sini atau di .env:
 *
 *   homepage.introDesktop    = 'assets/img/home/pembuka-desktop.webp'
 *   homepage.entryStudent    = 'assets/img/home/siswa.webp'
 *   homepage.publicLiveCount = false
 *
 * Path relatif terhadap folder public/ (tanpa "/" di depan). Format: svg,
 * webp, jpg/jpeg, png, avif. Jangan taruh di public/uploads/: folder itu
 * hanya menyajikan foto kandidat (public/uploads/.htaccess).
 */
class Homepage extends BaseConfig
{
    /**
     * Logo lengkap untuk latar gelap (navigasi beranda, layar pembuka).
     */
    public string $logoOnDark = 'assets/img/brand/logo-light.svg';

    /**
     * Logo lengkap untuk latar terang (login, dasbor, halaman voting).
     */
    public string $logoOnLight = 'assets/img/brand/logo-dark.svg';

    /**
     * Latar layar pembuka untuk layar lanskap (laptop, monitor, HP miring).
     * Disarankan 1920x1080 atau lebih; bagian tengah tertutup logo.
     */
    public string $introDesktop = 'assets/img/home/intro-desktop.svg';

    /**
     * Latar layar pembuka untuk layar potret (HP & tablet tegak).
     * Disarankan 1080x1920.
     */
    public string $introMobile = 'assets/img/home/intro-mobile.svg';

    /**
     * Ilustrasi pintu masuk Siswa & Guru (dipotong "cover" dari tengah,
     * diberi lapisan gelap agar teks terbaca). Versi *Mobile opsional:
     * dipakai di layar potret selebar HP (kotak pintu melebar), null = sama.
     */
    public string $entryStudent = 'assets/img/home/entry-student.svg';

    public ?string $entryStudentMobile = null;
    public string $entryTeacher        = 'assets/img/home/entry-teacher.svg';
    public ?string $entryTeacherMobile = null;

    /**
     * Live count publik di beranda (persentase per pasangan + partisipasi).
     * false = beranda hanya menampilkan pasangan calon tanpa angka dan
     * GET live-count menjawab 404. Rincian analitik tetap hanya untuk admin.
     */
    public bool $publicLiveCount = true;

    /**
     * Irama pembaruan otomatis live count (detik) saat pemilihan belum
     * dibuka atau sedang berlangsung. Minimal 10; 0 = tanpa pembaruan otomatis.
     */
    public int $livePollSeconds = 30;

    /**
     * Cache singkat angka publik (detik) agar ratusan layar yang terbuka
     * bersamaan tidak masing-masing menghitung ulang ke MySQL. 0 = tanpa cache.
     */
    public int $liveCacheSeconds = 5;
}
