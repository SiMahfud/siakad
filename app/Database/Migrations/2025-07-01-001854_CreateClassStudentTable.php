<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassStudentTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'class_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            // You can add other fields if needed, e.g., enrollment_date
            // 'enrollment_date' => ['type' => 'DATE', 'null' => false],
        ]);
        $this->forge->addPrimaryKey(['class_id', 'student_id']);
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE'); // If a class is deleted, remove enrollments
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE'); // If a student is deleted, remove enrollments
        $this->forge->createTable('class_student');
    }

    public function down()
    {
        $this->forge->dropTable('class_student');
    }
}
