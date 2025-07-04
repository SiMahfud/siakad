<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        $roleModel = new RoleModel();
        $userModel = new UserModel();

        // Define roles to ensure they exist
        $roles = [
            ['role_name' => 'Administrator Sistem'],
            ['role_name' => 'Guru'],
            ['role_name' => 'Siswa'],
            ['role_name' => 'Orang Tua'], // Added Orang Tua role
        ];

        foreach ($roles as $roleData) {
            $existingRole = $roleModel->where('role_name', $roleData['role_name'])->first();
            if (!$existingRole) {
                $roleModel->insert($roleData);
                // echo "Role '{$roleData['role_name']}' created.\n";
            } else {
                // echo "Role '{$roleData['role_name']}' already exists.\n";
            }
        }

        // Get role IDs
        $adminRole = $roleModel->where('role_name', 'Administrator Sistem')->first();
        $guruRole  = $roleModel->where('role_name', 'Guru')->first();

        // Define users
        $users = [
            [
                'username'  => 'testadmin',
                'password'  => 'password123', // Will be hashed by UserModel
                'password_confirm' => 'password123',
                'full_name' => 'Test Admin User',
                'role_id'   => $adminRole ? $adminRole['id'] : null,
                'is_active' => 1,
            ],
            [
                'username'  => 'testguru',
                'password'  => 'password123', // Will be hashed by UserModel
                'password_confirm' => 'password123',
                'full_name' => 'Test Guru User',
                'role_id'   => $guruRole ? $guruRole['id'] : null,
                'is_active' => 1,
            ],
        ];

        foreach ($users as $userData) {
            if ($userData['role_id'] === null) {
                // echo "Skipping user {$userData['username']} due to missing role ID.\n";
                continue;
            }
            // Check if user already exists
            $existingUser = $userModel->where('username', $userData['username'])->first();
            if (!$existingUser) {
                if ($userModel->insert($userData)) {
                    // echo "User '{$userData['username']}' created successfully.\n";
                } else {
                    // echo "Failed to create user '{$userData['username']}'. Errors: " . implode(', ', $userModel->errors()) . "\n";
                }
            } else {
                // echo "User '{$userData['username']}' already exists. Skipping.\n";
            }
        }

        // Additionally, create a Teacher record for 'testguru' user
        $guruUser = $userModel->where('username', 'testguru')->first();
        if ($guruUser) {
            $teacherModel = new \App\Models\TeacherModel();
            $existingTeacher = $teacherModel->where('user_id', $guruUser['id'])->first();
            if (!$existingTeacher) {
                $teacherData = [
                    'full_name' => $guruUser['full_name'] ?? 'Guru Test (Wali Kelas)',
                    'nip'       => 'NIP-GURU-' . substr(uniqid(), -6), // Ensure unique NIP
                    'user_id'   => $guruUser['id'],
                ];
                if ($teacherModel->insert($teacherData)) {
                    // echo "Teacher record for 'testguru' created.\n";
                } else {
                    // echo "Failed to create teacher record for 'testguru'. Errors: " . implode(', ', $teacherModel->errors()) . "\n";
                }
            }
        }

        // Add Siswa Test User and Ortu Test User
        $siswaRole = $roleModel->where('role_name', 'Siswa')->first();
        $ortuRole  = $roleModel->where('role_name', 'Orang Tua')->first(); // Assuming 'Orang Tua' role exists from RoleSeeder logic

        if ($siswaRole && !$userModel->where('username', 'testsiswa')->first()) {
            $userModel->insert([
                'username'  => 'testsiswa',
                'password'  => 'password123',
                'password_confirm' => 'password123',
                'full_name' => 'Siswa Test User',
                'role_id'   => $siswaRole['id'],
                'is_active' => 1,
            ]);
        }

        if ($ortuRole && !$userModel->where('username', 'testortu')->first()) {
            $userModel->insert([
                'username'  => 'testortu',
                'password'  => 'password123',
                'password_confirm' => 'password123',
                'full_name' => 'Ortu Test User',
                'role_id'   => $ortuRole['id'],
                'is_active' => 1,
            ]);
        }
    }
}
