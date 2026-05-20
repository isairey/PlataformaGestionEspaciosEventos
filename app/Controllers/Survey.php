<?php

namespace App\Controllers;

use App\Models\SurveyModel;
use App\Models\BookingModel;

// Manually load helper since autoloader may not catch it
require_once APPPATH . 'Helpers/ExcelSurveyGenerator.php';
use App\Helpers\ExcelSurveyGenerator;

class Survey extends BaseController
{
    protected $surveyModel;
    protected $bookingModel;

    public function __construct()
    {
        $this->surveyModel = new SurveyModel();
        $this->bookingModel = new BookingModel();
    }

    /**
     * Display survey form using unique token
     */
    public function index($token = null)
    {
        if (!$token) {
            return view('survey/invalid_token');
        }

        // Get survey by token
        $survey = $this->surveyModel->getByToken($token);

        if (!$survey) {
            return view('survey/invalid_token');
        }

        // Get booking details
        $booking = $this->bookingModel->find($survey['booking_id']);

        if (!$booking) {
            return view('survey/invalid_token');
        }

        // If survey already submitted, show thank you
        if ($survey['staff_punctuality'] !== null) {
            return view('survey/already_submitted', [
                'booking' => $booking,
                'survey' => $survey
            ]);
        }

        return view('survey/facilityEvaluation', [
            'booking' => $booking,
            'survey' => $survey,
            'token' => $token
        ]);
    }

    /**
     * Submit survey response
     */
    public function submit()
    {
        try {
            $token = $this->request->getPost('survey_token');
            log_message('info', '=== SURVEY SUBMIT START ===');
            log_message('info', 'Token received: ' . ($token ? substr($token, 0, 20) . '...' : 'NONE'));

            if (!$token) {
                log_message('error', 'No survey token provided');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid survey token'
                ]);
            }

            // Get survey by token
            $survey = $this->surveyModel->getByToken($token);
            log_message('info', 'Survey lookup result: ' . ($survey ? 'Found' : 'Not Found'));

