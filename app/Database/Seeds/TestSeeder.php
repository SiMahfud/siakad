<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        // Call other seeders needed for testing environment
        $this->call('RoleSeeder');
        $this->call('UserSeeder'); // Make sure this seeder creates users for roles
        $this->call('TeacherSeeder');
        $this->call('StudentSeeder');
        $this->call('SubjectSeeder');
        $this->call('ClassSeeder');
        $this->call('ClassStudentSeeder');
        $this->call('ScheduleSeeder'); // Added ScheduleSeeder
        // Add any other seeders that are prerequisites for tests
    }
}
