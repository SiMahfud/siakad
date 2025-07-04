<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\StudentModel;
use App\Models\UserModel;

class StudentSeeder extends Seeder
{
    public static $student1Id; // To store created student ID for other seeders

    public function run()
    {
        $studentModel = new StudentModel();
        $userModel = new UserModel();

        $studentUsername = 'siswa1';
        $parentUsername = 'ortu1';
        $studentFullName = 'Siswa Pertama';
        $studentNisn = 'S001';

        $studentUser = $userModel->where('username', $studentUsername)->first();
        $parentUser = $userModel->where('username', $parentUsername)->first();

        if (!$studentUser) {
            // echo "User {$studentUsername} (for student) not found. Skipping student creation.\n";
            return;
        }

        // Parent user is optional for a student, so proceed even if not found, but log it.
        if (!$parentUser) {
            // echo "User {$parentUsername} (for parent) not found. Student will be created without a linked parent account.\n";
        }

        $existingStudentByUserId = $studentModel->where('user_id', $studentUser['id'])->first();
        if ($existingStudentByUserId) {
            // echo "Student record for user {$studentUsername} (User ID: {$studentUser['id']}) already exists. Fetching ID.\n";
            self::$student1Id = $existingStudentByUserId['id'];
            return;
        }

        $existingStudentByNisn = $studentModel->where('nisn', $studentNisn)->first();
        if ($existingStudentByNisn) {
            // echo "Student record with NISN {$studentNisn} already exists. Fetching ID.\n";
            self::$student1Id = $existingStudentByNisn['id'];
            // Optionally, link this student to the studentUser if not already linked and user_id is null
            if ($existingStudentByNisn['user_id'] === null && $studentUser) {
                $studentModel->update($existingStudentByNisn['id'], ['user_id' => $studentUser['id'], 'parent_user_id' => $parentUser ? $parentUser['id'] : null]);
                // echo "Updated existing student {$studentNisn} with user account {$studentUsername} and parent {$parentUsername}.\n";
            }
            return;
        }

        $newStudentData = [
            'user_id'        => $studentUser['id'],
            'parent_user_id' => $parentUser ? $parentUser['id'] : null,
            'full_name'      => $studentFullName,
            'nisn'           => $studentNisn,
            'gender'         => 'L', // Default
            'join_date'      => date('Y-m-d'),
            // Add other fields as required by StudentModel validation rules
        ];

        // Initialize Faker locally
        $faker = \Faker\Factory::create();
        $newStudentData['nis'] = $faker->unique()->numerify('10####');
        $newStudentData['pob'] = $faker->city;
        $newStudentData['dob'] = $faker->date('Y-m-d', '2008-12-31'); // Students typically 15-18
        $newStudentData['address'] = $faker->address;


        if ($studentId = $studentModel->insert($newStudentData)) {
            // echo "Student {$studentFullName} created successfully, linked to user {$studentUsername} and parent user {$parentUsername}.\n";
            self::$student1Id = $studentId;
        } else {
            // echo "Failed to create student {$studentFullName}. Errors: " . implode(', ', $studentModel->errors()) . "\n";
        }
    }
}
