<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
// Models are used by the seeders that might be called by tests extending this.
// It's okay to leave them here if the seeders are complex, or remove if seeders are self-contained.
// For now, the ensure... methods are removed, so these direct model uses might not be needed IN THIS FILE.
// However, test classes extending this WILL use models.
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\TeacherModel;
use CodeIgniter\Test\DatabaseTestTrait; // Add this for $this->seeInDatabase and $this->seed

class BaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait; // Use the trait here to make its methods available

    /**
     * Should the database be refreshed before each test?
     *
     * @var boolean
     */
    protected $refresh = true; // This will be inherited by child classes

    /**
     * The namespace(s) to look for migrations and seeders.
     * Set to null to use `APPNAMESPACE`.
     *
     * @var string|array|null
     */
    protected $namespace = 'App'; // This will be inherited

    // Common seeders that all or most tests might need.
    // Child classes can override or add to this if they use DatabaseTestTrait directly
    // and don't rely solely on this base setUp for seeding.
    protected $baseSeeders = [
        'App\Database\Seeds\RoleSeeder',
        'App\Database\Seeds\UserSeederForTests',
        'App\Database\Seeds\TeacherSeederForTests',
    ];

    protected function setUp(): void
    {
        parent::setUp(); // CIUnitTestCase setUp
                         // This will also trigger DatabaseTestTrait's setUp logic, which should
                         // handle $this->refresh and then $this->seeders (from the child class)
    }
}
