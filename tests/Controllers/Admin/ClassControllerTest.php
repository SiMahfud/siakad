<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\TeacherModel; // For creating/checking wali_kelas
use App\Models\ClassModel;  // For direct interaction if needed, though FeatureTestTrait is primary

class ClassControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $seed = 'UserRoleSeeder'; // Provides admin, guru (teacher), siswa, ortu users & roles
    protected $basePath = APPPATH . 'Database';
    protected $namespace = 'App';

    protected $adminUser;
    protected $guruUser; // 'testguru' from seeder
    // protected $stafUser; // TODO: Add 'teststaf' to UserRoleSeeder if needed for specific tests

    protected function setUp(): void
    {
        parent::setUp();
        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'testadmin')->first();
        $this->guruUser = $userModel->where('username', 'testguru')->first();

        // Ensure session service is ready for FeatureTestTrait calls
        $this->session = service('session');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        }
    }

    public function testIndexAccessDeniedIfNotLoggedIn()
    {
        $result = $this->get('/admin/classes');
        $result->assertRedirectTo(site_url('/login'));
    }

    public function testIndexAccessDeniedIfLoggedInAsGuru()
    {
        // 'testguru' does not have 'Administrator Sistem', 'Staf Tata Usaha', or 'Kepala Sekolah' roles
        // The route group '/admin/classes' uses filter $dataIndukFilter = 'auth:Administrator Sistem,Staf Tata Usaha,Kepala Sekolah'
        $result = $this->actingAs($this->guruUser)
                         ->get('/admin/classes');

        // Expect redirect to unauthorized page as per AuthFilter logic
        $result->assertRedirectTo(site_url('/unauthorized-access'));
    }

    public function testIndexAccessGrantedIfLoggedInAsAdmin()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/classes');

        $result->assertStatus(200);
        $result->assertSee('Daftar Kelas'); // Assuming this title or text exists on the classes index page
    }

    // Further test cases for CRUD operations will be added here:
    // testShowNewClassForm()
    // testCreateClassSuccess()
    // testCreateClassFailsWithValidationErrors()
    // testEditClassFormShowsCorrectData()
    // testUpdateClassSuccess()
    // testUpdateClassFailsWithValidationErrors()
    // testDeleteClassSuccess()
    // testDeleteClassNonExistent()
}
