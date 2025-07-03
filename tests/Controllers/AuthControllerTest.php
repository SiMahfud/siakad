<?php

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;

class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';
    protected $namespace = 'App';

    protected function tearDown(): void
    {
        parent::tearDown();
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Destroy session and re-initialize service for a clean slate if needed by subsequent non-feature tests.
            // For FeatureTestTrait, it manages session per request via withSession().
            session()->destroy();
        }
    }

    public function testLoginPageShowsCorrectly()
    {
        $result = $this->get('/login');

        $result->assertStatus(200);
        // Assuming 'Login SI Akademik' might be part of a <title> tag or a prominent heading.
        // More robust checks might target specific form elements.
        $result->assertSee('Username'); // Check for presence of 'Username' label or input name
        $result->assertSee('Password'); // Check for presence of 'Password' label or input name
        // $result->assertSeeElement('input[name="username"]');
        // $result->assertSeeElement('input[name="password"]');
    }

    public function testLoginSuccessWithValidAdminCredentials()
    {
        $credentials = [
            'username' => 'testadmin', // from UserRoleSeeder
            'password' => 'password123'
        ];

        $result = $this->post('/login', $credentials);

        // AuthController redirects 'Administrator Sistem' (role_id 1) to 'admin/students'
        $result->assertRedirectTo(site_url('admin/students'));

        $result->assertSessionHas('is_logged_in', true);
        $result->assertSessionHas('username', 'testadmin');

        $userModel = new UserModel();
        $adminUser = $userModel->where('username', 'testadmin')->first();
        $this->assertNotNull($adminUser, "Admin user 'testadmin' should exist.");
        $result->assertSessionHas('user_id', $adminUser['id']);

        if (isset($adminUser['role_id'])) {
            $result->assertSessionHas('role_id', $adminUser['role_id']);
        }
    }

    public function testLoginFailsWithInvalidPassword()
    {
        $credentials = [
            'username' => 'testadmin', // User exists
            'password' => 'wrongpassword'
        ];

        $result = $this->post('/login', $credentials);

        $result->assertRedirectTo(site_url('/login'));
        // Check for the specific error message from AuthController
        $result->assertSessionHas('error', 'Incorrect password.');

        $this->assertNull(session()->get('is_logged_in'), "User should not be logged in after failed attempt.");
    }

    public function testLoginFailsWithNonExistentUser()
    {
        $credentials = [
            'username' => 'nonexistentuser',
            'password' => 'password123'
        ];

        $result = $this->post('/login', $credentials);

        $result->assertRedirectTo(site_url('/login'));
        // Check for the specific error message from AuthController
        $result->assertSessionHas('error', 'Username not found.');
        $this->assertNull(session()->get('is_logged_in'), "User should not be logged in with non-existent username.");
    }

    public function testLogoutSuccessfully()
    {
        // 1. Ensure the user is logged in by calling the login route first
        $loginCredentials = [
            'username' => 'testadmin',
            'password' => 'password123'
        ];
        // Perform the login POST request. FeatureTestTrait will maintain the session for subsequent calls by default.
        $this->post('/login', $loginCredentials);

        // Verify session is set after login
        $this->assertTrue(session()->get('is_logged_in'), 'User should be logged in before logout.');
        $this->assertEquals('testadmin', session()->get('username'));

        // 2. Call logout route
        $result = $this->get('/logout');

        // 3. Assert redirection and session state
        $result->assertRedirectTo(site_url('/login'));

        // Check that specific session variables are cleared
        $this->assertNull(session()->get('is_logged_in'), 'is_logged_in should be null after logout.');
        $this->assertNull(session()->get('user_id'), 'user_id should be null after logout.');
        $this->assertNull(session()->get('username'), 'username should be null after logout.');
    }
}
