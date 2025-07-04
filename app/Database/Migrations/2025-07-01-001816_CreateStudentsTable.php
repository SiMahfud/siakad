<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentsTable extends Migration
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
            'user_id' => [ // For student login
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true, // Student might not have a login initially
            ],
            'nisn' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true, // Per design, can be unique if not null
                'unique'     => true,
            ],
            'nis' => [ // Added NIS column
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true, // Can be unique if not null
                'unique'     => true,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            // ... other student data columns from document (e.g., address, phone) can be added here
            // For now, focusing on the specified ones.
            // 'address' => ['type' => 'TEXT', 'null' => true],
            // 'phone_number' => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'parent_user_id' => [ // For parent login
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true, // Parent might not have a login
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('parent_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('students');
    }

    public function down()
    {
        $this->forge->dropTable('students');
    }
}
