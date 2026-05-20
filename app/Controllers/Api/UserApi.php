<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\BookingModel;
use App\Models\BookingFileModel;
use App\Services\CancellationEmailService;

class UserApi extends ResourceController
{
    protected $format = 'json';

    // EXISTING METHODS - KEEP AS IS
public function getDashboardStats()
{
    $session = session();
    $userEmail = $session->get('email');  // CHANGED FROM user_id

    if (!$userEmail) {
        return $this->respond(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $bookingModel = new BookingModel();
    
    $stats = [
        'active' => $bookingModel->where('email_address', $userEmail)  // CHANGED
                               ->where('status', 'confirmed')
                               ->where('event_date >=', date('Y-m-d'))
                               ->countAllResults(),
        'pending' => $bookingModel->where('email_address', $userEmail)  // CHANGED
                                ->where('status', 'pending')
                                ->countAllResults(),
        'completed' => $bookingModel->where('email_address', $userEmail)  // CHANGED
                                  ->where('status', 'completed')
                                  ->countAllResults(),
        'totalSpent' => $bookingModel->selectSum('total_cost')
                                   ->where('email_address', $userEmail)  // CHANGED
                                   ->where('status !=', 'cancelled')
                                   ->get()
                                   ->getRow()
                                   ->total_cost ?? 0
    ];

    return $this->respond([
        'success' => true,
        'stats' => $stats
    ]);
}

public function getRecentBookings()
{
    $session = session();
    $userEmail = $session->get('email');  // CHANGED FROM user_id

    if (!$userEmail) {
        return $this->respond(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $bookingModel = new BookingModel();
    
    $bookings = $bookingModel->select('bookings.*, facilities.name as facility_name')
                            ->join('facilities', 'facilities.id = bookings.facility_id')
                            ->where('bookings.email_address', $userEmail)  // CHANGED
                            ->orderBy('bookings.created_at', 'DESC')
                            ->limit(5)
                            ->findAll();

    return $this->respond([
        'success' => true,
        'bookings' => $bookings
    ]);
}

    // NEW METHODS - SECURITY HARDENED
    
    /**
     * Verify user authentication and return email
     * Security: Prevents unauthorized access
     */
    private function verifyUserAccess()
    {
        $userEmail = session()->get('email');
        $userId = session()->get('user_id');
        
        if (!$userEmail || !$userId) {
            log_message('warning', 'Unauthorized access attempt - Missing session data');
            return null;
        }
        
        // Security: Validate email format
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            log_message('error', 'Invalid email in session: ' . $userEmail);
            session()->destroy();
            return null;
        }
        
        return $userEmail;
    }

    /**
     * Verify booking ownership
     * Security: Ensures users can only access their own bookings
     */
    private function verifyBookingOwnership($bookingId, $userEmail)
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->select('id, email_address, status')
                                ->where('id', $bookingId)
                                ->first();
        
        if (!$booking) {
            log_message('warning', "Booking not found: {$bookingId}");
            return false;
        }
        
        if ($booking['email_address'] !== $userEmail) {
            log_message('warning', "Unauthorized booking access attempt - User: {$userEmail}, Booking: {$bookingId}");
            return false;
        }
        
        return $booking;
    }

    /**
     * Get user's bookings list
     * Security: Only returns bookings for authenticated user's email
     */
    public function getUserBookings()
    {
        try {
            $userEmail = $this->verifyUserAccess();
            
            if (!$userEmail) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $bookingModel = new BookingModel();
            $bookingFileModel = new BookingFileModel();

            // Security: Filter by email only, no SQL injection possible
            $bookings = $bookingModel
                ->select('bookings.*, facilities.name as facility_name, facilities.icon as facility_icon, facilities.additional_hours_rate as hourly_rate, plans.name as plan_name')
                ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
                ->join('plans', 'plans.id = bookings.plan_id', 'left')
                ->where('bookings.email_address', $userEmail)
                ->orderBy('bookings.created_at', 'DESC')
                ->findAll();

            // Check receipt status for each booking
foreach ($bookings as &$booking) {
    $receipt = $bookingFileModel
        ->where('booking_id', $booking['id'])
        ->where('file_type', 'receipt')
        ->orderBy('upload_date', 'DESC')
        ->first();
    
    $booking['receipt_uploaded'] = $receipt !== null;
    $booking['receipt_uploaded_at'] = $receipt ? $receipt['upload_date'] : null;
}

            return $this->respond([
                'success' => true,
                'bookings' => $bookings
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in getUserBookings: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load bookings'
            ], 500);
        }
    }

    /**
     * Get booking details for user
     * Security: Verifies ownership before returning details
     */
    public function getUserBookingDetails($bookingId)
    {
        try {
            // Security: Validate booking ID
            if (!is_numeric($bookingId) || $bookingId < 1) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid booking ID'
                ], 400);
            }

            $userEmail = $this->verifyUserAccess();
            if (!$userEmail) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $bookingModel = new BookingModel();
            $booking = $bookingModel->getBookingWithFullDetails($bookingId);
            
            if (!$booking) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Security: Verify ownership
            if ($booking['email_address'] !== $userEmail) {
                log_message('warning', "Unauthorized access to booking {$bookingId} by {$userEmail}");
                return $this->respond([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Check receipt status
$bookingFileModel = new BookingFileModel();
$receipt = $bookingFileModel
    ->where('booking_id', $bookingId)
    ->where('file_type', 'receipt')
    ->orderBy('upload_date', 'DESC')
    ->first();

$booking['receipt_uploaded'] = $receipt !== null;
$booking['receipt_uploaded_at'] = $receipt ? $receipt['upload_date'] : null;

            // Security: Remove sensitive data before sending
            unset($booking['decline_reason']);
            if ($booking['status'] !== 'cancelled') {
                unset($booking['decline_notes']);
            }

            return $this->respond([
                'success' => true,
                'booking' => $booking
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in getUserBookingDetails: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load booking details'
            ], 500);
        }
    }

    /**
     * Upload receipt for booking
     * Security: Multiple validation layers
     */
    public function uploadReceipt($bookingId)
    {
        try {
            // Security: Validate booking ID
            if (!is_numeric($bookingId) || $bookingId < 1) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid booking ID'
                ], 400);
            }

            $userEmail = $this->verifyUserAccess();
            if (!$userEmail) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Security: Verify booking ownership and status
            $booking = $this->verifyBookingOwnership($bookingId, $userEmail);
            if (!$booking) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Booking not found or access denied'
                ], 403);
            }

            // Security: Only allow uploads for pending/confirmed bookings
            if (!in_array($booking['status'], ['pending', 'confirmed'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Cannot upload receipt for ' . $booking['status'] . ' bookings'
                ], 400);
            }

            // Security: Validate file upload
            $file = $this->request->getFile('receipt');
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid file upload'
                ], 400);
            }

            // Security: Strict file type validation
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            $fileMime = $file->getMimeType();
            
            if (!in_array($fileMime, $allowedMimes)) {
                log_message('warning', "Invalid file type attempted: {$fileMime} by {$userEmail}");
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid file type. Only PDF, JPG, and PNG allowed.'
                ], 400);
            }

            // Security: File size limit (5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return $this->respond([
                    'success' => false,
                    'message' => 'File exceeds 5MB limit'
                ], 400);
            }

            // Security: Create isolated directory per booking
            $uploadPath = WRITEPATH . 'uploads/booking_files/' . $bookingId . '/';
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    log_message('error', "Failed to create upload directory: {$uploadPath}");
                    return $this->respond([
                        'success' => false,
                        'message' => 'Upload directory creation failed'
                    ], 500);
                }
            }

            // Security: Generate cryptographically secure filename
            $extension = $file->getExtension();
            $newName = bin2hex(random_bytes(16)) . '.' . $extension;
            
            // Move file
            if (!$file->move($uploadPath, $newName)) {
                log_message('error', "File move failed for booking {$bookingId}");
                return $this->respond([
                    'success' => false,
                    'message' => 'File upload failed'
                ], 500);
            }

            // Save to database
