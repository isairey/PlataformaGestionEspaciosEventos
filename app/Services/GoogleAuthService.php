<?php

namespace App\Services;

use Config\Google;
use Exception;

class GoogleAuthService
{
    protected Google $config;
    protected string $state;

    public function __construct()
    {
        $this->config = new Google();
    }

    /**
     * Generate the authorization URL for user login
     */
    public function getAuthorizationUrl(): string
    {
        $this->state = bin2hex(random_bytes(16));
        session()->set('google_oauth_state', $this->state);

        $params = [
            'client_id'     => $this->config->clientId,
            'redirect_uri'  => $this->config->redirectUri,
            'response_type' => 'code',
            'scope'         => implode(' ', $this->config->scopes),
            'state'         => $this->state,
            'access_type'   => 'online'
        ];

        return $this->config->authorizationEndpoint . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code, string $state): ?array
    {
        // Verify state parameter for security
        $sessionState = session()->get('google_oauth_state');
        if ($state !== $sessionState) {
            throw new Exception('Invalid state parameter');
        }

        $payload = [
            'client_id'     => $this->config->clientId,
            'client_secret' => $this->config->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->config->redirectUri,
            'grant_type'    => 'authorization_code'
        ];

        $ch = curl_init($this->config->tokenEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'Google token exchange failed: ' . $response);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get user information from Google
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $ch = curl_init($this->config->userInfoEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', 'Failed to fetch Google user info: ' . $response);
            return null;
        }

        $userData = json_decode($response, true);
        
        if (!$userData || !isset($userData['email'])) {
            log_message('error', 'Invalid Google user data received');
            return null;
        }

        return $userData;
    }

    /**
     * Handle Google login - Find or create user and log them in
     */
    public function handleLogin(array $googleUser): array
    {
        $userModel = new \App\Models\UserModel();
        $email = $googleUser['email'];

        // Check if user already exists
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            // Create new user from Google data
            $fullName = $googleUser['name'] ?? 'Google User';
            
            // Determine role based on email domain
            $role = 'user';
            if (str_ends_with(strtolower($email), '@my.cspc.edu.ph')) {
                $role = 'student';
            } elseif (str_ends_with(strtolower($email), '@cspc.edu.ph') && !str_ends_with(strtolower($email), '@my.cspc.edu.ph')) {
                $role = 'employee';
            }

            $userData = [
                'full_name'      => $fullName,
                'email'          => $email,
                'password'       => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Random password
                'contact_number' => '',
                'role'           => $role,
                'is_verified'    => 1, // Google-authenticated users are verified
                'google_id'      => $googleUser['id'] ?? null,
            ];

            try {
                $userModel->insert($userData);
                $user = $userModel->where('email', $email)->first();
                log_message('info', "New user created via Google OAuth: {$email}");
            } catch (\Exception $e) {
                log_message('error', 'Error creating user from Google: ' . $e->getMessage());
                throw $e;
            }
        } else {
            // Update user's Google ID if not already set
            if (!$user['google_id'] && isset($googleUser['id'])) {
                $userModel->update($user['id'], ['google_id' => $googleUser['id']]);
            }
        }

        return $user;
    }
}



