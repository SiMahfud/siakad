<?php namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RoleModel;
class UserSeederForTests extends Seeder {
    public function run() {
        $userModel = new UserModel(); $roleModel = new RoleModel();
        $roles = ['Administrator Sistem', 'Guru', 'Siswa', 'Orang Tua', 'TestUserRole'];
        $existingRoleIds = [];
        foreach($roles as $rName) {
            $role = $roleModel->where('role_name', $rName)->first();
            if(!$role) { $roleModel->insert(['role_name' => $rName]); $existingRoleIds[$rName] = $roleModel->getInsertID(); }
            else { $existingRoleIds[$rName] = $role['id']; }
        }
        $adminRoleId = $existingRoleIds['Administrator Sistem'];
        $guruRoleId = $existingRoleIds['Guru'];
        $usersData = [
            ['username' => 'admin_user_for_tests', 'password' => 'password123', 'full_name'=> 'Admin Test User', 'role_id'  => $adminRoleId, 'is_active'=> 1,],
            ['username' => 'teacher_user_for_teacher_seeder', 'password' => 'password123', 'full_name'=> 'Teacher User for Seeder', 'role_id'  => $guruRoleId, 'is_active'=> 1,],
            ['username' => 'another_teacher_user', 'password' => 'password123', 'full_name'=> 'Another Teacher User', 'role_id'  => $guruRoleId, 'is_active'=> 1,],
        ];
        foreach ($usersData as $userData) { if (!$userModel->where('username', $userData['username'])->first()) { $userModel->insert($userData); } }
    }
}