$bookingFileModel = new BookingFileModel();
$fileData = [
    'booking_id' => $bookingId,
    'file_type' => 'receipt',
    'original_filename' => $file->getClientName(),
    'stored_filename' => $newName,
    'file_path' => $uploadPath . $newName,
    'file_size' => $file->getSize(),
    'mime_type' => $fileMime,
    'uploaded_by' => null,
    'upload_date' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

            $fileId = $bookingFileModel->insert($fileData);

            if (!$fileId) {
                // Cleanup on failure
                @unlink($uploadPath . $newName);
                log_message('error', "Database insert failed for booking {$bookingId}");
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to save upload record'
                ], 500);
            }

            log_message('info', "Receipt uploaded successfully - Booking: {$bookingId}, User: {$userEmail}, File: {$fileId}");

            return $this->respond([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'file_id' => $fileId
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Upload error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Upload failed'
            ], 500);
        }
    }

    /**
     * Download receipt
     * Security: Verifies ownership before allowing download
     */
public function downloadReceipt($bookingId)
{
    try {
        // Security: Validate booking ID
        if (!is_numeric($bookingId) || $bookingId < 1) {
            return redirect()->back()->with('error', 'Invalid booking ID');
        }

        $userEmail = $this->verifyUserAccess();
        if (!$userEmail) {
            return redirect()->to('/login');
        }

        // Security: Verify ownership
        $booking = $this->verifyBookingOwnership($bookingId, $userEmail);
        if (!$booking) {
            log_message('warning', "Unauthorized download attempt - Booking: {$bookingId}, User: {$userEmail}");
            return redirect()->back()->with('error', 'Access denied');
        }

        // Get receipt file
        $bookingFileModel = new BookingFileModel();
        $receipt = $bookingFileModel
            ->where('booking_id', $bookingId)
            ->where('file_type', 'receipt')
            ->orderBy('upload_date', 'DESC')
            ->first();

        if (!$receipt) {
            return redirect()->back()->with('error', 'Receipt not found');
        }

        // Security: Verify file exists and is within allowed directory
        $filePath = $receipt['file_path'];
        $allowedPath = WRITEPATH . 'uploads/booking_files/' . $bookingId . '/';
        
        if (strpos(realpath($filePath), realpath($allowedPath)) !== 0) {
            log_message('error', "Path traversal attempt detected: {$filePath}");
            return redirect()->back()->with('error', 'Invalid file path');
        }

        if (!file_exists($filePath)) {
            log_message('error', "Receipt file missing: {$filePath}");
            return redirect()->back()->with('error', 'File not found on server');
        }

        // Security: Set secure headers
        return $this->response
            ->download($filePath, null)
            ->setFileName($receipt['original_filename'])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Security-Policy', "default-src 'none'");

    } catch (\Exception $e) {
        log_message('error', 'Download error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Download failed');
    }
}

