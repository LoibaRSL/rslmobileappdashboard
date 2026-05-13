<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class WSO2Service
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $scopes;
    protected $authorizationUrl;
    protected $tokenUrl;
    protected $userInfoUrl;
    protected $logoutUrl;
    protected $postLogoutRedirectUri;
    protected $enableStateVerification;

    public function __construct()
    {
        $this->clientId = config('services.wso2.client_id');
        $this->clientSecret = config('services.wso2.client_secret');
        $this->redirectUri = config('services.wso2.redirect');
        $this->scopes = config('services.wso2.scopes', ['openid', 'profile', 'email']);
        $this->authorizationUrl = config('services.wso2.authorization_url');
        $this->tokenUrl = config('services.wso2.token_url');
        $this->userInfoUrl = config('services.wso2.userinfo_url');
        $this->logoutUrl = config('services.wso2.logout_url');
        $this->postLogoutRedirectUri = config('services.wso2.post_logout_redirect_uri');
        $this->enableStateVerification = env('WSO2_ENABLE_STATE_VERIFICATION', false);
    }

    /**
     * Get the authorization URL for redirecting to WSO2
     */
    public function getAuthorizationUrl(): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
        ];
        
        // WSO2 uses session_state parameter
        if ($this->enableStateVerification) {
            $params['session_state'] = $this->generateState();
        }

        Log::info('WSO2 Authorization URL generated', [
            'url' => $this->authorizationUrl,
            'has_session_state' => isset($params['session_state'])
        ]);

        return $this->authorizationUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$response->successful()) {
                Log::error('WSO2 Token Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WSO2 Token Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get user info from WSO2
     */
    public function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get($this->userInfoUrl);

            if (!$response->successful()) {
                Log::error('WSO2 UserInfo Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WSO2 UserInfo Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get logout URL
     */
    public function getLogoutUrl(string $idTokenHint = null): string
    {
        $params = [
            'post_logout_redirect_uri' => $this->postLogoutRedirectUri,
        ];

        if ($idTokenHint) {
            $params['id_token_hint'] = $idTokenHint;
        }

        return $this->logoutUrl . '?' . http_build_query($params);
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$response->successful()) {
                Log::error('WSO2 Refresh Token Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WSO2 Refresh Token Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate random session state parameter for CSRF protection
     */
    protected function generateState(): string
    {
        $state = bin2hex(random_bytes(32));
        session(['wso2_session_state' => $state]);
        Log::info('WSO2 Session state generated and stored in session', ['state_prefix' => substr($state, 0, 10)]);
        return $state;
    }

    /**
     * Verify session state parameter (WSO2 uses session_state)
     */
    public function verifyState(?string $sessionState = null): bool
    {
        // If state verification is disabled, always return true
        if (!$this->enableStateVerification) {
            Log::info('WSO2 Session state verification disabled, skipping check');
            return true;
        }
        
        // If no session state provided, fail verification
        if (is_null($sessionState)) {
            Log::warning('WSO2 Session state verification failed: No session_state parameter provided in callback');
            return false;
        }
        
        $savedState = session('wso2_session_state');
        session()->forget('wso2_session_state');
        
        // Check if saved state exists
        if (is_null($savedState)) {
            Log::warning('WSO2 Session state verification failed: No saved state found in session');
            return false;
        }
        
        // Verify state
        $isValid = hash_equals($savedState, $sessionState);
        
        if (!$isValid) {
            Log::warning('WSO2 Session state verification failed: State mismatch', [
                'received_prefix' => substr($sessionState, 0, 10),
                'saved_prefix' => substr($savedState, 0, 10)
            ]);
        } else {
            Log::info('WSO2 Session state verification successful');
        }
        
        return $isValid;
    }

    /**
     * Extract user data from WSO2 userinfo response
     */
    public function extractUserData(array $userInfo): array
    {
        return [
            'id' => $userInfo['sub'] ?? null,
            'name' => $userInfo['name'] ?? $userInfo['username'] ?? $userInfo['preferred_username'] ?? 'User',
            'email' => $userInfo['email'] ?? '',
            'username' => $userInfo['username'] ?? $userInfo['preferred_username'] ?? $userInfo['email'] ?? '',
            'department' => $userInfo['department'] ?? null,
        ];
    }
}