<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingAddonModel extends Model
{
    protected $table = 'booking_addons';
    protected $primaryKey = 'id';
    protected $allowedFields = ['booking_id', 'addon_id', 'price'];
    protected $useTimestamps = false;
}