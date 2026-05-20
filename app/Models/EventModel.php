<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'booking_id',
        'facility_id',
        'event_title',
        'event_date',
        'event_time',
        'duration',
        'client_name',
        'contact_number',
        'email_address',
        'organization',
        'address',
        'attendees',
        'special_requirements',
        'total_cost',
        'status',
        'approval_notes',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'booking_id' => 'required|integer',
        'facility_id' => 'required|integer',
        'event_title' => 'required|string|max_length[255]',
        'event_date' => 'required|valid_date',
        'event_time' => 'required',
        'duration' => 'required|integer',
        'client_name' => 'required|string|max_length[255]',
        'status' => 'required|in_list[scheduled,ongoing,completed,cancelled]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Create event from approved booking
     */
 public function createFromBooking($booking, $notes = null)
{
    try {
        // Parse duration safely
        $duration = $booking['duration'] ?? 8;
        $durationValue = 8; // Default value
        
        if (is_numeric($duration)) {
            $durationValue = intval($duration);
        } elseif (is_string($duration)) {
            // Extract numbers from string (e.g., "8 hours" -> 8)
            if (preg_match('/(\d+)/', $duration, $matches)) {
                $durationValue = intval($matches[1]);
            }
        }
        
        // Validate duration
        if ($durationValue < 1 || $durationValue > 24) {
            $durationValue = 8;
        }

        // Get facility name
        $facilityModel = new \App\Models\FacilityModel();
        $facility = $facilityModel->find($booking['facility_id']);
        $facilityName = $facility ? $facility['name'] : 'Unknown Facility';

        // Prepare event data - match exactly with database columns
        $eventData = [
            'booking_id' => $booking['id'],
            'event_title' => $booking['event_title'],
            'client_name' => $booking['client_name'],
            'contact_number' => $booking['contact_number'],
            'email_address' => $booking['email_address'],
            'organization' => $booking['organization'] ?? null,
            'address' => $booking['address'] ?? null,
            'facility_id' => $booking['facility_id'],
            'facility_name' => $facilityName,
            'event_date' => $booking['event_date'],
            'event_time' => $booking['event_time'],
            'duration' => (string)$durationValue, // Cast to string to match VARCHAR column
            'attendees' => $booking['attendees'] ?? null,
            'total_cost' => $booking['total_cost'],
            'special_requirements' => $booking['special_requirements'] ?? null,
            'approval_notes' => $notes,
            'status' => 'scheduled',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert event record
        $eventId = $this->insert($eventData);
        
        if (!$eventId) {
            $errors = $this->errors();
            log_message('error', 'Failed to insert event record. Errors: ' . json_encode($errors));
            log_message('error', 'Event data attempted: ' . json_encode($eventData));
            return false;
        }

        log_message('info', 'Event created successfully from booking #' . $booking['id'] . ' with event ID: ' . $eventId);
        
        return $eventId;
        
    } catch (\Exception $e) {
        log_message('error', 'Exception in createFromBooking: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        return false;
    }
}
    /**
     * Check for scheduling conflicts
     */
    public function hasConflict($facilityId, $eventDate, $eventTime, $duration)
    {
        try {
            $startTime = new \DateTime($eventDate . ' ' . $eventTime);
            $endTime = clone $startTime;
            $endTime->add(new \DateInterval('PT' . $duration . 'H'));
            
            // Add 2-hour grace period to new booking
            $endTimeWithGrace = clone $endTime;
            $endTimeWithGrace->add(new \DateInterval('PT2H'));

            $conflicts = $this->where('facility_id', $facilityId)
                             ->where('event_date', $eventDate)
                             ->where('status !=', 'cancelled')
                             ->findAll();

            foreach ($conflicts as $event) {
                $existingStart = new \DateTime($event['event_date'] . ' ' . $event['event_time']);
                $existingEnd = clone $existingStart;
                $existingEnd->add(new \DateInterval('PT' . $event['duration'] . 'H'));
                
                // Add 2-hour grace period to existing booking
                $existingEndWithGrace = clone $existingEnd;
                $existingEndWithGrace->add(new \DateInterval('PT2H'));

                // Check for overlap with grace periods
                // Conflict if new event overlaps with existing event + grace period
                // OR existing event overlaps with new event + grace period
                if ($startTime < $existingEndWithGrace && $endTimeWithGrace > $existingStart) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            log_message('error', 'Error checking conflicts: ' . $e->getMessage());
            return true; // Return true to be safe
        }
    }

    /**
     * Get events with facility details
     */
    public function getEventsWithDetails()
    {
        return $this->select('events.*, facilities.name as facility_name, facilities.icon as facility_icon')
                    ->join('facilities', 'facilities.id = events.facility_id', 'left')
                    ->orderBy('events.event_date', 'DESC')
                    ->orderBy('events.event_time', 'DESC')
                    ->findAll();
    }

    /**
     * Get upcoming events
     */
public function getUpcomingEvents($limit = 10)
{
    // Make sure limit is an integer
    $limit = (int) $limit;
    
    return $this->select('events.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = events.facility_id', 'left')
                ->where('events.event_date >=', date('Y-m-d'))
                ->where('events.status', 'scheduled')
                ->orderBy('events.event_date', 'ASC')
                ->orderBy('events.event_time', 'ASC')
                ->limit($limit)
                ->findAll();
}

    /**
     * Get event statistics
     */
    public function getEventStatistics()
    {
        $total = $this->countAll();
        $scheduled = $this->where('status', 'scheduled')->countAllResults();
        $ongoing = $this->where('status', 'ongoing')->countAllResults();
        $completed = $this->where('status', 'completed')->countAllResults();
        $cancelled = $this->where('status', 'cancelled')->countAllResults();

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'ongoing' => $ongoing,
            'completed' => $completed,
            'cancelled' => $cancelled
        ];
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange($startDate, $endDate)
    {
        return $this->select('events.*, facilities.name as facility_name, facilities.icon as facility_icon')
                    ->join('facilities', 'facilities.id = events.facility_id', 'left')
                    ->where('events.event_date >=', $startDate)
                    ->where('events.event_date <=', $endDate)
                    ->orderBy('events.event_date', 'ASC')
                    ->orderBy('events.event_time', 'ASC')
                    ->findAll();
    }
}