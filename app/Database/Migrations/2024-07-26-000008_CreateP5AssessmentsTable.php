<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateP5AssessmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'p5_project_student_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'p5_sub_element_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'assessment_value' => [
                'type' => 'VARCHAR',
                'constraint' => '255', // e.g., "Belum Berkembang", "Mulai Berkembang", "Berkembang Sesuai Harapan", "Sangat Berkembang"
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'assessed_by' => [ // teacher_id
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
        $this->forge->addForeignKey('p5_project_student_id', 'p5_project_students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('p5_sub_element_id', 'p5_sub_elements', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assessed_by', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('p5_assessments');
    }

    public function down()
    {
        $this->forge->dropTable('p5_assessments');
    }
}
