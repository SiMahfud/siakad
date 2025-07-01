<?php

namespace Tests\Controllers;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;

class AuthControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait; // Enables call(), get(), post() etc.

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // Seeders are now handled by BaseTestCase's setUp for common seeders.
    // This class might not need to specify $seeders if BaseTestCase covers RoleSeeder and UserSeederForTests.
    protected $seeders     = []; // Or remove this line if BaseTestCase handles all needed seeds.

    protected $userModel;
    protected $roleModel;
    protected $testUserCredentials = [
        'username' => 'auth_test_user',
        'password' => 'password123'
    ];
    protected $testUserId;
    protected $adminRoleId;

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        // $this->ensureUserSeederExists(); // Now in BaseTestCase, called by parent::setUp() or explicitly if needed

        // Get Admin role ID (assuming role 'Administrator Sistem' is ID 1 or seeded as such)
        // Ensure seeders have run by virtue of $this->seeders and $this->refresh
        // parent::ensureUserSeederExists(); // This call is problematic as the method is removed from BaseTestCase

        $adminRole = $this->roleModel->where('role_name', 'Administrator Sistem')->first();
        if (!$adminRole) {
            // This is a critical failure for test setup. Seeders might not have run.
            // Forcing RoleSeeder here if it hasn't run.
            $this->seed('App\Database\Seeds\RoleSeeder');
            $adminRole = $this->roleModel->where('role_name', 'Administrator Sistem')->first();
        }
        $this->adminRoleId = $adminRole ? $adminRole['id'] : 1; // Fallback to 1 if not found by name

        // Create a specific user for authentication tests
        $existingUser = $this->userModel->where('username', $this->testUserCredentials['username'])->first();
        if ($existingUser) {
            $this->testUserId = $existingUser['id'];
            // Ensure password is known if user already exists from a previous partial test run
            $this->userModel->update($this->testUserId, ['password' => $this->testUserCredentials['password']]);
        } else {
            $this->testUserId = $this->userModel->insert([
                'username'  => $this->testUserCredentials['username'],
                'password'  => $this->testUserCredentials['password'], // Will be hashed by model
                'full_name' => 'Auth Test User',
                'role_id'   => $this->adminRoleId,
                'is_active' => 1,
            ]);
        }
    }

    // ensureUserSeederExists() is now in BaseTestCase

    public function testLoginPageDisplays()
    {
        $result = $this->call('get', '/login');
        $result->assertStatus(200);
        $result->assertSee('Login SI-AKADEMIK'); // Check for a title or unique text
        $result->assertSee('Username:');
        $result->assertSee('Password:');
    }

    public function testLoginSuccessWithValidCredentials()
    {
        $result = $this->call('post', '/login', [
            'username' => $this->testUserCredentials['username'],
            'password' => $this->testUserCredentials['password'],
        ]);

        $result->assertRedirectTo('/admin/students'); // Or wherever admin redirects
        $result->assertSessionHas('is_logged_in', true);
        $result->assertSessionHas('user_id', $this->testUserId);
        $result->assertSessionHas('username', $this->testUserCredentials['username']);
    }

    public function testLoginFailsWithInvalidUsername()
    {
        $result = $this->call('post', '/login', [
            'username' => 'nonexistentuser',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('is_logged_in');
        $result->assertSessionHas('error', 'Username not found.');
    }

    public function testLoginFailsWithIncorrectPassword()
    {
        $result = $this->call('post', '/login', [
            'username' => $this->testUserCredentials['username'],
            'password' => 'wrongpassword',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('is_logged_in');
        $result->assertSessionHas('error', 'Incorrect password.');
    }

    public function testLoginFailsWithInactiveUser()
    {
        // Deactivate the test user first
        $this->userModel->update($this->testUserId, ['is_active' => 0]);

        $result = $this->call('post', '/login', [
            'username' => $this->testUserCredentials['username'],
            'password' => $this->testUserCredentials['password'],
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('is_logged_in');
        $result->assertSessionHas('error', 'Your account is inactive. Please contact an administrator.');

        // Reactivate for other tests
        $this->userModel->update($this->testUserId, ['is_active' => 1]);
    }

    public function testLoginFailsWithMissingUsername()
    {
        $result = $this->call('post', '/login', ['password' => 'password123']);
        $result->assertRedirectTo('/login');
        $result->assertSessionHas('validation'); // Check for validation errors
    }

    public function testLoginFailsWithMissingPassword()
    {
        $result = $this->call('post', '/login', ['username' => $this->testUserCredentials['username']]);
        $result->assertRedirectTo('/login');
        $result->assertSessionHas('validation');
    }

    public function testLogout()
    {
        // First, log in the user
        $this->call('post', '/login', [
            'username' => $this->testUserCredentials['username'],
            'password' => $this->testUserCredentials['password'],
        ]);
        // Ensure session is set
        $this->assertTrue(session()->get('is_logged_in'));

        // Then, call logout
        $result = $this->call('get', '/logout');

        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('is_logged_in');
        $result->assertSessionHas('success', 'You have been logged out.');
    }

    public function testRedirectIfAlreadyLoggedIn()
    {
        // Simulate a logged-in user by manually setting session or by calling login
        $this->withSession([
            'user_id'       => $this->testUserId,
            'username'      => $this->testUserCredentials['username'],
            'role_id'       => $this->adminRoleId,
            'is_logged_in'  => true,
        ]);

        $result = $this->call('get', '/login');
        $result->assertRedirectTo('admin/students'); // As per AuthController logic for admins
    }
}
