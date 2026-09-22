<?php

namespace Config;

use App\Filters\AdminAuthFilter;
use App\Filters\StudentAuthFilter;
use App\Filters\TeacherAuthFilter;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Alias filter bawaan CodeIgniter + filter kustom aplikasi ini.
     * Filter proteksi route (adminauth/studentauth/teacherauth) dipasang
     * langsung pada masing-masing route group di app/Config/Routes.php.
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'adminauth'     => AdminAuthFilter::class,
        'studentauth'   => StudentAuthFilter::class,
        'teacherauth'   => TeacherAuthFilter::class,
    ];

    /**
     * Filter global. CSRF aktif untuk semua request (aman untuk GET,
     * memvalidasi token pada POST/PUT/DELETE).
     */
    public array $globals = [
        'before' => [
            'csrf',
            'invalidchars',
        ],
        'after' => [
            'secureheaders',
        ],
    ];

    public array $methods = [];

    /**
     * Route-based filters. Sengaja dikosongkan karena proteksi
     * student/teacher/admin dashboard sudah dipasang lewat route
     * group filter di app/Config/Routes.php agar lebih eksplisit
     * dan mudah dibaca.
     */
    public array $filters = [];
}