/**
 * Delete receipt
 * Security: Verifies ownership before allowing deletion
 */
public function deleteReceipt($bookingId)
{
    try {
        // Security: Validate booking ID
        if (!is_numeric($bookingId) || $bookingId < 1) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid booking ID'
            ], 400);
        }

        $userEmail = $this->verifyUserAccess();
        if (!$userEmail) {
            return $this->respond([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Security: Verify ownership
        $booking = $this->verifyBookingOwnership($bookingId, $userEmail);
        if (!$booking) {
            log_message('warning', "Unauthorized delete attempt - Booking: {$bookingId}, User: {$userEmail}");
            return $this->respond([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Get receipt file
        $bookingFileModel = new BookingFileModel();
        $receipt = $bookingFileModel
            ->where('booking_id', $bookingId)
            ->where('file_type', 'receipt')
            ->orderBy('upload_date', 'DESC')
            ->first();

        if (!$receipt) {
            return $this->respond([
                'success' => false,
                'message' => 'Receipt not found'
            ], 404);
        }

        // Security: Verify file exists and is within allowed directory
        $filePath = $receipt['file_path'];
        $allowedPath = WRITEPATH . 'uploads/booking_files/' . $bookingId . '/';
        
        if (strpos(realpath($filePath), realpath($allowedPath)) !== 0) {
            log_message('error', "Path traversal attempt detected on delete: {$filePath}");
            return $this->respond([
                'success' => false,
                'message' => 'Invalid file path'
            ], 400);
        }

        // Delete file from filesystem
        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                log_message('error', "Failed to delete receipt file: {$filePath}");
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to delete file'
                ], 500);
            }
        }

        // Delete from database
        $bookingFileModel->delete($receipt['id']);

        log_message('info', "Receipt deleted successfully - Booking: {$bookingId}, User: {$userEmail}, File ID: {$receipt['id']}");

        return $this->respond([
            'success' => true,
            'message' => 'Receipt deleted successfully'
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Delete receipt error: ' . $e->getMessage());
        return $this->respond([
            'success' => false,
            'message' => 'Delete failed'
        ], 500);
    }
}

/**
 * Cancel booking (user-initiated cancellation)
 * Security: Verifies ownership and validates status
 */
public function cancelBooking($bookingId)
{
    $db = \Config\Database::connect();
    $db->transStart();
    
    try {
        // Add debugging
        log_message('info', "Cancel booking request received for ID: {$bookingId}");
        
        // Security: Validate booking ID
        if (!is_numeric($bookingId) || $bookingId < 1) {
            log_message('warning', "Invalid booking ID received: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Invalid booking ID'
            ], 400);
        }

        $userEmail = $this->verifyUserAccess();
        if (!$userEmail) {
            log_message('warning', "Unauthenticated cancel request for booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        log_message('info', "User {$userEmail} attempting to cancel booking {$bookingId}");

        // Get full booking details
        $bookingModel = new BookingModel();
        $booking = $bookingModel->where('id', $bookingId)->first();
        
        if (!$booking) {
            log_message('warning', "Booking not found in database: {$bookingId}");
            // Add query to check if booking exists at all
            $allBookings = $bookingModel->findAll();
            log_message('info', "Total bookings in database: " . count($allBookings));
            
            return $this->respond([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        log_message('info', "Booking found - Status: {$booking['status']}, Email: {$booking['email_address']}");

        // Security: Verify ownership
        if ($booking['email_address'] !== $userEmail) {
            log_message('warning', "Unauthorized cancellation attempt - User: {$userEmail}, Booking Owner: {$booking['email_address']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Security: Only allow cancelling pending or confirmed bookings
        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            log_message('warning', "Attempt to cancel non-cancellable booking - Status: {$booking['status']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Only pending or confirmed bookings can be cancelled'
            ], 400);
        }

        // Get cancellation details from POST request
        $reason = $this->request->getPost('reason');
        $notes = $this->request->getPost('notes');
        
        // Validation
        if (empty($reason)) {
            return $this->respond([
                'success' => false,
                'message' => 'Cancellation reason is required'
            ], 400);
        }

        // Handle cancellation letter file upload
        $cancelLetterPath = null;
        $file = $this->request->getFile('cancel_letter');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Validate file size (10MB)
            if ($file->getSize() > 10 * 1024 * 1024) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Cancellation letter must be less than 10MB'
                ], 400);
            }

            // Validate file type
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Only PDF, JPG, and PNG files are allowed'
                ], 400);
            }

            // Create cancellations directory if it doesn't exist
            $uploadDir = WRITEPATH . 'uploads/cancellations';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Move file to uploads directory
            $newName = $file->getRandomName();
            $file->move($uploadDir, $newName);
            $cancelLetterPath = 'cancellations/' . $newName;
        } else {
            return $this->respond([
                'success' => false,
                'message' => 'Cancellation letter is required'
            ], 400);
        }

        // Update booking status to "cancelled" directly
        $updateData = [
            'status' => 'cancelled',
            'decline_reason' => $reason,
            'decline_notes' => 'USER CANCELLED: ' . ($notes ?? 'No additional notes provided'),
            'cancellation_letter_path' => $cancelLetterPath,
            'cancellation_requested_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updated = $bookingModel->update($bookingId, $updateData);
        
        if (!$updated) {
            $dbError = $db->error();
            log_message('error', "Database update error for booking {$bookingId}: " . json_encode($dbError));
            log_message('error', "Update data: " . json_encode($updateData));
            // Try to get more detailed error info
            log_message('error', "Last query: " . $db->getLastQuery());
        }
        
        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \Exception('Database transaction failed');
        }
        
        if ($updated) {
            log_message('info', "Cancellation request submitted for booking #{$bookingId} by user {$userEmail}. Reason: {$reason}");
            
            // Send cancellation request notification email to system
            try {
                $emailService = new CancellationEmailService();
                $session = session();
                $userFullName = $session->get('full_name') ?? 'User';
                
                $emailService->sendCancellationNotification(
                    $booking,
                    $reason,
                    $notes,
                    $userEmail,
                    $userFullName
                );
            } catch (\Exception $emailException) {
                log_message('error', 'Failed to send cancellation email: ' . $emailException->getMessage());
                // Don't fail the cancellation if email fails - log and continue
            }
            
            return $this->respond([
                'success' => true,
                'message' => 'Booking cancelled successfully. Cancellation notice has been sent to the office.'
            ]);
        } else {
            log_message('error', "Failed to update booking {$bookingId} in database");
            return $this->respond([
                'success' => false,
                'message' => 'Failed to cancel booking'
            ], 500);
        }

    } catch (\Exception $e) {
        $db->transRollback();
        log_message('error', 'Error cancelling booking: ' . $e->getMessage());
        return $this->respond([
            'success' => false,
            'message' => 'Failed to cancel booking: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Restore equipment inventory when booking is cancelled
 */
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

/**
 * Delete a cancelled booking
 */
public function deleteBooking($bookingId)
{
    try {
        // Security: Validate booking ID
        if (!is_numeric($bookingId) || $bookingId < 1) {
            log_message('warning', "Invalid booking ID for deletion: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Invalid booking ID'
            ], 400);
        }

        $userEmail = $this->verifyUserAccess();
        if (!$userEmail) {
            log_message('warning', "Unauthenticated delete request for booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Get booking
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            log_message('warning', "Booking not found for deletion: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Security: Verify ownership
        if ($booking['email_address'] !== $userEmail) {
            log_message('warning', "Unauthorized deletion attempt - User: {$userEmail}, Booking Owner: {$booking['email_address']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Security: Only allow deleting cancelled bookings
        if ($booking['status'] !== 'cancelled') {
            log_message('warning', "Attempt to delete non-cancelled booking - Status: {$booking['status']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Only cancelled bookings can be deleted'
            ], 400);
        }

        // Delete cancellation letter file if it exists
        if (!empty($booking['cancellation_letter_path'])) {
            $filePath = WRITEPATH . 'uploads/' . $booking['cancellation_letter_path'];
            if (file_exists($filePath)) {
                try {
                    unlink($filePath);
                    log_message('info', "Deleted cancellation letter: {$filePath}");
                } catch (\Exception $fileException) {
                    log_message('warning', "Failed to delete cancellation letter file: " . $fileException->getMessage());
                    // Don't fail the deletion if file deletion fails
                }
            }
        }

        // Delete the booking
        $deleted = $bookingModel->delete($bookingId);
        
        if ($deleted) {
            log_message('info', "Booking #{$bookingId} deleted by user {$userEmail}");
            return $this->respond([
                'success' => true,
                'message' => 'Booking deleted successfully'
            ]);
        } else {
            log_message('error', "Failed to delete booking {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Failed to delete booking'
            ], 500);
        }

    } catch (\Exception $e) {
        log_message('error', 'Error deleting booking: ' . $e->getMessage());
        return $this->respond([
            'success' => false,
            'message' => 'Failed to delete booking: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Send reschedule request email to admin for pending/confirmed booking
 */
public function rescheduleBooking($bookingId)
{
    try {
        // Security: Validate booking ID
        if (!is_numeric($bookingId) || $bookingId < 1) {
            log_message('warning', "Invalid booking ID for reschedule: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Invalid booking ID'
            ], 400);
        }

        $userEmail = $this->verifyUserAccess();
        if (!$userEmail) {
            log_message('warning', "Unauthenticated reschedule request for booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Get booking
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking) {
            log_message('warning', "Booking not found for reschedule: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Security: Verify ownership
        if ($booking['email_address'] !== $userEmail) {
            log_message('warning', "Unauthorized reschedule attempt - User: {$userEmail}, Booking Owner: {$booking['email_address']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Security: Only allow rescheduling pending or confirmed bookings
        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            log_message('warning', "Attempt to reschedule non-eligible booking - Status: {$booking['status']}, Booking: {$bookingId}");
            return $this->respond([
                'success' => false,
                'message' => 'Only pending or confirmed bookings can be rescheduled'
            ], 400);
        }

        // Get reschedule details from request body
        $json = $this->request->getJSON();
        $reason = $json->reason ?? null;
        $newDate = $json->new_date ?? null;
        $newTime = $json->new_time ?? null;
        $notes = $json->notes ?? '';
        
        // Validation
        if (empty($reason)) {
            return $this->respond([
                'success' => false,
                'message' => 'Reschedule reason is required'
            ], 400);
        }

        if (empty($newDate)) {
            return $this->respond([
                'success' => false,
                'message' => 'New date is required'
            ], 400);
        }

        if (empty($newTime)) {
            return $this->respond([
                'success' => false,
                'message' => 'New time is required'
            ], 400);
        }

        // Validate date format
        if (!strtotime($newDate)) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid date format'
            ], 400);
        }

        // Validate time format
        if (!strtotime($newTime)) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid time format'
            ], 400);
        }

        // Send reschedule email to admin
        try {
            $email = \Config\Services::email();
            
            $email->setFrom('cspcsphere@gmail.com', 'CSPC Sphere Booking System');
            $email->setTo('cspcsphere@gmail.com');
            $email->setSubject('Booking Reschedule Request - ' . $booking['event_title']);
            
            // Prepare email body
            $emailBody = $this->generateRescheduleEmailBody($booking, $reason, $newDate, $newTime, $notes);
            
            $email->setMessage($emailBody);
            
            if (!$email->send()) {
                log_message('error', 'Failed to send reschedule email: ' . $email->printDebugger());
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to send reschedule request email'
                ], 500);
            }
            
            log_message('info', "Reschedule request email sent to admin for booking #{$bookingId} by user {$userEmail}");
            
            return $this->respond([
                'success' => true,
                'message' => 'Reschedule request submitted successfully. Email has been sent to the office.'
            ]);
            
        } catch (\Exception $emailException) {
            log_message('error', 'Error sending reschedule email: ' . $emailException->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to send reschedule request: ' . $emailException->getMessage()
            ], 500);
        }

    } catch (\Exception $e) {
        log_message('error', 'Error processing reschedule request: ' . $e->getMessage());
        return $this->respond([
            'success' => false,
            'message' => 'Failed to process reschedule request: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Generate reschedule email body for admin
 */
private function generateRescheduleEmailBody($booking, $reason, $newDate, $newTime, $notes)
{
    $facilityName = $booking['facility_name'] ?? 'N/A';
    $eventTitle = $booking['event_title'] ?? 'N/A';
    $currentEventDate = isset($booking['event_date']) ? date('F d, Y', strtotime($booking['event_date'])) : 'N/A';
    $currentEventTime = isset($booking['event_time']) ? date('g:i A', strtotime($booking['event_time'])) : 'N/A';
    $clientName = $booking['client_name'] ?? 'Client';
    $clientEmail = $booking['email_address'] ?? 'N/A';
    $contactNumber = $booking['contact_number'] ?? 'N/A';
    $bookingId = $booking['id'] ?? 'N/A';
    $newDateFormatted = date('F d, Y', strtotime($newDate));
    $newTimeFormatted = date('g:i A', strtotime($newTime));
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 700px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .section { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2a5298; }
            .section-title { font-weight: bold; color: #1e3c72; margin-top: 0; margin-bottom: 15px; font-size: 1.1em; }
            .info-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
            .info-table td:first-child { font-weight: bold; width: 30%; color: #666; }
            .info-table tr:last-child td { border-bottom: none; }
            .old-info { background: #fef3cd; }
            .new-info { background: #d1fae5; }
            .footer { background: #1e3c72; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
            .status-badge { display: inline-block; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.9em; }
            .status-pending { background: #fef3c7; color: #92400e; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📅 Booking Reschedule Request</h1>
            </div>
            
            <div class='content'>
                <div class='section'>
                    <h3 class='section-title'>🔔 New Reschedule Request Received</h3>
                    <table class='info-table'>
                        <tr>
                            <td>Booking ID:</td>
                            <td><strong>#{$bookingId}</strong></td>
                        </tr>
                        <tr>
                            <td>Client Name:</td>
                            <td>{$clientName}</td>
                        </tr>
                        <tr>
                            <td>Client Email:</td>
                            <td>{$clientEmail}</td>
                        </tr>
                        <tr>
                            <td>Contact Number:</td>
                            <td>{$contactNumber}</td>
                        </tr>
                        <tr>
                            <td>Request Status:</td>
                            <td><span class='status-badge status-pending'>Pending Review</span></td>
                        </tr>
                    </table>
                </div>
                
                <div class='section'>
                    <h3 class='section-title'>📌 Booking Details</h3>
                    <table class='info-table'>
                        <tr>
                            <td>Facility:</td>
                            <td>{$facilityName}</td>
                        </tr>
                        <tr>
                            <td>Event:</td>
                            <td>{$eventTitle}</td>
                        </tr>
                    </table>
                </div>
                
                <div class='section'>
                    <h3 class='section-title'>🔄 Current vs. Requested Schedule</h3>
                    <table class='info-table'>
                        <tr class='old-info'>
                            <td>Current Date:</td>
                            <td>{$currentEventDate} at {$currentEventTime}</td>
                        </tr>
                        <tr class='new-info'>
                            <td>Requested Date:</td>
                            <td><strong>{$newDateFormatted} at {$newTimeFormatted}</strong></td>
                        </tr>
                    </table>
                </div>
                
                <div class='section'>
                    <h3 class='section-title'>💬 Reschedule Details</h3>
                    <table class='info-table'>
                        <tr>
                            <td>Reason:</td>
                            <td>{$reason}</td>
                        </tr>
                        <tr>
                            <td>Additional Notes:</td>
                            <td>" . (!empty($notes) ? $notes : 'None') . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196F3;'>
                    <strong>⚠️ Action Required:</strong> Please review this reschedule request and approve or reject it. Contact the client if needed at {$clientEmail} or {$contactNumber}.
                </p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email from CSPC Sphere Booking System.</p>
                <p>&copy; " . date('Y') . " CSPC Sphere. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return $html;
}

}