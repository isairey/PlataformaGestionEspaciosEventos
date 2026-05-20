<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingExtensionsTable extends Migration
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
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'extension_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Number of hours to extend',
            ],
            'extension_cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'comment'    => 'Cost of extension based on hourly rate',
            ],
            'extension_reason' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Reason for extension request',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'completed'],
                'default'    => 'pending',
                'comment'    => 'Status of extension request',
            ],
            'requested_by' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Name of user who requested extension',
            ],
            'requested_by_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID of requester',
            ],
            'requested_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'When extension was requested',
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID of admin who approved',
            ],
            'approved_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'When extension was approved',
            ],
            'payment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'received', 'failed'],
                'default'    => 'pending',
                'comment'    => 'Payment status for extension',
            ],
            'payment_order_generated' => [
                'type'    => 'BOOLEAN',
                'default' => false,
                'comment' => 'Whether payment order has been generated',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('requested_by_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('booking_extensions');
    }

    public function down()
    {
        $this->forge->dropTable('booking_extensions');
    }
}
