<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilityMaintenanceFields extends Migration
{
    public function up()
    {
        // Add new fields to facilities table
        $fields = [
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'icon'
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
                'after' => 'extended_hour_rate'
            ],
            'is_maintenance' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'after' => 'is_active'
            ]
        ];

        $this->forge->addColumn('facilities', $fields);
    }

    public function down()
    {
        // Remove the added fields
        $this->forge->dropColumn('facilities', ['description', 'is_active', 'is_maintenance']);
    }
}
