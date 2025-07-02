<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();
        $roleModel = new RoleModel();

        // Define roles and their corresponding user data
        $usersData = [
            [
                'role_name' => 'Administrator Sistem',
                'username' => 'admin',
                'password' => 'password123',
                'full_name' => 'Admin SI-Akademik',
            ],
            [
                'role_name' => 'Staf Tata Usaha',
                'username' => 'staf',
                'password' => 'password123',
                'full_name' => 'Staf TU SI-Akademik',
            ],
            [
                'role_name' => 'Kepala Sekolah',
                'username' => 'kepsek',
                'password' => 'password123',
                'full_name' => 'Kepala Sekolah SI-Akademik',
            ],
            [
                'role_name' => 'Guru',
                'username' => 'guru1',
                'password' => 'password123',
                'full_name' => 'Guru Pertama',
            ],
            [
                'role_name' => 'Guru',
                'username' => 'guru2',
                'password' => 'password123',
                'full_name' => 'Guru Kedua',
            ],
            [
                'role_name' => 'Siswa',
                'username' => 'siswa1',
                'password' => 'password123',
                'full_name' => 'Siswa Pertama',
            ],
            [
                'role_name' => 'Orang Tua',
                'username' => 'ortu1',
                'password' => 'password123',
                'full_name' => 'Orang Tua Siswa Pertama',
            ],
        ];

        foreach ($usersData as $userData) {
            // Check if user already exists
            if ($userModel->where('username', $userData['username'])->first()) {
                echo "User {$userData['username']} already exists. Skipping.\n";
                continue;
            }

            // Get role_id
            $role = $roleModel->where('role_name', $userData['role_name'])->first();
            if (!$role) {
                echo "Role {$userData['role_name']} not found. Skipping user {$userData['username']}.\n";
                continue;
            }

            $userToInsert = [
                'username'  => $userData['username'],
                'password'  => $userData['password'], // UserModel's beforeInsert callback should hash this
                'full_name' => $userData['full_name'],
                'role_id'   => $role['id'],
                'is_active' => 1, // Default to active
            ];

            if ($userModel->insert($userToInsert)) {
                echo "User {$userData['username']} created successfully with role {$userData['role_name']}.\n";
            } else {
                echo "Failed to create user {$userData['username']}. Errors: " . implode(', ', $userModel->errors()) . "\n";
            }
        }
    }
}
