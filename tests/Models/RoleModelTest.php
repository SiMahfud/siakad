<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\RoleModel;
use App\Models\UserModel; // To test FK constraint with users table

class RoleModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $namespace = 'App';
    // Not using UserRoleSeeder here to have a cleaner slate specifically for role tests,
    // and to avoid potential conflicts if UserRoleSeeder creates roles that this test also tries to create.
    // protected $seed = 'UserRoleSeeder';
    // protected $basePath = APPPATH . 'Database';


    protected $roleModel;
    protected $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        // It's important that migrations for roles and users tables are run.
        // $this->db->table('roles')->truncate(); // Optional: ensure clean roles table if not using $refresh or specific seeder
        // $this->db->table('users')->truncate(); // Optional: ensure clean users table
        $this->roleModel = new RoleModel();
        $this->userModel = new UserModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->roleModel);
        unset($this->userModel);
    }

    public function testCreateRoleWithValidData()
    {
        $data = ['role_name' => 'Test Role One'];
        $roleId = $this->roleModel->insert($data);

        $this->assertIsNumeric($roleId, "Insert should return the new role ID. Errors: ".implode(', ', $this->roleModel->errors()));
        $this->seeInDatabase('roles', ['role_name' => 'Test Role One', 'id' => $roleId]);
    }

    public function testCreateRoleFailsIfNameMissing()
    {
        $data = ['role_name' => ''];
        $result = $this->roleModel->insert($data);
        $errors = $this->roleModel->errors();

        $this->assertFalse($result, "Insert should fail if role_name is missing/empty.");
        $this->assertArrayHasKey('role_name', $errors, "Validation errors should contain 'role_name'.");
        // Check for 'required' part of the message.
        $this->assertMatchesRegularExpression('/required/i', $errors['role_name']);
    }

    public function testCreateRoleFailsIfNameTooShort()
    {
        $data = ['role_name' => 'AB']; // Too short (min_length[3])
        $result = $this->roleModel->insert($data);
        $errors = $this->roleModel->errors();

        $this->assertFalse($result, "Insert should fail if role_name is too short.");
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertStringContainsStringIgnoringCase('at least 3 characters', $errors['role_name']);
    }


    public function testCreateRoleFailsIfNameTaken()
    {
        $initialData = ['role_name' => 'Unique Role Name'];
        $firstRoleId = $this->roleModel->insert($initialData);
        $this->assertIsNumeric($firstRoleId, "First role should be inserted successfully.");

        $duplicateData = ['role_name' => 'Unique Role Name'];
        $result = $this->roleModel->insert($duplicateData);
        $errors = $this->roleModel->errors();

        $this->assertFalse($result, "Insert should fail if role_name is already taken.");
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertEquals('This role name already exists.', $errors['role_name']);
    }

    public function testUpdateRoleName()
    {
        $data = ['role_name' => 'Original Role Name'];
        $roleId = $this->roleModel->insert($data);
        $this->assertIsNumeric($roleId);

        $updatedData = ['role_name' => 'Updated Role Name'];
        $result = $this->roleModel->update($roleId, $updatedData);

        $this->assertTrue($result, "Update should return true. Errors: ".implode(', ', $this->roleModel->errors()));
        $this->seeInDatabase('roles', ['id' => $roleId, 'role_name' => 'Updated Role Name']);
        $this->dontSeeInDatabase('roles', ['id' => $roleId, 'role_name' => 'Original Role Name']);
    }

    public function testUpdateRoleNameToExistingNameFails()
    {
        $this->roleModel->insert(['role_name' => 'Existing Role A']);
        $roleB_id = $this->roleModel->insert(['role_name' => 'Role B To Update']);
        $this->assertIsNumeric($roleB_id);

        $updatedData = ['role_name' => 'Existing Role A'];
        $result = $this->roleModel->update($roleB_id, $updatedData);
        $errors = $this->roleModel->errors();

        $this->assertFalse($result, "Update should fail if new role_name is already taken by another role.");
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertEquals('This role name already exists.', $errors['role_name']);
        $this->seeInDatabase('roles', ['id' => $roleB_id, 'role_name' => 'Role B To Update']);
    }

    public function testDeleteRoleNotUsedByUser()
    {
        $data = ['role_name' => 'Role To Delete'];
        $roleId = $this->roleModel->insert($data);
        $this->assertIsNumeric($roleId);

        $result = $this->roleModel->delete($roleId);
        $this->assertTrue($result, "Delete should return true for an unused role.");
        $this->dontSeeInDatabase('roles', ['id' => $roleId]);
    }

    public function testDeleteRoleUsedByUserSetsUserRoleIdToNull()
    {
        // 1. Create a new role
        $roleData = ['role_name' => 'Temporary Role For User'];
        $roleId = $this->roleModel->insert($roleData);
        $this->assertIsNumeric($roleId, "Temporary role should be created.");
        $this->seeInDatabase('roles', ['id' => $roleId]);

        // 2. Create a user assigned to this role
        // Make sure this user data is valid according to UserModel rules
        $userData = [
            'username' => 'userwithtemprole',
            'password' => 'password123',
            'password_confirm' => 'password123', // Required by UserModel validation
            'full_name' => 'User With Temporary Role',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];
        $userId = $this->userModel->insert($userData);
        $this->assertIsNumeric($userId, "User for role deletion test should be created. Errors: " . implode(', ', $this->userModel->errors()));
        $this->seeInDatabase('users', ['id' => $userId, 'role_id' => $roleId]);

        // 3. Delete the role
        $deleteResult = $this->roleModel->delete($roleId);
        $this->assertTrue($deleteResult, "Deleting the role should be successful.");
        $this->dontSeeInDatabase('roles', ['id' => $roleId]);

        // 4. Verify user's role_id is now NULL
        $updatedUser = $this->userModel->find($userId);
        $this->assertNotNull($updatedUser, "User should still exist after role deletion.");
        $this->assertNull($updatedUser['role_id'], "User's role_id should be NULL after the assigned role is deleted.");
    }
}
