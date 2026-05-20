<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Employee extends Controller
{
    protected $db;
    protected $session;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->session = session();
        helper(['url', 'form']);
    }

    /**
     * Check if current user is Employee or admin
     * Returns redirect response if not authorized
     */
    private function checkEmployeeRole()
    {
        if (!$this->session->get('isLoggedIn')) {
            return redirect()->to('login')->with('error', 'Please log in to continue');
        }
        
        $role = $this->session->get('role');
        if (!in_array($role, ['employee', 'admin'])) {
            return redirect()->to('unauthorized')->with('error', 'Employee access required');
        }
        
        return null;
    }

    /**
     * Employee Dashboard
     */
    public function index()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        return redirect()->to('employee/dashboard');
    }

    /**
     * Main dashboard view
     */
    public function dashboard()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'Employee Dashboard',
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role')
        ];

        return view('employee/dashboard', $data);
    }

    /**
     * Bookings page
     */
    public function bookings()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'My Bookings',
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role')
        ];

        return view('employee/bookings', $data);
    }

    /**
     * Profile page
     */
    public function profile()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        $userId = $this->session->get('user_id');

        // Get user details from database
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return redirect()->to('employee/dashboard')->with('error', 'User not found.');
        }

        $data = [
            'title' => 'My Profile',
            'user' => $user,
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role')
        ];

        return view('employee/profile', $data);
    }

    /**
     * Booking history page
     */
    public function history()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'Booking History',
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role')
        ];

        return view('employee/history', $data);
    }

    public function book()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        // Get ALL facilities (active and inactive) - frontend will handle availability display
        $facilitiesModel = new \App\Models\FacilityModel();
        $facilities = $facilitiesModel
            ->orderBy('name', 'ASC')
            ->findAll();

        // Log the query and results
        log_message('info', '[Employee::book] Query: SELECT * FROM facilities (no filter)');
        log_message('info', '[Employee::book] Facilities found: ' . count($facilities));
        foreach ($facilities as $facility) {
            log_message('info', '[Employee::book] - ' . $facility['name'] . ' (ID: ' . $facility['id'] . ', key: ' . $facility['facility_key'] . ', is_active: ' . $facility['is_active'] . ', is_maintenance: ' . ($facility['is_maintenance'] ?? 'null') . ')');
        }

        $data = [
            'title' => 'Book Facility',
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role'),
            'facilities' => $facilities
        ];

        return view('employee/book', $data);
    }

    public function attendance()
    {
        $redirect = $this->checkEmployeeRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'Booking History',
            'user_name' => $this->session->get('full_name'),
            'user_email' => $this->session->get('email'),
            'user_role' => $this->session->get('role')
        ];

        return view('employee/attendance', $data);
    }
}
