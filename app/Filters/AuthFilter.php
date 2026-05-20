<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * AuthFilter - Authentication Check
 * 
 * This filter ensures the user is authenticated (logged in).
 * It does NOT handle authorization/role checking - that's done by RoleFilter.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            // For AJAX requests, return JSON response
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return service('response')
                    ->setJSON(['error' => 'Unauthorized access. Please log in.'])
                    ->setStatusCode(401);
            }
            
            // For regular requests, redirect to login
            return redirect()->to('login')->with('message', 'Please log in to continue');
        }

        // User is authenticated, authorization will be checked by RoleFilter if applied
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here if needed after the request
    }
}