<?php

namespace Tests\Models;

use App\Models\RoleModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class RoleModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait; // Provides migration and seeder features for tests

    protected $namespace   = 'App'; // Specify the namespace for migrations
    protected $refresh     = true; // Refresh database for each test class
    // protected $migrate     = true; // Automatically run migrations - redundant if refresh is true
    // protected $migrateOnce = false; // Run migrations for each test method for isolation - redundant if refresh is true
    protected $seed        = 'App\Database\Seeds\RoleSeeder'; // Use FQCN
    // Or use $seedOnce = true; and $this->seeInDatabase('roles', ['role_name' => 'Administrator Sistem']);
    // For this simple model, seeding per method is fine.

    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new RoleModel();
    }

    public function testFindAllRoles()
    {
        $roles = $this->model->findAll();

        // We expect 6 roles from RoleSeeder
        $this->assertCount(6, $roles);

        // Check if a specific known role exists
        $this->assertTrue(in_array('Administrator Sistem', array_column($roles, 'role_name')));
        $this->assertTrue(in_array('Siswa', array_column($roles, 'role_name')));
    }

    public function testRoleNameIsRequired()
    {
        $data = ['role_name' => ''];
        $this->assertFalse($this->model->insert($data));

        $errors = $this->model->errors();
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertStringContainsStringIgnoringCase('The role_name field is required.', $errors['role_name']);
    }

    public function testRoleNameMinLength()
    {
        $data = ['role_name' => 'Ad']; // Too short
        $this->assertFalse($this->model->insert($data));
        $errors = $this->model->errors();
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertStringContainsStringIgnoringCase('must be at least 3 characters in length', $errors['role_name']);
    }

    public function testRoleNameIsUnique()
    {
        // RoleSeeder already added 'Guru'
        $data = ['role_name' => 'Guru'];
        $this->assertFalse($this->model->insert($data));

        $errors = $this->model->errors();
        $this->assertArrayHasKey('role_name', $errors);
        $this->assertStringContainsStringIgnoringCase('This role name already exists.', $errors['role_name']);
    }

    public function testCreateValidRole()
    {
        $data = ['role_name' => 'New Test Role'];
        $result = $this->model->insert($data);

        $this->assertIsNumeric($result); // insert() returns the new ID
        $this->seeInDatabase('roles', ['role_name' => 'New Test Role']);
    }

    public function testUpdateRole()
    {
        // Get one of the seeded roles, e.g., 'Siswa'
        $role = $this->model->where('role_name', 'Siswa')->first();
        $this->assertNotNull($role);

        $updatedName = 'Siswa Updated';
        $this->model->update($role['id'], ['role_name' => $updatedName]);
        $this->seeInDatabase('roles', ['id' => $role['id'], 'role_name' => $updatedName]);
    }

    public function testDeleteRole()
    {
        $role = $this->model->where('role_name', 'Orang Tua')->first();
        $this->assertNotNull($role);

        $this->model->delete($role['id']);
        $this->dontSeeInDatabase('roles', ['id' => $role['id']]);
    }
}
