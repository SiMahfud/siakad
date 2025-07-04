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
use App\Models\AssessmentModel;
use App\Models\TeacherClassSubjectAssignmentModel;
use App\Database\Seeds\UserSeeder as AppUserSeeder; // For user details
use App\Database\Seeds\TeacherSeeder as AppTeacherSeeder;
use App\Database\Seeds\ClassSeeder as AppClassSeeder;
use App\Database\Seeds\SubjectSeeder as AppSubjectSeeder;
use App\Database\Seeds\StudentSeeder as AppStudentSeeder;
use App\Database\Seeds\ScheduleSeeder as AppScheduleSeeder;


class AssessmentControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait; // Enables calling controller actions via HTTP verbs

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';
    protected $seed        = 'App\Database\Seeds\TestSeeder';

    protected $admin, $guru, $guruUser, $adminUser, $teacherData1;
    protected $classData1, $subjectData1, $studentData1, $assessmentData1;
    protected $teacherClassSubjectAssignmentModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacherClassSubjectAssignmentModel = new TeacherClassSubjectAssignmentModel();

        // Assuming TestSeeder runs all necessary seeders like UserSeeder, TeacherSeeder, etc.
        // Get seeded users
        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'admin')->first();
        $this->guruUser = $userModel->where('username', 'guru1')->first();

        // Get teacher data related to guruUser
        $teacherModel = new TeacherModel();
        if ($this->guruUser) {
            $this->teacherData1 = $teacherModel->where('user_id', $this->guruUser['id'])->first();
        } else {
            $this->fail("User 'guru1' not found from seeder. Check UserSeeder.");
        }

        // Fallback creation for teacher if not seeded correctly
        if (!$this->teacherData1 && $this->guruUser) {
            $teacherId = $teacherModel->skipValidation(true)->insert([
                'user_id' => $this->guruUser['id'],
                'nip' => 'G001TEST'.rand(100,999), // Ensure NIP is unique if seeder runs multiple times in a single test session with no refresh
                'full_name' => $this->guruUser['full_name'],
                'gender' => 'L',
                'phone' => '08111'.rand(100,999)
            ]);
            if ($teacherId) {
                $this->teacherData1 = $teacherModel->find($teacherId);
            } else {
                 $this->fail("Fallback teacher creation failed in setUp. Errors: " . json_encode($teacherModel->errors()));
            }
        }
        $this->assertNotNull($this->teacherData1, "teacherData1 is null even after fallback.");
        $this->assertArrayHasKey('id', $this->teacherData1, "teacherData1['id'] is not set after fallback.");


        // Get class, subject, student data
        $classModel = new ClassModel();
        $this->classData1 = $classModel->first();
        if (!$this->classData1) {
            $classId = $classModel->skipValidation(true)->insert([
                'class_name' => 'Test X-Z',
                'wali_kelas_id' => $this->teacherData1['id'],
                'academic_year' => '2023/2024',
                'fase' => 'E'
            ]);
            $this->classData1 = $classModel->find($classId);
        }
        $this->assertNotNull($this->classData1, "classData1 is null.");


        $subjectModel = new SubjectModel();
        $this->subjectData1 = $subjectModel->where('subject_code', 'MTK-W')->first();
         if (!$this->subjectData1) {
            $subjectId = $subjectModel->skipValidation(true)->insert(['subject_name' => 'Test Matematika', 'subject_code' => 'TST-MTK'.rand(1,100)]);
            $this->subjectData1 = $subjectModel->find($subjectId);
        }
        $this->assertNotNull($this->subjectData1, "subjectData1 is null.");

        $studentModel = new StudentModel();
        $this->studentData1 = $studentModel->first();
        if (!$this->studentData1) {
            $studentUserModel = new UserModel();
            $sUser = $studentUserModel->where('username', 'siswa1')->first();
            if (!$sUser) {
                $sUserId = $studentUserModel->skipValidation(true)->insert(['username' => 'testsisfal'.rand(1,100), 'password'=>'p', 'password_confirm'=>'p', 'full_name'=>'Test Siswa Fallback', 'role_id'=>4, 'is_active'=>1]);
                $sUser = $studentUserModel->find($sUserId);
            }
            $this->assertNotNull($sUser, "Fallback student user (sUser) is null.");

            $studentId = $studentModel->skipValidation(true)->insert([
                'user_id' => $sUser['id'],
                'nisn' => '000FALL'.rand(1000,9999),
                'nis' => '100FALL'.rand(100,999),
                'full_name' => 'Fallback Student',
                'gender' => 'L', 'pob'=>'fb', 'dob'=>'2006-01-01', 'join_date'=>date('Y-m-d')
            ]);
            $this->studentData1 = $studentModel->find($studentId);
        }
        $this->assertNotNull($this->studentData1, "studentData1 is null.");

        // Ensure student is in class
        $csTable = $this->db->table('class_student');
        $isStudentInClass = $csTable->where('class_id', $this->classData1['id'])
                                   ->where('student_id', $this->studentData1['id'])
                                   ->countAllResults() > 0;
        if (!$isStudentInClass) {
            $csTable->insert(['class_id' => $this->classData1['id'], 'student_id' => $this->studentData1['id']]);
        }

        // Ensure teacher is assigned to the class and subject
        if ($this->teacherData1 && $this->classData1 && $this->subjectData1) {
            $assignment = $this->teacherClassSubjectAssignmentModel
                ->where('teacher_id', $this->teacherData1['id'])
                ->where('class_id', $this->classData1['id'])
                ->where('subject_id', $this->subjectData1['id'])
                ->first();
            if (!$assignment) {
                $this->teacherClassSubjectAssignmentModel->skipValidation(true)->insert([
                    'teacher_id' => $this->teacherData1['id'],
                    'class_id'   => $this->classData1['id'],
                    'subject_id' => $this->subjectData1['id'],
                ]);
            }
        } else {
             $this->fail("Prerequisite data (teacher, class, or subject) for assignment is missing in setUp: "
                . "Teacher: " . ($this->teacherData1 ? $this->teacherData1['id'] : 'null')
                . ", Class: " . ($this->classData1 ? $this->classData1['id'] : 'null')
                . ", Subject: " . ($this->subjectData1 ? $this->subjectData1['id'] : 'null'));
        }

        // Create a sample assessment for edit/delete tests
        $assessmentModel = new AssessmentModel();
        // SOLUSI 1: Nonaktifkan validasi untuk insert ini
        $this->assessmentData1 = $assessmentModel->skipValidation(true)->insert([
            'student_id' => $this->studentData1['id'],
            'subject_id' => $this->subjectData1['id'],
            'class_id' => $this->classData1['id'],
            'teacher_id' => $this->teacherData1['id'],
            'assessment_type' => 'FORMATIF',
            'assessment_title' => 'Initial Formative Assessment',
            'assessment_date' => date('Y-m-d'),
            'description' => 'Good start'
        ]);
         if (!$this->assessmentData1) {
            // If insert still fails even with validation skipped, there's a deeper DB or data issue.
            $this->fail("Failed to create sample assessment data in setUp even with validation skipped. DB Error: " . json_encode($assessmentModel->db()->error()));
        }
    }

    public function testIndexNoLogin()
    {
        $result = $this->call('get', 'guru/assessments');
        // Default Auth filter redirects to login page (status 302)
        $result->assertStatus(302);

        // Ensure Auth config is loaded or use default
        $authConfig = config('Auth');
        $redirectUrl = $authConfig ? $authConfig->loginRedirect() : '/login';
        if (!$authConfig) {
            log_message('debug', 'Auth config was null in testIndexNoLogin. Defaulting redirect check to /login.');
        }
        $result->assertRedirectTo($redirectUrl);
    }

    public function testIndexAsGuru()
    {
        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->get('guru/assessments');
        $result->assertStatus(200);
        $result->assertSee('Select Class and Subject for Assessment');
    }

    public function testIndexAsAdmin()
    {
        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->adminUser['id'],
                            'role_id'      => $this->adminUser['role_id']
                        ])->get('guru/assessments');
        $result->assertStatus(200);
        $result->assertSee('Select Class and Subject for Assessment');
    }

    public function testAjaxGetSubjectsForClassAsGuru()
    {
        $this->assertNotNull($this->classData1, "ClassData1 is null in testAjaxGetSubjectsForClassAsGuru");

        $response = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])
                        ->withHeaders([
                            'X-Requested-With' => 'XMLHttpRequest'
                        ])
                        ->get("guru/assessments/ajax/get-subjects-for-class/{$this->classData1['id']}");

        $response->assertStatus(200);
        $response->assertJSON();
        $subjects = json_decode($response->getJSON());

        $found = false;
        foreach($subjects as $subject) {
            if ($subject->id == $this->subjectData1['id']) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Subject assigned to guru1 not found in AJAX response.");
    }

    public function testShowInputFormNoParams()
    {
        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->get('guru/assessments/input');
        $result->assertRedirectTo('guru/assessments');
        $result->assertSessionHas('error', 'Please select both class and subject.');
    }

    public function testShowInputFormWithValidParams()
    {
        $this->assertNotNull($this->classData1, "classData1 is null");
        $this->assertNotNull($this->subjectData1, "subjectData1 is null");

        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->get("guru/assessments/input?class_id={$this->classData1['id']}&subject_id={$this->subjectData1['id']}");
        $result->assertStatus(200);
        $result->assertSee('Input Assessment Scores');
        $result->assertSee($this->studentData1['full_name']);
    }

    public function testSaveAssessmentsValid()
    {
        $this->assertNotNull($this->classData1, "classData1 is null for save test");
        $this->assertNotNull($this->subjectData1, "subjectData1 is null for save test");
        $this->assertNotNull($this->studentData1, "studentData1 is null for save test");
        $this->assertNotNull($this->teacherData1, "teacherData1 is null for save test");

        $postData = [
            'class_id'   => $this->classData1['id'],
            'subject_id' => $this->subjectData1['id'],
            'assessments' => [
                $this->studentData1['id'] => [
                    [
                        'assessment_type'  => 'SUMATIF',
                        'assessment_title' => 'UTS Semester 1',
                        'assessment_date'  => date('Y-m-d'),
                        'score'            => 95,
                        'description'      => '',
                    ],
                    [
                        'assessment_type'  => 'FORMATIF',
                        'assessment_title' => 'Kuis Harian Bab 1',
                        'assessment_date'  => date('Y-m-d', strtotime('-1 day')),
                        'score'            => null,
                        'description'      => 'Sangat aktif dalam diskusi.',
                    ]
                ]
            ]
        ];

        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->post('guru/assessments/save', $postData);

        $result->assertRedirectTo("guru/assessments/input?class_id={$this->classData1['id']}&subject_id={$this->subjectData1['id']}");
        $result->assertSessionHas('success', 'Assessments saved successfully.');

        $this->seeInDatabase('assessments', [
            'student_id' => $this->studentData1['id'],
            'assessment_title' => 'UTS Semester 1',
            'score' => 95
        ]);
        $this->seeInDatabase('assessments', [
            'student_id' => $this->studentData1['id'],
            'assessment_title' => 'Kuis Harian Bab 1',
            'description' => 'Sangat aktif dalam diskusi.'
        ]);
    }

    public function testSaveAssessmentsInvalidData()
    {
        $postData = [
            'class_id'   => $this->classData1['id'],
            'subject_id' => $this->subjectData1['id'],
            'assessments' => [
                $this->studentData1['id'] => [
                    [
                        'assessment_type'  => 'SUMATIF',
                        'assessment_title' => 'Test Invalid Score',
                        'assessment_date'  => date('Y-m-d'),
                        'score'            => 105,
                        'description'      => '',
                    ]
                ]
            ]
        ];

        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->post('guru/assessments/save', $postData);

        $result->assertRedirect();
        $result->assertSessionHas('error', 'Please correct the errors in the form.');
        $result->assertSessionHas('validation_errors');

        $validationErrors = session('validation_errors');
        $this->assertArrayHasKey($this->studentData1['id'], $validationErrors);
        $this->assertArrayHasKey(0, $validationErrors[$this->studentData1['id']]);
        $this->assertArrayHasKey('score', $validationErrors[$this->studentData1['id']][0]);
        $this->assertEquals('Score cannot exceed 100.', $validationErrors[$this->studentData1['id']][0]['score']);
    }

    public function testEditAssessmentAsOwner()
    {
        $this->assertNotNull($this->assessmentData1, "AssessmentData1 (ID) is null for edit test");
        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->get("guru/assessments/edit/{$this->assessmentData1}");
        $result->assertStatus(200);
        $result->assertSee('Edit Assessment');
        $result->assertSee('Initial Formative Assessment');
    }

    public function testUpdateAssessmentAsOwnerValid()
    {
        $this->assertNotNull($this->assessmentData1, "AssessmentData1 (ID) is null for update test");
        $assessment = model(AssessmentModel::class)->find($this->assessmentData1);

        $updateData = [
            'assessment_type'  => 'FORMATIF',
            'assessment_title' => 'Updated Formative Assessment Title',
            'assessment_date'  => $assessment['assessment_date'],
            'score'            => null,
            'description'      => 'Updated description here.',
        ];

        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->post("guru/assessments/update/{$this->assessmentData1}", $updateData);

        $result->assertRedirectTo("guru/assessments/input?class_id={$assessment['class_id']}&subject_id={$assessment['subject_id']}");
        $result->assertSessionHas('success', 'Assessment updated successfully.');
        $this->seeInDatabase('assessments', [
            'id' => $this->assessmentData1,
            'assessment_title' => 'Updated Formative Assessment Title',
            'description' => 'Updated description here.'
        ]);
    }

    public function testDeleteAssessmentAsOwner()
    {
        $this->assertNotNull($this->assessmentData1, "AssessmentData1 (ID) is null for delete test");
        $assessment = model(AssessmentModel::class)->find($this->assessmentData1);

        $result = $this->withSession([
                            'is_logged_in' => true,
                            'user_id'      => $this->guruUser['id'],
                            'role_id'      => $this->guruUser['role_id']
                        ])->get("guru/assessments/delete/{$this->assessmentData1}");

        $result->assertRedirectTo("guru/assessments/input?class_id={$assessment['class_id']}&subject_id={$assessment['subject_id']}");
        $result->assertSessionHas('success', 'Assessment deleted successfully.');
        $this->dontSeeInDatabase('assessments', ['id' => $this->assessmentData1]);
    }

}
