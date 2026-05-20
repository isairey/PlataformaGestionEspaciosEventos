<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class StudentController extends BaseController
{
    /**
     * Show student booking page
     * For logged-in students to book facilities
     * URL: /student/book
     */
public function book()
{
    // Get session
    $session = session();
    $isLoggedIn = $session->get('user_id') !== null;
    $userRole = $session->get('role');

    // Redirect if not logged in
    if (!$isLoggedIn) {
        return redirect()->to('/login')->with('error', 'Please log in to book a facility');
    }

    // Only students and admins can access
    if (!in_array($userRole, ['student', 'admin'])) {
        return redirect()->to('/')->with('error', 'Unauthorized access');
    }

    // ✅ FETCH ALL FACILITIES (active and inactive) - frontend handles availability display
    $facilityModel = new \App\Models\FacilityModel();
    $facilities = $facilityModel
        ->orderBy('name', 'ASC')
        ->findAll();

    // Log the query and results
    log_message('info', '[StudentController::book] Query: SELECT * FROM facilities (no filter)');
    log_message('info', '[StudentController::book] Facilities found: ' . count($facilities));
    foreach ($facilities as $facility) {
        log_message('info', '[StudentController::book] - ' . $facility['name'] . ' (ID: ' . $facility['id'] . ', key: ' . $facility['facility_key'] . ', is_active: ' . $facility['is_active'] . ', is_maintenance: ' . ($facility['is_maintenance'] ?? 'null') . ')');
    }

    // Prepare data to pass to view
    $data = [
        'userName' => $session->get('full_name'),
        'userEmail' => $session->get('email'),
        'userPhone' => $session->get('contact_number'),
        'userId' => $session->get('user_id'),
        'userRole' => $userRole,
        'facilities' => $facilities, // ✅ Pass all facilities to view
    ];

    // Load the booking page view
    return view('student/student_book', $data);
}

    /**
     * Show student bookings list
     * URL: /student/bookings
     */
    public function bookings()
    {
        $session = session();
        $isLoggedIn = $session->get('user_id') !== null;

        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $data = [
            'userName' => $session->get('full_name'),
            'userEmail' => $session->get('email'),
        ];

        return view('student/bookings', $data);
    }

    /**
     * Show student dashboard
     * URL: /student/dashboard
     */
    public function dashboard()
    {
        $session = session();
        $isLoggedIn = $session->get('user_id') !== null;

        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $data = [
            'userName' => $session->get('full_name'),
        ];

        return view('student/dashboard', $data);
    }

    /**
     * Show student profile
     * URL: /student/profile
     */
    public function profile()
    {
        $session = session();
        $isLoggedIn = $session->get('user_id') !== null;

        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $data = [
            'userName' => $session->get('full_name'),
            'userEmail' => $session->get('email'),
        ];

        return view('student/profile', $data);
    }

    /**
     * Show booking history
     * URL: /student/history
     */
    public function history()
    {
        $session = session();
        $isLoggedIn = $session->get('user_id') !== null;

        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $data = [
            'userName' => $session->get('full_name'),
        ];

        return view('student/history', $data);
    }
    
}