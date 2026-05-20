<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table = 'facilities';
    protected $primaryKey = 'id';
    protected $allowedFields = ['facility_key', 'name', 'icon', 'additional_hours_rate', 'extended_hour_rate', 'description', 'is_active', 'is_maintenance'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getFacilityWithPlans($facilityKey)
    {
        return $this->db->query("
            SELECT 
                f.*,
                p.id as plan_id,
                p.plan_key,
                p.name as plan_name,
                p.duration,
                p.price,
                pf.feature,
                pe.equipment_id,
                e.name as equipment_name,
                pe.quantity_included,
                e.unit,
                e.category as equipment_category
            FROM facilities f
            LEFT JOIN plans p ON f.id = p.facility_id
            LEFT JOIN plan_features pf ON p.id = pf.plan_id
            LEFT JOIN plan_equipment pe ON p.id = pe.plan_id
            LEFT JOIN equipment e ON pe.equipment_id = e.id
            WHERE f.facility_key = ?
            ORDER BY p.price ASC, pf.id ASC, e.name ASC
        ", [$facilityKey])->getResultArray();
    }

    public function getAllFacilitiesWithPlans()
    {
        return $this->db->query("
            SELECT 
                f.*,
                p.id as plan_id,
                p.plan_key,
                p.name as plan_name,
                p.duration,
                p.price,
                pf.feature,
                pe.equipment_id,
                e.name as equipment_name,
                pe.quantity_included,
                e.unit,
                e.category as equipment_category
            FROM facilities f
            LEFT JOIN plans p ON f.id = p.facility_id
            LEFT JOIN plan_features pf ON p.id = pf.plan_id
            LEFT JOIN plan_equipment pe ON p.id = pe.plan_id
            LEFT JOIN equipment e ON pe.equipment_id = e.id
            ORDER BY f.id ASC, p.price ASC, pf.id ASC, e.name ASC
        ")->getResultArray();
    }
}