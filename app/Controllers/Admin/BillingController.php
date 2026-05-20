<?php

namespace App\Controllers\Admin;

use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\PlanModel;
use App\Models\EquipmentModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BillingController extends Controller
{
    protected $bookingModel;
    protected $facilityModel;
    protected $planModel;
    protected $equipmentModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->facilityModel = new FacilityModel();
        $this->planModel = new PlanModel();
        $this->equipmentModel = new EquipmentModel();
    }

    /**
     * Generate billing statement using template
     */
    public function generateBillingStatement($bookingId)
    {
        try {
            // Get booking details
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Load the template
           $templatePath = 'assets/templates/billing_statement_template.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Billing statement template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill in the template with booking data
            $this->fillBillingTemplate($sheet, $booking);

            // Generate filename
            $filename = 'Billing_Statement_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the filled template
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return file for download
$response = service('response');
return $response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
    log_message('error', 'Error generating billing statement: ' . $e->getMessage());
    
    // Create response service since BillingController extends basic Controller
    $response = service('response');
    return $response->setJSON([
        'success' => false,
        'message' => 'Failed to generate billing statement: ' . $e->getMessage()
    ])->setStatusCode(500);
        }
    }

    /**
     * Fill the template with booking data
     */
private function fillBillingTemplate($sheet, $booking)
{
    // Basic information
    $sheet->setCellValue('A4', $booking['client_name']); // Name in A4
    
    // Date in F4 - extend existing text
    $currentDateText = $sheet->getCell('F4')->getValue();
    $sheet->setCellValue('F4', $currentDateText . ' ' . date('F j, Y'));
    
    // Calculate total cost minus equipment FIRST
    $totalCostMinusEquipment = floatval($booking['total_cost']);
    if (isset($booking['equipment']) && !empty($booking['equipment'])) {
        foreach ($booking['equipment'] as $equipment) {
            $totalCostMinusEquipment -= floatval($equipment['total_cost']);
        }
    }
    
    // Facilities mapping and pricing
    $facilityMappings = [
        'University Gymnasium' => [
            'cell' => 'F6', 
            'day_checkbox' => 'B6', 
            'night_checkbox' => 'C6'
        ],
        'University Auditorium' => [
            'cell' => 'F7', 
            'day_checkbox' => 'B7', 
            'night_checkbox' => 'C7'
        ],
        'Function Hall (ACAD Bldg.)' => [
            'cell' => 'F8',
            'gym_checkbox' => 'B10',
            'acad_checkbox' => 'C10'
        ],
        'AVR Library' => [
            'cell' => 'F9', 
            'checkbox' => 'B9'
        ],
        'AVR Calibo Engineering' => [
            'cell' => 'F9', 
            'checkbox' => 'C9'
        ],
        'Pearl Mini Restaurant' => ['cell' => 'F11'],
        'Pearl Hotel Rooms' => ['cell' => 'F11'],
        'Classrooms' => ['cell' => 'F12'],
        'Staff House Rooms' => ['cell' => 'F15']
    ];
    
    // Fill facility information
    if (isset($facilityMappings[$booking['facility_name']])) {
        $mapping = $facilityMappings[$booking['facility_name']];
        
        // Set total cost minus equipment in the correct facility row
        if ($totalCostMinusEquipment > 0) {
            $sheet->setCellValue($mapping['cell'], $totalCostMinusEquipment);
            $sheet->getStyle($mapping['cell'])->getNumberFormat()
                  ->setFormatCode('#,##0.00');
        }
        
        // Handle Gymnasium and Auditorium checkboxes (day/night based on time)
        if (isset($mapping['day_checkbox']) && isset($mapping['night_checkbox'])) {
            $eventHour = (int)date('H', strtotime($booking['event_time']));
            $checkboxCell = ($eventHour >= 6 && $eventHour < 18) 
                ? $mapping['day_checkbox'] 
                : $mapping['night_checkbox'];
            
            $existingContent = $sheet->getCell($checkboxCell)->getValue();
            $sheet->setCellValue($checkboxCell, '☑ ' . trim(str_replace(['☐', '☑'], '', $existingContent)));
        }
        
        // Handle AVR checkboxes (Library vs COE)
        if (isset($mapping['checkbox'])) {
            $existingContent = $sheet->getCell($mapping['checkbox'])->getValue();
            $sheet->setCellValue($mapping['checkbox'], '☑ ' . trim(str_replace(['☐', '☑'], '', $existingContent)));
        }
        
        // Handle Function Hall checkboxes (Gym vs ACAD2)
        if (isset($mapping['gym_checkbox']) && isset($mapping['acad_checkbox'])) {
            $checkboxCell = $mapping['acad_checkbox'];
            
            $existingContent = $sheet->getCell($checkboxCell)->getValue();
            $sheet->setCellValue($checkboxCell, '☑ ' . trim(str_replace(['☐', '☑'], '', $existingContent)));
        }
    }
    
    // Equipment mapping with quantity display and pricing
    $equipmentMappings = [
        'Multimedia Projector' => ['qty_cell' => 'C20', 'price_cell' => 'F20'],
        'Monobloc Chairs' => ['qty_cell' => 'C21', 'price_cell' => 'F21'],
        'Chair Covers' => ['qty_cell' => 'C22', 'price_cell' => 'F22'],
        'Tables' => ['qty_cell' => 'C23', 'price_cell' => 'F23'],
        'Table Covers' => ['qty_cell' => 'C24', 'price_cell' => 'F24'],
        'Steel Framed Fence' => ['qty_cell' => 'C25', 'price_cell' => 'F25'],
        'School Bus' => ['qty_cell' => 'C26', 'price_cell' => 'F26']
    ];
    
    // Fill equipment information if any
    if (isset($booking['equipment']) && !empty($booking['equipment'])) {
        foreach ($booking['equipment'] as $equipment) {
            foreach ($equipmentMappings as $equipName => $cells) {
                if (stripos($equipment['name'], $equipName) !== false || 
                    stripos($equipName, $equipment['name']) !== false) {
                    
                    $quantity = intval($equipment['quantity']);
                    $rate = floatval($equipment['rate']);
                    $totalCost = floatval($equipment['total_cost']);
                    
                    if ($quantity > 0 && $rate > 0) {
                        $qtyText = $quantity . ' X ₱' . number_format($rate, 2);
                        $sheet->setCellValue($cells['qty_cell'], $qtyText);
                    }
                    
                    if ($totalCost > 0) {
                        $sheet->setCellValue($cells['price_cell'], $totalCost);
                        $sheet->getStyle($cells['price_cell'])->getNumberFormat()
                              ->setFormatCode('#,##0.00');
                    }
                    
                    break;
                }
            }
        }
    }
}

    /**
     * Find the row containing a specific section header
     */
    private function findSectionRow($sheet, $sectionText)
    {
        $highestRow = $sheet->getHighestRow();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if (strpos($cellValue, $sectionText) !== false) {
                return $row;
            }
        }
        
        return null;
    }

    /**
     * Find text in a specific column
     */
    private function findTextInColumn($sheet, $column, $searchText)
    {
        $highestRow = $sheet->getHighestRow();
        if (isset($booking['equipment']) && !empty($booking['equipment'])) {
            for ($row = 1; $row <= $highestRow; $row++) {
                $cellValue = $sheet->getCell($column . $row)->getValue();
                if (strpos($cellValue, $searchText) !== false) {
                    return $row;
                }
            }

            return null;
        }
    }

    /**
     * API endpoint for downloading billing statement
     */
    public function downloadBillingStatement($bookingId)
    {
        return $this->generateBillingStatement($bookingId);
    }


    
}