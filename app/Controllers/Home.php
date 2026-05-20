<?php

namespace App\Controllers;

use App\Models\FacilityModel;
use App\Models\PlanModel;

class Home extends BaseController
{
    protected $facilityModel;
    protected $planModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
        $this->planModel = new PlanModel();
    }

    public function index()
    {
        $session = session();
        
        // Fetch all active facilities from database
        $facilities = $this->facilityModel
            ->where('is_active', 1)
            ->where('is_maintenance', 0)
            ->orderBy('id', 'ASC')
            ->findAll();
        
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'facilities' => $facilities
        ];
        
        return view('home', $data);
    }
    
    public function dashboard(): string
    {
        return view('user/dashboard');
    }

    public function facilities()
    {
        $session = session();
        
        // Fetch all active facilities (including maintenance status which updates dynamically)
        $facilities = $this->facilityModel
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
        
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'facilities' => $facilities
        ];
        
        return view('facilities', $data);
    }

    public function facilityDetail($facilityKey)
    {
        $session = session();
        
        // Fetch facility from database
        $facility = $this->facilityModel
            ->where('facility_key', $facilityKey)
            ->where('is_active', 1)
            ->first();
        
        // If facility not found, throw 404 error
        if (!$facility) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Facility not found');
        }
        
        // Fetch plans for this facility
        $plans = $this->planModel
            ->where('facility_id', $facility['id'])
            ->findAll();
        
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'userEmail' => $session->get('email'),
            'userContact' => $session->get('contact_number'),
            'facility' => $facility,
            'plans' => $plans
        ];
        
        return view('facilities/facility_detail', $data);
    }
    
    public function contact()
    {
        $session = session();
        
        $facilities = $this->facilityModel
            ->where('is_active', 1)
            ->where('is_maintenance', 0)
            ->orderBy('id', 'ASC')
            ->findAll();
        
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name'),
            'facilities' => $facilities
        ];
        
        return view('contact', $data);
    }
    
    public function event()
    {
        return view('event');
    }

    public function about()
    {
        return view('about');
    }
   
    // Backward compatibility methods - redirect to dynamic facility detail
    public function gymnasium()
    {
        return $this->facilityDetail('gymnasium');
    }

    public function pearlmini()
    {
        return $this->facilityDetail('pearl-restaurant');
    }

    public function Auditorium()
    {
        return $this->facilityDetail('auditorium');
    }

    public function Dormitory()
    {
        return $this->facilityDetail('dormitory');
    }

    public function FunctionHall()
    {
        return $this->facilityDetail('function-hall');
    }

    public function PearlHotelRooms()
    {
        return $this->facilityDetail('pearl-hotel-rooms');
    }
    
    public function classroom()
    {
        return $this->facilityDetail('classrooms');
    }

    public function staffhouse(): string
    {
        return $this->facilityDetail('staff-house');
    }
    
    public function events()
    {
        $session = session();
        $data = [
            'isLoggedIn' => $session->get('user_id') !== null,
            'userRole' => $session->get('role'),
            'userName' => $session->get('full_name')
        ];
        
        return view('event', $data);
    }
}

