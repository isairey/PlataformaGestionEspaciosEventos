<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter - Comprehensive role-based access control
 * 
 * Ensures users can only access routes appropriate for their role:
 * - Admin: Can access /admin routes only
 * - Employee: Can access /Employee routes only
 * - Facilitator: Can access /facilitator routes only
 * - Student: Can access /student routes and public booking pages
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Skip if not logged in (let AuthFilter handle this)
        if (!$session->get('isLoggedIn')) {
            return null;
        }
        
        $userRole = $session->get('role');
        $uri = $request->getUri();
        $path = $uri->getPath();
        
        // Check authorization based on role and path
        $authorizationCheck = $this->checkAuthorization($userRole, $path);
        
        if ($authorizationCheck === false) {
            // Log unauthorized access attempt
            log_message('warning', "Unauthorized access attempt by {$userRole} to {$path}");
            
            // For AJAX requests, return JSON error
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return service('response')
                    ->setJSON(['error' => 'Access denied. Insufficient permissions.'])
                    ->setStatusCode(403);
            }
            
            // Redirect to unauthorized page
            return redirect()->to('/unauthorized')->with('error', 'Access denied. You do not have permission to access this area.');
        }

        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
    
    /**
     * Check if user role is authorized to access the given path
     * 
     * @param string $role User's role
     * @param string $path Request path
     * @return bool|null true if allowed, false if denied, null for no specific rule
     */
    private function checkAuthorization($role, $path)
    {
        // Admin routes - only admin role
        if ($this->isAdminRoute($path)) {
            return $role === 'admin';
        }
        
        // Employee routes - only Employee role (admin can also access)
        if ($this->isEmployeeRoute($path)) {
            return in_array($role, ['Employee', 'admin']);
        }
        
        // Facilitator routes - only facilitator role (admin can also access)
        if ($this->isFacilitatorRoute($path)) {
            return in_array($role, ['facilitator', 'admin']);
        }
        
        // Student routes - student role or admin
        if ($this->isStudentRoute($path)) {
            return in_array($role, ['student', 'admin']);
        }
        
        // Public routes accessible by all authenticated users
        return null; // Allow access
    }
    
    /**
     * Check if path is an admin route
     */
    private function isAdminRoute($path)
    {
        return strpos($path, '/admin') === 0;
    }
    
    /**
     * Check if path is a Employee route
     */
    private function isEmployeeRoute($path)
    {
        return strpos($path, '/Employee') === 0;
    }
    
    /**
     * Check if path is a facilitator route
     */
    private function isFacilitatorRoute($path)
    {
        return strpos($path, '/facilitator') === 0;
    }
    
    /**
     * Check if path is a student route
     */
    private function isStudentRoute($path)
    {
        return strpos($path, '/student') === 0 || 
               strpos($path, '/booking') === 0 ||
               strpos($path, '/my-bookings') === 0;
    }
}


