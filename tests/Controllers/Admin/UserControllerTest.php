<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait; // Changed from ControllerTestTrait
// We might need to use App\Models\UserModel or RoleModel later
// use App\Models\UserModel;
// use App\Models\RoleModel;

class UserControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait; // Changed from ControllerTestTrait

    // Migrate database before tests
    protected $migrate = true;
    // Rollback migrations and run them again before each test for a clean slate
    protected $refresh = true;
    // Specify a seeder to run, we'll create this next
    protected $seed = 'UserRoleSeeder';
    // Namespace for migrations. Set to 'App' to run app migrations.
    protected $namespace = 'App';
    protected $basePath = APPPATH . 'Database'; // Correct base path for seeders

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure session service is initialized
        // It's better to initialize it fresh if needed, or rely on service()
        // $this->session = \Config\Services::session();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up session after each test
        // Ensure session() helper or service('session') is used to get the instance
        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        }
    }

    // Test methods will be added here based on the plan

    public function testIndexAccessDeniedWhenNotLoggedIn()
    {
        // Ensure no one is logged in by destroying any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
            // Re-initialize session service for the test runner if needed, though typically not required here.
            // $this->session = \Config\Services::session();
        }

        // Call the /admin/users route using a GET request
        $result = $this->withSession([]) // Ensure fresh session, no logged-in user
                         ->get('/admin/users');

        // Assert that the response is a redirect
        $result->assertRedirect();

        // Assert that it redirects to the login page
        $result->assertRedirectTo('/login');

        // Optionally, check for a specific flash message if your AuthFilter sets one
        // This requires session to be active and flash messages to be correctly set and retrieved.
        // $result->assertSessionHas('error', 'You must be logged in to access this page.');
    }

    public function testIndexAccessDeniedWhenLoggedInAsNonAdmin()
    {
        // Get the non-admin user (e.g., 'testguru') created by UserRoleSeeder
        $userModel = new \App\Models\UserModel();
        $guruUser = $userModel->where('username', 'testguru')->first();

        $this->assertNotNull($guruUser, 'Test guru user should exist.');

        // Simulate login as the 'testguru' user
        // The session data should match what AuthFilter and hasRole() helper expect.
        // Typically, this includes 'user_id', 'username', 'role_id' (or 'role_name'), 'isLoggedIn'.
        // The hasRole() helper in this project seems to use role_name from a joined query or direct role_id.
        // Let's use the structure from AuthController::attemptLogin after successful login.
        // Assuming AuthController sets 'user_id', 'username', 'role_id', 'role_name', 'isLoggedIn'.

        $sessionData = [
            'user_id'    => $guruUser['id'],
            'username'   => $guruUser['username'],
            'role_id'    => $guruUser['role_id'],
            // 'role_name' => 'Guru', // Assuming UserRoleSeeder sets this role or we fetch it.
                                   // The AuthFilter uses hasRole($allowedRoles) which in turn will use get_user_roles()
                                   // which fetches roles based on logged_in user's role_id.
            'is_logged_in' => true, // Corrected session key to snake_case
        ];

        // Make sure the RoleModel can find the role name for 'Guru'
        $roleModel = new \App\Models\RoleModel();
        $guruRole = $roleModel->find($guruUser['role_id']);
        $this->assertNotNull($guruRole, "Role for testguru (ID: {$guruUser['role_id']}) should exist.");
        // $sessionData['role_name'] = $guruRole['role_name']; // This is good for completeness.

        $result = $this->withSession($sessionData)
                         ->get('/admin/users');

        // Assert that the response is a redirect because non-admin cannot access
        $result->assertRedirect();

        // Assert that it redirects to the '/unauthorized-access' page (as per AuthFilter.php)
        $result->assertRedirectTo(site_url('/unauthorized-access'));

        // Alternatively, assert a 403 Forbidden status if the filter returns that directly
        // $result->assertStatus(403);
    }

    public function testIndexAccessGrantedWhenLoggedInAsAdmin()
    {
        // Get the admin user (e.g., 'testadmin') created by UserRoleSeeder
        $userModel = new \App\Models\UserModel();
        $adminUser = $userModel->where('username', 'testadmin')->first();

        $this->assertNotNull($adminUser, 'Test admin user should exist.');
        $this->assertEquals('testadmin', $adminUser['username']);


        // Get the role for 'Administrator Sistem' to ensure ID is correct for session
        $roleModel = new \App\Models\RoleModel();
        $adminRole = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $this->assertNotNull($adminRole, "'Administrator Sistem' role should exist.");
        $this->assertEquals($adminUser['role_id'], $adminRole['id'], "Test admin user's role_id should match 'Administrator Sistem' role id.");

        $sessionData = [
            'user_id'      => $adminUser['id'],
            'username'     => $adminUser['username'],
            'role_id'      => $adminUser['role_id'],
            // 'role_name'    => $adminRole['role_name'], // Good for completeness, used by some helpers
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/users');

        // Assert that the response is successful (HTTP 200 OK)
        $result->assertStatus(200);

        // Assert that we see some text that should be on the user list page
        // This depends on the content of your 'admin/users/index' view
        $result->assertSee('Manage Users'); // Assuming the title is 'Manage Users' as per UserController
        $result->assertSee($adminUser['username']); // Check if the admin's own username is listed
    }
}
