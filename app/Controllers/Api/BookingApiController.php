<?php
namespace App\Controllers\Api;

helper('email'); 

use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\BookingAddonModel;
use App\Models\BookingEquipmentModel;
use CodeIgniter\RESTful\ResourceController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BookingApiController extends ResourceController
{
    protected $bookingModel;
    protected $facilityModel;
    protected $bookingAddonModel;
    protected $bookingEquipmentModel;
    protected $eventModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->facilityModel = new FacilityModel();
        $this->bookingAddonModel = new BookingAddonModel();
        $this->bookingEquipmentModel = new BookingEquipmentModel();
        $this->eventModel = new \App\Models\EventModel();
    }

    /**
     * Format booking type for display
     */
    private function formatBookingType($type)
    {
        switch($type) {
            case 'student':
                return 'Student';
            case 'employee':
                return 'Employee';
            case 'user':
            default:
                return 'User';
        }
    }

    /**
     * Check if a date/time has conflicts for a facility
     */
    public function checkDateConflict()
    {
        try {
            $request = $this->request->getJSON(true);
            
            // Validate required fields
            if (!isset($request['facility_id']) || !isset($request['event_date'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'facility_id and event_date are required',
                    'hasConflict' => false
                ])->setStatusCode(400);
            }

            $facilityId = $request['facility_id'];
            $eventDate = $request['event_date'];
            $eventTime = $request['event_time'] ?? '08:00'; // Default time if not provided
            $duration = $request['duration'] ?? 8; // Default duration if not provided

            // Check for bookings (pending, confirmed, approved) on this date
            $db = \Config\Database::connect();
            $hasBookings = $db->table('bookings')
                ->where('facility_id', $facilityId)
                ->where('event_date', $eventDate)
                ->whereIn('status', ['pending', 'confirmed', 'approved'])
                ->countAllResults();

            // Check for facility conflicts using EventModel
            $hasConflict = $this->eventModel->hasConflict(
                $facilityId,
                $eventDate,
                $eventTime,
                $duration
            );

            return $this->response->setJSON([
                'success' => true,
                'hasConflict' => $hasConflict || $hasBookings > 0,
                'hasPendingOrApprovedBooking' => $hasBookings > 0,
                'message' => ($hasConflict || $hasBookings > 0) ? 
                    'There is a pending or accepted booking on this date' : 
                    'Date is available'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error checking date conflict: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error checking date availability',
                'hasConflict' => true // Be safe and assume conflict
            ])->setStatusCode(500);
        }
    }

    /**
     * Get equipment availability for a specific date
     */
    public function equipmentAvailability()
    {
        try {
            // Try to get POST data from both JSON and form-encoded formats
            $eventDate = $this->request->getPost('event_date');
            $facilityId = $this->request->getPost('facility_id');
            
            // If not found in form data, try JSON
            if (!$eventDate) {
                $jsonData = $this->request->getJSON(true);
                $eventDate = $jsonData['event_date'] ?? null;
                $facilityId = $jsonData['facility_id'] ?? null;
            }
            
            if (!$eventDate) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'event_date is required'
                ])->setStatusCode(400);
            }
            
            $equipmentScheduleModel = new \App\Models\EquipmentScheduleModel();
            $equipmentModel = new \App\Models\EquipmentModel();
            
            // Get all rentable furniture equipment
            $allEquipment = $equipmentModel->where('is_rentable', 1)
                                           ->where('category', 'furniture')
                                           ->findAll();
            
            // Build equipment array with availability
            $equipment = [];
            $equipmentAvailability = [];
            
            foreach ($allEquipment as $eq) {
                // Get schedule for this equipment on this date
                $schedule = $equipmentScheduleModel->where('equipment_id', $eq['id'])
                                                    ->where('event_date', $eventDate)
                                                    ->first();
                
                if ($schedule) {
                    // Use saved available_quantity from database
                    $availableOnDate = (int)$schedule['available_quantity'];
                } else {
                    // If no schedule exists yet, create one with full inventory
                    $equipmentScheduleModel->getOrCreateSchedule($eq['id'], $eventDate);
                    $availableOnDate = (int)$eq['available'];
                }
                
                $equipment[] = [
                    'id' => (int)$eq['id'],
                    'name' => $eq['name'],
                    'rate' => (float)$eq['rate'],
                    'unit' => $eq['unit'] ?? 'piece',
                    'category' => $eq['category'],
                    'is_rentable' => (int)$eq['is_rentable'],
                    'available_on_date' => $availableOnDate,
                    'booked_quantity' => $schedule ? (int)$schedule['booked_quantity'] : 0
                ];
                
                // Also add to availability map for backward compatibility
                $equipmentAvailability[$eq['id']] = $availableOnDate;
            }
            
            return $this->response->setJSON([
                'success' => true,
                'event_date' => $eventDate,
                'equipment' => $equipment,
                'equipment_availability' => $equipmentAvailability
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting equipment availability: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get equipment availability',
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get all bookings with complete details
     */
    public function list()
    {
        try {
            // Auto-update expired bookings before returning list
            $this->bookingModel->autoUpdateExpiredBookings();
            
            $bookings = $this->bookingModel->getBookingsWithDetails();
            
            return $this->response->setJSON([
                'success' => true,
                'bookings' => $bookings
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching bookings: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch bookings'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get detailed booking information
     */
    public function detail($bookingId)
    {
        try {
            // Auto-update expired bookings
            $this->bookingModel->autoUpdateExpiredBookings();
            
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'success' => true,
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching booking details: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch booking details'
            ])->setStatusCode(500);
        }
    }

    public function approve($bookingId)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $request = $this->request->getJSON(true);
            
            // Get the booking details first
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Check if booking is already approved
            if ($booking['status'] === 'confirmed') {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking is already approved'
                ])->setStatusCode(400);
            }

            // Load BookingHelper for duration calculations
            $bookingHelper = new \App\Services\BookingHelper();

            // Parse base duration from plan
            $baseDuration = $booking['duration'] ?? '8 hours';
            $baseHours = $bookingHelper->parseDurationToHours($baseDuration);
            
            // Calculate total duration (base + additional hours)
            $additionalHours = (int)($booking['additional_hours'] ?? 0);
            $totalDurationHours = $baseHours + $additionalHours;

            // Calculate event end time
            $eventEndTime = $bookingHelper->calculateEventEndTime(
                $booking['event_time'],
                $totalDurationHours
            );

            // Validate duration is reasonable (1-720 hours = up to 30 days)
            if ($totalDurationHours < 1 || $totalDurationHours > 720) {
                $totalDurationHours = 8;
                $baseHours = 8;
                $eventEndTime = $bookingHelper->calculateEventEndTime(
                    $booking['event_time'],
                    8
                );
            }

            // Initialize EventModel if not already done
            if (!isset($this->eventModel)) {
                $this->eventModel = new \App\Models\EventModel();
            }

            // Check for facility conflicts
            $hasConflict = $this->eventModel->hasConflict(
                $booking['facility_id'],
                $booking['event_date'],
                $booking['event_time'],
                $totalDurationHours
            );

            if ($hasConflict) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'There is a scheduling conflict with another event at the same facility'
                ])->setStatusCode(409);
            }

            // Update booking status and add calculated end time
            $updateData = [
                'status' => 'confirmed',
                'event_end_time' => $eventEndTime,
                'total_duration_hours' => $totalDurationHours,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $bookingUpdated = $this->bookingModel->update($bookingId, $updateData);
            
            if (!$bookingUpdated) {
                $db->transRollback();
                throw new \Exception('Failed to update booking status');
            }

            // Create event record
            $eventCreated = $this->eventModel->createFromBooking($booking, $request['notes'] ?? null);
            
            if (!$eventCreated) {
                $db->transRollback();
                throw new \Exception('Failed to create event record');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }

            // Create survey record with unique token (outside transaction)
            $surveyModel = new \App\Models\SurveyModel();
            $surveyToken = $surveyModel->generateToken();
            $surveyCrated = $surveyModel->insert([
                'booking_id' => $bookingId,
                'survey_token' => $surveyToken
            ]);

            if (!$surveyCrated) {
                log_message('error', 'Failed to create survey record for booking #' . $bookingId);
                $surveyToken = null;
            } else {
                log_message('info', 'Survey created successfully for booking #' . $bookingId . ' with token: ' . $surveyToken);
            }

            log_message('info', "Booking #{$bookingId} approved and event created. Notes: " . ($request['notes'] ?? 'No notes') . ". End time: {$eventEndTime}, Duration: {$totalDurationHours} hours");
            
            // Send email notification with survey link
            try {
                if ($surveyToken) {
                    $booking['survey_token'] = $surveyToken;
                    log_message('info', 'Survey token added to booking data: ' . $surveyToken);
                }
                sendBookingNotification('approved', $booking);
            } catch (\Exception $e) {
                log_message('error', 'Failed to send approval email: ' . $e->getMessage());
                // Don't fail the approval if email fails
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Booking approved successfully and event created',
                'event_id' => $eventCreated,
                'event_end_time' => $eventEndTime,
                'total_duration_hours' => $totalDurationHours
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error approving booking: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to approve booking: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
}
    /**
     * Decline booking
     */
   public function decline($bookingId)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $request = $this->request->getJSON(true);
            
            if (empty($request['reason']) || empty($request['notes'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Reason and notes are required'
                ])->setStatusCode(400);
            }

            // Get booking status first
            $booking = $this->bookingModel->find($bookingId);
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Date-based availability system: No equipment restoration needed
            // Equipment availability is calculated dynamically per date

            // Update booking status
            $updateData = [
                'status' => 'cancelled',
                'decline_reason' => $request['reason'],
                'decline_notes' => $request['notes'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = $this->bookingModel->update($bookingId, $updateData);
            
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }
            
            if ($updated) {
                log_message('info', "Booking #{$bookingId} declined. Reason: {$request['reason']}");

                // Send email notification - ADD THIS BLOCK
                try {
                    $reasonText = $request['reason'] . "\n\nAdditional Notes: " . $request['notes'];
                    sendBookingNotification('declined', $booking, $reasonText);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to send decline email: ' . $e->getMessage());
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Booking declined successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to decline booking'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error declining booking: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to decline booking'
            ])->setStatusCode(500);
        }
    }

    /**
     * Reschedule a booking to a new date
     */
    public function reschedule()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $request = $this->request->getJSON(true);
            
            // Validate required fields
            if (empty($request['booking_id']) || empty($request['new_event_date']) || empty($request['new_event_time'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking ID, new event date, and new event time are required'
                ])->setStatusCode(400);
            }

            $bookingId = $request['booking_id'];
            $newEventDate = $request['new_event_date'];
            $newEventTime = $request['new_event_time'];
            $reason = $request['reason'] ?? 'Not specified';
            $notes = $request['notes'] ?? '';
            $notifyClient = $request['notify_client'] ?? true;

            // Get booking details
            $booking = $this->bookingModel->getBookingWithFullDetails($bookingId);
            if (!$booking) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Validate the booking can be rescheduled (pending or confirmed only)
            if (!in_array($booking['status'], ['pending', 'confirmed'])) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Only pending or confirmed bookings can be rescheduled'
                ])->setStatusCode(400);
            }

            // Validate new date is in the future
            $newDate = new \DateTime($newEventDate);
            $today = new \DateTime();
            $today->setTime(0, 0, 0);

            if ($newDate < $today) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'New event date must be in the future'
                ])->setStatusCode(400);
            }

            // Check for facility conflicts on new date
            $duration = $booking['duration'] ?? 8;
            $durationValue = 8;
            
            if (is_numeric($duration)) {
                $durationValue = intval($duration);
            } elseif (is_string($duration)) {
                if (preg_match('/(\d+)/', $duration, $matches)) {
                    $durationValue = intval($matches[1]);
                }
            }
            
            if ($durationValue < 1 || $durationValue > 24) {
                $durationValue = 8;
            }

            // Initialize BookingHelper for time calculations
            $bookingHelper = new \App\Services\BookingHelper();

            // Parse base duration from plan
            $baseDuration = $booking['duration'] ?? '8 hours';
            $baseHours = $bookingHelper->parseDurationToHours($baseDuration);
            
            // Calculate total duration (base + additional hours)
            $additionalHours = (int)($booking['additional_hours'] ?? 0);
            $totalDurationHours = $baseHours + $additionalHours;

            // Calculate new event end time
            $newEventEndTime = $bookingHelper->calculateEventEndTime(
                $newEventTime,
                $totalDurationHours
            );

            // Calculate end time with 2-hour grace period
            $newEndWithGrace = $bookingHelper->calculateGracePeriodEndTime(
                $newEventEndTime
            );

            // Check for DATE + TIME + GRACE conflicts on new date (excluding current booking)
            $existingBookings = $db->table('bookings')
                ->select('id, event_date, event_time, duration, additional_hours, event_end_time')
                ->where('facility_id', $booking['facility_id'])
                ->where('event_date', $newEventDate)
                ->where('id !=', $bookingId) // Exclude current booking
                ->whereIn('status', ['pending', 'confirmed', 'approved'])
                ->get()
                ->getResultArray();

            foreach ($existingBookings as $existing) {
                // Calculate existing booking's end time with grace period
                $existingEndTime = $existing['event_end_time'];
                
                if (!$existingEndTime) {
                    // Calculate if not stored
                    $existingBaseDuration = $existing['duration'] ?? 8;
                    $existingBaseHours = $bookingHelper->parseDurationToHours($existingBaseDuration);
                    $existingAdditionalHours = (int)($existing['additional_hours'] ?? 0);
                    $existingTotalHours = $existingBaseHours + $existingAdditionalHours;
                    $existingEndTime = $bookingHelper->calculateEventEndTime(
                        $existing['event_time'],
                        $existingTotalHours
                    );
                }

                $existingEndWithGrace = $bookingHelper->calculateGracePeriodEndTime(
                    $existingEndTime
                );

                // Convert to DateTime for comparison
                $newStart = new \DateTime($newEventDate . ' ' . $newEventTime);
                $newEnd = new \DateTime($newEventDate . ' ' . $newEventEndTime);
                $newEndGrace = new \DateTime($newEventDate . ' ' . $newEndWithGrace);
                
                $existingStart = new \DateTime($newEventDate . ' ' . $existing['event_time']);
                $existingEndGrace = new \DateTime($newEventDate . ' ' . $existingEndWithGrace);

                // Check for overlap including grace periods
                // Conflict if: newStart < existingEndGrace AND newEndGrace > existingStart
                if ($newStart < $existingEndGrace && $newEndGrace > $existingStart) {
                    $db->transRollback();
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Facility has conflicting booking on selected date/time. ' .
                                   'Your requested time: ' . date('h:i A', strtotime($newEventTime)) . ' - ' . 
                                   date('h:i A', strtotime($newEventEndTime)) . 
                                   '. With 2-hour grace period, available from: ' . 
                                   date('h:i A', strtotime($newEndWithGrace))
                    ])->setStatusCode(409);
                }
            }

            // Update booking with new date, time and reschedule information
            $updateData = [
                'event_date' => $newEventDate,
                'event_time' => $newEventTime,
                'reschedule_reason' => $reason,
                'reschedule_notes' => $notes,
                'rescheduled_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = $this->bookingModel->update($bookingId, $updateData);
            
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }
            
            if ($updated) {
                log_message('info', "Booking #{$bookingId} rescheduled. Old date: {$booking['event_date']} at {$booking['event_time']}, New date: {$newEventDate} at {$newEventTime}");

                // Send email notification to client if enabled
                if ($notifyClient) {
                    try {
                        // Create updated booking object for email with new date and time
                        $bookingForEmail = $booking;
                        $bookingForEmail['event_date'] = $newEventDate;
                        $bookingForEmail['event_time'] = $newEventTime;
                        
                        // Create reason message for email
                        $reasonMessage = "Reschedule Reason: " . $reason;
                        if (!empty($notes)) {
                            $reasonMessage .= "\n\nAdditional Notes: " . $notes;
                        }

                        sendBookingNotification('rescheduled', $bookingForEmail, $reasonMessage);
                    } catch (\Exception $e) {
                        log_message('error', 'Failed to send reschedule email: ' . $e->getMessage());
                        // Don't fail the reschedule operation if email fails
                    }
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Booking rescheduled successfully',
                    'booking' => [
                        'id' => $bookingId,
                        'old_event_date' => $booking['event_date'],
                        'old_event_time' => $booking['event_time'],
                        'new_event_date' => $newEventDate,
                        'new_event_time' => $newEventTime
                    ]
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to reschedule booking'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error rescheduling booking: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to reschedule booking: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get download files for a booking
     */
    public function getDownloadFiles($bookingId)
    {
        try {
            $booking = $this->bookingModel->find($bookingId);
            
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Define available download files
            $downloadFiles = [
                [
                    'id' => 'booking-receipt',
                    'name' => 'Booking Receipt',
                    'description' => 'Official booking receipt with payment details',
                    'icon' => '??',
                    'format' => 'PDF'
                ],
                [
                    'id' => 'booking-contract',
                    'name' => 'Booking Contract',
                    'description' => 'Terms and conditions agreement',
                    'icon' => '??',
                    'format' => 'PDF'
                ],
                [
                    'id' => 'facility-guidelines',
                    'name' => 'Facility Guidelines',
                    'description' => 'Rules and regulations for facility usage',
                    'icon' => '??',
                    'format' => 'PDF'
                ],
                [
                    'id' => 'event-checklist',
                    'name' => 'Event Planning Checklist',
                    'description' => 'Pre-event preparation checklist',
                    'icon' => '?',
                    'format' => 'PDF'
                ]
            ];

            // Add conditional files based on booking status
            if ($booking['status'] === 'confirmed') {
                $downloadFiles[] = [
                    'id' => 'confirmation-letter',
                    'name' => 'Confirmation Letter',
                    'description' => 'Official booking confirmation document',
                    'icon' => '??',
                    'format' => 'PDF'
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'files' => $downloadFiles
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting download files: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get download files'
            ])->setStatusCode(500);
        }
    }

    /**
     * Download specific file (placeholder for now)
     */
public function downloadFile($bookingId, $fileId)
{
    try {
        $db = \Config\Database::connect();
        
        // Get file details from database
        $file = $db->table('booking_files')
                  ->where('id', $fileId)
                  ->where('booking_id', $bookingId)
                  ->get()
                  ->getRowArray();
        
        if (!$file) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File not found'
            ])->setStatusCode(404);
        }

        // Check if physical file exists
        if (!file_exists($file['file_path'])) {
            log_message('error', 'File not found on disk: ' . $file['file_path']);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File not found on server'
            ])->setStatusCode(404);
        }

        // Set appropriate headers for file download
        $this->response->setHeader('Content-Type', $file['mime_type']);
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $file['original_filename'] . '"');
        $this->response->setHeader('Content-Length', $file['file_size']);
        $this->response->setHeader('Cache-Control', 'no-cache, must-revalidate');
        
        // Read and output the file
        return $this->response->setBody(file_get_contents($file['file_path']));

    } catch (\Exception $e) {
        log_message('error', 'Error downloading file: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to download file'
        ])->setStatusCode(500);
    }
}

    /**
     * Generate booking report
     */
    public function generateReport()
    {
        try {
            // Get request parameters for filtering
            $status = $this->request->getGet('status');
            $dateFrom = $this->request->getGet('date_from');
            $dateTo = $this->request->getGet('date_to');

            // Fetch bookings with filters
            $bookings = $this->bookingModel->getBookingsWithDetails();

            // Apply filters
            if ($status) {
                $bookings = array_filter($bookings, function($booking) use ($status) {
                    return $booking['status'] === $status;
                });
            }

            if ($dateFrom) {
                $bookings = array_filter($bookings, function($booking) use ($dateFrom) {
                    return $booking['event_date'] >= $dateFrom;
                });
            }

            if ($dateTo) {
                $bookings = array_filter($bookings, function($booking) use ($dateTo) {
                    return $booking['event_date'] <= $dateTo;
                });
            }

            // Generate report statistics
            $report = [
                'total_bookings' => count($bookings),
                'status_summary' => [
                    'pending' => 0,
                    'confirmed' => 0,
                    'cancelled' => 0
                ],
                'revenue' => [
                    'total' => 0,
                    'confirmed' => 0,
                    'pending' => 0
                ],
                'bookings' => []
            ];

            // Calculate statistics and prepare booking list
            foreach ($bookings as $booking) {
                // Count by status - check if key exists first
                if (isset($booking['status']) && isset($report['status_summary'][$booking['status']])) {
                    $report['status_summary'][$booking['status']]++;
                }

                // Calculate revenue (exclude student and employee bookings)
                if (isset($booking['booking_type']) && !in_array($booking['booking_type'], ['student', 'employee'])) {
                    $cost = floatval($booking['total_cost'] ?? 0);
                    $report['revenue']['total'] += $cost;

                    if (isset($booking['status']) && $booking['status'] === 'confirmed') {
                        $report['revenue']['confirmed'] += $cost;
                    } elseif (isset($booking['status']) && $booking['status'] === 'pending') {
                        $report['revenue']['pending'] += $cost;
                    }
                }

                // Add booking to list
                $report['bookings'][] = [
                    'id' => $booking['id'] ?? 0,
                    'booking_id' => '#BK' . str_pad($booking['id'] ?? 0, 3, '0', STR_PAD_LEFT),
                    'client_name' => $booking['client_name'] ?? 'N/A',
                    'email' => $booking['email_address'] ?? 'N/A',
                    'contact' => $booking['contact_number'] ?? 'N/A',
                    'organization' => $booking['organization'] ?? 'N/A',
                    'booking_type' => (isset($booking['booking_type']) && in_array($booking['booking_type'], ['student', 'employee'])) ? ucfirst($booking['booking_type']) : 'User',
                    'facility' => $booking['facility_name'] ?? 'N/A',
                    'event_title' => $booking['event_title'] ?? 'N/A',
                    'event_date' => $booking['event_date'] ?? 'N/A',
                    'event_time' => $booking['event_time'] ?? 'N/A',
                    'duration' => $booking['duration'] ?? 'N/A',
                    'attendees' => $booking['attendees'] ?? 'N/A',
                    'status' => isset($booking['status']) ? ucfirst($booking['status']) : 'N/A',
                    'total_cost' => (isset($booking['booking_type']) && in_array($booking['booking_type'], ['student', 'employee'])) ? 'FREE' : '?' . number_format($booking['total_cost'] ?? 0, 2),
                    'created_at' => $booking['created_at'] ?? 'N/A'
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error generating report: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to generate report'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get booking statistics
     */
    public function getStatistics()
    {
        try {
            // Auto-update expired bookings before getting statistics
            $this->bookingModel->autoUpdateExpiredBookings();
            
            $statistics = $this->bookingModel->getBookingStatistics();
            
            return $this->response->setJSON([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching statistics: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ])->setStatusCode(500);
        }
    }

    /**
     * Search bookings with filters
     */
    public function searchBookings()
    {
        try {
            // Auto-update expired bookings before searching
            $this->bookingModel->autoUpdateExpiredBookings();
            
            $request = $this->request->getJSON(true);
            $bookings = $this->bookingModel->searchBookings($request);
            
            return $this->response->setJSON([
                'success' => true,
                'bookings' => $bookings
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error searching bookings: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to search bookings'
            ])->setStatusCode(500);
        }
    }


public function getEvents()
{
    try {
        log_message('info', 'getEvents() - Starting to fetch events for attendance page');

        if (!isset($this->eventModel)) {
            $this->eventModel = new \App\Models\EventModel();
        }

        $events = $this->eventModel->getEventsWithDetails();

        log_message('info', 'getEvents() - Found ' . count($events) . ' events');

        if (count($events) > 0) {
            log_message('info', 'getEvents() - First event: ' . json_encode($events[0]));
        } else {
            log_message('warning', 'getEvents() - No events found in database');
        }

        return $this->response->setJSON([
            'success' => true,
            'events' => $events
        ]);
    } catch (\Exception $e) {
        log_message('error', 'getEvents() - Error fetching events: ' . $e->getMessage());
        log_message('error', 'getEvents() - Stack trace: ' . $e->getTraceAsString());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to fetch events'
        ])->setStatusCode(500);
    }
}

/**
 * Get upcoming events
 */
public function getUpcomingEvents()
{
    try {
        if (!isset($this->eventModel)) {
            $this->eventModel = new \App\Models\EventModel();
        }
        
        $limit = $this->request->getGet('limit') ?? 10;
        $events = $this->eventModel->getUpcomingEvents($limit);
        
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
 * Get event statistics
 */
public function getEventStatistics()
{
    try {
        if (!isset($this->eventModel)) {
            $this->eventModel = new \App\Models\EventModel();
        }
        
        $statistics = $this->eventModel->getEventStatistics();
        
        return $this->response->setJSON([
            'success' => true,
            'statistics' => $statistics
        ]);
    } catch (\Exception $e) {
        log_message('error', 'Error fetching event statistics: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to fetch event statistics'
        ])->setStatusCode(500);
    }
}

public function getCalendarEvents()
{
    try {
        if (!isset($this->eventModel)) {
            $this->eventModel = new \App\Models\EventModel();
        }
        
        // Get date range from request (optional)
        $startDate = $this->request->getGet('start');
        $endDate = $this->request->getGet('end');
        
        if ($startDate && $endDate) {
            $events = $this->eventModel->getEventsByDateRange($startDate, $endDate);
        } else {
            $events = $this->eventModel->getEventsWithDetails();
        }
        
        // Format events for FullCalendar
        $calendarEvents = [];
        foreach ($events as $event) {
            // Determine event category based on facility or other criteria
            $category = $this->determineEventCategory($event['facility_id']);
            
            $calendarEvents[] = [
                'id' => $event['id'],
                'title' => $event['event_title'],
                'start' => $event['event_date'] . 'T' . $event['event_time'],
                'end' => $this->calculateEndTime($event['event_date'], $event['event_time'], $event['duration']),
                'className' => $category,
                'extendedProps' => [
                    'location' => $event['facility_name'],
                    'organizer' => $event['organization'] ?? $event['client_name'],
                    'description' => $event['special_requirements'] ?? 'No additional details available.',
                    'status' => ucfirst($event['status']),
                    'clientName' => $event['client_name'],
                    'contactNumber' => $event['contact_number'],
                    'emailAddress' => $event['email_address'],
                    'attendees' => $event['attendees'],
                    'totalCost' => $event['total_cost']
                ]
            ];
        }
        
        return $this->response->setJSON([
            'success' => true,
            'events' => $calendarEvents
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
 * Determine event category based on facility or other criteria
 */
private function determineEventCategory($facilityId)
{
    // Map facility types to event categories
    $facilityCategories = [
        1 => 'academic',    // University Auditorium
        2 => 'sports',      // University Gymnasium  
        3 => 'academic',    // AVR Library
        4 => 'cultural',    // Function Hall
        5 => 'academic',    // AVR Engineering
        6 => 'cultural',    // Pearl Restaurant
        7 => 'academic',    // Staff House
        8 => 'academic',    // Classrooms
        9 => 'cultural',    // Pearl Hotel
    ];
    
    return $facilityCategories[$facilityId] ?? 'academic';
}

/**
 * Calculate event end time based on start time and duration
 */
private function calculateEndTime($eventDate, $eventTime, $duration)
{
    $startDateTime = new \DateTime($eventDate . ' ' . $eventTime);
    
    // Parse duration (assuming it's in hours)
    $durationHours = intval($duration);
    $startDateTime->add(new \DateInterval('PT' . $durationHours . 'H'));
    
    return $startDateTime->format('Y-m-d\TH:i:s');
}

/**
 * Get event details for modal
 */
public function getEventDetails($eventId)
{
    try {
        if (!isset($this->eventModel)) {
            $this->eventModel = new \App\Models\EventModel();
        }
        
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event not found'
            ])->setStatusCode(404);
        }
        
        // Get facility details
        $facility = $this->facilityModel->find($event['facility_id']);
        
        return $this->response->setJSON([
            'success' => true,
            'event' => array_merge($event, [
                'facility_name' => $facility['name'] ?? 'Unknown Facility',
                'facility_icon' => $facility['icon'] ?? '??'
            ])
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
 * Delete booking (hard delete - permanently remove from database)
 */
public function delete($id = null)
{
    try {
        // Add validation for booking ID parameter
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking ID is required'
            ])->setStatusCode(400);
        }

        // Find the booking record
        $booking = $this->bookingModel->find($id);
        
        if (!$booking) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking not found'
            ])->setStatusCode(404);
        }

        // ?? REMOVE THE STATUS CHECK - Allow deletion of any status
        // Old code removed: if ($booking['status'] !== 'cancelled') {...}

        // Get delete reason from request body for logging purposes
        $input = $this->request->getJSON();
        $deleteReason = '';
        
        if ($input && isset($input->reason)) {
            $deleteReason = $input->reason;
        } else {
            $rawInput = $this->request->getBody();
            if ($rawInput) {
                $data = json_decode($rawInput, true);
                $deleteReason = $data['reason'] ?? 'No reason provided';
            } else {
                $deleteReason = 'No reason provided';
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete related records first (foreign key constraints)
            $db->table('booking_addons')->where('booking_id', $id)->delete();
            $db->table('booking_equipment')->where('booking_id', $id)->delete();
            $db->table('events')->where('booking_id', $id)->delete();
            
            // Delete booking files (both user and student files)
            $db->table('booking_files')->where('booking_id', $id)->delete();
            $db->table('student_booking_files')->where('booking_id', $id)->delete();

            // Finally, delete the main booking record
            $deleted = $this->bookingModel->delete($id);
            
            if (!$deleted) {
                throw new \Exception('Failed to delete booking record');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }

            log_message('info', "Booking #{$id} permanently deleted from database. Status: {$booking['status']}. Reason: {$deleteReason}");
            
            // Send email notification
            try {
                sendBookingNotification('deleted', $booking, $deleteReason);
            } catch (\Exception $e) {
                log_message('error', 'Failed to send deletion email: ' . $e->getMessage());
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Booking deleted successfully'
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }

    } catch (\Exception $e) {
        log_message('error', 'Error deleting booking: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to delete booking: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}

/**
 * Archive booking (soft delete - mark as deleted but keep in database)
 */
public function archive($id = null)
{
    try {
        // Add validation for booking ID parameter
        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking ID is required'
            ])->setStatusCode(400);
        }

        // Find the booking record
        $booking = $this->bookingModel->find($id);
        
        if (!$booking) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking not found'
            ])->setStatusCode(404);
        }

        // Check if booking can be archived
        if ($booking['status'] !== 'cancelled') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Only cancelled bookings can be archived'
            ])->setStatusCode(400);
        }

        // Get archive reason from request body
        $input = $this->request->getJSON();
        $archiveReason = '';
        
        if ($input && isset($input->reason)) {
            $archiveReason = $input->reason;
        } else {
            $rawInput = $this->request->getBody();
            if ($rawInput) {
                $data = json_decode($rawInput, true);
                $archiveReason = $data['reason'] ?? 'No reason provided';
            } else {
                $archiveReason = 'No reason provided';
            }
        }

        // Use decline_reason and decline_notes to mark as archived
        $updateData = [
            'decline_reason' => 'archived',
            'decline_notes' => 'ARCHIVED: ' . $archiveReason,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updated = $this->bookingModel->update($id, $updateData);
        
        if ($updated) {
            log_message('info', "Booking #{$id} archived successfully. Reason: {$archiveReason}");
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Booking archived successfully'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to archive booking'
            ])->setStatusCode(500);
        }
    } catch (\Exception $e) {
        log_message('error', 'Error archiving booking: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to archive booking: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}


/**
 * Upload files for a booking (UPDATED VERSION)
 */
public function uploadFiles($bookingId)
{
    try {
        log_message('info', "=== UPLOAD FILES START - Booking ID: {$bookingId} (BookingApiController) ===");
        
        // Verify booking exists
        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            log_message('error', "Booking {$bookingId} not found");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking not found'
            ])->setStatusCode(404);
        }

        log_message('info', "Booking found: {$booking['id']}, Type: {$booking['booking_type']}");

        $uploadPath = WRITEPATH . 'uploads/student_booking_files/' . $bookingId . '/';
        
        log_message('info', "Upload path: {$uploadPath}");
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            @mkdir($uploadPath, 0755, true);
            log_message('info', "Created directory");
        }

        $uploadedFiles = [];
        $files = $this->request->getFiles();

        log_message('info', "getFiles() returned: " . count($files) . " file(s)");
        log_message('debug', "Files array keys: " . json_encode(array_keys($files)));

        if (empty($files)) {
            log_message('error', "No files in request");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No files uploaded'
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        
        // Handle both single files and array of files from FormData
        $filesToProcess = [];
        if (isset($files['files'])) {
            // Files uploaded as "files[]" in FormData
            $filesToProcess = is_array($files['files']) ? $files['files'] : [$files['files']];
            log_message('info', "Processing files[] array with " . count($filesToProcess) . " item(s)");
        } else {
            // Individual file fields
            $filesToProcess = $files;
            log_message('info', "Processing individual file fields");
        }
        
        // File type mapping for student/employee bookings
        $fileTypeMapping = [
            0 => 'permission_letter',
            1 => 'request_letter',
            2 => 'approval_letter'
        ];
        
        foreach ($filesToProcess as $fileIndex => $file) {
            log_message('info', "Processing file index {$fileIndex}, type: " . gettype($file));
            
            // Handle nested arrays from CodeIgniter file uploads
            while (is_array($file) && !empty($file)) {
                log_message('debug', "File is array, unwrapping...");
                $file = reset($file); // Get first element
            }
            
            log_message('info', "After unwrap - type: " . gettype($file));
            
            // Skip if not a valid UploadedFile object
            if (!is_object($file) || !method_exists($file, 'isValid')) {
                log_message('warning', "File at index {$fileIndex} is not a valid UploadedFile object. Type: " . gettype($file) . ", Is object: " . (is_object($file) ? 'yes' : 'no'));
                continue;
            }
            
            log_message('info', "File is valid UploadedFile object, checking if valid...");
            
            if ($file->isValid() && !$file->hasMoved()) {
                
                // Validate file size (10MB max)
                if ($file->getSize() > 10 * 1024 * 1024) {
                    log_message('warning', "File too large: {$file->getClientName()}");
                    continue; // Skip files larger than 10MB
                }

                // Determine the file type
                // For numeric indices, use mapping; otherwise use the key as-is
                $fileType = isset($fileTypeMapping[$fileIndex]) ? $fileTypeMapping[$fileIndex] : $fileIndex;

                log_message('info', "Processing file type: {$fileType}");

                // Check if file of this type already exists for this booking
                $existingFile = $db->table('student_booking_files')
                                  ->where('booking_id', $bookingId)
                                  ->where('file_type', $fileType)
                                  ->get()
                                  ->getRowArray();

                if ($existingFile) {
                    log_message('info', "Deleting existing file: {$existingFile['original_filename']}");
                    // Delete the old file from disk
                    if (file_exists($existingFile['file_path'])) {
                        unlink($existingFile['file_path']);
                    }
                    
                    // Delete the old record from database
                    $db->table('student_booking_files')->where('id', $existingFile['id'])->delete();
                }

                // Generate unique filename
                $newName = $file->getRandomName();
                
                log_message('info', "Moving file {$file->getClientName()} to {$uploadPath}{$newName}");
                
                // Move file to upload directory
                if ($file->move($uploadPath, $newName)) {
                    
                    log_message('info', "File moved successfully, now inserting to database");
                    
                    // Save file info to database
                    $fileData = [
                        'booking_id' => $bookingId,
                        'file_type' => $fileType,
                        'original_filename' => $file->getClientName(),
                        'stored_filename' => $newName,
                        'file_path' => $uploadPath . $newName,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getClientMimeType(),
                        'upload_date' => date('Y-m-d H:i:s')
                    ];
                    
                    log_message('debug', "File data to insert: " . json_encode($fileData));
                    
                    $insertResult = $db->table('student_booking_files')->insert($fileData);
                    $insertID = $db->insertID();
                    
                    log_message('info', "Database insert result: {$insertResult}, ID: {$insertID}");
                    
                    if ($insertResult) {
                        log_message('info', "✓ File successfully saved to database with ID {$insertID}");
                    } else {
                        log_message('error', "✗ Failed to insert file to database");
                    }
                    
                    $uploadedFiles[] = [
                        'id' => $insertID,
                        'file_type' => $fileType,
                        'filename' => $file->getClientName(),
                        'size' => $file->getSize()
                    ];
                } else {
                    log_message('error', "Failed to move file to {$uploadPath}{$newName}");
                }
            }
        }

        log_message('info', "Total files uploaded: " . count($uploadedFiles));

        if (empty($uploadedFiles)) {
            log_message('error', "No files were successfully uploaded");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No files were successfully uploaded'
            ])->setStatusCode(400);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => count($uploadedFiles) . ' files uploaded successfully',
            'files' => $uploadedFiles
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Error uploading files: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to upload files'
        ])->setStatusCode(500);
    }
}

