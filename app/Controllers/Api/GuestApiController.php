<?php

namespace App\Controllers\Api;

use App\Models\EventGuestModel;
use App\Models\BookingModel;
use App\Models\EventModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GuestApiController extends ResourceController
{
    protected $guestModel;
    protected $bookingModel;
    protected $eventModel;

    public function __construct()
    {
        $this->guestModel = new EventGuestModel();
        $this->bookingModel = new BookingModel();
        $this->eventModel = new EventModel();
    }

    /**
     * Get all guests for a specific event
     * GET /api/events/{eventId}/guests
     */
    public function getGuests($eventId)
    {
        try {
            log_message('info', 'getGuests() - Called with eventId: ' . $eventId);

            // Get event to find associated booking_id
            $event = $this->eventModel->find($eventId);
            log_message('info', 'getGuests() - Event lookup result: ' . ($event ? json_encode($event) : 'NULL'));

            if (!$event) {
                log_message('warning', 'getGuests() - Event not found for ID: ' . $eventId);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get booking_id from event
            $bookingId = $event['booking_id'];
            log_message('info', 'getGuests() - Extracted booking_id: ' . $bookingId);

            // Verify booking exists
            $booking = $this->bookingModel->find($bookingId);
            log_message('info', 'getGuests() - Booking lookup result: ' . ($booking ? 'Found' : 'NULL'));

            if (!$booking) {
                log_message('warning', 'getGuests() - Booking not found for ID: ' . $bookingId);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Associated booking not found'
                ])->setStatusCode(404);
            }

            // Get guests using booking_id
            $guests = $this->guestModel->getGuestsByBooking($bookingId);
            log_message('info', 'getGuests() - Found ' . count($guests) . ' guests for booking_id: ' . $bookingId);

            if (count($guests) > 0) {
                log_message('info', 'getGuests() - First guest: ' . json_encode($guests[0]));
            }

            return $this->response->setJSON([
                'success' => true,
                'guests' => $guests
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getGuests() - Error fetching guests: ' . $e->getMessage());
            log_message('error', 'getGuests() - Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch guests'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get all guests for a specific booking (for user/student use)
     * GET /api/bookings/{bookingId}/guests
     */
    public function getGuestsByBooking($bookingId)
    {
        try {
            log_message('info', 'getGuestsByBooking() - Called with bookingId: ' . $bookingId);

            // Verify booking exists
            $booking = $this->bookingModel->find($bookingId);
            log_message('info', 'getGuestsByBooking() - Booking lookup result: ' . ($booking ? 'Found' : 'NULL'));

            if (!$booking) {
                log_message('warning', 'getGuestsByBooking() - Booking not found for ID: ' . $bookingId);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Get guests using booking_id
            $guests = $this->guestModel->getGuestsByBooking($bookingId);
            log_message('info', 'getGuestsByBooking() - Found ' . count($guests) . ' guests for booking_id: ' . $bookingId);

            if (count($guests) > 0) {
                log_message('info', 'getGuestsByBooking() - First guest: ' . json_encode($guests[0]));
            }

            return $this->response->setJSON([
                'success' => true,
                'guests' => $guests
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getGuestsByBooking() - Error fetching guests: ' . $e->getMessage());
            log_message('error', 'getGuestsByBooking() - Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch guests'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get attendance statistics for an event
     * GET /api/events/{eventId}/attendance-stats
     */
    public function getAttendanceStats($eventId)
    {
        try {
            // Get event to find associated booking_id
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get booking_id from event
            $bookingId = $event['booking_id'];

            // Get statistics using booking_id
            $stats = $this->guestModel->getAttendanceStats($bookingId);

            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching attendance stats: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ])->setStatusCode(500);
        }
    }

    /**
     * Create a new guest
     * POST /api/guests/create
     */
    public function create()
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ])->setStatusCode(400);
            }

            // Validate required fields
            if (empty($json['booking_id']) || empty($json['guest_name'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking ID and guest name are required'
                ])->setStatusCode(400);
            }

            // Verify booking exists
            $booking = $this->bookingModel->find($json['booking_id']);
            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Create guest
            $result = $this->guestModel->createGuest($json);

            if ($result['success']) {
                $guest = $this->guestModel->find($result['id']);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Guest created successfully',
                    'guest' => $guest
                ])->setStatusCode(201);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to create guest',
                    'errors' => $result['errors']
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error creating guest: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create guest'
            ])->setStatusCode(500);
        }
    }

    /**
     * Update guest information
     * PUT /api/guests/{guestId}/update
     */
    public function update($guestId = null)
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ])->setStatusCode(400);
            }

            // Check if guest exists
            $guest = $this->guestModel->find($guestId);
            if (!$guest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Guest not found'
                ])->setStatusCode(404);
            }

            // Only allow updating certain fields
            $updateData = [];
            if (isset($json['guest_name'])) $updateData['guest_name'] = $json['guest_name'];
            if (isset($json['guest_email'])) $updateData['guest_email'] = $json['guest_email'];
            if (isset($json['guest_phone'])) $updateData['guest_phone'] = $json['guest_phone'];

            if (empty($updateData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ])->setStatusCode(400);
            }

            // Update guest
            $updated = $this->guestModel->update($guestId, $updateData);

            if ($updated) {
                $guest = $this->guestModel->find($guestId);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Guest updated successfully',
                    'guest' => $guest
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update guest',
                    'errors' => $this->guestModel->errors()
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error updating guest: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update guest'
            ])->setStatusCode(500);
        }
    }

    /**
     * Delete a guest
     * DELETE /api/guests/{guestId}/delete
     */
    public function delete($guestId = null)
    {
        try {
            $result = $this->guestModel->deleteGuest($guestId);

            if ($result['success']) {
                return $this->response->setJSON($result);
            } else {
                return $this->response->setJSON($result)->setStatusCode(404);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting guest: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to delete guest'
            ])->setStatusCode(500);
        }
    }

    /**
     * Record attendance by QR code scan
     * POST /api/attendance/scan
     */
    public function recordAttendance()
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json || empty($json['qr_code'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'QR code is required'
                ])->setStatusCode(400);
            }

            $result = $this->guestModel->recordAttendance($json['qr_code']);

            if ($result['success']) {
                return $this->response->setJSON($result);
            } else {
                $statusCode = ($result['message'] === 'Guest not found') ? 404 : 400;
                return $this->response->setJSON($result)->setStatusCode($statusCode);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error recording attendance: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to record attendance'
            ])->setStatusCode(500);
        }
    }

    /**
     * Manual check-in for a guest
     * POST /api/attendance/manual-checkin
     */
    public function manualCheckIn()
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json || empty($json['guest_id'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Guest ID is required'
                ])->setStatusCode(400);
            }

            $notes = $json['notes'] ?? null;
            $result = $this->guestModel->manualCheckIn($json['guest_id'], $notes);

            if ($result['success']) {
                return $this->response->setJSON($result);
            } else {
                $statusCode = ($result['message'] === 'Guest not found') ? 404 : 400;
                return $this->response->setJSON($result)->setStatusCode($statusCode);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error manual check-in: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to check in guest'
            ])->setStatusCode(500);
        }
    }

    /**
     * Download QR code image
     * GET /api/guests/{guestId}/qr-download
     */
    public function downloadQRCode($guestId)
    {
        try {
            $guest = $this->guestModel->find($guestId);

            if (!$guest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Guest not found'
                ])->setStatusCode(404);
            }

            if (!$guest['qr_code_path']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'QR code not generated for this guest'
                ])->setStatusCode(404);
            }

            $filepath = WRITEPATH . $guest['qr_code_path'];

            if (!file_exists($filepath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'QR code file not found'
                ])->setStatusCode(404);
            }

            // Return file for download
            return $this->response->download($filepath, null)
                ->setFileName($guest['qr_code'] . '.png');
        } catch (\Exception $e) {
            log_message('error', 'Error downloading QR code: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to download QR code'
            ])->setStatusCode(500);
        }
    }

    /**
     * Get QR code image URL
     * GET /api/guests/{guestId}/qr-url
     */
    public function getQRCodeUrl($guestId)
    {
        try {
            $guest = $this->guestModel->find($guestId);

            if (!$guest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Guest not found'
                ])->setStatusCode(404);
            }

            if (!$guest['qr_code_path']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'QR code not generated'
                ])->setStatusCode(404);
            }

            // Return URL path (assuming writable is accessible via /writable/)
            return $this->response->setJSON([
                'success' => true,
                'qr_url' => base_url($guest['qr_code_path']),
                'qr_code' => $guest['qr_code']
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting QR URL: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get QR code URL'
            ])->setStatusCode(500);
        }
    }

    /**
     * Bulk create guests
     * POST /api/guests/bulk-create
     */
    public function bulkCreate()
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json || empty($json['guests']) || !is_array($json['guests'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid guest data'
                ])->setStatusCode(400);
            }

            $results = $this->guestModel->createGuestsInBulk($json['guests']);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Guests created',
                'results' => $results
            ])->setStatusCode(201);
        } catch (\Exception $e) {
            log_message('error', 'Error bulk creating guests: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create guests'
            ])->setStatusCode(500);
        }
    }

    /**
     * Public guest self-registration endpoint
     * POST /api/guest-registration/register
     * No authentication required
     */
    public function publicRegister()
    {
        helper('email');

        try {
            $json = $this->request->getJSON(true);

            if (!$json) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request data'
                ])->setStatusCode(400);
            }

            // Validate required fields
            if (empty($json['booking_id']) || empty($json['guest_name']) || empty($json['guest_email'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide your name and email address'
                ])->setStatusCode(400);
            }

            // Validate email format
            if (!filter_var($json['guest_email'], FILTER_VALIDATE_EMAIL)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide a valid email address'
                ])->setStatusCode(400);
            }

            // Verify booking exists and is confirmed (with facility name)
            $booking = $this->bookingModel
                ->select('bookings.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = bookings.facility_id')
                ->where('bookings.id', $json['booking_id'])
                ->first();

            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            if ($booking['status'] !== 'confirmed') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This event is not yet confirmed'
                ])->setStatusCode(400);
            }

            // Check if event hasn't passed
            $eventDate = strtotime($booking['event_date']);
            $today = strtotime(date('Y-m-d'));

            if ($eventDate < $today) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This event has already passed'
                ])->setStatusCode(400);
            }

            // Check for duplicate email registration for this event
            $existingGuest = $this->guestModel->where([
                'booking_id' => $json['booking_id'],
                'guest_email' => $json['guest_email']
            ])->first();

            if ($existingGuest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already registered for this event'
                ])->setStatusCode(400);
            }

            // Create guest with QR code
            log_message('info', 'Attempting to create guest: ' . json_encode($json));
            $result = $this->guestModel->createGuest($json);
            log_message('info', 'Guest creation result: ' . json_encode($result));

            if ($result['success']) {
                // Get the created guest
                $guest = $this->guestModel->find($result['id']);

                // Send registration confirmation email with QR code
                // Note: facility_name is already included from JOIN query above
                $emailSent = sendGuestRegistrationEmail($guest, $booking, $result['qr_code_path']);

                if (!$emailSent) {
                    log_message('warning', "Guest registered but email failed to send to {$guest['guest_email']}");
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Registration successful! Check your email for your QR code.',
                    'guest' => [
                        'name' => $guest['guest_name'],
                        'email' => $guest['guest_email'],
                        'qr_code' => $guest['qr_code']
                    ]
                ])->setStatusCode(201);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.',
                    'errors' => $result['errors']
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error in public guest registration: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.',
                'debug' => ENVIRONMENT === 'development' ? $e->getMessage() : null
            ])->setStatusCode(500);
        }
    }

    /**
     * Export attendance data to Excel
     * GET /api/events/{eventId}/attendance-export
     */
    public function exportAttendanceExcel($eventId)
    {
        try {
            // Get event to find associated booking_id
            $event = $this->eventModel
                ->select('events.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = events.facility_id', 'left')
                ->find($eventId);

            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ])->setStatusCode(404);
            }

            // Get booking_id from event
            $bookingId = $event['booking_id'];

            // Get guests using booking_id
            $guests = $this->guestModel->getGuestsByBooking($bookingId);

            // Get statistics
            $stats = $this->guestModel->getAttendanceStats($bookingId);

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Attendance Report');

            // Set header information
            $sheet->setCellValue('A1', 'EVENT ATTENDANCE REPORT');
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Event details
            $row = 3;
            $sheet->setCellValue('A' . $row, 'Event Name:');
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
            $sheet->setCellValue('B' . $row, $event['event_time']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            // Statistics
            $row += 2;
            $sheet->setCellValue('A' . $row, 'STATISTICS');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');

            $row++;
            $sheet->setCellValue('A' . $row, 'Total Guests:');
            $sheet->setCellValue('B' . $row, $stats['total']);
            $sheet->setCellValue('C' . $row, 'Attended:');
            $sheet->setCellValue('D' . $row, $stats['attended']);
            $sheet->setCellValue('E' . $row, 'Pending:');
            $sheet->setCellValue('F' . $row, $stats['pending']);
            $sheet->setCellValue('G' . $row, 'Rate: ' . $stats['attendance_rate'] . '%');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);

            // Guest data table header
            $row += 2;
            $headerRow = $row;
            $headers = ['No.', 'Guest Name', 'Email', 'Phone', 'QR Code', 'Status', 'Check-in Time'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }

            // Style header row
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A5568');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Guest data rows
            $row++;
            $no = 1;
            foreach ($guests as $guest) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $guest['guest_name']);
                $sheet->setCellValue('C' . $row, $guest['guest_email'] ?? '-');
                $sheet->setCellValue('D' . $row, $guest['guest_phone'] ?? '-');
                $sheet->setCellValue('E' . $row, $guest['qr_code']);

                // Status with color
                $status = $guest['attended'] == 1 ? 'Attended' : 'Pending';
                $sheet->setCellValue('F' . $row, $status);
                if ($guest['attended'] == 1) {
                    $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');
                    $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('065F46');
                } else {
                    $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                    $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('92400E');
                }

                // Check-in time
                $time = $guest['attended'] == 1 && $guest['attendance_time']
                    ? date('Y-m-d H:i:s', strtotime($guest['attendance_time']))
                    : 'Not checked in';
                $sheet->setCellValue('G' . $row, $time);

                $row++;
                $no++;
            }

            // Add borders to table
            $lastRow = $row - 1;
            $sheet->getStyle('A' . $headerRow . ':G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Auto-size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $eventName = preg_replace('/[^A-Za-z0-9\-]/', '_', $event['event_title']);
            $filename = 'Attendance_' . $eventName . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the spreadsheet
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return file for download
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error exporting attendance to Excel: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to export attendance data'
            ])->setStatusCode(500);
        }
    }

    /**
     * Export attendance data to Excel using booking ID
     * GET /api/bookings/{bookingId}/export-attendance
     */
    public function exportAttendanceByBooking($bookingId)
    {
        try {
            // Get booking details
            $booking = $this->bookingModel
                ->select('bookings.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
                ->find($bookingId);

            if (!$booking) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Booking not found'
                ])->setStatusCode(404);
            }

            // Get guests using booking_id
            $guests = $this->guestModel->getGuestsByBooking($bookingId);

            // Get statistics
            $stats = $this->guestModel->getAttendanceStats($bookingId);

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Attendance Report');

            // Set header information
            $sheet->setCellValue('A1', 'EVENT ATTENDANCE REPORT');
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Event details
            $row = 3;
            $sheet->setCellValue('A' . $row, 'Event Name:');
            $sheet->setCellValue('B' . $row, $booking['event_title']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue('A' . $row, 'Facility:');
            $sheet->setCellValue('B' . $row, $booking['facility_name']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue('A' . $row, 'Client:');
            $sheet->setCellValue('B' . $row, $booking['client_name']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue('A' . $row, 'Event Date:');
            $sheet->setCellValue('B' . $row, date('F d, Y', strtotime($booking['event_date'])));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue('A' . $row, 'Event Time:');
            $sheet->setCellValue('B' . $row, $booking['event_time']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);

            // Statistics
            $row += 2;
            $sheet->setCellValue('A' . $row, 'STATISTICS');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');

            $row++;
            $sheet->setCellValue('A' . $row, 'Total Guests:');
            $sheet->setCellValue('B' . $row, $stats['total']);
            $sheet->setCellValue('C' . $row, 'Attended:');
            $sheet->setCellValue('D' . $row, $stats['attended']);
            $sheet->setCellValue('E' . $row, 'Pending:');
            $sheet->setCellValue('F' . $row, $stats['pending']);
            $sheet->setCellValue('G' . $row, 'Rate: ' . $stats['attendance_rate'] . '%');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);

            // Guest data table header
            $row += 2;
            $headerRow = $row;
            $headers = ['No.', 'Guest Name', 'Email', 'Phone', 'QR Code', 'Status', 'Check-in Time'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }

            // Style header row
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A5568');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Guest data rows
            $row++;
            $no = 1;
            foreach ($guests as $guest) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $guest['guest_name']);
                $sheet->setCellValue('C' . $row, $guest['guest_email'] ?? '-');
                $sheet->setCellValue('D' . $row, $guest['guest_phone'] ?? '-');
                $sheet->setCellValue('E' . $row, $guest['qr_code']);

                // Status with color
                $status = $guest['attended'] == 1 ? 'Attended' : 'Pending';
                $sheet->setCellValue('F' . $row, $status);
                if ($guest['attended'] == 1) {
                    $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');
                    $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('065F46');
                } else {
                    $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                    $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('92400E');
                }

                // Check-in time
                $time = $guest['attended'] == 1 && $guest['attendance_time']
                    ? date('Y-m-d H:i:s', strtotime($guest['attendance_time']))
                    : 'Not checked in';
                $sheet->setCellValue('G' . $row, $time);

                $row++;
                $no++;
            }

            // Add borders to table
            $lastRow = $row - 1;
            $sheet->getStyle('A' . $headerRow . ':G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Auto-size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $eventName = preg_replace('/[^A-Za-z0-9\-]/', '_', $booking['event_title']);
            $filename = 'Attendance_' . $eventName . '_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the spreadsheet
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return file for download
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error exporting attendance by booking: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to export attendance data',
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Export all attendance data to Excel
     * GET /api/bookings/export-all-attendance
     */
    public function exportAllAttendance()
    {
        try {
            // Get all confirmed bookings
            $bookings = $this->bookingModel
                ->select('bookings.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
                ->whereIn('bookings.status', ['confirmed', 'completed'])
                ->findAll();

            if (empty($bookings)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No confirmed bookings found'
                ])->setStatusCode(404);
            }

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('All Attendance');

            // Set header information
            $sheet->setCellValue('A1', 'ALL EVENTS ATTENDANCE REPORT');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Column headers
            $row = 3;
            $headers = ['Event ID', 'Event Title', 'Facility', 'Client', 'Date', 'Time', 'Total Guests', 'Attended', 'Attendance Rate'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }

            // Style header row
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4A5568');
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Data rows
            $row++;
            $totalGuests = 0;
            $totalAttended = 0;

            foreach ($bookings as $booking) {
                // Get statistics for this booking
                $stats = $this->guestModel->getAttendanceStats($booking['id']);

                $totalGuests += $stats['total'];
                $totalAttended += $stats['attended'];

                $sheet->setCellValue('A' . $row, '#' . $booking['id']);
                $sheet->setCellValue('B' . $row, $booking['event_title']);
                $sheet->setCellValue('C' . $row, $booking['facility_name']);
                $sheet->setCellValue('D' . $row, $booking['client_name']);
                $sheet->setCellValue('E' . $row, date('M d, Y', strtotime($booking['event_date'])));
                $sheet->setCellValue('F' . $row, $booking['event_time']);
                $sheet->setCellValue('G' . $row, $stats['total']);
                $sheet->setCellValue('H' . $row, $stats['attended']);
                $sheet->setCellValue('I' . $row, $stats['attendance_rate'] . '%');

                // Color code attendance rate
                $rate = floatval($stats['attendance_rate']);
                if ($rate >= 80) {
                    $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');
                    $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('065F46');
                } elseif ($rate >= 50) {
                    $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                    $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('92400E');
                } else {
                    $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                    $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('991B1B');
                }

                $row++;
            }

            // Add summary row
            $row++;
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $totalGuests);
            $sheet->setCellValue('H' . $row, $totalAttended);
            $overallRate = $totalGuests > 0 ? round(($totalAttended / $totalGuests) * 100, 1) : 0;
            $sheet->setCellValue('I' . $row, $overallRate . '%');
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');

            // Add borders to table
            $lastRow = $row;
            $sheet->getStyle('A3:I' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate filename
            $filename = 'All_Attendance_Report_' . date('Y-m-d') . '.xlsx';
            $filepath = WRITEPATH . 'temp/' . $filename;

            // Create temp directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'temp/')) {
                mkdir(WRITEPATH . 'temp/', 0755, true);
            }

            // Save the spreadsheet
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            // Return file for download
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Error exporting all attendance: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to export attendance data',
                'error' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
