<?php

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;
use App\Models\SubjectModel;
use App\Models\RoleModel;

class SubjectControllerTest extends CIUnitTestCase
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

    protected $userModel;
    protected $subjectModel;
    protected $roleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
        $this->subjectModel = new SubjectModel();
        $this->roleModel = new RoleModel();

        $this->adminUser = $this->userModel->where('username', 'testadmin')->first();
        $this->guruUser = $this->userModel->where('username', 'testguru')->first();

        if (!$this->adminUser) {
            $this->fail("Test setup failed: 'testadmin' user not found. Ensure UserRoleSeeder ran correctly.");
        }
        if (!$this->guruUser) {
            $this->fail("Test setup failed: 'testguru' user not found. Ensure UserRoleSeeder ran correctly.");
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
        $result = $this->get('/admin/subjects');
        $result->assertRedirectTo(site_url('/login'));
    }

    public function testIndexAccessDeniedIfLoggedInAsUnauthorizedRole()
    {
        // testguru (Guru role) is not in $dataIndukFilter for subjects
        $sessionData = [
            'user_id'    => $this->guruUser['id'],
            'username'   => $this->guruUser['username'],
            'role_id'    => $this->guruUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/subjects');
        $result->assertRedirectTo(site_url('/unauthorized-access'));
    }

    public function testIndexAccessGrantedIfLoggedInAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/subjects');

        $result->assertStatus(200);
        // Controller Admin/SubjectController::index() sets $data['title'] = 'Manage Subjects';
        $result->assertSee('Manage Subjects');
    }

    public function testIndexPageDisplaysSubjectsAsAdmin()
    {
        // 1. Create a sample subject
        $subjectData = [
            'subject_name' => 'Test Subject Physics ' . uniqid(),
            'subject_code' => 'PHYS-' . uniqid(),
            'is_pilihan'   => 0, // Explicitly set
        ];
        $subjectId = $this->subjectModel->insert($subjectData);
        $this->assertIsNumeric($subjectId, "Failed to create subject for display test. Errors: " . print_r($this->subjectModel->errors(), true));

        // 2. Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/subjects');

        // 3. Assertions
        $result->assertStatus(200);
        $result->assertSee('Manage Subjects'); // Page title
        $result->assertSee(esc($subjectData['subject_name']));
        $result->assertSee(esc($subjectData['subject_code']));
    }

    public function testShowNewSubjectFormAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get('/admin/subjects/new');

        $result->assertStatus(200);
        $result->assertSee('Add New Subject'); // From view title
        $result->assertSee('Subject Name:');
        $result->assertSee('Subject Code (Optional):');
        $result->assertSee('Subject Type:');
    }

    public function testCreateSubjectSuccessAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $subjectPostData = [
            'subject_name' => 'New Test Subject - Math ' . uniqid(),
            'subject_code' => 'MATH-' . uniqid(),
            'is_pilihan'   => '0', // 0 for Wajib (Core)
        ];

        $result = $this->withSession($sessionData)
                         ->post('/admin/subjects', $subjectPostData);

        if (!$result->isRedirect()) {
            fwrite(STDERR, "\nDEBUG CreateSubjectSuccess: Expected redirect, got status " . $result->getStatus() . "\n");
            fwrite(STDERR, "Body:\n" . $result->getBody() . "\n");
        }

        $result->assertRedirectTo(site_url('/admin/subjects'));
        // Controller uses "Subject added successfully."
        $result->assertSessionHas('success', 'Subject added successfully.');

        // Verify subject in database, converting 'is_pilihan' to boolean for DB check if necessary
        // The controller converts '0'/'1' string from form to boolean before insert.
        $dbCheckData = [
            'subject_name' => $subjectPostData['subject_name'],
            'subject_code' => $subjectPostData['subject_code'],
            'is_pilihan'   => ($subjectPostData['is_pilihan'] == '1') ? 1 : 0, // Expect integer 0 or 1 in DB
        ];
        $this->seeInDatabase('subjects', $dbCheckData);
    }

    public function testCreateSubjectFailsWithValidationErrorsAsAdmin()
    {
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $invalidSubjectData = [
            'subject_name' => '', // Invalid: subject_name is required
            'subject_code' => 'FAIL-' . uniqid(),
            'is_pilihan'   => '0',
        ];

        $result = $this->withSession($sessionData)
                         ->post('/admin/subjects', $invalidSubjectData);

        $result->assertStatus(200); // Expecting form to re-render
        $result->assertSee('Add New Subject');

        // SubjectModel rule for subject_name: 'required|min_length[3]|max_length[100]'
        // Expect "The subject_name field is required."
        $result->assertSee('The subject_name field is required.');

        $this->dontSeeInDatabase('subjects', ['subject_code' => $invalidSubjectData['subject_code']]);
    }

    public function testEditSubjectFormShowsCorrectDataAsAdmin()
    {
        // 1. Create a subject
        $initialSubjectData = [
            'subject_name' => 'Edit Test Subject - Chemistry ' . uniqid(),
            'subject_code' => 'CHEM-' . uniqid(),
            'is_pilihan'   => 1, // Pilihan (Elective)
        ];
        $subjectId = $this->subjectModel->insert($initialSubjectData);
        $this->assertIsNumeric($subjectId, "Failed to create subject for edit test.");

        // 2. Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->get("/admin/subjects/{$subjectId}/edit");

        // 3. Assertions
        $result->assertStatus(200);
        // Controller Admin/SubjectController::edit() sets $data['title'] = 'Edit Subject';
        $result->assertSee('Edit Subject');

        // Check form fields are populated with existing data
        // The view admin/subjects/edit.php uses old('field', $subject['field'])
        $expectedNameValue = 'value="' . $initialSubjectData['subject_name'] . '"';
        if (strpos($result->getBody(), $expectedNameValue) === false) {
            fwrite(STDERR, "\nDEBUG EditSubjectForm: Name value not found. Looking for: {$expectedNameValue}\n");
            // fwrite(STDERR, "Relevant HTML part for name: " . substr($result->getBody(), strpos($result->getBody(), 'id="subject_name"'), 200) . "\n");
        }
        $result->assertSee($expectedNameValue);

        $expectedCodeValue = 'value="' . $initialSubjectData['subject_code'] . '"';
        $result->assertSee($expectedCodeValue);

        // For select 'is_pilihan', check if the correct option is selected.
        // If $initialSubjectData['is_pilihan'] was 1, option value="1" should be selected.
        $expectedSelectedOption = '<option value="1" selected>'; // Since we set is_pilihan to 1
        $result->assertSee($expectedSelectedOption);
    }

    public function testUpdateSubjectSuccessAsAdmin()
    {
        // 1. Create an initial subject
        $initialSubjectData = [
            'subject_name' => 'Initial Subject Bio ' . uniqid(),
            'subject_code' => 'BIO-' . uniqid(),
            'is_pilihan'   => 0,
        ];
        $subjectId = $this->subjectModel->insert($initialSubjectData);
        $this->assertIsNumeric($subjectId);

        // 2. Prepare updated data
        $updatedSubjectData = [
            'subject_name' => 'Updated Subject Biology Advanced ' . uniqid(),
            'subject_code' => 'BIA-' . uniqid(), // Shortened prefix to ensure <= 20 chars
            'is_pilihan'   => '1', // Change to Pilihan, POST data is string '0' or '1'
        ];

        // 3. Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->post("/admin/subjects/update/{$subjectId}", $updatedSubjectData);

        // 4. Assertions
        if (!$result->isRedirect()) {
            fwrite(STDERR, "\nDEBUG UpdateSubjectSuccess: Expected redirect, got status " . $result->getStatus() . "\n");
            fwrite(STDERR, "Body:\n" . $result->getBody() . "\n");
        }
        $result->assertRedirectTo(site_url('/admin/subjects'));
        // Controller uses "Subject updated successfully."
        $result->assertSessionHas('success', 'Subject updated successfully.');

        // Verify updated data in database
        // Controller converts 'is_pilihan' from POST ('0'/'1') to int (0/1) for DB
        $dbCheckUpdatedData = [
            'id'           => $subjectId,
            'subject_name' => $updatedSubjectData['subject_name'],
            'subject_code' => $updatedSubjectData['subject_code'],
            'is_pilihan'   => ($updatedSubjectData['is_pilihan'] == '1') ? 1 : 0,
        ];
        $this->seeInDatabase('subjects', $dbCheckUpdatedData);

        // Verify old data is not present for changed fields
        if ($initialSubjectData['subject_name'] !== $updatedSubjectData['subject_name']) {
            $this->dontSeeInDatabase('subjects', ['id' => $subjectId, 'subject_name' => $initialSubjectData['subject_name']]);
        }
    }

    public function testUpdateSubjectFailsWithValidationErrorsAsAdmin()
    {
        // 1. Create an initial subject
        $initialSubjectData = [
            'subject_name' => 'Subject To Fail Update ' . uniqid(),
            'subject_code' => 'FAILUP-' . uniqid(),
            'is_pilihan'   => 0,
        ];
        $subjectId = $this->subjectModel->insert($initialSubjectData);
        $this->assertIsNumeric($subjectId);

        // 2. Prepare invalid update data (empty subject_name)
        $invalidUpdateData = [
            'subject_name' => '', // Invalid
            'subject_code' => 'FAILUP2-' . uniqid(), // Attempt to change code
            'is_pilihan'   => '1', // Attempt to change type
        ];

        // 3. Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        $result = $this->withSession($sessionData)
                         ->post("/admin/subjects/update/{$subjectId}", $invalidUpdateData);

        // 4. Assertions
        $result->assertStatus(200); // Expect edit form to re-render
        $result->assertSee('Edit Subject');
        $result->assertSee('The subject_name field is required.');

        // Verify original data is still in the database and not the invalid attempted update
        $this->seeInDatabase('subjects', [
            'id'             => $subjectId,
            'subject_name'   => $initialSubjectData['subject_name'],
            'subject_code'   => $initialSubjectData['subject_code'], // Ensure code wasn't updated
            'is_pilihan'     => $initialSubjectData['is_pilihan'],   // Ensure type wasn't updated
        ]);
    }

    public function testDeleteSubjectSuccessAsAdmin()
    {
        // 1. Create a subject to delete
        $subjectData = [
            'subject_name' => 'Subject To Be Deleted ' . uniqid(),
            'subject_code' => 'DEL-' . uniqid(),
            'is_pilihan'   => 0,
        ];
        $subjectId = $this->subjectModel->insert($subjectData);
        $this->assertIsNumeric($subjectId, "Failed to create subject for deletion test.");
        $this->seeInDatabase('subjects', ['id' => $subjectId]); // Confirm it exists

        // 2. Act as admin
        $sessionData = [
            'user_id'      => $this->adminUser['id'],
            'username'     => $this->adminUser['username'],
            'role_id'      => $this->adminUser['role_id'],
            'is_logged_in' => true,
        ];

        // Send DELETE request
        $result = $this->withSession($sessionData)
                         ->delete("/admin/subjects/{$subjectId}");

        // 3. Assertions
        $result->assertRedirectTo(site_url('/admin/subjects'));
        // Controller uses "Subject deleted successfully."
        $result->assertSessionHas('success', 'Subject deleted successfully.');

        $this->dontSeeInDatabase('subjects', ['id' => $subjectId]);
    }
}
