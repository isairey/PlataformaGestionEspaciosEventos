<?php

namespace App\Controllers\Admin;

use App\Models\BookingModel;
use CodeIgniter\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FacultyEvaluationController extends Controller
{
    protected $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    public function generateFacultyEvaluation($bookingId)
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

            $templatePath = FCPATH . 'assets/templates/faculty_evaluation_template.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Faculty Evaluation template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill template - C11, E11, C12
            $eventDate = new \DateTime($booking['event_date']);
            $sheet->setCellValue('C11', $booking['client_name']);
            $sheet->setCellValue('E11', $eventDate->format('F j, Y'));
            $sheet->setCellValue('C12', $booking['event_title']);

            $filename = 'Faculty_Evaluation_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Use service('response') like in EquipmentRequestController
            $response = service('response');
            return $response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error generating Faculty Evaluation: ' . $e->getMessage());
            
            $response = service('response');
            return $response->setJSON([
                'success' => false,
                'message' => 'Failed to generate Faculty Evaluation: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}