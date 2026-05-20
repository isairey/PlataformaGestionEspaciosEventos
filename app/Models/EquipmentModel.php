<?php

namespace App\Models;

use CodeIgniter\Model;

class EquipmentModel extends Model
{
    protected $table = 'equipment';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'name', 'quantity', 'price', 'rate', 'unit', 
        'good', 'damaged', 'available', 'rented'
    ];
    
    // Fix the timestamps issue
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at'; // Enable this if you have updated_at column
    
    // Add validation rules
    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'quantity' => 'required|integer|greater_than[0]',
        'price' => 'required|numeric|greater_than_equal_to[0]',
        'good' => 'required|integer|greater_than_equal_to[0]',
        'damaged' => 'required|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Equipment name is required',
            'min_length' => 'Equipment name must be at least 3 characters long'
        ],
        'quantity' => [
            'required' => 'Quantity is required',
            'integer' => 'Quantity must be a valid number',
            'greater_than' => 'Quantity must be greater than 0'
        ],
        'price' => [
            'required' => 'Price is required',
            'numeric' => 'Price must be a valid number',
            'greater_than_equal_to' => 'Price must be 0 or greater'
        ]
    ];

    public function getAvailableEquipment()
    {
        return $this->where('available >', 0)->findAll();
    }

    public function updateEquipmentQuantity($id, $rentedQuantity)
    {
        $equipment = $this->find($id);
        if ($equipment) {
            $newAvailable = $equipment['available'] - $rentedQuantity;
            $newRented = $equipment['rented'] + $rentedQuantity;
            
            return $this->update($id, [
                'available' => max(0, $newAvailable),
                'rented' => $newRented
            ]);
        }
        return false;
    }

    /**
     * Return rented equipment
     */
