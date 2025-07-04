<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyAttendancesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'class_id' => [ // Kelas siswa saat pencatatan, untuk referensi historis jika siswa pindah kelas
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'attendance_date' => [
                'type' => 'DATE',
            ],
            'status' => [ // 1: Hadir, 2: Sakit, 3: Izin, 4: Alfa
                'type'       => 'TINYINT',
                'constraint' => 1,
                'comment'    => '1:Hadir, 2:Sakit, 3:Izin, 4:Alfa',
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'recorded_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'NO ACTION'); // Atau SET NULL jika kelas dihapus
        $this->forge->addForeignKey('recorded_by_user_id', 'users', 'id', 'CASCADE', 'NO ACTION');
        $this->forge->addUniqueKey(['student_id', 'attendance_date']);
        $this->forge->createTable('daily_attendances');
    }

    public function down()
    {
        $this->forge->dropTable('daily_attendances');
    }
}
