<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendancesTable extends Migration
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
            'schedule_id' => [ // References a specific teaching slot
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'attendance_date' => [ // The actual date of the attendance
                'type' => 'DATE',
            ],
            'status' => [ // 1: Hadir, 2: Sakit, 3: Izin, 4: Alfa
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'comment'    => '1:Hadir, 2:Sakit, 3:Izin, 4:Alfa',
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'recorded_by_user_id' => [ // User who recorded/last updated this attendance
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Nullable if system-generated or initial state
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
        $this->forge->addForeignKey('schedule_id', 'schedules', 'id', 'CASCADE', 'CASCADE'); // If schedule deleted, attendance records for it also deleted
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');   // If student deleted, their attendance records also deleted
        $this->forge->addForeignKey('recorded_by_user_id', 'users', 'id', 'SET NULL', 'SET NULL'); // If user deleted, set to NULL

        // Unique key to ensure one attendance record per student, per schedule slot, per date
        $this->forge->addUniqueKey(['schedule_id', 'student_id', 'attendance_date'], 'uq_student_attendance_slot_date');

        $this->forge->createTable('attendances');
    }

    public function down()
    {
        $this->forge->dropTable('attendances');
    }
}
