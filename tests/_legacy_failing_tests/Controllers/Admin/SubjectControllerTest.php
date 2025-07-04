<?php

namespace Tests\Controllers\Admin;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel; // For admin user
use App\Models\RoleModel; // For admin user role
use App\Models\SubjectModel;

class SubjectControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // protected $seeders     = ['App\Database\Seeds\RoleSeeder', 'App\Database\Seeds\UserSeederForTests']; // Now handled by BaseTestCase

    protected $adminUser;
    protected $subjectModel;

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        // $this->ensureUserSeederExists(); // Now in BaseTestCase

        $userModel = new UserModel();
        $roleModel = new RoleModel();
        $this->subjectModel = new SubjectModel();

        $adminRoleObj = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $adminRoleId = $adminRoleObj['id'];

        $this->adminUser = $userModel->where('username', 'admin_user_for_tests')->first();
        if (!$this->adminUser) {
            $adminUserId = $userModel->insert([
                'username'  => 'admin_user_for_tests',
                'password'  => 'password123',
                'full_name' => 'Admin Test User for Subjects',
                'role_id'   => $adminRoleId,
                'is_active' => 1,
            ]);
            $this->adminUser = $userModel->find($adminUserId);
        }
    }

    // ensureUserSeederExists() is now in BaseTestCase

    public function testAccessDeniedIfNotLoggedIn()
    {
        $result = $this->call('get', '/admin/subjects');
        $result->assertRedirectTo('/login');
    }

    public function testIndexPageDisplaysSubjects()
    {
        $this->subjectModel->insert(['subject_name' => 'Test List Subject', 'subject_code' => 'TLS01', 'is_pilihan' => 0]);

        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/subjects');
        $result->assertStatus(200);
        $result->assertSee('Manage Subjects');
        $result->assertSee('Test List Subject');
    }

    public function testNewSubjectPageDisplaysForm()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/subjects/new');
        $result->assertStatus(200);
        $result->assertSee('Add New Subject');
        $result->assertSee('Subject Name:');
        $result->assertSee('Subject Code');
        $result->assertSee('Subject Type:');
    }

    public function testCreateSubjectSuccess()
    {
        $subjectData = [
            'subject_name' => 'New Created Subject ' . uniqid(),
            'subject_code' => 'NCS_' . uniqid(),
            'is_pilihan'   => '1', // Pilihan
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/subjects/create', $subjectData);

        $result->assertRedirectTo('/admin/subjects');
        $result->assertSessionHas('success', 'Subject added successfully.');
        $this->seeInDatabase('subjects', ['subject_code' => $subjectData['subject_code'], 'is_pilihan' => 1]);
    }

    public function testCreateSubjectFailsWithInvalidData()
    {
        // Missing subject_name
        $subjectData = ['subject_code' => 'INV_SUB_'.uniqid(), 'is_pilihan' => '0'];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/subjects/create', $subjectData);

        $result->assertStatus(200);
        $result->assertSee('Add New Subject');
        $result->assertSessionHas('validation');
        $this->dontSeeInDatabase('subjects', ['subject_code' => $subjectData['subject_code']]);
    }

    public function testEditSubjectPageDisplaysCorrectly()
    {
        $subjectId = $this->subjectModel->insert([
            'subject_name' => 'Subject To Edit',
            'subject_code' => 'STE_'.uniqid(),
            'is_pilihan' => 0
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get("/admin/subjects/edit/{$subjectId}");

        $result->assertStatus(200);
        $result->assertSee("Edit Subject: Subject To Edit");
        $result->assertInputValue('subject_name', 'Subject To Edit');
    }

    public function testUpdateSubjectSuccess()
    {
        $subjectId = $this->subjectModel->insert([
            'subject_name' => 'Subject Before Update',
            'subject_code' => 'SBU_'.uniqid(),
            'is_pilihan' => 0
        ]);

        $updateData = [
            'subject_name' => 'Subject After Update',
            'subject_code' => 'SAU_'.uniqid(),
            'is_pilihan'   => '1',
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/subjects/update/{$subjectId}", $updateData);

        $result->assertRedirectTo('/admin/subjects');
        $result->assertSessionHas('success', 'Subject updated successfully.');
        $this->seeInDatabase('subjects', ['id' => $subjectId, 'subject_name' => 'Subject After Update', 'is_pilihan' => 1]);
    }

    public function testDeleteSubjectSuccess()
    {
        $subjectId = $this->subjectModel->insert([
            'subject_name' => 'Subject To Delete',
            'subject_code' => 'STD_'.uniqid(),
            'is_pilihan' => 0
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/subjects/delete/{$subjectId}");

        $result->assertRedirectTo('/admin/subjects');
        $result->assertSessionHas('success', 'Subject deleted successfully.');
        $this->dontSeeInDatabase('subjects', ['id' => $subjectId]);
    }
}
