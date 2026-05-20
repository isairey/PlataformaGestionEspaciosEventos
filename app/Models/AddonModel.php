<?php

namespace App\Models;

use CodeIgniter\Model;

class AddonModel extends Model
{
    protected $table = 'addons';
    protected $primaryKey = 'id';
    protected $allowedFields = ['addon_key', 'name', 'description', 'price'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}