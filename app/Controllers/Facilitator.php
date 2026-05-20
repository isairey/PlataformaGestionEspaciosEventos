<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Facilitator extends Controller
{
    protected $db;
    protected $session;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->session = session();
        helper(['url', 'form']);
    }

    /**
     * Check if current user is facilitator or admin
     * Returns redirect response if not authorized
     */
    private function checkFacilitatorRole()
    {
        if (!$this->session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue');
        }
        
        $role = $this->session->get('role');
        if (!in_array($role, ['facilitator', 'admin'])) {
            return redirect()->to('/unauthorized')->with('error', 'Facilitator access required');
        }
        
        return null;
    }

    /**
     * Main dashboard - Single page workflow
     */
    public function index()
    {
        $redirect = $this->checkFacilitatorRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'Facilitator Dashboard',
            'user_name' => $this->session->get('full_name'),
            'user_role' => $this->session->get('role')
        ];

        return view('facilitator/dashboard', $data);
    }

    /**
     * Get completed events for inspection (API)
     */
    public function getCompletedEvents()
    {
        try {
            // Get all events with confirmed bookings (no date filter)
            $events = $this->db->table('events e')
                ->select('e.id, e.event_title, e.client_name, e.facility_id, e.event_date, 
                         e.event_time, e.duration, e.attendees, e.organization, e.status,
                         e.booking_id, f.name as facility_name, f.icon as facility_icon,
                         b.status as booking_status, 
                         fc.id as checklist_id,
                         fc.submitted_at as last_inspection_date')
                ->join('facilities f', 'f.id = e.facility_id', 'left')
                ->join('bookings b', 'b.id = e.booking_id', 'left')
                ->join('facilitator_checklists fc', 'fc.event_id = e.id', 'left')
                ->where('b.status', 'confirmed')
                ->orderBy('e.event_date', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray();

            // Process results to add is_inspected flag
            $processed = [];
            foreach ($events as $event) {
                $event['is_inspected'] = !empty($event['checklist_id']) ? 1 : 0;
                $processed[] = $event;
            }

            log_message('info', 'Facilitator - Found ' . count($processed) . ' confirmed bookings');

            return $this->response->setJSON([
                'success' => true,
                'events' => $processed,
                'count' => count($processed)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error fetching events: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load events: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get event equipment checklist - FIXED VERSION
     */
    public function getEventChecklist($eventId)
    {
        try {
            // Get event details
            $event = $this->db->table('events e')
                ->select('e.*, f.name as facility_name, f.icon as facility_icon')
                ->join('facilities f', 'f.id = e.facility_id', 'left')
                ->where('e.id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                log_message('error', "Facilitator - Event not found: ID {$eventId}");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get booking details
            $booking = $this->db->table('bookings')
                ->where('id', $event['booking_id'])
                ->get()
                ->getRowArray();

            if (!$booking) {
                log_message('error', "Facilitator - Booking not found for event {$eventId}, booking ID: {$event['booking_id']}");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking record not found'
                ])->setStatusCode(404);
            }

            // Get equipment from booking with proper error handling
            $equipment = [];
            try {
                // Get equipment from booking_equipment (rented equipment)
                $bookingEquipment = $this->db->table('booking_equipment be')
                    ->select('be.equipment_id,
                             be.quantity,
                             be.rate,
                             be.total_cost,
                             e.name,
                             e.category,
                             e.status as equipment_status,
                             e.unit,
                             "rental" as source_type')
                    ->join('equipment e', 'e.id = be.equipment_id', 'left')
                    ->where('be.booking_id', $event['booking_id'])
                    ->get()
                    ->getResultArray();

                // Get equipment from plan_equipment (included in plan)
                $planEquipment = [];
                if (!empty($booking['plan_id'])) {
                    $planEquipment = $this->db->table('plan_equipment pe')
                        ->select('pe.equipment_id,
                                 pe.quantity_included as quantity,
                                 pe.additional_rate as rate,
                                 0 as total_cost,
                                 e.name,
                                 e.category,
                                 e.status as equipment_status,
                                 e.unit,
                                 "plan" as source_type')
                        ->join('equipment e', 'e.id = pe.equipment_id', 'left')
                        ->where('pe.plan_id', $booking['plan_id'])
                        ->get()
                        ->getResultArray();
                }

                // Merge both arrays
                $equipment = array_merge($bookingEquipment, $planEquipment);

                // Remove duplicates (if equipment is in both plan and rental, keep rental version)
                $uniqueEquipment = [];
                $seenIds = [];
                foreach ($equipment as $item) {
                    if (!in_array($item['equipment_id'], $seenIds)) {
                        $uniqueEquipment[] = $item;
                        $seenIds[] = $item['equipment_id'];
                    }
                }
                $equipment = $uniqueEquipment;

                // Sort by category and name
                usort($equipment, function($a, $b) {
                    $catCompare = strcmp($a['category'] ?? '', $b['category'] ?? '');
                    if ($catCompare !== 0) return $catCompare;
                    return strcmp($a['name'] ?? '', $b['name'] ?? '');
                });

                log_message('info', "Facilitator - Found " . count($equipment) . " equipment items (booking: " . count($bookingEquipment) . ", plan: " . count($planEquipment) . ") for booking {$event['booking_id']}");

            } catch (\Exception $equipError) {
                log_message('error', 'Facilitator - Error fetching equipment: ' . $equipError->getMessage());
                // Continue with empty equipment array
            }

            // Check if facilitator has already submitted checklist
            $existingChecklist = $this->db->table('facilitator_checklists')
                ->where('event_id', $eventId)
                ->where('facilitator_id', $this->session->get('user_id'))
                ->get()
                ->getRowArray();

            return $this->response->setJSON([
                'success' => true,
                'event' => array_merge($event, [
                    'is_inspected' => !empty($existingChecklist) ? 1 : 0
                ]),
                'booking' => $booking,
                'equipment' => $equipment,
                'has_equipment' => count($equipment) > 0,
                'already_submitted' => !empty($existingChecklist),
                'checklist_id' => $existingChecklist['id'] ?? null
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error loading checklist: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load equipment checklist',
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Generate equipment inspection report
     */
    public function generateEquipmentReport($eventId)
    {
        try {
            // Get event with facility details
            $event = $this->db->table('events e')
                ->select('e.*, f.name as facility_name')
                ->join('facilities f', 'f.id = e.facility_id', 'left')
                ->where('e.id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get booking with full details
            $bookingModel = new \App\Models\BookingModel();
            $booking = $bookingModel->getBookingWithFullDetails($event['booking_id']);

            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Get checklist data from request
            $requestData = $this->request->getJSON(true);
            $equipmentStatuses = $requestData['equipment_statuses'] ?? [];

            if (empty($equipmentStatuses)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No equipment status data provided'
                ])->setStatusCode(400);
            }

            // Store checklist in database
            $checklistId = $this->storeFacilitatorChecklist($event, $equipmentStatuses);

            if (!$checklistId) {
                throw new \Exception('Failed to store checklist data');
            }

            // Load template
            $templatePath = FCPATH . 'assets/templates/inspection_evaluation_template.xlsx';
            if (!file_exists($templatePath)) {
                throw new \Exception('Inspection and Evaluation template not found at: ' . $templatePath);
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // === POPULATE BASIC BOOKING INFO (SECTION A & B) ===
            // B9 - Sponsoring Org/Group
            $sheet->setCellValue('B9', $booking['organization'] ?? 'N/A');
            
            // B10 - Point Person
            $sheet->setCellValue('B10', $booking['client_name']);
            
            // B11 - Address
            $sheet->setCellValue('B11', $booking['address'] ?? 'N/A');
            
            // B12 - Contact Number
            $sheet->setCellValue('B12', $booking['contact_number']);
            
            // B14 - Name of Event
            $sheet->setCellValue('B14', $booking['event_title']);
            
            // B15 - Date
            $eventDate = new \DateTime($booking['event_date']);
            $sheet->setCellValue('B15', $eventDate->format('F j, Y'));
            
            // B16 - Time
            $timeRange = date('g:i A', strtotime($booking['event_time']));
            $endTime = date('g:i A', strtotime($booking['event_time']) + ($booking['duration'] * 3600));
            $sheet->setCellValue('B16', $timeRange . ' - ' . $endTime);

            // === POPULATE SECTION C: FACILITIES/EQUIPMENT USED ===
            // Get rental equipment (from booking_equipment)
            $rentalEquipment = $this->getRentalEquipmentForBooking($event['booking_id']);
            
            // Row 18 starts equipment list in section C
            $equipmentRow = 18;
            $equipmentIndex = 0;

            // Create a map of equipment status by equipment_id for quick lookup
            $equipmentStatusMap = [];
            foreach ($equipmentStatuses as $status) {
                $equipmentStatusMap[$status['equipment_id']] = $status;
            }

            // Add rental equipment to section C
            foreach ($rentalEquipment as $equip) {
                if ($equipmentIndex >= 8) break; // Limit to 8 items for Section C
                
                // Column A: Equipment Name
                $sheet->setCellValue('A' . $equipmentRow, $equip['name']);
                
                // Column B: Quantity or Status
                // Check if this equipment was inspected
                if (isset($equipmentStatusMap[$equip['id']])) {
                    // Show inspection status
                    $status = $equipmentStatusMap[$equip['id']];
                    $good = $status['good_quantity'] ?? 0;
                    $damaged = $status['damaged_quantity'] ?? 0;
                    $missing = $status['missing_quantity'] ?? 0;
                    $statusText = "Good: $good | Damaged: $damaged | Missing: $missing";
                    $sheet->setCellValue('B' . $equipmentRow, $statusText);
                } else {
                    // Show just the quantity if not inspected
                    $sheet->setCellValue('B' . $equipmentRow, $equip['quantity']);
                }
                
                $equipmentRow++;
                $equipmentIndex++;
            }

            // === POPULATE SECTION 27B: PLAN EQUIPMENT (WITH STATUS SUMMARY) ===
            // Get plan equipment (from plan_equipment)
            $planEquipment = $this->getPlanEquipmentForBooking($booking['plan_id']);

            // If we have plan equipment, we need to insert rows and shift the sections below
            $planEquipmentCount = count($planEquipment);
            
            if ($planEquipmentCount > 0) {
                // Insert rows for plan equipment (starting at row 27, we need planEquipmentCount rows)
                // This shifts everything below down
                $sheet->insertNewRowBefore(27, $planEquipmentCount);
                
                // Now add plan equipment with their individual status summaries
                foreach ($planEquipment as $index => $equip) {
                    $currentRow = 27 + $index;
                    
                    // Column A: Equipment Name
                    $sheet->setCellValue('A' . $currentRow, $equip['name']);
                    
                    // Column B: Expected Quantity and Status
                    // Get status for this specific equipment
                    if (isset($equipmentStatusMap[$equip['id']])) {
                        $status = $equipmentStatusMap[$equip['id']];
                        $expectedQty = $status['expected_quantity'] ?? $equip['quantity'] ?? 0;
                        $good = $status['good_quantity'] ?? 0;
                        $damaged = $status['damaged_quantity'] ?? 0;
                        $missing = $status['missing_quantity'] ?? 0;
                        
                        // Format status string as: "Good: X | Damaged: Y | Missing: Z"
                        $statusText = "Good: $good | Damaged: $damaged | Missing: $missing";
                    } else {
                        // If not inspected, just show the quantity
                        $expectedQty = $equip['quantity'] ?? 0;
                        $statusText = "Not Inspected";
                    }
                    
                    // Set status in column B with quantity info
                    $sheet->setCellValue('B' . $currentRow, $statusText);
                }
                
                // Now the sections below are automatically shifted down by the insertNewRowBefore
                // So "D. CHARGES FOR DAMAGES" will be at row (29 + planEquipmentCount)
            }

            // Create booking_management folder if it doesn't exist
            $bookingManagementPath = WRITEPATH . 'uploads/booking_management/booking_' . $event['booking_id'];
            if (!is_dir($bookingManagementPath)) {
                mkdir($bookingManagementPath, 0755, true);
            }

            // Save file
            $filename = 'Inspection_Evaluation_Event' . $eventId . '_' . date('Y-m-d_His') . '.xlsx';
            $filepath = $bookingManagementPath . '/' . $filename;

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return Excel file
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error generating report: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get rental equipment for a booking
     */
    private function getRentalEquipmentForBooking($bookingId)
    {
        return $this->db->table('booking_equipment be')
            ->select('e.id, e.name, e.category, be.quantity, e.unit')
            ->join('equipment e', 'e.id = be.equipment_id', 'left')
            ->where('be.booking_id', $bookingId)
            ->orderBy('e.category', 'ASC')
            ->orderBy('e.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get plan equipment for a plan
     */
    private function getPlanEquipmentForBooking($planId)
    {
        if (empty($planId)) {
            return [];
        }

        return $this->db->table('plan_equipment pe')
            ->select('e.id, e.name, e.category, pe.quantity_included as quantity, e.unit')
            ->join('equipment e', 'e.id = pe.equipment_id', 'left')
            ->where('pe.plan_id', $planId)
            ->orderBy('e.category', 'ASC')
            ->orderBy('e.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Store facilitator checklist data
     */
    private function storeFacilitatorChecklist($event, $equipmentStatuses)
    {
        try {
            $facilitatorId = $this->session->get('user_id');

            // Check if checklist already exists
            $existing = $this->db->table('facilitator_checklists')
                ->where('event_id', $event['id'])
                ->where('facilitator_id', $facilitatorId)
                ->get()
                ->getRowArray();

            if ($existing) {
                log_message('info', "Facilitator checklist already exists for event {$event['id']}");
                return $existing['id'];
            }

            // Create checklist record
            $checklistData = [
                'booking_id' => $event['booking_id'],
                'event_id' => $event['id'],
                'facilitator_id' => $facilitatorId,
                'facilitator_name' => $this->session->get('full_name'),
                'submitted_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->table('facilitator_checklists')->insert($checklistData);
            $checklistId = $this->db->insertID();

            if (!$checklistId) {
                throw new \Exception('Failed to insert checklist record');
            }

            // Insert equipment items
            foreach ($equipmentStatuses as $item) {
                $equipment = $this->db->table('equipment')
                    ->where('id', $item['equipment_id'])
                    ->get()
                    ->getRowArray();

                if ($equipment) {
                    $itemData = [
                        'checklist_id' => $checklistId,
                        'equipment_id' => $item['equipment_id'],
                        'equipment_name' => $equipment['name'],
                        'expected_quantity' => $item['expected_quantity'] ?? $item['quantity'] ?? 1,
                        'actual_quantity' => $item['actual_quantity'] ?? $item['quantity'] ?? 1,
                        'equipment_condition' => $item['status'],
                        'remarks' => $item['notes'] ?? '',
                        'is_available' => ($item['status'] === 'good' ? 1 : 0),
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->db->table('facilitator_checklist_items')->insert($itemData);
                }
            }

            log_message('info', "Facilitator checklist stored for event {$event['id']} by user {$facilitatorId}");
            
            return $checklistId;

        } catch (\Exception $e) {
            log_message('error', 'Error storing checklist: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate PDF report (simplified HTML version)
     */
    private function generatePDFReport($event, $equipmentStatuses, $checklistId)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px;
            color: #333;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header h2 {
            color: #007bff;
            margin-bottom: 5px;
        }
        .event-info { 
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .event-info p {
            margin: 8px 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .status-good { color: #28a745; font-weight: bold; }
        .status-damaged { color: #dc3545; font-weight: bold; }
        .status-maintenance { color: #ffc107; font-weight: bold; }
        .status-missing { color: #6c757d; font-weight: bold; }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Equipment Inspection Report</h2>
        <p>Checklist ID: #' . str_pad($checklistId, 6, '0', STR_PAD_LEFT) . '</p>
    </div>
    
    <div class="event-info">
        <p><strong>Event:</strong> ' . htmlspecialchars($event['event_title']) . '</p>
        <p><strong>Facility:</strong> ' . htmlspecialchars($event['facility_name']) . '</p>
        <p><strong>Event Date:</strong> ' . date('F d, Y', strtotime($event['event_date'])) . '</p>
        <p><strong>Event Time:</strong> ' . date('h:i A', strtotime($event['event_time'])) . '</p>
        <p><strong>Duration:</strong> ' . $event['duration'] . ' hours</p>
        <p><strong>Organizer:</strong> ' . htmlspecialchars($event['client_name']) . '</p>
        <p><strong>Inspector:</strong> ' . htmlspecialchars($this->session->get('full_name')) . '</p>
        <p><strong>Inspection Date:</strong> ' . date('F d, Y h:i A') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Equipment</th>
                <th style="width: 15%;">Category</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 20%;">Notes</th>
            </tr>
        </thead>
        <tbody>';
        
        if (empty($equipmentStatuses)) {
            $html .= '<tr><td colspan="5" style="text-align: center; padding: 20px;">No equipment to inspect</td></tr>';
        } else {
            foreach ($equipmentStatuses as $item) {
                $equipment = $this->db->table('equipment')->where('id', $item['equipment_id'])->get()->getRowArray();
                
                if ($equipment) {
                    $statusClass = 'status-' . $item['status'];
                    $statusLabel = ucfirst($item['status']);
                    
                    $html .= '<tr>
                        <td>' . htmlspecialchars($equipment['name']) . '</td>
                        <td>' . htmlspecialchars($equipment['category'] ?? 'N/A') . '</td>
                        <td>' . ($item['quantity'] ?? 1) . '</td>
                        <td class="' . $statusClass . '">' . $statusLabel . '</td>
                        <td>' . htmlspecialchars($item['notes'] ?? 'N/A') . '</td>
                    </tr>';
                }
            }
        }
        
        $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p><em>This report was automatically generated by the Facilities Management System.</em></p>
        <p>Generated on ' . date('F d, Y \a\t h:i A') . '</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Generate Excel report for equipment inspection
     */
    private function generateExcelReport($event, $equipmentStatuses, $checklistId)
    {
        // Load PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('CSPC Facilities Management System')
            ->setTitle('Equipment Inspection Report')
            ->setSubject('Facilitator Equipment Checklist')
            ->setDescription('Equipment condition inspection report for event #' . $event['id']);

        // Set header row styles
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '007bff']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];

        // Title
        $sheet->setCellValue('A1', 'EQUIPMENT INSPECTION REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Report Details
        $row = 3;
        $sheet->setCellValue('A' . $row, 'Checklist ID:');
        $sheet->setCellValue('B' . $row, '#' . str_pad($checklistId, 6, '0', STR_PAD_LEFT));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Event:');
        $sheet->setCellValue('B' . $row, $event['event_title']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Facility:');
        $sheet->setCellValue('B' . $row, $event['facility_name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Event Date:');
        $sheet->setCellValue('B' . $row, date('F d, Y', strtotime($event['event_date'])));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Event Time:');
        $sheet->setCellValue('B' . $row, date('h:i A', strtotime($event['event_time'])));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Duration:');
        $sheet->setCellValue('B' . $row, $event['duration'] . ' hours');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Organizer:');
        $sheet->setCellValue('B' . $row, $event['client_name']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Inspector:');
        $sheet->setCellValue('B' . $row, $this->session->get('full_name'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue('A' . $row, 'Inspection Date:');
        $sheet->setCellValue('B' . $row, date('F d, Y h:i A'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // Equipment table header
        $row += 2;
        $headerRow = $row;
        $sheet->setCellValue('A' . $row, 'Equipment Name');
        $sheet->setCellValue('B' . $row, 'Category');
        $sheet->setCellValue('C' . $row, 'Source');
        $sheet->setCellValue('D' . $row, 'Quantity');
        $sheet->setCellValue('E' . $row, 'Condition');
        $sheet->setCellValue('F' . $row, 'Notes');

        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerStyle);

        // Equipment data
        $row++;
        if (!empty($equipmentStatuses)) {
            foreach ($equipmentStatuses as $item) {
                $equipment = $this->db->table('equipment')->where('id', $item['equipment_id'])->get()->getRowArray();

                if ($equipment) {
                    $sheet->setCellValue('A' . $row, $equipment['name']);
                    $sheet->setCellValue('B' . $row, $equipment['category'] ?? 'N/A');
                    $sheet->setCellValue('C' . $row, ucfirst($item['source'] ?? 'N/A'));
                    $sheet->setCellValue('D' . $row, $item['quantity'] ?? 1);
                    $sheet->setCellValue('E' . $row, ucfirst($item['status']));
                    $sheet->setCellValue('F' . $row, $item['notes'] ?? '');

                    // Apply conditional formatting for status
                    $statusCell = 'E' . $row;
                    switch ($item['status']) {
                        case 'good':
                            $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('28a745');
                            break;
                        case 'damaged':
                            $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('dc3545');
                            break;
                        case 'maintenance':
                            $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('ffc107');
                            break;
                        case 'missing':
                            $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('6c757d');
                            break;
                    }
                    $sheet->getStyle($statusCell)->getFont()->setBold(true);

                    $row++;
                }
            }
        } else {
            $sheet->setCellValue('A' . $row, 'No equipment to inspect');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to the table
        $lastRow = $row - 1;
        $sheet->getStyle('A' . $headerRow . ':F' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'This report was automatically generated by the Facilities Management System.');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Save to temp file
        $fileName = 'equipment_inspection_' . $checklistId . '_' . time() . '.xlsx';
        $filePath = WRITEPATH . 'uploads/temp/' . $fileName;

        // Create temp directory if it doesn't exist
        if (!is_dir(WRITEPATH . 'uploads/temp/')) {
            mkdir(WRITEPATH . 'uploads/temp/', 0755, true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);

        log_message('info', "Facilitator - Generated Excel report: {$filePath}");

        return $filePath;
    }

    /**
     * Get submission history
     */
    public function getSubmissionHistory()
    {
        try {
            $facilitatorId = $this->session->get('id');

            $submissions = $this->db->table('facilitator_checklists fc')
                ->select('fc.*, e.event_title, e.event_date, f.name as facility_name')
                ->join('events e', 'e.id = fc.event_id', 'left')
                ->join('facilities f', 'f.id = e.facility_id', 'left')
                ->where('fc.facilitator_id', $facilitatorId)
                ->orderBy('fc.submitted_at', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'submissions' => $submissions
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching submission history: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch history'
            ])->setStatusCode(500);
        }
    }

    /**
     * Update equipment quantities based on inspection report
     */
    public function updateEquipmentFromInspection($eventId)
    {
        try {
            $requestData = $this->request->getJSON(true);
            $equipmentStatuses = $requestData['equipment_statuses'] ?? [];

            if (empty($equipmentStatuses)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No equipment status data provided'
                ])->setStatusCode(400);
            }

            // Store checklist first
            $event = $this->db->table('events e')
                ->select('e.*')
                ->where('e.id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            $checklistId = $this->storeFacilitatorChecklist($event, $equipmentStatuses);

            // Update equipment quantities and status
            foreach ($equipmentStatuses as $status) {
                $equipmentId = $status['equipment_id'];
                $goodQty = $status['good_quantity'] ?? 0;
                $damagedQty = $status['damaged_quantity'] ?? 0;
                $missingQty = $status['missing_quantity'] ?? 0;
                $expectedQty = $status['expected_quantity'] ?? 0;

                // Get current equipment data
                $equipment = $this->db->table('equipment')
                    ->where('id', $equipmentId)
                    ->get()
                    ->getRowArray();

                if ($equipment) {
                    // Update equipment based on inspection results
                    // Equipment that was rented is now being returned
                    $currentGood = $equipment['good'] ?? 0;
                    $currentDamaged = $equipment['damaged'] ?? 0;
                    $currentRented = $equipment['rented'] ?? $expectedQty;

                    // After inspection: Add returned items to inventory
                    // Good items go back to available stock
                    $returnedGood = $goodQty;
                    $newGood = $currentGood + $returnedGood;

                    // Damaged items go to damaged inventory
                    $returnedDamaged = $damagedQty;
                    $newDamaged = $currentDamaged + $returnedDamaged;

                    // Total quantity stays the same (missing items are tracked separately)
                    $newTotalQty = $equipment['quantity'] ?? 0;

                    // Rented count is reduced by returned items
                    $newRented = max(0, $currentRented - $expectedQty);

                    // Available = good - rented (not currently out on loan)
                    $newAvailable = max(0, $newGood - $newRented);

                    // Update equipment record
                    $updateData = [
                        'quantity' => $newTotalQty,
                        'good' => $newGood,
                        'damaged' => $newDamaged,
                        'rented' => $newRented,
                        'available' => $newAvailable,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $this->db->table('equipment')
                        ->where('id', $equipmentId)
                        ->update($updateData);

                    log_message('info', "Equipment {$equipmentId} returned after inspection: Good: +{$returnedGood} (now {$newGood}), Damaged: +{$returnedDamaged} (now {$newDamaged}), Rented: {$currentRented}->{$newRented}, Missing: {$missingQty}");
                }
            }

            // Update event status to 'completed' to mark inspection as done
            $this->db->table('events')
                ->where('id', $eventId)
                ->update([
                    'status' => 'completed',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            log_message('info', "Event {$eventId} inspection completed and status updated to 'completed'");

            // Get the checklist record to return submission date
            $checklist = $this->db->table('facilitator_checklists')
                ->where('id', $checklistId)
                ->get()
                ->getRowArray();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Equipment quantities updated successfully and inspection completed',
                'checklist_id' => $checklistId,
                'event_id' => $eventId,
                'submitted_at' => $checklist['submitted_at'] ?? date('Y-m-d H:i:s'),
                'facilitator_name' => $checklist['facilitator_name'] ?? $this->session->get('full_name')
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error updating equipment: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update equipment: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get generated inspection files for an event
     */
    public function getGeneratedFiles($eventId)
    {
        try {
            // Get event with booking info
            $event = $this->db->table('events')
                ->where('id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            $bookingId = $event['booking_id'];
            $bookingManagementPath = WRITEPATH . 'uploads/booking_management/booking_' . $bookingId;

            $files = [];

            // Check if directory exists and list files
            if (is_dir($bookingManagementPath)) {
                $scandir = scandir($bookingManagementPath);
                foreach ($scandir as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $bookingManagementPath . '/' . $file;
                        $files[] = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'date' => date('M d, Y H:i:s', filemtime($filePath)),
                            'url' => base_url('api/events/' . $eventId . '/download-file/' . urlencode($file))
                        ];
                    }
                }
            }

            // Sort by date descending (most recent first)
            usort($files, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return $this->response->setJSON([
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'message' => count($files) > 0 ? count($files) . ' file(s) found' : 'No generated files yet'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error fetching generated files: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch generated files: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Download a generated inspection file
     */
    public function downloadInspectionFile($eventId, $fileName)
    {
        try {
            // Get event with booking info
            $event = $this->db->table('events')
                ->where('id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                throw new \Exception('Event not found');
            }

            $bookingId = $event['booking_id'];
            $bookingManagementPath = WRITEPATH . 'uploads/booking_management/booking_' . $bookingId;
            
            // Sanitize filename to prevent directory traversal
            $fileName = basename(urldecode($fileName));
            $filePath = $bookingManagementPath . '/' . $fileName;

            // Verify file exists and is in the correct directory
            $realPath = realpath($filePath);
            $realBasePath = realpath($bookingManagementPath);
            
            if (!$realPath || !$realBasePath || strpos($realPath, $realBasePath) !== 0) {
                throw new \Exception('Invalid file path');
            }

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $fileName);
            }

            // Log the download
            log_message('info', 'Facilitator - Downloaded file: ' . $fileName . ' for event: ' . $eventId);

            // Return file for download
            return $this->response->download($filePath, null, true);

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error downloading file: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ])->setStatusCode(404);
        }
    }

    /**
     * Delete an inspection report file
     */
    public function deleteInspectionFile($eventId, $fileName)
    {
        try {
            // Get event with booking info
            $event = $this->db->table('events')
                ->where('id', $eventId)
                ->get()
                ->getRowArray();

            if (!$event) {
                throw new \Exception('Event not found');
            }

            $bookingId = $event['booking_id'];
            $bookingManagementPath = WRITEPATH . 'uploads/booking_management/booking_' . $bookingId;
            
            // Sanitize filename to prevent directory traversal
            $fileName = basename(urldecode($fileName));
            $filePath = $bookingManagementPath . '/' . $fileName;

            // Verify file exists and is in the correct directory
            $realPath = realpath($filePath);
            $realBasePath = realpath($bookingManagementPath);
            
            if (!$realPath || !$realBasePath || strpos($realPath, $realBasePath) !== 0) {
                throw new \Exception('Invalid file path');
            }

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $fileName);
            }

            // Delete the file
            if (unlink($filePath)) {
                log_message('info', 'Facilitator - Deleted file: ' . $fileName . ' for event: ' . $eventId);
                
                // Also delete inspection records from database
                // First, find the checklist for this booking and event
                $checklist = $this->db->table('facilitator_checklists')
                    ->where('booking_id', $bookingId)
                    ->where('event_id', $eventId)
                    ->get()
                    ->getRowArray();
                
                if ($checklist) {
                    // Delete all checklist items for this checklist
                    $this->db->table('facilitator_checklist_items')
                        ->where('checklist_id', $checklist['id'])
                        ->delete();
                    
                    // Delete the checklist record itself
                    $this->db->table('facilitator_checklists')
                        ->where('id', $checklist['id'])
                        ->delete();
                    
                    log_message('info', 'Facilitator - Deleted inspection records for booking: ' . $bookingId);
                }
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File and inspection records deleted successfully'
                ]);
            } else {
                throw new \Exception('Could not delete file');
            }

        } catch (\Exception $e) {
            log_message('error', 'Facilitator - Error deleting file: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ])->setStatusCode(400);
        }
    }
}