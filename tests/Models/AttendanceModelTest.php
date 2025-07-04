<?php

namespace Tests\Models;

use App\Models\AttendanceModel;
use App\Models\ScheduleModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Database\Seeds\ScheduleSeeder; // To access static ID
use App\Database\Seeds\StudentSeeder as AppStudentSeeder; // Alias to avoid conflict
use App\Database\Seeds\UserSeeder as AppUserSeeder; // Alias
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

class AttendanceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder';

    protected $model;
    protected $faker;

    protected static $scheduleId;
    protected static $studentId;
    protected static $userId; // For recorded_by_user_id

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AttendanceModel();
        $this->faker = Factory::create();

        // Get IDs from seeders after they have run
        self::$scheduleId = ScheduleSeeder::$schedule1Id;
        self::$studentId = AppStudentSeeder::$student1Id;

        $userModel = new UserModel();
        $firstUser = $userModel->first(); // Get any user for recorded_by
        if ($firstUser) {
            self::$userId = $firstUser['id'];
        } else {
            // Fallback: try to get admin user specifically if TestSeeder ensures it
            $adminUser = $userModel->where('username', 'admin')->first();
            if ($adminUser) {
                 self::$userId = $adminUser['id'];
            } else {
                // If still no user, tests requiring recorded_by_user_id might be problematic
                // or need to create a user here. For now, log and proceed.
                log_message('error', 'AttendanceModelTest::setUp - No user found for recorded_by_user_id.');
                // As a last resort for tests that need a user ID, create one directly
                // This is not ideal as it duplicates seeder logic somewhat.
                $roleModel = new \App\Models\RoleModel();
                $adminRole = $roleModel->where('role_name', 'Administrator Sistem')->first();
                if ($adminRole) {
                    self::$userId = $userModel->insert([
                        'username' => 'testrecorder',
                        'password' => password_hash('password123', PASSWORD_DEFAULT),
                        'password_confirm' => 'password123',
                        'full_name' => 'Test Recorder User',
                        'role_id'   => $adminRole['id'],
                        'is_active' => 1
                    ]);
                }
            }
        }

        // Ensure static IDs are set
        if (!self::$scheduleId) {
            log_message('error', 'AttendanceModelTest::setUp - ScheduleSeeder::$schedule1Id is not set.');
            // Potentially create a schedule here if critical, or fail test.
            // For now, let tests fail if this happens to highlight seeder issues.
        }
        if (!self::$studentId) {
            log_message('error', 'AttendanceModelTest::setUp - StudentSeeder::$student1Id is not set.');
        }
    }

    public function testCreateAttendanceValid()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $data = [
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => date('Y-m-d'),
            'status'          => AttendanceModel::STATUS_HADIR,
            'remarks'         => 'Attended class',
            'recorded_by_user_id' => self::$userId,
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id, $this->model->errors() ? implode(', ', $this->model->errors()) : 'Insert failed for unknown reasons');
        $this->seeInDatabase('attendances', ['id' => $id, 'status' => AttendanceModel::STATUS_HADIR]);
    }

    public function testCreateAttendanceInvalidStatus()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");

        $data = [
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => date('Y-m-d'),
            'status'          => 99, // Invalid status
            'recorded_by_user_id' => self::$userId,
        ];

        $result = $this->model->insert($data);
        $this->assertFalse($result);
        $errors = $this->model->errors();
        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals('Invalid attendance status.', $errors['status']);
    }

    public function testCreateAttendanceDuplicateEntry()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $date = date('Y-m-d');
        $data1 = [
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => $date,
            'status'          => AttendanceModel::STATUS_HADIR,
            'recorded_by_user_id' => self::$userId,
        ];
        $this->model->insert($data1);

        $data2 = [ // Same student, schedule, date
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => $date,
            'status'          => AttendanceModel::STATUS_SAKIT, // Different status, but still duplicate by unique key
            'recorded_by_user_id' => self::$userId,
        ];

        // Catch database exception for unique constraint violation
        try {
            $this->model->insert($data2);
            $this->fail('Expected a DatabaseException for duplicate entry, but none was thrown.');
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Check if the error message indicates a unique constraint violation
            // SQLite error messages for unique constraints can vary.
            // It might mention the explicit constraint name or the columns involved.
            $message = $e->getMessage();
            $this->assertTrue(
                str_contains(strtolower($message), 'unique constraint failed: attendances.uq_student_attendance_slot_date') ||
                str_contains(strtolower($message), 'unique constraint failed: attendances.schedule_id, attendances.student_id, attendances.attendance_date'),
                "Error message '{$message}' did not match expected unique constraint failure."
            );
        }
    }


    public function testUpdateAttendance()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $data = [
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => date('Y-m-d'),
            'status'          => AttendanceModel::STATUS_ALFA,
            'recorded_by_user_id' => self::$userId,
        ];
        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $updateData = [
            'status'  => AttendanceModel::STATUS_IZIN,
            'remarks' => 'Updated to Izin with reason.',
        ];
        $this->model->update($id, $updateData);
        $this->seeInDatabase('attendances', ['id' => $id, 'status' => AttendanceModel::STATUS_IZIN, 'remarks' => 'Updated to Izin with reason.']);
    }

    public function testDeleteAttendance()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $data = [
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => date('Y-m-d'),
            'status'          => AttendanceModel::STATUS_SAKIT,
            'recorded_by_user_id' => self::$userId,
        ];
        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $this->model->delete($id);
        $this->dontSeeInDatabase('attendances', ['id' => $id]);
    }

    public function testGetAttendanceByScheduleAndDate()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $date = '2023-11-15';
        $this->model->insert([
            'schedule_id'     => self::$scheduleId,
            'student_id'      => self::$studentId,
            'attendance_date' => $date,
            'status'          => AttendanceModel::STATUS_HADIR,
            'recorded_by_user_id' => self::$userId,
        ]);

        $attendanceData = $this->model->getAttendanceByScheduleAndDate(self::$scheduleId, $date);
        $this->assertCount(1, $attendanceData);
        $this->assertEquals(self::$studentId, $attendanceData[0]['student_id']);
        $this->assertEquals(AttendanceModel::STATUS_HADIR, $attendanceData[0]['status']);
        $this->assertArrayHasKey('student_name', $attendanceData[0]);
    }

    public function testGetStudentAttendanceSummary()
    {
        $this->assertNotNull(self::$scheduleId, "Prerequisite scheduleId is null");
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $startDate = '2023-11-01';
        $endDate = '2023-11-30';

        $this->model->insert([
            'schedule_id' => self::$scheduleId, 'student_id' => self::$studentId,
            'attendance_date' => '2023-11-05', 'status' => AttendanceModel::STATUS_HADIR,
            'recorded_by_user_id' => self::$userId]);
        $this->model->insert([
            'schedule_id' => self::$scheduleId, 'student_id' => self::$studentId,
            'attendance_date' => '2023-11-06', 'status' => AttendanceModel::STATUS_SAKIT,
            'recorded_by_user_id' => self::$userId]);
        $this->model->insert([ // Different student, should not appear
            'schedule_id' => self::$scheduleId, 'student_id' => ($this->getAnotherStudentId(self::$studentId)),
            'attendance_date' => '2023-11-06', 'status' => AttendanceModel::STATUS_HADIR,
            'recorded_by_user_id' => self::$userId]);


        $summaryAll = $this->model->getStudentAttendanceSummary(self::$studentId, $startDate, $endDate);
        $this->assertCount(2, $summaryAll);

        $summarySakit = $this->model->getStudentAttendanceSummary(self::$studentId, $startDate, $endDate, [AttendanceModel::STATUS_SAKIT]);
        $this->assertCount(1, $summarySakit);
        $this->assertEquals(AttendanceModel::STATUS_SAKIT, $summarySakit[0]['status']);
    }

    // Helper to get a different student ID for testing filters
    private function getAnotherStudentId($currentStudentId)
    {
        $studentModel = new StudentModel();
        $anotherStudent = $studentModel->where('id !=', $currentStudentId)->first();
        if ($anotherStudent) {
            return $anotherStudent['id'];
        }
        // Create a new student if no other exists
        $userModel = new UserModel();
        $roleModel = new \App\Models\RoleModel();
        $studentRole = $roleModel->where('role_name', 'Siswa')->first();
        $studentRoleId = $studentRole ? $studentRole['id'] : null;

        if (!$studentRoleId) {
            log_message('error', 'Helper getAnotherStudentId: Role "Siswa" not found.');
            // Try to find any student user that is not the current one
            $fallbackUser = $userModel->where('role_id IS NOT NULL', null, false) // Placeholder for a valid role if Siswa not found by name
                                     ->where('id !=', $currentStudentId) // This is wrong, currentStudentId is student.id not user.id
                                     ->first();
            if($fallbackUser) { // This logic is flawed, needs a proper student.
                 // This path is problematic, ideally seeders provide enough diverse data.
            }
            // For now, let's assume seeder has other students.
            // This helper should primarily find, not create complex data.
            // If we must create, ensure all dependencies are met.
            $this->fail("Role 'Siswa' not found, cannot reliably create another student for test.");
            return null; // Or throw exception
        }

        $faker = Factory::create();
        $newUserName = $faker->userName . $faker->numerify('##');
        $newUser = $userModel->insert([
            'username' => $newUserName,
            'password' => 'password', // Relies on UserModel to hash
            'password_confirm' => 'password',
            'full_name' => $faker->name,
            'role_id' => $studentRoleId,
            'is_active' => 1
        ]);
        if (!$newUser) {
            log_message('error', 'Helper getAnotherStudentId: Failed to create new user. Errors: '.json_encode($userModel->errors()));
            $this->fail('Failed to create a new user for another student in helper. Errors: '.json_encode($userModel->errors()));
            return null;
        }

        $newStudentNisn = $faker->unique()->numerify('00########');
        $newStudentNis = $faker->unique()->numerify('10####');

        $newStudentId = $studentModel->insert([
            'user_id' => $newUser,
            'nisn' => $newStudentNisn,
            'nis' => $newStudentNis,
            'full_name' => $faker->name, // Can be same as user's full_name or different
            'gender' => $faker->randomElement(['L', 'P']),
            'pob' => $faker->city,
            'dob' => $faker->date('Y-m-d', '2007-12-31'),
            'join_date' => date('Y-m-d')
        ]);

        if (!$newStudentId) {
            log_message('error', 'Helper getAnotherStudentId: Failed to create new student. Errors: '.json_encode($studentModel->errors()));
            $this->fail('Failed to create another student in helper. Errors: '.json_encode($studentModel->errors()));
            return null;
        }
        return $newStudentId;
    }

}
