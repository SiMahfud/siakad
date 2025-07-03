<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateP5ProjectTargetSubElementsTable extends Migration
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
            'p5_project_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'p5_sub_element_id' => [
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
        $this->forge->addForeignKey('p5_project_id', 'p5_projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('p5_sub_element_id', 'p5_sub_elements', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('p5_project_target_sub_elements');
    }

    public function down()
    {
        $this->forge->dropTable('p5_project_target_sub_elements');
    }
}
