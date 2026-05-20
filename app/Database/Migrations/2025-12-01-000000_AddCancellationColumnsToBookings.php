<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCancellationColumnsToBookings extends Migration
{
    public function up()
    {
        // Add cancellation_letter_path column if it doesn't exist
        $this->forge->addColumn('bookings', [
            'cancellation_letter_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '500',
                'null'       => true,
                'default'    => null,
                'comment'    => 'Path to the cancellation letter file',
                'after'      => 'decline_notes'
            ],
            'cancellation_requested_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'default'    => null,
                'comment'    => 'Timestamp when user requested cancellation',
                'after'      => 'cancellation_letter_path'
            ]
        ]);

        // Add 'pending_cancellation' to status enum if it doesn't exist
        // Note: This modifies the existing status column
        $this->db->query("ALTER TABLE `bookings` MODIFY `status` ENUM('pending','confirmed','cancelled','completed','pending_cancellation') DEFAULT 'pending'");
    }

    public function down()
    {
        // Drop the new columns if rolling back
        $this->forge->dropColumn('bookings', 'cancellation_letter_path');
        $this->forge->dropColumn('bookings', 'cancellation_requested_at');

        // Revert status enum
        $this->db->query("ALTER TABLE `bookings` MODIFY `status` ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending'");
    }
}
