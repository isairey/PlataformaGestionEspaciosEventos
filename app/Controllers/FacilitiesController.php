<?php

namespace App\Controllers;

use App\Models\FacilityModel;
use App\Models\AddonModel;
use App\Models\BookingModel;
use App\Models\PlanModel;
use CodeIgniter\RESTful\ResourceController;

class FacilitiesController extends ResourceController
{
    protected $facilityModel;
    protected $addonModel;
    protected $bookingModel;
    protected $planModel;

    protected $db;  
    protected $equipmentModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
        $this->addonModel = new AddonModel();
        $this->bookingModel = new BookingModel();
        $this->planModel = new PlanModel();
        $this->equipmentModel = new \App\Models\EquipmentModel(); 
    $this->db = \Config\Database::connect();
    }

    public function index()
    {
        return view('admin/facilities');
    }

    // API endpoint to get facility data for JavaScript
    public function getFacilityData($facilityKey = null)
    {
        if ($facilityKey) {
            $data = $this->facilityModel->getFacilityWithPlans($facilityKey);

            // DEBUG LOG: Log raw data from database
            log_message('info', '=== FACILITY DATA DEBUG ===');
            log_message('info', 'Facility Key: ' . $facilityKey);
            log_message('info', 'Raw data count: ' . count($data));
            if (!empty($data)) {
                log_message('info', 'First row data: ' . json_encode($data[0]));
                log_message('info', 'additional_hours_rate from DB: ' . ($data[0]['additional_hours_rate'] ?? 'NOT SET'));
            }

            $formattedData = $this->formatFacilityData($data);

            // DEBUG LOG: Log formatted data
            log_message('info', 'Formatted facility data: ' . json_encode($formattedData));
            log_message('info', '=== END FACILITY DATA DEBUG ===');

            return $this->response->setJSON($formattedData);
        } else {
            $data = $this->facilityModel->getAllFacilitiesWithPlans();
            return $this->response->setJSON($this->formatAllFacilitiesData($data));
        }
    }

    // Get addons data
    public function getAddons()
    {
        try {
            $addons = $this->addonModel->findAll();
            log_message('debug', 'getAddons - Raw addons from DB: ' . json_encode($addons));
            
            // Return array directly as expected by JavaScript
            return $this->response->setJSON($addons ?? []);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching addons: ' . $e->getMessage());
            return $this->response->setJSON([])->setStatusCode(500);
        }
    }

    // Get equipment data
    public function getEquipment()
    {
        try {
            $equipmentModel = new \App\Models\EquipmentModel();

            // Get date and facility from query parameters
            $eventDate = $this->request->getGet('event_date');
            $facilityId = $this->request->getGet('facility_id');

            // If date is provided, get equipment available for that specific date
            if ($eventDate) {
                $equipment = $equipmentModel->getEquipmentAvailableForDate($eventDate, $facilityId);
                $filtered_by_date = true;
            } else {
                // Otherwise, get all equipment
                $equipment = $equipmentModel->getEquipmentForBooking();
                $filtered_by_date = false;
            }

            // Return properly formatted response
            return $this->response->setJSON([
                'success' => true,
                'equipment' => $equipment ?? [],
                'filtered_by_date' => $filtered_by_date,
                'event_date' => $eventDate
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching equipment: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to load equipment: ' . $e->getMessage(),
                'equipment' => []
            ])->setStatusCode(500);
        }
    }
    // Create new booking
