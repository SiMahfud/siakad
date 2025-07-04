<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\ClassModel;
use App\Models\TeacherModel; // To get a valid wali_kelas_id
use App\Models\UserModel;    // To get user for teacher

class ClassModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';
    // UserRoleSeeder now creates 'testguru' user AND a linked teacher record.
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';

    protected $classModel;
    protected $teacherModel;
    protected $userModel;
    protected $testTeacherId; // To store the ID of the teacher created by seeder

    protected function setUp(): void
    {
        parent::setUp();
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();

        // Fetch the teacher record that UserRoleSeeder should have created (linked to 'testguru')
        $guruUser = $this->userModel->where('username', 'testguru')->first();
        if ($guruUser) {
            $teacher = $this->teacherModel->where('user_id', $guruUser['id'])->first();
            $this->testTeacherId = $teacher ? (int)$teacher['id'] : null;
        } else {
             // If testguru wasn't found by seeder (should not happen if seeder ran), create a fallback teacher
            $fallbackTeacher = $this->teacherModel->where('nip', 'FALLBACK-WK-NIP')->first();
            if (!$fallbackTeacher) {
                $fallbackTeacherId = $this->teacherModel->insert([
                    'full_name' => 'Fallback Wali Kelas',
                    'nip' => 'FALLBACK-WK-NIP'
                ]);
                $this->testTeacherId = $fallbackTeacherId;
            } else {
                $this->testTeacherId = $fallbackTeacher['id'];
            }
        }
        $this->assertNotNull($this->testTeacherId, "A Test teacher (for wali kelas) must be available either from UserRoleSeeder or fallback creation.");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->classModel);
        unset($this->teacherModel);
        unset($this->userModel);
    }

    public function testCreateClassWithValidData()
    {
        $data = [
            'class_name'    => 'XI IPA 1',
            'academic_year' => '2023/2024',
            'wali_kelas_id' => $this->testTeacherId,
            'fase'          => 'F',
        ];
        $classId = $this->classModel->insert($data);

        $this->assertIsNumeric($classId, "Insert should return new class ID. Errors: ".implode(', ', $this->classModel->errors()));
        $this->seeInDatabase('classes', ['id' => $classId, 'class_name' => $data['class_name'], 'wali_kelas_id' => $this->testTeacherId]);
    }

    public function testCreateClassWithMinimalData()
    {
        $data = [
            'class_name'    => 'X IPS 2',
            'academic_year' => '2023/2024',
            // wali_kelas_id and fase are permit_empty in model, will be NULL in DB
        ];
        $classId = $this->classModel->insert($data);
        $this->assertIsNumeric($classId, "Insert should return ID for minimal data. Errors: ".implode(', ', $this->classModel->errors()));

        $insertedClass = $this->classModel->find($classId);
        $this->assertNotNull($insertedClass);
        $this->assertEquals($data['class_name'], $insertedClass['class_name']);
        $this->assertEquals($data['academic_year'], $insertedClass['academic_year']);
        $this->assertNull($insertedClass['wali_kelas_id']);
        $this->assertNull($insertedClass['fase']);
    }

    public function testCreateClassFailsIfClassNameMissing()
    {
        $data = [
            'academic_year' => '2023/2024',
            'wali_kelas_id' => $this->testTeacherId,
            // class_name is missing
        ];
        $result = $this->classModel->insert($data);
        $errors = $this->classModel->errors();

        $this->assertFalse($result, "Insert should fail if class_name is missing.");
        $this->assertArrayHasKey('class_name', $errors);
        $this->assertMatchesRegularExpression('/required/i', $errors['class_name']);
    }

    public function testCreateClassFailsIfAcademicYearMissing()
    {
        $data = [
            'class_name'    => 'XII Bahasa',
            'wali_kelas_id' => $this->testTeacherId,
            // academic_year is missing
        ];
        $result = $this->classModel->insert($data);
        $errors = $this->classModel->errors();

        $this->assertFalse($result, "Insert should fail if academic_year is missing.");
        $this->assertArrayHasKey('academic_year', $errors);
        $this->assertMatchesRegularExpression('/required/i', $errors['academic_year']);
    }

    public function testCreateClassFailsIfWaliKelasIdInvalidAtDbLevel()
    {
        $invalidTeacherId = 99999; // Assuming this ID does not exist
        $teacher = $this->teacherModel->find($invalidTeacherId); // Verify non-existence
        $this->assertNull($teacher, "Teacher ID {$invalidTeacherId} should not exist for this test.");

        $data = [
            'class_name'    => 'XI IPA Super',
            'academic_year' => '2024/2025',
            'wali_kelas_id' => $invalidTeacherId,
        ];

        // Expect a DatabaseException due to FOREIGN KEY constraint violation
        // The model validation for wali_kelas_id is 'permit_empty|integer', so it won't catch this.
        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $this->classModel->insert($data);

        // Note: If tests are run with FK checks off for SQLite, this might pass where it shouldn't.
        // Ensure FKs are on for the test DB connection. Default CI test setup for SQLite usually enables them.
    }

    public function testUpdateClass()
    {
        $initialData = [
            'class_name'    => 'Kelas Lama Update',
            'academic_year' => '2022/2023',
            'wali_kelas_id' => $this->testTeacherId,
        ];
        $classId = $this->classModel->insert($initialData);
        $this->assertIsNumeric($classId, "Initial class insert for update test failed.");

        $updatedData = [
            'class_name'    => 'Kelas Baru Keren Update',
            'academic_year' => '2023/2024',
            'fase'          => 'E',
            // wali_kelas_id can also be updated or set to null
        ];
        $result = $this->classModel->update($classId, $updatedData);

        $this->assertTrue($result, "Update should be successful. Errors: ".implode(', ', $this->classModel->errors()));

        $dbClass = $this->classModel->find($classId);
        $this->assertEquals($updatedData['class_name'], $dbClass['class_name']);
        $this->assertEquals($updatedData['academic_year'], $dbClass['academic_year']);
        $this->assertEquals($updatedData['fase'], $dbClass['fase']);
    }

    public function testDeleteClass()
    {
        $data = [
            'class_name'    => 'Kelas Akan Dihapus Test',
            'academic_year' => '2021/2022',
            'wali_kelas_id' => $this->testTeacherId,
        ];
        $classId = $this->classModel->insert($data);
        $this->assertIsNumeric($classId, "Class for deletion should be inserted.");

        $result = $this->classModel->delete($classId);
        $this->assertTrue($result, "Delete should be successful.");
        $this->dontSeeInDatabase('classes', ['id' => $classId]);
    }
}
