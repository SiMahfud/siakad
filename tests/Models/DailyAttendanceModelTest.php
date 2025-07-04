<?php

namespace Tests\Models;

use App\Models\DailyAttendanceModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\UserModel;
use App\Database\Seeds\StudentSeeder as AppStudentSeeder;
use App\Database\Seeds\ClassSeeder as AppClassSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

class DailyAttendanceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder';

    protected $model;
    protected $faker;

    protected static $studentId;
    protected static $classId;
    protected static $userId; // For recorded_by_user_id

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new DailyAttendanceModel();
        $this->faker = Factory::create();

        self::$studentId = AppStudentSeeder::$student1Id;
        self::$classId = AppClassSeeder::$classX_A_Id;

        $userModel = new UserModel();
        $adminUser = $userModel->where('username', 'admin')->first();
        if ($adminUser) {
            self::$userId = $adminUser['id'];
        } else {
            log_message('error', 'DailyAttendanceModelTest::setUp - Admin user not found for recorded_by_user_id.');
            // Create a fallback user if admin not found by seeder
            $roleModel = new \App\Models\RoleModel();
            $anyRole = $roleModel->first() ?? $roleModel->insert(['role_name' => 'Fallback Role', 'description' => 'For testing']);
            $fallbackUserId = $userModel->insert([
                'username' => 'fallbackrecorder', 'password' => 'password', 'password_confirm' => 'password',
                'full_name' => 'Fallback Recorder', 'role_id'   => is_array($anyRole) ? $anyRole['id'] : $anyRole, 'is_active' => 1
            ]);
            self::$userId = $fallbackUserId;
        }

        if (!self::$studentId) {
            log_message('error', 'DailyAttendanceModelTest::setUp - StudentSeeder::$student1Id is not set.');
            // Create a student if seeder failed
            $studentModel = new StudentModel();
            $faker = Factory::create();
            $studentUserId = $userModel->insert([
                'username' => $faker->userName, 'password' => 'password', 'password_confirm' => 'password',
                'full_name' => $faker->name, 'role_id' => 4, 'is_active' => 1 // Assuming role_id 4 is Siswa
            ]);
            self::$studentId = $studentModel->insert([
                 'user_id' => $studentUserId, 'nisn' => $faker->unique()->numerify('001#######'), 'nis' => $faker->unique()->numerify('100###'),
                 'full_name' => $faker->name, 'gender' => 'L', 'pob' => 'Testville', 'dob' => '2005-01-01', 'join_date' => date('Y-m-d')
            ]);
        }
        if (!self::$classId) {
            log_message('error', 'DailyAttendanceModelTest::setUp - ClassSeeder::$classX_A_Id is not set.');
             // Create a class if seeder failed
            $classModel = new ClassModel();
            $teacherModel = new \App\Models\TeacherModel();
            $anyTeacher = $teacherModel->first();
            if(!$anyTeacher){ //Create teacher if none
                 $teacherUserId = $userModel->insert(['username' => 'teacherclass'.rand(1,100), 'password' => 'password', 'password_confirm' => 'password', 'full_name' => 'Teacher Class', 'role_id' => 3, 'is_active' => 1]);
                 $anyTeacherId = $teacherModel->insert(['user_id' => $teacherUserId, 'nip' => 'NIPCLASS'.rand(1,100), 'full_name' => 'Teacher Class', 'gender' => 'L', 'phone' => '123']);
                 $anyTeacher = $teacherModel->find($anyTeacherId);
            }
            self::$classId = $classModel->insert([
                'class_name' => 'Fallback Class', 'wali_kelas_id' => $anyTeacher['id'], 'academic_year' => '2023/2024', 'fase' => 'E'
            ]);
        }
         if (!self::$userId) {
            log_message('critical', 'DailyAttendanceModelTest::setUp - UserID for recorder could not be established.');
            $this->fail("UserID for recorder could not be established in setUp.");
        }
    }

    public function testCreateDailyAttendanceValid()
    {
        $this->assertNotNull(self::$studentId, "Prerequisite studentId is null");
        $this->assertNotNull(self::$classId, "Prerequisite classId is null");
        $this->assertNotNull(self::$userId, "Prerequisite userId is null");

        $data = [
            'student_id'      => self::$studentId,
            'class_id'        => self::$classId,
            'attendance_date' => date('Y-m-d'),
            'status'          => DailyAttendanceModel::STATUS_HADIR,
            'remarks'         => 'Present today',
            'recorded_by_user_id' => self::$userId,
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id, $this->model->errors() ? implode(', ', $this->model->errors()) : 'Insert failed');
        $this->seeInDatabase('daily_attendances', ['id' => $id, 'status' => DailyAttendanceModel::STATUS_HADIR]);
    }

    public function testCreateDailyAttendanceInvalidForeignKey()
    {
        $data = [
            'student_id'      => 99999, // Invalid
            'class_id'        => self::$classId,
            'attendance_date' => date('Y-m-d'),
            'status'          => DailyAttendanceModel::STATUS_ALFA,
            'recorded_by_user_id' => self::$userId,
        ];
        $result = $this->model->insert($data);
        $this->assertFalse($result);
        $this->assertArrayHasKey('student_id', $this->model->errors());
    }

    public function testCreateDailyAttendanceDuplicateEntry()
    {
        $date = date('Y-m-d');
        $data1 = [
            'student_id'      => self::$studentId,
            'class_id'        => self::$classId,
            'attendance_date' => $date,
            'status'          => DailyAttendanceModel::STATUS_HADIR,
            'recorded_by_user_id' => self::$userId,
        ];
        $this->model->insert($data1);

        $data2 = [
            'student_id'      => self::$studentId, // Same student
            'class_id'        => self::$classId,
            'attendance_date' => $date,          // Same date
            'status'          => DailyAttendanceModel::STATUS_SAKIT,
            'recorded_by_user_id' => self::$userId,
        ];

        try {
            $this->model->insert($data2);
            $this->fail('Expected a DatabaseException for duplicate entry, but none was thrown.');
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            $this->assertStringContainsStringIgnoringCase('UNIQUE constraint failed: daily_attendances.student_id, daily_attendances.attendance_date', $e->getMessage());
        }
    }

    public function testGetDailyAttendance()
    {
        $date = '2023-10-05';
        $this->model->insert([
            'student_id' => self::$studentId, 'class_id' => self::$classId, 'attendance_date' => $date,
            'status' => DailyAttendanceModel::STATUS_SAKIT, 'recorded_by_user_id' => self::$userId,
        ]);

        $results = $this->model->getDailyAttendance(self::$classId, $date);
        $this->assertCount(1, $results);
        $this->assertEquals(self::$studentId, $results[0]['student_id']);
    }

    public function testGetStudentDailyAttendanceRange()
    {
        $date1 = '2023-10-01';
        $date2 = '2023-10-02';
        $this->model->insert([
            'student_id' => self::$studentId, 'class_id' => self::$classId, 'attendance_date' => $date1,
            'status' => DailyAttendanceModel::STATUS_IZIN, 'recorded_by_user_id' => self::$userId,
        ]);
         $this->model->insert([
            'student_id' => self::$studentId, 'class_id' => self::$classId, 'attendance_date' => $date2,
            'status' => DailyAttendanceModel::STATUS_ALFA, 'recorded_by_user_id' => self::$userId,
        ]);

        $results = $this->model->getStudentDailyAttendanceRange(self::$studentId, '2023-10-01', '2023-10-05');
        $this->assertCount(2, $results);
        $this->assertEquals(DailyAttendanceModel::STATUS_IZIN, $results[0]['status']);
        $this->assertEquals(DailyAttendanceModel::STATUS_ALFA, $results[1]['status']);
    }

    public function testSaveBulkDailyAttendanceNewAndOverwrite()
    {
        $date = '2023-09-10';
        // Initial entry for student1
        $this->model->insert([
            'student_id' => self::$studentId, 'class_id' => self::$classId, 'attendance_date' => $date,
            'status' => DailyAttendanceModel::STATUS_HADIR, 'recorded_by_user_id' => self::$userId, 'remarks' => 'Initial Hadir'
        ]);

        // Get another student (or create if none other seeded)
        $studentModel = new StudentModel();
        $anotherStudent = $studentModel->where('id !=', self::$studentId)->first();
        $student2Id = $anotherStudent ? $anotherStudent['id'] : $this->getAnotherStudentId(self::$studentId);
        $this->assertNotNull($student2Id, "Could not get/create a second student for bulk test.");


        $bulkData = [
            self::$studentId => ['status' => DailyAttendanceModel::STATUS_SAKIT, 'remarks' => 'Updated to Sakit'], // Overwrite
            $student2Id      => ['status' => DailyAttendanceModel::STATUS_ALFA, 'remarks' => 'New Alfa entry'],     // New
        ];

        $result = $this->model->saveBulkDailyAttendance(self::$classId, $date, $bulkData, self::$userId);
        $this->assertTrue($result);

        $this->seeInDatabase('daily_attendances', [
            'student_id' => self::$studentId, 'attendance_date' => $date,
            'status' => DailyAttendanceModel::STATUS_SAKIT, 'remarks' => 'Updated to Sakit'
        ]);
        $this->seeInDatabase('daily_attendances', [
            'student_id' => $student2Id, 'attendance_date' => $date,
            'status' => DailyAttendanceModel::STATUS_ALFA, 'remarks' => 'New Alfa entry'
        ]);
    }

    public function testSaveBulkDailyAttendanceWithEmptyStatusDeletesRecord()
    {
        $date = '2023-09-11';
        // Initial entry for student1
        $initialRecord = [
            'student_id' => self::$studentId, 'class_id' => self::$classId, 'attendance_date' => $date,
            'status' => DailyAttendanceModel::STATUS_HADIR, 'recorded_by_user_id' => self::$userId, 'remarks' => 'To be deleted'
        ];
        $insertedId = $this->model->insert($initialRecord);
        $this->seeInDatabase('daily_attendances', ['id' => $insertedId]);


        $bulkData = [
            self::$studentId => ['status' => '', 'remarks' => ''], // Empty status should delete
        ];

        $result = $this->model->saveBulkDailyAttendance(self::$classId, $date, $bulkData, self::$userId);
        $this->assertTrue($result);
        $this->dontSeeInDatabase('daily_attendances', ['id' => $insertedId]);
    }


    // Helper from AttendanceModelTest, adapted slightly if needed
    private function getAnotherStudentId($currentStudentId)
    {
        $studentModel = new StudentModel();
        $anotherStudent = $studentModel->where('id !=', $currentStudentId)->first();
        if ($anotherStudent) {
            return $anotherStudent['id'];
        }

        $userModel = new UserModel();
        $roleModel = new \App\Models\RoleModel();
        $faker = Factory::create();

        $studentRole = $roleModel->where('role_name', 'Siswa')->first();
        $studentRoleId = $studentRole ? $studentRole['id'] : 4; // Default to 4 if not found

        $newUserName = $faker->unique()->userName . $faker->numerify('##');
        $newUserId = $userModel->insert([
            'username' => $newUserName, 'password' => 'password', 'password_confirm' => 'password',
            'full_name' => $faker->name, 'role_id' => $studentRoleId, 'is_active' => 1
        ]);
        if(!$newUserId) {
             log_message('error', 'DailyAttendanceModelTest Helper: Failed to create user for another student. Errors: '.json_encode($userModel->errors()));
             return null; // Or fail test
        }

        $newStudentId = $studentModel->insert([
            'user_id' => $newUserId,
            'nisn' => $faker->unique()->numerify('0012#######'),
            'nis' => $faker->unique()->numerify('102###'),
            'full_name' => $faker->name,
            'gender' => 'P', 'pob' => 'Otherville', 'dob' => '2006-01-01', 'join_date' => date('Y-m-d')
        ]);
         if(!$newStudentId) {
             log_message('error', 'DailyAttendanceModelTest Helper: Failed to create another student. Errors: '.json_encode($studentModel->errors()));
             return null; // Or fail test
        }
        return $newStudentId;
    }
}