public function createBooking()
{
    $session = session();
    $userId = $session->get('user_id');

    // Check if user is logged in
    if (!$userId) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'You must be logged in to create a booking'
        ])->setStatusCode(401);
    }

    try {
        $request = $this->request->getJSON(true);
        
        // Get user details from database
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);
        
        if ($user) {
            // Auto-fill user details if not provided in request
            $request['client_name'] = $request['client_name'] ?? $user['full_name'];
            $request['contact_number'] = $request['contact_number'] ?? $user['contact_number'];
            $request['email_address'] = $request['email_address'] ?? $user['email'];
        }

        // Validate required fields
        $validation = \Config\Services::validation();
        $validation->setRules([
            'facility_key' => 'required',
            'plan_id' => 'required|integer',
            'client_name' => 'required|max_length[255]',
            'contact_number' => 'required|max_length[20]',
            'email_address' => 'required|valid_email',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'event_title' => 'required|max_length[255]',
            'total_cost' => 'required|decimal',
             'address' => 'required|max_length[500]'  // CHANGED FROM permit_empty to required
        ]);

        if (!$validation->run($request)) {
            log_message('error', 'Validation failed: ' . json_encode($validation->getErrors()));
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        // Get facility ID from facility_key
        $facility = $this->facilityModel->where('facility_key', $request['facility_key'])->first();
        if (!$facility) {
            log_message('error', 'Facility not found: ' . $request['facility_key']);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Facility not found: ' . $request['facility_key']
            ])->setStatusCode(404);
        }

        // Check for existing pending or completed bookings on the same facility and date
        $existingBooking = $this->bookingModel
            ->where('facility_id', $facility['id'])
            ->where('event_date', $request['event_date'])
            ->whereIn('status', ['pending', 'completed'])
            ->get()
            ->getResultArray();

        // Check for time conflicts (with 2-hour grace period)
        if (!empty($existingBooking)) {
            $newEventTime = $request['event_time'];
            $newDuration = $request['duration'] ?? 4;
            
            // Calculate new event end time
            $newStart = new \DateTime($request['event_date'] . ' ' . $newEventTime);
            $newEnd = clone $newStart;
            $newEnd->add(new \DateInterval('PT' . intval($newDuration * 60) . 'M'));
            
            // Add 2-hour grace period
            $newEndWithGrace = clone $newEnd;
            $newEndWithGrace->add(new \DateInterval('PT2H'));

            // Check each existing booking for time conflict
            foreach ($existingBooking as $booking) {
                $existingStart = new \DateTime($booking['event_date'] . ' ' . $booking['event_time']);
                $existingEnd = clone $existingStart;
                $existingEnd->add(new \DateInterval('PT' . intval($booking['duration'] * 60) . 'M'));
                
                // Add 2-hour grace period to existing booking
                $existingEndWithGrace = clone $existingEnd;
                $existingEndWithGrace->add(new \DateInterval('PT2H'));

                // Check for overlap including grace periods
                if ($newStart < $existingEndWithGrace && $newEndWithGrace > $existingStart) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'This facility has a conflicting booking. Your event time: ' . 
                                   $newStart->format('h:i A') . ' - ' . $newEnd->format('h:i A') . 
                                   ' (with 2-hour grace: until ' . $newEndWithGrace->format('h:i A') . '). ' .
                                   'Existing booking: ' . $existingStart->format('h:i A') . ' - ' . 
                                   $existingEnd->format('h:i A') . ' (with grace until ' . 
                                   $existingEndWithGrace->format('h:i A') . ')'
                    ])->setStatusCode(409);
                }
            }
        }

        // Check facility availability
        $duration = $request['duration'] ?? 4;
        $isAvailable = $this->bookingModel->checkFacilityAvailability(
            $facility['id'],
            $request['event_date'],
            $request['event_time'],
            $duration
        );

        if (!$isAvailable) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Facility is not available at the selected date and time'
            ])->setStatusCode(409);
        }

        // Prepare booking data with proper null handling
        $bookingData = [
            'facility_id' => $facility['id'],
            'plan_id' => $request['plan_id'],
            'client_name' => $request['client_name'],
            'contact_number' => $request['contact_number'],
            'email_address' => $request['email_address'],
            'organization' => !empty($request['organization']) ? $request['organization'] : null,
            'address' => !empty($request['address']) ? $request['address'] : null,
            'event_date' => $request['event_date'],
            'event_time' => $request['event_time'],
            'duration' => $duration,
            'attendees' => !empty($request['attendees']) ? (int)$request['attendees'] : null,
            'event_title' => $request['event_title'],
            'special_requirements' => !empty($request['special_requirements']) ? $request['special_requirements'] : null,
            'total_cost' => (float)$request['total_cost'],
            'additional_hours' => !empty($request['additional_hours']) ? (int)$request['additional_hours'] : 0,
            'maintenance_fee' => !empty($request['maintenance_fee']) ? (float)$request['maintenance_fee'] : 2000.00,
            'status' => 'pending'
        ];

        // Prepare addons data
        $addons = [];
        if (!empty($request['selected_addons']) && is_array($request['selected_addons'])) {
            foreach ($request['selected_addons'] as $addonKey) {
                $addon = $this->addonModel->where('addon_key', $addonKey)->first();
                if ($addon) {
                    $addons[] = [
                        'addon_id' => $addon['id'],
                        'price' => (float)$addon['price']
                    ];
                }
            }
        }

        // Always add maintenance fee as an addon
        $maintenanceAddon = $this->addonModel->where('addon_key', 'maintenance')->first();
        if ($maintenanceAddon) {
            $addons[] = [
                'addon_id' => $maintenanceAddon['id'],
                'price' => (float)$maintenanceAddon['price']
            ];
        }

        // Add additional hours as addon if specified
        if (!empty($request['additional_hours']) && $request['additional_hours'] > 0) {
            // Get the facility-specific hourly rate from database
            $hourlyRate = $facility['extended_hour_rate'] ?? 500;
            $additionalHoursCost = $request['additional_hours'] * $hourlyRate;
            
            // Create or get additional hours addon
            $additionalHoursAddon = $this->addonModel->where('addon_key', 'additional_hours')->first();
            if ($additionalHoursAddon) {
                $addons[] = [
                    'addon_id' => $additionalHoursAddon['id'],
                    'price' => (float)$additionalHoursCost
                ];
            }
        }

        // Prepare equipment data (date-based availability)
