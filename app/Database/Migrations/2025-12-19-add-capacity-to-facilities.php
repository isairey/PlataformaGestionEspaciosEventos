<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCapacityToFacilities extends Migration
{
    public function up()
    {
        // Add capacity field to facilities table
        $fields = [
            'capacity' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'icon',
                'comment' => 'Maximum number of people the facility can hold'
            ]
        ];

        $this->forge->addColumn('facilities', $fields);
    }

    public function down()
    {
        // Remove the capacity field
        $this->forge->dropColumn('facilities', ['capacity']);
    }
}
