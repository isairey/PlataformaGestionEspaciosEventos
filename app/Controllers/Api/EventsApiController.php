<?php

namespace App\Controllers\Api;

use App\Models\EventModel;
use App\Models\BookingModel;
use App\Models\FacilityModel;
use CodeIgniter\RESTful\ResourceController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EventsApiController extends ResourceController
{
    protected $eventModel;
    protected $bookingModel;
    protected $facilityModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->bookingModel = new BookingModel();
        $this->facilityModel = new FacilityModel();
    }

    protected function setCorsHeaders()
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

    /**
     * Get calendar events with date range
     */
    public function getCalendarEvents()
    {

            $this->setCorsHeaders();
        try {
            $startDate = $this->request->getGet('start');
            $endDate = $this->request->getGet('end');
            $facilityFilter = $this->request->getGet('facility');
            $statusFilter = $this->request->getGet('status');
            $dateRangeFilter = $this->request->getGet('dateRange');
            $dateFrom = $this->request->getGet('dateFrom');
            $dateTo = $this->request->getGet('dateTo');

            $db = \Config\Database::connect();
            $builder = $db->table('bookings b')
                ->select('b.*, f.name as facility_name, f.icon as facility_icon')
                ->join('facilities f', 'f.id = b.facility_id', 'left');

            // Apply date range filter from calendar view
            if ($startDate && $endDate) {
                $builder->where('b.event_date >=', $startDate);
                $builder->where('b.event_date <=', $endDate);
            }

            // Apply custom date from filter
            if ($dateFrom) {
                $builder->where('b.event_date >=', $dateFrom);
            }

            // Apply custom date to filter
            if ($dateTo) {
                $builder->where('b.event_date <=', $dateTo);
            }

            // Apply facility filter
            if ($facilityFilter) {
                $builder->where('b.facility_id', $facilityFilter);
            }

            // Apply status filter - if not provided, show pending and confirmed
            if ($statusFilter) {
                $builder->where('b.status', $statusFilter);
            } else {
                // Default: show pending, confirmed, and completed events
                $builder->whereIn('b.status', ['pending', 'confirmed', 'completed']);
            }

            // Apply date range preset filter
            if ($dateRangeFilter) {
                $today = date('Y-m-d');
                switch ($dateRangeFilter) {
                    case 'today':
                        $builder->where('b.event_date', $today);
                        break;
                    case 'week':
                        $weekStart = date('Y-m-d', strtotime('monday this week'));
                        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                        $builder->where('b.event_date >=', $weekStart);
                        $builder->where('b.event_date <=', $weekEnd);
                        break;
                    case 'month':
                        $monthStart = date('Y-m-01');
                        $monthEnd = date('Y-m-t');
                        $builder->where('b.event_date >=', $monthStart);
                        $builder->where('b.event_date <=', $monthEnd);
                        break;
                    case 'upcoming':
                        $builder->where('b.event_date >=', $today);
                        break;
                }
            }

            $bookings = $builder->orderBy('b.event_date', 'ASC')
                               ->orderBy('b.event_time', 'ASC')
                               ->get()
                               ->getResultArray();

            // Format events for FullCalendar
$events = array_map(function($booking) {
    $status = strtolower($booking['status']);
    return [
        'id' => $booking['id'],
        'title' => $booking['event_title'],
        'start' => $booking['event_date'] . 'T' . $booking['event_time'],
        'end' => $this->calculateEndTime($booking['event_date'], $booking['event_time'], $booking['duration']),
        'className' => 'fc-event-' . $status,
        'backgroundColor' => $this->getStatusColor($status),
        'borderColor' => $this->getStatusColor($status),
        'status' => $booking['status'],
        'facility' => $booking['facility_name'],
        'facilityIcon' => $booking['facility_icon'],
        'organizer' => $booking['organization'] ?: $booking['client_name'],
        'description' => $booking['special_requirements'],
        'attendees' => $booking['attendees'],
        'clientName' => $booking['client_name'],
        'contactNumber' => $booking['contact_number'],
        'emailAddress' => $booking['email_address'],
        'totalCost' => $booking['total_cost'],
        'bookingType' => $booking['booking_type']
    ];
}, $bookings);

            return $this->response->setJSON([
                'success' => true,
                'events' => $events
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching calendar events: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch calendar events'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get event statistics
     */
    public function getEventStats()
    {
        try {
            $db = \Config\Database::connect();
            
            // Get counts by status
            $stats = [
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];

            $statusCounts = $db->table('bookings')
                ->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->getResultArray();

            foreach ($statusCounts as $row) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = (int)$row['count'];
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching event stats: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get events list with filters
     */
    public function getEventsList()
    {
        try {
            $facilityFilter = $this->request->getGet('facility');
            $statusFilter = $this->request->getGet('status');
            $dateRangeFilter = $this->request->getGet('dateRange');

            $db = \Config\Database::connect();
            $builder = $db->table('bookings b')
                ->select('b.*, f.name as facility_name, f.icon as facility_icon')
                ->join('facilities f', 'f.id = b.facility_id', 'left');

            // Apply filters
            if ($facilityFilter) {
                $builder->where('b.facility_id', $facilityFilter);
            }

            if ($statusFilter) {
                $builder->where('b.status', $statusFilter);
            }

            // Apply date range filter
            if ($dateRangeFilter) {
                $today = date('Y-m-d');
                switch ($dateRangeFilter) {
                    case 'today':
                        $builder->where('b.event_date', $today);
                        break;
                    case 'week':
                        $weekStart = date('Y-m-d', strtotime('monday this week'));
                        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                        $builder->where('b.event_date >=', $weekStart);
                        $builder->where('b.event_date <=', $weekEnd);
                        break;
                    case 'month':
                        $monthStart = date('Y-m-01');
                        $monthEnd = date('Y-m-t');
                        $builder->where('b.event_date >=', $monthStart);
                        $builder->where('b.event_date <=', $monthEnd);
                        break;
                    case 'upcoming':
                        $builder->where('b.event_date >=', $today);
                        break;
                }
            }

            $events = $builder->orderBy('b.event_date', 'DESC')
                             ->orderBy('b.event_time', 'DESC')
                             ->limit(50) // Limit to 50 most recent
                             ->get()
                             ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'events' => $events
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching events list: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch events list'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get single event details
     */
    public function getEventDetails($eventId)
    {
        try {
            $db = \Config\Database::connect();
            
            $event = $db->table('bookings b')
                ->select('b.*, f.name as facility_name, f.icon as facility_icon, p.name as plan_name')
                ->join('facilities f', 'f.id = b.facility_id', 'left')
                ->join('plans p', 'p.id = b.plan_id', 'left')
                ->where('b.id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get equipment
            $equipment = $db->table('booking_equipment be')
                ->select('e.name, be.quantity, be.rate, be.total_cost')
                ->join('equipment e', 'e.id = be.equipment_id')
                ->where('be.booking_id', $eventId)
                ->get()
                ->getResultArray();

            $event['equipment'] = $equipment;

            return $this->response->setJSON([
                'success' => true,
                'event' => $event
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching event details: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch event details'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents()
    {
        try {
            $limit = $this->request->getGet('limit') ?: 10;
            
            $db = \Config\Database::connect();
            $today = date('Y-m-d');
            
            $events = $db->table('bookings b')
                ->select('b.*, f.name as facility_name, f.icon as facility_icon')
                ->join('facilities f', 'f.id = b.facility_id', 'left')
                ->where('b.event_date >=', $today)
                ->whereIn('b.status', ['pending', 'confirmed'])
                ->orderBy('b.event_date', 'ASC')
                ->orderBy('b.event_time', 'ASC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'events' => $events
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching upcoming events: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch upcoming events'
            ])->setStatusCode(500);
        }
    }

    /**
     * Calculate event end time
     */
    private function calculateEndTime($eventDate, $eventTime, $duration)
    {
        try {
            $startDateTime = new \DateTime($eventDate . ' ' . $eventTime);
            $durationHours = (int)$duration;
            $startDateTime->add(new \DateInterval('PT' . $durationHours . 'H'));
            return $startDateTime->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            log_message('error', 'Error calculating end time: ' . $e->getMessage());
            return $eventDate . 'T' . $eventTime;
        }
    }

    /**
     * Get facility availability for a specific date
     */
    public function getFacilityAvailability()
    {
        try {
            $facilityId = $this->request->getGet('facility_id');
            $date = $this->request->getGet('date');

            if (!$facilityId || !$date) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility ID and date are required'
                ])->setStatusCode(400);
            }

            $db = \Config\Database::connect();
            
            // Get all bookings for this facility on this date
            $bookings = $db->table('bookings')
                ->select('event_time, duration, status')
                ->where('facility_id', $facilityId)
                ->where('event_date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get()
                ->getResultArray();

            // Calculate available time slots
            $bookedSlots = [];
            foreach ($bookings as $booking) {
                $startTime = strtotime($booking['event_time']);
                $duration = (int)$booking['duration'];
                $endTime = $startTime + ($duration * 3600);
                
                $bookedSlots[] = [
                    'start' => date('H:i', $startTime),
                    'end' => date('H:i', $endTime),
                    'status' => $booking['status']
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'date' => $date,
                'booked_slots' => $bookedSlots,
                'is_available' => count($bookedSlots) === 0
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error checking facility availability: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to check availability'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange()
    {
        try {
            $startDate = $this->request->getGet('start_date');
            $endDate = $this->request->getGet('end_date');

            if (!$startDate || !$endDate) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Start date and end date are required'
                ])->setStatusCode(400);
            }

            $db = \Config\Database::connect();
            
            $events = $db->table('bookings b')
                ->select('b.*, f.name as facility_name, f.icon as facility_icon')
                ->join('facilities f', 'f.id = b.facility_id', 'left')
                ->where('b.event_date >=', $startDate)
                ->where('b.event_date <=', $endDate)
                ->orderBy('b.event_date', 'ASC')
                ->orderBy('b.event_time', 'ASC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'events' => $events,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching events by date range: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch events'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get monthly event summary
     */
    public function getMonthlyEventSummary()
    {
        try {
            $year = $this->request->getGet('year') ?: date('Y');
            $month = $this->request->getGet('month') ?: date('m');

            $db = \Config\Database::connect();
            
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            // Get events grouped by status
            $summary = $db->table('bookings')
                ->select('status, COUNT(*) as count')
                ->where('event_date >=', $startDate)
                ->where('event_date <=', $endDate)
                ->groupBy('status')
                ->get()
                ->getResultArray();

            // Get daily event counts
            $dailyCounts = $db->table('bookings')
                ->select('event_date, COUNT(*) as count')
                ->where('event_date >=', $startDate)
                ->where('event_date <=', $endDate)
                ->groupBy('event_date')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'summary' => $summary,
                'daily_counts' => $dailyCounts
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching monthly summary: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch monthly summary'
            ])->setStatusCode(500);
        }
    }
private function getStatusColor($status)
{
    $colors = [
        'pending' => '#f59e0b',
        'confirmed' => '#3b82f6',
        'completed' => '#10b981',
        'cancelled' => '#ef4444',
        'declined' => '#ef4444'
    ];
    
    return $colors[$status] ?? '#64748b';
}

public function list()
{
    $this->setCorsHeaders();
    
    try {
        $db = \Config\Database::connect();
        
        // Query bookings to get pending, confirmed, and completed bookings
        // Database ENUM values: 'pending','confirmed','cancelled','completed'
        $events = $db->table('bookings b')
            ->select('b.id,
                     b.event_title,
                     b.event_date,
                     b.event_time,
                     b.duration,
                     b.attendees,
                     b.client_name,
                     b.contact_number,
                     b.email_address,
                     b.organization,
                     b.special_requirements,
                     b.total_cost,
                     b.status,
                     b.booking_type,
                     f.name as facility_name,
                     f.icon as facility_icon')
            ->join('facilities f', 'f.id = b.facility_id', 'left')
            // Show pending, confirmed, and completed bookings (exclude cancelled)
            ->whereIn('b.status', ['pending', 'confirmed', 'completed'])
            // Optional: Filter out very old completed events (adjust date as needed)
            // ->where('b.event_date >=', date('Y-m-d', strtotime('-3 months')))
            ->orderBy('b.event_date', 'ASC')
            ->orderBy('b.event_time', 'ASC')
            ->get()
            ->getResultArray();

        // Ensure dates are in proper ISO 8601 format (YYYY-MM-DD)
        $events = array_map(function($event) {
            if ($event['event_date']) {
                // Extract just the date part (YYYY-MM-DD)
                $event['event_date'] = substr($event['event_date'], 0, 10);
            }
            return $event;
        }, $events);

        log_message('info', 'EventsAPI - Found ' . count($events) . ' events (pending + confirmed + completed)');

        // Optional: Debug log to see what statuses we're returning
        $statusCounts = [];
        foreach ($events as $event) {
            $status = $event['status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        log_message('info', 'EventsAPI - Status breakdown: ' . json_encode($statusCounts));

        return $this->response->setJSON([
            'success' => true,
            'events' => $events,
            'count' => count($events),
            'status_breakdown' => $statusCounts ?? []
        ]);

    } catch (\Exception $e) {
        log_message('error', 'EventsAPI - Error fetching events list: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to fetch events list',
            'error' => $e->getMessage()
        ])->setStatusCode(500);
    }
}

/**
 * Download Equipment Reports Summary as Excel
 */
public function downloadEquipmentReport()
{
    try {
        $db = \Config\Database::connect();
        $session = session();
        
        // Get current facilitator ID from session
        $facilitatorId = $session->get('user_id');
        
        if (!$facilitatorId) {
            throw new \Exception('Not authenticated');
        }
        
        // Get summary statistics from facilitator_checklist_items (filtered by current facilitator)
        // Using SUM of expected_quantity instead of COUNT to get actual total equipment quantities
        $summaryQuery = $db->table('facilitator_checklist_items fci')
            ->select('fci.equipment_condition, SUM(fci.expected_quantity) as total_quantity')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->groupBy('fci.equipment_condition')
            ->get()
            ->getResultArray();

        $summary = [
            'total' => 0,
            'good' => 0,
            'damaged' => 0,
            'missing' => 0
        ];

        foreach ($summaryQuery as $row) {
            $total_qty = $row['total_quantity'] ?? 0;
            $summary['total'] += $total_qty;
            if ($row['equipment_condition'] === 'good') {
                $summary['good'] = $total_qty;
            } elseif ($row['equipment_condition'] === 'damaged') {
                $summary['damaged'] = $total_qty;
            } elseif ($row['equipment_condition'] === 'missing') {
                $summary['missing'] = $total_qty;
            }
        }

        // Get equipment quantity summary (sum expected quantity by equipment name and condition)
        $equipmentSummary = $db->table('facilitator_checklist_items fci')
            ->select('fci.equipment_name, fci.equipment_condition, SUM(fci.expected_quantity) as expected_quantity')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->groupBy('fci.equipment_name, fci.equipment_condition')
            ->orderBy('fci.equipment_name', 'ASC')
            ->get()
            ->getResultArray();

        // Process equipment summary into grouped format
        $equipmentGrouped = [];
        foreach ($equipmentSummary as $item) {
            $name = $item['equipment_name'];
            if (!isset($equipmentGrouped[$name])) {
                $equipmentGrouped[$name] = [
                    'name' => $name,
                    'expected_total' => 0,
                    'good' => 0,
                    'damaged' => 0,
                    'missing' => 0
                ];
            }
            $equipmentGrouped[$name]['expected_total'] += $item['expected_quantity'] ?? 0;
            if ($item['equipment_condition'] === 'good') {
                $equipmentGrouped[$name]['good'] = $item['expected_quantity'] ?? 0;
            } elseif ($item['equipment_condition'] === 'damaged') {
                $equipmentGrouped[$name]['damaged'] = $item['expected_quantity'] ?? 0;
            } elseif ($item['equipment_condition'] === 'missing') {
                $equipmentGrouped[$name]['missing'] = $item['expected_quantity'] ?? 0;
            }
        }
        $equipmentGrouped = array_values($equipmentGrouped);

        // Create Excel spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Equipment Report');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(18);

        // Add title
        $sheet->mergeCells('A1:G1');
        $titleCell = $sheet->getCell('A1');
        $titleCell->setValue('Equipment Inspection Reports Summary');
        $titleCell->getStyle()->getFont()->setBold(true)->setSize(16)->setColor(new Color('FFFFFF'));
        $titleCell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3C72');
        $titleCell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension('1')->setRowHeight(25);

        // Add generation date
        $sheet->mergeCells('A2:F2');
        $dateCell = $sheet->getCell('A2');
        $dateCell->setValue('Generated on: ' . date('Y-m-d H:i:s'));
        $dateCell->getStyle()->getFont()->setItalic(true)->setSize(10)->setColor(new Color('666666'));
        $dateCell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add summary section
        $sheet->mergeCells('A4:G4');
        $summaryHeader = $sheet->getCell('A4');
        $summaryHeader->setValue('Summary Statistics');
        $summaryHeader->getStyle()->getFont()->setBold(true)->setSize(12)->setColor(new Color('FFFFFF'));
        $summaryHeader->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2A5298');

        // Summary data
        $row = 5;
        $sheet->setCellValue('A' . $row, 'Total Equipment Inspected:');
        $sheet->setCellValue('B' . $row, $summary['total']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'In Good Condition:');
        $sheet->setCellValue('B' . $row, $summary['good']);
        $sheet->getStyle('B' . $row)->getFont()->setColor(new Color('16A34A'));

        $row++;
        $sheet->setCellValue('A' . $row, 'Damaged/Maintenance:');
        $sheet->setCellValue('B' . $row, $summary['damaged']);
        $sheet->getStyle('B' . $row)->getFont()->setColor(new Color('DC2626'));

        $row++;
        $sheet->setCellValue('A' . $row, 'Missing Equipment:');
        $sheet->setCellValue('B' . $row, $summary['missing']);
        $sheet->getStyle('B' . $row)->getFont()->setColor(new Color('6B7280'));

        // Add detailed reports section
        $row += 1;
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $detailHeader = $sheet->getCell('A' . $row);
        $detailHeader->setValue('Inspection Details');
        $detailHeader->getStyle()->getFont()->setBold(true)->setSize(12)->setColor(new Color('FFFFFF'));
        $detailHeader->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2A5298');

        // Table headers
        $row++;
        $headers = ['Event Name', 'Equipment', 'Total', 'Good', 'Damaged', 'Missing', 'Inspection Date'];
        $col = 'A';
        foreach ($headers as $header) {
            $cell = $sheet->getCell($col . $row);
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3C72');
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Get detailed inspection data
        $detailedData = $db->table('facilitator_checklist_items fci')
            ->select('b.event_title, fci.equipment_name, fci.expected_quantity, fci.equipment_condition, fci.created_at')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->join('bookings b', 'b.id = fc.booking_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->orderBy('b.event_title', 'ASC')
            ->orderBy('fci.equipment_name', 'ASC')
            ->get()
            ->getResultArray();

        // Group data by event and equipment
        $groupedData = [];
        foreach ($detailedData as $item) {
            $key = $item['event_title'] . '||' . $item['equipment_name'];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'event_title' => $item['event_title'],
                    'equipment_name' => $item['equipment_name'],
                    'total' => 0,
                    'good' => 0,
                    'damaged' => 0,
                    'missing' => 0,
                    'inspection_date' => $item['created_at']
                ];
            }
            $groupedData[$key]['total'] += $item['expected_quantity'];
            if ($item['equipment_condition'] === 'good') {
                $groupedData[$key]['good'] += $item['expected_quantity'];
            } elseif ($item['equipment_condition'] === 'damaged') {
                $groupedData[$key]['damaged'] += $item['expected_quantity'];
            } elseif ($item['equipment_condition'] === 'missing') {
                $groupedData[$key]['missing'] += $item['expected_quantity'];
            }
        }

        // Add data rows
        $row++;
        foreach ($groupedData as $detail) {
            $sheet->setCellValue('A' . $row, $detail['event_title'] ?? '');
            $sheet->setCellValue('B' . $row, $detail['equipment_name'] ?? '');
            $sheet->setCellValue('C' . $row, $detail['total']);
            $sheet->setCellValue('D' . $row, $detail['good']);
            $sheet->setCellValue('E' . $row, $detail['damaged']);
            $sheet->setCellValue('F' . $row, $detail['missing']);
            $sheet->setCellValue('G' . $row, $detail['inspection_date'] ? date('Y-m-d', strtotime($detail['inspection_date'])) : '');

            // Center align numbers
            for ($i = 'C'; $i <= 'G'; $i++) {
                $sheet->getStyle($i . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Output file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Equipment_Inspection_Summary_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Write to output
        $writer->save('php://output');
        exit;

    } catch (\Exception $e) {
        log_message('error', 'EventsAPI - Error generating equipment report: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate report: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}

/**
 * Get Equipment Reports Summary
 */
public function equipmentReportsSummary()
{
    try {
        $db = \Config\Database::connect();
        $session = session();
        
        // Get current facilitator ID from session
        $facilitatorId = $session->get('user_id');
        
        if (!$facilitatorId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated'
            ]);
        }
        
        // Check if the facilitator_checklist_items table exists
        $tables = $db->listTables();
        
        if (!in_array('facilitator_checklist_items', $tables)) {
            // Table doesn't exist yet, return empty summary
            return $this->response->setJSON([
                'success' => true,
                'summary' => [
                    'total' => 0,
                    'good' => 0,
                    'damaged' => 0,
                    'missing' => 0
                ],
                'reports' => [],
                'count' => 0,
                'message' => 'No inspection reports found yet'
            ]);
        }
        
        // Get summary statistics from facilitator_checklist_items (filtered by current facilitator)
        // Using SUM of expected_quantity instead of COUNT to get actual total equipment quantities
        $summaryQuery = $db->table('facilitator_checklist_items fci')
            ->select('fci.equipment_condition, SUM(fci.expected_quantity) as total_quantity')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->groupBy('fci.equipment_condition')
            ->get()
            ->getResultArray();

        $summary = [
            'total' => 0,
            'good' => 0,
            'damaged' => 0,
            'missing' => 0
        ];

        foreach ($summaryQuery as $row) {
            $total_qty = $row['total_quantity'] ?? 0;
            $summary['total'] += $total_qty;
            if ($row['equipment_condition'] === 'good') {
                $summary['good'] = $total_qty;
            } elseif ($row['equipment_condition'] === 'damaged') {
                $summary['damaged'] = $total_qty;
            } elseif ($row['equipment_condition'] === 'missing') {
                $summary['missing'] = $total_qty;
            }
        }

        // Get equipment quantity summary (sum expected quantity by equipment name and condition)
        $equipmentSummary = $db->table('facilitator_checklist_items fci')
            ->select('fci.equipment_name, fci.equipment_condition, SUM(fci.expected_quantity) as expected_quantity, COUNT(*) as records')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->groupBy('fci.equipment_name, fci.equipment_condition')
            ->orderBy('fci.equipment_name', 'ASC')
            ->get()
            ->getResultArray();

        // Process equipment summary into grouped format
        $equipmentGrouped = [];
        foreach ($equipmentSummary as $item) {
            $name = $item['equipment_name'];
            if (!isset($equipmentGrouped[$name])) {
                $equipmentGrouped[$name] = [
                    'name' => $name,
                    'expected_total' => 0,
                    'good' => 0,
                    'damaged' => 0,
                    'missing' => 0
                ];
            }
            $equipmentGrouped[$name]['expected_total'] += $item['expected_quantity'] ?? 0;
            if ($item['equipment_condition'] === 'good') {
                $equipmentGrouped[$name]['good'] = $item['expected_quantity'] ?? 0;
            } elseif ($item['equipment_condition'] === 'damaged') {
                $equipmentGrouped[$name]['damaged'] = $item['expected_quantity'] ?? 0;
            } elseif ($item['equipment_condition'] === 'missing') {
                $equipmentGrouped[$name]['missing'] = $item['expected_quantity'] ?? 0;
            }
        }
        // Convert to indexed array
        $equipmentGrouped = array_values($equipmentGrouped);

        // Get detailed reports (latest 50) from facilitator_checklist_items (filtered by current facilitator)
        $reports = $db->table('facilitator_checklist_items fci')
            ->select('fci.*, fc.facilitator_name, b.event_title, b.event_date, f.name as facility_name')
            ->join('facilitator_checklists fc', 'fc.id = fci.checklist_id', 'left')
            ->join('bookings b', 'b.id = fc.booking_id', 'left')
            ->join('facilities f', 'f.id = b.facility_id', 'left')
            ->where('fc.facilitator_id', $facilitatorId)
            ->orderBy('fci.created_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'summary' => $summary,
            'equipmentSummary' => $equipmentGrouped,
            'reports' => $reports,
            'count' => count($reports)
        ]);

    } catch (\Exception $e) {
        log_message('error', 'EventsAPI - Error fetching equipment reports: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => true,
            'summary' => [
                'total' => 0,
                'good' => 0,
                'damaged' => 0,
                'missing' => 0
            ],
            'reports' => [],
            'count' => 0,
            'message' => 'Unable to fetch reports at this time'
        ]);
    }
}
}
