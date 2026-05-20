<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRescheduleFields extends Migration
{
    public function up()
    {
        // Add reschedule-related fields to bookings table
        $this->forge->addColumn('bookings', [
            'reschedule_reason' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Reason for rescheduling the booking'
            ],
            'reschedule_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null,
                'comment' => 'Additional notes about the reschedule'
            ],
            'rescheduled_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
                'comment' => 'Timestamp when the booking was rescheduled'
            ],
        ]);
    }

    public function down()
    {
        // Remove the reschedule fields
        $this->forge->dropColumn('bookings', ['reschedule_reason', 'reschedule_notes', 'rescheduled_at']);
    }
}
