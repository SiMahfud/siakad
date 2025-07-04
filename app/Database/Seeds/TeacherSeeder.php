<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\TeacherModel;
use App\Models\UserModel;

class TeacherSeeder extends Seeder
{
    public static $teacher1Id; // To store created teacher ID for other seeders
    public static $teacher2Id;

    public function run()
    {
        $teacherModel = new TeacherModel();
        $userModel = new UserModel();

        $teacherData = [
            [
                'username' => 'guru1',
                'full_name' => 'Guru Pertama',
                'nip' => 'G001'
            ],
            [
                'username' => 'guru2',
                'full_name' => 'Guru Kedua',
                'nip' => 'G002'
            ],
        ];

        foreach ($teacherData as $data) {
            $user = $userModel->where('username', $data['username'])->first();

            if (!$user) {
                // echo "User {$data['username']} not found. Skipping teacher creation for {$data['full_name']}.\n";
                continue;
            }

            // Check if teacher record already exists for this user_id
            $existingTeacher = $teacherModel->where('user_id', $user['id'])->first();
            if ($existingTeacher) {
                // echo "Teacher record for user {$data['username']} (User ID: {$user['id']}) already exists. Fetching ID. \n";
                if ($data['username'] === 'guru1') {
                    self::$teacher1Id = $existingTeacher['id'];
                } elseif ($data['username'] === 'guru2') {
                    self::$teacher2Id = $existingTeacher['id'];
                }
                continue;
            }

            $newTeacherData = [
                'user_id'   => $user['id'],
                'full_name' => $data['full_name'],
                'nip'       => $data['nip'],
                // Add other fields like gender, phone, address if they are required by model validation
                'gender'    => 'L', // Default, adjust as needed
            ];

            // Initialize Faker locally
            $faker = \Faker\Factory::create();
            $newTeacherData['phone'] = $faker->numerify('08##########');
            $newTeacherData['address'] = $faker->address;


            if ($teacherId = $teacherModel->insert($newTeacherData)) {
                // echo "Teacher {$data['full_name']} created successfully and linked to user {$data['username']}.\n";
                if ($data['username'] === 'guru1') {
                    self::$teacher1Id = $teacherId;
                } elseif ($data['username'] === 'guru2') {
                    self::$teacher2Id = $teacherId;
                }
            } else {
                // echo "Failed to create teacher {$data['full_name']}. Errors: " . implode(', ', $teacherModel->errors()) . "\n";
            }
        }
    }
}
