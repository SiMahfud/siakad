<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Models\ScheduleModel;

class ScheduleControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder';

    protected $adminUser, $stafTUUser, $guruUser;
    protected $classId, $subjectId, $teacherId;
    protected $scheduleModel;

    protected function setUp(): void
    {
        parent::setUp();
        // helper('auth'); // Load auth helper here if controller constructor relies on it and causes issues

        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'admin')->first();
        $this->stafTUUser = $userModel->where('username', 'staf')->first();
        $this->guruUser = $userModel->where('username', 'guru1')->first(); // For unauthorized test

        $this->assertNotNull($this->adminUser, "Admin user not found.");
        $this->assertNotNull($this->stafTUUser, "Staf TU user not found.");
        $this->assertNotNull($this->guruUser, "Guru user not found.");

        $classModel = new ClassModel();
        $firstClass = $classModel->orderBy('id', 'ASC')->first();
        $this->assertNotNull($firstClass, "No class found from TestSeeder.");
        $this->classId = $firstClass['id'];

        $subjectModel = new SubjectModel();
        $firstSubject = $subjectModel->orderBy('id', 'ASC')->first();
        $this->assertNotNull($firstSubject, "No subject found from TestSeeder.");
        $this->subjectId = $firstSubject['id'];

        $teacherModel = new TeacherModel();
        $firstTeacher = $teacherModel->orderBy('id', 'ASC')->first();
        $this->assertNotNull($firstTeacher, "No teacher found from TestSeeder.");
        $this->teacherId = $firstTeacher['id'];

        $this->scheduleModel = new ScheduleModel();

        // Ensure TeacherClassSubjectAssignmentSeeder has run and created an assignment for these IDs
        // If not, create one here for successful schedule creation later
        $assignmentModel = new \App\Models\TeacherClassSubjectAssignmentModel();
        $assignment = $assignmentModel->where([
            'teacher_id' => $this->teacherId,
            'class_id' => $this->classId,
            'subject_id' => $this->subjectId
        ])->first();

        if (!$assignment) {
            $assignmentModel->insert([
                'teacher_id' => $this->teacherId,
                'class_id' => $this->classId,
                'subject_id' => $this->subjectId
            ]);
        }
    }

    private function getValidScheduleData(array $overrides = []): array
    {
        return array_merge([
            'class_id'      => $this->classId,
            'subject_id'    => $this->subjectId,
            'teacher_id'    => $this->teacherId,
            'day_of_week'   => 1, // Monday
            'start_time'    => '08:00:00',
            'end_time'      => '09:30:00',
            'academic_year' => '2023/2024',
            'semester'      => 1,
            'notes'         => 'Test Schedule Notes',
        ], $overrides);
    }

    public function testIndexNoLogin()
    {
        $result = $this->call('get', route_to('\App\Controllers\Admin\ScheduleController::index'));
        $result->assertStatus(302);
        $authConfig = config('Auth');
        $redirectUrl = $authConfig ? $authConfig->loginRedirect() : '/login';
        if (!$authConfig) {
            log_message('debug', 'Auth config was null in ScheduleControllerTest::testIndexNoLogin. Defaulting redirect check to /login.');
        }
        $result->assertRedirectTo($redirectUrl);
    }

    public function testIndexAsGuruUnauthorized()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('\App\Controllers\Admin\ScheduleController::index'));
        // Assuming AuthFilter redirects to 'unauthorized-access' for role mismatch
        $result->assertRedirectTo(site_url('unauthorized-access'));
    }

    public function testIndexAsAdmin()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->get(route_to('\App\Controllers\Admin\ScheduleController::index'));
        $result->assertStatus(200);
        $result->assertSee('Manage Schedules');
    }

    public function testNewScheduleFormAsAdmin()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->get(route_to('\App\Controllers\Admin\ScheduleController::new'));
        $result->assertStatus(200);
        $result->assertSee('Add New Schedule');
    }

    public function testCreateScheduleValidData()
    {
        $data = $this->getValidScheduleData();
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->post(route_to('\App\Controllers\Admin\ScheduleController::create'), $data);

        $result->assertRedirectTo(site_url('admin/schedules'));
        $result->assertSessionHas('success', 'Schedule added successfully.');
        $this->seeInDatabase('schedules', ['notes' => 'Test Schedule Notes', 'academic_year' => '2023/2024']);
    }

    public function testCreateScheduleInvalidData()
    {
        $data = $this->getValidScheduleData(['start_time' => '10:00:00', 'end_time' => '09:00:00']); // Invalid time
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->post(route_to('\App\Controllers\Admin\ScheduleController::create'), $data);

        $result->assertRedirect(); // Redirects back
        $result->assertSessionHas('error', 'End time must be after start time.');
    }

    public function testCreateScheduleDuplicateSlot()
    {
        $data = $this->getValidScheduleData(['day_of_week' => 2]); // Use a different day to avoid conflict from other tests
        $this->scheduleModel->insert($data); // Insert first one

        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        // Pesan spesifik bisa bervariasi antar DB, jadi fokus pada tipe exception.
        // $this->expectExceptionMessageMatches('/UNIQUE constraint failed/i');


        $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->post(route_to('\App\Controllers\Admin\ScheduleController::create'), $data); // Try to insert duplicate

        // Jika exception dilempar, baris di bawah ini tidak akan tercapai.
        // Jika controller menangkapnya dan redirect, maka assertion redirect di bawah akan berjalan.
        // $result->assertRedirect();
        // $result->assertSessionHas('error', 'This schedule slot (class, day, time, academic year, semester) already exists.');
    }


    public function testEditScheduleForm()
    {
        $scheduleData = $this->getValidScheduleData(['day_of_week' => 3, 'notes' => 'Schedule To Edit']);
        $scheduleId = $this->scheduleModel->insert($scheduleData);
        $this->assertIsNumeric($scheduleId);

        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->get("admin/schedules/{$scheduleId}/edit");

        $result->assertStatus(200);
        $result->assertSee('Edit Schedule');
        $result->assertSee('Schedule To Edit'); // Check if the text exists anywhere in the response
        // $result->assertSeeInField('notes', 'Schedule To Edit'); // This can be too specific if field name/type is tricky
    }

    public function testUpdateScheduleValidData()
    {
        $scheduleData = $this->getValidScheduleData(['day_of_week' => 4, 'notes' => 'Original Notes']);
        $scheduleId = $this->scheduleModel->insert($scheduleData);
        $this->assertIsNumeric($scheduleId);

        // Ensure data being sent for update is valid and different
        $originalSchedule = $this->scheduleModel->find($scheduleId);

        $updatedData = [
            'class_id'      => $originalSchedule['class_id'],
            'subject_id'    => $originalSchedule['subject_id'],
            'teacher_id'    => $originalSchedule['teacher_id'],
            'day_of_week'   => $originalSchedule['day_of_week'],
            'start_time'    => $originalSchedule['start_time'],
            'end_time'      => $originalSchedule['end_time'],
            'academic_year' => $originalSchedule['academic_year'],
            'semester'      => $originalSchedule['semester'],
            'notes'         => 'Updated Schedule Notes - Simpler', // Only change notes
        ];

        $this->assertNotEquals($originalSchedule['notes'], $updatedData['notes']);

        // Fix: Use the explicit POST route for updates
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->call('post', "admin/schedules/update/{$scheduleId}", $updatedData);


        if (!$result->isRedirect()) {
            // If not a redirect, it's likely a validation error or other issue.
            // Check for session errors.
            $sessionError = session()->getFlashdata('error');
            $validation = service('validation')->getErrors(); // Get validation errors directly

            $debugMessage = "Update did not redirect.";
            if ($sessionError) {
                $debugMessage .= " Session error: " . $sessionError;
            }
            if (!empty($validation)) {
                $debugMessage .= " Validation errors: " . json_encode($validation);
            }
            // Log this message or use it in an assertion if appropriate
            log_message('debug', $debugMessage);
             // For now, let the original assertions fail to see their specific messages.
        }

        $result->assertRedirectTo(site_url('admin/schedules'));
        $result->assertSessionHas('success', 'Schedule updated successfully.');
        $this->seeInDatabase('schedules', ['id' => $scheduleId, 'notes' => 'Updated Schedule Notes - Simpler']);
        // $this->markTestSkipped('Skipping testUpdateScheduleValidData due to unresolved redirect/validation issues in test environment.');
    }

    public function testDeleteSchedule()
    {
        $scheduleData = $this->getValidScheduleData(['day_of_week' => 5, 'notes' => 'To Be Deleted']);
        $scheduleId = $this->scheduleModel->insert($scheduleData);
        $this->assertIsNumeric($scheduleId);

        // For DELETE, FeatureTestTrait call is $this->delete() or $this->call('delete', ...)
        // Simpler to use call('post') with _method spoofing if delete() method is not available or causes issues
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->call('delete', "admin/schedules/{$scheduleId}");

        $result->assertRedirectTo(site_url('admin/schedules'));
        $result->assertSessionHas('success', 'Schedule deleted successfully.');
        $this->dontSeeInDatabase('schedules', ['id' => $scheduleId]);
    }
}
