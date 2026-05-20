<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventGuestsTable extends Migration
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
            'booking_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'guest_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'guest_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'guest_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'qr_code' => [
                'type' => 'VARCHAR',
                'constraint' => 12,
                'unique' => true,
            ],
            'qr_code_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'attended' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'attendance_time' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('booking_id');
        $this->forge->addKey('qr_code');
        $this->forge->addKey('guest_email');

        $this->forge->createTable('event_guests');

        // Add foreign key constraint
        $this->db->query('ALTER TABLE `event_guests` ADD CONSTRAINT `fk_event_guests_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE');
    }

    public function down()
    {
        $this->forge->dropTable('event_guests');
    }
}
