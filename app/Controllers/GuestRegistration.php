<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\EventGuestModel;

class GuestRegistration extends BaseController
{
    protected $bookingModel;
    protected $guestModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->guestModel = new EventGuestModel();
        helper('email');
    }

    /**
     * Display guest registration form
     * Public access - no authentication required
     */
    public function index($bookingId = null)
    {
        if (!$bookingId) {
            return view('public/guest_registration', [
                'error' => 'Invalid registration link. Please check the link provided by the event organizer.'
            ]);
        }

        // Fetch booking details with facility name
        $booking = $this->bookingModel
            ->select('bookings.*, facilities.name as facility_name')
            ->join('facilities', 'facilities.id = bookings.facility_id')
            ->where('bookings.id', $bookingId)
            ->first();

        if (!$booking) {
            return view('public/guest_registration', [
                'error' => 'Event not found. This registration link may be invalid or expired.'
            ]);
        }

        // Check if booking is confirmed
        if ($booking['status'] !== 'confirmed') {
            return view('public/guest_registration', [
                'error' => 'This event is not yet confirmed. Please wait for confirmation from the organizer.'
            ]);
        }

        // Check if event hasn't passed
        $eventDate = strtotime($booking['event_date']);
        $today = strtotime(date('Y-m-d'));

        if ($eventDate < $today) {
            return view('public/guest_registration', [
                'error' => 'This event has already passed. Registration is no longer available.'
            ]);
        }

        // Note: facility_name is already included from JOIN query above
        return view('public/guest_registration', [
            'booking' => $booking
        ]);
    }

    /**
     * Process guest registration
     * Public API endpoint - no authentication required
     */
    public function register()
    {
        // This will be handled by GuestApiController
        // But we can add it here too for direct controller access
        return redirect()->to('/');
    }
}
