<?php

namespace Tests\Controllers\Admin;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // protected $seeders     = ['App\Database\Seeds\RoleSeeder', 'App\Database\Seeds\UserSeederForTests']; // Now handled by BaseTestCase

    protected $adminUser; // To store the admin user acting in tests
    protected $adminRoleId;
    protected $siswaRoleId; // For creating test users with different roles

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        // $this->ensureUserSeederExists(); // Now in BaseTestCase

        $userModel = new UserModel();
        $roleModel = new RoleModel();

        // Get 'Administrator Sistem' role ID
        $adminRoleObj = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $this->adminRoleId = $adminRoleObj['id'];

        // Get 'Siswa' role ID for creating non-admin test users
        $siswaRoleObj = $roleModel->where('role_name', 'Siswa')->first();
        $this->siswaRoleId = $siswaRoleObj['id'];

        // Create or find an admin user to act as the logged-in user for tests
        $this->adminUser = $userModel->where('username', 'admin_user_for_tests')->first();
        if (!$this->adminUser) {
            $adminUserId = $userModel->insert([
                'username'  => 'admin_user_for_tests',
                'password'  => 'password123', // Will be hashed
                'full_name' => 'Admin Test User',
                'role_id'   => $this->adminRoleId,
                'is_active' => 1,
            ]);
            $this->adminUser = $userModel->find($adminUserId);
        }
    }

    // ensureUserSeederExists() is now in BaseTestCase

    public function testAccessDeniedIfNotLoggedIn()
    {
        $result = $this->call('get', '/admin/users');
        $result->assertRedirectTo('/login'); // Due to AuthFilter
    }

    public function testIndexPageDisplaysUsersWhenLoggedInAsAdmin()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/users');
        $result->assertStatus(200);
        $result->assertSee('Manage Users');
        $result->assertSee($this->adminUser['username']); // Admin user should be in the list
    }

    public function testNewUserPageDisplaysForm()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/users/new');
        $result->assertStatus(200);
        $result->assertSee('Add New User');
        $result->assertSee('Username:');
        $result->assertSee('Password:');
        $result->assertSee('Role:');
    }

    public function testCreateUserSuccess()
    {
        $userData = [
            'username'  => 'newtestuser' . uniqid(),
            'full_name' => 'New Test User Full Name',
            'password'  => 'password1234',
            'role_id'   => $this->siswaRoleId, // Create a 'Siswa' role user
            'is_active' => '1',
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/users/create', $userData);

        $result->assertRedirectTo('/admin/users');
        $result->assertSessionHas('success', 'User added successfully.');
        $this->seeInDatabase('users', ['username' => $userData['username']]);
    }

    public function testCreateUserFailsWithInvalidData()
    {
        $userData = [ // Missing username, password too short
            'full_name' => 'Invalid User',
            'password'  => 'short',
            'role_id'   => $this->siswaRoleId,
            'is_active' => '1',
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/users/create', $userData);

        $result->assertStatus(200); // Should redisplay the form
        $result->assertSee('Add New User'); // Back to the form
        $result->assertSessionHas('validation'); // Check for validation errors
        $this->dontSeeInDatabase('users', ['full_name' => 'Invalid User']);
    }

    public function testEditUserPageDisplaysCorrectly()
    {
        // Create a user to edit first
        $userModel = new UserModel();
        $editUserId = $userModel->insert([
            'username' => 'user_to_edit_' . uniqid(),
            'password' => 'password123',
            'full_name'=> 'User To Edit',
            'role_id'  => $this->siswaRoleId,
            'is_active'=> 1
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get("/admin/users/edit/{$editUserId}");

        $result->assertStatus(200);
        $result->assertSee("Edit User: User To Edit");
        $result->assertInputValue('username', 'user_to_edit_'); // Check if value is pre-filled (approx)
    }

    public function testUpdateUserSuccess()
    {
        $userModel = new UserModel();
        $updateUserId = $userModel->insert([
            'username' => 'user_to_update_' . uniqid(),
            'password' => 'initialPass',
            'full_name'=> 'User Before Update',
            'role_id'  => $this->siswaRoleId,
            'is_active'=> 1
        ]);

        $updateData = [
            'username'  => 'updated_username_' . uniqid(),
            'full_name' => 'User After Update',
            'role_id'   => $this->adminRoleId, // Change role
            'is_active' => '0',
            'password'  => '', // Not changing password
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/users/update/{$updateUserId}", $updateData);

        $result->assertRedirectTo('/admin/users');
        $result->assertSessionHas('success', 'User updated successfully.');
        $this->seeInDatabase('users', [
            'id' => $updateUserId,
            'username' => $updateData['username'],
            'full_name'=> 'User After Update',
            'role_id'  => $this->adminRoleId,
            'is_active'=> 0
        ]);
    }

    public function testUpdateUserPasswordSuccess()
    {
        $userModel = new UserModel();
        $updatePassUserId = $userModel->insert([
            'username' => 'user_pass_update_' . uniqid(),
            'password' => 'initialPass',
            'full_name'=> 'User Pass Update',
            'role_id'  => $this->siswaRoleId,
            'is_active'=> 1
        ]);
        $oldUser = $userModel->find($updatePassUserId);


        $updateData = [
            'username'  => $oldUser['username'], // Keep username same
            'full_name' => $oldUser['full_name'],
            'role_id'   => $oldUser['role_id'],
            'is_active' => $oldUser['is_active'],
            'password'  => 'newSecurePassword123',
        ];

        $this->actingAs($this->adminUser)
             ->post("/admin/users/update/{$updatePassUserId}", $updateData);

        $updatedUser = $userModel->find($updatePassUserId);
        $this->assertNotEquals($oldUser['password'], $updatedUser['password']); // Password should have changed
        $this->assertTrue(password_verify('newSecurePassword123', $updatedUser['password']));
    }


    public function testDeleteUserSuccess()
    {
        $userModel = new UserModel();
        $deleteUserId = $userModel->insert([
            'username' => 'user_to_delete_' . uniqid(),
            'password' => 'password123',
            'full_name'=> 'User To Delete',
            'role_id'  => $this->siswaRoleId,
            'is_active'=> 1
        ]);
        // Ensure user is not the admin user itself
        $this->assertNotEquals($this->adminUser['id'], $deleteUserId);


        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/users/delete/{$deleteUserId}");

        $result->assertRedirectTo('/admin/users');
        $result->assertSessionHas('success', 'User deleted successfully.');
        $this->dontSeeInDatabase('users', ['id' => $deleteUserId]);
    }
}
