<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'facility_id',
        'plan_id',
        'client_name',
        'contact_number',
        'email_address',
        'organization',
        'event_date',
        'address',
        'event_time',
        'event_end_time',
        'duration',
        'attendees',
        'event_title',
        'special_requirements',
        'total_cost',
        'status',
        'decline_reason',
        'decline_notes',
        'additional_hours',
        'total_duration_hours',
        'maintenance_fee',
        'booking_type',
        'cancellation_letter_path',
        'cancellation_requested_at',
        'approved_at',
        'approved_by',
        'approval_notes'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'facility_id' => 'required|integer',
        'plan_id' => 'required|integer',
        'client_name' => 'required|max_length[255]',
        'email_address' => 'required|valid_email',
        'event_date' => 'required|valid_date',
        'event_title' => 'required|max_length[255]',
        'total_cost' => 'required|decimal',
        'status' => 'required|in_list[pending,confirmed,cancelled]',
        'address' => 'permit_empty|min_length[10]|max_length[500]'
    ];

    protected $validationMessages = [
        'facility_id' => [
            'required' => 'Facility selection is required',
            'integer' => 'Invalid facility selected'
        ],
        'plan_id' => [
            'required' => 'Plan selection is required',
            'integer' => 'Invalid plan selected'
        ],
        'client_name' => [
            'required' => 'Client name is required',
            'max_length' => 'Client name cannot exceed 255 characters'
        ],
        'email_address' => [
            'required' => 'Email address is required',
            'valid_email' => 'Please enter a valid email address'
        ],
        'event_title' => [
            'required' => 'Event title is required',
            'max_length' => 'Event title cannot exceed 255 characters'
        ],
        'total_cost' => [
            'required' => 'Total cost is required',
            'decimal' => 'Total cost must be a valid amount'
        ],
    'address' => [
        'min_length' => 'Address must be at least 10 characters',
        'max_length' => 'Address cannot exceed 500 characters'
    ], 
    ];

    protected $beforeInsert = ['setDefaults'];
    protected $beforeUpdate = ['updateTimestamp'];

    protected function setDefaults(array $data)
    {
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        if (!isset($data['data']['maintenance_fee'])) {
            $data['data']['maintenance_fee'] = 2000.00;
        }

        return $data;
    }

    protected function updateTimestamp(array $data)
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    /**
     * Get bookings with facility and plan information
     */
    public function getBookingsWithDetails($limit = null, $offset = 0)
    {
        $query = $this->db->table('bookings b')
            ->select('b.*, 
                     f.name as facility_name, 
                     f.icon as facility_icon, 
                     p.name as plan_name, 
                     p.duration as plan_duration, 
                     b.booking_type,
                     (SELECT COUNT(*) FROM booking_files bf WHERE bf.booking_id = b.id) as user_files_count,
                     (SELECT COUNT(*) FROM student_booking_files sbf WHERE sbf.booking_id = b.id) as student_files_count,
                     CASE 
                        WHEN b.booking_type IN ("student", "employee") THEN (SELECT COUNT(*) FROM student_booking_files sbf WHERE sbf.booking_id = b.id)
                        ELSE (SELECT COUNT(*) FROM booking_files bf WHERE bf.booking_id = b.id)
                     END as files_count')
            ->join('facilities f', 'b.facility_id = f.id', 'left')
            ->join('plans p', 'b.plan_id = p.id', 'left')
            ->orderBy('b.created_at', 'DESC');

        if ($limit !== null) {
            $query->limit($limit, $offset);
        }

        $bookings = $query->get()->getResultArray();
        
        // Ensure booking_type and files_count are always set
        foreach ($bookings as &$booking) {
            if (!isset($booking['booking_type'])) {
                $booking['booking_type'] = 'user'; // Default to 'user' if not set
            }
            if (!isset($booking['files_count'])) {
                $booking['files_count'] = 0; // Default to 0 if not set
            }
            // Ensure status has a valid value (default to 'pending' if blank/null)
            if (empty($booking['status'])) {
                $booking['status'] = 'pending';
            }
        }
        
        return $bookings;
    }

    /**
     * Get booking with full details including addons and equipment
     */
public function getBookingWithFullDetails($bookingId)
{
    // Get basic booking details
    $booking = $this->select('
            bookings.*,
            facilities.name as facility_name,
            facilities.icon as facility_icon,
            facilities.additional_hours_rate as hourly_rate,
            plans.name as plan_name,
            plans.price as plan_price,
            plans.duration as plan_duration
        ')
        ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
        ->join('plans', 'plans.id = bookings.plan_id', 'left')
        ->where('bookings.id', $bookingId)
        ->first();

    if (!$booking) {
        return null;
    }

    // Ensure booking_type is set
    if (!isset($booking['booking_type'])) {
        $booking['booking_type'] = 'user'; // Default to 'user' if not set
    }

    // Calculate overtime fee if not already set
    if (!isset($booking['overtime_fee']) || $booking['overtime_fee'] === null) {
        $booking['overtime_fee'] = $this->calculateOvertimeFee(
            $booking['event_date'] ?? null,
            $booking['event_time'] ?? null
        );
    }

    // Get plan features
    $db = \Config\Database::connect();
    $planFeatures = $db->table('plan_features')
        ->select('feature')
        ->where('plan_id', $booking['plan_id'])
        ->get()
        ->getResultArray();

    $booking['plan_features'] = array_column($planFeatures, 'feature');

    // Get booking addons
    $addons = $db->table('booking_addons ba')
        ->select('a.name, ba.price')
        ->join('addons a', 'a.id = ba.addon_id')
        ->where('ba.booking_id', $bookingId)
        ->get()
        ->getResultArray();

    $booking['addons'] = $addons;

    // Get booking equipment
$equipment = $db->table('booking_equipment be')
    ->select('e.id, e.name, e.category, be.quantity, be.rate, be.total_cost, e.unit')
    ->join('equipment e', 'e.id = be.equipment_id')
    ->where('be.booking_id', $bookingId)
    ->orderBy('e.category', 'ASC')
    ->orderBy('e.name', 'ASC')
    ->get()
    ->getResultArray();

$booking['equipment'] = $equipment;;

    return $booking;
}

    /**
     * Get booking statistics
     */
    public function getBookingStatistics()
    {
        $stats = $this->db->table('bookings')
            ->select('status, COUNT(*) as count, SUM(total_cost) as total_revenue')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $result = [
            'pending' => ['count' => 0, 'revenue' => 0],
            'confirmed' => ['count' => 0, 'revenue' => 0],
            'cancelled' => ['count' => 0, 'revenue' => 0],
            'total' => ['count' => 0, 'revenue' => 0]
        ];

        foreach ($stats as $stat) {
            $result[$stat['status']] = [
                'count' => (int)$stat['count'],
                'revenue' => (float)$stat['total_revenue']
            ];
            $result['total']['count'] += (int)$stat['count'];
            $result['total']['revenue'] += (float)$stat['total_revenue'];
        }

        return $result;
    }

    /**
     * Search bookings with filters
     */
    public function searchBookings($filters = [])
    {
        $query = $this->db->table('bookings b')
            ->select('b.*, f.name as facility_name, f.icon as facility_icon, p.name as plan_name, p.duration as plan_duration')
            ->join('facilities f', 'b.facility_id = f.id', 'left')
            ->join('plans p', 'b.plan_id = p.id', 'left');

        // Apply search term
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->groupStart()
                ->like('b.client_name', $searchTerm)
                ->orLike('b.email_address', $searchTerm)
                ->orLike('b.event_title', $searchTerm)
                ->orLike('f.name', $searchTerm)
                ->groupEnd();
        }

        // Apply other filters
        if (!empty($filters['status'])) {
            $query->where('b.status', $filters['status']);
        }

        if (!empty($filters['facility_id'])) {
            $query->where('b.facility_id', $filters['facility_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('b.event_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('b.event_date <=', $filters['date_to']);
        }

        $query->orderBy('b.created_at', 'DESC');

        return $query->get()->getResultArray();
    }

   public function createBookingWithRelations($bookingData, $addons = [], $equipment = [])
{
    $db = \Config\Database::connect();
    $db->transStart();

    try {
        // Calculate overtime fee
        $overtimeFee = $this->calculateOvertimeFee(
            $bookingData['event_date'], 
            $bookingData['event_time']
        );
        $bookingData['overtime_fee'] = $overtimeFee;
        
        // Add overtime to total cost
        $bookingData['total_cost'] = (float)$bookingData['total_cost'] + $overtimeFee;

        // Validate booking data before insertion
        if (!$this->validate($bookingData)) {
            throw new \Exception('Booking validation failed: ' . json_encode($this->errors()));
        }

        // Insert main booking record
        $bookingId = $this->insert($bookingData);
        
        if (!$bookingId) {
            throw new \Exception('Failed to create booking record');
        }

        // Insert booking addons if any
        if (!empty($addons)) {
            $bookingAddonModel = new \App\Models\BookingAddonModel();
            foreach ($addons as $addon) {
                $addonData = [
                    'booking_id' => $bookingId,
                    'addon_id' => $addon['addon_id'],
                    'price' => $addon['price']
                ];
                
                if (!$bookingAddonModel->insert($addonData)) {
                    throw new \Exception('Failed to insert booking addon: ' . json_encode($bookingAddonModel->errors()));
                }
            }
        }

        // Insert booking equipment (date-based availability system)
        // Equipment is NOT reduced globally - availability is calculated per date
        if (!empty($equipment)) {
            $bookingEquipmentModel = new \App\Models\BookingEquipmentModel();
            $equipmentModel = new \App\Models\EquipmentModel();
            $eventDate = $bookingData['event_date'];

            foreach ($equipment as $equip) {
                // Verify equipment exists
                $equipData = $equipmentModel->find($equip['equipment_id']);
                if (!$equipData) {
                    throw new \Exception('Equipment not found: ID ' . $equip['equipment_id']);
                }

                // Calculate date-based availability for this equipment
                $bookedQuery = $db->table('booking_equipment be')
                    ->select('SUM(be.quantity) as booked_quantity')
                    ->join('bookings b', 'b.id = be.booking_id', 'inner')
                    ->where('be.equipment_id', $equip['equipment_id'])
                    ->where('b.event_date', $eventDate)
                    ->whereIn('b.status', ['pending', 'confirmed', 'approved'])
                    ->get();

                $bookedResult = $bookedQuery->getRowArray();
                $bookedQuantity = (int)($bookedResult['booked_quantity'] ?? 0);
                $availableForDate = $equipData['quantity'] - $bookedQuantity;

                // Validate availability for the requested date
                if ($availableForDate < $equip['quantity']) {
                    throw new \Exception("Insufficient {$equipData['name']} available on {$eventDate}. Requested: {$equip['quantity']}, Available: {$availableForDate}");
                }

                // Insert booking equipment record
                // The system will calculate availability by date dynamically
                if (!$bookingEquipmentModel->insert([
                    'booking_id' => $bookingId,
                    'equipment_id' => $equip['equipment_id'],
                    'quantity' => $equip['quantity'],
                    'rate' => $equip['rate'],
                    'total_cost' => $equip['total_cost']
                ])) {
                    throw new \Exception('Failed to insert booking equipment');
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \Exception('Transaction failed');
        }

        return $bookingId;

    } catch (\Exception $e) {
        $db->transRollback();
        log_message('error', 'Booking creation failed: ' . $e->getMessage());
        return false;
    }
}

    public function updateBookingStatus($bookingId, $status, $notes = null, $reason = null)
    {
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'cancelled' && $reason) {
            $updateData['decline_reason'] = $reason;
        }

        if ($notes) {
            $updateData['decline_notes'] = $notes;
        }

        return $this->update($bookingId, $updateData);
    }

    /**
     * Check if facility is available for given date and time
     * Prevents double booking and includes 2-hour facility preparation buffer
     * After each booking ends, facility needs 2 hours to be cleaned and setup for next event
     */
    public function checkFacilityAvailability($facilityId, $eventDate, $eventTime, $duration = 2, $excludeBookingId = null)
    {
        // Define facility preparation time in hours
        $FACILITY_PREP_TIME_HOURS = 2;
        
        $query = $this->db->table('bookings')
            ->where('facility_id', $facilityId)
            ->where('event_date', $eventDate)
            ->where('status !=', 'cancelled');

        if ($excludeBookingId) {
            $query->where('id !=', $excludeBookingId);
        }

        $existingBookings = $query->get()->getResultArray();

        foreach ($existingBookings as $booking) {
            // Convert time strings to timestamps for comparison
            $existingStart = strtotime($booking['event_time']);
            $existingEnd = strtotime($booking['event_time']) + ($booking['duration'] * 3600);
            
            // Add facility prep time buffer after existing booking
            $existingEndWithPrep = $existingEnd + ($FACILITY_PREP_TIME_HOURS * 3600);
            
            // Add facility prep time buffer before new booking (facility must be ready 2 hours before)
            $newStartWithPrep = strtotime($eventTime) - ($FACILITY_PREP_TIME_HOURS * 3600);
            $newStart = strtotime($eventTime);
            $newEnd = strtotime($eventTime) + ($duration * 3600);

            // Check for time overlap including prep time buffers
            // Condition 1: New booking starts before existing booking ends (with prep)
            // Condition 2: New booking ends after existing booking starts (with prep)
            if (($newStart < $existingEndWithPrep) && ($newEnd + ($FACILITY_PREP_TIME_HOURS * 3600) > $existingStart)) {
                return false; // Conflict found
            }
        }
        
        return true; // No conflicts
    }

public function cancelBookingWithEquipment($bookingId)
{
    // Date-based availability system: Equipment is not restored to inventory
    // Availability is calculated dynamically per date from booking_equipment table
    // When booking is cancelled, the booking_equipment records remain for historical tracking
    // but the booking status changes to 'cancelled' so they don't count toward availability

    return true; // No equipment restoration needed in date-based system
}
public function calculateOvertimeFee($eventDate, $eventTime)
{
    $overtimeFee = 0.00;
    
    // Validate inputs - return 0 if empty
    if (empty($eventDate) || empty($eventTime)) {
        return $overtimeFee;
    }
    
    try {
        $date = new \DateTime($eventDate);
        $time = new \DateTime($eventTime);
        
        // Check if weekend (Saturday = 6, Sunday = 0)
        $dayOfWeek = (int)$date->format('w');
        $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
        
        // Check if after 5PM (17:00)
        $hour = (int)$time->format('H');
        $isAfter5PM = ($hour >= 17);
        
        // Apply overtime fee for weekends or after 5PM
        if ($isWeekend || $isAfter5PM) {
            $overtimeFee = 5000.00;
        }
        
    } catch (\Exception $e) {
        log_message('error', 'Error calculating overtime fee: ' . $e->getMessage());
        return 0.00;
    }
    
    return $overtimeFee;
}

/**
 * Automatically update booking statuses based on event date
 * - Convert confirmed bookings to complete if event date is past
 * - Convert pending bookings to cancelled if event date is past
 * - Also fix any blank/null statuses
 */
public function autoUpdateExpiredBookings()
{
    try {
        $today = date('Y-m-d');
        
        // First, fix any blank or null statuses - default them to 'pending'
        $this->db->table('bookings')
            ->set('status', 'pending')
            ->where('status IS NULL')
            ->orWhere('status = ""')
            ->update();
        
        // Update confirmed bookings to complete if event date is past
        $this->db->table('bookings')
            ->set('status', 'complete')
            ->where('status', 'confirmed')
            ->where('event_date <', $today)
            ->update();
        
        // Update pending bookings to cancelled if event date is past
        $this->db->table('bookings')
            ->set('status', 'cancelled')
            ->where('status', 'pending')
            ->where('event_date <', $today)
            ->update();
        
        return true;
    } catch (\Exception $e) {
        log_message('error', 'Error auto-updating expired bookings: ' . $e->getMessage());
        return false;
    }
}

}
