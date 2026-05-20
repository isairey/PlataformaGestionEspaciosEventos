<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PlanModel;
use App\Models\PlanFeaturesModel;
use App\Models\PlanEquipmentModel;
use App\Models\AddonModel;
use App\Models\FacilityModel;
use App\Models\EquipmentModel;
use App\Models\SettingModel;
use CodeIgniter\HTTP\ResponseInterface;

class Plans extends BaseController
{
    protected $plansModel;
    protected $planFeaturesModel;
    protected $planEquipmentModel;
    protected $addonsModel;
    protected $facilityModel;
    protected $equipmentModel;
    protected $settingModel;

    public function __construct()
    {
        $this->plansModel = new PlanModel();
        $this->planFeaturesModel = new PlanFeaturesModel();
        $this->planEquipmentModel = new PlanEquipmentModel();
        $this->addonsModel = new AddonModel();
        $this->facilityModel = new FacilityModel();
        $this->equipmentModel = new EquipmentModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Display plans management page
     */
    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Plans Management'
        ];

        return view('admin/plans', $data);
    }

    /**
     * Get all plans with facility info (AJAX)
     */
    public function getPlans()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $plans = $this->plansModel
                ->select('plans.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = plans.facility_id', 'left')
                ->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $plans
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Plans fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch plans'
            ]);
        }
    }

    /**
     * Get plan details with features and equipment (AJAX)
     */
    public function getPlanDetails($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $plan = $this->plansModel
                ->select('plans.*, facilities.name as facility_name')
                ->join('facilities', 'facilities.id = plans.facility_id', 'left')
                ->find($id);

            if (!$plan) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Plan not found'
                ]);
            }

            // Get features
            $features = $this->planFeaturesModel
                ->where('plan_id', $id)
                ->orderBy('display_order', 'ASC')
                ->findAll();

            // Get equipment with details
            $equipment = $this->planEquipmentModel
                ->select('plan_equipment.*, equipment.name as equipment_name, equipment.rate')
                ->join('equipment', 'equipment.id = plan_equipment.equipment_id', 'left')
                ->where('plan_equipment.plan_id', $id)
                ->findAll();

            $plan['features'] = $features;
            $plan['equipment'] = $equipment;

            return $this->response->setJSON([
                'success' => true,
                'data' => $plan
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Plan details fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch plan details'
            ]);
        }
    }

    /**
     * Add new plan (AJAX)
     */
    public function addPlan()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $inputData = $this->request->getJSON(true);

        $validation = \Config\Services::validation();
        $validation->setRules([
            'facility_id' => 'required|integer',
            'plan_key' => 'required|min_length[3]|max_length[50]',
            'name' => 'required|min_length[3]|max_length[255]',
            'duration' => 'required|max_length[50]',
            'price' => 'required|numeric|greater_than_equal_to[0]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // Insert plan
            $planData = [
                'facility_id' => $inputData['facility_id'],
                'plan_key' => $inputData['plan_key'],
                'name' => $inputData['name'],
                'duration' => $inputData['duration'],
                'price' => $inputData['price']
            ];

            $planId = $this->plansModel->insert($planData);

            // Insert features
            if (!empty($inputData['features'])) {
                foreach ($inputData['features'] as $index => $feature) {
                    $this->planFeaturesModel->insert([
                        'plan_id' => $planId,
                        'feature' => $feature['feature'],
                        'feature_type' => $feature['feature_type'] ?? 'amenity',
                        'is_physical' => $feature['is_physical'] ?? 0,
                        'display_order' => $index + 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Insert equipment
            if (!empty($inputData['equipment'])) {
                foreach ($inputData['equipment'] as $equip) {
                    $this->planEquipmentModel->insert([
                        'plan_id' => $planId,
                        'equipment_id' => $equip['equipment_id'],
                        'quantity_included' => $equip['quantity_included'] ?? 1,
                        'is_mandatory' => $equip['is_mandatory'] ?? 1,
                        'additional_rate' => $equip['additional_rate'] ?? null
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to add plan'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Plan added successfully',
                'data' => $this->plansModel->find($planId)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Plan add error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while adding plan'
            ]);
        }
    }

    /**
     * Update plan (AJAX)
     */
    public function updatePlan()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $inputData = $this->request->getJSON(true);

            if (!$inputData) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'No data received'
                ]);
            }

            $id = $inputData['id'] ?? null;

            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Plan ID is required'
                ]);
            }

            $plan = $this->plansModel->find($id);

            if (!$plan) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Plan not found'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Update plan
            $planData = [
                'facility_id' => $inputData['facility_id'],
                'plan_key' => $inputData['plan_key'],
                'name' => $inputData['name'],
                'duration' => $inputData['duration'],
                'price' => $inputData['price']
            ];

            $this->plansModel->update($id, $planData);

            // Delete and re-insert features
            $this->planFeaturesModel->where('plan_id', $id)->delete();
            if (!empty($inputData['features'])) {
                foreach ($inputData['features'] as $index => $feature) {
                    $this->planFeaturesModel->insert([
                        'plan_id' => $id,
                        'feature' => $feature['feature'],
                        'feature_type' => $feature['feature_type'] ?? 'amenity',
                        'is_physical' => $feature['is_physical'] ?? 0,
                        'display_order' => $index + 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Delete and re-insert equipment
            $this->planEquipmentModel->where('plan_id', $id)->delete();
            if (!empty($inputData['equipment'])) {
                foreach ($inputData['equipment'] as $equip) {
                    $this->planEquipmentModel->insert([
                        'plan_id' => $id,
                        'equipment_id' => $equip['equipment_id'],
                        'quantity_included' => $equip['quantity_included'] ?? 1,
                        'is_mandatory' => $equip['is_mandatory'] ?? 1,
                        'additional_rate' => $equip['additional_rate'] ?? null
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to update plan'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Plan updated successfully',
                'data' => $this->plansModel->find($id)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Plan update error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating plan: ' . $e->getMessage(),
                'trace' => ENVIRONMENT === 'development' ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * Delete plan (AJAX)
     */
    public function deletePlan($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $plan = $this->plansModel->find($id);

            if (!$plan) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Plan not found'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Delete features and equipment first
            $this->planFeaturesModel->where('plan_id', $id)->delete();
            $this->planEquipmentModel->where('plan_id', $id)->delete();

            // Delete plan
            $this->plansModel->delete($id);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete plan'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Plan deleted successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Plan delete error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while deleting plan'
            ]);
        }
    }

    /**
     * Get all facilities for dropdown (AJAX)
     */
    public function getFacilities()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $facilities = $this->facilityModel->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $facilities
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Facilities fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch facilities'
            ]);
        }
    }

    /**
     * Get all equipment for dropdown (AJAX)
     */
    public function getEquipmentList()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $equipment = $this->equipmentModel->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $equipment
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Equipment fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch equipment'
            ]);
        }
    }

    /**
     * Get all addons (AJAX)
     */
    public function getAddons()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $addons = $this->addonsModel->findAll();

            return $this->response->setJSON([
                'success' => true,
                'data' => $addons
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Addons fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch addons'
            ]);
        }
    }

    /**
     * Add new addon (AJAX)
     */
    public function addAddon()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $inputData = $this->request->getJSON(true);

        $validation = \Config\Services::validation();
        $validation->setRules([
            'addon_key' => 'required|min_length[3]|max_length[50]|is_unique[addons.addon_key]',
            'name' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty',
            'price' => 'required|numeric|greater_than_equal_to[0]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        try {
            $addonData = [
                'addon_key' => $inputData['addon_key'],
                'name' => $inputData['name'],
                'description' => $inputData['description'] ?? null,
                'price' => $inputData['price']
            ];

            $addonId = $this->addonsModel->insert($addonData);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Additional service added successfully',
                'data' => $this->addonsModel->find($addonId)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Addon add error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while adding service'
            ]);
        }
    }

    /**
     * Update addon (AJAX)
     */
    public function updateAddon()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $inputData = $this->request->getJSON(true);

            $id = $inputData['id'] ?? null;
            if (!$id) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Addon ID is required'
                ]);
            }

            $addon = $this->addonsModel->find($id);
            if (!$addon) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Service not found'
                ]);
            }

            $addonData = [
                'addon_key' => $inputData['addon_key'],
                'name' => $inputData['name'],
                'description' => $inputData['description'] ?? null,
                'price' => $inputData['price']
            ];

            $this->addonsModel->update($id, $addonData);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Additional service updated successfully',
                'data' => $this->addonsModel->find($id)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Addon update error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating service'
            ]);
        }
    }

    /**
     * Delete addon (AJAX)
     */
    public function deleteAddon($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $addon = $this->addonsModel->find($id);

            if (!$addon) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Service not found'
                ]);
            }

            $this->addonsModel->delete($id);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Additional service deleted successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Addon delete error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while deleting service'
            ]);
        }
    }

    /**
     * Get system settings (overtime rate, maintenance fee, extended hours rate)
     */
    public function getSettings()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            // Get settings from settings table
            $settings = [
                'overtime_rate' => $this->settingModel->getValue('overtime_rate', 5000.00),
                'maintenance_fee' => $this->settingModel->getValue('maintenance_fee', 2000.00),
                'extended_hours_rate' => $this->settingModel->getValue('extended_hours_rate', 500.00)
            ];

            // Also try to get from addons table for backwards compatibility
            $overtimeAddon = $this->addonsModel->where('addon_key', 'overtime')->first();
            if ($overtimeAddon) {
                $settings['overtime_rate'] = $overtimeAddon['price'];
            }

            $additionalHoursAddon = $this->addonsModel->where('addon_key', 'additional-hours')->first();
            if ($additionalHoursAddon) {
                $settings['extended_hours_rate'] = $additionalHoursAddon['price'];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Settings fetch error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch settings'
            ]);
        }
    }

    /**
     * Update system settings
     */
    public function updateSettings()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $inputData = $this->request->getJSON(true);

            // Update overtime rate - save to both settings table and addons for compatibility
            if (isset($inputData['overtime_rate'])) {
                $this->settingModel->setValue('overtime_rate', $inputData['overtime_rate']);

                $overtimeAddon = $this->addonsModel->where('addon_key', 'overtime')->first();
                if ($overtimeAddon) {
                    $this->addonsModel->update($overtimeAddon['id'], ['price' => $inputData['overtime_rate']]);
                }
            }

            // Update extended hours rate - save to both settings table and addons for compatibility
            if (isset($inputData['extended_hours_rate'])) {
                $this->settingModel->setValue('extended_hours_rate', $inputData['extended_hours_rate']);

                $additionalHoursAddon = $this->addonsModel->where('addon_key', 'additional-hours')->first();
                if ($additionalHoursAddon) {
                    $this->addonsModel->update($additionalHoursAddon['id'], ['price' => $inputData['extended_hours_rate']]);
                }
            }

            // Update maintenance fee - save to settings table
            if (isset($inputData['maintenance_fee'])) {
                $this->settingModel->setValue('maintenance_fee', $inputData['maintenance_fee']);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Settings update error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating settings'
            ]);
        }
    }

    /**
     * Update facility additional hours rate (AJAX)
     */
    public function updateFacilityRate()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            $inputData = $this->request->getJSON(true);

            $facilityId = $inputData['facility_id'] ?? null;
            $rate = $inputData['additional_hours_rate'] ?? null;

            if (!$facilityId || $rate === null) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Facility ID and rate are required'
                ]);
            }

            $facility = $this->facilityModel->find($facilityId);
            if (!$facility) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Facility not found'
                ]);
            }

            $this->facilityModel->update($facilityId, [
                'additional_hours_rate' => $rate
            ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Facility rate updated successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Facility rate update error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating facility rate'
            ]);
        }
    }
}
