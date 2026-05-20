<?php

namespace App\Controllers\Admin;

use App\Models\BookingModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InspectionEvaluationController extends Controller
{
    protected $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    public function generateInspectionEvaluation($bookingId)
    {
        try {
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                $response = service('response');
                return $response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            $templatePath = FCPATH . 'assets/templates/inspection_evaluation_template.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Inspection and Evaluation template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill template fields
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

            $filename = 'Inspection_Evaluation_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            $response = service('response');
            return $response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error generating Inspection and Evaluation form: ' . $e->getMessage());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate Inspection and Evaluation form: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}