<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Faculty extends Controller
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
     * Redirects to /employee/dashboard if valid
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
     * Legacy Faculty Routes - Redirect to Employee
     * All routes point to /employee/* equivalent
     */
    public function index()
    {
        return redirect()->to('employee');
    }

    public function dashboard()
    {
        return redirect()->to('employee/dashboard');
    }

    public function bookings()
    {
        return redirect()->to('employee/bookings');
    }

    public function profile()
    {
        return redirect()->to('employee/profile');
    }

    public function history()
    {
        return redirect()->to('employee/history');
    }

    public function attendance()
    {
        return redirect()->to('employee/attendance');
    }

    public function book()
    {
        return redirect()->to('employee/book');
    }
}


