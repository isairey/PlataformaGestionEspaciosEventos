<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class User extends BaseController
{

protected $userModel;

public function __construct()
{
    $this->userModel = new \App\Models\UserModel();
}
    public function dashboard()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        // Check if user is not admin (regular user)
        if (session()->get('role') === 'admin') {
            return redirect()->to('/admin');
        }
        
        $data = [
            'title' => 'Dashboard',
            'userEmail' => session()->get('email')
        ];
        
        return view('user/dashboard', $data);
    }
    
    public function bookings()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        // Check if user is not admin (regular user)
        if (session()->get('role') === 'admin') {
            return redirect()->to('/admin');
        }
        
        $data = [
            'title' => 'My Bookings',
            'userEmail' => session()->get('email')
        ];
        
        return view('user/bookings', $data);
    }
    
    public function profile()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
        
        $data = [
            'title' => 'Profile',
            'userEmail' => session()->get('email')
        ];
        
        return view('user/profile', $data);
    }
    
public function getProfile($userId)
{
    $this->response->setContentType('application/json');
    
    if (!session()->get('isLoggedIn')) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Please log in to continue'
        ])->setStatusCode(401);
    }

    $sessionUserId = session()->get('user_id');
    if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized access'
        ])->setStatusCode(403);
    }
    
    try {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ])->setStatusCode(404);
        }
        
        unset($user['password']);
        
        return $this->response->setJSON([
            'success' => true,
            'user' => $user
        ]);
    } catch (\Exception $e) {
        log_message('error', 'Error fetching user profile: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to load profile'
        ])->setStatusCode(500);
    }
}
    

    
public function updateProfile($userId)
{
    // Enable PUT request handling
    $request = $this->request;
    
    // Get JSON data from request body
    $json = $request->getJSON(true); // true converts to array
    
    // If JSON is empty, try getting from raw input
    if (empty($json)) {
        $json = json_decode($request->getBody(), true);
    }
    
    // Validate user authorization
    $session = session();
    if ($session->get('user_id') != $userId && $session->get('role') !== 'admin') {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized access'
        ])->setStatusCode(403);
    }
    
    try {
        $userModel = new \App\Models\UserModel();
        
        // Prepare update data
        $updateData = [];
        if (isset($json['full_name'])) $updateData['full_name'] = $json['full_name'];
        if (isset($json['email'])) $updateData['email'] = $json['email'];
        if (isset($json['contact_number'])) $updateData['contact_number'] = $json['contact_number'];
        
        // Update user
        if ($userModel->update($userId, $updateData)) {
            // Update session if user updated their own profile
            if ($session->get('user_id') == $userId) {
                $session->set('full_name', $updateData['full_name'] ?? $session->get('full_name'));
            }
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update profile'
            ])->setStatusCode(500);
        }
        
    } catch (\Exception $e) {
        log_message('error', 'Profile update error: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'An error occurred while updating profile'
        ])->setStatusCode(500);
    }
}

public function changePassword($userId)
{
    // Set JSON response header FIRST
    $this->response->setContentType('application/json');
    
    // Verify user is logged in and changing their own password
    if (!session()->get('isLoggedIn')) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Please log in to continue'
        ])->setStatusCode(401);
    }

    $sessionUserId = session()->get('user_id');
    log_message('debug', "Session user_id: " . var_export($sessionUserId, true) . ", Requested userId: {$userId}");

    if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
        log_message('error', "Authorization failed - Session ID: {$sessionUserId}, Requested ID: {$userId}");
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized access'
        ])->setStatusCode(403);
    }
    
    try {
        $data = $this->request->getJSON(true);
        
        // Debug log
        log_message('debug', 'Change password request data: ' . json_encode($data));
        
        // Validate required fields - FIXED: Check for password fields, not profile fields
        if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password and new password are required'
            ])->setStatusCode(400);
        }
        
        // Get current user
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ])->setStatusCode(404);
        }
        
        // Verify current password
        if (!password_verify($data['current_password'], $user['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password is incorrect'
            ])->setStatusCode(400);
        }
        
        // Validate new password length
        if (strlen($data['new_password']) < 6) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'New password must be at least 6 characters long'
            ])->setStatusCode(400);
        }
        
        // Update password
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $result = $this->userModel->update($userId, ['password' => $hashedPassword]);
        
        if ($result !== false) {
            log_message('info', "Password changed successfully for user {$userId}");
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Password changed successfully'
            ])->setStatusCode(200);
        } else {
            log_message('error', "Password change failed for user {$userId}");
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to change password'
            ])->setStatusCode(500);
        }
    } catch (\Exception $e) {
        log_message('error', 'Error changing password: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'An error occurred while changing password'
        ])->setStatusCode(500);
    }
}

    public function deleteAccount($userId)
{
    // Verify user is logged in and deleting their own account
if (!session()->get('isLoggedIn')) {
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Please log in to continue'
    ])->setStatusCode(401);
}

