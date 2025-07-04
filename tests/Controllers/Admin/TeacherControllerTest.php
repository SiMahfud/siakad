<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\TeacherModel;
use App\Models\RoleModel;

class TeacherControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $refresh = true;
    protected $seed = 'UserRoleSeeder'; // This seeder creates testadmin, testguru users and a teacher record for testguru
    protected $basePath = APPPATH . 'Database';
    protected $namespace = 'App';

    protected $adminUser;
    protected $guruUser; // User with 'Guru' role (created by UserRoleSeeder)
    protected $siswaUser; // User with 'Siswa' role (created by UserRoleSeeder, for unauthorized access test)

    protected $userModel;
    protected $teacherModel;
    protected $roleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->teacherModel = new TeacherModel();
        $this->roleModel = new RoleModel();

        $this->adminUser = $this->userModel->where('username', 'testadmin')->first();
        $this->guruUser = $this->userModel->where('username', 'testguru')->first(); // This user should have 'Guru' role and a teacher record
        $this->siswaUser = $this->userModel->where('username', 'testsiswa')->first();


        if (!$this->adminUser) {
            $this->fail("Test setup failed: 'testadmin' user not found. Ensure UserRoleSeeder ran correctly.");
        }
        if (!$this->guruUser) {
            $this->fail("Test setup failed: 'testguru' user not found. Ensure UserRoleSeeder ran correctly.");
        }
        if (!$this->siswaUser) {
            $this->fail("Test setup failed: 'testsiswa' user not found. Ensure UserRoleSeeder ran correctly.");
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        }
    }

    // Test methods will be added here
    public function testIndexAccessDeniedIfNotLoggedIn()
    {
        $this->withSession([]);
        $result = $this->get('/admin/teachers');
        $result->assertRedirectTo(site_url('/login'));
    }

    public function testIndexAccessDeniedIfLoggedInAsUnauthorizedRole()
    {
        // testsiswa (Siswa role) is not in $dataIndukFilter for teachers
        $sessionData = [
            'user_id'    => $this->siswaUser['id'],
            'username'   => $this->siswaUser['username'],
            'role_id'    => $this->siswaUser['role_id'], // Role ID for 'Siswa'
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/teachers');
        $result->assertRedirectTo(site_url('/unauthorized-access'));
    }

    public function testIndexAccessGrantedIfLoggedInAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'], // Role ID for 'Administrator Sistem'
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/teachers');

        $result->assertStatus(200);
        // Controller Admin/TeacherController::index() sets $data['title'] = 'Manage Teachers';
        $result->assertSee('Manage Teachers');
    }

    public function testIndexPageDisplaysTeachersAsAdmin()
    {
        // UserRoleSeeder creates 'testguru' user and a teacher record for them.
        // We need to fetch this teacher's details to assert their presence.
        $testGuruTeacherRecord = $this->teacherModel->where('user_id', $this->guruUser['id'])->first();
        $this->assertNotNull($testGuruTeacherRecord, "Teacher record for 'testguru' should exist from seeder.");

        // Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/teachers');

        // Assertions
        $result->assertStatus(200);
        $result->assertSee('Manage Teachers'); // Page title
        $result->assertSee(esc($testGuruTeacherRecord['full_name']));
        $result->assertSee(esc($testGuruTeacherRecord['nip']));
    }

    public function testShowNewTeacherFormAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/teachers/new');

        $result->assertStatus(200);
        $result->assertSee('Add New Teacher'); // From view title
        $result->assertSee('Full Name:');
        $result->assertSee('NIP:');
        $result->assertSee('User ID');
    }

    public function testCreateTeacherSuccessAsAdmin()
    {
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $newTeacherUsername = 'newteacheruser_' . uniqid();
        $newTeacherNIP = 'NIP-' . uniqid();
        $newTeacherFullName = 'New Teacher ' . uniqid();

        // 1. Create a 'Guru' user first
        $guruRole = $this->roleModel->where('role_name', 'Guru')->first();
        $this->assertNotNull($guruRole, "Guru role not found.");

        $newUserData = [
            'username'         => $newTeacherUsername,
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'full_name'        => $newTeacherFullName, // User's full name
            'role_id'          => $guruRole['id'],
            'is_active'        => 1,
        ];
        $newUserId = $this->userModel->insert($newUserData);
        $this->assertIsNumeric($newUserId, "Failed to create user for teacher. Errors: " . print_r($this->userModel->errors(), true));

        // 2. Prepare teacher data, linking to the created user
        $teacherPostData = [
            'full_name'        => $newTeacherFullName, // Teacher's full name, can be same or different from user's
            'nip'              => $newTeacherNIP,
            'user_id'          => $newUserId, // Link to existing user
        ];

        $result = $this->withSession($adminSessionData)
                         ->post('/admin/teachers', $teacherPostData);

        $result->assertRedirectTo(site_url('/admin/teachers'));
        $result->assertSessionHas('success', 'Teacher added successfully.');

        // Verify teacher in database
        $this->seeInDatabase('teachers', [
            'nip'       => $newTeacherNIP,
            'full_name' => $teacherPostData['full_name'],
            'user_id'   => $newUserId,
        ]);

        // Verify the user exists (already asserted by user creation)
        $this->seeInDatabase('users', ['id' => $newUserId, 'username' => $newTeacherUsername]);
    }

    public function testCreateTeacherFailsWithValidationErrorsAsAdmin()
    {
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        // Prepare invalid teacher data (full_name is required)
        $invalidTeacherData = [
            'full_name'        => '', // Invalid
            'nip'              => 'NIP-FAIL-' . uniqid(), // Unique NIP for this attempt
            // Not attempting to link or create a user for this validation test
        ];

        $result = $this->withSession($adminSessionData)
                         ->post('/admin/teachers', $invalidTeacherData);

        $result->assertStatus(200); // Expecting form to re-render
        $result->assertSee('Add New Teacher');

        // TeacherModel validation rule for full_name: 'required|max_length[255]'
        // Expect "The full_name field is required."
        $result->assertSee('The full_name field is required.');

        $this->dontSeeInDatabase('teachers', ['nip' => $invalidTeacherData['nip']]);
    }

    public function testEditTeacherFormShowsCorrectDataAsAdmin()
    {
        // 1. Use the teacher record created for 'testguru' by UserRoleSeeder
        $teacherToEdit = $this->teacherModel->where('user_id', $this->guruUser['id'])->first();
        $this->assertNotNull($teacherToEdit, "Teacher record for 'testguru' must exist for this test.");
        $teacherId = $teacherToEdit['id'];

        // 2. Act as admin
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($adminSessionData)
                         ->get("/admin/teachers/{$teacherId}/edit");

        // 3. Assertions
        $result->assertStatus(200);
        // Controller Admin/TeacherController::edit() sets $data['title'] = 'Edit Teacher';
        $result->assertSee('Edit Teacher');

        // Check form fields are populated with existing data
        // View admin/teachers/edit.php uses old('field', $teacher['field'])
        $result->assertSee('value="' . $teacherToEdit['full_name'] . '"'); // Removed esc for value
        $result->assertSee('value="' . $teacherToEdit['nip'] . '"'); // Removed esc for value

        if (!empty($teacherToEdit['user_id'])) {
            $result->assertSee('name="user_id" id="user_id" value="' . $teacherToEdit['user_id'] . '"');
        } else {
            // If user_id is null or empty, the value attribute might be empty or just "value="
             $result->assertSee('name="user_id" id="user_id" value=""');
        }
    }

    public function testUpdateTeacherSuccessAsAdmin()
    {
        // 1. Use the teacher record for 'testguru'
        $teacherToUpdate = $this->teacherModel->where('user_id', $this->guruUser['id'])->first();
        $this->assertNotNull($teacherToUpdate, "Teacher record for 'testguru' must exist.");
        $teacherId = $teacherToUpdate['id'];
        $originalUserId = $teacherToUpdate['user_id']; // Keep original user_id

        // 2. Prepare updated data
        $updatedTeacherData = [
            'full_name' => 'Updated ' . $teacherToUpdate['full_name'],
            'nip'       => 'NIP-UPD-' . uniqid(),
            'user_id'   => $originalUserId, // Keep the same user link for this test
        ];

        // 3. Act as admin
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($adminSessionData)
                         ->post("/admin/teachers/update/{$teacherId}", $updatedTeacherData);

        // 4. Assertions
        $result->assertRedirectTo(site_url('/admin/teachers'));
        $result->assertSessionHas('success', 'Teacher updated successfully.');

        // Verify updated data in database
        $this->seeInDatabase('teachers', array_merge(['id' => $teacherId], $updatedTeacherData));

        // Verify old data is not present for changed fields
        if ($teacherToUpdate['full_name'] !== $updatedTeacherData['full_name']) {
            $this->dontSeeInDatabase('teachers', ['id' => $teacherId, 'full_name' => $teacherToUpdate['full_name']]);
        }
        if ($teacherToUpdate['nip'] !== $updatedTeacherData['nip']) {
            $this->dontSeeInDatabase('teachers', ['id' => $teacherId, 'nip' => $teacherToUpdate['nip']]);
        }
    }

    public function testUpdateTeacherFailsWithValidationErrorsAsAdmin()
    {
        // 1. Use the teacher record for 'testguru'
        $teacherToUpdate = $this->teacherModel->where('user_id', $this->guruUser['id'])->first();
        $this->assertNotNull($teacherToUpdate, "Teacher record for 'testguru' must exist.");
        $teacherId = $teacherToUpdate['id'];

        // 2. Prepare invalid update data (empty full_name)
        $invalidUpdateData = [
            'full_name' => '', // Invalid
            'nip'       => 'NIP-FAIL-UPD-' . uniqid(), // Attempt to change NIP
            'user_id'   => $teacherToUpdate['user_id'],
        ];

        // 3. Act as admin
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($adminSessionData)
                         ->post("/admin/teachers/update/{$teacherId}", $invalidUpdateData);

        // 4. Assertions
        $result->assertStatus(200); // Expect edit form to re-render
        $result->assertSee('Edit Teacher');
        $result->assertSee('The full_name field is required.');

        // Verify original data is still in the database
        $this->seeInDatabase('teachers', [
            'id'         => $teacherId,
            'full_name'  => $teacherToUpdate['full_name'], // Original name
            'nip'        => $teacherToUpdate['nip'],       // Original NIP
        ]);
    }

    public function testDeleteTeacherSuccessAsAdmin()
    {
        // 1. Create a teacher with a dedicated user account for this test
        $guruRole = $this->roleModel->where('role_name', 'Guru')->first();
        $this->assertNotNull($guruRole, "Guru role not found.");

        $deleteTeacherUsername = 'deleteteacheruser_' . uniqid();
        $deleteUserId = $this->userModel->insert([
            'username'         => $deleteTeacherUsername,
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'full_name'        => 'User for Deleted Teacher',
            'role_id'          => $guruRole['id'],
            'is_active'        => 1,
        ]);
        $this->assertIsNumeric($deleteUserId, "Failed to create user for teacher deletion test.");

        $teacherData = [
            'full_name' => 'Teacher To Be Deleted ' . uniqid(),
            'nip'       => 'NIP-DEL-' . uniqid(),
            'user_id'   => $deleteUserId,
        ];
        $teacherId = $this->teacherModel->insert($teacherData);
        $this->assertIsNumeric($teacherId, "Failed to create teacher for deletion test.");
        $this->seeInDatabase('teachers', ['id' => $teacherId]);
        $this->seeInDatabase('users', ['id' => $deleteUserId]);

        // 2. Act as admin
        $adminSessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        // Send DELETE request
        $result = $this->withSession($adminSessionData)
                         ->delete("/admin/teachers/{$teacherId}");

        // 3. Assertions
        $result->assertRedirectTo(site_url('/admin/teachers'));
        $result->assertSessionHas('success', 'Teacher deleted successfully.');

        $this->dontSeeInDatabase('teachers', ['id' => $teacherId]);

        // TODO: Verify associated user account handling. Currently, TeacherModel does not have a hook
        // to delete the associated user account. If this is desired behavior, this assertion should
        // be seeInDatabase. If user should be deleted/deactivated, model logic needs an update.
        $this->seeInDatabase('users', ['id' => $deleteUserId]); // For now, assert user still exists
    }
}
