<?php

namespace Tests\Controllers\Admin;

use Tests\Support\BaseTestCase; // Use the new base test case
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ClassModel;
use App\Models\TeacherModel;

class ClassControllerTest extends BaseTestCase // Extend BaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true;  // Refresh database for each test class
    // protected $migrate     = true; // Redundant
    // protected $migrateOnce = false; // Redundant
    // Seeders are now primarily handled by BaseTestCase's setUp.
    // This array can be left empty or list additional, class-specific seeders if any.
    protected $seeders     = []; // Rely on BaseTestCase for common seeding

    protected $adminUser;
    protected $classModel;
    protected $teacherModel; // To get a valid teacher_id for wali_kelas

    protected function setUp(): void
    {
        parent::setUp(); // This should call the seeder helpers from BaseTestCase
        // $this->ensureUserSeederExists(); // Now in BaseTestCase
        // $this->ensureTeacherSeederExists(); // Now in BaseTestCase

        $userModel = new UserModel();
        $roleModel = new RoleModel();
        $this->classModel = new ClassModel();
        $this->teacherModel = new TeacherModel();

        $adminRoleObj = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $adminRoleId = $adminRoleObj['id'];

        $this->adminUser = $userModel->where('username', 'admin_user_for_tests')->first();
        if (!$this->adminUser) {
            $adminUserId = $userModel->insert([
                'username'  => 'admin_user_for_tests',
                'password'  => 'password123',
                'full_name' => 'Admin Test User for Classes',
                'role_id'   => $adminRoleId,
                'is_active' => 1,
            ]);
            $this->adminUser = $userModel->find($adminUserId);
        }
    }

    // ensureUserSeederExists() and ensureTeacherSeederExists() are now in BaseTestCase.

    public function testAccessDeniedIfNotLoggedIn()
    {
        $result = $this->call('get', '/admin/classes');
        $result->assertRedirectTo('/login');
    }

    public function testIndexPageDisplaysClasses()
    {
        // Ensure at least one teacher exists for wali_kelas_id
        $teacher = $this->teacherModel->first();
        $this->assertNotNull($teacher, "Teacher for wali_kelas not found. Check TeacherSeederForTests.");

        $this->classModel->insert([
            'class_name'    => 'Test List Class',
            'academic_year' => '2023/2024',
            'fase' => 'E',
            'wali_kelas_id' => $teacher['id']
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/classes');
        $result->assertStatus(200);
        $result->assertSee('Manage Classes');
        $result->assertSee('Test List Class');
        $result->assertSee($teacher['full_name']); // Check if wali_kelas name is displayed
    }

    public function testNewClassPageDisplaysForm()
    {
        $result = $this->actingAs($this->adminUser)
                         ->get('/admin/classes/new');
        $result->assertStatus(200);
        $result->assertSee('Add New Class');
        $result->assertSee('Class Name');
        $result->assertSee('Academic Year');
        $result->assertSee('Wali Kelas'); // Dropdown for teachers

        // Check if teachers are passed to the view and displayed in select
        $teacher = $this->teacherModel->first();
        if($teacher) {
            $result->assertSee(esc($teacher['full_name']));
        }
    }

    public function testCreateClassSuccess()
    {
        $teacher = $this->teacherModel->first();
        $this->assertNotNull($teacher, "Teacher for wali_kelas not found for create test.");

        $classData = [
            'class_name'    => 'New Created Class ' . uniqid(),
            'academic_year' => '2024/2025',
            'fase'          => 'F',
            'wali_kelas_id' => $teacher['id'],
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/classes/create', $classData);

        $result->assertRedirectTo('/admin/classes');
        $result->assertSessionHas('success', 'Class added successfully.');
        $this->seeInDatabase('classes', ['class_name' => $classData['class_name'], 'wali_kelas_id' => $teacher['id']]);
    }

    public function testCreateClassFailsWithInvalidData()
    {
        // Missing class_name
        $classData = ['academic_year' => '2023/2024'];

        $result = $this->actingAs($this->adminUser)
                         ->post('/admin/classes/create', $classData);

        $result->assertStatus(200);
        $result->assertSee('Add New Class');
        $result->assertSessionHas('validation');
        $this->dontSeeInDatabase('classes', ['academic_year' => '2023/2024', 'class_name' => '']);
    }

    public function testEditClassPageDisplaysCorrectly()
    {
        $teacher = $this->teacherModel->first();
        $this->assertNotNull($teacher, "Teacher for wali_kelas not found for edit page test.");
        $classId = $this->classModel->insert([
            'class_name'    => 'Class To Edit',
            'academic_year' => '2022/2023',
            'fase' => 'E',
            'wali_kelas_id' => $teacher['id']
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->get("/admin/classes/edit/{$classId}");

        $result->assertStatus(200);
        $result->assertSee("Edit Class: Class To Edit");
        $result->assertInputValue('class_name', 'Class To Edit');
        $result->assertElementHasAttribute('select[name=wali_kelas_id] option[value="'.$teacher['id'].'"]', 'selected');
    }

    public function testUpdateClassSuccess()
    {
        $teacher1 = $this->teacherModel->first();
        $this->assertNotNull($teacher1, "Initial teacher for update test not found.");

        $teachers = $this->teacherModel->findAll();
        $teacher2 = count($teachers) > 1 ? $teachers[1] : $teacher1; // Get a different teacher if available

        $classId = $this->classModel->insert([
            'class_name'    => 'Class Before Update',
            'academic_year' => '2021/2022',
            'wali_kelas_id' => $teacher1['id']
        ]);

        $updateData = [
            'class_name'    => 'Class After Update',
            'academic_year' => '2025/2026',
            'fase'          => 'F',
            'wali_kelas_id' => $teacher2['id'],
        ];

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/classes/update/{$classId}", $updateData);

        $result->assertRedirectTo('/admin/classes');
        $result->assertSessionHas('success', 'Class updated successfully.');
        $this->seeInDatabase('classes', ['id' => $classId, 'class_name' => 'Class After Update', 'wali_kelas_id' => $teacher2['id']]);
    }

    public function testDeleteClassSuccess()
    {
        $classId = $this->classModel->insert([
            'class_name'    => 'Class To Delete ' . uniqid(),
            'academic_year' => '2020/2021'
        ]);

        $result = $this->actingAs($this->adminUser)
                         ->post("/admin/classes/delete/{$classId}");

        $result->assertRedirectTo('/admin/classes');
        $result->assertSessionHas('success', 'Class deleted successfully.');
        $this->dontSeeInDatabase('classes', ['id' => $classId]);
    }
}
