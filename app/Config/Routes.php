<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Authentication routes
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin'); // Match with form action
$routes->get('logout', 'AuthController::logout');


// Admin routes for Modul Data Induk & User Management
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'auth'], static function ($routes) {
    // Add a default route for /admin to redirect or show a dashboard later
    // $routes->get('/', 'DashboardController::index');

    $routes->resource('students', ['controller' => 'StudentController']);
    $routes->resource('teachers', ['controller' => 'TeacherController']);
    $routes->resource('subjects', ['controller' => 'SubjectController']);
    $routes->resource('classes', ['controller' => 'ClassController']);
    $routes->resource('users', ['controller' => 'UserController']); // User Management
});

// Additional routes can be added here.

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
// $routes->get('/', 'Home::index'); // Already defined above

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
