<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\BookingExtensionModel;
use App\Models\FacilityModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class OrderOfPaymentController extends BaseController
{
    protected $bookingModel;
    protected $bookingExtensionModel;
    protected $facilityModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->bookingExtensionModel = new BookingExtensionModel();
        $this->facilityModel = new FacilityModel();
    }

    /**
     * Generate Order of Payment using template
     */
    public function generateOrderOfPayment($bookingId)
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
            $templatePath = FCPATH . 'assets/templates/order_of_payment_template.xlsx';

            if (!file_exists($templatePath)) {
                throw new \Exception('Order of Payment template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill in the template with booking data
            $this->fillOrderOfPaymentTemplate($sheet, $booking);

            // Generate filename
            $filename = 'Order_of_Payment_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
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
            log_message('error', 'Error generating Order of Payment: ' . $e->getMessage());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate Order of Payment: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Fill the Order of Payment template with booking data
     */
private function fillOrderOfPaymentTemplate($sheet, $booking)
{
    // Date (G8:H8) - Current date when downloaded with "Date: " prefix
    $currentDate = 'Date: ' . date('F j, Y'); // e.g., "Date: October 9, 2025"
    $sheet->setCellValue('G8', $currentDate);
    $sheet->setCellValue('H8', $currentDate);
    
    // Client Name (F15:H17) - Make it bold
    $clientName = $booking['client_name'];
    $sheet->setCellValue('F15', $clientName);
    $sheet->setCellValue('G15', $clientName);
    $sheet->setCellValue('H15', $clientName);
    $sheet->setCellValue('F16', $clientName);
    $sheet->setCellValue('G16', $clientName);
    $sheet->setCellValue('H16', $clientName);
    $sheet->setCellValue('F17', $clientName);
    $sheet->setCellValue('G17', $clientName);
    $sheet->setCellValue('H17', $clientName);
    
    // Apply bold formatting to name cells
    $sheet->getStyle('F15:H17')->getFont()->setBold(true);
    
    // Total amount in words (D21:H21)
    $totalAmount = floatval($booking['total_cost']);
    $amountInWords = $this->convertNumberToWords($totalAmount);
    $sheet->setCellValue('D21', $amountInWords);
    $sheet->setCellValue('E21', $amountInWords);
    $sheet->setCellValue('F21', $amountInWords);
    $sheet->setCellValue('G21', $amountInWords);
    $sheet->setCellValue('H21', $amountInWords);
    
    // Payment description (D22:H22, D23:H24 if needed)
    $description = $this->buildPaymentDescription($booking);
    
    // Split description if too long
    if (strlen($description) > 100) {
        // Split into multiple lines
        $words = explode(' ', $description);
        $line1 = '';
        $line2 = '';
        $currentLine = 1;
        
        foreach ($words as $word) {
            if ($currentLine === 1 && strlen($line1 . ' ' . $word) < 100) {
                $line1 .= ($line1 ? ' ' : '') . $word;
            } else {
                $currentLine = 2;
                $line2 .= ($line2 ? ' ' : '') . $word;
            }
        }
        
        // Fill row 22
        $sheet->setCellValue('D22', $line1);
        $sheet->setCellValue('E22', $line1);
        $sheet->setCellValue('F22', $line1);
        $sheet->setCellValue('G22', $line1);
        $sheet->setCellValue('H22', $line1);
        
        // Fill rows 23-24 if needed (D23:H24)
        if ($line2) {
            $sheet->setCellValue('D23', $line2);
            $sheet->setCellValue('E23', $line2);
            $sheet->setCellValue('F23', $line2);
            $sheet->setCellValue('G23', $line2);
            $sheet->setCellValue('H23', $line2);
            $sheet->setCellValue('D24', $line2);
            $sheet->setCellValue('E24', $line2);
            $sheet->setCellValue('F24', $line2);
            $sheet->setCellValue('G24', $line2);
            $sheet->setCellValue('H24', $line2);
        }
    } else {
        // Fits in one line
        $sheet->setCellValue('D22', $description);
        $sheet->setCellValue('E22', $description);
        $sheet->setCellValue('F22', $description);
        $sheet->setCellValue('G22', $description);
        $sheet->setCellValue('H22', $description);
    }
    
    // Total Amount (G31:H31)
    $formattedAmount = number_format($totalAmount, 2);
    $sheet->setCellValue('G31', $formattedAmount);
    $sheet->setCellValue('H31', $formattedAmount);
    
    // Apply number formatting
    $sheet->getStyle('G31:H31')->getNumberFormat()
          ->setFormatCode('#,##0.00');
}
    
    /**
     * Build payment description from facility and equipment
     */
private function buildPaymentDescription($booking)
{
    $description = 'Rental of ' . $booking['facility_name'];
    
    // Add equipment if any (without quantities)
    if (isset($booking['equipment']) && !empty($booking['equipment'])) {
        $equipmentList = [];
        foreach ($booking['equipment'] as $equipment) {
            if ($equipment['quantity'] > 0) {
                // Just add equipment name without quantity
                $equipmentList[] = $equipment['name'];
            }
        }
        
        if (!empty($equipmentList)) {
            $description .= ' with ' . implode(', ', $equipmentList);
        }
    }
    
    // Add event date
    $eventDate = date('F j, Y', strtotime($booking['event_date']));
    $description .= ' for ' . $eventDate;
    
    return $description;
}
    
    /**
     * Convert number to words (Philippine Peso format)
     */
    private function convertNumberToWords($number)
    {
        $number = floatval($number);
        $pesos = floor($number);
        $centavos = round(($number - $pesos) * 100);
        
        $words = $this->numberToWords($pesos);
        
        if ($centavos > 0) {
            return strtoupper($words . ' PESOS AND ' . $this->numberToWords($centavos) . ' CENTAVOS ONLY');
        } else {
            return strtoupper($words . ' PESOS ONLY');
        }
    }
    
    /**
     * Helper function to convert numbers to words
     */
    private function numberToWords($number)
    {
        $ones = array(
            0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR',
            5 => 'FIVE', 6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE',
            10 => 'TEN', 11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN',
            14 => 'FOURTEEN', 15 => 'FIFTEEN', 16 => 'SIXTEEN', 17 => 'SEVENTEEN',
            18 => 'EIGHTEEN', 19 => 'NINETEEN'
        );
        
        $tens = array(
            0 => '', 2 => 'TWENTY', 3 => 'THIRTY', 4 => 'FORTY', 5 => 'FIFTY',
            6 => 'SIXTY', 7 => 'SEVENTY', 8 => 'EIGHTY', 9 => 'NINETY'
        );
        
        if ($number < 20) {
            return $ones[$number];
        } elseif ($number < 100) {
            return $tens[floor($number / 10)] . ($number % 10 != 0 ? ' ' . $ones[$number % 10] : '');
        } elseif ($number < 1000) {
            return $ones[floor($number / 100)] . ' HUNDRED' . ($number % 100 != 0 ? ' ' . $this->numberToWords($number % 100) : '');
        } elseif ($number < 1000000) {
            return $this->numberToWords(floor($number / 1000)) . ' THOUSAND' . ($number % 1000 != 0 ? ' ' . $this->numberToWords($number % 1000) : '');
        } elseif ($number < 1000000000) {
            return $this->numberToWords(floor($number / 1000000)) . ' MILLION' . ($number % 1000000 != 0 ? ' ' . $this->numberToWords($number % 1000000) : '');
        }
        
        return '';
    }

    /**
     * API endpoint for downloading Order of Payment
     */
    public function downloadOrderOfPayment($bookingId)
    {
        return $this->generateOrderOfPayment($bookingId);
    }

    /**
     * Generate Order of Payment for booking extension only
     * Used when approving an extension - generates payment order with only extension hours cost
     */
    public function generateExtensionOrderOfPayment($extensionId)
    {
        try {
            log_message('info', "Generating extension order of payment for extension ID: $extensionId");
            
            // Get extension details with related booking and facility info
            $extension = $this->bookingExtensionModel->getExtensionWithDetails($extensionId);
            
            log_message('info', "Extension data retrieved: " . json_encode($extension ? array_keys($extension) : 'NULL'));
            
            if (!$extension) {
                log_message('error', "Extension not found with ID: $extensionId");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Extension not found'
                ])->setStatusCode(404);
            }

            // Get booking details for client info
            $booking = $this->bookingModel->find($extension['booking_id']);
            
            if (!$booking) {
                log_message('error', "Booking not found with ID: {$extension['booking_id']}");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Get facility details for facility name
            $facility = $this->facilityModel->find($booking['facility_id']);
            
            if (!$facility) {
                log_message('error', "Facility not found with ID: {$booking['facility_id']}");
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility not found'
                ])->setStatusCode(404);
            }

            // Ensure facility_name is in the extension data for the template
            $extension['facility_name'] = $facility['name'];
            $booking['facility_name'] = $facility['name'];
            
            log_message('info', "About to fill template with extension cost: {$extension['extension_cost']}");

            // Load the template
            $templatePath = FCPATH . 'assets/templates/order_of_payment_template.xlsx';

            if (!file_exists($templatePath)) {
                throw new \Exception('Order of Payment template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill in the template with extension data (only extension cost)
            $this->fillExtensionOrderOfPaymentTemplate($sheet, $booking, $extension);

            // Generate filename
            $filename = 'Extension_Payment_EXT' . str_pad($extension['id'], 3, '0', STR_PAD_LEFT) . '_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the filled template
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            log_message('info', "Extension order of payment saved to: $filepath");

            // Return file for download
            $response = service('response');
            return $response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error generating Extension Order of Payment: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate Extension Order of Payment: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Fill Order of Payment template for extension ONLY (not including original booking cost)
     */
    private function fillExtensionOrderOfPaymentTemplate($sheet, $booking, $extension)
    {
        try {
            // Safely get facility name from multiple sources
            $facilityName = 'Unknown Facility';
            
            if (!empty($extension['facility_name'])) {
                $facilityName = $extension['facility_name'];
            } elseif (!empty($booking['facility_name'])) {
                $facilityName = $booking['facility_name'];
            }
            
            log_message('info', "Filling template with facility name: $facilityName");
            
            // Date (G8:H8) - Current date when downloaded with "Date: " prefix
            $currentDate = 'Date: ' . date('F j, Y');
            $sheet->setCellValue('G8', $currentDate);
            $sheet->setCellValue('H8', $currentDate);
            
            // Client Name (F15:H17) - Make it bold
            $clientName = $booking['client_name'] ?? 'Unknown Client';
            $sheet->setCellValue('F15', $clientName);
            $sheet->setCellValue('G15', $clientName);
            $sheet->setCellValue('H15', $clientName);
            $sheet->setCellValue('F16', $clientName);
            $sheet->setCellValue('G16', $clientName);
            $sheet->setCellValue('H16', $clientName);
            $sheet->setCellValue('F17', $clientName);
            $sheet->setCellValue('G17', $clientName);
            $sheet->setCellValue('H17', $clientName);
            
            // Apply bold formatting to name cells
            $sheet->getStyle('F15:H17')->getFont()->setBold(true);
            
            // Total amount in words (D21:H21) - USE ONLY EXTENSION COST
            $extensionCost = floatval($extension['extension_cost'] ?? 0);
            $amountInWords = $this->convertNumberToWords($extensionCost);
            $sheet->setCellValue('D21', $amountInWords);
            $sheet->setCellValue('E21', $amountInWords);
            $sheet->setCellValue('F21', $amountInWords);
            $sheet->setCellValue('G21', $amountInWords);
            $sheet->setCellValue('H21', $amountInWords);
            
            // Payment description (D22:H22, D23:H24 if needed) - FOR EXTENSION ONLY
            $description = $this->buildExtensionPaymentDescription(
                $facilityName, 
                $booking, 
                $extension
            );
            
            log_message('info', "Extension payment description: $description");
            
            // Split description if too long
            if (strlen($description) > 100) {
                // Split into multiple lines
                $words = explode(' ', $description);
                $line1 = '';
                $line2 = '';
                $currentLine = 1;
                
                foreach ($words as $word) {
                    if ($currentLine === 1 && strlen($line1 . ' ' . $word) < 100) {
                        $line1 .= ($line1 ? ' ' : '') . $word;
                    } else {
                        $currentLine = 2;
                        $line2 .= ($line2 ? ' ' : '') . $word;
                    }
                }
                
                // Fill row 22
                $sheet->setCellValue('D22', $line1);
                $sheet->setCellValue('E22', $line1);
                $sheet->setCellValue('F22', $line1);
                $sheet->setCellValue('G22', $line1);
                $sheet->setCellValue('H22', $line1);
                
                // Fill rows 23-24 if needed (D23:H24)
                if ($line2) {
                    $sheet->setCellValue('D23', $line2);
                    $sheet->setCellValue('E23', $line2);
                    $sheet->setCellValue('F23', $line2);
                    $sheet->setCellValue('G23', $line2);
                    $sheet->setCellValue('H23', $line2);
                    $sheet->setCellValue('D24', $line2);
                    $sheet->setCellValue('E24', $line2);
                    $sheet->setCellValue('F24', $line2);
                    $sheet->setCellValue('G24', $line2);
                    $sheet->setCellValue('H24', $line2);
                }
            } else {
                // Fits in one line
                $sheet->setCellValue('D22', $description);
                $sheet->setCellValue('E22', $description);
                $sheet->setCellValue('F22', $description);
                $sheet->setCellValue('G22', $description);
                $sheet->setCellValue('H22', $description);
            }
            
            // Total Amount (G31:H31) - USE ONLY EXTENSION COST
            $formattedAmount = number_format($extensionCost, 2);
            $sheet->setCellValue('G31', $formattedAmount);
            $sheet->setCellValue('H31', $formattedAmount);
            
            // Apply number formatting
            $sheet->getStyle('G31:H31')->getNumberFormat()
                  ->setFormatCode('#,##0.00');
        } catch (\Exception $e) {
            log_message('error', 'Error filling extension template: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build payment description for extension ONLY
     */
    private function buildExtensionPaymentDescription($facilityName, $booking, $extension)
    {
        // Description: Extension of [facility] rental for additional [hours] hours
        $description = 'Extension of ' . $facilityName . ' rental for additional ' . 
                      $extension['extension_hours'] . ' hour' . ($extension['extension_hours'] != 1 ? 's' : '');
        
        // Add original event date reference
        $eventDate = date('F j, Y', strtotime($booking['event_date']));
        $description .= ' (Original event date: ' . $eventDate . ')';
        
        return $description;
    }

    /**
     * API endpoint for downloading Extension Order of Payment
     */
    public function downloadExtensionOrderOfPayment($extensionId)
    {
        return $this->generateExtensionOrderOfPayment($extensionId);
    }
}