/**
 * Get files for a booking
 */
public function getBookingFiles($bookingId)
{
    try {
        log_message('info', "=== GET BOOKING FILES START for booking {$bookingId} ===");
        
        $db = \Config\Database::connect();
        
        // Get files from student_booking_files table (used by both admin and student bookings)
        $files = $db->table('student_booking_files')
                   ->where('booking_id', $bookingId)
                   ->get()
                   ->getResultArray();

        log_message('info', "Retrieved " . count($files) . " files for booking {$bookingId}");
        
        if (!empty($files)) {
            foreach ($files as $file) {
                log_message('info', "  - File: {$file['original_filename']} (Type: {$file['file_type']}, Size: {$file['file_size']})");
            }
        } else {
            log_message('warning', "No files found for booking {$bookingId}");
        }

        // Normalize response format to match JavaScript expectations
        // This ensures consistency between different upload flows
        $formattedFiles = array_map(function($file) {
            return [
                'id' => $file['id'],
                'file_type' => $file['file_type'],
                'filename' => $file['original_filename'],  // Normalize field name
                'size' => $file['file_size'],              // Normalize field name
                'mime_type' => $file['mime_type'],
                'upload_date' => $file['upload_date']
            ];
        }, $files);

        return $this->response->setJSON([
            'success' => true,
            'files' => $formattedFiles,
            'count' => count($formattedFiles)
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error getting booking files: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to get booking files'
        ])->setStatusCode(500);
    }
}

