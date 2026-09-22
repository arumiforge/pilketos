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
// Stage 3 menambahkan seluruh route panel admin di dalam grup ini.
// -----------------------------------------------------------------
$routes->group('admin', ['filter' => 'adminauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->post('logout', 'Admin\AuthController::logout');
});
