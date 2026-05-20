<?php

namespace App\Controllers\Admin;

use App\Models\BookingModel;
use CodeIgniter\Controller;
use PhpOffice\PhpWord\TemplateProcessor;

class MoaController extends Controller
{
    protected $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    /**
     * Generate MOA using template
     */
    public function generateMoa($bookingId)
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
            $templatePath = 'assets/templates/moa_template.docx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('MOA template not found at: ' . $templatePath);
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // Fill in the template with booking data
            $this->fillMoaTemplate($templateProcessor, $booking);

            // Generate filename
            $filename = 'MOA_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.docx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the filled template
            $templateProcessor->saveAs($filepath);

            // Return file for download
            $response = service('response');
            return $response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error generating MOA: ' . $e->getMessage());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate MOA: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get custom signatory name from the template document or use default
     */
    private function getSignatoryName($marker)
    {
        log_message('debug', '=== getSignatoryName called with marker: ' . $marker);
        
        try {
            // Read from database first
            $signatoriesModel = model('SignatoriesModel');
            $value = $signatoriesModel->getValue('moa_template.docx', $marker);
            
            log_message('debug', 'Database query result for ' . $marker . ': "' . $value . '"');
            
            if (!empty($value)) {
                log_message('info', 'Using database value for ' . $marker . ': ' . $value);
                return $value;
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception reading signatory from database: ' . $e->getMessage());
        }
        
        // Fallback to config default
        $moaConfig = config('MoaSignatories');
        foreach ($moaConfig::SIGNATORIES as $sig) {
            if ($sig['marker'] === $marker) {
                log_message('info', 'Using config default for ' . $marker . ': ' . $sig['default_name']);
                return $sig['default_name'];
            }
        }
        
        log_message('warning', 'No signatory found for marker: ' . $marker);
        return '';
    }

    /**
     * Fill the MOA template with booking data and signatory names
     */
    private function fillMoaTemplate($templateProcessor, $booking)
    {
        // Load signatory config
        $moaConfig = config('MoaSignatories');
        
        log_message('debug', '=== fillMoaTemplate starting ===');
        log_message('debug', 'MOA Config Signatories: ' . json_encode($moaConfig::SIGNATORIES));
        
        // Date components
        $eventDate = new \DateTime($booking['event_date']);
        $templateProcessor->setValue('DATE', $eventDate->format('j'));
        $templateProcessor->setValue('MONTH', $eventDate->format('F'));
        $templateProcessor->setValue('YEAR', $eventDate->format('Y'));
        
        // Party of the SECOND PART details
        $templateProcessor->setValue('ORGNAME', $booking['organization'] ?: 'N/A');
        $templateProcessor->setValue('ADDRESS', $booking['address'] ?: 'N/A');
        $templateProcessor->setValue('CLIENTNAME', $booking['client_name']);
        
        // Event details
        $templateProcessor->setValue('FACILITYNAME', $booking['facility_name']);
        $templateProcessor->setValue('EVENTNAME', $booking['event_title']);
        $templateProcessor->setValue('EVENTDATE', $eventDate->format('F j, Y'));
        
        // Fill signatory names from custom storage with fallback to defaults
        log_message('debug', '--- Processing signatories ---');
        foreach ($moaConfig::SIGNATORIES as $sig) {
            log_message('debug', 'Processing signatory: ' . $sig['marker']);
            $customName = $this->getSignatoryName($sig['marker']);
            log_message('debug', 'Final name for ' . $sig['marker'] . ': ' . $customName);
            // Remove ### markers for PhpWord setValue
            $placeholderKey = str_replace('###', '', $sig['marker']);
            log_message('debug', 'Setting placeholder "' . $placeholderKey . '" to "' . $customName . '"');
            $templateProcessor->setValue($placeholderKey, $customName);
        }
        log_message('debug', '--- Signatories processing complete ---');
        
        // Calculate costs
        $totalCost = floatval($booking['total_cost']);
        $maintenanceFee = floatval($booking['maintenance_fee'] ?? 2000.00);
        
        // Calculate venue rent (total - maintenance - equipment)
        $venueRent = $totalCost - $maintenanceFee;
        $equipmentTotal = 0;
        $equipmentNames = [];
        
        if (isset($booking['equipment']) && !empty($booking['equipment'])) {
            foreach ($booking['equipment'] as $equipment) {
                $equipmentTotal += floatval($equipment['total_cost']);
                $equipmentNames[] = $equipment['name'];
            }
            $venueRent -= $equipmentTotal;
        }
        
        // Venue rent with text and numerical format
        $venueRentText = $this->numberToWords($venueRent);
        $templateProcessor->setValue('VENUERENT', 
            ucfirst($venueRentText) . ' pesos only (₱' . number_format($venueRent, 2) . ')'
        );
        
        // Equipment rental
        if (!empty($equipmentNames)) {
            $equipmentList = implode(', ', $equipmentNames);
            $equipmentText = $this->numberToWords($equipmentTotal);
            $templateProcessor->setValue('EQUIPMENTNAMES', $equipmentList);
            $templateProcessor->setValue('EQUIPMENTTOTAL', 
                ucfirst($equipmentText) . ' pesos only (₱' . number_format($equipmentTotal, 2) . ')'
            );
        } else {
            $templateProcessor->setValue('EQUIPMENTNAMES', 'None');
            $templateProcessor->setValue('EQUIPMENTTOTAL', 'Zero pesos (₱0.00)');
        }
        
        // Overtime charges
        $overtimeCost = 0;
        if (isset($booking['addons']) && !empty($booking['addons'])) {
            foreach ($booking['addons'] as $addon) {
                if (stripos($addon['name'], 'overtime') !== false) {
                    $overtimeCost = floatval($addon['price']);
                    break;
                }
            }
        }
        
        if ($overtimeCost > 0) {
            $overtimeText = $this->numberToWords($overtimeCost);
            $templateProcessor->setValue('OVERTIMECOST', 
                ucfirst($overtimeText) . ' pesos only (₱' . number_format($overtimeCost, 2) . ')'
            );
        } else {
            $templateProcessor->setValue('OVERTIMECOST', 'Not applicable');
        }
        
        // Maintenance cost (always 2000)
        $templateProcessor->setValue('MAINTENANCECOST', 'Two thousand pesos only (₱2,000.00)');
    }
    
    /**
     * Convert number to words (Philippine Peso format)
     */
    private function numberToWords($number)
    {
        $number = floatval($number);
        
        if ($number == 0) {
            return 'zero';
        }
        
        $ones = [
            '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen'
        ];
        
        $tens = [
            '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
        ];
        
        $thousands = ['', 'thousand', 'million', 'billion'];
        
        // Split into pesos and centavos
        $pesos = floor($number);
        $centavos = round(($number - $pesos) * 100);
        
        $result = '';
        
        if ($pesos >= 1000000) {
            $millions = floor($pesos / 1000000);
            $result .= $this->convertHundreds($millions, $ones, $tens) . ' million ';
            $pesos %= 1000000;
        }
        
        if ($pesos >= 1000) {
            $thousands_part = floor($pesos / 1000);
            $result .= $this->convertHundreds($thousands_part, $ones, $tens) . ' thousand ';
            $pesos %= 1000;
        }
        
        $result .= $this->convertHundreds($pesos, $ones, $tens);
        
        if ($centavos > 0) {
            $result .= ' and ' . $this->convertHundreds($centavos, $ones, $tens) . ' centavos';
        }
        
        return trim($result);
    }
    
    /**
     * Convert hundreds place
     */
    private function convertHundreds($number, $ones, $tens)
    {
        $result = '';
        
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= $ones[$hundreds] . ' hundred ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $result .= $tens[floor($number / 10)] . ' ';
            $number %= 10;
        }
        
        if ($number > 0 && $number < 20) {
            $result .= $ones[$number] . ' ';
        }
        
        return trim($result);
    }

    /**
     * API endpoint for downloading MOA
     */
    public function downloadMoa($bookingId)
    {
        return $this->generateMoa($bookingId);
    }
}