<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\StudentModel;
use App\Models\UserModel; // To get valid user_ids for FKs
// RoleModel might not be directly needed if UserRoleSeeder handles role creation sufficiently.

class StudentModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';
    // UserRoleSeeder now creates 'testsiswa' and 'testortu' users and necessary roles.
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';

    protected $studentModel;
    protected $userModel;
    protected $testSiswaUserId;  // For student's own account
    protected $testOrtuUserId;   // For parent's account link

    protected function setUp(): void
    {
        parent::setUp();
        $this->studentModel = new StudentModel();
        $this->userModel = new UserModel();

        $siswaUser = $this->userModel->where('username', 'testsiswa')->first();
        $this->testSiswaUserId = $siswaUser ? (int)$siswaUser['id'] : null;
        $this->assertNotNull($this->testSiswaUserId, "'testsiswa' user must be created by UserRoleSeeder and available for tests.");

        $ortuUser = $this->userModel->where('username', 'testortu')->first();
        $this->testOrtuUserId = $ortuUser ? (int)$ortuUser['id'] : null;
        $this->assertNotNull($this->testOrtuUserId, "'testortu' user must be created by UserRoleSeeder and available for tests.");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->studentModel);
        unset($this->userModel);
    }

    public function testCreateStudentWithValidData()
    {
        $data = [
            'full_name'      => 'Siswa Uji Valid Lengkap',
            'nisn'           => '0012345001', // Unique NISN
            'user_id'        => $this->testSiswaUserId,
            'parent_user_id' => $this->testOrtuUserId,
        ];
        $studentId = $this->studentModel->insert($data);

        $this->assertIsNumeric($studentId, "Insert should return new student ID. Errors: ".implode(', ', $this->studentModel->errors()));
        $this->seeInDatabase('students', ['id' => $studentId, 'nisn' => $data['nisn'], 'user_id' => $data['user_id'], 'parent_user_id' => $data['parent_user_id']]);
    }

    public function testCreateStudentWithMinimalData()
    {
        $data = [
            'full_name' => 'Siswa Uji Minimal Sekali',
            // nisn, user_id, parent_user_id are permit_empty
        ];
        $studentId = $this->studentModel->insert($data);
        $this->assertIsNumeric($studentId, "Insert with minimal data should return ID. Errors: ".implode(', ', $this->studentModel->errors()));

        $insertedStudent = $this->studentModel->find($studentId);
        $this->assertNotNull($insertedStudent);
        $this->assertEquals($data['full_name'], $insertedStudent['full_name']);
        $this->assertNull($insertedStudent['nisn']);
        $this->assertNull($insertedStudent['user_id']);
        $this->assertNull($insertedStudent['parent_user_id']);
    }

    public function testCreateStudentFailsIfFullNameMissing()
    {
        $data = [
            'nisn' => '0012345002',
            'user_id' => $this->testSiswaUserId
            // full_name is missing
        ];
        $result = $this->studentModel->insert($data);
        $errors = $this->studentModel->errors();

        $this->assertFalse($result, "Insert should fail if full_name is missing.");
        $this->assertArrayHasKey('full_name', $errors);
        $this->assertMatchesRegularExpression('/required/i', $errors['full_name']);
    }

    public function testCreateStudentFailsIfNisnTaken()
    {
        $uniqueNisn = 'NISN-UNIK-XYZ01';
        $this->studentModel->insert([
            'full_name' => 'Siswa Awal Dengan NISN',
            'nisn'      => $uniqueNisn,
        ]);

        $duplicateData = [
            'full_name' => 'Siswa Dobel NISN Test',
            'nisn'      => $uniqueNisn, // Duplicate NISN
        ];
        $result = $this->studentModel->insert($duplicateData);
        $errors = $this->studentModel->errors();

        $this->assertFalse($result, "Insert should fail if NISN is already taken.");
        $this->assertArrayHasKey('nisn', $errors);
        $this->assertEquals('This NISN is already registered.', $errors['nisn']);
    }

    public function testCreateStudentFailsIfInvalidUserId()
    {
        $invalidUserId = 99999; // Non-existent user ID
        $this->assertNull($this->userModel->find($invalidUserId), "User ID {$invalidUserId} should not exist.");

        $data = [
            'full_name' => 'Siswa Dengan User ID Salah',
            'nisn'      => '0012345003', // Unique NISN
            'user_id'   => $invalidUserId,
        ];
        $result = $this->studentModel->insert($data);
        $errors = $this->studentModel->errors();

        $this->assertFalse($result, "Insert should fail with invalid user_id.");
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertEquals('The selected User ID for student login does not exist.', $errors['user_id']);
    }

    public function testCreateStudentFailsIfInvalidParentUserId()
    {
        $invalidParentUserId = 99998; // Another non-existent user ID
        $this->assertNull($this->userModel->find($invalidParentUserId), "Parent User ID {$invalidParentUserId} should not exist.");

        $data = [
            'full_name'      => 'Siswa Dengan Parent ID Salah',
            'nisn'           => '0012345004', // Unique NISN
            'parent_user_id' => $invalidParentUserId,
        ];
        $result = $this->studentModel->insert($data);
        $errors = $this->studentModel->errors();

        $this->assertFalse($result, "Insert should fail with invalid parent_user_id.");
        $this->assertArrayHasKey('parent_user_id', $errors);
        $this->assertEquals('The selected User ID for parent login does not exist.', $errors['parent_user_id']);
    }

    public function testUpdateStudent()
    {
        $initialData = [
            'full_name' => 'Nama Siswa Awal Diupdate',
            'nisn'      => 'NISN-AWAL-UPDATE05', // Unique NISN
        ];
        $studentId = $this->studentModel->insert($initialData);
        $this->assertIsNumeric($studentId, "Initial student insert for update test failed.");

        $updatedData = [
            'full_name' => 'Nama Siswa Setelah Update',
            'nisn'      => 'NISN-BARU-UPDATE06', // New unique NISN
            'user_id'   => $this->testSiswaUserId,
        ];
        $result = $this->studentModel->update($studentId, $updatedData);

        $this->assertTrue($result, "Update should be successful. Errors: ".implode(', ', $this->studentModel->errors()));
        $this->seeInDatabase('students', array_merge(['id' => $studentId], $updatedData));
    }

    public function testDeleteStudent()
    {
        $data = [
            'full_name' => 'Siswa Akan Dihapus Sekali',
            'nisn'      => 'NISN-HAPUS-XYZ07', // Unique NISN
        ];
        $studentId = $this->studentModel->insert($data);
        $this->assertIsNumeric($studentId, "Student for deletion should be inserted.");

        $result = $this->studentModel->delete($studentId);
        $this->assertTrue($result, "Delete should be successful.");
        $this->dontSeeInDatabase('students', ['id' => $studentId]);
    }

    public function testFindByParentUserId()
    {
        $this->studentModel->insert([
            'full_name'      => 'Anak Ortu Satu A',
            'nisn'           => 'ANAKORTU001A',
            'parent_user_id' => $this->testOrtuUserId,
        ]);
        $this->studentModel->insert([
            'full_name'      => 'Anak Ortu Satu B',
            'nisn'           => 'ANAKORTU001B',
            'parent_user_id' => $this->testOrtuUserId,
        ]);

        $adminUser = $this->userModel->where('username', 'testadmin')->first(); // Use another existing user
        $this->assertNotNull($adminUser, "'testadmin' user should exist for findByParentUserId test.");
        $this->studentModel->insert([
            'full_name'      => 'Anak Ortu Lainnya',
            'nisn'           => 'ANAKORTULAIN001',
            'parent_user_id' => $adminUser['id'],
        ]);

        $studentsOfTestOrtu = $this->studentModel->findByParentUserId($this->testOrtuUserId);
        $this->assertCount(2, $studentsOfTestOrtu, "Should find 2 students for testOrtuUserId.");

        $studentNames = array_column($studentsOfTestOrtu, 'full_name');
        $this->assertContains('Anak Ortu Satu A', $studentNames);
        $this->assertContains('Anak Ortu Satu B', $studentNames);
        $this->assertNotContains('Anak Ortu Lainnya', $studentNames);
    }
}
