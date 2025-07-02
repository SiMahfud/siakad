<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentSubjectChoicesTable extends Migration
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
            'subject_offering_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'chosen_at' => [
                'type' => 'DATETIME',
                'null' => true, // Can be set when choice is made
            ],
            'status' => [ // 1: Chosen/Pending, 2: Approved, 3: Rejected (for more advanced workflow)
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '1:Chosen/Pending, 2:Approved, 3:Rejected',
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
        $this->forge->addForeignKey('subject_offering_id', 'subject_offerings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['student_id', 'subject_offering_id'], 'uq_student_subject_offering');
        $this->forge->createTable('student_subject_choices');
    }

    public function down()
    {
        $this->forge->dropTable('student_subject_choices');
    }
}