$equipment = [];
if (!empty($request['selected_equipment']) && is_array($request['selected_equipment'])) {
    $eventDate = $request['event_date'];

    foreach ($request['selected_equipment'] as $equipmentId => $equipData) {
        // Handle both formats: {id: quantity} or {id: {quantity: X}}
        $quantity = is_array($equipData) ? (int)($equipData['quantity'] ?? 0) : (int)$equipData;

        if ($quantity > 0) {
            // Get equipment by ID directly
            $equipmentItem = $this->equipmentModel->find($equipmentId);

            if ($equipmentItem) {
                // Calculate date-based availability for this equipment
                $bookedQuery = $this->db->table('booking_equipment be')
                    ->select('SUM(be.quantity) as booked_quantity')
                    ->join('bookings b', 'b.id = be.booking_id', 'inner')
                    ->where('be.equipment_id', $equipmentId)
                    ->where('b.event_date', $eventDate)
                    ->whereIn('b.status', ['pending', 'confirmed', 'approved'])
                    ->get();

                $bookedResult = $bookedQuery->getRowArray();
                $bookedQuantity = (int)($bookedResult['booked_quantity'] ?? 0);
                $availableForDate = $equipmentItem['quantity'] - $bookedQuantity;

                if ($availableForDate >= $quantity) {
                    $rate = (float)$equipmentItem['rate'];
                    $totalCost = $rate * $quantity;

                    $equipment[] = [
                        'equipment_id' => $equipmentItem['id'],
                        'quantity' => $quantity,
                        'rate' => $rate,
                        'total_cost' => $totalCost
                    ];
                } else {
                    log_message('warning', "Equipment ID {$equipmentId} not available on {$eventDate}. Requested: {$quantity}, Available: {$availableForDate}");
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Insufficient {$equipmentItem['name']} available on {$eventDate}. Requested: {$quantity}, Available: {$availableForDate}"
                    ])->setStatusCode(409);
                }
            } else {
                log_message('warning', "Equipment ID {$equipmentId} not found");
            }
        }
    }
}

        // Start transaction
        $this->db->transStart();

        // Insert booking
        $bookingId = $this->bookingModel->insert($bookingData);
        
        if (!$bookingId) {
            throw new \Exception('Failed to create booking');
        }

        // Insert addons
        if (!empty($addons)) {
            foreach ($addons as $addon) {
                $this->db->table('booking_addons')->insert([
                    'booking_id' => $bookingId,
                    'addon_id' => $addon['addon_id'],
                    'price' => $addon['price']
                ]);
            }
        }

        // Insert equipment (date-based availability system)
