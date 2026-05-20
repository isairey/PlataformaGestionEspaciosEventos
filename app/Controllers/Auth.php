<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        return view('auth/login');
    }

public function register()
{
    $validation = \Config\Services::validation();

    $rules = [
        'full_name'       => 'required|min_length[3]',
        'contact_number'  => 'required|min_length[6]',
        'email'           => 'required|valid_email|is_unique[users.email]',
        'password'        => 'required|min_length[6]',
        'confirm_password'=> 'required|matches[password]',
    ];

    if (!$this->validate($rules)) {
        $errors = $validation->getErrors();
        
        // Provide user-friendly error messages
        if (isset($errors['email'])) {
            if (strpos($errors['email'], 'is_unique') !== false) {
                return redirect()->back()->withInput()->with('error', 'An account with this email already exists. Please log in or use a different email address.');
            } elseif (strpos($errors['email'], 'valid_email') !== false) {
                return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
            }
        }
        
        if (isset($errors['password'])) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters long.');
        }
        
        if (isset($errors['confirm_password'])) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match. Please ensure both password fields are identical.');
        }
        
        if (isset($errors['full_name'])) {
            return redirect()->back()->withInput()->with('error', 'Please enter your full name (minimum 3 characters).');
        }
        
        if (isset($errors['contact_number'])) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid contact number.');
        }
        
        // Fallback for other errors
        return redirect()->back()->withInput()->with('error', implode(' ', $validation->getErrors()));
    }

    $userModel = new \App\Models\UserModel();
    $email = $this->request->getPost('email');

    // Auto-assign role based on email domain
    $role = 'user'; // Default role

    // Check for student email (must be @my.cspc.edu.ph)
    if (str_ends_with(strtolower($email), '@my.cspc.edu.ph')) {
        $role = 'student';
    }
    // Check for employee email (must be @cspc.edu.ph but NOT @my.cspc.edu.ph)
    elseif (str_ends_with(strtolower($email), '@cspc.edu.ph') && !str_ends_with(strtolower($email), '@my.cspc.edu.ph')) {
        $role = 'employee';
    }

    $userData = [
        'full_name'      => $this->request->getPost('full_name'),
        'contact_number' => $this->request->getPost('contact_number'),
        'email'          => $email,
        'password'       => $this->request->getPost('password'), // Model will hash it
        'role'           => $role,
    ];

    try {
        if ($userModel->insert($userData)) {
            $roleNames = [
                'student' => 'Student',
                'employee' => 'Employee',
                'user' => 'User'
            ];
            $roleText = $roleNames[$role] ?? 'User';
            return redirect()->to('login')->with('success', "🎉 Account created successfully as {$roleText}! You can now log in with your credentials.");
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to create account. Please try again or contact support if the problem persists.');
        }
    } catch (\Exception $e) {
        log_message('error', 'Registration error: ' . $e->getMessage());
        
        // Check for specific database errors
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return redirect()->back()->withInput()->with('error', 'An account with this email already exists. Please use a different email or try logging in.');
        }
        
        return redirect()->back()->withInput()->with('error', 'An unexpected error occurred during registration. Please try again later.');
    }
}

public function attemptLogin()
{
    $session = session();
    $model = new UserModel();

    $email = $this->request->getPost('email');
    $password = $this->request->getPost('password');

    // Validate input
    if (empty($email) || empty($password)) {
        return redirect()->back()->with('error', 'Please enter both email/student ID and password.');
    }

    // Find user by email (or student ID for students)
    $user = $model->where('email', $email)->first();
    
    // If not found by email, try searching by student ID (if email looks like a student ID)
    if (!$user && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Search for student with this ID pattern
        $user = $model->like('email', $email, 'after')
                     ->where('role', 'student')
                     ->first();
    }

    if (!$user) {
        log_message('warning', "Failed login attempt for: {$email} - User not found");
        return redirect()->back()->with('error', 'No account found with this email or student ID. Please check your credentials or sign up for a new account.');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        log_message('warning', "Failed login attempt for: {$email} - Incorrect password");
        return redirect()->back()->with('error', 'Incorrect password. Please try again or contact support if you\'ve forgotten your password.');
    }

    // Check if account is active (if you have status field)
    if (isset($user['status']) && $user['status'] === 'suspended') {
        return redirect()->back()->with('error', 'Your account has been suspended. Please contact the administrator for assistance.');
    }

    if (isset($user['status']) && $user['status'] === 'inactive') {
        return redirect()->back()->with('warning', 'Your account is inactive. Please contact the administrator to activate your account.');
    }

    // Check if account needs verification (if you have is_verified field)
    if (isset($user['is_verified']) && !$user['is_verified']) {
        return redirect()->back()->with('warning', 'Your account is pending verification. Please check your email or contact the administrator.');
    }

    // Set session data
    $session->set([
        'user_id'        => $user['id'],
        'email'          => $user['email'],
        'full_name'      => $user['full_name'],
        'contact_number' => $user['contact_number'], 
        'role'           => $user['role'],
        'isLoggedIn'     => true
    ]);

    // Log successful login
    log_message('info', "User {$user['email']} logged in successfully as {$user['role']}");

    // Success message based on role
    $roleNames = [
        'admin' => 'Administrator',
        'student' => 'Student',
        'facilitator' => 'Facilitator',
        'employee' => 'Employee',
        'user' => 'User'
    ];

    $roleName = $roleNames[$user['role']] ?? 'User';
    $session->setFlashdata('success', "Welcome back, {$user['full_name']}! Logged in as {$roleName}.");

    // Redirect based on role
    switch ($user['role']) {
        case 'admin':
            return redirect()->to('admin');
        case 'student':
            return redirect()->to('student/dashboard');
        case 'facilitator':
            return redirect()->to('facilitator');
        case 'employee':
            return redirect()->to('employee/dashboard');
        default:
            return redirect()->to('user/dashboard');
    }
}

