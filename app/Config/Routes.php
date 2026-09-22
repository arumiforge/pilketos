<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------
// PUBLIC
// -----------------------------------------------------------------
$routes->get('/', 'Home::index');

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
// Stage 2 menambahkan route voting siswa di dalam grup ini.
// -----------------------------------------------------------------
$routes->group('student', ['filter' => 'studentauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Student\DashboardController::index');
    $routes->post('logout', 'Student\AuthController::logout');
});

// -----------------------------------------------------------------
// TEACHER (protected by teacherauth filter)
// Stage 2 menambahkan route voting guru di dalam grup ini.
// -----------------------------------------------------------------
$routes->group('teacher', ['filter' => 'teacherauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Teacher\DashboardController::index');
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
