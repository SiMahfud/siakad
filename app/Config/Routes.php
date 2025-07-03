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
$routes->get('unauthorized-access', 'Home::unauthorizedAccess'); // Route for unauthorized page


// Admin routes for Modul Data Induk & User Management
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'auth'], static function ($routes) {
    // Default admin route - can be a dashboard accessible by multiple admin-level roles
    // $routes->get('/', 'DashboardController::index', ['filter' => 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah,Guru']);

    // User Management: Only Administrator Sistem
    $routes->resource('users', [
        'controller' => 'UserController',
        'filter'     => 'auth:Administrator Sistem' // Role ID 1
    ]);

    // Data Induk: Administrator Sistem & Staf Tata Usaha (CRUD), Kepala Sekolah (Read-only via controller logic)
    $dataIndukFilter = 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah'; // Role IDs 1, 2, 3
    $dataIndukEditFilter = 'auth:Administrator Sistem,Staf Tata Usaha'; // For CRUD actions beyond viewing

    $routes->resource('students', ['controller' => 'StudentController', 'filter' => $dataIndukFilter]);
    $routes->resource('teachers', ['controller' => 'TeacherController', 'filter' => $dataIndukFilter]);
    $routes->resource('subjects', ['controller' => 'SubjectController', 'filter' => $dataIndukFilter]);

    // Class Management (CRUD)
    $routes->resource('classes', ['controller' => 'ClassController', 'filter' => $dataIndukFilter]);

    // Class Student Management specific routes
    // Ensure these routes are protected by a filter that allows 'Administrator Sistem' and 'Staf Tata Usaha'
    $routes->get('classes/manage-students/(:num)', 'ClassController::manageStudents/$1', ['as' => 'admin_class_manage_students', 'filter' => $dataIndukEditFilter]);
    $routes->post('classes/add-student/(:num)', 'ClassController::addStudentToClass/$1', ['as' => 'admin_class_add_student', 'filter' => $dataIndukEditFilter]);
    $routes->get('classes/remove-student/(:num)/(:num)', 'ClassController::removeStudentFromClass/$1/$2', ['as' => 'admin_class_remove_student', 'filter' => $dataIndukEditFilter]);


    // Teacher Class Subject Assignments Management: Only Administrator Sistem
    $routes->resource('assignments', [
        'controller' => 'TeacherClassSubjectAssignmentController',
        'filter'     => 'auth:Administrator Sistem',
        'as'         => 'admin_assignments' // Route alias prefix
    ]);

    // Schedule Management: Administrator Sistem & Staf Tata Usaha
    $routes->resource('schedules', [
        'controller' => 'ScheduleController',
        'filter'     => 'auth:Administrator Sistem,Staf Tata Usaha'
    ]);
    // Note: 'delete' method in resource routes defaults to GET if not specified otherwise or handled by form method.
    // Our form uses POST for delete, which is good.

    // Subject Offerings Management: Administrator Sistem & Staf Tata Usaha (or Curriculum Role)
    $routes->resource('subject-offerings', [
        'controller' => 'SubjectOfferingController',
        'filter'     => 'auth:Administrator Sistem,Staf Tata Usaha'
        // 'as' => 'admin_subject_offerings' // Optional: if you need a prefix for named routes
    ]);
    // Example for a route accessible by Kepala Sekolah (read-only conceptually)
    // For now, KepSek can access general admin area due to 'auth' filter on group,
    // specific read-only views would need controller logic.
    // $routes->get('dashboard/overview', 'DashboardController::overview', ['filter' => 'auth:Kepala Sekolah']);


    // Fallback for any other /admin routes not specifically role-filtered above
    // They will just use the group's 'auth' filter (logged-in check only)
    // More specific routes should be defined above this if they need stricter role checks.

    // Rekapitulasi Routes (Admin, Staf TU, Kepsek, Guru/Wali Kelas)
    // Filter di controller akan membatasi data untuk Wali Kelas
    $routes->get('recaps/attendance', 'RecapController::attendance', [
        'as' => 'admin_recap_attendance',
        'filter' => 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah,Guru'
    ]);
    $routes->get('recaps/subject-choices', 'RecapController::subjectChoices', [
        'as' => 'admin_recap_subject_choices',
        'filter' => 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah,Guru' // Guru di sini mungkin lebih ke TU Kurikulum atau Admin
    ]);
});

