<?php

namespace Tests\Models;

use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;

class TeacherModelTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // protected $seeders     = ['App\Database\Seeds\RoleSeeder', 'App\Database\Seeds\UserSeederForTests', 'App\Database\Seeds\TeacherSeederForTests']; // Now handled by BaseTestCase

    protected $teacherModel;
    protected $userModel;
    protected $validUserIdForTeacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel(); // UserModel is still needed here for fetching specific users
        // $this->ensureUserSeederExists(); // This will be called by parent::setUp() if BaseTestCase calls it, or call directly
        // $this->ensureTeacherSeederExists(); // This will be called by parent::setUp() if BaseTestCase calls it, or call directly

        // Get a user that can be a teacher (e.g., from UserSeederForTests or create one)
        // Ensure seeders have run if this relies on specific seeded users
        // parent::ensureUserSeederExists(); // These are removed from BaseTestCase. Seeders run via $seeders property.
        // parent::ensureTeacherSeederExists();

        $teacherUser = $this->userModel->where('username', 'testteacheruser_for_model')->first();
        if (!$teacherUser) {
            $roleModel = new RoleModel();
            $role = $roleModel->where('role_name', 'Guru')->first() ?? $roleModel->first();
             if (!$role) {
                $roleModel->insert(['role_name' => 'TestRoleForTeacherFK']);
                $role = ['id' => $roleModel->getInsertID()];
            }
            $teacherUserId = $this->userModel->insert([
                'username' => 'testteacheruser_for_model' . uniqid(),
                'password' => 'password123',
                'role_id'  => $role['id'],
                'is_active'=> 1,
                'full_name'=> 'Teacher User For Model Test'
            ]);
            $this->validUserIdForTeacher = $teacherUserId;
        } else {
            $this->validUserIdForTeacher = $teacherUser['id'];
        }
    }

    // ensureUserSeederExists() and ensureTeacherSeederExists() are now in BaseTestCase

    private function getValidTeacherData(array $override = []): array
    {
        return array_merge([
            'full_name' => 'Test Teacher Name ' . uniqid(),
            'nip'       => uniqid('nip_'), // Must be unique
            'user_id'   => $this->validUserIdForTeacher, // FK to users table
        ], $override);
    }

    public function testCreateTeacherSuccessfully()
    {
        $data = $this->getValidTeacherData();
        $teacherId = $this->teacherModel->insert($data);

        $this->assertIsNumeric($teacherId);
        $this->seeInDatabase('teachers', ['nip' => $data['nip'], 'full_name' => $data['full_name']]);
    }

    public function testFullNameIsRequired()
    {
        $data = $this->getValidTeacherData(['full_name' => '']);
        $this->assertFalse($this->teacherModel->insert($data));
        $this->assertArrayHasKey('full_name', $this->teacherModel->errors());
    }

    public function testNipIsUnique()
    {
        $commonNip = 'nip_unique_teacher_test';
        $data1 = $this->getValidTeacherData(['nip' => $commonNip]);
        $this->teacherModel->insert($data1);

        $data2 = $this->getValidTeacherData(['nip' => $commonNip]);
        $this->assertFalse($this->teacherModel->insert($data2));
        $this->assertArrayHasKey('nip', $this->teacherModel->errors());
        $this->assertStringContainsStringIgnoringCase('This NIP is already registered.', $this->teacherModel->errors()['nip']);
    }

    public function testUserIdMustExistInUsersTableIfExists()
    {
        $data = $this->getValidTeacherData(['user_id' => 999997]); // Non-existent user_id
        $this->assertFalse($this->teacherModel->insert($data));
        $this->assertArrayHasKey('user_id', $this->teacherModel->errors());
        $this->assertStringContainsStringIgnoringCase('The selected User ID for teacher login does not exist.', $this->teacherModel->errors()['user_id']);

        // Test with null user_id (should be allowed by 'permit_empty')
        $dataValidWithNullUser = $this->getValidTeacherData(['user_id' => null]);
        $teacherId = $this->teacherModel->insert($dataValidWithNullUser);
        $this->assertIsNumeric($teacherId, "Teacher creation should succeed with null user_id. Errors: " . print_r($this->teacherModel->errors(), true));
        $this->seeInDatabase('teachers', ['id' => $teacherId, 'user_id' => null]);
    }

    public function testUpdateTeacher()
    {
        $data = $this->getValidTeacherData();
        $teacherId = $this->teacherModel->insert($data);

        $updatedData = [
            'full_name' => 'Updated Teacher Name',
            'nip' => 'nip_updated_teacher_' . uniqid(),
        ];
        $this->teacherModel->update($teacherId, $updatedData);
        $this->seeInDatabase('teachers', ['id' => $teacherId, 'full_name' => 'Updated Teacher Name']);
    }

    public function testDeleteTeacher()
    {
        $data = $this->getValidTeacherData();
        $teacherId = $this->teacherModel->insert($data);
        $this->seeInDatabase('teachers', ['id' => $teacherId]);

        $this->teacherModel->delete($teacherId);
        $this->dontSeeInDatabase('teachers', ['id' => $teacherId]);
    }
}
