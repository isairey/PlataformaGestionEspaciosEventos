<?php

namespace App\Controllers\Admin;

use App\Models\BookingModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EquipmentRequestController extends Controller
{
    protected $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Generate equipment request form using template
     */
    public function generateEquipmentRequestForm($bookingId)
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
            $templatePath = 'assets/templates/equipment_request_form_template.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Equipment request form template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill in the template with booking data
            $this->fillEquipmentRequestTemplate($sheet, $booking);

            // Generate filename
            $filename = 'Equipment_Request_Form_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
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
            log_message('error', 'Error generating equipment request form: ' . $e->getMessage());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate equipment request form: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Fill the equipment request form template with booking data
     */
private function fillEquipmentRequestTemplate($sheet, $booking)
{
    // Basic information - Row 18-21
    // Group/Org Name
    $sheet->setCellValue('D18', $booking['organization'] ?? 'N/A');
    $sheet->setCellValue('E18', $booking['organization'] ?? 'N/A');
    $sheet->setCellValue('F18', $booking['organization'] ?? 'N/A');
    $sheet->setCellValue('G18', $booking['organization'] ?? 'N/A');
    
    // Name
    $sheet->setCellValue('D19', $booking['client_name']);
    $sheet->setCellValue('E19', $booking['client_name']);
    $sheet->setCellValue('F19', $booking['client_name']);
    $sheet->setCellValue('G19', $booking['client_name']);
    
    // Address
    $sheet->setCellValue('D20', $booking['address'] ?? 'N/A');
    
    // Contact Number
    $sheet->setCellValue('D21', $booking['contact_number']);
    $sheet->setCellValue('E21', $booking['contact_number']);
    $sheet->setCellValue('F21', $booking['contact_number']);
    $sheet->setCellValue('G21', $booking['contact_number']);
    
    // Name of Event - Row 23
    $sheet->setCellValue('D23', $booking['event_title']);
    $sheet->setCellValue('E23', $booking['event_title']);
    $sheet->setCellValue('F23', $booking['event_title']);
    $sheet->setCellValue('G23', $booking['event_title']);
    
    // Nature of Event - Set checkboxes only (no labels)
    $sheet->setCellValue('B25', '☐');
    $sheet->setCellValue('B26', '☐');
    $sheet->setCellValue('B27', '☐');
    $sheet->setCellValue('B28', '☐');
    $sheet->setCellValue('E25', '☐');
    $sheet->setCellValue('E26', '☐');
    $sheet->setCellValue('E27', '☐');
    
    // Determine event type based on event title
    $eventTitle = strtolower($booking['event_title']);
    if (strpos($eventTitle, 'meeting') !== false || strpos($eventTitle, 'assembly') !== false) {
        $sheet->setCellValue('B25', '☑');
    } elseif (strpos($eventTitle, 'symposium') !== false || strpos($eventTitle, 'talk') !== false) {
        $sheet->setCellValue('B26', '☑');
    } elseif (strpos($eventTitle, 'seminar') !== false || strpos($eventTitle, 'workshop') !== false) {
        $sheet->setCellValue('B27', '☑');
    } elseif (strpos($eventTitle, 'exhibit') !== false) {
        $sheet->setCellValue('B28', '☑');
    } elseif (strpos($eventTitle, 'reception') !== false) {
        $sheet->setCellValue('E25', '☑');
    } elseif (strpos($eventTitle, 'concert') !== false || strpos($eventTitle, 'performance') !== false) {
        $sheet->setCellValue('E26', '☑');
    } else {
        $sheet->setCellValue('E27', '☑');
    }
    
    // Purpose - Row 28
    $purpose = $booking['special_requirements'] ?: 'N/A';
    $sheet->setCellValue('G28', $purpose);
    
    // Date - Row 29
    $formattedDate = date('F j, Y', strtotime($booking['event_date']));
    $sheet->setCellValue('D29', $formattedDate);
    $sheet->setCellValue('E29', $formattedDate);
    $sheet->setCellValue('F29', $formattedDate);
    $sheet->setCellValue('G29', $formattedDate);
    
    // Main Venue - Row 30
    $sheet->setCellValue('D30', $booking['facility_name']);
    $sheet->setCellValue('E30', $booking['facility_name']);
    $sheet->setCellValue('F30', $booking['facility_name']);
    $sheet->setCellValue('G30', $booking['facility_name']);
    
    // Duration - Row 32
    $sheet->setCellValue('D32', $booking['duration'] . ' hours');
    
    // Time - Row 32
    $timeRange = date('g:i A', strtotime($booking['event_time']));
    // Calculate end time
    $endTime = date('g:i A', strtotime($booking['event_time']) + ($booking['duration'] * 3600));
    $sheet->setCellValue('E32', $timeRange);
    $sheet->setCellValue('F32', $timeRange . ' - ' . $endTime);
    
    // Total Cost - Row 32 (excluding maintenance fee and equipment costs)
    $totalCost = floatval($booking['total_cost']);
    
    // Subtract maintenance fee
    $maintenanceFee = floatval($booking['maintenance_fee'] ?? 2000.00);
    $totalCost -= $maintenanceFee;
    
    // Subtract equipment costs
    if (isset($booking['equipment']) && !empty($booking['equipment'])) {
        foreach ($booking['equipment'] as $equipment) {
            $totalCost -= floatval($equipment['total_cost']);
        }
    }
    
    $sheet->setCellValue('G32', number_format($totalCost, 2));
    
    // Equipment Quantities - Rows 42-45
    if (isset($booking['equipment']) && !empty($booking['equipment'])) {
        foreach ($booking['equipment'] as $equipment) {
            $equipName = strtolower($equipment['name']);
            
            if (strpos($equipName, 'table') !== false && strpos($equipName, 'cover') === false) {
                $sheet->setCellValue('D42', $equipment['quantity']);
            } elseif (strpos($equipName, 'chair') !== false && strpos($equipName, 'cover') === false) {
                $sheet->setCellValue('D43', $equipment['quantity']);
            } elseif (strpos($equipName, 'table cover') !== false) {
                $sheet->setCellValue('D44', $equipment['quantity']);
            } elseif (strpos($equipName, 'chair cover') !== false) {
                $sheet->setCellValue('D45', $equipment['quantity']);
            }
        }
    }
}
    /**
     * API endpoint for downloading equipment request form
     */
    public function downloadEquipmentRequestForm($bookingId)
    {
        return $this->generateEquipmentRequestForm($bookingId);
    }
}