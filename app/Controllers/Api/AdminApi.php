<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\FacilityModel;
use App\Models\EquipmentModel;

class AdminApi extends ResourceController
{
    protected $format = 'json';

    /**
     * Verify admin access
     */
    private function verifyAdminAccess()
    {
        $session = session();
        
        if (!$session->get('isLoggedIn')) {
            return false;
        }
        
        if ($session->get('role') !== 'admin') {
            log_message('warning', 'Non-admin user attempted to access admin API: ' . $session->get('email'));
            return false;
        }
        
        return true;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $userModel = new UserModel();
            $bookingModel = new BookingModel();
            $facilityModel = new FacilityModel();
            $equipmentModel = new EquipmentModel();

            $stats = [
                'totalUsers' => $userModel->countAllResults(),
                'activeBookings' => $bookingModel->whereIn('status', ['pending', 'confirmed'])
                                                 ->where('event_date >=', date('Y-m-d'))
                                                 ->countAllResults(),
                'totalFacilities' => $facilityModel->countAllResults(),
                'totalEquipment' => $equipmentModel->selectSum('quantity')->get()->getRow()->quantity ?? 0,
                'pendingBookings' => $bookingModel->where('status', 'pending')->countAllResults(),
                'confirmedBookings' => $bookingModel->where('status', 'confirmed')
                                                    ->where('event_date >=', date('Y-m-d'))
                                                    ->countAllResults(),
                'completedBookings' => $bookingModel->where('status', 'completed')->countAllResults(),
                'cancelledBookings' => $bookingModel->where('status', 'cancelled')->countAllResults()
            ];

            return $this->respond([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching admin dashboard stats: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load dashboard statistics'
            ], 500);
        }
    }

    /**
     * Get recent bookings
     */
    public function getRecentBookings()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $bookingModel = new BookingModel();
            
            $bookings = $bookingModel
                ->select('bookings.*, facilities.name as facility_name, users.full_name as user_name')
                ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
                ->join('users', 'users.email = bookings.email_address', 'left')
                ->orderBy('bookings.created_at', 'DESC')
                ->limit(10)
                ->findAll();

            return $this->respond([
                'success' => true,
                'bookings' => $bookings
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching recent bookings: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load recent bookings'
            ], 500);
        }
    }

    /**
     * Get upcoming events for calendar
     */
    public function getUpcomingEvents()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $bookingModel = new BookingModel();

            // Get year and month from query parameters, default to current month
            $year = $this->request->getGet('year');
            $month = $this->request->getGet('month');

            // Validate and sanitize parameters
            $year = $year ? (int)$year : (int)date('Y');
            $month = $month ? (int)$month : (int)date('m');

            // Ensure valid ranges
            $year = max(2000, min(2100, $year));
            $month = max(1, min(12, $month));

            // Build date range for the specified month
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            $events = $bookingModel
                ->select('bookings.id, bookings.event_date, bookings.event_time, bookings.duration, bookings.status, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = bookings.facility_id', 'left')
                ->where('bookings.event_date >=', $startDate)
                ->where('bookings.event_date <=', $endDate)
                ->whereIn('bookings.status', ['pending', 'confirmed', 'completed'])
                ->orderBy('bookings.event_date', 'ASC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'events' => $events
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching upcoming events: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load upcoming events'
            ], 500);
        }
    }

    /**
     * Get equipment status
     */
    public function getEquipmentStatus()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $equipmentModel = new EquipmentModel();
            
            $equipment = $equipmentModel
                ->select('id, name, quantity, available, status')
                ->where('status', 'available')
                ->orderBy('name', 'ASC')
                ->findAll();

            // Add availability status
            foreach ($equipment as &$item) {
                $item['available_quantity'] = $item['available'];
                $item['availability_status'] = $this->getAvailabilityStatus($item['available'], $item['quantity']);
            }

            return $this->respond([
                'success' => true,
                'equipment' => $equipment
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching equipment status: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load equipment status'
            ], 500);
        }
    }

    /**
     * Determine equipment availability status
     */
    private function getAvailabilityStatus($available, $total)
    {
        if ($available == 0) {
            return 'unavailable';
        }
        
        $percentage = ($available / $total) * 100;
        
        if ($percentage >= 70) {
            return 'available';
        } elseif ($percentage >= 30) {
            return 'limited';
        } else {
            return 'low';
        }
    }

    /**
     * Get facility utilization
     */
    public function getFacilityUtilization()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $bookingModel = new BookingModel();
            $facilityModel = new FacilityModel();
            
            $facilities = $facilityModel->findAll();
            $utilization = [];
            
            foreach ($facilities as $facility) {
                $bookingCount = $bookingModel
                    ->where('facility_id', $facility['id'])
                    ->where('status', 'confirmed')
                    ->where('event_date >=', date('Y-m-01'))
                    ->where('event_date <=', date('Y-m-t'))
                    ->countAllResults();
                
                $utilization[] = [
                    'facility_id' => $facility['id'],
                    'facility_name' => $facility['name'],
                    'booking_count' => $bookingCount
                ];
            }

            return $this->respond([
                'success' => true,
                'utilization' => $utilization
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching facility utilization: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load facility utilization'
            ], 500);
        }
    }

    /**
     * Get pending cancellation requests
     */
    public function getPendingCancellations()
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        try {
            $bookingModel = new BookingModel();
            
            $cancellations = $bookingModel
                ->where('status', 'pending_cancellation')
                ->orderBy('cancellation_requested_at', 'DESC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'cancellations' => $cancellations,
                'count' => count($cancellations)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching pending cancellations: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load pending cancellations'
            ], 500);
        }
    }

    /**
     * Approve booking cancellation
     */
    public function approveCancellation($bookingId = null)
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        if (!$bookingId) {
            return $this->respond([
                'success' => false,
                'message' => 'Booking ID is required'
            ], 400);
        }

        try {
            $bookingModel = new BookingModel();
            $session = session();

            $booking = $bookingModel->find($bookingId);

            if (!$booking) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            if ($booking['status'] !== 'pending_cancellation') {
                return $this->respond([
                    'success' => false,
                    'message' => 'This booking is not pending cancellation'
                ], 400);
            }

            $approvalNotes = $this->request->getPost('approval_notes') ?? 'Cancellation approved';

            $bookingModel->update($bookingId, [
                'status' => 'cancelled',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $session->get('user_id'),
                'approval_notes' => $approvalNotes
            ]);

            log_message('info', 'Booking #' . $bookingId . ' cancellation approved by admin: ' . $session->get('email'));

            return $this->respond([
                'success' => true,
                'message' => 'Cancellation approved successfully'
            ], 200);

        } catch (\Exception $e) {
            log_message('error', 'Error approving cancellation for booking ' . $bookingId . ': ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to approve cancellation'
            ], 500);
        }
    }

    /**
     * Reject booking cancellation
     */
    public function rejectCancellation($bookingId = null)
    {
        if (!$this->verifyAdminAccess()) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        if (!$bookingId) {
            return $this->respond([
                'success' => false,
                'message' => 'Booking ID is required'
            ], 400);
        }

        try {
            $bookingModel = new BookingModel();
            $session = session();

            $booking = $bookingModel->find($bookingId);

            if (!$booking) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            if ($booking['status'] !== 'pending_cancellation') {
                return $this->respond([
                    'success' => false,
                    'message' => 'This booking is not pending cancellation'
                ], 400);
            }

            $rejectionNotes = $this->request->getPost('rejection_notes') ?? 'Cancellation rejected';

            $bookingModel->update($bookingId, [
                'status' => 'confirmed',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $session->get('user_id'),
                'approval_notes' => 'REJECTION: ' . $rejectionNotes,
                'cancellation_letter_path' => null,
                'cancellation_requested_at' => null
            ]);

            log_message('info', 'Booking #' . $bookingId . ' cancellation rejected by admin: ' . $session->get('email'));

            return $this->respond([
                'success' => true,
                'message' => 'Cancellation rejected successfully'
            ], 200);

        } catch (\Exception $e) {
            log_message('error', 'Error rejecting cancellation for booking ' . $bookingId . ': ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to reject cancellation'
            ], 500);
        }
    }

}