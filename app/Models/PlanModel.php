<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $allowedFields = ['facility_id', 'plan_key', 'name', 'duration', 'price'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getPlanWithFeatures($planId)
    {
        return $this->db->query("
            SELECT 
                p.*,
                GROUP_CONCAT(pf.feature SEPARATOR '|') as features
            FROM plans p
            LEFT JOIN plan_features pf ON p.id = pf.plan_id
            WHERE p.id = ?
            GROUP BY p.id
        ", [$planId])->getRowArray();
    }
}