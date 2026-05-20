<?php

namespace App\Controllers\Api;

use App\Models\BookingExtensionModel;
use App\Models\ExtensionFileModel;
use App\Models\BookingModel;
use App\Models\UserModel;
use App\Models\FacilityModel;
use App\Services\ExtensionEmailService;
use CodeIgniter\RESTful\ResourceController;

class ExtensionApiController extends ResourceController
{
    protected $bookingExtensionModel;
    protected $extensionFileModel;
    protected $bookingModel;
    protected $userModel;
    protected $facilityModel;
    protected $emailService;

    public function __construct()
    {
        $this->bookingExtensionModel = new BookingExtensionModel();
        $this->extensionFileModel = new ExtensionFileModel();
        $this->bookingModel = new BookingModel();
        $this->userModel = new UserModel();
        $this->facilityModel = new FacilityModel();
        $this->emailService = new ExtensionEmailService();
    }

    /**
     * REQUEST EXTENSION - Student/Faculty endpoint
     * POST /api/extensions/request
     */
    public function requestExtension()
    {
        try {
            // Accept both AJAX and JSON POST requests
            $method = strtoupper($this->request->getMethod());
            if ($method !== 'POST') {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method. Use POST.'
                ]);
            }

            $session = session();
            $userId = $session->get('user_id');
            $userFullName = $session->get('full_name');

            if (!$userId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            }

            $requestData = $this->request->getJSON(true);
            $bookingId = $requestData['booking_id'] ?? null;
            $extensionHours = $requestData['extension_hours'] ?? null;
            $reason = $requestData['reason'] ?? '';

