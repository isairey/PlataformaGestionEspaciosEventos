<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanFeaturesModel extends Model
{
    protected $table = 'plan_features';
    protected $primaryKey = 'id';
    protected $allowedFields = ['plan_id', 'feature', 'feature_type', 'is_physical', 'display_order'];
    protected $useTimestamps = false; // Table only has created_at, no updated_at
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
