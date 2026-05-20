<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanEquipmentModel extends Model
{
    protected $table = 'plan_equipment';
    protected $primaryKey = 'id';
    protected $allowedFields = ['plan_id', 'equipment_id', 'quantity_included', 'is_mandatory', 'additional_rate'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