            if (!$survey) {
                log_message('error', 'Survey not found for token: ' . substr($token, 0, 20));
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Survey not found'
                ]);
            }

            log_message('info', 'Survey found - Booking ID: ' . $survey['booking_id']);

            // If already submitted, return error
            if ($survey['staff_punctuality'] !== null) {
                log_message('warning', 'Survey already submitted for booking #' . $survey['booking_id']);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This survey has already been submitted'
                ]);
            }

            // Collect all POST data and filter for database columns
            $allPostData = $this->request->getPost();
            
            log_message('debug', 'All POST data received: ' . json_encode($allPostData));
            
            $surveyData = [];
            
            // STAFF SECTION - Direct mapping
            if (isset($allPostData['staff_punctuality'])) {
                $surveyData['staff_punctuality'] = $allPostData['staff_punctuality'];
            }
            if (isset($allPostData['staff_courtesy_property'])) {
                $surveyData['staff_courtesy_property'] = $allPostData['staff_courtesy_property'];
            }
            if (isset($allPostData['staff_courtesy_audio'])) {
                $surveyData['staff_courtesy_audio'] = $allPostData['staff_courtesy_audio'];
            }
            if (isset($allPostData['staff_courtesy_janitor'])) {
                $surveyData['staff_courtesy_janitor'] = $allPostData['staff_courtesy_janitor'];
            }
            
            // FACILITY SECTION - Direct mapping
            if (isset($allPostData['facility_level_expectations'])) {
                $surveyData['facility_level_expectations'] = $allPostData['facility_level_expectations'];
            }
            
            // FACILITY CLEANLINESS - Combine multiple fields
            $cleanlinessValues = [];
            if (isset($allPostData['facility_cleanliness_function_hall'])) {
                $cleanlinessValues[] = $allPostData['facility_cleanliness_function_hall'];
            }
            if (isset($allPostData['facility_cleanliness_classrooms'])) {
                $cleanlinessValues[] = $allPostData['facility_cleanliness_classrooms'];
            }
            if (isset($allPostData['facility_cleanliness_restrooms'])) {
                $cleanlinessValues[] = $allPostData['facility_cleanliness_restrooms'];
            }
            if (isset($allPostData['facility_cleanliness_reception'])) {
                $cleanlinessValues[] = $allPostData['facility_cleanliness_reception'];
            }
            if (!empty($cleanlinessValues)) {
                $surveyData['facility_cleanliness'] = implode(' | ', $cleanlinessValues);
            }
            
            // EQUIPMENT/FACILITY MAINTENANCE - Combine all equipment fields
            $maintenanceValues = [];
            $equipmentFields = [
                'equipment_airconditioning',
                'equipment_lighting',
                'equipment_electric_fans',
                'equipment_tables',
                'equipment_monobloc_chairs',
                'equipment_chair_cover',
                'equipment_podium',
                'equipment_multimedia_projector',
                'equipment_sound_system',
                'equipment_microphone',
                'equipment_others'
            ];
            
            foreach ($equipmentFields as $field) {
                if (isset($allPostData[$field]) && $allPostData[$field] !== '') {
                    $maintenanceValues[] = $allPostData[$field];
                }
            }
            
            if (!empty($maintenanceValues)) {
                $surveyData['facility_maintenance'] = implode(' | ', $maintenanceValues);
            }
            
            // OVERALL EXPERIENCE - Combine satisfaction responses
            $satisfactionValues = [];
            if (isset($allPostData['overall_would_rent_again'])) {
                $satisfactionValues[] = $allPostData['overall_would_rent_again'];
            }
            if (isset($allPostData['overall_would_recommend'])) {
                $satisfactionValues[] = $allPostData['overall_would_recommend'];
            }
            if (!empty($satisfactionValues)) {
                $surveyData['overall_satisfaction'] = implode(' | ', $satisfactionValues);
            }
            
            // VENUE ACCURACY - How found facility
            if (isset($allPostData['overall_how_found_facility'])) {
                $surveyData['venue_accuracy_setup'] = $allPostData['overall_how_found_facility'];
            }
            
            // COMMENTS - Capture as improvements_needed and most_enjoyed
            if (isset($allPostData['comments_suggestions']) && $allPostData['comments_suggestions'] !== '') {
                $surveyData['most_enjoyed'] = $allPostData['comments_suggestions'];
                $surveyData['improvements_needed'] = $allPostData['comments_suggestions'];
            }

            // Add submission flag
            $surveyData['is_submitted'] = 1;

            log_message('debug', 'Mapped survey data: ' . json_encode($surveyData));
            log_message('info', 'Survey data field count: ' . count($surveyData));

            if (count($surveyData) <= 1) { // Only has is_submitted
                log_message('error', 'No valid survey data - only is_submitted flag present');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No survey data submitted'
                ]);
            }

            // Update survey response
            log_message('info', 'About to update survey - Data count: ' . count($surveyData) . ', Data: ' . json_encode($surveyData));
            $updated = $this->surveyModel->updateSurvey($survey['booking_id'], $surveyData);

            if ($updated) {
                log_message('info', "Survey submitted for booking #{$survey['booking_id']} with token: {$token}");
                
                // Generate Excel file with survey responses
                try {
                    // Generate the Excel file using the helper
                    $result = ExcelSurveyGenerator::generateEvaluationForm($survey['booking_id'], $surveyData);
                    
                    if ($result['success']) {
                        log_message('info', 'Excel evaluation form generated: ' . $result['filepath']);
                    } else {
                        log_message('warning', 'Excel generation returned error: ' . ($result['message'] ?? 'Unknown error'));
                    }
                    
                } catch (\Exception $e) {
                    log_message('error', 'Failed to generate Excel file: ' . $e->getMessage());
                    // Don't fail the survey submission if Excel generation fails
                }
                
                log_message('info', '=== SURVEY SUBMIT SUCCESS ===');
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Thank you! Your survey has been submitted successfully',
                    'redirect' => base_url('survey/thank-you')
                ]);
            } else {
                log_message('error', 'Survey update returned false for booking #' . $survey['booking_id']);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to submit survey. Please try again.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('critical', 'Survey submit exception: ' . $e->getMessage());
            log_message('critical', 'Exception stack: ' . $e->getTraceAsString());
            log_message('info', '=== SURVEY SUBMIT ERROR ===');
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Thank you page
     */
    public function thankYou()
    {
        return view('survey/thank_you');
    }

    /**
     * API: Get survey by booking ID (admin only)
     */
    public function getSurvey($bookingId)
    {
        if (!$this->isAdmin()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(403);
        }

        $survey = $this->surveyModel->getByBookingId($bookingId);

        if (!$survey) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Survey not found'
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $survey
        ]);
    }

    /**
     * Check if user is admin
     */
    private function isAdmin()
    {
        $userRole = session('role');
        return $userRole === 'admin' || $userRole === 'super_admin';
    }

    /**
     * API: Get evaluation files for booking
     */
    public function getEvaluationFiles($bookingId)
    {
        $files = [];
        $uploadsDir = WRITEPATH . 'temp/';
        
        // Look for Excel evaluation files matching the booking ID
        if (is_dir($uploadsDir)) {
            $pattern = 'Faculty_Evaluation_BK' . str_pad($bookingId, 3, '0', STR_PAD_LEFT) . '_*.xlsx';
            $matchedFiles = glob($uploadsDir . $pattern);
            
            if ($matchedFiles) {
                foreach ($matchedFiles as $filepath) {
                    $filename = basename($filepath);
                    $files[] = [
                        'name' => $filename,
                        'path' => $filename,
                        'size' => filesize($filepath),
                        'created' => filemtime($filepath),
                        'url' => base_url('api/bookings/evaluation-file/' . urlencode($filename))
                    ];
                }
                
                // Sort by creation date (newest first)
                usort($files, function($a, $b) {
                    return $b['created'] - $a['created'];
                });
            }
        }
        
        return $this->response->setJSON([
            'success' => true,
            'files' => $files,
            'count' => count($files)
        ]);
    }

    /**
     * Download evaluation file
     */
    public function downloadEvaluationFile($filename)
    {
        $filename = basename($filename); // Prevent directory traversal
        $filepath = WRITEPATH . 'temp/' . $filename;

        log_message('info', '=== EVALUATION FILE DOWNLOAD STARTED ===');
        log_message('info', 'Requested filename: ' . $filename);
        log_message('info', 'Full filepath: ' . $filepath);
        log_message('info', 'WRITEPATH: ' . WRITEPATH);
        log_message('info', 'Base URL: ' . base_url());
        log_message('info', 'Current URL: ' . current_url());

        // Check if temp directory exists
        $uploadsDir = WRITEPATH . 'temp/';
        if (!is_dir($uploadsDir)) {
            log_message('error', 'Temp directory does not exist: ' . $uploadsDir);
            log_message('error', 'WRITEPATH value: ' . WRITEPATH);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Temp directory not found',
                'writepath' => WRITEPATH,
                'expected_dir' => $uploadsDir
            ]);
        }
        log_message('info', 'Temp directory exists and is readable');

        // Verify file exists and matches pattern
        if (!file_exists($filepath)) {
            log_message('error', 'Evaluation file not found at path: ' . $filepath);

            // List files in directory for debugging
            $files = scandir($uploadsDir);
            log_message('info', 'Files in temp directory: ' . json_encode($files));
            log_message('info', 'Number of files found: ' . count($files));
            
            // Look for similar files
            $pattern = 'Faculty_Evaluation_*';
            $similarFiles = glob($uploadsDir . $pattern);
            log_message('info', 'Similar files matching pattern "' . $pattern . '": ' . json_encode($similarFiles));

            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'File not found',
                'filepath' => $filepath,
                'requested_filename' => $filename,
                'directory_contents' => $files,
                'similar_files' => $similarFiles
            ]);
        }

        log_message('info', 'File exists at: ' . $filepath);

        // Get file info
        $fileSize = filesize($filepath);
        $isReadable = is_readable($filepath);
        $filePerms = substr(sprintf('%o', fileperms($filepath)), -4);
        log_message('info', 'File size: ' . $fileSize . ' bytes');
        log_message('info', 'File readable: ' . ($isReadable ? 'YES' : 'NO'));
        log_message('info', 'File permissions: ' . $filePerms);

        if (!$isReadable) {
            log_message('error', 'File is not readable: ' . $filepath);
            log_message('error', 'File permissions: ' . $filePerms);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'File is not readable',
                'file_permissions' => $filePerms
            ]);
        }

        // Verify it's an evaluation file
        if (strpos($filename, 'Faculty_Evaluation_BK') !== 0) {
            log_message('error', 'Invalid evaluation file requested: ' . $filename);
            log_message('warning', 'File does not start with Faculty_Evaluation_BK');
            return $this->response->setStatusCode(403)->setJSON([
                'error' => 'Access denied - Invalid file pattern',
                'filename' => $filename
            ]);
        }

        log_message('info', 'File validation passed - file matches expected pattern');

        try {
            // Check file extension
            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            log_message('info', 'File extension: ' . $extension);
            
            if ($extension !== 'xlsx' && $extension !== 'xls') {
                log_message('warning', 'Unusual file extension detected: ' . $extension);
            }

            // Detect MIME type
            $mime = mime_content_type($filepath);
            if (!$mime) {
                $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                log_message('info', 'mime_content_type() returned false, using default MIME type: ' . $mime);
            } else {
                log_message('info', 'Detected MIME type: ' . $mime);
            }

            log_message('info', 'Reading file content...');

            // Read file content
            $fileContent = file_get_contents($filepath);
            if ($fileContent === false) {
                log_message('error', 'Failed to read file content using file_get_contents(): ' . $filepath);
                return $this->response->setStatusCode(500)->setJSON([
                    'error' => 'Failed to read file',
                    'filepath' => $filepath
                ]);
            }

            $contentLength = strlen($fileContent);
            log_message('info', 'File content read successfully. Content length: ' . $contentLength . ' bytes');

            if ($contentLength === 0) {
                log_message('error', 'File content is empty!');
                return $this->response->setStatusCode(500)->setJSON([
                    'error' => 'File is empty',
                    'filepath' => $filepath,
                    'actual_file_size' => $fileSize
                ]);
            }

            if ($contentLength !== $fileSize) {
                log_message('warning', 'Content length mismatch - Expected: ' . $fileSize . ', Got: ' . $contentLength);
            }

            log_message('info', 'Setting response headers...');
            log_message('info', 'Content-Type: ' . $mime);
            log_message('info', 'Content-Disposition: attachment; filename="' . $filename . '"');
            log_message('info', 'Content-Length: ' . $contentLength);

            $response = $this->response
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setHeader('Content-Length', $contentLength)
                ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0')
                ->setBody($fileContent);

            log_message('info', 'Response prepared successfully');
            log_message('info', '=== EVALUATION FILE DOWNLOAD COMPLETED SUCCESSFULLY ===');

            return $response;
        } catch (\Exception $e) {
            log_message('error', 'Exception during download: ' . $e->getMessage());
            log_message('error', 'Exception code: ' . $e->getCode());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Download failed: ' . $e->getMessage(),
                'exception_code' => $e->getCode()
            ]);
        }
    }
}
