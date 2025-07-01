<?php

namespace Tests\Models;

use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;

class StudentModelTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // Seeders are now handled by BaseTestCase
    // protected $seeders = ['App\Database\Seeds\RoleSeeder', 'App\Database\Seeds\UserSeederForTests'];

    protected $studentModel;
    protected $userModel; // To create dummy users for FK checks
    protected $validUserId; // For user_id and parent_user_id

    protected function setUp(): void
    {
        parent::setUp();
        $this->studentModel = new StudentModel();
        $this->userModel = new UserModel(); // Needed to create users for FK

        // Create a dummy user to satisfy FK constraints if UserSeederForTests is not used or is basic
        // This ensures 'users.id' exists for 'user_id' and 'parent_user_id' validation.
        $roleModel = new RoleModel();
        $role = $roleModel->where('role_name', 'Siswa')->first() ?? $roleModel->first(); // Get any role
        if (!$role) { // Still no role, create one
             $roleModel->insert(['role_name' => 'TestRoleForStudentFK']);
             $role = ['id' => $roleModel->getInsertID()];
        }

        $tempUser = $this->userModel->insert([
            'username' => 'fk_user_' . uniqid(),
            'password' => 'password123',
            'role_id'  => $role['id'],
            'is_active' => 1
        ]);
        $this->validUserId = $tempUser;

        // If UserSeederForTests is more elaborate, this manual creation might be simplified.
        // For now, this ensures at least one user record exists.
        // The ensureUserSeederExists() call is now in BaseTestCase and called via parent::setUp()
        // or explicitly if BaseTestCase::setUp() doesn't handle it.
        // For now, let's assume we might need to call it if BaseTestCase doesn't automatically.
        // parent::ensureUserSeederExists(); // This was from BaseTestCase.
    }

    // ensureUserSeederExists() is now in BaseTestCase

    private function getValidStudentData(array $override = []): array
    {
        // $this->ensureUserSeederExists(); // This should have been handled by setUp calling parent::setUp which now might call it.
                                        // Or the $seeders property ensures this.
        // Fetch users created by seeder to ensure FK constraints pass
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $parentUser = $this->userModel->where('username', 'testparentuser')->first();

        return array_merge([
            'full_name'      => 'Test Student Name ' . uniqid(),
            'nisn'           => uniqid('nisn_'), // Must be unique
            'user_id'        => $studentUser ? $studentUser['id'] : $this->validUserId, // FK to users table
            'parent_user_id' => $parentUser ? $parentUser['id'] : $this->validUserId, // FK to users table
        ], $override);
    }

    public function testCreateStudentSuccessfully()
    {
        $data = $this->getValidStudentData();
        $studentId = $this->studentModel->insert($data);

        $this->assertIsNumeric($studentId);
        $this->seeInDatabase('students', ['nisn' => $data['nisn'], 'full_name' => $data['full_name']]);
    }

    public function testFullNameIsRequired()
    {
        $data = $this->getValidStudentData(['full_name' => '']);
        $this->assertFalse($this->studentModel->insert($data));
        $this->assertArrayHasKey('full_name', $this->studentModel->errors());
    }

    public function testNisnIsUnique()
    {
        $commonNisn = 'nisn_unique_test';
        $data1 = $this->getValidStudentData(['nisn' => $commonNisn]);
        $this->studentModel->insert($data1);

        $data2 = $this->getValidStudentData(['nisn' => $commonNisn]);
        $this->assertFalse($this->studentModel->insert($data2));
        $this->assertArrayHasKey('nisn', $this->studentModel->errors());
        $this->assertStringContainsStringIgnoringCase('This NISN is already registered.', $this->studentModel->errors()['nisn']);
    }

    public function testUserIdMustExistInUsersTableIfExists()
    {
        // Test with a non-existent user_id
        $data = $this->getValidStudentData(['user_id' => 999998]); // Non-existent user_id
        $this->assertFalse($this->studentModel->insert($data));
        $this->assertArrayHasKey('user_id', $this->studentModel->errors());
        $this->assertStringContainsStringIgnoringCase('The selected User ID for student login does not exist.', $this->studentModel->errors()['user_id']);

        // Test with null user_id (should be allowed by 'permit_empty')
        $dataValidWithNullUser = $this->getValidStudentData(['user_id' => null]);
        $studentId = $this->studentModel->insert($dataValidWithNullUser);
        $this->assertIsNumeric($studentId, "Student creation should succeed with null user_id. Errors: " . print_r($this->studentModel->errors(), true));
        $this->seeInDatabase('students', ['id' => $studentId, 'user_id' => null]);
    }

    public function testParentUserIdMustExistInUsersTableIfExists()
    {
        // Test with a non-existent parent_user_id
        $data = $this->getValidStudentData(['parent_user_id' => 999999]); // Non-existent parent_user_id
        $this->assertFalse($this->studentModel->insert($data));
        $this->assertArrayHasKey('parent_user_id', $this->studentModel->errors());
        $this->assertStringContainsStringIgnoringCase('The selected User ID for parent login does not exist.', $this->studentModel->errors()['parent_user_id']);

        // Test with null parent_user_id (should be allowed by 'permit_empty')
        $dataValidWithNullParent = $this->getValidStudentData(['parent_user_id' => null]);
        $studentId = $this->studentModel->insert($dataValidWithNullParent);
        $this->assertIsNumeric($studentId, "Student creation should succeed with null parent_user_id. Errors: " . print_r($this->studentModel->errors(), true));
        $this->seeInDatabase('students', ['id' => $studentId, 'parent_user_id' => null]);
    }


    public function testUpdateStudent()
    {
        $data = $this->getValidStudentData();
        $studentId = $this->studentModel->insert($data);

        $updatedData = [
            'full_name' => 'Updated Student Name',
            'nisn' => 'nisn_updated_' . uniqid(),
        ];
        $this->studentModel->update($studentId, $updatedData);
        $this->seeInDatabase('students', ['id' => $studentId, 'full_name' => 'Updated Student Name']);
    }

    public function testDeleteStudent()
    {
        $data = $this->getValidStudentData();
        $studentId = $this->studentModel->insert($data);
        $this->seeInDatabase('students', ['id' => $studentId]);

        $this->studentModel->delete($studentId);
        $this->dontSeeInDatabase('students', ['id' => $studentId]);
    }
}
