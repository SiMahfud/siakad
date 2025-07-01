<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'class_name' => [ // e.g., XI-1, X-A
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'academic_year' => [ // e.g., 2024/2025
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'null'       => false,
            ],
            'wali_kelas_id' => [ // FK to teachers.id
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true, // Wali kelas can be assigned later
            ],
            'fase' => [ // E or F
                'type'       => 'VARCHAR',
                'constraint' => '1',
                'null'       => true,
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
        $this->forge->addForeignKey('wali_kelas_id', 'teachers', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('classes');
    }

    public function down()
    {
        $this->forge->dropTable('classes');
    }
}