if (!empty($equipment)) {
    $equipmentScheduleModel = new \App\Models\EquipmentScheduleModel();
    
    foreach ($equipment as $equip) {
        // Insert booking equipment record
        $this->db->table('booking_equipment')->insert([
            'booking_id' => $bookingId,
            'equipment_id' => $equip['equipment_id'],
            'quantity' => $equip['quantity'],
            'rate' => $equip['rate'],
            'total_cost' => $equip['total_cost']
        ]);

        // SAVE to equipment_schedule table (persistent date-based tracking)
        $equipmentScheduleModel->updateBookedQuantity(
            $equip['equipment_id'],
            $request['event_date'],
            $equip['quantity']
        );
    }
}

        // Complete transaction
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            log_message('error', 'Transaction failed for booking creation');
            throw new \Exception('Failed to complete booking transaction');
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $bookingId
        ]);

    } catch (\Exception $e) {
        log_message('error', 'Booking creation error: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'An error occurred while creating the booking: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}


    // Get booking data
    public function getBookingData()
    {
        try {
            // Your logic to fetch facility data
            $data = [
                'success' => true,
                'facilities' => $this->facilityModel->findAll()
            ];
            
            return $this->response->setJSON($data);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to load facility data'
            ]);
        }
    }

    private function formatFacilityData($data)
    {
        if (empty($data)) return null;

        $facility = [
            'name' => $data[0]['name'],
            'icon' => $data[0]['icon'],
            'facility_id' => $data[0]['id'] ?? null,
            'extended_hour_rate' => $data[0]['extended_hour_rate'] ?? 500,
            'additional_hours_rate' => $data[0]['additional_hours_rate'] ?? 500,
            'plans' => []
        ];

        $plans = [];
        foreach ($data as $row) {
            $planId = $row['plan_id'];
            
            // Initialize plan if not exists
            if (!isset($plans[$planId])) {
                $plans[$planId] = [
                    'id' => $planId,
                    'name' => $row['plan_name'],
                    'duration' => $row['duration'],
                    'price' => (int)$row['price'],
                    'features' => [],
                    'included_equipment' => []
                ];
            }
            
            // Add feature if exists and not already added
            if ($row['feature'] && !in_array($row['feature'], $plans[$planId]['features'])) {
                $plans[$planId]['features'][] = $row['feature'];
            }
            
            // Add equipment if exists and not already added
            if ($row['equipment_id']) {
                $equipmentExists = false;
                foreach ($plans[$planId]['included_equipment'] as $eq) {
                    if ($eq['id'] === (int)$row['equipment_id']) {
                        $equipmentExists = true;
                        break;
                    }
                }
                
                if (!$equipmentExists) {
                    $plans[$planId]['included_equipment'][] = [
                        'id' => (int)$row['equipment_id'],
                        'name' => $row['equipment_name'],
                        'quantity_included' => (int)$row['quantity_included'],
                        'unit' => $row['unit'],
                        'category' => $row['equipment_category']
                    ];
                }
            }
        }

        $facility['plans'] = array_values($plans);

        return [
            'success' => true,
            'facility' => $facility
        ];
    }

    private function formatAllFacilitiesData($data)
    {
        $facilities = [];

        foreach ($data as $row) {
            $facilityKey = $row['facility_key'];

            if (!isset($facilities[$facilityKey])) {
                $facilities[$facilityKey] = [
                    'name' => $row['name'],
                    'icon' => $row['icon'],
                    'facility_id' => $row['id'] ?? null,
                    'additional_hours_rate' => $row['additional_hours_rate'] ?? 500,
                    'plans' => []
                ];
            }

            if ($row['plan_id']) {
                $planExists = false;
                foreach ($facilities[$facilityKey]['plans'] as &$plan) {
                    if ($plan['id'] == $row['plan_id']) {
                        if ($row['feature']) {
                            $plan['features'][] = $row['feature'];
                        }
                        $planExists = true;
                        break;
                    }
                }

                if (!$planExists) {
                    $newPlan = [
                        'id' => $row['plan_id'],
                        'name' => $row['plan_name'],
                        'duration' => $row['duration'],
                        'price' => (int)$row['price'],
                        'features' => []
                    ];
                    if ($row['feature']) {
                        $newPlan['features'][] = $row['feature'];
                    }
                    $facilities[$facilityKey]['plans'][] = $newPlan;
                }
            }
        }

        return [
            'success' => true,
            'facilities' => array_values($facilities),
            'count' => count($facilities)
        ];
    }

    // ========================================
    // FACILITIES MANAGEMENT API ENDPOINTS
    // ========================================

    // Get all facilities for management
    public function getAllFacilities()
    {
        try {
            $facilities = $this->facilityModel->orderBy('id', 'ASC')->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $facilities
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching facilities: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch facilities'
            ])->setStatusCode(500);
        }
    }

    // Create new facility
    public function createFacility()
    {
        try {
            // Get form data (handles both JSON and FormData)
            $request = $this->request->getPost();
            if (empty($request)) {
                $request = $this->request->getJSON(true);
            }

            // Validation
            $validation = \Config\Services::validation();
            $validation->setRules([
                'name' => 'required|max_length[255]',
                'facility_key' => 'required|max_length[100]|is_unique[facilities.facility_key]',
                'icon' => 'permit_empty|max_length[10]',
                'extended_hour_rate' => 'permit_empty|decimal',
                'additional_hours_rate' => 'permit_empty|decimal',
                'description' => 'permit_empty',
                'is_active' => 'permit_empty|in_list[0,1]',
                'is_maintenance' => 'permit_empty|in_list[0,1]'
            ]);

            if (!$validation->run($request)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ])->setStatusCode(400);
            }

            $facilityData = [
                'name' => $request['name'],
                'facility_key' => $request['facility_key'],
                'icon' => $request['icon'] ?? '🏢',
                'extended_hour_rate' => $request['extended_hour_rate'] ?? 500,
                'additional_hours_rate' => $request['additional_hours_rate'] ?? $request['extended_hour_rate'] ?? 500,
                'description' => $request['description'] ?? null,
                'is_active' => $request['is_active'] ?? 1,
                'is_maintenance' => $request['is_maintenance'] ?? 0
            ];

            $facilityId = $this->facilityModel->insert($facilityData);

            if ($facilityId) {
                // Handle gallery image uploads
                $this->handleGalleryUploads($facilityId, $request['facility_key']);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Facility created successfully',
                    'facility_id' => $facilityId
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to create facility'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error creating facility: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error creating facility: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // Update facility
    public function updateFacility($facilityId)
    {
        try {
            $facility = $this->facilityModel->find($facilityId);
            if (!$facility) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility not found'
                ])->setStatusCode(404);
            }

            // Get form data (handles both JSON and FormData)
            $request = $this->request->getPost();
            if (empty($request)) {
                $request = $this->request->getJSON(true);
            }

            // Validation
            $validation = \Config\Services::validation();
            $validationRules = [
                'name' => 'permit_empty|max_length[255]',
                'icon' => 'permit_empty|max_length[10]',
                'extended_hour_rate' => 'permit_empty|decimal',
                'additional_hours_rate' => 'permit_empty|decimal',
                'description' => 'permit_empty',
                'is_active' => 'permit_empty|in_list[0,1]',
                'is_maintenance' => 'permit_empty|in_list[0,1]'
            ];

            // Only validate facility_key uniqueness if it's being changed
            if (isset($request['facility_key']) && $request['facility_key'] !== $facility['facility_key']) {
                $validationRules['facility_key'] = 'required|max_length[100]|is_unique[facilities.facility_key]';
            }

            $validation->setRules($validationRules);

            if (!$validation->run($request)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ])->setStatusCode(400);
            }

            // Build update data
            $updateData = [];
            if (isset($request['name'])) $updateData['name'] = $request['name'];
            if (isset($request['facility_key'])) $updateData['facility_key'] = $request['facility_key'];
            if (isset($request['icon'])) $updateData['icon'] = $request['icon'];
            if (isset($request['extended_hour_rate'])) $updateData['extended_hour_rate'] = $request['extended_hour_rate'];
            if (isset($request['additional_hours_rate'])) $updateData['additional_hours_rate'] = $request['additional_hours_rate'];
            if (isset($request['description'])) $updateData['description'] = $request['description'];
            if (isset($request['is_active'])) $updateData['is_active'] = $request['is_active'];
            if (isset($request['is_maintenance'])) $updateData['is_maintenance'] = $request['is_maintenance'];

            if (empty($updateData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No data to update'
                ])->setStatusCode(400);
            }

            $result = $this->facilityModel->update($facilityId, $updateData);

            if ($result) {
                // Handle gallery image uploads
                $this->handleGalleryUploads($facilityId, $request['facility_key'] ?? $facility['facility_key']);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Facility updated successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update facility'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error updating facility: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error updating facility: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // Delete facility
    public function deleteFacility($facilityId)
    {
        try {
            $facility = $this->facilityModel->find($facilityId);
            if (!$facility) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility not found'
                ])->setStatusCode(404);
            }

            // Delete the facility - bookings will be preserved with facility_id set to NULL
            $result = $this->facilityModel->delete($facilityId);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Facility deleted successfully. Associated bookings have been preserved.'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete facility'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting facility: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error deleting facility: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // Get lightweight list of active facilities for JavaScript
    // Returns: [{ id, facility_key, name, icon }, ...]
    public function getFacilitiesList()
    {
        try {
            // Return ALL facilities (active and inactive) - frontend will handle availability
            $facilities = $this->facilityModel
                ->select('id, facility_key, name, icon, is_active, is_maintenance, extended_hour_rate')
                ->orderBy('name', 'ASC')
                ->findAll();

            log_message('info', '[getFacilitiesList] Query: SELECT id, facility_key, name, icon, is_active, is_maintenance, extended_hour_rate FROM facilities (no filter)');
            log_message('info', '[getFacilitiesList] Facilities found: ' . count($facilities));
            foreach ($facilities as $facility) {
                log_message('info', '[getFacilitiesList] - ' . $facility['name'] . ' (key: ' . $facility['facility_key'] . ', is_active: ' . $facility['is_active'] . ', is_maintenance: ' . $facility['is_maintenance'] . ')');
            }

            return $this->response->setJSON([
                'success' => true,
                'facilities' => $facilities
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching facilities list: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch facilities list'
            ])->setStatusCode(500);
        }
    }

    // Get facilities for student internal bookings
    // Returns student-specific facility data
    public function getStudentFacilities()
    {
        try {
            $facilities = $this->facilityModel
                ->select('id, facility_key, name, icon, description, is_active, is_maintenance')
                ->orderBy('name', 'ASC')
                ->findAll();

            log_message('info', '[getStudentFacilities] Fetching facilities for student booking');
            log_message('info', '[getStudentFacilities] Total facilities found: ' . count($facilities));

            // Format facilities for student view (internal bookings are free)
            $formattedFacilities = array_map(function($facility) {
                return [
                    'id' => $facility['id'],
                    'key' => $facility['facility_key'],
                    'facility_key' => $facility['facility_key'],
                    'name' => $facility['name'],
                    'title' => $facility['name'],
                    'icon' => $facility['icon'],
                    'description' => $facility['description'] ?? 'No description available',
                    'features' => ['Air Conditioned', 'Sound System', 'Projector'],
                    'price' => 'Free Booking',
                    'is_active' => $facility['is_active'],
                    'is_maintenance' => $facility['is_maintenance']
                ];
            }, $facilities);

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedFacilities
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching student facilities: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch facilities'
            ])->setStatusCode(500);
        }
    }

    // Get facilities for external bookings
    // Returns external-specific facility data with pricing
    public function getExternalFacilities()
    {
        try {
            $facilities = $this->facilityModel
                ->select('id, facility_key, name, icon, description, is_active, is_maintenance, extended_hour_rate')
                ->orderBy('name', 'ASC')
                ->findAll();

            log_message('info', '[getExternalFacilities] Fetching facilities for external booking');
            log_message('info', '[getExternalFacilities] Total facilities found: ' . count($facilities));

            // Format facilities for external view with pricing
            $priceMap = [
                'auditorium' => '₱7,000 - ₱25,000',
                'gymnasium' => '₱7,000 - ₱35,000',
                'function-hall' => '₱1,000 - ₱2,000',
                'pearl-restaurant' => '₱1,000 - ₱2,000',
                'staff-house' => '₱500 - ₱1,500',
                'classrooms' => '₱300 - ₱800'
            ];

            $formattedFacilities = array_map(function($facility) use ($priceMap) {
                return [
                    'id' => $facility['id'],
                    'key' => $facility['facility_key'],
                    'facility_key' => $facility['facility_key'],
                    'name' => $facility['name'],
                    'title' => $facility['name'],
                    'icon' => $facility['icon'],
                    'description' => $facility['description'] ?? 'No description available',
                    'features' => ['Air Conditioned', 'Sound System', 'Projector'],
                    'price_range' => $priceMap[$facility['facility_key']] ?? 'Contact for pricing',
                    'is_active' => $facility['is_active'],
                    'is_maintenance' => $facility['is_maintenance']
                ];
            }, $facilities);

            return $this->response->setJSON([
                'success' => true,
                'data' => $formattedFacilities
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching external facilities: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch facilities'
            ])->setStatusCode(500);
        }
    }

    // Get facility gallery images
    public function getFacilityGallery($facilityKey = null)
    {
        try {
            if (!$facilityKey) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility key is required'
                ])->setStatusCode(400);
            }

            // Get facility by key
            $facility = $this->facilityModel
                ->where('facility_key', $facilityKey)
                ->first();

            if (!$facility) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility not found'
                ])->setStatusCode(404);
            }

            // Scan facility-specific uploads folder for gallery images
            $galleryDir = WRITEPATH . 'uploads/facilities/' . $facilityKey . '/';
            $gallery = [];

            if (is_dir($galleryDir)) {
                $files = array_diff(scandir($galleryDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $galleryDir . $file;
                    if (is_file($filePath)) {
                        // Generate a route-based URL with facility key
                        $gallery[] = [
                            'path' => base_url('api/facilities/image/' . $facilityKey . '/' . $file),
                            'name' => $file,
                            'size' => filesize($filePath)
                        ];
                    }
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'gallery' => $gallery,
                'facility_id' => $facility['id'],
                'facility_key' => $facility['facility_key']
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching gallery: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch gallery'
            ])->setStatusCode(500);
        }
    }

    // Serve gallery images
    public function getGalleryImage($facilityKey = null, $filename = null)
    {
        try {
            if (!$facilityKey || !$filename) {
                throw new \Exception('Facility key and filename are required');
            }

            // Security: prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || 
                strpos($facilityKey, '..') !== false || strpos($facilityKey, '/') !== false) {
                throw new \Exception('Invalid parameters');
            }

            $filePath = WRITEPATH . 'uploads/facilities/' . $facilityKey . '/' . $filename;

            if (!file_exists($filePath)) {
                throw new \Exception('File not found');
            }

            // Determine MIME type
            $mimeType = mime_content_type($filePath);
            
            // Return the file
            return $this->response
                ->setHeader('Content-Type', $mimeType)
                ->setHeader('Content-Length', filesize($filePath))
                ->setBody(file_get_contents($filePath));
        } catch (\Exception $e) {
            log_message('error', 'Error serving gallery image: ' . $e->getMessage());
            return $this->response->setStatusCode(404)->setBody('File not found');
        }
    }

    // Handle gallery image uploads
    protected function handleGalleryUploads($facilityId, $facilityKey)
    {
        $files = $this->request->getFiles();
        // Create facility-specific upload directory
        $uploadDir = WRITEPATH . 'uploads/facilities/' . $facilityKey . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (isset($files['gallery_images'])) {
            $images = $files['gallery_images'];
            
            // Handle single file or multiple files
            if (!is_array($images)) {
                $images = [$images];
            }

            foreach ($images as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    // Validate file
                    $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file->getMimeType(), $validMimes)) {
                        log_message('error', 'Invalid file type: ' . $file->getMimeType());
                        continue;
                    }

                    // Generate unique filename
                    $newName = $file->getRandomName();
                    
                    // Move file to facility-specific folder
                    if ($file->move($uploadDir, $newName)) {
                        $imagePath = 'uploads/facilities/' . $facilityKey . '/' . $newName;
                        log_message('info', 'Gallery image uploaded: ' . $imagePath);
                    } else {
                        log_message('error', 'Failed to move uploaded file: ' . $file->getErrorString());
                    }
                }
            }
        }
    }

    // Delete gallery image
    public function deleteGalleryImage($facilityKey = null, $filename = null)
    {
        try {
            if (!$facilityKey || !$filename) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Facility key and filename are required'
                ])->setStatusCode(400);
            }

            // Security: prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false ||
                strpos($facilityKey, '..') !== false || strpos($facilityKey, '/') !== false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid parameters'
                ])->setStatusCode(400);
            }

            $filePath = WRITEPATH . 'uploads/facilities/' . $facilityKey . '/' . $filename;

            if (!file_exists($filePath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'File not found'
                ])->setStatusCode(404);
            }

            // Delete the file
            if (unlink($filePath)) {
                log_message('info', 'Gallery image deleted: ' . $filename);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete image'
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting gallery image: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error deleting image: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