/**
 * Delete a file
 */
public function deleteFile($fileId)
{
    try {
        $db = \Config\Database::connect();
        
        // Get file from student_booking_files table (used by both admin and student bookings)
        $file = $db->table('student_booking_files')->where('id', $fileId)->get()->getRowArray();
        
        if (!$file) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File not found'
            ])->setStatusCode(404);
        }

        // Delete physical file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Delete from database
        $db->table('student_booking_files')->where('id', $fileId)->delete();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Error deleting file: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to delete file'
        ])->setStatusCode(500);
    }
}    

/**
 * Generate and download billing statement
 */
public function generateBillingStatement($bookingId)
{
    try {
        // Load the billing controller
        $billingController = new \App\Controllers\Admin\BillingController();
        return $billingController->generateBillingStatement($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating billing statement: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate billing statement'
        ])->setStatusCode(500);
    }
}

public function generateEquipmentRequestForm($bookingId)
{
    try {
        // Load the equipment request controller
        $equipmentController = new \App\Controllers\Admin\EquipmentRequestController();
        return $equipmentController->generateEquipmentRequestForm($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating equipment request form: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate equipment request form'
        ])->setStatusCode(500);
    }
}

    private function restoreEquipmentInventory($bookingId)
    {
        try {
            $bookingEquipmentModel = new \App\Models\BookingEquipmentModel();
            $equipmentModel = new \App\Models\EquipmentModel();
            
            // Get all equipment for this booking
            $bookingEquipment = $bookingEquipmentModel->where('booking_id', $bookingId)->findAll();
            
            foreach ($bookingEquipment as $equipment) {
                // Restore equipment inventory
                $equipmentModel->returnEquipment($equipment['equipment_id'], $equipment['quantity']);
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to restore equipment inventory: ' . $e->getMessage());
            return false;
        }
    }

public function getEquipment()
{
    try {
        $equipmentModel = new \App\Models\EquipmentModel();
        $eventDate = $this->request->getGet('event_date');

        // If event date is provided, calculate date-based availability
        if ($eventDate) {
            $db = \Config\Database::connect();

            // Get all equipment
            $equipment = $equipmentModel->getEquipmentForBooking();

            // Calculate availability for each equipment on the specified date
            foreach ($equipment as &$item) {
                // Get quantity booked on this date
                $bookedQuery = $db->table('booking_equipment be')
                    ->select('SUM(be.quantity) as booked_quantity')
                    ->join('bookings b', 'b.id = be.booking_id', 'inner')
                    ->where('be.equipment_id', $item['id'])
                    ->where('b.event_date', $eventDate)
                    ->whereIn('b.status', ['pending', 'confirmed', 'approved'])
                    ->get();

                $bookedResult = $bookedQuery->getRowArray();
                $bookedQuantity = (int)($bookedResult['booked_quantity'] ?? 0);

                // Calculate available quantity for this date
                $item['booked_quantity'] = $bookedQuantity;
                $item['available_on_date'] = max(0, $item['quantity'] - $bookedQuantity);
            }

            return $this->response->setJSON([
                'success' => true,
                'equipment' => $equipment,
                'filtered_by_date' => true,
                'event_date' => $eventDate
            ]);
        } else {
            // Return equipment with global availability (for backward compatibility)
            $equipment = $equipmentModel->getEquipmentForBooking();

            return $this->response->setJSON([
                'success' => true,
                'equipment' => $equipment,
                'filtered_by_date' => false
            ]);
        }

    } catch (\Exception $e) {
        log_message('error', 'Error fetching equipment for booking: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to fetch equipment data'
        ])->setStatusCode(500);
    }
}

