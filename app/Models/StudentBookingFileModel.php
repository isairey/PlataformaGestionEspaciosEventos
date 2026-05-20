<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentBookingFileModel extends Model
{
    protected $table = 'student_booking_files';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'booking_id',
        'file_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'upload_date'
    ];
    protected $useTimestamps = false;
}
