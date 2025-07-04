<?php

namespace Tests\Controllers\Admin;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\TeacherModel;

class TeacherControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // protected $seeders     = [ // Now handled by BaseTestCase
    //     'App\Database\Seeds\RoleSeeder',
    //     'App\Database\Seeds\UserSeederForTests',
    //     'App\Database\Seeds\TeacherSeederForTests'
    // ];
    protected $seeders = []; // Rely on BaseTestCase for common seeding

    protected $adminUser;
    protected $teacherModel;
    protected $userModel;

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        // $this->ensureUserSeederExists();  // Now in BaseTestCase
        // $this->ensureTeacherSeederExists(); // Now in BaseTestCase


        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
        $this->teacherModel = new TeacherModel();

        $adminRoleObj = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $adminRoleId = $adminRoleObj['id'];

        $this->adminUser = $this->userModel->where('username', 'admin_user_for_tests')->first();
        if (!$this->adminUser) {
            $adminUserId = $this->userModel->insert([
                'username'  => 'admin_user_for_tests',
                'password'  => 'password123',
                'full_name' => 'Admin Test User for Teachers',
                'role_id'   => $adminRoleId,
                'is_active' => 1,
            ]);
            $this->adminUser = $this->userModel->find($adminUserId);
        }
    }

    // ensureUserSeederExists() and ensureTeacherSeederExists() are now in BaseTestCase.
    // The parent::setUp() in BaseTestCase should handle calling them if they are defined there.
    // If BaseTestCase::setUp() doesn't call them, we might need to call parent::ensureUserSeederExists() etc. here.
    // For now, assuming BaseTestCase's setUp calls them or they are not needed in every single test class setUp.


    public function testAccessDeniedIfNotLoggedIn()
    {
        $result = $this->call('get', '/admin/teachers');
        $result->assertRedirectTo('/login');
    }

    public function testIndexPageDisplaysTeachers()
    {
        // Assumes TeacherSeederForTests creates at least one teacher
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/teachers');
        $result->assertStatus(200);
        $result->assertSee('Manage Teachers');
        $result->assertSee('Seeded Test Teacher From Seeder'); // Check for seeded teacher
    }

    public function testNewTeacherPageDisplaysForm()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/teachers/new');
        $result->assertStatus(200);
        $result->assertSee('Add New Teacher');
        $result->assertSee('Full Name:');
        $result->assertSee('NIP:');
    }

    public function testCreateTeacherSuccess()
    {
        $teacherUserData = $this->userModel->where('username', 'teacher_user_for_teacher_seeder')->first();
        $teacherData = [
            'full_name' => 'New Created Teacher ' . uniqid(),
            'nip'       => 'nip_create_' . uniqid(),
            'user_id'   => $teacherUserData['id'] ?? null,
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/teachers/create', $teacherData);

        $result->assertRedirectTo('/admin/teachers');
        $result->assertSessionHas('success', 'Teacher added successfully.');
        $this->seeInDatabase('teachers', ['nip' => $teacherData['nip']]);
    }

    public function testCreateTeacherFailsWithInvalidData()
    {
        $teacherData = ['nip' => 'nip_invalid_' . uniqid()]; // Missing full_name

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/teachers/create', $teacherData);

        $result->assertStatus(200);
        $result->assertSee('Add New Teacher');
        $result->assertSessionHas('validation');
        $this->dontSeeInDatabase('teachers', ['nip' => $teacherData['nip']]);
    }

    public function testEditTeacherPageDisplaysCorrectly()
    {
        $teacher = $this->teacherModel->where('nip', 'nip_seeded_teacher_')->first() ?? $this->teacherModel->first(); // Get seeded teacher
        $this->assertNotNull($teacher, "No teacher found to edit. Check TeacherSeederForTests.");


        $result = $this->actingAs($this->adminUser)
                         ->get("/admin/teachers/edit/{$teacher['id']}");

        $result->assertStatus(200);
        $result->assertSee("Edit Teacher: {$teacher['full_name']}");
        $result->assertInputValue('full_name', $teacher['full_name']);
    }

    public function testUpdateTeacherSuccess()
    {
        $teacher = $this->teacherModel->first();
        $this->assertNotNull($teacher, "No teacher found to update.");

        $updateData = [
            'full_name' => 'Teacher After Update',
            'nip'       => 'nip_after_update_'.uniqid(),
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/teachers/update/{$teacher['id']}", $updateData);

        $result->assertRedirectTo('/admin/teachers');
        $result->assertSessionHas('success', 'Teacher updated successfully.');
        $this->seeInDatabase('teachers', ['id' => $teacher['id'], 'full_name' => 'Teacher After Update']);
    }

    public function testDeleteTeacherSuccess()
    {
        // Create a new teacher specifically for this delete test to avoid FK issues with classes
        $teacherUser = $this->userModel->where('username', 'teacher_user_for_teacher_seeder')->first();
        $teacherId = $this->teacherModel->insert([
            'full_name' => 'Teacher To Delete ' . uniqid(),
            'nip'       => 'nip_to_delete_'.uniqid(),
            'user_id'   => $teacherUser['id'] ?? null
        ]);


        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/teachers/delete/{$teacherId}");

        $result->assertRedirectTo('/admin/teachers');
        $result->assertSessionHas('success', 'Teacher deleted successfully.');
        $this->dontSeeInDatabase('teachers', ['id' => $teacherId]);
    }
}
