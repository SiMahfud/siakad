<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubjectOfferingsTable extends Migration
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
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'academic_year' => [
                'type'       => 'VARCHAR',
                'constraint' => '10', // e.g., 2023/2024
            ],
            'semester' => [ // 1: Ganjil, 2: Genap
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
            ],
            'max_quota' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Nullable if no quota limit
            ],
            'current_enrollment' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [ // For students to choose
                'type'       => 'BOOLEAN',
                'default'    => true,
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
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['subject_id', 'academic_year', 'semester'], 'uq_subject_offering_period');
        $this->forge->createTable('subject_offerings');
    }

    public function down()
    {
        $this->forge->dropTable('subject_offerings');
    }
}
