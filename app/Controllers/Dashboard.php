<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        // Check if user is logged in
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Check user role and redirect accordingly
        $userRole = $session->get('role');
        
        if ($userRole === 'admin') {
            return redirect()->to('/admin');
        } elseif ($userRole === 'student') {
            return redirect()->to('/student/dashboard');
        } elseif ($userRole === 'facilitator') {
            return redirect()->to('/facilitator/checklist');
        }
        
        // Load user dashboard for other user types
        $data = [
            'isLoggedIn' => true,
            'userRole' => $userRole,
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('user/dashboard', $data);
    }

   public function bookings()
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Redirect based on role
        $userRole = $session->get('role');

        if ($userRole === 'student') {
            return redirect()->to('/student/bookings');
        } elseif ($userRole === 'facilitator') {
            return redirect()->to('/facilitator/checklist');
        }

        $data = [
            'isLoggedIn' => true,
            'userRole' => $userRole,
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('user/bookings', $data);
    }

    public function bookingDetails($bookingId = null)
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Redirect students to their booking page
        if ($session->get('role') === 'student') {
            return redirect()->to('/student/bookings');
        }

        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email'),
            'bookingId' => $bookingId
        ];

        return view('user/bookings', $data);
    }

  public function profile()
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Redirect students to their specific profile page
        if ($session->get('role') === 'student') {
            return redirect()->to('/student/profile');
        }

        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('user/profile', $data);
    }

    public function history()
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Redirect students to their specific history page
        if ($session->get('role') === 'student') {
            return redirect()->to('/student/history');
        }


        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];

        return view('user/history', $data);
    }


public function attendance($bookingId = null)
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }

        // Redirect students to their specific attendance page
        if ($session->get('role') === 'student') {
            if ($bookingId) {
                return redirect()->to('/student/attendance/' . $bookingId);
            }
            return redirect()->to('/student/attendance');
        }

        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email'),
            'bookingId' => $bookingId
        ];

        return view('user/attendance', $data);
    }
}