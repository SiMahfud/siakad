<?php

namespace Tests\Models;

use App\Models\AssessmentModel;
use App\Models\StudentModel;
use App\Models\SubjectModel;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

class AssessmentModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false; // Set to false to run migrations for each test
    protected $refresh     = true;
    protected $namespace   = 'App';
    // protected $basePath    = APPPATH . 'Database'; // For seeders in app/Database/Seeds
    protected $seed        = 'App\Database\Seeds\TestSeeder'; // Main seeder calling others

    protected $model;
    protected $faker;

    // Data IDs from seeders
    protected static $studentId;
    protected static $subjectId;
    protected static $classId;
    protected static $teacherId;
    protected static $userIdForTeacher; // User ID for the teacher
    protected static $userIdForStudent; // User ID for the student
    protected static $userIdForParent;  // User ID for the parent

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // No direct DB operations here, use setUp() or test methods
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AssessmentModel();
        $this->faker = Factory::create();

        // Ensure seeders have run and we can get some IDs
        // This is a bit tricky because seeders run per-test if migrateOnce is false and refresh is true.
        // We might need to fetch these IDs within each test or ensure seeders create predictable data.

        // For robust testing, it's better to create specific prerequisite data within tests
        // or have seeders that create known, testable entities.
        // Let's assume TestSeeder creates at least one of each.

        $userModel = new UserModel();
        $teacherUser = $userModel->where('role_id', 3)->first(); // Assuming role_id 3 is 'Guru'
        if (!$teacherUser) {
            log_message('error', 'TestSetup: Default teacher user not found from UserSeeder.');
            // Create one if not found
            $teacherUser = $userModel->insert([
                'username' => 'testteacher',
                'email'    => 'testteacher@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role_id'  => 3, // Assuming role_id 3 is Guru
                'active'   => 1
            ]);
            self::$userIdForTeacher = $teacherUser; // This is an ID
        } else {
            self::$userIdForTeacher = $teacherUser['id'];
        }

        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->where('user_id', self::$userIdForTeacher)->first();
        if (!$teacher) {
            log_message('error', 'TestSetup: Default teacher not found from TeacherSeeder.');
            $teacherId = $teacherModel->insert([
                'user_id' => self::$userIdForTeacher,
                'nip' => '123456789012345000',
                'full_name' => 'Test Teacher seeded',
                'gender' => 'L',
                'phone' => '081234567000',
            ]);
            self::$teacherId = $teacherId;
        } else {
            self::$teacherId = $teacher['id'];
        }


        $studentUser = $userModel->where('role_id', 4)->first(); // Assuming role_id 4 is 'Siswa'
         if (!$studentUser) {
            log_message('error', 'TestSetup: Default student user not found from UserSeeder.');
            $studentUserId = $userModel->insert([
                'username' => 'teststudent',
                'email'    => 'teststudent@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role_id'  => 4,
                'active'   => 1
            ]);
            self::$userIdForStudent = $studentUserId;
        } else {
            self::$userIdForStudent = $studentUser['id'];
        }

        $studentModel = new StudentModel();
        $student = $studentModel->where('user_id', self::$userIdForStudent)->first();
        if (!$student) {
            log_message('error', 'TestSetup: Default student not found from StudentSeeder.');
             $studentId = $studentModel->insert([
                'user_id' => self::$userIdForStudent,
                'nis' => '10000',
                'nisn' => '0000000001',
                'full_name' => 'Test Student seeded',
                'gender' => 'L',
                'pob' => 'Test City',
                'dob' => '2005-01-01',
            ]);
            self::$studentId = $studentId;
        } else {
            self::$studentId = $student['id'];
        }

        $subjectModel = new SubjectModel();
        $subject = $subjectModel->first();
        if (!$subject) {
            log_message('error', 'TestSetup: Default subject not found from SubjectSeeder.');
            $subjectId = $subjectModel->insert([
                'subject_name' => 'Test Subject seeded',
                'subject_code' => 'TST-001'
            ]);
            self::$subjectId = $subjectId;
        } else {
            self::$subjectId = $subject['id'];
        }

        $classModel = new ClassModel();
        $class_ = $classModel->where('wali_kelas_id', self::$teacherId)->first();
        if (!$class_) {
            log_message('error', 'TestSetup: Default class not found from ClassSeeder or wali_kelas mismatch.');
             $classId = $classModel->insert([
                'class_name' => 'Test Class X-A seeded',
                'wali_kelas_id' => self::$teacherId, // Ensure this teacher exists
                'academic_year' => '2023/2024'
            ]);
            self::$classId = $classId;
        } else {
            self::$classId = $class_['id'];
        }

        // Ensure student is in class (ClassStudentSeeder should handle this, but as a fallback)
        $db = \Config\Database::connect();
        $isStudentInClass = $db->table('class_student')
                               ->where('class_id', self::$classId)
                               ->where('student_id', self::$studentId)
                               ->countAllResults() > 0;
        if (!$isStudentInClass) {
            $db->table('class_student')->insert([
                'class_id' => self::$classId,
                'student_id' => self::$studentId
            ]);
        }

    }

    public function testCreateAssessmentValid()
    {
        $data = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Test Sumatif 1',
            'score'           => 85.50,
            'assessment_date' => date('Y-m-d'),
            'description'     => 'Deskripsi Test Sumatif 1',
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);
        $this->seeInDatabase('assessments', ['id' => $id, 'assessment_title' => 'Test Sumatif 1']);
    }

    public function testCreateAssessmentInvalidScoreTooHigh()
    {
        $data = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Test Sumatif Invalid Score',
            'score'           => 105.00, // Invalid score
            'assessment_date' => date('Y-m-d'),
        ];

        $result = $this->model->insert($data);
        $this->assertFalse($result);
        $errors = $this->model->errors();
        $this->assertArrayHasKey('score', $errors);
        $this->assertEquals('Score cannot exceed 100.', $errors['score']);
    }

    public function testCreateAssessmentInvalidScoreNegative()
    {
        $data = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Test Sumatif Invalid Score Negative',
            'score'           => -10.00, // Invalid score
            'assessment_date' => date('Y-m-d'),
        ];

        $result = $this->model->insert($data);
        $this->assertFalse($result);
        $errors = $this->model->errors();
        $this->assertArrayHasKey('score', $errors);
        $this->assertEquals('Score must be 0 or greater.', $errors['score']);
    }

    public function testCreateAssessmentInvalidForeignKeyStudent()
    {
        $data = [
            'student_id'      => 99999, // Non-existent student
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'FORMATIF',
            'assessment_title'=> 'Test Formatif Invalid Student',
            'assessment_date' => date('Y-m-d'),
        ];

        $result = $this->model->insert($data);
        $this->assertFalse($result);
        $errors = $this->model->errors();
        $this->assertArrayHasKey('student_id', $errors);
    }

    public function testUpdateAssessment()
    {
        $data = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'FORMATIF',
            'assessment_title'=> 'Initial Formatif Title',
            'score'           => 70.00,
            'assessment_date' => date('Y-m-d'),
        ];
        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $updateData = [
            'assessment_title' => 'Updated Formatif Title',
            'score'            => 75.50,
        ];
        $this->model->update($id, $updateData);
        $this->seeInDatabase('assessments', ['id' => $id, 'assessment_title' => 'Updated Formatif Title', 'score' => '75.50']);
    }

    public function testDeleteAssessment()
    {
        $data = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'To Be Deleted',
            'score'           => 90.00,
            'assessment_date' => date('Y-m-d'),
        ];
        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $this->model->delete($id);
        $this->dontSeeInDatabase('assessments', ['id' => $id]);
    }

    public function testGetAssessmentsForRecap()
    {
        // Create a few assessments for the same student, class, subject
        $data1 = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'FORMATIF',
            'assessment_title'=> 'Recap Formatif 1',
            'assessment_date' => '2023-10-01',
            'score'           => 80,
        ];
        $this->model->insert($data1);

        $data2 = [
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Recap Sumatif 1',
            'assessment_date' => '2023-10-15',
            'score'           => 90,
        ];
        $this->model->insert($data2);

        $recapData = $this->model->getAssessmentsForRecap(self::$classId, self::$subjectId);
        $this->assertCount(2, $recapData);
        $this->assertEquals('Recap Formatif 1', $recapData[0]['assessment_title']);
        $this->assertEquals('Recap Sumatif 1', $recapData[1]['assessment_title']);
        $this->assertEquals(self::$studentId, $recapData[0]['student_id']);
    }

    public function testGetExportDataForErapor()
    {
        // This test requires more complex setup:
        // 1. Schedules for the class, subject, academic_year, semester
        // 2. Assessments (Sumatif) within the date range of that semester

        // Setup Schedule
        $scheduleModel = new \App\Models\ScheduleModel();
        $academicYear = '2023/2024';
        $semester = 1; // Ganjil (July-Dec 2023)

        // Clean up existing schedules for this class/subject/teacher to avoid conflicts
        $scheduleModel->where('class_id', self::$classId)
                      ->where('subject_id', self::$subjectId)
                      ->where('teacher_id', self::$teacherId)
                      ->delete();

        $scheduleModel->insert([
            'class_id'      => self::$classId,
            'subject_id'    => self::$subjectId,
            'teacher_id'    => self::$teacherId,
            'day_of_week'   => 1, // Monday
            'start_time'    => '07:00:00',
            'end_time'      => '08:30:00',
            'academic_year' => $academicYear,
            'semester'      => $semester,
        ]);

        // Create Sumatif assessments within Semester 1 2023/2024
        // Score 1: 80
        $this->model->insert([
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Sumatif Erapor 1',
            'score'           => 80.00,
            'assessment_date' => '2023-09-15', // Within Semester 1
        ]);

        // Score 2: 90
        $this->model->insert([
            'student_id'      => self::$studentId,
            'subject_id'      => self::$subjectId,
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Sumatif Erapor 2',
            'score'           => 90.00,
            'assessment_date' => '2023-11-20', // Within Semester 1
        ]);

        // Score 3 (different subject, should not be included for self::$subjectId)
        $otherSubjectModel = new SubjectModel();
        $otherSubject = $otherSubjectModel->where('id !=', self::$subjectId)->first();
        if(!$otherSubject) {
            $otherSubjectId = $otherSubjectModel->insert(['subject_name' => 'Other Subject', 'subject_code' => 'OTH-001']);
        } else {
            $otherSubjectId = $otherSubject['id'];
        }
        // Ensure schedule for this other subject
         $scheduleModel->insert([
            'class_id'      => self::$classId,
            'subject_id'    => $otherSubjectId,
            'teacher_id'    => self::$teacherId,
            'day_of_week'   => 2,
            'start_time'    => '09:00:00',
            'end_time'      => '10:30:00',
            'academic_year' => $academicYear,
            'semester'      => $semester,
        ]);

        $this->model->insert([
            'student_id'      => self::$studentId,
            'subject_id'      => $otherSubjectId, // Different subject
            'class_id'        => self::$classId,
            'teacher_id'      => self::$teacherId,
            'assessment_type' => 'SUMATIF',
            'assessment_title'=> 'Sumatif Erapor Other Subject',
            'score'           => 70.00,
            'assessment_date' => '2023-10-01',
        ]);


        $exportData = $this->model->getExportDataForErapor(self::$classId, $academicYear, $semester);

        $this->assertArrayHasKey('students', $exportData);
        $this->assertArrayHasKey('subjects', $exportData);

        $this->assertArrayHasKey(self::$studentId, $exportData['students']);
        $studentExportData = $exportData['students'][self::$studentId];

        $this->assertArrayHasKey(self::$subjectId, $studentExportData['scores']);
        // Average of 80 and 90 is 85
        $this->assertEquals(85, $studentExportData['scores'][self::$subjectId]);

        // Check that the other subject's score is also present if scheduled
        if (isset($exportData['subjects'][$otherSubjectId])) {
            $this->assertArrayHasKey($otherSubjectId, $studentExportData['scores']);
            $this->assertEquals(70, $studentExportData['scores'][$otherSubjectId]);
        }

        // Test with a semester that has no assessments
        $exportDataSem2 = $this->model->getExportDataForErapor(self::$classId, $academicYear, 2); // Semester 2 (Jan-June 2024)
        $this->assertArrayHasKey(self::$studentId, $exportDataSem2['students']);
        $studentExportDataSem2 = $exportDataSem2['students'][self::$studentId];

        // Ensure schedule for semester 2 for the main subject if we expect it to appear
         $scheduleModel->insert([
            'class_id'      => self::$classId,
            'subject_id'    => self::$subjectId,
            'teacher_id'    => self::$teacherId,
            'day_of_week'   => 1,
            'start_time'    => '07:00:00',
            'end_time'      => '08:30:00',
            'academic_year' => $academicYear,
            'semester'      => 2, // Semester 2
        ]);
        // Re-fetch export data for semester 2 after ensuring the schedule exists
        $exportDataSem2 = $this->model->getExportDataForErapor(self::$classId, $academicYear, 2);
        $studentExportDataSem2 = $exportDataSem2['students'][self::$studentId];

        if (isset($exportDataSem2['subjects'][self::$subjectId])) {
             $this->assertEquals('', $studentExportDataSem2['scores'][self::$subjectId]); // No scores, should be empty string
        } else {
            // If the subject is not scheduled for semester 2, it shouldn't be in the subjects list for that export
            $this->assertArrayNotHasKey(self::$subjectId, $exportDataSem2['subjects']);
        }

    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up any stray data if necessary, though refresh=true should handle it.
        $db = \Config\Database::connect();
        // $db->table('assessments')->emptyTable(); // Example, be careful with this
    }
}
