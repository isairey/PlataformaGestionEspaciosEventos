<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    protected $allowedFields = [
        'full_name',
        'email',
        'password',
        'contact_number',
        'role',
        'google_id',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation rules for INSERT
    protected $validationRules = [
        'full_name' => 'required|min_length[3]|max_length[50]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'role' => 'permit_empty|in_list[student,facilitator,employee,user,admin]'
    ];

    protected $validationMessages = [
        'full_name' => [
            'required' => 'Full name is required',
            'min_length' => 'Full name must be at least 3 characters long'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please provide a valid email address',
            'is_unique' => 'This email is already registered'
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 6 characters long'
        ],
        'role' => [
            'in_list' => 'Invalid role selected'
        ]
    ];

    // Password hashing callbacks
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPasswordOnUpdate'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            // Only hash if not already hashed (length check)
            if (strlen($data['data']['password']) < 60) {
                $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            }
        }
        return $data;
    }

    protected function hashPasswordOnUpdate(array $data)
    {
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            // Only hash if not already hashed
            if (strlen($data['data']['password']) < 60) {
                $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            }
        } else {
            // Remove password field if empty (don't update it)
            unset($data['data']['password']);
        }
        return $data;
    }

    public function getValidationRules(array $options = []): array
    {
        $rules = $this->validationRules;
        
        // If updating, modify email validation to exclude current user
        if (isset($options['id'])) {
            $rules['email'] = "required|valid_email|is_unique[users.email,id,{$options['id']}]";
            // Make password optional for updates
            $rules['password'] = 'permit_empty|min_length[6]';
        }
        
        return $rules;
    }
}