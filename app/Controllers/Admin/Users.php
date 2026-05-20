<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        // Check authentication and admin role
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/login');
        }
        
        return view('admin/users'); // load the correct users page
    }

    public function getUsers()
    {
        try {
            $users = $this->userModel->findAll();
            
            if ($users === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to fetch users',
                    'users' => []
                ])->setStatusCode(500);
            }
            
            // Remove passwords from response
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'users' => $users,
                'total' => count($users)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching users: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while fetching users',
                'users' => []
            ])->setStatusCode(500);
        }
    }

    public function add()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();

            // Validate required fields
            if (empty($data['email']) || empty($data['full_name']) || empty($data['role'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Email, full name, and role are required'
                ])->setStatusCode(400);
            }

            // Check if email already exists
            if ($this->userModel->where('email', $data['email'])->first()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Email already exists'
                ])->setStatusCode(400);
            }

            // Prepare insert data
            $insertData = [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'contact_number' => $data['contact_number'] ?? null
            ];

            // Add password if provided
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Password must be at least 6 characters'
                    ])->setStatusCode(400);
                }
                $insertData['password'] = $data['password']; // Model will hash it
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Password is required'
                ])->setStatusCode(400);
            }

            $userId = $this->userModel->insert($insertData);
            
            if ($userId) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User added successfully',
                    'user_id' => $userId
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to add user',
                    'errors' => $this->userModel->errors()
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error adding user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while adding the user',
                'error_details' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function update($id)
    {
        try {
            // Verify user exists
            $user = $this->userModel->find($id);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ])->setStatusCode(404);
            }

            // Get input data (handle both JSON and form data)
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            
            if (empty($data)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No data provided'
                ])->setStatusCode(400);
            }

            // Validate required fields
            if (empty($data['full_name']) || empty($data['email']) || empty($data['role'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Full name, email, and role are required'
                ])->setStatusCode(400);
            }

            // Check if email is already taken by another user
            $existingUser = $this->userModel
                ->where('email', $data['email'])
                ->where('id !=', $id)
                ->first();

            if ($existingUser) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Email address is already in use by another user'
                ])->setStatusCode(400);
            }

            // Prepare update data
            $updateData = [
                'full_name'      => $data['full_name'],
                'email'          => $data['email'],
                'role'           => $data['role'],
                'contact_number' => $data['contact_number'] ?? $user['contact_number']
            ];

            // Only update password if provided
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Password must be at least 6 characters'
                    ])->setStatusCode(400);
                }
                $updateData['password'] = $data['password']; // Model will hash it
            }

            // Set validation rules with current user ID to exclude from unique check
            $this->userModel->setValidationRules($this->userModel->getValidationRules(['id' => $id]));

            // Update user
            if ($this->userModel->update($id, $updateData)) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                $errors = $this->userModel->errors();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to update user',
                    'errors' => $errors
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error updating user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function delete($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ])->setStatusCode(400);
            }
            
            // Check if user exists
            $user = $this->userModel->find($id);
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ])->setStatusCode(404);
            }
            
            // Prevent self-deletion if this is the current logged-in admin
            if (session()->get('user_id') == $id) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ])->setStatusCode(400);
            }
            
            $result = $this->userModel->delete($id);
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete user'
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while deleting the user'
            ])->setStatusCode(500);
        }
    }

    public function view($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ])->setStatusCode(400);
            }
            
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found'
                ])->setStatusCode(404);
            }
            
            // Remove sensitive information
            unset($user['password']);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error viewing user: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while fetching user details'
            ])->setStatusCode(500);
        }
    }
}