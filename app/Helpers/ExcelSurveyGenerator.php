<?php

namespace App\Helpers;

use App\Models\BookingModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelSurveyGenerator
{
    public static function generateEvaluationForm($bookingId, $surveyData)
    {
        try {
            $bookingModel = new BookingModel();
            $booking = $bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return [
                    'success' => false,
                    'message' => 'Booking not found'
                ];
            }

            $templatePath = FCPATH . 'assets/templates/faculty_evaluation_template.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Faculty Evaluation template not found at: ' . $templatePath);
            }

            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill template - C11, E11, C12
            $eventDate = new \DateTime($booking['event_date']);
            $sheet->setCellValue('C11', $booking['client_name']);
            $sheet->setCellValue('E11', $eventDate->format('F j, Y'));
            $sheet->setCellValue('C12', $booking['event_title']);

            // STAFF SECTION
            $rating = $surveyData['staff_punctuality'] ?? null;
            self::placeRatingMark($sheet, 16, $rating);

            $rating = $surveyData['staff_courtesy_property'] ?? null;
            self::placeRatingMark($sheet, 18, $rating);

            $rating = $surveyData['staff_courtesy_audio'] ?? null;
            self::placeRatingMark($sheet, 19, $rating);

            $rating = $surveyData['staff_courtesy_janitor'] ?? null;
            self::placeRatingMark($sheet, 20, $rating);

            // FACILITY SECTION
            $rating = $surveyData['facility_level_expectations'] ?? null;
            self::placeRatingMark($sheet, 22, $rating);

            $cleanlinessValues = isset($surveyData['facility_cleanliness']) 
                ? explode('|', $surveyData['facility_cleanliness']) 
                : [];
            
            if (isset($cleanlinessValues[0])) {
                self::placeRatingMark($sheet, 24, trim($cleanlinessValues[0]));
            }

            if (isset($cleanlinessValues[1])) {
                self::placeRatingMark($sheet, 25, trim($cleanlinessValues[1]));
            }

            if (isset($cleanlinessValues[2])) {
                self::placeRatingMark($sheet, 26, trim($cleanlinessValues[2]));
            }

            // EQUIPMENT SECTION
            $equipmentValues = isset($surveyData['facility_maintenance']) 
                ? explode('|', $surveyData['facility_maintenance']) 
                : [];
            
            $startRow = 28;
            foreach ($equipmentValues as $index => $rating) {
                $row = $startRow + $index;
                if ($row <= 38) {
                    self::placeRatingMark($sheet, $row, trim($rating));
                }
            }

            // OVERALL EXPERIENCE
            $rentAgain = $surveyData['overall_satisfaction'] ?? null;
            if ($rentAgain) {
                $values = explode('|', $rentAgain);
                if (isset($values[0])) {
                    $answer = trim($values[0]);
                    if ($answer === 'Yes') {
                        $sheet->setCellValue('D41', '/');
                    } elseif ($answer === 'No') {
                        $sheet->setCellValue('G41', '/');
                    }
                }
            }

            if ($rentAgain) {
                $values = explode('|', $rentAgain);
                if (isset($values[1])) {
                    $answer = trim($values[1]);
                    if ($answer === 'Yes') {
                        $sheet->setCellValue('D42', '/');
                    } elseif ($answer === 'No') {
                        $sheet->setCellValue('G42', '/');
                    }
                }
            }

            $howFound = $surveyData['venue_accuracy_setup'] ?? null;
            if ($howFound) {
                $howFound = trim($howFound);
                if (stripos($howFound, 'Website') !== false) {
                    $sheet->setCellValue('D43', '/');
                } elseif (stripos($howFound, 'Brochure') !== false) {
                    $sheet->setCellValue('D44', '/');
                } elseif (stripos($howFound, 'Friend') !== false) {
                    $sheet->setCellValue('D45', '/');
                } elseif (stripos($howFound, 'Others') !== false || stripos($howFound, 'Other') !== false) {
                    $sheet->setCellValue('D46', '/');
                }
            }

            // Save file
            $writer = new Xlsx($spreadsheet);
            
            $filename = 'Faculty_Evaluation_BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            $writer->save($filepath);

            // Cleanup
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            unset($writer);

            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            log_message('error', 'ExcelSurveyGenerator Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate Faculty Evaluation: ' . $e->getMessage()
            ];
        }
    }


    private static function placeRatingMark($sheet, $row, $ratingValue)
    {
        if (!$ratingValue) {
            return;
        }

        $ratingValue = strtolower(trim($ratingValue));

        $columnMap = [
            'excellent' => 'D',
            'very good' => 'E',
            'good' => 'F',
            'fair' => 'G',
            'poor' => 'H',
            'n/a' => 'I',
        ];

        $column = $columnMap[$ratingValue] ?? null;
        if ($column) {
            $cellAddress = $column . $row;
            $sheet->setCellValue($cellAddress, '/');
            $sheet->getStyle($cellAddress)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($cellAddress)->getFont()->setBold(true);
        }
    }
}