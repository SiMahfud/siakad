<?php

namespace Tests\Controllers\Guru;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\StudentModel;
use App\Models\ScheduleModel;
use App\Models\AttendanceModel;
use App\Models\ClassStudentModel;
use App\Database\Seeds\ScheduleSeeder as AppScheduleSeeder; // To access static ID

class AttendanceControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder'; // TestSeeder calls ScheduleSeeder

    protected $guruUser, $adminUser, $teacherData1;
    protected $scheduleId, $classId, $studentId1, $studentId2;

    protected function setUp(): void
    {
        parent::setUp();

        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'admin')->first();
        $this->guruUser = $userModel->where('username', 'guru1')->first();
        $this->assertNotNull($this->guruUser, "User 'guru1' not found. Check UserSeeder.");

        $teacherModel = new TeacherModel();
        $this->teacherData1 = $teacherModel->where('user_id', $this->guruUser['id'])->first();
        $this->assertNotNull($this->teacherData1, "Teacher data for 'guru1' not found. Check TeacherSeeder.");

        // Fetch a schedule created for $this->teacherData1['id'] by ScheduleSeeder
        $scheduleModel = new ScheduleModel();
        $schedule = $scheduleModel->where('teacher_id', $this->teacherData1['id'])->first();

        if (!$schedule) {
            // If no schedule found, create one explicitly for this teacher
            $classModel = new ClassModel();
            $firstClass = $classModel->first();
            if (!$firstClass) { // Should not happen if ClassSeeder ran
                 $this->fail("No classes found. ClassSeeder might have failed or class table is empty.");
            }
            $this->classId = $firstClass['id'];

            $subjectModel = new SubjectModel();
            $firstSubject = $subjectModel->first();
             if (!$firstSubject) { // Should not happen if SubjectSeeder ran
                 $this->fail("No subjects found. SubjectSeeder might have failed or subject table is empty.");
            }
            $subjectId = $firstSubject['id'];

            $newScheduleId = $scheduleModel->skipValidation(true)->insert([
                'class_id'      => $this->classId,
                'subject_id'    => $subjectId,
                'teacher_id'    => $this->teacherData1['id'],
                'day_of_week'   => 1, // Monday
                'start_time'    => '07:00:00',
                'end_time'      => '08:30:00',
                'academic_year' => '2024/2025',
                'semester'      => 1,
            ]);
            $this->assertIsNumeric($newScheduleId, "Failed to create fallback schedule in setUp.");
            $this->scheduleId = $newScheduleId;
            $schedule = $scheduleModel->find($this->scheduleId);
        } else {
            $this->scheduleId = $schedule['id'];
            $this->classId = $schedule['class_id'];
        }

        $this->assertNotNull($this->scheduleId, "Could not obtain or create a scheduleId for the test teacher.");
        $this->assertNotNull($this->classId, "Could not obtain classId from schedule.");

        // Get students from this class
        $classStudentModel = new ClassStudentModel();
        $studentsInClass = $classStudentModel->getStudentsInClass($this->classId);

        if (empty($studentsInClass)) {
            // If class has no students, create and add one.
            $studentModel = new StudentModel();
            $faker = \Faker\Factory::create();
            $sUser1Id = $userModel->skipValidation(true)->insert([
                'username' => $faker->userName.rand(100,999), 'password' => 'password', 'password_confirm' => 'password',
                'full_name'=> $faker->name, 'role_id' => 5, 'is_active' => 1 // Role Siswa
            ]);
            $this->studentId1 = $studentModel->skipValidation(true)->insert([
                'user_id' => $sUser1Id, 'nisn' => $faker->unique()->numerify('003#######'), 'nis' => $faker->unique()->numerify('103###'),
                'full_name' => $faker->name, 'gender' => 'L', 'pob' => 'TestCity1', 'dob' => '2005-01-01', 'join_date' => date('Y-m-d')
            ]);
            $this->db->table('class_student')->insert(['class_id' => $this->classId, 'student_id' => $this->studentId1]);
            $studentsInClass = $classStudentModel->getStudentsInClass($this->classId); // Re-fetch
        }

        $this->assertGreaterThanOrEqual(1, count($studentsInClass), "Class for schedule still has no students after fallback.");
        $this->studentId1 = $studentsInClass[0]['id'];

        if (count($studentsInClass) > 1) {
            $this->studentId2 = $studentsInClass[1]['id'];
        } else {
            // Create another student and add to class if only one exists
            $studentModel = new StudentModel();
            $faker = \Faker\Factory::create();
            $sUser2Id = $userModel->skipValidation(true)->insert([
                'username' => $faker->userName.rand(100,999), 'password' => 'password', 'password_confirm' => 'password',
                'full_name'=> $faker->name, 'role_id' => 5, 'is_active' => 1 // Role Siswa
            ]);
            $this->studentId2 = $studentModel->skipValidation(true)->insert([
                'user_id' => $sUser2Id, 'nisn' => $faker->unique()->numerify('002#######'), 'nis' => $faker->unique()->numerify('102###'),
                'full_name' => $faker->name, 'gender' => 'P', 'pob' => 'TestCity2', 'dob' => '2006-02-02', 'join_date' => date('Y-m-d')
            ]);
            $this->db->table('class_student')->insert(['class_id' => $this->classId, 'student_id' => $this->studentId2]);
        }
         $this->assertNotNull($this->studentId1, "studentId1 could not be set.");
         $this->assertNotNull($this->studentId2, "studentId2 could not be set.");
    }

    public function testSelectScheduleNoLogin()
    {
        $result = $this->call('get', route_to('guru_attendance_select_schedule'));
        $result->assertStatus(302); // Redirect to login

        $authConfig = config('Auth');
        $redirectUrl = $authConfig ? $authConfig->loginRedirect() : '/login';
        if (!$authConfig) {
            log_message('debug', 'Auth config was null in testSelectScheduleNoLogin (Attendance). Defaulting redirect check to /login.');
        }
        $result->assertRedirectTo($redirectUrl);
    }

    public function testSelectScheduleAsGuru()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('guru_attendance_select_schedule'));

        $result->assertStatus(200);
        $result->assertSee('Select Schedule for Attendance');
    }

    public function testShowAttendanceFormNoParams()
    {
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('guru_attendance_form'));

        $result->assertRedirectTo(site_url('guru/attendance/select-schedule'));
        $result->assertSessionHas('error', 'Schedule ID or date not provided.');
    }

    public function testShowAttendanceFormValidParams()
    {
        $date = date('Y-m-d');
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('guru_attendance_form')."?schedule_id={$this->scheduleId}&date={$date}");

        $result->assertStatus(200);
        $result->assertSee('Input Attendance');

        // Check if student name is on the page
        $studentModel = new StudentModel();
        $student1 = $studentModel->find($this->studentId1);
        $result->assertSee($student1['full_name']);
    }

    public function testShowAttendanceFormUnauthorizedTeacher()
    {
        // Create another teacher and schedule not owned by $this->guruUser
        $userModel = new UserModel();
        $otherGuruUser = $userModel->skipValidation(true)->insert([
            'username' => 'otherguru'.rand(1,100), 'password' => 'password', 'password_confirm' => 'password',
            'full_name'=> 'Other Guru', 'role_id' => 4, 'is_active' => 1 // Role Guru
        ]);
        $teacherModel = new TeacherModel();
        $otherTeacherId = $teacherModel->skipValidation(true)->insert([
            'user_id' => $otherGuruUser, 'nip' => 'G999'.rand(1,100), 'full_name' => 'Other Guru', 'gender' => 'P', 'phone' => '08999'
        ]);

        $scheduleModel = new ScheduleModel();
        $otherScheduleId = $scheduleModel->skipValidation(true)->insert([
            'class_id' => $this->classId, // Same class
            'subject_id' => 1, // Assuming subject 1 exists
            'teacher_id' => $otherTeacherId,
            'day_of_week' => 1, 'start_time' => '10:00:00', 'end_time' => '11:00:00',
            'academic_year' => '2023/2024', 'semester' => 1
        ]);
        $this->assertNotEquals($this->teacherData1['id'], $otherTeacherId, "Created otherTeacherId is same as loggedInTeacherId.");
        $this->assertNotNull($otherScheduleId, "Failed to create other schedule for unauthorized test.");


        $date = date('Y-m-d');
        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->get(route_to('guru_attendance_form')."?schedule_id={$otherScheduleId}&date={$date}");

        $result->assertRedirectTo(site_url('guru/attendance/select-schedule'));
        $result->assertSessionHas('error', 'You are not authorized to input attendance for this schedule.');
    }

    public function testSaveAttendanceValid()
    {
        $date = date('Y-m-d');
        $postData = [
            'schedule_id' => $this->scheduleId,
            'attendance_date' => $date,
            'attendance' => [
                $this->studentId1 => ['status' => AttendanceModel::STATUS_HADIR, 'remarks' => 'OK'],
                $this->studentId2 => ['status' => AttendanceModel::STATUS_SAKIT, 'remarks' => 'Flu ringan'],
            ]
        ];

        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->post(route_to('guru_attendance_save'), $postData);

        $result->assertRedirectTo(site_url("guru/attendance/form?schedule_id={$this->scheduleId}&date={$date}"));
        $result->assertSessionHas('success', 'Attendance saved successfully.');

        $this->seeInDatabase('attendances', [
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId1,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_HADIR
        ]);
        $this->seeInDatabase('attendances', [
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId2,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_SAKIT
        ]);
    }

    public function testSaveAttendanceExistingRecordsUpdate()
    {
        $date = date('Y-m-d', strtotime('+1 day')); // Use a new date to avoid unique constraint with other tests.
        // First, insert initial records
        $attendanceModel = new AttendanceModel();
        $attendanceModel->insert([
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId1,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_ALFA,
            'recorded_by_user_id' => $this->guruUser['id']
        ]);
         $attendanceModel->insert([
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId2,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_ALFA,
            'recorded_by_user_id' => $this->guruUser['id']
        ]);


        $postData = [
            'schedule_id' => $this->scheduleId,
            'attendance_date' => $date,
            'attendance' => [
                $this->studentId1 => ['status' => AttendanceModel::STATUS_HADIR, 'remarks' => 'Correction: Present'],
                $this->studentId2 => ['status' => AttendanceModel::STATUS_IZIN, 'remarks' => 'Surat Izin Dokter'],
            ]
        ];

        $result = $this->withSession([
            'is_logged_in' => true, 'user_id' => $this->guruUser['id'], 'role_id' => $this->guruUser['role_id']
        ])->post(route_to('guru_attendance_save'), $postData);

        $result->assertRedirectTo(site_url("guru/attendance/form?schedule_id={$this->scheduleId}&date={$date}"));
        $result->assertSessionHas('success', 'Attendance saved successfully.');

        $this->seeInDatabase('attendances', [
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId1,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_HADIR, 'remarks' => 'Correction: Present'
        ]);
        $this->seeInDatabase('attendances', [
            'schedule_id' => $this->scheduleId, 'student_id' => $this->studentId2,
            'attendance_date' => $date, 'status' => AttendanceModel::STATUS_IZIN, 'remarks' => 'Surat Izin Dokter'
        ]);
    }

}
