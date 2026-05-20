<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class DashboardRouter extends BaseController
{
    /**
     * Smart dashboard router - redirects to appropriate dashboard based on user role
     */
    public function index()
    {
        // Check if user is logged in
        if (!session()->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please login to access the dashboard');
        }

        $role = session()->get('role');

        // Redirect based on role
        switch ($role) {
            case 'admin':
                return redirect()->to('/admin/dashboard');

            case 'student':
                return redirect()->to('/student/dashboard');

            case 'user':
            default:
                return redirect()->to('/user/dashboard');
        }
    }
}