// Guru routes
$routes->group('guru', ['namespace' => 'App\Controllers\Guru', 'filter' => 'auth:Guru,Administrator Sistem'], static function ($routes) {
    // Assessment routes
    $routes->get('assessments', 'AssessmentController::index', ['as' => 'guru_assessment_index']);
    $routes->get('assessments/input', 'AssessmentController::showInputForm', ['as' => 'guru_assessment_input_form']); // Using GET for form display
    $routes->post('assessments/save', 'AssessmentController::saveAssessments', ['as' => 'guru_assessment_save']);

    // Routes for editing and updating assessments
    $routes->get('assessments/edit/(:num)', 'AssessmentController::editAssessment/$1', ['as' => 'guru_assessment_edit']);
    $routes->post('assessments/update/(:num)', 'AssessmentController::updateAssessment/$1', ['as' => 'guru_assessment_update']);

    // Route for deleting an assessment (using POST for safety, or GET if simple link with JS confirm)
    // Using GET for simplicity with JS confirm, but POST is generally safer for delete actions.
    // For now, let's use GET and make sure there's a JS confirmation on the link/button.
    $routes->get('assessments/delete/(:num)', 'AssessmentController::deleteAssessment/$1', ['as' => 'guru_assessment_delete']);

    // Routes for assessment recap
    $routes->get('assessments/recap', 'AssessmentController::showRecapSelection', ['as' => 'guru_assessment_recap_select']);
    $routes->get('assessments/show-recap', 'AssessmentController::displayRecap', ['as' => 'guru_assessment_recap_display']); // Will take class_id and subject_id as GET params

    // AJAX route for dynamic subject loading
    $routes->get('assessments/ajax/get-subjects-for-class/(:num)', 'AssessmentController::ajaxGetSubjectsForClass/$1', ['as' => 'ajax_get_subjects_for_class']);

    // Routes for Guru (Class View)
    // This should be inside the main 'guru' group if ClassViewController is in App\Controllers\Guru
    // And if it shares the same filter 'auth:Guru,Administrator Sistem'
    // If ClassViewController is at App\Controllers\Guru\ClassViewController, then it's fine.
    $routes->get('my-classes', 'ClassViewController::index', ['as' => 'guru_my_classes']);
    $routes->get('my-classes/view-students/(:num)', 'ClassViewController::viewStudents/$1', ['as' => 'guru_view_class_students']);
    $routes->get('my-schedule', 'ClassViewController::mySchedule', ['as' => 'guru_my_schedule']);

    // Attendance Routes for Guru
    $routes->get('attendance/select-schedule', 'AttendanceController::selectSchedule', ['as' => 'guru_attendance_select_schedule']);
    $routes->get('attendance/form', 'AttendanceController::showAttendanceForm', ['as' => 'guru_attendance_form']); // Using GET to display form with params
    $routes->post('attendance/save', 'AttendanceController::saveAttendance', ['as' => 'guru_attendance_save']);

    // Wali Kelas - Ekspor e-Rapor
    // Namespace controller sudah App\Controllers\WaliKelas, jadi path relatif dari sana.
    // Namun, karena controller ada di subdirektori WaliKelas, kita perlu menentukannya.
    // Cara yang lebih eksplisit:
    $routes->get('wali-kelas/erapor/export', '\App\Controllers\WaliKelas\EraporController::exportForm', ['as' => 'wali_kelas_erapor_form', 'filter' => 'auth:Guru']);
    $routes->post('wali-kelas/erapor/process', '\App\Controllers\WaliKelas\EraporController::processExport', ['as' => 'wali_kelas_erapor_process', 'filter' => 'auth:Guru']);
});

// Siswa routes
$routes->group('siswa', ['namespace' => 'App\Controllers\Siswa', 'filter' => 'auth:Siswa'], static function ($routes) {
    $routes->get('nilai', 'NilaiController::index', ['as' => 'siswa_nilai_index']);
    $routes->get('my-schedule', 'ScheduleController::classSchedule', ['as' => 'siswa_my_schedule']);

    // Subject Choice Routes for Siswa
    $routes->get('subject-choices', 'SubjectChoiceController::index', ['as' => 'siswa_subject_choices_index']);
    $routes->post('subject-choices/process', 'SubjectChoiceController::processChoice', ['as' => 'siswa_subject_choices_process']);
    // Add other siswa specific routes here
});

// Orang Tua routes
$routes->group('ortu', ['namespace' => 'App\Controllers\Ortu', 'filter' => 'auth:Orang Tua'], static function ($routes) {
    $routes->get('nilai', 'NilaiController::index', ['as' => 'ortu_nilai_index']);
    $routes->get('nilai/(:num)', 'NilaiController::showStudentRecap/$1', ['as' => 'ortu_nilai_recap_siswa']);
    // Add other orang tua specific routes here
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