// Check if user is accessing their own profile
$sessionUserId = session()->get('user_id');
if (!$sessionUserId || $sessionUserId != $userId) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized access'
        ])->setStatusCode(403);
    }
    
    try {
        $data = $this->request->getJSON(true);
        
        // Validate required fields
        if (empty($data['password']) || empty($data['delete_option'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password and delete option are required'
            ]);
        }
        
        // Get current user
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ])->setStatusCode(404);
        }
        
        // Verify password
        if (!password_verify($data['password'], $user['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Incorrect password'
            ]);
        }
        
        $bookingModel = new \App\Models\BookingModel();
        $bookingFileModel = new \App\Models\BookingFileModel();
        $bookingEquipmentModel = new \App\Models\BookingEquipmentModel();
        $equipmentModel = new \App\Models\EquipmentModel();
        $db = \Config\Database::connect();
        
        if ($data['delete_option'] === 'everything') {
            // DELETE EVERYTHING OPTION
            $db->transStart();
            
            // Get user's bookings by email
            $userBookings = $bookingModel->where('email_address', $user['email'])->findAll();
            
            foreach ($userBookings as $booking) {
                // Step 1: Return rented equipment to inventory
                $bookingEquipment = $bookingEquipmentModel->where('booking_id', $booking['id'])->findAll();
                foreach ($bookingEquipment as $equip) {
                    $equipmentModel->returnEquipment($equip['equipment_id'], $equip['quantity']);
                }
                
                // Step 2: Delete physical files from filesystem
                $files = $bookingFileModel->where('booking_id', $booking['id'])->findAll();
                foreach ($files as $file) {
                    if (file_exists($file['file_path'])) {
                        @unlink($file['file_path']);
                    }
                    // Remove empty directory
                    $dir = dirname($file['file_path']);
                    if (is_dir($dir) && count(scandir($dir)) == 2) {
                        @rmdir($dir);
                    }
                }
                
                // Step 3: Delete database records
                $bookingFileModel->where('booking_id', $booking['id'])->delete();
                $db->table('events')->where('booking_id', $booking['id'])->delete();
                $bookingEquipmentModel->where('booking_id', $booking['id'])->delete();
                $db->table('booking_addons')->where('booking_id', $booking['id'])->delete();
                $bookingModel->delete($booking['id']);
            }
            
            // Step 4: Delete user account
            $this->userModel->delete($userId);
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete account and history'
                ]);
            }
            
            $message = 'Your account and all booking history have been permanently deleted';
            
        } else {
            // DELETE PROFILE ONLY OPTION (Anonymize bookings)
            $db->transStart();
            
            // Create unique anonymous email
            $anonymousEmail = 'deleted_user_' . $userId . '_' . time() . '@system.local';
            $anonymousData = [
                'client_name' => 'Deleted User',
                'email_address' => $anonymousEmail,
                'contact_number' => 'N/A',
                'organization' => 'Deleted Account',
                'address' => 'N/A'
            ];
            
            // Anonymize bookings
            $bookingModel->where('email_address', $user['email'])
                        ->set($anonymousData)
                        ->update();
            
            // Anonymize events table as well
            $db->table('events')
               ->where('email_address', $user['email'])
               ->update([
                   'client_name' => 'Deleted User',
                   'email_address' => $anonymousEmail,
                   'contact_number' => 'N/A',
                   'organization' => 'Deleted Account'
               ]);
            
            // Delete user account
            $this->userModel->delete($userId);
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete profile'
                ]);
            }
            
            $message = 'Your profile has been deleted. Booking history retained for administrative purposes';
        }
        
        // Destroy session
        session()->destroy();
        
        // Log the deletion
        log_message('info', "User {$userId} ({$user['email']}) deleted account with option: {$data['delete_option']}");
        
        return $this->response->setJSON([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error deleting account: ' . $e->getMessage());
        return $this->response->setJSON([
            'success' => false,
            'message' => 'An error occurred while deleting account'
        ])->setStatusCode(500);
    }
}
}