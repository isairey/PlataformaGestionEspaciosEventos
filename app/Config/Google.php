<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Google extends BaseConfig
{
    /**
     * Google OAuth 2.0 Configuration
     */

    /**
     * Google OAuth Client ID
     * Get this from Google Cloud Console: https://console.cloud.google.com/
     */
    public string $clientId = '';

    /**
     * Google OAuth Client Secret
     * Get this from Google Cloud Console
     */
    public string $clientSecret = '';

    /**
     * Redirect URI for OAuth callback
     * Must match the one configured in Google Cloud Console
     */
    public string $redirectUri = '';

    /**
     * Google OAuth Authorization endpoint
     */
    public string $authorizationEndpoint = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * Google OAuth Token endpoint
     */
    public string $tokenEndpoint = 'https://www.googleapis.com/oauth2/v4/token';

    /**
     * Google User info endpoint
     */
    public string $userInfoEndpoint = 'https://www.googleapis.com/oauth2/v1/userinfo';

    /**
     * OAuth Scopes required
     */
    public array $scopes = [
        'openid',
        'email',
        'profile'
    ];

    /**
     * Constructor - Load from environment variables
     */
    public function __construct()
    {
        // Load credentials from .env file
        $this->clientId = env('GOOGLE_CLIENT_ID', '');
        $this->clientSecret = env('GOOGLE_CLIENT_SECRET', '');
        $this->redirectUri = env('GOOGLE_REDIRECT_URI', 'http://localhost:8080/google/callback');
    }
}
