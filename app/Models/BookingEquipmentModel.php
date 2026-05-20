<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingEquipmentModel extends Model
{
    protected $table = 'booking_equipment';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'booking_id',
        'equipment_id', 
        'quantity',
        'rate',
        'total_cost'
    ];
    
    protected $useTimestamps = false;
    
    protected $validationRules = [
        'booking_id' => 'required|integer',
        'equipment_id' => 'required|integer',
        'quantity' => 'required|integer|greater_than[0]',
        'rate' => 'required|numeric|greater_than_equal_to[0]',
        'total_cost' => 'required|numeric|greater_than_equal_to[0]'
    ];

    /**
     * Get equipment for a specific booking
     */
    public function getBookingEquipment($bookingId)
    {
        return $this->select('booking_equipment.*, equipment.name as equipment_name')
                   ->join('equipment', 'equipment.id = booking_equipment.equipment_id')
                   ->where('booking_id', $bookingId)
                   ->findAll();
    }

    /**
     * Delete equipment for a booking (used during booking cancellation)
     */
    public function deleteBookingEquipment($bookingId)
    {
        return $this->where('booking_id', $bookingId)->delete();
    }
}