            if (!$bookingId || !$extensionHours) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Booking ID and extension hours are required'
                ]);
            }

            if ($extensionHours <= 0 || $extensionHours > 12) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension hours must be between 1 and 12'
                ]);
            }

            $result = $this->bookingExtensionModel->requestExtension(
                $bookingId,
                $extensionHours,
                $userId,
                $userFullName,
                $reason
            );

            if (!$result['success']) {
                return $this->response->setStatusCode(400)->setJSON($result);
            }

            // Send notification email to admin/facilitators
            if (isset($result['extension_id'])) {
                $extension = $this->bookingExtensionModel->find($result['extension_id']);
                $booking = $this->bookingModel->find($bookingId);
                $user = $this->userModel->find($userId);

                if ($extension && $booking && $user) {
                    // Add facility_name to booking for email template
                    if ($booking['facility_id']) {
                        $facility = $this->facilityModel->find($booking['facility_id']);
                        if ($facility) {
                            $booking['facility_name'] = $facility['name'];
                        }
                    }

                    $this->emailService->sendExtensionRequestNotification($extension, $booking, $user);
                }
            }

            return $this->response->setStatusCode(201)->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', 'Extension request error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * GET PENDING EXTENSIONS - Admin/Facilitator
     * GET /api/extensions/pending
     * Supports filtering by: facility_id, booking_id, limit, page
     */
    public function getPendingExtensions()
    {
        try {
            $session = session();
            $userRole = $session->get('role');
            $facilityId = $this->request->getGet('facility_id');
            $bookingId = $this->request->getGet('booking_id');

            if (!in_array($userRole, ['admin', 'facilitator'])) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Not authorized'
                ]);
            }

            $limit = (int)$this->request->getGet('limit') ?? 50;
            $page = (int)$this->request->getGet('page') ?? 1;
            $offset = ($page - 1) * $limit;

            // If booking_id is provided, get extensions for that specific booking
            if ($bookingId) {
                $extension = $this->bookingExtensionModel->getByBookingId($bookingId);
                $extensions = $extension ? [$extension] : [];
            } else if ($userRole === 'admin') {
                $extensions = $this->bookingExtensionModel->getAllPendingExtensions($limit, $offset);
            } else {
                if (!$facilityId) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'message' => 'Facility ID required'
                    ]);
                }
                $extensions = $this->bookingExtensionModel->getPendingExtensionsByFacility($facilityId, $limit, $offset);
            }

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'data' => $extensions,
                'extensions' => $extensions,
                'count' => count($extensions),
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get pending extensions error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * CHECK STUDENT EXTENSION - Student endpoint to check if they have an extension for a booking
     * GET /api/extensions/check-booking/{booking_id}
     * Only students can use this to check their own extensions
     */
    public function checkStudentExtension($bookingId = null)
    {
        try {
            if (!$bookingId) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Booking ID required'
                ]);
            }

            // Get extension for this booking (if any)
            $extension = $this->bookingExtensionModel->getByBookingId($bookingId);

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'has_extension' => $extension !== null,
                'extension' => $extension,
                'extensions' => $extension ? [$extension] : []
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Check student extension error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * GET EXTENSION DETAILS
     * GET /api/extensions/{id}
     */
    public function getExtensionDetails($id = null)
    {
        try {
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension ID required'
                ]);
            }

            $extension = $this->bookingExtensionModel->getExtensionWithDetails($id);

            if (!$extension) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Extension not found'
                ]);
            }

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'data' => $extension
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get extension details error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * APPROVE EXTENSION
     * POST /api/extensions/{id}/approve
     */
    public function approveExtension($id = null)
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid request'
                ]);
            }

            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension ID required'
                ]);
            }

            $session = session();
            $adminId = $session->get('user_id');
            $userRole = $session->get('role');

            if (!in_array($userRole, ['admin', 'facilitator'])) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Not authorized'
                ]);
            }

            $result = $this->bookingExtensionModel->approveExtension($id, $adminId);

            if (!$result['success']) {
                return $this->response->setStatusCode(400)->setJSON($result);
            }

            // Send approval notification email to student
            $extension = $this->bookingExtensionModel->find($id);
            if ($extension) {
                $booking = $this->bookingModel->find($extension['booking_id']);
                $user = $this->userModel->find($extension['requested_by_id']);

                if ($booking && $user) {
                    // Add facility_name to booking for email template
                    if ($booking['facility_id']) {
                        $facility = $this->facilityModel->find($booking['facility_id']);
                        if ($facility) {
                            $booking['facility_name'] = $facility['name'];
                        }
                    }

                    // Optional: attach payment order if available
                    // For now, just send the notification without attachment
                    $this->emailService->sendExtensionApprovalNotification($extension, $booking, $user);
                }
            }

            return $this->response->setStatusCode(200)->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', 'Approve extension error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * REJECT EXTENSION
     * POST /api/extensions/{id}/reject
     */
    public function rejectExtension($id = null)
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid request'
                ]);
            }

            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension ID required'
                ]);
            }

            $requestData = $this->request->getJSON(true);
            $rejectReason = $requestData['reason'] ?? 'No reason provided';

            $result = $this->bookingExtensionModel->rejectExtension($id, $rejectReason);

            if (!$result['success']) {
                return $this->response->setStatusCode(400)->setJSON($result);
            }

            // Send rejection notification email to student
            $extension = $this->bookingExtensionModel->find($id);
            if ($extension) {
                $booking = $this->bookingModel->find($extension['booking_id']);
                $user = $this->userModel->find($extension['requested_by_id']);

                if ($booking && $user) {
                    // Add facility_name to booking for email template
                    if ($booking['facility_id']) {
                        $facility = $this->facilityModel->find($booking['facility_id']);
                        if ($facility) {
                            $booking['facility_name'] = $facility['name'];
                        }
                    }

                    $this->emailService->sendExtensionRejectionNotification($extension, $booking, $user, $rejectReason);
                }
            }

            return $this->response->setStatusCode(200)->setJSON($result);
        } catch (\Exception $e) {
            log_message('error', 'Reject extension error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * UPLOAD FILE
     * POST /api/extensions/{id}/upload
     */
    public function uploadFile($id = null)
    {
        try {
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension ID required'
                ]);
            }

            $file = $this->request->getFile('file');
            $fileType = $this->request->getPost('document_type') ?? $this->request->getPost('file_type');

            if (!$file || !$fileType) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File and file type required'
                ]);
            }

            if (!$file->isValid()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid file: ' . $file->getErrorString()
                ]);
            }

            if ($file->getSize() > 10485760) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File exceeds 10MB limit'
                ]);
            }

            // Get mime type BEFORE moving the file
            $mimeType = $file->getMimeType();
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

            if (!in_array($mimeType, $allowedMimes)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File type not allowed'
                ]);
            }

            $newName = $file->getRandomName();
            $uploadPath = WRITEPATH . 'uploads/extensions/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $newName);

            $session = session();
            $userId = $session->get('user_id');

            $result = $this->extensionFileModel->uploadFile(
                $id,
                $file->getName(),
                $newName,
                $uploadPath . $newName,
                $file->getSize(),
                $mimeType,
                $userId
            );

            if (!$result['success']) {
                return $this->response->setStatusCode(400)->setJSON($result);
            }

            // Fetch the uploaded file details to return
            $uploadedFile = $this->extensionFileModel->find($result['file_id']);
            
            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $result['file_id'],
                'file' => $uploadedFile,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Upload file error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'File upload failed'
            ]);
        }
    }

    /**
     * MARK PAYMENT RECEIVED
     * POST /api/extensions/{id}/mark-paid
     */
    public function markPaymentReceived($id = null)
    {
        try {
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Extension ID required'
                ]);
            }

            log_message('info', "Marking payment received for extension: $id");

            $result = $this->bookingExtensionModel->markPaymentReceived($id);

            if (!$result['success']) {
                log_message('error', "Mark payment result failed: " . json_encode($result));
                return $this->response->setStatusCode(400)->setJSON($result);
            }

            // Send payment confirmation notification email to student
            $extension = $this->bookingExtensionModel->find($id);
            if ($extension) {
                $booking = $this->bookingModel->find($extension['booking_id']);

                // Try to get user info - handle if it doesn't exist
                $user = null;
                if (!empty($extension['user_id'])) {
                    $user = $this->userModel->find($extension['user_id']);
                } elseif (!empty($extension['requested_by_id'])) {
                    $user = $this->userModel->find($extension['requested_by_id']);
                }

                if ($booking && $user) {
                    // Add facility_name to booking for email template
                    if ($booking['facility_id']) {
                        $facility = $this->facilityModel->find($booking['facility_id']);
                        if ($facility) {
                            $booking['facility_name'] = $facility['name'];
                        }
                    }

                    try {
                        $this->emailService->sendPaymentConfirmationNotification($extension, $booking, $user);
                        log_message('info', "Payment confirmation email sent for extension: $id");
                    } catch (\Exception $emailError) {
                        log_message('warning', "Failed to send payment confirmation email: " . $emailError->getMessage());
                        // Don't fail the whole operation if email fails
                    }
                }
            }

            // Fetch updated extension with files for response
            $updatedExtension = $this->bookingExtensionModel->getExtensionWithDetails($id);

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'message' => 'Payment marked as received',
                'data' => $updatedExtension
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Mark payment error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET EXTENSION STATS
     * GET /api/extensions/stats
     */
    public function getStats()
    {
        try {
            $facilityId = $this->request->getGet('facility_id');
            $stats = $this->bookingExtensionModel->getExtensionStats($facilityId);

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get stats error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
    }

    /**
     * DOWNLOAD FILE
     * GET /api/extensions/files/{fileId}/download
     */
    public function downloadFile($fileId = null)
    {
        try {
            if (!$fileId) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File ID required'
                ]);
            }

            $file = $this->extensionFileModel->find($fileId);

            if (!$file) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'File not found'
                ]);
            }

            if (!file_exists($file['file_path'])) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'File does not exist on server'
                ]);
            }

            return $this->response->download($file['file_path'], $file['original_filename']);
        } catch (\Exception $e) {
            log_message('error', 'Download file error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Download failed'
            ]);
        }
    }

    /**
     * DELETE FILE
     * DELETE /api/extensions/files/{fileId}
     */
    public function deleteFile($fileId = null)
    {
        try {
            if (!$fileId) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'File ID required'
                ]);
            }

            $file = $this->extensionFileModel->find($fileId);

            if (!$file) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'File not found'
                ]);
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete database record
            $this->extensionFileModel->delete($fileId);

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Delete file error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Delete failed'
            ]);
        }
    }
}
