<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdditionalHoursRateToFacilities extends Migration
{
    public function up()
    {
        // Add additional_hours_rate field to facilities table
        $fields = [
            'additional_hours_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 500.00,
                'null' => false,
                'after' => 'icon'
            ]
        ];

        $this->forge->addColumn('facilities', $fields);
    }

    public function down()
    {
        // Remove the additional_hours_rate field
        $this->forge->dropColumn('facilities', 'additional_hours_rate');
    }
}
