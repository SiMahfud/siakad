<?php

namespace Tests\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel; // Needed to ensure roles exist for foreign key constraints

class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    // Migrate database before tests and refresh for each test
    protected $migrate = true;
    protected $refresh = true;
    // UserRoleSeeder creates 'Administrator Sistem' and 'Guru' roles and users.
    // This is fine for getting valid role_ids.
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';
    protected $namespace = 'App'; // Run application migrations

    protected $userModel;
    protected $roleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->userModel);
        unset($this->roleModel);
    }

    private function getValidRoleId(string $roleName = 'Guru'): ?int
    {
        // Roles are created by UserRoleSeeder. 'Guru' should exist.
        $role = $this->roleModel->where('role_name', $roleName)->first();
        if (!$role) {
            // Fallback if UserRoleSeeder didn't create 'Guru' specifically, try 'Administrator Sistem'
            $role = $this->roleModel->where('role_name', 'Administrator Sistem')->first();
        }
        return $role ? (int)$role['id'] : null;
    }

    public function testCreateUserWithValidData()
    {
        $roleId = $this->getValidRoleId();
        $this->assertNotNull($roleId, "A valid role ID ('Guru' or 'Administrator Sistem') must exist for this test.");

        $userData = [
            'username'  => 'newtestuser',
            'password'  => 'password123',
            'password_confirm' => 'password123',
            'full_name' => 'New Test User',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];

        $result = $this->userModel->insert($userData);

        $this->assertIsNumeric($result, "Insert should return the new user ID. Errors: " . implode(', ', $this->userModel->errors()));
        $this->seeInDatabase('users', ['username' => 'newtestuser', 'full_name' => 'New Test User']);

        $dbUser = $this->userModel->find($result);
        $this->assertNotNull($dbUser, "User should be found in DB after insert.");
        $this->assertNotEquals('password123', $dbUser['password']);
        $this->assertTrue(password_verify('password123', $dbUser['password']));
    }

    public function testCreateUserFailsWithInvalidUsernameTooShort()
    {
        $roleId = $this->getValidRoleId();
        $this->assertNotNull($roleId, "A valid role ID must exist for this test.");

        $userData = [
            'username'  => 'nu', // Too short
            'password'  => 'password123',
            'password_confirm' => 'password123',
            'full_name' => 'Short User',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];

        $result = $this->userModel->insert($userData);
        $errors = $this->userModel->errors();

        $this->assertFalse($result, "Insert should fail with short username.");
        $this->assertArrayHasKey('username', $errors, "Errors array should contain 'username' key.");
        // UserModel rule is min_length[3]. The message might vary.
        // $this->assertStringContainsString('min_length[3]', strtolower($errors['username']));
        $this->dontSeeInDatabase('users', ['username' => 'nu']);
    }

    public function testCreateUserFailsWithUsernameTaken()
    {
        $roleId = $this->getValidRoleId('Administrator Sistem'); // 'testadmin' is an admin
        $this->assertNotNull($roleId, "'Administrator Sistem' role ID must exist for this test.");

        // 'testadmin' is created by UserRoleSeeder
        $userData = [
            'username'  => 'testadmin', // Username already taken
            'password'  => 'password123',
            'password_confirm' => 'password123',
            'full_name' => 'Another Admin',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];

        $result = $this->userModel->insert($userData);
        $errors = $this->userModel->errors();

        $this->assertFalse($result, "Insert should fail with taken username.");
        $this->assertArrayHasKey('username', $errors, "Errors array should contain 'username' key for taken username.");
        $this->assertEquals('This username is already taken.', $errors['username']);
    }

    public function testCreateUserFailsWithPasswordMismatch()
    {
        $roleId = $this->getValidRoleId();
        $this->assertNotNull($roleId, "A valid role ID must exist for this test.");

        $userData = [
            'username'  => 'mismatchuser',
            'password'  => 'password123',
            'password_confirm' => 'password456', // Mismatch
            'full_name' => 'Mismatch Pass User',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];

        $result = $this->userModel->insert($userData);
        $errors = $this->userModel->errors();

        $this->assertFalse($result, "Insert should fail with password mismatch.");
        $this->assertArrayHasKey('password_confirm', $errors, "Errors array should contain 'password_confirm' key.");
        $this->assertEquals('Passwords do not match.', $errors['password_confirm']);
        $this->dontSeeInDatabase('users', ['username' => 'mismatchuser']);
    }

    public function testCreateUserFailsWithInvalidRoleId()
    {
        $invalidRoleId = 9999;
        $role = $this->roleModel->find($invalidRoleId);
        $this->assertNull($role, "Role ID {$invalidRoleId} should not exist for this test.");

        $userData = [
            'username'  => 'invalidroleuser',
            'password'  => 'password123',
            'password_confirm' => 'password123',
            'full_name' => 'Invalid Role User',
            'role_id'   => $invalidRoleId,
            'is_active' => 1,
        ];

        $result = $this->userModel->insert($userData);
        $errors = $this->userModel->errors();

        $this->assertFalse($result, "Insert should fail with invalid role_id.");
        $this->assertArrayHasKey('role_id', $errors, "Errors array should contain 'role_id' key for invalid role.");
        $this->assertEquals('The selected role is invalid.', $errors['role_id']);
        $this->dontSeeInDatabase('users', ['username' => 'invalidroleuser']);
    }

    public function testUpdateUserChangesFullName()
    {
        $guruUser = $this->userModel->where('username', 'testguru')->first(); // from UserRoleSeeder
        $this->assertNotNull($guruUser, "'testguru' user should exist for update test.");

        $newFullName = 'Updated Guru Name';
        $updateData = ['full_name' => $newFullName];

        $result = $this->userModel->update($guruUser['id'], $updateData);
        $this->assertTrue($result, "Update should return true. Errors: " . implode(', ', $this->userModel->errors()));

        $updatedUser = $this->userModel->find($guruUser['id']);
        $this->assertEquals($newFullName, $updatedUser['full_name']);
    }

    public function testUpdateUserPasswordIsHashed()
    {
        $guruUser = $this->userModel->where('username', 'testguru')->first();
        $this->assertNotNull($guruUser, "'testguru' user should exist for password update test.");

        $newPassword = 'newpassword123';
        // For update, UserModel validation rules are slightly different.
        // Password is not required unless changing. If password field is present in data, it's hashed.
        // The 'password_confirm' rule is only triggered if 'password_confirm' field is also in the data.
        // So, for this test, we only need to pass 'password'.
        $updateData = [
            'password' => $newPassword
            // 'password_confirm' => $newPassword // Not strictly needed for model's beforeUpdate if not in rules for update unless password_confirm is also present
        ];

        // To ensure validation passes for password if it has specific update rules (e.g. min_length if provided)
        // we might need to temporarily adjust or ensure the model's update rules for password are met.
        // The current UserModel has 'password' => 'required|min_length[8]' which applies to insert.
        // For update, it's handled by the callback if password field is present.
        // Let's ensure our new password meets min_length if that rule were to apply on update.
        // 'newpassword123' is > 8 chars.

        $result = $this->userModel->update($guruUser['id'], $updateData);
        $this->assertTrue($result, "Password update should be successful. Errors: " . implode(', ', $this->userModel->errors()));

        $updatedUser = $this->userModel->find($guruUser['id']);
        $this->assertNotEquals($newPassword, $updatedUser['password']);
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
    }

    public function testDeleteUserRemovesRecord()
    {
        $roleId = $this->getValidRoleId();
        $this->assertNotNull($roleId, "A valid role ID must exist for this test.");

        $userData = [
            'username'  => 'todeleteuser',
            'password'  => 'password123',
            'password_confirm' => 'password123',
            'full_name' => 'User To Delete',
            'role_id'   => $roleId,
            'is_active' => 1,
        ];
        $userId = $this->userModel->insert($userData);
        $this->assertIsNumeric($userId, "User 'todeleteuser' should be created for deletion test.");
        $this->seeInDatabase('users', ['id' => $userId]);

        $result = $this->userModel->delete($userId);
        $this->assertTrue($result, "Delete should return true.");
        $this->dontSeeInDatabase('users', ['id' => $userId]);
    }
}
