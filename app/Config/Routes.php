<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------
// PUBLIC
// -----------------------------------------------------------------
$routes->get('/', 'Home::index');

// Jam server untuk countdown (JSON publik, tanpa data pemilih/suara).
$routes->get('election/clock', 'ElectionController::clock');

// Live count publik beranda (JSON): hanya persentase per pasangan +
// partisipasi, tanpa identitas/rincian. Rincian tetap di admin/live-count.
$routes->get('live-count', 'Home::liveCount');

$routes->get('student/login', 'Student\AuthController::loginForm');
$routes->post('student/login', 'Student\AuthController::attemptLogin');

$routes->get('teacher/login', 'Teacher\AuthController::loginForm');
$routes->post('teacher/login', 'Teacher\AuthController::attemptLogin');

$routes->get('admin/login', 'Admin\AuthController::loginForm');
$routes->post('admin/login', 'Admin\AuthController::attemptLogin');

// Alamat pendek -> dasbor (tetap melewati filter auth masing-masing).
$routes->addRedirect('student', 'student/dashboard');
$routes->addRedirect('teacher', 'teacher/dashboard');
$routes->addRedirect('admin', 'admin/dashboard');

// -----------------------------------------------------------------
// STUDENT (protected by studentauth filter)
// Voting (Stage 2): identitas pemilih hanya dari sesi, tidak ada id di URL.
// -----------------------------------------------------------------
$routes->group('student', ['filter' => 'studentauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Student\DashboardController::index');
    $routes->get('vote', 'Student\VoteController::index');
    $routes->post('vote', 'Student\VoteController::submit');
    $routes->get('vote/confirm/(:num)', 'Student\VoteController::confirm/$1');
    $routes->get('my-vote', 'Student\VoteController::myVote');
    $routes->post('logout', 'Student\AuthController::logout');
});

// -----------------------------------------------------------------
// TEACHER (protected by teacherauth filter)
// Voting (Stage 2): identitas pemilih hanya dari sesi, tidak ada id di URL.
// -----------------------------------------------------------------
$routes->group('teacher', ['filter' => 'teacherauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Teacher\DashboardController::index');
    $routes->get('vote', 'Teacher\VoteController::index');
    $routes->post('vote', 'Teacher\VoteController::submit');
    $routes->get('vote/confirm/(:num)', 'Teacher\VoteController::confirm/$1');
    $routes->get('my-vote', 'Teacher\VoteController::myVote');
    $routes->post('logout', 'Teacher\AuthController::logout');
});

// -----------------------------------------------------------------
// ADMIN (protected by adminauth filter)
// Panel admin Stage 3. Semua POST melewati CSRF global. Tidak ada route
// admin yang menulis suara: unlock hanya membuka hak suara.
// -----------------------------------------------------------------
$routes->group('admin', ['filter' => 'adminauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->post('logout', 'Admin\AuthController::logout');

    // Live count (AJAX JSON) & analitik
    $routes->get('live-count', 'Admin\LiveCountController::index');
    $routes->get('analytics', 'Admin\AnalyticsController::index');
    $routes->get('analytics/votes', 'Admin\AnalyticsController::votes');

    // Hasil akhir + confetti (Stage 4): aktif hanya saat pemilihan FINISHED
    $routes->get('results', 'Admin\ResultController::index');

    // Pasangan calon + tema
    $routes->get('candidates', 'Admin\CandidateController::index');
    $routes->get('candidates/new', 'Admin\CandidateController::new');
    $routes->post('candidates', 'Admin\CandidateController::create');
    $routes->get('candidates/(:num)/edit', 'Admin\CandidateController::edit/$1');
    $routes->get('candidates/(:num)/preview', 'Admin\CandidateController::preview/$1');
    $routes->post('candidates/(:num)', 'Admin\CandidateController::update/$1');
    $routes->post('candidates/(:num)/delete', 'Admin\CandidateController::delete/$1');

    // Siswa + impor Excel
    $routes->get('students', 'Admin\StudentController::index');
    $routes->get('students/import', 'Admin\StudentImportController::index');
    $routes->post('students/import', 'Admin\StudentImportController::upload');
    $routes->get('students/import/template', 'Admin\StudentImportController::template');
    $routes->get('students/import/preview/(:segment)', 'Admin\StudentImportController::preview/$1');
    $routes->post('students/import/commit', 'Admin\StudentImportController::commit');
    $routes->get('students/import/result', 'Admin\StudentImportController::result');
    $routes->get('students/(:num)', 'Admin\StudentController::show/$1');
    $routes->post('students/(:num)/status', 'Admin\StudentController::status/$1');
    $routes->post('students/(:num)/delete', 'Admin\StudentController::delete/$1');

    // Guru + impor Excel
    $routes->get('teachers', 'Admin\TeacherController::index');
    $routes->get('teachers/import', 'Admin\TeacherImportController::index');
    $routes->post('teachers/import', 'Admin\TeacherImportController::upload');
    $routes->get('teachers/import/template', 'Admin\TeacherImportController::template');
    $routes->get('teachers/import/preview/(:segment)', 'Admin\TeacherImportController::preview/$1');
    $routes->post('teachers/import/commit', 'Admin\TeacherImportController::commit');
    $routes->get('teachers/import/result', 'Admin\TeacherImportController::result');
    $routes->get('teachers/(:num)', 'Admin\TeacherController::show/$1');
    $routes->post('teachers/(:num)/status', 'Admin\TeacherController::status/$1');
    $routes->post('teachers/(:num)/delete', 'Admin\TeacherController::delete/$1');

    // Jadwal pemilihan (status dihitung dari jadwal & jam server)
    $routes->get('election', 'Admin\ElectionController::index');
    $routes->post('election', 'Admin\ElectionController::save');
    $routes->post('election/close', 'Admin\ElectionController::close');
    $routes->post('election/open', 'Admin\ElectionController::open');

    // Unlock hak suara & audit log
    $routes->get('unlock', 'Admin\UnlockController::index');
    $routes->get('unlock/student/(:num)', 'Admin\UnlockController::form/student/$1');
    $routes->post('unlock/student/(:num)', 'Admin\UnlockController::unlock/student/$1');
    $routes->get('unlock/teacher/(:num)', 'Admin\UnlockController::form/teacher/$1');
    $routes->post('unlock/teacher/(:num)', 'Admin\UnlockController::unlock/teacher/$1');
    $routes->get('audit', 'Admin\AuditController::index');
});
