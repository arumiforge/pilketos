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

// -----------------------------------------------------------------
// STUDENT (protected by studentauth filter)
// -----------------------------------------------------------------
$routes->group('student', ['filter' => 'studentauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Student\DashboardController::index');
    $routes->post('logout', 'Student\AuthController::logout');
});

// -----------------------------------------------------------------
// TEACHER (protected by teacherauth filter)
// -----------------------------------------------------------------
$routes->group('teacher', ['filter' => 'teacherauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Teacher\DashboardController::index');
    $routes->post('logout', 'Teacher\AuthController::logout');
});

// -----------------------------------------------------------------
// ADMIN (protected by adminauth filter)
// -----------------------------------------------------------------
$routes->group('admin', ['filter' => 'adminauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->post('logout', 'Admin\AuthController::logout');
});

// -----------------------------------------------------------------
// Catatan handoff Stage 2:
// Route voting (student/vote, teacher/vote, dst) belum dibuat di
// Stage 1. Controller & view voting akan ditambahkan Stage 2 di
// dalam grup 'studentauth' dan 'teacherauth' di atas.
// -----------------------------------------------------------------