public function logout()
{
    $userName = session()->get('full_name') ?? 'User';
    
    // Set flag for back button handling
    session()->setFlashdata('justLoggedOut', true);
    session()->destroy();
    
    return redirect()->to('/')->with('info', "Goodbye, {$userName}! You have been logged out successfully. See you next time! 👋");
}

/**
 * Redirect user to Google OAuth consent screen
 */
public function googleLogin()
{
    $googleService = new \App\Services\GoogleAuthService();
    $authUrl = $googleService->getAuthorizationUrl();
    return redirect()->to($authUrl);
}

/**
 * Handle Google OAuth callback
 */
public function googleCallback()
{
    $code = $this->request->getGet('code');
    $state = $this->request->getGet('state');
    $error = $this->request->getGet('error');

    // Check for errors from Google
    if ($error) {
        $errorDesc = $this->request->getGet('error_description') ?? 'Unknown error occurred';
        log_message('warning', "Google OAuth error: {$error} - {$errorDesc}");
        return redirect()->to('login')->with('error', 'Google login was cancelled or failed. Please try again.');
    }

    // Check if authorization code is present
    if (!$code) {
        log_message('warning', 'Google callback received without authorization code');
        return redirect()->to('login')->with('error', 'Invalid authorization code from Google.');
    }

    try {
        $googleService = new \App\Services\GoogleAuthService();
        
        // Exchange code for access token
        $tokenData = $googleService->getAccessToken($code, $state);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            return redirect()->to('login')->with('error', 'Failed to obtain access token from Google.');
        }

        // Get user information from Google
        $googleUser = $googleService->getUserInfo($tokenData['access_token']);
        if (!$googleUser) {
            return redirect()->to('login')->with('error', 'Failed to retrieve your Google profile information.');
        }

        // Handle login - create user if doesn't exist
        $user = $googleService->handleLogin($googleUser);

        // Check if account is active
        if (isset($user['status']) && $user['status'] === 'suspended') {
            return redirect()->to('login')->with('error', 'Your account has been suspended. Please contact the administrator for assistance.');
        }

        if (isset($user['status']) && $user['status'] === 'inactive') {
            return redirect()->to('login')->with('warning', 'Your account is inactive. Please contact the administrator to activate your account.');
        }

        // Set session data
        $session = session();
        $session->set([
            'user_id'        => $user['id'],
            'email'          => $user['email'],
            'full_name'      => $user['full_name'],
            'contact_number' => $user['contact_number'],
            'role'           => $user['role'],
            'isLoggedIn'     => true
        ]);

        // Log successful login
        log_message('info', "User {$user['email']} logged in successfully via Google OAuth as {$user['role']}");

        // Success message based on role
        $roleNames = [
            'admin' => 'Administrator',
            'student' => 'Student',
            'facilitator' => 'Facilitator',
            'employee' => 'Employee',
            'user' => 'User'
        ];

        $roleName = $roleNames[$user['role']] ?? 'User';
        $session->setFlashdata('success', "Welcome back, {$user['full_name']}! Logged in as {$roleName}.");

        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                return redirect()->to('/admin');
            case 'student':
                return redirect()->to('/student/dashboard');
            case 'facilitator':
                return redirect()->to('/facilitator');
            case 'employee':
                return redirect()->to('/employee/dashboard');
            default:
                return redirect()->to('/user/dashboard');
        }

    } catch (\Exception $e) {
        log_message('error', 'Google OAuth callback error: ' . $e->getMessage());
        return redirect()->to('login')->with('error', 'An unexpected error occurred during Google login. Please try again.');
    }
}
}