<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11, // Changed to 11 for potentially more assessments
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'class_id' => [ // To know which class this assessment was for (context)
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'teacher_id' => [ // Teacher who input the assessment
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'assessment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['FORMATIF', 'SUMATIF'],
                'null'       => false,
            ],
            'assessment_title' => [ // e.g., "Ulangan Bab 1: Vektor" or "Observasi Diskusi Kelompok"
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'score' => [ // Can be NULL for FORMATIF if it's descriptive
                'type'       => 'DECIMAL',
                'constraint' => '5,2', // Allows scores like 95.50
                'null'       => true,
            ],
            'description' => [ // For FORMATIF descriptive feedback
                'type' => 'TEXT',
                'null' => true,
            ],
            'assessment_date' => [
                'type' => 'DATE',
                'null' => true,
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
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('assessments');
    }

    public function down()
    {
        $this->forge->dropTable('assessments');
    }
}
