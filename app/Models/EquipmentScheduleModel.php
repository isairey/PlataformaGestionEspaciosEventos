<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipmentScheduleModel extends Model
{
    protected $table = 'equipment_schedule';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'equipment_id',
        'event_date',
        'total_quantity',
        'booked_quantity',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get or create schedule for equipment on a specific date
     */
    public function getOrCreateSchedule($equipmentId, $eventDate)
    {
        $schedule = $this->where('equipment_id', $equipmentId)
                        ->where('event_date', $eventDate)
                        ->first();

        if (!$schedule) {
            // Get equipment total availability
            $equipmentModel = new EquipmentModel();
            $equipment = $equipmentModel->find($equipmentId);

            $this->insert([
                'equipment_id' => $equipmentId,
                'event_date' => $eventDate,
                'total_quantity' => $equipment['available'] ?? 0,
                'booked_quantity' => 0,
                // available_quantity is generated automatically (total_quantity - booked_quantity)
            ]);

            $schedule = $this->where('equipment_id', $equipmentId)
                            ->where('event_date', $eventDate)
                            ->first();
        }

        return $schedule;
    }

    /**
     * Update booked quantity and recalculate available
     */
    public function updateBookedQuantity($equipmentId, $eventDate, $bookedQty)
    {
        $schedule = $this->getOrCreateSchedule($equipmentId, $eventDate);

        // Only update booked_quantity; available_quantity is calculated automatically
        $this->update($schedule['id'], [
            'booked_quantity' => $bookedQty,
        ]);

        return $this->find($schedule['id']);
    }

    /**
     * Get availability for a date range
     */
    public function getAvailabilityForDateRange($equipmentId, $startDate, $endDate)
    {
        return $this->where('equipment_id', $equipmentId)
                   ->where('event_date >=', $startDate)
                   ->where('event_date <=', $endDate)
                   ->findAll();
    }

    /**
     * Get available quantity for a specific date
     */
    public function getAvailableQuantity($equipmentId, $eventDate)
    {
        $schedule = $this->where('equipment_id', $equipmentId)
                        ->where('event_date', $eventDate)
                        ->first();

        return $schedule['available_quantity'] ?? 0;
    }

    /**
     * Bulk update availability for multiple dates
     */
    public function bulkUpdateAvailability($equipmentId, $dates, $totalQuantity)
    {
        foreach ($dates as $date) {
            $this->getOrCreateSchedule($equipmentId, $date);
            
            $this->where('equipment_id', $equipmentId)
                 ->where('event_date', $date)
                 ->update(['total_quantity' => $totalQuantity]);
        }
    }
}
