<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\DailyAttendanceModel;

class DailyAttendanceControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder';

    protected $adminUser, $stafTUUser, $guruUser;
    protected $classId, $studentId1, $studentId2;

    protected function setUp(): void
    {
        parent::setUp();
        helper('auth'); // Load auth helper here for the test environment

        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'admin')->first();
        $this->stafTUUser = $userModel->where('username', 'staf')->first();
        $this->guruUser = $userModel->where('username', 'guru1')->first();

        $this->assertNotNull($this->adminUser, "User 'admin' not found.");
        $this->assertNotNull($this->stafTUUser, "User 'staf' not found.");
        $this->assertNotNull($this->guruUser, "User 'guru1' not found.");

        $classModel = new ClassModel();
        $firstClass = $classModel->orderBy('id', 'ASC')->first();
        $this->assertNotNull($firstClass, "No class found. Check ClassSeeder.");
        $this->classId = $firstClass['id'];

        // Ensure class has students
        $studentModel = new StudentModel();
        $studentsInClass = $studentModel
            ->select('students.id')
            ->join('class_student cs', 'cs.student_id = students.id')
            ->where('cs.class_id', $this->classId)
            ->orderBy('students.id', 'ASC')
            ->findAll(2);

        if (count($studentsInClass) < 2) {
            // Create students and add to class if not enough
            $faker = \Faker\Factory::create();
            for ($i = count($studentsInClass); $i < 2; $i++) {
                $sUserId = $userModel->skipValidation(true)->insert([
                    'username' => $faker->userName.rand(1000,9999), 'password' => 'password', 'password_confirm' => 'password',
                    'full_name'=> $faker->name, 'role_id' => 5, 'is_active' => 1 // Role Siswa
                ]);
                $newStudentId = $studentModel->skipValidation(true)->insert([
                    'user_id' => $sUserId, 'nisn' => $faker->unique()->numerify('005#######'), 'nis' => $faker->unique()->numerify('105###'),
                    'full_name' => $faker->name, 'gender' => ($i % 2 == 0 ? 'L' : 'P'), 'pob' => 'TestCity', 'dob' => "2007-01-0".($i+1), 'join_date' => date('Y-m-d')
                ]);
                $this->db->table('class_student')->insert(['class_id' => $this->classId, 'student_id' => $newStudentId]);
            }
            $studentsInClass = $studentModel->select('students.id')->join('class_student cs', 'cs.student_id = students.id')->where('cs.class_id', $this->classId)->orderBy('students.id', 'ASC')->findAll(2);
        }

        $this->assertCount(2, $studentsInClass, "Failed to ensure at least 2 students in the test class.");
        $this->studentId1 = $studentsInClass[0]['id'];
        $this->studentId2 = $studentsInClass[1]['id'];
    }

    public function testIndexNoLogin()
    {
        $result = $this->call('get', route_to('admin_daily_attendance_index'));
        $result->assertStatus(302);
        $authConfig = config('Auth');
        $redirectUrl = $authConfig ? $authConfig->loginRedirect() : '/login';
        if (!$authConfig) {
            log_message('debug', 'Auth config was null in DailyAttendanceControllerTest::testIndexNoLogin. Defaulting redirect check to /login.');
        }
        $result->assertRedirectTo($redirectUrl);
    }

    public function testIndexAsGuruUnauthorized()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('admin_daily_attendance_index'));

        // AuthFilter should redirect to 'unauthorized-access' route due to role mismatch
        $result->assertRedirectTo(site_url('unauthorized-access'));
    }

    public function testIndexAsAdmin()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->get(route_to('admin_daily_attendance_index'));

        $result->assertStatus(200);
        $result->assertSee('Input Absensi Harian Umum');
    }

    public function testIndexAsStafTU()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->stafTUUser['id'], 'role_id' => $this->stafTUUser['role_id']
        ])->get(route_to('admin_daily_attendance_index'));

        $result->assertStatus(200);
        $result->assertSee('Input Absensi Harian Umum');
    }

    public function testIndexWithClassAndDateLoadsStudents()
    {
        $date = date('Y-m-d');
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->get(route_to('admin_daily_attendance_index')."?class_id={$this->classId}&date={$date}");

        $result->assertStatus(200);
        $studentModel = new StudentModel();
        $student1 = $studentModel->find($this->studentId1);
        $result->assertSee($student1['full_name']);
    }

    public function testSaveNoLogin()
    {
        $result = $this->call('post', route_to('admin_daily_attendance_save'));
        $result->assertStatus(302);
        $authConfig = config('Auth');
        $redirectUrl = $authConfig ? $authConfig->loginRedirect() : '/login';
        if (!$authConfig) {
            log_message('debug', 'Auth config was null in DailyAttendanceControllerTest::testSaveNoLogin. Defaulting redirect check to /login.');
        }
        $result->assertRedirectTo($redirectUrl);
    }

    public function testSaveAsGuruUnauthorized()
    {
         $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->post(route_to('admin_daily_attendance_save'), []);
        // AuthFilter should redirect to 'unauthorized-access' route
        $result->assertRedirectTo(site_url('unauthorized-access'));
    }

    public function testSaveIncompleteData()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->post(route_to('admin_daily_attendance_save'), [
            'class_id' => $this->classId,
            // Missing attendance_date and attendance data
        ]);
        $result->assertRedirect(); // Redirects back
        $result->assertSessionHas('error', 'Data tidak lengkap. Harap pilih kelas, tanggal, dan isi absensi.');
    }

    public function testSaveValidData()
    {
        $date = date('Y-m-d', strtotime('-1 day')); // Use a slightly different date
        $postData = [
            'class_id' => $this->classId,
            'attendance_date' => $date,
            'attendance' => [
                $this->studentId1 => ['status' => DailyAttendanceModel::STATUS_HADIR, 'remarks' => 'Hadir test'],
                $this->studentId2 => ['status' => DailyAttendanceModel::STATUS_ALFA, 'remarks' => 'Alfa test'],
            ]
        ];

        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->adminUser['id'], 'role_id' => $this->adminUser['role_id']
        ])->post(route_to('admin_daily_attendance_save'), $postData);

        $result->assertRedirectTo(site_url("admin/daily-attendance?class_id={$this->classId}&date={$date}"));
        $result->assertSessionHas('message', 'Absensi harian berhasil disimpan.');

        $this->seeInDatabase('daily_attendances', [
            'class_id' => $this->classId, 'student_id' => $this->studentId1,
            'attendance_date' => $date, 'status' => DailyAttendanceModel::STATUS_HADIR
        ]);
        $this->seeInDatabase('daily_attendances', [
            'class_id' => $this->classId, 'student_id' => $this->studentId2,
            'attendance_date' => $date, 'status' => DailyAttendanceModel::STATUS_ALFA
        ]);
    }
}
