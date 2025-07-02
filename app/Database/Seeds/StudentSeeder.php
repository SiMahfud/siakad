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
            echo "User {$studentUsername} (for student) not found. Skipping student creation.\n";
            return;
        }

        // Parent user is optional for a student, so proceed even if not found, but log it.
        if (!$parentUser) {
            echo "User {$parentUsername} (for parent) not found. Student will be created without a linked parent account.\n";
        }

        // Check if student record already exists for this user_id
        if ($studentModel->where('user_id', $studentUser['id'])->first()) {
            echo "Student record for user {$studentUsername} (User ID: {$studentUser['id']}) already exists. Fetching ID.\n";
            $existingStudent = $studentModel->where('user_id', $studentUser['id'])->first();
            self::$student1Id = $existingStudent['id'];
            return;
        }

        // Or check by NISN if NISN should be absolutely unique and is the primary identifier for a student
        if ($studentModel->where('nisn', $studentNisn)->first()) {
             echo "Student record with NISN {$studentNisn} already exists. Fetching ID.\n";
             $existingStudent = $studentModel->where('nisn', $studentNisn)->first();
             self::$student1Id = $existingStudent['id'];
            // Optionally, link this student to the studentUser if not already linked and user_id is null
            if ($existingStudent && $existingStudent['user_id'] === null && $studentUser) {
                $studentModel->update($existingStudent['id'], ['user_id' => $studentUser['id'], 'parent_user_id' => $parentUser ? $parentUser['id'] : null]);
                echo "Updated existing student {$studentNisn} with user account {$studentUsername} and parent {$parentUsername}.\n";
            }
             return;
        }


        $newStudentData = [
            'user_id'       => $studentUser['id'],
            'parent_user_id'=> $parentUser ? $parentUser['id'] : null,
            'full_name'     => $studentFullName,
            'nisn'          => $studentNisn,
        ];

        if ($studentId = $studentModel->insert($newStudentData)) {
            echo "Student {$studentFullName} created successfully, linked to user {$studentUsername} and parent user {$parentUsername}.\n";
            self::$student1Id = $studentId;
        } else {
            echo "Failed to create student {$studentFullName}. Errors: " . implode(', ', $studentModel->errors()) . "\n";
        }
    }
}