public function rentEquipment($equipmentId, $quantity)
{
    $equipment = $this->find($equipmentId);
    
    if (!$equipment || $equipment['available'] < $quantity) {
        return false;
    }
    
    $newAvailable = $equipment['available'] - $quantity;
    $newRented = $equipment['rented'] + $quantity;
    $status = $newAvailable > 0 ? 'available' : 'out_of_stock';
    
    return $this->update($equipmentId, [
        'available' => $newAvailable,
        'rented' => $newRented,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}


    /**
     * Get equipment with calculated fields
     */
    public function getEquipmentWithStatus()
    {
        $equipment = $this->findAll();
        
        foreach ($equipment as &$item) {
            $item['available'] = max(0, $item['good'] - ($item['rented'] ?? 0));
            $item['status'] = $this->calculateStatus($item);
        }
        
        return $equipment;
    }

    /**
     * Get equipment summary statistics
     */
    public function getEquipmentStats()
    {
        $equipment = $this->getEquipmentWithStatus();
        
        $stats = [
            'total_equipment' => count($equipment),
            'total_quantity' => 0,
            'total_value' => 0,
            'total_available' => 0,
            'total_rented' => 0,
            'total_damaged' => 0,
            'status_count' => [
                'good' => 0,
                'damaged' => 0,
                'maintenance' => 0,
                'rented' => 0
            ]
        ];

        foreach ($equipment as $item) {
            $stats['total_quantity'] += $item['quantity'];
            $stats['total_value'] += $item['price'];
            $stats['total_available'] += $item['available'];
            $stats['total_rented'] += $item['rented'] ?? 0;
            $stats['total_damaged'] += $item['damaged'];
            $stats['status_count'][$item['status']]++;
        }

        return $stats;
    }

    /**
     * Search equipment by name
     */
    public function searchEquipment($searchTerm, $status = null)
    {
        $builder = $this->builder();
        
        if (!empty($searchTerm)) {
            $builder->like('name', $searchTerm);
        }
        
        $equipment = $builder->get()->getResultArray();
        
        // Apply status filter and calculate fields
        $filteredEquipment = [];
        foreach ($equipment as $item) {
            $item['available'] = max(0, $item['good'] - ($item['rented'] ?? 0));
            $item['status'] = $this->calculateStatus($item);
            
            if (!$status || $item['status'] === $status) {
                $filteredEquipment[] = $item;
            }
        }
        
        return $filteredEquipment;
    }

    /**
     * Get low stock equipment (available quantity below threshold)
     */
    public function getLowStockEquipment($threshold = 5)
    {
        $equipment = $this->getEquipmentWithStatus();
        
        return array_filter($equipment, function($item) use ($threshold) {
            return $item['available'] <= $threshold && $item['available'] > 0;
        });
    }

    /**
     * Get out of stock equipment
     */
    public function getOutOfStockEquipment()
    {
        $equipment = $this->getEquipmentWithStatus();
        
        return array_filter($equipment, function($item) {
            return $item['available'] <= 0;
        });
    }

    /**
     * Calculate equipment status based on condition and availability
     */
    private function calculateStatus($equipment)
    {
        if ($equipment['damaged'] > 0) {
            return 'damaged';
        } elseif (($equipment['rented'] ?? 0) > 0) {
            return 'rented';
        } elseif ($equipment['good'] < $equipment['quantity']) {
            return 'maintenance';
        } else {
            return 'good';
        }
    }

    /**
     * Validate equipment quantities before save
     */
    public function validateQuantities($data)
    {
        $quantity = (int)($data['quantity'] ?? 0);
        $good = (int)($data['good'] ?? 0);
        $damaged = (int)($data['damaged'] ?? 0);
        
        if ($good + $damaged !== $quantity) {
            return [
                'valid' => false,
                'message' => 'Good condition + Damaged must equal Total Quantity'
            ];
        }
        
        if ($good < 0 || $damaged < 0 || $quantity < 0) {
            return [
                'valid' => false,
                'message' => 'Quantities cannot be negative'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Override insert to validate quantities
     */
    public function insert($data = null, bool $returnID = true)
    {
        $validation = $this->validateQuantities($data);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }
        
        return parent::insert($data, $returnID);
    }

    /**
     * Override update to validate quantities
     */
    public function update($id = null, $data = null): bool
    {
        if (is_array($data) && (isset($data['quantity']) || isset($data['good']) || isset($data['damaged']))) {
            // Get current data to merge with updates
            $current = $this->find($id);
            if ($current) {
                $mergedData = array_merge($current, $data);
                $validation = $this->validateQuantities($mergedData);
                if (!$validation['valid']) {
                    throw new \InvalidArgumentException($validation['message']);
                }
            }
        }
        
        return parent::update($id, $data);
    }


 public function returnEquipment($equipmentId, $quantity)
{
    $equipment = $this->find($equipmentId);
    
    if (!$equipment) {
        return false;
    }
    
    $newAvailable = $equipment['available'] + $quantity;
    $newRented = max(0, $equipment['rented'] - $quantity);
    $status = $newAvailable > 0 ? 'available' : 'out_of_stock';
    
    return $this->update($equipmentId, [
        'available' => $newAvailable,
        'rented' => $newRented,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}


 
public function getEquipmentForBooking()
{
    return $this->select('
            id,
            name,
            category,
            quantity,
            price,
            rate,
            unit,
            available,
            rented,
            status,
            good,
            is_rentable,
            is_trackable,
            is_plan_includable
        ')
        ->where('good >', 0)
        ->orderBy('category', 'ASC')
        ->orderBy('name', 'ASC')
        ->findAll();
}

public function getRentableEquipment()
{
    return $this->where('is_rentable', 1)
                ->where('rate >', 0)
                ->where('available >', 0)
                ->orderBy('name', 'ASC')
                ->findAll();
}

/**
 * Get equipment available for a specific date and facility
 * Checks existing bookings and returns available quantities
 */
public function getEquipmentAvailableForDate($eventDate, $facilityId = null)
{
    $db = \Config\Database::connect();

    // Get all rentable equipment with base quantities
    $equipment = $this->select('
            id,
            name,
            category,
            price,
            rate,
            unit,
            available,
            good,
            is_rentable
        ')
        ->where('good >', 0)
        ->orderBy('category', 'ASC')
        ->orderBy('name', 'ASC')
        ->findAll();

    // For each equipment, calculate how much is already booked for this date
    foreach ($equipment as &$item) {
        // Query to get total quantity booked for this equipment on this date
        // NOTE: We check ALL facilities because equipment is shared globally across all facilities
        $builder = $db->table('booking_equipment be');
        $builder->select('SUM(be.quantity) as booked_quantity')
                ->join('bookings b', 'b.id = be.booking_id')
                ->where('be.equipment_id', $item['id'])
                ->where('b.event_date', $eventDate)
                ->where('b.status !=', 'cancelled');

        // DO NOT filter by facility - equipment is shared across all facilities
        // If 5 chairs are booked for Auditorium on March 1, those same 5 chairs
        // should not be available for Gymnasium on March 1

        $result = $builder->get()->getRowArray();
        $bookedQty = $result['booked_quantity'] ?? 0;

        // Calculate available quantity for this date
        $item['available_on_date'] = max(0, $item['good'] - $bookedQty);
        $item['booked_quantity'] = $bookedQty;
    }

    return $equipment;
}
}

