<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;

class ClassControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $seed = 'UserRoleSeeder';
    protected $basePath = APPPATH . 'Database';
    protected $namespace = 'App';

    protected $adminUser;
    protected $guruUser;
    protected $testTeacher;
    protected $teacherModel;
    protected $classModel;

    protected function setUp(): void
    {
        parent::setUp();
        $userModel = new UserModel();
        $this->adminUser = $userModel->where('username', 'testadmin')->first();
        $this->guruUser = $userModel->where('username', 'testguru')->first();

        if (!$this->adminUser) {
            $this->fail("Test setup failed: 'testadmin' user not found. Ensure UserRoleSeeder ran correctly and creates this user.");
        }
        if (!$this->guruUser) {
            $this->fail("Test setup failed: 'testguru' user not found. Ensure UserRoleSeeder ran correctly and creates this user.");
        }

        $this->teacherModel = new \App\Models\TeacherModel();
        $this->testTeacher = $this->teacherModel->where('user_id', $this->guruUser['id'])->first();
        if (!$this->testTeacher) {
            $this->fail("Test setup failed: Teacher record for 'testguru' not found. Ensure UserRoleSeeder creates this teacher linked to testguru user.");
        }
        $this->classModel = new \App\Models\ClassModel();
    }

    public function testIndexAccessDeniedIfNotLoggedIn()
    {
        $this->withSession([]);
        $result = $this->get('/admin/classes');
        $result->assertRedirectTo(site_url('/login'));
    }

    public function testIndexAccessDeniedIfLoggedInAsGuru()
    {
        // Simulate login as the 'testguru' user
        $sessionData = [
            'user_id'    => $this->guruUser['id'],
            'username'   => $this->guruUser['username'],
            'role_id'    => $this->guruUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/classes');

        $result->assertRedirectTo(site_url('/unauthorized-access'));
    }

    public function testIndexAccessGrantedIfLoggedInAsAdmin()
    {
        // Simulate login as the 'testadmin' user
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/classes');

        // Remove debug output for admin test if it passes with new assertion
        // if ($result->response()->getStatusCode() !== 200 || strpos($result->getBody(), 'Manage Classes (Rombel)') === false) {
        //     fwrite(STDERR, "\nDEBUG ADMIN TEST: Unexpected response body:\n" . $result->getBody() . "\n");
        //     fwrite(STDERR, "DEBUG ADMIN TEST: Status: " . $result->response()->getStatusCode() . "\n");
        // }
        $result->assertStatus(200);
        $result->assertSee('Manage Classes (Rombel)'); // Changed assertion
    }

    // TODO: Tambahkan test case CRUD (create, store, edit, update, delete)

    public function testShowNewClassFormAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/classes/new');

        $result->assertStatus(200);
        $result->assertSee('Add New Class'); // From view title/header
        $result->assertSee('Class Name'); // Corrected Form label
        $result->assertSee('Wali Kelas'); // Form label
    }

    public function testCreateClassSuccessAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $classData = [
            'class_name'    => 'Test Class X-A',
            'academic_year' => '2025/2026',
            'fase'          => 'E',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];

        $result = $this->withSession($sessionData)
                         ->post('/admin/classes', $classData); // Corrected POST URL

        $result->assertRedirectTo(site_url('/admin/classes'));
        $result->assertSessionHas('success', 'Class added successfully.');

        // Check if the class was actually inserted into the database
        $this->seeInDatabase('classes', [
            'class_name'    => 'Test Class X-A',
            'academic_year' => '2025/2026',
            'wali_kelas_id' => $this->testTeacher['id']
        ]);
    }

    public function testCreateClassFailsWithValidationErrorsAsAdmin()
    {
        // Simplified for debugging syntax error
        // $this->assertTrue(true);
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $invalidClassData = [
            'class_name'    => '', // Invalid: class_name is required
            'academic_year' => '2025/2026',
            'fase'          => 'E',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];

        $result = $this->withSession($sessionData)
                         ->post('/admin/classes', $invalidClassData); // Using corrected POST URL

        $result->assertStatus(200); // Should return to the form
        $result->assertSee('Add New Class'); // Still on the new form

        // $result->assertSee('id="class_name" class="form-control is-invalid"');
        $result->assertSee('The class_name field is required.');


        // Ensure the class was not inserted into the database
        $this->dontSeeInDatabase('classes', [
            'academic_year' => '2025/2026', // Check against some unique data from the attempt
        ]);
        // $this->assertTrue(true); // Remove temporary assertion
    }

    public function testEditClassFormShowsCorrectDataAsAdmin()
    {
        // 1. Create a class first to edit
        $initialClassData = [
            'class_name'    => 'Edit Test Class XI-B',
            'academic_year' => '2024/2025',
            'fase'          => 'F',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];
        $insertedId = $this->classModel->insert($initialClassData);
        $this->assertIsNumeric($insertedId);

        // 2. Act as admin and fetch the edit form
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get("/admin/classes/{$insertedId}/edit"); // Corrected URL structure

        // 3. Assertions
        $result->assertStatus(200);
        $result->assertSee('Edit Class (Rombel)'); // Title of the edit page

        // Check if form fields contain the correct data
        $result->assertSee($initialClassData['class_name']); // Check for the class name text
        $result->assertSee($initialClassData['academic_year']); // Check for the academic year text
        $result->assertSee($initialClassData['fase']); // Check for the fase text

        // Check if the correct teacher's name is visible and part of the selected option context
        // This is a less strict check than parsing the DOM for the 'selected' attribute,
        // but less prone to DOMParser issues.
        $result->assertSee($this->testTeacher['full_name']);
        // A slightly more specific check for the selected option might be:
        // $result->assertSee('value="' . $this->testTeacher['id'] . '" selected'); // Removed the <option> tag for now
        // For now, let's ensure the teacher's name (who should be selected) is on the page.
        // The view renders: <option value="X" YYY selected ZZZ>TEACHER_NAME (NIP: ...)</option>
        // So, if testTeacher is selected, their name will be visible.
        // To be more precise about selection:
        $expectedSelectedOptionString = 'value="' . $this->testTeacher['id'] . '" selected';
        $result->assertSee($expectedSelectedOptionString);

    }

    public function testUpdateClassSuccessAsAdmin()
    {
        // 1. Create a class first
        $initialClassData = [
            'class_name'    => 'Update Test Class XII-C',
            'academic_year' => '2023/2024',
            'fase'          => 'F',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];
        $insertedId = $this->classModel->insert($initialClassData);
        $this->assertIsNumeric($insertedId);

        // 2. Data for update
        // For this test, let's find another teacher if available, or use the same one.
        // UserRoleSeeder creates 'testadmin' (role_id 1) and 'testguru' (role_id 4).
        // 'testguru' is $this->testTeacher.
        // Let's check if 'testadmin' also has a teacher record. If not, we might need to enhance UserRoleSeeder or create one.
        // For now, to ensure test robustness, let's use a different teacher if one exists, otherwise same.

        $otherTeacherToAssign = $this->teacherModel->where('id !=', $this->testTeacher['id'])->first();
        $newWaliKelasId = $otherTeacherToAssign ? $otherTeacherToAssign['id'] : $this->testTeacher['id']; // Fallback to same if no other

        $updatedClassData = [
            'class_name'    => 'Updated Class XII-C Alpha',
            'academic_year' => '2023/2024', // Keep same for this test, could change
            'fase'          => 'G', // Valid single character
            'wali_kelas_id' => $newWaliKelasId,
        ];

        // 3. Act as admin and perform update
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        // The form in `app/Views/admin/classes/edit.php` posts to `site_url('admin/classes/update/' . $class_item['id'])`
        // So, the test should also POST to this URL.
        $result = $this->withSession($sessionData)
                         ->post("/admin/classes/update/{$insertedId}", $updatedClassData);

        $result->assertRedirectTo(site_url('/admin/classes'));
        $result->assertSessionHas('success', 'Class updated successfully.');

        // Verify data in database
        $this->seeInDatabase('classes', array_merge(['id' => $insertedId], $updatedClassData));

        // Ensure old specific data that was changed is not present for this ID (e.g. old class_name if it was unique to this record)
        // More direct: check that the initial class_name for this ID is gone IF it was changed.
        if ($initialClassData['class_name'] !== $updatedClassData['class_name']) {
            $this->dontSeeInDatabase('classes', ['id' => $insertedId, 'class_name' => $initialClassData['class_name']]);
        }
        if ($initialClassData['fase'] !== $updatedClassData['fase']) {
            $this->dontSeeInDatabase('classes', ['id' => $insertedId, 'fase' => $initialClassData['fase']]);
        }
         if ($initialClassData['wali_kelas_id'] !== $updatedClassData['wali_kelas_id']) {
            $this->dontSeeInDatabase('classes', ['id' => $insertedId, 'wali_kelas_id' => $initialClassData['wali_kelas_id']]);
        }
    }

    public function testUpdateClassFailsWithValidationErrorsAsAdmin()
    {
        // 1. Create a class first
        $initialClassData = [
            'class_name'    => 'Validate Update Class XIII-D',
            'academic_year' => '2022/2023',
            'fase'          => 'F',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];
        $insertedId = $this->classModel->insert($initialClassData);
        $this->assertIsNumeric($insertedId);

        // 2. Data for update (invalid - empty class_name)
        $invalidUpdateData = [
            'class_name'    => '', // Invalid
            'academic_year' => '2022/2023',
            'fase'          => 'E', // Try to change fase
            'wali_kelas_id' => $this->testTeacher['id'],
        ];

        // 3. Act as admin and attempt update
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->post("/admin/classes/update/{$insertedId}", $invalidUpdateData);

        // 4. Assertions
        $result->assertStatus(200); // Should return to the edit form
        $result->assertSee('Edit Class (Rombel)'); // Still on the edit form

        // Check for validation error message
        $result->assertSee('The class_name field is required.');

        // Ensure the original data is still in the database and not the invalid attempted update
        $this->seeInDatabase('classes', ['id' => $insertedId, 'class_name' => $initialClassData['class_name']]);
        $this->dontSeeInDatabase('classes', ['id' => $insertedId, 'fase' => $invalidUpdateData['fase']]); // Ensure fase was not updated
    }

    public function testDeleteClassSuccessAsAdmin()
    {
        // 1. Create a class first
        $classData = [
            'class_name'    => 'Delete Test Class XIV-E',
            'academic_year' => '2021/2022',
            'fase'          => 'E',
            'wali_kelas_id' => $this->testTeacher['id'],
        ];
        $insertedId = $this->classModel->insert($classData);
        $this->assertIsNumeric($insertedId);
        $this->seeInDatabase('classes', ['id' => $insertedId]); // Confirm it's there

        // 2. Act as admin and perform delete
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        // Resource routes define DELETE verb for the delete action.
        // The view link uses GET: site_url('admin/classes/delete/' . $id). This might be an issue in the app's UI.
        // For testing the controller's delete method as per resource routing, we use delete().
        $result = $this->withSession($sessionData)
                         ->delete("/admin/classes/{$insertedId}");

        // 3. Assertions
        $result->assertRedirectTo(site_url('/admin/classes'));
        $result->assertSessionHas('success', 'Class deleted successfully.');

        $this->dontSeeInDatabase('classes', ['id' => $insertedId]);
    }
}
