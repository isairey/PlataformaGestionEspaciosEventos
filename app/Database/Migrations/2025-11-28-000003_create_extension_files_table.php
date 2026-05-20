<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExtensionFilesTable extends Migration
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
            'extension_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'FK to booking_extensions',
            ],
            'file_type' => [
                'type'       => 'ENUM',
                'constraint' => ['payment_receipt', 'payment_order', 'additional_document'],
                'comment'    => 'Type of file being uploaded',
            ],
            'original_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'comment'    => 'Original filename uploaded by user',
            ],
            'stored_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'comment'    => 'Filename stored on server',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '500',
                'comment'    => 'Full path to file',
            ],
            'file_size' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'File size in bytes',
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'MIME type of file',
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'User ID who uploaded file',
            ],
            'upload_date' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'When file was uploaded',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'deleted', 'archived'],
                'default'    => 'active',
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
        $this->forge->addKey('extension_id');
        $this->forge->addKey('file_type');
        $this->forge->addForeignKey('extension_id', 'booking_extensions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('extension_files');
    }

    public function down()
    {
        $this->forge->dropTable('extension_files');
    }
}
