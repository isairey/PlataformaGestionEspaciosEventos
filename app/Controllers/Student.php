<?php

namespace App\Controllers;

class Student extends BaseController
{
    /**
     * Check if current user is a student or admin
     * Returns redirect response if not authorized
     */
    private function checkStudentRole()
    {
        $session = session();
        
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Please log in to continue');
        }
        
        $role = $session->get('role');
        if (!in_array($role, ['student', 'admin'])) {
            return redirect()->to('/unauthorized')->with('error', 'Student access required');
        }
        
        return null;
    }

    public function index()
    {
        $redirect = $this->checkStudentRole();
        if ($redirect) return $redirect;

        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('student/dashboard', $data);
    }

    public function bookings()
    {
        $redirect = $this->checkStudentRole();
        if ($redirect) return $redirect;

        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('student/bookings', $data);
    }

    public function profile()
    {
        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('student/profile', $data);
    }

        public function attendance()
    {
        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('student/attendance', $data);
    }

    public function history()
    {
        $session = session();
        $data = [
            'isLoggedIn' => true,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userId' => $session->get('user_id'),
            'userEmail' => $session->get('email')
        ];
        
        return view('student/history', $data);
    }


    public function booking()
{
    $session = session();
    $data = [
        'isLoggedIn' => true,
        'userRole' => $session->get('role'),
        'userName' => $session->get('full_name'),
        'userId' => $session->get('user_id'),
        'userEmail' => $session->get('email')
    ];
    
    return view('admin/student', $data);
}



}
