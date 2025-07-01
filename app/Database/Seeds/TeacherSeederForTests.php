<?php namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
use App\Models\TeacherModel;
use App\Models\UserModel;
class TeacherSeederForTests extends Seeder {
    public function run() {
        $teacherModel = new TeacherModel(); $userModel = new UserModel();
        $user1 = $userModel->where('username', 'teacher_user_for_teacher_seeder')->first();
        if ($user1 && !$teacherModel->where('user_id', $user1['id'])->first()) {
            $teacherModel->insert([ 'full_name' => 'Wali Kelas Satu', 'nip' => 'nip_wali1_'.uniqid(), 'user_id' => $user1['id'] ]);
        }
        $user2 = $userModel->where('username', 'another_teacher_user')->first();
        if ($user2 && !$teacherModel->where('user_id', $user2['id'])->first()) {
             $teacherModel->insert([ 'full_name' => 'Wali Kelas Dua', 'nip' => 'nip_wali2_'.uniqid(), 'user_id' => $user2['id'] ]);
        }
    }
}