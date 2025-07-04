<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\TeacherModel;
use App\Models\UserModel; // To get a valid user_id for tests
use App\Models\RoleModel; // To ensure roles are seeded for UserModel if UserRoleSeeder isn't run for all model tests

class TeacherModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';
    // UserRoleSeeder creates 'testguru' user which can be linked to a teacher
    // It also creates roles, which are prerequisites for users.
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';

    protected $teacherModel;
    protected $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->teacherModel);
        unset($this->userModel);
    }

    private function getTestGuruUserId(): ?int
    {
        $guruUser = $this->userModel->where('username', 'testguru')->first();
        return $guruUser ? (int)$guruUser['id'] : null;
    }

    public function testCreateTeacherWithValidDataAndUser()
    {
        $guruUserId = $this->getTestGuruUserId();
        $this->assertNotNull($guruUserId, "'testguru' user (ID: {$guruUserId}) should exist to link to a teacher.");

        $data = [
            'full_name' => 'Guru Test Satu',
            'nip'       => '199001012020121001',
            'user_id'   => $guruUserId,
        ];
        $teacherId = $this->teacherModel->insert($data);

        $this->assertIsNumeric($teacherId, "Insert should return new teacher ID. Errors: ".implode(', ', $this->teacherModel->errors()));
        $this->seeInDatabase('teachers', ['id' => $teacherId, 'full_name' => $data['full_name'], 'nip' => $data['nip'], 'user_id' => $data['user_id']]);
    }

    public function testCreateTeacherWithValidDataNoUser()
    {
        $data = [
            'full_name' => 'Guru Test Dua (No Account)',
            'nip'       => '199001012020121002',
            // user_id is permit_empty, so it should default to NULL in DB
        ];
        $teacherId = $this->teacherModel->insert($data);

        $this->assertIsNumeric($teacherId, "Insert should return new teacher ID. Errors: ".implode(', ', $this->teacherModel->errors()));
        $insertedTeacher = $this->teacherModel->find($teacherId);
        $this->assertNotNull($insertedTeacher);
        $this->assertEquals($data['full_name'], $insertedTeacher['full_name']);
        $this->assertEquals($data['nip'], $insertedTeacher['nip']);
        $this->assertNull($insertedTeacher['user_id']);
    }

    public function testCreateTeacherWithValidDataNoNip()
    {
        $guruUserId = $this->getTestGuruUserId();
        $this->assertNotNull($guruUserId, "'testguru' user should exist for this test.");

        $data = [
            'full_name' => 'Guru Test Tiga (No NIP)',
            'user_id'   => $guruUserId,
            // nip is permit_empty, should default to NULL
        ];
        $teacherId = $this->teacherModel->insert($data);

        $this->assertIsNumeric($teacherId, "Insert should return ID. Errors: ".implode(', ', $this->teacherModel->errors()));
        $insertedTeacher = $this->teacherModel->find($teacherId);
        $this->assertNotNull($insertedTeacher);
        $this->assertEquals($data['full_name'], $insertedTeacher['full_name']);
        $this->assertNull($insertedTeacher['nip']);
        $this->assertEquals($guruUserId, $insertedTeacher['user_id']);
    }

    public function testCreateTeacherFailsIfFullNameMissing()
    {
        $data = [
            'nip' => '123456789012345678',
            // full_name is missing, which is required
        ];
        $result = $this->teacherModel->insert($data);
        $errors = $this->teacherModel->errors();

        $this->assertFalse($result, "Insert should fail if full_name is missing.");
        $this->assertArrayHasKey('full_name', $errors);
        $this->assertMatchesRegularExpression('/required/i', $errors['full_name']);
    }

    public function testCreateTeacherFailsIfNipTaken()
    {
        // First teacher with a unique NIP
        $this->teacherModel->insert([
            'full_name' => 'Guru A With NIP',
            'nip'       => 'NIP-UNIK-001XYZ',
        ]);

        // Attempt to create another teacher with the same NIP
        $duplicateData = [
            'full_name' => 'Guru B Also With NIP',
            'nip'       => 'NIP-UNIK-001XYZ', // Duplicate NIP
        ];
        $result = $this->teacherModel->insert($duplicateData);
        $errors = $this->teacherModel->errors();

        $this->assertFalse($result, "Insert should fail if NIP is already taken.");
        $this->assertArrayHasKey('nip', $errors);
        $this->assertEquals('This NIP is already registered.', $errors['nip']);
    }

    public function testCreateTeacherFailsIfInvalidUserId()
    {
        $invalidUserId = 99999; // Assuming this user ID does not exist
        $user = $this->userModel->find($invalidUserId); // Verify it doesn't exist
        $this->assertNull($user, "User ID {$invalidUserId} should not exist for this test.");

        $data = [
            'full_name' => 'Guru Dengan User Invalid',
            'nip'       => '198001012010121003XYZ', // Unique NIP
            'user_id'   => $invalidUserId,
        ];
        $result = $this->teacherModel->insert($data);
        $errors = $this->teacherModel->errors();

        $this->assertFalse($result, "Insert should fail due to invalid user_id.");
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertEquals('The selected User ID for teacher login does not exist.', $errors['user_id']);
    }

    public function testUpdateTeacher()
    {
        $initialData = [
            'full_name' => 'Nama Awal Guru Update',
            'nip'       => 'NIPAWAL123XYZ',
        ];
        $teacherId = $this->teacherModel->insert($initialData);
        $this->assertIsNumeric($teacherId, "Initial teacher insert failed.");

        $updatedData = [
            'full_name' => 'Nama Baru Guru Update',
            'nip'       => 'NIPBARU456XYZ', // New unique NIP
        ];
        $result = $this->teacherModel->update($teacherId, $updatedData);

        $this->assertTrue($result, "Update should be successful. Errors: ".implode(', ', $this->teacherModel->errors()));

        $dbTeacher = $this->teacherModel->find($teacherId);
        $this->assertEquals($updatedData['full_name'], $dbTeacher['full_name']);
        $this->assertEquals($updatedData['nip'], $dbTeacher['nip']);
    }

    public function testDeleteTeacher()
    {
        $data = [
            'full_name' => 'Guru Akan Dihapus Test',
            'nip'       => 'NIPHAPUS789XYZ', // Unique NIP
        ];
        $teacherId = $this->teacherModel->insert($data);
        $this->assertIsNumeric($teacherId, "Teacher for deletion should be inserted.");

        $result = $this->teacherModel->delete($teacherId);
        $this->assertTrue($result, "Delete should be successful.");
        $this->dontSeeInDatabase('teachers', ['id' => $teacherId]);
    }
}
