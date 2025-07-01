<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['role_name' => 'Administrator Sistem'],
            ['role_name' => 'Staf Tata Usaha'],
            ['role_name' => 'Kepala Sekolah'],
            ['role_name' => 'Guru'],
            ['role_name' => 'Siswa'],
            ['role_name' => 'Orang Tua'],
        ];

        // Using Query Builder
        $this->db->table('roles')->insertBatch($data);
    }
}
