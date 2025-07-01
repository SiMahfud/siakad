<?php

namespace Tests\Controllers\Admin;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\StudentModel;

class StudentControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // protected $seeders     = ['App\Database\Seeds\RoleSeeder', 'App\Database\Seeds\UserSeederForTests']; // Now handled by BaseTestCase

    protected $adminUser;
    protected $studentModel;
    protected $userModel; // To get valid user_ids for student records

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        // $this->ensureUserSeederExists(); // Now in BaseTestCase

        $this->userModel = new UserModel();
        $roleModel = new RoleModel();
        $this->studentModel = new StudentModel();

        // Get 'Administrator Sistem' role ID
        $adminRoleObj = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $adminRoleId = $adminRoleObj['id'];

        $this->adminUser = $this->userModel->where('username', 'admin_user_for_tests')->first();
        if (!$this->adminUser) {
            $adminUserId = $this->userModel->insert([
                'username'  => 'admin_user_for_tests',
                'password'  => 'password123',
                'full_name' => 'Admin Test User for Students',
                'role_id'   => $adminRoleId,
                'is_active' => 1,
            ]);
            $this->adminUser = $this->userModel->find($adminUserId);
        }
    }

    // ensureUserSeederExists() is now in BaseTestCase

    public function testAccessDeniedIfNotLoggedIn()
    {
        $result = $this->call('get', '/admin/students');
        $result->assertRedirectTo('/login');
    }

    public function testIndexPageDisplaysStudents()
    {
        // Create a sample student to ensure the list is not empty
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $this->studentModel->insert([
            'full_name' => 'List Test Student',
            'nisn' => 'nisn_list_test_'.uniqid(),
            'user_id' => $studentUser ? $studentUser['id'] : null
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/students');
        $result->assertStatus(200);
        $result->assertSee('Manage Students');
        $result->assertSee('List Test Student');
    }

    public function testNewStudentPageDisplaysForm()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/students/new');
        $result->assertStatus(200);
        $result->assertSee('Add New Student');
        $result->assertSee('Full Name:');
        $result->assertSee('NISN:');
    }

    public function testCreateStudentSuccess()
    {
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $parentUser = $this->userModel->where('username', 'testparentuser_fk')->first();

        $studentData = [
            'full_name'      => 'New Created Student ' . uniqid(),
            'nisn'           => 'nisn_create_' . uniqid(),
            'user_id'        => $studentUser['id'] ?? null, // Use ID if user exists
            'parent_user_id' => $parentUser['id'] ?? null,
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/students/create', $studentData);

        $result->assertRedirectTo('/admin/students');
        $result->assertSessionHas('success', 'Student added successfully.');
        $this->seeInDatabase('students', ['nisn' => $studentData['nisn']]);
    }

    public function testCreateStudentFailsWithInvalidData()
    {
        // Missing full_name
        $studentData = ['nisn' => 'nisn_invalid_' . uniqid()];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/students/create', $studentData);

        $result->assertStatus(200); // Redisplays form
        $result->assertSee('Add New Student');
        $result->assertSessionHas('validation');
        $this->dontSeeInDatabase('students', ['nisn' => $studentData['nisn']]);
    }

    public function testEditStudentPageDisplaysCorrectly()
    {
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $studentId = $this->studentModel->insert([
            'full_name' => 'Student To Edit',
            'nisn'      => 'nisn_to_edit_'.uniqid(),
            'user_id'   => $studentUser['id'] ?? null
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get("/admin/students/edit/{$studentId}");

        $result->assertStatus(200);
        $result->assertSee("Edit Student: Student To Edit");
        $result->assertInputValue('full_name', 'Student To Edit');
    }

    public function testUpdateStudentSuccess()
    {
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $studentId = $this->studentModel->insert([
            'full_name' => 'Student Before Update',
            'nisn'      => 'nisn_before_update_'.uniqid(),
            'user_id'   => $studentUser['id'] ?? null
        ]);

        $updateData = [
            'full_name' => 'Student After Update',
            'nisn'      => 'nisn_after_update_'.uniqid(),
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/students/update/{$studentId}", $updateData);

        $result->assertRedirectTo('/admin/students');
        $result->assertSessionHas('success', 'Student updated successfully.');
        $this->seeInDatabase('students', ['id' => $studentId, 'full_name' => 'Student After Update']);
    }

    public function testDeleteStudentSuccess()
    {
        $studentUser = $this->userModel->where('username', 'teststudentuser_fk')->first();
        $studentId = $this->studentModel->insert([
            'full_name' => 'Student To Delete',
            'nisn'      => 'nisn_to_delete_'.uniqid(),
            'user_id'   => $studentUser['id'] ?? null
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/students/delete/{$studentId}"); // CI resource route uses POST for delete

        $result->assertRedirectTo('/admin/students');
        $result->assertSessionHas('success', 'Student deleted successfully.');
        $this->dontSeeInDatabase('students', ['id' => $studentId]);
    }
}
