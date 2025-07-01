<?php

namespace Tests\Models;

use App\Models\UserModel;
use App\Models\RoleModel; // To get a valid role_id
use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;

class UserModelTest extends BaseTestCase // Extend BaseTestCase
{
    // DatabaseTestTrait is used in BaseTestCase
    // protected $namespace   = 'App'; // Inherited from BaseTestCase
    // protected $refresh     = true;  // Inherited from BaseTestCase
    // protected $seeders     = ['App\Database\Seeds\RoleSeeder']; // Common seeders handled by BaseTestCase

    protected $userModel;
    protected $roleModel;
    protected $validRoleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();

        // Get a valid role_id from the seeded roles for creating users
        $role = $this->roleModel->where('role_name', 'Siswa')->first(); // Assuming 'Siswa' role exists
        if ($role) {
            $this->validRoleId = $role['id'];
        } else {
            // Fallback if seeder didn't run or 'Siswa' role is missing
            // This might indicate an issue with test setup if it happens
            $this->roleModel->insert(['role_name' => 'Test Role For User']);
            $role = $this->roleModel->getInsertID();
            $this->validRoleId = $role;
        }
    }

    private function getValidUserData(array $override = []): array
    {
        return array_merge([
            'username'  => 'testuser' . uniqid(),
            'full_name' => 'Test User Full Name',
            'password'  => 'password123',
            'role_id'   => $this->validRoleId,
            'is_active' => 1,
        ], $override);
    }

    public function testCreateUserSuccessfully()
    {
        $data = $this->getValidUserData();
        $userId = $this->userModel->insert($data);

        $this->assertIsNumeric($userId);
        $this->seeInDatabase('users', ['username' => $data['username']]);

        $createdUser = $this->userModel->find($userId);
        $this->assertTrue(password_verify('password123', $createdUser['password']));
    }

    public function testUsernameIsRequired()
    {
        $data = $this->getValidUserData(['username' => '']);
        $this->assertFalse($this->userModel->insert($data));
        $this->assertArrayHasKey('username', $this->userModel->errors());
    }

    public function testUsernameIsUnique()
    {
        $commonUsername = 'uniqueuser';
        $data1 = $this->getValidUserData(['username' => $commonUsername]);
        $this->userModel->insert($data1); // First user

        $data2 = $this->getValidUserData(['username' => $commonUsername]);
        $this->assertFalse($this->userModel->insert($data2)); // Attempt to insert same username
        $this->assertArrayHasKey('username', $this->userModel->errors());
        $this->assertStringContainsStringIgnoringCase('is already taken', $this->userModel->errors()['username']);
    }

    public function testPasswordIsRequiredOnCreate()
    {
        $data = $this->getValidUserData();
        unset($data['password']); // Remove password
        $this->assertFalse($this->userModel->insert($data));
        $this->assertArrayHasKey('password', $this->userModel->errors());
    }

    public function testPasswordMinLength()
    {
        $data = $this->getValidUserData(['password' => 'short']);
        $this->assertFalse($this->userModel->insert($data));
        $this->assertArrayHasKey('password', $this->userModel->errors());
        $this->assertStringContainsStringIgnoringCase('at least 8 characters', $this->userModel->errors()['password']);
    }

    public function testRoleIdIsRequiredAndValid()
    {
        // Role ID required
        $data = $this->getValidUserData(['role_id' => '']);
        $this->assertFalse($this->userModel->insert($data));
        $this->assertArrayHasKey('role_id', $this->userModel->errors());

        // Role ID must exist in roles table
        $data = $this->getValidUserData(['role_id' => 99999]); // Non-existent role_id
        $this->assertFalse($this->userModel->insert($data));
        $this->assertArrayHasKey('role_id', $this->userModel->errors());
        $this->assertStringContainsStringIgnoringCase('selected role is invalid', $this->userModel->errors()['role_id']);
    }

    public function testPasswordIsHashed()
    {
        $plainPassword = 'passwordToHash123';
        $data = $this->getValidUserData(['password' => $plainPassword]);
        $userId = $this->userModel->insert($data);

        $user = $this->userModel->find($userId);
        $this->assertNotEquals($plainPassword, $user['password']); // Ensure it's not plain text
        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }

    public function testVerifyPassword()
    {
        $plainPassword = 'mySecurePassword';
        $data = $this->getValidUserData(['password' => $plainPassword]);
        $userId = $this->userModel->insert($data);

        $user = $this->userModel->find($userId);

        $this->assertTrue($this->userModel->verifyPassword($plainPassword, $user['password']));
        $this->assertFalse($this->userModel->verifyPassword('wrongPassword', $user['password']));
    }

    public function testUpdateUser()
    {
        $data = $this->getValidUserData();
        $userId = $this->userModel->insert($data);

        $updatedData = [
            'full_name' => 'Updated Test User Name',
            'is_active' => 0,
        ];
        $this->userModel->update($userId, $updatedData);
        $this->seeInDatabase('users', ['id' => $userId, 'full_name' => 'Updated Test User Name', 'is_active' => 0]);
    }

    public function testUpdateUserPassword()
    {
        $data = $this->getValidUserData();
        $userId = $this->userModel->insert($data);
        $oldHashedPassword = $this->userModel->find($userId)['password'];

        $newPassword = 'newPassword456';
        $this->userModel->update($userId, ['password' => $newPassword]);

        $updatedUser = $this->userModel->find($userId);
        $this->assertNotEquals($oldHashedPassword, $updatedUser['password']);
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
    }

    public function testDeleteUser()
    {
        $data = $this->getValidUserData();
        $userId = $this->userModel->insert($data);
        $this->seeInDatabase('users', ['id' => $userId]);

        $this->userModel->delete($userId);
        $this->dontSeeInDatabase('users', ['id' => $userId]);
    }

    public function testGetUserWithRole()
    {
        $userData = $this->getValidUserData();
        $userId = $this->userModel->insert($userData);

        $userWithRole = $this->userModel->getUserWithRole($userId);
        $this->assertNotNull($userWithRole);
        $this->assertEquals($userData['username'], $userWithRole['username']);

        $expectedRole = $this->roleModel->find($this->validRoleId);
        $this->assertEquals($expectedRole['role_name'], $userWithRole['role_name']);
    }

    public function testGetAllUsersWithRoles()
    {
        $userData1 = $this->getValidUserData(['username' => 'user1_roles']);
        $this->userModel->insert($userData1);
        $userData2 = $this->getValidUserData(['username' => 'user2_roles']);
        $this->userModel->insert($userData2);

        $usersWithRoles = $this->userModel->getAllUsersWithRoles();
        $this->assertCount(2, $usersWithRoles); // Assuming only these two users exist in this specific test context

        foreach ($usersWithRoles as $user) {
            $this->assertArrayHasKey('role_name', $user);
            $this->assertNotNull($user['role_name']);
        }
    }
}
