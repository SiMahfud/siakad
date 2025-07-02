<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "Starting database seeding...\n";

        $this->call('RoleSeeder');
        echo "RoleSeeder finished.\n";

        $this->call('UserSeeder');
        echo "UserSeeder finished.\n";

        $this->call('TeacherSeeder');
        echo "TeacherSeeder finished.\n";

        $this->call('StudentSeeder');
        echo "StudentSeeder finished.\n";

        $this->call('SubjectSeeder');
        echo "SubjectSeeder finished.\n";

        $this->call('ClassSeeder');
        echo "ClassSeeder finished.\n";

        $this->call('ClassStudentSeeder');
        echo "ClassStudentSeeder finished.\n";

        $this->call('TeacherClassSubjectAssignmentSeeder');
        echo "TeacherClassSubjectAssignmentSeeder finished.\n";

        // Call test data seeders if needed for specific testing environments, but not for default seeding.
        // if (ENVIRONMENT === 'testing') {
        //     $this->call('UserSeederForTests');
        //     $this->call('TeacherSeederForTests');
        // }

        echo "Database seeding completed.\n";
    }
}
