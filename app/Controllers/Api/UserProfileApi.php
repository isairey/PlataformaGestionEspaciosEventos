<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;

class UserProfileApi extends ResourceController
{
    protected $format = 'json';
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Get user profile
     */
    public function getProfile($userId)
    {
        // Verify authentication
        if (!session()->get('isLoggedIn')) {
            return $this->respond([
                'success' => false,
                'message' => 'Please log in to continue'
            ], 401);
        }

        // Verify authorization
        $sessionUserId = session()->get('user_id');
        if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->respond([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            unset($user['password']);

            return $this->respond([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching user profile: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to load profile'
            ], 500);
        }
    }

    /**
     * Update user profile
     */
public function updateProfile($userId)
{
    // Verify authentication
    if (!session()->get('isLoggedIn')) {
        return $this->respond([
            'success' => false,
            'message' => 'Please log in to continue'
        ], 401);
    }

    // Verify authorization
    $sessionUserId = session()->get('user_id');
    if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
        return $this->respond([
            'success' => false,
            'message' => 'Unauthorized access'
        ], 403);
    }

    try {
        $json = $this->request->getJSON(true);

        // Get current user data
        $currentUser = $this->userModel->find($userId);
        if (!$currentUser) {
            return $this->respond([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prepare update data
        $updateData = [];
        
        if (isset($json['full_name']) && !empty($json['full_name'])) {
            $updateData['full_name'] = trim($json['full_name']);
        }
        
        if (isset($json['email']) && !empty($json['email'])) {
            $newEmail = trim($json['email']);
            
            // Only validate email uniqueness if it's being changed
            if ($newEmail !== $currentUser['email']) {
                // Check if email already exists
                $existingUser = $this->userModel->where('email', $newEmail)
                                                 ->where('id !=', $userId)
                                                 ->first();
                if ($existingUser) {
                    return $this->respond([
                        'success' => false,
                        'message' => 'This email is already registered'
                    ], 400);
                }
            }
            
            $updateData['email'] = $newEmail;
        }
        
        if (isset($json['contact_number'])) {
            $updateData['contact_number'] = trim($json['contact_number']);
        }

        // Check if there's data to update
        if (empty($updateData)) {
            return $this->respond([
                'success' => false,
                'message' => 'No data to update'
            ], 400);
        }

        // Set validation rules to allow updating with same email
        $this->userModel->setValidationRule('email', 'valid_email|is_unique[users.email,id,' . $userId . ']');

        // Update user
        $result = $this->userModel->update($userId, $updateData);

        if ($result) {
            // Update session if user updated their own profile
            if ($sessionUserId == $userId) {
                if (isset($updateData['full_name'])) {
                    session()->set('full_name', $updateData['full_name']);
                }
                if (isset($updateData['email'])) {
                    session()->set('email', $updateData['email']);
                }
            }

            return $this->respond([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } else {
            $errors = $this->userModel->errors();
            
            return $this->respond([
                'success' => false,
                'message' => !empty($errors) ? implode(', ', $errors) : 'Failed to update profile'
            ], 400);
        }
    } catch (\Exception $e) {
        log_message('error', 'Profile update error: ' . $e->getMessage());
        return $this->respond([
            'success' => false,
            'message' => 'An error occurred while updating profile'
        ], 500);
    }
}

    /**
     * Change password
     */
    public function changePassword($userId)
    {
        // Verify authentication
        if (!session()->get('isLoggedIn')) {
            return $this->respond([
                'success' => false,
                'message' => 'Please log in to continue'
            ], 401);
        }

        // Verify authorization
        $sessionUserId = session()->get('user_id');
        if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['current_password']) || empty($data['new_password'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Current password and new password are required'
                ], 400);
            }

            // Get current user
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->respond([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Verify current password
            if (!password_verify($data['current_password'], $user['password'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Validate new password length
            if (strlen($data['new_password']) < 6) {
                return $this->respond([
                    'success' => false,
                    'message' => 'New password must be at least 6 characters long'
                ], 400);
            }

            // Update password
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $result = $this->userModel->update($userId, ['password' => $hashedPassword]);

            if ($result !== false) {
                log_message('info', "Password changed successfully for user {$userId}");
                return $this->respond([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                return $this->respond([
                    'success' => false,
                    'message' => 'Failed to change password'
                ], 500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error changing password: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred while changing password'
            ], 500);
        }
    }

    /**
     * Delete account
     */
    public function deleteAccount($userId)
    {
        // Verify authentication
        if (!session()->get('isLoggedIn')) {
            return $this->respond([
                'success' => false,
                'message' => 'Please log in to continue'
            ], 401);
        }

        // Verify authorization
        $sessionUserId = session()->get('user_id');
        if (!$sessionUserId || (int)$sessionUserId !== (int)$userId) {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['password']) || empty($data['delete_option'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Password and delete option are required'
                ], 400);
            }

            // Get current user
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->respond([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Incorrect password'
                ], 400);
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
                    // Return equipment to inventory
                    $bookingEquipment = $bookingEquipmentModel->where('booking_id', $booking['id'])->findAll();
                    foreach ($bookingEquipment as $equip) {
                        $equipmentModel->returnEquipment($equip['equipment_id'], $equip['quantity']);
                    }

                    // Delete physical files
                    $files = $bookingFileModel->where('booking_id', $booking['id'])->findAll();
                    foreach ($files as $file) {
                        if (file_exists($file['file_path'])) {
                            @unlink($file['file_path']);
                        }
                        $dir = dirname($file['file_path']);
                        if (is_dir($dir) && count(scandir($dir)) == 2) {
                            @rmdir($dir);
                        }
                    }

                    // Delete database records
                    $bookingFileModel->where('booking_id', $booking['id'])->delete();
                    $db->table('events')->where('booking_id', $booking['id'])->delete();
                    $bookingEquipmentModel->where('booking_id', $booking['id'])->delete();
                    $db->table('booking_addons')->where('booking_id', $booking['id'])->delete();
                    $bookingModel->delete($booking['id']);
                }

                // Delete user account
                $this->userModel->delete($userId);

                $db->transComplete();

                if ($db->transStatus() === false) {
                    return $this->respond([
                        'success' => false,
                        'message' => 'Failed to delete account and history'
                    ], 500);
                }

                $message = 'Your account and all booking history have been permanently deleted';
            } else {
                // DELETE PROFILE ONLY (Anonymize bookings)
                $db->transStart();

                $anonymousEmail = 'deleted_user_' . $userId . '_' . time() . '@system.local';
                $anonymousData = [
                    'client_name' => 'Deleted User',
                    'email_address' => $anonymousEmail,
                    'contact_number' => 'N/A',
                    'organization' => 'Deleted Account',
                    'address' => 'N/A'
                ];

                // Anonymize bookings
                $bookingModel->where('email_address', $user['email'])->set($anonymousData)->update();

                // Anonymize events
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
                    return $this->respond([
                        'success' => false,
                        'message' => 'Failed to delete profile'
                    ], 500);
                }

                $message = 'Your profile has been deleted. Booking history retained for administrative purposes';
            }

            // Destroy session
            session()->destroy();

            log_message('info', "User {$userId} ({$user['email']}) deleted account with option: {$data['delete_option']}");

            return $this->respond([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting account: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred while deleting account'
            ], 500);
        }
    }
}