/**
 * Generate and download MOA
 */
public function generateMoa($bookingId)
{
    try {
        $moaController = new \App\Controllers\Admin\MoaController();
        return $moaController->generateMoa($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating MOA: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate MOA'
        ])->setStatusCode(500);
    }
}

public function generateFacultyEvaluation($bookingId)
{
    try {
        $evaluationController = new \App\Controllers\Admin\FacultyEvaluationController();
        return $evaluationController->generateFacultyEvaluation($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating Faculty Evaluation: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate Faculty Evaluation'
        ])->setStatusCode(500);
    }
}

public function generateInspectionEvaluation($bookingId)
{
    try {
        $inspectionController = new \App\Controllers\Admin\InspectionEvaluationController();
        return $inspectionController->generateInspectionEvaluation($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating Inspection Evaluation: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate Inspection Evaluation'
        ])->setStatusCode(500);
    }
}

public function downloadSelectedZip($booking_id)
{
    try {
        // Verify booking exists
        $booking = $this->bookingModel->find($booking_id);
        if (!$booking) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Booking not found'
            ])->setStatusCode(404);
        }

        // Get selected file types from query parameter
        $selectedTypes = $this->request->getGet('types');
        if (empty($selectedTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No file types specified'
            ])->setStatusCode(400);
        }

        $fileTypes = explode(',', $selectedTypes);

        // Create ZIP in temp directory
        $zipFileName = 'booking_BK' . str_pad($booking_id, 3, '0', STR_PAD_LEFT) . '_documents.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipFileName;

        if (!class_exists('ZipArchive')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ZIP functionality not available on server'
            ])->setStatusCode(500);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create ZIP file'
            ])->setStatusCode(500);
        }

        $filesAdded = 0;

        // Add each document by fetching its content
        foreach ($fileTypes as $fileType) {
            $fileType = trim($fileType);
            
            // Get the document content
            $content = $this->fetchDocumentContent($booking_id, $fileType);
            
            if ($content) {
                $fileName = $this->getDocumentFileName($booking, $fileType);
                $zip->addFromString($fileName, $content);
                $filesAdded++;
            }
        }

        $zip->close();

        if ($filesAdded === 0) {
            @unlink($zipPath);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No files could be added to ZIP. Documents may not be generated yet.'
            ])->setStatusCode(404);
        }

        // Download and cleanup
        $response = $this->response->download($zipPath, null)->setFileName($zipFileName);
        
        // Schedule cleanup after response is sent
        register_shutdown_function(function() use ($zipPath) {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
        });

        return $response;

    } catch (\Exception $e) {
        log_message('error', 'ZIP Download Error: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to create ZIP: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}

// Helper method to get document path based on type
private function getDocumentPath($booking_id, $fileType)
{
    // Check if file exists in uploaded files
    $uploadedFile = $this->bookingFileModel
        ->where('booking_id', $booking_id)
        ->where('file_type', $fileType)
        ->first();
    
    if ($uploadedFile) {
        return WRITEPATH . 'uploads/bookings/' . $uploadedFile['file_path'];
    }

    // If not uploaded, check if it's a generated document
    $generatedPath = $this->getGeneratedDocumentPath($booking_id, $fileType);
    return $generatedPath;
}

// Helper method to get appropriate filename
private function getDocumentFileName($booking, $fileType)
{
    $bookingId = 'BK' . str_pad($booking['id'], 3, '0', STR_PAD_LEFT);
    
    $fileNames = [
        'moa' => $bookingId . '_Memorandum_of_Agreement.pdf',
        'billing' => $bookingId . '_Billing_Statement.pdf',
        'equipment' => $bookingId . '_Equipment_Request_Form.pdf',
        'evaluation' => $bookingId . '_Facility_Evaluation_Form.pdf',
        'inspection' => $bookingId . '_Inspection_Evaluation.pdf',
        'receipt' => $bookingId . '_Receipt.pdf',
        'orderofpayment' => $bookingId . '_Order_of_Payment.xlsx'  
    ];
    
    return $fileNames[$fileType] ?? $bookingId . '_' . ucfirst($fileType) . '.pdf';
}

// Helper to get generated document paths
private function getGeneratedDocumentPath($booking_id, $fileType)
{
    // This should match your document generation logic
    // Adjust paths based on where you store generated documents
    $basePath = WRITEPATH . 'uploads/generated/';
    
    switch ($fileType) {
        case 'moa':
        case 'billing':
        case 'equipment':
        case 'evaluation':
        case 'inspection':
            // Return null if document needs to be generated on-demand
            // Or return path if you pre-generate and store them
            return null;
        default:
            return null;
    }
}

private function fetchDocumentContent($booking_id, $fileType)
{
    // Map file types to their respective methods/endpoints
    switch ($fileType) {
        case 'billing':
            return $this->getBillingStatementContent($booking_id);
        case 'moa':
            return $this->getMoaContent($booking_id);
        case 'equipment':
            return $this->getEquipmentFormContent($booking_id);
        case 'evaluation':
            return $this->getEvaluationFormContent($booking_id);
        case 'inspection':
            return $this->getInspectionFormContent($booking_id);
        case 'orderofpayment':  // ADD THIS CASE
            return $this->getOrderOfPaymentContent($booking_id);
        default:
            return null;
    }
}

private function getBillingStatementContent($booking_id)
{
    try {
        $billingController = new \App\Controllers\Admin\BillingController();
        $response = $billingController->generateBillingStatement($booking_id);
        
        // If response is a download response, get its body
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting billing content: ' . $e->getMessage());
        return null;
    }
}

private function getMoaContent($booking_id)
{
    try {
        $moaController = new \App\Controllers\Admin\MoaController();
        $response = $moaController->generateMoa($booking_id);
        
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting MOA content: ' . $e->getMessage());
        return null;
    }
}

private function getEquipmentFormContent($booking_id)
{
    try {
        $equipmentController = new \App\Controllers\Admin\EquipmentRequestController();
        $response = $equipmentController->generateEquipmentRequestForm($booking_id);
        
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting equipment form content: ' . $e->getMessage());
        return null;
    }
}

private function getEvaluationFormContent($booking_id)
{
    try {
        $evaluationController = new \App\Controllers\Admin\FacultyEvaluationController();
        $response = $evaluationController->generateFacultyEvaluation($booking_id);
        
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting evaluation form content: ' . $e->getMessage());
        return null;
    }
}

private function getInspectionFormContent($booking_id)
{
    try {
        $inspectionController = new \App\Controllers\Admin\InspectionEvaluationController();
        $response = $inspectionController->generateInspectionEvaluation($booking_id);
        
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting inspection form content: ' . $e->getMessage());
        return null;
    }
}
private function getOrderOfPaymentContent($booking_id)
{
    try {
        $orderController = new \App\Controllers\Admin\OrderOfPaymentController();
        $response = $orderController->generateOrderOfPayment($booking_id);
        
        if ($response instanceof \CodeIgniter\HTTP\DownloadResponse) {
            return $response->getBinary();
        }
        
        return null;
    } catch (\Exception $e) {
        log_message('error', 'Error getting order of payment content: ' . $e->getMessage());
        return null;
    }
}

public function generateOrderOfPayment($bookingId)
{
    try {
        $orderController = new \App\Controllers\Admin\OrderOfPaymentController();
        return $orderController->generateOrderOfPayment($bookingId);

    } catch (\Exception $e) {
        log_message('error', 'Error generating Order of Payment: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to generate Order of Payment'
        ])->setStatusCode(500);
    }
}
public function getFacilityPlans($facilityId)
{
    try {
        $planModel = new \App\Models\PlanModel();
        $db = \Config\Database::connect();
        
        // Get all plans for the facility
        $plans = $planModel->where('facility_id', $facilityId)->findAll();
        
        foreach ($plans as &$plan) {
            // Get plan features
            $features = $db->table('plan_features')
                ->select('feature, feature_type, is_physical')
                ->where('plan_id', $plan['id'])
                ->orderBy('display_order', 'ASC')
                ->get()
                ->getResultArray();
            
            $plan['features'] = $features;
            
            // Get plan equipment (included equipment)
            $equipment = $db->table('plan_equipment pe')
                ->select('e.id, e.name, e.category, pe.quantity_included, pe.is_mandatory, e.unit')
                ->join('equipment e', 'pe.equipment_id = e.id')
                ->where('pe.plan_id', $plan['id'])
                ->where('e.is_trackable', 1) // Only trackable equipment
                ->get()
                ->getResultArray();
            
            $plan['included_equipment'] = $equipment;
        }
        
        // Get additional rentable equipment (furniture/logistics only)
        $additionalEquipment = $db->table('equipment')
            ->select('id, name, rate, unit, available, category')
            ->where('is_rentable', 1)
            ->whereIn('category', ['furniture', 'logistics'])
            ->where('available >', 0)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
        
        return $this->response->setJSON([
            'success' => true,
            'plans' => $plans,
            'additional_equipment' => $additionalEquipment
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error fetching facility plans: ' . $e->getMessage());
        return $this->response->setStatusCode(500)->setJSON([
            'success' => false,
            'message' => 'Failed to fetch facility plans'
        ]);
    }
}

/**
 * Get facility data with plans, features, and equipment
 */
public function getFacilityData($facilityKey)
{
    try {
        $db = \Config\Database::connect();
        
        // Get facility
        $facility = $db->table('facilities')
            ->where('facility_key', $facilityKey)
            ->get()
            ->getRowArray();
            
        if (!$facility) {
            log_message('error', "Facility not found: {$facilityKey}");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Facility not found'
            ])->setStatusCode(404);
        }
        
        // Get plans for this facility
        $plans = $db->table('plans')
            ->where('facility_id', $facility['id'])
            ->orderBy('price', 'ASC')
            ->get()
            ->getResultArray();
        
        // For each plan, get features and equipment
        foreach ($plans as &$plan) {
            // Ensure price is a number
            $plan['price'] = (float)$plan['price'];
            
            // Get plan features
            $features = $db->table('plan_features')
                ->select('feature')
                ->where('plan_id', $plan['id'])
                ->where('is_physical', 0) // Non-physical features
                ->orderBy('display_order', 'ASC')
                ->get()
                ->getResultArray();
            
            $plan['features'] = array_column($features, 'feature');
            
            // Get included equipment for this plan
            $includedEquipment = $db->table('plan_equipment pe')
                ->select('e.name, pe.quantity_included, e.unit, e.category')
                ->join('equipment e', 'pe.equipment_id = e.id')
                ->where('pe.plan_id', $plan['id'])
                ->orderBy('e.name', 'ASC')
                ->get()
                ->getResultArray();
            
            $plan['included_equipment'] = $includedEquipment;
        }
        
        // Format response to match JavaScript expectations
        return $this->response->setJSON([
            'success' => true,
            'facility' => [
                'id' => $facility['id'],
                'key' => $facility['facility_key'],
                'name' => $facility['name'],
                'icon' => $facility['icon'],
                'additional_hours_rate' => (float)($facility['additional_hours_rate'] ?? 500),
                'plans' => $plans
            ]
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error in getFacilityData: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to load facility data: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}
public function getBookingsList()
{
    try {
        $bookingModel = new BookingModel();
        $db = \Config\Database::connect();
        
        // Get bookings with file counts
        $builder = $db->table('bookings b')
            ->select('b.*, 
                     facilities.name as facility_name, 
                     plans.name as plan_name,
                     (SELECT COUNT(*) FROM booking_files bf WHERE bf.booking_id = b.id) as user_files_count,
                     (SELECT COUNT(*) FROM student_booking_files sbf WHERE sbf.booking_id = b.id) as student_files_count,
                     CASE 
                        WHEN b.booking_type IN ("student", "faculty", "employee") THEN (SELECT COUNT(*) FROM student_booking_files sbf WHERE sbf.booking_id = b.id)
                        ELSE (SELECT COUNT(*) FROM booking_files bf WHERE bf.booking_id = b.id)
                     END as files_count')
            ->join('facilities', 'facilities.id = b.facility_id', 'left')
            ->join('plans', 'plans.id = b.plan_id', 'left')
            ->orderBy('b.created_at', 'DESC');

        $bookings = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'bookings' => $bookings
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Get bookings error: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to fetch bookings'
        ])->setStatusCode(500);
    }
}

    /**
     * Generate Facility Rental Report - Excel format using template
     * Filters by month/year and includes only confirmed and completed bookings
     */
    public function generateFacilityRentalReport()
    {
        try {
            // Get request parameters
            $month = $this->request->getGet('month'); // Format: 01-12
            $year = $this->request->getGet('year');   // Format: YYYY

            // Validate parameters
            if (!$month || !$year || !is_numeric($month) || !is_numeric($year)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid month or year'
                ])->setStatusCode(400);
            }

            // Build date range for the selected month
            $dateFrom = "{$year}-{$month}-01";
            $lastDay = cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year));
            $dateTo = "{$year}-{$month}-{$lastDay}";

            // Fetch bookings - confirmed and completed only
            $db = \Config\Database::connect();
            $builder = $db->table('bookings b');
            $builder->select('
                b.id,
                b.facility_id,
                b.total_cost,
                b.event_date,
                b.status,
                f.name as facility_name
            ')
            ->join('facilities f', 'f.id = b.facility_id', 'left')
            ->whereIn('b.status', ['confirmed', 'completed'])
            ->where('DATE(b.event_date) >=', $dateFrom)
            ->where('DATE(b.event_date) <=', $dateTo)
            ->orderBy('b.event_date', 'ASC');

            $bookings = $builder->get()->getResultArray();

            // Load template
            $templatePath = FCPATH . 'assets/templates/report_summary.xlsx';
            
            if (!file_exists($templatePath)) {
                throw new \Exception('Template not found at: ' . $templatePath);
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Fill template with report data
            $this->fillFacilityRentalTemplate($sheet, $bookings, $month, $year);

            // Generate filename
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            $monthName = $monthNames[intval($month)];
            $filename = 'Facility_Rental_Report_' . $monthName . '_' . $year . '.xlsx';
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
            log_message('error', 'Error generating facility rental report: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to generate facility rental report: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Fill facility rental report template with booking data
     */
    private function fillFacilityRentalTemplate(&$sheet, $bookings, $month, $year)
    {
        // Format month name
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        $monthName = $monthNames[intval($month)];
        $reportDate = $monthName . ' ' . $year;

        // Update header information
        $sheet->setCellValue('A4', 'CSPC-F-SPMU-44');
        $sheet->setCellValue('C5', 'Reports on Rentals');
        $sheet->setCellValue('C6', 'FACILITIES AND EQUIPMENT');
        $sheet->setCellValue('C8', 'Date: ' . $reportDate);

        // Group bookings by facility
        $facilityData = [];
        $grandTotal = 0;

        foreach ($bookings as $booking) {
            $facilityName = $booking['facility_name'] ?? 'Unknown Facility';
            $amount = floatval($booking['total_cost'] ?? 0);

            if (!isset($facilityData[$facilityName])) {
                $facilityData[$facilityName] = [
                    'name' => $facilityName,
                    'bookings' => [],
                    'subtotal' => 0
                ];
            }

            $facilityData[$facilityName]['bookings'][] = [
                'booking_id' => $booking['id'],
                'amount' => $amount,
                'event_date' => $booking['event_date'],
                'status' => $booking['status']
            ];

            $facilityData[$facilityName]['subtotal'] += $amount;
            $grandTotal += $amount;
        }

        // Data starts at row 10
        $row = 10;

        // Fill in facility and booking data
        foreach ($facilityData as $facility) {
            foreach ($facility['bookings'] as $booking) {
                // Column A: Article/Description (Facility name)
                $sheet->setCellValue('A' . $row, $facility['name']);
                // Column B: Buyer
                $sheet->setCellValue('B' . $row, 'Pls. see attached sheet');
                // Column C: Amount
                $sheet->setCellValue('C' . $row, $booking['amount']);
                // Column D: OR Number
                $sheet->setCellValue('D' . $row, 'Pls. see attached sheet');
                // Column E: Date
                $sheet->setCellValue('E' . $row, $reportDate);
                
                // Format amount as currency
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('? #,##0.00');
                
                $row++;
            }
        }

        // Add TOTAL row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, $grandTotal);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('? #,##0.00');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);

        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
    }
}



