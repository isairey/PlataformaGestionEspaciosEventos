<?php

namespace App\Controllers;

class Admin extends BaseController
{
    /**
     * Check if current user has admin role
     * Returns redirect response if not authorized, null if authorized
     */
    private function checkAdminRole()
    {
        $session = session();
        
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'admin') {
            return redirect()->to('/unauthorized')->with('error', 'Admin access required');
        }
        
        return null;
    }

    public function dashboard()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;
        
        return view('admin/dashboard');
    }
    
    public function index()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;
        
        return view('admin/dashboard');
    }

    public function equipment()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        return view('admin/equipment');
    }

    public function plans()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        return view('admin/plans');
    }

    public function events()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        return view('admin/events');
    }
    

    public function users()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;
        
        return view('admin/users');
    }
    
    public function external()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;
        
        return view('admin/external');
    }
   
    public function booking()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;
        
        return view('admin/booking');
    }

    public function bookingManagement()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        $data = [
            'title' => 'Booking Management - CSPC Admin',
            'page' => 'booking-management'
        ];

        return view('admin/bookingManagement', $data);
    }

    /**
     * Alternative booking management page name
     */
    public function bookings()
    {
        return $this->bookingManagement();
    }

    public function internal()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('admin/internal', $data);
    }

    public function attendance()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('admin/attendance', $data);
    }

    public function calendarDebug()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        return view('admin/calendar_debug');
    }

    public function facilitiesManagement()
    {
        $redirect = $this->checkAdminRole();
        if ($redirect) return $redirect;

        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('admin/facilities-management', $data);
    }
}

